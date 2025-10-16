<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\User;
use App\Models\Role;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Auth;

class UserController extends Controller
{
    /**
     * Display a listing of all users and roles (The Read operation).
     */
    public function index()
    {
        $users = User::with('role')->get(); 
        $roles = Role::all();
        return view('settings', compact('users', 'roles'));
    }

    /**
     * Handles the creation of a new Employee Account (The Create operation).
     */
    public function store(Request $request)
    {
        $request->validate([
            'first_name' => 'required|string|max:255',
            'last_name' => 'required|string|max:255',
            'email' => 'required|email|unique:users,email',
            'employee_id' => 'required|unique:users,employee_id',
            'role_id' => 'required|exists:roles,id', 
            'password' => 'required|confirmed|min:8', 
        ]);

        $hashedPassword = Hash::make($request->password);

        User::create([
            'first_name' => $request->first_name,
            'last_name' => $request->last_name,
            'email' => $request->email,
            'employee_id' => $request->employee_id,
            'starting_date' => now(), 
            'role_id' => $request->role_id,
            'password_hash' => $hashedPassword,
            'is_active' => true,
        ]);

        return redirect()->route('settings.index')->with('success', 'Employee account created successfully.');
    }
    
    /**
     * Update the specified user in storage (The Update operation).
     */
    public function update(Request $request, User $user) // NEW METHOD
    {
        // 1. Validation: Ignore current user's email/ID for uniqueness checks
        $request->validate([
            'first_name' => 'required|string|max:255',
            'last_name' => 'required|string|max:255',
            'email' => 'required|email|unique:users,email,'.$user->id,
            'employee_id' => 'required|unique:users,employee_id,'.$user->id,
            'role_id' => 'required|exists:roles,id',
            'password' => 'nullable|confirmed|min:8', // Password is optional
        ]);

        // 2. Prepare the update data array
        $updateData = [
            'first_name' => $request->first_name,
            'last_name' => $request->last_name,
            'email' => $request->email,
            'employee_id' => $request->employee_id,
            'role_id' => $request->role_id,
        ];

        // 3. Handle optional password update
        if ($request->filled('password')) {
            $updateData['password_hash'] = Hash::make($request->password);
        }

        // 4. Perform the update
        $user->update($updateData);

        return redirect()->route('settings.index')->with('success', $user->first_name . ' ' . $user->last_name . ' updated successfully.');
    }

    /**
     * Remove the specified user from storage (The Delete/Destroy operation).
     */
    public function destroy(User $user)
    {
        if (Auth::id() == $user->id) {
            return redirect()->route('settings.index')->with('error', 'Cannot delete your own active account.');
        }
        
        if ($user) {
            $user->delete();
            return redirect()->route('settings.index')->with('success', 'User account deleted successfully.');
        }

        return redirect()->route('settings.index')->with('error', 'User not found.');
    }
}