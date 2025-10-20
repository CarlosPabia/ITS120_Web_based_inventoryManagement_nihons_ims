<?php

namespace App\Http\Controllers;

use App\Models\Order;
use App\Models\StockLevel;
use App\Models\OrderItem; 
use App\Models\Supplier;
use App\Models\InventoryItem;
use Illuminate\Support\Facades\DB;
use Illuminate\Http\Request;
use Carbon\Carbon;

class ReportController extends Controller
{
    private const CRITICAL_THRESHOLD = 10;
    /**
     * Show the Reports page with aggregated metrics.
     */
    public function index(Request $request)
    {
        $metrics = $this->getDashboardMetrics();

        // Count stock batches expiring within 30 days (with remaining quantity)
        $expiryCount = StockLevel::where('quantity', '>', 0)
            ->whereDate('expiry_date', '<=', Carbon::now()->copy()->addDays(30))
            ->count();

        $filterConfig = $this->buildInternalSalesFilters($request);
        $internalSales = $this->getInternalSalesPerformance(
            $filterConfig['start'],
            $filterConfig['end'],
            $filterConfig['top']
        );

        $supplierFilters = $this->buildSupplierPerformanceFilters($request);

        return view('reports', array_merge($metrics, [
            'expiryCount' => $expiryCount,
            'internalSales' => $internalSales,
            'internalSalesFilters' => [
                'start_date' => $filterConfig['start']->format('Y-m-d'),
                'end_date' => $filterConfig['end']->format('Y-m-d'),
                'top' => $filterConfig['top'],
            ],
            'supplierPerformance' => $this->getSupplierPerformance(
                $supplierFilters['start'],
                $supplierFilters['end'],
                $supplierFilters['top']
            ),
            'supplierFilters' => [
                'start_date' => $supplierFilters['start']->format('Y-m-d'),
                'end_date' => $supplierFilters['end']->format('Y-m-d'),
                'top' => $supplierFilters['top'],
            ],
        ]));
    }

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

        $inventorySnapshot = $this->buildInventorySnapshot();
        $pendingOrders = $this->getPendingOrdersSummary();

