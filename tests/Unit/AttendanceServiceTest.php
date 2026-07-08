<?php

use App\Services\AttendanceService;
use Mockery;

it('returns belum check in when no attendance exists for today', function () {
    $user = Mockery::mock();
    $builder = Mockery::mock();

    $builder->shouldReceive('whereDate')->with('work_date', now()->toDateString())->andReturnSelf();
    $builder->shouldReceive('latest')->andReturnSelf();
    $builder->shouldReceive('first')->andReturnNull();

    $user->shouldReceive('attendances')->andReturn($builder);

    $service = new AttendanceService();

    expect($service->getTodayAttendanceStatus($user))->toBe('Belum Check In');
});
