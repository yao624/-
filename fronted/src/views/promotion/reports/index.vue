<template>
  <div class="promotion-reports-page">
    <div class="promotion-reports">
      <!-- 左侧平台导航 -->
      <div class="platform-sidebar">
        <div
          v-for="platform in platforms"
          :key="platform.value"
          class="sidebar-item"
          :class="{ active: selectedPlatform === platform.value }"
          @click="selectPlatform(platform.value)"
        >
          <span class="platform-icon">{{ platform.icon }}</span>
          <span>{{ platform.label }}</span>
        </div>
      </div>

      <!-- 主内容区域 -->
      <div class="main-content">
        <!-- 子导航标签 -->
        <div class="sub-tabs">
          <a-tabs v-model:activeKey="activeTab" @change="handleTabChange">
            <a-tab-pane key="ad-account" :tab="t('广告账户')">
              <ad-account-report
                v-if="activeTab === 'ad-account'"
                :key="`ad-account-${selectedPlatform}`"
                :platform="selectedPlatform"
              />
            </a-tab-pane>
            <a-tab-pane key="campaign" :tab="t('广告系列')">
              <campaign-report
                v-if="activeTab === 'campaign'"
                :key="`campaign-${selectedPlatform}`"
                :platform="selectedPlatform"
              />
            </a-tab-pane>
            <a-tab-pane key="ad-group" :tab="t('广告组')">
              <ad-group-report
                v-if="activeTab === 'ad-group'"
                :key="`ad-group-${selectedPlatform}`"
                :platform="selectedPlatform"
              />
            </a-tab-pane>
            <a-tab-pane key="ad" :tab="t('广告')">
              <ad-report
                v-if="activeTab === 'ad'"
                :key="`ad-${selectedPlatform}`"
                :platform="selectedPlatform"
              />
            </a-tab-pane>
            <a-tab-pane key="material" :tab="t('素材')">
              <material-report
                v-if="activeTab === 'material'"
                :key="`material-${selectedPlatform}`"
                :platform="selectedPlatform"
              />
            </a-tab-pane>
          </a-tabs>
        </div>
      </div>
    </div>
  </div>
</template>

<script lang="ts" setup>
import { ref, onMounted, onErrorCaptured } from 'vue';
import { useI18n } from 'vue-i18n';
import AdAccountReport from './components/ad-account-report.vue';
import CampaignReport from './components/campaign-report.vue';
import AdGroupReport from './components/ad-group-report.vue';
import AdReport from './components/ad-report.vue';
import MaterialReport from './components/material-report.vue';

const { t } = useI18n();

const selectedPlatform = ref('meta');
const activeTab = ref('ad-account');

onErrorCaptured((err, _instance, info) => {
  console.error('推广报表页面错误:', err, info);
  return false;
});

onMounted(() => {
  console.log('推广报表页面已加载', {
    selectedPlatform: selectedPlatform.value,
    activeTab: activeTab.value,
  });
});

const platforms = ref([
  { value: 'meta', label: 'Meta', icon: '∞' },
]);

const selectPlatform = (platform: string) => {
  selectedPlatform.value = platform;
};

const handleTabChange = (key: string) => {
  activeTab.value = key;
};
</script>

<style lang="less" scoped>
.promotion-reports-page {
  width: 100%;
  min-height: calc(100vh - 120px);
  padding: 16px;
  box-sizing: border-box;
  background: #f0f2f5;
}

.promotion-reports {
  display: flex;
  gap: 0;
  width: 100%;
  height: 100%;
  min-height: calc(100vh - 152px);
  background: #f0f2f5;
  border-radius: 4px;
  overflow: hidden;
  box-shadow: 0 2px 8px rgba(0, 0, 0, 0.1);

  .platform-sidebar {
    width: 200px;
    min-width: 200px;
    max-width: 200px;
    background: #fff !important;
    padding: 16px 8px;
    display: flex !important;
    flex-direction: column;
    border-right: 1px solid #e8e8e8;
    overflow-y: auto;
    overflow-x: hidden;
    flex-shrink: 0;
    position: relative;
    z-index: 1;
    visibility: visible !important;
    opacity: 1 !important;

    .sidebar-item {
      display: flex;
      align-items: center;
      gap: 12px;
      padding: 12px 8px;
      cursor: pointer;
      border-radius: 4px;
      margin-bottom: 4px;
      transition: all 0.3s;
      color: #333;

      &:hover {
        background: #f5f5f5;
      }

      &.active {
        background: #e6f7ff;
        color: #1890ff;
        font-weight: 500;
      }

      .platform-icon {
        font-size: 18px;
        width: 24px;
        text-align: center;
        font-weight: normal;
      }
    }

    .sidebar-collapse {
      margin-top: auto;
      padding: 8px;
      cursor: pointer;
      color: #999;
      font-size: 12px;
      display: flex;
      align-items: center;
      gap: 8px;

      &:hover {
        color: #1890ff;
      }
    }
  }

  .main-content {
    flex: 1;
    min-width: 0;
    background: #fff;
    padding: 0;
    overflow: hidden;
    display: flex;
    flex-direction: column;
    position: relative;

    .sub-tabs {
      flex: 1;
      display: flex;
      flex-direction: column;
      overflow: hidden;

      :deep(.ant-tabs) {
        display: flex;
        flex-direction: column;
        height: 100%;
      }

      :deep(.ant-tabs-nav) {
        margin: 0;
        padding: 0 16px;
        background: #fff;
        border-bottom: 1px solid #e8e8e8;
        flex-shrink: 0;
      }

      :deep(.ant-tabs-content-holder) {
        flex: 1;
        overflow: auto;
        padding: 16px;
      }

      :deep(.ant-tabs-content) {
        height: 100%;
      }

      :deep(.ant-tabs-tabpane) {
        height: 100%;
        overflow: auto;
      }
    }
  }
}
</style>

