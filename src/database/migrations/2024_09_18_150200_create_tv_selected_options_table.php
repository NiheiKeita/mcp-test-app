<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('tv_selected_options', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('tv_id')->constrained('tvs')->cascadeOnDelete();
            $table->foreignId('tv_option_id')->constrained('tv_options')->cascadeOnDelete();
            $table->unsignedTinyInteger('quantity');
            $table->timestamps();

            $table->unique(['tv_id', 'tv_option_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('tv_selected_options');
    }
};
