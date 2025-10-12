<?php

namespace App\Models;

/**
 * Sekedar “enum” helper untuk daftar role yang valid.
 */
class Role
{
    public const USER       = 'user';
    public const ADMIN      = 'admin';
    public const SUPERADMIN = 'superadmin';

    /**
     * Kembalikan array [value => label] untuk dropdown atau validasi.
     *
     * @return array<string,string>
     */
    public static function getAll(): array
    {
        return [
            self::USER       => 'Employee',
            self::ADMIN      => 'Admin',
            self::SUPERADMIN => 'Superadmin',
        ];
    }
}
