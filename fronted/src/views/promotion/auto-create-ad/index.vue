<template>
  <div class="auto-create-ad-container">
    <div class="filter-bar">
      <a-form layout="inline" :model="filterForm">
        <a-form-item :label="t('模板: ')">
          <a-input v-model:value="filterForm.template" :placeholder="t('请输入')" style="width: 160px" />
        </a-form-item>
        <a-form-item :label="t('状态: ')">
          <a-select v-model:value="filterForm.status" :placeholder="t('不限')" style="width: 120px" allow-clear>
            <a-select-option value="active">{{ t('已开启') }}</a-select-option>
            <a-select-option value="inactive">{{ t('已关闭') }}</a-select-option>
          </a-select>
        </a-form-item>
        <a-form-item :label="t('语言: ')">
          <a-select v-model:value="filterForm.language" :placeholder="t('请选择')" style="width: 120px" allow-clear>
            <a-select-option value="zh">中文</a-select-option>
            <a-select-option value="en">English</a-select-option>
          </a-select>
        </a-form-item>
        <a-form-item :label="t('创建人: ')">
          <a-select v-model:value="filterForm.creator" :placeholder="t('Z')" style="width: 120px" allow-clear>
            <a-select-option value="Z">Z</a-select-option>
          </a-select>
        </a-form-item>
      </a-form>
    </div>

    <div class="action-bar">
      <div class="left-actions">
        <a-dropdown :trigger="['click']">
          <a-button type="primary">
            <template #icon><plus-outlined /></template>
            {{ t('添加模板') }}
          </a-button>
          <template #overlay>
            <a-menu @click="handleMenuClick">
              <a-menu-item key="meta">Meta</a-menu-item>
            </a-menu>
          </template>
        </a-dropdown>
        <a-button @click="handleRefresh" class="ml-8">
          <template #icon><reload-outlined /></template>
          {{ t('刷新') }}
        </a-button>
      </div>
      <div class="right-info">
        <a-alert type="info" show-icon class="custom-alert">
          <template #message>
            <span>{{ t('本月已创建广告0个，上限100000个，如需提升上限，请升级套餐，或通过下方充值去支付') }}</span>
          </template>
        </a-alert>
      </div>
    </div>

    <div class="table-container">
      <a-table
        :columns="columns"
        :data-source="dataSource"
        :pagination="pagination"
        size="middle"
        :row-key="record => record.id"
      >
        <template #bodyCell="{ column, record }">
          <template v-if="column.key === 'status'">
            <a-switch v-model:checked="record.status" size="small" />
          </template>
          <template v-if="column.key === 'action'">
            <a-space>
              <a-button type="link" size="small">{{ t('编辑') }}</a-button>
              <a-button type="link" size="small" danger>{{ t('删除') }}</a-button>
            </a-space>
          </template>
        </template>
      </a-table>
    </div>

    <!-- 新建广告弹窗 -->
    <a-modal
      v-model:open="modalVisible"
      :title="null"
      :footer="null"
      :width="800"
      @cancel="handleModalCancel"
      class="create-ad-modal"
    >
      <div class="modal-content">
        <div class="modal-tabs">
          <div
            class="tab-item"
            :class="{ active: activeTab === 'new' }"
            @click="activeTab = 'new'"
          >
            {{ t('新建广告') }}
          </div>
          <div
            class="tab-item"
            :class="{ active: activeTab === 'existing' }"
            @click="activeTab = 'existing'"
          >
            {{ t('选择已有') }}
          </div>
        </div>

        <div v-if="activeTab === 'new'" class="tab-content">
          <a-form :model="createForm" layout="horizontal" :label-col="{ span: 4 }">
            <a-form-item :label="t('广告目标')">
              <div class="option-group">
                <div
                  v-for="goal in adGoals"
                  :key="goal.value"
                  class="option-item"
                  :class="{ active: createForm.goal === goal.value }"
                  @click="createForm.goal = goal.value"
                >
                  {{ goal.label }}
                </div>
              </div>
            </a-form-item>

            <div v-if="createForm.goal === 'sales'" class="info-notice">
              <info-circle-filled class="info-icon" />
              <span>{{ t('在创建页面打开赋能型广告系列预算优化、进阶赋能型版位、进阶赋能型受众，即可创建进阶赋能型广告') }}</span>
            </div>

            <a-form-item v-if="createForm.goal === 'sales'" :label="t('进阶赋能型目录广告')" class="advanced-catalog-item">
              <a-switch v-model:checked="createForm.advancedCatalog" />
            </a-form-item>

            <a-form-item :label="t('转化发生位置')" v-if="!(createForm.goal === 'sales' && createForm.advancedCatalog)">
              <div class="option-group">
                <div
                  v-for="pos in currentPositions"
                  :key="pos.value"
                  class="option-item"
                  :class="{ active: createForm.position === pos.value }"
                  @click="createForm.position = pos.value"
                >
                  {{ pos.label }}
                </div>
              </div>
            </a-form-item>
          </a-form>
        </div>

        <div v-else class="tab-content">
          <a-form :model="createForm" layout="horizontal" :label-col="{ span: 4 }">
            <a-form-item :label="t('广告账户')">
              <a-select v-model:value="createForm.account" :placeholder="t('请选择广告账户')" style="width: 100%">
                <a-select-option value="acc1">Account 1</a-select-option>
              </a-select>
            </a-form-item>
          </a-form>
        </div>

        <div class="modal-footer">
          <div class="footer-info">
            {{ t('本月已创建广告0个，上限100000个。如需提升上限，请升级套餐，或通过右下角“联系我们”') }}
          </div>
          <a-button type="primary" :disabled="activeTab === 'existing' && !createForm.account" @click="handleContinue">
            {{ t('继续') }}
          </a-button>
        </div>
      </div>
    </a-modal>
  </div>
