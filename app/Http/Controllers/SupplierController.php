<?php

namespace App\Http\Controllers;

use App\Models\Supplier;
use App\Models\InventoryItem;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use App\Models\ActivityLog;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

class SupplierController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index()
    {
        $suppliers = Supplier::with([
                'catalogItems:id,item_name,item_description,unit_of_measure,default_unit_price',
                'catalogItems.stockLevels:id,item_id,quantity,minimum_stock_threshold,expiry_date',
            ])
            ->orderBy('supplier_name')
            ->get()
            ->map(function (Supplier $supplier) {
                return [
                    'id' => $supplier->id,
                    'supplier_name' => $supplier->supplier_name,
                    'contact_person' => $supplier->contact_person,
                    'phone' => $supplier->phone,
                    'email' => $supplier->email,
                    'address' => $supplier->address,
                    'is_active' => $supplier->is_active,
                    'is_system' => $supplier->is_system,
                    'items' => $supplier->catalogItems->map(function ($item) {
                        $totalQuantity = $item->stockLevels->sum('quantity');
                        $minimumThreshold = $item->stockLevels->max('minimum_stock_threshold');
                        $description = $item->item_description !== null ? trim($item->item_description) : null;

                        return [
                            'id' => $item->id,
                            'name' => $item->item_name,
                            'unit' => $item->unit_of_measure,
                            'description' => $description === '' ? null : $description,
                            'initial_quantity' => (float) $totalQuantity,
                            'minimum_stock_threshold' => is_null($minimumThreshold) ? null : (float) $minimumThreshold,
                            'default_price' => (float) $item->default_unit_price,
                        ];
                    })->values(),
                ];
            });

        return response()->json($suppliers);
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request)
    {
        $validatedData = $request->validate($this->validationRules());

        return DB::transaction(function () use ($validatedData) {
            $existingItems = $validatedData['items'] ?? [];
            $newItems = $validatedData['new_items'] ?? [];
            $existingUpdates = $validatedData['existing_items'] ?? [];
            unset($validatedData['items'], $validatedData['new_items'], $validatedData['existing_items']);

            $supplier = Supplier::create($validatedData);
            $createdItemIds = $this->createNewCatalogItems($supplier, $newItems);
            $syncIds = array_unique(array_merge($existingItems, $createdItemIds));
            $supplier->catalogItems()->sync($syncIds);
            $this->applyExistingItemUpdates($supplier, $existingUpdates, $syncIds);

            ActivityLog::create([
                'user_id' => Auth::id(),
                'activity_type' => 'Supplier Created',
                'details' => 'Supplier #' . $supplier->id . ' (' . $supplier->supplier_name . ') added to directory.',
            ]);

            return response()->json(['message' => 'Supplier added successfully.', 'supplier' => $supplier], 201);
        });
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, Supplier $supplier)
    {
        if ($supplier->is_system && $request->has('is_active') && !$request->boolean('is_active')) {
            return response()->json(['error' => 'System suppliers cannot be deactivated.'], 403);
        }

        $validatedData = $request->validate($this->validationRules($supplier->id));

        return DB::transaction(function () use ($supplier, $validatedData) {
            $existingItems = $validatedData['items'] ?? [];
            $newItems = $validatedData['new_items'] ?? [];
            $existingUpdates = $validatedData['existing_items'] ?? [];
            unset($validatedData['items'], $validatedData['new_items'], $validatedData['existing_items']);

            $supplier->update($validatedData);

            $createdItemIds = $this->createNewCatalogItems($supplier, $newItems);
            $syncIds = array_unique(array_merge($existingItems, $createdItemIds));
            $supplier->catalogItems()->sync($syncIds);
            $this->applyExistingItemUpdates($supplier, $existingUpdates, $syncIds);

            ActivityLog::create([
                'user_id' => Auth::id(),
                'activity_type' => 'Supplier Updated',
                'details' => 'Supplier #' . $supplier->id . ' updated. Status: ' . ($supplier->is_active ? 'active' : 'inactive') . '.',
            ]);

            return response()->json(['message' => 'Supplier updated successfully.', 'supplier' => $supplier]);
        });
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(Supplier $supplier)
    {
        if ($supplier->is_system) {
            return response()->json(['error' => 'System suppliers cannot be deleted.'], 403);
        }

        $inventoryItems = $supplier->inventoryItems()->with(['stockLevels', 'orderItems'])->get();

        foreach ($inventoryItems as $item) {
            if ($item->orderItems()->exists()) {
                return response()->json([
                    'error' => 'Cannot delete supplier. One or more catalog items are used in order history.'
                ], 409);
            }
        }

        DB::transaction(function () use ($supplier, $inventoryItems) {
            foreach ($inventoryItems as $item) {
                $item->stockLevels()->delete();
                $item->delete();
            }

            $supplier->catalogItems()->detach();
            $supplier->delete();
        });

        ActivityLog::create([
            'user_id' => Auth::id(),
            'activity_type' => 'Supplier Deleted',
            'details' => 'Supplier #' . $supplier->id . ' (' . $supplier->supplier_name . ') removed from directory.',
        ]);

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
            'items' => ['sometimes', 'array'],
            'items.*' => ['integer', 'exists:inventory_items,id'],
            'new_items' => ['sometimes', 'array'],
            'new_items.*.name' => ['required', 'string', 'max:150'],
            'new_items.*.unit' => ['nullable', 'string', 'max:50'],
            'new_items.*.description' => ['nullable', 'string'],
            'new_items.*.initial_quantity' => ['nullable', 'numeric', 'min:0'],
            'new_items.*.minimum_stock_threshold' => ['nullable', 'numeric', 'min:0'],
            'new_items.*.price' => ['required', 'numeric', 'min:0'],
            'existing_items' => ['sometimes', 'array'],
            'existing_items.*.id' => ['required', 'integer', 'exists:inventory_items,id'],
            'existing_items.*.price' => ['required', 'numeric', 'min:0'],
        ];
    }

    private function createNewCatalogItems(Supplier $supplier, array $newItems): array
    {
        if (empty($newItems)) {
            return [];
        }

        return collect($newItems)->map(function (array $item) use ($supplier) {
            $name = isset($item['name']) ? trim($item['name']) : '';
            $unit = isset($item['unit']) ? trim($item['unit']) : '';
            $description = isset($item['description']) ? trim($item['description']) : '';
            $initialQuantity = isset($item['initial_quantity']) ? (float) $item['initial_quantity'] : 0;
            $minimumThreshold = isset($item['minimum_stock_threshold']) ? (float) $item['minimum_stock_threshold'] : 0;
            $price = isset($item['price']) ? (float) $item['price'] : 0;

            $inventoryItem = InventoryItem::create([
                'item_name' => $name,
                'item_description' => $description !== '' ? $description : null,
                'supplier_id' => $supplier->id,
                'unit_of_measure' => $unit !== '' ? $unit : null,
                'default_unit_price' => max(0, round($price, 2)),
            ]);

            $inventoryItem->stockLevels()->create([
                'quantity' => max(0, $initialQuantity),
                'minimum_stock_threshold' => max(0, $minimumThreshold),
                'expiry_date' => null,
            ]);

            return $inventoryItem->id;
        })->all();
    }

    private function applyExistingItemUpdates(Supplier $supplier, array $existingUpdates, array $allowedIds = []): void
    {
        if (empty($existingUpdates)) {
            return;
        }

        $allowedLookup = !empty($allowedIds) ? array_flip($allowedIds) : null;

        $ids = collect($existingUpdates)
            ->pluck('id')
            ->map(function ($id) use ($allowedLookup) {
                $intId = (int) $id;
                if ($intId <= 0) {
                    return null;
                }

                if ($allowedLookup !== null && !isset($allowedLookup[$intId])) {
                    return null;
                }

                return $intId;
            })
            ->filter()
            ->unique()
            ->values()
            ->all();

        if (empty($ids)) {
            return;
        }

        $items = InventoryItem::whereIn('id', $ids)->get()->keyBy('id');

        foreach ($existingUpdates as $update) {
            $itemId = isset($update['id']) ? (int) $update['id'] : 0;
            if (!$itemId || ($allowedLookup !== null && !isset($allowedLookup[$itemId]))) {
                continue;
            }

            $inventoryItem = $items->get($itemId);
            if (!$inventoryItem) {
                continue;
            }

            $shouldSave = false;

            if (array_key_exists('price', $update)) {
                $price = (float) $update['price'];
                if ($price < 0) {
                    $price = 0;
                }
                $price = round($price, 2);

                if ((float) $inventoryItem->default_unit_price !== $price) {
                    $inventoryItem->default_unit_price = $price;
                    $shouldSave = true;
                }
            }

            if ($shouldSave) {
                $inventoryItem->save();
            }
        }
    }
}
