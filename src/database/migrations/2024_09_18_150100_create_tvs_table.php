<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('tvs', function (Blueprint $table): void {
            $table->id();
            $table->string('name');
            $table->string('maker');
            $table->unsignedInteger('inch');
            $table->string('resolution');
            $table->string('panel');
            $table->unsignedTinyInteger('hdmi_ports');
            $table->boolean('has_hdr');
            $table->boolean('has_wifi');
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('tvs');
    }
};
