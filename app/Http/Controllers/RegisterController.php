<?php

namespace App\Http\Controllers;

use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\View\View;

class RegisterController extends Controller
{
    public function create(): View
    {
        return view('register');
    }

    public function store(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'name' => [
                'required',
                'string',
                'max:255',
                'regex:/^[A-Za-z]+(?: [A-Za-z]+)*$/',
            ],
            'email' => ['required', 'email', 'unique:users,email'],
            'password' => ['required', 'confirmed'],
        ], [
            'name.regex' => 'Use letters only, with spaces between words. Numbers and symbols are not allowed.',
            'name.required' => 'Please enter your full name.',
        ]);

        $user = User::create([
            'name' => $validated['name'],
            'email' => $validated['email'],
            'password' => Hash::make($validated['password']),
            'onboarding_completed' => false,
        ]);

        Auth::login($user);
        $request->session()->regenerate();

        return redirect()->route('onboarding.show');
    }
}
