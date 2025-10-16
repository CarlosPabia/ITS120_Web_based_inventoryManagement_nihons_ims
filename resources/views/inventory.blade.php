<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Inventory Management - Nihon Café</title>
    
    <link rel="stylesheet" href="{{ asset('main.css') }}">
    <link rel="stylesheet" href="{{ asset('inventory.css') }}">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/5.15.4/css/all.min.css">
    
    <style>
        /* Minimal Layout Styles */
        body { font-family: sans-serif; margin: 0; background-color: #f4f4f4; }
        .dashboard-layout { display: grid; grid-template-columns: 200px 1fr; min-height: 100vh; }
        .sidebar { background-color: #333; color: white; padding: 20px 0; position: fixed; height: 100%; width: 200px; }
        .sidebar-nav-link { display: block; padding: 10px 20px; text-decoration: none; color: white; }
        .sidebar-nav-link:hover, .sidebar-nav-link.active { background-color: #a03c3c; }
        .top-navbar { grid-column: 2 / 3; background: white; padding: 10px 20px; box-shadow: 0 1px 3px rgba(0,0,0,0.1); display: flex; justify-content: flex-end; align-items: center; height: 40px; }
        .main-content { grid-column: 2 / 3; padding: 20px; margin-top: 60px; }
        .panel-title { color: #a03c3c; margin-top: 0; border-bottom: 1px solid #eee; padding-bottom: 10px; margin-bottom: 20px; }
        .panel { background: white; padding: 20px; border-radius: 6px; box-shadow: 0 2px 4px rgba(0,0,0,0.05); }

        /* Stock Status Styles */
        .status-tag { padding: 4px 8px; border-radius: 4px; font-weight: bold; font-size: 12px; }
        .status-Normal { background-color: #d4edda; color: #155724; }
        .status-Low { background-color: #fff3cd; color: #856404; }
        .status-Critical { background-color: #f8d7da; color: #721c24; }
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
            
            <form method="POST" action="{{ route('logout') }}" style="display:inline;">
                @csrf
                <button type="submit" class="logout-btn">Logout</button>
            </form>
        </header>

        <main class="main-content">
            
            <div style="display: flex; justify-content: space-between; align-items: center; border-bottom: 1px solid #eee; padding-bottom: 10px;">
                <h1 class="panel-title" style="border: none; margin: 0;">Inventory</h1>
                <button id="add-item-button" style="background-color: green; color: white; padding: 10px 15px; border: none; border-radius: 4px;">+ Add New Item</button>
            </div>

            <div style="margin-bottom: 20px; padding: 10px; background: #fff; border-radius: 6px; margin-top: 20px;">
                <input type="text" id="search-input" placeholder="Search SKU, Item, or Supplier" style="padding: 8px; width: 300px; border: 1px solid #ccc; border-radius: 4px;">
            </div>
            
            <div class="panel" style="padding: 0; overflow-x: auto;">
                <table style="width: 100%; border-collapse: collapse;">
                    <thead>
                        <tr style="background-color: #f0f0f0;">
                            <th style="padding: 10px; text-align: left;">ID</th>
                            <th style="padding: 10px; text-align: left;">Item Name</th>
                            <th style="padding: 10px; text-align: left;">Quantity in Stock</th>
                            <th style="padding: 10px; text-align: left;">Unit</th>
                            <th style="padding: 10px; text-align: left;">Supplier</th>
                            <th style="padding: 10px; text-align: left;">Status</th>
                            <th style="padding: 10px; text-align: left;">Actions</th>
                        </tr>
                    </thead>
                    <tbody id="inventory-table-body">
                        <tr><td colspan="7" style="text-align: center; padding: 20px;">Loading Inventory Data...</td></tr>
                    </tbody>
                </table>
            </div>
            
            <div id="stock-drawer" style="position: fixed; right: 0; top: 0; width: 350px; height: 100%; background: #fff; box-shadow: -2px 0 5px rgba(0,0,0,0.3); padding: 20px; box-sizing: border-box; z-index: 10; display: none; margin-top: 60px;">
                <h3 id="drawer-title" style="margin-top: 0; border-bottom: 1px solid #eee; padding-bottom: 10px;">Add New Item</h3>
                
                <form id="stock-form" style="display: grid; gap: 10px;">
                    <input type="hidden" name="_token" value="{{ csrf_token() }}"> 
                    <input type="hidden" name="id" id="form-item-id"> 

                    <label>Item Name</label>
                    <input type="text" name="item_name" id="form-item-name" style="padding: 8px;" required>
                    
                    <label>Unit (e.g., kg, bottle, pc)</label>
                    <input type="text" name="unit_of_measure" id="form-unit" style="padding: 8px;" required>

                    <label>Preferred Supplier</label>
                    <select name="supplier_id" id="supplier-dropdown" style="padding: 8px;">
                        <option value="">-- Select Supplier --</option>
                    </select>
                    
                    <h4 style="margin: 15px 0 5px;">Stock Adjustment / Initial Stock</h4>
                    
                    <label>Quantity Change (+/-)</label>
                    <input type="number" name="quantity_adjustment" placeholder="+5 to add, -2 to deduct" style="padding: 8px;" step="any">
                    
                    <label>Expiry Date (if adding stock)</label>
                    <input type="date" name="expiry_date" style="padding: 8px;">
                    
                    <div style="display: flex; justify-content: flex-end; gap: 10px; margin-top: 20px;">
                        <button type="button" onclick="document.getElementById('stock-drawer').style.display='none';" style="padding: 10px;">Cancel</button>
                        <button type="submit" id="save-button" style="padding: 10px; background: #a03c3c; color: white; border: none;">Save Changes</button>
                    </div>
                </form>
            </div>

        </main>
    </div>
    
    <script>
        document.addEventListener('DOMContentLoaded', function() {
            // FIX: Use the clean, non-API-prefixed routes
            const API_INVENTORY = '/inventory-data'; 
            const API_SUPPLIERS = '/suppliers-data'; 
            
            const tableBody = document.getElementById('inventory-table-body');
            const stockDrawer = document.getElementById('stock-drawer');
            const stockForm = document.getElementById('stock-form');
            const supplierDropdown = document.getElementById('supplier-dropdown');
            const drawerTitle = document.getElementById('drawer-title');

            const csrfToken = document.querySelector('input[name="_token"]').value; 
            
            let allSuppliers = [];
            let allInventoryItems = [];

            // --- A. HELPER FUNCTIONS ---

            function getStatusClass(status) {
                if (status === 'Low') return 'status-Low';
                if (status === 'Critical') return 'status-Critical';
                return 'status-Normal';
            }

            // --- B. LOAD INITIAL DATA ---

            async function loadInitialData() {
                try {
                    const [inventoryResponse, supplierResponse] = await Promise.all([
                        fetch(API_INVENTORY),
                        fetch(API_SUPPLIERS)
                    ]);

                    if (!inventoryResponse.ok || !supplierResponse.ok) {
                         // This catches 404 (Route Not Found) or 500 (PHP Crash)
                         throw new Error('API request failed or route not found.');
                    }
                    
                    const inventoryData = await inventoryResponse.json();
                    const supplierData = await supplierResponse.json();

                    allSuppliers = supplierData;
                    allInventoryItems = inventoryData; 
                    
                    renderSupplierDropdown();
                    renderInventoryTable(inventoryData);
                    
                } catch (error) {
                    console.error('Failed to load initial data:', error);
                    tableBody.innerHTML = '<tr><td colspan="7" style="text-align: center; color: red; padding: 20px;">Could not load data. Check console for details.</td></tr>';
                }
            }

            // --- C. RENDER FUNCTIONS ---

            function renderSupplierDropdown() {
                supplierDropdown.innerHTML = '<option value="">-- Select Supplier --</option>';
                allSuppliers.forEach(supplier => {
                    const option = document.createElement('option');
                    option.value = supplier.id;
                    option.textContent = supplier.supplier_name;
                    supplierDropdown.appendChild(option);
                });
            }

            function renderInventoryTable(items) {
                tableBody.innerHTML = ''; 
                
                if (items.length === 0) {
                     tableBody.innerHTML = '<tr><td colspan="7" style="text-align: center; padding: 15px;">No inventory records found.</td></tr>';
                     return;
                }

                items.forEach(item => {
                    const row = document.createElement('tr');
                    const statusClass = getStatusClass(item.status);

                    row.innerHTML = `
                        <td style="padding: 10px; border-bottom: 1px solid #eee;">${item.id}</td>
                        <td style="padding: 10px; border-bottom: 1px solid #eee;">${item.name}</td>
                        
                        <td style="padding: 10px; border-bottom: 1px solid #eee;">${item.quantity.toFixed(2)}</td>
                        
                        <td style="padding: 10px; border-bottom: 1px solid #eee;">${item.unit}</td> 
                        
                        <td style="padding: 10px; border-bottom: 1px solid #eee;">${item.supplier_name}</td>
                        <td style="padding: 10px; border-bottom: 1px solid #eee;"><span class="status-tag ${statusClass}">${item.status}</span></td>
                        
                        <td style="padding: 10px; border-bottom: 1px solid #eee;">
                            <button type="button" class="edit-btn" data-item-id="${item.id}" style="margin-right: 5px;">Edit</button>
                            <button type="button" class="delete-btn" data-item-id="${item.id}" style="color: red;">Delete</button>
                        </td>
                    `;
                    tableBody.appendChild(row);
                });
                
                addActionButtonListeners();
            }
            
            // --- D. FORM SUBMISSION LOGIC (Fixes Create/Update) ---
            
            stockForm.addEventListener('submit', async function(event) {
                event.preventDefault();
                
                const formData = new FormData(stockForm);
                const itemId = formData.get('id');
                
                const data = {
                    id: itemId || null,
                    item_name: formData.get('item_name'),
                    unit_of_measure: formData.get('unit_of_measure'), 
                    supplier_id: formData.get('supplier_id') || null,
                    quantity_adjustment: parseFloat(formData.get('quantity_adjustment')) || 0,
                    expiry_date: formData.get('expiry_date'),
                };
                
                if (!data.item_name || !data.unit_of_measure) {
                    alert('Error: Item Name and Unit are required.');
                    return;
                }

                try {
                    const response = await fetch(API_INVENTORY, {
                        method: 'POST', 
                        headers: {
                            'Content-Type': 'application/json',
                            'X-CSRF-TOKEN': csrfToken, 
                            'Accept': 'application/json',
                        },
                        body: JSON.stringify(data),
                    });
                    
                    const result = await response.json();

                    if (response.ok) {
                        alert(result.message);
                        stockDrawer.style.display = 'none'; 
                        stockForm.reset();
                        loadInitialData();
                    } else {
                        const errorMsg = result.errors ? Object.values(result.errors).flat().join('\n') : (result.error || result.message || 'Failed to save changes.');
                        alert('Error: ' + errorMsg);
                    }
                } catch (error) {
                    console.error('Submission error:', error);
                    alert('An unexpected network error occurred.');
                }
            });
            
            // --- E. ACTION LISTENERS (Fixes Edit/Delete Button Hooks) ---
            
            function addActionButtonListeners() {
    // 1. EDIT BUTTONS
    document.querySelectorAll('.edit-btn').forEach(button => {
        button.addEventListener('click', function() {
            const itemId = this.dataset.itemId;
            const item = allInventoryItems.find(i => i.id == itemId); 

            if (item) {
                document.getElementById('form-item-id').value = item.id;
                drawerTitle.textContent = `Edit Item: ${item.name}`;
                
                // Populate the form fields
                stockForm.querySelector('[name="item_name"]').value = item.name;
                stockForm.querySelector('[name="unit_of_measure"]').value = item.unit;
                stockForm.querySelector('[name="supplier_id"]').value = item.supplier_id || '';
                
                // FIX: Lock Name/Unit fields for editing existing items
                stockForm.querySelector('[name="item_name"]').readOnly = true; 
                stockForm.querySelector('[name="unit_of_measure"]').readOnly = true;

                // Clear adjustment fields for new input
                stockForm.querySelector('[name="quantity_adjustment"]').value = '';
                stockForm.querySelector('[name="expiry_date"]').value = '';
                
                stockDrawer.style.display = 'block'; 
            }
        });
    });
                
                // 2. DELETE BUTTONS
                document.querySelectorAll('.delete-btn').forEach(button => {
                    button.addEventListener('click', function() {
                        const itemId = this.dataset.itemId;
                        if (confirm('Are you sure you want to delete this item? Stock must be zero.')) {
                            deleteItem(itemId);
                        }
                    });
                });
            }
            
            async function deleteItem(itemId) {
                try {
                    const response = await fetch(`${API_INVENTORY}/${itemId}`, {
                        method: 'DELETE',
                        headers: {
                            'Content-Type': 'application/json',
                            'X-CSRF-TOKEN': csrfToken, 
                        },
                    });
                    
                    const result = await response.json();
                    
                    if (response.ok) {
                        alert(result.message);
                        loadInitialData(); 
                    } else {
                        alert('Error: ' + (result.error || 'Failed to delete item.'));
                    }
                } catch (error) {
                    console.error('Delete error:', error);
                    alert('An unexpected error occurred during deletion.');
                }
            }
            
                    document.getElementById('add-item-button').addEventListener('click', () => {
                // ... (rest of the reset logic) ...
                
                // FIX: Ensure Name/Unit fields are enabled for *new* creation
                stockForm.querySelector('[name="item_name"]').readOnly = false;
                stockForm.querySelector('[name="unit_of_measure"]').readOnly = false;
                stockDrawer.style.display = 'block'; 
            });
            
            loadInitialData();
        });
    </script>
</body>
</html>