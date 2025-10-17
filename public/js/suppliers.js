document.addEventListener('DOMContentLoaded', function() {
    // --- API Endpoint ---
    const API_URL = '/suppliers-data';

    // --- Element References ---
    const grid = document.getElementById('supplier-cards-grid');
    const searchInput = document.getElementById('supplier-search');
    
    // Add Form Elements
    const addForm = document.getElementById('add-supplier-form');
    const addSupplierBtn = document.getElementById('add-supplier-btn-header');

    // Edit Modal Elements
    const editModal = document.getElementById('edit-supplier-modal');
    const editForm = document.getElementById('edit-supplier-form-modal');
    const cancelEditBtn = document.getElementById('cancel-edit-btn');
    const csrfToken = addForm.querySelector('input[name="_token"]').value;

    let allSuppliers = []; // Cache for the suppliers list

    // --- Main Functions ---

    /**
     * Fetches all suppliers from the backend and renders them.
     */
    async function initializeSuppliers() {
        try {
            const response = await fetch(API_URL);
            if (!response.ok) throw new Error('Failed to fetch suppliers.');
            allSuppliers = await response.json();
            renderCards(allSuppliers);
        } catch (error) {
            console.error('Error initializing suppliers:', error);
            grid.innerHTML = `<p class="error-message">Error loading data.</p>`;
        }
    }

    /**
     * Renders an array of supplier objects as cards in the grid.
     * @param {Array} suppliers - The array of supplier objects.
     */
    function renderCards(suppliers) {
        grid.innerHTML = '';
        if (suppliers.length === 0) {
            grid.innerHTML = `<p style="text-align: center; padding: 20px;">No suppliers found.</p>`;
            return;
        }

        suppliers.forEach(supplier => {
            const statusClass = supplier.is_active ? 'status-active-text' : 'status-inactive-text';
            const statusText = supplier.is_active ? 'ACTIVE' : 'INACTIVE';

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
                <div class="card-actions">
                    <button class="edit-supplier-btn" data-id="${supplier.id}"><i class="fas fa-edit"></i> Edit</button>
                    <button class="delete-supplier-btn" data-id="${supplier.id}"><i class="fas fa-trash"></i> Delete</button>
                </div>
            `;
            grid.appendChild(card);
        });
    }
    
    /**
     * Handles the submission of the "Add New Supplier" form.
     */
    async function handleAddFormSubmit(event) {
        event.preventDefault();
        const formData = new FormData(addForm);
        const data = Object.fromEntries(formData.entries());

        try {
            const response = await fetch(API_URL, {
                method: 'POST',
                headers: { 'Content-Type': 'application/json', 'X-CSRF-TOKEN': csrfToken, 'Accept': 'application/json' },
                body: JSON.stringify(data),
            });
            const result = await response.json();
            if (!response.ok) throw new Error(result.error || 'Could not add supplier.');
            
            alert(result.message);
            addForm.reset();
            initializeSuppliers(); // Refresh the list
        } catch (error) {
            console.error('Add supplier error:', error);
            alert(`Error: ${error.message}`);
        }
    }

    /**
     * Handles the submission of the "Edit Supplier" modal form.
     */
    async function handleEditFormSubmit(event) {
        event.preventDefault();
        const supplierId = document.getElementById('edit-supplier-id').value;
        const formData = new FormData(editForm);
        const data = Object.fromEntries(formData.entries());

        try {
            const response = await fetch(`${API_URL}/${supplierId}`, {
                method: 'PATCH',
                headers: { 'Content-Type': 'application/json', 'X-CSRF-TOKEN': csrfToken, 'Accept': 'application/json' },
                body: JSON.stringify(data),
            });
            const result = await response.json();
            if (!response.ok) throw new Error(result.error || 'Could not update supplier.');

            alert(result.message);
            closeEditModal();
            initializeSuppliers(); // Refresh the list
        } catch (error) {
            console.error('Update supplier error:', error);
            alert(`Error: ${error.message}`);
        }
    }
    
    /**
     * Deletes a supplier after confirmation.
     * @param {string} supplierId - The ID of the supplier to delete.
     */
    async function deleteSupplier(supplierId) {
        if (!confirm('Are you sure you want to delete this supplier? This action cannot be undone.')) return;

        try {
            const response = await fetch(`${API_URL}/${supplierId}`, {
                method: 'DELETE',
                headers: { 'X-CSRF-TOKEN': csrfToken, 'Accept': 'application/json' }
            });
            const result = await response.json();
            if (!response.ok) throw new Error(result.error || 'Failed to delete supplier.');

            alert(result.message);
            initializeSuppliers(); // Refresh the list
        } catch (error) {
            console.error('Delete error:', error);
            alert(`Error: ${error.message}`);
        }
    }

    // --- Modal & Helper Functions ---

    function openEditModal(supplierId) {
        const supplier = allSuppliers.find(s => s.id == supplierId);
        if (!supplier) return;

        editForm.reset();
        document.getElementById('edit-supplier-id').value = supplier.id;
        document.getElementById('modal-supplier-name').textContent = supplier.supplier_name;
        document.getElementById('edit-contact-person').value = supplier.contact_person || '';
        document.getElementById('edit-email').value = supplier.email || '';
        document.getElementById('edit-phone').value = supplier.phone || '';
        document.getElementById('edit-address').value = supplier.address || '';
        
        const statusToggle = document.getElementById('modal-status-toggle');
        statusToggle.querySelectorAll('.status-toggle-btn').forEach(btn => btn.classList.remove('active'));
        const activeBtn = statusToggle.querySelector(`[data-status="${supplier.is_active ? 1 : 0}"]`);
        if (activeBtn) activeBtn.classList.add('active');
        document.getElementById('edit-is-active').value = supplier.is_active ? 1 : 0;

        editModal.classList.remove('hidden');
    }

    function closeEditModal() {
        editModal.classList.add('hidden');
    }

    function handleSearch() {
        const query = searchInput.value.toLowerCase();
        const filtered = allSuppliers.filter(s => 
            s.supplier_name.toLowerCase().includes(query) ||
            (s.contact_person && s.contact_person.toLowerCase().includes(query))
        );
        renderCards(filtered);
    }

    // --- Event Listeners ---

    addForm.addEventListener('submit', handleAddFormSubmit);
    editForm.addEventListener('submit', handleEditFormSubmit);
    cancelEditBtn.addEventListener('click', closeEditModal);
    
    addSupplierBtn.addEventListener('click', () => {
        document.querySelector('.add-supplier-panel').scrollIntoView({ behavior: 'smooth' });
        document.querySelector('#add-supplier-form input').focus();
    });

    searchInput.addEventListener('input', handleSearch);

    // Use event delegation for buttons inside the dynamic grid
    grid.addEventListener('click', function(event) {
        const editBtn = event.target.closest('.edit-supplier-btn');
        if (editBtn) {
            openEditModal(editBtn.dataset.id);
        }
        const deleteBtn = event.target.closest('.delete-supplier-btn');
        if (deleteBtn) {
            deleteSupplier(deleteBtn.dataset.id);
        }
    });
    
    // Handle status toggle clicks inside the modal
    document.getElementById('modal-status-toggle').addEventListener('click', function(event) {
        const statusBtn = event.target.closest('.status-toggle-btn');
        if (statusBtn) {
            this.querySelectorAll('.status-toggle-btn').forEach(b => b.classList.remove('active'));
            statusBtn.classList.add('active');
            document.getElementById('edit-is-active').value = statusBtn.dataset.status;
        }
    });

    // --- Initial Load ---
    initializeSuppliers();
});

