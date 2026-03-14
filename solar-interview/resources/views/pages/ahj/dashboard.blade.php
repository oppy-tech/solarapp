@extends('layouts.app')

@section('content')
<div class="container mx-auto px-4 py-6">
    <div class="mb-8">
        <h1 class="text-2xl font-bold mb-2">Welcome, {{ $ahj->name }}</h1>
        <p class="text-gray-600">Here is your jurisdiction's activity.</p>
    </div>

    <!-- Date Range Filter Form -->
    <div class="bg-white p-6 rounded-lg shadow mb-8">
        <form method="GET" action="/" class="flex flex-wrap items-end gap-4">
            <div class="flex-1 min-w-32">
                <label for="start_date" class="block text-sm font-medium text-gray-700 mb-1">Start Date</label>
                <input type="date" 
                       id="start_date" 
                       name="start_date" 
                       value="{{ $filters['start_date'] ?? '' }}"
                       class="w-full border border-gray-300 rounded-md px-3 py-2 focus:ring-2 focus:ring-blue-500 focus:border-blue-500">
            </div>
            <div class="flex-1 min-w-32">
                <label for="end_date" class="block text-sm font-medium text-gray-700 mb-1">End Date</label>
                <input type="date" 
                       id="end_date" 
                       name="end_date" 
                       value="{{ $filters['end_date'] ?? '' }}"
                       class="w-full border border-gray-300 rounded-md px-3 py-2 focus:ring-2 focus:ring-blue-500 focus:border-blue-500">
            </div>
            <div class="flex gap-2">
                <button type="submit" 
                        class="bg-blue-600 hover:bg-blue-700 text-white font-medium px-4 py-2 rounded-md transition-colors">
                    Filter
                </button>
                <a href="/" 
                   class="bg-gray-500 hover:bg-gray-600 text-white font-medium px-4 py-2 rounded-md transition-colors">
                    Clear
                </a>
            </div>
        </form>
    </div>

    <!-- Statistics Cards -->
    <div class="grid grid-cols-1 md:grid-cols-3 gap-6 mb-8">
        <div class="bg-white p-6 rounded-lg shadow">
            <h3 class="text-gray-500 text-sm font-uppercase">Total Projects</h3>
            <p class="text-3xl font-bold">{{ $stats['total_projects'] }}</p>
        </div>
        <div class="bg-white p-6 rounded-lg shadow">
            <h3 class="text-gray-500 text-sm font-uppercase">Approved</h3>
            <p class="text-3xl font-bold text-green-600">{{ $stats['approved_projects'] }}</p>
        </div>
        <div class="bg-white p-6 rounded-lg shadow">
            <h3 class="text-gray-500 text-sm font-uppercase">Avg. Approval Time</h3>
            <p class="text-3xl font-bold text-blue-600">
                @php
                    $avgTime = $stats['avg_approval_time'];
                    if ($avgTime === null) {
                        echo 'N/A';
                    } else {
                        $totalSeconds = (int) $avgTime;
                        $days = (int) floor($totalSeconds / 86400);
                        $hours = (int) floor(($totalSeconds % 86400) / 3600);
                        $minutes = (int) floor(($totalSeconds % 3600) / 60);

                        $parts = [];
                        if ($days > 0) $parts[] = $days . ' day' . ($days !== 1 ? 's' : '');
                        if ($hours > 0) $parts[] = $hours . ' hour' . ($hours !== 1 ? 's' : '');
                        if ($minutes > 0 && $days === 0) $parts[] = $minutes . ' minute' . ($minutes !== 1 ? 's' : '');
                        
                        echo !empty($parts) ? implode(', ', $parts) : 'Less than 1 minute';
                    }
                @endphp
            </p>
        </div>
    </div>

    <!-- Projects List -->
    <div class="bg-white rounded-lg shadow overflow-hidden">
        <table class="min-w-full">
            <thead class="bg-gray-50 text-left">
                <tr>
                    <th class="px-6 py-3 text-xs font-medium text-gray-500 uppercase">Project Title</th>
                    <th class="px-6 py-3 text-xs font-medium text-gray-500 uppercase">Status</th>
                    <th class="px-6 py-3 text-xs font-medium text-gray-500 uppercase">Submitted Date</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-gray-200">
                @foreach($projects as $project)
                <tr>
                    <td class="px-6 py-4">{{ $project->title }}</td>
                    <td class="px-6 py-4">{{ $project->status }}</td>
                    <td class="px-6 py-4">{{ $project->submitted_at?->format('M d, Y') ?? '—' }}</td>
                </tr>
                @endforeach
            </tbody>
        </table>
    </div>

    <!-- Pagination Navigation -->
    <div class="mt-6">
        {{ $projects->links() }}
    </div>
</div>
@endsection
