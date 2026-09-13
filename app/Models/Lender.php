<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

/**
 * Een hypotheekverstrekker voor het tarievenblok. Beheerd via /admin/lenders.
 */
final class Lender extends Model
{
    use HasFactory;

    protected $fillable = [
        'slug',
        'name',
        'description',
        'apply_url',
        'afm_number',
        'logo_path',
        'logo_url',
        'active',
        'sort_order',
        'delta',
    ];

    protected function casts(): array
    {
        return [
            'active' => 'boolean',
            'sort_order' => 'integer',
            'delta' => 'float',
        ];
    }

    /** Publieke URL van het logo, of null voor een plaatshouder. */
    public function logoUrl(): ?string
    {
        if ($this->logo_path !== null) {
            return asset($this->logo_path);
        }

        return $this->logo_url;
    }
}
