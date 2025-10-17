<?php

namespace App\Http\Controllers;

use App\Models\InventoryItem;
use App\Models\StockLevel;
use App\Models\Supplier;
use App\Models\OrderItem;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Illuminate\Support\Facades\DB;

class InventoryController extends Controller
{
    /**
     * Display a listing of the resource (READ operation).
     */
    public function index()
    {
        $items = InventoryItem::with(['supplier', 'stockLevels'])
            ->get()
            ->map(function ($item) {
                $totalQuantity = $item->stockLevels->sum('quantity');
                $minStock = $item->stockLevels->max('minimum_stock_threshold');
                
                if ($totalQuantity <= 0) {
                    $status = 'Critical';
                } elseif ($totalQuantity < $minStock) {
                    $status = 'Low';
                } else {
                    $status = 'Normal';
                }

                return [
                    'id' => $item->id,
                    'name' => $item->item_name,
                    'unit' => $item->unit_of_measure,
                    'supplier_id' => $item->supplier_id,
                    'supplier_name' => $item->supplier->supplier_name ?? 'N/A',
                    'quantity' => $totalQuantity,
                    'status' => $status,
                    'batches' => $item->stockLevels, // Data needed for the Edit form (for expiry dates)
                ];
            });

        return response()->json($items);
    }

    /**
     * Store a newly created item OR update stock (CREATE/UPDATE operation).
     * This method fixes the "Call to undefined method" error.
     */
    public function store(Request $request)
    {
        // 1. Validation
        $request->validate([
            'id' => ['nullable', 'exists:inventory_items,id'],
            'item_name' => 'required|string|max:255',
            'unit_of_measure' => 'required|string|max:20',
            'supplier_id' => 'nullable|exists:suppliers,id',
            'quantity_adjustment' => 'nullable|numeric|sometimes', 
            'expiry_date' => 'nullable|date_format:Y-m-d|required_with:quantity_adjustment',
        ]);
        
        DB::beginTransaction();

        try {
            // 2. Find or Create the Inventory Item (Update Item Details)
            if ($request->filled('id')) {
                $item = InventoryItem::findOrFail($request->id);
                // Note: The form disables item_name and unit_of_measure during edit, 
                // but we update supplier_id if provided.
                $item->update($request->only(['item_name', 'unit_of_measure', 'supplier_id']));
            } else {
                // Creating a new item
                $item = InventoryItem::create($request->only(['item_name', 'unit_of_measure', 'supplier_id', 'item_description']));
            }
            
            // 3. Handle Stock Adjustment
            if ($request->filled('quantity_adjustment') && $request->quantity_adjustment > 0) {
                
                $stock = StockLevel::firstOrNew([
                    'item_id' => $item->id,
                    'expiry_date' => $request->expiry_date, 
                ]);
                
                $stock->quantity += $request->quantity_adjustment;
                
                if (!$stock->exists) {
                    $stock->minimum_stock_threshold = 10; 
                }
                
                $stock->save();
            }
            
            DB::commit();
            return response()->json(['message' => 'Inventory processed successfully.', 'item' => $item], 200);

        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json(['error' => 'Failed to process inventory update.'], 500);
        }
    }
    
    /**
     * Remove the specified resource from storage (DELETE operation).
     */
    public function destroy(InventoryItem $inventoryItem)
    {
        // Safety Check: Prevent deletion if item has existing stock > 0
        if ($inventoryItem->stockLevels()->where('quantity', '>', 0)->exists()) {
            return response()->json(['error' => 'Cannot delete item with remaining stock. Adjust stock to zero first.'], 409);
        }
        
        $inventoryItem->stockLevels()->delete();
        $inventoryItem->delete();
        
        return response()->json(['message' => 'Item deleted successfully.'], 200);
    }
}