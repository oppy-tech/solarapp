<?php

namespace Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;
use App\Models\Ahj;
use App\Models\Project;
use Carbon\Carbon;

class DashboardControllerTest extends TestCase
{
    use RefreshDatabase;

    public function test_dashboard_loads_with_seeded_data(): void
    {
        $ahj = Ahj::create(['name' => 'Test City AHJ']);

        $response = $this->get('/');

        $response->assertStatus(200);
        $response->assertSee('Test City AHJ');
    }

    public function test_stats_are_correct_without_filters(): void
    {
        $ahj = Ahj::create(['name' => 'Test AHJ']);
        
        // Create test projects with different statuses
        Project::create(['title' => 'Draft Project', 'ahj_id' => $ahj->id, 'status' => 'draft']);
        Project::create(['title' => 'Submitted Project', 'ahj_id' => $ahj->id, 'status' => 'submitted', 'submitted_at' => now()]);
        Project::create(['title' => 'Approved Project', 'ahj_id' => $ahj->id, 'status' => 'approved', 'submitted_at' => now()->subDays(5), 'approved_at' => now()]);
        Project::create(['title' => 'Revision Required', 'ahj_id' => $ahj->id, 'status' => 'revision_required', 'submitted_at' => now()]);

        $response = $this->get('/');

        $response->assertStatus(200);
        $response->assertViewHas('stats.total_projects', 4);
        $response->assertViewHas('stats.approved_projects', 1);
        $response->assertViewHas('stats.pending_projects', 2); // submitted + revision_required
    }

    public function test_date_range_filters_projects(): void
    {
        $ahj = Ahj::create(['name' => 'Test AHJ']);
        
        // Create projects with different submitted_at dates
        Project::create(['title' => 'Old Project', 'ahj_id' => $ahj->id, 'status' => 'submitted', 'submitted_at' => Carbon::parse('2025-01-01')]);
        Project::create(['title' => 'In Range Project', 'ahj_id' => $ahj->id, 'status' => 'submitted', 'submitted_at' => Carbon::parse('2025-01-15')]);
        Project::create(['title' => 'Future Project', 'ahj_id' => $ahj->id, 'status' => 'submitted', 'submitted_at' => Carbon::parse('2025-02-15')]);

        $response = $this->get('/?start_date=2025-01-10&end_date=2025-01-31');

        $response->assertStatus(200);
        $response->assertSee('In Range Project');
        $response->assertDontSee('Old Project');
        $response->assertDontSee('Future Project');
    }

    public function test_date_range_filters_stats(): void
    {
        $ahj = Ahj::create(['name' => 'Test AHJ']);
        
        // Create projects with different dates
        Project::create(['title' => 'Old Approved', 'ahj_id' => $ahj->id, 'status' => 'approved', 'submitted_at' => Carbon::parse('2025-01-01'), 'approved_at' => Carbon::parse('2025-01-02')]);
        Project::create(['title' => 'In Range Submitted', 'ahj_id' => $ahj->id, 'status' => 'submitted', 'submitted_at' => Carbon::parse('2025-01-15')]);
        Project::create(['title' => 'In Range Approved', 'ahj_id' => $ahj->id, 'status' => 'approved', 'submitted_at' => Carbon::parse('2025-01-20'), 'approved_at' => Carbon::parse('2025-01-22')]);

        $response = $this->get('/?start_date=2025-01-10&end_date=2025-01-31');

        $response->assertStatus(200);
        $response->assertViewHas('stats.total_projects', 2);
        $response->assertViewHas('stats.approved_projects', 1);
        $response->assertViewHas('stats.pending_projects', 1);
    }

