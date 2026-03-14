@extends('layouts.app')

@section('content')
<div class="container mx-auto px-4 py-6">
    <div class="mb-8">
        <h1 class="text-2xl font-bold mb-2">Welcome, {{ $ahj->name }}</h1>
        <p class="text-gray-600">Here is your jurisdiction's activity.</p>
    </div>

    <!-- Date Range Filter -->
    <div class="bg-white p-6 rounded-lg shadow mb-8">
        <form method="GET" action="/" class="flex flex-wrap items-end gap-4">
            <div class="flex-1 min-w-32">
                <label for="start_date" class="block text-sm font-medium text-gray-700 mb-1">Start Date</label>
                <input type="date" name="start_date" id="start_date" 
                       value="{{ request('start_date') }}"
                       class="block w-full px-3 py-2 border border-gray-300 rounded-md shadow-sm focus:outline-none focus:ring-blue-500 focus:border-blue-500">
            </div>
            <div class="flex-1 min-w-32">
                <label for="end_date" class="block text-sm font-medium text-gray-700 mb-1">End Date</label>
                <input type="date" name="end_date" id="end_date" 
                       value="{{ request('end_date') }}"
                       class="block w-full px-3 py-2 border border-gray-300 rounded-md shadow-sm focus:outline-none focus:ring-blue-500 focus:border-blue-500">
            </div>
            <div class="flex gap-2">
                <button type="submit" class="bg-blue-600 hover:bg-blue-700 text-white px-4 py-2 rounded-md shadow-sm focus:outline-none focus:ring-2 focus:ring-blue-500 focus:ring-offset-2">
                    Filter
                </button>
                <a href="/" class="bg-gray-200 hover:bg-gray-300 text-gray-700 px-4 py-2 rounded-md shadow-sm focus:outline-none focus:ring-2 focus:ring-gray-500 focus:ring-offset-2 inline-flex items-center">
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
                    if (is_null($avgTime) || $avgTime === 'N/A' || !is_numeric($avgTime)) {
                        echo 'N/A';
                    } else {
                        $days = floor($avgTime / 86400);
                        $hours = floor(($avgTime % 86400) / 3600);
                        $minutes = floor(($avgTime % 3600) / 60);
                        
                        $parts = [];
                        if ($days > 0) $parts[] = $days . ' day' . ($days > 1 ? 's' : '');
                        if ($hours > 0) $parts[] = $hours . ' hour' . ($hours > 1 ? 's' : '');
                        if ($minutes > 0 && $days == 0) $parts[] = $minutes . ' minute' . ($minutes > 1 ? 's' : '');
                        
                        echo empty($parts) ? '< 1 minute' : implode(', ', $parts);
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
                    <td class="px-6 py-4">{{ $project->submitted_at->format('M d, Y') }}</td>
                </tr>
                @endforeach
            </tbody>
        </table>
        
        <!-- Pagination Navigation -->
        <div class="px-6 py-4 bg-gray-50 border-t">
            @if(method_exists($projects, 'links'))
                {{ $projects->links() }}
            @else
                {{-- Pagination will be available once backend implements ->paginate() --}}
                <p class="text-sm text-gray-600">Showing all projects</p>
            @endif
        </div>
    </div>
</div>
@endsection
