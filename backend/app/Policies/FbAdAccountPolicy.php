<?php

namespace App\Policies;

use App\Models\FbAdAccount;
use App\Models\User;
use Illuminate\Support\Facades\Log;

class FbAdAccountPolicy
{
    /**
     * Create a new policy instance.
     */
    public function operate(User $user, FbAdAccount $fbAdAccount)
    {
        if ($user->hasRole('admin')) {
            return true;
        }
        // 检查用户是否是广告账户的owner
        if ((string)$fbAdAccount->owner === (string)$user->id) {
            return true;
        }
        // 检查用户是否是协助人员（通过多对多关系）
        if ($fbAdAccount->users->contains($user)) {
            return true;
        }
        // 检查用户是否直接关联了 FbAdAccount
        if ($user->fbAdAccounts->contains($fbAdAccount)) {
            return true;
        }
        // 检查用户是否通过 FbAccount 关联了 FbAdAccount
        foreach ($user->fbAccounts as $fbAccount) {
            if ($fbAccount->fbAdAccounts->contains($fbAdAccount)) {
                return true;
            }
        }

        return false;
    }
}