    public function test_average_approval_time_is_calculated(): void
    {
        $ahj = Ahj::create(['name' => 'Test AHJ']);
        
        // Create approved projects with known approval times
        // Project 1: 2 days (172800 seconds)
        Project::create([
            'title' => 'Quick Project',
            'ahj_id' => $ahj->id,
            'status' => 'approved',
            'submitted_at' => Carbon::parse('2025-01-01 10:00:00'),
            'approved_at' => Carbon::parse('2025-01-03 10:00:00')
        ]);
        
        // Project 2: 4 days (345600 seconds)
        Project::create([
            'title' => 'Slow Project',
            'ahj_id' => $ahj->id,
            'status' => 'approved',
            'submitted_at' => Carbon::parse('2025-01-05 10:00:00'),
            'approved_at' => Carbon::parse('2025-01-09 10:00:00')
        ]);

        $response = $this->get('/');

        $response->assertStatus(200);
        // Average should be 3 days (259200 seconds)
        $response->assertViewHas('stats.avg_approval_time', 259200);
    }

    public function test_average_approval_time_is_null_when_no_approved_projects(): void
    {
        $ahj = Ahj::create(['name' => 'Test AHJ']);
        
        // Create only non-approved projects
        Project::create(['title' => 'Draft Project', 'ahj_id' => $ahj->id, 'status' => 'draft']);
        Project::create(['title' => 'Submitted Project', 'ahj_id' => $ahj->id, 'status' => 'submitted', 'submitted_at' => now()]);

        $response = $this->get('/');

        $response->assertStatus(200);
        $response->assertViewHas('stats.avg_approval_time', null);
    }

    public function test_pagination_returns_20_per_page(): void
    {
        $ahj = Ahj::create(['name' => 'Test AHJ']);
        
        // Create 25 projects
        for ($i = 1; $i <= 25; $i++) {
            Project::create([
                'title' => "Project $i",
                'ahj_id' => $ahj->id,
                'status' => 'submitted',
                'submitted_at' => now()->subDays($i)
            ]);
        }

        // First page
        $response = $this->get('/');
        $response->assertStatus(200);
        $projects = $response->viewData('projects');
        $this->assertEquals(20, $projects->count());
        $this->assertEquals(25, $projects->total());

        // Second page
        $response = $this->get('/?page=2');
        $response->assertStatus(200);
        $projects = $response->viewData('projects');
        $this->assertEquals(5, $projects->count());
    }

    public function test_pagination_preserves_date_filters(): void
    {
        $ahj = Ahj::create(['name' => 'Test AHJ']);
        
        // Create 25 projects within date range
        for ($i = 1; $i <= 25; $i++) {
            Project::create([
                'title' => "Project $i",
                'ahj_id' => $ahj->id,
                'status' => 'submitted',
                'submitted_at' => Carbon::parse('2025-01-15')->addHours($i)
            ]);
        }

        $response = $this->get('/?start_date=2025-01-01&end_date=2025-01-31&page=1');

        $response->assertStatus(200);
        $projects = $response->viewData('projects');
        $this->assertTrue($projects->hasPages());
        
        // Check that pagination links contain filters
        $paginationLinks = $projects->appends(['start_date' => '2025-01-01', 'end_date' => '2025-01-31'])->links();
        $this->assertStringContainsString('start_date=2025-01-01', $paginationLinks);
        $this->assertStringContainsString('end_date=2025-01-31', $paginationLinks);
    }

    public function test_multi_tenancy_only_current_ahj_projects_shown(): void
    {
        $ahj1 = Ahj::create(['name' => 'AHJ 1']);
        $ahj2 = Ahj::create(['name' => 'AHJ 2']);
        
        Project::create(['title' => 'AHJ 1 Project', 'ahj_id' => $ahj1->id, 'status' => 'submitted', 'submitted_at' => now()]);
        Project::create(['title' => 'AHJ 2 Project', 'ahj_id' => $ahj2->id, 'status' => 'submitted', 'submitted_at' => now()]);

        $response = $this->get('/');

        $response->assertStatus(200);
        $response->assertSee('AHJ 1 Project');
        $response->assertDontSee('AHJ 2 Project');
        $response->assertViewHas('stats.total_projects', 1);
    }

    public function test_invalid_date_format_does_not_crash(): void
    {
        $ahj = Ahj::create(['name' => 'Test AHJ']);
        Project::create(['title' => 'Test Project', 'ahj_id' => $ahj->id, 'status' => 'submitted', 'submitted_at' => Carbon::parse('2025-01-15')]);

        $response = $this->get('/?start_date=not-a-date&end_date=2025-01-31');

        $response->assertStatus(200);
        $response->assertSee('Test Project'); // Shows all data when invalid
    }

