<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Models\Project;
use App\Models\Ahj;
use Carbon\Carbon;

class DataIntegrityCheck extends Command
{
    protected $signature = 'data:integrity-check';
    protected $description = 'Scan project data for integrity issues and generate report';

    private $criticalIssues = [];
    private $warningIssues = [];
    private $infoIssues = [];
    private $ahjBreakdown = [];

    public function handle()
    {
        $this->info('Starting data integrity check...');

        // Get all AHJs
        $ahjs = Ahj::all();
        
        foreach ($ahjs as $ahj) {
            $this->info("AHJ: {$ahj->name}");
            $this->checkAhjProjects($ahj);
        }

        // Also check for orphaned projects
        $this->checkOrphanedProjects();

        // Generate report
        $this->generateReport();

        // Output summary
        $totalIssues = count($this->criticalIssues) + count($this->warningIssues) + count($this->infoIssues);
        
        if ($totalIssues === 0) {
            $this->info('No critical issues found');
        } else {
            $this->warn("Found {$totalIssues} total issues");
        }

        $this->info('Data integrity check completed');
        
        return 0;
    }

    private function checkAhjProjects(Ahj $ahj)
    {
        $projects = Project::where('ahj_id', $ahj->id)->get();
        $this->ahjBreakdown[$ahj->name] = [
            'total_projects' => $projects->count(),
            'issues' => []
        ];

        foreach ($projects as $project) {
            $this->checkProject($project, $ahj->name);
        }
    }

    private function checkOrphanedProjects()
    {
        // Check for projects with non-existent AHJ IDs
        $orphanedProjects = Project::whereNotIn('ahj_id', Ahj::pluck('id'))->get();
        
        foreach ($orphanedProjects as $project) {
            $issue = "Orphaned AHJ reference: ID {$project->id} (ahj_id: {$project->ahj_id})";
            $this->criticalIssues[] = $issue;
            $this->error("Orphaned AHJ reference: ID {$project->id}");
        }
    }

    private function checkProject(Project $project, string $ahjName)
    {
        // Status/Timestamp Consistency Checks
        $this->checkStatusTimestampConsistency($project, $ahjName);
        
        // Impossible Timestamps
        $this->checkImpossibleTimestamps($project, $ahjName);
        
        // Missing Required Fields
        $this->checkMissingFields($project, $ahjName);
        
        // Unknown Status Values
        $this->checkUnknownStatus($project, $ahjName);
    }

    private function checkStatusTimestampConsistency(Project $project, string $ahjName)
    {
        // Approved projects missing approved_at
        if ($project->status === 'approved' && is_null($project->approved_at)) {
            $issue = "Approved project missing approved_at: ID {$project->id}";
            $this->criticalIssues[] = $issue;
            $this->ahjBreakdown[$ahjName]['issues'][] = $issue;
            $this->error($issue);
        }

        // Approved projects missing submitted_at
        if ($project->status === 'approved' && is_null($project->submitted_at)) {
            $issue = "Approved project missing submitted_at: ID {$project->id}";
            $this->criticalIssues[] = $issue;
            $this->ahjBreakdown[$ahjName]['issues'][] = $issue;
            $this->error($issue);
        }

        // Draft projects with submitted_at
        if ($project->status === 'draft' && !is_null($project->submitted_at)) {
            $issue = "Draft project with submitted_at: ID {$project->id}";
            $this->warningIssues[] = $issue;
            $this->ahjBreakdown[$ahjName]['issues'][] = $issue;
            $this->warn($issue);
        }

        // Draft projects with approved_at
        if ($project->status === 'draft' && !is_null($project->approved_at)) {
            $issue = "Draft project with approved_at: ID {$project->id}";
            $this->warningIssues[] = $issue;
            $this->ahjBreakdown[$ahjName]['issues'][] = $issue;
            $this->warn($issue);
        }

        // Submitted or revision_required projects with approved_at
        if (in_array($project->status, ['submitted', 'revision_required']) && !is_null($project->approved_at)) {
            $issue = "Submitted project with approved_at: ID {$project->id}";
            $this->criticalIssues[] = $issue;
            $this->ahjBreakdown[$ahjName]['issues'][] = $issue;
            $this->error($issue);
        }
    }

