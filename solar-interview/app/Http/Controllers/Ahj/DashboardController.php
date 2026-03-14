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

        // Validate and extract date filter parameters
        $startDate = null;
        $endDate = null;
        
        if ($request->has('start_date') && $request->filled('start_date')) {
            try {
                $startDate = Carbon::createFromFormat('Y-m-d', $request->start_date)->startOfDay();
            } catch (\Exception $e) {
                // Invalid date format - ignore the filter
            }
        }
        
        if ($request->has('end_date') && $request->filled('end_date')) {
            try {
                $endDate = Carbon::createFromFormat('Y-m-d', $request->end_date)->endOfDay();
            } catch (\Exception $e) {
                // Invalid date format - ignore the filter
            }
        }
        
        // Ensure start_date <= end_date
        if ($startDate && $endDate && $startDate->gt($endDate)) {
            // Invalid range - ignore both filters
            $startDate = null;
            $endDate = null;
        }

        // Apply date range filters to the base query
        $projectsQuery = $ahj->projects();
        
        if ($startDate) {
            $projectsQuery->where('submitted_at', '>=', $startDate);
        }
        
        if ($endDate) {
            $projectsQuery->where('submitted_at', '<=', $endDate);
        }

        // Calculate stats using efficient database queries
        $totalProjects = $projectsQuery->count();
        $approvedProjectsCount = (clone $projectsQuery)->where('status', 'approved')->count();
        $pendingProjects = (clone $projectsQuery)->whereIn('status', ['submitted', 'revision_required'])->count();
        
        // Calculate average approval time for approved projects only
        $avgApprovalTimeQuery = (clone $projectsQuery)
            ->where('status', 'approved')
            ->whereNotNull('submitted_at')
            ->whereNotNull('approved_at');
            
        $avgApprovalTime = null;
        
        if ($avgApprovalTimeQuery->count() > 0) {
            // Get approved projects and calculate in PHP for cross-database compatibility
            $approvedProjectsForTime = $avgApprovalTimeQuery
                ->select(['submitted_at', 'approved_at'])
                ->get();
            
            $totalSeconds = 0;
            foreach ($approvedProjectsForTime as $project) {
                $totalSeconds += $project->submitted_at->diffInSeconds($project->approved_at);
            }
            
            if ($approvedProjectsForTime->count() > 0) {
                $avgApprovalTime = (int) round($totalSeconds / $approvedProjectsForTime->count());
            }
        }

        $stats = [
            'total_projects' => $totalProjects,
            'approved_projects' => $approvedProjectsCount,
            'pending_projects' => $pendingProjects,
            'avg_approval_time' => $avgApprovalTime,
        ];

        // Paginated projects with date filters preserved
        $projects = (clone $projectsQuery)
            ->latest('submitted_at')
            ->paginate(20)
            ->appends($request->query());

        // Prepare filters for view
        $filters = [
            'start_date' => $startDate ? $startDate->format('Y-m-d') : null,
            'end_date' => $endDate ? $endDate->format('Y-m-d') : null,
        ];

        return view('pages.ahj.dashboard', [
            'ahj' => $ahj,
            'stats' => $stats,
            'projects' => $projects,
            'filters' => $filters,
        ]);
    }
}
