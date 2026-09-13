<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('scrape_runs', function (Blueprint $table) {
            $table->id();
            $table->foreignId('lender_id')->constrained()->cascadeOnDelete();
            $table->boolean('is_dry_run')->default(false);
            $table->unsignedInteger('duration_ms')->default(0);
            $table->unsignedInteger('rate_count')->default(0);
            $table->string('status', 10); // ok | failed
            $table->text('message');
            // Alleen bewaard bij een mislukte of lege run; scheelt ruimte en
            // is verder niet nodig zodra een run gewoon slaagde.
            $table->longText('raw_response')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('scrape_runs');
    }
};
