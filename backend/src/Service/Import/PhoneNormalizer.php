<?php

namespace App\Service\Import;

final class PhoneNormalizer
{
    public function normalize(string $raw): ?string
    {
        $trimmed = trim($raw);
        if ($trimmed === '' || !preg_match('/^[\d+\s\-()]+$/', $trimmed)) {
            return null;
        }

        $compact = preg_replace('/[\s\-()]/', '', $trimmed) ?? '';
        if (preg_match('/^\+7\d{10}$/', $compact)) {
            return $compact;
        }
        if (preg_match('/^8\d{10}$/', $compact)) {
            return '+7'.substr($compact, 1);
        }

        return null;
    }
}
