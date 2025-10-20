<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>Inventory - Nihon Cafe</title>

    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/5.15.4/css/all.min.css">
    <link rel="stylesheet" href="{{ asset('main.css') }}">
    <link rel="stylesheet" href="{{ asset('inventory.css') }}">
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
                <li class="nav-item"><a href="{{ route('orders.index') }}" class="nav-link"><i class="fas fa-shopping-cart"></i> Orders</a></li>
                <li class="nav-item sidebar-title-item"><a href="{{ route('inventory.index') }}" class="nav-link active"><i class="fas fa-boxes"></i> Inventory</a></li>
                <li class="nav-item"><a href="{{ route('suppliers.index') }}" class="nav-link"><i class="fas fa-truck"></i> Suppliers</a></li>
                <li class="nav-item"><a href="{{ route('reports.index') }}" class="nav-link"><i class="fas fa-file-alt"></i> Reports</a></li>
                <li class="nav-item settings-separator"><a href="{{ route('settings.index') }}" class="nav-link"><i class="fas fa-cog"></i> Settings</a></li>
            </ul>
        </nav>

        <main class="content-area" data-role="{{ Auth::user()->role->role_name ?? '' }}">
            <div class="inventory-controls">
                <div class="search-bar">
                    <i class="fas fa-search"></i>
                    <input type="text" id="search-input" placeholder="Search inventory..." />
                </div>
                <a href="{{ route('dashboard') }}" class="security-btn">Back to Home</a>
            </div>

            <div class="inventory-table-container card">
                <table class="inventory-table">
                    <thead>
                        <tr>
                            <th>SKU / ID</th>
                            <th>Item Name</th>
                            <th>Quantity in Stock</th>
                            <th>Unit</th>
                            <th>Expiry Date</th>
                            <th>Supplier</th>
                            <th>Status</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody id="inventory-table-body">
                        <tr><td colspan="8" style="text-align:center; padding: 20px;">Loading inventory...</td></tr>
                    </tbody>
                </table>
            </div>
        </main>
    </div>

    <!-- Edit Expiry Modal -->
    <div id="edit-expiry-modal" class="modal-overlay hidden">
        <div class="modal-content">
            <h2 style="margin-top:0;">Edit Expiry Date</h2>
            <p id="edit-expiry-item-name" style="color:#555; margin-top:0;"></p>
            <div style="display:grid; grid-template-columns: 1fr; gap:10px;">
                <label style="display:flex; flex-direction:column; gap:6px;">
                    <span style="font-size:12px; font-weight:600; color:#666;">Expiry Date</span>
                    <input type="date" id="edit-expiry-input" class="form-control" />
                </label>
            </div>
            <div style="display:flex; justify-content:flex-end; gap:10px; margin-top:16px;">
                <button type="button" class="secondary-btn outline-btn" id="edit-expiry-cancel">Cancel</button>
                <button type="button" class="primary-btn" id="edit-expiry-save">Save</button>
            </div>
        </div>
    </div>

    <!-- Edit Details Modal -->
    <div id="edit-details-modal" class="modal-overlay hidden">
        <div class="modal-content">
            <h2 style="margin-top:0;">Edit Item Details</h2>
            <p id="edit-details-item-name" style="color:#555; margin-top:0;"></p>
            <div style="display:grid; grid-template-columns: repeat(auto-fit, minmax(180px, 1fr)); gap:10px;">
                <label style="display:flex; flex-direction:column; gap:6px;">
                    <span style="font-size:12px; font-weight:600; color:#666;">Unit of Measure</span>
                    <input type="text" id="edit-unit-input" class="form-control" placeholder="e.g., kg, L, pcs" />
                </label>
                <label style="display:flex; flex-direction:column; gap:6px;">
                    <span style="font-size:12px; font-weight:600; color:#666;">Minimum Stock Threshold</span>
                    <input type="number" id="edit-threshold-input" class="form-control" min="0" step="1" />
                </label>
                <label style="display:flex; flex-direction:column; gap:6px;">
                    <span style="font-size:12px; font-weight:600; color:#666;">Quantity in Stock</span>
                    <input type="number" id="edit-quantity-input" class="form-control" min="0" step="0.01" />
                </label>
            </div>
            <div style="display:flex; justify-content:flex-end; gap:10px; margin-top:16px;">
                <button type="button" class="secondary-btn outline-btn" id="edit-details-cancel">Cancel</button>
                <button type="button" class="primary-btn" id="edit-details-save">Save</button>
            </div>
        </div>
    </div>

    <script src="{{ asset('js/inventory.js') }}" defer></script>
    <script src="{{ asset('js/notify.js') }}" defer></script>
</body>
</html>
