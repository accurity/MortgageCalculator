<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('tax_years', function (Blueprint $table) {
            $table->id();
            $table->unsignedSmallInteger('jaar')->unique();
            // Lijst van {tot: float|null, tarief: float}; de laatste schijf heeft tot=null.
            $table->json('schijven');
            $table->decimal('max_aftrektarief', 6, 4);
            $table->decimal('ewf_percentage', 6, 4);
            $table->decimal('ewf_grens', 12, 2);
            $table->decimal('hillen_aandeel', 6, 4);
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('tax_years');
    }
};
