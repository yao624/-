<template>
  <a-list item-layout="horizontal">
    <a-list-item>
      <a-list-item-meta>
        <template v-slot:title>
          <a>风格配色</a>
        </template>
        <template v-slot:description>
          <span>整体风格配色设置</span>
        </template>
      </a-list-item-meta>
      <template v-slot:actions>
        <a-switch
          checkedChildren="白色"
          unCheckedChildren="暗色"
          :checked="navTheme === 'realDark'"
          @update:checked="val => appStore.SET_NAV_THEME(val ? 'realDark' : 'dark')"
        />
      </template>
    </a-list-item>
    <a-list-item>
      <a-list-item-meta>
        <template v-slot:title>
          <a>主题色</a>
        </template>
        <template v-slot:description>
          <span>
            页面风格配色：
            <a>{{ colorFilter(primaryColor) }}</a>
          </span>
        </template>
      </a-list-item-meta>
    </a-list-item>
  </a-list>
</template>

<script lang="ts">
import { DEFAULT_PRIMARY_COLOR, useAppStore } from '@/store/app';
import { defineComponent, computed } from 'vue';

const themeMap: { [key: string]: string } = {
  dark: '暗色',
  light: '白色',
  realDark: '暗黑模式',
};

const colorList = [
  {
    key: '薄暮',
    color: '#F5222D',
  },
  {
    key: '火山',
    color: '#FA541C',
  },
  {
    key: '日暮',
    color: '#FAAD14',
  },
  {
    key: '明青',
    color: '#13C2C2',
  },
  {
    key: '极光绿',
    color: '#52C41A',
  },
  {
    key: '拂晓蓝（默认）',
    color: DEFAULT_PRIMARY_COLOR,
  },
  {
    key: '极客蓝',
    color: '#2F54EB',
  },
  {
    key: '酱紫',
    color: '#722ED1',
  },
];

const themeFilter = (theme: string) => {
  return themeMap[theme];
};

const colorFilter = (color: string) => {
  const c = colorList.find(o => o.color === color);
  return c && c.key;
};

export default defineComponent({
  name: 'CustomSettings',
  setup() {
    const appStore = useAppStore();
    return {
      navTheme: computed(() => appStore.navTheme),
      primaryColor: computed(() => appStore.primaryColor),
      appStore,
      themeFilter,
      colorFilter,
    };
  },
});
</script>
