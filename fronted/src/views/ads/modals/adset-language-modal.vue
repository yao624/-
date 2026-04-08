<template>
  <a-modal
    :open="open"
    :title="t('Adset Languages')"
    width="600px"
    :footer="null"
    @cancel="handleCancel"
    @update:open="handleCancel"
  >
    <div v-if="loading" class="text-center p-4">
      <a-spin />
    </div>
    <div v-else>
      <a-card :title="`Adset ID: ${adsetData?.adset_id}`" class="mb-4">
        <p><strong>{{ t('Adset Name') }}:</strong> {{ adsetData?.adset_name }}</p>
      </a-card>

      <a-card :title="t('Configured Languages')">
        <div v-if="languages.length === 0" class="text-center text-gray-500 p-4">
          {{ t('No languages configured') }}
        </div>
        <div v-else>
          <a-tag
            v-for="lang in languages"
            :key="lang.label_name"
            color="blue"
            class="mb-2"
          >
            {{ lang.english_name }} ({{ lang.native_name }})
          </a-tag>
        </div>
      </a-card>
    </div>
  </a-modal>
</template>

<script setup lang="ts">
import { ref, watch, computed } from 'vue';
import { useI18n } from 'vue-i18n';
import { getLanguages } from '@/api/ads';
import type { ApiLanguageItem } from '@/api/ads/types';

interface AdsetData {
  adset_id: string;
  adset_name: string;
  targeting: {
    locales?: number[];
  };
}

interface Props {
  open: boolean;
  adsetData: AdsetData | null;
}

interface Emits {
  (e: 'cancel'): void;
}

const props = defineProps<Props>();
const emit = defineEmits<Emits>();
const { t } = useI18n();

const loading = ref(false);
const allLanguages = ref<ApiLanguageItem[]>([]);

// 获取语言数据
const fetchLanguages = async () => {
  try {
    loading.value = true;
    const response = await getLanguages();
    allLanguages.value = response.data;
  } catch (error) {
    console.error('Failed to fetch languages:', error);
  } finally {
    loading.value = false;
  }
};

// 计算当前Adset配置的语言
const languages = computed(() => {
  if (!props.adsetData?.targeting?.locales || allLanguages.value.length === 0) {
    return [];
  }

  const configuredLocales = props.adsetData.targeting.locales;
  return allLanguages.value.filter(lang =>
    lang.locales.some(locale => configuredLocales.includes(locale)),
  );
});

const handleCancel = () => {
  emit('cancel');
};

// 监听modal打开状态，打开时获取语言数据
watch(() => props.open, (newVal) => {
  if (newVal && allLanguages.value.length === 0) {
    fetchLanguages();
  }
});
</script>