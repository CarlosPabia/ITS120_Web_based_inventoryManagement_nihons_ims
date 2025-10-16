<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Role extends Model
{
    public $timestamps = false; // Add this line
    protected $table = 'roles';

    // A Role has many Users (Manager has many staff)
    public function users(): HasMany
    {
        return $this->hasMany(User::class, 'role_id', 'id');
    }
}