<template>
  <page-container :showPageHeader="false">
    <div class="ad-account-page">
      <!-- 左侧平台选择器 -->
      <div class="page-sidebar">
        <platform-sidebar v-model="currentPlatform" />
      </div>

      <!-- 右侧内容区域 -->
      <div class="page-content">
        <!-- 授权账号限制信息 -->
        <div class="auth-limit-banner">
          <span class="limit-item">
            {{ t('pages.adAccount.authLimit.total') }}: <strong>{{ authLimit.total }}</strong>
          </span>
          <span class="limit-item">
            {{ t('pages.adAccount.authLimit.authorized') }}: <strong>{{ authLimit.authorized }}</strong>
          </span>
          <span class="limit-item remaining">
            {{ t('pages.adAccount.authLimit.remaining') }}: <strong>{{ authLimit.remaining }}</strong>
          </span>
        </div>

        <div class="content-card">
          <!-- 筛选区域 -->
          <div class="filter-area">
            <filter-section
              ref="filterSectionRef"
              :platform="currentPlatform"
              :current-tab="currentTab"
              @tab-change="handleTabChange"
              @search="handleSearch"
              @user-filter-change="handleUserFilterChange"
              @ad-account-filter-change="handleAdAccountFilterChange"
            />
          </div>

          <!-- 操作栏 -->
          <div class="action-bar">
            <div class="action-left">
              <a-button v-if="currentTab === 'adAccounts'" type="primary" @click="handleAddAccount">
                {{ t('pages.adAccount.actions.addAccount') }}
              </a-button>
              <!-- 批量操作下拉菜单 - 仅广告账户显示 -->
              <a-dropdown v-if="currentTab === 'adAccounts'" :disabled="!hasSelected">
                <template #overlay>
                  <a-menu @click="handleBatchMenuClick">
                    <a-menu-item key="edit">
                      {{ t('pages.adAccount.batch.edit') }}
                    </a-menu-item>
                    <a-menu-item key="unbind">
                      {{ t('pages.adAccount.batch.unbind') }}
                    </a-menu-item>
                    <a-menu-item key="delete">
                      {{ t('pages.adAccount.batch.delete') }}
                    </a-menu-item>
                    <a-menu-item key="modifyFbAccount">
                      {{ t('pages.adAccount.batch.modifyFbAccount') }}
                    </a-menu-item>
                  </a-menu>
                </template>
                <a-button :disabled="!hasSelected">
                  {{ t('pages.adAccount.actions.batchOperation') }}
                  <DownOutlined />
                </a-button>
              </a-dropdown>
            </div>
            <div class="action-right">
              <a-button v-if="currentTab === 'adAccounts'" @click="handleSyncData">
                {{ t('pages.adAccount.actions.syncData') }}
              </a-button>
              <a-button v-if="currentTab === 'adAccounts'" @click="handleCustomColumns">
                {{ t('pages.adAccount.actions.customColumns') }}
              </a-button>
            </div>
          </div>

          <!-- 表格区域 -->
          <div class="table-area">
            <!-- 用户表格 -->
            <user-table
              v-if="currentTab === 'users'"
              :data-source="userData"
              :loading="userLoading"
              :pagination="pagination"
              :platform="currentPlatform"
              @auto-bind-change="handleAutoBindChange"
              @delete="handleDeleteUser"
              @page-change="handlePageChange"
            />

            <!-- 广告账户表格 -->
            <ad-account-table
              v-else
              :data-source="accountData"
              :loading="accountLoading"
              :pagination="pagination"
              :selected-row-keys="selectedRowKeys"
              :platform="currentPlatform"
              :selected-custom-columns="selectedCustomColumns"
              @update:selected-row-keys="handleSelectionChange"
              @edit="handleEditAccount"
              @delete="handleDeleteAccount"
              @page-change="handlePageChange"
            />
          </div>
        </div>
      </div>
    </div>

    <!-- 编辑广告账户弹窗 -->
    <edit-account-modal
      v-model:open="editModalVisible"
      :platform="currentPlatform"
      :is-batch="isBatchEdit"
      :record="currentEditRecord"
      :owner-options="ownerOptions"
      :assistant-options="assistantOptions"
      :selected-accounts="selectedAccountsForBatch"
      @confirm="handleEditConfirm"
    />

    <!-- 修改FB个人号弹窗 -->
    <modify-fb-account-modal
      v-model:open="modifyFbAccountModalVisible"
      :fb-accounts="fbAccountsList"
      @confirm="handleModifyFbAccountConfirm"
    />

    <!-- 自定义列弹窗 -->
    <custom-column-selector
      v-model:open="customColumnModalVisible"
      :categories="customColumnCategories"
      :default-selected="selectedCustomColumns"
      @confirm="handleCustomColumnConfirm"
      @cancel="customColumnModalVisible = false"
    />

  </page-container>
