<?php
// app/Models/OrderItem.php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class OrderItem extends Model
{
    public $timestamps = false;
    protected $table = 'order_items';
    protected $fillable = [ /* ... */ ];

    // An OrderItem belongs to one Order (header)
    public function order(): BelongsTo
    {
        return $this->belongsTo(Order::class, 'order_id', 'id');
    }
    
    // CRITICAL: An OrderItem belongs to one InventoryItem (the product)
    public function inventoryItem(): BelongsTo 
    {
        return $this->belongsTo(InventoryItem::class, 'item_id', 'id');
    }
}