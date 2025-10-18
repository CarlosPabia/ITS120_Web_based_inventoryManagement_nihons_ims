<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use App\Models\Supplier;
use App\Models\StockLevel;
use App\Models\OrderItem;

class InventoryItem extends Model
{
    public $timestamps = false;
    protected $table = 'inventory_items';
    
    protected $fillable = [
        'item_name',
        'item_description',
        'supplier_id',
        'unit_of_measure',
        'default_unit_price',
    ];

    protected $casts = [
        'default_unit_price' => 'decimal:2',
    ];

    public function supplier(): BelongsTo
    {
        return $this->belongsTo(Supplier::class, 'supplier_id', 'id');
    }

    public function stockLevels(): HasMany
    {
        return $this->hasMany(StockLevel::class, 'item_id', 'id');
    }
    
    public function orderItems(): HasMany
    {
        return $this->hasMany(OrderItem::class, 'item_id', 'id');
    }

    public function suppliers(): BelongsToMany
    {
        return $this->belongsToMany(Supplier::class, 'supplier_catalog', 'inventory_item_id', 'supplier_id')
            ->withTimestamps();
    }
}
