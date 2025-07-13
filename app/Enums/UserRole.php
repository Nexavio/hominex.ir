<?php

namespace App\Enums;

enum UserRole: string
{
    case REGULAR = 'regular';
    case CONSULTANT = 'consultant';
    case ADMIN = 'admin';

    public function label(): string
    {
        return match ($this) {
            self::REGULAR => 'کاربر عادی',
            self::CONSULTANT => 'مشاور',
            self::ADMIN => 'مدیر',
        };
    }
}
