<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use App\Models\Ahj;
use App\Models\Project;
use Carbon\Carbon;

class DataIntegrityCheck extends Command
{
    protected $signature = 'data:integrity-check';
    protected $description = 'Scan database for data integrity issues and generate a report';

    private $issues = [
        'critical' => [],
        'warning' => [],
        'info' => []
    ];

    private $totalProjects = 0;
    private $ahjBreakdown = [];

    public function handle(): int
    {
        $this->info('Starting data integrity check...');
        
        // Get all AHJs and process their projects
        $ahjs = Ahj::all();
        
        foreach ($ahjs as $ahj) {
            $this->info("Scanning AHJ: {$ahj->name} (ID: {$ahj->id})");
            $this->scanAhjProjects($ahj);
        }

        // Check for orphaned projects (ahj_id references non-existent AHJ)
        $this->checkOrphanedProjects();

        // Generate report
        $this->generateReport();

        // Display summary
        $this->displaySummary();

        // Return exit code based on findings
        $criticalCount = count($this->issues['critical']);
        $warningCount = count($this->issues['warning']);
        
        if ($criticalCount > 0) {
            $this->error("Found {$criticalCount} critical issues. Exiting with code 1.");
            return 1;
        }
        
        if ($warningCount > 0) {
            $this->warn("Found {$warningCount} warnings. Exiting with code 0.");
        } else {
            $this->info("No issues found. All data appears consistent.");
        }
        
        return 0;
    }

    private function scanAhjProjects(Ahj $ahj): void
    {
        $projectCount = 0;
        $ahjIssues = ['critical' => 0, 'warning' => 0, 'info' => 0];

        // Use chunk to process projects in batches for production readiness
        $ahj->projects()->chunk(100, function ($projects) use (&$projectCount, &$ahjIssues) {
            foreach ($projects as $project) {
                $projectCount++;
                $this->totalProjects++;

                // Check all integrity issues for this project
                $this->checkStatusTimestampConsistency($project, $ahjIssues);
                $this->checkImpossibleTimestamps($project, $ahjIssues);
                $this->checkMissingFields($project, $ahjIssues);
            }
        });

        $this->ahjBreakdown[$ahj->id] = [
            'name' => $ahj->name,
            'project_count' => $projectCount,
            'issues' => $ahjIssues
        ];
    }

    private function checkStatusTimestampConsistency(Project $project, array &$ahjIssues): void
    {
        // Approved projects missing approved_at
        if ($project->status === 'approved' && is_null($project->approved_at)) {
            $this->addIssue('critical', "Project {$project->id}: status is 'approved' but approved_at is null");
            $ahjIssues['critical']++;
        }

        // Approved projects missing submitted_at
        if ($project->status === 'approved' && is_null($project->submitted_at)) {
            $this->addIssue('critical', "Project {$project->id}: status is 'approved' but submitted_at is null");
            $ahjIssues['critical']++;
        }

        // Draft projects with submitted_at or approved_at set
        if ($project->status === 'draft' && !is_null($project->submitted_at)) {
            $this->addIssue('warning', "Project {$project->id}: status is 'draft' but submitted_at is set");
            $ahjIssues['warning']++;
        }

        if ($project->status === 'draft' && !is_null($project->approved_at)) {
            $this->addIssue('warning', "Project {$project->id}: status is 'draft' but approved_at is set");
            $ahjIssues['warning']++;
        }

        // Submitted or revision_required projects with approved_at set
        if (in_array($project->status, ['submitted', 'revision_required']) && !is_null($project->approved_at)) {
            $this->addIssue('warning', "Project {$project->id}: status is '{$project->status}' but approved_at is set");
            $ahjIssues['warning']++;
        }
    }

