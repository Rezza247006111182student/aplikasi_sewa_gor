<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('gelanggang_images', function (Blueprint $table) {
            $table->id();
            $table->foreignId('gelanggang_id')->constrained('gelanggangs')->cascadeOnDelete();
            $table->string('path');
            $table->unsignedInteger('urutan')->default(1);
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('gelanggang_images');
    }
};
