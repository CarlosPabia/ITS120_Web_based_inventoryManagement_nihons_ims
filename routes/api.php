<?php

use App\Http\Controllers\InventoryController;
use App\Http\Controllers\SupplierController;
use App\Http\Controllers\OrderController; // <-- ADDED: For Order Management
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| API Routes
|--------------------------------------------------------------------------
| Routes protected by session authentication for local testing (auth)
*/

// Grouping all API routes that require a logged-in user
Route::middleware('auth')->group(function () {
    
    // --- INVENTORY MANAGEMENT API (CRUD) ---
    // READ (List all inventory items)
    Route::get('inventory', [InventoryController::class, 'index']);
    // CREATE/UPDATE (Store new item or adjust stock)
    Route::post('inventory', [InventoryController::class, 'store']);
    // DELETE (Remove item)
    Route::delete('inventory/{inventoryItem}', [InventoryController::class, 'destroy']);
    
    
    // --- ORDER MANAGEMENT API (Staff/Manager Access) ---
    // READ (View order history for the table)
    Route::get('orders', [OrderController::class, 'index']);
    // CREATE (Add new Customer or Supplier Order)
    Route::post('orders', [OrderController::class, 'store']);
    // Placeholder for updating order status (e.g., 'Pending' to 'Completed')
    Route::patch('orders/{order}', [OrderController::class, 'update']); 
    
    
    // --- SUPPLIER MANAGEMENT API (RBAC Manager-Only) ---
    // Note: This inner group is protected by the 'role:Manager' middleware
    Route::middleware('role:Manager')->group(function () {
        // READ (List all suppliers for forms/display)
        Route::get('suppliers', [SupplierController::class, 'index']);
        // CREATE (Add new supplier)
        Route::post('suppliers', [SupplierController::class, 'store']);
        // UPDATE (Edit contact info)
        Route::patch('suppliers/{supplier}', [SupplierController::class, 'update']);
        // DELETE (Remove supplier)
        Route::delete('suppliers/{supplier}', [SupplierController::class, 'destroy']);
    });
});

// If you have any public API routes, they would go here.