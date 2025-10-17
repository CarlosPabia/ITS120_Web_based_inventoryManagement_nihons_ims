<?php

namespace App\Http\Controllers;

use App\Models\Supplier;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class SupplierController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index()
    {
        return response()->json(Supplier::all());
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request)
    {
        $request->validate([
            'supplier_name' => 'required|string|max:100|unique:suppliers',
            'contact_person' => 'nullable|string|max:100',
            'phone' => 'nullable|string|max:20',
            'email' => 'nullable|email|max:100|unique:suppliers',
            'address' => 'nullable|string|max:255',
        ]);

        $supplier = Supplier::create($request->all());

        return response()->json(['message' => 'Supplier added successfully.', 'supplier' => $supplier], 201);
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, Supplier $supplier)
    {
        $request->validate([
            // --- THIS IS THE FIX ---
            // The supplier name is made optional, as it's not edited in the modal.
            // We still validate it if it IS sent, ensuring it remains unique.
            'supplier_name' => ['sometimes', 'string', 'max:100', Rule::unique('suppliers')->ignore($supplier->id)],
            
            'contact_person' => 'nullable|string|max:100',
            'phone' => 'nullable|string|max:20',
            'email' => ['nullable', 'email', 'max:100', Rule::unique('suppliers')->ignore($supplier->id)],
            'address' => 'nullable|string|max:255',
            'is_active' => 'required|boolean',
        ]);

        $supplier->update($request->all());

        return response()->json(['message' => 'Supplier updated successfully.']);
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(Supplier $supplier)
    {
        // Prevent deletion if the supplier is linked to any inventory items.
        if ($supplier->inventoryItems()->exists()) {
            return response()->json(['error' => 'Cannot delete supplier. It is currently linked to one or more inventory items.'], 409);
        }

        $supplier->delete();

        return response()->json(['message' => 'Supplier deleted successfully.']);
    }
}
