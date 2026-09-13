<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

/** Rentevaste periode in jaren (1, 5, 10, 20, 30) waarop een tarief kan gelden. */
final class FixedPeriod extends Model
{
    use HasFactory;

    public $timestamps = false;

    protected $fillable = ['years', 'sort_order', 'active'];

    protected function casts(): array
    {
        return [
            'years' => 'integer',
            'sort_order' => 'integer',
            'active' => 'boolean',
        ];
    }

    public function rates(): HasMany
    {
        return $this->hasMany(Rate::class);
    }
}