    private function checkImpossibleTimestamps(Project $project, array &$ahjIssues): void
    {
        $now = Carbon::now();

        // Check if approved_at is before submitted_at
        if (!is_null($project->approved_at) && !is_null($project->submitted_at)) {
            if ($project->approved_at < $project->submitted_at) {
                $this->addIssue('critical', "Project {$project->id}: approved_at ({$project->approved_at}) is before submitted_at ({$project->submitted_at})");
                $ahjIssues['critical']++;
            } else {
                // Check for unreasonably fast approvals (only when approved_at > submitted_at)
                $diffSeconds = $project->submitted_at->diffInSeconds($project->approved_at);
                if ($diffSeconds < 60) {
                    $this->addIssue('warning', "Project {$project->id}: approved within {$diffSeconds} seconds of submission (suspiciously fast)");
                    $ahjIssues['warning']++;
                }
            }
        }

        // Check if submitted_at is before created_at
        if (!is_null($project->submitted_at) && $project->submitted_at < $project->created_at) {
            $this->addIssue('critical', "Project {$project->id}: submitted_at ({$project->submitted_at}) is before created_at ({$project->created_at})");
            $ahjIssues['critical']++;
        }

        // Check for future timestamps
        if (!is_null($project->submitted_at) && $project->submitted_at > $now) {
            $this->addIssue('critical', "Project {$project->id}: submitted_at ({$project->submitted_at}) is in the future");
            $ahjIssues['critical']++;
        }

        if (!is_null($project->approved_at) && $project->approved_at > $now) {
            $this->addIssue('critical', "Project {$project->id}: approved_at ({$project->approved_at}) is in the future");
            $ahjIssues['critical']++;
        }
    }

    private function checkMissingFields(Project $project, array &$ahjIssues): void
    {
        // Check for missing or empty title
        if (empty($project->title)) {
            $this->addIssue('critical', "Project {$project->id}: title is empty or null");
            $ahjIssues['critical']++;
        }

        // Check for missing or empty status
        if (empty($project->status)) {
            $this->addIssue('critical', "Project {$project->id}: status is empty or null");
            $ahjIssues['critical']++;
        }

        // Check for unrecognized status values
        $validStatuses = ['draft', 'submitted', 'approved', 'revision_required'];
        if (!empty($project->status) && !in_array($project->status, $validStatuses)) {
            $this->addIssue('warning', "Project {$project->id}: unrecognized status '{$project->status}'");
            $ahjIssues['warning']++;
        }

        // Check for unrecognized project_type_id values
        $validTypes = ['PV', 'PV+ST', 'ST', 'BIPV'];
        if (!in_array($project->project_type_id, $validTypes)) {
            $this->addIssue('warning', "Project {$project->id}: unrecognized project_type_id '{$project->project_type_id}'");
            $ahjIssues['warning']++;
        }

        // Check for missing ahj_id (should not happen due to foreign key constraint)
        if (empty($project->ahj_id)) {
            $this->addIssue('critical', "Project {$project->id}: ahj_id is empty or null");
            $ahjIssues['critical']++;
        }

        // Check for orphaned ahj_id (reference to non-existent AHJ)
        if (!empty($project->ahj_id) && is_null($project->ahj)) {
            $this->addIssue('critical', "Project {$project->id}: references non-existent AHJ ID {$project->ahj_id}");
            $ahjIssues['critical']++;
        }
    }

    private function checkOrphanedProjects(): void
    {
        $orphans = Project::whereNotIn('ahj_id', Ahj::pluck('id'))->get();
        foreach ($orphans as $project) {
            $this->totalProjects++;
            $this->addIssue('critical', "Project {$project->id}: references non-existent AHJ ID {$project->ahj_id}");
        }
        if ($orphans->count() > 0) {
            $this->info("Found {$orphans->count()} orphaned project(s) referencing non-existent AHJs");
        }
    }

    private function addIssue(string $severity, string $message): void
    {
        $this->issues[$severity][] = $message;
    }

