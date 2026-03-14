<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use App\Models\Ahj;
use App\Models\Project;
use Carbon\Carbon;

class InterviewSeeder extends Seeder
{
    public function run(): void
    {
        $jsonPath = base_path('stubs/sample_data.json');
        if (!file_exists($jsonPath)) {
            return;
        }

        $data = json_decode(file_get_contents($jsonPath), true);

        // 1. Create primary AHJ
        $ahjData = $data['ahj'];
        $ahj = Ahj::create([
            'id' => $ahjData['id'],
            'name' => $ahjData['name'],
            'created_at' => $ahjData['created_at'],
            'updated_at' => $ahjData['created_at'],
        ]);

        // 2. Create secondary AHJ (tests multi-tenancy)
        if (isset($data['ahj_secondary'])) {
            $ahj2Data = $data['ahj_secondary'];
            Ahj::create([
                'id' => $ahj2Data['id'],
                'name' => $ahj2Data['name'],
                'created_at' => $ahj2Data['created_at'],
                'updated_at' => $ahj2Data['created_at'],
            ]);
        }

        // 3. Create primary AHJ's projects (includes edge case data)
        foreach ($data['projects'] as $p) {
            // Skip comment entries
            if (isset($p['_comment'])) {
                continue;
            }
            Project::create([
                'id' => $p['id'],
                'ahj_id' => $ahj->id,
                'title' => $p['title'],
                'status' => $p['status'],
                'project_type_id' => $p['project_type_id'],
                'submitted_at' => $p['submitted_at'] ? Carbon::parse($p['submitted_at']) : null,
                'approved_at' => $p['approved_at'] ? Carbon::parse($p['approved_at']) : null,
            ]);
        }

        // 4. Create secondary AHJ's projects
        if (isset($data['projects_secondary'])) {
            foreach ($data['projects_secondary'] as $p) {
                Project::create([
                    'id' => $p['id'],
                    'ahj_id' => $p['ahj_id'],
                    'title' => $p['title'],
                    'status' => $p['status'],
                    'project_type_id' => $p['project_type_id'],
                    'submitted_at' => $p['submitted_at'] ? Carbon::parse($p['submitted_at']) : null,
                    'approved_at' => $p['approved_at'] ? Carbon::parse($p['approved_at']) : null,
                ]);
            }
        }

        // 5. Create a User attached to primary AHJ for auth()
        \App\Models\User::factory()->create([
            'name' => 'AHJ Admin',
            'email' => 'admin@solarville.gov',
            'password' => bcrypt('password'),
            'ahj_id' => $ahj->id,
        ]);

        $projectCount = count(array_filter($data['projects'], fn($p) => !isset($p['_comment'])));
        $secondaryCount = isset($data['projects_secondary']) ? count($data['projects_secondary']) : 0;
        echo "✅ Database seeded with " . ($projectCount + $secondaryCount) . " projects across " . (isset($data['ahj_secondary']) ? '2' : '1') . " AHJs.\n";
    }
}
