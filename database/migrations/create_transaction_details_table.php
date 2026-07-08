<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('transaction_details', function (Blueprint $table) {

            $table->id();

            $table->foreignId('transaction_id')
                ->constrained()
                ->cascadeOnDelete();

            $table->string('msisdn', 20)
                ->comment('Nomor HP pelanggan (MSISDN)');

            $table->enum('type', [
                'starter_pack',
                'reload',
                'gemini_activation'
            ])->comment('Jenis transaksi per MSISDN');

            $table->text('notes')->nullable();

            $table->timestamps();

        });
    }

    public function down(): void
    {
        Schema::dropIfExists('transaction_details');
    }
};
