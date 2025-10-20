<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Suppliers - Nihon CafÃ©</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/5.15.4/css/all.min.css">
    
    {{-- Use Laravel's asset() helper to generate correct paths to your CSS --}}
    <link rel="stylesheet" href="{{ asset('main.css') }}"> 
    <link rel="stylesheet" href="{{ asset('suppliers.css') }}?v=2">
</head>
<body>
    <nav class="navbar">
        <div class="navbar-left">
            {{-- Use asset() helper for images in the public folder --}}
            <img src="{{ asset('image/logo.png') }}" alt="Nihon Cafe Logo" class="logo">
            <span class="logo-text">NIHON CAFE</span>
        </div>
        <div class="navbar-right">
            <div class="user-dropdown">
                <div class="user-profile-trigger">
                    {{-- Dynamically display the logged-in user's name --}}
                    <img src="{{ asset('image/logo.png') }}" alt="User Avatar" class="profile-avatar">
                    <span class="profile-name">
                        @auth {{ Auth::user()->first_name }} {{ Auth::user()->last_name }} @endauth
                    </span>
                    <i class="fas fa-chevron-down dropdown-icon"></i>
                </div>
                <div class="dropdown-content">
                    {{-- Use a secure Laravel form for the logout action --}}
                    <form method="POST" action="{{ route('logout') }}">
                        @csrf
                        <button type="submit" class="dropdown-item logout-btn-dropdown">
                            <i class="fas fa-sign-out-alt"></i> Logout
                        </button>
                    </form>
                </div>
            </div>
        </div>
    </nav>

    <div class="app-container">
        <nav id="sidebar" class="sidebar">
            <div class="sidebar-header-placeholder"></div>
            <ul class="nav-links">
                {{-- These links now use the dynamic route() helper --}}
                <li class="nav-item">
                    <a href="{{ route('dashboard') }}" class="nav-link {{ request()->routeIs('dashboard') ? 'active' : '' }}">
                        <i class="fas fa-chart-line"></i> Dashboard
                    </a>
                </li>
                <li class="nav-item">
                    <a href="{{ route('orders.index') }}" class="nav-link {{ request()->routeIs('orders.index') ? 'active' : '' }}">
                        <i class="fas fa-clipboard-list"></i> Orders
                    </a>
                </li>
                <li class="nav-item">
                    <a href="{{ route('inventory.index') }}" class="nav-link {{ request()->routeIs('inventory.index') ? 'active' : '' }}">
                        <i class="fas fa-boxes"></i> Inventory
                    </a>
                </li>
                {{-- Blade directive to show links only to Managers --}}
                @if(Auth::check() && Auth::user()->role->role_name == 'Manager')
                <li class="nav-item">
                    <a href="{{ route('suppliers.index') }}" class="nav-link {{ request()->routeIs('suppliers.index') ? 'active' : '' }}">
                        <i class="fas fa-truck"></i> Suppliers
                    </a>
                </li>
                <li class="nav-item">
                    <a href="{{ route('reports.index') }}" class="nav-link {{ request()->routeIs('reports.index') ? 'active' : '' }}">
                        <i class="fas fa-file-alt"></i> Reports
                    </a>
                </li>
                @endif
                <li class="nav-item settings-separator">
                    @if(Auth::check() && Auth::user()->role->role_name == 'Manager')
                    <a href="{{ route('settings.index') }}" class="nav-link {{ request()->routeIs('settings.index') ? 'active' : '' }}">
                        <i class="fas fa-cog"></i> Settings
                    </a>
                    @endif
                </li>
            </ul>
        </nav>

        <main class="content-area">
            <header class="content-header">
                <div class="page-title-block active">
                    <h2>Supplier Management</h2>
                </div>
            </header>

            <section id="suppliers-view" class="view supplier-main-grid">
                <div class="supplier-list-panel">
                    <div class="supplier-controls-row">
                        <div class="search-bar-inventory supplier-search">
                            <i class="fas fa-search"></i>
                            <input type="text" id="supplier-search" placeholder="Search Supplier..." />
                        </div>
                        <button class="add-supplier-header-btn" id="add-supplier-btn-header">
                            <i class="fas fa-plus"></i> Add
                        </button>
                    </div>
                    {{-- This grid will be dynamically filled by JavaScript --}}
                    <div class="supplier-cards-grid" id="supplier-cards-grid">
                        <p>Loading suppliers...</p>
                    </div>
                </div>

                <div class="add-supplier-panel">
                    <div class="card add-supplier-card">
                        <h3 class="form-title-small">Add New Supplier</h3>
                        {{-- This form is for adding a NEW supplier --}}
                        <form id="add-supplier-form" class="supplier-form-fields">
                            @csrf {{-- Essential Laravel security token --}}
                            <input type="text" name="supplier_name" placeholder="Supplier Name" class="form-input-suppliers" required/>
                            <input type="text" name="contact_person" placeholder="Contact Person" class="form-input-suppliers" required/>
                            <input type="email" name="email" placeholder="Email Address" class="form-input-suppliers" />
                            <input type="tel" name="phone" placeholder="Phone Number" class="form-input-suppliers" />
                            <input type="text" name="address" placeholder="Address" class="form-input-suppliers" />
                            <label class="form-label">Catalog Items</label>
                            <div class="catalog-input-row">
                                <input type="text" id="add-catalog-name" placeholder="Item Name" class="form-input-suppliers" />
                                <input type="text" id="add-catalog-unit" placeholder="Unit (e.g., kg, pcs)" class="form-input-suppliers" />
                            </div>
                            <div class="catalog-input-row">
                                <input type="number" id="add-catalog-quantity" min="0" step="0.01" placeholder="Initial Quantity" class="form-input-suppliers" />
                                <input type="number" id="add-catalog-threshold" min="0" step="0.01" placeholder="Min Threshold" class="form-input-suppliers" />
                            </div>
                            <div class="catalog-input-row">
                                <input type="number" id="add-catalog-price" min="0" step="0.01" placeholder="Default Price" class="form-input-suppliers" />
                            </div>
                            <textarea id="add-catalog-description" placeholder="Description (optional)" class="form-input-suppliers"></textarea>
                            <button type="button" class="catalog-add-btn" id="add-catalog-add-btn">Add Catalog Item</button>
                            <ul id="add-catalog-list" class="catalog-item-list"></ul>
                            <button type="submit" class="add-supplier-confirm-btn">Add Supplier</button>
                        </form>
                    </div>
                </div>
            </section>
        </main>
    </div>
    
    {{-- This is the EDIT modal that pops up --}}
    <div id="edit-supplier-modal" class="modal-overlay hidden">
        <div class="card modal-content">
            <h3 class="form-title-small">Edit Supplier: <span id="modal-supplier-name"></span></h3>
            <form id="edit-supplier-form-modal" class="supplier-form-grid">
                @csrf
                <input type="hidden" id="edit-supplier-id" name="id">
                <div class="supplier-field">
                    <label>Contact Person</label>
                    <input type="text" name="contact_person" class="form-input-suppliers" id="edit-contact-person"/>
                </div>
                <div class="supplier-field">
                    <label>Email</label>
                    <input type="email" name="email" class="form-input-suppliers" id="edit-email"/>
                </div>
                <div class="supplier-field">
                    <label>Phone</label>
                    <input type="tel" name="phone" class="form-input-suppliers" id="edit-phone"/>
                </div>
                <div class="supplier-field">
                    <label>Address</label>
                    <input type="text" name="address" class="form-input-suppliers" id="edit-address"/>
                </div>
                <div class="supplier-field supplier-field--full">
                    <label>Catalog Items</label>
                    <div class="catalog-input-row">
                        <input type="text" id="edit-catalog-name" placeholder="Item Name" class="form-input-suppliers" />
                        <input type="text" id="edit-catalog-unit" placeholder="Unit (e.g., kg, pcs)" class="form-input-suppliers" />
                    </div>
                    <div class="catalog-input-row">
                        <input type="number" id="edit-catalog-quantity" min="0" step="0.01" placeholder="Initial Quantity" class="form-input-suppliers" />
                        <input type="number" id="edit-catalog-threshold" min="0" step="0.01" placeholder="Min Threshold" class="form-input-suppliers" />
                    </div>
                    <div class="catalog-input-row">
                        <input type="number" id="edit-catalog-price" min="0" step="0.01" placeholder="Default Price" class="form-input-suppliers" />
                    </div>
                    <textarea id="edit-catalog-description" placeholder="Description (optional)" class="form-input-suppliers"></textarea>
                    <button type="button" class="catalog-add-btn" id="edit-catalog-add-btn">Add Catalog Item</button>
                    <ul id="edit-catalog-list" class="catalog-item-list"></ul>
                </div>
                <div class="supplier-field supplier-field--full">
                    <label class="status-label">Status</label>
                    <div class="status-toggle-group" id="modal-status-toggle">
                        <button type="button" class="status-toggle-btn" data-status="1">Active</button> 
                        <button type="button" class="status-toggle-btn" data-status="0">Inactive</button>
                    </div>
                    <input type="hidden" id="edit-is-active" name="is_active">
                </div>
                <div class="modal-actions">
                    <button type="button" id="cancel-edit-btn" class="btn-secondary modal-cancel-btn">Cancel</button>
                    <button type="submit" class="save-edit-btn">Save Changes</button>
                </div>
            </form>
        </div>
    </div>

    {{-- Link to the new, dedicated JavaScript file for this page --}}
    <script src="{{ asset('js/suppliers.js') }}?v=3" defer></script>
    <script src="{{ asset('.html/script_Front.js') }}" defer></script>
    <script src="{{ asset('js/notify.js') }}" defer></script>
</body>
</html>
