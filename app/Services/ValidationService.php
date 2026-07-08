<?php

namespace App\Services;

class ValidationService
{
    public function normalizeStatus(?string $status): string
    {
        $status = strtolower((string) ($status ?? ''));

        return match ($status) {
            'valid', 'approved', 'success' => 'valid',
            'invalid', 'rejected', 'failed' => 'invalid',
            default => 'pending',
        };
    }

    public function isValid(?string $status): bool
    {
        return $this->normalizeStatus($status) === 'valid';
    }
}
