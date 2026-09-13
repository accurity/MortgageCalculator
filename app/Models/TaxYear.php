<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

/**
 * Fiscale parameters voor box 1 / eigen woning voor één belastingjaar.
 * Gelezen door App\Services\Mortgage\Domain\TaxRules, beheerd via de admin.
 */
final class TaxYear extends Model
{
    use HasFactory;

    protected $fillable = [
        'jaar',
        'schijven',
        'max_aftrektarief',
        'ewf_percentage',
        'ewf_grens',
        'hillen_aandeel',
    ];

    protected function casts(): array
    {
        return [
            'jaar' => 'integer',
            'schijven' => 'array',
            'max_aftrektarief' => 'float',
            'ewf_percentage' => 'float',
            'ewf_grens' => 'float',
            'hillen_aandeel' => 'float',
        ];
    }
}
