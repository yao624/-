<template>
  <div class="basic-settings">
    <h2 class="section-title">{{ t('基础设置') }}</h2>

    <a-form :model="localFormData" layout="vertical">
      <!-- 创建模式 -->
      <a-form-item :label="t('创建模式')">
        <a-radio-group v-model:value="localFormData.creationMode">
          <a-radio-button value="standard">{{ t('标准') }}</a-radio-button>
          <a-radio-button value="quick">{{ t('快速') }}</a-radio-button>
        </a-radio-group>
      </a-form-item>

      <!-- 广告目标 -->
      <a-form-item :label="t('广告目标')">
        <a-radio-group v-model:value="localFormData.adGoal">
          <a-radio-button value="sales">{{ t('销量') }}</a-radio-button>
          <a-radio-button value="leads">{{ t('潜在客户') }}</a-radio-button>
          <a-radio-button value="engagement">{{ t('互动') }}</a-radio-button>
          <a-radio-button value="traffic">{{ t('流量') }}</a-radio-button>
          <a-radio-button value="awareness">{{ t('知名度') }}</a-radio-button>
        </a-radio-group>
      </a-form-item>

      <!-- 进阶赋能型目录广告 -->
      <a-form-item :label="t('进阶赋能型目录广告')">
        <a-switch v-model:checked="localFormData.advancedCatalogAds" />
      </a-form-item>

      <!-- 转化发生位置 -->
      <a-form-item :label="t('转化发生位置')">
        <a-radio-group v-model:value="localFormData.conversionLocation">
          <a-radio-button value="app">{{ t('应用') }}</a-radio-button>
          <a-radio-button value="website">{{ t('网站') }}</a-radio-button>
          <a-radio-button value="messaging">{{ t('消息应用') }}</a-radio-button>
        </a-radio-group>
      </a-form-item>

      <!-- 广告账户 -->
      <a-form-item :label="t('广告账户') + ' *'" required>
        <a-select
          v-model:value="localFormData.adAccount"
          :placeholder="t('请选择广告账户')"
          show-search
          :filter-option="filterOption"
        >
          <a-select-option v-for="account in adAccounts" :key="account.id" :value="account.id">
            {{ account.name }} ({{ account.id }})
          </a-select-option>
        </a-select>
      </a-form-item>

      <!-- FB个人号 -->
      <a-form-item :label="t('FB个人号') + ' *'" required>
        <a-select
          v-model:value="localFormData.fbPersonalAccount"
          :placeholder="t('请选择FB个人号')"
          show-search
          :filter-option="filterOption"
        >
          <a-select-option
            v-for="account in fbPersonalAccounts"
            :key="account.id"
            :value="account.id"
          >
            {{ account.name }} (ID: {{ account.fbId }})
          </a-select-option>
        </a-select>
      </a-form-item>
    </a-form>

    <h2 class="section-title" style="margin-top: 32px">{{ t('广告系列') }}</h2>

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
        <a-switch v-model:checked="localFormData.campaignStatus" />
      </a-form-item>

      <!-- 特殊广告类别 -->
      <a-form-item :label="t('特殊广告类别')">
        <a-select
          v-model:value="localFormData.specialAdCategory"
          :placeholder="t('请选择')"
          mode="multiple"
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
        <a-switch v-model:checked="localFormData.campaignBudgetOptimization" />
        <a-tooltip>
          <template #title>{{ t('开启后可以优化广告系列预算分配') }}</template>
          <question-circle-outlined class="info-icon" />
        </a-tooltip>
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
        <a-radio-group v-model:value="localFormData.spendLimitType">
          <a-radio value="unlimited">{{ t('不限') }}</a-radio>
          <a-radio value="custom">{{ t('自定义') }}</a-radio>
        </a-radio-group>
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
import { getAdAccounts, getFbPersonalAccounts, getSpecialAdCategories } from '../mock-data';

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
  creationMode: 'standard',
  adGoal: 'sales',
  advancedCatalogAds: false,
  conversionLocation: 'app',
  adAccount: undefined,
  fbPersonalAccount: undefined,
  campaignName: '',
  campaignStatus: true,
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

// 监听本地数据变化
watch(
  localFormData,
  (newVal) => {
    emit('update:formData', { ...newVal });
  },
  { deep: true },
);

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

// 数据
const adAccounts = ref<any[]>([]);
const fbPersonalAccounts = ref<any[]>([]);
const specialAdCategories = ref<any[]>([]);

// 过滤选项
const filterOption = (input: string, option: any) => {
  return option.children.toLowerCase().indexOf(input.toLowerCase()) >= 0;
};

// 插入动态名称
const insertDynamicName = (tag: string) => {
  localFormData.value.campaignName += `{${tag}}`;
};

// 切换展开
const toggleExpand = () => {
  isExpanded.value = !isExpanded.value;
};

// 加载数据
const loadData = async () => {
  try {
    adAccounts.value = await getAdAccounts();
    fbPersonalAccounts.value = await getFbPersonalAccounts();
    specialAdCategories.value = await getSpecialAdCategories();
  } catch (error) {
    console.error('加载数据失败:', error);
  }
};

loadData();
</script>

<style lang="less" scoped>
.basic-settings {
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

