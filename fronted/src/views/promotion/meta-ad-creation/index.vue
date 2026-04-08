<template>
  <page-container :show-page-header="false" title="Meta广告创建">
    <div class="meta-ad-creation-container">
      <!-- 左侧步骤导航 -->
      <div class="steps-sidebar">
        <div
          v-for="(step, index) in steps"
          :key="index"
          class="step-item"
          :class="{ active: currentStep === index }"
          @click="goToStep(index)"
        >
          <div class="step-dot" :class="{ active: currentStep === index }"></div>
          <div class="step-title">{{ step.title }}</div>
        </div>
      </div>

      <!-- 主内容区域 -->
      <div class="main-content">
        <!-- 基础设置 -->
        <div v-show="currentStep === 0" class="step-content">
          <basic-settings
            :form-data="formData.basicSettings"
            @update:form-data="formData.basicSettings = $event"
          />
        </div>

        <!-- 广告系列 -->
        <div v-show="currentStep === 1" class="step-content">
          <ad-campaign
            :form-data="formData.adCampaign"
            @update:form-data="formData.adCampaign = $event"
          />
        </div>

        <!-- 投放内容 -->
        <div v-show="currentStep === 2" class="step-content">
          <delivery-content
            :form-data="formData.deliveryContent"
            @update:form-data="formData.deliveryContent = $event"
          />
        </div>

        <!-- 地区组 -->
        <div v-show="currentStep === 3" class="step-content">
          <region-group
            :form-data="formData.regionGroup"
            @update:form-data="formData.regionGroup = $event"
          />
        </div>

        <!-- 版位 -->
        <div v-show="currentStep === 4" class="step-content">
          <placement
            :form-data="formData.placement"
            @update:form-data="formData.placement = $event"
          />
        </div>

        <!-- 定向包 -->
        <div v-show="currentStep === 5" class="step-content">
          <targeting-package
            :form-data="formData.targetingPackage"
            @update:form-data="formData.targetingPackage = $event"
          />
        </div>

        <!-- 出价和预算 -->
        <div v-show="currentStep === 6" class="step-content">
          <bid-budget
            :form-data="formData.bidBudget"
            @update:form-data="formData.bidBudget = $event"
          />
        </div>

        <!-- 创意设置 -->
        <div v-show="currentStep === 7" class="step-content">
          <creative-settings
            :form-data="formData.creativeSettings"
            @update:form-data="formData.creativeSettings = $event"
          />
        </div>

        <!-- 创意组 -->
        <div v-show="currentStep === 8" class="step-content">
          <creative-group
            :form-data="formData.creativeGroup"
            @update:form-data="formData.creativeGroup = $event"
          />
        </div>

        <!-- 底部操作按钮 -->
        <div class="footer-actions">
          <a-button @click="handleReset">{{ t('重置') }}</a-button>
          <a-button @click="handleSaveDraft">{{ t('保存草稿并退出') }}</a-button>
          <a-button type="primary" @click="handlePreview">{{ t('预览') }}</a-button>
          <a-button @click="handleSaveTemplate">{{ t('保存模板') }}</a-button>
        </div>
      </div>

      <!-- 右侧配置摘要 -->
      <div class="config-summary">
        <a-card :title="t('Meta')" size="small">
          <template #extra>
            <a-button type="link" size="small">{{ t('编辑') }}</a-button>
          </template>
          <div class="summary-content">
            <div class="account-count">{{ t('已选账户数量') }}: {{ selectedAccountCount }}</div>
            <a-table
              :columns="summaryColumns"
              :data-source="summaryData"
              :pagination="false"
              size="small"
            >
              <template #bodyCell="{ column, record }">
                <template v-if="column.dataIndex === 'action'">
                  <a-button type="link" size="small" @click="handleAddNew">
                    {{ t('+ 新增') }}
                  </a-button>
                </template>
              </template>
            </a-table>
            <div class="summary-footer">
              <a-button type="link" size="small" @click="handleClear">{{ t('清空') }}</a-button>
              <span class="item-count">{{ currentItemCount }}/{{ maxItemCount }}</span>
            </div>
          </div>
        </a-card>
      </div>
    </div>
  </page-container>
</template>