</template>

<script lang="ts" setup>
import { reactive, ref, computed } from 'vue';
import { useI18n } from 'vue-i18n';
import { PlusOutlined, ReloadOutlined, InfoCircleFilled } from '@ant-design/icons-vue';

const { t } = useI18n();

const modalVisible = ref(false);
const activeTab = ref('new');

const adGoals = [
  { label: '销量', value: 'sales' },
  { label: '互动', value: 'engagement' },
  { label: '潜在客户', value: 'leads' },
  { label: '流量', value: 'traffic' },
  { label: '知名度', value: 'awareness' },
];

const positionsMap: Record<string, { label: string; value: string }[]> = {
  sales: [
    { label: '应用', value: 'app' },
    { label: '网站', value: 'website' },
    { label: '消息应用', value: 'messaging' },
  ],
  engagement: [
    { label: '应用', value: 'app' },
    { label: '网站', value: 'website' },
    { label: 'Facebook 公共主页', value: 'fb_page' },
    { label: '消息应用', value: 'messaging' },
  ],
  leads: [
    { label: '即时表单', value: 'instant_forms' },
    { label: '应用', value: 'app' },
    { label: '网站', value: 'website' },
    { label: 'Messenger', value: 'messenger' },
    { label: 'Instagram', value: 'instagram' },
  ],
  traffic: [
    { label: '应用', value: 'app' },
    { label: '网站', value: 'website' },
    { label: '消息应用', value: 'messaging' },
  ],
  awareness: [], // No positions shown for awareness in images
};

const createForm = reactive({
  goal: 'sales',
  advancedCatalog: false,
  position: 'app',
  account: undefined,
});

const currentPositions = computed(() => positionsMap[createForm.goal] || []);

const filterForm = reactive({
  template: '',
  status: undefined,
  language: undefined,
  creator: 'Z',
});

const columns = [
  { title: t('状态'), dataIndex: 'status', key: 'status', width: 80 },
  { title: t('ID'), dataIndex: 'id', key: 'id', width: 80 },
  { title: t('模板名称'), dataIndex: 'name', key: 'name' },
  { title: t('操作'), key: 'action', width: 120 },
  { title: t('通路'), dataIndex: 'channel', key: 'channel' },
  { title: t('账号'), dataIndex: 'account', key: 'account' },
  { title: t('执行次数'), dataIndex: 'execCount', key: 'execCount' },
  { title: t('最后执行时间 (UTC+8)'), dataIndex: 'lastExecTime', key: 'lastExecTime' },
  { title: t('创建人'), dataIndex: 'creator', key: 'creator' },
  { title: t('创建时间 (UTC+8)'), dataIndex: 'createTime', key: 'createTime' },
  { title: t('备注'), dataIndex: 'remark', key: 'remark' },
];

