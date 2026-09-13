<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

/** Losse sleutel/waarde-instellingen (NHG-grens, terugvalgemiddelden, ...). */
final class Setting extends Model
{
    public $timestamps = false;
    public $incrementing = false;

    protected $primaryKey = 'key';
    protected $keyType = 'string';
    protected $fillable = ['key', 'value'];

    public static function get(string $sleutel, ?string $default = null): ?string
    {
        return self::query()->find($sleutel)?->value ?? $default;
    }

    public static function set(string $sleutel, string $waarde): void
    {
        self::query()->updateOrCreate(['key' => $sleutel], ['value' => $waarde]);
    }
}
