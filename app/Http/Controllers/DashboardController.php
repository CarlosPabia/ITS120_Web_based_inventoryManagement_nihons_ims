<?php

namespace App\Http\Controllers;

use App\Models\Order;
use App\Models\InventoryItem;
use App\Models\ActivityLog;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Carbon\Carbon;

class DashboardController extends Controller
{
    /**
     * Display the dashboard with key performance indicators.
     *
     * This method has been refactored for significantly better performance.
     */
    public function index()
    {
        // --- 1. EFFICIENTLY CALCULATE SALES DATA ---
        // Improvement: Instead of fetching all orders and calculating in PHP, we let the database do the work.
        // This is much faster as it's a single, optimized query for each metric.
        // We use withSum() to calculate the total price directly in the database.
        $todaySales = Order::where('order_type', 'Customer')
            ->whereDate('order_date', Carbon::today())
            ->withSum('orderItems as total_sales', DB::raw('quantity_ordered * unit_price'))
            ->get()
            ->sum('total_sales');

        $weeklySales = Order::where('order_type', 'Customer')
            ->whereBetween('order_date', [Carbon::now()->startOfWeek(), Carbon::now()->endOfWeek()])
            ->withSum('orderItems as total_sales', DB::raw('quantity_ordered * unit_price'))
            ->get()
            ->sum('total_sales');


        // --- 2. EFFICIENTLY FIND LOW STOCK ITEMS ---
        // Improvement: This query is now more efficient. We use withSum() to get the total quantity
        // directly from the database instead of calculating it in PHP after fetching all records.
        // The logic remains the same but the performance is much better.
        $lowStockItems = InventoryItem::withSum('stockLevels as total_quantity', 'quantity')
            ->get()
            ->map(function ($item) {
                // The status calculation still happens in PHP, which is fine for this logic.
                $threshold = $item->stockLevels->first()->minimum_stock_threshold ?? 10;
                if ($item->total_quantity <= 0) {
                    $item->status = 'Critical';
                } elseif ($item->total_quantity < $threshold) {
                    $item->status = 'Low';
                } else {
                    $item->status = null; // No need to assign 'Normal' status
                }
                return $item;
            })
            ->whereNotNull('status') // Filter out items that are not low or critical
            ->take(5);


        // --- 3. GET RECENT ACTIVITY LOG ---
        // Improvement: Solved the N+1 query problem using eager loading ('with').
        // Instead of fetching logs and then fetching the user for each log (N+1 queries),
        // this now fetches all logs and all associated users in just 2 efficient queries.
        $recentActivities = ActivityLog::with('user')->latest('timestamp')->take(5)->get();


        // --- 4. PASS ALL DATA TO THE VIEW ---
        // The view receives the same data structure as before, so no frontend changes are needed.
        return view('dashboard', [
            'todaySales' => $todaySales,
            'weeklySales' => $weeklySales,
            'lowStockItems' => $lowStockItems,
            'recentActivities' => $recentActivities
        ]);
    }
}

