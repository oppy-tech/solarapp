@extends('layouts.app')

@section('content')
<div class="container mx-auto px-4 py-6">
    <div class="mb-8">
        <h1 class="text-2xl font-bold mb-2">Welcome, {{ $ahj->name }}</h1>
        <p class="text-gray-600">Here is your jurisdiction's activity.</p>
    </div>

    <!-- TODO: Add Date Range Filter Here -->

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
            <p class="text-3xl font-bold text-blue-600">{{ $stats['avg_approval_time'] }}</p>
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
    </div>
</div>
@endsection
