@extends('layouts.app')

@section('title', 'Schedule · '.$project->name)

@section('content')
<h1>{{ $project->name }}</h1>
<p class="lede">Milestones and deadlines.</p>
@include('projects._nav')

<div class="panel">
    <table>
        <thead>
            <tr><th>Title</th><th>Due</th><th>Status</th></tr>
        </thead>
        <tbody>
            @forelse ($milestones as $m)
                <tr>
                    <td>{{ $m->title }}</td>
                    <td>{{ optional($m->due_date)->toFormattedDateString() ?: '—' }}</td>
                    <td><span class="badge">{{ $m->status }}</span></td>
                </tr>
            @empty
                <tr><td colspan="3">No milestones yet.</td></tr>
            @endforelse
        </tbody>
    </table>
</div>
@endsection
