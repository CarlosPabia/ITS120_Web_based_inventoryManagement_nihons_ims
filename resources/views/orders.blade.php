<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Orders Management - Nihon Café</title>
    
    <link rel="stylesheet" href="{{ asset('main.css') }}">
    <link rel="stylesheet" href="{{ asset('orders.css') }}"> 
    
    <style>
        /* Minimal Layout Styles (Copy/Paste this block into your file) */
        body { font-family: sans-serif; margin: 0; background-color: #f4f4f4; }
        .dashboard-layout { display: grid; grid-template-columns: 200px 1fr; min-height: 100vh; }
        .sidebar { background-color: #333; color: white; padding: 20px 0; position: fixed; height: 100%; width: 200px; }
        .sidebar-nav-link { display: block; padding: 10px 20px; text-decoration: none; color: white; }
        .sidebar-nav-link:hover, .sidebar-nav-link.active { background-color: #a03c3c; }
        .top-navbar { grid-column: 2 / 3; background: white; padding: 10px 20px; box-shadow: 0 1px 3px rgba(0,0,0,0.1); display: flex; justify-content: flex-end; align-items: center; height: 40px; }
        .main-content { grid-column: 2 / 3; padding: 20px; margin-top: 60px; }
        .panel-title { color: #a03c3c; margin-top: 0; border-bottom: 1px solid #eee; padding-bottom: 10px; margin-bottom: 20px; }
        .panel { background: white; padding: 20px; border-radius: 6px; box-shadow: 0 2px 4px rgba(0,0,0,0.05); }
        .modal-overlay { display: flex; justify-content: center; align-items: center; position: fixed; top: 0; left: 0; width: 100%; height: 100%; background: rgba(0,0,0,0.7); z-index: 100; }
    </style>
</head>
<body>
    <div class="dashboard-layout">
        
        <aside class="sidebar">
            <div class="sidebar-logo">
                <h2 style="margin: 0;">NIHON CAFE</h2>
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
            
            <form method="POST" action="{{ route('logout') }}" style="display:inline;">
                @csrf
                <button type="submit" class="logout-btn">Logout</button>
            </form>
        </header>

        <main class="main-content">
            <h1 class="panel-title">Orders Management</h1>
            
            <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 20px;">
                <button id="create-order-btn" style="background-color: green; color: white; padding: 10px 15px; border: none; border-radius: 4px;">+ Create New Order</button>
                <input type="text" id="order-search" placeholder="Search Order ID or Status" style="padding: 8px; width: 300px; border: 1px solid #ccc; border-radius: 4px;">
            </div>

            <div class="panel" style="background: white; padding: 15px; border-radius: 6px; box-shadow: 0 2px 4px rgba(0,0,0,0.05);">
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
            
            <div id="order-modal" class="modal-overlay" style="display: none;">
                <div class="modal-content">
                    <h2 style="margin-top: 0;">Create New Order</h2>
                    <form id="order-form" method="POST" action="/api/orders">
                        @csrf
                        <input type="hidden" name="order_status" value="Completed">
                        
                        <div style="margin-bottom: 15px;">
                            <label>Order Type:</label>
                            <select id="order-type-select" name="order_type" required style="padding: 8px;">
                                <option value="Customer">Customer Sale (Deduct Stock)</option>
                                <option value="Supplier">Supplier Purchase (Add Stock)</option>
                            </select>
                        </div>
                        
                        <div id="supplier-select-group" style="margin-bottom: 15px; display: none;">
                            <label>Supplier:</label>
                            <select id="supplier-dropdown-order" name="supplier_id" style="padding: 8px;">
                                </select>
                        </div>
                        
                        <h3 style="border-bottom: 1px solid #eee; padding-bottom: 5px;">Items</h3>
                        <table id="order-items-table" style="width: 100%; border-collapse: collapse; margin-bottom: 15px;">
                            <thead>
                                <tr>
                                    <th style="padding: 5px;">Item</th>
                                    <th style="padding: 5px;">Qty</th>
                                    <th style="padding: 5px;">Price/Unit</th>
                                    <th class="expiry-input" style="padding: 5px; display: none;">Expiry (If Purchase)</th>
                                    <th style="padding: 5px;"></th>
                                </tr>
                            </thead>
                            <tbody id="order-item-rows">
                                </tbody>
                        </table>
                        <button type="button" id="add-item-row-btn" style="padding: 8px; background: #eee; border: 1px solid #ccc;">+ Add Item Line</button>

                        <div style="display: flex; justify-content: flex-end; gap: 10px; margin-top: 20px;">
                            <button type="button" onclick="document.getElementById('order-modal').style.display='none';" style="padding: 10px;">Cancel</button>
                            <button type="submit" style="padding: 10px; background: #a03c3c; color: white; border: none;">Process Order</button>
                        </div>
                    </form>
                </div>
            </div>
        </main>
    </div>
    
    <script>
        document.addEventListener('DOMContentLoaded', function() {
            const API_ORDERS = '/api/orders';
            const API_INVENTORY = '/api/inventory';
            const API_SUPPLIERS = '/api/suppliers';

            const tableBody = document.getElementById('orders-table-body');
            const orderModal = document.getElementById('order-modal');
            const orderForm = document.getElementById('order-form');
            const orderTypeSelect = document.getElementById('order-type-select');
            const supplierGroup = document.getElementById('supplier-select-group');
            const supplierDropdownOrder = document.getElementById('supplier-dropdown-order');
            const itemRowsBody = document.getElementById('order-item-rows');
            
            const csrfToken = orderForm.querySelector('input[name="_token"]').value;

            let allInventory = [];
            let allSuppliers = [];
            let lineItemCounter = 0;

            // --- A. INITIAL DATA LOAD ---

            async function loadInitialData() {
                try {
                    const [ordersData, inventoryData, supplierData] = await Promise.all([
                        fetch(API_ORDERS).then(res => res.json()),
                        fetch(API_INVENTORY).then(res => res.json()),
                        fetch(API_SUPPLIERS).then(res => res.json())
                    ]);
                    
                    allInventory = inventoryData;
                    allSuppliers = supplierData;
                    
                    renderOrdersTable(ordersData);
                    renderSupplierDropdowns(); 
                    
                } catch (error) {
                    console.error('Initial data load error:', error);
                    // Display error if API calls fail
                    tableBody.innerHTML = '<tr><td colspan="6" style="text-align: center; color: red;">Failed to load order history. Check console for error details.</td></tr>';
                }
            }
            
            function renderSupplierDropdowns() {
                supplierDropdownOrder.innerHTML = '<option value="">-- Select Supplier --</option>';
                allSuppliers.forEach(supplier => {
                    const option = document.createElement('option');
                    option.value = supplier.id;
                    option.textContent = supplier.supplier_name;
                    supplierDropdownOrder.appendChild(option);
                });
            }

            function renderOrdersTable(orders) {
                tableBody.innerHTML = '';
                if (orders.length === 0) {
                     tableBody.innerHTML = '<tr><td colspan="6" style="text-align: center; padding: 15px;">No orders found.</td></tr>';
                     return;
                }

                orders.forEach(order => {
                    const row = document.createElement('tr');
                    const statusColor = order.order_status === 'Completed' ? 'green' : (order.order_status === 'Pending' ? 'orange' : 'red');

                    row.innerHTML = `
                        <td style="padding: 10px; border-bottom: 1px solid #eee;">${order.id}</td>
                        <td style="padding: 10px; border-bottom: 1px solid #eee;">${order.order_type}</td>
                        <td style="padding: 10px; border-bottom: 1px solid #eee;">${order.order_date.substring(0, 10)}</td>
                        <td style="padding: 10px; border-bottom: 1px solid #eee;"><span style="color: ${statusColor};">${order.order_status}</span></td>
                        <td style="padding: 10px; border-bottom: 1px solid #eee;">${order.user.first_name} ${order.user.last_name.charAt(0)}.</td>
                        <td style="padding: 10px; border-bottom: 1px solid #eee;"><button>View Details</button></td>
                    `;
                    tableBody.appendChild(row);
                });
            }
            
            // --- B. DYNAMIC FORM LOGIC ---
            
            function updateFormVisibility() {
                const isSupplierOrder = orderTypeSelect.value === 'Supplier';
                supplierGroup.style.display = isSupplierOrder ? 'block' : 'none';
                
                // Show/Hide Expiry column header and input fields
                document.querySelectorAll('#order-items-table .expiry-input').forEach(el => {
                    el.style.display = isSupplierOrder ? 'table-cell' : 'none';
                });
            }
            
            function addItemRow() {
                lineItemCounter++;
                const row = document.createElement('tr');
                row.id = `line-item-${lineItemCounter}`;
                
                // Build item dropdown dynamically
                const itemOptions = allInventory.map(item => 
                    `<option value="${item.id}" data-unit="${item.unit}">
                        ${item.name} (${item.unit}) - Stock: ${item.quantity.toFixed(1)}
                    </option>`
                ).join('');

                row.innerHTML = `
                    <td>
                        <select name="item_id[]" required style="width: 100%; padding: 5px;">
                            <option value="">Select Item</option>
                            ${itemOptions}
                        </select>
                    </td>
                    <td><input type="number" name="quantity[]" required min="0.01" step="any" style="width: 60px; padding: 5px;"></td>
                    <td><input type="number" name="unit_price[]" required min="0" step="any" style="width: 80px; padding: 5px;"></td>
                    <td class="expiry-input" style="display: none;"><input type="date" name="expiry_date[]" style="width: 100px; padding: 5px;"></td>
                    <td><button type="button" onclick="document.getElementById('order-item-rows').removeChild(document.getElementById('line-item-${lineItemCounter}')); updateFormVisibility();" style="color: red; border: none; background: none;">X</button></td>
                `;
                
                itemRowsBody.appendChild(row);
                updateFormVisibility(); // Check visibility for new row
            }
            
            // --- C. SUBMISSION HANDLER ---

            orderForm.addEventListener('submit', async function(event) {
                event.preventDefault();

                // 1. Collect line item data
                const itemIds = Array.from(this.querySelectorAll('[name="item_id[]"]')).map(el => el.value);
                const quantities = Array.from(this.querySelectorAll('[name="quantity[]"]')).map(el => el.value);
                const prices = Array.from(this.querySelectorAll('[name="unit_price[]"]')).map(el => el.value);
                const expiryDates = Array.from(this.querySelectorAll('[name="expiry_date[]"]')).map(el => el.value);

                const items = itemIds.map((id, index) => ({
                    item_id: id,
                    quantity: parseFloat(quantities[index]),
                    unit_price: parseFloat(prices[index]),
                    expiry_date: orderTypeSelect.value === 'Supplier' ? expiryDates[index] : null,
                }));
                
                const payload = {
                    _token: csrfToken,
                    order_type: orderTypeSelect.value,
                    supplier_id: orderTypeSelect.value === 'Supplier' ? supplierDropdownOrder.value : null,
                    order_status: 'Completed', 
                    items: items,
                };
                
                // 2. Send to API
                try {
                    const response = await fetch(orderForm.action, {
                        method: 'POST',
                        headers: {
                            'Content-Type': 'application/json',
                            'X-CSRF-TOKEN': csrfToken, 
                        },
                        body: JSON.stringify(payload),
                    });
                    
                    const result = await response.json();

                    if (response.ok) {
                        alert(result.message);
                        orderModal.style.display = 'none';
                        orderForm.reset();
                        loadInitialData(); // Refresh history table
                    } else {
                        const errorMsg = result.errors ? Object.values(result.errors).flat().join('\n') : (result.error || result.message || 'Failed to process order.');
                        alert('Error: ' + errorMsg);
                    }
                } catch (error) {
                    console.error('Order submission error:', error);
                    alert('An unexpected network error occurred.');
                }
            });

            // --- D. INITIALIZATION AND EVENT LISTENERS ---
            
            document.getElementById('create-order-btn').addEventListener('click', () => {
                itemRowsBody.innerHTML = ''; 
                orderForm.reset();
                addItemRow();
                orderModal.style.display = 'block';
            });
            
            document.getElementById('add-item-row-btn').addEventListener('click', addItemRow);
            
            orderTypeSelect.addEventListener('change', updateFormVisibility);

            loadInitialData();
        });
    </script>
</body>
</html>