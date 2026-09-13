<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/** Eén tarief binnen een tariefset: verstrekker × periode × tariefklasse. */
final class Rate extends Model
{
    use HasFactory;

    public $timestamps = false;

    protected $fillable = ['rate_set_id', 'fixed_period_id', 'risk_class_id', 'percentage'];

    protected function casts(): array
    {
        return [
            'percentage' => 'float',
        ];
    }

    public function rateSet(): BelongsTo
    {
        return $this->belongsTo(RateSet::class);
    }

    public function fixedPeriod(): BelongsTo
    {
        return $this->belongsTo(FixedPeriod::class);
    }

    public function riskClass(): BelongsTo
    {
        return $this->belongsTo(RiskClass::class);
    }
}
