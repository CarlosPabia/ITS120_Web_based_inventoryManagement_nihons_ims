<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Suppliers Directory - Nihon Café</title>
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
        <h1 style="color: #a03c3c;">Suppliers Directory (Manager Only)</h1>
        <p style="color: red; font-weight: bold;">[ACCESS IS PROTECTED BY RBAC MIDDLEWARE]</p>

        <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 20px; border-bottom: 1px solid #eee; padding-bottom: 10px;">
            <input type="text" placeholder="Search Supplier Name or Contact" style="padding: 8px; width: 300px; border: 1px solid #ccc; border-radius: 4px;">
            <button style="background-color: blue; color: white; padding: 10px 15px; border: none; border-radius: 4px;">+ Add New Supplier</button>
        </div>

        <div style="display: grid; grid-template-columns: repeat(3, 1fr); gap: 20px;">
            <div style="background: white; padding: 15px; border-radius: 6px; box-shadow: 0 2px 4px rgba(0,0,0,0.05);">
                <h3>Japanese Coffee Wholesaler</h3>
                <p>Contact: Mr. Sato</p>
                <p>Email: sato@jcw.com</p>
                <p>Status: Active</p>
                <button>Edit Contact Info</button>
            </div>
            <div style="background: white; padding: 15px; border-radius: 6px; box-shadow: 0 2px 4px rgba(0,0,0,0.05);">
                <h3>Dairy Fresh PH</h3>
                <p>Contact: Ms. Liza Reyes</p>
                <p>Email: contact@dairyfresh.ph</p>
                <p>Status: Active</p>
                <button>Edit Contact Info</button>
            </div>
        </div>
    </div>
</body>
</html>