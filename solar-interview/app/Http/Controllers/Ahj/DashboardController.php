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

        // Get and validate date filters
        $startDate = $this->validateDate($request->query('start_date'));
        $endDate = $this->validateDate($request->query('end_date'));

        // Ensure valid date range
        if ($startDate && $endDate && $startDate > $endDate) {
            $startDate = null;
            $endDate = null;
        }

        // Build base query with date filters
        $baseQueryBuilder = $ahj->projects();
        
        if ($startDate && $endDate) {
            $baseQueryBuilder->whereBetween('submitted_at', [$startDate . ' 00:00:00', $endDate . ' 23:59:59']);
        } elseif ($startDate) {
            $baseQueryBuilder->where('submitted_at', '>=', $startDate . ' 00:00:00');
        } elseif ($endDate) {
            $baseQueryBuilder->where('submitted_at', '<=', $endDate . ' 23:59:59');
        }

        // Calculate stats using cloned queries to avoid interference
        $stats = [
            'total_projects' => (clone $baseQueryBuilder)->count(),
            'approved_projects' => (clone $baseQueryBuilder)->where('status', 'approved')->count(),
            'pending_projects' => (clone $baseQueryBuilder)->whereIn('status', ['submitted', 'revision_required'])->count(),
            'avg_approval_time' => $this->calculateAverageApprovalTime($baseQueryBuilder),
        ];

        // Get paginated projects with filter preservation
        $projects = (clone $baseQueryBuilder)->whereNotNull('submitted_at')
            ->latest('submitted_at')
            ->paginate(20)
            ->appends($request->query());

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

    private function validateDate($date)
    {
        if (empty($date) || !is_string($date)) {
            return null;
        }

        try {
            $parsedDate = Carbon::createFromFormat('Y-m-d', $date);
            return $parsedDate->format('Y-m-d');
        } catch (\Exception $e) {
            return null;
        }
    }

    private function calculateAverageApprovalTime($query)
    {
        // Clone the query to avoid modifying the original
        $approvalQuery = clone $query;
        
        // Use different SQL for different database drivers
        $driver = config('database.default');
        $connection = config("database.connections.{$driver}.driver");
        
        if ($connection === 'sqlite') {
            // SQLite: Calculate seconds using julianday
            $avgSeconds = $approvalQuery
                ->where('status', 'approved')
                ->whereNotNull('submitted_at')
                ->whereNotNull('approved_at')
                ->selectRaw('AVG((julianday(approved_at) - julianday(submitted_at)) * 86400) as avg_seconds')
                ->value('avg_seconds');
        } else {
            // MySQL: Use TIMESTAMPDIFF
            $avgSeconds = $approvalQuery
                ->where('status', 'approved')
                ->whereNotNull('submitted_at')
                ->whereNotNull('approved_at')
                ->selectRaw('AVG(TIMESTAMPDIFF(SECOND, submitted_at, approved_at)) as avg_seconds')
                ->value('avg_seconds');
        }

        return $avgSeconds !== null ? (int) round($avgSeconds) : null;
    }
}
