<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('rates', function (Blueprint $table) {
            $table->id();
            $table->foreignId('rate_set_id')->constrained()->cascadeOnDelete();
            $table->foreignId('fixed_period_id')->constrained()->cascadeOnDelete();
            $table->foreignId('risk_class_id')->constrained()->cascadeOnDelete();
            $table->decimal('percentage', 5, 2);
            $table->unique(['rate_set_id', 'fixed_period_id', 'risk_class_id'], 'rates_uniek_per_set');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('rates');
    }
};
