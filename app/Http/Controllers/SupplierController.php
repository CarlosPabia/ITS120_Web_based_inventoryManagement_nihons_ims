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
        // Improvement: Added orderBy to ensure the list is always presented in a predictable, alphabetical order.
        return response()->json(Supplier::orderBy('supplier_name')->get());
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request)
    {
        // Improvement: Extracted validation rules into a private method to keep the code DRY (Don't Repeat Yourself),
        // making it more maintainable if rules need to change in the future.
        $validatedData = $request->validate($this->validationRules());
        
        $supplier = Supplier::create($validatedData);

        return response()->json(['message' => 'Supplier added successfully.', 'supplier' => $supplier], 201);
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, Supplier $supplier)
    {
        // Use the same validation rules, but adjust for the specific supplier being updated.
        $validatedData = $request->validate($this->validationRules($supplier->id));

        $supplier->update($validatedData);

        return response()->json(['message' => 'Supplier updated successfully.', 'supplier' => $supplier]);
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(Supplier $supplier)
    {
        // Edge Case Improvement: Added a crucial check to prevent deleting a supplier
        // if they are still linked to inventory items. This avoids a 500 server error
        // due to a foreign key constraint violation and provides a clear, user-friendly error message.
        if ($supplier->inventoryItems()->exists()) {
            return response()->json(['error' => 'Cannot delete supplier. It is currently linked to one or more inventory items.'], 409); // 409 Conflict is a more appropriate HTTP status code.
        }

        $supplier->delete();

        return response()->json(['message' => 'Supplier deleted successfully.']);
    }

    /**
     * Centralized validation rules for creating and updating suppliers.
     * This promotes code reuse and makes maintenance easier.
     */
    private function validationRules($id = null): array
    {
        return [
            // 'sometimes' means only validate if present (used for updates where name isn't sent).
            'supplier_name' => [$id ? 'sometimes' : 'required', 'string', 'max:100', Rule::unique('suppliers')->ignore($id)],
            'contact_person' => 'nullable|string|max:100',
            'phone' => 'nullable|string|max:20',
            'email' => ['nullable', 'email', 'max:100', Rule::unique('suppliers')->ignore($id)],
            'address' => 'nullable|string|max:255',
            'is_active' => 'sometimes|boolean',
        ];
    }
}