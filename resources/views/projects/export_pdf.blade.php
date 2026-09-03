<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <title>{{ $project->name }} · Project report</title>
    <style>
        body { font-family: Helvetica, Arial, sans-serif; font-size: 11px; color: #0f172a; }
        h1 { font-size: 20px; margin: 0 0 4px; }
        h2 { font-size: 13px; margin: 18px 0 8px; color: #4338b5; }
        p { margin: 0 0 8px; }
        .muted { color: #64748b; }
        .meta { margin-bottom: 12px; }
        table { width: 100%; border-collapse: collapse; margin-bottom: 4px; }
        th, td { border: 1px solid #e2e8f0; padding: 5px 6px; text-align: left; vertical-align: top; }
        th { background: #5e4ee3; color: #fff; font-size: 9px; text-transform: uppercase; letter-spacing: 0.04em; }
        .cards td { border: 1px solid #ddd6fe; background: #f8fafc; width: 25%; }
        .label { display: block; font-size: 8px; color: #64748b; text-transform: uppercase; letter-spacing: 0.04em; }
        .value { font-weight: 700; }
        .empty { color: #64748b; font-style: italic; }
        .footer { margin-top: 18px; font-size: 8px; color: #94a3b8; }
    </style>
</head>
<body>
@php
    use App\Support\Labels;
    $used = Labels::budgetUsedPercent((float) $project->budget, (float) $project->spent);
    $remaining = (float) $project->budget - (float) $project->spent;
    $progressPct = round(((float) ($project->progress ?? 0)) * 100);
    $catSum = max(0.0001, (float) $byCategory->sum('total'));
@endphp

<h1>{{ $project->name }}</h1>
<p class="muted">Full project report · exported {{ $exportedAt->timezone(config('app.timezone'))->format('d M Y H:i') }}</p>
@if ($project->description)
    <p>{{ $project->description }}</p>
@endif

<table class="cards">
    <tr>
        <td><span class="label">Status</span><span class="value">{{ Labels::projectStatus($project->status) }}</span></td>
        <td><span class="label">Category</span><span class="value">{{ $project->category ?: '—' }}</span></td>
        <td><span class="label">Progress</span><span class="value">{{ $progressPct }}%</span></td>
        <td><span class="label">Budget used</span><span class="value">{{ round($used) }}%</span></td>
    </tr>
    <tr>
        <td><span class="label">Budget</span><span class="value">{{ Labels::money($project->budget) }}</span></td>
        <td><span class="label">Spent</span><span class="value">{{ Labels::money($project->spent) }}</span></td>
        <td><span class="label">Remaining</span><span class="value">{{ Labels::money($remaining) }}</span></td>
        <td>
            <span class="label">Dates</span>
            <span class="value">{{ optional($project->start_date)->toFormattedDateString() ?: '—' }}
                – {{ optional($project->end_date)->toFormattedDateString() ?: '—' }}</span>
        </td>
    </tr>
</table>
@if ($project->tags->isNotEmpty())
    <p class="meta"><strong>Tags:</strong> {{ $project->tags->pluck('tag')->implode(', ') }}</p>
@endif

<h2>Spend by category</h2>
@if ($byCategory->isEmpty())
    <p class="empty">No expense data.</p>
@else
    <table>
        <thead><tr><th>Category</th><th>Total</th><th>Share</th></tr></thead>
        <tbody>
            @foreach ($byCategory as $row)
                <tr>
                    <td>{{ Labels::expenseCategory($row->category) }}</td>
                    <td>{{ Labels::money($row->total) }}</td>
                    <td>{{ round(($row->total / $catSum) * 100) }}%</td>
                </tr>
            @endforeach
        </tbody>
    </table>
@endif

<h2>Expenses ({{ $expenses->count() }})</h2>
@if ($expenses->isEmpty())
    <p class="empty">None recorded.</p>
@else
    <table>
        <thead><tr><th>Date</th><th>Merchant</th><th>Category</th><th>Amount</th><th>Status</th><th>Receipt</th></tr></thead>
        <tbody>
            @foreach ($expenses as $expense)
                <tr>
                    <td>{{ optional($expense->date)->toFormattedDateString() }}</td>
                    <td>{{ $expense->merchant }}</td>
                    <td>{{ Labels::expenseCategory($expense->category) }}</td>
                    <td>{{ Labels::money($expense->amount) }}</td>
                    <td>{{ Labels::expenseStatus($expense->status) }}</td>
                    <td>{{ filled($expense->receipt_image) ? 'Yes' : 'No' }}</td>
                </tr>
            @endforeach
        </tbody>
    </table>
@endif

<h2>Tasks ({{ $tasks->count() }})</h2>
@if ($tasks->isEmpty())
    <p class="empty">None recorded.</p>
@else
    <table>
        <thead><tr><th>Title</th><th>Status</th><th>Priority</th><th>Due</th><th>Assignee</th></tr></thead>
        <tbody>
            @foreach ($tasks as $task)
                <tr>
                    <td>{{ $task->title }}</td>
                    <td>{{ Labels::taskStatus($task->status) }}</td>
                    <td>{{ Labels::taskPriority($task->priority) }}</td>
                    <td>{{ optional($task->due_date)->toFormattedDateString() ?: '—' }}</td>
                    <td>{{ $task->assigned_to ?: '—' }}</td>
                </tr>
            @endforeach
        </tbody>
    </table>
@endif

<h2>Inventory ({{ $inventory->count() }})</h2>
@if ($inventory->isEmpty())
    <p class="empty">None recorded.</p>
@else
    <table>
        <thead><tr><th>Item</th><th>On hand</th><th>Unit</th><th>Reorder</th><th>Status</th></tr></thead>
        <tbody>
            @foreach ($inventory as $item)
                @php
                    $out = $item->quantity_base <= 0;
                    $low = $item->reorder_level > 0 && $item->quantity_base <= $item->reorder_level;
                @endphp
                <tr>
                    <td>{{ $item->name }}</td>
                    <td>{{ rtrim(rtrim(number_format($item->quantity_base, 2), '0'), '.') }}</td>
                    <td>{{ $item->unit_base }}</td>
                    <td>{{ $item->reorder_level > 0 ? rtrim(rtrim(number_format($item->reorder_level, 2), '0'), '.') : '—' }}</td>
                    <td>{{ $out ? 'Out of stock' : ($low ? 'Low stock' : 'In stock') }}</td>
                </tr>
            @endforeach
        </tbody>
    </table>
@endif

<h2>Activities ({{ $activities->count() }})</h2>
@if ($activities->isEmpty())
    <p class="empty">None recorded.</p>
@else
    <table>
        <thead><tr><th>When</th><th>Title</th><th>Type</th><th>Hours</th><th>By</th></tr></thead>
        <tbody>
            @foreach ($activities as $activity)
                <tr>
                    <td>{{ optional($activity->occurred_at)->format('d M Y H:i') }}</td>
                    <td>{{ $activity->title }}</td>
                    <td>{{ Labels::activityType($activity->activity_type) }}</td>
                    <td>{{ $activity->hours_spent !== null ? number_format($activity->hours_spent, 1) : '—' }}</td>
                    <td>{{ $activity->performed_by ?: '—' }}</td>
                </tr>
            @endforeach
        </tbody>
    </table>
@endif

<h2>Schedule ({{ $milestones->count() }})</h2>
@if ($milestones->isEmpty())
    <p class="empty">None recorded.</p>
@else
    <table>
        <thead><tr><th>Title</th><th>Due</th><th>Status</th></tr></thead>
        <tbody>
            @foreach ($milestones as $milestone)
                <tr>
                    <td>{{ $milestone->title }}</td>
                    <td>{{ optional($milestone->due_date)->toFormattedDateString() ?: '—' }}</td>
                    <td>{{ Labels::milestoneStatus($milestone->status) }}</td>
                </tr>
            @endforeach
        </tbody>
    </table>
@endif

<p class="footer">Generated by ProjectRack</p>
</body>
</html>
