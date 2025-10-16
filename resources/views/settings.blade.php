<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Settings - User Management</title>
    <style>
        /* Minimal Styles for Layout (Combined from previous steps) */
        body { font-family: sans-serif; margin: 0; background-color: #f4f4f4; }
        .dashboard-layout { display: grid; grid-template-columns: 200px 1fr; min-height: 100vh; }
        .sidebar { background-color: #333; color: white; padding: 20px 0; position: fixed; height: 100%; width: 200px; }
        .sidebar-logo { text-align: center; margin-bottom: 30px; }
        .sidebar-nav-link { display: block; padding: 10px 20px; text-decoration: none; color: white; }
        .sidebar-nav-link:hover, .sidebar-nav-link.active { background-color: #a03c3c; }
        .top-navbar { grid-column: 2 / 3; background: white; padding: 10px 20px; box-shadow: 0 1px 3px rgba(0, 0, 0, 0.1); display: flex; justify-content: flex-end; align-items: center; height: 40px; }
        .main-content { grid-column: 2 / 3; padding: 20px; margin-top: 60px; }
        .panel-title { color: #a03c3c; margin-top: 0; border-bottom: 1px solid #eee; padding-bottom: 10px; margin-bottom: 20px; }
        .panel { background: white; padding: 20px; border-radius: 6px; box-shadow: 0 2px 4px rgba(0, 0, 0, 0.05); }

        /* Modal Styles */
        .modal-overlay { 
            position: fixed; 
            top: 0; 
            left: 0; 
            width: 100%; 
            height: 100%; 
            background: rgba(0,0,0,0.6); 
            z-index: 100;
            display: flex;
            justify-content: center;
            align-items: center;
        }
        .modal-content {
            background: white; 
            padding: 30px; 
            width: 450px; 
            max-width: 90%;
            border-radius: 8px; 
        }
    </style>
</head>
<body>
    <div class="dashboard-layout">
        
        <!-- SIDEBAR -->
        <aside class="sidebar">
            <div class="sidebar-logo">
                <h2 style="margin: 0;">NIHON CAFE</h2>
            </div>
            <ul style="list-style: none; padding: 0;">
                <li><a href="{{ route('dashboard') }}" class="sidebar-nav-link">Dashboard</a></li>
                <li><a href="{{ route('orders.index') }}" class="sidebar-nav-link">Orders</a></li>
                <li><a href="{{ route('inventory.index') }}" class="sidebar-nav-link">Inventory</a></li>
                <li><a href="{{ route('suppliers.index') }}" class="sidebar-nav-link">Suppliers</a></li>
                <li><a href="{{ route('reports.index') }}" class="sidebar-nav-link">Reports</a></li>
                <li><a href="{{ route('settings.index') }}" class="sidebar-nav-link active">Settings</a></li>
            </ul>
        </aside>
        
        <!-- TOP HEADER -->
        <header class="top-navbar">
            <div class="user-info">
                @auth
                    {{ Auth::user()->first_name }} {{ Auth::user()->last_name }} ({{ Auth::user()->role->role_name ?? 'N/A' }})
                @endauth
            </div>
            
            <form method="POST" action="{{ route('logout') }}" style="display:inline;">
                @csrf
                <button type="submit" class="logout-btn">Logout</button>
            </form>
        </header>

        <!-- MAIN CONTENT AREA -->
        <main class="main-content">

            <h1 class="panel-title">Settings</h1>

            {{-- Display Success and Error Messages --}}
            @if(session('success'))
                <div style="background-color: #d4edda; color: #155724; padding: 10px; border-radius: 4px; margin-bottom: 15px;">
                    {{ session('success') }}
                </div>
            @endif
            @if(session('error'))
                <div style="background-color: #f8d7da; color: #721c24; padding: 10px; border-radius: 4px; margin-bottom: 15px;">
                    {{ session('error') }}
                </div>
            @endif
            @if ($errors->any())
                <div style="background-color: #f8d7da; color: #721c24; padding: 10px; border-radius: 4px; margin-bottom: 15px;">
                    Error: {{ $errors->first() }}
                </div>
            @endif

            <!-- Main Settings Menu Grid -->
            <div style="display: grid; grid-template-columns: repeat(4, 1fr); gap: 20px; margin-bottom: 30px;">
                <div style="background: white; padding: 20px; text-align: center; border-radius: 6px; cursor: pointer; box-shadow: 0 2px 4px rgba(0,0,0,0.05);" onclick="document.getElementById('user-management-panel').style.display='block'; document.getElementById('access-control-panel').style.display='none';">
                    <h3>👥 User Management</h3>
                </div>
                <div style="background: white; padding: 20px; text-align: center; border-radius: 6px; cursor: pointer; box-shadow: 0 2px 4px rgba(0,0,0,0.05);" onclick="document.getElementById('access-control-panel').style.display='block'; document.getElementById('user-management-panel').style.display='none';">
                    <h3>🔒 Access Control (RBAC)</h3>
                </div>
                <div style="background: white; padding: 20px; text-align: center; border-radius: 6px; cursor: pointer; box-shadow: 0 2px 4px rgba(0,0,0,0.05);">
                    <h3>💾 Backup & Restore</h3>
                </div>
                <div style="background: white; padding: 20px; text-align: center; border-radius: 6px; cursor: pointer; box-shadow: 0 2px 4px rgba(0,0,0,0.05);">
                    <h3>🔔 Notifications</h3>
                </div>
            </div>

            <!-- --- 1. USER MANAGEMENT PANEL (Manager CRUD) --- -->
            <div id="user-management-panel" class="panel" style="background: #fff; padding: 20px;">
                <h2 style="color: #a03c3c; border-bottom: 1px solid #eee; padding-bottom: 10px;">User Management</h2>

                <!-- CREATE ACCOUNT FORM -->
                <h3 style="margin-top: 20px; font-size: 18px;">Create Employee Account</h3>
                <form method="POST" action="{{ route('user.store') }}" style="display: grid; grid-template-columns: 1fr 1fr; gap: 15px; background: #f9f9f9; padding: 20px; border-radius: 4px; margin-bottom: 20px;">
                    @csrf
                    
                    <input type="text" name="first_name" placeholder="First Name" required style="padding: 8px;">
                    <input type="text" name="last_name" placeholder="Last Name" required style="padding: 8px;">

                    <input type="text" name="employee_id" placeholder="Employee ID (e.g., NIH004)" required style="padding: 8px;">
                    <input type="email" name="email" placeholder="Email" required style="padding: 8px;">

                    <select name="role_id" required style="padding: 8px;">
                        <option value="">Select Role</option>
                        {{-- DYNAMICALLY POPULATE ROLES --}}
                        @foreach ($roles as $role)
                            <option value="{{ $role->id }}" {{ $role->role_name === 'Employee' ? 'selected' : '' }}>
                                {{ $role->role_name }}
                            </option>
                        @endforeach
                    </select>
                    <input type="text" placeholder="Starting Date (Optional)" name="starting_date" style="padding: 8px;">

                    <input type="password" name="password" placeholder="Password (Min 8 chars)" required style="padding: 8px;">
                    <input type="password" name="password_confirmation" placeholder="Confirm Password" required style="padding: 8px;">
                    

                    <button type="submit" style="grid-column: 2 / 3; background-color: green; color: white; padding: 10px 15px; border: none; border-radius: 4px; cursor: pointer;">
                        Create Account
                    </button>
                </form>

                <!-- USER LIST TABLE (READ OPERATION) -->
                <h3 style="margin-top: 0; font-size: 18px;">Existing Staff Accounts</h3>
                <table style="width: 100%; border-collapse: collapse; text-align: left; background: white;">
                    <thead>
                        <tr style="background-color: #f0f0f0;">
                            <th style="padding: 10px;">Name</th>
                            <th style="padding: 10px;">Employee ID</th>
                            <th style="padding: 10px;">Email</th>
                            <th style="padding: 10px;">Role</th>
                            <th style="padding: 10px;">Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        {{-- DYNAMICALLY POPULATE USERS --}}
                        @foreach ($users as $user)
                            <tr>
                                <td style="padding: 10px; border-bottom: 1px solid #eee;">{{ $user->first_name }} {{ $user->last_name }}</td>
                                <td style="padding: 10px; border-bottom: 1px solid #eee;">{{ $user->employee_id }}</td>
                                <td style="padding: 10px; border-bottom: 1px solid #eee;">{{ $user->email }}</td>
                                <td style="padding: 10px; border-bottom: 1px solid #eee;">
                                    <span style="color: {{ $user->role->role_name === 'Manager' ? 'darkgreen' : 'darkblue' }};">
                                        {{ $user->role->role_name ?? 'N/A' }}
                                    </span>
                                </td>
                                <td style="padding: 10px; border-bottom: 1px solid #eee;">
                                    <button 
                                        onclick="document.getElementById('edit-form-{{ $user->id }}').style.display='flex';"
                                        style="margin-right: 5px;">
                                        Edit
                                    </button>
                                    
                                    {{-- DELETE BUTTON --}}
                                    <form action="{{ route('user.destroy', $user) }}" method="POST" style="display:inline;">
                                        @csrf
                                        @method('DELETE')
                                        <button type="submit" style="color: red;" onclick="return confirm('Are you sure you want to delete {{ $user->first_name }}?');">
                                            Delete
                                        </button>
                                    </form>
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>


            <!-- --- 2. ACCESS CONTROL PANEL (RBAC Visualization) --- -->
            <div id="access-control-panel" class="panel" style="margin-top: 30px; display: none;">
                <h2 style="color: #a03c3c; border-bottom: 1px solid #eee; padding-bottom: 10px;">Access Control (RBAC)</h2>
                <p>Define which modules each user role can access. (This view is static placeholder for now)</p>
                
                <table style="width: 100%; border-collapse: collapse;">
                    <thead>
                        <tr style="background-color: #f0f0f0;">
                            <th style="padding: 10px; text-align: left;">Role</th>
                            <th style="padding: 10px; text-align: center;">Inventory</th>
                            <th style="padding: 10px; text-align: center;">Orders</th>
                            <th style="padding: 10px; text-align: center; color: red;">Suppliers</th>
                            <th style="padding: 10px; text-align: center; color: red;">Reports</th>
                        </tr>
                    </thead>
                    <tbody>
                        {{-- Manager Row --}}
                        <tr>
                            <td style="padding: 10px; border-bottom: 1px solid #eee;">Manager</td>
                            <td style="padding: 10px; text-align: center; border-bottom: 1px solid #eee;"><input type="checkbox" checked disabled></td>
                            <td style="padding: 10px; text-align: center; border-bottom: 1px solid #eee;"><input type="checkbox" checked disabled></td>
                            <td style="padding: 10px; text-align: center; border-bottom: 1px solid #eee;"><input type="checkbox" checked disabled></td>
                            <td style="padding: 10px; text-align: center; border-bottom: 1px solid #eee;"><input type="checkbox" checked disabled></td>
                        </tr>
                        {{-- Employee Row (Read-Only/Limited Access) --}}
                        <tr>
                            <td style="padding: 10px; border-bottom: 1px solid #eee;">Employee</td>
                            <td style="padding: 10px; text-align: center; border-bottom: 1px solid #eee;"><input type="checkbox" checked disabled></td>
                            <td style="padding: 10px; text-align: center; border-bottom: 1px solid #eee;"><input type="checkbox" checked disabled></td>
                            <td style="padding: 10px; text-align: center; border-bottom: 1px solid #eee;"><input type="checkbox" disabled></td>
                            <td style="padding: 10px; text-align: center; border-bottom: 1px solid #eee;"><input type="checkbox" disabled></td>
                        </tr>
                    </tbody>
                </table>
                <button style="margin-top: 15px; padding: 10px 20px; background-color: #a03c3c; color: white; border: none; border-radius: 4px;">Save Permissions</button>
            </div>
        </main>
    </div>
    
    {{-- --- HIDDEN EDIT MODALS (ONE PER USER) --- --}}
    {{-- This section must be outside the main content to be positioned correctly --}}
    @foreach ($users as $user)
        <div id="edit-form-{{ $user->id }}" class="modal-overlay" style="display: none;">
            <div class="modal-content">
                <h3 style="margin-top: 0; border-bottom: 1px solid #eee; padding-bottom: 10px;">Edit Account: {{ $user->first_name }} {{ $user->last_name }}</h3>
                
                <form method="POST" action="{{ route('user.update', $user) }}" style="display: grid; gap: 10px;">
                    @csrf
                    @method('PATCH') {{-- REQUIRED for the PATCH route method --}}

                    <label style="font-weight: bold;">Basic Details</label>
                    <input type="text" name="first_name" value="{{ $user->first_name }}" required style="padding: 8px;">
                    <input type="text" name="last_name" value="{{ $user->last_name }}" required style="padding: 8px;">

                    <input type="text" name="employee_id" value="{{ $user->employee_id }}" required style="padding: 8px;">
                    <input type="email" name="email" value="{{ $user->email }}" required style="padding: 8px;">

                    <label style="font-weight: bold; margin-top: 10px;">User Role</label>
                    <select name="role_id" required style="padding: 8px;">
                        @foreach ($roles as $role)
                            <option value="{{ $role->id }}" {{ $user->role_id === $role->id ? 'selected' : '' }}>
                                {{ $role->role_name }}
                            </option>
                        @endforeach
                    </select>

                    <hr style="margin: 15px 0;">

                    <label style="font-weight: bold; color: #a03c3c;">Change Password (Optional)</label>
                    <input type="password" name="password" placeholder="New Password" style="padding: 8px;">
                    <input type="password" name="password_confirmation" placeholder="Confirm New Password" style="padding: 8px;">
                    
                    <div style="display: flex; justify-content: space-between; margin-top: 20px;">
                        <button type="button" onclick="document.getElementById('edit-form-{{ $user->id }}').style.display='none';" style="padding: 10px; background: #ccc; border: none; border-radius: 4px; cursor: pointer;">Cancel</button>
                        <button type="submit" style="padding: 10px; background-color: green; color: white; border: none; border-radius: 4px; cursor: pointer;">Save Changes</button>
                    </div>
                </form>
            </div>
        </div>
    @endforeach
</body>
</html>