<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('jadwal_operasional', function (Blueprint $table) {
            $table->id();
            $table->foreignId('gelanggang_id')->constrained('gelanggangs')->cascadeOnDelete();
            $table->enum('hari', ['senin', 'selasa', 'rabu', 'kamis', 'jumat', 'sabtu', 'minggu']);
            $table->time('jam_buka')->default('07:00:00');
            $table->time('jam_tutup')->default('22:00:00');
            $table->boolean('is_libur')->default(false);

            $table->unique(['gelanggang_id', 'hari']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('jadwal_operasional');
    }
};
