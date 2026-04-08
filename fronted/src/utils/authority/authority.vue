<template>
  <a-result v-if="!auth && noMatch" :status="403" title="403" sub-title="无权限" />
  <slot v-else-if="auth" />
</template>

<script lang="ts">
import { useUserStore } from '@/store/user';
import type { PropType, VNodeChild } from 'vue';
import { watchEffect, computed, ref, defineComponent } from 'vue';

export type AuthorityType = string | string[] | ((permissions: string[]) => boolean);

export default defineComponent({
  name: 'Authority',
  props: {
    authority: {
      type: [String, Array, Function] as PropType<AuthorityType>,
      default: () => true,
    },
    noMatch: {
      type: [String, Boolean, Object] as PropType<string | boolean | VNodeChild>,
      default: () => undefined,
    },
  },
  setup(props) {
    const userStore = useUserStore();
    const permissions = computed(() => userStore.permissions || []);
    const isSuper = computed(() => userStore.is_super === 1);
    const auth = ref(false);

    watchEffect(() => {
      // 超级管理员有所有权限
      if (isSuper.value) {
        auth.value = true;
        return;
      }

      const perms = permissions.value;
      const authority = props.authority;
      if (typeof authority === 'string') {
        auth.value = perms.includes(authority);
      } else if (Array.isArray(authority)) {
        auth.value = authority.some(a => perms.includes(a));
      } else if (typeof authority === 'function') {
        auth.value = authority(perms);
      } else {
        auth.value = true;
      }
    });

    return {
      auth,
    };
  },
});
</script>
