document.addEventListener('DOMContentLoaded', () => {
    const ordersTableBody = document.getElementById('orders-table-body');
    const orderModal = document.getElementById('order-modal');
    const orderForm = document.getElementById('order-form');
    const orderFormFeedback = document.getElementById('order-form-feedback');
    const orderTypeSelect = document.getElementById('order-type-select');
    const supplierDropdown = document.getElementById('supplier-dropdown-order');
    const itemRowsBody = document.getElementById('order-item-rows');
    const searchInput = document.getElementById('orders-search');
    const typeFilter = document.getElementById('orders-type-filter');
    const statusFilter = document.getElementById('orders-status-filter');
    const addItemRowBtn = document.getElementById('add-item-row-btn');
    const orderTypeHint = document.getElementById('order-type-hint');
    const schedulingFields = document.getElementById('order-scheduling-fields');
    const orderDateInput = document.getElementById('order-date-input');
    const expectedDateInput = document.getElementById('expected-date-input');
    const launchOrderBtn = document.getElementById('launch-order-modal');
    const orderTabs = document.querySelectorAll('.order-tab');
    const orderFormHelper = document.getElementById('order-form-helper-text');
    const inlineSupplierDropdown = document.getElementById('inline-supplier-dropdown');
    const inlineScheduling = document.getElementById('inline-scheduling');
    const inlineOrderDate = document.getElementById('inline-order-date');
    const inlineExpectedDate = document.getElementById('inline-expected-date');
    const inlineResetBtn = document.getElementById('inline-reset-btn');
    const inlineItemsPlaceholder = document.getElementById('inline-items-placeholder');

    const detailsModal = document.getElementById('order-details-modal');
    const detailsStatusSelect = document.getElementById('details-status-select');
    const detailsOrderDateInput = document.getElementById('details-order-date');
    const detailsExpectedDateInput = document.getElementById('details-expected-date');
    const detailsSaveBtn = document.getElementById('details-save-btn');
    const detailsDeleteBtn = document.getElementById('details-delete-btn');
    const detailsFeedback = document.getElementById('details-feedback');

    const csrfToken = document.querySelector('meta[name="csrf-token"]').getAttribute('content');
    const canManage = (document.body.dataset.role || '').toLowerCase() === 'manager';

    const DEFAULT_PRICE = 1.5;
    const STATUS_COLORS = {
        Pending: 'status-pending',
        Confirmed: 'status-confirmed',
        Cancelled: 'status-cancelled',
    };

    const detailsFields = {
        id: document.getElementById('details-order-id'),
        type: document.getElementById('details-order-type'),
        createdBy: document.getElementById('details-created-by'),
        supplierContainer: document.getElementById('details-supplier-info'),
        supplier: document.getElementById('details-supplier-name'),
        itemsBody: document.getElementById('details-item-list'),
        total: document.getElementById('details-order-total'),
    };

    let state = {
        orders: [],
        inventory: [],
        suppliers: [],
    };

    let createMode = 'Supplier';
    let activeDetailsOrderId = null;

    // Safe event binding helper
    function on(el, event, handler) {
        if (el && typeof el.addEventListener === 'function') {
            el.addEventListener(event, handler);
        }
    }

    function setOrderFormFeedback(message, type = 'error') {
        if (!orderFormFeedback) return;
        if (!message) {
            orderFormFeedback.style.display = 'none';
            orderFormFeedback.textContent = '';
            orderFormFeedback.classList.remove('error', 'success');
            return;
        }
        orderFormFeedback.textContent = message;
        orderFormFeedback.style.display = 'block';
        orderFormFeedback.classList.remove('error', 'success');
        if (type) {
            orderFormFeedback.classList.add(type);
        }
    }

    function formatUnit(unit) {
        if (unit === null || unit === undefined) {
            return '-';
        }
        const trimmed = unit.toString().trim();
        return trimmed.length ? trimmed : '-';
    }

    function formatOrderId(id) {
        const prefix = 'ORD-';
        const numeric = typeof id === 'number' ? id : parseInt(id, 10);
        if (Number.isNaN(numeric)) {
            return prefix + id;
        }
        return `${prefix}${numeric.toString().padStart(4, '0')}`;
    }

    function formatOrderTypeLabel(type) {
        return type === 'Supplier' ? 'Supplier (Adding)' : 'Customer (Deducting)';
    }

    function updateInlinePlaceholder() {
        if (!inlineItemsPlaceholder) {
            return;
        }

        const supplierDisabled = inlineSupplierDropdown ? inlineSupplierDropdown.disabled : false;
        if (launchOrderBtn) {
            const defaultLabel = createMode === 'Supplier'
                ? 'Open Adding Builder'
                : 'Open Deducting Builder';
            launchOrderBtn.textContent = supplierDisabled
                ? 'Add a supplier to continue'
                : defaultLabel;
        }

        if (supplierDisabled) {
            inlineItemsPlaceholder.textContent = 'Add suppliers to start creating orders.';
            return;
        }

        inlineItemsPlaceholder.textContent = createMode === 'Supplier'
            ? 'No items yet. Launch the builder to add catalog products.'
            : 'No items yet. Launch the builder to deduct inventory.';
    }

    function formatDateDisplay(value) {
        if (!value) {
            return '&mdash;';
        }
        const date = new Date(value);
        if (Number.isNaN(date.getTime())) {
            return value;
        }
        return date.toISOString().slice(0, 10);
    }
        const date = new Date(value);
        if (Number.isNaN(date.getTime())) {
            return value;
        }
        return date.toISOString().slice(0, 10);
    },

    function formatDateForInput(value) {
        if (!value) {
            return '';
        }
        const date = new Date(value);
        if (Number.isNaN(date.getTime())) {
            return '';
        }
        return date.toISOString().slice(0, 10);
    },

    function normalizeStatus(status) {
        if (!status) {
            return 'Pending';
        }
        if (status === 'Completed' || status === 'Delivered') {
            return 'Confirmed';
        }
        if (status === 'In Progress') {
            return 'Pending';
        }
        return status;
    },

    function renderStatusBadge(status) {
        const normalized = normalizeStatus(status);
        const cssClass = STATUS_COLORS[normalized] || '';
        return `<span class="${cssClass}">${normalized}</span>`;
    },

    function getSupplierById(id) {
        return state.suppliers.find(supplier => Number(supplier.id) === Number(id));
    },

    function getInventoryMetaById(id) {
        return state.inventory.find(item => Number(item.id) === Number(id));
    },

    function getAvailableItemsForCurrentSelection() {
        const supplierId = supplierDropdown.value;
        if (!supplierId) {
            return [];
        }

        const supplier = getSupplierById(supplierId);
        if (!supplier) {
            return [];
        }

        if (orderTypeSelect.value === 'Supplier') {
            if (!Array.isArray(supplier.items)) {
                return [];
            }
            return supplier.items.map(item => {
                const inventoryMeta = getInventoryMetaById(item.id);
                const unit = inventoryMeta ? inventoryMeta.unit : item.unit;
                return {
                    id: item.id,
                    name: inventoryMeta ? inventoryMeta.name : item.name,
                    unit: formatUnit(unit),
                    quantity: inventoryMeta ? inventoryMeta.quantity : undefined,
                };
            });
        }

        return state.inventory
            .filter(item => Number(item.supplier_id) === Number(supplierId))
            .map(item => ({
                id: item.id,
                name: item.name,
                unit: formatUnit(item.unit),
                quantity: item.quantity,
            }));
    },

    function buildItemOptionsHtml(items) {
        if (!items.length) {
            const prompt = supplierDropdown.value ? 'No items available for this supplier' : 'Select supplier first';
            return `<option value="">${prompt}</option>`;
        }

        const optionList = items.map(item => {
            const unitLabel = formatUnit(item.unit);
            const qtyLabel = typeof item.quantity === 'number' ? `Stock: ${Number(item.quantity).toFixed(2)}` : '';
            return `<option value="${item.id}" data-unit="${unitLabel}" data-qty="${qtyLabel}">${item.name}</option>`;
        }).join('');

        return `<option value="">Select Item</option>${optionList}`;
    },

    function refreshAllItemSelects() {
        const availableItems = getAvailableItemsForCurrentSelection();
        const optionsMarkup = buildItemOptionsHtml(availableItems);

        itemRowsBody.querySelectorAll('.item-select').forEach(select => {
            const currentValue = select.value;
            select.innerHTML = optionsMarkup;
            if (currentValue && availableItems.some(item => String(item.id) === currentValue)) {
                select.value = currentValue;
            } else {
                select.value = '';
            }

            select.disabled = availableItems.length === 0;
            select.title = availableItems.length === 0
                ? 'Select a supplier to load available items.'
                : 'Choose an item provided by the selected supplier.';

            const unitDisplay = select.closest('tr').querySelector('.unit-display');
            const selectedOption = select.options[select.selectedIndex];
            unitDisplay.textContent = selectedOption && selectedOption.value ? selectedOption.dataset.unit || '-' : '-';
        });

        addItemRowBtn.disabled = availableItems.length === 0;
    },

    async function loadInitialData() {
        try {
            const [ordersRes, inventoryRes, suppliersRes] = await Promise.all([
                fetch('/orders-data'),
                fetch('/inventory-data'),
                fetch('/suppliers-data'),
            ]);

            if (!ordersRes.ok || !inventoryRes.ok || !suppliersRes.ok) {
                throw new Error('Failed to fetch initial data.');
            }

            const orders = await ordersRes.json();
            state.orders = orders.map(order => ({
                ...order,
                order_status: normalizeStatus(order.order_status),
            }));
            state.inventory = await inventoryRes.json();
            state.suppliers = await suppliersRes.json();

            renderSupplierDropdowns();
            applyFiltersAndRender();
        } catch (error) {
            console.error('Initial data load error:', error);
            ordersTableBody.innerHTML = '<tr><td colspan="7" style="padding: 20px; text-align: center; color: #c0392b;">Unable to load orders. Please refresh the page.</td></tr>';
        }
    },

    function renderSupplierDropdowns() {
        const activeSuppliers = state.suppliers.filter(supplier => supplier.is_active);

        const previousModalValue = supplierDropdown.value;
        const previousInlineValue = inlineSupplierDropdown ? inlineSupplierDropdown.value : '';

        supplierDropdown.innerHTML = '<option value="">-- Select Supplier --</option>';
        if (inlineSupplierDropdown) {
            inlineSupplierDropdown.innerHTML = '<option value="">Select Supplier</option>';
        }

        activeSuppliers.forEach(supplier => {
            const itemCount = Array.isArray(supplier.items) ? supplier.items.length : 0;
            const label = itemCount > 0
                ? `${supplier.supplier_name} (${itemCount} items)`
                : `${supplier.supplier_name} (no catalog items)`;

            const modalOption = document.createElement('option');
            modalOption.value = supplier.id;
            modalOption.textContent = label;
            supplierDropdown.appendChild(modalOption);

            if (inlineSupplierDropdown) {
                const inlineOption = document.createElement('option');
                inlineOption.value = supplier.id;
                inlineOption.textContent = label;
                inlineSupplierDropdown.appendChild(inlineOption);
            }
        });

        const modalMatch = activeSuppliers.some(supplier => String(supplier.id) === previousModalValue);
        supplierDropdown.value = modalMatch ? previousModalValue : '';

        if (inlineSupplierDropdown) {
            const inlineMatch = activeSuppliers.some(supplier => String(supplier.id) === previousInlineValue);
            inlineSupplierDropdown.value = inlineMatch ? previousInlineValue : '';
            inlineSupplierDropdown.disabled = activeSuppliers.length === 0;
        }

        const disabled = activeSuppliers.length === 0;
        supplierDropdown.disabled = disabled;

        if (launchOrderBtn) {
            launchOrderBtn.disabled = disabled;
            launchOrderBtn.classList.toggle('is-disabled', disabled);
        }

        updateInlinePlaceholder();
    },

    function renderOrdersTable(orders) {
        if (!Array.isArray(orders) || orders.length === 0) {
            ordersTableBody.innerHTML = '<tr><td colspan="7" style="text-align: center; padding: 20px;">No orders found.</td></tr>';
            return;
        }

        ordersTableBody.innerHTML = '';

        orders.forEach(order => {
            const row = document.createElement('tr');
            const normalizedStatus = normalizeStatus(order.order_status);
            const statusHtml = renderStatusBadge(normalizedStatus);
            const supplierName = order.supplier ? order.supplier.supplier_name : '&mdash;';

            row.innerHTML = `
                <td>${formatOrderId(order.id)}</td>
                <td>${formatOrderTypeLabel(order.order_type)}</td>
                <td>${supplierName}</td>
                <td>${statusHtml}</td>
                <td>${formatDateDisplay(order.order_date)}</td>
                <td>${formatDateDisplay(order.expected_date)}</td>
                <td style="text-align: right;"></td>
            `;

            const actionsCell = row.children[6];
            actionsCell.style.display = 'flex';
            actionsCell.style.justifyContent = 'flex-end';
            actionsCell.style.gap = '8px';
            actionsCell.style.flexWrap = 'wrap';
            actionsCell.classList.add('row-actions');

            const viewBtn = document.createElement('button');
            viewBtn.type = 'button';
            viewBtn.textContent = 'View';
            viewBtn.className = 'secondary-btn compact-btn outline-btn view-details-btn';
            viewBtn.addEventListener('click', event => {
                event.stopPropagation();
                fetchAndShowOrderDetails(order.id);
            });
            actionsCell.appendChild(viewBtn);

            if (canManage && ['Confirmed', 'Cancelled'].includes(normalizedStatus)) {
                const deleteBtn = document.createElement('button');
                deleteBtn.type = 'button';
                deleteBtn.textContent = 'Delete';
                deleteBtn.className = 'danger-btn compact-btn delete-order-btn';
                deleteBtn.addEventListener('click', event => {
                    event.stopPropagation();
                    if (confirm('Delete this order? This action cannot be undone.')) {
                        deleteOrder(order.id);
                    }
                });
                actionsCell.appendChild(deleteBtn);
            }

            row.addEventListener('click', () => fetchAndShowOrderDetails(order.id));
            ordersTableBody.appendChild(row);
        });
    },

    function applyFiltersAndRender() {
        const term = (searchInput.value || '').toLowerCase().trim();
        const typeValue = typeFilter.value;
        const statusValue = statusFilter.value;

        const filtered = state.orders.filter(order => {
            const orderId = formatOrderId(order.id).toLowerCase();
            const supplierName = order.supplier ? order.supplier.supplier_name.toLowerCase() : '';
            const actionLabel = formatOrderTypeLabel(order.order_type).toLowerCase();
            const matchesSearch = !term || orderId.includes(term) || supplierName.includes(term) || actionLabel.includes(term);
            const matchesType = typeValue === 'All' || order.order_type === typeValue;
            const matchesStatus = statusValue === 'All' || normalizeStatus(order.order_status) === statusValue;
            return matchesSearch && matchesType && matchesStatus;
        });

        renderOrdersTable(filtered);
    },

    function addItemRow() {
        const row = document.createElement('tr');
        row.className = 'order-line-item';

        const availableItems = getAvailableItemsForCurrentSelection();
        const itemOptions = buildItemOptionsHtml(availableItems);

        row.innerHTML = `
            <td style="padding: 5px;">
                <select name="item_id[]" required class="item-select" style="width: 100%; padding: 5px;">
                    ${itemOptions}
                </select>
            </td>
            <td style="padding: 5px;"><input type="number" name="quantity[]" required min="0.01" step="any" style="width: 80px; padding: 5px;"></td>
            <td style="padding: 5px; width: 80px;"><span class="unit-display" style="display: inline-block; min-width: 40px;">-</span></td>
            <td style="padding: 5px;"><input type="number" name="unit_price[]" required min="0" step="any" value="${DEFAULT_PRICE.toFixed(2)}" style="width: 90px; padding: 5px;"></td>
            <td class="expiry-input" style="padding: 5px;"><input type="date" name="expiry_date[]" style="width: 140px; padding: 5px;" title="Not required for customer orders."></td>
            <td style="padding: 5px;"><button type="button" class="link-btn remove-row-btn">Remove</button></td>
        `;

        itemRowsBody.appendChild(row);
        updateFormVisibility();
        refreshAllItemSelects();
    },

    function updateFormVisibility() {
        const isSupplierOrder = orderTypeSelect.value === 'Supplier';

        if (orderTypeHint) {
            orderTypeHint.textContent = isSupplierOrder
                ? 'Supplier orders add stock back to inventory.'
                : 'Customer orders deduct stock supplied by the selected supplier.';
        }

        if (schedulingFields) {
            schedulingFields.style.display = isSupplierOrder ? 'grid' : 'none';
        }

        if (orderDateInput && expectedDateInput) {
            if (isSupplierOrder) {
                orderDateInput.disabled = false;
                expectedDateInput.disabled = false;
            } else {
                orderDateInput.value = '';
                expectedDateInput.value = '';
                orderDateInput.disabled = true;
                expectedDateInput.disabled = true;
            }
        }

        itemRowsBody.querySelectorAll('.expiry-input').forEach(cell => {
            const input = cell.querySelector('input[name="expiry_date[]"]');
            if (!input) {
                return;
            }

            if (isSupplierOrder) {
                input.disabled = false;
                input.title = 'Optional: set the expiry date for this stock batch.';
                input.style.backgroundColor = '';
                input.style.cursor = '';
            } else {
                input.value = '';
                input.disabled = true;
                input.title = 'Not required for customer orders.';
                input.style.backgroundColor = '#f5f5f5';
                input.style.cursor = 'not-allowed';
            }
        });

        refreshAllItemSelects();
    },

    function removeItemRow(event) {
        if (event.target.classList.contains('remove-row-btn')) {
            event.preventDefault();
            event.target.closest('tr').remove();
        }
    },

    async function submitOrderForm(event) {
        event.preventDefault();
        setOrderFormFeedback('');

        if (!supplierDropdown.value) {
            setOrderFormFeedback('Select a supplier before saving the order.', 'error');
            supplierDropdown.focus();
            return;
        }

        const isSupplierOrder = orderTypeSelect.value === 'Supplier';
        if (isSupplierOrder) {
            if (!orderDateInput.value || !expectedDateInput.value) {
                setOrderFormFeedback('Set both order date and expected date for supplier orders.', 'error');
                return;
            }
        }

        const lineItems = Array.from(itemRowsBody.querySelectorAll('tr')).map(row => {
            const itemSelect = row.querySelector('.item-select');
            const unitDisplay = row.querySelector('.unit-display');
            if (itemSelect) {
                const selectedOption = itemSelect.options[itemSelect.selectedIndex];
                unitDisplay.textContent = selectedOption && selectedOption.value ? selectedOption.dataset.unit || '-' : '-';
            }

            const expiryInput = row.querySelector('[name="expiry_date[]"]');
            const expiryValue = expiryInput ? expiryInput.value : null;

            return {
                item_id: itemSelect ? itemSelect.value : null,
                quantity: parseFloat(row.querySelector('[name="quantity[]"]').value || '0'),
                unit_price: parseFloat(row.querySelector('[name="unit_price[]"]').value || '0'),
                expiry_date: isSupplierOrder ? (expiryValue || null) : null,
            };
        }).filter(item => item.item_id);

        if (lineItems.length === 0) {
            setOrderFormFeedback('Add at least one item to the order.', 'error');
            return;
        }

        const payload = {
            order_type: orderTypeSelect.value,
            supplier_id: supplierDropdown.value,
            order_date: isSupplierOrder ? orderDateInput.value : null,
            expected_date: isSupplierOrder ? expectedDateInput.value : null,
            items: lineItems,
        };

        try {
            const response = await fetch(orderForm.action, {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'X-CSRF-TOKEN': csrfToken,
                    'Accept': 'application/json',
                },
                body: JSON.stringify(payload),
            });

            const result = await response.json();

            if (!response.ok) {
                if (result && result.errors) {
                    const messages = Object.values(result.errors).flat().join(' ');
                    setOrderFormFeedback(messages || 'Failed to process order.', 'error');
                    return;
                }
                setOrderFormFeedback(result.error || 'Failed to process order.', 'error');
                return;
            }

            alert(result.message || 'Order created successfully.');
            orderModal.classList.add('hidden');
            orderForm.reset();
            setOrderFormFeedback('');
            itemRowsBody.innerHTML = '';
            addItemRow();
            await loadInitialData();
        } catch (error) {
            console.error('Order submission error:', error);
            setOrderFormFeedback(error.message || 'Failed to process order.', 'error');
        }
    },

    async function fetchAndShowOrderDetails(orderId) {
        try {
            const response = await fetch(`/orders-data/${orderId}`);
            if (!response.ok) {
                throw new Error('Failed to retrieve order details.');
            }

            const order = await response.json();
            activeDetailsOrderId = order.id;

            const normalizedStatus = normalizeStatus(order.order_status);
            detailsFields.id.textContent = formatOrderId(order.id);
            detailsFields.type.textContent = formatOrderTypeLabel(order.order_type);
            detailsFields.createdBy.textContent = order.user ? `${order.user.first_name} ${order.user.last_name}` : 'N/A';

            if (order.supplier) {
                detailsFields.supplier.textContent = order.supplier.supplier_name;
                detailsFields.supplierContainer.style.display = 'block';
            } else {
                detailsFields.supplierContainer.style.display = order.order_type === 'Supplier' ? 'block' : 'none';
                detailsFields.supplier.textContent = order.order_type === 'Supplier' ? 'Unknown Supplier' : '';
            }

            detailsStatusSelect.value = normalizedStatus;
            configureDetailsStatusControls(normalizedStatus);

            if (order.order_type === 'Supplier') {
                detailsOrderDateInput.value = formatDateForInput(order.order_date);
                detailsExpectedDateInput.value = formatDateForInput(order.expected_date);
                detailsOrderDateInput.disabled = false;
                detailsExpectedDateInput.disabled = false;
            } else {
                detailsOrderDateInput.value = formatDateForInput(order.order_date);
                detailsExpectedDateInput.value = formatDateForInput(order.expected_date);
                detailsOrderDateInput.disabled = true;
                detailsExpectedDateInput.disabled = true;
            }

            detailsFields.itemsBody.innerHTML = '';
            let total = 0;
            (order.order_items || []).forEach(item => {
                const subtotal = Number(item.quantity_ordered) * Number(item.unit_price);
                total += subtotal;
                const itemRow = document.createElement('tr');
                itemRow.innerHTML = `
                    <td style="padding: 5px;">${item.inventory_item ? item.inventory_item.item_name : 'Unknown Item'}</td>
                    <td style="padding: 5px; text-align: right;">${Number(item.quantity_ordered).toFixed(2)}</td>
                    <td style="padding: 5px; text-align: right;">${Number(item.unit_price).toFixed(2)}</td>
                    <td style="padding: 5px; text-align: right;">${subtotal.toFixed(2)}</td>
                `;
                detailsFields.itemsBody.appendChild(itemRow);
            });
            detailsFields.total.textContent = total.toFixed(2);

            detailsFeedback.style.display = 'none';
            detailsFeedback.textContent = '';

            if (!canManage) {
                detailsSaveBtn.style.display = 'none';
                detailsStatusSelect.disabled = true;
                detailsOrderDateInput.disabled = true;
                detailsExpectedDateInput.disabled = true;
                detailsDeleteBtn.style.display = 'none';
            } else {
                detailsSaveBtn.style.display = 'inline-block';
                detailsDeleteBtn.style.display = 'inline-block';
            }

            detailsDeleteBtn.dataset.orderId = order.id;
            detailsModal.classList.remove('hidden');
        } catch (error) {
            console.error('Details fetch error:', error);
            alert(error.message);
        }
    },

    function configureDetailsStatusControls(currentStatus) {
        const options = Array.from(detailsStatusSelect.options);
        options.forEach(option => {
            option.disabled = false;
        });

        if (currentStatus === 'Confirmed' || currentStatus === 'Cancelled') {
            detailsStatusSelect.disabled = true;
        } else {
            detailsStatusSelect.disabled = false;
        }
    }
