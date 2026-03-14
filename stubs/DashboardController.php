<?php

namespace App\Http\Controllers\Ahj;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\Ahj;
use App\Models\Project;
use Carbon\Carbon;

class DashboardController extends Controller
{
    public function index(Request $request)
    {
        // ----------------------------------------------------------------
        // INSTRUCTIONS:
        // ----------------------------------------------------------------
        // This controller is already connected to a SQLite database seeded
        // with ~160 projects. You can use standard Eloquent relationships.
        //
        // NOTE: This code was written by a previous contractor and shipped
        // quickly. Feel free to improve anything you see fit.
        // ----------------------------------------------------------------

        // MOCK AUTH: If not logged in, use the first AHJ found in the DB.
        // In a real app, this would be `auth()->user()->ahj`.
        $ahj = auth()->user()?->ahj ?? Ahj::first();

        if (!$ahj) {
            abort(500, 'Database is empty. Did you run the migration/seeder?');
        }

        // ----------------------------------------------------------------
        // YOUR LOGIC STARTS HERE
        // ----------------------------------------------------------------

        // TODO: Apply Date Range Filters to this query
        $projectsQuery = $ahj->projects();

        // Get all projects to calculate stats
        $allProjects = $projectsQuery->get();

        $stats = [
            'total_projects' => $allProjects->count(),
            'approved_projects' => $allProjects->where('status', 'approved')->count(),
            'pending_projects' => $allProjects->whereIn('status', ['submitted', 'revision_required'])->count(),
            'avg_approval_time' => 'N/A', // TODO: Calculate this efficiently
        ];

        // TODO: Replace limit(10) with ->paginate()
        $projects = $ahj->projects()->latest('submitted_at')->limit(10)->get();

        return view('pages.ahj.dashboard', [
            'ahj' => $ahj,
            'stats' => $stats,
            'projects' => $projects
        ]);
    }
}
