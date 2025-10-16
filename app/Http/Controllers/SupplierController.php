<?php

namespace App\Http\Controllers;

use App\Models\Supplier;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class SupplierController extends Controller
{
    /**
     * Display a listing of the resources (READ operation).
     * This provides the supplier list for the Manager and the dropdowns.
     */
    public function index()
    {
        // Simply fetch all suppliers from the database
        $suppliers = Supplier::all();

        // Return the full list as JSON for the frontend to consume
        return response()->json($suppliers);
    }

    /**
     * Store a newly created supplier (CREATE operation).
     */
    public function store(Request $request)
    {
        $request->validate([
            'supplier_name' => 'required|string|max:100|unique:suppliers,supplier_name',
            'contact_person' => 'nullable|string|max:100',
            'phone' => 'nullable|string|max:20',
            'email' => 'nullable|email|max:100|unique:suppliers,email',
        ]);

        $supplier = Supplier::create($request->all());
        
        // TODO: Log the creation activity

        return response()->json(['message' => 'Supplier added successfully.', 'supplier' => $supplier], 201);
    }

    /**
     * Update the specified supplier (UPDATE operation).
     */
    public function update(Request $request, Supplier $supplier)
    {
        // Validation: Ignore the current supplier's name/email when checking for uniqueness
        $request->validate([
            'supplier_name' => ['required', 'string', 'max:100', Rule::unique('suppliers')->ignore($supplier->id)],
            'contact_person' => 'nullable|string|max:100',
            'phone' => 'nullable|string|max:20',
            'email' => ['nullable', 'email', 'max:100', Rule::unique('suppliers')->ignore($supplier->id)],
        ]);

        $supplier->update($request->all());

        // TODO: Log the update activity

        return response()->json(['message' => 'Supplier updated successfully.', 'supplier' => $supplier], 200);
    }

    /**
     * Remove the specified supplier (DELETE operation).
     */
    public function destroy(Supplier $supplier)
    {
        // Safety Check: Prevent deletion if any inventory item still links to this supplier
        if ($supplier->inventoryItems()->exists()) {
            return response()->json(['error' => 'Cannot delete supplier. Active inventory items are still linked.'], 409);
        }
        
        $supplier->delete();
        
        // TODO: Log the deletion activity

        return response()->json(['message' => 'Supplier deleted successfully.'], 200);
    }
}