@extends('layouts.app')

@section('title', 'Log in · ProjectRack')

@section('content')
<div class="auth-wrap panel">
    <h1>Welcome back</h1>
    <p class="lede">Monitor shared projects from the web portal.</p>
    @if ($errors->any())
        <div class="errors">{{ $errors->first() }}</div>
    @endif
    <form method="POST" action="{{ route('login') }}">
        @csrf
        <label for="email">Email</label>
        <input id="email" type="email" name="email" value="{{ old('email') }}" required autofocus>
        <label for="password">Password</label>
        <input id="password" type="password" name="password" required>
        <label style="font-weight:500"><input type="checkbox" name="remember" value="1"> Remember me</label>
        <div style="margin-top:1.25rem">
            <button class="btn" type="submit">Log in</button>
        </div>
    </form>
    <p class="muted" style="margin-top:1rem">No account? <a href="{{ route('register') }}">Register</a></p>
</div>
@endsection
