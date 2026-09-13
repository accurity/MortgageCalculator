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
        Schema::create('lenders', function (Blueprint $table) {
            $table->id();
            // Vast en onveranderlijk: hier koppelen scrapers en imports straks aan.
            $table->string('slug', 60)->unique();
            $table->string('name');
            $table->text('description')->nullable();
            $table->string('apply_url')->nullable();
            $table->string('afm_number', 50)->nullable();
            $table->string('logo_path')->nullable();
            $table->string('logo_url')->nullable();
            $table->boolean('active')->default(true);
            $table->integer('sort_order')->default(0);
            // Tijdelijke tariefopslag t.o.v. de marktrente, in procentpunten,
            // totdat een echte tariefset (#7) dit vervangt.
            $table->decimal('delta', 5, 2)->default(0);
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('lenders');
    }
};
