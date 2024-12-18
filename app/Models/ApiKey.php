<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class ApiKey extends Model
{

    /**
     * Generate a cryptographically secure random key.
     *
     * @param int $length
     * @return string
     */
    public static function generate(int $length=32) : string
    {
        $alphabet = "ABCDEFGHIJKLMNOPQRSTUVWXYZabcdefghijklmnopqrstuvwxyz0123456789";

        $buf = '';
        $alphabetSize = strlen($alphabet);
        for ($i = 0; $i < $length; ++$i) {
            $buf .= $alphabet[random_int(0, $alphabetSize - 1)];
        }

        return $buf;
    }
}
