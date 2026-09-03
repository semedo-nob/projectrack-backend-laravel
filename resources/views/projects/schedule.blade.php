@extends('layouts.app')

@section('title', 'Schedule · '.$project->name)

@section('content')
@php use App\Support\Labels; @endphp
<h1>{{ $project->name }}</h1>
<p class="lede">Milestones and deadlines from the mobile schedule.</p>
@include('projects._nav')

<div class="grid grid-3" style="margin-bottom:1rem">
    <div class="panel stat"><strong>{{ $counts['total'] }}</strong><span>Total</span></div>
    <div class="panel stat"><strong>{{ $counts['done'] }}</strong><span>Done</span></div>
    <div class="panel stat"><strong>{{ $counts['overdue'] }}</strong><span>Overdue</span></div>
</div>

<div class="panel">
    <table>
        <thead>
            <tr><th>Title</th><th>Due</th><th>Status</th></tr>
        </thead>
        <tbody>
            @forelse ($milestones as $m)
                @php
                    $overdue = $m->status === 'pending' && $m->due_date && $m->due_date->isPast();
                @endphp
                <tr class="{{ $overdue ? 'row-overdue' : '' }}">
                    <td>
                        <strong>{{ $m->title }}</strong>
                        @if ($m->description)
                            <div class="muted">{{ \Illuminate\Support\Str::limit($m->description, 80) }}</div>
                        @endif
                    </td>
                    <td>
                        {{ optional($m->due_date)->toFormattedDateString() ?: '—' }}
                        @if ($overdue)<div class="muted" style="color:var(--danger)">Overdue</div>@endif
                    </td>
                    <td><span class="badge {{ Labels::statusTone('milestone', $m->status) }}">{{ Labels::milestoneStatus($m->status) }}</span></td>
                </tr>
            @empty
                <tr><td colspan="3" class="empty">No milestones yet.</td></tr>
            @endforelse
        </tbody>
    </table>
</div>
@endsection
