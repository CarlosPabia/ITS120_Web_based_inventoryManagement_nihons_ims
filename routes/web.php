<?php

use App\Http\Controllers\AuthController;
use App\Http\Controllers\Admin\UserController; // For User Management
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\DashboardController;
/*
|--------------------------------------------------------------------------
| Public Routes (Login)
|--------------------------------------------------------------------------
*/

// GET route to display the login form
Route::get('/login', [AuthController::class, 'showLoginForm'])->name('login');

// POST route to handle the actual login attempt
Route::post('/login', [AuthController::class, 'store']);


/*
|--------------------------------------------------------------------------
| Protected Routes (Requires Authentication - All users)
|--------------------------------------------------------------------------
*/

Route::middleware(['auth'])->group(function () {
    
    // --- Core Navigation Routes ---
    
    // Dashboard 
    Route::get('/dashboard', function () {
        return view('dashboard');
    })->name('dashboard');

    // POST route for logout
    Route::post('/logout', [AuthController::class, 'logout'])->name('logout');

    // Inventory Management (Employee/Manager access)
    Route::get('/inventory', function () {
        return view('inventory');
    })->name('inventory.index');

    // Orders Management (Employee/Manager access)
    Route::get('/orders', function () {
        return view('orders');
    })->name('orders.index');

    // --- Sensitive/Manager-Only Routes (Protected by RBAC) ---

    // Suppliers (Manager access)
    Route::get('/suppliers', function () {
        return view('suppliers'); 
    })->name('suppliers.index')
    ->middleware('role:Manager'); 

    // Reports (Manager access)
    Route::get('/reports', function () {
        return view('reports'); 
    })->name('reports.index')
    ->middleware('role:Manager');

    // SETTINGS (Manager Access)
    // FIX: This route now points directly to the Controller's index method, which fetches $users and $roles.
    Route::get('/settings', [UserController::class, 'index'])->name('settings.index')
        ->middleware('role:Manager'); 
    
    // Settings: User Management (Create/Store User Account - POST route)
    // We keep a separate POST route since the HTML form submits to a specific endpoint.
   // Settings: User Management (Create/Store User Account - POST route)
Route::post('/settings/users', [UserController::class, 'store'])->name('user.store')
    ->middleware('role:Manager');

// NEW: Settings: Update User Account (PATCH route for Manager function)
Route::patch('/settings/users/{user}', [UserController::class, 'update'])
    ->name('user.update') // <-- This is the missing name that resolves the error
    ->middleware('role:Manager');

// Settings: User Management (DELETE route)
Route::delete('/settings/users/{user}', [UserController::class, 'destroy'])
    ->name('user.destroy')
    ->middleware('role:Manager');
Route::get('/dashboard', [DashboardController::class, 'index'])->name('dashboard');
});


/*
|--------------------------------------------------------------------------
| Default Root Redirect
|--------------------------------------------------------------------------
*/

// Set the root URL (/) to automatically redirect to the login page.
Route::get('/', function () {
    return redirect()->route('login');
});