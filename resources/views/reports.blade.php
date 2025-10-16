<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Reports & Analytics - Nihon Café</title>
    </head>
<body>
    <aside class="sidebar">
            <div class="sidebar-logo">
                <h2 style="margin: 0;">NIHON CAFE</h2>
            </div>
            <ul style="list-style: none; padding: 0;">
                <li><a href="dashboard.blade.php" class="sidebar-nav-link active">Dashboard</a></li>
                <li><a href="orders.blade.php" class="sidebar-nav-link">Orders</a></li>
                <li><a href="inventory.blade.php" class="sidebar-nav-link">Inventory</a></li>
                <li><a href="suppliers.blade.php" class="sidebar-nav-link">Suppliers</a></li>
                <li><a href="reports.blade.php" class="sidebar-nav-link">Reports</a></li>
                <li><a href="settings.html" class="sidebar-nav-link">Settings</a></li>
            </ul>
        </aside>
    <div style="padding: 20px;">
        <h1 style="color: #a03c3c;">Sales & Orders Reports (Manager Only)</h1>
        <p style="color: red; font-weight: bold;">[ACCESS IS PROTECTED BY RBAC MIDDLEWARE]</p>

        <div style="background: white; padding: 20px; border-radius: 6px; margin-bottom: 20px; box-shadow: 0 2px 4px rgba(0,0,0,0.05);">
            <h2 style="margin-top: 0; font-size: 18px;">Generate Report</h2>
            <form style="display: flex; gap: 15px; align-items: center;">
                <label for="period">Select Period:</label>
                <select id="period" style="padding: 8px;">
                    <option>Weekly Sales & Orders</option>
                    <option>Monthly Sales & Orders</option>
                    <option>Expiry Forecast</option>
                </select>

                <label for="date_range">Date Range:</label>
                <input type="date" style="padding: 8px;">
                <input type="date" style="padding: 8px;">

                <button type="submit" style="background-color: #a03c3c; color: white; padding: 8px 15px; border: none; border-radius: 4px;">Generate</button>
            </form>
        </div>

        <div style="background: white; padding: 20px; border-radius: 6px; box-shadow: 0 2px 4px rgba(0,0,0,0.05);">
            <h2 style="margin-top: 0; font-size: 18px;">Last Report Summary (October 2025)</h2>
            <p>Total Revenue: **₱42,300.00**</p>
            <p>Waste from Expired Stock (YTD): **₱0.00** (Goal: Reduce Financial Loss)</p>
            <p>Top Performing Item: Coffee Beans (Arabica)</p>
        </div>
    </div>
</body>
</html>