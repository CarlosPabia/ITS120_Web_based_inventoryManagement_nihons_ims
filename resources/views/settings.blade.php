<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>System Settings - Nihon Cafe</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/5.15.4/css/all.min.css">
    <link rel="stylesheet" href="{{ asset('main.css') }}">
    <link rel="stylesheet" href="{{ asset('settings.css') }}">
</head>
<body>

    <nav class="navbar">
        <div class="navbar-left">
            <img src="{{ asset('logo.png') }}" alt="Nihon Cafe Logo" class="logo">
            <span class="logo-text">NIHON CAFE</span>
        </div>
        <div class="navbar-right">
            <div class="user-dropdown">
                <div class="user-profile-trigger">
                    <img src="{{ asset('user.png') }}" alt="User Avatar" class="profile-avatar">
                    <span class="profile-name">
                        @auth {{ Auth::user()->first_name }} {{ Auth::user()->last_name }} @endauth
                    </span>
                    <i class="fas fa-chevron-down dropdown-icon"></i>
                </div>
                <div class="dropdown-content">
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
                <li class="nav-item">
                    <a href="{{ route('dashboard') }}" class="nav-link">
                        <i class="fas fa-chart-line"></i> Dashboard
                    </a>
                </li>
                <li class="nav-item">
                    <a href="{{ route('orders.index') }}" class="nav-link">
                        <i class="fas fa-clipboard-list"></i> Orders
                    </a>
                </li>
                <li class="nav-item">
                    <a href="{{ route('inventory.index') }}" class="nav-link">
                        <i class="fas fa-boxes"></i> Inventory
                    </a>
                </li>
                <li class="nav-item">
                    <a href="{{ route('suppliers.index') }}" class="nav-link">
                        <i class="fas fa-truck"></i> Suppliers
                    </a>
                </li>
                <li class="nav-item">
                    <a href="{{ route('reports.index') }}" class="nav-link">
                        <i class="fas fa-file-alt"></i> Reports
                    </a>
                </li>
                <li class="nav-item sidebar-title-item">
                    <a href="{{ route('settings.index') }}" class="nav-link active">
                        <i class="fas fa-cog"></i> Settings
                    </a>
                </li>
            </ul>
        </nav>

        <main class="content-area">
            <header class="content-header">
                <div class="page-title-block active">
                    <h2>System Settings</h2>
                </div>
                
            </header>

            @if(session('success'))
                <div class="card" style="margin-bottom: 20px; background-color: #e8f5e9; color: #1b5e20; border-left: 4px solid #1b5e20;">
                    {{ session('success') }}
                </div>
            @endif
            @if(session('error'))
                <div class="card" style="margin-bottom: 20px; background-color: #ffebee; color: #b71c1c; border-left: 4px solid #b71c1c;">
                    {{ session('error') }}
                </div>
            @endif
            @if ($errors->any())
                <div class="card" style="margin-bottom: 20px; background-color: #ffebee; color: #b71c1c; border-left: 4px solid #b71c1c;">
                    Error: {{ $errors->first() }}
                </div>
            @endif

            <section id="settings-hub" class="settings-hub-grid">
                <button class="setting-card" data-panel="panel-user-management">
                    <i class="fas fa-user-cog"></i>
                    <p>User Management</p>
                </button>
                <button class="setting-card" data-panel="panel-access-control">
                    <i class="fas fa-key"></i>
                    <p>Access Control</p>
                </button>
                <button class="setting-card" data-panel="panel-audit-logs">
                    <i class="fas fa-history"></i>
                    <p>Audit Logs</p>
                </button>
                <button class="setting-card" data-panel="panel-notifications">
                    <i class="fas fa-bell"></i>
                    <p>Stock Alerts</p>
                </button>
            </section>

            <button id="settings-back-btn" class="back-btn hidden" type="button">← Back to Settings</button>

            <section id="panel-user-management" class="setting-panel hidden">
                <div class="card setting-detail-card">
                    <div class="detail-card-header">
                        <h3 class="setting-title">User Management</h3>
                        <button type="button" class="add-account-btn" data-open-modal="create-user-modal">
                            <i class="fas fa-plus"></i> Add User
                        </button>
                    </div>
                    <div class="settings-table-wrapper">
                        <table class="data-table user-management-table">
                            <thead>
                                <tr>
                                    <th>User</th>
                                    <th>Email</th>
                                    <th>Employee ID</th>
                                    <th>Role</th>
                                    <th style="text-align:right;">Actions</th>
                                </tr>
                            </thead>
                            <tbody>
                                @forelse ($users as $user)
                                    <tr>
                                        <td>{{ $user->first_name }} {{ $user->last_name }}</td>
                                        <td>{{ $user->email }}</td>
                                        <td>{{ $user->employee_id }}</td>
                                        <td>{{ $user->role->role_name ?? 'N/A' }}</td>
                                        <td style="text-align:right;">
                                            <button type="button" class="action-btn" data-open-modal="edit-user-{{ $user->id }}">Edit</button>
                                            <form action="{{ route('user.destroy', $user) }}" method="POST" style="display:inline;">
                                                @csrf
                                                @method('DELETE')
                                                <button type="submit" class="action-btn" style="color:#c0392b;" onclick="return confirm('Delete {{ $user->first_name }} {{ $user->last_name }}?');">Delete</button>
                                            </form>
                                        </td>
                                    </tr>
                                @empty
                                    <tr>
                                        <td colspan="5" style="text-align:center; padding: 20px;">No users found.</td>
                                    </tr>
                                @endforelse
                            </tbody>
                        </table>
                    </div>
                </div>
            </section>

            <section id="panel-access-control" class="setting-panel hidden">
                <div class="card setting-detail-card">
                    <h3 class="setting-title">Access Control</h3>
                    <p style="margin-bottom: 15px;">Role-based access overview. Permissions are managed by the system and shown here for quick reference.</p>
                    <table class="data-table">
                        <thead>
                            <tr>
                                <th>Role</th>
                                <th style="text-align:center;">Inventory</th>
                                <th style="text-align:center;">Orders</th>
                                <th style="text-align:center;">Suppliers</th>
                                <th style="text-align:center;">Reports</th>
                                <th style="text-align:center;">Settings</th>
                            </tr>
                        </thead>
                        <tbody>
                            <tr>
                                <td>Manager</td>
                                <td style="text-align:center;">Full</td>
                                <td style="text-align:center;">Full</td>
                                <td style="text-align:center;">Full</td>
                                <td style="text-align:center;">Full</td>
                                <td style="text-align:center;">Full</td>
                            </tr>
                            <tr>
                                <td>Employee</td>
                                <td style="text-align:center;">View</td>
                                <td style="text-align:center;">Create / View</td>
                                <td style="text-align:center;">View</td>
                                <td style="text-align:center;">View</td>
                                <td style="text-align:center;">Restricted</td>
                            </tr>
                        </tbody>
                    </table>
                </div>
            </section>

            <section id="panel-audit-logs" class="setting-panel hidden">
                <div class="card setting-detail-card">
                    <h3 class="setting-title">Recent Audit Logs</h3>
                    <p style="margin-bottom: 15px;">Tracking order activity for compliance. Most recent 25 entries are shown.</p>
                    <div class="settings-table-wrapper">
                        <table class="data-table">
                            <thead>
                                <tr>
                                    <th>Timestamp</th>
                                    <th>User</th>
                                    <th>Activity</th>
                                    <th>Details</th>
                                </tr>
                            </thead>
                            <tbody>
                                @forelse ($auditLogs as $log)
                                    <tr>
                                        <td>{{ optional($log->timestamp)->format('Y-m-d H:i') ?? '—' }}</td>
                                        <td>{{ $log->user ? $log->user->first_name.' '.$log->user->last_name : 'System' }}</td>
                                        <td>{{ $log->activity_type }}</td>
                                        <td>{{ $log->details }}</td>
                                    </tr>
                                @empty
                                    <tr>
                                        <td colspan="4" style="text-align:center; padding: 20px;">No audit activity recorded yet.</td>
                                    </tr>
                                @endforelse
                            </tbody>
                        </table>
                    </div>
                </div>
            </section>

            <section id="panel-notifications" class="setting-panel hidden">
                <div class="card setting-detail-card">
                    <h3 class="setting-title">Stock Alert Configuration</h3>
                    <p>Automated dashboard alerts will highlight items with low or critical stock levels. Configuration options are coming soon.</p>
                    <div class="card" style="margin-top: 15px; padding: 20px; background-color: #fff8e1; border-left: 4px solid #f39c12;">
                        <strong>Next Steps</strong>
                        <ul style="margin-top: 10px; padding-left: 20px;">
                            <li>Define threshold rules per inventory category.</li>
                            <li>Support email & in-app notifications for low stock.</li>
                            <li>Integrate with dashboard widgets for quick review.</li>
                        </ul>
                    </div>
                </div>
            </section>
        </main>
    </div>

    <div id="create-user-modal" class="modal-overlay hidden">
        <div class="modal-content card user-modal-content">
            <h3 class="setting-title" style="margin-bottom: 10px;">Add New User</h3>
            <form method="POST" action="{{ route('user.store') }}" class="user-form-fields">
                @csrf
                <div style="display: grid; grid-template-columns: repeat(2, 1fr); gap: 10px;">
                    <input type="text" name="first_name" class="form-input-user" placeholder="First Name" value="{{ old('first_name') }}" required>
                    <input type="text" name="last_name" class="form-input-user" placeholder="Last Name" value="{{ old('last_name') }}" required>
                </div>
                <input type="email" name="email" class="form-input-user" placeholder="Email" value="{{ old('email') }}" required>
                <input type="text" name="employee_id" class="form-input-user" placeholder="Employee ID" value="{{ old('employee_id') }}" required>
                <select name="role_id" class="form-input-user" required>
                    <option value="">Select Role</option>
                    @foreach ($roles as $role)
                        <option value="{{ $role->id }}" {{ old('role_id') == $role->id ? 'selected' : '' }}>
                            {{ $role->role_name }}
                        </option>
                    @endforeach
                </select>
                <input type="password" name="password" class="form-input-user" placeholder="Password" required>
                <input type="password" name="password_confirmation" class="form-input-user" placeholder="Confirm Password" required>
                <div style="display:flex; justify-content:flex-end; gap:10px;">
                    <button type="button" class="secondary-action-btn" data-modal-close>Cancel</button>
                    <button type="submit" class="primary-action-btn">Create User</button>
                </div>
            </form>
        </div>
    </div>

    @foreach ($users as $user)
        <div id="edit-user-{{ $user->id }}" class="modal-overlay hidden">
            <div class="modal-content card">
                <h3 class="setting-title" style="margin-bottom: 10px;">Edit: {{ $user->first_name }} {{ $user->last_name }}</h3>
                <form method="POST" action="{{ route('user.update', $user) }}" class="user-form-fields">
                    @csrf
                    @method('PATCH')
                    <div style="display: grid; grid-template-columns: repeat(2, 1fr); gap: 10px;">
                        <input type="text" name="first_name" class="form-input-user" value="{{ $user->first_name }}" required>
                        <input type="text" name="last_name" class="form-input-user" value="{{ $user->last_name }}" required>
                    </div>
                    <input type="text" name="employee_id" class="form-input-user" value="{{ $user->employee_id }}" required>
                    <input type="email" name="email" class="form-input-user" value="{{ $user->email }}" required>
                    <select name="role_id" class="form-input-user" required>
                        @foreach ($roles as $role)
                            <option value="{{ $role->id }}" {{ $user->role_id === $role->id ? 'selected' : '' }}>
                                {{ $role->role_name }}
                            </option>
                        @endforeach
                    </select>
                    <input type="password" name="password" class="form-input-user" placeholder="New Password (optional)">
                    <input type="password" name="password_confirmation" class="form-input-user" placeholder="Confirm Password">
                    <div style="display:flex; justify-content:flex-end; gap:10px;">
                        <button type="button" class="secondary-action-btn" data-modal-close>Cancel</button>
                        <button type="submit" class="primary-action-btn">Save Changes</button>
                    </div>
                </form>
            </div>
        </div>
    @endforeach

    <script>
        document.addEventListener('DOMContentLoaded', () => {
            const csrfToken = document.querySelector('meta[name="csrf-token"]').getAttribute('content');
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

            const hub = document.getElementById('settings-hub');
            const backBtn = document.getElementById('settings-back-btn');
            const panels = document.querySelectorAll('.setting-panel');
            const cards = document.querySelectorAll('.setting-card');
            const searchInput = document.getElementById('settings-search-input');

            function showPanel(panelId) {
                panels.forEach(panel => panel.classList.add('hidden'));
                const targetPanel = document.getElementById(panelId);
                if (targetPanel) {
                    hub.classList.add('hidden');
                    backBtn.classList.remove('hidden');
                    targetPanel.classList.remove('hidden');
                }
            }

            function showHub() {
                panels.forEach(panel => panel.classList.add('hidden'));
                hub.classList.remove('hidden');
                backBtn.classList.add('hidden');
            }

            cards.forEach(card => {
                card.addEventListener('click', () => {
                    const target = card.dataset.panel;
                    if (target) {
                        showPanel(target);
                    }
                });
            });

            backBtn.addEventListener('click', showHub);

            if (searchInput) {
                searchInput.addEventListener('input', () => {
                    const term = searchInput.value.toLowerCase().trim();
                    cards.forEach(card => {
                        const text = card.textContent.toLowerCase();
                        card.style.display = !term || text.includes(term) ? 'flex' : 'none';
                    });
                });
            }

            const openModalTriggers = document.querySelectorAll('[data-open-modal]');
            const modalOverlays = document.querySelectorAll('.modal-overlay');

            function closeModal(modal) {
                modal.classList.add('hidden');
            }

            function attachModalEvents(modal) {
                modal.addEventListener('click', event => {
                    if (event.target === modal || event.target.hasAttribute('data-modal-close')) {
                        closeModal(modal);
                    }
                });
            }

            openModalTriggers.forEach(trigger => {
                trigger.addEventListener('click', () => {
                    const targetId = trigger.dataset.openModal;
                    const modal = document.getElementById(targetId);
                    if (modal) {
                        modal.classList.remove('hidden');
                    }
                });
            });

            modalOverlays.forEach(modal => attachModalEvents(modal));
        });
    </script>
</body>
</html>
