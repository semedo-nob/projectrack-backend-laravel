@extends('layouts.app')

@section('title', 'Expenses · '.$project->name)

@section('content')
@php use App\Support\Labels; @endphp
<h1>{{ $project->name }}</h1>
<p class="lede">Expenses synced from the field app.</p>
@include('projects._nav')

<div class="grid grid-3" style="margin-bottom:1rem">
    <div class="panel stat"><strong class="money">{{ Labels::money($totalSpent) }}</strong><span>Expense total</span></div>
    <div class="panel stat"><strong>{{ $expenses->total() }}</strong><span>Records</span></div>
    <div class="panel stat"><strong>{{ $pending }}</strong><span>Pending / missing</span></div>
</div>

<div class="panel">
    <div class="table-scroll">
    <table>
        <thead>
            <tr>
                <th>Date</th><th>Merchant</th><th>Category</th><th>Qty</th><th>Amount</th><th>Receipt</th><th>Status</th>
            </tr>
        </thead>
        <tbody>
            @forelse ($expenses as $expense)
                @php
                    $thumb = Labels::receiptUrl(
                        $expense->receipt_image,
                        $expense->receipts->first()?->image_url
                    );
                    $localOnly = Labels::hasLocalOnlyReceipt($expense->receipt_image, $expense->receipts);
                @endphp
                <tr>
                    <td>{{ optional($expense->date)->toFormattedDateString() }}</td>
                    <td>
                        <strong>{{ $expense->merchant }}</strong>
                        @if ($expense->notes)
                            <div class="muted">{{ \Illuminate\Support\Str::limit($expense->notes, 60) }}</div>
                        @endif
                    </td>
                    <td>{{ Labels::expenseCategory($expense->category) }}</td>
                    <td>
                        @if ($expense->quantity_original)
                            {{ rtrim(rtrim(number_format($expense->quantity_original, 2), '0'), '.') }}
                            {{ $expense->unit_original }}
                        @else
                            —
                        @endif
                    </td>
                    <td class="money">{{ Labels::money($expense->amount) }}</td>
                    <td>
                        @if ($thumb)
                            <a class="receipt-thumb" href="{{ $thumb }}" target="_blank" rel="noopener">
                                <img src="{{ $thumb }}" alt="Receipt">
                            </a>
                        @elseif ($localOnly)
                            <span class="badge muted" title="Photo is on the supervisor device; re-share after cloud sync to upload.">On device</span>
                        @else
                            <span class="muted">—</span>
                        @endif
                    </td>
                    <td><span class="badge {{ Labels::statusTone('expense', $expense->status) }}">{{ Labels::expenseStatus($expense->status) }}</span></td>
                </tr>
            @empty
                <tr><td colspan="7" class="empty">No expenses yet.</td></tr>
            @endforelse
        </tbody>
    </table>
    </div>
    <div class="pagination">{{ $expenses->links() }}</div>
</div>
@endsection
