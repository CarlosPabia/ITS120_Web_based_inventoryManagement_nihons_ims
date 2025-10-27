<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Illuminate\Database\Eloquent\Relations\BelongsTo; // For the Role relationship
use App\Casts\EncryptedFallback;

class User extends Authenticatable
{
    use HasFactory, Notifiable;

    // 1. Set Custom Table and Primary Key
    public $timestamps = false; // <-- ADD THIS LINE
    protected $table = 'users';
    
    // 2. Define Mass Assignable Fields (Your custom columns)
    // These fields can be safely updated by the manager when creating accounts.
    protected $fillable = [
        'first_name',
        'last_name',
        'email',
        'employee_id',
        'starting_date',
        'role_id',
        'password_hash', // Your password field
        'is_active',
    ];

    // 3. Define Hidden Fields
    // Prevents sensitive fields from being automatically included in JSON/array representations.
    protected $hidden = [
        'password_hash', // Use 'password_hash' instead of Laravel's default 'password'
        'remember_token',
    ];

    // 4. Override Authentication Password Field
    // This tells Laravel's login system to use the 'password_hash' column for verification.
    public function getAuthPassword()
    {
        return $this->password_hash;
    }

    // 5. Attribute casts (PII encryption with legacy fallback)
    protected function casts(): array
    {
        return [
            'first_name' => EncryptedFallback::class,
            'last_name'  => EncryptedFallback::class,
            // Keep email and employee_id plaintext for login and unique lookups
            'email_verified_at' => 'datetime',
            'is_active' => 'boolean',
        ];
    }
    
    // 6. Define Role Relationship (Essential for RBAC)
    // A User belongs to one Role (Manager or Employee).
    public function role(): BelongsTo
    {
        return $this->belongsTo(Role::class);
    }
}
