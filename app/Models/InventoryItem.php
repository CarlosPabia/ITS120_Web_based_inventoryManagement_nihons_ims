<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class InventoryItem extends Model
{
    public $timestamps = false;
    protected $table = 'inventory_items';
    
    protected $fillable = [
        'item_name', 'item_description', 'supplier_id', 'unit_of_measure'
    ];

    // An InventoryItem belongs to one Supplier
    public function supplier(): BelongsTo
    {
        return $this->belongsTo(Supplier::class, 'supplier_id', 'id');
    }

    // An InventoryItem has many StockLevels (batches/expiries)
    public function stockLevels(): HasMany
    {
        return $this->hasMany(StockLevel::class, 'item_id', 'id');
    }
    
    // An InventoryItem appears in many OrderItems
    public function orderItems(): HasMany
    {
        return $this->hasMany(OrderItem::class, 'item_id', 'id');
    }
}