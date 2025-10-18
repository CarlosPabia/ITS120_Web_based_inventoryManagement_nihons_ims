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

  let state = { orders: [], inventory: [], suppliers: [] };
  let createMode = 'Supplier';
  let internalSupplierId = null;
  let activeDetailsOrderId = null;

  // ---- Helper Functions ----
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

  function formatDateDisplay(val) {
    if (!val) return '&mdash;';
    const d = new Date(val);
    return isNaN(d.getTime()) ? val : d.toISOString().slice(0, 10);
  }

  function normalizeStatus(s) {
    if (!s) return 'Pending';
    if (['Completed', 'Delivered'].includes(s)) return 'Confirmed';
    if (s === 'In Progress') return 'Pending';
    return s;
  }

  function renderStatusBadge(s) {
    const n = normalizeStatus(s);
    return `<span class="${STATUS_COLORS[n] || ''}">${n}</span>`;
  }

  async function loadInitialData() {
    try {
      const [ordersRes, inventoryRes, suppliersRes] = await Promise.all([
        fetch('/orders-data'),
        fetch('/inventory-data'),
        fetch('/suppliers-data'),
      ]);

      if (!ordersRes.ok || !inventoryRes.ok || !suppliersRes.ok)
        throw new Error('Failed to load data.');

      state.orders = (await ordersRes.json()).map(o => ({ ...o, order_status: normalizeStatus(o.order_status) }));
      state.inventory = await inventoryRes.json();
      state.suppliers = await suppliersRes.json();
      renderOrdersTable(state.orders);
    } catch (err) {
      console.error(err);
      ordersTableBody.innerHTML = `<tr><td colspan="7" style="text-align:center;">Load error</td></tr>`;
    }
  }

  function renderOrdersTable(list) {
    if (!Array.isArray(list) || list.length === 0) {
      ordersTableBody.innerHTML = '<tr><td colspan="7" style="text-align:center;">No orders found.</td></tr>';
      return;
    }

    ordersTableBody.innerHTML = '';
    list.forEach(order => {
      const tr = document.createElement('tr');
      const statusHtml = renderStatusBadge(order.order_status);
      const supplierName = order.supplier ? order.supplier.supplier_name : '—';

      tr.innerHTML = `
        <td>${formatOrderId(order.id)}</td>
        <td>${order.order_type}</td>
        <td>${supplierName}</td>
        <td>${statusHtml}</td>
        <td>${formatDateDisplay(order.order_date)}</td>
        <td>${formatDateDisplay(order.expected_date)}</td>
        <td style="text-align:right;">
          <button class="secondary-btn compact-btn outline-btn view-details-btn">View</button>
        </td>
      `;

      tr.querySelector('.view-details-btn').addEventListener('click', () => fetchAndShowOrderDetails(order.id));
      ordersTableBody.appendChild(tr);
    });
  }

  async function fetchAndShowOrderDetails(id) {
    try {
      const res = await fetch(`/orders-data/${id}`);
      if (!res.ok) throw new Error('Failed to fetch details');
      const order = await res.json();
      activeDetailsOrderId = order.id;
      detailsFields.id.textContent = formatOrderId(order.id);
      detailsFields.type.textContent = order.order_type;
      detailsFields.supplier.textContent = order.supplier?.supplier_name || '—';
      detailsModal.classList.remove('hidden');
    } catch (e) {
      alert(e.message);
    }
  }

  // ---- Event bindings ----
  on(addItemRowBtn, 'click', () => console.log('add row placeholder'));
  on(searchInput, 'input', () => renderOrdersTable(state.orders));
  on(detailsSaveBtn, 'click', () => console.log('save details placeholder'));
  on(detailsDeleteBtn, 'click', () => console.log('delete placeholder'));

  window.addEventListener('click', e => {
    if (e.target === orderModal) orderModal.classList.add('hidden');
    if (e.target === detailsModal) detailsModal.classList.add('hidden');
  });

  loadInitialData();
});
