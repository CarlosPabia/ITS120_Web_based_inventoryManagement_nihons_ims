<?php

namespace App\Http\Controllers;

use App\Models\Order;       // <--- CRITICAL FIX
use App\Models\OrderItem;   // <--- CRITICAL FIX
use App\Models\StockLevel;  // <--- CRITICAL FIX
use App\Models\Supplier;    // <--- CRITICAL FIX
use App\Models\User;        // <--- CRITICAL FIX
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
    // FIX: Simplify the eager loading path. Remove deep nesting:
    // We only load the direct relationships for the Order header.
    // The inner item details are available by traversing orderItems later.
    $orders = Order::with(['orderItems', 'user']) 
        ->orderByDesc('order_date')
        ->get();
    
    // Check if the data loads now
    return response()->json($orders);
}
// ... (rest of the file)

    /**
     * Store a new Customer or Supplier order (CREATE operation).
     */
    public function store(Request $request)
    {
        // 1. Validation
        $request->validate([
            'order_type' => 'required|in:Customer,Supplier',
            // supplier_id is required ONLY if order_type is Supplier
            'supplier_id' => 'required_if:order_type,Supplier|nullable|exists:suppliers,id',
            'order_status' => 'required|string|max:50',
            
            // Validate the array of line items
            'items' => 'required|array|min:1',
            'items.*.item_id' => 'required|exists:inventory_items,id',
            'items.*.quantity' => 'required|numeric|min:0.01',
            'items.*.unit_price' => 'required|numeric|min:0',
            'items.*.expiry_date' => 'nullable|date_format:Y-m-d',
        ]);
        
        // Use a database transaction to ensure atomicity (all or nothing)
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
                
                // 4. Update Stock Levels (CRITICAL STEP - assuming immediate processing/Completed)
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

            // TODO: Log activity: "Order created: [ID]"

            DB::commit();
            return response()->json(['message' => 'Order created and inventory processed.', 'order' => $order], 201);

        } catch (\Exception $e) {
            DB::rollBack();
            // This catches stockout errors from deductStock() as well
            return response()->json(['error' => 'Failed to process order. Stock may be insufficient or a system error occurred.'], 500);
        }
    }

    /**
     * Placeholder for updating order status (e.g., 'Pending' to 'Completed').
     */
    public function update(Request $request, Order $order)
    {
        // This is where you would validate a status change and apply stock changes if needed.
        // For now, return a placeholder response.
        return response()->json(['message' => 'Order status updated (placeholder).'], 200);
    }
    
    // --- Helper Functions for Stock Manipulation ---

    /**
     * Deducts stock using a basic FIFO (First-In, First-Out) principle.
     */
    protected function deductStock($itemId, $quantityToDeduct)
    {
        $remaining = $quantityToDeduct;
        
        // Find the oldest, non-expired stock first (Basic FIFO)
        $batches = StockLevel::where('item_id', $itemId)
            ->where('quantity', '>', 0)
            ->orderBy('expiry_date', 'asc') // Oldest expiry first
            ->get();

        foreach ($batches as $batch) {
            if ($remaining <= 0) break;

            if ($batch->quantity >= $remaining) {
                // The current batch covers the remaining quantity
                $batch->quantity -= $remaining;
                $remaining = 0;
            } else {
                // The batch is depleted entirely
                $remaining -= $batch->quantity;
                $batch->quantity = 0;
            }
            $batch->save();
        }

        // Throw an error if deduction was incomplete (Stockout prevention)
        if ($remaining > 0) {
             throw new \Exception("Stockout detected: Not enough inventory for item ID $itemId.");
        }
    }

    /**
     * Adds stock for a purchase order.
     */
    protected function addStock($itemId, $quantityToAdd, $expiryDate)
    {
        // Find or create the batch based on item and expiry date
        $batch = StockLevel::firstOrNew([
            'item_id' => $itemId,
            'expiry_date' => $expiryDate, 
        ]);
        
        $batch->quantity += $quantityToAdd;
        $batch->minimum_stock_threshold = $batch->minimum_stock_threshold > 0 ? $batch->minimum_stock_threshold : 10;
        $batch->save();
    }
}