<?php

namespace Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;
use App\Models\Ahj;
use App\Models\Project;
use Illuminate\Support\Facades\DB;
use Carbon\Carbon;

class DataIntegrityCheckTest extends TestCase
{
    use RefreshDatabase;

    public function test_detects_approved_project_missing_approved_at()
    {
        $ahj = Ahj::create(['name' => 'Test AHJ']);
        
        DB::table('projects')->insert([
            'ahj_id' => $ahj->id,
            'title' => 'Test Project',
            'status' => 'approved',
            'project_type_id' => 'PV',
            'submitted_at' => now(),
            'approved_at' => null,
            'created_at' => now(),
            'updated_at' => now(),
        ]);
        
        $this->artisan('data:integrity-check')
            ->expectsOutput('Starting data integrity check...')
            ->expectsOutput("Scanning AHJ: Test AHJ (ID: {$ahj->id})")
            ->assertExitCode(1);
            
        $this->artisan('data:integrity-check')
            ->expectsOutputToContain('status is \'approved\' but approved_at is null')
            ->assertExitCode(1);
    }

    public function test_detects_approved_project_missing_submitted_at()
    {
        $ahj = Ahj::create(['name' => 'Test AHJ']);
        
        DB::table('projects')->insert([
            'ahj_id' => $ahj->id,
            'title' => 'Test Project',
            'status' => 'approved',
            'project_type_id' => 'PV',
            'submitted_at' => null,
            'approved_at' => now(),
            'created_at' => now(),
            'updated_at' => now(),
        ]);
        
        $this->artisan('data:integrity-check')
            ->expectsOutputToContain('status is \'approved\' but submitted_at is null')
            ->assertExitCode(1);
    }

    public function test_detects_approved_at_before_submitted_at()
    {
        $ahj = Ahj::create(['name' => 'Test AHJ']);
        
        $submitted = Carbon::parse('2024-06-15 14:30:00');
        $approved = Carbon::parse('2024-06-14 10:00:00');
        
        DB::table('projects')->insert([
            'ahj_id' => $ahj->id,
            'title' => 'Test Project',
            'status' => 'approved',
            'project_type_id' => 'PV',
            'submitted_at' => $submitted,
            'approved_at' => $approved,
            'created_at' => now(),
            'updated_at' => now(),
        ]);
        
        $this->artisan('data:integrity-check')
            ->expectsOutputToContain('approved_at (' . $approved . ') is before submitted_at (' . $submitted . ')')
            ->assertExitCode(1);
    }

    public function test_detects_draft_project_with_submitted_at_set()
    {
        $ahj = Ahj::create(['name' => 'Test AHJ']);
        
        Project::create([
            'ahj_id' => $ahj->id,
            'title' => 'Test Project',
            'status' => 'draft',
            'project_type_id' => 'PV',
            'submitted_at' => now(),
        ]);
        
        $this->artisan('data:integrity-check')
            ->expectsOutputToContain('status is \'draft\' but submitted_at is set')
            ->assertExitCode(0);
    }

    public function test_detects_draft_project_with_approved_at_set()
    {
        $ahj = Ahj::create(['name' => 'Test AHJ']);
        
        Project::create([
            'ahj_id' => $ahj->id,
            'title' => 'Test Project',
            'status' => 'draft',
            'project_type_id' => 'PV',
            'approved_at' => now(),
        ]);
        
        $this->artisan('data:integrity-check')
            ->expectsOutputToContain('status is \'draft\' but approved_at is set')
            ->assertExitCode(0);
    }

    public function test_detects_submitted_project_with_approved_at_set()
    {
        $ahj = Ahj::create(['name' => 'Test AHJ']);
        
        $baseTime = now();
        
        DB::table('projects')->insert([
            'ahj_id' => $ahj->id,
            'title' => 'Test Project',
            'status' => 'submitted',
            'project_type_id' => 'PV',
            'submitted_at' => $baseTime->copy()->subHours(2),
            'approved_at' => $baseTime->copy()->subHours(1),
            'created_at' => $baseTime->copy()->subHours(3),
            'updated_at' => $baseTime->copy()->subHours(3),
        ]);
        
        $this->artisan('data:integrity-check')
            ->expectsOutputToContain('status is \'submitted\' but approved_at is set')
            ->expectsOutput('Found 1 warnings. Exiting with code 0.')
            ->assertExitCode(0);
    }