const dataSource = ref([]);

const pagination = reactive({
  total: 0,
  current: 1,
  pageSize: 20,
  showSizeChanger: true,
  showQuickJumper: true,
  showTotal: (total: number) => `共 ${total} 条`,
});

const handleMenuClick = (info: any) => {
  if (info.key === 'meta') {
    modalVisible.value = true;
    activeTab.value = 'new';
  }
};

const handleModalCancel = () => {
  modalVisible.value = false;
};

const handleContinue = () => {
  console.log('Continue with form:', createForm);
};

const handleRefresh = () => {
  console.log('Refresh');
};
</script>

<style lang="less" scoped>
.auto-create-ad-container {
  padding: 16px;
  background-color: #f0f2f5;
  min-height: 100vh;

  .filter-bar {
    background: #fff;
    padding: 16px 24px;
    margin-bottom: 16px;
    border-radius: 2px;
  }

  .action-bar {
    display: flex;
    justify-content: space-between;
    align-items: center;
    margin-bottom: 16px;

    .left-actions {
      display: flex;
      align-items: center;
    }

    .ml-8 {
      margin-left: 8px;
    }

    .custom-alert {
      padding: 4px 12px;
      background-color: #e6f7ff;
      border: 1px solid #91d5ff;
      
      :deep(.ant-alert-message) {
        font-size: 12px;
        color: rgba(0, 0, 0, 0.65);
      }
    }
  }

  .table-container {
    background: #fff;
    padding: 0;
    border-radius: 2px;

    :deep(.ant-table-thead > tr > th) {
      background-color: #fafafa;
      font-weight: 500;
    }
  }
}

.create-ad-modal {
  :deep(.ant-modal-content) {
    padding: 0;
    border-radius: 8px;
    overflow: hidden;
  }

  .modal-content {
    .modal-tabs {
      display: flex;
      padding: 20px 24px 0;
      border-bottom: 1px solid #f0f0f0;

      .tab-item {
        padding: 8px 16px;
        cursor: pointer;
        font-size: 16px;
        font-weight: 500;
        color: rgba(0, 0, 0, 0.45);
        position: relative;
        margin-right: 16px;

        &.active {
          color: rgba(0, 0, 0, 0.85);
          &::after {
            content: '';
            position: absolute;
            bottom: 0;
            left: 16px;
            right: 16px;
            height: 3px;
            background: #1890ff;
            border-radius: 2px;
          }
        }
      }
    }

    .tab-content {
      padding: 32px 48px;
      min-height: 300px;

      .option-group {
        display: flex;
        flex-wrap: wrap;
        gap: 0;
        border: 1px solid #d9d9d9;
        border-radius: 4px;
        overflow: hidden;
        width: fit-content;

        .option-item {
          padding: 6px 20px;
          cursor: pointer;
          background: #fff;
          border-right: 1px solid #d9d9d9;
          transition: all 0.3s;

          &:last-child {
            border-right: none;
          }

          &.active {
            background: #e6f7ff;
            color: #1890ff;
            border-color: #1890ff;
            position: relative;
            z-index: 1;
            box-shadow: -1px 0 0 0 #1890ff, 0 0 0 1px #1890ff;
          }

          &:hover:not(.active) {
            color: #40a9ff;
          }
        }
      }

      .info-notice {
        margin: 16px 0 24px 16.666%;
        padding: 8px 16px;
        background: #fff;
        border: 1px solid #1890ff;
        border-radius: 4px;
        display: flex;
        align-items: center;
        gap: 8px;
        font-size: 13px;
        color: #1890ff;

        .info-icon {
          font-size: 16px;
        }
      }

      .advanced-catalog-item {
        :deep(.ant-form-item-label) {
          text-align: left;
          padding-right: 12px;
          flex: 0 0 auto;
          width: auto;
          min-width: 150px;
        }
        :deep(.ant-form-item-control) {
          flex: 1;
        }
      }
    }

    .modal-footer {
      padding: 16px 24px;
      border-top: 1px solid #f0f0f0;
      display: flex;
      justify-content: space-between;
      align-items: center;

      .footer-info {
        color: rgba(0, 0, 0, 0.45);
        font-size: 12px;
      }
    }
  }
}
</style>

