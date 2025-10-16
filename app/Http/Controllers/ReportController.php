<?php

namespace App\Http\Controllers;

use App\Models\Order; // <-- ADD THIS LINE
use App\Models\StockLevel; // This should already be here
use App\Models\OrderItem;
use Illuminate\Http\Request;
use Carbon\Carbon; // Used for date calculations

class ReportController extends Controller
{
    /**
     * Generates and returns core metrics required by the Dashboard.
     */
    public function getDashboardMetrics()
    {
        $dateNow = Carbon::now();
        $dateWeekAgo = $dateNow->copy()->subDays(7);
        $dateMonthAgo = $dateNow->copy()->subDays(30);

        // --- 1. Calculate Sales Metrics ---
        $weeklySales = $this->getSalesMetrics($dateWeekAgo, $dateNow);
        $monthlySales = $this->getSalesMetrics($dateMonthAgo, $dateNow);

        // --- 2. Calculate Inventory & Order Status ---
        
        $lowStockCount = StockLevel::whereColumn('quantity', '<', 'minimum_stock_threshold')->count();
        $pendingOrdersCount = Order::where('order_type', 'Supplier')
                                    ->where('order_status', 'Pending')
                                    ->count();

        // Return as a single array for the dashboard view
        return compact('weeklySales', 'monthlySales', 'lowStockCount', 'pendingOrdersCount');
    }

    /**
     * Display the full reports view (Manager-Only access).
     */
    public function index(Request $request)
    {
        // Define reporting periods
        $dateNow = Carbon::now();
        $dateWeekAgo = $dateNow->copy()->subDays(7);
        $dateMonthAgo = $dateNow->copy()->subDays(30);

        // Fetch all metrics
        $weeklySales = $this->getSalesMetrics($dateWeekAgo, $dateNow);
        $monthlySales = $this->getSalesMetrics($dateMonthAgo, $dateNow);
        
        $lowStockCount = StockLevel::whereColumn('quantity', '<', 'minimum_stock_threshold')->count();
        $expiryCount = StockLevel::where('expiry_date', '<', $dateNow->copy()->addDays(30))->count();
        $pendingOrdersCount = Order::where('order_type', 'Supplier')->where('order_status', 'Pending')->count();

        // Pass all aggregated data to the Reports view (resources/views/reports.blade.php)
        return view('reports', compact('weeklySales', 'monthlySales', 'lowStockCount', 'expiryCount', 'pendingOrdersCount'));
    }

    /**
     * Helper function to aggregate sales data (Revenue, Total Orders) within a date range.
     * NOTE: This method must be PUBLIC for DashboardController to access it!
     */
    public function getSalesMetrics(Carbon $startDate, Carbon $endDate)
    {
        // Fetch completed customer orders and their items within the date range
        $orders = Order::with('orderItems')
                        ->where('order_type', 'Customer')
                        ->whereBetween('order_date', [$startDate, $endDate])
                        ->where('order_status', 'Completed')
                        ->get();
        
        $totalOrders = $orders->count();
        $totalRevenue = 0;
        
        // Calculate total revenue by summing item prices (price * quantity)
        foreach ($orders as $order) {
            $totalRevenue += $order->orderItems->sum(function ($item) {
                return $item->quantity_ordered * $item->unit_price; 
            });
        }

        return [
            'total_orders' => $totalOrders,
            'total_revenue' => number_format($totalRevenue, 2, '.', ','),
            'start_date' => $startDate->format('Y-m-d'),
            'end_date' => $endDate->format('Y-m-d'),
        ];
    }
}