</template>

<script setup lang="ts">
import { ref, computed, onMounted, reactive, watch } from 'vue';
import { useI18n } from 'vue-i18n';
import { message } from 'ant-design-vue';
import { DownOutlined } from '@ant-design/icons-vue';
import PlatformSidebar from './components/PlatformSidebar.vue';
import FilterSection from './components/FilterSection.vue';
import AccountActions from './components/AccountActions.vue';
import UserTable from './components/UserTable.vue';
import AdAccountTable from './components/AdAccountTable.vue';
import EditAccountModal from './components/EditAccountModal.vue';
import ModifyFbAccountModal from './components/ModifyFbAccountModal.vue';
import CustomColumnSelector from '@/components/custom-column-selector';
import { getAccountsApi, getAdAccountsApi, getUserOptionsApi, editAdAccountApi, batchEditAdAccountsApi, deleteAdAccountApi, batchDeleteAdAccountsApi, deleteAccountApi, updateAutoBindApi, type UserOption } from '@/api/account-manage';
import type { AdAccount, FilterTab, PlatformType, UserAccount } from './types';
import type { ColumnCategory } from '@/components/custom-column-selector';

const { t } = useI18n();

// Data
const accountData = ref<AdAccount[]>([]);
const userData = ref<UserAccount[]>([]);
const accountLoading = ref(false);
const userLoading = ref(false);
const selectedRowKeys = ref<string[]>([]);
const currentPlatform = ref<PlatformType>('meta');
const currentTab = ref<FilterTab>('adAccounts');
const currentKeyword = ref('');
const currentPage = ref(1);
const pageSize = ref(20);
const total = ref(0);

const filterSectionRef = ref();

// 用户筛选条件
const userFilters = ref({
  autoBind: undefined as boolean | undefined,
  personalAccount: '',
  authorizer: '',
  authTimeRange: undefined as any,
  authStatus: undefined as string | undefined,
});

// 广告账户筛选条件
const adAccountFilters = ref({
  personalAccount: '',
});

// 授权账号限制信息
const authLimit = ref({
  total: 2000,
  authorized: 3,
  remaining: 1997,
});

// 编辑弹窗相关
const editModalVisible = ref(false);
const isBatchEdit = ref(false);
const currentEditRecord = ref<any>(null);

// 修改FB个人号弹窗相关
const modifyFbAccountModalVisible = ref(false);
const fbAccountsList = ref<any[]>([
  {
    id: 'fb_001',
    fbAccount: 'FB账号1',
    fbAccountId: '1000001',
    authStatus: 'authorized',
    adspolarUsername: 'admin_z',
    authTime: '2024-01-15 10:30:00',
  },
  {
    id: 'fb_002',
    fbAccount: 'FB账号2',
    fbAccountId: '1000002',
    authStatus: 'expired',
    adspolarUsername: 'zhang_san',
    authTime: '2024-02-20 14:20:00',
  },
]);

