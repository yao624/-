<template>
  <div class="step-four-creative">
    <h3 class="section-title">{{ t('配置广告创意') }}</h3>
    <a-form :model="localFormData" layout="vertical">
      <a-form-item :label="t('创意组')">
        <a-select
          v-model:value="localFormData.creativeGroup"
          :placeholder="t('请选择创意组')"
          style="width: 100%"
        >
          <a-select-option value="group1">{{ t('创意组1') }}</a-select-option>
          <a-select-option value="group2">{{ t('创意组2') }}</a-select-option>
        </a-select>
      </a-form-item>
    </a-form>
  </div>
</template>

<script lang="ts" setup>
import { ref, watch } from 'vue';
import { useI18n } from 'vue-i18n';

interface Props {
  formData: {
    creativeGroup: string | null;
  };
}

interface Emits {
  (e: 'update:formData', value: Props['formData']): void;
}

const props = defineProps<Props>();
const emit = defineEmits<Emits>();
const { t } = useI18n();

const localFormData = ref({ ...props.formData });

// 监听props变化
watch(
  () => props.formData,
  (newVal) => {
    localFormData.value = { ...newVal };
  },
  { deep: true },
);

// 监听本地数据变化
watch(
  localFormData,
  (newVal) => {
    emit('update:formData', { ...newVal });
  },
  { deep: true },
);
</script>

<style lang="less" scoped>
.step-four-creative {
  .section-title {
    font-size: 16px;
    font-weight: 500;
    margin-bottom: 24px;
    color: #262626;
  }
}
</style>

