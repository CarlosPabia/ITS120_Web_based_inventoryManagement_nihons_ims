<?php

namespace App\Http\Controllers;

use App\Models\Order;
use App\Models\OrderItem;
use App\Models\StockLevel;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Auth;

class OrderController extends Controller
{
    /**
     * Display a listing of orders (READ operation for Order History).
     */
   // app/Http/Controllers/OrderController.php

public function index()
{
    // TEMPORARY FIX: Fetch only the necessary user relationship for now
    $orders = Order::with('user') // REMOVE: .inventoryItem and .orderItems 
        ->orderByDesc('order_date')
        ->get();
    
    // Check if the data loads now
    return response()->json($orders);
}

    /**
     * Store a new Customer or Supplier order (CREATE operation).
     */
    public function store(Request $request)
    {
        // 1. Validation
        $request->validate([
            'order_type' => 'required|in:Customer,Supplier',
            'supplier_id' => 'required_if:order_type,Supplier|nullable|exists:suppliers,id',
            'order_status' => 'required|string|max:50',
            'items' => 'required|array|min:1',
            'items.*.item_id' => 'required|exists:inventory_items,id',
            'items.*.quantity' => 'required|numeric|min:0.01',
            'items.*.unit_price' => 'required|numeric|min:0',
            'items.*.expiry_date' => 'nullable|date_format:Y-m-d',
        ]);
        
        DB::beginTransaction();

        try {
            // 2. Create Order Header
            $order = Order::create([
                'order_type' => $request->order_type,
                'supplier_id' => $request->supplier_id ?? null,
                'order_status' => $request->order_status,
                'order_date' => now(),
                'created_by_user_id' => Auth::id(),
            ]);

            // 3. Process Line Items and Update Inventory
            foreach ($request->items as $itemData) {
                // Insert line item record
                OrderItem::create([
                    'order_id' => $order->id,
                    'item_id' => $itemData['item_id'],
                    'quantity_ordered' => $itemData['quantity'],
                    'unit_price' => $itemData['unit_price'],
                ]);
                
                // 4. Update Stock Levels (assuming immediate processing/Completed)
                if ($request->order_status === 'Completed') {
                    if ($request->order_type === 'Customer') {
                        // --- CUSTOMER SALE: DEDUCT STOCK (FIFO) ---
                        $this->deductStock($itemData['item_id'], $itemData['quantity']);
                        
                    } elseif ($request->order_type === 'Supplier') {
                        // --- SUPPLIER PURCHASE: ADD STOCK ---
                        $this->addStock(
                            $itemData['item_id'], 
                            $itemData['quantity'], 
                            $itemData['expiry_date'] ?? now()->addYears(1)->format('Y-m-d')
                        );
                    }
                }
            }
            DB::commit();
            return response()->json(['message' => 'Order created and inventory processed.', 'order' => $order], 201);

        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json(['error' => 'Failed to process order. Stock may be insufficient or a system error occurred.'], 500);
        }
    }
    
    // --- Helper Functions for Stock Manipulation ---
    
    protected function deductStock($itemId, $quantityToDeduct)
    {
        $remaining = $quantityToDeduct;
        
        $batches = StockLevel::where('item_id', $itemId)
            ->where('quantity', '>', 0)
            ->orderBy('expiry_date', 'asc') // FIFO: Oldest expiry first
            ->get();

        foreach ($batches as $batch) {
            if ($remaining <= 0) break;

            if ($batch->quantity >= $remaining) {
                $batch->quantity -= $remaining;
                $remaining = 0;
            } else {
                $remaining -= $batch->quantity;
                $batch->quantity = 0;
            }
            $batch->save();
        }

        if ($remaining > 0) {
             throw new \Exception("Stockout detected: Not enough inventory for item ID $itemId.");
        }
    }

    protected function addStock($itemId, $quantityToAdd, $expiryDate)
    {
        $batch = StockLevel::firstOrNew([
            'item_id' => $itemId,
            'expiry_date' => $expiryDate, 
        ]);
        
        $batch->quantity += $quantityToAdd;
        $batch->minimum_stock_threshold = $batch->minimum_stock_threshold > 0 ? $batch->minimum_stock_threshold : 10;
        $batch->save();
    }
}