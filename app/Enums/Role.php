<?php

namespace App\Enums;

enum Role: string
{
    case SuperAdmin = 'super_admin';
    case Admin = 'admin';
    case Manager = 'manager';
    case SalesRep = 'sales_rep';
    case Free = 'free';

    public static function values(): array
    {
        return array_column(self::cases(), 'value');
    }
}
