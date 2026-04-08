<template>
  <setting-outlined
    v-bind="$attrs"
    class="ant-pro-dropdown ant-pro-dropdown-action"
    @click="handleSettingBtnClick"
  />
  <a-modal
    v-model:open="state.visible"
    :width="700"
    :title="t('global.setting.title')"
    :confirm-loading="state.confirmLoading"
    @ok="handleOk"
  >
    <a-row :gutter="16">
      <a-col :span="12">
        <list label="Basic Layout">
          <list-item label="Layout Mode">
            <a-select :value="layout" @change="handleLayoutChange" style="width: 150px">
              <a-select-option value="side">Side</a-select-option>
              <a-select-option value="left">Left</a-select-option>
              <a-select-option value="top">Top</a-select-option>
              <a-select-option value="mix">Mix</a-select-option>
            </a-select>
          </list-item>
          <list-item label="Content Width">
            <a-radio-group
              :value="contentWidth"
              @change="handleContentWidthChange"
              style="width: 150px"
            >
              <a-radio-button value="Fluid">Fluid</a-radio-button>
              <a-radio-button value="Fixed">Fixed</a-radio-button>
            </a-radio-group>
          </list-item>
          <list-item label="Nav Theme">
            <a-radio-group
              :disabled="hasMix"
              :value="navTheme"
              @change="handleNavThemeChange"
              style="width: 150px"
            >
              <a-radio-button value="dark">Dark</a-radio-button>
              <a-radio-button value="light">Light</a-radio-button>
            </a-radio-group>
          </list-item>
          <list-item label="Split Menu">
            <a-switch
              checked-children="开"
              un-checked-children="关"
              :disabled="layout !== 'mix'"
              :checked="splitMenus"
              @change="handleSplitMenusChange"
            />
          </list-item>
        </list>

        <list v-if="false" label="Box">
          <list-item label="Fixed Header">
            <a-switch :checked="fixedHeader" />
          </list-item>
          <list-item label="Fixed Sidebar">
            <a-switch :checked="fixedSidebar" />
          </list-item>
        </list>

        <list label="Router Animation">
          <a-select
            :value="transitionName || 'null'"
            @change="handleChangeTransitionName"
            style="width: 200px"
          >
            <a-select-option value="null">Null</a-select-option>
            <a-select-option value="slide-fadein-up">Slide Up</a-select-option>
            <a-select-option value="slide-fadein-right">Slide Right</a-select-option>
            <a-select-option value="fadein">Fade In</a-select-option>
            <a-select-option value="zoom-fadein">Zoom</a-select-option>
          </a-select>
        </list>
      </a-col>
    </a-row>
  </a-modal>
</template>

<script lang="ts">
import { computed, defineComponent, reactive } from 'vue';
import { useI18n } from 'vue-i18n';
import { SettingOutlined } from '@ant-design/icons-vue';

import List from './list.vue';
import ListItem from './list-item.vue';
import type { RadioChangeEvent, SelectProps, SwitchProps } from 'ant-design-vue';
import { useAppStore } from '@/store/app';
import type { ContentWidth, LayoutType, MenuTheme } from '../base-layouts/typing';

export default defineComponent({
  name: 'SettingDialog',
  inheritAttrs: false,
  setup() {
    const { t } = useI18n();
    const state = reactive({
      visible: false,
      confirmLoading: false,
    });
    const handleOk = () => {
      state.visible = false;
    };
    const handleSettingBtnClick = () => {
      state.visible = true;
    };
    const appStore = useAppStore();
    const layout = computed(() => appStore.layout);
    const navTheme = computed(() => appStore.navTheme);
    const contentWidth = computed(() => appStore.contentWidth);
    const splitMenus = computed(() => appStore.splitMenus);
    const fixedHeader = computed(() => appStore.fixedHeader);
    const fixedSidebar = computed(() => appStore.fixedSidebar);
    const transitionName = computed(() => appStore.transitionName);

    const hasMix = computed(() => layout.value === 'mix');

    const handleLayoutChange: SelectProps['onChange'] = value => {
      if (value !== 'mix') {
        // 强制停止使用分割菜单
        appStore.SET_SPLIT_MENUS(false);
      } else {
        // 如果是 mix 模式，则需要将主题色设置为 dark
        appStore.SET_NAV_THEME('dark');
      }
      appStore.SET_LAYOUT(value as LayoutType);
    };
    const handleContentWidthChange = (e: RadioChangeEvent) => {
      const value = (e.target as HTMLInputElement).value as ContentWidth;
      appStore.SET_CONTENT_WIDTH(value);
    };
    const handleNavThemeChange = (e: RadioChangeEvent) => {
      const value = (e.target as HTMLInputElement).value as MenuTheme | 'realDark';
      appStore.SET_NAV_THEME(value);
    };
    const handleSplitMenusChange: SwitchProps['onChange'] = checked => {
      appStore.SET_SPLIT_MENUS(checked as boolean);
    };
    const handleChangeTransitionName: SelectProps['onChange'] = value => {
      appStore.SET_TRANSITION_NAME(value === 'null' ? '' : (value as string));
    };

    return {
      t,

      state,
      layout,
      navTheme,
      contentWidth,
      splitMenus,
      fixedHeader,
      fixedSidebar,
      transitionName,
      hasMix,

      handleOk,
      handleSettingBtnClick,

      handleLayoutChange,
      handleContentWidthChange,
      handleNavThemeChange,
      handleSplitMenusChange,
      handleChangeTransitionName,
    };
  },
  components: {
    SettingOutlined,
    List,
    ListItem,
  },
});
</script>
