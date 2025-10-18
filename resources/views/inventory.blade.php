<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>Inventory - Nihon Cafe</title>

    <link rel="stylesheet" href="{{ asset('main.css') }}">
    <link rel="stylesheet" href="{{ asset('inventory.css') }}">
    <style>
        body { font-family: sans-serif; margin: 0; background-color: #f4f4f4; }
        .dashboard-layout { display: grid; grid-template-columns: 200px 1fr; min-height: 100vh; }
        .sidebar { background-color: #333; color: white; padding: 20px 0; position: fixed; height: 100%; width: 200px; }
        .sidebar-nav-link { display: block; padding: 10px 20px; text-decoration: none; color: white; }
        .sidebar-nav-link:hover, .sidebar-nav-link.active { background-color: #a03c3c; }
        .top-navbar { grid-column: 2 / 3; background: white; padding: 10px 20px; box-shadow: 0 1px 3px rgba(0,0,0,0.1); display: flex; justify-content: flex-end; align-items: center; height: 40px; position: fixed; top: 0; left: 200px; right: 0; z-index: 99; }
        .main-content { grid-column: 2 / 3; padding: 20px; margin-top: 60px; }
        .panel { background: white; padding: 20px; border-radius: 6px; box-shadow: 0 2px 4px rgba(0,0,0,0.05); }
        .panel-title { color: #a03c3c; margin-top: 0; }
        .status-tag { padding: 4px 8px; border-radius: 4px; font-weight: bold; font-size: 12px; }
        .status-Normal { background-color: #d4edda; color: #155724; }
        .status-Low { background-color: #fff3cd; color: #856404; }
        .status-Critical { background-color: #f8d7da; color: #721c24; }
        .hint-box { background: #fff3cd; border: 1px solid #ffeeba; color: #856404; padding: 10px 15px; border-radius: 4px; margin-bottom: 20px; }
        .action-btn { padding: 6px 12px; border-radius: 4px; border: none; cursor: pointer; }
        .action-btn[disabled] { cursor: not-allowed; opacity: 0.5; }
        .danger-btn { background-color: #c0392b; color: #fff; }
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
                <li><a href="{{ route('orders.index') }}" class="sidebar-nav-link">Orders</a></li>
                <li><a href="{{ route('inventory.index') }}" class="sidebar-nav-link active">Inventory</a></li>
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

        <main class="main-content" data-role="{{ Auth::user()->role->role_name ?? '' }}">
            <h1 class="panel-title">Inventory</h1>

            <div class="hint-box">
                Items are created and replenished through order processing. You may retire an item once all stock is cleared and it is no longer tied to order history.
            </div>

            <div style="margin-bottom: 20px; display: flex; justify-content: space-between; align-items: center; gap: 20px;">
                <input type="text" id="search-input" placeholder="Search item or supplier" style="padding: 8px; width: 280px; border: 1px solid #ccc; border-radius: 4px;">
                <span id="inventory-summary" style="color: #555; font-size: 14px;"></span>
            </div>

            <div class="panel" style="padding: 0; overflow-x: auto;">
                <table style="width: 100%; border-collapse: collapse;">
                    <thead>
                        <tr style="background-color: #f0f0f0;">
                            <th style="padding: 10px; text-align: left;">ID</th>
                            <th style="padding: 10px; text-align: left;">Item Name</th>
                            <th style="padding: 10px; text-align: left;">Quantity</th>
                            <th style="padding: 10px; text-align: left;">Unit</th>
                            <th style="padding: 10px; text-align: left;">Supplier</th>
                            <th style="padding: 10px; text-align: left;">Status</th>
                            <th style="padding: 10px; text-align: left;">Actions</th>
                        </tr>
                    </thead>
                    <tbody id="inventory-table-body">
                        <tr><td colspan="7" style="padding: 20px; text-align: center;">Loading inventory...</td></tr>
                    </tbody>
                </table>
            </div>
        </main>
    </div>

    <script src="{{ asset('js/inventory.js') }}" defer></script>
</body>
</html>
