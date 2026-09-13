<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

/** Tariefklasse: NHG of een LTV-staffel (bijv. "≤ 90% marktwaarde"). */
final class RiskClass extends Model
{
    use HasFactory;

    public $timestamps = false;

    protected $fillable = ['code', 'name', 'max_ltv', 'nhg', 'sort_order'];

    protected function casts(): array
    {
        return [
            'max_ltv' => 'float',
            'nhg' => 'boolean',
            'sort_order' => 'integer',
        ];
    }

    public function rates(): HasMany
    {
        return $this->hasMany(Rate::class);
    }
}
