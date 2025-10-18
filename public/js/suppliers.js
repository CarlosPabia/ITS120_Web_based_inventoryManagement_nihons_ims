document.addEventListener('DOMContentLoaded', () => {
    const API_URL = '/suppliers-data';

    const grid = document.getElementById('supplier-cards-grid');
    const searchInput = document.getElementById('supplier-search');
    const addForm = document.getElementById('add-supplier-form');
    const addSupplierBtn = document.getElementById('add-supplier-btn-header');
    const csrfToken = addForm.querySelector('input[name="_token"]').value;

    const addCatalogNameInput = document.getElementById('add-catalog-name');
    const addCatalogUnitInput = document.getElementById('add-catalog-unit');
    const addCatalogQuantityInput = document.getElementById('add-catalog-quantity');
    const addCatalogThresholdInput = document.getElementById('add-catalog-threshold');
    const addCatalogPriceInput = document.getElementById('add-catalog-price');
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
    const editCatalogQuantityInput = document.getElementById('edit-catalog-quantity');
    const editCatalogThresholdInput = document.getElementById('edit-catalog-threshold');
    const editCatalogPriceInput = document.getElementById('edit-catalog-price');
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
            return '';
        }
        const trimmed = unit.toString().trim();
        return trimmed.length ? trimmed : '';
    }

    function formatCatalogLabel(name, unit) {
        const normalised = formatUnit(unit);
        return normalised ? `${name} (${normalised})` : name;
    }

    function parseOptionalNumber(rawValue) {
        if (rawValue === null || rawValue === undefined) {
            return null;
        }

        const trimmed = rawValue.toString().trim();
        if (!trimmed.length) {
            return null;
        }

        const parsed = Number.parseFloat(trimmed);
        if (Number.isNaN(parsed)) {
            return NaN;
        }

        return Math.round(parsed * 100) / 100;
    }

    function formatNumericDisplay(value) {
        if (value === null || value === undefined) {
            return null;
        }

        const numeric = Number(value);
        if (Number.isNaN(numeric)) {
            return null;
        }

        if (Number.isInteger(numeric)) {
            return numeric.toString();
        }

        return numeric.toFixed(2).replace(/\.?0+$/, '');
    }

    function buildCatalogMeta(quantity, threshold, price) {
        const parts = [];
        const priceText = formatNumericDisplay(price);
        const quantityText = formatNumericDisplay(quantity);
        const thresholdText = formatNumericDisplay(threshold);

        if (priceText !== null) {
            parts.push(`Price: ${priceText}`);
        }

        if (quantityText !== null) {
            parts.push(`Qty: ${quantityText}`);
        }

        if (thresholdText !== null) {
            parts.push(`Min: ${thresholdText}`);
        }

        return parts.join(' | ');
    }

    function formatInputNumber(value) {
        if (value === null || value === undefined || value === '') {
            return '';
        }

        const numeric = Number(value);
        if (Number.isNaN(numeric)) {
            return '';
        }

        return numeric.toFixed(2);
    }

    function extractQuantityFields(source) {
        const hasQuantity = Object.prototype.hasOwnProperty.call(source, 'quantity');
        const hasInitialQuantity = Object.prototype.hasOwnProperty.call(source, 'initial_quantity');
        const hasThreshold = Object.prototype.hasOwnProperty.call(source, 'threshold');
        const hasMinimumThreshold = Object.prototype.hasOwnProperty.call(source, 'minimum_stock_threshold');

        return {
            quantity: hasQuantity ? source.quantity : (hasInitialQuantity ? source.initial_quantity : null),
            threshold: hasThreshold ? source.threshold : (hasMinimumThreshold ? source.minimum_stock_threshold : null),
        };
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
                ? `<ul class="supplier-item-list">${items.map(item => {
                        const { quantity, threshold } = extractQuantityFields(item);
                        const priceSource = Object.prototype.hasOwnProperty.call(item, 'default_price') ? item.default_price : item.price;
                        const meta = buildCatalogMeta(quantity, threshold, priceSource);
                        const baseLabel = formatCatalogLabel(item.name, item.unit);
                        const metaHtml = meta ? ` - <span class="supplier-item-meta">${meta}</span>` : '';
                        const descriptionHtml = item.description
                            ? `<div class="supplier-item-desc">${item.description}</div>`
                            : '';

                        return `<li><div class="supplier-item-line">${baseLabel}${metaHtml}</div>${descriptionHtml}</li>`;
                    }).join('')}</ul>`
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
        addCatalogQuantityInput.value = '';
        addCatalogThresholdInput.value = '';
        if (addCatalogPriceInput) {
            addCatalogPriceInput.value = '';
        }
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
            .map((item, index) => {
                const { quantity, threshold } = extractQuantityFields(item);
                const meta = buildCatalogMeta(quantity, threshold, null);
                const metaHtml = meta ? `<br><small class="catalog-entry-meta">${meta}</small>` : '';
                const descriptionHtml = item.description
                    ? `<br><small class="catalog-entry-desc">${item.description}</small>`
                    : '';
                const priceEditorHtml = `
                    <div class="catalog-entry-price-editor">
                        <span class="catalog-entry-price-label">Price</span>
                        <input type="number"
                               class="catalog-entry-price-input"
                               data-context="add"
                               data-index="${index}"
                               min="0"
                               step="0.01"
                               value="${formatInputNumber(item.price)}">
                    </div>`;

                return `
                    <li class="catalog-entry">
                        <span>
                            ${formatCatalogLabel(item.name, item.unit)}
                            ${metaHtml}
                            ${priceEditorHtml}
                            ${descriptionHtml}
                        </span>
                        <button type="button" class="catalog-remove-btn" data-context="add" data-index="${index}">x</button>
                    </li>
                `;
            })
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
            .map((item, index) => {
                const { quantity, threshold } = extractQuantityFields(item);
                const meta = buildCatalogMeta(quantity, threshold, null);
                const metaHtml = meta ? `<br><small class="catalog-entry-meta">${meta}</small>` : '';
                const descriptionHtml = item.description
                    ? `<br><small class="catalog-entry-desc">${item.description}</small>`
                    : '';
                const priceEditorHtml = `
                    <div class="catalog-entry-price-editor">
                        <span class="catalog-entry-price-label">Price</span>
                        <input type="number"
                               class="catalog-entry-price-input"
                               data-context="existing"
                               data-index="${index}"
                               min="0"
                               step="0.01"
                               value="${formatInputNumber(item.price)}">
                    </div>`;

                return `
                    <li class="catalog-entry">
                        <span>
                            ${formatCatalogLabel(item.name, item.unit)}
                            ${metaHtml}
                            ${priceEditorHtml}
                            ${descriptionHtml}
                        </span>
                        <button type="button" class="catalog-remove-btn" data-context="existing" data-index="${index}">x</button>
                    </li>
                `;
            })
            .join('');

        const newHtml = editCatalogNewItems
            .map((item, index) => {
                const { quantity, threshold } = extractQuantityFields(item);
                const meta = buildCatalogMeta(quantity, threshold, null);
                const metaHtml = meta ? `<br><small class="catalog-entry-meta">${meta}</small>` : '';
                const descriptionHtml = item.description
                    ? `<br><small class="catalog-entry-desc">${item.description}</small>`
                    : '';
                const priceEditorHtml = `
                    <div class="catalog-entry-price-editor">
                        <span class="catalog-entry-price-label">Price</span>
                        <input type="number"
                               class="catalog-entry-price-input"
                               data-context="new"
                               data-index="${index}"
                               min="0"
                               step="0.01"
                               value="${formatInputNumber(item.price)}">
                    </div>`;

                return `
                    <li class="catalog-entry is-new">
                        <span>
                            ${formatCatalogLabel(item.name, item.unit)} <em>(new)</em>
                            ${metaHtml}
                            ${priceEditorHtml}
                            ${descriptionHtml}
                        </span>
                        <button type="button" class="catalog-remove-btn" data-context="new" data-index="${index}">x</button>
                    </li>
                `;
            })
            .join('');

        editCatalogList.innerHTML = existingHtml + newHtml;
    }

    function buildNewItemPayload(items) {
        return items.map(item => {
            const description = item.description ? item.description.trim() : '';
            const payload = {
                name: item.name,
                unit: formatUnit(item.unit) || null,
                description: description.length ? description : null,
            };

            const { quantity, threshold } = extractQuantityFields(item);

            if (quantity !== null && !Number.isNaN(quantity)) {
                payload.initial_quantity = quantity;
            }

            if (threshold !== null && !Number.isNaN(threshold)) {
                payload.minimum_stock_threshold = threshold;
            }

            if (typeof item.price === 'number' && !Number.isNaN(item.price)) {
                payload.price = item.price;
            }

            return payload;
        });
    }

    function assignPriceValueToContext(context, index, value) {
        let target = null;

        if (context === 'add') {
            target = addCatalogNewItems;
        } else if (context === 'existing') {
            target = editCatalogExistingItems;
        } else if (context === 'new') {
            target = editCatalogNewItems;
        }

        if (!target || !Array.isArray(target) || !target[index]) {
            return;
        }

        target[index].price = value;
    }

    addCatalogAddBtn.addEventListener('click', () => {
        const name = addCatalogNameInput.value.trim();
        const unit = addCatalogUnitInput.value.trim();
        const description = addCatalogDescriptionInput.value.trim();
        const quantityValue = parseOptionalNumber(addCatalogQuantityInput.value);
        const thresholdValue = parseOptionalNumber(addCatalogThresholdInput.value);
        const priceValue = parseOptionalNumber(addCatalogPriceInput ? addCatalogPriceInput.value : null);

        if (!name) {
            alert('Provide a catalog item name.');
            return;
        }

        if (Number.isNaN(quantityValue)) {
            alert('Initial quantity must be a valid number.');
            return;
        }

        if (quantityValue !== null && quantityValue < 0) {
            alert('Initial quantity cannot be negative.');
            return;
        }

        if (Number.isNaN(thresholdValue)) {
            alert('Minimum threshold must be a valid number.');
            return;
        }

        if (thresholdValue !== null && thresholdValue < 0) {
            alert('Minimum threshold cannot be negative.');
            return;
        }

        if (priceValue === null || Number.isNaN(priceValue)) {
            alert('Provide a valid default price.');
            return;
        }

        if (priceValue < 0) {
            alert('Default price cannot be negative.');
            return;
        }

        addCatalogNewItems.push({
            name,
            unit: formatUnit(unit),
            quantity: quantityValue,
            threshold: thresholdValue,
            price: priceValue,
            description,
        });
        addCatalogNameInput.value = '';
        addCatalogUnitInput.value = '';
        addCatalogQuantityInput.value = '';
        addCatalogThresholdInput.value = '';
        if (addCatalogPriceInput) {
            addCatalogPriceInput.value = '';
        }
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
        const quantityValue = parseOptionalNumber(editCatalogQuantityInput.value);
        const thresholdValue = parseOptionalNumber(editCatalogThresholdInput.value);
        const priceValue = parseOptionalNumber(editCatalogPriceInput ? editCatalogPriceInput.value : null);

        if (!name) {
            alert('Provide a catalog item name.');
            return;
        }

        if (Number.isNaN(quantityValue)) {
            alert('Initial quantity must be a valid number.');
            return;
        }

        if (quantityValue !== null && quantityValue < 0) {
            alert('Initial quantity cannot be negative.');
            return;
        }

        if (Number.isNaN(thresholdValue)) {
            alert('Minimum threshold must be a valid number.');
            return;
        }

        if (thresholdValue !== null && thresholdValue < 0) {
            alert('Minimum threshold cannot be negative.');
            return;
        }

        if (priceValue === null || Number.isNaN(priceValue)) {
            alert('Provide a valid default price.');
            return;
        }

        if (priceValue < 0) {
            alert('Default price cannot be negative.');
            return;
        }

        editCatalogNewItems.push({
            name,
            unit: formatUnit(unit),
            quantity: quantityValue,
            threshold: thresholdValue,
            price: priceValue,
            description,
        });
        editCatalogNameInput.value = '';
        editCatalogUnitInput.value = '';
        editCatalogQuantityInput.value = '';
        editCatalogThresholdInput.value = '';
        if (editCatalogPriceInput) {
            editCatalogPriceInput.value = '';
        }
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

    function handleCatalogPriceInput(event) {
        const priceInput = event.target.closest('.catalog-entry-price-input');
        if (!priceInput) {
            return;
        }

        const context = priceInput.dataset.context;
        const index = Number(priceInput.dataset.index);
        if (!context || Number.isNaN(index)) {
            return;
        }

        const parsedValue = parseOptionalNumber(priceInput.value);

        if (parsedValue === null || Number.isNaN(parsedValue) || parsedValue < 0) {
            priceInput.classList.add('input-error');
            assignPriceValueToContext(context, index, null);
            return;
        }

        priceInput.classList.remove('input-error');
        assignPriceValueToContext(context, index, parsedValue);
    }

    if (addCatalogList) {
        addCatalogList.addEventListener('input', handleCatalogPriceInput);
    }

    if (editCatalogList) {
        editCatalogList.addEventListener('input', handleCatalogPriceInput);
    }

    async function handleAddFormSubmit(event) {
        event.preventDefault();

        const formData = new FormData(addForm);
        const payload = Object.fromEntries(formData.entries());
        payload.items = [];
        payload.new_items = buildNewItemPayload(addCatalogNewItems);

        const hasInvalidPrices = addCatalogNewItems.some(item => typeof item.price !== 'number' || Number.isNaN(item.price));
        if (hasInvalidPrices) {
            alert('Provide a valid default price for each catalog item.');
            return;
        }

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
        const existingPricesInvalid = editCatalogExistingItems.some(item => typeof item.price !== 'number' || Number.isNaN(item.price));
        const newPricesInvalid = editCatalogNewItems.some(item => typeof item.price !== 'number' || Number.isNaN(item.price));

        if (existingPricesInvalid || newPricesInvalid) {
            alert('Provide a valid default price for each catalog item.');
            return;
        }

        payload.existing_items = editCatalogExistingItems.map(item => ({
            id: item.id,
            price: item.price,
        }));

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
            quantity: (() => {
                const { quantity } = extractQuantityFields(item);
                const value = typeof quantity === 'number' ? quantity : parseOptionalNumber(quantity);
                return Number.isNaN(value) ? null : value;
            })(),
            threshold: (() => {
                const { threshold } = extractQuantityFields(item);
                const value = typeof threshold === 'number' ? threshold : parseOptionalNumber(threshold);
                return Number.isNaN(value) ? null : value;
            })(),
            price: (() => {
                const raw = Object.prototype.hasOwnProperty.call(item, 'default_price') ? item.default_price : item.price;
                const value = parseOptionalNumber(raw);
                if (value === null || Number.isNaN(value)) {
                    return null;
                }
                return value < 0 ? 0 : value;
            })(),
            description: item.description ? item.description.trim() : '',
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
