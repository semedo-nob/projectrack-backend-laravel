@extends('layouts.app')

@section('title', 'Activities · '.$project->name)

@section('content')
@php use App\Support\Labels; @endphp
<h1>{{ $project->name }}</h1>
<p class="lede">Non-financial site activity logs from the field.</p>
@include('projects._nav')

<div class="grid grid-2" style="margin-bottom:1rem">
    <div class="panel stat"><strong>{{ $activities->total() }}</strong><span>Activity records</span></div>
    <div class="panel stat"><strong>{{ number_format($totalHours, 1) }}</strong><span>Hours logged</span></div>
</div>

<div class="panel">
    <table>
        <thead>
            <tr><th>When</th><th>Title</th><th>Type</th><th>Hours</th><th>By</th><th>Location</th></tr>
        </thead>
        <tbody>
            @forelse ($activities as $activity)
                <tr>
                    <td>{{ optional($activity->occurred_at)->toDayDateTimeString() }}</td>
                    <td>
                        <strong>{{ $activity->title }}</strong>
                        @if ($activity->description)
                            <div class="muted">{{ \Illuminate\Support\Str::limit($activity->description, 80) }}</div>
                        @endif
                    </td>
                    <td><span class="badge info">{{ Labels::activityType($activity->activity_type) }}</span></td>
                    <td>{{ $activity->hours_spent !== null ? number_format($activity->hours_spent, 1) : '—' }}</td>
                    <td>{{ $activity->performed_by ?: '—' }}</td>
                    <td>{{ $activity->location ?: '—' }}</td>
                </tr>
            @empty
                <tr><td colspan="6" class="empty">No activities yet.</td></tr>
            @endforelse
        </tbody>
    </table>
    <div class="pagination">{{ $activities->links() }}</div>
</div>
@endsection
