@extends('layouts.app')

@section('title', 'Reports · '.$project->name)

@section('content')
<h1>{{ $project->name }}</h1>
<p class="lede">Spend versus budget and category breakdown.</p>
@include('projects._nav')

<div class="grid grid-2" style="margin-bottom:1rem">
    <div class="panel">
        <h2>Budget utilization</h2>
        @php
            $pct = $project->budget > 0 ? min(100, ($project->spent / $project->budget) * 100) : 0;
        @endphp
        <p class="muted">{{ number_format($project->spent, 2) }} of {{ number_format($project->budget, 2) }}</p>
        <div class="bar"><span style="width: {{ round($pct) }}%"></span></div>
    </div>
    <div class="panel stat">
        <strong>{{ round($pct) }}%</strong>
        <span>of budget used</span>
    </div>
</div>

<div class="panel">
    <h2>Spend by category</h2>
    <table>
        <thead><tr><th>Category</th><th>Total</th><th>Share</th></tr></thead>
        <tbody>
            @php $sum = max(0.0001, $byCategory->sum('total')); @endphp
            @forelse ($byCategory as $row)
                <tr>
                    <td>{{ $row->category }}</td>
                    <td>{{ number_format($row->total, 2) }}</td>
                    <td>
                        <div class="bar"><span style="width: {{ round(($row->total / $sum) * 100) }}%"></span></div>
                    </td>
                </tr>
            @empty
                <tr><td colspan="3">No expense data.</td></tr>
            @endforelse
        </tbody>
    </table>
</div>
@endsection