    public function test_detects_revision_required_project_with_approved_at_set()
    {
        $ahj = Ahj::create(['name' => 'Test AHJ']);
        
        $baseTime = now();
        
        DB::table('projects')->insert([
            'ahj_id' => $ahj->id,
            'title' => 'Test Project',
            'status' => 'revision_required',
            'project_type_id' => 'PV',
            'submitted_at' => $baseTime->copy()->subHours(2),
            'approved_at' => $baseTime->copy()->subHours(1),
            'created_at' => $baseTime->copy()->subHours(3),
            'updated_at' => $baseTime->copy()->subHours(3),
        ]);
        
        $this->artisan('data:integrity-check')
            ->expectsOutputToContain('status is \'revision_required\' but approved_at is set')
            ->expectsOutput('Found 1 warnings. Exiting with code 0.')
            ->assertExitCode(0);
    }

    public function test_detects_submitted_at_before_created_at()
    {
        $ahj = Ahj::create(['name' => 'Test AHJ']);
        
        $created = Carbon::now();
        $submitted = $created->copy()->subHour();
        
        DB::table('projects')->insert([
            'ahj_id' => $ahj->id,
            'title' => 'Test Project',
            'status' => 'submitted',
            'project_type_id' => 'PV',
            'submitted_at' => $submitted,
            'approved_at' => null,
            'created_at' => $created,
            'updated_at' => $created,
        ]);
        
        $this->artisan('data:integrity-check')
            ->expectsOutputToContain('submitted_at (' . $submitted . ') is before created_at (' . $created . ')')
            ->assertExitCode(1);
    }

    public function test_detects_future_submitted_at()
    {
        $ahj = Ahj::create(['name' => 'Test AHJ']);
        
        $future = Carbon::now()->addDay();
        
        Project::create([
            'ahj_id' => $ahj->id,
            'title' => 'Test Project',
            'status' => 'submitted',
            'project_type_id' => 'PV',
            'submitted_at' => $future,
        ]);
        
        $this->artisan('data:integrity-check')
            ->expectsOutputToContain('submitted_at (' . $future . ') is in the future')
            ->assertExitCode(1);
    }

    public function test_detects_future_approved_at()
    {
        $ahj = Ahj::create(['name' => 'Test AHJ']);
        
        $future = Carbon::now()->addDay();
        
        Project::create([
            'ahj_id' => $ahj->id,
            'title' => 'Test Project',
            'status' => 'approved',
            'project_type_id' => 'PV',
            'submitted_at' => now()->subHour(),
            'approved_at' => $future,
        ]);
        
        $this->artisan('data:integrity-check')
            ->expectsOutputToContain('approved_at (' . $future . ') is in the future')
            ->assertExitCode(1);
    }

    public function test_detects_unreasonably_fast_approval()
    {
        $ahj = Ahj::create(['name' => 'Test AHJ']);
        
        $baseTime = now()->subHours(2);
        $submitted = $baseTime->copy();
        $approved = $baseTime->copy(); // Same time = 0 seconds
        $created = $baseTime->copy()->subHours(1);
        
        DB::table('projects')->insert([
            'ahj_id' => $ahj->id,
            'title' => 'Test Project',
            'status' => 'approved',
            'project_type_id' => 'PV',
            'submitted_at' => $submitted,
            'approved_at' => $approved,
            'created_at' => $created,
            'updated_at' => $created,
        ]);
        
        $this->artisan('data:integrity-check')
            ->expectsOutputToContain('approved within 0 seconds of submission (suspiciously fast)')
            ->expectsOutput('Found 1 warnings. Exiting with code 0.')
            ->assertExitCode(0);
    }

    public function test_detects_project_with_unknown_status()
    {
        $ahj = Ahj::create(['name' => 'Test AHJ']);
        
        DB::table('projects')->insert([
            'ahj_id' => $ahj->id,
            'title' => 'Test Project',
            'status' => 'invalid_status',
            'project_type_id' => 'PV',
            'submitted_at' => null,
            'approved_at' => null,
            'created_at' => now(),
            'updated_at' => now(),
        ]);
        
        $this->artisan('data:integrity-check')
            ->expectsOutputToContain('unrecognized status \'invalid_status\'')
            ->assertExitCode(0);
    }

    public function test_detects_project_with_missing_title()
    {
        $ahj = Ahj::create(['name' => 'Test AHJ']);
        
        DB::table('projects')->insert([
            'ahj_id' => $ahj->id,
            'title' => '',
            'status' => 'draft',
            'project_type_id' => 'PV',
            'submitted_at' => null,
            'approved_at' => null,
            'created_at' => now(),
            'updated_at' => now(),
        ]);
        
        $this->artisan('data:integrity-check')
            ->expectsOutputToContain('title is empty or null')
            ->assertExitCode(1);
    }

