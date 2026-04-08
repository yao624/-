<template>
  <div class="pick-accounts-section">
    <a-form layout="vertical">
      <!-- Template Selection -->
      <a-form-item name="template" :label="t('Select Template')" required class="custom-form-item">
        <a-row justify="space-between" align="middle">
          <a-col>
            <a-tag v-if="formData.template.length > 0" color="blue" class="template-tag">
              {{ formData.template[0]?.name }}
            </a-tag>
            <span v-else class="placeholder-text">
              {{ t('Please select a template') }}
            </span>
          </a-col>
          <a-col>
            <pick-objects
              :multiple="false"
              :api="getFbAdTemplates"
              :allow-empty="false"
              :columns="templateColumns"
              :button-text="t('Select Template')"
              :title="t('Select Template')"
              @confirm:items-selected="handleTemplateSelection"
              class="template-picker"
            ></pick-objects>
          </a-col>
        </a-row>
      </a-form-item>

      <!-- Ad Accounts Display -->
      <a-form-item :label="t('Ad Accounts')" required class="custom-form-item">
        <div class="accounts-table-wrapper">
          <template v-if="loading">
            <a-table
              :loading="true"
              :columns="[...accountColumns, actionColumn]"
              :data-source="[]"
              :row-key="record => record.id"
              :pagination="false"
              class="custom-table"
            />
          </template>
          <template v-else>
            <a-table
              v-if="selectedAccounts.length > 0"
              :columns="[...accountColumns, actionColumn]"
              :data-source="selectedAccounts"
              :row-key="record => record.id"
              :pagination="false"
              class="custom-table"
            >
              <template #bodyCell="{ column, record }">
                <template v-if="column.key === 'action'">
                  <a @click="onRemove(record)" class="remove-link">{{ t('Remove') }}</a>
                </template>
                <template v-if="column.key === 'account_status'">
                  <a-tag :color="getStatusColor(record.account_status)" class="status-tag">
                    {{ record.account_status }}
                  </a-tag>
                </template>
              </template>
            </a-table>
            <div v-else class="empty-accounts">
              <a-empty :description="t('No ad accounts selected')" />
            </div>
          </template>
        </div>
      </a-form-item>

      <!-- Action Buttons -->
      <a-row :gutter="[12, 0]">
        <a-col>
          <a-button type="primary" @click="openSelectAccountsModal" class="action-btn primary-btn">
            {{ selectedAccounts.length > 0 ? t('Change Ad Accounts') : t('Select Ad Accounts') }}
          </a-button>
        </a-col>
      </a-row>
    </a-form>

    <!-- Add/Select Accounts Modal -->
    <a-modal
      v-model:visible="selectAccountsModalVisible"
      :title="t('Select Ad Accounts')"
      width="900px"
      @ok="handleSelectAccounts"
      :confirm-loading="accountsLoading"
      class="custom-modal"
    >
      <!-- Add search field for accounts here -->
      <div class="search-container">
        <div class="custom-search-wrapper">
          <input
            v-model="searchText"
            class="custom-search-input"
            :placeholder="t('Search by name or ID...')"
            @keyup.enter="handleSearch"
          />
          <button class="custom-search-button" @click="handleSearch">
            <search-outlined />
          </button>
          <span v-if="searchText" class="clear-icon" @click="clearSearch">
            <close-circle-filled />
          </span>
        </div>
      </div>

      <a-table
        :loading="accountsLoading"
        :columns="accountColumns"
        :data-source="availableAccounts"
        :row-key="record => record.id"
        :pagination="accountsPagination"
        :row-selection="{
          selectedRowKeys: modalSelectedRowKeys,
          onChange: onModalSelectChange,
        }"
        :custom-row="customRowEvents"
        @change="handleModalTableChange"
        class="custom-table"
      >
        <template #bodyCell="{ column, record }">
          <template v-if="column.key === 'account_status'">
            <a-tag :color="getStatusColor(record.account_status)" class="status-tag">
              {{ record.account_status }}
            </a-tag>
          </template>
        </template>
      </a-table>
    </a-modal>
  </div>
</template>

