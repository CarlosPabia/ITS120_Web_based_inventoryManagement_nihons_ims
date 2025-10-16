<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Dashboard - Nihon Café</title>
    
    <!-- External styles -->
    <link rel="stylesheet" href="{{ asset('main.css') }}">
    <link rel="stylesheet" href="{{ asset('dashboard.css') }}">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/5.15.4/css/all.min.css">
</head>
<body>

    <!-- Top navbar -->
    <nav class="navbar">
        <div class="navbar-left">
            <img src="{{ asset('logo.png') }}" alt="Nihon Cafe Logo" class="logo">
            <span class="logo-text">NIHON CAFE</span>
        </div>
        <div class="navbar-right">
            <div class="user-dropdown">
                <div class="user-profile-trigger">
                    <img src="{{ asset('user.png') }}" alt="User Avatar" class="profile-avatar">
                    <span class="profile-name">Peter Parks</span>
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

    <!-- Sidebar + Content -->
    <div class="app-container">

        <!-- Sidebar -->
        <nav id="sidebar" class="sidebar">
            <div class="sidebar-header-placeholder"></div>
            <ul class="nav-links">
                <li class="nav-item sidebar-title-item">
                    <a href="{{ route('dashboard') }}" class="nav-link {{ Route::is('dashboard') ? 'active' : '' }}">
                        <i class="fas fa-chart-line"></i> Dashboard
                    </a>
                </li>
                <li class="nav-item">
                    <a href="{{ route('orders.index') }}" class="nav-link {{ Route::is('orders.index') ? 'active' : '' }}">
                        <i class="fas fa-shopping-cart"></i> Orders
                    </a>
                </li>
                <li class="nav-item">
                    <a href="{{ route('inventory.index') }}" class="nav-link {{ Route::is('inventory.index') ? 'active' : '' }}">
                        <i class="fas fa-boxes"></i> Inventory
                    </a>
                </li>
                <li class="nav-item">
                    <a href="{{ route('suppliers.index') }}" class="nav-link {{ Route::is('suppliers.index') ? 'active' : '' }}">
                        <i class="fas fa-truck"></i> Suppliers
                    </a>
                </li>
                <li class="nav-item">
                    <a href="{{ route('reports.index') }}" class="nav-link {{ Route::is('reports.index') ? 'active' : '' }}">
                        <i class="fas fa-file-alt"></i> Reports
                    </a>
                </li>
                <li class="nav-item">
                    <a href="{{ route('settings.index') }}" class="nav-link {{ Route::is('settings.index') ? 'active' : '' }}">
                        <i class="fas fa-cog"></i> Settings
                    </a>
                </li>
            </ul>
        </nav>

        <!-- Main content area -->
        <main class="content-area">
            <section id="dashboard-view" class="view">

                <div class="dashboard-controls">
                    <div class="search-bar">
                        <i class="fas fa-search"></i>
                        <input type="text" placeholder="Search Inventory, Orders, or Suppliers...">
                    </div>
                    <a href="{{ route('settings.index') }}" class="security-btn">
                        <i class="fas fa-shield-alt"></i> Access & Security
                    </a>
                </div>

                <div class="summary-cards-grid">
                    <div class="card summary-card">
                        <h3>Inventory Summary</h3>
                        <div class="card-content">
                            <i class="fas fa-chart-pie summary-icon"></i>
                            <div class="summary-details">
                                <strong>152</strong>
                                <p>Total Value:</p>
                                <p>152 items</p>
                            </div>
                        </div>
                        <a href="{{ route('inventory.index') }}" class="review-btn">review</a>
                    </div>

                    <div class="card summary-card">
                        <h3>Low Stock Items</h3>
                        <div class="card-content">
                            <i class="fas fa-exclamation-triangle summary-icon alert"></i>
                            <div class="summary-details">
                                <strong>12</strong>
                                <p>Total Value:</p>
                                <p>12 items</p>
                            </div>
                        </div>
                        <a href="{{ route('inventory.index') }}" class="review-btn">review</a>
                    </div>

                    <div class="card summary-card">
                        <h3>Pending Orders</h3>
                        <div class="card-content">
                            <i class="fas fa-hourglass-half summary-icon"></i>
                            <div class="summary-details">
                                <strong>5</strong>
                                <p>5 orders pending</p>
                            </div>
                        </div>
                        <a href="{{ route('orders.index') }}" class="review-btn">review</a>
                    </div>
                </div>

                <div class="dashboard-overview-grid">

                    <div class="card overview-card inventory-overview-card">
                        <div class="card-header">
                            <h3>Inventory Overview</h3>
                            <a href="{{ route('inventory.index') }}" class="view-reports-link">View Reports</a>
                        </div>
                        <div class="inventory-overview-content">
                            <div class="stock-list-grid">
                                <ul>
                                    <li>Matcha Powder</li>
                                    <li>Coffee Beans</li>
                                    <li>Fresh Milk</li>
                                    <li>Cheesecake</li>
                                </ul>
                                <ul>
                                    <li>Green Tea Leaves</li>
                                    <li>Soy Milk</li>
                                    <li>Croissant Dough</li>
                                    <li>Chocolate Syrup</li>
                                </ul>
                            </div>
                            <div class="bar-chart-container">
                                <div class="bar" style="height: 70%;"></div>
                                <div class="bar" style="height: 90%;"></div>
                                <div class="bar" style="height: 50%;"></div>
                                <div class="bar" style="height: 80%;"></div>
                            </div>
                        </div>
                    </div>

                    <div class="card overview-card sales-orders-overview-card">
                        <div class="card-header">
                            <h3>Sales & Orders Overview</h3>
                            <a href="{{ route('reports.index') }}" class="view-reports-link">View Reports</a>
                        </div>

                        <div class="sales-overview-content">
                            <div class="sales-details-container">
                                <h4>Monthly Summary</h4>
                                <p>Total Orders: <strong>540</strong></p>
                                <p>Total Sales: <strong>₱420,300.00</strong></p>
                                <p>Completed Orders: <strong>522</strong></p>
                            </div>
                            <div class="pie-chart-container">
                                <div class="pie-chart-placeholder-actual"></div>
                            </div>
                        </div>
                    </div>

                    <div class="card overview-card top-selling-items-card">
                        <h3>Top 3 Selling Items</h3>
                        <ul>
                            <li>• Caffé Latte</li>
                            <li>• Matcha Latte</li>
                            <li>• Croissant</li>
                        </ul>
                    </div>
                </div>

               
            </section>
        </main>
    </div>

    <!-- User dropdown interaction -->
    <script>
        document.addEventListener('DOMContentLoaded', function () {
            const userDropdown = document.querySelector('.user-dropdown');
            const dropdownContent = userDropdown.querySelector('.dropdown-content');

            userDropdown.addEventListener('click', function (event) {
                event.stopPropagation();
                dropdownContent.classList.toggle('show');
            });

            window.addEventListener('click', function (event) {
                if (!userDropdown.contains(event.target)) {
                    dropdownContent.classList.remove('show');
                }
            });
        });
    </script>

</body>
</html>
