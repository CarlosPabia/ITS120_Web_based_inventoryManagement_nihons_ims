<?php

namespace App\Http\Controllers;

use App\Models\Order;
use App\Models\OrderItem;
use App\Models\StockLevel;
use App\Models\Supplier;
use App\Models\InventoryItem;
use App\Models\ActivityLog;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;

class OrderController extends Controller
{
    private const CRITICAL_THRESHOLD = 10;
    /**
     * Display a listing of orders (READ operation for Order History).
     */
    public function index()
    {
        $orders = Order::with(['orderItems', 'user', 'supplier']) 
            ->orderByDesc('order_date')
            ->get();
        
        return response()->json($orders);
    }

    /**
     * Display the specified order with its details (READ operation).
     */
    public function show(Order $order)
    {
        $orderDetails = $order->load(['orderItems.inventoryItem', 'user', 'supplier']);
        return response()->json($orderDetails);
    }

    /**
     * Store a new Customer or Supplier order (CREATE operation).
     */
    public function store(Request $request)
    {
        $validated = $request->validate([
            'order_type' => 'required|in:Customer,Supplier',
            'supplier_id' => 'required|exists:suppliers,id',
            'order_date' => 'nullable|date',
            'expected_date' => 'nullable|date|after_or_equal:order_date',
            'items' => 'required|array|min:1',
            'items.*.item_id' => 'required|exists:inventory_items,id',
            'items.*.quantity' => 'required|numeric|min:0.01',
            'items.*.unit_price' => 'required|numeric|min:0',
            'items.*.expiry_date' => 'nullable|date_format:Y-m-d',
        ]);

        $supplier = Supplier::with('catalogItems:id')->find($validated['supplier_id']);
        if (!$supplier) {
            return response()->json([
                'error' => 'Selected supplier was not found.'
            ], 422);
        }

        $catalogItemIds = $supplier->catalogItems->pluck('id')->map(fn ($id) => (int) $id)->all();
        $itemIds = collect($validated['items'])->pluck('item_id')->map(fn ($id) => (int) $id)->all();
        $inventoryItems = InventoryItem::whereIn('id', $itemIds)->get()->keyBy('id');

        $orderType = $validated['order_type'];
        if ($orderType === 'Supplier') {
            if (!$supplier->is_active) {
                return response()->json([
                    'error' => 'Cannot create an order for an inactive supplier.'
                ], 422);
            }
            if (false) {
                return response()->json([
                    'error' => 'Internal supplier cannot be selected for Supplier (Adding) orders.'
                ], 422);
            }
            if (empty($validated['order_date']) || empty($validated['expected_date'])) {
                return response()->json([
                    'error' => 'Order date and expected date are required for supplier orders.'
                ], 422);
            }
        }

        foreach ($validated['items'] as $itemData) {
            $itemId = (int) $itemData['item_id'];
            $inventoryItem = $inventoryItems->get($itemId);
            $belongsToSupplier = $inventoryItem && (int) $inventoryItem->supplier_id === (int) $supplier->id;
            $isCatalogItem = in_array($itemId, $catalogItemIds, true);

            if ($orderType === 'Customer' && !$belongsToSupplier) {
                return response()->json([
                    'error' => 'Customer orders may only deduct items supplied by the selected supplier.'
                ], 422);
            }

            if ($orderType === 'Supplier' && !$belongsToSupplier && !$isCatalogItem) {
                return response()->json([
                    'error' => 'Selected items must belong to the chosen supplier.'
                ], 422);
            }
        }

        $orderDate = $validated['order_date']
            ? Carbon::parse($validated['order_date'])
            : Carbon::now();

        $expectedDate = $validated['expected_date']
            ? Carbon::parse($validated['expected_date'])
            : ($orderType === 'Supplier' ? null : $orderDate->copy());
        
        DB::beginTransaction();
        try {
            $order = Order::create([
                'order_type' => $orderType,
                'action_type' => $orderType === 'Supplier' ? 'Add' : 'Deduct',
                'supplier_id' => $validated['supplier_id'],
                'order_status' => 'Pending',
                'order_date' => $orderType === 'Supplier' ? $orderDate : Carbon::now(),
                'expected_date' => $orderType === 'Supplier' ? $expectedDate : ($expectedDate ?? Carbon::now()),
                'created_by_user_id' => Auth::id(),
            ]);

            foreach ($validated['items'] as $itemData) {
                OrderItem::create([
                    'order_id' => $order->id,
                    'item_id' => $itemData['item_id'],
                    'quantity_ordered' => $itemData['quantity'],
                    'unit_price' => $itemData['unit_price'],
                    'expected_stock_expiry' => $itemData['expiry_date'] ?? null,
                ]);
            }

            DB::commit();
            ActivityLog::create([
                'user_id' => Auth::id(),
                'activity_type' => 'Order Created',
                'details' => sprintf('Order #%d (%s) recorded with status %s.', $order->id, $order->order_type, $order->order_status),
            ]);

            return response()->json(['message' => 'Order created successfully and is now pending confirmation.', 'order' => $order], 201);
        } catch (\Throwable $e) {
            DB::rollBack();
            Log::error('Order creation failed', [
                'payload' => $request->all(),
                'exception' => $e->getMessage(),
            ]);

            return response()->json(['error' => 'Failed to process order.'], 500);
        }
    }

