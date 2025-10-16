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
    // READ & CREATE/UPDATE are required by staff, so only 'auth' is needed.
    Route::get('inventory-data', [InventoryController::class, 'index'])->name('api.inventory.read');
    Route::post('inventory-data', [InventoryController::class, 'store'])->name('api.inventory.store');
    // DELETE is Manager-Only
    Route::delete('inventory-data/{inventoryItem}', [InventoryController::class, 'destroy'])->name('api.inventory.delete')->middleware('role:Manager');
    
    // Order API
    // READ & CREATE are required by staff, so only 'auth' is needed.
    Route::get('orders-data', [OrderController::class, 'index'])->name('api.orders.read');
    Route::post('orders-data', [OrderController::class, 'store'])->name('api.orders.store');
    // UPDATE is managerial for processing status changes
    Route::patch('orders-data/{order}', [OrderController::class, 'update'])->name('api.orders.update')->middleware('role:Manager'); 
    
   // --- SUPPLIER MANAGEMENT API (RBAC Manager-Only) ---
    
    // FIX: READ access must be open to all 'auth' users (Staff needs this for dropdowns).
    Route::get('suppliers-data', [SupplierController::class, 'index'])->name('api.suppliers.read'); 
    
    // CREATE/UPDATE/DELETE remain Manager-Only for security.
    Route::post('suppliers-data', [SupplierController::class, 'store'])->name('api.suppliers.store')->middleware('role:Manager');
    Route::patch('suppliers-data/{supplier}', [SupplierController::class, 'update'])->name('api.suppliers.update')->middleware('role:Manager');
    Route::delete('suppliers-data/{supplier}', [SupplierController::class, 'destroy'])->name('api.suppliers.delete')->middleware('role:Manager'); 


    // --- 5. Reports UI Links (RBAC enforced) ---
    
    // The UI link for the Suppliers page should still be Manager-Only
    Route::get('/suppliers', function () { return view('suppliers'); })->name('suppliers.index')->middleware('role:Manager'); 
    
    // Reports remains Manager-Only
    Route::get('/reports', [ReportController::class, 'index'])->name('reports.index')->middleware('role:Manager');
});


/*
|--------------------------------------------------------------------------
| Default Root Redirect
|--------------------------------------------------------------------------
*/

// Set the root URL (/) to automatically redirect to the login page.
Route::get('/', function () { return redirect()->route('login'); });