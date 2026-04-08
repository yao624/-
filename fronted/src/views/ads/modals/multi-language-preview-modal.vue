<template>
  <a-modal
    :title="t('Multi-language Preview')"
    :open="open"
    :width="600"
    :footer="null"
    @cancel="handleCancel"
  >
    <div class="multi-language-preview">
      <div class="ad-info">
        <h4>{{ adData?.ad_name || adData?.name || t('Untitled Ad') }}</h4>
        <p class="ad-id">{{ t('Ad ID') }}: {{ adData?.ad_id || adData?.id }}</p>
      </div>

      <a-divider />

      <div class="language-list">
        <h5>{{ t('Available Languages') }}</h5>
        <div class="language-buttons">
          <a-button
            v-for="language in availableLanguages"
            :key="language.labelName"
            type="primary"
            :loading="loadingLanguage === language.labelName"
            @click="handlePreviewLanguage(language)"
            class="language-btn"
            :size="'large'"
          >
            <global-outlined />
            {{ language.englishName }}
          </a-button>
        </div>
      </div>

      <div v-if="availableLanguages.length === 0" class="no-languages">
        <a-empty :description="t('No multi-language data found')" />
      </div>
    </div>
  </a-modal>
</template>

<script setup lang="ts">
import { ref, computed, watch } from 'vue';
import { message } from 'ant-design-vue';
import { useI18n } from 'vue-i18n';
import { GlobalOutlined } from '@ant-design/icons-vue';
import { getMultiLanguagePreview, getLanguages } from '@/api/ads';

interface Props {
  open: boolean;
  adData: any;
}

interface ApiLanguageItem {
  label_name: string;
  english_name: string;
  native_name: string;
  locale: number;
}

const props = defineProps<Props>();
const emit = defineEmits<{
  cancel: [];
}>();

const { t } = useI18n();
const loadingLanguage = ref<string>('');
const allLanguages = ref<ApiLanguageItem[]>([]);
// 缓存每个语言的预览URL
const cachedUrls = ref<Record<string, string>>({});

// 获取语言列表
const fetchLanguages = async () => {
  try {
    const { data } = await getLanguages();
    allLanguages.value = data;
  } catch (error) {
    console.error('获取语言列表失败:', error);
  }
};

// 映射语言标签到语言信息
const mapLanguageLabelToCode = (labelName: string): string => {
  const specialMappings: Record<string, string> = {
    'swedish': 'sv_SE',
    'afrikaans': 'af_ZA',
  };

  if (specialMappings[labelName]) {
    return specialMappings[labelName];
  }

  return labelName;
};

// 获取可用的语言列表
const availableLanguages = computed(() => {
  if (!props.adData?.creative) {
    return [];
  }

  const creative = props.adData.creative;
  const languages: Array<{ labelName: string; englishName: string; isDefault: boolean }> = [];

  // 检查是否为普通多语言广告（有asset_feed_spec）
  if (creative.asset_feed_spec?.bodies) {
    const assetFeedSpec = creative.asset_feed_spec;
    const bodies = assetFeedSpec.bodies;
    const customizationRules = assetFeedSpec.asset_customization_rules || [];

    // 找到默认语言
    const defaultRule = customizationRules.find((rule: any) => rule.is_default === true);
    const defaultLanguageLabelName = defaultRule?.body_label?.name;

    bodies.forEach((body: any) => {
      if (body.adlabels && body.adlabels.length > 0) {
        const labelName = body.adlabels[0].name;
        const isDefault = labelName === defaultLanguageLabelName;

        // 查找对应的语言信息
        let englishName = labelName;

        if (labelName === 'swedish') {
          englishName = 'Swedish';
        } else if (labelName === 'afrikaans') {
          englishName = 'Afrikaans';
        } else {
          const languageInfo = allLanguages.value.find(lang => lang.label_name === labelName);
          if (languageInfo) {
            englishName = languageInfo.english_name;
          }
        }

        // 如果是默认语言，添加 (default) 标识
        if (isDefault) {
          englishName += ' (default)';
        }

        languages.push({
          labelName,
          englishName,
          isDefault,
        });
      }
    });
  }
  // 检查是否为目录多语言广告（有customization_rules_spec）
  else if (creative.object_story_spec?.template_data?.customization_rules_spec) {
    const customizationRules = creative.object_story_spec.template_data.customization_rules_spec;

    if (Array.isArray(customizationRules) && customizationRules.length > 0) {
      customizationRules.forEach((rule: any) => {
        if (rule.customization_spec?.language) {
          const labelName = rule.customization_spec.language;
          const isDefault = false; // 目录广告暂时没有明确的默认语言标识

          // 查找对应的语言信息
          let englishName = labelName;

          if (labelName === 'swedish') {
            englishName = 'Swedish';
          } else if (labelName === 'afrikaans') {
            englishName = 'Afrikaans';
          } else {
            const languageInfo = allLanguages.value.find(lang => lang.label_name === labelName);
            if (languageInfo) {
              englishName = languageInfo.english_name;
            }
          }

          languages.push({
            labelName,
            englishName,
            isDefault,
          });
        }
      });
    }
  }

  if (languages.length === 0) {
    return [];
  }

  // 去重
  const uniqueLanguages = languages.filter((lang, index, self) =>
    index === self.findIndex(l => l.labelName === lang.labelName),
  );

  // 排序：默认语言排在第一位
  uniqueLanguages.sort((a, b) => {
    if (a.isDefault) return -1;
    if (b.isDefault) return 1;
    return a.englishName.localeCompare(b.englishName);
  });

  return uniqueLanguages;
});

