<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Models\Project;
use App\Models\Ahj;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;

class DataIntegrityCheck extends Command
{
    protected $signature = 'data:integrity-check';
    protected $description = 'Scan project data for integrity issues and generate a report';

    private $criticalIssues = [];
    private $warningIssues = [];
    private $infoIssues = [];
    
    private $validStatuses = ['draft', 'submitted', 'approved', 'revision_required'];
    private $validProjectTypes = ['PV', 'PV+ST', 'ST', 'BIPV'];

    public function handle()
    {
        $this->info('Running data integrity check...');

        $this->checkStatusTimestampConsistency();
        $this->checkImpossibleTimestamps();
        $this->checkMissingRequiredData();
        $this->checkOrphanedReferences();
        $this->checkInvalidValues();

        $totalIssues = count($this->criticalIssues) + count($this->warningIssues) + count($this->infoIssues);

        if ($totalIssues > 0) {
            $this->error('Data integrity issues found.');
            $this->generateReport();
            return 1;
        } else {
            $this->info('No data integrity issues found.');
            $this->generateReport();
            return 0;
        }
    }

    private function checkStatusTimestampConsistency()
    {
        // Check approved projects missing approved_at
        $projects = Project::where('status', 'approved')
            ->whereNull('approved_at')
            ->get();
        
        foreach ($projects as $project) {
            $this->warningIssues[] = [
                'type' => 'Status/Timestamp Inconsistency',
                'message' => "Project ID {$project->id} ('{$project->title}') has status 'approved' but missing approved_at timestamp",
                'project_id' => $project->id,
                'ahj_id' => $project->ahj_id,
                'details' => [
                    'status' => $project->status,
                    'submitted_at' => $project->submitted_at,
                    'approved_at' => $project->approved_at,
                ]
            ];
        }

        // Check approved projects missing submitted_at
        $projects = Project::where('status', 'approved')
            ->whereNull('submitted_at')
            ->get();
        
        foreach ($projects as $project) {
            $this->warningIssues[] = [
                'type' => 'Status/Timestamp Inconsistency',
                'message' => "Project ID {$project->id} ('{$project->title}') has status 'approved' but missing submitted_at timestamp",
                'project_id' => $project->id,
                'ahj_id' => $project->ahj_id,
                'details' => [
                    'status' => $project->status,
                    'submitted_at' => $project->submitted_at,
                    'approved_at' => $project->approved_at,
                ]
            ];
        }

        // Check draft projects with submitted_at or approved_at
        $projects = Project::where('status', 'draft')
            ->where(function($query) {
                $query->whereNotNull('submitted_at')->orWhereNotNull('approved_at');
            })
            ->get();
        
        foreach ($projects as $project) {
            $timestamps = [];
            if ($project->submitted_at) $timestamps[] = 'submitted_at';
            if ($project->approved_at) $timestamps[] = 'approved_at';
            
            $this->warningIssues[] = [
                'type' => 'Status/Timestamp Inconsistency',
                'message' => "Project ID {$project->id} ('{$project->title}') has status 'draft' but has " . implode(' and ', $timestamps) . " set",
                'project_id' => $project->id,
                'ahj_id' => $project->ahj_id,
                'details' => [
                    'status' => $project->status,
                    'submitted_at' => $project->submitted_at,
                    'approved_at' => $project->approved_at,
                ]
            ];
        }

        // Check submitted/revision_required projects with approved_at
        $projects = Project::whereIn('status', ['submitted', 'revision_required'])
            ->whereNotNull('approved_at')
            ->get();
        
        foreach ($projects as $project) {
            $this->warningIssues[] = [
                'type' => 'Status/Timestamp Inconsistency',
                'message' => "Project ID {$project->id} ('{$project->title}') has status '{$project->status}' but has approved_at timestamp set",
                'project_id' => $project->id,
                'ahj_id' => $project->ahj_id,
                'details' => [
                    'status' => $project->status,
                    'submitted_at' => $project->submitted_at,
                    'approved_at' => $project->approved_at,
                ]
            ];
        }
    }

