<?php

namespace Tests\Feature;

use Tests\TestCase;
use Illuminate\Foundation\Testing\RefreshDatabase;
use App\Models\Ahj;
use App\Models\Project;
use Carbon\Carbon;

class DashboardControllerTest extends TestCase
{
    use RefreshDatabase;

    private Ahj $ahj1;
    private Ahj $ahj2;

    protected function setUp(): void
    {
        parent::setUp();
        
        // Create two AHJs for multi-tenancy testing
        $this->ahj1 = Ahj::create([
            'name' => 'City of San Francisco',
            'address_line_1' => '123 Main St',
            'city' => 'San Francisco',
            'state' => 'CA',
            'zip' => '94102',
            'contact_email' => 'admin@sf.gov',
            'charges_fees' => true,
            'is_live' => true,
        ]);

        $this->ahj2 = Ahj::create([
            'name' => 'City of Oakland',
            'address_line_1' => '456 Oak St',
            'city' => 'Oakland',
            'state' => 'CA',
            'zip' => '94612',
            'contact_email' => 'admin@oakland.gov',
            'charges_fees' => true,
            'is_live' => true,
        ]);
    }

    /** @test */
    public function dashboard_loads_with_seeded_data()
    {
        // Create a project for AHJ1
        Project::create([
            'title' => 'Test Project',
            'ahj_id' => $this->ahj1->id,
            'status' => 'submitted',
            'submitted_at' => now(),
        ]);

        $response = $this->get('/');

        $response->assertStatus(200);
        $response->assertSee($this->ahj1->name);
    }

    /** @test */
    public function stats_are_correct_without_filters()
    {
        // Create test projects for AHJ1
        Project::create([
            'title' => 'Approved Project 1',
            'ahj_id' => $this->ahj1->id,
            'status' => 'approved',
            'submitted_at' => Carbon::parse('2025-01-01'),
            'approved_at' => Carbon::parse('2025-01-03'),
        ]);

        Project::create([
            'title' => 'Approved Project 2',
            'ahj_id' => $this->ahj1->id,
            'status' => 'approved',
            'submitted_at' => Carbon::parse('2025-01-02'),
            'approved_at' => Carbon::parse('2025-01-05'),
        ]);

        Project::create([
            'title' => 'Pending Project',
            'ahj_id' => $this->ahj1->id,
            'status' => 'submitted',
            'submitted_at' => Carbon::parse('2025-01-03'),
        ]);

        Project::create([
            'title' => 'Revision Required',
            'ahj_id' => $this->ahj1->id,
            'status' => 'revision_required',
            'submitted_at' => Carbon::parse('2025-01-04'),
        ]);

        $response = $this->get('/');

        $response->assertStatus(200);
        
        $response->assertViewHas('stats', function ($stats) {
            return $stats['total_projects'] === 4 &&
                   $stats['approved_projects'] === 2 &&
                   $stats['pending_projects'] === 2;
        });
    }

    /** @test */
    public function date_range_filters_projects()
    {
        // Create projects across different dates
        Project::create([
            'title' => 'Old Project',
            'ahj_id' => $this->ahj1->id,
            'status' => 'approved',
            'submitted_at' => Carbon::parse('2024-12-01'),
            'approved_at' => Carbon::parse('2024-12-02'),
        ]);

        Project::create([
            'title' => 'In Range Project 1',
            'ahj_id' => $this->ahj1->id,
            'status' => 'approved',
            'submitted_at' => Carbon::parse('2025-01-15'),
            'approved_at' => Carbon::parse('2025-01-17'),
        ]);

        Project::create([
            'title' => 'In Range Project 2',
            'ahj_id' => $this->ahj1->id,
            'status' => 'submitted',
            'submitted_at' => Carbon::parse('2025-01-20'),
        ]);

        Project::create([
            'title' => 'Future Project',
            'ahj_id' => $this->ahj1->id,
            'status' => 'draft',
            'submitted_at' => Carbon::parse('2025-02-15'),
        ]);

        $response = $this->get('/?start_date=2025-01-01&end_date=2025-01-31');

        $response->assertStatus(200);
        
        // Should only see projects from January 2025
        $response->assertViewHas('projects', function ($projects) {
            return $projects->count() === 2;
        });

        $response->assertSee('In Range Project 1');
        $response->assertSee('In Range Project 2');
        $response->assertDontSee('Old Project');
        $response->assertDontSee('Future Project');
    }

