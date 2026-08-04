@extends('layouts.app')

@section('title', 'Dashboard · ProjectRack')

@section('content')
<h1>Your projects</h1>
<p class="lede">Projects synced from the field app and shares accepted via owner links.</p>

@if ($projects->isEmpty())
    <div class="panel">
        <p>No projects yet. Ask a supervisor to share a project link from the mobile app, or create projects in the app and sync when online.</p>
    </div>
@else
    <div class="grid grid-2">
        @foreach ($projects as $project)
            <a class="panel project-card" href="{{ route('projects.show', $project) }}" style="color:inherit;text-decoration:none">
                <div class="meta">
                    <span class="badge">{{ $project->status }}</span>
                    <span>{{ $project->category }}</span>
                    <span>{{ $project->pivot->role }}</span>
                </div>
                <h2>{{ $project->name }}</h2>
                <p>{{ \Illuminate\Support\Str::limit($project->description ?: 'No description', 110) }}</p>
                <div class="bar" title="Progress"><span style="width: {{ min(100, round(($project->progress ?? 0) * 100)) }}%"></span></div>
                <div class="meta" style="margin-top:0.75rem">
                    <span>Budget {{ number_format($project->budget, 0) }}</span>
                    <span>Spent {{ number_format($project->spent, 0) }}</span>
                    <span>{{ $project->expenses_count }} expenses</span>
                </div>
            </a>
        @endforeach
    </div>
@endif
@endsection
