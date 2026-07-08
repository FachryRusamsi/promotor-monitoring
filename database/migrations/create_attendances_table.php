<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('attendances', function (Blueprint $table) {

            $table->id();

            $table->foreignId('user_id')
                ->constrained()
                ->cascadeOnDelete();

            $table->foreignId('outlet_id')
                ->constrained()
                ->cascadeOnDelete();

            $table->date('work_date');

            $table->timestamp('check_in_at')->nullable();

            $table->timestamp('check_out_at')->nullable();

            $table->string('check_in_photo')->nullable();

            $table->string('check_out_photo')->nullable();

            $table->decimal('check_in_lat',10,7)->nullable();

            $table->decimal('check_in_lng',10,7)->nullable();

            $table->decimal('check_out_lat',10,7)->nullable();

            $table->decimal('check_out_lng',10,7)->nullable();

            $table->enum('status',[
                'working',
                'finished'
            ])->default('working');

            $table->timestamps();

        });
    }

    public function down(): void
    {
        Schema::dropIfExists('attendances');
    }
};