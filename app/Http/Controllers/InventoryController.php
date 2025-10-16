<?php

namespace App\Http\Controllers;

use App\Models\InventoryItem;
use App\Models\StockLevel;
use App\Models\Supplier;
use Illuminate\Http\Request;

class InventoryController extends Controller
{
    /**
     * Display a listing of the resource (READ operation).
     * Returns JSON data for the inventory table.
     */
    public function index()
    {
        // 1. Fetch all Inventory Items and eager-load related data (Supplier, Stock)
        $items = InventoryItem::with(['supplier', 'stockLevels'])
            ->get()
            // 2. Map the data to calculate real-time status and total quantity
            ->map(function ($item) {
                // Sum the quantity from all associated stock batches
                $totalQuantity = $item->stockLevels->sum('quantity');
                // Find the highest reorder point defined in stock levels
                $minStock = $item->stockLevels->max('minimum_stock_threshold');
                
                // Determine status for the frontend alerts
                if ($totalQuantity <= 0) {
                    $status = 'Critical';
                } elseif ($totalQuantity < $minStock) {
                    $status = 'Low';
                } else {
                    $status = 'Normal';
                }

                // Return the data structure that the frontend expects
                return [
                    'id' => $item->id,
                    'name' => $item->item_name,
                    'unit' => $item->unit_of_measure,
                    'supplier_id' => $item->supplier_id,
                    'supplier_name' => $item->supplier->supplier_name ?? 'N/A',
                    'quantity' => $totalQuantity,
                    'status' => $status,
                ];
            });

        // 3. Return the data as JSON
        return response()->json($items);
    }
    
    // ... (store and destroy methods will be added later)
}