<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('risk_classes', function (Blueprint $table) {
            $table->id();
            $table->string('code', 20)->unique();
            $table->string('name');
            // Null voor de NHG-klasse: die wordt bepaald door de NHG-grens, niet door de LTV.
            $table->decimal('max_ltv', 5, 2)->nullable();
            $table->boolean('nhg')->default(false);
            $table->unsignedInteger('sort_order')->default(0);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('risk_classes');
    }
};
