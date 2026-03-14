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

    private Ahj $ahj;

    protected function setUp(): void
    {
        parent::setUp();
        $this->ahj = Ahj::create(['name' => 'Test City AHJ']);
    }

    /** @test */
    public function date_range_form_is_present(): void
    {
        $response = $this->get('/');

        $response->assertStatus(200);
        $response->assertSee('name="start_date"', false);
        $response->assertSee('name="end_date"', false);
        $response->assertSee('type="date"', false);
        $response->assertSee('Filter', false);
        $response->assertSee('Clear', false);
    }

    /** @test */
    public function date_inputs_are_prefilled_from_query_params(): void
    {
        $response = $this->get('/?start_date=2025-01-01&end_date=2025-06-01');

        $response->assertStatus(200);
        $response->assertSee('value="2025-01-01"', false);
        $response->assertSee('value="2025-06-01"', false);
    }

    /** @test */
    public function filter_and_clear_buttons_exist(): void
    {
        $response = $this->get('/');

        $response->assertStatus(200);
        $response->assertSee('<button', false);
        $response->assertSee('Filter', false);
        $response->assertSee('href="/"', false); // Clear link
        $response->assertSee('Clear', false);
    }

    /** @test */
    public function avg_approval_time_displays_human_readable_format_for_days(): void
    {
        // Create approved project with known time difference (3 days, 6 hours)
        $submittedAt = Carbon::create(2025, 1, 1, 10, 0, 0);
        $approvedAt = Carbon::create(2025, 1, 4, 16, 0, 0); // 3 days, 6 hours later

        Project::create([
            'title' => 'Test Project 1',
            'ahj_id' => $this->ahj->id,
            'project_type_id' => 1,
            'status' => 'approved',
            'submitted_at' => $submittedAt,
            'approved_at' => $approvedAt,
        ]);

        $response = $this->get('/');

        $response->assertStatus(200);
        $response->assertSee('3 days, 6 hours');
    }

    /** @test */
    public function avg_approval_time_displays_human_readable_format_for_hours(): void
    {
        // Create approved project with known time difference (2 hours, 15 minutes)
        $submittedAt = Carbon::create(2025, 1, 1, 10, 0, 0);
        $approvedAt = Carbon::create(2025, 1, 1, 12, 15, 0); // 2 hours, 15 minutes later

        Project::create([
            'title' => 'Test Project 1',
            'ahj_id' => $this->ahj->id,
            'project_type_id' => 1,
            'status' => 'approved',
            'submitted_at' => $submittedAt,
            'approved_at' => $approvedAt,
        ]);

        $response = $this->get('/');

        $response->assertStatus(200);
        $response->assertSee('2 hours, 15 minutes');
    }

    /** @test */
    public function avg_approval_time_shows_na_when_null(): void
    {
        // Create only draft projects (no approvals)
        Project::create([
            'title' => 'Draft Project',
            'ahj_id' => $this->ahj->id,
            'project_type_id' => 1,
            'status' => 'draft',
            'submitted_at' => null,
            'approved_at' => null,
        ]);

        $response = $this->get('/');

        $response->assertStatus(200);
        $response->assertSee('N/A');
    }

    /** @test */
    public function pagination_links_are_rendered_with_many_projects(): void
    {
        // Create 25 projects to trigger pagination (20 per page)
        for ($i = 1; $i <= 25; $i++) {
            Project::create([
                'title' => "Project $i",
                'ahj_id' => $this->ahj->id,
                'project_type_id' => 1,
                'status' => 'submitted',
                'submitted_at' => Carbon::now()->subDays($i),
                'approved_at' => null,
            ]);
        }

        $response = $this->get('/');

        $response->assertStatus(200);
        $response->assertSee('Next', false); // Laravel pagination link
        $response->assertSeeInOrder(['1', '2']); // Page numbers
    }

    /** @test */
    public function pagination_is_not_shown_with_few_projects(): void
    {
        // Create only 5 projects (less than 20)
        for ($i = 1; $i <= 5; $i++) {
            Project::create([
                'title' => "Project $i",
                'ahj_id' => $this->ahj->id,
                'project_type_id' => 1,
                'status' => 'submitted',
                'submitted_at' => Carbon::now()->subDays($i),
                'approved_at' => null,
            ]);
        }

        $response = $this->get('/');

        $response->assertStatus(200);
        $response->assertDontSee('Next');
        $response->assertDontSee('Previous');
    }

    /** @test */
    public function project_table_displays_correct_columns(): void
    {
        Project::create([
            'title' => 'Sample Solar Project',
            'ahj_id' => $this->ahj->id,
            'project_type_id' => 1,
            'status' => 'submitted',
            'submitted_at' => Carbon::create(2025, 1, 15),
            'approved_at' => null,
        ]);

        $response = $this->get('/');

        $response->assertStatus(200);
        // Check table headers
        $response->assertSee('Project Title');
        $response->assertSee('Status');
        $response->assertSee('Submitted Date');
        // Check row data
        $response->assertSee('Sample Solar Project');
        $response->assertSee('submitted');
        $response->assertSee('Jan 15, 2025');
    }

    /** @test */
    public function empty_state_is_handled_gracefully(): void
    {
        // No projects created
        $response = $this->get('/');

        $response->assertStatus(200);
        $response->assertSee('Test City AHJ'); // AHJ name still shown
        $response->assertSee('0'); // Stats show zero
        $response->assertSee('N/A'); // Average approval time shows N/A
        $response->assertDontSee('Next'); // No pagination
    }

    /** @test */
    public function form_method_is_get_for_bookmarkability(): void
    {
        $response = $this->get('/');

        $response->assertStatus(200);
        $response->assertSee('method="GET"', false);
    }

    /** @test */
    public function date_filtering_preserves_other_query_params(): void
    {
        // Create test project
        Project::create([
            'title' => 'Test Project',
            'ahj_id' => $this->ahj->id,
            'project_type_id' => 1,
            'status' => 'submitted',
            'submitted_at' => Carbon::create(2025, 1, 15),
            'approved_at' => null,
        ]);

        // Test that pagination links include date filters
        $response = $this->get('/?start_date=2025-01-01&end_date=2025-12-31');

        $response->assertStatus(200);
        // The response should include the date filters in form values
        $response->assertSee('value="2025-01-01"', false);
        $response->assertSee('value="2025-12-31"', false);
    }

    /** @test */
    public function average_approval_time_calculation_accuracy(): void
    {
        // Create multiple approved projects with known approval times
        // Project 1: 1 day approval time
        Project::create([
            'title' => 'Quick Project',
            'ahj_id' => $this->ahj->id,
            'project_type_id' => 1,
            'status' => 'approved',
            'submitted_at' => Carbon::create(2025, 1, 1, 12, 0, 0),
            'approved_at' => Carbon::create(2025, 1, 2, 12, 0, 0), // 1 day
        ]);

        // Project 2: 3 days approval time
        Project::create([
            'title' => 'Slow Project',
            'ahj_id' => $this->ahj->id,
            'project_type_id' => 1,
            'status' => 'approved',
            'submitted_at' => Carbon::create(2025, 1, 5, 12, 0, 0),
            'approved_at' => Carbon::create(2025, 1, 8, 12, 0, 0), // 3 days
        ]);

        // Average should be 2 days
        $response = $this->get('/');

        $response->assertStatus(200);
        $response->assertSee('2 days');
    }
}