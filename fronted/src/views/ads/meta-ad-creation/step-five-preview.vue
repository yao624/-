<template>
  <div class="step-five-preview">
    <h3 class="section-title">{{ t('预览发布') }}</h3>
    <a-descriptions :column="2" bordered>
      <a-descriptions-item :label="t('售卖类型')">
        {{ getPurchaseTypeText(formData.stepOne?.purchaseType) }}
      </a-descriptions-item>
      <a-descriptions-item :label="t('推广目标')">
        {{ getObjectiveText(formData.stepOne?.objective) }}
      </a-descriptions-item>
      <a-descriptions-item :label="t('广告账户')">
        {{ formData.stepOne?.adAccount || '-' }}
      </a-descriptions-item>
      <a-descriptions-item :label="t('预算')">
        {{ formData.stepTwo?.budget || '-' }}
      </a-descriptions-item>
      <a-descriptions-item :label="t('出价策略')">
        {{ formData.stepThree?.bidStrategy || '-' }}
      </a-descriptions-item>
      <a-descriptions-item :label="t('优化目标')">
        {{ formData.stepThree?.optimization || '-' }}
      </a-descriptions-item>
    </a-descriptions>
  </div>
</template>

<script lang="ts" setup>
import { useI18n } from 'vue-i18n';

interface Props {
  formData: any;
}

const props = defineProps<Props>();
const { t } = useI18n();

const getPurchaseTypeText = (type: string) => {
  if (type === 'auction') return t('竞拍');
  if (type === 'reservation') return t('预定');
  return '-';
};

const getObjectiveText = (objective: string) => {
  const objectiveMap: Record<string, string> = {
    APP_INSTALLS: t('应用推广'),
    OUTCOME_SALES: t('销量'),
    OUTCOME_LEADS: t('潜在客户'),
    OUTCOME_ENGAGEMENT: t('互动'),
    OUTCOME_AWARENESS: t('知名度'),
    OUTCOME_TRAFFIC: t('流量'),
  };
  return objectiveMap[objective] || '-';
};
</script>

<style lang="less" scoped>
.step-five-preview {
  .section-title {
    font-size: 16px;
    font-weight: 500;
    margin-bottom: 24px;
    color: #262626;
  }
}
</style>














