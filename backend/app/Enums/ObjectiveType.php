<?php

namespace App\Enums;

enum ObjectiveType: string {
    case AppPromotion = 'OUTCOME_APP_PROMOTION';
    case Awareness = 'OUTCOME_AWARENESS';
    case Engagement = 'OUTCOME_ENGAGEMENT';
    case Leads = 'OUTCOME_LEADS';
    case Sales = 'OUTCOME_SALES';
    case Traffic = 'OUTCOME_TRAFFIC';
}