// 自定义列弹窗相关
const customColumnModalVisible = ref(false);
const customColumnCategories = ref<ColumnCategory[]>([
  {
    key: 'attribute',
    label: '属性数据',
    items: [
      { key: 'accountName', label: '广告账户名称' },
      { key: 'accountId', label: '广告账户ID' },
      { key: 'accountNote', label: '账号备注名' },
      { key: 'accountStatus', label: '账户状态' },
      { key: 'authStatus', label: '授权状态' },
      { key: 'authTime', label: '授权时间' },
      { key: 'bm', label: 'BM' },
      { key: 'balance', label: '余额' },
      { key: 'owner', label: '所属人员' },
      { key: 'assistant', label: '协助人员' },
      { key: 'personalAccountCount', label: '个人账号数量' },
      { key: 'personalAccounts', label: '个人账号' },
    ],
  },
]);
const selectedCustomColumns = ref<string[]>([]);

// 自定义列localStorage key
const CUSTOM_COLUMNS_STORAGE_KEY = 'ad-account-custom-columns';

// 加载自定义列配置
const loadCustomColumns = () => {
  try {
    const saved = localStorage.getItem(CUSTOM_COLUMNS_STORAGE_KEY);
    if (saved) {
      const parsed = JSON.parse(saved);
      if (Array.isArray(parsed) && parsed.length > 0) {
        selectedCustomColumns.value = parsed;
        return;
      }
    }
    // 默认选中前几列
    selectedCustomColumns.value = customColumnCategories.value
      .slice(0, 1)
      .flatMap(cat => cat.items.slice(0, 6).map(item => item.key));
  } catch (error) {
    console.error('Failed to load custom columns:', error);
    // 默认选中前几列
    selectedCustomColumns.value = customColumnCategories.value
      .slice(0, 1)
      .flatMap(cat => cat.items.slice(0, 6).map(item => item.key));
  }
};

// 所属人员和协助人员选项
const ownerOptions = ref<Array<{ label: string; value: string }>>([]);
const assistantOptions = ref<Array<{ label: string; value: string }>>([]);

// Computed
const hasSelected = computed(() => selectedRowKeys.value.length > 0);

// 批量编辑时选中的账户数据
const selectedAccountsForBatch = computed(() => {
  if (!isBatchEdit.value) return [];
  return accountData.value
    .filter((account) => selectedRowKeys.value.includes(account.id))
    .map((account) => ({
      id: account.id,
      accountId: account.id,
      accountName: account.name,
      platform: currentPlatform.value,
    }));
});

const pagination = computed(() => ({
  current: currentPage.value,
  pageSize: pageSize.value,
  total: total.value,
  showSizeChanger: true,
  showQuickJumper: true,
  showTotal: (total: number) => t('pages.adAccount.messages.total', { count: total }),
}));

// Methods
const loadAccountData = async () => {
  accountLoading.value = true;
  try {
    const params: any = {
      platform: currentPlatform.value,
      page: currentPage.value,
      pageSize: pageSize.value,
      keyword: currentKeyword.value,
    };
    if (adAccountFilters.value.personalAccount) {
      params.personalAccount = adAccountFilters.value.personalAccount;
    }
    if (adAccountFilters.value.accountStatus) {
      params.accountStatus = adAccountFilters.value.accountStatus;
    }
    if (adAccountFilters.value.authStatus) {
      params.authStatus = adAccountFilters.value.authStatus;
    }
    if (adAccountFilters.value.owner) {
      params.owner = adAccountFilters.value.owner;
    }
    if (adAccountFilters.value.assistant) {
      params.assistant = adAccountFilters.value.assistant;
    }
    if (adAccountFilters.value.bm) {
      params.bm = adAccountFilters.value.bm;
    }
    if (adAccountFilters.value.authTimeRange) {
      params.authTimeStart = adAccountFilters.value.authTimeRange[0];
      params.authTimeEnd = adAccountFilters.value.authTimeRange[1];
    }

    console.log('loadAccountData params:', params);
    const response = await getAdAccountsApi(params);
    accountData.value = response.data.data;
    total.value = response.data.total;
  } catch (error) {
    console.error('Failed to load ad accounts:', error);
    accountData.value = [];
    total.value = 0;
  } finally {
    accountLoading.value = false;
  }
};