// 处理语言预览
const handlePreviewLanguage = async (language: { labelName: string; englishName: string; isDefault: boolean }) => {
  if (!props.adData) {
    message.error(t('Ad data not available'));
    return;
  }

  // 检查是否已缓存URL
  if (cachedUrls.value[language.labelName]) {
    console.log('使用缓存的预览URL:', cachedUrls.value[language.labelName]);
    window.open(cachedUrls.value[language.labelName], '_blank');
    message.success(t('Preview opened in new tab'));
    return;
  }

  loadingLanguage.value = language.labelName;

  try {
    const params = {
      ad_account: props.adData.ad_account_id,
      ad_id: props.adData.ad_id,
      label_name: language.labelName,
    };

    console.log('调用多语言预览接口，参数:', params);

    const response = await getMultiLanguagePreview(params);

    if (response.success && response.data?.url) {
      // 缓存URL
      cachedUrls.value[language.labelName] = response.data.url;

      // 在新标签页打开预览链接
      window.open(response.data.url, '_blank');
      message.success(t('Preview opened in new tab'));
    } else {
      message.error(response.message || t('Failed to get preview link'));
    }
  } catch (error) {
    console.error('获取多语言预览失败:', error);
    message.error(t('Failed to get preview link'));
  } finally {
    loadingLanguage.value = '';
  }
};

// 处理取消
const handleCancel = () => {
  emit('cancel');
};

// 监听模态框打开状态，获取语言列表
watch(() => props.open, (isOpen) => {
  if (isOpen) {
    fetchLanguages();
  } else {
    // 模态框关闭时清空缓存
    cachedUrls.value = {};
  }
}, { immediate: true });

// 监听广告数据变化，清空缓存
watch(() => props.adData, () => {
  cachedUrls.value = {};
}, { deep: true });
</script>

<style lang="less" scoped>
.multi-language-preview {
  .ad-info {
    text-align: center;
    margin-bottom: 16px;

    h4 {
      margin-bottom: 8px;
      color: #262626;
    }

    .ad-id {
      color: #8c8c8c;
      font-size: 14px;
      margin: 0;
    }
  }

  .language-list {
    h5 {
      margin-bottom: 16px;
      color: #262626;
    }

    .language-buttons {
      display: flex;
      flex-wrap: wrap;
      gap: 12px;

      .language-btn {
        display: flex;
        align-items: center;
        gap: 8px;
        height: 48px;
        padding: 0 20px;
        border-radius: 8px;
        font-weight: 500;

        &:hover {
          transform: translateY(-2px);
          box-shadow: 0 4px 12px rgba(24, 144, 255, 0.3);
        }

        &.ant-btn-loading {
          transform: none;
          box-shadow: none;
        }
      }
    }
  }

  .no-languages {
    text-align: center;
    padding: 40px 0;
  }
}
</style>