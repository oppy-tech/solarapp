<?php

namespace Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;
use App\Models\Ahj;
use App\Models\Project;
use Carbon\Carbon;

class DashboardViewTest extends TestCase
{
    use RefreshDatabase;

    private function createAhjWithProjects()
    {
        return Ahj::create(['name' => 'Test City AHJ']);
    }

    /**
     * Happy path tests
     */

    /** @test */
    public function date_range_form_is_present()
    {
        $ahj = $this->createAhjWithProjects();

        $response = $this->get('/');

        $response->assertStatus(200);
        $response->assertSee('name="start_date"', false);
        $response->assertSee('name="end_date"', false);
        $response->assertSee('type="date"', false);
    }

    /** @test */
    public function date_inputs_are_pre_filled_from_query_params()
    {
        $ahj = $this->createAhjWithProjects();

        $response = $this->get('/?start_date=2025-01-01&end_date=2025-06-01');

        $response->assertStatus(200);
        $response->assertSee('value="2025-01-01"', false);
        $response->assertSee('value="2025-06-01"', false);
    }

    /** @test */
    public function filter_and_clear_buttons_exist()
    {
        $ahj = $this->createAhjWithProjects();

        $response = $this->get('/');

        $response->assertStatus(200);
        $response->assertSee('type="submit"', false);
        $response->assertSee('href="/"', false); // Clear link
    }

    /** @test */
    public function avg_approval_time_displays_human_readable_format()
    {
        $ahj = $this->createAhjWithProjects();
        
        $submittedAt = Carbon::now()->subDays(3)->subHours(6);
        $approvedAt = Carbon::now();
        
        Project::create([
            'title' => 'Test Project',
            'ahj_id' => $ahj->id,
            'project_type_id' => 'PV',
            'status' => 'approved',
            'submitted_at' => $submittedAt,
            'approved_at' => $approvedAt,
        ]);

        $response = $this->get('/');

        $response->assertStatus(200);
        $response->assertSee('3 days');
        $response->assertSee('6 hours');
    }

    /** @test */
    public function avg_approval_time_shows_na_when_null()
    {
        $ahj = $this->createAhjWithProjects();
        
        // Create only draft projects (no approvals)
        Project::create([
            'title' => 'Draft Project',
            'ahj_id' => $ahj->id,
            'project_type_id' => 'PV',
            'status' => 'draft',
            'submitted_at' => null,
            'approved_at' => null,
        ]);

        $response = $this->get('/');

        $response->assertStatus(200);
        $response->assertSee('N/A');
    }

    /** @test */
    public function pagination_links_are_rendered()
    {
        $ahj = $this->createAhjWithProjects();
        
        // Create 25 projects to trigger pagination
        for ($i = 1; $i <= 25; $i++) {
            Project::create([
                'title' => "Project $i",
                'ahj_id' => $ahj->id,
                'project_type_id' => 'PV',
                'status' => 'submitted',
                'submitted_at' => Carbon::now()->subDays($i),
                'approved_at' => null,
            ]);
        }

        $response = $this->get('/');

        $response->assertStatus(200);
        // Laravel pagination creates navigation with "Next" link when there are more pages
        $response->assertSee('Next', false);
    }

    /** @test */
    public function project_table_displays_correct_columns()
    {
        $ahj = $this->createAhjWithProjects();
        
        Project::create([
            'title' => 'Sample Project',
            'ahj_id' => $ahj->id,
            'project_type_id' => 'PV',
            'status' => 'submitted',
            'submitted_at' => Carbon::now(),
            'approved_at' => null,
        ]);

        $response = $this->get('/');

        $response->assertStatus(200);
        $response->assertSee('Project Title');
        $response->assertSee('Status');
        $response->assertSee('Submitted Date');
        $response->assertSee('Sample Project');
        $response->assertSee('submitted');
    }

    /** @test */
    public function empty_state_is_handled()
    {
        $ahj = $this->createAhjWithProjects();
        // No projects seeded

        $response = $this->get('/');

        $response->assertStatus(200);
        // Should not crash and should show the structure
        $response->assertSee('Test City AHJ');
        $response->assertSee('Total Projects');
    }

    /**
     * Negative / edge case tests
     */

    /** @test */
    public function xss_in_date_params_is_escaped()
    {
        $ahj = $this->createAhjWithProjects();

        $response = $this->get('/?start_date=<script>alert(1)</script>');

        $response->assertStatus(200);
        $response->assertDontSee('<script>alert(1)</script>', false);
    }

    /** @test */
    public function null_submitted_at_does_not_crash_the_table()
    {
        $ahj = $this->createAhjWithProjects();
        
        // Create a draft project with null submitted_at
        Project::create([
            'title' => 'Draft Project',
            'ahj_id' => $ahj->id,
            'project_type_id' => 'PV',
            'status' => 'draft',
            'submitted_at' => null,
            'approved_at' => null,
        ]);

        $response = $this->get('/');

        $response->assertStatus(200);
        // Should not return 500 error - draft projects don't appear in the table
        // because they have null submitted_at, but the page should load without crashing
        $response->assertDontSee('Draft Project'); // Draft projects don't show in the table
    }

