<template>
  <a-result
    status="warning"
    title="暂无任何菜单权限"
    :sub-title="'尚未分配任何菜单权限，请联系管理员分配！'"
    style="background: none"
  >
    <template #extra>
      <a-button type="primary" @click="handleReLogin">重新登录</a-button>
    </template>
  </a-result>
</template>

<script lang="ts">
import { defineComponent } from 'vue';
import ls from '@/utils/local-storage';

export default defineComponent({
  name: 'NoMenuPermission',
  setup() {
    const handleReLogin = () => {
      // 清除 token，刷新页面后路由守卫会自动跳转到登录页
      ls.set('access_token', '');
      window.location.reload();
    };

    return {
      handleReLogin,
    };
  },
});
</script>
