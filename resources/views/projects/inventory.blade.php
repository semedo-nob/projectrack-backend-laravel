@extends('layouts.app')

@section('title', 'Inventory · '.$project->name)

@section('content')
@php use App\Support\Labels; @endphp
<h1>{{ $project->name }}</h1>
<p class="lede">On-hand materials and recent movements.</p>
@include('projects._nav')

<div class="grid grid-3" style="margin-bottom:1rem">
    <div class="panel stat"><strong>{{ $items->count() }}</strong><span>Items</span></div>
    <div class="panel stat"><strong>{{ $lowStockCount }}</strong><span>Low / out of stock</span></div>
    <div class="panel stat"><strong>{{ $usages->count() }}</strong><span>Recent movements shown</span></div>
</div>

<div class="panel" style="margin-bottom:1rem">
    <p class="section-label">Stock</p>
    <table>
        <thead>
            <tr><th>Item</th><th>On hand</th><th>Unit</th><th>Reorder</th><th>Status</th></tr>
        </thead>
        <tbody>
            @forelse ($items as $item)
                @php
                    $low = $item->reorder_level > 0 && $item->quantity_base <= $item->reorder_level;
                    $out = $item->quantity_base <= 0;
                @endphp
                <tr>
                    <td><strong>{{ $item->name }}</strong></td>
                    <td class="money">{{ number_format($item->quantity_base, 2) }}</td>
                    <td>{{ $item->unit_base }}</td>
                    <td>{{ number_format($item->reorder_level, 2) }}</td>
                    <td>
                        @if ($out)
                            <span class="badge danger">Out</span>
                        @elseif ($low)
                            <span class="badge danger">Low</span>
                        @else
                            <span class="badge ok">OK</span>
                        @endif
                    </td>
                </tr>
            @empty
                <tr><td colspan="5" class="empty">No inventory items.</td></tr>
            @endforelse
        </tbody>
    </table>
</div>

<div class="panel">
    <p class="section-label">Recent movements</p>
    <table>
        <thead>
            <tr><th>When</th><th>Item</th><th>Kind</th><th>Qty</th><th>Notes</th></tr>
        </thead>
        <tbody>
            @forelse ($usages as $u)
                <tr>
                    <td>{{ optional($u->occurred_at)->toDayDateTimeString() }}</td>
                    <td>{{ $u->inventoryItem?->name ?: '—' }}</td>
                    <td><span class="badge info">{{ ucfirst($u->kind) }}</span></td>
                    <td>{{ number_format($u->quantity_base, 2) }} {{ $u->unit_base }}</td>
                    <td>{{ $u->notes ?: '—' }}</td>
                </tr>
            @empty
                <tr><td colspan="5" class="empty">No movements logged.</td></tr>
            @endforelse
        </tbody>
    </table>
</div>
@endsection
