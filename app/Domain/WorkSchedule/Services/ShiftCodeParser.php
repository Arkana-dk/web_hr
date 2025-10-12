<?php

namespace App\Domain\WorkSchedule\Services;

final class ShiftCodeParser
{
    public static function fromCell(?string $cell): ?string
    {
        if (!$cell) return null;
        return preg_match('/\[(.*?)\]/', $cell, $m) ? $m[1] : null;
    }
}