    private function checkImpossibleTimestamps()
    {
        // Check approved_at before submitted_at
        $projects = Project::whereNotNull('submitted_at')
            ->whereNotNull('approved_at')
            ->whereColumn('approved_at', '<', 'submitted_at')
            ->get();
        
        foreach ($projects as $project) {
            $this->criticalIssues[] = [
                'type' => 'Impossible Timestamp',
                'message' => "Project ID {$project->id} ('{$project->title}') has approved_at ({$project->approved_at}) before submitted_at ({$project->submitted_at})",
                'project_id' => $project->id,
                'ahj_id' => $project->ahj_id,
                'details' => [
                    'submitted_at' => $project->submitted_at,
                    'approved_at' => $project->approved_at,
                ]
            ];
        }

        // Check submitted_at before created_at
        $projects = Project::whereNotNull('submitted_at')
            ->whereColumn('submitted_at', '<', 'created_at')
            ->get();
        
        foreach ($projects as $project) {
            $this->criticalIssues[] = [
                'type' => 'Impossible Timestamp',
                'message' => "Project ID {$project->id} ('{$project->title}') has submitted_at ({$project->submitted_at}) before created_at ({$project->created_at})",
                'project_id' => $project->id,
                'ahj_id' => $project->ahj_id,
                'details' => [
                    'created_at' => $project->created_at,
                    'submitted_at' => $project->submitted_at,
                ]
            ];
        }

        // Check future timestamps
        $now = Carbon::now();
        $projects = Project::where(function($query) use ($now) {
            $query->where('submitted_at', '>', $now)
                  ->orWhere('approved_at', '>', $now);
        })->get();
        
        foreach ($projects as $project) {
            $futureFields = [];
            if ($project->submitted_at && $project->submitted_at->gt($now)) {
                $futureFields[] = "submitted_at ({$project->submitted_at})";
            }
            if ($project->approved_at && $project->approved_at->gt($now)) {
                $futureFields[] = "approved_at ({$project->approved_at})";
            }
            
            $this->criticalIssues[] = [
                'type' => 'Future Timestamp',
                'message' => "Project ID {$project->id} ('{$project->title}') has future timestamp(s): " . implode(', ', $futureFields),
                'project_id' => $project->id,
                'ahj_id' => $project->ahj_id,
                'details' => [
                    'submitted_at' => $project->submitted_at,
                    'approved_at' => $project->approved_at,
                ]
            ];
        }

        // Check unreasonably fast approvals (within 1 minute)
        $projects = Project::whereNotNull('submitted_at')
            ->whereNotNull('approved_at')
            ->get()
            ->filter(function($project) {
                // Only check if approved_at is after submitted_at (valid order)
                return $project->approved_at->gt($project->submitted_at) && 
                       $project->submitted_at->diffInSeconds($project->approved_at) < 60;
            });
        
        foreach ($projects as $project) {
            $seconds = $project->submitted_at->diffInSeconds($project->approved_at);
            $this->warningIssues[] = [
                'type' => 'Unreasonably Fast Approval',
                'message' => "Project ID {$project->id} ('{$project->title}') was approved {$seconds} seconds after submission",
                'project_id' => $project->id,
                'ahj_id' => $project->ahj_id,
                'details' => [
                    'submitted_at' => $project->submitted_at,
                    'approved_at' => $project->approved_at,
                    'approval_time_seconds' => $seconds,
                ]
            ];
        }
    }

