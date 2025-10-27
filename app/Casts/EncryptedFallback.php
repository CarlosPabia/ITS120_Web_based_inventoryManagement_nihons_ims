<?php

namespace App\Casts;

use Illuminate\Contracts\Database\Eloquent\CastsAttributes;
use Illuminate\Support\Facades\Crypt;
use Illuminate\Contracts\Encryption\DecryptException;

class EncryptedFallback implements CastsAttributes
{
    public function get($model, string $key, $value, array $attributes)
    {
        if ($value === null) {
            return null;
        }
        try {
            return Crypt::decryptString($value);
        } catch (DecryptException $e) {
            // Return as-is when legacy plaintext exists
            return $value;
        } catch (\Throwable $e) {
            return $value;
        }
    }

    public function set($model, string $key, $value, array $attributes)
    {
        if ($value === null || $value === '') {
            return $value;
        }
        // Avoid double-encrypting values that already look encrypted
        try {
            // If decrypt works, assume it is already encrypted
            Crypt::decryptString($value);
            return $value;
        } catch (\Throwable $e) {
            return Crypt::encryptString($value);
        }
    }
}

