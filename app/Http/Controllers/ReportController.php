<?php

namespace App\Http\Controllers;

use App\Models\Order;
use App\Models\StockLevel;
use App\Models\OrderItem; 
use Illuminate\Http\Request;
use Carbon\Carbon;

class ReportController extends Controller
{
    // ... (index method remains the same) ...

    /**
     * Public method to aggregate and return the metrics required by the Dashboard.
     */
    public function getDashboardMetrics()
    {
        $dateNow = Carbon::now();
        $dateWeekAgo = $dateNow->copy()->subDays(7);
        $dateMonthAgo = $dateNow->copy()->subDays(30);

        $weeklySales = $this->getSalesMetrics($dateWeekAgo, $dateNow);
        $monthlySales = $this->getSalesMetrics($dateMonthAgo, $dateNow);
        
        $lowStockCount = StockLevel::whereColumn('quantity', '<', 'minimum_stock_threshold')->count();
        $pendingOrdersCount = Order::where('order_type', 'Supplier')->where('order_status', 'Pending')->count();

        return compact('weeklySales', 'monthlySales', 'lowStockCount', 'pendingOrdersCount');
    }

    /**
     * Helper function to aggregate sales data (Revenue, Total Orders) within a date range.
     * NOTE: This method must be PUBLIC for DashboardController to access it!
     */
    public function getSalesMetrics(Carbon $startDate, Carbon $endDate) // Changed to PUBLIC
    {
        // Fetch completed customer orders and their items within the date range
        $orders = Order::with('orderItems')
                        ->where('order_type', 'Customer')
                        ->whereBetween('order_date', [$startDate, $endDate])
                        ->where('order_status', 'Completed')
                        ->get();
        
        $totalOrders = $orders->count();
        $totalRevenue = 0;
        
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