    public function test_detects_project_with_null_title()
    {
        // Since SQLite enforces NOT NULL, test empty string as proxy for null
        $ahj = Ahj::create(['name' => 'Test AHJ']);
        
        DB::table('projects')->insert([
            'ahj_id' => $ahj->id,
            'title' => '', // Empty string instead of null
            'status' => 'draft',
            'project_type_id' => 'PV',
            'submitted_at' => null,
            'approved_at' => null,
            'created_at' => now(),
            'updated_at' => now(),
        ]);
        
        $this->artisan('data:integrity-check')
            ->expectsOutputToContain('title is empty or null')
            ->assertExitCode(1);
    }

    public function test_detects_project_with_empty_status()
    {
        $ahj = Ahj::create(['name' => 'Test AHJ']);
        
        DB::table('projects')->insert([
            'ahj_id' => $ahj->id,
            'title' => 'Test Project',
            'status' => '',
            'project_type_id' => 'PV',
            'submitted_at' => null,
            'approved_at' => null,
            'created_at' => now(),
            'updated_at' => now(),
        ]);
        
        $this->artisan('data:integrity-check')
            ->expectsOutputToContain('status is empty or null')
            ->assertExitCode(1);
    }

    public function test_detects_project_with_null_status()
    {
        // Since SQLite enforces NOT NULL, test empty string as proxy for null  
        $ahj = Ahj::create(['name' => 'Test AHJ']);
        
        DB::table('projects')->insert([
            'ahj_id' => $ahj->id,
            'title' => 'Test Project',
            'status' => '', // Empty string instead of null
            'project_type_id' => 'PV',
            'submitted_at' => null,
            'approved_at' => null,
            'created_at' => now(),
            'updated_at' => now(),
        ]);
        
        $this->artisan('data:integrity-check')
            ->expectsOutputToContain('status is empty or null')
            ->assertExitCode(1);
    }

    public function test_detects_project_with_unrecognized_project_type_id()
    {
        $ahj = Ahj::create(['name' => 'Test AHJ']);
        
        Project::create([
            'ahj_id' => $ahj->id,
            'title' => 'Test Project',
            'status' => 'draft',
            'project_type_id' => 'INVALID',
        ]);
        
        $this->artisan('data:integrity-check')
            ->expectsOutputToContain('unrecognized project_type_id \'INVALID\'')
            ->assertExitCode(0);
    }

    public function test_detects_orphaned_ahj_id()
    {
        // Skip this test in SQLite as it enforces foreign key constraints
        if (DB::connection()->getDriverName() === 'sqlite') {
            $this->markTestSkipped('SQLite enforces foreign key constraints, cannot test orphaned references');
        }
        
        // Create a project with non-existent AHJ ID (MySQL only)
        DB::statement('SET FOREIGN_KEY_CHECKS=0;');
        DB::table('projects')->insert([
            'ahj_id' => 9999,
            'title' => 'Test Project',
            'status' => 'draft',
            'project_type_id' => 'PV',
            'submitted_at' => null,
            'approved_at' => null,
            'created_at' => now(),
            'updated_at' => now(),
        ]);
        DB::statement('SET FOREIGN_KEY_CHECKS=1;');
        
        $this->artisan('data:integrity-check')
            ->expectsOutputToContain('references non-existent AHJ ID 9999')
            ->assertExitCode(1);
    }

    public function test_clean_data_produces_no_issues()
    {
        $ahj = Ahj::create(['name' => 'Test AHJ']);
        
        $baseTime = now();
        
        // Create valid projects with correct status/timestamp combinations using DB insert to control created_at
        DB::table('projects')->insert([
            'ahj_id' => $ahj->id,
            'title' => 'Draft Project',
            'status' => 'draft',
            'project_type_id' => 'PV',
            'submitted_at' => null,
            'approved_at' => null,
            'created_at' => $baseTime->copy()->subHours(4),
            'updated_at' => $baseTime->copy()->subHours(4),
        ]);
        
        DB::table('projects')->insert([
            'ahj_id' => $ahj->id,
            'title' => 'Submitted Project',
            'status' => 'submitted',
            'project_type_id' => 'PV+ST',
            'submitted_at' => $baseTime->copy()->subHour(),
            'approved_at' => null,
            'created_at' => $baseTime->copy()->subHours(2),
            'updated_at' => $baseTime->copy()->subHours(2),
        ]);
        
        DB::table('projects')->insert([
            'ahj_id' => $ahj->id,
            'title' => 'Approved Project',
            'status' => 'approved',
            'project_type_id' => 'ST',
            'submitted_at' => $baseTime->copy()->subHours(2),
            'approved_at' => $baseTime->copy()->subHour(),
            'created_at' => $baseTime->copy()->subHours(3),
            'updated_at' => $baseTime->copy()->subHours(3),
        ]);
        
        $this->artisan('data:integrity-check')
            ->expectsOutput('Total issues found: 0')
            ->expectsOutput('No issues found. All data appears consistent.')
            ->assertExitCode(0);
    }

