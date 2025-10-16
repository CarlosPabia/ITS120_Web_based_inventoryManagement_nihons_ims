<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Orders Management - Nihon Café</title>
    
    <link rel="stylesheet" href="{{ asset('main.css') }}">
    <link rel="stylesheet" href="{{ asset('orders.css') }}"> 
    
    <style>
        /* Minimal Layout Styles for Integration */
        body { font-family: sans-serif; margin: 0; background-color: #f4f4f4; }
        .dashboard-layout { display: grid; grid-template-columns: 200px 1fr; min-height: 100vh; }
        .sidebar { background-color: #333; color: white; padding: 20px 0; position: fixed; height: 100%; width: 200px; }
        .sidebar-nav-link { display: block; padding: 10px 20px; text-decoration: none; color: white; }
        .sidebar-nav-link:hover, .sidebar-nav-link.active { background-color: #a03c3c; }
        .top-navbar { grid-column: 2 / 3; background: white; padding: 10px 20px; box-shadow: 0 1px 3px rgba(0,0,0,0.1); display: flex; justify-content: flex-end; align-items: center; height: 40px; position: fixed; top: 0; left: 200px; right: 0; z-index: 99; }
        .main-content { grid-column: 2 / 3; padding: 20px; margin-top: 60px; }
        .panel-title { color: #a03c3c; margin-top: 0; border-bottom: 1px solid #eee; padding-bottom: 10px; margin-bottom: 20px; }
        .panel { background: white; padding: 20px; border-radius: 6px; box-shadow: 0 2px 4px rgba(0,0,0,0.05); }
        .modal-overlay { display: none; justify-content: center; align-items: center; position: fixed; top: 0; left: 0; width: 100%; height: 100%; background: rgba(0,0,0,0.7); z-index: 100; }
        .modal-content { background: white; margin: 5% auto; padding: 30px; border-radius: 8px; width: 600px; max-width: 90%; }
        .order-details-grid { display: grid; grid-template-columns: 1fr 1fr; gap: 10px; margin-bottom: 15px;}
    </style>
</head>
<body>
    <div class="dashboard-layout">
        
        <aside class="sidebar">
            <div class="sidebar-logo">
                <h2 style="margin: 0; text-align: center;">NIHON CAFE</h2>
            </div>
            <ul style="list-style: none; padding: 0;">
                <li><a href="{{ route('dashboard') }}" class="sidebar-nav-link">Dashboard</a></li>
                <li><a href="{{ route('orders.index') }}" class="sidebar-nav-link active">Orders</a></li>
                <li><a href="{{ route('inventory.index') }}" class="sidebar-nav-link">Inventory</a></li>
                <li><a href="{{ route('suppliers.index') }}" class="sidebar-nav-link">Suppliers</a></li>
                <li><a href="{{ route('reports.index') }}" class="sidebar-nav-link">Reports</a></li>
                <li><a href="{{ route('settings.index') }}" class="sidebar-nav-link">Settings</a></li>
            </ul>
        </aside>
        
        <header class="top-navbar">
            <div class="user-info">
                @auth
                    {{ Auth::user()->first_name }} {{ Auth::user()->last_name }} ({{ Auth::user()->role->role_name ?? 'N/A' }})
                @endauth
            </div>
            <form method="POST" action="{{ route('logout') }}" style="display:inline; margin-left: 15px;">
                @csrf
                <button type="submit" class="logout-btn">Logout</button>
            </form>
        </header>

        <main class="main-content">
            <h1 class="panel-title">Orders Management</h1>
            
            <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 20px;">
                <button id="create-order-btn" style="background-color: green; color: white; padding: 10px 15px; border: none; border-radius: 4px; cursor: pointer;">+ Create New Order</button>
                <input type="text" id="order-search" placeholder="Search Order ID or Status" style="padding: 8px; width: 300px; border: 1px solid #ccc; border-radius: 4px;">
            </div>

            <div class="panel">
                <h2 style="margin-top: 0; font-size: 18px;">Order History</h2>
                <table style="width: 100%; border-collapse: collapse; text-align: left;">
                    <thead>
                        <tr style="background-color: #f0f0f0;">
                            <th style="padding: 10px;">ID</th>
                            <th style="padding: 10px;">Type</th>
                            <th style="padding: 10px;">Date</th>
                            <th style="padding: 10px;">Status</th>
                            <th style="padding: 10px;">Created By</th>
                            <th style="padding: 10px;">Actions</th>
                        </tr>
                    </thead>
                    <tbody id="orders-table-body">
                        <tr><td colspan="6" style="text-align: center; padding: 20px;">Loading Order History...</td></tr>
                    </tbody>
                </table>
            </div>
            
            <div id="order-modal" class="modal-overlay">
                <div class="modal-content">
                    <h2 style="margin-top: 0;">Create New Order</h2>
                    
                    <form id="order-form" method="POST" action="{{ route('api.orders.store') }}">
                        @csrf
                        <input type="hidden" name="order_status" value="Completed"> 
                        
                        <div style="margin-bottom: 15px;">
                            <label>Order Type:</label>
                            <select id="order-type-select" name="order_type" required style="padding: 8px; width: 100%;">
                                <option value="Customer">Customer Sale (Deduct Stock)</option>
                                <option value="Supplier">Supplier Purchase (Add Stock)</option>
                            </select>
                        </div>
                        
                        <div id="supplier-select-group" style="margin-bottom: 15px; display: none;">
                            <label>Supplier:</label>
                            <select id="supplier-dropdown-order" name="supplier_id" style="padding: 8px; width: 100%;"></select>
                        </div>
                        
                        <h3 style="border-bottom: 1px solid #eee; padding-bottom: 5px;">Items</h3>
                        <table id="order-items-table" style="width: 100%; border-collapse: collapse; margin-bottom: 15px;">
                            <thead>
                                <tr>
                                    <th style="padding: 5px; text-align: left;">Item</th>
                                    <th style="padding: 5px; text-align: left;">Qty</th>
                                    <th style="padding: 5px; text-align: left;">Unit</th>
                                    <th style="padding: 5px; text-align: left;">Price/Unit</th>
                                    <th class="expiry-input" style="padding: 5px; text-align: left; display: none;">Expiry</th>
                                    <th style="padding: 5px;"></th>
                                </tr>
                            </thead>
                            <tbody id="order-item-rows"></tbody>
                        </table>
                        <button type="button" id="add-item-row-btn" style="padding: 8px; background: #eee; border: 1px solid #ccc; cursor: pointer;">+ Add Item Line</button>

                        <div style="display: flex; justify-content: flex-end; gap: 10px; margin-top: 20px;">
                            <button type="button" onclick="document.getElementById('order-modal').style.display='none';" style="padding: 10px; cursor: pointer;">Cancel</button>
                            <button type="submit" style="padding: 10px; background: #a03c3c; color: white; border: none; cursor: pointer;">Process Order</button>
                        </div>
                    </form>
                </div>
            </div>
        </main>

        <div id="order-details-modal" class="modal-overlay">
            <div class="modal-content">
                <h2 style="margin-top: 0;">Order Details: #<span id="details-order-id"></span></h2>
                
                <div class="order-details-grid">
                    <div><strong>Order Type:</strong> <span id="details-order-type"></span></div>
                    <div><strong>Status:</strong> <span id="details-order-status"></span></div>
                    <div><strong>Order Date:</strong> <span id="details-order-date"></span></div>
                    <div><strong>Created By:</strong> <span id="details-created-by"></span></div>
                    <div id="details-supplier-info" style="grid-column: 1 / -1;"><strong>Supplier:</strong> <span id="details-supplier-name"></span></div>
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
                    <tbody id="details-item-list">
                        </tbody>
                </table>
                <p style="text-align: right; font-weight: bold; font-size: 1.2em; margin-top: 15px;">
                    Total: <span id="details-order-total"></span>
                </p>

                <div style="display: flex; justify-content: flex-end; margin-top: 20px;">
                    <button type="button" onclick="document.getElementById('order-details-modal').style.display='none';" style="padding: 10px; cursor: pointer;">Close</button>
                </div>
            </div>
        </div>
        
    </div>
    
    <script src="{{ asset('js/orders.js') }}" defer></script>
</body>
</html>