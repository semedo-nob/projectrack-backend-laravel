@extends('layouts.app')

@section('title', $project->name.' · ProjectRack')

@section('content')
@php
    use App\Support\Labels;
    $used = Labels::budgetUsedPercent((float) $project->budget, (float) $project->spent);
    $remaining = (float) $project->budget - (float) $project->spent;
    $progressPct = round(((float) ($project->progress ?? 0)) * 100);
    $barClass = $used >= 100 ? 'danger' : ($used >= 80 ? 'warn' : '');
@endphp

<div class="meta">
    <span class="badge {{ Labels::statusTone('project', $project->status) }}">{{ Labels::projectStatus($project->status) }}</span>
    <span>{{ $project->category }}</span>
</div>
<h1>{{ $project->name }}</h1>
<p class="lede">{{ $project->description ?: 'No description provided.' }}</p>

@include('projects._nav')

<div class="finance-grid" style="margin-bottom:1rem">
    <div class="panel">
        <p class="section-label">Financials</p>
        <div class="grid grid-3">
            <div class="stat"><strong class="money">{{ Labels::money($project->spent) }}</strong><span>Total spent</span></div>
            <div class="stat"><strong class="money">{{ Labels::money($project->budget) }}</strong><span>Budget</span></div>
            <div class="stat"><strong class="money">{{ Labels::money($remaining) }}</strong><span>Remaining</span></div>
        </div>
        <p class="section-label" style="margin-top:1rem">Budget used · {{ round($used) }}%</p>
        <div class="bar {{ $barClass }}"><span style="width: {{ round($used) }}%"></span></div>
        @if ($used >= 80)
            <p class="tip">Budget utilization is high — review recent expenses.</p>
        @endif
    </div>
    <div class="panel">
        <p class="section-label">Completion</p>
        <div class="stat"><strong>{{ $progressPct }}%</strong><span>Project progress</span></div>
        <div class="bar" style="margin-top:0.85rem"><span style="width: {{ $progressPct }}%"></span></div>
        <div class="meta" style="margin-top:1rem">
            <span>Started {{ optional($project->start_date)->toFormattedDateString() ?: '—' }}</span>
            <span>Ends {{ optional($project->end_date)->toFormattedDateString() ?: '—' }}</span>
        </div>
    </div>
</div>

<div class="grid grid-4" style="margin-bottom:1rem">
    <div class="panel stat"><strong>{{ $expenseCount }}</strong><span>Expenses</span></div>
    <div class="panel stat"><strong>{{ $openTasks }}</strong><span>Open tasks</span></div>
    <div class="panel stat"><strong>{{ $lowStock }}</strong><span>Low-stock items</span></div>
    <div class="panel stat"><strong>{{ $project->members->count() }}</strong><span>Members</span></div>
</div>

<div class="grid grid-2">
    <div class="panel">
        <p class="section-label">Details</p>
        <table>
            <tr><th>Category</th><td>{{ $project->category }}</td></tr>
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
        <p class="section-label" style="margin-top:1.25rem">Daily expense logs</p>
        @forelse ($dailyLogs as $log)
            <div class="daily-log">
                <div>
                    <strong>{{ \Illuminate\Support\Carbon::parse($log->day)->toFormattedDateString() }}</strong>
                    <div class="muted">{{ $log->items }} {{ \Illuminate\Support\Str::plural('expense', $log->items) }}</div>
                </div>
                <div class="money">{{ Labels::money($log->total) }}</div>
            </div>
        @empty
            <p class="empty" style="padding:1rem 0">No daily logs yet.</p>
        @endforelse
    </div>
    <div class="panel">
        <p class="section-label">Recent expenses</p>
        @forelse ($recentExpenses as $expense)
            @php
                $thumb = Labels::receiptUrl(
                    $expense->receipt_image,
                    $expense->receipts->first()?->image_url ?? null
                );
            @endphp
            <div style="display:flex;justify-content:space-between;gap:1rem;padding:0.55rem 0;border-bottom:1px solid var(--line)">
                <div style="display:flex;gap:0.75rem;align-items:center;min-width:0">
                    @if ($thumb)
                        <a class="receipt-thumb" href="{{ $thumb }}" target="_blank" rel="noopener">
                            <img src="{{ $thumb }}" alt="">
                        </a>
                    @endif
                    <div>
                        <strong>{{ $expense->merchant }}</strong>
                        <div class="muted">{{ optional($expense->date)->toFormattedDateString() }} · {{ Labels::expenseCategory($expense->category) }}</div>
                    </div>
                </div>
                <div style="text-align:right">
                    <div class="money">{{ Labels::money($expense->amount) }}</div>
                    <span class="badge {{ Labels::statusTone('expense', $expense->status) }}">{{ Labels::expenseStatus($expense->status) }}</span>
                </div>
            </div>
        @empty
            <p class="empty">No expenses synced yet.</p>
        @endforelse
        <div style="margin-top:0.85rem">
            <a class="btn btn-sm btn-secondary" href="{{ route('projects.expenses', $project) }}">View all expenses</a>
        </div>
    </div>
</div>
@endsection
