<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>Orders - Nihon Cafe</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/5.15.4/css/all.min.css">
    <link rel="stylesheet" href="{{ asset('main.css') }}">
    <link rel="stylesheet" href="{{ asset('orders.css') }}">
</head>
<body data-role="{{ Auth::user()->role->role_name ?? '' }}">

    <nav class="navbar">
        <div class="navbar-left">
            <img src="{{ asset('logo.png') }}" alt="Nihon Cafe Logo" class="logo">
            <span class="logo-text">NIHON CAFE</span>
        </div>
        <div class="navbar-right">
            <div class="user-dropdown">
                <div class="user-profile-trigger">
                    <img src="{{ asset('user.png') }}" alt="User Avatar" class="profile-avatar">
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
                <li class="nav-item">
                    <a href="{{ route('dashboard') }}" class="nav-link">
                        <i class="fas fa-chart-line"></i> Dashboard
                    </a>
                </li>
                <li class="nav-item sidebar-title-item">
                    <a href="{{ route('orders.index') }}" class="nav-link active">
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

        <main class="content-area">
            <header class="content-header">
                <div class="page-title-block active">
                    <h2>Orders</h2>
                </div>
            </header>

            <section id="orders-view" class="view orders-main-grid">

                <div class="order-list-panel">
                    <div class="table-controls-row">
                        <div class="search-bar orders-search">
                            <i class="fas fa-search"></i>
                            <input type="text" id="orders-search" placeholder="Search orders by ID or supplier...">
                        </div>
                        <div class="filter-group">
                            <select id="orders-type-filter" class="form-select">
                                <option value="All">All Actions</option>
                                <option value="Supplier">Adding</option>
                                <option value="Customer">Deducting</option>
                            </select>
                            <select id="orders-status-filter" class="form-select">
                                <option value="All">All Statuses</option>
                                <option value="Pending">Pending</option>
                                <option value="Confirmed">Confirmed</option>
                                <option value="Cancelled">Cancelled</option>
                            </select>
                        </div>
                    </div>

                    <div class="card orders-table-card">
                        <table class="data-table orders-table">
                            <thead>
                                <tr>
                                    <th>Order ID</th>
                                    <th>Action</th>
                                    <th>Supplier</th>
                                    <th>Status</th>
                                    <th>Order Date</th>
                                    <th>Expected Date</th>
                                    <th style="text-align:right;">Actions</th>
                                </tr>
                            </thead>
                            <tbody id="orders-table-body">
                                <tr>
                                    <td colspan="7" style="text-align:center; padding: 20px;">Loading Order History...</td>
                                </tr>
                            </tbody>
                        </table>
                    </div>
                </div>

                <aside class="create-order-panel card" aria-labelledby="create-order-title">
                    <div class="create-order-header">
                        <h3 id="create-order-title">Create Order</h3>
                        <div class="order-tab-group">
                            <button type="button" class="order-tab active" data-order-mode="Supplier">Adding</button>
                            <button type="button" class="order-tab" data-order-mode="Customer">Deducting</button>
                        </div>
                    </div>
                    <div class="order-form-content">
                        <p class="order-form-helper" id="order-form-helper-text">
                            Add new stock from an approved supplier catalog.
                        </p>
                        <ul class="order-form-hints">
                            <li>Choose an approved supplier to unlock catalog items.</li>
                            <li>Use the toggle above to switch between adding and deducting.</li>
                            <li>Orders remain pending until you confirm them in the history.</li>
                        </ul>

                        <form id="inline-order-form" class="inline-order-form">
                            <div class="form-section">
                                <label class="floating-label">
                                    <span class="label-text">Supplier</span>
                                    <select id="inline-supplier-dropdown" class="form-control select-control" disabled required>
                                        <option value="">Select Supplier</option>
                                    </select>
                                </label>
                            </div>

                            <div id="inline-scheduling" class="form-section dual-grid hidden">
                                <label class="floating-label">
                                    <span class="label-text">Order Date</span>
                                    <input type="date" id="inline-order-date" class="form-control" />
                                </label>
                                <label class="floating-label">
                                    <span class="label-text">Expected Date</span>
                                    <input type="date" id="inline-expected-date" class="form-control" />
                                </label>
                            </div>

                            <div class="form-section">
                                <p class="mini-heading">Line Items</p>
                                <div class="inline-items-placeholder" id="inline-items-placeholder">
                                    No items yet. Launch the builder to add catalog products.
                                </div>
                            </div>

                            <div class="form-actions">
                                <button type="button" class="secondary-btn outline-btn" id="inline-reset-btn">Reset</button>
                                <button type="button" class="primary-btn is-disabled" id="launch-order-modal" disabled>
                                    Open Adding Builder
                                </button>
                            </div>
                        </form>
                    </div>
                </aside>

            </section>
        </main>
    </div>

    <div id="order-modal" class="modal-overlay hidden">
        <div class="modal-content">
            <h2 style="margin-top: 0;">Create New Order</h2>
            <form id="order-form" action="{{ route('api.orders.store') }}">
                <div id="order-form-feedback" class="form-feedback" style="display:none;"></div>
                @csrf
                <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 10px; margin-bottom: 15px;">
                    <label style="display: flex; flex-direction: column; gap: 4px;">Order Type
                        <select id="order-type-select" name="order_type" required style="width: 100%; padding: 8px;">
                            <option value="Customer">Deducting (Customer)</option>
                            <option value="Supplier">Adding (Supplier)</option>
                        </select>
                        <span id="order-type-hint" style="font-size: 12px; color: #666;">Customer orders deduct stock supplied by the selected supplier.</span>
                    </label>
                    <label id="supplier-select-group" style="display: flex; flex-direction: column; gap: 4px;">Supplier
                        <select id="supplier-dropdown-order" name="supplier_id" required style="width: 100%; padding: 8px;">
                            <option value="">-- Select Supplier --</option>
                        </select>
                        <small style="font-size: 12px; color: #666;">Required for all orders; filters the items list to that supplier.</small>
                    </label>
                </div>

                <div id="order-scheduling-fields" style="display: none; gap: 10px; margin-bottom: 15px; grid-template-columns: repeat(2, 1fr);" class="supplier-only-grid">
                    <label style="display: flex; flex-direction: column; gap: 4px;">Order Date
                        <input type="date" id="order-date-input" name="order_date" style="width: 100%; padding: 8px;">
                    </label>
                    <label style="display: flex; flex-direction: column; gap: 4px;">Expected Date
                        <input type="date" id="expected-date-input" name="expected_date" style="width: 100%; padding: 8px;">
                    </label>
                </div>

                <div style="margin-bottom: 15px;">
                    <table id="order-items-table" style="width: 100%; border-collapse: collapse;">
                        <thead>
                            <tr>
                                <th style="padding: 8px; text-align: left;">Item</th>
                                <th style="padding: 8px; text-align: left;">Quantity</th>
                                <th style="padding: 8px; text-align: left;">Unit</th>
                                <th style="padding: 8px; text-align: left;">Unit Price</th>
                                <th style="padding: 8px; text-align: left;">Expiry Date</th>
                                <th style="padding: 8px; text-align: left;">Actions</th>
                            </tr>
                        </thead>
                        <tbody id="order-item-rows"></tbody>
                    </table>
                    <button type="button" id="add-item-row-btn" class="secondary-btn ghost-btn">+ Add Item</button>
                </div>

                <div class="modal-footer-actions">
                    <button type="button" class="secondary-btn outline-btn" onclick="document.getElementById('order-modal').classList.add('hidden');">Cancel</button>
                    <button type="submit" class="primary-btn">Save Order</button>
                </div>
            </form>
        </div>
    </div>

    <div id="order-details-modal" class="modal-overlay hidden">
        <div class="modal-content">
            <h2 style="margin-top: 0;">Order Details: #<span id="details-order-id"></span></h2>

            <div class="order-details-grid">
                <div class="details-field">
                    <span class="details-label">Order Type</span>
                    <span id="details-order-type"></span>
                </div>
                <div class="details-field">
                    <span class="details-label">Status</span>
                    <select id="details-status-select" class="form-control select-control">
                        <option value="Pending">Pending</option>
                        <option value="Confirmed">Confirmed</option>
                        <option value="Cancelled">Cancelled</option>
                    </select>
                </div>
                <div class="details-field">
                    <span class="details-label">Order Date</span>
                    <input type="date" id="details-order-date" class="form-control">
                </div>
                <div class="details-field">
                    <span class="details-label">Expected Date</span>
                    <input type="date" id="details-expected-date" class="form-control">
                </div>
                <div class="details-field">
                    <span class="details-label">Created By</span>
                    <span id="details-created-by"></span>
                </div>
                <div id="details-supplier-info" class="details-field full-span">
                    <span class="details-label">Supplier</span>
                    <span id="details-supplier-name"></span>
                </div>
            </div>

            <h3 style="border-bottom: 1px solid #eee; padding-bottom: 5px; margin-top: 20px;">Items in this Order</h3>
            <table style="width: 100%; border-collapse: collapse;">
                <thead>
                    <tr>
                        <th style="padding: 5px; text-align: left;">Item Name</th>
                        <th style="padding: 5px; text-align: right;">Quantity</th>
                        <th style="padding: 5px; text-align: right;">Unit Price</th>
                        <th style="padding: 5px; text-align: right;">Subtotal</th>
                    </tr>
                </thead>
                <tbody id="details-item-list"></tbody>
            </table>
            <p style="text-align: right; font-weight: bold; font-size: 1.2em; margin-top: 15px;">
                Total: <span id="details-order-total"></span>
            </p>

            <div id="details-feedback" style="margin-bottom: 10px; color: #a03c3c; display: none;"></div>

            <div class="details-actions">
                <button type="button" id="details-delete-btn" class="danger-btn">Delete</button>
                <div class="details-actions-group">
                    <button type="button" id="details-save-btn" class="primary-btn">Save Changes</button>
                    <button type="button" class="secondary-btn" onclick="document.getElementById('order-details-modal').classList.add('hidden');">Close</button>
                </div>
            </div>
        </div>
    </div>

    <script src="{{ asset('js/orders.js') }}?v=5" defer></script>
</body>
</html>

