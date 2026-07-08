<?php

namespace App\Services;

use App\Models\Attendance;
use RuntimeException;

class AttendanceService
{
    public function getTodayAttendance($user)
    {
        return $user->attendances()
            ->whereDate('work_date', now()->toDateString())
            ->latest()
            ->first();
    }

    public function getTodayAttendanceStatus($user): string
    {
        $attendance = $this->getTodayAttendance($user);

        if (! $attendance) {
            return 'Belum Check In';
        }

        return $attendance->check_out_at ? 'Sudah Check Out' : 'Sudah Check In';
    }

    public function checkIn($user, array $data): Attendance
    {
        $today = now()->toDateString();

        if ($user->attendances()->whereDate('work_date', $today)->exists()) {
            throw new RuntimeException('Anda sudah melakukan Check In hari ini.');
        }

        $path = $data['photo']->store('attendances/checkin', 'public');

        return $user->attendances()->create([
            'outlet_id' => $data['outlet_id'] ?? 1,
            'work_date' => $today,
            'check_in_at' => now(),
            'check_in_photo' => $path,
            'check_in_lat' => $data['lat'],
            'check_in_lng' => $data['lng'],
            'status' => 'working',
        ]);
    }

    public function checkOut($user, array $data): Attendance
    {
        $attendance = $this->getTodayAttendance($user);

        if (! $attendance) {
            throw new RuntimeException('Anda belum Check In hari ini.');
        }

        if ($attendance->check_out_at) {
            throw new RuntimeException('Anda sudah melakukan Check Out.');
        }

        $path = $data['photo']->store('attendances/checkout', 'public');
        $workHours = now()->diffInMinutes($attendance->check_in_at) / 60;

        $attendance->update([
            'check_out_at' => now(),
            'check_out_photo' => $path,
            'check_out_lat' => $data['lat'],
            'check_out_lng' => $data['lng'],
            'work_hour' => round($workHours, 2),
            'status' => 'finished',
        ]);

        return $attendance;
    }
}
