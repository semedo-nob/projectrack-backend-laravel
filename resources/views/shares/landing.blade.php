@extends('layouts.app')

@section('title', 'Shared project · ProjectRack')

@section('content')
@php use App\Support\Labels; @endphp
<div class="hero-share">
    <p class="section-label">Project share invitation</p>
    <h1>{{ $share->project->name }}</h1>
    <p class="lede">
        {{ $share->creator?->name ?? 'A supervisor' }} invited you as
        <strong>{{ $share->role }}</strong> to monitor this project on the web — the same data synced from ProjectRack mobile.
    </p>
</div>

@if (! $valid)
    <div class="flash err">This share link is invalid, revoked, or expired.</div>
@else
    <div class="grid grid-3" style="margin-bottom:1rem">
        <div class="panel stat">
            <strong><span class="badge {{ Labels::statusTone('project', $share->project->status) }}">{{ $share->project->status }}</span></strong>
            <span>Status</span>
        </div>
        <div class="panel stat">
            <strong class="money">{{ Labels::money($share->project->budget) }}</strong>
            <span>Budget</span>
        </div>
        <div class="panel stat">
            <strong>{{ round(((float) ($share->project->progress ?? 0)) * 100) }}%</strong>
            <span>Progress</span>
        </div>
    </div>

    <div class="panel" style="margin-bottom:1rem">
        <p class="muted">{{ \Illuminate\Support\Str::limit($share->project->description ?: 'No description.', 180) }}</p>
        <div class="meta" style="margin-top:0.75rem">
            <span>{{ $share->project->category }}</span>
            <span class="money">Spent {{ Labels::money($share->project->spent) }}</span>
            @if ($share->expires_at)
                <span>Expires {{ $share->expires_at->toFormattedDateString() }}</span>
            @endif
        </div>
    </div>

    @auth
        <form method="POST" action="{{ route('share.accept', $share->token) }}">
            @csrf
            <button class="btn" type="submit">Accept access & open project</button>
        </form>
    @else
        <p class="lede" style="margin-bottom:0.75rem">Log in or register as an owner to accept this project.</p>
        <div style="display:flex;gap:0.75rem;flex-wrap:wrap">
            <a class="btn" href="{{ route('login', ['redirect' => '/p/'.$share->token]) }}">Log in</a>
            <a class="btn btn-secondary" href="{{ route('register', ['redirect' => '/p/'.$share->token]) }}">Register as owner</a>
        </div>
    @endauth
@endif
@endsection
