<?php

namespace App\Models;

use App\Traits\BelongsToGame;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ApiKey extends Model
{
    protected $hidden = ["secret"];

    /**
     * Generate a cryptographically secure random key.
     *
     * @param int $length
     * @return string
     */
    public static function generate(int $length = 32): string
    {
        $alphabet =
            "ABCDEFGHIJKLMNOPQRSTUVWXYZabcdefghijklmnopqrstuvwxyz0123456789";

        $buf = "";
        $alphabetSize = strlen($alphabet);
        for ($i = 0; $i < $length; ++$i) {
            $buf .= $alphabet[random_int(0, $alphabetSize - 1)];
        }

        return $buf;
    }

    public function scopeGame($query)
    {
        $query->where("game_id", Game::current()->id);
    }

    public function game(): BelongsTo
    {
        return $this->belongsTo(Game::class);
    }
}
