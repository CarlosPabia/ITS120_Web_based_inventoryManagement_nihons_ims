document.addEventListener('DOMContentLoaded', () => {
    const API_URL = '/inventory-data';
    const tableBody = document.getElementById('inventory-table-body');
    const searchInput = document.getElementById('search-input');
    const summaryLabel = document.getElementById('inventory-summary');
    const csrfToken = document.querySelector('meta[name="csrf-token"]').getAttribute('content');

    let allItems = [];

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
            tableBody.innerHTML = '<tr><td colspan="7" style="padding: 20px; text-align: center; color: #c0392b;">Unable to load inventory. Please refresh.</td></tr>';
            summaryLabel.textContent = '';
        }
    }

    function renderTable(items) {
        if (!Array.isArray(items) || items.length === 0) {
            tableBody.innerHTML = '<tr><td colspan="7" style="padding: 20px; text-align: center;">No inventory items found.</td></tr>';
            summaryLabel.textContent = '0 items';
            return;
        }

        tableBody.innerHTML = '';

        items.forEach(item => {
            const row = document.createElement('tr');

            const statusTag = document.createElement('span');
            statusTag.classList.add('status-tag', `status-${item.status}`);
            statusTag.textContent = item.status;

            const deleteButton = document.createElement('button');
            deleteButton.textContent = 'Delete';
            deleteButton.className = 'action-btn danger-btn';
            deleteButton.dataset.itemId = item.id;
            deleteButton.disabled = !item.can_delete;
            if (!item.can_delete && item.delete_block_reason) {
                deleteButton.title = item.delete_block_reason;
            }

            row.innerHTML = `
                <td style="padding: 10px; border-bottom: 1px solid #eee;">${item.id}</td>
                <td style="padding: 10px; border-bottom: 1px solid #eee;">${item.name}</td>
                <td style="padding: 10px; border-bottom: 1px solid #eee;">${Number(item.quantity).toFixed(2)}</td>
                <td style="padding: 10px; border-bottom: 1px solid #eee;">${item.unit}</td>
                <td style="padding: 10px; border-bottom: 1px solid #eee;">${item.supplier_name}</td>
                <td style="padding: 10px; border-bottom: 1px solid #eee;"></td>
                <td style="padding: 10px; border-bottom: 1px solid #eee;"></td>
            `;

            row.children[5].appendChild(statusTag);
            row.children[6].appendChild(deleteButton);

            tableBody.appendChild(row);
        });

        summaryLabel.textContent = `${items.length} item${items.length === 1 ? '' : 's'}`;
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

            alert(result.message || 'Item removed.');
            await loadInventory();
        } catch (error) {
            console.error('Delete inventory item error:', error);
            alert(`Error: ${error.message}`);
        }
    }

    function handleSearch() {
        const term = searchInput.value.trim().toLowerCase();
        if (!term) {
            renderTable(allItems);
            return;
        }

        const filtered = allItems.filter(item => {
            return [item.name, item.supplier_name, String(item.id)]
                .some(value => value && value.toLowerCase().includes(term));
        });

        renderTable(filtered);
    }

    tableBody.addEventListener('click', event => {
        const button = event.target.closest('button[data-item-id]');
        if (!button || button.disabled) {
            return;
        }

        const { itemId } = button.dataset;
        const confirmed = window.confirm('Delete this inventory item? This cannot be undone.');
        if (!confirmed) {
            return;
        }

        deleteItem(itemId);
    });

    searchInput.addEventListener('input', handleSearch);

    loadInventory();
});
