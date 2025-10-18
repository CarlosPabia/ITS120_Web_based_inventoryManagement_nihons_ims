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
            <img src="{{ asset('logo.png') }}" alt="Nihon Cafe Logo" class="logo">
            <span class="logo-text">NIHON CAFE</span>
        </div>
        <div class="navbar-right">
            <div class="user-dropdown">
                <div class="user-profile-trigger">
                    <img src="{{ asset('user.png') }}" alt="User Avatar" class="profile-avatar">
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
                    <div class="search-bar">
                        <i class="fas fa-search"></i>
                        <input type="text" placeholder="Search reports..." />
                    </div>
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
                        <h3 class="report-title">ORDER REPORT (SALES PERFORMANCE)</h3>
                        <table class="data-table report-table">
                            <thead>
                                <tr>
                                    <th>Item Name</th>
                                    <th>Category</th>
                                    <th>Quantity Sold</th>
                                    <th>Total Sales</th>
                                    <th>Best Seller Rank</th>
                                    <th>Date Range</th>
                                </tr>
                            </thead>
                            <tbody>
                                <tr><td>Matcha Latte</td><td>Latte</td><td>150</td><td>&#8369;7,500</td><td><span class="status-best">#1</span></td><td>Sept 1-7</td></tr>
                                <tr><td>Coffee Beans</td><td>Arabica</td><td>120</td><td>&#8369;6,000</td><td><span class="status-normal">#2</span></td><td>Sept 1-7</td></tr>
                                <tr><td>Milk Carton</td><td>Dairy</td><td>80</td><td>&#8369;4,000</td><td><span class="status-normal">#3</span></td><td>Sept 1-7</td></tr>
                                <tr><td>Cappuccino Mix</td><td>Medium Roast</td><td>60</td><td>&#8369;3,000</td><td><span class="status-normal">#4</span></td><td>Sept 1-7</td></tr>
                                <tr><td>Americano Pack</td><td>Dark Roast</td><td>40</td><td>&#8369;3,000</td><td><span class="status-normal">#5</span></td><td>Sept 1-7</td></tr>
                                <tr><td>Matcha Powder</td><td>Japanese</td><td>30</td><td>&#8369;3,000</td><td><span class="status-normal">#6</span></td><td>Sept 1-7</td></tr>
                            </tbody>
                        </table>
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
                                <tr><td>Kyoto Imports</td><td>+81 03-0000-0000</td><td>42</td><td>96%</td><td>2025-10-12</td></tr>
                                <tr><td>BakeHouse Corp.</td><td>+33 1 555 0100</td><td>36</td><td>91%</td><td>2025-10-10</td></tr>
                                <tr><td>DairyPure Co.</td><td>+1 555 0199</td><td>27</td><td>89%</td><td>2025-10-07</td></tr>
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
</body>
</html>
