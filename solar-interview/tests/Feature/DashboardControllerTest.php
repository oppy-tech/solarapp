<?php

namespace Tests\Feature;

use App\Models\Ahj;
use App\Models\Project;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class DashboardControllerTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        
        // Ensure we have a testing database connection
        $this->artisan('migrate');
    }

    /** @test */
    public function dashboard_loads_with_seeded_data()
    {
        // Create test AHJ
        $ahj = Ahj::create([
            'name' => 'Test AHJ',
            'city' => 'Test City',
            'state' => 'CA'
        ]);

        // Create a test project
        Project::create([
            'ahj_id' => $ahj->id,
            'title' => 'Test Project',
            'status' => 'submitted',
            'submitted_at' => Carbon::now()
        ]);

        $response = $this->get('/');
        
        $response->assertStatus(200);
        $response->assertSee('Test AHJ');
    }

    /** @test */
    public function stats_are_correct_without_filters()
    {
        $ahj = Ahj::create([
            'name' => 'Test AHJ',
            'city' => 'Test City',
            'state' => 'CA'
        ]);

        // Create projects with different statuses
        Project::create([
            'ahj_id' => $ahj->id,
            'title' => 'Draft Project',
            'status' => 'draft',
            'submitted_at' => Carbon::now()
        ]);
        
        Project::create([
            'ahj_id' => $ahj->id,
            'title' => 'Submitted Project',
            'status' => 'submitted',
            'submitted_at' => Carbon::now()
        ]);
        
        Project::create([
            'ahj_id' => $ahj->id,
            'title' => 'Approved Project',
            'status' => 'approved',
            'submitted_at' => Carbon::now(),
            'approved_at' => Carbon::now()->addDay()
        ]);
        
        Project::create([
            'ahj_id' => $ahj->id,
            'title' => 'Revision Required Project',
            'status' => 'revision_required',
            'submitted_at' => Carbon::now()
        ]);

        $response = $this->get('/');
        
        $response->assertStatus(200);
        
        // Extract stats from view data
        $viewData = $response->original->getData();
        $stats = $viewData['stats'];
        
        $this->assertEquals(4, $stats['total_projects']);
        $this->assertEquals(1, $stats['approved_projects']);
        $this->assertEquals(2, $stats['pending_projects']); // submitted + revision_required
    }

    /** @test */
    public function date_range_filters_projects()
    {
        $ahj = Ahj::create([
            'name' => 'Test AHJ',
            'city' => 'Test City',
            'state' => 'CA'
        ]);

        // Create projects on different dates
        Project::create([
            'ahj_id' => $ahj->id,
            'title' => 'Old Project',
            'status' => 'submitted',
            'submitted_at' => Carbon::parse('2024-01-01')
        ]);
        
        Project::create([
            'ahj_id' => $ahj->id,
            'title' => 'Recent Project 1',
            'status' => 'submitted',
            'submitted_at' => Carbon::parse('2024-06-01')
        ]);
        
        Project::create([
            'ahj_id' => $ahj->id,
            'title' => 'Recent Project 2',
            'status' => 'approved',
            'submitted_at' => Carbon::parse('2024-06-15'),
            'approved_at' => Carbon::parse('2024-06-20')
        ]);

        $response = $this->get('/?start_date=2024-06-01&end_date=2024-06-30');
        
        $response->assertStatus(200);
        
        $viewData = $response->original->getData();
        $projects = $viewData['projects'];
        
        // Should only see the 2 projects from June
        $this->assertEquals(2, $projects->total());
    }

    /** @test */
    public function date_range_filters_stats()
    {
        $ahj = Ahj::create([
            'name' => 'Test AHJ',
            'city' => 'Test City',
            'state' => 'CA'
        ]);

        // Create projects on different dates
        Project::create([
            'ahj_id' => $ahj->id,
            'title' => 'Old Approved Project',
            'status' => 'approved',
            'submitted_at' => Carbon::parse('2024-01-01'),
            'approved_at' => Carbon::parse('2024-01-05')
        ]);
        
        Project::create([
            'ahj_id' => $ahj->id,
            'title' => 'Recent Submitted Project',
            'status' => 'submitted',
            'submitted_at' => Carbon::parse('2024-06-01')
        ]);
        
        Project::create([
            'ahj_id' => $ahj->id,
            'title' => 'Recent Approved Project',
            'status' => 'approved',
            'submitted_at' => Carbon::parse('2024-06-15'),
            'approved_at' => Carbon::parse('2024-06-20')
        ]);

        $response = $this->get('/?start_date=2024-06-01&end_date=2024-06-30');
        
        $viewData = $response->original->getData();
        $stats = $viewData['stats'];
        
        // Stats should reflect only the filtered date range
        $this->assertEquals(2, $stats['total_projects']); // only June projects
        $this->assertEquals(1, $stats['approved_projects']); // only June approved
        $this->assertEquals(1, $stats['pending_projects']); // only June submitted
    }

    /** @test */
    public function average_approval_time_is_calculated_correctly()
    {
        $ahj = Ahj::create([
            'name' => 'Test AHJ',
            'city' => 'Test City',
            'state' => 'CA'
        ]);

        $submittedAt = Carbon::parse('2024-06-01 10:00:00');
        $approvedAt = Carbon::parse('2024-06-06 10:00:00'); // Exactly 5 days later
        
        // Create approved project with known timestamps
        Project::create([
            'ahj_id' => $ahj->id,
            'title' => 'Approved Project 1',
            'status' => 'approved',
            'submitted_at' => $submittedAt,
            'approved_at' => $approvedAt
        ]);
        
        // Create another approved project - 3 days approval time
        Project::create([
            'ahj_id' => $ahj->id,
            'title' => 'Approved Project 2',
            'status' => 'approved',
            'submitted_at' => Carbon::parse('2024-06-10 12:00:00'),
            'approved_at' => Carbon::parse('2024-06-13 12:00:00')
        ]);
        
        // Create non-approved project - should not be included
        Project::create([
            'ahj_id' => $ahj->id,
            'title' => 'Submitted Project',
            'status' => 'submitted',
            'submitted_at' => Carbon::parse('2024-06-01')
        ]);

        $response = $this->get('/');
        
        $viewData = $response->original->getData();
        $stats = $viewData['stats'];
        
        // Average should be 4 days (5 days + 3 days) / 2 = 4 days = 345600 seconds
        $expectedAvgSeconds = (5 * 24 * 60 * 60 + 3 * 24 * 60 * 60) / 2;
        $this->assertEquals($expectedAvgSeconds, $stats['avg_approval_time']);
    }

    /** @test */
    public function average_approval_time_is_null_when_no_approved_projects()
    {
        $ahj = Ahj::create([
            'name' => 'Test AHJ',
            'city' => 'Test City',
            'state' => 'CA'
        ]);

        // Create only non-approved projects
        Project::create([
            'ahj_id' => $ahj->id,
            'title' => 'Draft Project',
            'status' => 'draft',
            'submitted_at' => Carbon::now()
        ]);
        
        Project::create([
            'ahj_id' => $ahj->id,
            'title' => 'Submitted Project',
            'status' => 'submitted',
            'submitted_at' => Carbon::now()
        ]);

        $response = $this->get('/');
        
        $viewData = $response->original->getData();
        $stats = $viewData['stats'];
        
        $this->assertNull($stats['avg_approval_time']);
    }

    /** @test */
    public function pagination_returns_20_per_page()
    {
        $ahj = Ahj::create([
            'name' => 'Test AHJ',
            'city' => 'Test City',
            'state' => 'CA'
        ]);

        // Create 25 projects
        for ($i = 1; $i <= 25; $i++) {
            Project::create([
                'ahj_id' => $ahj->id,
                'title' => "Project {$i}",
                'status' => 'submitted',
                'submitted_at' => Carbon::now()->subDays($i)
            ]);
        }

        // Test first page
        $response = $this->get('/');
        $viewData = $response->original->getData();
        $projects = $viewData['projects'];
        
        $this->assertEquals(20, $projects->count());
        $this->assertEquals(25, $projects->total());
        
        // Test second page
        $response = $this->get('/?page=2');
        $viewData = $response->original->getData();
        $projects = $viewData['projects'];
        
        $this->assertEquals(5, $projects->count());
        $this->assertEquals(25, $projects->total());
    }

    /** @test */
    public function pagination_preserves_date_filters()
    {
        $ahj = Ahj::create([
            'name' => 'Test AHJ',
            'city' => 'Test City',
            'state' => 'CA'
        ]);

        // Create 25 projects in June 2024
        for ($i = 1; $i <= 25; $i++) {
            Project::create([
                'ahj_id' => $ahj->id,
                'title' => "June Project {$i}",
                'status' => 'submitted',
                'submitted_at' => Carbon::parse('2024-06-01')->addHours($i)
            ]);
        }

        $response = $this->get('/?start_date=2024-06-01&end_date=2024-06-30&page=2');
        
        $viewData = $response->original->getData();
        $projects = $viewData['projects'];
        $filters = $viewData['filters'];
        
        $this->assertEquals('2024-06-01', $filters['start_date']);
        $this->assertEquals('2024-06-30', $filters['end_date']);
        $this->assertEquals(5, $projects->count()); // Second page should have 5 items
        
        // Check that pagination links contain the date filters
        $paginationUrl = $projects->url(1);
        $this->assertStringContainsString('start_date=2024-06-01', $paginationUrl);
        $this->assertStringContainsString('end_date=2024-06-30', $paginationUrl);
    }

    /** @test */
    public function invalid_dates_are_handled_gracefully()
    {
        $ahj = Ahj::create([
            'name' => 'Test AHJ',
            'city' => 'Test City',
            'state' => 'CA'
        ]);

        Project::create([
            'ahj_id' => $ahj->id,
            'title' => 'Test Project',
            'status' => 'submitted',
            'submitted_at' => Carbon::now()
        ]);

        // Test invalid date format
        $response = $this->get('/?start_date=invalid-date&end_date=2024-06-30');
        $response->assertStatus(200); // Should not crash
        
        // Test end date before start date
        $response = $this->get('/?start_date=2024-06-30&end_date=2024-06-01');
        $response->assertStatus(200); // Should not crash
        
        // Test malformed dates
        $response = $this->get('/?start_date=2024-13-40&end_date=2024-06-32');
        $response->assertStatus(200); // Should not crash
    }

    /** @test */
    public function multi_tenancy_only_current_ahj_projects_shown()
    {
        // Create two AHJs
        $ahj1 = Ahj::create([
            'name' => 'AHJ 1',
            'city' => 'City 1',
            'state' => 'CA'
        ]);
        
        $ahj2 = Ahj::create([
            'name' => 'AHJ 2',
            'city' => 'City 2',
            'state' => 'TX'
        ]);

        // Create projects for each AHJ
        Project::create([
            'ahj_id' => $ahj1->id,
            'title' => 'AHJ 1 Project',
            'status' => 'submitted',
            'submitted_at' => Carbon::now()
        ]);
        
        Project::create([
            'ahj_id' => $ahj2->id,
            'title' => 'AHJ 2 Project',
            'status' => 'approved',
            'submitted_at' => Carbon::now(),
            'approved_at' => Carbon::now()->addDay()
        ]);

        // Test that dashboard shows only first AHJ's projects (mock auth uses first AHJ)
        $response = $this->get('/');
        
        $viewData = $response->original->getData();
        $stats = $viewData['stats'];
        $projects = $viewData['projects'];
        
        // Should only see AHJ 1's data
        $this->assertEquals(1, $stats['total_projects']);
        $this->assertEquals(0, $stats['approved_projects']);
        $this->assertEquals(1, $stats['pending_projects']);
        
        // Should only see AHJ 1's project
        $this->assertEquals(1, $projects->total());
        $projectTitles = $projects->pluck('title')->toArray();
        $this->assertContains('AHJ 1 Project', $projectTitles);
        $this->assertNotContains('AHJ 2 Project', $projectTitles);
    }

    /** @test */
    public function filters_are_returned_in_view_data()
    {
        $ahj = Ahj::create([
            'name' => 'Test AHJ',
            'city' => 'Test City',
            'state' => 'CA'
        ]);

        // Test with filters
        $response = $this->get('/?start_date=2024-06-01&end_date=2024-06-30');
        
        $viewData = $response->original->getData();
        $filters = $viewData['filters'];
        
        $this->assertEquals('2024-06-01', $filters['start_date']);
        $this->assertEquals('2024-06-30', $filters['end_date']);
        
        // Test without filters
        $response = $this->get('/');
        
        $viewData = $response->original->getData();
        $filters = $viewData['filters'];
        
        $this->assertNull($filters['start_date']);
        $this->assertNull($filters['end_date']);
    }

    /** @test */
    public function no_projects_in_date_range_displays_gracefully()
    {
        $ahj = Ahj::create([
            'name' => 'Test AHJ',
            'city' => 'Test City',
            'state' => 'CA'
        ]);

        // Create a project outside the filter range
        Project::create([
            'ahj_id' => $ahj->id,
            'title' => 'Old Project',
            'status' => 'submitted',
            'submitted_at' => Carbon::parse('2023-01-01')
        ]);

        // Filter for a date range with no projects
        $response = $this->get('/?start_date=2024-06-01&end_date=2024-06-30');
        
        $response->assertStatus(200);
        
        $viewData = $response->original->getData();
        $stats = $viewData['stats'];
        $projects = $viewData['projects'];
        
        $this->assertEquals(0, $stats['total_projects']);
        $this->assertEquals(0, $stats['approved_projects']);
        $this->assertEquals(0, $stats['pending_projects']);
        $this->assertNull($stats['avg_approval_time']);
        $this->assertEquals(0, $projects->total());
    }
}