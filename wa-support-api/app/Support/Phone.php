<?php

namespace App\Support;

class Phone
{
    public static function normalize(?string $raw): string
    {
        if ($raw === null || $raw === '') {
            return '';
        }

        return preg_replace('/\D+/', '', $raw) ?? '';
    }
}
