<template>
  <a-style-provider
    :hash-priority="hashPriority"
    :transformers="[legacyLogicalPropertiesTransformer]"
  >
    <a-config-provider :locale="locale" :theme="themeConfig">
      <site-token>
        <router-view />
        <a-tour :open="open" :steps="steps" @close="handleCloseTour" :arrow="false" />
      </site-token>
    </a-config-provider>
  </a-style-provider>
</template>

<script lang="ts" setup>
import SiteToken from './components/site-token.vue';
import { computed, provide, ref, shallowRef, watch } from 'vue';
import { legacyLogicalPropertiesTransformer, theme as antdTheme } from 'ant-design-vue';
import { STORAGE_LANG_KEY, useAppStore } from '@/store/app';
import { localStorage } from '@/utils/local-storage';
import useMediaQuery from '@/utils/hooks/useMediaQuery';
import { useI18n } from 'vue-i18n';
import useMenuState, { MenuStateSymbol } from './layouts/use-menu-state';
import { useMultiTabStateProvider } from './components/multi-tab';
import { defaultLang } from './locales';
import type { ConfigProviderProps, TourProps } from 'ant-design-vue';
import { useRoute } from 'vue-router';
// import { routes } from './router';
const i18n = useI18n();
const appStore = useAppStore();

// eslint-disable-next-line @typescript-eslint/no-unused-vars
const findWorkplace = (routes: any[]) => {
  for (const route of routes) {
    if (route.path === '/workplace') {
      return route;
    }
    if (route.children) {
      const res = findWorkplace(route.children);
      if (res) {
        return res;
      }
    }
  }
};

// const workplace = findWorkplace(routes);

const multiTabState = useMultiTabStateProvider({
  // 如需要初始路由，可以在这里添加
  // initCacheList: [
  //   {
  //     path: workplace.path,
  //     route: workplace as any,
  //     tabTitle: workplace?.meta?.title as string,
  //     tabPath: workplace.path,
  //     lock: !!workplace.meta.lock,
  //     lastActiveTime: Date.now(),
  //   },
  // ],
});
const colSize = useMediaQuery();
const isMobile = computed(() => colSize.value === 'sm' || colSize.value === 'xs');
const menuState = useMenuState(
  {
    collapsed: isMobile.value,
    openKeys: [] as string[],
    selectedKeys: [] as string[],
    isMobile,
  },
  multiTabState,
);
const open = ref(false);
// 引导只出现一次即可，下次添加新的引导时，需要修改这里的key
const tourKey = 'admin-pro-tour-key-v1';
const steps = shallowRef<TourProps['steps']>([]);
const route = useRoute();

const openTour = () => {
  setTimeout(() => {
    steps.value = [
      document.getElementById('header-collapsed-button') && {
        title: '折叠菜单',
        description: '点击收起或展开菜单。',
        target: () => document.getElementById('header-collapsed-button') as HTMLElement,
      },
      document.getElementById('setting-drawer-handle') && {
        title: '主题',
        description: '打开设置网站皮肤',
        target: () => document.getElementById('setting-drawer-handle') as HTMLElement,
      },
      document.getElementsByClassName('surveybyantdv-launch-button')?.[0] && {
        title: '调研',
        description: '点击按钮，填写你的想法',
        target: () =>
          document.getElementsByClassName('surveybyantdv-launch-button')?.[0] as HTMLElement,
      },
    ].filter(Boolean);
    open.value = steps.value.length > 0;
  }, 1000);
};
const handleCloseTour = () => {
  localStorage.set(tourKey, Date.now());
  open.value = false;
};

watch(
  () => route.name,
  () => {
    if (!route.name || route.name === 'login' || route.name === 'register') {
      return;
    }
    if (isMobile.value) {
      return;
    }
    if (localStorage.get(tourKey)) {
      return;
    }
    openTour();
  },
  { immediate: true },
);
const lang = localStorage.get(STORAGE_LANG_KEY, defaultLang);
if (lang) {
  appStore.SET_LANG(lang);
}
// 强制设置布局为左右结构
appStore.SET_LAYOUT('left');
const hashPriority = ref('high' as const);
watch(hashPriority, () => {
  location.reload();
});
export type ThemeName = '' | 'light' | 'dark' | 'compact';
const getAlgorithm = (themes: ThemeName[] = []) =>
  themes
    .filter(theme => !!theme)
    .map(theme => {
      if (theme === 'dark') {
        return antdTheme.darkAlgorithm;
      }
      if (theme === 'compact') {
        return antdTheme.compactAlgorithm;
      }
      return antdTheme.defaultAlgorithm;
    });
const themeConfig = computed(() => {
  return {
    algorithm: getAlgorithm(['light']),
    token: { colorPrimary: appStore.primaryColor, colorInfo: appStore.primaryColor },
  };
});
const theme = computed(() => appStore.navTheme);
watch(
  theme,
  () => {
    if (theme.value === 'realDark') {
      document
        .getElementsByTagName('html')[0]
        .setAttribute('data-pro-theme', 'antdv-pro-theme-dark');
    } else {
      document
        .getElementsByTagName('html')[0]
        .setAttribute('data-pro-theme', 'antdv-pro-theme-light');
    }
  },
  { immediate: true },
);
provide('isMobile', isMobile);
provide(
  'isRealDark',
  computed(() => theme.value === 'realDark'),
);
provide(MenuStateSymbol, menuState);
const locale = computed(() => {
  return i18n.getLocaleMessage(i18n.locale.value).antd as ConfigProviderProps['locale'];
});
</script>
