<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class PowerBiController extends Controller
{
    /**
     * Optional simple auth for Power BI API
     */
    public function __construct()
    {
        // For production, you might want to protect this using sanctum or a static API token.
        // E.g., $this->middleware('auth:sanctum');
    }

    /**
     * Fetch all transactions for Power BI
     */
    public function transactions(Request $request)
    {
        // We can query directly from the View we created, or use Eloquent.
        // Using the view is cleaner for Power BI to avoid nested JSON arrays.
        $data = DB::table('vw_powerbi_transactions')->get();
        return response()->json($data);
    }

    /**
     * Fetch all attendances for Power BI
     */
    public function attendances(Request $request)
    {
        $data = DB::table('vw_powerbi_attendances')->get();
        return response()->json($data);
    }
}
