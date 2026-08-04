@extends('layouts.app')

@section('title', $project->name.' · ProjectRack')

@section('content')
<div class="meta">
    <span class="badge">{{ $project->status }}</span>
    <span>{{ $project->category }}</span>
</div>
<h1>{{ $project->name }}</h1>
<p class="lede">{{ $project->description ?: 'No description provided.' }}</p>

@include('projects._nav')

<div class="grid grid-3" style="margin-bottom:1rem">
    <div class="panel stat"><strong>{{ number_format($project->budget, 2) }}</strong><span>Budget</span></div>
    <div class="panel stat"><strong>{{ number_format($project->spent, 2) }}</strong><span>Spent</span></div>
    <div class="panel stat"><strong>{{ round(($project->progress ?? 0) * 100) }}%</strong><span>Progress</span></div>
</div>

<div class="panel">
    <h2>Details</h2>
    <table>
        <tr><th>Start</th><td>{{ optional($project->start_date)->toFormattedDateString() }}</td></tr>
        <tr><th>End</th><td>{{ optional($project->end_date)->toFormattedDateString() ?: '—' }}</td></tr>
        <tr><th>Tags</th>
            <td>
                @forelse ($project->tags as $tag)
                    <span class="badge">{{ $tag->tag }}</span>
                @empty
                    —
                @endforelse
            </td>
        </tr>
        <tr><th>Members</th>
            <td>
                @foreach ($project->members as $member)
                    <div>{{ $member->user?->name }} <span class="muted">({{ $member->role }})</span></div>
                @endforeach
            </td>
        </tr>
    </table>
</div>
@endsection
