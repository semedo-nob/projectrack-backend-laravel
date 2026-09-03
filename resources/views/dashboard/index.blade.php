@extends('layouts.app')

@section('title', 'Dashboard · ProjectRack')

@section('content')
@php use App\Support\Labels; @endphp

<p class="section-label">Overview</p>
<h1 class="greeting">{{ $greeting }}, {{ auth()->user()->name }}</h1>
<p class="lede">{{ $activeCount }} active projects · {{ $openTasks }} open tasks — synced from the field app.</p>

<div class="grid grid-4" style="margin-bottom:1.25rem">
    <div class="panel stat">
        <strong>{{ $projects->count() }}</strong>
        <span>Projects</span>
    </div>
    <div class="panel stat">
        <strong class="money">{{ Labels::money($totalSpent) }}</strong>
        <span>Total spent</span>
    </div>
    <div class="panel stat">
        <strong class="money">{{ Labels::money($totalBudget) }}</strong>
        <span>Total budget</span>
    </div>
    <div class="panel stat">
        <strong class="money">{{ Labels::money($remainingBudget) }}</strong>
        <span>Remaining</span>
    </div>
</div>

@if ($trend->isNotEmpty())
<div class="panel" style="margin-bottom:1.25rem">
    <p class="section-label">30-day spend trend</p>
    <div class="spark" aria-hidden="true">
        @foreach ($trend as $point)
            @php $h = max(8, round(($point['total'] / $maxTrend) * 56)); @endphp
            <i style="height: {{ $h }}px" title="{{ $point['day'] }}: {{ Labels::money($point['total']) }}"></i>
        @endforeach
    </div>
</div>
@endif

<p class="section-label">Your projects</p>
@if ($projects->isEmpty())
    <div class="panel empty">
        <p>No projects yet. Ask a supervisor to share a link from the mobile app (<strong>Share with owner</strong>), then accept it here.</p>
    </div>
@else
    <div class="grid grid-2">
        @foreach ($projects as $project)
            @php
                $used = Labels::budgetUsedPercent((float) $project->budget, (float) $project->spent);
                $tone = Labels::statusTone('project', $project->status);
                $barClass = $used >= 100 ? 'danger' : ($used >= 80 ? 'warn' : '');
            @endphp
            <a class="panel project-card" href="{{ route('projects.show', $project) }}">
                <div class="meta">
                    <span class="badge {{ $tone }}">{{ Labels::projectStatus($project->status) }}</span>
                    <span>{{ $project->category }}</span>
                    <span class="badge muted">{{ $project->pivot->role }}</span>
                </div>
                <h2>{{ $project->name }}</h2>
                <p>{{ \Illuminate\Support\Str::limit($project->description ?: 'No description', 110) }}</p>
                <div class="bar {{ $barClass }}" title="Budget used {{ round($used) }}%">
                    <span style="width: {{ round($used) }}%"></span>
                </div>
                <div class="meta" style="margin-top:0.75rem">
                    <span class="money">{{ Labels::money($project->spent) }} / {{ Labels::money($project->budget) }}</span>
                    <span>{{ $project->expenses_count }} expenses</span>
                    <span>{{ $project->open_tasks_count }} open tasks</span>
                </div>
            </a>
        @endforeach
    </div>
@endif
@endsection
