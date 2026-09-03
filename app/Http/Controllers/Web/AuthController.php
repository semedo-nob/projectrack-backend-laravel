<?php

namespace App\Http\Controllers\Web;

use App\Http\Controllers\Controller;
use App\Models\User;
use App\Support\ShareInvite;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Str;
use Illuminate\View\View;

class AuthController extends Controller
{
    public function showLogin(): View
    {
        return view('auth.login');
    }

    public function showRegister(): View
    {
        return view('auth.register');
    }

    public function login(Request $request): RedirectResponse
    {
        $credentials = $request->validate([
            'email' => ['required', 'email'],
            'password' => ['required'],
        ]);

        if (Auth::attempt([
            'email' => Str::lower($credentials['email']),
            'password' => $credentials['password'],
        ], $request->boolean('remember'))) {
            $request->session()->regenerate();

            $invite = ShareInvite::pathFromRedirect(
                $request->input('redirect') ?: $request->query('redirect')
            );
            if ($invite !== null) {
                return redirect()->to($invite);
            }

            return redirect()->intended(route('dashboard'));
        }

        return back()->withErrors(['email' => 'Invalid credentials.'])->onlyInput('email');
    }

    public function register(Request $request): RedirectResponse
    {
        $data = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'email', 'unique:users,email'],
            'password' => ['required', 'string', 'min:6', 'confirmed'],
            'role' => ['nullable', 'in:supervisor,owner'],
        ]);

        $user = User::create([
            'id' => 'user_'.Str::lower(Str::ulid()),
            'name' => $data['name'],
            'email' => Str::lower($data['email']),
            'password' => $data['password'],
            'role' => $data['role'] ?? 'owner',
        ]);

        Auth::login($user);
        $request->session()->regenerate();

        $invite = ShareInvite::pathFromRedirect(
            $request->input('redirect') ?: $request->query('redirect')
        );
        if ($invite !== null) {
            return redirect()->to($invite);
        }

        return redirect()->route('dashboard');
    }

    public function logout(Request $request): RedirectResponse
    {
        Auth::logout();
        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return redirect()->route('login');
    }
}
