<template>
  <a-modal
    :title="t('Info')"
    :open="open"
    :width="800"
    :confirmLoading="loading"
    @ok="
      () => {
        reset();
        $emit('ok');
      }
    "
    @cancel="
      () => {
        reset();
        $emit('cancel');
      }
    "
  >
    <!-- 单语言显示 -->
    <div v-if="!modelRef.isMultiLanguage">
      <a-descriptions title="" bordered>
        <a-descriptions-item :label="t('Primary text')" :span="3">
          <copy-outlined
            style="color: #1677ff"
            v-if="modelRef.primary_text"
            @click="copyCell(modelRef.primary_text)"
          />
          &nbsp;
          {{ modelRef.primary_text }}
        </a-descriptions-item>
        <a-descriptions-item :label="t('Headline')" :span="3">
          <copy-outlined
            style="color: #1677ff"
            v-if="modelRef.headline"
            @click="copyCell(modelRef.headline)"
          />
          &nbsp;{{ modelRef.headline }}
        </a-descriptions-item>
        <a-descriptions-item :label="t('Description')" :span="3">
          <copy-outlined
            style="color: #1677ff"
            v-if="modelRef.description"
            @click="copyCell(modelRef.description)"
          />
          &nbsp;{{ modelRef.description }}
        </a-descriptions-item>
        <a-descriptions-item :label="t('URL')" :span="3">
          <copy-outlined style="color: #1677ff" v-if="modelRef.url" @click="copyCell(modelRef.url)" />
          &nbsp; {{ modelRef.url }}
        </a-descriptions-item>
        <a-descriptions-item :label="canPreviewAds ? t('Track parameters') : 'Pixel'" :span="3">
          <copy-outlined
            style="color: #1677ff"
            v-if="canPreviewAds ? modelRef.url_tags : getPixelValue(modelRef.url_tags)"
            @click="copyCell(canPreviewAds ? modelRef.url_tags : getPixelValue(modelRef.url_tags))"
          />
          &nbsp;{{ canPreviewAds ? modelRef.url_tags : getPixelValue(modelRef.url_tags) }}
        </a-descriptions-item>
      </a-descriptions>
    </div>

    <!-- 多语言显示 -->
    <div v-else>
      <!-- 语言选择器Tab -->
      <a-tabs
        :activeKey="activeLanguageKey"
        type="card"
        @change="handleTabChange"
        @tabClick="handleTabClick"
      >
        <a-tab-pane
          v-for="language in modelRef.languages"
          :key="language.languageCode"
          :tab="language.languageName + (language.isDefault ? ' (' + t('Default') + ')' : '')"
          :title="language.nativeName"
        >
          <!-- 空的TabPane，内容在下面统一显示 -->
        </a-tab-pane>
      </a-tabs>

      <!-- 当前活跃语言的内容 -->
      <div v-if="currentLanguage" style="margin-top: 16px;">
        <a-descriptions title="" bordered>
          <a-descriptions-item :label="t('Primary text')" :span="3">
            <copy-outlined
              style="color: #1677ff"
              v-if="currentLanguage.primary_text"
              @click="copyCell(currentLanguage.primary_text)"
            />
            &nbsp;
            {{ currentLanguage.primary_text }}
          </a-descriptions-item>
          <a-descriptions-item :label="t('Headline')" :span="3">
            <copy-outlined
              style="color: #1677ff"
              v-if="currentLanguage.headline"
              @click="copyCell(currentLanguage.headline)"
            />
            &nbsp;{{ currentLanguage.headline }}
          </a-descriptions-item>
          <a-descriptions-item :label="t('Description')" :span="3">
            <copy-outlined
              style="color: #1677ff"
              v-if="currentLanguage.description"
              @click="copyCell(currentLanguage.description)"
            />
            &nbsp;{{ currentLanguage.description }}
          </a-descriptions-item>
          <a-descriptions-item :label="t('URL')" :span="3">
            <copy-outlined style="color: #1677ff" v-if="currentLanguage.url" @click="copyCell(currentLanguage.url)" />
            &nbsp; {{ currentLanguage.url }}
          </a-descriptions-item>
          <a-descriptions-item :label="canPreviewAds ? t('Track parameters') : 'Pixel'" :span="3">
            <copy-outlined
              style="color: #1677ff"
              v-if="canPreviewAds ? currentLanguage.url_tags : getPixelValue(currentLanguage.url_tags)"
              @click="copyCell(canPreviewAds ? currentLanguage.url_tags : getPixelValue(currentLanguage.url_tags))"
            />
            &nbsp;{{ canPreviewAds ? currentLanguage.url_tags : getPixelValue(currentLanguage.url_tags) }}
          </a-descriptions-item>
        </a-descriptions>
      </div>
    </div>
  </a-modal>