        return [
            'weeklySales' => $weeklySales,
            'monthlySales' => $monthlySales,
            'inventorySummary' => $inventorySnapshot['summary'],
            'availableStock' => $inventorySnapshot['available_stock'],
            'availableStockChart' => $inventorySnapshot['available_stock_chart'],
            'lowStockItems' => $inventorySnapshot['low_stock_items'],
            'lowStockCount' => $inventorySnapshot['low_stock_count'],
            'criticalStockItems' => $inventorySnapshot['critical_stock_items'],
            'criticalStockCount' => $inventorySnapshot['critical_stock_count'],
            'pendingOrders' => $pendingOrders['orders'],
            'pendingOrdersCount' => $pendingOrders['count'],
            'topSellingItems' => $this->getTopSellingItems(),
        ];
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
            ->whereIn('order_status', ['Confirmed', 'Completed']) // support new + legacy
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
            'completed_orders' => $totalOrders,
            'total_revenue_raw' => (float) $totalRevenue,
            'total_revenue' => number_format($totalRevenue, 2, '.', ','),
            'start_date' => $startDate->format('Y-m-d'),
            'end_date' => $endDate->format('Y-m-d'),
        ];
    }

    protected function buildInternalSalesFilters(Request $request): array
    {
        $end = $request->filled('end_date') ? Carbon::parse($request->input('end_date')) : Carbon::now();
        $start = $request->filled('start_date')
            ? Carbon::parse($request->input('start_date'))
            : $end->copy()->subDays(30);

        if ($start->greaterThan($end)) {
            [$start, $end] = [$end->copy(), $start];
        }

        $top = (int) $request->input('top', 10);
        if ($top <= 0) {
            $top = 10;
        }
        $top = min($top, 100);

        return [
            'start' => $start->startOfDay(),
            'end' => $end->endOfDay(),
            'top' => $top,
        ];
    }

    protected function getInternalSalesPerformance(Carbon $startDate, Carbon $endDate, int $top): array
    {
        $internalSupplier = Supplier::internal()->first();

        if (!$internalSupplier) {
            return [
                'rows' => collect(),
                'total_quantity' => 0,
                'total_sales' => 0,
                'supplier_found' => false,
            ];
        }

        $rows = OrderItem::select([
                'inventory_items.item_name as item_name',
                DB::raw('SUM(order_items.quantity_ordered) as quantity_sold'),
                DB::raw('SUM(order_items.quantity_ordered * order_items.unit_price) as total_sales'),
            ])
            ->join('orders', 'order_items.order_id', '=', 'orders.id')
            ->join('inventory_items', 'order_items.item_id', '=', 'inventory_items.id')
            ->where('orders.supplier_id', $internalSupplier->id)
            ->whereIn('orders.order_status', ['Confirmed', 'Completed'])
            ->whereBetween('orders.order_date', [$startDate, $endDate])
            ->groupBy('inventory_items.id', 'inventory_items.item_name')
            ->orderByDesc('total_sales')
            ->limit($top)
            ->get();

        $totalQuantity = (int) $rows->sum('quantity_sold');
        $totalSales = (float) $rows->sum('total_sales');

        $ranked = $rows->sortByDesc('total_sales')->values()->map(function ($row, $index) {
            return [
                'item_name' => $row->item_name,
                'category' => 'Nihon Cafe',
                'quantity_sold' => (int) $row->quantity_sold,
                'total_sales' => (float) $row->total_sales,
                'rank' => $index + 1,
            ];
        });

        return [
            'rows' => $ranked,
            'total_quantity' => $totalQuantity,
            'total_sales' => $totalSales,
            'supplier_found' => true,
        ];
    }

    protected function buildSupplierPerformanceFilters(Request $request): array
    {
        $end = $request->filled('supplier_end_date')
            ? Carbon::parse($request->input('supplier_end_date'))
            : Carbon::now();

        $start = $request->filled('supplier_start_date')
            ? Carbon::parse($request->input('supplier_start_date'))
            : $end->copy()->subDays(30);

        if ($start->greaterThan($end)) {
            [$start, $end] = [$end->copy(), $start];
        }

        $top = (int) $request->input('supplier_top', 10);
        if ($top <= 0) {
            $top = 10;
        }
        $top = min($top, 50);

        return [
            'start' => $start->startOfDay(),
            'end' => $end->endOfDay(),
            'top' => $top,
        ];
    }

    protected function buildInventorySnapshot(): array
    {
        $items = InventoryItem::with('stockLevels')->orderBy('item_name')->get();

        $priceMap = OrderItem::select('order_items.item_id', DB::raw('AVG(order_items.unit_price) as avg_price'))
            ->join('orders', 'orders.id', '=', 'order_items.order_id')
            ->join('inventory_items', 'inventory_items.id', '=', 'order_items.item_id')
            ->where('orders.action_type', 'Add')
            ->whereIn('orders.order_status', ['Confirmed', 'Completed'])
            ->groupBy('order_items.item_id')
            ->pluck('avg_price', 'item_id');

        $summary = [
            'total_quantity' => 0,
            'sku_count' => $items->count(),
            'total_value' => 0,
        ];

        $availableStats = [];
        $lowStock = [];
        $criticalStock = [];

        foreach ($items as $item) {
            $quantity = (float) $item->stockLevels->sum('quantity');
            $thresholdRaw = $item->stockLevels->max('minimum_stock_threshold');
            $threshold = $thresholdRaw !== null ? (float) $thresholdRaw : null;
            $effectiveLowThreshold = $threshold !== null && $threshold > self::CRITICAL_THRESHOLD
                ? $threshold
                : null;

            $summary['total_quantity'] += $quantity;
            $summary['total_value'] += $quantity * (float) ($priceMap[$item->id] ?? 0);

            if ($quantity > 0) {
                $availableStats[] = [
                    'name' => $item->item_name,
                    'quantity' => $quantity,
                ];
            }

            if ($quantity <= self::CRITICAL_THRESHOLD) {
                $criticalStock[] = [
                    'name' => $item->item_name,
                    'quantity' => $quantity,
                    'threshold' => self::CRITICAL_THRESHOLD,
                ];
            } elseif ($effectiveLowThreshold !== null && $quantity <= $effectiveLowThreshold) {
                $lowStock[] = [
                    'name' => $item->item_name,
                    'quantity' => $quantity,
                    'threshold' => $effectiveLowThreshold,
                ];
            }
        }

        usort($availableStats, fn($a, $b) => $b['quantity'] <=> $a['quantity']);
        $availableChart = array_slice($availableStats, 0, 8);
        $availableNames = array_column(array_slice($availableStats, 0, 16), 'name');

        usort($criticalStock, fn($a, $b) => $a['quantity'] <=> $b['quantity']);
        $totalCriticalCount = count($criticalStock);
        $criticalStock = array_slice($criticalStock, 0, 8);

        usort($lowStock, fn($a, $b) => $a['quantity'] <=> $b['quantity']);
        $totalLowStockCount = count($lowStock);
        $lowStock = array_slice($lowStock, 0, 8);

        return [
            'summary' => $summary,
            'available_stock' => $availableNames,
            'available_stock_chart' => $availableChart,
            'low_stock_items' => $lowStock,
            'low_stock_count' => $totalLowStockCount,
            'critical_stock_items' => $criticalStock,
            'critical_stock_count' => $totalCriticalCount,
        ];
    }

    protected function getPendingOrdersSummary(): array
    {
        $orders = Order::with('supplier')
            ->where('order_type', 'Supplier')
            ->where('order_status', 'Pending')
            ->orderByDesc('order_date')
            ->limit(6)
            ->get();

        $list = $orders->map(function (Order $order) {
            return [
                'id' => $order->id,
                'display_id' => sprintf('ORD-%04d', $order->id),
                'supplier' => $order->supplier?->supplier_name ?? 'Unknown Supplier',
                'order_date' => Carbon::parse($order->order_date)->format('Y-m-d'),
                'expected_date' => $order->expected_date ? Carbon::parse($order->expected_date)->format('Y-m-d') : null,
            ];
        });

        return [
            'count' => $orders->count(),
            'orders' => $list,
        ];
    }

    protected function getTopSellingItems(): array
    {
        $since = Carbon::now()->subDays(30);

        $rows = OrderItem::select('order_items.item_id', 'inventory_items.item_name', DB::raw('SUM(order_items.quantity_ordered) as total_quantity'))
            ->join('orders', 'orders.id', '=', 'order_items.order_id')
            ->join('inventory_items', 'inventory_items.id', '=', 'order_items.item_id')
            ->where('orders.action_type', 'Deduct')
            ->whereIn('orders.order_status', ['Confirmed', 'Completed'])
            ->where('orders.order_date', '>=', $since)
            ->groupBy('order_items.item_id', 'inventory_items.item_name')
            ->orderByDesc('total_quantity')
            ->limit(5)
            ->get()
            ->map(function ($row) {
                return [
                    'name' => $row->item_name,
                    'quantity' => (float) $row->total_quantity,
                ];
            });

        return $rows->toArray();
    }

    protected function getSupplierPerformance(?Carbon $start = null, ?Carbon $end = null, ?int $top = null)
    {
        $rows = Supplier::query()
            ->select([
                'suppliers.id',
                'suppliers.supplier_name',
                'suppliers.contact_person',
                'suppliers.phone',
                DB::raw('COUNT(o.id) as total_orders'),
                DB::raw('SUM(CASE WHEN o.expected_date IS NOT NULL AND o.status_processed_at IS NOT NULL AND o.status_processed_at <= o.expected_date THEN 1 ELSE 0 END) as on_time_orders'),
                DB::raw('MAX(COALESCE(o.status_processed_at, o.order_date)) as last_delivery_at'),
            ])
            ->leftJoin('orders as o', function ($join) use ($start, $end) {
                $join->on('o.supplier_id', '=', 'suppliers.id')
                    ->where('o.order_type', 'Supplier')
                    ->whereIn('o.order_status', ['Confirmed', 'Completed']);

                if ($start && $end) {
                    $join->whereBetween('o.order_date', [
                        $start->toDateString(),
                        $end->toDateString(),
                    ]);
                }
            })
            ->where('suppliers.is_system', false)
            ->groupBy('suppliers.id', 'suppliers.supplier_name', 'suppliers.contact_person', 'suppliers.phone')
            ->orderByDesc(DB::raw('total_orders'))
            ->orderBy('suppliers.supplier_name')
            ->when($top, fn($query, $limit) => $query->limit($limit))
            ->get();

        return $rows->map(function ($row) {
            $totalOrders = (int) $row->total_orders;
            $onTimeOrders = (int) $row->on_time_orders;
            $rate = $totalOrders > 0 ? round(($onTimeOrders / $totalOrders) * 100) : null;
            $contactParts = array_filter([$row->contact_person, $row->phone]);
            $contact = count($contactParts) ? implode(' - ', $contactParts) : 'N/A';
            $lastDelivery = $row->last_delivery_at ? Carbon::parse($row->last_delivery_at)->format('Y-m-d') : 'N/A';

            return [
                'name' => $row->supplier_name,
                'contact' => $contact,
                'total_orders' => $totalOrders,
                'on_time_rate' => $rate,
                'last_delivery' => $lastDelivery,
            ];
        });
    }
}
