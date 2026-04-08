<template>
  <div class="account-table">
    <a-table
      :columns="columns"
      :data-source="dataSource"
      :loading="loading"
      :pagination="pagination"
      :row-selection="rowSelection"
      :scroll="{ x: 1800 }"
      row-key="id"
      @change="handleTableChange"
    >
      <template #bodyCell="{ column, record }">
        <template v-if="column.key === 'authorizationStatus'">
          <div class="status-cell">
            <a-tag :color="getStatusColor(record.authorizationStatus)">
              {{ t(`pages.adAccount.status.${record.authorizationStatus}`) }}
            </a-tag>
            <a-tooltip v-if="record.authorizationStatus === 'expired'" :title="t('pages.adAccount.status.tokenExpiredTip')">
              <QuestionCircleOutlined class="status-help-icon" />
            </a-tooltip>
          </div>
        </template>
        <template v-else-if="column.key === 'accountStatus'">
          <a-tag :color="getAccountStatusColor(record.accountStatus)">
            {{ t(`pages.adAccount.status.${record.accountStatus}`) }}
          </a-tag>
        </template>
        <template v-else-if="column.key === 'personalAccounts'">
          <a-tooltip v-if="record.personalAccounts && record.personalAccounts.length > 0" :title="record.personalAccounts.join(', ')">
            <span>{{ record.personalAccounts.slice(0, 2).join(', ') }}</span>
            <span v-if="record.personalAccounts.length > 2">
              ... (+{{ record.personalAccounts.length - 2 }})
            </span>
          </a-tooltip>
          <span v-else>-</span>
        </template>
        <template v-else-if="column.key === 'balance'">
          <span>{{ record.balance || '-' }}</span>
        </template>
        <template v-else-if="column.key === 'entity'">
          <span>{{ record.entity || '-' }}</span>
        </template>
        <template v-else-if="column.key === 'accountNote'">
          <span>{{ record.accountNote || '-' }}</span>
        </template>
        <template v-else-if="column.key === 'owner'">
          <span>{{ record.owner?.name || '-' }}</span>
        </template>
        <template v-else-if="column.key === 'assistants'">
          <span v-if="!record.assistants || record.assistants.length === 0">-</span>
          <a-tooltip v-else :title="record.assistants.map((a: any) => a.name).join(', ')">
            <span>{{ record.assistants.slice(0, 2).map((a: any) => a.name).join(', ') }}</span>
            <span v-if="record.assistants.length > 2">
              ... (+{{ record.assistants.length - 2 }})
            </span>
          </a-tooltip>
        </template>
        <template v-else-if="column.key === 'actions'">
          <a-space>
            <a-button type="link" size="small" @click="handleEdit(record)">
              {{ t('pages.common.edit') }}
            </a-button>
            <a-popconfirm
              :title="t('pages.adAccount.messages.deleteConfirm')"
              :ok-text="t('pages.confirm')"
              :cancel-text="t('pages.cancel')"
              @confirm="handleDelete(record)"
            >
              <a-button type="link" size="small" danger>
                {{ t('pages.common.delete') }}
              </a-button>
            </a-popconfirm>
          </a-space>
        </template>
      </template>
    </a-table>
  </div>
</template>

<script setup lang="ts">
import { computed } from 'vue';
import { useI18n } from 'vue-i18n';
import { QuestionCircleOutlined } from '@ant-design/icons-vue';
import type { TableProps } from 'ant-design-vue';
import type { AdAccount, PlatformType } from '../types';

const props = defineProps<{
  dataSource: AdAccount[];
  loading: boolean;
  pagination: {
    current: number;
    pageSize: number;
    total: number;
    showSizeChanger: boolean;
    showQuickJumper: boolean;
    showTotal: (total: number) => string;
  };
  selectedRowKeys?: string[];
  platform?: PlatformType;
  selectedCustomColumns?: string[];
}>();

const emit = defineEmits<{
  (e: 'update:selectedRowKeys', keys: string[]): void;
  (e: 'edit', record: AdAccount): void;
  (e: 'delete', record: AdAccount): void;
  (e: 'pageChange', page: number, pageSize: number): void;
}>();

const { t, locale } = useI18n();

const isZh = computed(() => locale.value.startsWith('zh'));

const platformConfig: Record<PlatformType, { name: string; nameEn: string; color: string }> = {
  meta: { name: 'FB', nameEn: 'Facebook', color: '#1877F2' },
  google: { name: 'Google', nameEn: 'Google', color: '#4285F4' },
  tiktok: { name: 'TikTok', nameEn: 'TikTok', color: '#000000' },
};

const getPlatformLabel = (platform?: PlatformType) => {
  if (!platform) return '-';
  const config = platformConfig[platform];
  return isZh.value ? config.name : config.nameEn;
};

const getPlatformColor = (platform?: PlatformType) => {
  if (!platform) return 'default';
  return platformConfig[platform]?.color || 'default';
};

const personalAccountLabel = computed(() => {
  const platform = props.platform || 'meta';
  const config = platformConfig[platform];
  const platformName = isZh.value ? config.name : config.nameEn;
  return isZh.value ? `${platformName}账号` : `${platformName} Account`;
});

const personalAccountCountLabel = computed(() => {
  const platform = props.platform || 'meta';
  const config = platformConfig[platform];
  const platformName = isZh.value ? config.name : config.nameEn;
  return isZh.value ? `${platformName}账号数量` : `${platformName} Account Count`;
});