    /** @test */
    public function avg_approval_time_with_hours_only()
    {
        $ahj = $this->createAhjWithProjects();
        
        $submittedAt = Carbon::now()->subHours(2)->subMinutes(15);
        $approvedAt = Carbon::now();
        
        Project::create([
            'title' => 'Quick Approval',
            'ahj_id' => $ahj->id,
            'project_type_id' => 'PV',
            'status' => 'approved',
            'submitted_at' => $submittedAt,
            'approved_at' => $approvedAt,
        ]);

        $response = $this->get('/');

        $response->assertStatus(200);
        $response->assertSee('2 hours, 15 minutes');
        $response->assertDontSee('0 days');
    }

    /** @test */
    public function avg_approval_time_with_minutes_only()
    {
        $ahj = $this->createAhjWithProjects();
        
        $submittedAt = Carbon::now()->subMinutes(45);
        $approvedAt = Carbon::now();
        
        Project::create([
            'title' => 'Very Quick Approval',
            'ahj_id' => $ahj->id,
            'project_type_id' => 'PV',
            'status' => 'approved',
            'submitted_at' => $submittedAt,
            'approved_at' => $approvedAt,
        ]);

        $response = $this->get('/');

        $response->assertStatus(200);
        $response->assertSee('45 minutes');
        $response->assertDontSee('0 hours');
    }

    /** @test */
    public function avg_approval_time_with_very_large_value()
    {
        $ahj = $this->createAhjWithProjects();
        
        $submittedAt = Carbon::now()->subDays(30);
        $approvedAt = Carbon::now();
        
        Project::create([
            'title' => 'Slow Approval',
            'ahj_id' => $ahj->id,
            'project_type_id' => 'PV',
            'status' => 'approved',
            'submitted_at' => $submittedAt,
            'approved_at' => $approvedAt,
        ]);

        $response = $this->get('/');

        $response->assertStatus(200);
        $response->assertSee('30 days');
        // Should not cause layout issues
        $response->assertStatus(200);
    }

    /** @test */
    public function avg_approval_time_near_zero()
    {
        $ahj = $this->createAhjWithProjects();
        
        $now = Carbon::now();
        
        Project::create([
            'title' => 'Instant Approval',
            'ahj_id' => $ahj->id,
            'project_type_id' => 'PV',
            'status' => 'approved',
            'submitted_at' => $now,
            'approved_at' => $now,
        ]);

        $response = $this->get('/');

        $response->assertStatus(200);
        $response->assertSee('Less than 1 minute');
    }

    /** @test */
    public function pagination_not_shown_with_exactly_20_projects()
    {
        $ahj = $this->createAhjWithProjects();
        
        // Create exactly 20 projects
        for ($i = 1; $i <= 20; $i++) {
            Project::create([
                'title' => "Project $i",
                'ahj_id' => $ahj->id,
                'project_type_id' => 'PV',
                'status' => 'submitted',
                'submitted_at' => Carbon::now()->subDays($i),
                'approved_at' => null,
            ]);
        }

        $response = $this->get('/');

        $response->assertStatus(200);
        // Should NOT have Next link with exactly 20 projects
        $response->assertDontSee('Next', false);
    }

    /** @test */
    public function clear_link_resets_to_clean_url()
    {
        $ahj = $this->createAhjWithProjects();

        $response = $this->get('/');

        $response->assertStatus(200);
        $response->assertSee('href="/"', false);
        $response->assertDontSee('href="/?', false); // No query params in clear link
    }

    /** @test */
    public function table_renders_with_special_characters_in_title()
    {
        $ahj = $this->createAhjWithProjects();
        
        Project::create([
            'title' => '<b>O\'Malley & Sons "Solar"</b>',
            'ahj_id' => $ahj->id,
            'project_type_id' => 'PV',
            'status' => 'submitted',
            'submitted_at' => Carbon::now(),
            'approved_at' => null,
        ]);

        $response = $this->get('/');

        $response->assertStatus(200);
        // Should be escaped - we should NOT see the raw HTML tags
        $response->assertDontSee('<b>O\'Malley & Sons "Solar"</b>', false);
        // But we should see the escaped content (Laravel automatically escapes with {{ }})
        $response->assertSee('&lt;b&gt;O&#039;Malley &amp; Sons &quot;Solar&quot;&lt;/b&gt;', false);
    }

    /** @test */
    public function date_form_uses_get_method()
    {
        $ahj = $this->createAhjWithProjects();

        $response = $this->get('/');

        $response->assertStatus(200);
        $response->assertSee('method="GET"', false);
    }
}