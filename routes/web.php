<?php

use App\Http\Controllers\AuthController;
use App\Http\Controllers\Admin\UserController;
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\InventoryController;
use App\Http\Controllers\SupplierController; 
use App\Http\Controllers\OrderController; 
use App\Http\Controllers\ReportController;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| Public Routes (Login)
|--------------------------------------------------------------------------
*/
Route::get('/login', [AuthController::class, 'showLoginForm'])->name('login');
Route::post('/login', [AuthController::class, 'store']);


/*
|--------------------------------------------------------------------------
| Protected Routes (Requires Authentication - All core functions)
|--------------------------------------------------------------------------
*/

Route::middleware(['auth'])->group(function () {
    
    // --- 1. UI Navigation Routes (Loads the HTML pages) ---
    Route::get('/dashboard', [DashboardController::class, 'index'])->name('dashboard');
    Route::post('/logout', [AuthController::class, 'logout'])->name('logout');
    Route::get('/inventory', function () { return view('inventory'); })->name('inventory.index');
    Route::get('/orders', function () { return view('orders'); })->name('orders.index');
    
    // --- 2. User Management CRUD Routes (Manager-Only) ---
    Route::get('/settings', [UserController::class, 'index'])->name('settings.index')->middleware('role:Manager'); 
    Route::post('/settings/users', [UserController::class, 'store'])->name('user.store')->middleware('role:Manager');
    Route::patch('/settings/users/{user}', [UserController::class, 'update'])->name('user.update')->middleware('role:Manager');
    Route::delete('/settings/users/{user}', [UserController::class, 'destroy'])->name('user.destroy')->middleware('role:Manager');

    // --- 3. Data API Endpoints (CLEAN, Non-Conflicting Data Routes) ---
    
    // Inventory API
    Route::get('inventory-data', [InventoryController::class, 'index'])->name('api.inventory.read');
    Route::post('inventory-data', [InventoryController::class, 'store'])->name('api.inventory.store');
    Route::delete('inventory-data/{inventoryItem}', [InventoryController::class, 'destroy'])->name('api.inventory.delete')->middleware('role:Manager');
    
    // Order API
    Route::get('orders-data', [OrderController::class, 'index'])->name('api.orders.read');
    Route::post('orders-data', [OrderController::class, 'store'])->name('api.orders.store');
    Route::patch('orders-data/{order}', [OrderController::class, 'update'])->name('api.orders.update')->middleware('role:Manager');
    Route::get('orders-data/{order}', [OrderController::class, 'show'])->name('api.orders.show'); // <-- THIS IS THE NEW ROUTE
    
   // --- SUPPLIER MANAGEMENT API (RBAC Manager-Only) ---
    Route::get('suppliers-data', [SupplierController::class, 'index'])->name('api.suppliers.read'); 
    Route::post('suppliers-data', [SupplierController::class, 'store'])->name('api.suppliers.store')->middleware('role:Manager');
    // --- ADD THIS LINE TO FIX THE UPDATE FUNCTIONALITY ---
    Route::patch('suppliers-data/{supplier}', [SupplierController::class, 'update'])->name('api.suppliers.update')->middleware('role:Manager');
    Route::delete('suppliers-data/{supplier}', [SupplierController::class, 'destroy'])->name('api.suppliers.delete')->middleware('role:Manager'); 


    // --- 5. Reports UI Links (RBAC enforced) ---
    Route::get('/suppliers', function () { return view('suppliers'); })->name('suppliers.index')->middleware('role:Manager'); 
    Route::get('/reports', [ReportController::class, 'index'])->name('reports.index')->middleware('role:Manager');
});


/*
|--------------------------------------------------------------------------
| Default Root Redirect
|--------------------------------------------------------------------------
*/

// Set the root URL (/) to automatically redirect to the login page.
Route::get('/', function () { return redirect()->route('login'); });