const loadUserData = async () => {
  userLoading.value = true;
  try {
    const params: any = {
      platform: currentPlatform.value,
      page: currentPage.value,
      pageSize: pageSize.value,
      keyword: currentKeyword.value,
    };
    if (userFilters.value.autoBind !== undefined) {
      params.autoBind = userFilters.value.autoBind;
    }
    if (userFilters.value.personalAccount) {
      params.personalAccount = userFilters.value.personalAccount;
    }
    if (userFilters.value.authorizer) {
      params.authorizer = userFilters.value.authorizer;
    }
    if (userFilters.value.authStatus) {
      params.authStatus = userFilters.value.authStatus;
    }
    if (userFilters.value.authTimeRange) {
      params.authTimeStart = userFilters.value.authTimeRange[0];
      params.authTimeEnd = userFilters.value.authTimeRange[1];
    }

    const response = await getAccountsApi(params);
    userData.value = response.data.data;
    total.value = response.data.total;
  } catch (error) {
    console.error('Failed to load user accounts:', error);
    userData.value = [];
    total.value = 0;
  } finally {
    userLoading.value = false;
  }
};

const loadData = async () => {
  if (currentTab.value === 'users') {
    await loadUserData();
  } else {
    await loadAccountData();
  }
};

const handleTabChange = (tab: FilterTab) => {
  currentTab.value = tab;
  currentPage.value = 1;
  selectedRowKeys.value = [];
  currentKeyword.value = '';
  userFilters.value = {
    autoBind: undefined,
    personalAccount: '',
    authorizer: '',
    authTimeRange: undefined,
    authStatus: undefined,
  };
  loadData();
};

const handleSearch = (keyword: string) => {
  currentKeyword.value = keyword;
  currentPage.value = 1;
  loadData();
};

const handleUserFilterChange = (filters: any) => {
  userFilters.value = { ...filters };
  currentPage.value = 1;
  // TODO: 应用筛选条件
  loadUserData();
};

const handleAdAccountFilterChange = (filters: any) => {
  adAccountFilters.value = { ...filters };
  currentPage.value = 1;
  // TODO: 应用筛选条件
  loadAccountData();
};

const handleSelectionChange = (keys: string[]) => {
  selectedRowKeys.value = keys;
};

const clearSelection = () => {
  selectedRowKeys.value = [];
};

const handlePageChange = (page: number, size: number) => {
  currentPage.value = page;
  pageSize.value = size;
  loadData();
};

const handleAddAccount = () => {
  const facebookAuthUrl = 'https://www.facebook.com/login.php?skip_api_login=1&api_key=2305992779568415&kid_directed_site=0&app_id=2305992779568415&signed_next=1&next=https%3A%2F%2Fwww.facebook.com%2Fdialog%2Foauth%3Fclient_id%3D2305992779568415%26scope%3Dpublic_profile%252Cads_management%252Cads_read%252Cpages_read_engagement%252Cbusiness_management%252Cpages_manage_ads%252Ccatalog_management%252Cpages_manage_metadata%252Cpages_manage_engagement%252Cpages_read_user_content%252Cpages_manage_posts%26redirect_uri%3Dhttps%253A%252F%252Ftm-ac.mobvista.com%252Fxmp%252Ffacebook%252Fauth%26state%3Dmediapluse09%2540gmail.com%257C30618%257C11406%257C5b0f605ea2fa92763763245721eadce6%257C%257Cr4UXyrDgQ2WuQfGV%26ret%3Dlogin%26fbapp_pres%3D0%26logger_id%3D019d3dec-f032-7d89-923e-81e13dd75f51%26tp%3Dunspecified&cancel_url=https%3A%2F%2Ftm-ac.mobvista.com%2Fxmp%2Ffacebook%2Fauth%3Ferror%3Daccess_denied%26error_code%3D200%26error_description%3DPermissions%2Berror%26error_reason%3Duser_denied%26state%3Dmediapluse09%2540gmail.com%257C30618%257C11406%257C5b0f605ea2fa92763763245721eadce6%257C%257Cr4UXyrDgQ2WuQfGV%23_%3D_&display=page&locale=zh_CN&pl_dbl=0&is_business_login=1';
  window.open(facebookAuthUrl, '_blank');
};

