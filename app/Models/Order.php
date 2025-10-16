<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Order extends Model
{
    public $timestamps = false;
    protected $table = 'orders';
    protected $fillable = ['order_type', 'supplier_id', 'order_status', 'order_date', 'created_by_user_id'];

    // CRITICAL: An Order has many OrderItems (the lines in the transaction)
    public function orderItems(): HasMany 
    {
        return $this->hasMany(OrderItem::class, 'order_id', 'id');
    }
    
    // An Order belongs to one User (who created the order)
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by_user_id', 'id');
    }
    
    // An Order belongs to a Supplier (if order_type is 'Supplier')
    public function supplier(): BelongsTo
    {
        return $this->belongsTo(Supplier::class, 'supplier_id', 'id');
    }
}