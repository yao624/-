<?php

namespace App\Services;

use App\Models\MetaCopyLibrary;
use App\Models\MetaCopyLibraryPermission;
use App\Models\User;

/**
 * Meta 文案库权限：个人库仅 owner；企业库 owner + meta_copy_library_permissions。
 */
class MetaCopyAccess
{
    private static function isAccessDisabled(): bool
    {
        // 本地/调试时可关闭所有权限校验：
        // - 未设置时默认关闭（true）
        // - 重新开启请在 .env 设置 META_COPY_ACCESS_DISABLED=false
        return filter_var(env('META_COPY_ACCESS_DISABLED', true), FILTER_VALIDATE_BOOLEAN);
    }

    private static function visibilityMode(MetaCopyLibrary $library): ?string
    {
        $scope = $library->visibility_scope;
        if (! is_array($scope)) return null;
        $mode = $scope['mode'] ?? null;
        return is_string($mode) ? $mode : null;
    }

    private static function hasEnterprisePermission(User $user, MetaCopyLibrary $library, ?array $flagCols = null): bool
    {
        // Spatie roles: user->getRoleNames() returns role name strings.
        $roleNames = $user->getRoleNames()->toArray();

        $q = MetaCopyLibraryPermission::query()
            ->where('library_id', $library->id)
            ->where(function ($sub) use ($user, $roleNames) {
                $sub->where(function ($u) use ($user) {
                    $u->where('subject_type', MetaCopyLibraryPermission::SUBJECT_USER)
                        ->where('subject_id', $user->id);
                });

                if (!empty($roleNames)) {
                    $sub->orWhere(function ($r) use ($roleNames) {
                        $r->where('subject_type', MetaCopyLibraryPermission::SUBJECT_ROLE)
                            ->whereIn('subject_id', $roleNames);
                    });
                }
            });

        if ($flagCols === null) {
            return $q->exists();
        }

        return $q->where(function ($f) use ($flagCols) {
            foreach ($flagCols as $col) {
                $f->orWhere($col, true);
            }
        })->exists();
    }

    public static function canViewLibrary(User $user, MetaCopyLibrary $library): bool
    {
        if (self::isAccessDisabled()) return true;

        if ($library->type === MetaCopyLibrary::TYPE_ENTERPRISE) {
            // owner 即使未显式写入权限表，也默认可见（避免首次创建后锁死）
            if ($library->owner_user_id === $user->id) return true;
            if (self::visibilityMode($library) === 'all_company') return true;
            return self::hasEnterprisePermission($user, $library, null);
        }

        return $library->owner_user_id === $user->id;
    }

    public static function canWriteLibrary(User $user, MetaCopyLibrary $library): bool
    {
        if (self::isAccessDisabled()) return true;

        if ($library->type === MetaCopyLibrary::TYPE_ENTERPRISE) {
            if ($library->owner_user_id === $user->id) return true;
            if (self::visibilityMode($library) === 'all_company') return true;
            return self::hasEnterprisePermission($user, $library, ['can_write']);
        }

        return $library->owner_user_id === $user->id;
    }

    public static function canDeleteInLibrary(User $user, MetaCopyLibrary $library): bool
    {
        if (self::isAccessDisabled()) return true;

        if ($library->type === MetaCopyLibrary::TYPE_ENTERPRISE) {
            if ($library->owner_user_id === $user->id) return true;
            return self::hasEnterprisePermission($user, $library, ['can_delete']);
        }

        return $library->owner_user_id === $user->id;
    }

    public static function canManageLibrary(User $user, MetaCopyLibrary $library): bool
    {
        if (self::isAccessDisabled()) return true;

        if ($library->type === MetaCopyLibrary::TYPE_ENTERPRISE) {
            if ($library->owner_user_id === $user->id) return true;
            return self::hasEnterprisePermission($user, $library, ['can_manage']);
        }

        return $library->owner_user_id === $user->id;
    }

}