const handleEditAccount = (record: AdAccount) => {
  isBatchEdit.value = false;
  currentEditRecord.value = record;
  editModalVisible.value = true;
};

const handleDeleteAccount = async (record: AdAccount) => {
  try {
    await deleteAdAccountApi(record.id);
    message.success(t('pages.adAccount.messages.deleteSuccess'));
    loadAccountData();
  } catch (error: any) {
    console.error('Delete failed:', error);
    message.error(error.response?.data?.message || t('pages.adAccount.messages.deleteError'));
  }
};

const handleDeleteUser = async (record: UserAccount) => {
  try {
    await deleteAccountApi(record.id);
    message.success(t('pages.adAccount.messages.deleteSuccess'));
    loadUserData();
  } catch (error: any) {
    console.error('Delete user failed:', error);
    message.error(error.response?.data?.message || t('pages.adAccount.messages.deleteError'));
  }
};

const handleViewAccounts = (record: UserAccount) => {
  message.info(t('pages.adAccount.messages.viewAccounts', { name: record.name, count: record.boundCount }));
};

const handleAutoBindChange = async (record: UserAccount, checked: boolean) => {
  record.switching = true;
  try {
    await updateAutoBindApi({
      id: record.id,
      autoBind: checked,
    });
    record.autoBind = checked;
    message.success(
      checked
        ? t('pages.adAccount.messages.autoBindEnabled')
        : t('pages.adAccount.messages.autoBindDisabled')
    );
  } catch (error: any) {
    console.error('Update auto-bind failed:', error);
    message.error(error.response?.data?.message || t('pages.adAccount.messages.autoBindError'));
    // 恢复开关状态
    record.autoBind = !checked;
  } finally {
    record.switching = false;
  }
};

// 批量操作菜单点击处理
const handleBatchMenuClick = async ({ key }: { key: string }) => {
  switch (key) {
    case 'edit':
      await handleBatchEdit();
      break;
    case 'unbind':
      await handleBatchUnbind();
      break;
    case 'delete':
      await handleBatchDelete();
      break;
    case 'modifyFbAccount':
      await handleBatchModifyFbAccount();
      break;
  }
};

// 批量编辑
const handleBatchEdit = async () => {
  isBatchEdit.value = true;
  currentEditRecord.value = null;
  editModalVisible.value = true;
};

// 批量解绑
const handleBatchUnbind = async () => {
  message.success(t('pages.adAccount.batch.unbindSuccess'));
  selectedRowKeys.value = [];
  loadData();
};

// 批量删除
const handleBatchDelete = async () => {
  if (selectedRowKeys.value.length === 0) {
    message.warning(t('pages.adAccount.messages.noSelection'));
    return;
  }

  try {
    await batchDeleteAdAccountsApi({ ids: selectedRowKeys.value });
    message.success(t('pages.adAccount.batch.deleteSuccess'));
    selectedRowKeys.value = [];
    loadData();
  } catch (error: any) {
    console.error('Batch delete failed:', error);
    message.error(error.response?.data?.message || t('pages.adAccount.messages.deleteError'));
  }
};

// 批量修改FB个人号
const handleBatchModifyFbAccount = async () => {
  modifyFbAccountModalVisible.value = true;
};

// 修改FB个人号弹窗确认
const handleModifyFbAccountConfirm = (fbAccounts: any[]) => {
  fbAccountsList.value = fbAccounts;
  message.success(t('pages.adAccount.batch.modifyFbAccountSuccess'));
  selectedRowKeys.value = [];
  loadData();
};

const handleSyncData = () => {
  loadData();
};

const handleCustomColumns = () => {
  customColumnModalVisible.value = true;
};

// 自定义列确认处理
const handleCustomColumnConfirm = (data: { selected: any[]; saveAsCommon: boolean }) => {
  selectedCustomColumns.value = data.selected.map(item => item.key);
  // 保存到localStorage
  try {
    localStorage.setItem(CUSTOM_COLUMNS_STORAGE_KEY, JSON.stringify(selectedCustomColumns.value));
  } catch (error) {
    console.error('Failed to save custom columns:', error);
  }
  message.success(t('pages.adAccount.messages.customColumnsSaved'));
};