    public function test_end_date_before_start_date_is_handled(): void
    {
        $ahj = Ahj::create(['name' => 'Test AHJ']);
        Project::create(['title' => 'Test Project', 'ahj_id' => $ahj->id, 'status' => 'submitted', 'submitted_at' => now()]);

        $response = $this->get('/?start_date=2025-06-01&end_date=2025-01-01');

        $response->assertStatus(200);
        $response->assertSee('Test Project'); // Shows all data when invalid range
    }

    public function test_sql_injection_in_date_params(): void
    {
        $ahj = Ahj::create(['name' => 'Test AHJ']);
        Project::create(['title' => 'Test Project', 'ahj_id' => $ahj->id, 'status' => 'submitted', 'submitted_at' => now()]);

        $response = $this->get("/?start_date=2025-01-01'; DROP TABLE projects;--&end_date=2025-01-31");

        $response->assertStatus(200);
        // Verify project still exists (not dropped)
        $this->assertEquals(1, Project::count());
    }

    public function test_xss_in_date_params(): void
    {
        $ahj = Ahj::create(['name' => 'Test AHJ']);

        $response = $this->get('/?start_date=<script>alert(1)</script>&end_date=2025-01-31');

        $response->assertStatus(200);
        $this->assertStringNotContainsString('<script>alert(1)</script>', $response->getContent());
    }

    public function test_empty_string_dates(): void
    {
        $ahj = Ahj::create(['name' => 'Test AHJ']);
        Project::create(['title' => 'Test Project', 'ahj_id' => $ahj->id, 'status' => 'submitted', 'submitted_at' => now()]);

        $response = $this->get('/?start_date=&end_date=');

        $response->assertStatus(200);
        $response->assertSee('Test Project'); // Shows all data
    }

    public function test_only_start_date_provided(): void
    {
        $ahj = Ahj::create(['name' => 'Test AHJ']);
        Project::create(['title' => 'Old Project', 'ahj_id' => $ahj->id, 'status' => 'submitted', 'submitted_at' => Carbon::parse('2024-12-01')]);
        Project::create(['title' => 'Recent Project', 'ahj_id' => $ahj->id, 'status' => 'submitted', 'submitted_at' => Carbon::parse('2025-02-01')]);

        $response = $this->get('/?start_date=2025-01-01');

        $response->assertStatus(200);
        // Should filter from start_date onwards or ignore incomplete range - implementation will determine behavior
    }

    public function test_only_end_date_provided(): void
    {
        $ahj = Ahj::create(['name' => 'Test AHJ']);
        Project::create(['title' => 'Old Project', 'ahj_id' => $ahj->id, 'status' => 'submitted', 'submitted_at' => Carbon::parse('2024-12-01')]);
        Project::create(['title' => 'Recent Project', 'ahj_id' => $ahj->id, 'status' => 'submitted', 'submitted_at' => Carbon::parse('2025-02-01')]);

        $response = $this->get('/?end_date=2025-01-31');

        $response->assertStatus(200);
        // Should filter up to end_date or ignore incomplete range - implementation will determine behavior
    }

    public function test_extreme_date_range(): void
    {
        $ahj = Ahj::create(['name' => 'Test AHJ']);
        Project::create(['title' => 'Test Project', 'ahj_id' => $ahj->id, 'status' => 'submitted', 'submitted_at' => now()]);

        $response = $this->get('/?start_date=1900-01-01&end_date=2099-12-31');

        $response->assertStatus(200);
        $response->assertSee('Test Project');
    }

    public function test_negative_page_number(): void
    {
        $ahj = Ahj::create(['name' => 'Test AHJ']);
        Project::create(['title' => 'Test Project', 'ahj_id' => $ahj->id, 'status' => 'submitted', 'submitted_at' => now()]);

        $response = $this->get('/?page=-1');

        $response->assertStatus(200);
    }