<script lang="ts" setup>
import { ref, reactive } from 'vue';
import { useI18n } from 'vue-i18n';
import BasicSettings from './components/basic-settings.vue';
import AdCampaign from './components/ad-campaign.vue';
import DeliveryContent from './components/delivery-content.vue';
import RegionGroup from './components/region-group.vue';
import Placement from './components/placement.vue';
import TargetingPackage from './components/targeting-package.vue';
import BidBudget from './components/bid-budget.vue';
import CreativeSettings from './components/creative-settings.vue';
import CreativeGroup from './components/creative-group.vue';

const { t } = useI18n();

// 步骤列表
const steps = ref([
  { title: t('基础设置') },
  { title: t('广告系列') },
  { title: t('投放内容') },
  { title: t('地区组') },
  { title: t('版位') },
  { title: t('定向包') },
  { title: t('出价和预算') },
  { title: t('创意设置') },
  { title: t('创意组') },
]);

// 当前步骤
const currentStep = ref(0);

// 表单数据
const formData = reactive({
  basicSettings: {},
  adCampaign: {},
  deliveryContent: {},
  regionGroup: {},
  placement: {},
  targetingPackage: {},
  bidBudget: {},
  creativeSettings: {},
  creativeGroup: {},
});

// 配置摘要
const selectedAccountCount = ref(1);
const currentItemCount = ref(1);
const maxItemCount = ref(20);

const summaryColumns = [
  { title: t('属性'), dataIndex: 'attribute', key: 'attribute' },
  { title: t('数量'), dataIndex: 'quantity', key: 'quantity' },
  { title: t('拆分规则'), dataIndex: 'splitRule', key: 'splitRule' },
];

const summaryData = ref([
  { attribute: t('地区组'), quantity: 1, splitRule: t('广告组') },
  { attribute: t('定向包'), quantity: 1, splitRule: t('广告组') },
  { attribute: t('出价和预算'), quantity: 1, splitRule: t('广告组') },
  { attribute: t('创意组'), quantity: 1, splitRule: t('广告组') },
]);

// 跳转到指定步骤
const goToStep = (index: number) => {
  currentStep.value = index;
};

// 重置
const handleReset = () => {
  // TODO: 重置表单数据
};

// 保存草稿
const handleSaveDraft = () => {
  // TODO: 保存草稿
};

// 预览
const handlePreview = () => {
  // TODO: 预览
};

// 保存模板
const handleSaveTemplate = () => {
  // TODO: 保存模板
};

// 新增
const handleAddNew = () => {
  // TODO: 新增配置项
};

// 清空
const handleClear = () => {
  // TODO: 清空配置
};
</script>

<style lang="less" scoped>
.meta-ad-creation-container {
  display: flex;
  gap: 16px;
  height: calc(100vh - 120px);

  .steps-sidebar {
    width: 200px;
    background: #fff;
    border-radius: 4px;
    padding: 16px;
    overflow-y: auto;

    .step-item {
      display: flex;
      align-items: center;
      gap: 12px;
      padding: 12px 8px;
      cursor: pointer;
      border-radius: 4px;
      margin-bottom: 8px;
      transition: all 0.3s;

      &:hover {
        background: #f5f5f5;
      }

      &.active {
        background: #e6f7ff;
        color: #1890ff;
      }

      .step-dot {
        width: 8px;
        height: 8px;
        border-radius: 50%;
        background: #d9d9d9;
        transition: all 0.3s;

        &.active {
          background: #1890ff;
          width: 12px;
          height: 12px;
        }
      }

      .step-title {
        font-size: 14px;
      }
    }
  }

  .main-content {
    flex: 1;
    background: #fff;
    border-radius: 4px;
    padding: 24px;
    display: flex;
    flex-direction: column;
    overflow-y: auto;

    .step-content {
      flex: 1;
    }

    .footer-actions {
      display: flex;
      gap: 8px;
      justify-content: flex-end;
      padding-top: 24px;
      border-top: 1px solid #f0f0f0;
      margin-top: 24px;
    }
  }

  .config-summary {
    width: 300px;
    background: #fff;
    border-radius: 4px;
    padding: 16px;

    .summary-content {
      .account-count {
        margin-bottom: 16px;
        color: #666;
        font-size: 14px;
      }

      .summary-footer {
        display: flex;
        justify-content: space-between;
        align-items: center;
        margin-top: 16px;
        padding-top: 16px;
        border-top: 1px solid #f0f0f0;

        .item-count {
          color: #999;
          font-size: 12px;
        }
      }
    }
  }
}
</style>

