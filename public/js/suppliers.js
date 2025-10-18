document.addEventListener('DOMContentLoaded', () => {
    const API_URL = '/suppliers-data';

    const grid = document.getElementById('supplier-cards-grid');
    const searchInput = document.getElementById('supplier-search');
    const addForm = document.getElementById('add-supplier-form');
    const addSupplierBtn = document.getElementById('add-supplier-btn-header');
    const csrfToken = addForm.querySelector('input[name="_token"]').value;

    const addCatalogNameInput = document.getElementById('add-catalog-name');
    const addCatalogUnitInput = document.getElementById('add-catalog-unit');
    const addCatalogDescriptionInput = document.getElementById('add-catalog-description');
    const addCatalogList = document.getElementById('add-catalog-list');
    const addCatalogAddBtn = document.getElementById('add-catalog-add-btn');

    const editModal = document.getElementById('edit-supplier-modal');
    const editForm = document.getElementById('edit-supplier-form-modal');
    const cancelEditBtn = document.getElementById('cancel-edit-btn');
    const statusToggle = document.getElementById('modal-status-toggle');
    const inactiveToggleBtn = statusToggle.querySelector('[data-status="0"]');
    const editCatalogNameInput = document.getElementById('edit-catalog-name');
    const editCatalogUnitInput = document.getElementById('edit-catalog-unit');
    const editCatalogDescriptionInput = document.getElementById('edit-catalog-description');
    const editCatalogAddBtn = document.getElementById('edit-catalog-add-btn');
    const editCatalogList = document.getElementById('edit-catalog-list');

    let allSuppliers = [];
    let addCatalogNewItems = [];
    let editCatalogExistingItems = [];
    let editCatalogNewItems = [];

    async function fetchSuppliers() {
        try {
            const response = await fetch(API_URL);
            if (!response.ok) {
                throw new Error('Failed to fetch suppliers.');
            }

            allSuppliers = await response.json();
            renderCards(allSuppliers);
        } catch (error) {
            console.error('Supplier fetch error:', error);
            grid.innerHTML = `<p class="error-message">${error.message}</p>`;
        }
    }

    function formatUnit(unit) {
        if (!unit) {
            return '-';
        }
        const trimmed = unit.toString().trim();
        return trimmed.length ? trimmed : '-';
    }

    function renderCards(suppliers) {
        grid.innerHTML = '';

        if (!suppliers.length) {
            grid.innerHTML = '<p style="text-align: center; padding: 20px;">No suppliers found.</p>';
            return;
        }

        suppliers.forEach(supplier => {
            const statusClass = supplier.is_active ? 'status-active-text' : 'status-inactive-text';
            const statusText = supplier.is_active ? 'ACTIVE' : 'INACTIVE';
            let deleteDisabled = supplier.is_active || supplier.is_system;
            let deleteTitle = supplier.is_active ? 'Deactivate the supplier before deleting.' : 'Delete supplier';
            if (supplier.is_system) {
                deleteDisabled = true;
                deleteTitle = 'System suppliers cannot be deleted.';
            }

            const items = Array.isArray(supplier.items) ? supplier.items : [];
            const catalogHtml = items.length
                ? `<ul class="supplier-item-list">${items.map(item => `<li>${item.name} (${formatUnit(item.unit)})</li>`).join('')}</ul>`
                : '<p class="supplier-no-items">No catalog items assigned yet.</p>';

            const card = document.createElement('div');
            card.className = 'card supplier-card';
            card.dataset.supplierId = supplier.id;
            card.innerHTML = `
                <h3 class="supplier-name">${supplier.supplier_name}</h3>
                <p><strong>Contact Person:</strong> ${supplier.contact_person || 'N/A'}</p>
                <p><strong>Email:</strong> ${supplier.email || 'N/A'}</p>
                <p><strong>Phone:</strong> ${supplier.phone || 'N/A'}</p>
                <p><strong>Address:</strong> ${supplier.address || 'N/A'}</p>
                <p class="${statusClass}"><strong>Status:</strong> ${statusText}</p>
                <div class="supplier-items-block">
                    <strong>Catalog:</strong>
                    ${catalogHtml}
                </div>
                <div class="card-actions">
                    <button class="edit-supplier-btn" data-id="${supplier.id}"><i class="fas fa-edit"></i> Edit</button>
                    <button class="delete-supplier-btn" data-id="${supplier.id}" ${deleteDisabled ? 'disabled' : ''} title="${deleteTitle}"><i class="fas fa-trash"></i> Delete</button>
                </div>
            `;

            grid.appendChild(card);
        });
    }

    function resetAddCatalogState() {
        addCatalogNewItems = [];
        addCatalogNameInput.value = '';
        addCatalogUnitInput.value = '';
        addCatalogDescriptionInput.value = '';
        renderAddCatalogList();
    }

    function renderAddCatalogList() {
        if (!addCatalogList) {
            return;
        }

        if (!addCatalogNewItems.length) {
            addCatalogList.innerHTML = '<li class="catalog-empty">No catalog items added yet.</li>';
            return;
        }

        addCatalogList.innerHTML = addCatalogNewItems
            .map((item, index) => `
                <li class="catalog-entry">
                    <span>${item.name} (${formatUnit(item.unit)})</span>
                    <button type="button" class="catalog-remove-btn" data-context="add" data-index="${index}">×</button>
                </li>
            `)
            .join('');
    }

    function renderEditCatalogList() {
        if (!editCatalogList) {
            return;
        }

        if (!editCatalogExistingItems.length && !editCatalogNewItems.length) {
            editCatalogList.innerHTML = '<li class="catalog-empty">No catalog items assigned.</li>';
            return;
        }

        const existingHtml = editCatalogExistingItems
            .map((item, index) => `
                <li class="catalog-entry">
                    <span>${item.name} (${formatUnit(item.unit)})</span>
                    <button type="button" class="catalog-remove-btn" data-context="existing" data-index="${index}">×</button>
                </li>
            `)
            .join('');

        const newHtml = editCatalogNewItems
            .map((item, index) => `
                <li class="catalog-entry is-new">
                    <span>${item.name} (${formatUnit(item.unit)}) <em>(new)</em></span>
                    <button type="button" class="catalog-remove-btn" data-context="new" data-index="${index}">×</button>
                </li>
            `)
            .join('');

        editCatalogList.innerHTML = existingHtml + newHtml;
    }

    function buildNewItemPayload(items) {
        return items.map(item => ({
            name: item.name,
            unit: item.unit,
            description: item.description || null,
        }));
    }

    addCatalogAddBtn.addEventListener('click', () => {
        const name = addCatalogNameInput.value.trim();
        const unit = addCatalogUnitInput.value.trim();
        const description = addCatalogDescriptionInput.value.trim();

        if (!name || !unit) {
            alert('Provide both name and unit for the catalog item.');
            return;
        }

        addCatalogNewItems.push({ name, unit, description });
        addCatalogNameInput.value = '';
        addCatalogUnitInput.value = '';
        addCatalogDescriptionInput.value = '';
        renderAddCatalogList();
    });

    addCatalogList.addEventListener('click', event => {
        const removeBtn = event.target.closest('.catalog-remove-btn');
        if (!removeBtn) {
            return;
        }

        const index = Number(removeBtn.dataset.index);
        if (!Number.isNaN(index)) {
            addCatalogNewItems.splice(index, 1);
            renderAddCatalogList();
        }
    });

    editCatalogAddBtn.addEventListener('click', () => {
        const name = editCatalogNameInput.value.trim();
        const unit = editCatalogUnitInput.value.trim();
        const description = editCatalogDescriptionInput.value.trim();

        if (!name || !unit) {
            alert('Provide both name and unit for the catalog item.');
            return;
        }

        editCatalogNewItems.push({ name, unit, description });
        editCatalogNameInput.value = '';
        editCatalogUnitInput.value = '';
        editCatalogDescriptionInput.value = '';
        renderEditCatalogList();
    });

    editCatalogList.addEventListener('click', event => {
        const removeBtn = event.target.closest('.catalog-remove-btn');
        if (!removeBtn) {
            return;
        }

        const context = removeBtn.dataset.context;
        const index = Number(removeBtn.dataset.index);

        if (context === 'existing' && !Number.isNaN(index)) {
            editCatalogExistingItems.splice(index, 1);
        } else if (context === 'new' && !Number.isNaN(index)) {
            editCatalogNewItems.splice(index, 1);
        }

        renderEditCatalogList();
    });

    async function handleAddFormSubmit(event) {
        event.preventDefault();

        const formData = new FormData(addForm);
        const payload = Object.fromEntries(formData.entries());
        payload.items = [];
        payload.new_items = buildNewItemPayload(addCatalogNewItems);

        try {
            const response = await fetch(API_URL, {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'X-CSRF-TOKEN': csrfToken,
                    'Accept': 'application/json'
                },
                body: JSON.stringify(payload),
            });

            const result = await response.json();
            if (!response.ok) {
                throw new Error(result.error || 'Could not add supplier.');
            }

            alert(result.message);
            addForm.reset();
            resetAddCatalogState();
            await fetchSuppliers();
        } catch (error) {
            console.error('Add supplier error:', error);
            alert(`Error: ${error.message}`);
        }
    }

    async function handleEditFormSubmit(event) {
        event.preventDefault();

        const supplierId = document.getElementById('edit-supplier-id').value;
        const formData = new FormData(editForm);
        const payload = Object.fromEntries(formData.entries());
        payload.items = editCatalogExistingItems.map(item => item.id);
        payload.new_items = buildNewItemPayload(editCatalogNewItems);

        try {
            const response = await fetch(`${API_URL}/${supplierId}`, {
                method: 'PATCH',
                headers: {
                    'Content-Type': 'application/json',
                    'X-CSRF-TOKEN': csrfToken,
                    'Accept': 'application/json'
                },
                body: JSON.stringify(payload),
            });

            const result = await response.json();
            if (!response.ok) {
                throw new Error(result.error || 'Could not update supplier.');
            }

            alert(result.message);
            closeEditModal();
            await fetchSuppliers();
        } catch (error) {
            console.error('Update supplier error:', error);
            alert(`Error: ${error.message}`);
        }
    }

    async function deleteSupplier(supplierId) {
        if (!confirm('Are you sure you want to delete this supplier? This action cannot be undone.')) {
            return;
        }

        try {
            const response = await fetch(`${API_URL}/${supplierId}`, {
                method: 'DELETE',
                headers: {
                    'X-CSRF-TOKEN': csrfToken,
                    'Accept': 'application/json'
                }
            });

            const result = await response.json();
            if (!response.ok) {
                throw new Error(result.error || 'Failed to delete supplier.');
            }

            alert(result.message);
            await fetchSuppliers();
        } catch (error) {
            console.error('Delete supplier error:', error);
            alert(`Error: ${error.message}`);
        }
    }

    function openEditModal(supplierId) {
        const supplier = allSuppliers.find(s => Number(s.id) === Number(supplierId));
        if (!supplier) {
            return;
        }

        editForm.reset();
        editCatalogNewItems = [];
        editCatalogExistingItems = (supplier.items || []).map(item => ({
            id: item.id,
            name: item.name,
            unit: formatUnit(item.unit),
        }));
        renderEditCatalogList();

        document.getElementById('edit-supplier-id').value = supplier.id;
        document.getElementById('modal-supplier-name').textContent = supplier.supplier_name;
        document.getElementById('edit-contact-person').value = supplier.contact_person || '';
        document.getElementById('edit-email').value = supplier.email || '';
        document.getElementById('edit-phone').value = supplier.phone || '';
        document.getElementById('edit-address').value = supplier.address || '';
        document.getElementById('edit-is-active').value = supplier.is_active ? 1 : 0;

        statusToggle.querySelectorAll('.status-toggle-btn').forEach(btn => btn.classList.remove('active'));
        const activeBtn = statusToggle.querySelector(`[data-status="${supplier.is_active ? 1 : 0}"]`);
        if (activeBtn) {
            activeBtn.classList.add('active');
        }

        if (supplier.is_system) {
            inactiveToggleBtn.setAttribute('disabled', 'disabled');
            inactiveToggleBtn.title = 'System suppliers cannot be deactivated.';
        } else {
            inactiveToggleBtn.removeAttribute('disabled');
            inactiveToggleBtn.title = '';
        }

        editModal.classList.remove('hidden');
    }

    function closeEditModal() {
        editModal.classList.add('hidden');
        editCatalogExistingItems = [];
        editCatalogNewItems = [];
        renderEditCatalogList();
    }

    function handleSearch() {
        const query = searchInput.value.toLowerCase();
        const filtered = allSuppliers.filter(supplier =>
            supplier.supplier_name.toLowerCase().includes(query) ||
            (supplier.contact_person && supplier.contact_person.toLowerCase().includes(query))
        );
        renderCards(filtered);
    }

    addForm.addEventListener('submit', handleAddFormSubmit);
    editForm.addEventListener('submit', handleEditFormSubmit);
    cancelEditBtn.addEventListener('click', closeEditModal);

    addSupplierBtn.addEventListener('click', () => {
        document.querySelector('.add-supplier-panel').scrollIntoView({ behavior: 'smooth' });
        document.querySelector('#add-supplier-form input').focus();
    });

    searchInput.addEventListener('input', handleSearch);

    grid.addEventListener('click', event => {
        const editBtn = event.target.closest('.edit-supplier-btn');
        if (editBtn) {
            openEditModal(editBtn.dataset.id);
            return;
        }

        const deleteBtn = event.target.closest('.delete-supplier-btn');
        if (deleteBtn && !deleteBtn.disabled) {
            deleteSupplier(deleteBtn.dataset.id);
        }
    });

    statusToggle.addEventListener('click', event => {
        const statusBtn = event.target.closest('.status-toggle-btn');
        if (!statusBtn || statusBtn.disabled) {
            return;
        }

        statusToggle.querySelectorAll('.status-toggle-btn').forEach(button => button.classList.remove('active'));
        statusBtn.classList.add('active');
        document.getElementById('edit-is-active').value = statusBtn.dataset.status;
    });

    window.addEventListener('click', event => {
        if (event.target === editModal) {
            closeEditModal();
        }
    });

    resetAddCatalogState();
    renderEditCatalogList();
    fetchSuppliers();
});
