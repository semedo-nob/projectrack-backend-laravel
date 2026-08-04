@extends('layouts.app')

@section('title', 'Expenses · '.$project->name)

@section('content')
<h1>{{ $project->name }}</h1>
<p class="lede">Expenses synced from the field.</p>
@include('projects._nav')

<div class="panel">
    <table>
        <thead>
            <tr>
                <th>Date</th><th>Merchant</th><th>Category</th><th>Amount</th><th>Status</th>
            </tr>
        </thead>
        <tbody>
            @forelse ($expenses as $expense)
                <tr>
                    <td>{{ optional($expense->date)->toFormattedDateString() }}</td>
                    <td>{{ $expense->merchant }}</td>
                    <td>{{ $expense->category }}</td>
                    <td>{{ number_format($expense->amount, 2) }}</td>
                    <td><span class="badge">{{ $expense->status }}</span></td>
                </tr>
            @empty
                <tr><td colspan="5">No expenses yet.</td></tr>
            @endforelse
        </tbody>
    </table>
    <div style="margin-top:1rem">{{ $expenses->links() }}</div>
</div>
@endsection
