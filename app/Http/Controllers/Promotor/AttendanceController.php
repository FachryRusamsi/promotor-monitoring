<?php

namespace App\Http\Controllers\Promotor;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class AttendanceController extends Controller
{
    public function index(Request $request): Response
    {
        $user = $request->user();
        $today = now()->toDateString();
        $attendance = $user->attendances()->whereDate('work_date', $today)->latest()->first();
        
        $status = 'Belum Check In';
        if ($attendance) {
            $status = $attendance->check_out_at ? 'Sudah Check Out' : 'Sudah Check In';
        }

        return Inertia::render('Promotor/AttendanceForm', [
            'status' => $status
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
        $today = now()->toDateString();

        // Check if already checked in today
        $exists = $user->attendances()->whereDate('work_date', $today)->exists();
        if ($exists) {
            return back()->with('error', 'Anda sudah melakukan Check In hari ini.');
        }

        $path = $request->file('photo')->store('attendances/checkin', 'public');

        $user->attendances()->create([
            'outlet_id' => 1, // Default outlet for now
            'work_date' => $today,
            'check_in_at' => now(),
            'check_in_photo' => $path,
            'check_in_lat' => $request->lat,
            'check_in_lng' => $request->lng,
            'status' => 'working',
        ]);

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
        $today = now()->toDateString();

        $attendance = $user->attendances()->whereDate('work_date', $today)->latest()->first();
        
        if (!$attendance) {
            return back()->with('error', 'Anda belum Check In hari ini.');
        }
        if ($attendance->check_out_at) {
            return back()->with('error', 'Anda sudah melakukan Check Out.');
        }

        $path = $request->file('photo')->store('attendances/checkout', 'public');

        $workHours = now()->diffInMinutes($attendance->check_in_at) / 60;

        $attendance->update([
            'check_out_at' => now(),
            'check_out_photo' => $path,
            'check_out_lat' => $request->lat,
            'check_out_lng' => $request->lng,
            'work_hour' => round($workHours, 2),
            'status' => 'finished',
        ]);

        return redirect()->route('promotor.dashboard')->with('success', 'Check Out berhasil.');
    }
}