    /** @test */
    public function date_range_filters_stats()
    {
        // Create projects across different dates
        Project::create([
            'title' => 'Old Approved',
            'ahj_id' => $this->ahj1->id,
            'status' => 'approved',
            'submitted_at' => Carbon::parse('2024-12-01'),
            'approved_at' => Carbon::parse('2024-12-02'),
        ]);

        Project::create([
            'title' => 'In Range Approved',
            'ahj_id' => $this->ahj1->id,
            'status' => 'approved',
            'submitted_at' => Carbon::parse('2025-01-15'),
            'approved_at' => Carbon::parse('2025-01-17'),
        ]);

        Project::create([
            'title' => 'In Range Pending',
            'ahj_id' => $this->ahj1->id,
            'status' => 'submitted',
            'submitted_at' => Carbon::parse('2025-01-20'),
        ]);

        $response = $this->get('/?start_date=2025-01-01&end_date=2025-01-31');

        $response->assertStatus(200);
        
        $response->assertViewHas('stats', function ($stats) {
            return $stats['total_projects'] === 2 &&
                   $stats['approved_projects'] === 1 &&
                   $stats['pending_projects'] === 1;
        });
    }

    /** @test */
    public function average_approval_time_is_calculated()
    {
        // Create approved projects with known timestamps
        // Project 1: 2 days approval time (172800 seconds)
        Project::create([
            'title' => 'Quick Approval',
            'ahj_id' => $this->ahj1->id,
            'status' => 'approved',
            'submitted_at' => Carbon::parse('2025-01-01 10:00:00'),
            'approved_at' => Carbon::parse('2025-01-03 10:00:00'),
        ]);

        // Project 2: 4 days approval time (345600 seconds)
        Project::create([
            'title' => 'Slow Approval',
            'ahj_id' => $this->ahj1->id,
            'status' => 'approved',
            'submitted_at' => Carbon::parse('2025-01-10 10:00:00'),
            'approved_at' => Carbon::parse('2025-01-14 10:00:00'),
        ]);

        $response = $this->get('/');

        $response->assertStatus(200);
        
        // Average should be 3 days (259200 seconds)
        $response->assertViewHas('stats', function ($stats) {
            return $stats['avg_approval_time'] === 259200;
        });
    }

    /** @test */
    public function average_approval_time_is_null_when_no_approved_projects()
    {
        // Create only non-approved projects
        Project::create([
            'title' => 'Pending Project',
            'ahj_id' => $this->ahj1->id,
            'status' => 'submitted',
            'submitted_at' => Carbon::parse('2025-01-15'),
        ]);

        Project::create([
            'title' => 'Draft Project',
            'ahj_id' => $this->ahj1->id,
            'status' => 'draft',
            'submitted_at' => Carbon::parse('2025-01-16'),
        ]);

        $response = $this->get('/');

        $response->assertStatus(200);
        
        $response->assertViewHas('stats', function ($stats) {
            return $stats['avg_approval_time'] === null;
        });
    }

    /** @test */
    public function pagination_returns_20_per_page()
    {
        // Create 25 projects
        for ($i = 1; $i <= 25; $i++) {
            Project::create([
                'title' => "Project $i",
                'ahj_id' => $this->ahj1->id,
                'status' => 'submitted',
                'submitted_at' => Carbon::now()->subDays($i),
            ]);
        }

        // First page should have 20 projects
        $response = $this->get('/');
        $response->assertStatus(200);
        $response->assertViewHas('projects', function ($projects) {
            return $projects->count() === 20 && $projects->hasMorePages();
        });

        // Second page should have 5 projects
        $response = $this->get('/?page=2');
        $response->assertStatus(200);
        $response->assertViewHas('projects', function ($projects) {
            return $projects->count() === 5 && !$projects->hasMorePages();
        });
    }

    /** @test */
    public function pagination_preserves_date_filters()
    {
        // Create 25 projects within the date range
        for ($i = 1; $i <= 25; $i++) {
            Project::create([
                'title' => "Project $i",
                'ahj_id' => $this->ahj1->id,
                'status' => 'submitted',
                'submitted_at' => Carbon::parse('2025-01-01')->addDays($i),
            ]);
        }

        $response = $this->get('/?start_date=2025-01-01&end_date=2025-01-31&page=1');
        $response->assertStatus(200);
        
        $response->assertViewHas('projects', function ($projects) {
            // Should have pagination links with preserved parameters
            $links = $projects->appends(['start_date' => '2025-01-01', 'end_date' => '2025-01-31'])->links();
            return $projects->count() === 20 && 
                   $projects->hasMorePages();
        });

        $response->assertViewHas('filters', function ($filters) {
            return $filters['start_date'] === '2025-01-01' &&
                   $filters['end_date'] === '2025-01-31';
        });
    }

