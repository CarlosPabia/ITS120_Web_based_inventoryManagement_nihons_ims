<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use App\Models\InventoryItem;

class Supplier extends Model
{
    public $timestamps = false;
    protected $table = 'suppliers';
    
    /**
     * The attributes that are mass assignable.
     *
     * We've added 'address' and 'is_active' to this array.
     */
    protected $fillable = [
        'supplier_name', 
        'contact_person', 
        'phone', 
        'email',
        'address',
        'is_active'
    ];

    protected $casts = [
        'is_active' => 'boolean',
        'is_system' => 'boolean',
        'contact_person' => 'encrypted',
        'email' => 'encrypted',
        'phone' => 'encrypted',
        'address' => 'encrypted',
    ];

    // Scopes for differentiating internal vs regular suppliers
    public function scopeRegular($query)
    {
        return $query->where('is_system', false);
    }

    public function scopeInternal($query)
    {
        return $query->where('is_system', true);
    }

    /**
     * Get the inventory items for the supplier.
     */
    public function inventoryItems(): HasMany
    {
        return $this->hasMany(InventoryItem::class, 'supplier_id', 'id');
    }

    public function catalogItems(): BelongsToMany
    {
        return $this->belongsToMany(InventoryItem::class, 'supplier_catalog', 'supplier_id', 'inventory_item_id')
            ->withTimestamps();
    }
}
