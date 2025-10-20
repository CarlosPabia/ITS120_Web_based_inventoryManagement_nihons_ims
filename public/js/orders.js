document.addEventListener('DOMContentLoaded', () => {
  const API = {
    ordersIndex: '/orders-data',
    orderUrl: id => `/orders-data/${id}`,
    inventoryIndex: '/inventory-data',
    suppliersIndex: '/suppliers-data',
  };

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

  const STATUS_COLORS = {
    Pending: 'status-pending',
    Confirmed: 'status-confirmed',
    Cancelled: 'status-cancelled',
  };

  const MODE_CONFIG = {
    Supplier: {
      helper: 'Add new stock from an approved supplier catalog.',
      orderTypeValue: 'Supplier',
      hint: 'Supplier orders add stock to inventory and can include scheduling.',
      showScheduling: true,
    },
    Customer: {
      helper: 'Deduct inventory for a customer pickup or delivery.',
      orderTypeValue: 'Customer',
      hint: 'Customer orders deduct stock supplied by the selected supplier.',
      showScheduling: false,
    },
    Internal: {
      helper: 'Create internal orders to adjust Nihon Cafe stock.',
      orderTypeValue: 'Supplier',
      hint: 'Internal orders use the Nihon Cafe supplier and auto-confirm.',
      showScheduling: false,
    },
  };

  const SUPPLIER_PLACEHOLDER_OPTION = '<option value="">-- Select Supplier --</option>';

  const detailsFields = {
    id: document.getElementById('details-order-id'),
    type: document.getElementById('details-order-type'),
    createdBy: document.getElementById('details-created-by'),
    supplierContainer: document.getElementById('details-supplier-info'),
    supplier: document.getElementById('details-supplier-name'),
    itemsBody: document.getElementById('details-item-list'),
    total: document.getElementById('details-order-total'),
  };

  const state = { orders: [], inventory: [], suppliers: [] };
  let createMode = 'Supplier';
  let internalSupplierId = null;
  let activeDetailsOrderId = null;

  function on(el, evt, handler) {
    if (el && el.addEventListener) el.addEventListener(evt, handler);
  }

  function setOrderFormFeedback(msg, type = 'error') {
    if (!orderFormFeedback) return;
    orderFormFeedback.textContent = msg || '';
    orderFormFeedback.style.display = msg ? 'block' : 'none';
    orderFormFeedback.className = type;
  }

  function formatOrderId(id) {
    const num = parseInt(id, 10);
    return isNaN(num) ? `ORD-${id}` : `ORD-${num.toString().padStart(4, '0')}`;
  }

  function formatDateDisplay(value) {
    if (!value) return '&mdash;';
    const d = new Date(value);
    return Number.isNaN(d.getTime()) ? value : d.toISOString().slice(0, 10);
  }

  function formatDateForInput(value) {
    if (!value) return '';
    const d = new Date(value);
    return Number.isNaN(d.getTime()) ? '' : d.toISOString().slice(0, 10);
  }

  function formatOrderTypeLabel(orderType, actionType) {
    if (actionType === 'Add' || orderType === 'Supplier') return 'Adding';
    return 'Deducting';
  }

  function normalizeStatus(status) {
    if (!status) return 'Pending';
    if (['Completed', 'Delivered'].includes(status)) return 'Confirmed';
    if (status === 'In Progress') return 'Pending';
    return status;
  }

  function todayISO() {
    return new Date().toISOString().slice(0, 10);
  }

  function renderStatusBadge(status) {
    const normalized = normalizeStatus(status);
    const cls = STATUS_COLORS[normalized] || '';
    return `<span class="${cls}">${normalized}</span>`;
  }

  function updateInlinePlaceholder() {
    if (launchOrderBtn) {
      if (createMode === 'Supplier') {
        launchOrderBtn.textContent = 'Open Adding Builder';
      } else if (createMode === 'Customer') {
        launchOrderBtn.textContent = 'Open Deducting Builder';
      } else {
        launchOrderBtn.textContent = 'Open Internal Builder';
      }
    }

    if (!inlineItemsPlaceholder) return;

    if (launchOrderBtn && launchOrderBtn.disabled) {
      inlineItemsPlaceholder.textContent = 'No suppliers available. Add a supplier first.';
      return;
    }

    const placeholderText = {
      Supplier: 'No items yet. Launch the builder to add catalog products.',
      Customer: 'No items yet. Launch the builder to deduct inventory.',
      Internal: 'No items yet. Launch the builder to create an internal order.',
    };

    inlineItemsPlaceholder.textContent = placeholderText[createMode] || '';
  }

  function getSupplierById(id) {
    return state.suppliers.find(supplier => Number(supplier.id) === Number(id));
  }

  function getInventoryItemById(id) {
    return state.inventory.find(item => Number(item.id) === Number(id));
  }

  function nearestBatchExpiryFromInventory(itemId) {
    const item = getInventoryItemById(itemId);
    if (!item || !Array.isArray(item.batches)) return null;
    const dates = item.batches
      .map(batch => batch?.expiry_date)
      .filter(Boolean)
      .map(date => new Date(date))
      .filter(d => !Number.isNaN(d.getTime()))
      .sort((a, b) => a - b);
    return dates.length ? dates[0].toISOString().slice(0, 10) : null;
  }

  function priceFromOption(option) {
    if (!option || !option.dataset) return null;
    const raw = option.dataset.price;
    if (raw === undefined || raw === null || raw === '') return null;
    const numeric = Number(raw);
    return Number.isNaN(numeric) ? null : numeric;
  }

  function syncPriceInputWithSelection(select, { force = false } = {}) {
    if (!select) return;

    const row = select.closest('tr');
    if (!row) return;

    const priceInput = row.querySelector('[name="unit_price[]"]');
    if (!priceInput) return;

    const selectedOption = select.options[select.selectedIndex];
    const hasSelection = selectedOption && selectedOption.value;

    if (!hasSelection) {
      if (force || priceInput.dataset.manual !== 'true') {
        priceInput.value = '';
        priceInput.dataset.manual = 'false';
      }
      return;
    }

    if (!force && priceInput.dataset.manual === 'true') {
      return;
    }

    const numericPrice = priceFromOption(selectedOption);

    if (numericPrice === null) {
      if (force) {
        priceInput.value = '';
        priceInput.dataset.manual = 'false';
      }
      return;
    }

    priceInput.value = numericPrice.toFixed(2);
    priceInput.dataset.manual = 'false';
  }

  function availableItemsForCurrentSelection() {
    const supplierId = supplierDropdown?.value;
    if (!supplierId) return [];
    const supplier = getSupplierById(supplierId);
    if (!supplier) return [];

    if ((createMode === 'Supplier' && orderTypeSelect?.value === 'Supplier') || createMode === 'Internal') {
      const catalog = Array.isArray(supplier.items) ? supplier.items : [];
      return catalog.map(item => {
        const inventoryMeta = getInventoryItemById(item.id);
        return {
          id: item.id,
          name: inventoryMeta ? inventoryMeta.name : item.name,
          unit: inventoryMeta ? inventoryMeta.unit : item.unit,
          price: (() => {
            if (inventoryMeta && typeof inventoryMeta.default_price === 'number') {
              return inventoryMeta.default_price;
            }
            if (typeof item.default_price === 'number') {
              return item.default_price;
            }
            if (typeof item.price === 'number') {
              return item.price;
            }
            return null;
          })(),
        };
      });
    }

    return state.inventory
      .filter(item => Number(item.supplier_id) === Number(supplierId))
      .map(item => ({
        id: item.id,
        name: item.name,
        unit: item.unit,
        price: typeof item.default_price === 'number' ? item.default_price : null,
      }));
  }

  function buildItemOptionsHtml(items) {
    if (!items.length) {
      const message = supplierDropdown?.value ? 'No catalog items available for this supplier.' : 'Select supplier first';
      return `<option value="">${message}</option>`;
    }

    const options = items
      .map(item => {
        const unitAttr = (item.unit || '-').toString();
        const priceAttr = typeof item.price === 'number' && !Number.isNaN(item.price)
          ? item.price.toFixed(2)
          : '';
        return `<option value="${item.id}" data-unit="${unitAttr}" data-price="${priceAttr}">${item.name}</option>`;
      })
      .join('');
    return `<option value="">Select Item</option>${options}`;
  }

  function refreshAllItemSelects() {
    if (!itemRowsBody) return;
    const items = availableItemsForCurrentSelection();
    const html = buildItemOptionsHtml(items);

    itemRowsBody.querySelectorAll('.item-select').forEach(select => {
      const previousValue = select.value;
      select.innerHTML = html;
      const hasPrevious = items.some(item => String(item.id) === previousValue);
      select.value = hasPrevious ? previousValue : '';
      select.disabled = items.length === 0;
      select.title = items.length === 0 ? 'Select a supplier to load available items.' : 'Choose an item provided by the selected supplier.';

      const unitSpan = select.closest('tr')?.querySelector('.unit-display');
      const selectedOption = select.options[select.selectedIndex];
      if (unitSpan) unitSpan.textContent = selectedOption && selectedOption.dataset ? (selectedOption.dataset.unit || '-') : '-';
      syncPriceInputWithSelection(select);
    });

    if (addItemRowBtn) addItemRowBtn.disabled = items.length === 0;
  }

  function renderOrdersTable(list) {
    if (!ordersTableBody) return;
    if (!Array.isArray(list) || list.length === 0) {
      ordersTableBody.innerHTML = '<tr><td colspan="7" style="text-align:center; padding:20px;">No orders found.</td></tr>';
      return;
    }

    ordersTableBody.innerHTML = '';

    list.forEach(order => {
      const tr = document.createElement('tr');
      const supplierNameRaw = order.supplier ? (order.supplier.supplier_name || order.supplier.name) : '&mdash;';
      const supplierName = order.supplier && (order.supplier.is_system === true || String(order.supplier.is_system) === '1')
        ? `${supplierNameRaw} (Internal)`
        : supplierNameRaw;

      const actionType = order.action_type || (order.order_type === 'Supplier' ? 'Add' : 'Deduct');
      const actionLabel = formatOrderTypeLabel(order.order_type, actionType);

      tr.innerHTML = `
        <td>${formatOrderId(order.id)}</td>
        <td>${actionLabel}</td>
        <td>${supplierName}</td>
        <td>${renderStatusBadge(order.order_status)}</td>
        <td>${formatDateDisplay(order.order_date)}</td>
        <td>${formatDateDisplay(order.expected_date)}</td>
        <td style="text-align:right;"></td>
      `;

      const actionsCell = tr.children[6];
      actionsCell.style.display = 'flex';
      actionsCell.style.justifyContent = 'flex-end';
      actionsCell.style.gap = '8px';

      const viewBtn = document.createElement('button');
      viewBtn.type = 'button';
      viewBtn.className = 'secondary-btn compact-btn outline-btn';
      viewBtn.textContent = 'View';
      viewBtn.addEventListener('click', event => {
        event.stopPropagation();
        fetchAndShowOrderDetails(order.id);
      });
      actionsCell.appendChild(viewBtn);

      if (canManage && ['Confirmed', 'Cancelled'].includes(normalizeStatus(order.order_status))) {
        const deleteBtn = document.createElement('button');
        deleteBtn.type = 'button';
        deleteBtn.className = 'danger-btn compact-btn';
        deleteBtn.textContent = 'Delete';
        deleteBtn.addEventListener('click', event => {
          event.stopPropagation();
          if (window.confirm('Delete this order? This action cannot be undone.')) {
            deleteOrder(order.id);
          }
        });
        actionsCell.appendChild(deleteBtn);
      }

      tr.addEventListener('click', () => fetchAndShowOrderDetails(order.id));
      ordersTableBody.appendChild(tr);
    });
  }

  function applyFiltersAndRender() {
    const term = (searchInput?.value || '').toLowerCase().trim();
    const typeValue = typeFilter?.value || 'All';
    const statusValue = statusFilter?.value || 'All';

    const filtered = state.orders.filter(order => {
      const formattedId = formatOrderId(order.id).toLowerCase();
      const supplierName = order.supplier ? (order.supplier.supplier_name || '').toLowerCase() : '';
      const actionType = order.action_type || (order.order_type === 'Supplier' ? 'Add' : 'Deduct');
      const actionLabel = formatOrderTypeLabel(order.order_type, actionType).toLowerCase();
      const status = normalizeStatus(order.order_status);

      const matchesTerm =
        !term ||
        formattedId.includes(term) ||
        supplierName.includes(term) ||
        actionLabel.includes(term);

      const matchesType = typeValue === 'All' || actionType === typeValue;
      const matchesStatus = statusValue === 'All' || status === statusValue;

      return matchesTerm && matchesType && matchesStatus;
    });

    renderOrdersTable(filtered);
  }

  function applyOrderTypeUI(type, modeOverride) {
    const effectiveMode = MODE_CONFIG[modeOverride || type] ? (modeOverride || type) : (type === 'Supplier' ? 'Supplier' : 'Customer');
    const config = MODE_CONFIG[effectiveMode];
    if (orderTypeHint) orderTypeHint.textContent = config.hint;
    if (schedulingFields) schedulingFields.style.display = config.showScheduling ? 'grid' : 'none';
  }

  function populateSupplierDropdown(mode = createMode, preserveSelection = true) {
    if (!supplierDropdown && !inlineSupplierDropdown) return;

    const suppliers = Array.isArray(state.suppliers) ? state.suppliers : [];
    const activeSuppliers = suppliers.filter(s => !!s.is_active || s.is_system);
    const nonSystemSuppliers = activeSuppliers.filter(s => !s.is_system);
    const internalSuppliers = activeSuppliers.filter(s => s.is_system);

    const previousModalValue = preserveSelection && supplierDropdown ? supplierDropdown.value : '';
    const previousInlineValue = preserveSelection && inlineSupplierDropdown ? inlineSupplierDropdown.value : '';

    let options = [];
    let inlineOptions = [];
    let disableModal = false;
    let disableInline = false;
    let nextModalValue = '';

    if (mode === 'Internal') {
      const internal = internalSuppliers[0];
      internalSupplierId = internal ? internal.id : null;
      if (supplierDropdown) {
        if (internal) {
          options = [`<option value="${internal.id}">${internal.supplier_name}</option>`];
          nextModalValue = String(internal.id);
        } else {
          options = ['<option value="">No internal supplier available</option>'];
          nextModalValue = '';
        }
        disableModal = true;
      }
      if (inlineSupplierDropdown) {
        inlineOptions = internalSuppliers.map(s => `<option value="${s.id}">${s.supplier_name}</option>`);
        disableInline = internalSuppliers.length <= 1;
      }
    } else {
      if (!internalSupplierId) {
        const internal = internalSuppliers[0];
        if (internal) internalSupplierId = internal.id;
      }
      const pool = nonSystemSuppliers;
      options = [SUPPLIER_PLACEHOLDER_OPTION].concat(pool.map(s => `<option value="${s.id}">${s.supplier_name}</option>`));
      inlineOptions = ['<option value="">Select Supplier</option>'].concat(pool.map(s => `<option value="${s.id}">${s.supplier_name}</option>`));
      disableModal = pool.length === 0;
      disableInline = pool.length === 0;

      if (supplierDropdown) {
        const stillValid = pool.some(s => String(s.id) === previousModalValue);
        nextModalValue = stillValid ? previousModalValue : '';
      }
    }

    if (supplierDropdown) {
      supplierDropdown.innerHTML = options.join('');
      supplierDropdown.disabled = disableModal;
      supplierDropdown.value = nextModalValue;
    }

    if (inlineSupplierDropdown) {
      inlineSupplierDropdown.innerHTML = inlineOptions.join('');
      inlineSupplierDropdown.disabled = disableInline;
      if (previousInlineValue && inlineOptions.some(option => option.includes(`value="${previousInlineValue}"`))) {
        inlineSupplierDropdown.value = previousInlineValue;
      } else {
        inlineSupplierDropdown.value = '';
      }
    }

    if (launchOrderBtn) {
      const noSuppliersAvailable = mode === 'Internal'
        ? internalSuppliers.length === 0
        : nonSystemSuppliers.length === 0;
      const shouldDisable =
        noSuppliersAvailable ||
        (disableModal && mode !== 'Internal');
      launchOrderBtn.disabled = shouldDisable;
      launchOrderBtn.classList.toggle('is-disabled', shouldDisable);
    }

    refreshAllItemSelects();
    updateInlinePlaceholder();
  }

  function syncModeToForm(mode) {
    const config = MODE_CONFIG[mode];
    if (!config) return;

    orderTabs.forEach(tab => {
      tab.classList.toggle('active', tab.dataset.orderMode === mode);
    });

    if (orderFormHelper) orderFormHelper.textContent = config.helper;

    if (launchOrderBtn) launchOrderBtn.dataset.mode = mode;

    if (orderTypeSelect) {
      orderTypeSelect.value = config.orderTypeValue;
      applyOrderTypeUI(orderTypeSelect.value, mode);
    }

    populateSupplierDropdown(mode);
    updateInlinePlaceholder();
    updateFormVisibility();
  }

  function updateFormVisibility() {
    const isSupplierOrder = orderTypeSelect?.value === 'Supplier';
    const isInternalMode = createMode === 'Internal';

    if (schedulingFields) schedulingFields.style.display = isSupplierOrder ? 'grid' : 'none';

    if (orderDateInput && expectedDateInput) {
      if (isSupplierOrder) {
        const selectedSupplier = getSupplierById(supplierDropdown?.value);
        const isInternalSupplier = selectedSupplier && (selectedSupplier.is_system === true || String(selectedSupplier.is_system) === '1');
        if (isInternalSupplier) {
          const today = todayISO();
          orderDateInput.value = today;
          expectedDateInput.value = today;
          orderDateInput.disabled = true;
          expectedDateInput.disabled = true;
        } else {
          orderDateInput.disabled = false;
          expectedDateInput.disabled = false;
        }
      } else {
        orderDateInput.value = '';
        expectedDateInput.value = '';
        orderDateInput.disabled = true;
        expectedDateInput.disabled = true;
      }
    }

    itemRowsBody?.querySelectorAll('.expiry-input input[name="expiry_date[]"]').forEach(input => {
      if (isSupplierOrder && !isInternalMode) {
        input.disabled = false;
        input.title = 'Optional: set the expected expiry date for this stock batch.';
        input.style.backgroundColor = '';
        input.style.cursor = '';
      } else {
        input.value = '';
        input.disabled = true;
        input.title = 'Not required for this order type.';
        input.style.backgroundColor = '#f5f5f5';
        input.style.cursor = 'not-allowed';
      }
    });

    refreshAllItemSelects();
  }

  function setCreateMode(mode) {
    createMode = MODE_CONFIG[mode] ? mode : 'Supplier';
    syncModeToForm(createMode);

    if (inlineScheduling) {
      const showScheduling = createMode === 'Supplier';
      inlineScheduling.classList.toggle('hidden', !showScheduling);
      if (inlineOrderDate) {
        inlineOrderDate.disabled = !showScheduling;
        if (!showScheduling) inlineOrderDate.value = '';
      }
      if (inlineExpectedDate) {
        inlineExpectedDate.disabled = !showScheduling;
        if (!showScheduling) inlineExpectedDate.value = '';
      }
    }

    updateInlinePlaceholder();
  }

  function resetInlinePanel() {
    setCreateMode('Supplier');
    if (inlineSupplierDropdown) inlineSupplierDropdown.value = '';
    if (inlineOrderDate) inlineOrderDate.value = '';
    if (inlineExpectedDate) inlineExpectedDate.value = '';
    updateInlinePlaceholder();
  }

  function addItemRow() {
    if (!itemRowsBody) return;

    const availableItems = availableItemsForCurrentSelection();
    if (!availableItems.length) {
      setOrderFormFeedback('Select a supplier to load available items.');
      return;
    }

    const row = document.createElement('tr');
    row.className = 'order-line-item';
    row.innerHTML = `
      <td style="padding:5px;">
        <select name="item_id[]" class="item-select" required style="width:100%;padding:5px;">
          ${buildItemOptionsHtml(availableItems)}
        </select>
      </td>
      <td style="padding:5px;">
        <input type="number" name="quantity[]" min="0.01" step="any" required style="width:90px;padding:5px;">
      </td>
      <td style="padding:5px;width:80px;">
        <span class="unit-display" style="display:inline-block;min-width:40px;">-</span>
      </td>
      <td style="padding:5px;">
        <input type="number" name="unit_price[]" min="0" step="0.01" value="" required style="width:90px;padding:5px;" data-manual="false">
      </td>
      <td class="expiry-input" style="padding:5px;">
        <input type="date" name="expiry_date[]" style="width:150px;padding:5px;">
      </td>
      <td style="padding:5px;">
        <button type="button" class="link-btn remove-row-btn">Remove</button>
      </td>
    `;

    itemRowsBody.appendChild(row);
    refreshAllItemSelects();

    const select = row.querySelector('.item-select');
    if (select && select.value && orderTypeSelect?.value === 'Supplier') {
      const expiryInput = row.querySelector('[name="expiry_date[]"]');
      if (expiryInput) {
        expiryInput.value = nearestBatchExpiryFromInventory(select.value) || todayISO();
      }
    }

    updateFormVisibility();
  }

  function removeItemRow(event) {
    if (!event.target.classList.contains('remove-row-btn')) return;
    event.preventDefault();
    const row = event.target.closest('tr');
    if (row) row.remove();
  }

  async function loadInitialData() {
    try {
      const [ordersRes, inventoryRes, suppliersRes] = await Promise.all([
        fetch(API.ordersIndex),
        fetch(API.inventoryIndex),
        fetch(API.suppliersIndex),
      ]);

      if (!ordersRes.ok || !inventoryRes.ok || !suppliersRes.ok) {
        throw new Error('Failed to load initial data.');
      }

      const orders = await ordersRes.json();
      state.orders = (orders || []).map(order => ({
        ...order,
        order_status: normalizeStatus(order.order_status),
      }));
      state.inventory = await inventoryRes.json();
      state.suppliers = await suppliersRes.json();

      populateSupplierDropdown(createMode, false);
      applyFiltersAndRender();
    } catch (error) {
      console.error('Initial data load error:', error);
      if (ordersTableBody) {
        ordersTableBody.innerHTML = '<tr><td colspan="7" style="text-align:center; padding:20px; color:#c0392b;">Unable to load orders. Please refresh the page.</td></tr>';
      }
    }
  }

  function openOrderModalWithMode(mode) {
    setCreateMode(mode);
    orderForm?.reset();
    setOrderFormFeedback('');

    if (itemRowsBody) itemRowsBody.innerHTML = '';

    populateSupplierDropdown(mode, false);

    if (createMode === 'Internal' && supplierDropdown && internalSupplierId) {
      supplierDropdown.value = String(internalSupplierId);
      supplierDropdown.disabled = true;
    }

    refreshAllItemSelects();
    updateFormVisibility();

    if (orderDateInput && expectedDateInput && createMode === 'Supplier') {
      orderDateInput.value = inlineOrderDate?.value || '';
      expectedDateInput.value = inlineExpectedDate?.value || '';
    }

    if (createMode === 'Internal' && orderDateInput && expectedDateInput) {
      const today = todayISO();
      orderDateInput.value = today;
      expectedDateInput.value = today;
      orderDateInput.disabled = true;
      expectedDateInput.disabled = true;
    }

    const availableItems = availableItemsForCurrentSelection();
    if (addItemRowBtn) addItemRowBtn.disabled = availableItems.length === 0;
    if (availableItems.length > 0) {
      addItemRow();
    }

    if (orderModal) orderModal.classList.remove('hidden');
  }

  async function submitOrderForm(event) {
    event.preventDefault();
    setOrderFormFeedback('');

    if (!supplierDropdown || !supplierDropdown.value) {
      setOrderFormFeedback('Select a supplier before saving the order.');
      supplierDropdown?.focus();
      return;
    }

    const orderType = createMode === 'Customer' ? 'Customer' : 'Supplier';
    const isInternal = createMode === 'Internal';

    const lineItems = Array.from(itemRowsBody?.querySelectorAll('tr') || []).map(row => {
      const itemSelect = row.querySelector('.item-select');
      const quantityInput = row.querySelector('[name="quantity[]"]');
      const priceInput = row.querySelector('[name="unit_price[]"]');
      const expiryInput = row.querySelector('[name="expiry_date[]"]');

      return {
        item_id: itemSelect?.value || null,
        quantity: quantityInput ? parseFloat(quantityInput.value || '0') : 0,
        unit_price: priceInput ? parseFloat(priceInput.value || '0') : 0,
        expiry_date: orderType === 'Supplier' && !isInternal ? (expiryInput?.value || null) : null,
      };
    }).filter(item => item.item_id);

    if (lineItems.length === 0) {
      setOrderFormFeedback('Add at least one item to the order.');
      return;
    }

    const payload = {
      order_type: orderType,
      supplier_id: supplierDropdown.value,
      order_date: orderType === 'Supplier' && !isInternal ? orderDateInput?.value || null : null,
      expected_date: orderType === 'Supplier' && !isInternal ? expectedDateInput?.value || null : null,
      items: lineItems,
    };

    try {
      const response = await fetch(API.ordersIndex, {
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
        const message = result?.error || (result?.errors ? Object.values(result.errors).flat().join(' ') : 'Failed to process order.');
        setOrderFormFeedback(message);
        return;
      }

      notify.success(result?.message || 'Order created successfully.');
      orderModal?.classList.add('hidden');
      orderForm?.reset();
      if (itemRowsBody) itemRowsBody.innerHTML = '';
      await loadInitialData();
    } catch (error) {
      console.error('Order submission error:', error);
      setOrderFormFeedback(error.message || 'Failed to process order.');
    }
  }

  async function fetchAndShowOrderDetails(orderId) {
    try {
      const response = await fetch(API.orderUrl(orderId));
      if (!response.ok) throw new Error('Failed to retrieve order details.');

      const order = await response.json();
      activeDetailsOrderId = order.id;

      const actionType = order.action_type || (order.order_type === 'Supplier' ? 'Add' : 'Deduct');
      const actionLabel = formatOrderTypeLabel(order.order_type, actionType);

      if (detailsFields.id) detailsFields.id.textContent = formatOrderId(order.id);
      if (detailsFields.type) detailsFields.type.textContent = actionLabel;
      if (detailsFields.createdBy) {
        detailsFields.createdBy.textContent = order.user ? `${order.user.first_name} ${order.user.last_name}` : 'N/A';
      }

      if (order.supplier && detailsFields.supplier) {
        detailsFields.supplier.textContent = order.supplier.supplier_name;
        detailsFields.supplierContainer?.classList.remove('hidden');
      } else {
        if (order.order_type === 'Supplier') {
          detailsFields.supplierContainer?.classList.remove('hidden');
          if (detailsFields.supplier) detailsFields.supplier.textContent = 'Unknown Supplier';
        } else {
          detailsFields.supplierContainer?.classList.add('hidden');
        }
      }

      const status = normalizeStatus(order.order_status);
      if (detailsStatusSelect) {
        detailsStatusSelect.value = status;
        const isInternalOrder = order.supplier && (order.supplier.is_system === true || String(order.supplier.is_system) === '1');
        detailsStatusSelect.disabled = isInternalOrder || status === 'Confirmed' || status === 'Cancelled' || !canManage;
      }

      if (detailsOrderDateInput) {
        detailsOrderDateInput.value = formatDateForInput(order.order_date);
        detailsOrderDateInput.disabled = order.order_type !== 'Supplier' || !canManage;
      }

      if (detailsExpectedDateInput) {
        detailsExpectedDateInput.value = formatDateForInput(order.expected_date);
        detailsExpectedDateInput.disabled = order.order_type !== 'Supplier' || !canManage;
      }

      if (detailsFields.itemsBody) {
        detailsFields.itemsBody.innerHTML = '';
        let total = 0;
        const items = order.order_items || order.orderItems || [];
        items.forEach(item => {
          const name =
            item.inventory_item?.item_name ||
            item.inventoryItem?.item_name ||
            item.inventory_item?.name ||
            'Unknown Item';
          const quantity = Number(item.quantity_ordered ?? item.quantity ?? 0);
          const price = Number(item.unit_price ?? 0);
          const subtotal = quantity * price;
          total += subtotal;

          const tr = document.createElement('tr');
          tr.innerHTML = `
            <td style="padding:5px;">${name}</td>
            <td style="padding:5px;text-align:right;">${quantity.toFixed(2)}</td>
            <td style="padding:5px;text-align:right;">${price.toFixed(2)}</td>
            <td style="padding:5px;text-align:right;">${subtotal.toFixed(2)}</td>
          `;
          detailsFields.itemsBody.appendChild(tr);
        });
        if (detailsFields.total) detailsFields.total.textContent = total.toFixed(2);
      }

      if (detailsFeedback) {
        detailsFeedback.style.display = 'none';
        detailsFeedback.textContent = '';
      }

      if (detailsSaveBtn) detailsSaveBtn.style.display = canManage ? 'inline-block' : 'none';
      if (detailsDeleteBtn) {
        detailsDeleteBtn.style.display = canManage ? 'inline-block' : 'none';
        detailsDeleteBtn.dataset.orderId = order.id;
      }

      detailsModal?.classList.remove('hidden');
    } catch (error) {
      console.error('Details fetch error:', error);
      notify.success(result?.message || 'Order created successfully.');
    }
  }

  async function submitOrderUpdate() {
    if (!canManage || activeDetailsOrderId == null) return;

    const payload = {
      order_status: detailsStatusSelect?.value,
    };

    if (detailsOrderDateInput && !detailsOrderDateInput.disabled && detailsOrderDateInput.value) {
      payload.order_date = detailsOrderDateInput.value;
    }

    if (detailsExpectedDateInput && !detailsExpectedDateInput.disabled && detailsExpectedDateInput.value) {
      payload.expected_date = detailsExpectedDateInput.value;
    }

    try {
      const response = await fetch(API.orderUrl(activeDetailsOrderId), {
        method: 'PATCH',
        headers: {
          'Content-Type': 'application/json',
          'X-CSRF-TOKEN': csrfToken,
          'Accept': 'application/json',
        },
        body: JSON.stringify(payload),
      });

      const result = await response.json();

      if (!response.ok) throw new Error(result?.error || 'Unable to update order.');

      if (detailsFeedback) {
        detailsFeedback.style.display = 'block';
        detailsFeedback.style.color = '#2e7d32';
        detailsFeedback.textContent = result?.message || 'Order updated successfully.';
      }

      await loadInitialData();

      if (detailsStatusSelect && (detailsStatusSelect.value === 'Confirmed' || detailsStatusSelect.value === 'Cancelled')) {
        detailsStatusSelect.disabled = true;
      }
    } catch (error) {
      if (detailsFeedback) {
        detailsFeedback.style.display = 'block';
        detailsFeedback.style.color = '#c0392b';
        detailsFeedback.textContent = error.message;
      }
      console.error('Order update error:', error);
    }
  }

  async function deleteOrder(orderId) {
    if (!canManage) return;

    try {
      const response = await fetch(API.orderUrl(orderId), {
        method: 'DELETE',
        headers: {
          'X-CSRF-TOKEN': csrfToken,
          'Accept': 'application/json',
        },
      });

      const result = await response.json();
      if (!response.ok) throw new Error(result?.error || 'Failed to delete order.');

      notify.success(result?.message || 'Order created successfully.');
      detailsModal?.classList.add('hidden');
      await loadInitialData();
    } catch (error) {
      console.error('Delete order error:', error);
      notify.success(result?.message || 'Order created successfully.');
    }
  }

  orderTabs.forEach(tab => on(tab, 'click', () => setCreateMode(tab.dataset.orderMode)));
  on(launchOrderBtn, 'click', () => {
    if (!launchOrderBtn.disabled) openOrderModalWithMode(createMode);
  });

  on(addItemRowBtn, 'click', addItemRow);
  on(itemRowsBody, 'click', removeItemRow);

  on(itemRowsBody, 'change', event => {
    if (!event.target.classList.contains('item-select')) return;

    const select = event.target;
    const unitSpan = select.closest('tr')?.querySelector('.unit-display');
    const option = select.options[select.selectedIndex];
    if (unitSpan) unitSpan.textContent = option && option.value ? (option.dataset.unit || '-') : '-';
    syncPriceInputWithSelection(select, { force: true });

    if (orderTypeSelect?.value === 'Supplier') {
      const expiryInput = select.closest('tr')?.querySelector('[name="expiry_date[]"]');
      if (expiryInput) {
        expiryInput.value = option && option.value ? (nearestBatchExpiryFromInventory(option.value) || todayISO()) : todayISO();
      }
    }
  });

  on(itemRowsBody, 'input', event => {
    if (event.target && event.target.getAttribute('name') === 'unit_price[]') {
      event.target.dataset.manual = 'true';
    }
  });

  on(orderTypeSelect, 'change', () => {
    const config = MODE_CONFIG[createMode];
    if (config && orderTypeSelect.value !== config.orderTypeValue) {
      orderTypeSelect.value = config.orderTypeValue;
    }
    applyOrderTypeUI(orderTypeSelect.value, createMode);
    updateFormVisibility();
  });

  on(supplierDropdown, 'change', () => {
    refreshAllItemSelects();
    updateFormVisibility();
  });

  on(orderForm, 'submit', submitOrderForm);
  on(searchInput, 'input', applyFiltersAndRender);
  on(typeFilter, 'change', applyFiltersAndRender);
  on(statusFilter, 'change', applyFiltersAndRender);
  on(detailsSaveBtn, 'click', submitOrderUpdate);
  on(detailsDeleteBtn, 'click', () => {
    if (!detailsDeleteBtn?.dataset.orderId) return;
    if (window.confirm('Delete this order? This action cannot be undone.')) {
      deleteOrder(detailsDeleteBtn.dataset.orderId);
    }
  });

  window.addEventListener('click', event => {
    if (event.target === orderModal) orderModal?.classList.add('hidden');
    if (event.target === detailsModal) detailsModal?.classList.add('hidden');
  });

  on(inlineSupplierDropdown, 'change', updateInlinePlaceholder);
  on(inlineResetBtn, 'click', resetInlinePanel);

  setCreateMode('Supplier');
  loadInitialData();
});



