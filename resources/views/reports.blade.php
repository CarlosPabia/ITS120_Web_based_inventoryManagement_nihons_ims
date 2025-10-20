<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Reports - Nihon Cafe</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/5.15.4/css/all.min.css">
    <link rel="stylesheet" href="{{ asset('main.css') }}">
    <link rel="stylesheet" href="{{ asset('reports.css') }}">
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
                    <span class="profile-name">@auth {{ Auth::user()->first_name }} {{ Auth::user()->last_name }} @endauth</span>
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
                <li class="nav-item"><a href="{{ route('dashboard') }}" class="nav-link"><i class="fas fa-chart-line"></i> Dashboard</a></li>
                <li class="nav-item"><a href="{{ route('orders.index') }}" class="nav-link"><i class="fas fa-clipboard-list"></i> Orders</a></li>
                <li class="nav-item"><a href="{{ route('inventory.index') }}" class="nav-link"><i class="fas fa-boxes"></i> Inventory</a></li>
                <li class="nav-item"><a href="{{ route('suppliers.index') }}" class="nav-link"><i class="fas fa-truck"></i> Suppliers</a></li>
                <li class="nav-item sidebar-title-item"><a href="{{ route('reports.index') }}" class="nav-link active"><i class="fas fa-file-alt"></i> Reports</a></li>
                <li class="nav-item settings-separator"><a href="{{ route('settings.index') }}" class="nav-link"><i class="fas fa-cog"></i> Settings</a></li>
            </ul>
        </nav>

        <main class="content-area">
            <header class="content-header">
                <div class="page-title-block active">
                    <h2>Reports Overview</h2>
                </div>
            </header>

            <section id="reports-view" class="view">
                <div class="report-header-controls">
                    
                </div>

                <!-- Report hub: first two removed; Order Report renamed to Sales Report -->
                <div id="report-hub" class="report-hub-grid">
                    <button class="report-card" data-report="sales-report">
                        <i class="fas fa-shopping-basket"></i>
                        <p>Sales Report</p>
                    </button>
                    <button class="report-card" data-report="supplier-report">
                        <i class="fas fa-truck"></i>
                        <p>Supplier Report</p>
                    </button>
                </div>

                <div id="report-details-container" class="report-details-container hidden">

                    <div id="sales-report" class="report-detail-card card hidden">
                        <h3 class="report-title">ORDER REPORT (NIHON CAFE SALES PERFORMANCE)</h3>

                        <form method="GET" action="{{ route('reports.index') }}" class="report-filter-form">
                            <div class="filter-grid">
                                <label class="filter-field">
                                    <span>Start Date</span>
                                    <input type="date" name="start_date" value="{{ $internalSalesFilters['start_date'] ?? '' }}">
                                </label>
                                <label class="filter-field">
                                    <span>End Date</span>
                                    <input type="date" name="end_date" value="{{ $internalSalesFilters['end_date'] ?? '' }}">
                                </label>
                                <label class="filter-field">
                                    <span>Top Results</span>
                                    <select name="top">
                                        @foreach([5,10,15,20,50] as $option)
                                            <option value="{{ $option }}" @selected(($internalSalesFilters['top'] ?? 10) == $option)>Top {{ $option }}</option>
                                        @endforeach
                                    </select>
                                </label>
                                <div class="filter-actions">
                                    <button type="submit" class="primary-btn">Apply</button>
                                    <a href="{{ route('reports.index') }}" class="secondary-btn outline-btn">Reset</a>
                                </div>
                            </div>
                        </form>

                        <table class="data-table report-table">
                            <thead>
                                <tr>
                                    <th>Item Name</th>
                                    <th>Brand</th>
                                    <th style="text-align:right;">Quantity Sold</th>
                                    <th style="text-align:right;">Total Sales</th>
                                    <th style="text-align:center;">Best Seller Rank</th>
                                </tr>
                            </thead>
                            <tbody>
                                @if(!($internalSales['supplier_found'] ?? false))
                                    <tr>
                                        <td colspan="5" style="text-align:center; padding:20px;">
                                            No internal supplier was found. Add the Nihon Cafe supplier to view this report.
                                        </td>
                                    </tr>
                                @else
                                    @forelse(($internalSales['rows'] ?? collect()) as $row)
                                        <tr>
                                            <td>{{ $row['item_name'] }}</td>
                                            <td>{{ $row['category'] }}</td>
                                            <td style="text-align:right;">{{ number_format($row['quantity_sold']) }}</td>
                                            <td style="text-align:right;">&#8369;{{ number_format($row['total_sales'], 2) }}</td>
                                            <td style="text-align:center;">
                                                <span class="{{ $row['rank'] === 1 ? 'status-best' : 'status-normal' }}">#{{ $row['rank'] }}</span>
                                            </td>
                                        </tr>
                                    @empty
                                        <tr>
                                            <td colspan="5" style="text-align:center; padding:20px;">
                                                No orders found for the selected period.
                                            </td>
                                        </tr>
                                    @endforelse
                                @endif
                            </tbody>
                        </table>

                        @if(($internalSales['supplier_found'] ?? false) && ($internalSales['rows']->count() ?? 0) > 0)
                            <div class="report-summary">
                                <p><strong>Total Quantity Sold:</strong> {{ number_format($internalSales['total_quantity']) }} units</p>
                                <p><strong>Total Sales:</strong> &#8369;{{ number_format($internalSales['total_sales'], 2) }}</p>
                                <p><strong>Reporting Window:</strong> {{ $internalSalesFilters['start_date'] ?? '' }} to {{ $internalSalesFilters['end_date'] ?? '' }}</p>
                            </div>
                        @endif
                    </div>

                    <div id="supplier-report" class="report-detail-card card hidden">
                        <h3 class="report-title">SUPPLIER REPORT</h3>
                        <table class="data-table report-table">
                            <thead>
                                <tr>
                                    <th>Supplier Name</th>
                                    <th>Contact</th>
                                    <th>Total Orders</th>
                                    <th>On-time Rate</th>
                                    <th>Last Delivery</th>
                                </tr>
                            </thead>
                            <tbody>
                                @forelse($supplierPerformance as $supplier)
                                    <tr>
                                        <td>{{ $supplier['name'] }}</td>
                                        <td>{{ $supplier['contact'] }}</td>
                                        <td>{{ number_format($supplier['total_orders']) }}</td>
                                        <td>
                                            {{ $supplier['on_time_rate'] !== null ? $supplier['on_time_rate'] . '%' : 'â€”' }}
                                        </td>
                                        <td>{{ $supplier['last_delivery'] }}</td>
                                    </tr>
                                @empty
                                    <tr>
                                        <td colspan="5" style="text-align:center; padding:20px;">
                                            No supplier performance data available yet.
                                        </td>
                                    </tr>
                                @endforelse
                            </tbody>
                        </table>
                    </div>

                    <button id="report-back-btn" class="back-btn hidden">Back to Report Hub</button>
                </div>
            </section>
        </main>
    </div>

    <script>
        document.addEventListener('DOMContentLoaded', function() {
            const userDropdown = document.querySelector('.user-dropdown');
            const dropdownContent = userDropdown.querySelector('.dropdown-content');
            userDropdown.addEventListener('click', function(event) {
                event.stopPropagation();
                dropdownContent.classList.toggle('show');
            });
            window.addEventListener('click', function(event) {
                if (!userDropdown.contains(event.target)) dropdownContent.classList.remove('show');
            });

            const reportHub = document.getElementById('report-hub');
            const reportDetailsContainer = document.getElementById('report-details-container');
            const reportCards = document.querySelectorAll('.report-card');
            const detailCards = document.querySelectorAll('.report-detail-card');
            const backButton = document.getElementById('report-back-btn');
            const reportControls = document.querySelector('.report-header-controls');

            reportCards.forEach(card => {
                card.addEventListener('click', function() {
                    const reportId = this.getAttribute('data-report');
                    reportHub.classList.add('hidden');
                    if (reportControls) reportControls.classList.add('hidden');
                    reportDetailsContainer.classList.remove('hidden');
                    backButton.classList.remove('hidden');
                    detailCards.forEach(dc => dc.classList.add('hidden'));
                    document.getElementById(reportId).classList.remove('hidden');
                });
            });

            backButton.addEventListener('click', function() {
                reportHub.classList.remove('hidden');
                if (reportControls) reportControls.classList.remove('hidden');
                reportDetailsContainer.classList.add('hidden');
                backButton.classList.add('hidden');
                detailCards.forEach(dc => dc.classList.add('hidden'));
            });
        });
    </script>
    <script src="{{ asset('js/notify.js') }}" defer></script>
</body>
</html>
