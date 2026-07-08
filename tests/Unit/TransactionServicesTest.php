<?php

use App\Models\Transaction;
use App\Services\TransactionCacheService;
use App\Services\ValidationService;

it('normalizes validation status values', function () {
    $service = new ValidationService();

    expect($service->normalizeStatus('VALID'))->toBe('valid')
        ->and($service->normalizeStatus('invalid'))->toBe('invalid')
        ->and($service->normalizeStatus('unknown'))->toBe('pending');
});

it('builds an empty daily report payload when no transactions exist', function () {
    $service = new TransactionCacheService();

    expect($service->buildDailyReport(collect([])))->toBe([]);
});
