@extends('layouts.app')

@section('title', 'Inventory · '.$project->name)

@section('content')
<h1>{{ $project->name }}</h1>
<p class="lede">On-hand materials and low-stock levels.</p>
@include('projects._nav')

<div class="panel">
    <table>
        <thead>
            <tr><th>Item</th><th>Qty (base)</th><th>Unit</th><th>Reorder</th></tr>
        </thead>
        <tbody>
            @forelse ($items as $item)
                <tr>
                    <td>{{ $item->name }}</td>
                    <td>{{ number_format($item->quantity_base, 2) }}</td>
                    <td>{{ $item->unit_base }}</td>
                    <td>
                        @if ($item->reorder_level > 0 && $item->quantity_base <= $item->reorder_level)
                            <span class="badge danger">Low ({{ number_format($item->reorder_level, 2) }})</span>
                        @else
                            {{ number_format($item->reorder_level, 2) }}
                        @endif
                    </td>
                </tr>
            @empty
                <tr><td colspan="4">No inventory items.</td></tr>
            @endforelse
        </tbody>
    </table>
</div>
@endsection
