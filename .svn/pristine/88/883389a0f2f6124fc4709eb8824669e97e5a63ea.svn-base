<template>
  <a-dropdown class="ant-pro-dropdown ant-pro-dropdown-action" placement="bottomRight">
    <global-outlined />
    <template #overlay>
      <a-menu class="ant-pro-dropdown-menu" :selected-keys="[currentLang]" @click="handleMenuClick">
        <a-menu-item v-for="locale in locales" :key="locale" :disabled="!languageSupports[locale]">
          <template #icon>
            <span role="img" :aria-label="languageLabels[locale]" style="margin-right: 8px">
              {{ languageIcons[locale] }}
            </span>
          </template>
          {{ languageLabels[locale] }}
        </a-menu-item>
      </a-menu>
    </template>
  </a-dropdown>
</template>

<script lang="ts">
import { defineComponent, computed } from 'vue';
import type { Locale } from '@/locales';
import { locales } from '@/locales';
import { GlobalOutlined } from '@ant-design/icons-vue';
import type { MenuProps } from 'ant-design-vue';
import { useAppStore } from '@/store/app';

const languageLabels = {
  'zh-CN': '简体中文',
  // 'zh-TW': '繁体中文',
  'en-US': 'English',
  // 'pt-BR': 'Português',
};
const languageIcons = {
  'zh-CN': '🇨🇳',
  // 'zh-TW': '🇭🇰',
  'en-US': '🇺🇸',
  // 'pt-BR': '🇧🇷',
};
const languageSupports = {
  'zh-CN': true,
  // 'zh-TW': false,
  'en-US': true,
  // 'pt-BR': false,
};

export default defineComponent({
  setup() {
    const appStore = useAppStore();
    const currentLang = computed(() => appStore.lang);
    const handleMenuClick: MenuProps['onClick'] = ({ key }) => {
      appStore.SET_LANG(key as Locale);
    };

    return {
      currentLang,
      locales,
      languageLabels,
      languageIcons,
      languageSupports,
      handleMenuClick,
    };
  },
  components: {
    GlobalOutlined,
  },
});
</script>
