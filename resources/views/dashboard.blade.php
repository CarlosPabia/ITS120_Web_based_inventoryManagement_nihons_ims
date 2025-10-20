<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Dashboard - Nihon Cafe</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/5.15.4/css/all.min.css">
    <link rel="stylesheet" href="{{ asset('main.css') }}">
    <link rel="stylesheet" href="{{ asset('dashboard.css') }}">
</head>
<body>
    <nav class="navbar">
        <div class="navbar-left">
            <img src="{{ asset('image/logo.png') }}" alt="Nihon Cafe Logo" class="logo">
            <span class="logo-text">NIHON CAFE</span>
        </div>
        <div class="navbar-right">
            <div class="user-dropdown">
                <div class="user-profile-trigger">
                    <img src="{{ asset('image/logo.png') }}" alt="User Avatar" class="profile-avatar">
                    <span class="profile-name">
                        @auth {{ Auth::user()->first_name }} {{ Auth::user()->last_name }} @endauth
                    </span>
                    <i class="fas fa-chevron-down dropdown-icon"></i>
                </div>
                <div class="dropdown-content">
                    <form method="POST" action="{{ route('logout') }}">
                        @csrf
                        <button type="submit" class="dropdown-item logout-btn-dropdown">
                            <i class="fas fa-sign-out-alt"></i> Logout
                        </button>
                    </form>
                </div>
            </div>
        </div>
    </nav>

    <div class="app-container">
        <nav id="sidebar" class="sidebar">
            <div class="sidebar-header-placeholder"></div>
            <ul class="nav-links">
                <li class="nav-item sidebar-title-item">
                    <a href="{{ route('dashboard') }}" class="nav-link active">
                        <i class="fas fa-chart-line"></i> Dashboard
                    </a>
                </li>
                <li class="nav-item">
                    <a href="{{ route('orders.index') }}" class="nav-link">
                        <i class="fas fa-clipboard-list"></i> Orders
                    </a>
                </li>
                <li class="nav-item">
                    <a href="{{ route('inventory.index') }}" class="nav-link">
                        <i class="fas fa-boxes"></i> Inventory
                    </a>
                </li>
                <li class="nav-item">
                    <a href="{{ route('suppliers.index') }}" class="nav-link">
                        <i class="fas fa-truck"></i> Suppliers
                    </a>
                </li>
                <li class="nav-item">
                    <a href="{{ route('reports.index') }}" class="nav-link">
                        <i class="fas fa-file-alt"></i> Reports
                    </a>
                </li>
                <li class="nav-item settings-separator">
                    <a href="{{ route('settings.index') }}" class="nav-link">
                        <i class="fas fa-cog"></i> Settings
                    </a>
                </li>
            </ul>
        </nav>

        <main class="content-area dashboard-page">
            <header class="content-header dashboard-header">
                <div class="dashboard-title-box">Dashboard</div>
            </header>

            <div class="dashboard-search-bar">
                <i class="fas fa-search"></i>
                <input type="text" placeholder="Search Inventory, Orders, or Suppliers..." class="search-input">
                <a href="{{ route('settings.index') }}#access-control" class="access-security-link">
                    <i class="fas fa-lock"></i> Access & Security
                </a>
            </div>

            @php
                $inventorySummary = $inventorySummary ?? ['total_quantity' => 0, 'sku_count' => 0, 'total_value' => 0];
                $lowStockItems = $lowStockItems ?? [];
                $lowStockCount = $lowStockCount ?? count($lowStockItems);
                $criticalStockItems = $criticalStockItems ?? [];
                $criticalStockCount = $criticalStockCount ?? count($criticalStockItems);
                $availableStock = $availableStock ?? [];
                $availableStockChart = $availableStockChart ?? [];
                $salesChartData = array_slice($topSellingItems ?? [], 0, 5);
                $weeklySales = $weeklySales ?? ['total_orders' => 0, 'completed_orders' => 0, 'total_revenue_raw' => 0, 'total_revenue' => '0.00', 'start_date' => null, 'end_date' => null];
                $monthlySales = $monthlySales ?? ['total_orders' => 0, 'completed_orders' => 0, 'total_revenue_raw' => 0, 'total_revenue' => '0.00', 'start_date' => null, 'end_date' => null];
                $topSellingItems = $topSellingItems ?? [];
                $pendingOrdersList = collect($pendingOrders ?? []);
                $pendingOrdersTotal = $pendingOrdersCount ?? $pendingOrdersList->count();
                $formatCurrency = static function ($amount) {
                    return "\u{20B1}" . number_format((float) $amount, 2);
                };
            @endphp


            <section class="dashboard-grid-1">
                <div class="card summary-box inventory-summary-card">
                    <button class="close-btn" type="button"><i class="fas fa-times"></i></button>
                    <h4>Inventory Summary</h4>
                    <div class="summary-content">
                        <span class="main-metric">{{ number_format((float) $inventorySummary['total_quantity']) }}</span>
                        <div class="sub-metric">
                            <p class="sub-metric-value">Total Value:</p>
                            <p class="sub-metric-label">{{ $formatCurrency($inventorySummary['total_value']) }}</p>
                        </div>
                        <p class="sub-metric-label">{{ number_format((int) $inventorySummary['sku_count']) }} items</p>
                    </div>
                    <a href="{{ route('inventory.index') }}" class="review-btn">Review</a>
                </div>

                <div class="card summary-box critical-stock-card">
                    <button class="close-btn" type="button"><i class="fas fa-times"></i></button>
                    <h4>Critical Stock</h4>
                    <div class="summary-content">
                        <i class="fas fa-exclamation-circle critical-icon"></i>
                        <span class="main-metric">{{ number_format((int) $criticalStockCount) }}</span>
                        <p class="sub-metric-label">Items at or below 10 units</p>
                    </div>
                    @if($criticalStockCount)
                        <ul class="mini-summary-list">
                            @foreach($criticalStockItems as $item)
                                <li>{{ $item['name'] }} ({{ number_format($item['quantity']) }} units)</li>
                            @endforeach
                        </ul>
                    @else
                        <p class="sub-metric-label">No items at or below 10 units.</p>
                    @endif
                    <a href="{{ route('inventory.index') }}" class="review-btn">Review</a>
                </div>

                <div class="card summary-box low-stock-card">
                    <button class="close-btn" type="button"><i class="fas fa-times"></i></button>
                    <h4>Low Stock Items</h4>
                    <div class="summary-content">
                        <i class="fas fa-exclamation-triangle warning-icon"></i>
                        <span class="main-metric">{{ number_format((int) $lowStockCount) }}</span>
                        <div class="sub-metric">
                            <p class="sub-metric-value">{{ number_format(count($lowStockItems)) }} shown</p>
                            <p class="sub-metric-label">Below manager thresholds</p>
                        </div>
                    </div>
                    @if($lowStockCount)
                        <ul class="mini-summary-list">
                            @foreach($lowStockItems as $item)
                                <li>{{ $item['name'] }} ({{ number_format($item['quantity']) }}/{{ number_format($item['threshold']) }})</li>
                            @endforeach
                        </ul>
                    @else
                        <p class="sub-metric-label">No low stock alerts.</p>
                    @endif
                    <a href="{{ route('inventory.index') }}" class="review-btn">Review</a>
                </div>

                <div class="card summary-box pending-orders-card">
                    <button class="close-btn" type="button"><i class="fas fa-times"></i></button>
                    <h4>Pending Orders</h4>
                    <div class="summary-content">
                        <i class="fas fa-box-open order-icon"></i>
                        <span class="main-metric">{{ number_format((int) $pendingOrdersTotal) }}</span>
                        <p class="sub-metric-label">{{ number_format($pendingOrdersList->count()) }} orders pending</p>
                    </div>
                    @if($pendingOrdersList->isNotEmpty())
                        <ul class="mini-summary-list">
                            @foreach($pendingOrdersList->take(4) as $order)
                                <li>{{ $order['display_id'] }} &mdash; {{ $order['supplier'] }} &mdash; {{ $order['order_date'] }}</li>
                            @endforeach
                        </ul>
                    @else
                        <p class="sub-metric-label">No supplier orders awaiting processing.</p>
                    @endif
                    <a href="{{ route('orders.index') }}" class="review-btn">Review</a>
                </div>
            </section>

            <section class="dashboard-grid-2">
                <div class="card inventory-overview-card">
                    <div class="card-header">
                        <h3>Inventory Overview</h3>
                        <a href="{{ route('inventory.index') }}#inventory" class="view-reports-link">View Inventory</a>
                    </div>
                    <div class="inventory-content-grid">
                        <div class="available-stocks">
                            <p class="list-title">Available Stocks</p>
                            <ul class="stock-list">
                                @forelse($availableStock as $name)
                                    <li>{{ $name }}</li>
                                @empty
                                    <li>No inventory on hand.</li>
                                @endforelse
                            </ul>
                        </div>
                        <div class="inventory-chart-container">
                            <canvas id="inventoryBarChart"></canvas>
                            <p class="chart-empty hidden" data-empty-target="inventory">No recent stock movement to display.</p>
                        </div>
                    </div>
                </div>

                <div class="card sales-overview-card">
                    <div class="card-header">
                        <h3>Sales & Orders Overview</h3>
                        <a href="{{ route('reports.index') }}#sales" class="view-reports-link">View Reports</a>
                    </div>
                    <div class="summary-details-grid">
                        <div class="weekly-monthly-summary">
                            <p class="list-title">Weekly / Monthly Summary</p>
                            <ul class="summary-list">
                                <li>Total Orders (week): <span>{{ number_format((int) $weeklySales['total_orders']) }}</span></li>
                                <li>Total Sales (week): <span>{{ $formatCurrency($weeklySales['total_revenue_raw']) }}</span></li>
                                <li>Completed Orders (week): <span>{{ number_format((int) $weeklySales['completed_orders']) }}</span></li>
                                <li class="separator"></li>
                                <li>Total Orders (month): <span>{{ number_format((int) $monthlySales['total_orders']) }}</span></li>
                                <li>Total Sales (month): <span>{{ $formatCurrency($monthlySales['total_revenue_raw']) }}</span></li>
                                <li>Completed Orders (month): <span>{{ number_format((int) $monthlySales['completed_orders']) }}</span></li>
                            </ul>
                        </div>
                        <div class="sales-chart-container">
                            <canvas id="salesPieChart"></canvas>
                            <p class="chart-empty hidden" data-empty-target="sales">No sales data to display.</p>
                        </div>
                    </div>
                    <div class="top-selling-items">
                        <p class="list-title">Top Selling Items</p>
                        <ul class="selling-list">
                            @forelse(array_slice($salesChartData, 0, 3) as $item)
                                <li>{{ $loop->iteration }}. {{ $item['name'] }} <span>{{ number_format($item['quantity']) }}</span></li>
                            @empty
                                <li>No sales recorded for the current period.</li>
                            @endforelse
                        </ul>
                    </div>
                </div>
            </section>

            
        </main>
    </div>

    <script>
        window.dashboardData = {
            inventoryBar: @json($availableStockChart),
            salesPie: @json($salesChartData),
            lowStockItems: @json($lowStockItems),
            criticalStockItems: @json($criticalStockItems),
        };
    </script>
    <script src="https://cdn.jsdelivr.net/npm/chart.js@4.4.0/dist/chart.umd.min.js" defer></script>
    <script src="{{ asset('js/dialog.js') }}" defer></script>
    <script src="{{ asset('js/dashboard.js') }}" defer></script>
    <script src="{{ asset('js/notify.js') }}" defer></script>
</body>
</html>
