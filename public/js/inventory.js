document.addEventListener('DOMContentLoaded', () => {
    const API_URL = '/inventory-data';
    const tableBody = document.getElementById('inventory-table-body');
    const searchInput = document.getElementById('search-input');
    const summaryLabel = document.getElementById('inventory-summary');
    const csrfToken = document.querySelector('meta[name="csrf-token"]').getAttribute('content');

    const roleElement = document.querySelector('.content-area');
    const roleName = roleElement && roleElement.dataset && roleElement.dataset.role
        ? roleElement.dataset.role.trim().toLowerCase()
        : '';
    const canManageInventory = roleName === 'manager';

    // Edit modal elements
    const editModal = document.getElementById('edit-expiry-modal');
    const editItemName = document.getElementById('edit-expiry-item-name');
    const editExpiryInput = document.getElementById('edit-expiry-input');
    const editCancelBtn = document.getElementById('edit-expiry-cancel');
    const editSaveBtn = document.getElementById('edit-expiry-save');
    let editingItemId = null;

    // Edit details modal elements
    const detailsModal = document.getElementById('edit-details-modal');
    const detailsItemName = document.getElementById('edit-details-item-name');
    const detailsUnitInput = document.getElementById('edit-unit-input');
    const detailsThresholdInput = document.getElementById('edit-threshold-input');
    const detailsQuantityInput = document.getElementById('edit-quantity-input');
    const detailsCancelBtn = document.getElementById('edit-details-cancel');
    const detailsSaveBtn = document.getElementById('edit-details-save');
    let editingDetailsItemId = null;

    let allItems = [];

    function formatDateMMDDYYYY(iso) {
        if (!iso) return 'â€”';
        const d = new Date(iso);
        if (Number.isNaN(d.getTime())) return 'â€”';
        const mm = String(d.getMonth() + 1).padStart(2, '0');
        const dd = String(d.getDate()).padStart(2, '0');
        const yyyy = d.getFullYear();
        return `${mm}/${dd}/${yyyy}`;
    }

    function nearestExpiry(item) {
        const batches = Array.isArray(item.batches) ? item.batches : [];
        let min = null;
        for (const b of batches) {
            if (!b || !b.expiry_date) continue;
            const d = new Date(b.expiry_date);
            if (Number.isNaN(d.getTime())) continue;
            if (!min || d < min) min = d;
        }
        return min ? formatDateMMDDYYYY(min.toISOString()) : 'â€”';
    }

    function defaultOneMonthAheadISO() {
        const d = new Date();
        d.setMonth(d.getMonth() + 1);
        return d.toISOString().slice(0,10);
    }

    function openEditModal(item) {
        editingItemId = item.id;
        editItemName.textContent = `${item.name} (ID ${item.id})`;
        // Pre-fill with nearest expiry or one month ahead
        const batches = Array.isArray(item.batches) ? item.batches : [];
        let nearest = null;
        for (const b of batches) {
            if (b && b.expiry_date) {
                const d = new Date(b.expiry_date);
                if (!Number.isNaN(d.getTime())) {
                    if (!nearest || d < nearest) nearest = d;
                }
            }
        }
        editExpiryInput.value = nearest ? nearest.toISOString().slice(0,10) : defaultOneMonthAheadISO();
        editModal.classList.remove('hidden');
    }

    function closeEditModal() {
        editingItemId = null;
        editModal.classList.add('hidden');
    }

    function currentThreshold(item) {
        const batches = Array.isArray(item.batches) ? item.batches : [];
        let max = 0;
        for (const b of batches) {
            const t = Number(b && b.minimum_stock_threshold);
            if (!Number.isNaN(t) && t > max) max = t;
        }
        return max || 10;
    }

    function openEditDetailsModal(item) {
        editingDetailsItemId = item.id;
        detailsItemName.textContent = `${item.name} (ID ${item.id})`;
        detailsUnitInput.value = item.unit || '';
        detailsThresholdInput.value = currentThreshold(item);
        if (detailsQuantityInput) {
            const qty = Number(item.quantity);
            detailsQuantityInput.value = Number.isFinite(qty) ? qty : 0;
        }
        detailsModal.classList.remove('hidden');
    }

    function closeEditDetailsModal() {
        editingDetailsItemId = null;
        detailsModal.classList.add('hidden');
    }

    async function loadInventory() {
        try {
            const response = await fetch(API_URL);
            if (!response.ok) {
                throw new Error('Failed to retrieve inventory.');
            }

            allItems = await response.json();
            renderTable(allItems);
        } catch (error) {
            console.error('Inventory load error:', error);
            tableBody.innerHTML = '<tr><td colspan="8" style="padding: 20px; text-align: center; color: #c0392b;">Unable to load inventory. Please refresh.</td></tr>';
            if (summaryLabel) summaryLabel.textContent = '';
        }
    }

    function renderTable(items) {
        if (!Array.isArray(items) || items.length === 0) {
            tableBody.innerHTML = '<tr><td colspan="8" style="padding: 20px; text-align: center;">No inventory items found.</td></tr>';
            if (summaryLabel) summaryLabel.textContent = '0 items';
            return;
        }

        tableBody.innerHTML = '';

        items.forEach(item => {
            const row = document.createElement('tr');
            const statusTag = document.createElement('span');
            const statusKey = (item.status || '').toLowerCase();
            let cssClass = 'status-active';
            if (statusKey === 'critical') cssClass = 'status-critical';
            else if (statusKey === 'low') cssClass = 'status-low';
            else cssClass = 'status-active';
            statusTag.classList.add('status-tag', cssClass);
            statusTag.textContent = item.status;

            const deleteDisabled = !item.can_delete;

            const expiry = nearestExpiry(item);
            row.innerHTML = `
                <td style="padding: 10px; border-bottom: 1px solid #eee;">${item.id}</td>
                <td style="padding: 10px; border-bottom: 1px solid #eee;">${item.name}</td>
                <td style="padding: 10px; border-bottom: 1px solid #eee;">${Number(item.quantity).toFixed(2)}</td>
                <td style="padding: 10px; border-bottom: 1px solid #eee;">${item.unit || '-'}</td>
                <td style="padding: 10px; border-bottom: 1px solid #eee;">${expiry}</td>
                <td style="padding: 10px; border-bottom: 1px solid #eee;">${item.supplier_name}</td>
                <td style="padding: 10px; border-bottom: 1px solid #eee;"></td>
                <td style="padding: 10px; border-bottom: 1px solid #eee;"></td>
            `;

            row.children[6].appendChild(statusTag);
            const actionsCell = row.children[7];
            if (canManageInventory) {
                actionsCell.innerHTML = `
                    <div class="action-dropdown">
                        <button class="action-dropdown-btn edit-button">Edit <i class="fas fa-chevron-down dropdown-icon-small"></i></button>
                        <div class="action-dropdown-content hidden">
                            <a href="#" class="dropdown-action-item edit-details" data-id="${item.id}"><i class="fas fa-sliders-h"></i> Edit Details</a>
                            <a href="#" class="dropdown-action-item edit-item" data-id="${item.id}"><i class="fas fa-edit"></i> Edit Expiry</a>
                            <a href="#" class="dropdown-action-item delete ${deleteDisabled ? 'is-disabled' : ''}" data-id="${item.id}" ${deleteDisabled ? 'aria-disabled="true"' : ''}><i class="fas fa-trash"></i> Delete</a>
                        </div>
                    </div>`;
            } else {
                actionsCell.textContent = 'View only';
                actionsCell.classList.add('inventory-view-only');
            }

            tableBody.appendChild(row);
        });

        if (summaryLabel) summaryLabel.textContent = `${items.length} item${items.length === 1 ? '' : 's'}`;
    }

    async function deleteItem(itemId) {
        try {
            const response = await fetch(`${API_URL}/${itemId}`, {
                method: 'DELETE',
                headers: {
                    'X-CSRF-TOKEN': csrfToken,
                    'Accept': 'application/json',
                },
            });

            const result = await response.json();

            if (!response.ok) {
                throw new Error(result.error || 'Failed to delete item.');
            }

            notify.success(result.message || 'Item removed.');
            await loadInventory();
        } catch (error) {
            console.error('Delete inventory item error:', error);
            const message = error instanceof Error ? error.message : 'Failed to delete item.';
            notify.error(message || 'Failed to delete item.');
        }
    }

    function handleSearch() {
        const term = searchInput.value.trim().toLowerCase();
        if (!term) {
            renderTable(allItems);
            return;
        }

        const filtered = allItems.filter(item => {
            const exp = nearestExpiry(item);
            return [item.name, item.supplier_name, String(item.id), exp]
                .some(value => value && value.toLowerCase().includes(term));
        });

        renderTable(filtered);
    }

    tableBody.addEventListener('click', event => {
        if (!canManageInventory) {
            return;
        }
        // Toggle dropdown
        const toggleBtn = event.target.closest('.action-dropdown-btn');
        if (toggleBtn) {
            event.preventDefault();
            event.stopPropagation();
            document.querySelectorAll('.action-dropdown-content').forEach(el => el.classList.add('hidden'));
            const content = toggleBtn.parentElement.querySelector('.action-dropdown-content');
            if (content) content.classList.toggle('hidden');
            return;
        }

        // Edit details
        const detailsLink = event.target.closest('.dropdown-action-item.edit-details');
        if (detailsLink) {
            event.preventDefault();
            const itemId = detailsLink.dataset.id;
            const item = allItems.find(i => String(i.id) === String(itemId));
            if (item) openEditDetailsModal(item);
            const content = detailsLink.closest('.action-dropdown-content');
            if (content) content.classList.add('hidden');
            return;
        }

        // Edit expiry
        const editLink = event.target.closest('.dropdown-action-item.edit-item');
        if (editLink) {
            event.preventDefault();
            const itemId = editLink.dataset.id;
            const item = allItems.find(i => String(i.id) === String(itemId));
            if (item) openEditModal(item);
            const content = editLink.closest('.action-dropdown-content');
            if (content) content.classList.add('hidden');
            return;
        }

        // Delete
        const delLink = event.target.closest('.dropdown-action-item.delete');
        if (delLink) {
            event.preventDefault();
            if (delLink.classList.contains('is-disabled')) return;
            const itemId = delLink.dataset.id;
            const confirmed = window.confirm('Delete this inventory item? This cannot be undone.');
            if (!confirmed) return;
            const content = delLink.closest('.action-dropdown-content');
            if (content) content.classList.add('hidden');
            deleteItem(itemId);
            return;
        }
    });

    searchInput.addEventListener('input', handleSearch);

    // User dropdown toggle (match layout behavior)
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

    // Modal interactions
    editCancelBtn && editCancelBtn.addEventListener('click', closeEditModal);
    window.addEventListener('click', e => {
        if (e.target === editModal) closeEditModal();
        if (e.target === detailsModal) closeEditDetailsModal();
        document.querySelectorAll('.action-dropdown-content').forEach(el => el.classList.add('hidden'));
    });
    editSaveBtn && editSaveBtn.addEventListener('click', async () => {
        if (!editingItemId) return;
        const iso = editExpiryInput.value || defaultOneMonthAheadISO();
        try {
            const response = await fetch(`${API_URL}/${editingItemId}/expiry`, {
                method: 'PATCH',
                headers: {
                    'Content-Type': 'application/json',
                    'X-CSRF-TOKEN': csrfToken,
                    'Accept': 'application/json',
                },
                body: JSON.stringify({ expiry_date: iso }),
            });
            const result = await response.json();
            if (!response.ok) {
                throw new Error(result.error || 'Failed to update expiry date.');
            }
            await loadInventory();
            closeEditModal();
        } catch (err) {
                        notify.error(err.message || 'Failed to update expiry date.');
        }
    });

    detailsCancelBtn && detailsCancelBtn.addEventListener('click', closeEditDetailsModal);
    detailsSaveBtn && detailsSaveBtn.addEventListener('click', async () => {
        if (!editingDetailsItemId) return;
        const unit = (detailsUnitInput.value || '').trim();
        const threshold = Number(detailsThresholdInput.value || 0);
        const quantity = detailsQuantityInput ? Number(detailsQuantityInput.value || 0) : null;

        if (detailsQuantityInput && (Number.isNaN(quantity) || quantity < 0)) {
            notify.warn('Enter a valid quantity (0 or higher).');
            return;
        }

        if (Number.isNaN(threshold) || threshold < 0) {
            notify.warn('Enter a valid quantity (0 or higher).');
            return;
        }
        try {
            const response = await fetch(`${API_URL}/${editingDetailsItemId}`, {
                method: 'PATCH',
                headers: {
                    'Content-Type': 'application/json',
                    'X-CSRF-TOKEN': csrfToken,
                    'Accept': 'application/json',
                },
                body: JSON.stringify({
                    unit_of_measure: unit,
                    minimum_stock_threshold: threshold,
                    quantity: quantity,
                })
            });
            const result = await response.json();
            if (!response.ok) throw new Error(result.error || 'Failed to update item details.');
            await loadInventory();
            closeEditDetailsModal();
        } catch (err) {
                        notify.error(err.message || 'Failed to update item details.');
        }
    });

    loadInventory();
});






