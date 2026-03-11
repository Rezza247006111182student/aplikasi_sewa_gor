<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('bookings', function (Blueprint $table) {
            $table->id();
            $table->string('kode_booking', 20)->unique();
            $table->foreignId('user_id')->constrained('users')->cascadeOnDelete();
            $table->foreignId('gelanggang_id')->constrained('gelanggangs')->cascadeOnDelete();
            $table->date('tanggal');
            $table->time('jam_mulai');
            $table->time('jam_selesai');
            $table->unsignedInteger('durasi_jam');
            $table->decimal('total_harga', 10, 2);
            $table->enum('status', ['pending', 'confirmed', 'cancelled', 'selesai'])->default('pending');
            $table->text('catatan')->nullable();
            $table->timestamp('cancelled_at')->nullable();
            $table->text('alasan_cancel')->nullable();
            $table->timestamps();

            $table->index(['gelanggang_id', 'tanggal', 'jam_mulai', 'jam_selesai'], 'idx_cek_jadwal');
            $table->index(['user_id', 'status'], 'idx_user_booking');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('bookings');
    }
};
