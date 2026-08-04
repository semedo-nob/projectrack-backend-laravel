@extends('layouts.app')

@section('title', 'Activities · '.$project->name)

@section('content')
<h1>{{ $project->name }}</h1>
<p class="lede">Non-financial site activity logs.</p>
@include('projects._nav')

<div class="panel">
    <table>
        <thead>
            <tr><th>When</th><th>Title</th><th>Type</th><th>Hours</th><th>By</th></tr>
        </thead>
        <tbody>
            @forelse ($activities as $activity)
                <tr>
                    <td>{{ optional($activity->occurred_at)->toDayDateTimeString() }}</td>
                    <td>{{ $activity->title }}</td>
                    <td>{{ $activity->activity_type }}</td>
                    <td>{{ $activity->hours_spent !== null ? number_format($activity->hours_spent, 1) : '—' }}</td>
                    <td>{{ $activity->performed_by ?: '—' }}</td>
                </tr>
            @empty
                <tr><td colspan="5">No activities yet.</td></tr>
            @endforelse
        </tbody>
    </table>
    <div style="margin-top:1rem">{{ $activities->links() }}</div>
</div>
@endsection
