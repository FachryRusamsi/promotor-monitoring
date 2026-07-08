<?php

namespace App\Http\Controllers\Promotor;

use App\Http\Controllers\Controller;
use Inertia\Inertia;
use Inertia\Response;

class AttendanceController extends Controller
{
    public function index(): Response
    {
        return Inertia::render('Promotor/AttendanceForm');
    }
}
