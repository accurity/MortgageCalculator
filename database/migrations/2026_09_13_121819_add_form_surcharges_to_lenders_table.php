<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('lenders', function (Blueprint $table) {
            // Null = geen eigen opslag: annuïtair/lineair vallen dan terug op 0,
            // aflossingsvrij op de algemene opslag uit de rekeninstellingen.
            $table->decimal('surcharge_ann', 5, 2)->nullable()->after('delta');
            $table->decimal('surcharge_lin', 5, 2)->nullable()->after('surcharge_ann');
            $table->decimal('surcharge_av', 5, 2)->nullable()->after('surcharge_lin');
        });
    }

    public function down(): void
    {
        Schema::table('lenders', function (Blueprint $table) {
            $table->dropColumn(['surcharge_ann', 'surcharge_lin', 'surcharge_av']);
        });
    }
};
