<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('tv_options', function (Blueprint $table): void {
            $table->id();
            $table->string('category');
            $table->string('code')->unique();
            $table->string('label');
            $table->unsignedInteger('price_yen');
            $table->json('constraints')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('tv_options');
    }
};
