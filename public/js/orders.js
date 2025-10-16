document.addEventListener('DOMContentLoaded', function() {
    // --- 1. ELEMENT REFERENCES ---
    const tableBody = document.getElementById('orders-table-body');
    const orderModal = document.getElementById('order-modal');
    const orderForm = document.getElementById('order-form');
    const orderTypeSelect = document.getElementById('order-type-select');
    const supplierGroup = document.getElementById('supplier-select-group');
    const supplierDropdownOrder = document.getElementById('supplier-dropdown-order');
    const itemRowsBody = document.getElementById('order-item-rows');
    const detailsModal = document.getElementById('order-details-modal'); // <-- New element for details
    const csrfToken = orderForm.querySelector('input[name="_token"]').value;

    // --- 2. GLOBAL STATE ---
    let allInventory = [];
    let allSuppliers = [];
    let lineItemCounter = 0;
    const DEFAULT_PRICE = 1.50;

    // --- 3. CORE FUNCTIONS ---

    /**
     * Fetches all necessary data from the server on page load.
     */
    async function loadInitialData() {
        try {
            const [ordersResponse, inventoryResponse, supplierResponse] = await Promise.all([
                fetch('/orders-data').then(res => res.json()),
                fetch('/inventory-data').then(res => res.json()),
                fetch('/suppliers-data').then(res => res.json())
            ]);
            
            allInventory = inventoryResponse;
            allSuppliers = supplierResponse;
            
            renderOrdersTable(ordersResponse);
            renderSupplierDropdowns(); 
            
        } catch (error) {
            console.error('Initial data load error:', error);
            tableBody.innerHTML = '<tr><td colspan="6" style="text-align: center; color: red;">Failed to load order history.</td></tr>';
        }
    }

    /**
     * Renders the main table of all orders.
     */
    function renderOrdersTable(orders) {
        tableBody.innerHTML = '';
        if (!orders || orders.length === 0) {
            tableBody.innerHTML = '<tr><td colspan="6" style="text-align: center; padding: 15px;">No orders found.</td></tr>';
            return;
        }

        orders.forEach(order => {
            const row = tableBody.insertRow();
            const createdBy = order.user ? `${order.user.first_name}` : 'N/A';
            const statusColor = order.order_status === 'Completed' ? 'green' : (order.order_status === 'Pending' ? 'orange' : 'red');

            row.innerHTML = `
                <td style="padding: 10px;">${order.id}</td>
                <td style="padding: 10px;">${order.order_type}</td>
                <td style="padding: 10px;">${new Date(order.order_date).toLocaleDateString()}</td>
                <td style="padding: 10px;"><span style="color: ${statusColor}; font-weight: bold;">${order.order_status}</span></td>
                <td style="padding: 10px;">${createdBy}</td>
                <td class="actions-cell" style="padding: 10px;"></td>
            `;

            // Create and append the "View Details" button
            const detailsBtn = document.createElement('button');
            detailsBtn.textContent = 'Details';
            detailsBtn.className = 'view-details-btn';
            detailsBtn.style.cssText = 'padding: 5px 8px; background: #3498db; color: white; border: none; border-radius: 4px; cursor: pointer;';
            detailsBtn.dataset.orderId = order.id; // Store the ID on the button
            row.querySelector('.actions-cell').appendChild(detailsBtn);
        });
    }
    
    /**
     * Fetches and displays details for a single order in a modal.
     */
    async function fetchAndShowOrderDetails(orderId) {
        try {
            const response = await fetch(`/orders-data/${orderId}`);
            if (!response.ok) throw new Error('Failed to fetch order details.');
            
            const order = await response.json();
            
            // Populate modal fields
            document.getElementById('details-order-id').textContent = order.id;
            document.getElementById('details-order-type').textContent = order.order_type;
            document.getElementById('details-order-status').textContent = order.order_status;
            document.getElementById('details-order-date').textContent = new Date(order.order_date).toLocaleString();
            document.getElementById('details-created-by').textContent = order.user ? `${order.user.first_name} ${order.user.last_name}` : 'N/A';
            
            const supplierInfo = document.getElementById('details-supplier-info');
            if (order.order_type === 'Supplier' && order.supplier) {
                document.getElementById('details-supplier-name').textContent = order.supplier.supplier_name;
                supplierInfo.style.display = 'block';
            } else {
                supplierInfo.style.display = 'none';
            }

            const itemsTbody = document.getElementById('details-item-list');
            itemsTbody.innerHTML = '';
            let total = 0;

            order.order_items.forEach(item => {
                const subtotal = item.quantity_ordered * item.unit_price;
                total += subtotal;
                const itemRow = itemsTbody.insertRow();
                itemRow.innerHTML = `
                    <td style="padding: 5px;">${item.inventory_item ? item.inventory_item.item_name : 'Unknown Item'}</td>
                    <td style="text-align: right; padding: 5px;">${item.quantity_ordered}</td>
                    <td style="text-align: right; padding: 5px;">$${parseFloat(item.unit_price).toFixed(2)}</td>
                    <td style="text-align: right; padding: 5px;">$${subtotal.toFixed(2)}</td>
                `;
            });
            
            document.getElementById('details-order-total').textContent = `$${total.toFixed(2)}`;
            detailsModal.style.display = 'flex';

        } catch (error) {
            console.error('Error fetching order details:', error);
            alert('Could not load order details.');
        }
    }

    /**
     * Populates the supplier dropdown in the create order form.
     */
    function renderSupplierDropdowns() {
        supplierDropdownOrder.innerHTML = '<option value="">-- Select Supplier --</option>';
        allSuppliers.forEach(supplier => {
            const option = document.createElement('option');
            option.value = supplier.id;
            option.textContent = supplier.supplier_name;
            supplierDropdownOrder.appendChild(option);
        });
    }

    /**
     * Adds a new item row to the create order form.
     */
    function addItemRow() {
        lineItemCounter++;
        const rowId = `line-item-${lineItemCounter}`;
        const row = itemRowsBody.insertRow();
        row.id = rowId;
        
        const itemOptions = allInventory.map(item => 
            `<option value="${item.id}" data-unit="${item.unit_of_measure}" data-price="${DEFAULT_PRICE}">
                ${item.item_name}
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
            <td class="expiry-input" style="display: none;"><input type="date" name="expiry_date[]" style="width: 100px; padding: 5px;"></td>
            <td><button type="button" class="remove-row-btn" style="color: red; border: none; background: none;">X</button></td>
        `;
        
        updateFormVisibility();
    }

    /**
     * Toggles visibility of supplier and expiry fields based on order type.
     */
    function updateFormVisibility() {
        const isSupplierOrder = orderTypeSelect.value === 'Supplier';
        supplierGroup.style.display = isSupplierOrder ? 'block' : 'none';
        document.querySelectorAll('#order-items-table .expiry-input').forEach(el => {
            el.style.display = isSupplierOrder ? 'table-cell' : 'none';
        });
    }

    // --- 4. EVENT HANDLERS ---

    // Handle create order form submission
    orderForm.addEventListener('submit', async function(event) {
        event.preventDefault();
        const items = Array.from(this.querySelectorAll('#order-item-rows tr')).map(row => ({
            item_id: row.querySelector('[name="item_id[]"]').value,
            quantity: parseFloat(row.querySelector('[name="quantity[]"]').value),
            unit_price: parseFloat(row.querySelector('[name="unit_price[]"]').value),
            expiry_date: orderTypeSelect.value === 'Supplier' ? row.querySelector('[name="expiry_date[]"]').value : null,
        }));
        
        const payload = {
            _token: csrfToken,
            order_type: orderTypeSelect.value,
            supplier_id: orderTypeSelect.value === 'Supplier' ? supplierDropdownOrder.value : null,
            order_status: 'Completed',
            items: items,
        };
        
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
                loadInitialData();
            } else {
                alert('Error: ' + (result.error || 'Failed to process order.'));
            }
        } catch (error) {
            console.error('Order submission error:', error);
            alert('An unexpected network error occurred.');
        }
    });

    // Event Delegation for dynamic buttons (Details, Add/Remove Row)
    document.body.addEventListener('click', function(event) {
        // View Details Button
        if (event.target.classList.contains('view-details-btn')) {
            fetchAndShowOrderDetails(event.target.dataset.orderId);
        }
        // Remove Item Row Button
        if (event.target.classList.contains('remove-row-btn')) {
            event.target.closest('tr').remove();
        }
    });
    
    // Auto-fill unit and price when an item is selected in the create form
    itemRowsBody.addEventListener('change', function(event) {
        if (event.target.classList.contains('item-select')) {
            const selectedOption = event.target.options[event.target.selectedIndex];
            const row = event.target.closest('tr');
            const unitInput = row.querySelector('[name="unit_display[]"]');
            const priceInput = row.querySelector('[name="unit_price[]"]');
            
            unitInput.value = selectedOption.value ? selectedOption.dataset.unit : '—';
            priceInput.value = selectedOption.value ? selectedOption.dataset.price : DEFAULT_PRICE.toFixed(2);
        }
    });

    // Show create order modal
    document.getElementById('create-order-btn').addEventListener('click', () => {
        itemRowsBody.innerHTML = '';
        orderForm.reset();
        addItemRow();
        updateFormVisibility();
        orderModal.style.display = 'flex';
    });

    // Add another item row
    document.getElementById('add-item-row-btn').addEventListener('click', addItemRow);

    // Update form on order type change
    orderTypeSelect.addEventListener('change', updateFormVisibility);

    // --- 5. INITIALIZATION ---
    loadInitialData();
});