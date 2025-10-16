<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\ValidationException;

class AuthController extends Controller
{
    /**
     * Show the login form.
     * In a full application, this method would display the login.html page.
     * We'll focus on the 'store' method for now.
     */
    public function showLoginForm()
    {
        // For a simple prototype, we'll just return a view named 'auth.login'
        // You would place your login.html content inside resources/views/auth/login.blade.php
        return view('auth.login');
    }

    /**
     * Handle the incoming authentication request (form submission).
     */
    public function store(Request $request)
    {
        // 1. Validate the incoming request data
        $request->validate([
            'email' => ['required', 'email'],
            'password' => ['required'],
        ]);

        // 2. Attempt to log the user in
        // Note: Auth::attempt automatically uses the User model and the getAuthPassword() method
        // to check the provided password against the 'password_hash' column.
        $credentials = $request->only('email', 'password');

        if (Auth::attempt($credentials, $request->boolean('remember'))) {
            // Authentication was successful

            // 3. Record Activity Log (Required for accountability)
            // You will implement this model/logic later, but the concept is here.
            // ActivityLog::create([
            //     'user_id' => Auth::id(),
            //     'activity_type' => 'Login',
            //     'details' => 'User logged in successfully.',
            // ]);

            // 4. Regenerate session to prevent session fixation attacks
            $request->session()->regenerate();

            // 5. Redirect to the Dashboard upon successful login
            return redirect()->intended('/dashboard');

        }

        // 6. If authentication fails, throw a validation exception
        throw ValidationException::withMessages([
            'email' => [trans('auth.failed')],
        ]);
    }
    
    /**
     * Log the user out of the application.
     */
    public function logout(Request $request)
    {
        Auth::logout();

        $request->session()->invalidate();
        $request->session()->regenerateToken();

        // Redirect back to the login page
        return redirect('/login');
    }
}