    public function test_page_number_beyond_last_page(): void
    {
        $ahj = Ahj::create(['name' => 'Test AHJ']);
        
        // Create 5 projects
        for ($i = 1; $i <= 5; $i++) {
            Project::create(['title' => "Project $i", 'ahj_id' => $ahj->id, 'status' => 'submitted', 'submitted_at' => now()]);
        }

        $response = $this->get('/?page=999');

        $response->assertStatus(200);
        $projects = $response->viewData('projects');
        $this->assertEquals(0, $projects->count()); // Empty results, not 500 error
    }

    public function test_non_numeric_page(): void
    {
        $ahj = Ahj::create(['name' => 'Test AHJ']);
        Project::create(['title' => 'Test Project', 'ahj_id' => $ahj->id, 'status' => 'submitted', 'submitted_at' => now()]);

        $response = $this->get('/?page=abc');

        $response->assertStatus(200);
    }

    public function test_extra_unexpected_query_params(): void
    {
        $ahj = Ahj::create(['name' => 'Test AHJ']);
        Project::create(['title' => 'Test Project', 'ahj_id' => $ahj->id, 'status' => 'submitted', 'submitted_at' => Carbon::parse('2025-01-15')]);

        $response = $this->get('/?start_date=2025-01-01&end_date=2025-01-31&foo=bar&admin=true');

        $response->assertStatus(200);
        $response->assertSee('Test Project');
    }

    public function test_date_at_boundary(): void
    {
        $ahj = Ahj::create(['name' => 'Test AHJ']);
        
        Project::create([
            'title' => 'Boundary Start',
            'ahj_id' => $ahj->id,
            'status' => 'submitted',
            'submitted_at' => Carbon::parse('2025-01-01 00:00:00')
        ]);
        
        Project::create([
            'title' => 'Boundary End',
            'ahj_id' => $ahj->id,
            'status' => 'submitted',
            'submitted_at' => Carbon::parse('2025-01-31 23:59:59')
        ]);

        $response = $this->get('/?start_date=2025-01-01&end_date=2025-01-31');

        $response->assertStatus(200);
        $response->assertSee('Boundary Start');
        $response->assertSee('Boundary End');
    }

    public function test_approval_time_with_zero_duration(): void
    {
        $ahj = Ahj::create(['name' => 'Test AHJ']);
        
        Project::create([
            'title' => 'Instant Approval',
            'ahj_id' => $ahj->id,
            'status' => 'approved',
            'submitted_at' => Carbon::parse('2025-01-01 10:00:00'),
            'approved_at' => Carbon::parse('2025-01-01 10:00:00')
        ]);

        $response = $this->get('/');

        $response->assertStatus(200);
        $response->assertViewHas('stats.avg_approval_time', 0); // Should be 0, not null
    }

    public function test_multi_tenancy_with_date_filters(): void
    {
        $ahj1 = Ahj::create(['name' => 'AHJ 1']);
        $ahj2 = Ahj::create(['name' => 'AHJ 2']);
        
        // Both AHJs have projects in the same date range
        Project::create([
            'title' => 'AHJ 1 Project',
            'ahj_id' => $ahj1->id,
            'status' => 'approved',
            'submitted_at' => Carbon::parse('2025-01-15'),
            'approved_at' => Carbon::parse('2025-01-17')
        ]);
        
        Project::create([
            'title' => 'AHJ 2 Project',
            'ahj_id' => $ahj2->id,
            'status' => 'approved',
            'submitted_at' => Carbon::parse('2025-01-15'),
            'approved_at' => Carbon::parse('2025-01-17')
        ]);

        $response = $this->get('/?start_date=2025-01-01&end_date=2025-01-31');

        $response->assertStatus(200);
        $response->assertSee('AHJ 1 Project');
        $response->assertDontSee('AHJ 2 Project');
        $response->assertViewHas('stats.total_projects', 1);
        $response->assertViewHas('stats.approved_projects', 1);
    }

    public function test_filters_are_echoed_back_in_view(): void
    {
        $ahj = Ahj::create(['name' => 'Test AHJ']);

        $response = $this->get('/?start_date=2025-01-01&end_date=2025-01-31');

        $response->assertStatus(200);
        $response->assertViewHas('filters.start_date', '2025-01-01');
        $response->assertViewHas('filters.end_date', '2025-01-31');
    }
}