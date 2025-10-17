<?php

namespace App\Http\Controllers;

use App\Models\InventoryItem;
use App\Models\StockLevel;
use App\Models\Supplier;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Illuminate\Support\Facades\DB;

class InventoryController extends Controller
{
    /**
     * Display a listing of the resource.
     * This has been refactored for better performance and data consistency.
     */
    public function index()
    {
        // Improvement: Use 'with' to eager load relationships, preventing the N+1 query problem.
        // Improvement: Use 'withSum' to calculate the total quantity at the database level, which is much faster.
        $items = InventoryItem::with(['supplier', 'stockLevels'])
            ->withSum('stockLevels as total_quantity', 'quantity')
            ->get()
            ->map(function ($item) {
                // Logic Improvement: The status calculation is clearer and remains in PHP, which is appropriate here.
                $threshold = $item->stockLevels->first()->minimum_stock_threshold ?? 10;
                if ($item->total_quantity <= 0) {
                    $status = 'Critical';
                } elseif ($item->total_quantity < $threshold) {
                    $status = 'Low';
                } else {
                    $status = 'Normal';
                }

                // Data Consistency Improvement: The keys in this returned array now match the database column names
                // (e.g., 'item_name', 'unit_of_measure'). This prevents "undefined" errors on the frontend
                // and makes the API more predictable.
                return [
                    'id' => $item->id,
                    'item_name' => $item->item_name,
                    'unit_of_measure' => $item->unit_of_measure,
                    'price' => $item->price,
                    'supplier_id' => $item->supplier_id,
                    'supplier_name' => $item->supplier->supplier_name ?? 'N/A',
                    'total_quantity' => $item->total_quantity,
                    'status' => $status,
                ];
            });

        return response()->json($items);
    }

    /**
     * Store a newly created resource in storage.
     * This logic is already quite solid. No major refactoring needed.
     */
    public function store(Request $request)
    {
        $validatedData = $request->validate([
            'item_name' => 'required|string|max:100|unique:inventory_items,item_name',
            'unit_of_measure' => 'required|string|max:20',
            'price' => 'required|numeric|min:0',
            'supplier_id' => 'required|exists:suppliers,id',
        ]);

        $item = InventoryItem::create($validatedData);

        return response()->json(['message' => 'Inventory item created successfully.', 'item' => $item], 201);
    }

    /**
     * Remove the specified resource from storage.
     * This logic is already solid and includes a check for existing stock.
     */
    public function destroy(InventoryItem $inventoryItem)
    {
        // Edge Case Handling: This check correctly prevents deletion if stock exists.
        if ($inventoryItem->stockLevels()->where('quantity', '>', 0)->exists()) {
            return response()->json(['error' => 'Cannot delete item with remaining stock. Adjust stock to zero first.'], 409);
        }
        
        // This correctly deletes child records before the parent.
        $inventoryItem->stockLevels()->delete();
        $inventoryItem->delete();
        
        return response()->json(['message' => 'Item deleted successfully.']);
    }
}
