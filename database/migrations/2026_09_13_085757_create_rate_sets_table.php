<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('rate_sets', function (Blueprint $table) {
            $table->id();
            $table->foreignId('lender_id')->constrained()->cascadeOnDelete();
            // Actueel = de set die de calculator gebruikt; slechts één per verstrekker.
            // Oudere sets blijven staan als geschiedenis (is_current wordt false).
            $table->boolean('is_current')->default(false);
            $table->text('note')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('rate_sets');
    }
};
