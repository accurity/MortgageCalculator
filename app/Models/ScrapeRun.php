<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/** Eén scrape-poging (echt of dry-run) voor één verstrekker: het logboek achter de statuspagina. */
final class ScrapeRun extends Model
{
    protected $fillable = [
        'lender_id',
        'is_dry_run',
        'duration_ms',
        'rate_count',
        'status',
        'message',
        'raw_response',
    ];

    protected function casts(): array
    {
        return [
            'is_dry_run' => 'boolean',
            'duration_ms' => 'integer',
            'rate_count' => 'integer',
        ];
    }

    public function lender(): BelongsTo
    {
        return $this->belongsTo(Lender::class);
    }
}
