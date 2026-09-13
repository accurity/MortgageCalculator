<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/** Scrape-bron van één verstrekker: een HTML-tabel met rentes. Beheerd via /admin/lenders/{lender}/source. */
final class RateSource extends Model
{
    protected $fillable = ['lender_id', 'url', 'table_selector', 'column_map', 'scraping_allowed', 'note'];

    protected function casts(): array
    {
        return [
            'column_map' => 'array',
            'scraping_allowed' => 'boolean',
        ];
    }

    public function lender(): BelongsTo
    {
        return $this->belongsTo(Lender::class);
    }
}