    private function checkImpossibleTimestamps(Project $project, string $ahjName)
    {
        // Approved_at before submitted_at
        if (!is_null($project->approved_at) && !is_null($project->submitted_at)) {
            if (Carbon::parse($project->approved_at)->lt(Carbon::parse($project->submitted_at))) {
                $issue = "Approved before submitted: ID {$project->id}";
                $this->criticalIssues[] = $issue;
                $this->ahjBreakdown[$ahjName]['issues'][] = $issue;
                $this->error($issue);
            }
        }

        // Submitted_at before created_at
        if (!is_null($project->submitted_at)) {
            if (Carbon::parse($project->submitted_at)->lt(Carbon::parse($project->created_at))) {
                $issue = "Submitted before created: ID {$project->id}";
                $this->criticalIssues[] = $issue;
                $this->ahjBreakdown[$ahjName]['issues'][] = $issue;
                $this->error($issue);
            }
        }

        // Future dates
        $now = Carbon::now();
        if (!is_null($project->submitted_at) && Carbon::parse($project->submitted_at)->gt($now)) {
            $issue = "Future timestamp: ID {$project->id} (submitted_at: {$project->submitted_at})";
            $this->criticalIssues[] = $issue;
            $this->ahjBreakdown[$ahjName]['issues'][] = $issue;
            $this->error("Future timestamp: ID {$project->id}");
        }

        if (!is_null($project->approved_at) && Carbon::parse($project->approved_at)->gt($now)) {
            $issue = "Future timestamp: ID {$project->id} (approved_at: {$project->approved_at})";
            $this->criticalIssues[] = $issue;
            $this->ahjBreakdown[$ahjName]['issues'][] = $issue;
            $this->error("Future timestamp: ID {$project->id}");
        }

        // Unreasonably fast approvals (less than 60 seconds)
        if (!is_null($project->approved_at) && !is_null($project->submitted_at)) {
            $approvalTime = Carbon::parse($project->approved_at)->diffInSeconds(Carbon::parse($project->submitted_at));
            if ($approvalTime < 60) {
                $issue = "Unreasonably fast approval: ID {$project->id} ({$approvalTime} seconds)";
                $this->warningIssues[] = $issue;
                $this->ahjBreakdown[$ahjName]['issues'][] = $issue;
                $this->warn("Unreasonably fast approval: ID {$project->id}");
            }
        }
    }

    private function checkMissingFields(Project $project, string $ahjName)
    {
        // Missing title
        if (empty($project->title)) {
            $issue = "Missing title: ID {$project->id}";
            $this->criticalIssues[] = $issue;
            $this->ahjBreakdown[$ahjName]['issues'][] = $issue;
            $this->error($issue);
        }

        // Missing status
        if (empty($project->status)) {
            $issue = "Missing status: ID {$project->id}";
            $this->criticalIssues[] = $issue;
            $this->ahjBreakdown[$ahjName]['issues'][] = $issue;
            $this->error($issue);
        }

        // Missing project_type_id
        if (empty($project->project_type_id)) {
            $issue = "Missing project_type_id: ID {$project->id}";
            $this->warningIssues[] = $issue;
            $this->ahjBreakdown[$ahjName]['issues'][] = $issue;
            $this->warn($issue);
        }
    }

    private function checkUnknownStatus(Project $project, string $ahjName)
    {
        $validStatuses = ['draft', 'submitted', 'approved', 'revision_required'];
        
        if (!in_array($project->status, $validStatuses)) {
            $issue = "Unknown status: ID {$project->id} (status: {$project->status})";
            $this->criticalIssues[] = $issue;
            $this->ahjBreakdown[$ahjName]['issues'][] = $issue;
            $this->error("Unknown status: ID {$project->id}");
        }
    }

