<template>
  <div class="ad-campaign">
    <h2 class="section-title">{{ t('广告系列') }}</h2>
    <a-form :model="localFormData" layout="vertical">
      <!-- 广告系列名称 -->
      <a-form-item :label="t('广告系列名称') + ' *'" required>
        <a-input
          v-model:value="localFormData.campaignName"
          :placeholder="t('请输入')"
          :maxlength="200"
          show-count
        />
        <div class="dynamic-name-tags">
          <a-tag
            v-for="tag in dynamicNameTags"
            :key="tag"
            class="name-tag"
            @click="insertDynamicName(tag)"
          >
            {{ tag }}
          </a-tag>
          <a-button type="link" size="small" @click="toggleExpand">
            {{ isExpanded ? t('折叠') : t('展开') }}
          </a-button>
        </div>
      </a-form-item>

      <!-- 广告系列状态 -->
      <a-form-item :label="t('广告系列状态')">
        <a-switch v-model:checked="localFormData.status" />
      </a-form-item>

      <!-- 特殊广告类别 -->
      <a-form-item :label="t('特殊广告类别')">
        <a-select
          v-model:value="localFormData.specialAdCategory"
          :placeholder="t('请选择')"
          mode="multiple"
          :max-tag-count="3"
        >
          <a-select-option
            v-for="category in specialAdCategories"
            :key="category.value"
            :value="category.value"
          >
            {{ category.label }}
          </a-select-option>
        </a-select>
      </a-form-item>

      <!-- 赋能型广告系列预算优化 -->
      <a-form-item :label="t('赋能型广告系列预算优化')">
        <a-space>
          <a-switch v-model:checked="localFormData.campaignBudgetOptimization" />
          <a-tooltip>
            <template #title>{{ t('开启后可以优化广告系列预算分配') }}</template>
            <question-circle-outlined class="info-icon" />
          </a-tooltip>
        </a-space>
      </a-form-item>

      <!-- 广告系列预算 -->
      <a-form-item :label="t('广告系列预算')">
        <a-radio-group v-model:value="localFormData.budgetType">
          <a-radio value="daily">{{ t('单日预算') }}</a-radio>
          <a-radio value="total">{{ t('总预算') }}</a-radio>
        </a-radio-group>
        <a-input-number
          v-model:value="localFormData.budget"
          :min="0"
          :precision="2"
          style="width: 100%; margin-top: 8px"
          :placeholder="t('请输入')"
        >
          <template #addonAfter>USD</template>
        </a-input-number>
      </a-form-item>

      <!-- 广告系列竞价策略 -->
      <a-form-item :label="t('广告系列竞价策略')">
        <a-radio-group v-model:value="localFormData.biddingStrategy">
          <a-radio-button value="highest_volume">{{ t('最高数量') }}</a-radio-button>
          <a-radio-button value="cost_per_action">{{ t('单次成效费用目标') }}</a-radio-button>
          <a-radio-button value="bid_cap">{{ t('竞价上限') }}</a-radio-button>
          <a-radio-button value="ad_spend_return">{{ t('广告花费回报目标') }}</a-radio-button>
        </a-radio-group>
      </a-form-item>

      <!-- 投放时段 -->
      <a-form-item :label="t('投放时段')">
        <a-radio-group v-model:value="localFormData.deliverySchedule">
          <a-radio-button value="all_day">{{ t('全天投放广告') }}</a-radio-button>
          <a-radio-button value="segmented">{{ t('分时间段投放') }}</a-radio-button>
        </a-radio-group>
      </a-form-item>

      <!-- 投放类型 -->
      <a-form-item :label="t('投放类型')">
        <a-radio-group v-model:value="localFormData.deliveryType">
          <a-radio value="uniform">{{ t('匀速') }}</a-radio>
        </a-radio-group>
      </a-form-item>

      <!-- 广告系列花费限额 -->
      <a-form-item :label="t('广告系列花费限额')">
        <a-space>
          <a-radio-group v-model:value="localFormData.spendLimitType">
            <a-radio value="unlimited">{{ t('不限') }}</a-radio>
            <a-radio value="custom">{{ t('自定义') }}</a-radio>
          </a-radio-group>
          <a-tooltip>
            <template #title>{{ t('花费限额说明') }}</template>
            <question-circle-outlined class="info-icon" />
          </a-tooltip>
        </a-space>
        <a-alert
          v-if="localFormData.spendLimitType === 'custom'"
          :message="t('花费限额不是预算,如需设置广告系列预算请开启赋能型广告系列预算优化(CBO)')"
          type="warning"
          show-icon
          style="margin-top: 8px"
        />
      </a-form-item>
    </a-form>
  </div>
</template>

<script lang="ts" setup>
import { ref, computed, watch } from 'vue';
import { useI18n } from 'vue-i18n';
import { QuestionCircleOutlined } from '@ant-design/icons-vue';
import { getSpecialAdCategories } from '../mock-data';

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
  campaignName: '',
  status: true,
  specialAdCategory: [],
  campaignBudgetOptimization: true,
  budgetType: 'daily',
  budget: undefined,
  biddingStrategy: 'highest_volume',
  deliverySchedule: 'all_day',
  deliveryType: 'uniform',
  spendLimitType: 'unlimited',
  ...props.formData,
});

// 动态名称标签
const isExpanded = ref(false);
const dynamicNameTags = computed(() => {
  const basic = [
    t('系统用户名'),
    t('账户名'),
    t('账户备注名'),
    t('地区组名称'),
    t('定向包名称'),
    t('创意组名称'),
  ];
  if (isExpanded.value) {
    return [
      ...basic,
      t('首个素材名称'),
      t('首个素材名称(含格式)'),
      t('首个素材备注'),
      t('地区'),
      t('地区2(示例:CN)'),
      t('地区3(示例:CHN)'),
      t('语言'),
      t('创建日期(yyyy/mm/dd)'),
      t('时分秒'),
      t('开始日期(yyyy/mm/dd)'),
      t('开始时间'),
      t('App OS'),
      t('性别'),
      t('年龄'),
      t('出价'),
      t('成效目标'),
      t('版位'),
      t('原名称'),
      t('账户ID'),
      t('序号'),
      t('推广应用'),
      t('广告系列名称'),
      t('首个素材文件夹名称'),
    ];
  }
  return basic;
});

const specialAdCategories = ref<any[]>([]);

// 插入动态名称
const insertDynamicName = (tag: string) => {
  localFormData.value.campaignName += `{${tag}}`;
};

// 切换展开
const toggleExpand = () => {
  isExpanded.value = !isExpanded.value;
};

watch(
  localFormData,
  (newVal) => {
    emit('update:formData', { ...newVal });
  },
  { deep: true },
);

const loadData = async () => {
  try {
    specialAdCategories.value = await getSpecialAdCategories();
  } catch (error) {
    console.error('加载数据失败:', error);
  }
};

loadData();
</script>

<style lang="less" scoped>
.ad-campaign {
  .section-title {
    font-size: 18px;
    font-weight: 500;
    margin-bottom: 24px;
    color: #333;
  }

  .dynamic-name-tags {
    margin-top: 8px;
    display: flex;
    flex-wrap: wrap;
    gap: 8px;

    .name-tag {
      cursor: pointer;
      user-select: none;

      &:hover {
        background: #e6f7ff;
        border-color: #1890ff;
      }
    }
  }

  .info-icon {
    margin-left: 8px;
    color: #1890ff;
    cursor: help;
  }
}
</style>

