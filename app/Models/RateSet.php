<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * Eén volledige tariefset van één verstrekker op één moment. Nieuwe sets
 * worden pas actueel (is_current) als ze in hun geheel zijn opgeslagen; de
 * vorige set blijft als geschiedenis bewaard. Dezelfde eenheid waarop de
 * scraper (#17) straks terugvalt bij een mislukte ophaalronde.
 */
final class RateSet extends Model
{
    use HasFactory;

    protected $fillable = ['lender_id', 'is_current', 'note'];

    protected function casts(): array
    {
        return [
            'is_current' => 'boolean',
        ];
    }

    public function lender(): BelongsTo
    {
        return $this->belongsTo(Lender::class);
    }

    public function rates(): HasMany
    {
        return $this->hasMany(Rate::class);
    }
}
