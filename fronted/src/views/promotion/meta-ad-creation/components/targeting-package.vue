<template>
  <div class="targeting-package">
    <h2 class="section-title">{{ t('定向包') }}</h2>
    <a-form :model="localFormData" layout="vertical">
      <a-form-item>
        <a-checkbox v-model:checked="localFormData.selectExisting">
          {{ t('选择已有定向包') }}
        </a-checkbox>
      </a-form-item>
      <a-form-item :label="t('进阶赋能型受众')">
        <a-switch v-model:checked="localFormData.advancedAudience" />
      </a-form-item>
      <a-form-item :label="t('自定义受众')">
        <a-radio-group v-model:value="localFormData.customAudience">
          <a-radio-button value="unlimited">{{ t('不限') }}</a-radio-button>
          <a-radio-button value="custom">{{ t('自定义') }}</a-radio-button>
        </a-radio-group>
      </a-form-item>
      <a-form-item :label="t('最低年龄限制') + ' *'" required>
        <a-input-number v-model:value="localFormData.minAge" :min="13" :max="65" />
      </a-form-item>
      <a-form-item :label="t('性别')">
        <a-radio-group v-model:value="localFormData.gender">
          <a-radio-button value="all">{{ t('不限') }}</a-radio-button>
          <a-radio-button value="male">{{ t('男性') }}</a-radio-button>
          <a-radio-button value="female">{{ t('女性') }}</a-radio-button>
        </a-radio-group>
      </a-form-item>
      <a-form-item :label="t('细分定位')">
        <a-radio-group v-model:value="localFormData.detailedTargeting">
          <a-radio-button value="unlimited">{{ t('不限') }}</a-radio-button>
          <a-radio-button value="custom">{{ t('自定义') }}</a-radio-button>
        </a-radio-group>
      </a-form-item>
      <a-form-item :label="t('语言')">
        <a-select v-model:value="localFormData.language" :placeholder="t('请选择')">
          <a-select-option v-for="lang in languages" :key="lang" :value="lang">
            {{ lang }}
          </a-select-option>
        </a-select>
      </a-form-item>
    </a-form>
  </div>
</template>

<script lang="ts" setup>
import { ref, watch } from 'vue';
import { useI18n } from 'vue-i18n';

interface Props {
  formData: any;
}

interface Emits {
  (e: 'update:formData', value: any): void;
}

const props = defineProps<Props>();
const emit = defineEmits<Emits>();
const { t } = useI18n();

const localFormData = ref({
  selectExisting: false,
  advancedAudience: true,
  customAudience: 'unlimited',
  minAge: 18,
  gender: 'all',
  detailedTargeting: 'unlimited',
  language: undefined,
  ...props.formData,
});

const languages = ref(['中文', 'English', '日本語', '한국어', 'Español', 'Français']);

watch(
  localFormData,
  (newVal) => {
    emit('update:formData', { ...newVal });
  },
  { deep: true },
);
</script>

<style lang="less" scoped>
.targeting-package {
  .section-title {
    font-size: 18px;
    font-weight: 500;
    margin-bottom: 24px;
  }
}
</style>

