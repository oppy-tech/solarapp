<?php

namespace Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;
use App\Models\Ahj;

class SmokeTest extends TestCase
{
    use RefreshDatabase;

    public function test_dashboard_loads_with_seeded_ahj(): void
    {
        $ahj = Ahj::create(['name' => 'Test City']);

        $response = $this->get('/');

        $response->assertStatus(200);
        $response->assertSee('Test City');
    }
}