    private function generateReport()
    {
        $reportPath = base_path('DATA_INTEGRITY_REPORT.md');
        
        $content = "# Data Integrity Report\n";
        $content .= "Generated: " . Carbon::now()->format('Y-m-d H:i:s') . "\n\n";
        
        // Summary
        $totalProjects = Project::count();
        $totalIssues = count($this->criticalIssues) + count($this->warningIssues) + count($this->infoIssues);
        $content .= "## Summary\n\n";
        $content .= "- **Total projects scanned:** {$totalProjects}\n";
        $content .= "- **Total issues found:** {$totalIssues}\n";
        $content .= "- **Critical issues:** " . count($this->criticalIssues) . "\n";
        $content .= "- **Warning issues:** " . count($this->warningIssues) . "\n";
        $content .= "- **Info issues:** " . count($this->infoIssues) . "\n\n";
        
        // AHJ Breakdown
        $content .= "### AHJ Breakdown\n\n";
        foreach ($this->ahjBreakdown as $ahjName => $data) {
            $content .= "- **{$ahjName}:** {$data['total_projects']} projects, " . count($data['issues']) . " issues\n";
        }
        $content .= "\n";
        
        // Findings
        $content .= "## Findings\n\n";
        
        // Critical
        $content .= "### Critical\n";
        if (empty($this->criticalIssues)) {
            $content .= "*No critical issues found.*\n\n";
        } else {
            $content .= "*Issues that would cause application errors or data corruption:*\n\n";
            foreach ($this->criticalIssues as $issue) {
                $content .= "- {$issue}\n";
            }
            $content .= "\n";
        }
        
        // Warning
        $content .= "### Warning\n";
        if (empty($this->warningIssues)) {
            $content .= "*No warning issues found.*\n\n";
        } else {
            $content .= "*Issues that indicate bad data but won't crash the app:*\n\n";
            foreach ($this->warningIssues as $issue) {
                $content .= "- {$issue}\n";
            }
            $content .= "\n";
        }
        
        // Info
        $content .= "### Info\n";
        if (empty($this->infoIssues)) {
            $content .= "*No info issues found.*\n\n";
        } else {
            $content .= "*Minor anomalies or observations worth noting:*\n\n";
            foreach ($this->infoIssues as $issue) {
                $content .= "- {$issue}\n";
            }
            $content .= "\n";
        }
        
        // Recommendations
        $content .= "## Recommendations\n\n";
        $content .= "### Critical Issues\n";
        $content .= "- **Status/timestamp inconsistencies:** Add database constraints and application-level validation to ensure approved projects have both `submitted_at` and `approved_at` timestamps.\n";
        $content .= "- **Impossible timestamps:** Implement validation rules to prevent `approved_at` before `submitted_at` and `submitted_at` before `created_at`.\n";
        $content .= "- **Missing required fields:** Add NOT NULL constraints for essential fields like `title` and `status`.\n";
        $content .= "- **Unknown status values:** Create an ENUM constraint or validation rule limiting status to: draft, submitted, approved, revision_required.\n";
        $content .= "- **Orphaned references:** Add proper foreign key constraints with CASCADE rules.\n\n";
        
        $content .= "### Warning Issues\n";
        $content .= "- **Draft projects with timestamps:** Review business logic - drafts should not have submission timestamps.\n";
        $content .= "- **Fast approvals:** Consider if sub-minute approvals are realistic or indicate automated processing that should be flagged differently.\n";
        $content .= "- **Missing project_type_id:** Add validation to ensure all projects specify their type.\n\n";
        
        $content .= "### Infrastructure Recommendations\n";
        $content .= "- **Add application-level validation:** Implement form validation and model validation to prevent bad data entry.\n";
        $content .= "- **Database constraints:** Use database-level constraints as a safety net.\n";
        $content .= "- **Regular audits:** Schedule this integrity check to run weekly and alert on critical issues.\n";
        $content .= "- **Data migration scripts:** Create scripts to clean up existing bad data before implementing stricter validation.\n\n";
        
        $content .= "### Security Considerations\n";
        $content .= "- **Input validation gaps:** The data quality issues suggest insufficient input validation, which could be exploited.\n";
        $content .= "- **Audit trails:** Consider adding audit trails to track when/how bad data entered the system.\n";
        $content .= "- **Access controls:** Review who can modify project status and timestamps.\n\n";

        file_put_contents($reportPath, $content);
        $this->info("Report generated: {$reportPath}");
    }
}