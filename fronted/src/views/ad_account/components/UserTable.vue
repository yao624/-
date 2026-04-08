<template>
  <div class="user-table">
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
        <template v-if="column.key === 'platform'">
          <a-tag :color="getPlatformColor(record.platform)">
            {{ getPlatformLabel(record.platform) }}
          </a-tag>
        </template>
        <template v-else-if="column.key === 'autoBind'">
          <a-switch
            :checked="record.autoBind"
            :loading="record.switching"
            @change="(checked: boolean) => handleAutoBindChange(record, checked)"
          />
        </template>
        <template v-else-if="column.key === 'boundCount'">
          <span>{{ record.boundCount }}</span>
        </template>
        <template v-else-if="column.key === 'authStatus'">
          <a-tag :color="getAuthStatusColor(record.authStatus)">
            {{ getAuthStatusLabel(record.authStatus) }}
          </a-tag>
        </template>
        <template v-else-if="column.key === 'authFailReason'">
          <a-tooltip v-if="record.authFailReason" :title="record.authFailReason">
            <span class="fail-reason">{{ record.authFailReason }}</span>
          </a-tooltip>
          <span v-else>-</span>
        </template>
        <template v-else-if="column.key === 'actions'">
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
        </template>
      </template>
    </a-table>
  </div>
</template>

<script setup lang="ts">
import { computed, h } from 'vue';
import { useI18n } from 'vue-i18n';
import { QuestionCircleOutlined } from '@ant-design/icons-vue';
import { Tooltip } from 'ant-design-vue';
import type { TableProps } from 'ant-design-vue';
import type { PlatformType, UserAccount } from '../types';

const props = defineProps<{
  dataSource: UserAccount[];
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
}>();

const emit = defineEmits<{
  (e: 'update:selectedRowKeys', keys: string[]): void;
  (e: 'autoBindChange', record: UserAccount, checked: boolean): void;
  (e: 'delete', record: UserAccount): void;
  (e: 'pageChange', page: number, pageSize: number): void;
}>();

// selectedRowKeys is optional for row selection feature

const { t, locale } = useI18n();

const isZh = computed(() => locale.value.startsWith('zh'));

const platformConfig: Record<PlatformType, { name: string; nameEn: string; color: string }> = {
  meta: { name: 'Facebook', nameEn: 'Facebook', color: '#1877F2' },
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

const getAuthStatusLabel = (status: string) => {
  return t(`pages.adAccount.authStatus.${status}`);
};

const getAuthStatusColor = (status: string): string => {
  const colorMap: Record<string, string> = {
    authorized: 'success',
    unauthorized: 'default',
    expired: 'warning',
    failed: 'error',
  };
  return colorMap[status] || 'default';
};

const personalAccountLabel = computed(() => {
  const platform = props.platform || 'meta';
  if (platform === 'meta') {
    return isZh.value ? 'FB个人号' : 'FB Account';
  } else if (platform === 'google') {
    return isZh.value ? 'Google账号' : 'Google Account';
  } else if (platform === 'tiktok') {
    return isZh.value ? 'TikTok账号' : 'TikTok Account';
  }
  return isZh.value ? '个人号' : 'Account';
});

const columns = computed(() => [
  {
    title: () => h('span', [
      t('pages.adAccount.table.autoBind'),
      h(Tooltip, { title: t('pages.adAccount.autoBind.description') }, () =>
        h(QuestionCircleOutlined, { class: 'header-icon' })
      ),
    ]),
    dataIndex: 'autoBind',
    key: 'autoBind',
    width: 120,
    align: 'center' as const,
  },
  {
    title: personalAccountLabel.value,
    dataIndex: 'personalAccount',
    key: 'personalAccount',
    width: 180,
    ellipsis: true,
  },
  {
    title: t('pages.adAccount.table.boundCount'),
    dataIndex: 'boundCount',
    key: 'boundCount',
    width: 100,
    align: 'center' as const,
  },
  {
    title: t('pages.adAccount.table.authorizer'),
    dataIndex: 'authorizer',
    key: 'authorizer',
    width: 120,
  },
  {
    title: t('pages.adAccount.table.lastAuthTime'),
    dataIndex: 'lastAuthTime',
    key: 'lastAuthTime',
    width: 170,
  },
  {
    title: t('pages.adAccount.table.authStatus'),
    dataIndex: 'authStatus',
    key: 'authStatus',
    width: 100,
    align: 'center' as const,
  },
  {
    title: t('pages.adAccount.table.authFailReason'),
    dataIndex: 'authFailReason',
    key: 'authFailReason',
    width: 200,
    ellipsis: true,
  },
  {
    title: t('pages.adAccount.table.actions'),
    key: 'actions',
    width: 100,
    fixed: 'right' as const,
  },
]);

const rowSelection = computed(() => {
  // 如果没有传入 selectedRowKeys，则不显示行选择
  if (!props.selectedRowKeys) {
    return undefined;
  }
  return {
    selectedRowKeys: props.selectedRowKeys,
    onChange: (selectedRowKeys: string[]) => {
      emit('update:selectedRowKeys', selectedRowKeys);
    },
  };
});

const handleAutoBindChange = (record: UserAccount, checked: boolean) => {
  emit('autoBindChange', record, checked);
};

const handleDelete = (record: UserAccount) => {
  emit('delete', record);
};

const handleTableChange: TableProps['onChange'] = (pagination) => {
  emit('pageChange', pagination.current, pagination.pageSize);
};
</script>

<style scoped lang="less">
.user-table {
  :deep(.ant-table-wrapper) {
    .ant-tag {
      margin: 0;
    }

    .fail-reason {
      color: #ff4d4f;
    }

    .header-icon {
      margin-left: 4px;
      color: #999;
      font-size: 14px;
      cursor: help;

      &:hover {
        color: #1890ff;
      }
    }
  }
}
</style>
