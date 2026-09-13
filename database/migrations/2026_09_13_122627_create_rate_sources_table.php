<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('rate_sources', function (Blueprint $table) {
            $table->id();
            $table->foreignId('lender_id')->unique()->constrained()->cascadeOnDelete();
            $table->string('url', 2048);
            $table->string('table_selector');
            // Rol per kolom, op volgorde: periode, klasse, nhg, rente of negeren.
            $table->json('column_map');
            $table->boolean('scraping_allowed')->default(false);
            $table->text('note')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('rate_sources');
    }
};
