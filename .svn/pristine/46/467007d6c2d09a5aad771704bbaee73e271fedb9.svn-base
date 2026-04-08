import type { MenuDataItem } from '@/router/typing';
import type { Ref } from 'vue';
import { computed, ref, watchEffect } from 'vue';
import { useUserStore } from '@/store/user';

export const filterChildRoute = (route: MenuDataItem, permissions: string[]) =>
  route.children
    ?.filter(item => {
      const hasAllow = hasAuthority(item, permissions);
      if (hasAllow && item.children && item.children.length > 0) {
        item.children = filterChildRoute(item, permissions!);
      }
      return hasAllow;
    })
    .filter(item => item);

// permissions: Permission[]
export const hasAuthority = (route: MenuDataItem, permissions: string[]) => {
  if (route.meta?.authority) {
    return permissions.some(value => {
      return route.meta?.authority?.includes(value);
    });
  }
  return true;
};

// Action 类型：权限字符串，如 'user:query', 'user:add'
export type Action = string;

// 按钮级别权限检查 Hook
type MaybeRef<T> = T | Ref<T>;
export const useAuth = (actions: MaybeRef<Action | Action[]>) => {
  const userStore = useUserStore();
  const permissions = computed(() => userStore.permissions || []);
  const isSuper = computed(() => userStore.is_super === 1);
  const hasAuth = ref(false);

  watchEffect(() => {
    // 超级管理员有所有权限
    if (isSuper.value) {
      hasAuth.value = true;
      return;
    }
    const auths = Array.isArray(actions) ? actions : [actions];
    // 检查是否有所需的权限
    hasAuth.value = auths.some(auth => permissions.value.includes(auth));
  });

  return hasAuth;
};
