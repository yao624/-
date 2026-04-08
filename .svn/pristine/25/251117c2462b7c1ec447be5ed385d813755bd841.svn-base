import type { Directive, DirectiveBinding } from 'vue';
import { useUserStore } from '@/store/user';

type PermissionAction = string | string[];

/**
 * v-hasPermission="'permission:add'"
 * v-hasPermission="['permission:add', 'permission:delete']"
 */
const hasPermission: Directive = {
  mounted(el: HTMLElement, binding: DirectiveBinding<PermissionAction>) {
    const { value } = binding;
    if (!value) {
      return;
    }

    const userStore = useUserStore();
    const { permissions, is_super } = userStore;

    // 超级管理员有所有权限
    if (is_super === 1) {
      return;
    }

    const auths = Array.isArray(value) ? value : [value];
    const hasAuth = auths.some(auth => permissions.includes(auth));

    if (!hasAuth) {
      el.parentNode?.removeChild(el);
    }
  },
};

export default hasPermission;
