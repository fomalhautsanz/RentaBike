<?php

namespace App\Helpers;

class HashHelper
{
    public static function email(string $email): string
    {
        return hash_hmac(
            'sha256',
            strtolower(trim($email)),
            config('app.email_hash_key')
        );
    }
}