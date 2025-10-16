<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use App\Models\InventoryItem;

class StockLevel extends Model
{
    public $timestamps = false;
    protected $table = 'stock_levels';

    protected $fillable = [
        'item_id', 'quantity', 'expiry_date', 'minimum_stock_threshold'
    ];

    // A StockLevel batch belongs to one InventoryItem
    public function inventoryItem(): BelongsTo
    {
        return $this->belongsTo(InventoryItem::class, 'item_id', 'id');
    }
}