<script lang="ts">
import type { PropType } from 'vue';
import { defineComponent, reactive, ref, watch } from 'vue';
import { useI18n } from 'vue-i18n';
import { useRouter, useRoute } from 'vue-router';
import PickObjects from './pick-objects.vue';
import { getFbAdTemplates } from '@/api/fb_ad_template';
import { queryFB_AD_AccountsApi } from '@/api/fb_ad_accounts';
import type { FbAdAccount } from '@/utils/fb-interfaces'; // Assuming this interface exists
import { message, Empty } from 'ant-design-vue';
import { SearchOutlined, CloseCircleFilled } from '@ant-design/icons-vue';

export default defineComponent({
  name: 'PickAccountsV2',
  components: {
    PickObjects,
    'a-empty': Empty,
    SearchOutlined,
    CloseCircleFilled,
  },
  props: {
    adAccounts: {
      type: Array as PropType<FbAdAccount[]>,
      required: true,
    },
    loading: {
      type: Boolean,
      default: false,
    },
  },
  setup(props, { expose }) {
    const { t } = useI18n();
    const router = useRouter();
    const route = useRoute();

    // --- State ---
    const formData = reactive<{ template: any[] }>({
      template: [],
    });

    const selectedAccounts = ref<FbAdAccount[]>(props.adAccounts);

    // Accounts selection modal state
    const selectAccountsModalVisible = ref(false);
    const availableAccounts = ref<FbAdAccount[]>([]);
    const modalSelectedRowKeys = ref<(string | number)[]>([]);
    const accountsLoading = ref(false);
    const searchText = ref('');
    const accountsPagination = ref({
      current: 1,
      pageSize: 10,
      total: 0,
      showSizeChanger: true,
      pageSizeOptions: ['10', '20', '50', '100'],
    });

    // --- Columns Definitions ---
    const templateColumns = [
      { title: 'Name', dataIndex: 'name', key: 'name' },
      { title: 'Ad name', dataIndex: 'ad_name', key: 'ad_name' },
      { title: 'Ad set name', dataIndex: 'adset_name', key: 'adset_name' },
      { title: 'Campaign name', dataIndex: 'campaign_name', key: 'campaign_name' },
    ];

    const accountColumns = [
      { title: 'Name', dataIndex: 'name', key: 'name' },
      { title: 'ID', dataIndex: 'source_id', key: 'source_id' },
      { title: 'Status', dataIndex: 'account_status', key: 'account_status' },
      { title: 'Currency', dataIndex: 'currency', key: 'currency' },
      { title: 'Balance', dataIndex: 'balance', key: 'balance' },
    ];

    const actionColumn = ref({ title: 'Action', dataIndex: 'action', key: 'action' });

    // --- Watchers ---
    watch(
      () => props.adAccounts,
      newVal => {
        selectedAccounts.value = newVal;
        // Update modal selection if modal is open to reflect current selection
        modalSelectedRowKeys.value = newVal.map(acc => acc.id);
      },
      { immediate: true, deep: true },
    );

    // --- Methods ---
    const getData = () => {
      return {
        template: formData.template[0]?.id,
        ad_accounts: selectedAccounts.value.map(({ id }) => id),
      };
    };

    const handleTemplateSelection = (_keys: any[], rows: any[]) => {
      formData.template = rows;
    };

    const onRemove = (rowToRemove: FbAdAccount) => {
      const updatedAccounts = selectedAccounts.value.filter(acc => acc.id !== rowToRemove.id);
      updateRouteWithAccounts(updatedAccounts);
    };

    const updateRouteWithAccounts = (accounts: FbAdAccount[]) => {
      if (!accounts || accounts.length === 0) {
        // If no accounts are left, navigate back or clear query params
        router.push({ path: route.path, query: {} });
        selectedAccounts.value = []; // Ensure local state is also cleared
        return;
      }
      const queryParams = accounts.map(acc => `aid=${acc.source_id}`).join('&');
      router.push(`${route.path}?${queryParams}`);
    };

    const getStatusColor = (status: string) => {
      const statusMap: Record<string, string> = {
        ACTIVE: 'green',
        DISABLED: 'red',
        PENDING_REVIEW: 'orange',
        UNSETTLED: 'gold',
        CLOSED: 'red',
      };
      return statusMap[status] || 'default';
    };

    // --- Account Selection Modal Methods ---
    const openSelectAccountsModal = () => {
      // Reset pagination and load first page
      accountsPagination.value.current = 1;
      // Pre-select keys based on currently selected accounts
      modalSelectedRowKeys.value = selectedAccounts.value.map(acc => acc.id);
      loadAvailableAccounts();
      selectAccountsModalVisible.value = true;
    };

    const loadAvailableAccounts = async () => {
      accountsLoading.value = true;
      try {
        const params: any = {
          pageNo: accountsPagination.value.current,
          pageSize: accountsPagination.value.pageSize,
          is_archived: false,
        };

        // Add search parameter if text is present
        if (searchText.value.trim()) {
          params.keywords = searchText.value.trim();
        }

        const response = await queryFB_AD_AccountsApi(params);
        if (response && response.data) {
          availableAccounts.value = response.data;
          accountsPagination.value.total =
            (response as any).totalCount || response.data.totalCount || 0;
        } else {
          availableAccounts.value = [];
          accountsPagination.value.total = 0;
        }
      } catch (error) {
        console.error('Failed to load accounts:', error);
        message.error(t('Failed to load ad accounts'));
        availableAccounts.value = [];
        accountsPagination.value.total = 0;
      } finally {
        accountsLoading.value = false;
      }
    };

    const handleSearch = () => {
      accountsPagination.value.current = 1;
      loadAvailableAccounts();
    };

    const clearSearch = () => {
      searchText.value = '';
      handleSearch();
    };

    const handleModalTableChange = (pag: { current: number; pageSize: number }) => {
      accountsPagination.value.current = pag.current;
      accountsPagination.value.pageSize = pag.pageSize;
      loadAvailableAccounts();
    };

    const onModalSelectChange = (selectedKeys: (string | number)[]) => {
      modalSelectedRowKeys.value = selectedKeys;
    };

    const customRowEvents = (record: FbAdAccount) => ({
      onClick: () => {
        const recordId = record.id;
        if (!recordId) return;

        // Check if row is already selected
        const index = modalSelectedRowKeys.value.findIndex(key => key === recordId);

        if (index >= 0) {
          // Remove from selection if already selected
          modalSelectedRowKeys.value = modalSelectedRowKeys.value.filter(key => key !== recordId);
        } else {
          // Add to selection if not selected
          modalSelectedRowKeys.value = [...modalSelectedRowKeys.value, recordId];
        }
      },
      style: { cursor: 'pointer' },
    });

    const handleSelectAccounts = () => {
      const newlySelectedAccounts = availableAccounts.value.filter(account =>
        modalSelectedRowKeys.value.includes(account.id),
      );

      if (newlySelectedAccounts.length === 0) {
        message.warning(t('Please select at least one ad account.'));
        // Optionally keep the modal open
        // return;
      }

      // Identify existing accounts that are not in the current available accounts list
      // to preserve them when updating (they may be from a different page)
      const existingAccountIds = new Set(availableAccounts.value.map(acc => acc.id));
      const accountsToPreserve = selectedAccounts.value.filter(
        acc => !existingAccountIds.has(acc.id),
      );

      // Merge existing accounts with newly selected ones
      const mergedAccounts = [...accountsToPreserve, ...newlySelectedAccounts];

      // Update with combined accounts
      updateRouteWithAccounts(mergedAccounts);
      selectAccountsModalVisible.value = false;
    };

    // Expose method for parent component
    expose({ getData });

    return {
      t,
      formData,
      selectedAccounts,
      accountColumns,
      actionColumn,
      getData,
      onRemove,
      getFbAdTemplates,
      getStatusColor,
      handleTemplateSelection,

      // Account selection modal
      selectAccountsModalVisible,
      availableAccounts,
      modalSelectedRowKeys,
      accountsLoading,
      accountsPagination,
      openSelectAccountsModal,
      handleModalTableChange,
      onModalSelectChange,
      handleSelectAccounts,
      templateColumns,
      searchText,
      handleSearch,
      clearSearch,
      customRowEvents,
    };
  },
});
</script>

