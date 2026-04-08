<?php

namespace App\Enums;

enum EnumCatalogTasks:string
{
    case Admin = 'Admin';
    case GeneralUser = 'General user';

    public function tasks(): array
    {
        return match ($this) {
            self::Admin => ['ADVERTISE', 'MANAGE'],
            self::GeneralUser => ['ADVERTISE'],
        };
    }
}

