@extends('layouts.app')

@section('title', 'Shared project · ProjectRack')

@section('content')
<div class="hero-share">
    <p class="muted">Project share invitation</p>
    <h1>{{ $share->project->name }}</h1>
    <p class="lede">
        {{ $share->creator?->name ?? 'A supervisor' }} invited you as
        <strong>{{ $share->role }}</strong> to view this project on the web.
    </p>
</div>

@if (! $valid)
    <div class="flash err">This share link is invalid, revoked, or expired.</div>
@else
    <div class="panel grid grid-3" style="margin-bottom:1rem">
        <div class="stat"><strong>{{ $share->project->status }}</strong><span>Status</span></div>
        <div class="stat"><strong>{{ number_format($share->project->budget, 0) }}</strong><span>Budget</span></div>
        <div class="stat"><strong>{{ round(($share->project->progress ?? 0) * 100) }}%</strong><span>Progress</span></div>
    </div>

    @auth
        <form method="POST" action="{{ route('share.accept', $share->token) }}">
            @csrf
            <button class="btn" type="submit">Accept access</button>
        </form>
    @else
        <p>Log in or register to accept this project.</p>
        <div style="display:flex;gap:0.75rem;flex-wrap:wrap;margin-top:1rem">
            <a class="btn" href="{{ route('login') }}?redirect={{ urlencode(url('/p/'.$share->token)) }}">Log in</a>
            <a class="btn btn-secondary" href="{{ route('register') }}">Register as owner</a>
        </div>
    @endauth
@endif
@endsection
