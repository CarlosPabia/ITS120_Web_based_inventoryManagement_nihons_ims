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

    public function orderItems(): HasMany 
    {
        return $this->hasMany(OrderItem::class, 'order_id', 'id');
    }
    
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by_user_id', 'id');
    }
    
    public function supplier(): BelongsTo
    {
        return $this->belongsTo(Supplier::class, 'supplier_id', 'id');
    }
}