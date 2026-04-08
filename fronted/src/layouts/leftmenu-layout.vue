<template>
  <pro-provider :content-width="contentWidth">
    <a-layout class="ant-pro-leftmenu-layout">
      <sider-menu
        theme="light"
        layout="left"
        prefix-cls="ant-pro-leftmenu-sider"
        :fixed="true"
        :menus="menus"
        :sider-width="sideWidth"
        :collapsed-width="collapsedWidth"
        v-model:open-keys="openKeys"
        v-model:collapsed="collapsed"
        v-model:selected-keys="selectedKeys"
      ></sider-menu>
      <a-layout style="position: relative; flex-direction: column">
        <header-view
          :theme="theme"
          :layout="layout"
          :menus="menus"
          :has-sider-menu="true"
          :fixed-header="true"
          :split-menus="splitMenus"
          :collapsed-button="false"
          :collapsed-width="collapsed ? collapsedWidth : sideWidth"
          :selected-keys="[]"
          :open-keys="[]"
        >
          <div style="text-align: right">
            <notice-icon />
            <avatar-dropdown :current-user="currentUser" />
            <select-lang />
          </div>
        </header-view>
        <section style="flex: auto; overflow-x: hidden; margin-left: 0" class="ant-pro-leftmenu-layout-content">
          <wrap-content>
            <router-view />
            <slot />
          </wrap-content>
          <global-footer />
        </section>
      </a-layout>
    </a-layout>
    <setting-drawer />
  </pro-provider>
</template>

<script lang="ts">
import { defineComponent, computed } from 'vue';
import { genMenuInfo } from '@/utils/menu-util';
import { default as WrapContent } from '@/components/base-layouts/wrap-content/index.vue';
import { default as GlobalFooter } from '@/components/base-layouts/global-footer/index.vue';
import { default as SiderMenu } from '@/components/base-layouts/sider-menu/index.vue';
import { default as HeaderView } from '@/components/base-layouts/header/index.vue';
import { default as SelectLang } from '@/components/select-lang/index.vue';
import { default as AvatarDropdown } from '@/components/avatar-dropdown.vue';
import { default as SettingDrawer } from '@/components/setting-drawer/index.vue';
import { injectMenuState } from './use-menu-state';
import { default as NoticeIcon } from '@/components/notice-icon/index.vue';
import { useUserStore } from '@/store/user';

export default defineComponent({
  name: 'LeftMenuLayout',
  setup() {
    const userStore = useUserStore();
    const currentUser = computed(() => userStore.currentUser);

    // gen menus
    const allowRouters = computed(() => userStore.allowRouters); // genMenuInfo(filterMenu(routes)).menus;
    const menus = computed(() => genMenuInfo(allowRouters.value).menus);
    const menuState = injectMenuState();

    return {
      menus,
      ...menuState,
      currentUser,
    };
  },
  components: {
    WrapContent,
    SiderMenu,
    GlobalFooter,
    HeaderView,
    SelectLang,
    AvatarDropdown,
    SettingDrawer,
    NoticeIcon,
  },
});
</script>

<style lang="less">
#app-body {
  @import '../components/base-layouts/leftmenu-layout.less';
  @import '../components/base-layouts/leftmenu/index.less';
}
</style>
