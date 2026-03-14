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
        // MOCK AUTH: If not logged in, use the first AHJ found in the DB.
        // In a real app, this would be `auth()->user()->ahj`.
        $ahj = auth()->user()?->ahj ?? Ahj::first();

        if (!$ahj) {
            abort(500, 'Database is empty. Did you run the migration/seeder?');
        }

        // Validate and extract date filters
        $startDate = $request->input('start_date');
        $endDate = $request->input('end_date');
        
        // Validate date formats and logical order
        if ($startDate && !$this->isValidDate($startDate)) {
            $startDate = null;
        }
        if ($endDate && !$this->isValidDate($endDate)) {
            $endDate = null;
        }
        if ($startDate && $endDate && Carbon::parse($startDate)->gt(Carbon::parse($endDate))) {
            $startDate = $endDate = null;
        }

        // Build base projects query with date filtering
        $projectsQuery = $ahj->projects();
        if ($startDate && $endDate) {
            $projectsQuery->whereBetween('submitted_at', [
                Carbon::parse($startDate)->startOfDay(),
                Carbon::parse($endDate)->endOfDay()
            ]);
        }

        // Calculate stats using database aggregates (efficient)
        $stats = [
            'total_projects' => $projectsQuery->count(),
            'approved_projects' => (clone $projectsQuery)->where('status', 'approved')->count(),
            'pending_projects' => (clone $projectsQuery)->whereIn('status', ['submitted', 'revision_required'])->count(),
            'avg_approval_time' => $this->calculateAverageApprovalTime(clone $projectsQuery),
        ];

        // Get paginated projects with preserved filters
        // Only show projects that have been submitted (have submitted_at)
        $projects = (clone $projectsQuery)
            ->whereNotNull('submitted_at')
            ->latest('submitted_at')
            ->paginate(20)
            ->appends($request->only(['start_date', 'end_date']));

        return view('pages.ahj.dashboard', [
            'ahj' => $ahj,
            'stats' => $stats,
            'projects' => $projects,
            'filters' => [
                'start_date' => $startDate,
                'end_date' => $endDate,
            ],
        ]);
    }

    private function isValidDate($date)
    {
        try {
            Carbon::createFromFormat('Y-m-d', $date);
            return true;
        } catch (\Exception $e) {
            return false;
        }
    }

    private function calculateAverageApprovalTime($query)
    {
        $approvedProjects = $query
            ->where('status', 'approved')
            ->whereNotNull('submitted_at')
            ->whereNotNull('approved_at')
            ->get(['submitted_at', 'approved_at']);

        if ($approvedProjects->isEmpty()) {
            return null;
        }

        $totalSeconds = $approvedProjects->sum(function ($project) {
            return $project->submitted_at->diffInSeconds($project->approved_at);
        });

        return (int) ($totalSeconds / $approvedProjects->count());
    }
}