    /** @test */
    public function invalid_dates_are_handled_gracefully()
    {
        Project::create([
            'title' => 'Test Project',
            'ahj_id' => $this->ahj1->id,
            'status' => 'submitted',
            'submitted_at' => Carbon::parse('2025-01-15'),
        ]);

        // Test invalid date format
        $response = $this->get('/?start_date=invalid-date&end_date=2025-01-31');
        $response->assertStatus(200);
        
        // Test end date before start date
        $response = $this->get('/?start_date=2025-01-31&end_date=2025-01-01');
        $response->assertStatus(200);

        // Test empty dates
        $response = $this->get('/?start_date=&end_date=');
        $response->assertStatus(200);
    }

    /** @test */
    public function multi_tenancy_only_current_ahj_projects_shown()
    {
        // Create projects for both AHJs
        Project::create([
            'title' => 'AHJ1 Project',
            'ahj_id' => $this->ahj1->id,
            'status' => 'approved',
            'submitted_at' => Carbon::parse('2025-01-15'),
            'approved_at' => Carbon::parse('2025-01-17'),
        ]);

        Project::create([
            'title' => 'AHJ2 Project',
            'ahj_id' => $this->ahj2->id,
            'status' => 'approved',
            'submitted_at' => Carbon::parse('2025-01-16'),
            'approved_at' => Carbon::parse('2025-01-18'),
        ]);

        $response = $this->get('/');
        $response->assertStatus(200);

        // Should only see AHJ1 project (since Ahj::first() returns ahj1)
        $response->assertSee('AHJ1 Project');
        $response->assertDontSee('AHJ2 Project');
        
        $response->assertViewHas('stats', function ($stats) {
            return $stats['total_projects'] === 1 &&
                   $stats['approved_projects'] === 1;
        });
    }

    /** @test */
    public function no_projects_in_date_range_displays_gracefully()
    {
        // Create projects outside the filter range
        Project::create([
            'title' => 'Old Project',
            'ahj_id' => $this->ahj1->id,
            'status' => 'approved',
            'submitted_at' => Carbon::parse('2024-12-01'),
        ]);

        // Filter for a range with no projects
        $response = $this->get('/?start_date=2025-06-01&end_date=2025-06-30');
        $response->assertStatus(200);

        $response->assertViewHas('stats', function ($stats) {
            return $stats['total_projects'] === 0 &&
                   $stats['approved_projects'] === 0 &&
                   $stats['pending_projects'] === 0 &&
                   $stats['avg_approval_time'] === null;
        });

        $response->assertViewHas('projects', function ($projects) {
            return $projects->count() === 0;
        });
    }

    /** @test */
    public function average_approval_time_only_includes_projects_with_both_timestamps()
    {
        // Project with both timestamps (should be included)
        Project::create([
            'title' => 'Complete Approved',
            'ahj_id' => $this->ahj1->id,
            'status' => 'approved',
            'submitted_at' => Carbon::parse('2025-01-01 10:00:00'),
            'approved_at' => Carbon::parse('2025-01-03 10:00:00'), // 2 days
        ]);

        // Project approved but missing submitted_at (should be excluded)
        Project::create([
            'title' => 'Missing Submitted',
            'ahj_id' => $this->ahj1->id,
            'status' => 'approved',
            'submitted_at' => null,
            'approved_at' => Carbon::parse('2025-01-05 10:00:00'),
        ]);

        // Project submitted but not approved (should be excluded)
        Project::create([
            'title' => 'Not Approved',
            'ahj_id' => $this->ahj1->id,
            'status' => 'submitted',
            'submitted_at' => Carbon::parse('2025-01-02 10:00:00'),
            'approved_at' => null,
        ]);

        $response = $this->get('/');
        $response->assertStatus(200);

        // Average should be 2 days (172800 seconds) from only the complete project
        $response->assertViewHas('stats', function ($stats) {
            return $stats['avg_approval_time'] === 172800;
        });
    }

    /** @test */
    public function filters_are_echoed_back_for_form_persistence()
    {
        $response = $this->get('/?start_date=2025-01-01&end_date=2025-01-31');
        $response->assertStatus(200);

        $response->assertViewHas('filters', function ($filters) {
            return $filters['start_date'] === '2025-01-01' &&
                   $filters['end_date'] === '2025-01-31';
        });
    }

    /** @test */
    public function filters_are_null_when_not_provided()
    {
        $response = $this->get('/');
        $response->assertStatus(200);

        $response->assertViewHas('filters', function ($filters) {
            return $filters['start_date'] === null &&
                   $filters['end_date'] === null;
        });
    }
}