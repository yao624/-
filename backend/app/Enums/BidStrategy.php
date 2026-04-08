<?php

namespace App\Enums;

enum BidStrategy: string {
    case HighestVolume = 'LOWEST_COST_WITHOUT_CAP';
    case BidCap = 'LOWEST_COST_WITH_BID_CAP';
    case CostPerResultGoal = 'COST_CAP';
    /** 广告花费回报目标（ROAS），系列/广告组 bid_constraints.roas_average_floor 配合使用 */
    case MinRoas = 'LOWEST_COST_WITH_MIN_ROAS';
    case PacingTypeNoPacing = 'no_pacing';
    case PacingTypeStandard = 'standard';
}