    /**
     * Update an existing order (status transitions, scheduling adjustments).
     */
    public function update(Request $request, Order $order)
    {
        $validated = $request->validate([
            'order_status' => 'required|in:Pending,Confirmed,Cancelled',
            'order_date' => 'nullable|date',
            'expected_date' => 'nullable|date|after_or_equal:order_date',
        ]);

        $previousStatus = $order->order_status;
        $nextStatus = $validated['order_status'];

        if ($previousStatus === 'Confirmed' && $nextStatus !== 'Confirmed') {
            return response()->json([
                'error' => 'Confirmed orders cannot revert to another status.'
            ], 422);
        }

        if ($previousStatus === 'Cancelled' && $nextStatus !== 'Cancelled') {
            return response()->json([
                'error' => 'Cancelled orders may not be re-opened.'
            ], 422);
        }

        if ($previousStatus !== 'Pending' && $nextStatus === 'Pending') {
            return response()->json([
                'error' => 'Only pending orders may be edited.'
            ], 422);
        }

        $statusChanged = $previousStatus !== $nextStatus;

        DB::beginTransaction();
        try {
            $updates = [];

            if ($order->order_type === 'Supplier') {
                if (array_key_exists('order_date', $validated) && $validated['order_date']) {
                    $updates['order_date'] = Carbon::parse($validated['order_date']);
                }
                if (array_key_exists('expected_date', $validated) && $validated['expected_date']) {
                    $updates['expected_date'] = Carbon::parse($validated['expected_date']);
                }
            }

            if ($statusChanged) {
                $updates['order_status'] = $nextStatus;
            }

            if (!empty($updates)) {
                $order->fill($updates);
            }

            if ($statusChanged && $nextStatus === 'Confirmed' && $order->status_processed_at === null) {
                $this->processConfirmedOrder($order);
                $order->status_processed_at = Carbon::now();
            }

            $order->save();

            DB::commit();

            $activityDetails = $statusChanged
                ? sprintf('Order #%d status changed from %s to %s.', $order->id, $previousStatus, $order->order_status)
                : sprintf('Order #%d details updated.', $order->id);

            ActivityLog::create([
                'user_id' => Auth::id(),
                'activity_type' => 'Order Updated',
                'details' => $activityDetails,
            ]);

            return response()->json([
                'message' => 'Order updated successfully.',
                'order' => $order->fresh(['orderItems', 'supplier', 'user']),
            ]);
        } catch (\Throwable $e) {
            DB::rollBack();
            Log::error('Order update failed', [
                'order_id' => $order->id,
                'payload' => $request->all(),
                'exception' => $e->getMessage(),
            ]);

            return response()->json(['error' => 'Failed to update the order.'], 500);
        }
    }

    /**
     * Handles the permanent deletion of an order for archival purposes.
     * This action does NOT affect stock levels.
     */
    public function destroy(Order $order)
    {
        DB::beginTransaction();
        try {
            if (!in_array($order->order_status, ['Confirmed', 'Cancelled'])) {
                return response()->json([
                    'error' => 'Only confirmed or cancelled orders may be deleted.'
                ], 409);
            }

            $order->orderItems()->delete();
            $order->delete();

            DB::commit();
            ActivityLog::create([
                'user_id' => Auth::id(),
                'activity_type' => 'Order Deleted',
                'details' => sprintf('Order #%d removed from order history.', $order->id),
            ]);

            return response()->json(['message' => 'Order #' . $order->id . ' has been permanently deleted.']);

        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('Order Deletion Failed', [
                'order_id' => $order->id,
                'exception' => $e->getMessage(),
            ]);

            return response()->json(['error' => 'A database error occurred while trying to delete the order.'], 500);
        }
    }
    
    // --- Helper Functions ---

    protected function processConfirmedOrder(Order $order): void
    {
        $order->loadMissing('orderItems');

        foreach ($order->orderItems as $item) {
            if ($order->order_type === 'Customer') {
                $this->deductStock($item->item_id, $item->quantity_ordered);
            } else {
                $expiry = $item->expected_stock_expiry ?? now()->addYear()->toDateString();
                $this->addStock($item->item_id, $item->quantity_ordered, $expiry);
            }
        }
    }

    protected function deductStock($itemId, $quantityToDeduct)
    {
        $remaining = $quantityToDeduct;
        $batches = StockLevel::where('item_id', $itemId)
            ->where('quantity', '>', 0)
            ->orderBy('expiry_date', 'asc')
            ->get();

        foreach ($batches as $batch) {
            if ($remaining <= 0) break;
            $deduct = min($remaining, $batch->quantity);
            $batch->quantity -= $deduct;
            $remaining -= $deduct;
            $batch->save();
        }

        if ($remaining > 0) {
             throw new \Exception("Stockout detected for item ID $itemId.");
        }
    }

    protected function addStock($itemId, $quantityToAdd, $expiryDate)
    {
        $batch = StockLevel::firstOrNew([
            'item_id' => $itemId,
            'expiry_date' => $expiryDate, 
        ]);
        $batch->quantity += $quantityToAdd;
        $threshold = $batch->minimum_stock_threshold;
        $batch->minimum_stock_threshold = $threshold !== null && $threshold > self::CRITICAL_THRESHOLD
            ? $threshold
            : self::CRITICAL_THRESHOLD + 1;
        $batch->save();
    }
}
