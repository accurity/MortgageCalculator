<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('lenders', function (Blueprint $table) {
            // Voorkomt meer dan één e-mail per dag per verstrekker bij aanhoudend verouderde tarieven.
            $table->timestamp('last_stale_alert_at')->nullable()->after('surcharge_av');
        });
    }

    public function down(): void
    {
        Schema::table('lenders', function (Blueprint $table) {
            $table->dropColumn('last_stale_alert_at');
        });
    }
};
