<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('risk_classes', function (Blueprint $table) {
            $table->boolean('active')->default(true)->after('sort_order');
        });
        Schema::table('fixed_periods', function (Blueprint $table) {
            $table->boolean('active')->default(true)->after('sort_order');
        });
    }

    public function down(): void
    {
        Schema::table('risk_classes', function (Blueprint $table) {
            $table->dropColumn('active');
        });
        Schema::table('fixed_periods', function (Blueprint $table) {
            $table->dropColumn('active');
        });
    }
};
