<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        // View for Transactions
        DB::statement("
            CREATE OR REPLACE VIEW vw_powerbi_transactions AS
            SELECT 
                t.id AS transaction_id,
                t.jml_edukasi,
                t.jml_sp,
                t.jml_pulsa,
                t.jml_aktivasi_gemini,
                t.location_name,
                t.latitude,
                t.longitude,
                t.transaction_date,
                t.created_at,
                u.id AS promotor_id,
                u.name AS promotor_name,
                r.name AS region_name,
                a.name AS area_name,
                o.name AS outlet_name
            FROM transactions t
            LEFT JOIN users u ON t.user_id = u.id
            LEFT JOIN outlets o ON t.outlet_id = o.id
            LEFT JOIN areas a ON u.area_id = a.id
            LEFT JOIN regions r ON u.region_id = r.id;
        ");

        // View for Attendances
        DB::statement("
            CREATE OR REPLACE VIEW vw_powerbi_attendances AS
            SELECT 
                att.id AS attendance_id,
                att.work_date,
                att.check_in_at,
                att.check_out_at,
                att.status,
                att.work_hour,
                att.check_in_lat,
                att.check_in_lng,
                att.check_out_lat,
                att.check_out_lng,
                u.id AS promotor_id,
                u.name AS promotor_name,
                o.name AS outlet_name,
                r.name AS region_name,
                a.name AS area_name
            FROM attendances att
            LEFT JOIN users u ON att.user_id = u.id
            LEFT JOIN outlets o ON att.outlet_id = o.id
            LEFT JOIN areas a ON u.area_id = a.id
            LEFT JOIN regions r ON u.region_id = r.id;
        ");
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        DB::statement("DROP VIEW IF EXISTS vw_powerbi_transactions;");
        DB::statement("DROP VIEW IF EXISTS vw_powerbi_attendances;");
    }
};
