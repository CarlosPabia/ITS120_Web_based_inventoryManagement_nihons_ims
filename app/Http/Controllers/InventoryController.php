<?php

namespace App\Http\Controllers;

use App\Models\InventoryItem;
use App\Models\ActivityLog;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;

class InventoryController extends Controller
{
    /**
     * Display a listing of the resource (READ operation).
     */
    public function index()
    {
        $items = InventoryItem::with(['supplier', 'stockLevels'])
            ->withCount('orderItems')
            ->get()
            ->map(function ($item) {
                $totalQuantity = $item->stockLevels->sum('quantity');
                $minStock = $item->stockLevels->max('minimum_stock_threshold');
                $hasOrderHistory = $item->order_items_count > 0;
                
                if ($totalQuantity <= 0) {
                    $status = 'Critical';
                } elseif ($totalQuantity < $minStock) {
                    $status = 'Low';
                } else {
                    $status = 'Normal';
                }

                $canDelete = $totalQuantity <= 0 && !$hasOrderHistory;
                $deleteReason = null;

                if ($totalQuantity > 0) {
                    $deleteReason = 'Item still has stock remaining.';
                } elseif ($hasOrderHistory) {
                    $deleteReason = 'Item is referenced in existing order history.';
                }

                return [
                    'id' => $item->id,
                    'name' => $item->item_name,
                    'unit' => $item->unit_of_measure,
                    'default_price' => (float) $item->default_unit_price,
                    'supplier_id' => $item->supplier_id,
                    'supplier_name' => $item->supplier->supplier_name ?? 'N/A',
                    'quantity' => $totalQuantity,
                    'status' => $status,
                    'batches' => $item->stockLevels, // Data needed for the Edit form (for expiry dates)
                    'can_delete' => $canDelete,
                    'delete_block_reason' => $deleteReason,
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
        return response()->json([
            'error' => 'Manual inventory creation or editing is disabled. Use orders to adjust stock levels.'
        ], 403);
    }
    
    /**
     * Remove the specified resource from storage (DELETE operation).
     */
    public function destroy(InventoryItem $inventoryItem)
    {
        try {
            $hasStock = $inventoryItem->stockLevels()->where('quantity', '>', 0)->exists();

            if ($hasStock) {
                return response()->json([
                    'error' => 'Cannot delete item with remaining stock. Adjust stock to zero first.'
                ], 409);
            }

            if ($inventoryItem->orderItems()->exists()) {
                return response()->json([
                    'error' => 'Cannot delete item while it still appears in order history.'
                ], 409);
            }

            DB::transaction(function () use ($inventoryItem) {
                $inventoryItem->stockLevels()->delete();
                $inventoryItem->delete();
            });

            ActivityLog::create([
                'user_id' => Auth::id(),
                'activity_type' => 'Inventory Item Deleted',
                'details' => 'Inventory item #' . $inventoryItem->id . ' - ' . $inventoryItem->item_name . ' removed from catalogue.',
            ]);

            return response()->json(['message' => 'Item deleted successfully.'], 200);
        } catch (\Throwable $exception) {
            Log::error('Inventory deletion failed', [
                'item_id' => $inventoryItem->id,
                'exception' => $exception->getMessage(),
            ]);

            return response()->json([
                'error' => 'An unexpected error occurred while deleting the item.'
            ], 500);
        }
    }

    /**
     * Update the earliest stock batch expiry for an inventory item.
     * Creates a zero-quantity batch if none exist.
     */
    public function updateExpiry(Request $request, InventoryItem $inventoryItem)
    {
        $validated = $request->validate([
            'expiry_date' => 'required|date',
        ]);

        $batch = $inventoryItem->stockLevels()
            ->orderBy('expiry_date', 'asc')
            ->first();

        if (!$batch) {
            $batch = new StockLevel([
                'item_id' => $inventoryItem->id,
                'quantity' => 0,
                'minimum_stock_threshold' => 10,
            ]);
        }

        $batch->expiry_date = $validated['expiry_date'];
        $batch->save();

        return response()->json([
            'message' => 'Expiry date updated successfully.',
            'item_id' => $inventoryItem->id,
            'expiry_date' => $batch->expiry_date,
        ]);
    }

    /**
     * Update item details (unit_of_measure) and batch thresholds.
     */
    public function updateDetails(Request $request, InventoryItem $inventoryItem)
    {
        $validated = $request->validate([
            'unit_of_measure' => 'nullable|string|max:50',
            'minimum_stock_threshold' => 'nullable|numeric|min:0',
        ]);

        if (array_key_exists('unit_of_measure', $validated)) {
            $inventoryItem->unit_of_measure = $validated['unit_of_measure'];
            $inventoryItem->save();
        }

        if (array_key_exists('minimum_stock_threshold', $validated)) {
            $threshold = (int) $validated['minimum_stock_threshold'];
            $inventoryItem->stockLevels()->update(['minimum_stock_threshold' => $threshold]);
        }

        return response()->json([
            'message' => 'Item details updated successfully.',
            'item' => [
                'id' => $inventoryItem->id,
                'unit' => $inventoryItem->unit_of_measure,
            ],
        ]);
    }
}
