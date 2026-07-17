<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('transactions', function (Blueprint $table) {

            $table->id();

            $table->foreignId('user_id')
                ->constrained()
                ->cascadeOnDelete();

            $table->foreignId('outlet_id')
                ->constrained()
                ->cascadeOnDelete();

            $table->foreignId('attendance_id')
                ->nullable()
                ->constrained()
                ->nullOnDelete();

            $table->date('transaction_date');

            $table->unsignedInteger('jml_edukasi')->default(0)
                ->comment('Jumlah edukasi yang dilakukan');

            $table->unsignedInteger('jml_sp')->default(0)
                ->comment('Jumlah starter pack terjual');

            $table->unsignedInteger('jml_rebuy')->default(0)
                ->comment('Jumlah reload/pulsa terjual');

            $table->unsignedInteger('jml_aktivasi_gemini')->default(0)
                ->comment('Jumlah aktivasi Gemini');

            $table->json('foto_edukasi')->nullable()
                ->comment('Array path foto bukti edukasi');

            $table->json('foto_penjualan')->nullable()
                ->comment('Array path foto bukti penjualan');

            $table->decimal('latitude', 10, 7)->nullable();

            $table->decimal('longitude', 10, 7)->nullable();

            $table->enum('validation_status', [
                'pending',
                'valid',
                'invalid'
            ])->default('pending');

            $table->text('validation_notes')->nullable();

            $table->timestamps();

        });
    }

    public function down(): void
    {
        Schema::dropIfExists('transactions');
    }
};