// BM/MCC列标题
const bmLabel = computed(() => {
  const platform = props.platform || 'meta';
  if (platform === 'google') {
    return 'MCC';
  }
  return isZh.value ? 'BM' : 'BM';
});

const columns = computed(() => {
  const platform = props.platform || 'meta';

  // 所有可用的列定义
  const allColumns: Record<string, any> = {
    accountId: {
      title: t('pages.adAccount.table.accountId'),
      dataIndex: 'id',
      key: 'id',
      width: 150,
      fixed: 'left' as const,
    },
    accountName: {
      title: t('pages.adAccount.table.accountName'),
      dataIndex: 'name',
      key: 'name',
      width: 180,
      ellipsis: true,
    },
    accountNote: {
      title: t('pages.adAccount.table.accountNote'),
      dataIndex: 'accountNote',
      key: 'accountNote',
      width: 150,
      ellipsis: true,
    },
    accountStatus: {
      title: t('pages.adAccount.table.accountStatus'),
      dataIndex: 'accountStatus',
      key: 'accountStatus',
      width: 100,
      align: 'center' as const,
    },
    authStatus: {
      title: t('pages.adAccount.table.authStatus'),
      dataIndex: 'authorizationStatus',
      key: 'authorizationStatus',
      width: 110,
      align: 'center' as const,
    },
    authTime: {
      title: t('pages.adAccount.table.authTime'),
      dataIndex: 'authorizationTime',
      key: 'authorizationTime',
      width: 170,
    },
    bm: {
      title: bmLabel.value,
      dataIndex: 'bm',
      key: 'bm',
      width: 120,
    },
    balance: {
      title: t('pages.adAccount.table.balance'),
      dataIndex: 'balance',
      key: 'balance',
      width: 120,
      align: 'right' as const,
    },
    owner: {
      title: t('pages.adAccount.table.owner'),
      dataIndex: 'owner',
      key: 'owner',
      width: 100,
    },
    assistant: {
      title: t('pages.adAccount.table.assistants'),
      dataIndex: 'assistants',
      key: 'assistants',
      width: 180,
      ellipsis: true,
    },
    personalAccountCount: {
      title: personalAccountCountLabel.value,
      dataIndex: 'personalAccountCount',
      key: 'personalAccountCount',
      width: 130,
      align: 'center' as const,
    },
    personalAccounts: {
      title: personalAccountLabel.value,
      dataIndex: 'personalAccounts',
      key: 'personalAccounts',
      width: 200,
      ellipsis: true,
    },
  };

  // 自定义列key到实际列key的映射
  const keyMap: Record<string, string> = {
    accountName: 'accountName',
    accountId: 'accountId',
    accountNote: 'accountNote',
    accountStatus: 'accountStatus',
    authStatus: 'authStatus',
    authTime: 'authTime',
    bm: 'bm',
    balance: 'balance',
    owner: 'owner',
    assistant: 'assistant',
    personalAccountCount: 'personalAccountCount',
    personalAccounts: 'personalAccounts',
  };

  // 如果没有自定义列配置，使用默认列
  const selectedKeys = props.selectedCustomColumns && props.selectedCustomColumns.length > 0
    ? props.selectedCustomColumns
    : ['accountId', 'accountName', 'bm', 'accountStatus', 'authStatus', 'owner'];

  // 根据选中的key构建列数组
  const resultColumns: any[] = [];

  for (const key of selectedKeys) {
    const columnKey = keyMap[key];
    if (columnKey && allColumns[columnKey]) {
      // BM列只对FB和Google显示
      if (columnKey === 'bm' && platform !== 'meta' && platform !== 'google') {
        continue;
      }
      // 余额列目前只对TikTok显示
      if (columnKey === 'balance' && platform !== 'tiktok') {
        continue;
      }
      resultColumns.push(allColumns[columnKey]);
    }
  }

  // 总是添加操作列
  resultColumns.push({
    title: t('pages.adAccount.table.actions'),
    key: 'actions',
    width: 130,
    fixed: 'right' as const,
  });

  return resultColumns;
});

const rowSelection = computed(() => ({
  selectedRowKeys: props.selectedRowKeys || [],
  onChange: (selectedRowKeys: string[]) => {
    emit('update:selectedRowKeys', selectedRowKeys);
  },
}));

const getStatusColor = (status: string): string => {
  const colorMap: Record<string, string> = {
    authorized: 'green',
    expired: 'orange',
    pending: 'blue',
  };
  return colorMap[status] || 'default';
};

const getAccountStatusColor = (status: string): string => {
  const colorMap: Record<string, string> = {
    active: 'success',
    disabled: 'error',
    pending: 'processing',
  };
  return colorMap[status] || 'default';
};

const handleEdit = (record: AdAccount) => {
  emit('edit', record);
};

const handleDelete = (record: AdAccount) => {
  emit('delete', record);
};

const handleTableChange: TableProps['onChange'] = (pagination) => {
  emit('pageChange', pagination.current, pagination.pageSize);
};
</script>

<style scoped lang="less">
.account-table {
  :deep(.ant-table-wrapper) {
    .ant-tag {
      margin: 0;
    }
  }

  .status-cell {
    display: flex;
    align-items: center;
    justify-content: center;
    gap: 4px;
  }

  .status-help-icon {
    font-size: 14px;
    color: #8c8c8c;
    cursor: help;

    &:hover {
      color: #1890ff;
    }
  }
}
</style>
