<?php

namespace App\Enums;

enum BusinessUserRelation: string {
    case Owener = 'Owner';
    case Partner = 'Partner';
    case SystemUser = 'SystemUsers';
}

enum BusinessAdAccountRole: string {
    case Admin = 'Admin';
    case GeneralUser = 'General user';
    case ReportingOnly = 'Reporting only';
}