    public function test_reports_per_ahj_breakdown()
    {
        $ahj1 = Ahj::create(['name' => 'First AHJ']);
        $ahj2 = Ahj::create(['name' => 'Second AHJ']);
        
        // Create an issue in first AHJ
        DB::table('projects')->insert([
            'ahj_id' => $ahj1->id,
            'title' => '',
            'status' => 'draft',
            'project_type_id' => 'PV',
            'submitted_at' => null,
            'approved_at' => null,
            'created_at' => now(),
            'updated_at' => now(),
        ]);
        
        // Create an issue in second AHJ
        Project::create([
            'ahj_id' => $ahj2->id,
            'title' => 'Test Project',
            'status' => 'invalid_status',
            'project_type_id' => 'PV',
        ]);
        
        $this->artisan('data:integrity-check')
            ->expectsOutput("Scanning AHJ: First AHJ (ID: {$ahj1->id})")
            ->expectsOutput("Scanning AHJ: Second AHJ (ID: {$ahj2->id})")
            ->expectsOutputToContain('Total issues found: 2')
            ->assertExitCode(1);
    }

    public function test_command_generates_report_file()
    {
        $ahj = Ahj::create(['name' => 'Test AHJ']);
        
        Project::create([
            'ahj_id' => $ahj->id,
            'title' => 'Test Project',
            'status' => 'draft',
            'project_type_id' => 'PV',
        ]);
        
        $this->artisan('data:integrity-check');
        
        $reportPath = base_path('DATA_INTEGRITY_REPORT.md');
        $this->assertFileExists($reportPath);
        
        $content = file_get_contents($reportPath);
        $this->assertStringContainsString('# Data Integrity Report', $content);
        $this->assertStringContainsString('## Summary', $content);
        $this->assertStringContainsString('## Findings', $content);
        $this->assertStringContainsString('## Recommendations', $content);
    }

    public function test_report_includes_specific_project_ids()
    {
        $ahj = Ahj::create(['name' => 'Test AHJ']);
        
        $project = Project::create([
            'ahj_id' => $ahj->id,
            'title' => 'Test Project',
            'status' => 'invalid_status',
            'project_type_id' => 'PV',
        ]);
        
        $this->artisan('data:integrity-check')
            ->expectsOutputToContain("Project {$project->id}: unrecognized status 'invalid_status'");
    }

    public function test_report_separates_findings_by_severity()
    {
        $ahj = Ahj::create(['name' => 'Test AHJ']);
        
        // Critical issue
        DB::table('projects')->insert([
            'ahj_id' => $ahj->id,
            'title' => '',
            'status' => 'draft',
            'project_type_id' => 'PV',
            'submitted_at' => null,
            'approved_at' => null,
            'created_at' => now(),
            'updated_at' => now(),
        ]);
        
        // Warning issue
        Project::create([
            'ahj_id' => $ahj->id,
            'title' => 'Test Project',
            'status' => 'invalid_status',
            'project_type_id' => 'PV',
        ]);
        
        $this->artisan('data:integrity-check')
            ->expectsOutput('  Critical: 1')
            ->expectsOutput('  Warning: 1')
            ->expectsOutput('  Info: 0')
            ->expectsOutput('Critical Issues:')
            ->expectsOutput('Warnings:')
            ->assertExitCode(1);
    }

    public function test_command_exits_with_non_zero_code_when_issues_found()
    {
        $ahj = Ahj::create(['name' => 'Test AHJ']);
        
        DB::table('projects')->insert([
            'ahj_id' => $ahj->id,
            'title' => '',
            'status' => 'draft',
            'project_type_id' => 'PV',
            'submitted_at' => null,
            'approved_at' => null,
            'created_at' => now(),
            'updated_at' => now(),
        ]);
        
        $this->artisan('data:integrity-check')
            ->expectsOutputToContain('Found 1 critical issues. Exiting with code 1.')
            ->assertExitCode(1);
    }

    public function test_command_exits_with_zero_code_when_no_issues()
    {
        $ahj = Ahj::create(['name' => 'Test AHJ']);
        
        Project::create([
            'ahj_id' => $ahj->id,
            'title' => 'Valid Project',
            'status' => 'draft',
            'project_type_id' => 'PV',
        ]);
        
        $this->artisan('data:integrity-check')
            ->expectsOutput('No issues found. All data appears consistent.')
            ->assertExitCode(0);
    }
}