    private function checkMissingRequiredData()
    {
        // Check projects with missing/empty titles
        $projects = Project::where(function($query) {
            $query->whereNull('title')->orWhere('title', '');
        })->get();
        
        foreach ($projects as $project) {
            $this->criticalIssues[] = [
                'type' => 'Missing Required Data',
                'message' => "Project ID {$project->id} has missing or empty title",
                'project_id' => $project->id,
                'ahj_id' => $project->ahj_id,
                'details' => [
                    'title' => $project->title,
                ]
            ];
        }

        // Check projects with missing/empty status
        $projects = Project::where(function($query) {
            $query->whereNull('status')->orWhere('status', '');
        })->get();
        
        foreach ($projects as $project) {
            $this->criticalIssues[] = [
                'type' => 'Missing Required Data',
                'message' => "Project ID {$project->id} has missing or empty status",
                'project_id' => $project->id,
                'ahj_id' => $project->ahj_id,
                'details' => [
                    'status' => $project->status,
                ]
            ];
        }
    }

    private function checkOrphanedReferences()
    {
        // Check for projects referencing non-existent AHJs using raw SQL
        // to bypass Eloquent's foreign key handling
        $orphanedProjects = DB::select('
            SELECT p.* FROM projects p 
            LEFT JOIN ahjs a ON p.ahj_id = a.id 
            WHERE a.id IS NULL
        ');
        
        foreach ($orphanedProjects as $project) {
            $this->criticalIssues[] = [
                'type' => 'Orphaned Reference',
                'message' => "Project ID {$project->id} ('{$project->title}') references non-existent AHJ ID {$project->ahj_id}",
                'project_id' => $project->id,
                'ahj_id' => $project->ahj_id,
                'details' => [
                    'orphaned_ahj_id' => $project->ahj_id,
                ]
            ];
        }
    }

    private function checkInvalidValues()
    {
        // Check for unrecognized status values
        $projects = Project::whereNotIn('status', $this->validStatuses)->get();
        
        foreach ($projects as $project) {
            $this->warningIssues[] = [
                'type' => 'Invalid Value',
                'message' => "Project ID {$project->id} ('{$project->title}') has unrecognized status '{$project->status}'",
                'project_id' => $project->id,
                'ahj_id' => $project->ahj_id,
                'details' => [
                    'invalid_status' => $project->status,
                    'valid_statuses' => $this->validStatuses,
                ]
            ];
        }

        // Check for unrecognized project type values
        $projects = Project::whereNotIn('project_type_id', $this->validProjectTypes)->get();
        
        foreach ($projects as $project) {
            $this->infoIssues[] = [
                'type' => 'Invalid Value',
                'message' => "Project ID {$project->id} ('{$project->title}') has unrecognized project_type_id '{$project->project_type_id}'",
                'project_id' => $project->id,
                'ahj_id' => $project->ahj_id,
                'details' => [
                    'invalid_project_type' => $project->project_type_id,
                    'valid_project_types' => $this->validProjectTypes,
                ]
            ];
        }
    }

    private function generateReport()
    {
        $totalIssues = count($this->criticalIssues) + count($this->warningIssues) + count($this->infoIssues);
        $totalProjects = Project::count();
        
        $ahjBreakdown = $this->getAhjBreakdown();
        
        $report = "# Data Integrity Report\n";
        $report .= "Generated: " . Carbon::now()->format('Y-m-d H:i:s T') . "\n\n";
        
        $report .= "## Summary\n";
        $report .= "- **Total projects scanned:** {$totalProjects}\n";
        $report .= "- **Total issues found:** {$totalIssues}\n";
        $report .= "- **Critical issues:** " . count($this->criticalIssues) . "\n";
        $report .= "- **Warning issues:** " . count($this->warningIssues) . "\n";
        $report .= "- **Info issues:** " . count($this->infoIssues) . "\n\n";

        if (!empty($ahjBreakdown)) {
            $report .= "### Per-AHJ Breakdown\n";
            foreach ($ahjBreakdown as $ahjName => $counts) {
                $report .= "- **{$ahjName}**: {$counts['total']} issues (Critical: {$counts['critical']}, Warning: {$counts['warning']}, Info: {$counts['info']})\n";
            }
            $report .= "\n";
        }

        $report .= "## Findings\n\n";

        // Always include severity sections for consistent report structure
        $report .= "### Critical\n";
        $report .= "_Issues that would cause application errors or data corruption_\n\n";
        if (!empty($this->criticalIssues)) {
            foreach ($this->criticalIssues as $issue) {
                $report .= "- **{$issue['type']}**: {$issue['message']}\n";
            }
        } else {
            $report .= "None found.\n";
        }
        $report .= "\n";

        $report .= "### Warning\n";
        $report .= "_Issues that indicate bad data but won't crash the app_\n\n";
        if (!empty($this->warningIssues)) {
            foreach ($this->warningIssues as $issue) {
                $report .= "- **{$issue['type']}**: {$issue['message']}\n";
            }
        } else {
            $report .= "None found.\n";
        }
        $report .= "\n";

        $report .= "### Info\n";
        $report .= "_Minor anomalies or observations worth noting_\n\n";
        if (!empty($this->infoIssues)) {
            foreach ($this->infoIssues as $issue) {
                $report .= "- **{$issue['type']}**: {$issue['message']}\n";
            }
        } else {
            $report .= "None found.\n";
        }
        $report .= "\n";

        if ($totalIssues === 0) {
            $report .= "_All projects have consistent statuses, valid timestamps, and complete required data._\n\n";
        }

        $report .= "## Recommendations\n\n";
        
        if (!empty($this->criticalIssues)) {
            $report .= "### Critical Issues\n";
            $report .= "- **Orphaned References**: Fix foreign key constraints or clean up orphaned records\n";
            $report .= "- **Missing Required Data**: Add application-level validation to prevent empty titles and statuses\n";
            $report .= "- **Future Timestamps**: Investigate data entry process and add client-side validation\n";
            $report .= "- **Impossible Timestamps**: Review business logic for status transitions\n\n";
        }

        if (!empty($this->warningIssues)) {
            $report .= "### Warning Issues\n";
            $report .= "- **Status/Timestamp Inconsistencies**: Implement validation rules in the backend to enforce proper status/timestamp relationships\n";
            $report .= "- **Unreasonably Fast Approvals**: Consider adding minimum review time validation or flagging for manual review\n\n";
        }

        if (!empty($this->infoIssues)) {
            $report .= "### Info Issues\n";
            $report .= "- **Invalid Project Types**: Update validation to use an enum or reference table for project types\n";
            $report .= "- **Consider adding database constraints**: Use check constraints to prevent invalid status combinations\n\n";
        }

        $report .= "### Security & Performance Considerations\n";
        $report .= "- Add input validation to prevent injection attacks through form fields\n";
        $report .= "- Consider indexing frequently queried timestamp columns for better performance\n";
        $report .= "- Implement row-level security for multi-tenant data isolation\n";
        $report .= "- Use chunked queries for large dataset scans to prevent memory issues\n";

        file_put_contents(base_path('DATA_INTEGRITY_REPORT.md'), $report);
    }

    private function getAhjBreakdown()
    {
        $breakdown = [];
        
        // Get AHJ names
        $ahjs = Ahj::all()->keyBy('id');
        
        $allIssues = array_merge($this->criticalIssues, $this->warningIssues, $this->infoIssues);
        
        foreach ($allIssues as $issue) {
            $ahjId = $issue['ahj_id'];
            $ahjName = isset($ahjs[$ahjId]) ? $ahjs[$ahjId]->name : "Unknown AHJ (ID: {$ahjId})";
            
            if (!isset($breakdown[$ahjName])) {
                $breakdown[$ahjName] = ['critical' => 0, 'warning' => 0, 'info' => 0, 'total' => 0];
            }
            
            if (in_array($issue, $this->criticalIssues)) {
                $breakdown[$ahjName]['critical']++;
            } elseif (in_array($issue, $this->warningIssues)) {
                $breakdown[$ahjName]['warning']++;
            } else {
                $breakdown[$ahjName]['info']++;
            }
            
            $breakdown[$ahjName]['total']++;
        }
        
        return $breakdown;
    }
}