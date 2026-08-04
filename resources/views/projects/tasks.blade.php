@extends('layouts.app')

@section('title', 'Tasks · '.$project->name)

@section('content')
<h1>{{ $project->name }}</h1>
<p class="lede">Task status from the mobile app.</p>
@include('projects._nav')

<div class="panel">
    <table>
        <thead>
            <tr><th>Title</th><th>Status</th><th>Priority</th><th>Due</th><th>Assignee</th></tr>
        </thead>
        <tbody>
            @forelse ($tasks as $task)
                <tr>
                    <td>{{ $task->title }}</td>
                    <td><span class="badge">{{ $task->status }}</span></td>
                    <td>{{ $task->priority }}</td>
                    <td>{{ optional($task->due_date)->toFormattedDateString() ?: '—' }}</td>
                    <td>{{ $task->assigned_to ?: '—' }}</td>
                </tr>
            @empty
                <tr><td colspan="5">No tasks yet.</td></tr>
            @endforelse
        </tbody>
    </table>
</div>
@endsection
