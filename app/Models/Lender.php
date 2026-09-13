<?php

declare(strict_types=1);

namespace App\Models;

use App\Services\Mortgage\Constants;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;

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
        'surcharge_ann',
        'surcharge_lin',
        'surcharge_av',
    ];

    protected function casts(): array
    {
        return [
            'active' => 'boolean',
            'sort_order' => 'integer',
            'delta' => 'float',
            'surcharge_ann' => 'float',
            'surcharge_lin' => 'float',
            'surcharge_av' => 'float',
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

    public function rateSets(): HasMany
    {
        return $this->hasMany(RateSet::class);
    }

    public function rateSource(): HasOne
    {
        return $this->hasOne(RateSource::class);
    }

    /**
     * Renteopslag per hypotheekvorm. Zonder eigen opslag geldt 0 voor
     * annuïtair/lineair en de algemene aflossingsvrij-opslag uit de
     * rekeninstellingen voor aflossingsvrij - verstrekkers prijzen die vorm
     * meestal als opslag, niet als aparte tabel.
     *
     * @return array{ann: float, lin: float, av: float}
     */
    public function vormOpslagen(): array
    {
        return [
            'ann' => $this->surcharge_ann ?? 0.0,
            'lin' => $this->surcharge_lin ?? 0.0,
            'av' => $this->surcharge_av ?? Constants::ioSurcharge(),
        ];
    }
}
