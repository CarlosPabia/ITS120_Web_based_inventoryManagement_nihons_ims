// public/js/orders.js

document.addEventListener('DOMContentLoaded', function() {
    // FIX: Updated API URLs to match the clean web routes
    const API_ORDERS_READ = '/orders-data';         
    const API_ORDERS_STORE = '/orders-data';        
    const API_INVENTORY_READ = '/inventory-data';   
    const API_SUPPLIERS_READ = '/suppliers-data';

    const tableBody = document.getElementById('orders-table-body');
    const orderModal = document.getElementById('order-modal');
    const orderForm = document.getElementById('order-form');
    const orderTypeSelect = document.getElementById('order-type-select');
    const supplierGroup = document.getElementById('supplier-select-group');
    const supplierDropdownOrder = document.getElementById('supplier-dropdown-order');
    const itemRowsBody = document.getElementById('order-item-rows');
    
    // Get the CSRF token from the form input
    const csrfToken = orderForm.querySelector('input[name="_token"]').value;

    let allInventory = [];
    let allSuppliers = [];
    let lineItemCounter = 0;
    
    const DEFAULT_PRICE = 1.50; // Placeholder price for auto-fill

    // --- A. INITIAL DATA LOAD (Omitted for brevity, assumed correct) ---
    async function loadInitialData() {
        try {
            const [ordersResponse, inventoryResponse, supplierResponse] = await Promise.all([
                fetch(API_ORDERS_READ).then(res => res.json()),
                fetch(API_INVENTORY_READ).then(res => res.json()),
                fetch(API_SUPPLIERS_READ).then(res => res.json())
            ]);
            
            const ordersData = ordersResponse;
            const inventoryData = inventoryResponse;
            const supplierData = supplierResponse;
            
            allInventory = inventoryData;
            allSuppliers = supplierData;
            
            renderOrdersTable(ordersData);
            renderSupplierDropdowns(); 
            
        } catch (error) {
            console.error('Initial data load error:', error);
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

    // --- B. RENDER ORDER HISTORY TABLE (Omitted for brevity) ---
    function renderOrdersTable(orders) {
        tableBody.innerHTML = '';
        if (orders.length === 0) {
             tableBody.innerHTML = '<tr><td colspan="6" style="text-align: center; padding: 15px;">No orders found.</td></tr>';
             return;
        }

        orders.forEach(order => {
            const row = document.createElement('tr');
            const statusColor = order.order_status === 'Completed' ? 'green' : (order.order_status === 'Pending' ? 'orange' : 'red');
            
            const createdBy = order.user 
                ? `${order.user.first_name} ${order.user.last_name.charAt(0)}.` 
                : 'System/Unknown';

            row.innerHTML = `
                <td style="padding: 10px; border-bottom: 1px solid #eee;">${order.id}</td>
                <td style="padding: 10px; border-bottom: 1px solid #eee;">${order.order_type}</td>
                <td style="padding: 10px; border-bottom: 1px solid #eee;">${order.order_date.substring(0, 10)}</td>
                <td style="padding: 10px; border-bottom: 1px solid #eee;"><span style="color: ${statusColor};">${order.order_status}</span></td>
                <td style="padding: 10px; border-bottom: 1px solid #eee;">${createdBy}</td>
                <td style="padding: 10px; border-bottom: 1px solid #eee;"><button>View Details</button></td>
            `;
            tableBody.appendChild(row);
        });
    }
    
    // --- C. DYNAMIC FORM LOGIC ---
    
    function updateFormVisibility() {
        const isSupplierOrder = orderTypeSelect.value === 'Supplier';
        supplierGroup.style.display = isSupplierOrder ? 'block' : 'none';
        
        document.querySelectorAll('#order-items-table .expiry-input').forEach(el => {
            el.style.display = isSupplierOrder ? 'table-cell' : 'none';
        });
    }
    
    function addItemRow() {
        lineItemCounter++;
        const row = document.createElement('tr');
        row.id = `line-item-${lineItemCounter}`;
        
        let defaultExpiry = new Date();
        defaultExpiry.setMonth(defaultExpiry.getMonth() + 6);
        defaultExpiry = defaultExpiry.toISOString().slice(0, 10);

        // Build item dropdown dynamically, embedding unit and price into data-attributes
        const itemOptions = allInventory.map(item => 
            `<option value="${item.id}" data-unit="${item.unit}" data-price="${DEFAULT_PRICE}">
                ${item.name} (Stock: ${item.quantity.toFixed(1)})
            </option>`
        ).join('');

        row.innerHTML = `
            <td>
                <select name="item_id[]" required class="item-select" style="width: 100%; padding: 5px;">
                    <option value="">Select Item</option>
                    ${itemOptions}
                </select>
            </td>
            <td><input type="number" name="quantity[]" required min="0.01" step="any" style="width: 60px; padding: 5px;"></td>
            
            <td><input type="text" name="unit_display[]" readonly style="width: 50px; padding: 5px; background: #eee;" value="—"></td>
            
            <td><input type="number" name="unit_price[]" required min="0" step="any" style="width: 80px; padding: 5px;" value="${DEFAULT_PRICE.toFixed(2)}"></td>
            <td class="expiry-input" style="display: none;"><input type="date" name="expiry_date[]" style="width: 100px; padding: 5px;" value="${defaultExpiry}"></td>
            <td><button type="button" onclick="document.getElementById('order-item-rows').removeChild(document.getElementById('line-item-${lineItemCounter}')); updateFormVisibility();" style="color: red; border: none; background: none;">X</button></td>
        `;
        
        itemRowsBody.appendChild(row);
        updateFormVisibility();

        // ADD EVENT LISTENER FOR UNIT & PRICE AUTO-FILL
        const newItemSelect = row.querySelector('.item-select');
        newItemSelect.addEventListener('change', function() {
            const selectedOption = this.options[this.selectedIndex];
            const unitInput = row.querySelector('[name="unit_display[]"]');
            const priceInput = row.querySelector('[name="unit_price[]"]');
            
            if (selectedOption.value) {
                const unit = selectedOption.dataset.unit;
                const price = selectedOption.dataset.price;
                
                unitInput.value = unit; // Auto-fill the unit
                priceInput.value = price; // Auto-fill the price
            } else {
                unitInput.value = '—';
                priceInput.value = DEFAULT_PRICE.toFixed(2);
            }
        });
    }
    
    // --- D. SUBMISSION HANDLER ---

    orderForm.addEventListener('submit', async function(event) {
        event.preventDefault();

        // 1. Collect line item data
        const itemIds = Array.from(this.querySelectorAll('[name="item_id[]"]')).map(el => el.value);
        const quantities = Array.from(this.querySelectorAll('[name="quantity[]"]')).map(el => el.value);
        const prices = Array.from(this.querySelectorAll('[name="unit_price[]"]')).map(el => el.value);
        const expiryDates = Array.from(this.querySelectorAll('[name="expiry_date[]"]')).map(el => el.value);

        // Map data into the structure the OrderController@store expects
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

    // --- E. INITIALIZATION AND EVENT LISTENERS ---
    
    document.getElementById('create-order-btn').addEventListener('click', () => {
        itemRowsBody.innerHTML = ''; // Clear previous items
        orderForm.reset();
        addItemRow(); // Start with one line item
        orderModal.style.display = 'block';
    });
    
    document.getElementById('add-item-row-btn').addEventListener('click', addItemRow);
    
    orderTypeSelect.addEventListener('change', updateFormVisibility);

    loadInitialData();
});