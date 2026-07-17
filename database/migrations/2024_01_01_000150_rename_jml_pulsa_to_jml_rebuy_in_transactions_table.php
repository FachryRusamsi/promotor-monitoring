<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        // Check if column exists before renaming (in case fresh migration was run and it's already named jml_rebuy)
        if (Schema::hasColumn('transactions', 'jml_pulsa')) {
            Schema::table('transactions', function (Blueprint $table) {
                $table->renameColumn('jml_pulsa', 'jml_rebuy');
            });
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        if (Schema::hasColumn('transactions', 'jml_rebuy')) {
            Schema::table('transactions', function (Blueprint $table) {
                $table->renameColumn('jml_rebuy', 'jml_pulsa');
            });
        }
    }
};
