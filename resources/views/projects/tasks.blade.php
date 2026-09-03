@extends('layouts.app')

@section('title', 'Tasks · '.$project->name)

@section('content')
@php use App\Support\Labels; @endphp
<h1>{{ $project->name }}</h1>
<p class="lede">Task status from the mobile app.</p>
@include('projects._nav')

<div class="grid grid-4" style="margin-bottom:1rem">
    <div class="panel stat"><strong>{{ $counts['pending'] }}</strong><span>Pending</span></div>
    <div class="panel stat"><strong>{{ $counts['in_progress'] }}</strong><span>In progress</span></div>
    <div class="panel stat"><strong>{{ $counts['done'] }}</strong><span>Done</span></div>
    <div class="panel stat"><strong>{{ $counts['blocked'] }}</strong><span>Blocked</span></div>
</div>

<div class="panel">
    <table>
        <thead>
            <tr><th>Title</th><th>Status</th><th>Priority</th><th>Due</th><th>Assignee</th></tr>
        </thead>
        <tbody>
            @forelse ($tasks as $task)
                @php
                    $overdue = $task->due_date && $task->status !== 'done' && $task->due_date->isPast();
                @endphp
                <tr class="{{ $overdue ? 'row-overdue' : '' }}">
                    <td>
                        <strong>{{ $task->title }}</strong>
                        @if ($task->description)
                            <div class="muted">{{ \Illuminate\Support\Str::limit($task->description, 80) }}</div>
                        @endif
                    </td>
                    <td><span class="badge {{ Labels::statusTone('task', $task->status) }}">{{ Labels::taskStatus($task->status) }}</span></td>
                    <td><span class="badge {{ Labels::statusTone('priority', $task->priority) }}">{{ Labels::taskPriority($task->priority) }}</span></td>
                    <td>
                        {{ optional($task->due_date)->toFormattedDateString() ?: '—' }}
                        @if ($overdue)<div class="muted" style="color:var(--danger)">Overdue</div>@endif
                    </td>
                    <td>{{ $task->assigned_to ?: '—' }}</td>
                </tr>
            @empty
                <tr><td colspan="5" class="empty">No tasks yet.</td></tr>
            @endforelse
        </tbody>
    </table>
</div>
@endsection
