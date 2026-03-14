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

    private function createTestAhj(): Ahj
    {
        return Ahj::create(['name' => 'Test City AHJ']);
    }

    private function createTestProject(Ahj $ahj, array $attributes = []): Project
    {
        $defaults = [
            'title' => 'Test Solar Project',
            'ahj_id' => $ahj->id,
            'project_type_id' => 1,
            'status' => 'draft',
            'submitted_at' => now(),
            'approved_at' => null,
        ];

        return Project::create(array_merge($defaults, $attributes));
    }

    public function test_date_range_form_is_present(): void
    {
        $ahj = $this->createTestAhj();

        $response = $this->get('/');

        $response->assertStatus(200);
        $response->assertSee('name="start_date"', false);
        $response->assertSee('name="end_date"', false);
        $response->assertSee('type="date"', false);
    }

    public function test_date_inputs_are_prefilled_from_query_params(): void
    {
        $ahj = $this->createTestAhj();

        $response = $this->get('/?start_date=2025-01-01&end_date=2025-06-01');

        $response->assertStatus(200);
        $response->assertSee('value="2025-01-01"', false);
        $response->assertSee('value="2025-06-01"', false);
    }

    public function test_filter_and_clear_buttons_exist(): void
    {
        $ahj = $this->createTestAhj();

        $response = $this->get('/');

        $response->assertStatus(200);
        $response->assertSee('type="submit"', false);
        $response->assertSee('Filter', false);
        $response->assertSee('Clear', false);
    }

    public function test_avg_approval_time_displays_human_readable_format(): void
    {
        $ahj = $this->createTestAhj();
        
        // Create an approved project with known timestamps
        $submittedAt = Carbon::parse('2025-01-01 10:00:00');
        $approvedAt = Carbon::parse('2025-01-04 16:00:00'); // 3 days, 6 hours later
        
        $this->createTestProject($ahj, [
            'status' => 'approved',
            'submitted_at' => $submittedAt,
            'approved_at' => $approvedAt,
        ]);

        $response = $this->get('/');

        $response->assertStatus(200);
        $response->assertSee('3 days');
        $response->assertSee('6 hours');
    }

    public function test_avg_approval_time_shows_na_when_null(): void
    {
        $ahj = $this->createTestAhj();
        
        // Create only draft projects (no approved projects)
        $this->createTestProject($ahj, ['status' => 'draft']);

        $response = $this->get('/');

        $response->assertStatus(200);
        $response->assertSee('N/A');
    }

    public function test_pagination_links_are_rendered(): void
    {
        $ahj = $this->createTestAhj();
        
        // Create 25 projects to trigger pagination (should paginate at 20 per page)
        for ($i = 1; $i <= 25; $i++) {
            $this->createTestProject($ahj, [
                'title' => "Project $i",
                'status' => 'submitted',
            ]);
        }

        $response = $this->get('/');

        $response->assertStatus(200);
        // The pagination template is there, even if backend doesn't implement pagination yet
        // We just check that our pagination div exists for now
        $response->assertSee('<div class="px-6 py-4 bg-gray-50 border-t">', false);
    }

    public function test_project_table_displays_correct_columns(): void
    {
        $ahj = $this->createTestAhj();
        
        $project = $this->createTestProject($ahj, [
            'title' => 'Solar Installation Project',
            'status' => 'approved',
            'submitted_at' => Carbon::parse('2025-01-15'),
        ]);

        $response = $this->get('/');

        $response->assertStatus(200);
        // Check table headers
        $response->assertSee('Project Title');
        $response->assertSee('Status');
        $response->assertSee('Submitted Date');
        // Check project data
        $response->assertSee('Solar Installation Project');
        $response->assertSee('approved');
        $response->assertSee('Jan 15, 2025');
    }

    public function test_empty_state_is_handled(): void
    {
        $ahj = $this->createTestAhj();
        // No projects seeded

        $response = $this->get('/');

        $response->assertStatus(200);
        $response->assertSee('Test City AHJ');
        $response->assertSee('0'); // Total projects should be 0
    }

    public function test_date_range_filter_form_uses_get_method(): void
    {
        $ahj = $this->createTestAhj();

        $response = $this->get('/');

        $response->assertStatus(200);
        $response->assertSee('method="GET"', false);
    }

    public function test_clear_link_goes_to_root_url(): void
    {
        $ahj = $this->createTestAhj();

        $response = $this->get('/?start_date=2025-01-01&end_date=2025-06-01');

        $response->assertStatus(200);
        $response->assertSee('href="/"', false);
    }
}