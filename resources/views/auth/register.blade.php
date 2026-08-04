@extends('layouts.app')

@section('title', 'Register · ProjectRack')

@section('content')
<div class="auth-wrap panel">
    <h1>Create account</h1>
    <p class="lede">Owners use the web to follow field progress. Supervisors sync from the mobile app.</p>
    @if ($errors->any())
        <ul class="errors">
            @foreach ($errors->all() as $error)
                <li>{{ $error }}</li>
            @endforeach
        </ul>
    @endif
    <form method="POST" action="{{ route('register') }}">
        @csrf
        <label for="name">Name</label>
        <input id="name" name="name" value="{{ old('name') }}" required>
        <label for="email">Email</label>
        <input id="email" type="email" name="email" value="{{ old('email') }}" required>
        <label for="role">I am a</label>
        <select id="role" name="role">
            <option value="owner" @selected(old('role', 'owner') === 'owner')>Project owner</option>
            <option value="supervisor" @selected(old('role') === 'supervisor')>Field supervisor</option>
        </select>
        <label for="password">Password</label>
        <input id="password" type="password" name="password" required>
        <label for="password_confirmation">Confirm password</label>
        <input id="password_confirmation" type="password" name="password_confirmation" required>
        <div style="margin-top:1.25rem">
            <button class="btn" type="submit">Create account</button>
        </div>
    </form>
    <p class="muted" style="margin-top:1rem">Already registered? <a href="{{ route('login') }}">Log in</a></p>
</div>
@endsection