    private function displaySummary(): void
    {
        $criticalCount = count($this->issues['critical']);
        $warningCount = count($this->issues['warning']);
        $infoCount = count($this->issues['info']);
        $totalIssues = $criticalCount + $warningCount + $infoCount;

        $this->line('');
        $this->info('=== Data Integrity Check Summary ===');
        $this->info("Total projects scanned: {$this->totalProjects}");
        $this->info("Total issues found: {$totalIssues}");
        $this->info("  Critical: {$criticalCount}");
        $this->info("  Warning: {$warningCount}");
        $this->info("  Info: {$infoCount}");

        if ($criticalCount > 0) {
            $this->line('');
            $this->error('Critical Issues:');
            foreach ($this->issues['critical'] as $issue) {
                $this->error("  {$issue}");
            }
        }

        if ($warningCount > 0) {
            $this->line('');
            $this->warn('Warnings:');
            foreach ($this->issues['warning'] as $issue) {
                $this->warn("  {$issue}");
            }
        }

        if ($infoCount > 0) {
            $this->line('');
            $this->info('Info:');
            foreach ($this->issues['info'] as $issue) {
                $this->info("  {$issue}");
            }
        }
    }

    private function generateReport(): void
    {
        $criticalCount = count($this->issues['critical']);
        $warningCount = count($this->issues['warning']);
        $infoCount = count($this->issues['info']);
        $totalIssues = $criticalCount + $warningCount + $infoCount;

        $report = "# Data Integrity Report\n";
        $report .= "Generated: " . Carbon::now()->format('Y-m-d H:i:s') . "\n\n";

        $report .= "## Summary\n";
        $report .= "- Total projects scanned: {$this->totalProjects}\n";
        $report .= "- Total issues found: {$totalIssues}\n";
        $report .= "- Critical issues: {$criticalCount}\n";
        $report .= "- Warning issues: {$warningCount}\n";
        $report .= "- Info issues: {$infoCount}\n\n";

        // AHJ breakdown
        $report .= "### AHJ Breakdown\n";
        foreach ($this->ahjBreakdown as $ahjId => $data) {
            $report .= "- {$data['name']} (ID: {$ahjId}): {$data['project_count']} projects, ";
            $report .= "{$data['issues']['critical']} critical, {$data['issues']['warning']} warnings, {$data['issues']['info']} info\n";
        }
        $report .= "\n";

        $report .= "## Findings\n\n";

        if ($criticalCount > 0) {
            $report .= "### Critical\n";
            $report .= "Issues that would cause application errors or data corruption:\n\n";
            foreach ($this->issues['critical'] as $issue) {
                $report .= "- {$issue}\n";
            }
            $report .= "\n";
        }

        if ($warningCount > 0) {
            $report .= "### Warning\n";
            $report .= "Issues that indicate bad data but won't crash the app:\n\n";
            foreach ($this->issues['warning'] as $issue) {
                $report .= "- {$issue}\n";
            }
            $report .= "\n";
        }

        if ($infoCount > 0) {
            $report .= "### Info\n";
            $report .= "Minor anomalies or observations worth noting:\n\n";
            foreach ($this->issues['info'] as $issue) {
                $report .= "- {$issue}\n";
            }
            $report .= "\n";
        }

        $report .= "## Recommendations\n\n";
        
        if ($criticalCount > 0) {
            $report .= "### Critical Issues\n";
            $report .= "- Fix data inconsistencies immediately in the database\n";
            $report .= "- Add application-level validation to prevent future occurrences\n";
            $report .= "- Review data entry processes and forms\n\n";
        }

        if ($warningCount > 0) {
            $report .= "### Warning Issues\n";
            $report .= "- Review and correct suspicious data patterns\n";
            $report .= "- Implement stricter validation rules\n";
            $report .= "- Add business logic constraints to prevent invalid status transitions\n\n";
        }

        if ($totalIssues === 0) {
            $report .= "No issues found. All data appears consistent and follows expected patterns.\n";
        }

        // Write report to file
        $reportPath = base_path('DATA_INTEGRITY_REPORT.md');
        file_put_contents($reportPath, $report);
        
        $this->info("Report generated: {$reportPath}");
    }
}