</template>

<script lang="ts">
import type { PostInfoModel } from '@/utils/fb-interfaces';
import type { PropType } from 'vue';
import { defineComponent, ref, reactive, toRaw, watchEffect, computed } from 'vue';
import { useI18n } from 'vue-i18n';
import useClipboard from 'vue-clipboard3';
import { CopyOutlined } from '@ant-design/icons-vue';
import { message } from 'ant-design-vue';
import { Action } from '@/api/user/login';
import { useAuth } from '@/utils/authority';

const formLayout = {
  labelCol: {
    xs: { span: 24 },
    sm: { span: 7 },
  },
  wrapperCol: {
    xs: { span: 24 },
    sm: { span: 13 },
  },
};

export default defineComponent({
  name: 'InfoModal',
  components: {
    CopyOutlined,
  },
  props: {
    open: {
      type: Boolean,
      required: true,
    },
    model: {
      type: Object as PropType<PostInfoModel | null>,
      default: () => null,
    },
  },
  emits: ['ok', 'cancel'],
  setup(props) {
    const { t } = useI18n();
    const loading = ref(false);
    const { toClipboard } = useClipboard();
    const activeLanguageKey = ref('');

    const initValues = (): PostInfoModel => ({
      url: '',
      url_tags: '',
      primary_text: '',
      headline: '',
      description: '',
      isMultiLanguage: false,
      languages: [],
    });

    const canPreviewAds = useAuth([Action.PREVIEW]);

    const modelRef = reactive<PostInfoModel>(initValues());

    watchEffect(() => {
      if (props.model) {
        const raw = toRaw(props.model) as PostInfoModel;
        Object.assign(modelRef, raw);

        // 设置默认活跃语言标签 - 只在 activeLanguageKey 为空或者语言不存在时设置
        if (modelRef.isMultiLanguage && modelRef.languages && modelRef.languages.length > 0) {
          const currentKeyExists = modelRef.languages.some(lang => lang.languageCode === activeLanguageKey.value);

          if (!activeLanguageKey.value || !currentKeyExists) {
            const defaultLanguage = modelRef.languages.find(lang => lang.isDefault);
            const newKey = defaultLanguage?.languageCode || modelRef.languages[0].languageCode;
            activeLanguageKey.value = newKey;
          }
        }
      } else if (props.model === null) {
        Object.assign(modelRef, initValues());
        activeLanguageKey.value = ''; // 重置语言键
      }
    });

    const handleSubmit = () => {
      // Object.assign(modelRef, initValues());
    };

    const reset = () => {
      // Object.assign(modelRef, initValues());
    };

    const copyCell = async (text: any) => {
      try {
        await toClipboard(text);
        message.success(t('Copied'));
      } catch (e) {
        console.error(e);
        message.error(t('Error'));
      }
    };

    const handleTabChange = (key: string) => {
      activeLanguageKey.value = key;
    };

    const handleTabClick = (key: string) => {
      activeLanguageKey.value = key;
    };

    const currentLanguage = computed(() => {
      if (modelRef.isMultiLanguage && modelRef.languages && modelRef.languages.length > 0) {
        return modelRef.languages.find(lang => lang.languageCode === activeLanguageKey.value);
      }
      return null;
    });

    const getPixelValue = (urlTags: string | undefined) => {
      if (!urlTags) {
        return '';
      }
      const match = urlTags.match(/pixel=([^&]*)/);
      return match ? match[1] : '';
    };

    return {
      t,
      ...formLayout,
      initValues,
      modelRef,
      loading,
      handleSubmit,
      reset,
      copyCell,
      activeLanguageKey,
      handleTabChange,
      handleTabClick,
      currentLanguage,
      canPreviewAds,
      getPixelValue,
    };
  },
});
</script>