// 编辑弹窗确认处理
const handleEditConfirm = async (value: any) => {
  console.log('handleEditConfirm called with:', value);
  console.log('currentEditRecord:', currentEditRecord.value);
  try {
    if (isBatchEdit.value) {
      // 批量编辑
      const ids = selectedRowKeys.value;
      const params: any = { ids };
      if (value.owner !== undefined && value.owner !== null) params.owner = value.owner;
      if (value.assistants !== undefined && value.assistants !== null) params.assistants = value.assistants;

      console.log('batchEditAdAccountsApi params:', params);
      await batchEditAdAccountsApi(params);
      message.success(t('pages.adAccount.edit.batchSuccess'));
    } else {
      // 单独编辑
      const params: any = {
        id: currentEditRecord.value.id,
      };
      // 只有当值不为 null/undefined 时才添加
      if (value.owner !== undefined && value.owner !== null) {
        params.owner = value.owner;
      }
      if (value.assistants !== undefined && value.assistants !== null) {
        params.assistants = value.assistants;
      }

      console.log('editAdAccountApi params:', params);
      await editAdAccountApi(params);
      message.success(t('pages.adAccount.edit.success'));
    }
    editModalVisible.value = false;
    loadData();
  } catch (error: any) {
    console.error('Edit failed:', error);
    message.error(error.response?.data?.message || error.message || t('pages.adAccount.edit.failed'));
  }
};

// 加载用户选项
const loadUserOptions = async () => {
  try {
    const response = await getUserOptionsApi();
    console.log('User options API response:', response);
    // /user/options 返回的数据结构: { status: true, data: [...] }
    // data 数组中每一项已有 label, value, name, email 字段
    const users = response.data || [];
    console.log('Users list:', users);

    const options = users.map((item: any) => ({
      label: item.label || `${item.name} (${item.email})`,
      value: String(item.value), // value 是 number，转为 string 以匹配 formData
    }));
    console.log('Mapped options:', options);
    ownerOptions.value = options;
    assistantOptions.value = options;
  } catch (error) {
    console.error('Failed to load user options:', error);
  }
};

// Watch platform change
watch(currentPlatform, () => {
  currentPage.value = 1;
  selectedRowKeys.value = [];
  loadData();
});

// Lifecycle
onMounted(() => {
  loadData();
  loadUserOptions();
  loadCustomColumns();
});
</script>

<style scoped lang="less">
.ad-account-page {
  display: flex;
  gap: 16px;
  height: calc(100vh - 120px);
  min-height: 500px;

  .page-sidebar {
    flex-shrink: 0;
    width: 200px;
    background: #fff;
    border-radius: 8px;
    overflow: hidden;
  }

  .page-content {
    flex: 1;
    overflow: hidden;
    display: flex;
    flex-direction: column;
    gap: 12px;

    .auth-limit-banner {
      display: flex;
      align-items: center;
      justify-content: center;
      gap: 32px;
      padding: 12px 16px;
      background: #fff;
      border-radius: 8px;
      box-shadow: 0 1px 4px rgba(0, 0, 0, 0.06);

      .limit-item {
        font-size: 14px;
        color: #595959;

        strong {
          color: #262626;
          font-weight: 500;
          font-size: 16px;
        }

        &.remaining {
          strong {
            color: #52c41a;
          }
        }
      }
    }

    .content-card {
      background: #fff;
      border-radius: 8px;
      padding: 16px;
      flex: 1;
      display: flex;
      flex-direction: column;
      gap: 16px;
      overflow: hidden;
      min-height: 0;

      .action-bar {
        display: flex;
        justify-content: space-between;
        align-items: center;

        .action-left {
          display: flex;
          gap: 8px;
        }

        .action-right {
          display: flex;
          gap: 8px;
        }
      }

      .filter-area {
        flex-shrink: 0;
      }

      .table-area {
        flex: 1;
        overflow: hidden;
        min-height: 0;
      }
    }
  }
}
</style>
