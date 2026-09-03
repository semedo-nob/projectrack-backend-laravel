@extends('layouts.app')

@section('title', 'Reports · '.$project->name)

@section('content')
@php
    use App\Support\Labels;
    $used = Labels::budgetUsedPercent((float) $project->budget, (float) $project->spent);
    $barClass = $used >= 100 ? 'danger' : ($used >= 80 ? 'warn' : '');
    $sum = max(0.0001, (float) $byCategory->sum('total'));
@endphp
<h1>{{ $project->name }}</h1>
<p class="lede">Spend versus budget and category breakdown — same insights as the app reports.</p>
@include('projects._nav')

<div class="grid grid-2" style="margin-bottom:1rem">
    <div class="panel">
        <p class="section-label">Budget utilization</p>
        <p class="muted money">{{ Labels::money($project->spent) }} of {{ Labels::money($project->budget) }}</p>
        <div class="bar {{ $barClass }}"><span style="width: {{ round($used) }}%"></span></div>
        @if ($used >= 80)
            <p class="tip">Over 80% of budget used.</p>
        @endif
    </div>
    <div class="panel stat">
        <strong>{{ round($used) }}%</strong>
        <span>of budget used</span>
        <div style="margin-top:0.85rem" class="muted">Progress {{ round(((float) ($project->progress ?? 0)) * 100) }}%</div>
    </div>
</div>

<div class="panel" style="margin-bottom:1rem">
    <p class="section-label">Budget forecast</p>
    <p class="muted">Burn rate from actual spend since project start. Category mix uses the same labels as expenses.</p>
    <div class="grid grid-2" style="margin-top:0.75rem">
        <div>
            <p class="muted">Burn / day</p>
            <p class="money">{{ Labels::money($forecast['burn_per_day']) }}</p>
        </div>
        <div>
            <p class="muted">Projected final cost</p>
            <p class="money">{{ Labels::money($forecast['projected_final_cost']) }}</p>
        </div>
        <div>
            <p class="muted">Remaining runway</p>
            <p>
                @if (is_null($forecast['runway_days']))
                    —
                @elseif ($forecast['runway_days'] === 0)
                    Depleted
                @else
                    {{ $forecast['runway_days'] }} days
                @endif
            </p>
        </div>
        <div>
            <p class="muted">Outlook</p>
            @if ($forecast['will_exceed'])
                <p class="tip">Over-budget warning — projected {{ round($forecast['projected_percent']) }}% of budget
                    @if ($forecast['projected_over_date'])
                        (around {{ $forecast['projected_over_date'] }})
                    @endif
                .</p>
            @else
                <p class="muted">On track versus budget.</p>
            @endif
        </div>
    </div>
</div>

<div class="grid grid-2">
    <div class="panel">
        <p class="section-label">Spend by category</p>
        <table>
            <thead><tr><th>Category</th><th>Total</th><th>Share</th></tr></thead>
            <tbody>
                @forelse ($byCategory as $row)
                    <tr>
                        <td>{{ Labels::expenseCategory($row->category) }}</td>
                        <td class="money">{{ Labels::money($row->total) }}</td>
                        <td>
                            <div class="bar"><span style="width: {{ round(($row->total / $sum) * 100) }}%"></span></div>
                        </td>
                    </tr>
                @empty
                    <tr><td colspan="3" class="empty">No expense data.</td></tr>
                @endforelse
            </tbody>
        </table>
    </div>
    <div class="panel">
        <p class="section-label">Expenses by status</p>
        <table>
            <thead><tr><th>Status</th><th>Count</th><th>Amount</th></tr></thead>
            <tbody>
                @forelse ($byStatus as $row)
                    <tr>
                        <td><span class="badge {{ Labels::statusTone('expense', $row->status) }}">{{ Labels::expenseStatus($row->status) }}</span></td>
                        <td>{{ $row->c }}</td>
                        <td class="money">{{ Labels::money($row->total) }}</td>
                    </tr>
                @empty
                    <tr><td colspan="3" class="empty">No expense data.</td></tr>
                @endforelse
            </tbody>
        </table>
    </div>
</div>
@endsection
