<template>
  <div class="step-three-adset">
    <h3 class="section-title">{{ t('配置广告组') }}</h3>
    <a-form :model="localFormData" layout="vertical">
      <a-form-item :label="t('预算排期')">
        <a-date-picker
          v-model:value="localFormData.schedule"
          show-time
          style="width: 100%"
          :placeholder="t('请选择排期')"
        />
      </a-form-item>
      <a-form-item :label="t('出价策略')">
        <a-select
          v-model:value="localFormData.bidStrategy"
          :placeholder="t('请选择出价策略')"
        >
          <a-select-option value="LOWEST_COST_WITHOUT_CAP">
            {{ t('最高量') }}
          </a-select-option>
          <a-select-option value="LOWEST_COST_WITH_BID_CAP">
            {{ t('出价上限') }}
          </a-select-option>
          <a-select-option value="COST_CAP">
            {{ t('单次结果费用目标') }}
          </a-select-option>
        </a-select>
      </a-form-item>
      <a-form-item :label="t('优化与投放')">
        <a-select
          v-model:value="localFormData.optimization"
          :placeholder="t('请选择优化目标')"
        >
          <a-select-option value="OFFSITE_CONVERSIONS">
            {{ t('最大化转化次数') }}
          </a-select-option>
          <a-select-option value="LEAD_GENERATION">
            {{ t('最大化潜在客户数量') }}
          </a-select-option>
        </a-select>
      </a-form-item>
      <a-form-item :label="t('定向')">
        <a-textarea
          v-model:value="localFormData.targeting"
          :rows="4"
          :placeholder="t('请输入定向设置')"
        />
      </a-form-item>
    </a-form>
  </div>
</template>

<script lang="ts" setup>
import { ref, watch } from 'vue';
import { useI18n } from 'vue-i18n';

interface Props {
  formData: {
    budget: number | null;
    schedule: any;
    bidStrategy: string | null;
    optimization: string | null;
    targeting: string | null;
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
.step-three-adset {
  .section-title {
    font-size: 16px;
    font-weight: 500;
    margin-bottom: 24px;
    color: #262626;
  }
}
</style>