,
    async function submitOrderUpdate() {
        if (!canManage || activeDetailsOrderId === null) {
            return;
        }

        const payload = {
            order_status: detailsStatusSelect.value,
        };

        const requestOptions = {
            method: 'PATCH',
            headers: {
                'Content-Type': 'application/json',
                'X-CSRF-TOKEN': csrfToken,
                'Accept': 'application/json',
            },
            body: null,
        };

        if (detailsOrderDateInput && !detailsOrderDateInput.disabled && detailsOrderDateInput.value) {
            payload.order_date = detailsOrderDateInput.value;
        }
        if (detailsExpectedDateInput && !detailsExpectedDateInput.disabled && detailsExpectedDateInput.value) {
            payload.expected_date = detailsExpectedDateInput.value;
        }

        requestOptions.body = JSON.stringify(payload);

        try {
            const response = await fetch(`/orders-data/${activeDetailsOrderId}`, requestOptions);
            const result = await response.json();

            if (!response.ok) {
                throw new Error(result.error || 'Unable to update order.');
            }

            detailsFeedback.style.display = 'block';
            detailsFeedback.style.color = '#2e7d32';
            detailsFeedback.textContent = result.message || 'Order updated successfully.';

            await loadInitialData();
            configureDetailsStatusControls(payload.order_status);
        } catch (error) {
            detailsFeedback.style.display = 'block';
            detailsFeedback.style.color = '#c0392b';
            detailsFeedback.textContent = error.message;
            console.error('Order update error:', error);
        }
    },

    async function deleteOrder(orderId) {
        try {
            const response = await fetch(`/orders-data/${orderId}`, {
                method: 'DELETE',
                headers: {
                    'X-CSRF-TOKEN': csrfToken,
                    'Accept': 'application/json',
                },
            });

            const result = await response.json();
            if (!response.ok) {
                throw new Error(result.error || 'Failed to delete order.');
            }

            alert(result.message || 'Order deleted.');
            detailsModal.classList.add('hidden');
            await loadInitialData();
        } catch (error) {
            console.error('Delete order error:', error);
            alert(`Error: ${error.message}`);
        }
    },

    function setCreateMode(mode) {
        createMode = mode;
        orderTabs.forEach(tab => {
            tab.classList.toggle('active', tab.dataset.orderMode === mode);
        });

        if (orderFormHelper) {
            orderFormHelper.textContent = mode === 'Supplier'
                ? 'Add new stock from an approved supplier catalog.'
                : 'Deduct stock for a customer order.';
        }

        if (launchOrderBtn) {
            launchOrderBtn.textContent = mode === 'Supplier'
                ? 'Open Adding Builder'
                : 'Open Deducting Builder';
        }

        if (inlineScheduling) {
            const showScheduling = mode === 'Supplier';
            inlineScheduling.classList.toggle('hidden', !showScheduling);
            if (inlineOrderDate) {
                inlineOrderDate.disabled = !showScheduling;
                if (!showScheduling) {
                    inlineOrderDate.value = '';
                }
            }
            if (inlineExpectedDate) {
                inlineExpectedDate.disabled = !showScheduling;
                if (!showScheduling) {
                    inlineExpectedDate.value = '';
                }
            }
        }

        updateInlinePlaceholder();
    },

    function openOrderModalWithMode(mode) {
        setCreateMode(mode);
        orderForm.reset();
        orderTypeSelect.value = mode === 'Supplier' ? 'Supplier' : 'Customer';
        updateFormVisibility();
        renderSupplierDropdowns();

        const inlineValue = inlineSupplierDropdown ? inlineSupplierDropdown.value : '';
        if (inlineValue) {
            supplierDropdown.value = inlineValue;
        }

        if (mode === 'Supplier') {
            if (inlineOrderDate && inlineOrderDate.value) {
                orderDateInput.value = inlineOrderDate.value;
            }
            if (inlineExpectedDate && inlineExpectedDate.value) {
                expectedDateInput.value = inlineExpectedDate.value;
            }
        }

        refreshAllItemSelects();
        itemRowsBody.innerHTML = '';
        addItemRow();
        orderModal.classList.remove('hidden');
    

    if (inlineSupplierDropdown) {
        inlineSupplierDropdown.addEventListener('change', () => {
            updateInlinePlaceholder();
        });
    }

    if (inlineResetBtn) {
        inlineResetBtn.addEventListener('click', () => {
            if (inlineSupplierDropdown) {
                inlineSupplierDropdown.value = '';
            }
            if (inlineOrderDate) {
                inlineOrderDate.value = '';
            }
            if (inlineExpectedDate) {
                inlineExpectedDate.value = '';
            }
            updateInlinePlaceholder();
        });
    }

    on(addItemRowBtn, 'click', addItemRow);
    on(itemRowsBody, 'click', removeItemRow);

    on(orderTypeSelect, 'change', updateFormVisibility);
    on(supplierDropdown, 'change', refreshAllItemSelects);

    on(orderForm, 'submit', submitOrderForm);

    on(searchInput, 'input', applyFiltersAndRender);
    on(typeFilter, 'change', applyFiltersAndRender);
    on(statusFilter, 'change', applyFiltersAndRender);

    Array.from(orderTabs || []).forEach(tab => {
        on(tab, 'click', () => {
            const mode = tab.dataset.orderMode === 'Customer' ? 'Customer' : 'Supplier';
            setCreateMode(mode);
        });
    });

    on(launchOrderBtn, 'click', () => {
        if (launchOrderBtn.disabled) {
            return;
        }
        openOrderModalWithMode(createMode);
    });

    on(itemRowsBody, 'change', event => {
        if (event.target && event.target.classList && event.target.classList.contains('item-select')) {
            const selectedOption = event.target.options[event.target.selectedIndex];
            const unitDisplay = event.target.closest('tr').querySelector('.unit-display');
            unitDisplay.textContent = selectedOption && selectedOption.value ? selectedOption.dataset.unit || '-' : '-';
        }
    });

    on(detailsSaveBtn, 'click', submitOrderUpdate);
    on(detailsDeleteBtn, 'click', () => {
        if (detailsDeleteBtn.dataset.orderId && confirm('Delete this order? This action cannot be undone.')) {
            deleteOrder(detailsDeleteBtn.dataset.orderId);
        }
    });

    window.addEventListener('click', event => {
        if (event.target === orderModal) {
            orderModal.classList.add('hidden');
        }
        if (event.target === detailsModal) {
            detailsModal.classList.add('hidden');
        }
    });

    const userDropdown = document.querySelector('.user-dropdown');
    if (userDropdown) {
        const dropdownContent = userDropdown.querySelector('.dropdown-content');
        userDropdown.addEventListener('click', event => {
            event.stopPropagation();
            dropdownContent.classList.toggle('show');
        });
        window.addEventListener('click', event => {
            if (!userDropdown.contains(event.target)) {
                dropdownContent.classList.remove('show');
            }
        });
    }

    if (itemRowsBody) {
        addItemRow();
    }
    if (orderTypeSelect) {
        updateFormVisibility();
    }
    setCreateMode('Supplier');
    loadInitialData();
});




