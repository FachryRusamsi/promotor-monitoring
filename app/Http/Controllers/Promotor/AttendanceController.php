<?php

namespace App\Http\Controllers\Promotor;

use App\Http\Controllers\Controller;
use App\Services\AttendanceService;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class AttendanceController extends Controller
{
    public function __construct(private readonly AttendanceService $attendanceService = new AttendanceService())
    {
    }

    public function index(Request $request): Response
    {
        $user = $request->user();

        return Inertia::render('Promotor/AttendanceForm', [
            'status' => $this->attendanceService->getTodayAttendanceStatus($user),
        ]);
    }

    public function checkIn(Request $request)
    {
        $request->validate([
            'photo' => 'required|image|max:10240',
            'lat' => 'required|numeric',
            'lng' => 'required|numeric',
        ]);

        $user = $request->user();

        try {
            $this->attendanceService->checkIn($user, [
                'photo' => $request->file('photo'),
                'lat' => $request->lat,
                'lng' => $request->lng,
            ]);
        } catch (\Throwable $exception) {
            return back()->with('error', $exception->getMessage());
        }

        return redirect()->route('promotor.dashboard')->with('success', 'Check In berhasil.');
    }

    public function checkOut(Request $request)
    {
        $request->validate([
            'photo' => 'required|image|max:10240',
            'lat' => 'required|numeric',
            'lng' => 'required|numeric',
        ]);

        $user = $request->user();

        try {
            $this->attendanceService->checkOut($user, [
                'photo' => $request->file('photo'),
                'lat' => $request->lat,
                'lng' => $request->lng,
            ]);
        } catch (\Throwable $exception) {
            return back()->with('error', $exception->getMessage());
        }

        return redirect()->route('promotor.dashboard')->with('success', 'Check Out berhasil.');
    }
}