<style lang="less" scoped>
.pick-accounts-section {
  margin-bottom: 24px;
  padding: 18px 20px;
  border-radius: 10px;
  box-shadow: 0 3px 15px rgba(0, 0, 0, 0.06);
  background: linear-gradient(to right, #fcfcfc, #ffffff);
  position: relative;
  border: 1px solid rgba(240, 240, 240, 0.8);
  transition: all 0.3s cubic-bezier(0.25, 0.8, 0.25, 1);

  &:hover {
    box-shadow: 0 6px 20px rgba(24, 144, 255, 0.1);
    transform: translateY(-2px);
  }

  &::before {
    content: '';
    position: absolute;
    left: 0;
    top: 10px;
    height: calc(100% - 20px);
    width: 4px;
    background: linear-gradient(to bottom, #1890ff, #36cfc9);
    border-radius: 4px 0 0 4px;
    transition: all 0.3s ease;
  }

  &:hover::before {
    top: 5px;
    height: calc(100% - 10px);
  }
}

.custom-form-item {
  margin-bottom: 18px;
  position: relative;

  &:hover {
    :deep(.ant-form-item-label > label) {
      color: #1890ff;
    }
  }
}

:deep(.ant-form-item-label) {
  padding-bottom: 5px;
  transition: all 0.3s ease;

  label {
    color: #555;
    font-weight: 500;
    font-size: 14px;
    transition: color 0.3s ease;
  }
}

.template-tag {
  border-radius: 4px;
  padding: 4px 10px;
  font-weight: 500;
  box-shadow: 0 2px 4px rgba(0, 0, 0, 0.1);
  border: none;
}

.placeholder-text {
  color: #999;
  font-style: italic;
}

.accounts-table-wrapper {
  border-radius: 8px;
  overflow: hidden;
  box-shadow: 0 2px 8px rgba(0, 0, 0, 0.05);
  transition: all 0.3s ease;
  margin-bottom: 16px;

  &:hover {
    box-shadow: 0 4px 12px rgba(0, 0, 0, 0.08);
  }
}

.empty-accounts {
  padding: 40px 0;
  text-align: center;
}

.action-btn {
  border-radius: 6px;
  height: 36px;
  font-weight: 500;
  transition: all 0.3s ease;
  display: flex;
  align-items: center;
  justify-content: center;
}

.primary-btn {
  background: linear-gradient(to bottom right, #1890ff, #36cfc9);
  border: none;
  box-shadow: 0 2px 4px rgba(24, 144, 255, 0.1);

  &:hover {
    transform: translateY(-1px);
    box-shadow: 0 4px 8px rgba(24, 144, 255, 0.15);
    background: linear-gradient(to bottom right, #40a9ff, #40d3cc);
  }

  &:active {
    transform: translateY(0);
  }
}

.remove-link {
  color: #ff4d4f;
  transition: all 0.2s ease;

  &:hover {
    color: #ff7875;
    text-decoration: underline;
  }
}

.status-tag {
  border-radius: 4px;
  font-weight: 500;
  text-transform: capitalize;
  box-shadow: 0 2px 4px rgba(0, 0, 0, 0.05);
  border: none;
}

:deep(.custom-modal) {
  .ant-modal-content {
    border-radius: 12px;
    overflow: hidden;
    box-shadow: 0 4px 20px rgba(0, 0, 0, 0.08);
  }

  .ant-modal-header {
    background: linear-gradient(to right, #f5f8ff, #f0f7ff);
    border-bottom: 1px solid #eef2ff;
    padding: 16px 24px;
  }

  .ant-modal-title {
    font-weight: 500;
    color: #333;
    font-size: 16px;
    letter-spacing: 0.5px;
  }

  .ant-modal-body {
    padding: 24px;
  }

  .ant-modal-footer {
    border-top: 1px solid #eef2ff;
    padding: 16px 24px;
    background: #fafbff;

    .ant-btn {
      border-radius: 6px;
      transition: all 0.3s ease;
      height: 36px;
      font-weight: 500;
      min-width: 90px;
    }

    .ant-btn-primary {
      background: linear-gradient(to bottom right, #1890ff, #36cfc9);
      border: none;
      box-shadow: 0 2px 4px rgba(24, 144, 255, 0.1);

      &:hover {
        background: linear-gradient(to bottom right, #40a9ff, #40d3cc);
        transform: translateY(-1px);
        box-shadow: 0 4px 8px rgba(24, 144, 255, 0.15);
      }

      &:active {
        transform: translateY(0);
      }
    }

    .ant-btn-default {
      border-color: #e8e8e8;
      color: #555;

      &:hover {
        color: #1890ff;
        border-color: #1890ff;
        transform: translateY(-1px);
      }

      &:active {
        transform: translateY(0);
      }
    }
  }
}

// Search styles
.search-container {
  margin-bottom: 18px;
  width: 100%;
}

.custom-search-wrapper {
  display: flex;
  align-items: center;
  position: relative;
  width: 100%;
  height: 44px;
  border-radius: 8px;
  overflow: hidden;
  box-shadow: 0 3px 10px rgba(0, 0, 0, 0.05);
  transition: all 0.3s ease;

  &:hover {
    box-shadow: 0 3px 15px rgba(0, 0, 0, 0.08);
  }

  &:focus-within {
    box-shadow: 0 3px 15px rgba(24, 144, 255, 0.1);
  }
}

.custom-search-input {
  flex: 1;
  height: 100%;
  border: 1px solid #e8e8e8;
  border-right: none;
  background-color: #fafbff;
  padding: 0 16px;
  font-size: 14px;
  outline: none;
  transition: all 0.3s ease;
  border-radius: 8px 0 0 8px;

  &:hover,
  &:focus {
    background-color: #fff;
    border-color: #40a9ff;
  }

  &::placeholder {
    color: #bbb;
  }
}

.custom-search-button {
  width: 48px;
  height: 100%;
  border: none;
  background: linear-gradient(to bottom right, #1890ff, #36cfc9);
  color: white;
  font-size: 18px;
  display: flex;
  align-items: center;
  justify-content: center;
  cursor: pointer;
  transition: all 0.3s ease;

  &:hover {
    background: linear-gradient(to bottom right, #40a9ff, #40d3cc);
  }

  &:active {
    transform: scale(0.98);
  }
}

.clear-icon {
  position: absolute;
  right: 56px;
  top: 50%;
  transform: translateY(-50%);
  color: #bbb;
  cursor: pointer;
  font-size: 14px;
  padding: 4px;
  transition: all 0.2s ease;

  &:hover {
    color: #999;
  }
}

.custom-table {
  :deep(.ant-table) {
    border-radius: 10px;
    overflow: hidden;
  }

  :deep(.ant-table-thead > tr > th) {
    background: linear-gradient(to right, #f5f8ff 30%, #f0f7ff);
    font-weight: 500;
    color: #333;
    padding: 14px 16px;
    border-bottom: 1px solid #e6f0ff;
    transition: background 0.3s ease;
    font-size: 13px;

    &:hover {
      background: #e6f0ff;
    }
  }

  :deep(.ant-table-tbody > tr) {
    transition: all 0.2s ease;

    > td {
      padding: 12px 16px;
      border-bottom: 1px solid #f0f0f0;
      font-size: 13px;
    }

    &:hover > td {
      background-color: #f7faff;
    }

    &.ant-table-row-selected > td {
      background-color: #e6f7ff;
    }

    &:last-child > td {
      border-bottom: none;
    }
  }

  :deep(.ant-pagination) {
    margin: 16px;

    .ant-pagination-item,
    .ant-pagination-prev .ant-pagination-item-link,
    .ant-pagination-next .ant-pagination-item-link {
      border-radius: 6px;
      transition: all 0.3s ease;
      border: 1px solid #e8e8e8;

      &:hover {
        border-color: #1890ff;
        color: #1890ff;
      }
    }

    .ant-pagination-item-active {
      border-color: #1890ff;
      background: #e6f7ff;

      a {
        color: #1890ff;
      }
    }
  }
}
</style>
