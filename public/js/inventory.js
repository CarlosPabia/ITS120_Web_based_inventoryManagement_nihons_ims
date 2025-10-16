document.addEventListener('DOMContentLoaded', function() {
    const tableBody = document.getElementById('inventory-table-body');
    const stockDrawer = document.getElementById('stock-drawer');
    const stockForm = document.getElementById('stock-form');
    const supplierDropdown = document.getElementById('supplier-dropdown');
    
    // The CSRF token is read directly from the hidden input field in the form
    const csrfToken = document.querySelector('input[name="_token"]').value; 
    
    let allSuppliers = []; // To store supplier data globally for the dropdown

    // --- A. HELPER FUNCTIONS ---

    function getStatusColor(status) {
        if (status === 'Low') return 'orange';
        if (status === 'Critical') return 'red';
        return 'green';
    }
    
    function formatDate(dateString) {
        if (!dateString) return 'N/A';
        // Converts YYYY-MM-DD to MM/DD/YYYY
        const date = new Date(dateString + 'T00:00:00'); 
        return date.toLocaleDateString('en-US');
    }

    // --- B. LOAD INITIAL DATA (Inventory and Suppliers) ---

    async function loadInitialData() {
        try {
            // 1. Fetch Inventory Data (Main Table)
            const inventoryPromise = fetch('/api/inventory').then(res => res.json());

            // 2. Fetch Supplier Data (For Dropdown)
            // Note: Suppliers API is protected by Auth, but not RBAC at the API level
            const supplierPromise = fetch('/api/suppliers').then(res => res.json());

            const [inventoryData, supplierData] = await Promise.all([inventoryPromise, supplierPromise]);
            
            allSuppliers = supplierData;
            
            renderSupplierDropdown();
            renderInventoryTable(inventoryData);
            
        } catch (error) {
            console.error('Failed to load initial data:', error);
            tableBody.innerHTML = '<tr><td colspan="7" style="text-align: center; color: red; padding: 20px;">Could not connect to the data API.</td></tr>';
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
        tableBody.innerHTML = ''; // Clear existing content
        
        if (items.length === 0) {
             tableBody.innerHTML = '<tr><td colspan="7" style="text-align: center; padding: 15px;">No inventory records found.</td></tr>';
             return;
        }

        items.forEach(item => {
            const row = document.createElement('tr');
            
            // Assume the item.batches array (from the backend model) is simplified to show one expiry date
            // Note: For simplicity, we just use 'N/A' for Expiry Date as the backend logic is complex.
            
            const statusColor = getStatusColor(item.status);

            row.innerHTML = `
                <td style="padding: 10px; border-bottom: 1px solid #eee;">${item.id}</td>
                <td style="padding: 10px; border-bottom: 1px solid #eee;">${item.name}</td>
                <td style="padding: 10px; border-bottom: 1px solid #eee;">${item.quantity.toFixed(2)} ${item.unit}</td>
                <td style="padding: 10px; border-bottom: 1px solid #eee;">N/A</td> 
                <td style="padding: 10px; border-bottom: 1px solid #eee;">${item.supplier_name}</td>
                <td style="padding: 10px; border-bottom: 1px solid #eee;"><span style="color: ${statusColor}; font-weight: bold;">${item.status}</span></td>
                <td style="padding: 10px; border-bottom: 1px solid #eee;">
                    <button type="button" class="edit-btn" data-item='${JSON.stringify(item)}' style="margin-right: 5px;">Edit</button>
                    <button type="button" class="delete-btn" data-item-id="${item.id}" style="color: red;">Delete</button>
                </td>
            `;
            tableBody.appendChild(row);
        });
        
        addActionButtonListeners(items);
    }
    
    // --- D. FORM SUBMISSION LOGIC (CREATE/UPDATE STOCK) ---
    
    stockForm.addEventListener('submit', async function(event) {
        event.preventDefault();
        
        const formData = new FormData(stockForm);
        const itemId = formData.get('id'); // Get ID from hidden field
        
        const data = {
            id: itemId || null,
            item_name: formData.get('item_name'),
            unit_of_measure: formData.get('unit_of_measure'),
            supplier_id: formData.get('supplier_id') || null,
            quantity_adjustment: parseFloat(formData.get('quantity_adjustment')) || 0,
            expiry_date: formData.get('expiry_date'),
        };

        try {
            const response = await fetch('/api/inventory', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'X-CSRF-TOKEN': csrfToken, // Pass CSRF token for security
                    'Accept': 'application/json',
                },
                body: JSON.stringify(data),
            });
            
            const result = await response.json();

            if (response.ok) {
                alert(result.message);
                stockDrawer.style.display = 'none'; // Close drawer
                stockForm.reset();
                loadInitialData(); // Refresh data
            } else {
                // Display validation errors from Laravel
                const errorMsg = result.errors ? Object.values(result.errors).flat().join('\n') : (result.error || result.message || 'Failed to save changes.');
                alert('Error: ' + errorMsg);
            }
        } catch (error) {
            console.error('Submission error:', error);
            alert('An unexpected network error occurred.');
        }
    });
    
    // --- E. ACTION LISTENERS ---
    
    function addActionButtonListeners(items) {
        // 1. EDIT BUTTONS
        document.querySelectorAll('.edit-btn').forEach(button => {
            button.addEventListener('click', function() {
                const itemData = JSON.parse(this.dataset.item);
                
                // Populate the form fields with current data
                document.getElementById('form-item-id').value = itemData.id;
                stockForm.querySelector('[name="item_name"]').value = itemData.name;
                stockForm.querySelector('[name="unit_of_measure"]').value = itemData.unit;
                stockForm.querySelector('[name="supplier_id"]').value = itemData.supplier_id || '';
                
                // Clear adjustment fields for new input
                stockForm.querySelector('[name="quantity_adjustment"]').value = '';
                stockForm.querySelector('[name="expiry_date"]').value = '';
                
                stockDrawer.style.display = 'block'; // Open drawer
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
            const response = await fetch(`/api/inventory/${itemId}`, {
                method: 'DELETE',
                headers: {
                    'Content-Type': 'application/json',
                    'X-CSRF-TOKEN': csrfToken, 
                },
            });
            
            const result = await response.json();
            
            if (response.ok) {
                alert(result.message);
                loadInitialData(); // Refresh the table
            } else {
                alert('Error: ' + (result.error || 'Failed to delete item.'));
            }
        } catch (error) {
            console.error('Delete error:', error);
            alert('An unexpected error occurred during deletion.');
        }
    }
    
    // --- INITIALIZATION ---
    
    // Event listener for the static "+ Add New Item" button (opens the drawer)
    document.getElementById('add-item-button').addEventListener('click', () => {
        // Clear item ID to ensure form is in CREATE mode
        document.getElementById('form-item-id').value = ''; 
        stockForm.reset();
        stockDrawer.style.display = 'block'; 
    });
    
    loadInitialData();
});