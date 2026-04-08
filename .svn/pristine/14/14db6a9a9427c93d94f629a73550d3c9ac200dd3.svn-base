<template>
  <a-modal
    v-model:open="visible"
    :title="t('pages.adAccount.modifyFbAccount.title')"
    :confirm-loading="loading"
    :width="1200"
    @ok="handleOk"
    @cancel="handleCancel"  
  >
    <div class="modify-fb-account-modal">
      <!-- 提示信息 -->
      <div class="tip-section">
        <a-alert
          :message="t('pages.adAccount.modifyFbAccount.tip')"
          type="info"
          show-icon
        />
      </div>

      <!-- FB个人号列表 -->
      <div class="accounts-table">
        <a-table
          :columns="columns"
          :data-source="fbAccounts"
          :pagination="false"
          :scroll="{ y: 300 }"
          row-key="id"
        >
          <template #bodyCell="{ column, record }">
            <template v-if="column.key === 'fbAccount'">
              <span>{{ record.fbAccount }}</span>
            </template>
            <template v-else-if="column.key === 'fbAccountId'">
              <span class="account-id">{{ record.fbAccountId }}</span>
            </template>
            <template v-else-if="column.key === 'authStatus'">
              <a-tag :color="getStatusColor(record.authStatus)">
                {{ getStatusText(record.authStatus) }}
              </a-tag>
            </template>
            <template v-else-if="column.key === 'adspolarUsername'">
              <span>{{ record.adspolarUsername }}</span>
            </template>
            <template v-else-if="column.key === 'authTime'">
              <span>{{ record.authTime }}</span>
            </template>
            <template v-else-if="column.key === 'actions'">
              <a-button
                type="link"
                danger
                si  e="small"
                @click="handleDelete(record)"
              >
                {{ t('pages.adAccount.modifyFbAccount.remove') }}
              </a-button>
            </template>
          </template>
        </a-table>
      </div>
    </div>
  </a-modal>
</template>

<script setup lang="ts">
import { ref, computed, watch } from 'vue';
import { useI18n } from 'vue-i18n';
import { message } from 'ant-design-vue';

type AuthStatus = 'authorized' | 'expired' | 'pending';

interface FbAccount {
  id: string;
  fbAccount: string;
  fbAccountId: string;
  authStatus: AuthStatus;
  adspolarUsername: string;
  authTime: string;
}

interface Props {
  open: boolean;
  fbAccounts?: FbAccount[];
}

interface Emits {
  (e: 'update:open', value: boolean): void;
  (e: 'confirm', value: FbAccount[]): void;
}

const props = withDefaults(defineProps<Props>(), {
  open: false,
  fbAccounts: () => [],
});

const emit = defineEmits<Emits>();

const { t } = useI18n();

const loading = ref(false);
const accounts = ref<FbAccount[]>([...props.fbAccounts]);

const visible = computed({
  get: () => props.open,
  set: (val) => emit('update:open', val),
});

// 表格列配置
const columns = computed(() => [
  {
    title: t('pages.adAccount.modifyFbAccount.table.fbAccount'),
    dataIndex: 'fbAccount',
    key: 'fbAccount',
    width: 140,
  },
  {
    title: t('pages.adAccount.modifyFbAccount.table.fbAccountId'),
    dataIndex: 'fbAccountId',
    key: 'fbAccountId',
    width: 160,
  },
  {
    title: t('pages.adAccount.modifyFbAccount.table.authStatus'),
    dataIndex: 'authStatus',
    key: 'authStatus',
    width: 100,
  },
  {
    title: t('pages.adAccount.modifyFbAccount.table.adspolarUsername'),
    dataIndex: 'adspolarUsername',
    key: 'adspolarUsername',
    width: 120,
  },
  {
    title: t('pages.adAccount.modifyFbAccount.table.authTime'),
    dataIndex: 'authTime',
    key: 'authTime',
    width: 140,
  },
  {
    title: t('pages.adAccount.modifyFbAccount.table.actions'),
    key: 'actions',
    width: 80,
    align: 'center' as const,
  },
]);

const fbAccounts = computed(() => accounts.value);

// 获取状态颜色
const getStatusColor = (status: AuthStatus): string => {
  const colorMap: Record<AuthStatus, string> = {
    authorized: 'success',
    expired: 'error',
    pending: 'warning',
  };
  return colorMap[status] || 'default';
};

// 获取状态文本
const getStatusText = (status: AuthStatus): string => {
  const textMap: Record<AuthStatus, string> = {
    authorized: t('pages.adAccount.modifyFbAccount.status.authorized'),
    expired: t('pages.adAccount.modifyFbAccount.status.expired'),
    pending: t('pages.adAccount.modifyFbAccount.status.pending'),
  };
  return textMap[status] || status;
};

// 删除FB个人号
const handleDelete = (record: FbAccount) => {
  accounts.value = accounts.value.filter((acc) => acc.id !== record.id);
  message.success(t('pages.adAccount.modifyFbAccount.removeSuccess'));
};

// 确定按钮
const handleOk = () => {
  emit('confirm', accounts.value);
  visible.value = false;
};

// 取消按钮
const handleCancel = () => {
  visible.value = false;
};

// 监听弹窗打开，重置数据
watch(() => props.open, (val) => {
  if (val) {
    accounts.value = [...props.fbAccounts];
  }
});
</script>

<style scoped lang="less">
.modify-fb-account-modal {
  .tip-section {
    margin-bottom: 16px;

    :deep(.ant-alert) {
      .ant-alert-message {
        font-size: 13px;
      }
    }
  }

  .accounts-table {
    :deep(.ant-table) {
      .ant-table-tbody {
        .ant-table-cell {
          padding: 8px 12px;
        }
      }
    }

    .account-id {
      font-family: 'Courier New', monospace;
      font-size: 12px;
      color: rgba(0, 0, 0, 0.65);
    }
  }
}
</style>
