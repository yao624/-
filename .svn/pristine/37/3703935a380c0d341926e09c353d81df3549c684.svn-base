<template>
  <page-container :showPageHeader="false">
    <div class="ant-pro-table">
      <a-card>
        <a-row :gutter="[12, 0]" :style="{ width: '100%' }" align="middle">
          <a-col>
            <span v-if="hasSelected">
              {{ `Selected ${selectedRowKeys.length} items` }}
              <a-button
                type="link"
                size="small"
                @click="clearSelection"
                style="margin-left: 8px; padding: 0;"
              >
                {{ t('pages.adc.clear.selection') }}
              </a-button>
            </span>
          </a-col>
          <a-col>
            <!-- <a-button
              type="primary"
              @click="router.push(`/ads/create_ads?${getQueryString(selectedRows)}`)"
              style="margin-right: 8px;"
            >
              {{ t('Create Ads') }}
            </a-button> -->
            <a-button
              type="primary"
              @click="router.push(`/ads/create-ads-v2?${getQueryString(selectedRows)}`)"
            >
              {{ t('Create Ads') }}
            </a-button>
          </a-col>
          <a-col :flex="1">
            <applied-filters :filters="appliedFilters"></applied-filters>
          </a-col>
          <a-col>
            <a-dropdown v-if="selectedRowKeys.length > -1">
              <a-button type="primary">
                {{ t('pages.common.action') }}
                <down-outlined />
              </a-button>
              <template #overlay>
                <a-menu @click="handleMenuClick" :items="actionItems"></a-menu>
              </template>
            </a-dropdown>
          </a-col>
          <a-col>
            <a-button @click="showFiltersModal" :icon="h(FilterOutlined)" style="margin-left: 8px">
              {{ getFiltersButtonText() }}
            </a-button>
          </a-col>
          <a-col>
            <a-tooltip :title="t('pages.common.refresh')">
              <a-button :icon="h(ReloadOutlined)" @click="reload"></a-button>
            </a-tooltip>
          </a-col>
          <a-col>
            <column-orgnizer
              :columns="mergedColumns"
              @change:columns="columns => (dynamicColumns = columns)"
            ></column-orgnizer>
          </a-col>
          <a-col>
            <dynamic-form :form-items="formItems" @change:form-data="onSearch"></dynamic-form>
          </a-col>
        </a-row>
      </a-card>
      <a-card>
        <a-table
          :columns="dynamicColumns"
          :data-source="state.dataSource"
          :row-key="record => record.id"
          :loading="state.loading"
          :pagination="pagination"
          :size="state.size"
          :scroll="{ y: tableHeight }"
          bordered
          sticky
          @change="handleTableChange"
          :custom-row="customAdAccountRow"
          :row-selection="{ selectedRowKeys: selectedRowKeys, onChange: onSelectChange }"
        >
          <template #bodyCell="{ column, text, record }">
            <template v-if="column['dataIndex'] === 'action'">
              <a @click="handleFilters(record)">
                {{ t('pages.adc.filters')
                }}{{
                  record.filters && record.filters.length > 0 ? `(${record.filters.length})` : ''
                }}
              </a>
              <a-divider type="vertical" />
              <a-dropdown>
                <a>
                  {{ t('pages.adc.action.more') }}
                  <down-outlined />
                </a>
                <template #overlay>
                  <a-menu @click="e => handleActionManuClick(e, record)">
                    <a-menu-item key="assign-rule">
                      <a>{{ t('pages.adc.assign_rule') }}</a>
                    </a-menu-item>
                    <a-menu-item v-if="record.is_archived" key="unarchive">
                      <a>{{ t('pages.adc.action.unarchive') }}</a>
                    </a-menu-item>
                    <a-menu-item v-if="!record.is_archived" key="archive">
                      <a>{{ t('pages.adc.action.archive') }}</a>
                    </a-menu-item>
                    <a-menu-item key="sync">
                      <a>{{ t('pages.adc.action.sync') }}</a>
                    </a-menu-item>
                    <a-menu-item v-if="!record.enable_rule" key="enable-rule">
                      <a>{{ t('pages.adc.action.rule.enable') }}</a>
                    </a-menu-item>
                    <a-menu-item v-if="record.enable_rule" key="disable-rule">
                      <a>{{ t('pages.adc.action.rule.disable') }}</a>
                    </a-menu-item>
                  </a-menu>
                </template>
              </a-dropdown>
            </template>
            <template v-if="['name'].includes(`${column['dataIndex']}`)">
              <copy-outlined
                style="color: #1677ff"
                v-if="text"
                @click.stop=""
                @click="copyCell(text)"
              />
              {{ text }}
            </template>
            <template v-if="['source_id'].includes(`${column['dataIndex']}`)">
              <copy-outlined
                style="color: #1677ff"
                v-if="text"
                @click.stop=""
                @click="copyCell(text)"
              />
              {{ text }}
            </template>
            <template v-if="['created_at', 'updated_at'].includes(`${column['dataIndex']}`)">
              <span>{{ dayjs(text).format('YYYY-MM-DD HH:mm:ss') }}</span>
            </template>
            <template v-if="column['dataIndex'] == 'account_status'">
              <a-badge
                :color="text === 'ACTIVE' ? 'green' : text === 'DISABLED' ? 'red' : 'gray'"
                :text="text"
              />
            </template>
            <template v-if="['fb_accounts'].includes(`${column['dataIndex']}`)">
              <a-button type="link" @click="showAccountModal(record)">
                {{ record.fb_accounts?.length }}
              </a-button>
            </template>
            <template v-if="['fb_business_users'].includes(`${column['dataIndex']}`)">
              <a-button type="link" @click="showAccountModal(record)">
                {{ record.fb_business_users?.length }}
              </a-button>
            </template>
            <template v-if="['bms'].includes(`${column['dataIndex']}`)">
              <a-button type="link" @click="showBmModal(record)">
                {{ record.bms?.length }}
              </a-button>
            </template>
            <template v-if="['pixels'].includes(`${column['dataIndex']}`)">
              <a-button type="link" @click="showPixelModal(record)">
                {{ record.pixels?.length }}
              </a-button>
            </template>
            <template v-if="['users'].includes(`${column['dataIndex']}`)">
              <a-button type="link" @click="showSysUsersModal(record)">
                {{ record.users?.length }}
              </a-button>
            </template>
            <template v-if="['bm_system_users'].includes(`${column['dataIndex']}`)">
              <a-button type="link" @click="showFbApiTokenModal(record)">
                {{ record.bm_system_users?.length }}
              </a-button>
            </template>
            <template v-if="column['dataIndex'] == 'enable_rule'">
              <a-badge
                :color="text === true ? 'green' : 'gray'"
                :text="text === true ? t('pages.common.enabled') : t('pages.common.disabled')"
              />
            </template>
            <template v-if="column['dataIndex'] == 'is_archived'">
              <a-badge
                :color="text === true ? 'gray' : 'green'"
                :text="text === true ? t('pages.adc.archived.true') : t('pages.adc.archived.false')"
              />
            </template>
            <template v-if="column['dataIndex'] == 'auto_sync'">
              <a-badge
                :color="text === true ? 'green' : 'gray'"
                :text="text === true ? t('Enabled') : t('Disabled')"
              />
            </template>
            <template v-if="column['dataIndex'] === 'tags'">
              <a-tag v-for="item in record.tags" :key="item.id">{{ item.name }}</a-tag>
            </template>
            <template v-if="column['dataIndex'] === 'default_funding'">
              <div class="payment-cell">
                <div class="payment-content">
                  <div class="payment-text">
                    <span class="payment-info" v-if="text">{{ text }}</span>
                    <span v-else class="no-payment">{{ t('No Payment') }}</span>
                  </div>
                  <div class="payment-actions" v-if="text || shouldShowCardIcon(record, text)">
                    <a-button
                      type="text"
                      size="small"
                      class="action-btn copy-btn"
                      @click.stop="copyPaymentInfo(text)"
                      v-if="text"
                    >
                      <copy-outlined />
                    </a-button>
                    <a-button
                      type="text"
                      size="small"
                      class="action-btn card-btn"
                      @click.stop="showCardModal(record)"
                      v-if="shouldShowCardIcon(record, text)"
                    >
                      <div class="card-icon-wrapper">
                        <credit-card-outlined />
                        <span class="card-count" v-if="record.cards && record.cards.length > 0">
                          {{ record.cards.length }}
                        </span>
                      </div>
                    </a-button>
                  </div>
                </div>
              </div>
            </template>
            <template v-if="column['dataIndex'] === 'is_topup'">
              <a-switch
                :checked="record.is_topup"
                :disabled="role !== 'admin'"
                @change="(checked: boolean) => handleTopupToggle(record, checked)"
                size="small"
              />
            </template>
          </template>
        </a-table>
      </a-card>
    </div>
    <div>
      <a-modal
        v-model:open="openPixelModal"
        :title="t('pages.adc.pixel')"
        width="800px"
        @ok="openPixelModal = false"
      >
        <a-table
          :columns="pixelColumns"
          :data-source="pixelData"
          :row-key="record => record.id"
          bordered
          sticky
          size="small"
        >
          <template #bodyCell="{ column, text, record }">
            <template v-if="column['dataIndex'] == 'adtrust_dsl'">
              {{ text }} ({{ record.currency }})
            </template>
            <template v-if="column['dataIndex'] == 'tags'">
              <a-tag v-for="item in record.tags" :key="item.id">{{ item.name }}</a-tag>
            </template>
            <template v-if="column['dataIndex'] == 'account_status'">
              <a-badge
                :color="text === 'ACTIVE' ? 'green' : text === 'DISABLED' ? 'red' : 'gray'"
                :text="text"
              />
            </template>
          </template>
        </a-table>
      </a-modal>

      <a-modal
        v-model:open="openAccountModal"
        :title="t('pages.adc.acc')"
        width="800px"
        @ok="openAccountModal = false"
      >
        <a-table
          :columns="accountColumns"
          :data-source="accountData"
          :row-key="record => record.id"
          bordered
          sticky
          size="small"
        >
          <template #bodyCell="{ column, text, record }">
            <template v-if="column['dataIndex'] === 'token_valid'">
              <a-badge
                :color="text === true ? 'green' : 'red'"
                :text="text === true ? t('pages.adc.token.valid') : t('pages.adc.token.invalid')"
              />
            </template>
            <template v-if="column['dataIndex'] === 'tags'">
              <div>
                <a-tag v-for="item in record.tags" :key="item.id">{{ item.name }}</a-tag>
              </div>
            </template>
          </template>
        </a-table>
      </a-modal>

      <a-modal v-model:open="openBmModal" title="BM" width="800px" @ok="openBmModal = false">
        <a-table
          :columns="bmColumns"
          :data-source="bmData"
          :row-key="record => record.id"
          bordered
          sticky
          size="small"
        >
          <template #bodyCell="{ column, text }">
            <template v-if="column['dataIndex'] == 'is_disabled_for_integrity_reasons'">
              <a-badge
                :color="text === false ? 'green' : text === true ? 'red' : 'gray'"
                :text="text"
              />
            </template>
          </template>
        </a-table>
      </a-modal>
      <tag-modal
        :model="tagModal.model"
        :visible="tagModal.visible"
        @cancel="
          () => {
            tagModal.visible = false;
          }
        "
        @ok="
          () => {
            tagModal.visible = false;
            reload();
          }
        "
      />

      <user-modal
        :model="userModal.model"
        :visible="userModal.visible"
        @cancel="
          () => {
            userModal.visible = false;
          }
        "
        @ok="
          () => {
            userModal.visible = false;
            reload();
          }
        "
      />

      <user-list-modal
        :model="userListModal.model"
        :visible="userListModal.visible"
        @cancel="
          () => {
            userListModal.visible = false;
          }
        "
        @ok="
          () => {
            userListModal.visible = false;
            reload();
          }
        "
      />

      <fb-api-token-modal
        :model="fbApiTokenModal.model"
        :visible="fbApiTokenModal.visible"
        @cancel="
          () => {
            fbApiTokenModal.visible = false;
          }
        "
        @ok="
          () => {
            fbApiTokenModal.visible = false;
          }
        "
      />

      <spend-cap-modal
        v-if="role === 'admin'"
        v-model:open="spendCapModal.visible"
        :adAccountIds="spendCapModal.adAccountIds"
        @success="reload"
      />

      <topup-modal
        v-if="role === 'admin'"
        v-model:open="topupModal.visible"
        :adAccounts="topupModal.adAccounts"
        @success="reload"
      />

      <filters-modal
        :visible="filtersModal.visible"
        :adAccountIds="filtersModal.adAccountIds"
        :currentFilters="filtersModal.currentFilters"
        @success="handleFiltersSuccess"
        @cancel="handleFiltersCancel"
      />

      <card-modal
        v-model:visible="cardModal.visible"
        :adAccount="cardModal.adAccount"
        :cards="cardModal.cards"
        @success="handleCardModalSuccess"
      />
    </div>
  </page-container>
</template>
<script lang="ts">
import {
  DownOutlined,
  CopyOutlined,
  ReloadOutlined,
  FilterOutlined,
  CreditCardOutlined,
} from '@ant-design/icons-vue';
import {
  defineComponent,
  ref,
  computed,
  watchEffect,
  reactive,
  toRefs,
  toRaw,
  watch,
  nextTick,
  onMounted,
  h,
} from 'vue';

import {
  queryFB_AD_AccountsApi,
  archiveFbAdAccounts,
  unarchiveFbAdAccounts,
  enableRule,
  disableRule,
  fetchData,
  fetchDataRecently,
  fetchAdAccountInfo,
  getFbAdAccountsValidTags,
  autoSyncConfig,
  toggleTopup,
} from '@/api/fb_ad_accounts';
import { addTagsOneApi, deleteTagsOneApi } from '@/api/tags';

// import { useTableDynamicColumns } from '@/utils/hooks/useTableColumn';
import { message, Modal } from 'ant-design-vue';
import useClipboard from 'vue-clipboard3';
import dayjs from 'dayjs';
import { useFetchData } from '@/utils/hooks/useFetchData';
import type { Pagination, TableColumn } from '@/typing';
import { useI18n } from 'vue-i18n';
import type { DefaultRecordType } from 'ant-design-vue/es/vc-table/interface';
import TagModal from './tag-modal.vue';
import UserModal from './user-modal.vue';
import UserListModal from './user-list-modal.vue';
import FbApiTokenModal from './fb-api-token-modal.vue';
import SpendCapModal from './spend-cap-modal.vue';
import TopupModal from './topup-modal.vue';
import FiltersModal from './filters-modal.vue';
import CardModal from './card-modal.vue';
import { useUserStore } from '@/store/user';
import { getUsers } from '@/api/user/role_v2';
import DynamicForm from '@/components/dynamic-form/dynamic-form.vue';
import AppliedFilters from '@/components/applied-filters/applied-filters.vue';
import ColumnOrgnizer from '@/components/column-orgnizer/column-orgnizer.vue';
import { useRouter, useRoute } from 'vue-router';
import { useTableHeight } from '@/utils/hooks/useTableHeight';
import { getFbAccountsValidTags } from '@/api/fb_accounts';
import { getFbApiTokens } from '@/api/fb_api_token';

type Key = string | number;

export default defineComponent({
  setup() {
    const { t } = useI18n();
    const sorterInfoMap = ref();
    const userStore = useUserStore();
    const role = userStore.currentUser.role.name;
    const router = useRouter();
    const route = useRoute();
    const { tableHeight } = useTableHeight(458);
    const userList = ref([]);

    const formItems = ref([
      {
        label: t('Facebook Account Tags'),
        field: 'fb_account_tags',
        multiple: true,
        options: getFbAccountsValidTags().then(({ data }) =>
          data.map(({ name }) => ({ label: name, value: name })),
        ),
      },
      {
        label: t('Ad Account Tags'),
        field: 'ad_account_tags',
        multiple: true,
        options: getFbAdAccountsValidTags().then(({ data }) =>
          data.map(({ name, user_name }) => ({ label: `${name} (${user_name})`, value: name })),
        ),
      },
      { label: 'FB Account Name', field: 'account_names', multiple: true },
      { label: 'FB Account ID', field: 'account_ids', multiple: true },
      { label: 'pages.ads.ad.acc_name', field: 'ad_account_names', multiple: true },
      { label: 'pages.ads.ad.acc_id', field: 'ad_account_ids', multiple: true },
      { label: 'Page Name', field: 'page_names', multiple: true },
      { label: 'Page ID', field: 'page_ids', multiple: true },
      { label: 'BM Name', field: 'bm_names', multiple: true },
      { label: 'BM ID', field: 'bm_ids', multiple: true },
      { label: 'Cards', field: 'cards', multiple: true },
      {
        label: 'pages.ads.sysuser',
        field: 'user_ids',
        multiple: true,
        options: [],
      },
      {
        label: 'Ad Account Status',
        field: 'account_status',
        multiple: true,
        options: [
          { label: 'ACTIVE', value: 'ACTIVE' },
          { label: 'DISABLED', value: 'DISABLED' },
          { label: 'UNSETTLED', value: 'UNSETTLED' },
        ],
      },
      {
        label: 'pages.adc.is_archived',
        field: 'is_archived',
        mode: 'radio',
        options: [
          { label: 'pages.adc.is_archived.all', value: '' },
          { label: 'pages.adc.is_archived.yes', value: 'true' },
          { label: 'pages.adc.is_archived.no', value: 'false' },
        ],
      },
      {
        label: 'pages.adc.enable_rule',
        field: 'enable_rule',
        mode: 'radio',
        options: [
          { label: 'pages.adc.enable_rule.all', value: '' },
          { label: 'pages.adc.enable_rule.yes', value: 'true' },
          { label: 'pages.adc.enable_rule.no', value: 'false' },
        ],
      },
      {
        label: t('Auto sync'),
        field: 'auto_sync',
        mode: 'radio',
        options: [
          { label: 'All', value: '' },
          { label: 'Enable', value: true },
          { label: 'Disable', value: false },
        ],
      },
    ]);
    const appliedFilters = ref({});
    const onSearch = data => {
      Object.entries(data).forEach(([key, value]) => (queryParam[key] = value));
      appliedFilters.value = data;
      // 搜索时清除选择
      selectedState.selectedRowKeys = [];
      selectedState.selectedRows = [];
      handleSearch();
    };

    const inputParam = reactive({
      account_type: 'name',
      account_value: undefined,
      bm_type: 'name',
      bm_value: undefined,
      page_type: 'name',
      page_value: undefined,
      ad_account_type: 'name',
      ad_account_value: undefined,
    });

    const queryParam = reactive({
      account_ids: undefined,
      account_names: undefined,
      ad_account_ids: undefined,
      ad_account_names: undefined,
      bm_ids: undefined,
      bm_names: undefined,
      account_status: undefined,
      is_archived: 'false',
      enable_rule: '',
      sortOrder: undefined,
      sortField: undefined,
      tags: [],
      user_ids: [],
    });

    watchEffect(() => {
      if (inputParam.account_type === 'name') {
        queryParam.account_names = inputParam.account_value;
        queryParam.account_ids = undefined;
      } else if (inputParam.account_type === 'id') {
        queryParam.account_ids = inputParam.account_value;
        queryParam.account_names = undefined;
      }

      if (inputParam.bm_type === 'name') {
        queryParam.bm_names = inputParam.bm_value;
        queryParam.bm_ids = undefined;
      } else if (inputParam.bm_type === 'id') {
        queryParam.bm_ids = inputParam.bm_value;
        queryParam.bm_names = undefined;
      }

      if (inputParam.ad_account_type === 'name') {
        queryParam.ad_account_names = inputParam.ad_account_value;
        queryParam.ad_account_ids = undefined;
      } else if (inputParam.ad_account_type === 'id') {
        queryParam.ad_account_ids = inputParam.ad_account_value;
        queryParam.ad_account_names = undefined;
      }
    });

    watch(
      () => inputParam.account_type,
      (newVal, oldVal) => {
        console.log('type change: ', 'new val: ', newVal, ', old val: ', oldVal);
        if (newVal !== oldVal) {
          console.log('changed');
          inputParam.account_value = undefined;
          if (newVal === 'name') {
            queryParam.account_names = undefined;
            queryParam.account_ids = undefined;
          } else if (newVal === 'id') {
            queryParam.account_ids = undefined;
            queryParam.account_names = undefined;
          }
        }
      },
      { immediate: true },
    );

    watch(
      () => inputParam.ad_account_type,
      (newVal, oldVal) => {
        if (newVal !== oldVal) {
          inputParam.ad_account_value = undefined;
          if (newVal === 'name') {
            queryParam.ad_account_names = undefined;
            queryParam.ad_account_ids = undefined;
          } else if (newVal === 'id') {
            queryParam.ad_account_ids = undefined;
            queryParam.ad_account_names = undefined;
          }
        }
      },
      { immediate: true },
    );

    watch(
      () => inputParam.bm_type,
      (newVal, oldVal) => {
        if (newVal !== oldVal) {
          inputParam.bm_value = undefined;
          if (newVal === 'name') {
            queryParam.bm_names = undefined;
            queryParam.bm_ids = undefined;
          } else if (newVal === 'id') {
            queryParam.bm_ids = undefined;
            queryParam.bm_names = undefined;
          }
        }
      },
      { immediate: true },
    );

    // 在初始化fetchDataContext之前处理URL参数
    const routeQuery = route.query;
    console.log('🚀 setup阶段检查URL参数:', routeQuery);

    // 检查URL数组参数 - 使用准确的数组参数格式
    if (routeQuery['ad_account_ids[]']) {
      let adAccountIds;
      if (Array.isArray(routeQuery['ad_account_ids[]'])) {
        adAccountIds = routeQuery['ad_account_ids[]'];
      } else {
        adAccountIds = [routeQuery['ad_account_ids[]']];
      }

      console.log('✅ setup阶段检测到URL参数，设置queryParam:', adAccountIds);
      queryParam.ad_account_ids = adAccountIds;
      queryParam.is_archived = 'false';

      // 设置应用的过滤器显示
      appliedFilters.value = {
        ad_account_ids: adAccountIds,
      };

      console.log('📋 setup阶段设置完成，最终queryParam:', { ...queryParam });
    }

    // 直接从URL参数构建初始请求参数
    const initialRequestParams: any = {
      account_ids: undefined,
      account_names: undefined,
      ad_account_ids: undefined,
      ad_account_names: undefined,
      bm_ids: undefined,
      bm_names: undefined,
      account_status: undefined,
      is_archived: 'false',
      enable_rule: '',
      sortField: undefined,
      sortOrder: undefined,
      tags: [],
      user_ids: [],
    };

    // 如果有URL数组参数，设置相关字段 - 使用准确的数组参数格式
    if (routeQuery['ad_account_ids[]']) {
      const adAccountIds = Array.isArray(routeQuery['ad_account_ids[]'])
        ? routeQuery['ad_account_ids[]']
        : [routeQuery['ad_account_ids[]']];

      initialRequestParams.ad_account_ids = adAccountIds;
      console.log('🎯 从URL设置 ad_account_ids:', adAccountIds);
    }

    console.log('🔧 最终的initialRequestParams:', initialRequestParams);

    const fetchDataContext = reactive({
      current: 1,
      pageSize: 10,
      requestParams: initialRequestParams,
    });

    console.log('🔧 fetchDataContext初始化:', fetchDataContext);
    console.log('🔍 initialRequestParams:', initialRequestParams);
    const sorted = sorterInfoMap.value || {};
    const mergedColumns = ref<TableColumn[]>([
      {
        title: t('pages.adc.index'),
        dataIndex: 'index',
        customRender: ({ index }) => {
          return `${index + 1}`;
        },
        width: 100,
        align: 'center',
        fixed: 'left',
      },
      ...(role === 'admin'
        ? [
            {
              title: t('pages.adc.action.sys-user'),
              dataIndex: 'users',
              width: 110,
              align: 'center',
              resizable: true,
            } as any,
          ]
        : []),
      {
        title: t('pages.adc.name'),
        dataIndex: 'name',
        sorter: true,
        sortOrder: sorted.columnKey === 'name' && sorted.order,
        align: 'center',
        minWidth: 160,
        ellipsis: false,
        resizable: true,
      },
      {
        title: 'ID',
        dataIndex: 'source_id',
        sorter: true,
        sortOrder: sorted.columnKey === 'source_id' && sorted.order,
        align: 'center',
        minWidth: 160,
        ellipsis: false,
        resizable: true,
      },
      {
        title: t('pages.adc.tags'),
        dataIndex: 'tags',
        minWidth: 100,
        resizable: true,
        ellipsis: true,
      },
      {
        title: t('pages.adc.status'),
        dataIndex: 'account_status',
        sorter: true,
        sortOrder: sorted.columnKey === 'account_status' && sorted.order,
        minWidth: 160,
      },
      {
        title: t('pages.adc.tz'),
        dataIndex: 'timezone_name',
        minWidth: 100,
        resizable: true,
      },
      {
        title: t('pages.adc.currency'),
        dataIndex: 'currency',
        minWidth: 100,
      },
      {
        title: t('pages.adc.limit'),
        dataIndex: 'adtrust_dsl',
        minWidth: 140,
        sorter: true,
        resizable: true,
        sortOrder: sorted.columnKey === 'adtrust_dsl' && sorted.order,
        customRender: ({ text, record }) => {
          return `${text || 'NA'} / ${record.spend_cap || 'NA'}`;
        },
      },
      {
        title: t('pages.adc.balance'),
        dataIndex: 'balance',
        minWidth: 140,
        sorter: true,
        sortOrder: sorted.columnKey === 'balance' && sorted.order,
        customRender: ({ text, record }) => {
          let displayBalance = text;

          // 如果是月付账单，余额需要计算为 spend_cap - balance
          if (
            (record.default_funding === 'monthly invoicing' ||
              record.default_funding === 'penagihan bulanan' ||
              record.default_funding === '月度结算') &&
            record.spend_cap &&
            text
          ) {
            try {
              const spendCap = parseFloat(record.spend_cap);
              const balance = parseFloat(text);
              if (!isNaN(spendCap) && !isNaN(balance)) {
                displayBalance = (spendCap - balance).toFixed(2);
              }
            } catch (error) {
              console.error('计算余额时出错:', error);
              displayBalance = text;
            }
            // 月付账单只显示余额，不显示 threshold_amount
            return `${displayBalance ? displayBalance : 'NA'}`;
          }

          // 非月付账单显示原有格式
          return `${displayBalance ? displayBalance : 'NA'} / ${record.threshold_amount}`;
        },
        resizable: true,
      },
      {
        title: t('pages.adc.spent'),
        dataIndex: 'amount_spent',
        minWidth: 150,
        align: 'center',
        sorter: true,
        sortOrder: sorted.columnKey === 'amount_spent' && sorted.order,
        resizable: true,
      },
      {
        title: t('Payment'),
        dataIndex: 'default_funding',
        minWidth: 140,
      },
      {
        title: t('Topup'),
        dataIndex: 'is_topup',
        minWidth: 80,
        align: 'center' as const,
      },
      {
        title: t('pages.adc.fb_acc'),
        dataIndex: 'fb_accounts',
        minWidth: 100,
      },
      {
        title: t('FB API Token'),
        dataIndex: 'bm_system_users',
        minWidth: 150,
      },
      {
        title: t('pages.adc.bm.acc'),
        dataIndex: 'fb_business_users',
        minWidth: 140,
      },
      {
        title: 'BM',
        dataIndex: 'bms',
        minWidth: 100,
      },
      {
        title: t('pages.adc.pixels'),
        dataIndex: 'pixels',
        minWidth: 100,
      },
      {
        title: t('pages.adc.age'),
        dataIndex: 'age',
        minWidth: 100,
      },
      {
        title: t('pages.adc.rule'),
        dataIndex: 'enable_rule',
        minWidth: 100,
      },
      {
        title: t('pages.adc.archive'),
        dataIndex: 'is_archived',
        minWidth: 100,
      },
      {
        title: t('Auto sync'),
        dataIndex: 'auto_sync',
        minWidth: 100,
      },
      {
        title: t('pages.adc.notes'),
        dataIndex: 'notes',
        minWidth: 100,
      },
      {
        title: 'Updated Date',
        dataIndex: 'updated_at',
        minWidth: 160,
        sorter: true,
        sortOrder: sorted.columnKey === 'updated_at' && sorted.order,
      },
      {
        title: t('pages.common.action'),
        dataIndex: 'action',
        fixed: 'right',
        width: 190,
      },
    ]);
    // mergedColumns.value = ;
    // if (role === 'admin') {
    //   mergedColumns.value.splice(1, 0, );
    // }
    const dynamicColumns = ref<TableColumn[]>([]);

    watch(
      sorterInfoMap,
      () => {
        console.log(sorterInfoMap.value);
        const sortField = sorterInfoMap.value?.columnKey;
        const sortOrder = sorterInfoMap.value?.order;
        if (sortOrder) {
          // 将'order'的值从'descend'或'ascend'转换为'desc'或'asc'
          const orderValue = sortOrder === 'descend' ? 'desc' : 'asc';
          queryParam.sortOrder = orderValue;
          queryParam.sortField = sortField;
          fetchDataContext.requestParams.sortOrder = orderValue;
          fetchDataContext.requestParams.sortField = sortField;
        } else {
          queryParam.sortOrder = undefined;
          queryParam.sortField = undefined;
          queryParam.sortOrder = undefined;
          queryParam.sortField = undefined;
          fetchDataContext.requestParams.sortOrder = undefined;
          fetchDataContext.requestParams.sortField = undefined;
        }
      },
      { immediate: true },
    );
    watchEffect(() => {});

    const selectedState = reactive<{
      selectedRowKeys: Key[];
      selectedRows: any[];
    }>({
      selectedRowKeys: [],
      selectedRows: [],
    });
    const hasSelected = computed(() => selectedState.selectedRowKeys.length > 0);

    // const needRowIndex = ref(false);
    // const {
    //   state: columnState,
    //   dynamicColumns,
    //   dynamicColumnItems,
    //   handleColumnAllClick,
    //   handleColumnChange,
    //   reset,
    //   move, // @ts-ignore
    // } = useTableDynamicColumns(mergedColumns, { needRowIndex });

    const { context: state, reload } = useFetchData(queryFB_AD_AccountsApi, fetchDataContext);

    const pagination = computed(() => ({
      total: state.total,
      current: state.current,
      pageSize: state.pageSize,
      pageSizeOptions: ['10', '20', '50', '100', '200', '500'],
      showTotal: (total: any) => `Total ${total} items`,
      showSizeChanger: true,
      showQuickJumper: true,
    }));

    // 分配规则
    // TODO: 暂时不做
    const handleAssignRules = (record: any) => {
      console.log(record);
      //   archiveFbAdAccounts({ ids: [record.id] })
      //     .then(res => {
      //       message.success(res['message']);
      //     })
      //     .catch(error => {
      //       message.error(t('pages.common.request.failed'));
      //       console.error(error);
      //     })
      //     .finally(() => {
      //       reload();
      //     });
    };

    const getQueryString = acc => acc.map(({ source_id }) => `aid=${source_id}`).join('&');

    const handleTableChange = ({ current, pageSize }: Pagination, _filters: any, sorter: any) => {
      sorterInfoMap.value = sorter;
      fetchDataContext.current = current;
      fetchDataContext.pageSize = pageSize;
    };

    // 搜索
    const handleSearch = () => {
      console.log('handle search, queryParam:', queryParam);
      fetchDataContext.current = 1;
      fetchDataContext.requestParams = { ...queryParam };
    };

    // 重置
    const handleReset = () => {
      inputParam.account_value = undefined;
      inputParam.ad_account_value = undefined;
      inputParam.bm_value = undefined;
      inputParam.page_value = undefined;

      queryParam.account_ids = undefined;
      queryParam.account_names = undefined;
      queryParam.ad_account_ids = undefined;
      queryParam.ad_account_names = undefined;
      queryParam.bm_ids = undefined;
      queryParam.bm_names = undefined;

      queryParam.account_status = undefined;
      queryParam.is_archived = 'false';
      queryParam.enable_rule = '';

      queryParam.tags = [];
      queryParam.user_ids = [];

      sorterInfoMap.value = undefined;
      fetchDataContext.current = 1;
      fetchDataContext.requestParams = { ...queryParam };
    };

    const onSelectChange = (selectedRowKeys: Key[], rows) => {
      // console.log('selectedRowKeys changed: ', selectedRowKeys);
      selectedState.selectedRowKeys = selectedRowKeys;
      selectedState.selectedRows = rows;
    };

    // 清除选择
    const clearSelection = () => {
      selectedState.selectedRowKeys = [];
      selectedState.selectedRows = [];
      message.success(t('pages.adc.selection.cleared'));
    };

    const handleActionManuClick = (e: any, record: any) => {
      const key = e.key;
      if (key === 'assign-rule') {
        handleAssignRules(record);
      } else if (key === 'archive') {
        //归档
        archiveFbAdAccounts({ ids: [record.id] })
          .then(res => {
            message.success(res['message']);
          })
          .catch(error => {
            message.error(t('pages.common.request.failed'));
            console.error(error);
          })
          .finally(() => {
            reload();
          });
      } else if (key === 'unarchive') {
        unarchiveFbAdAccounts({ ids: [record.id] })
          .then(res => {
            message.success(res['message']);
          })
          .catch(error => {
            message.error(t('pages.common.request.failed'));
            console.error(error);
          })
          .finally(() => {
            reload();
          });
      } else if (key === 'enable-rule') {
        enableRule({ ids: [record.id] })
          .then(res => {
            message.success(res['message']);
          })
          .catch(error => {
            message.error(t('pages.common.request.failed'));
            console.error(error);
          })
          .finally(() => {
            reload();
          });
      } else if (key === 'disable-rule') {
        disableRule({ ids: [record.id] })
          .then(res => {
            message.success(res['message']);
          })
          .catch(error => {
            message.error(t('pages.common.request.failed'));
            console.error(error);
          })
          .finally(() => {
            reload();
          });
      }
    };

    const handleMenuClick = (e: any) => {
      const key = e.key;
      const selectedIds = toRaw(selectedState.selectedRowKeys);
      if (selectedIds.length === 0) {
        message.error(t('pages.adc.msg.select_acc'));
        return;
      }
      if (key === 'sync-all') {
        console.log('sync all ad data');
        fetchData({
          fb_ad_account_ids: selectedIds,
        })
          .then(res => {
            message.success(res['message']);
          })
          .catch(e => {
            console.log(e);
          });
      } else if (key === 'sync-recently') {
        console.log('sync-recently');
        fetchDataRecently({
          fb_ad_account_ids: selectedIds,
          days: 3,
        })
          .then(res => {
            message.success(res['message']);
          })
          .catch(e => {
            console.log(e);
          });
      } else if (key === 'sync-ad-account') {
        console.log('sync ad accounts');
        fetchAdAccountInfo({
          fb_ad_account_ids: selectedIds,
          days: 3,
        })
          .then(res => {
            message.success(res['message']);
          })
          .catch(e => {
            console.log(e);
          });
      } else if (key === 'archive') {
        archiveFbAdAccounts({ ids: selectedIds })
          .then(res => {
            message.success(res['message']);
          })
          .catch(error => {
            message.error(t('pages.common.request.failed'));
            console.error(error);
          })
          .finally(() => {
            reload();
          });
      } else if (key === 'unarchive') {
        unarchiveFbAdAccounts({ ids: selectedIds })
          .then(res => {
            message.success(res['message']);
          })
          .catch(error => {
            message.error(t('pages.common.request.failed'));
            console.error(error);
          })
          .finally(() => {
            reload();
          });
      } else if (key === 'enable-rule') {
        enableRule({ ids: selectedIds })
          .then(res => {
            message.success(res['message']);
          })
          .catch(error => {
            message.error(t('pages.common.request.failed'));
            console.error(error);
          })
          .finally(() => {
            reload();
          });
      } else if (key === 'disable-rule') {
        disableRule({ ids: selectedIds })
          .then(res => {
            message.success(res['message']);
          })
          .catch(error => {
            message.error(t('pages.common.request.failed'));
            console.error(error);
          })
          .finally(() => {
            reload();
          });
      } else if (key === 'add-tags') {
        // 获取所有选中行的tags
        const allTags = selectedState.selectedRows.reduce((prev, curr) => {
          return prev.concat(curr.tags);
        }, []);

        // 去重
        const uniqueTags = allTags.reduce((prev, curr) => {
          if (!prev.includes(curr.name)) {
            prev.push(curr.name);
          }
          return prev;
        }, []);

        tagModal.model = {
          ids: selectedIds,
          action: 'add',
          tagList: uniqueTags,
          modelType: 'fbadaccounts',
        };
        tagModal.visible = true;
      } else if (key === 'delete-tags') {
        // 获取所有选中行的tags
        const allTags = selectedState.selectedRows.reduce((prev, curr) => {
          return prev.concat(curr.tags);
        }, []);

        // 去重
        const uniqueTags = allTags.reduce((prev, curr) => {
          if (!prev.includes(curr.name)) {
            prev.push(curr.name);
          }
          return prev;
        }, []);

        tagModal.model = {
          ids: selectedIds,
          action: 'delete',
          tagList: uniqueTags,
          modelType: 'fbadaccounts',
        };
        tagModal.visible = true;
      } else if (key === 'assign-sys-user') {
        const selectedIds = toRaw(selectedState.selectedRowKeys);
        userModal.model = {
          adAccountIds: selectedIds,
          action: 'assign',
          userList: userList.value,
        };
        userModal.visible = true;
      } else if (key === 'remove-sys-user') {
        const selectedIds = toRaw(selectedState.selectedRowKeys);
        userModal.model = {
          adAccountIds: selectedIds,
          action: 'remove',
          userList: userList.value,
        };
        userModal.visible = true;
      } else if (key === 'auto-sync-on') {
        const selectedIds = toRaw(selectedState.selectedRowKeys);
        autoSyncConfig({
          ids: [...selectedIds],
          on: true,
        })
          .then(() => {
            message.success(t('Operation success'));
            reload();
          })
          .catch(e => {
            console.log(e);
            message.error(t('Operation failed'));
          });
      } else if (key === 'auto-sync-off') {
        const selectedIds = toRaw(selectedState.selectedRowKeys);
        autoSyncConfig({
          ids: [...selectedIds],
          on: false,
        })
          .then(() => {
            message.success(t('Operation success'));
            reload();
          })
          .catch(e => {
            console.log(e);
            message.error(t('Operation failed'));
          });
      } else if (key === 'spend-cap' && role === 'admin') {
        const selectedIds = toRaw(selectedState.selectedRowKeys);
        if (selectedIds.length === 0) {
          message.error(t('Please select at least one ad account'));
          return;
        }
        spendCapModal.adAccountIds = selectedIds.map(id => String(id));
        spendCapModal.visible = true;
      } else if (key === 'topup' && role === 'admin') {
        const selectedIds = toRaw(selectedState.selectedRowKeys);
        if (selectedIds.length === 0) {
          message.error(t('Please select at least one ad account'));
          return;
        }
        // 获取选中的完整广告账户数据
        const selectedRows = toRaw(selectedState.selectedRows);
        topupModal.adAccounts = selectedRows.filter(row => selectedIds.includes(row.id));
        topupModal.visible = true;
      } else if (key === 'copy-account-and-id') {
        const selectedIds = toRaw(selectedState.selectedRowKeys);
        if (selectedIds.length === 0) {
          message.error(t('pages.adc.msg.select_acc'));
          return;
        }
        // 获取选中的完整广告账户数据
        const selectedRows = toRaw(selectedState.selectedRows);
        const copyText = selectedRows
          .filter(row => selectedIds.includes(row.id))
          .map(row => `${row.name} - ${row.source_id}`)
          .join('\n');

        copyAccountAndId(copyText);
      }
    };

    const { toClipboard } = useClipboard();
    const copyCell = async (text: any) => {
      try {
        await toClipboard(text);
        message.success('copied');
      } catch (e) {
        console.error(e);
      }
    };

    // 复制广告账户和ID
    const copyAccountAndId = async (text: string) => {
      try {
        await toClipboard(text);
        message.success(t('pages.adc.copy.account.and.id.success'));
      } catch (e) {
        console.error(e);
        message.error(t('pages.adc.copy.account.and.id.failed'));
      }
    };

    // 获取 tags 和 proxyes
    const tagsData = ref<any>([]);
    interface TagOption {
      label: string;
      value: string;
    }
    const tagOptions = ref<TagOption[]>([]);
    watch(tagsData, newValue => {
      tagOptions.value = newValue.map(tag => ({
        label: `${tag.name} - ${tag.user_name}`,
        value: tag.name,
      }));
    });

    // 标签相关
    const inputVisible = ref<boolean>(false);
    const inputValue = ref<string>('');
    const inputRef = ref();
    const handleCloseTag = (tag: any) => {
      console.log(' close tag');
      const index = tagsData.value.findIndex(i => i.value === tag.value);
      tagsData.value.splice(index, 0, tag);
      Modal.confirm({
        title: t('pages.hint'),
        content: t('pages.doubleConfirmDel'),
        okText: t('pages.confirm'),
        cancelText: t('pages.cancel'),
        wrapClassName: 'confirm-dialog',
        onOk() {
          deleteTagsOneApi(tag.value)
            .then(() => {
              message.success(t('pages.opSuccessfully'));
              tagsData.value.splice(index, 1);
            })
            .catch(err => {
              console.error(err);
            });
        },
      });
    };
    const changeTag = (tag: any) => {
      console.log('change tag', tag);
      //   console.log(newAccountForm.tag_ids);
      //   const index = newAccountForm.tag_ids.findIndex(i => i === tag.value);
      //   if (index >= 0) {
      //     newAccountForm.tag_ids.splice(index, 1);
      //   } else {
      //     newAccountForm.tag_ids.push(tag.value);
      //   }
    };
    const showInput = () => {
      inputVisible.value = true;
      nextTick(() => {
        inputRef.value.focus();
      });
    };
    const handleInputConfirm = () => {
      const tags = tagsData.value;
      if (inputValue.value && !tags.includes(inputValue.value)) {
        addTagsOneApi({ name: inputValue.value }).then(res => {
          message.success('Success');
          tags.push(res);
          inputVisible.value = false;
          tagsData.value = tags;
          inputValue.value = '';
        });
      }
    };

    // 显示广告账户
    const openPixelModal = ref(false);
    const pixelColumns = [
      {
        title: t('pages.adc.index'),
        dataIndex: 'index',
        customRender: ({ index }) => {
          return `${index + 1}`;
        },
        width: 100,
        align: 'center',
      },
      {
        title: t('pages.adc.name'),
        dataIndex: 'name',
        minWidth: 160,
        align: 'center',
        resizable: true,
      },
      {
        title: 'Pixel',
        dataIndex: 'pixel',
        minWidth: 140,
        align: 'center',
        resizable: true,
      },
    ];
    const pixelData = ref<DefaultRecordType[]>([]);
    const showPixelModal = record => {
      pixelData.value = record.pixels;
      openPixelModal.value = true;
    };

    // 显示主页的 modal
    const openAccountModal = ref(false);
    const accountColumns = [
      {
        title: t('pages.adc.index'),
        dataIndex: 'index',
        customRender: ({ index }) => {
          return `${index + 1}`;
        },
        width: 100,
        align: 'center',
      },
      {
        title: t('pages.adc.name'),
        dataIndex: 'name',
        minWidth: 160,
        align: 'center',
        resizable: true,
      },
      {
        title: 'ID',
        dataIndex: 'source_id',
        minWidth: 140,
        align: 'center',
        resizable: true,
      },
      {
        title: 'Token',
        dataIndex: 'token_valid',
        sorter: true,
        minWidth: 100,
        align: 'center',
      },
      {
        title: 'Notes',
        dataIndex: 'notes',
        sorter: true,
        minWidth: 100,
        align: 'center',
      },
      {
        title: 'Tags',
        dataIndex: 'tags',
        sorter: true,
        minWidth: 100,
        align: 'center',
        resizable: true,
      },
    ];
    const accountData = ref<DefaultRecordType[]>([]);
    const showAccountModal = record => {
      accountData.value = record.fb_accounts;
      openAccountModal.value = true;
    };

    // 显示BM的 modal
    const openBmModal = ref(false);
    const bmColumns = [
      {
        title: t('pages.adc.index'),
        dataIndex: 'index',
        customRender: ({ index }) => {
          return `${index + 1}`;
        },
        width: 100,
        align: 'center',
      },
      {
        title: t('pages.adc.name'),
        dataIndex: 'name',
        minWidth: 160,
        align: 'center',
        resizable: true,
      },
      {
        title: 'ID',
        dataIndex: 'source_id',
        minWidth: 140,
        align: 'center',
        resizable: true,
      },
      {
        title: t('pages.acc.page.restricted'),
        dataIndex: 'is_disabled_for_integrity_reasons',
        minWidth: 100,
        align: 'center',
      },
      {
        title: t('pages.adc'),
        dataIndex: 'ad_accounts',
        sorter: true,
        minWidth: 100,
        align: 'center',
        customRender: ({ record }) => {
          return `${record.ad_accounts.length}`;
        },
      },
      {
        title: t('pages.adc.bm.users'),
        dataIndex: 'users_count',
        minWidth: 100,
        align: 'center',
      },
    ];
    const bmData = ref<DefaultRecordType[]>([]);
    const showBmModal = record => {
      bmData.value = record.bms;
      openBmModal.value = true;
    };

    const actionItems = ref([
      {
        key: 'archive',
        label: t('pages.adc.action.archive'),
        title: t('pages.adc.action.archive'),
      },
      {
        key: 'unarchive',
        label: t('pages.adc.action.unarchive'),
        title: t('pages.adc.action.unarchive'),
      },
      {
        key: 'sync',
        label: t('pages.adc.action.sync'),
        title: t('pages.adc.action.sync'),
        children: [
          {
            key: 'sync-ad-account',
            label: t('pages.adc.action.sync.adc'),
            title: t('pages.adc.action.sync.adc'),
          },
          {
            key: 'sync-recently',
            label: t('pages.adc.action.sync.3days'),
            title: t('pages.adc.action.sync.3days'),
          },
          {
            key: 'sync-all',
            label: t('pages.adc.action.sync.all'),
            title: t('pages.adc.action.sync.all'),
          },
        ],
      },
      {
        key: 'rules',
        label: t('pages.adc.rule'),
        title: t('pages.adc.rule'),
        children: [
          {
            key: 'assign-rule',
            label: t('pages.adc.action.rule.assign'),
            title: t('pages.adc.action.rule.assign'),
          },
          {
            key: 'enable-rule',
            label: t('pages.adc.action.rule.enable'),
            title: t('pages.adc.action.rule.enable'),
          },
          {
            key: 'disable-rule',
            label: t('pages.adc.action.rule.disable'),
            title: t('pages.adc.action.rule.disable'),
          },
        ],
      },
      {
        key: 'tags',
        label: t('pages.adc.action.tags'),
        title: t('pages.adc.action.tags'),
        children: [
          {
            key: 'add-tags',
            label: t('pages.adc.action.tags.add'),
            title: t('pages.adc.action.tags.add'),
          },
          {
            key: 'delete-tags',
            label: t('pages.adc.action.tags.remove'),
            title: t('pages.adc.action.tags.remove'),
          },
        ],
      },
      {
        key: 'auto-sync',
        label: t('Auto sync'),
        title: t('Auto sync'),
        children: [
          {
            key: 'auto-sync-on',
            label: t('Turn on'),
            title: t('Turn on'),
          },
          {
            key: 'auto-sync-off',
            label: t('Turn off'),
            title: t('Turn on'),
          },
        ],
      },
      {
        key: 'copy-account-and-id',
        label: t('pages.adc.copy.account.and.id'),
        title: t('pages.adc.copy.account.and.id'),
      },
    ]);
    if (role === 'admin') {
      actionItems.value.push({
        key: 'sysUsers',
        label: t('pages.adc.action.sys-user'),
        title: t('pages.adc.action.sys-user'),
        children: [
          {
            key: 'assign-sys-user',
            label: t('pages.adc.action.sys-user.assign'),
            title: t('pages.adc.action.sys-user.assign'),
          },
          {
            key: 'remove-sys-user',
            label: t('pages.adc.action.sys-user.remove'),
            title: t('pages.adc.action.sys-user.remove'),
          },
        ],
      });

      // 只有 admin 才能看到 Spend Cap 选项
      actionItems.value.push({
        key: 'spend-cap',
        label: t('Spend Cap'),
        title: t('Spend Cap'),
      });

      // 添加 Topup 选项
      actionItems.value.push({
        key: 'topup',
        label: t('Topup'),
        title: t('Batch Set Topup'),
      });
    }

    // tagModal
    const tagModal = reactive({
      visible: false,
      model: null,
    });

    onMounted(() => {
      console.log('=== onMounted 开始执行 ===');
      console.log('当前route.query:', route.query);

      // 同步更新formItems（如果URL参数已在setup中处理）
      if (route.query.ad_account_ids) {
        let adAccountIds;
        if (Array.isArray(route.query.ad_account_ids)) {
          adAccountIds = route.query.ad_account_ids;
        } else {
          adAccountIds = [route.query.ad_account_ids];
        }

        console.log('📝 onMounted中同步更新formItems');
        formItems.value.forEach((item: any) => {
          if (item.field === 'ad_account_ids') {
            item.value = adAccountIds;
            console.log('✅ 设置formItems ad_account_ids:', adAccountIds);
          } else if (item.field === 'is_archived') {
            item.value = 'false';
          }
        });
      }

      getFbAdAccountsValidTags().then(res => {
        tagsData.value = res.data;
      });
      if (role === 'admin') {
        getUsers().then(res => {
          userList.value = res.data;

          const userIdsItem = formItems.value.find(item => item.field === 'user_ids');
          userIdsItem.options = res.data.map(({ id, name }) => {
            return { label: name, value: id };
          });
        });

        getFbApiTokens({ pageSize: 9999 }).then(res => {
          // console.log(res.data);
          formItems.value.push({
            label: 'FB API Token',
            field: 'bm_system_users',
            multiple: true,
            options: res.data.map(d => ({ label: d.name || 'empty', value: d.id })),
          });
        });
      }

      console.log('=== URL参数处理已在setup阶段完成 ===');
    });

    // userModal
    const userModal = reactive({
      visible: false,
      model: null,
    });

    // userModal
    const userListModal = reactive({
      visible: false,
      model: null,
    });

    // fb api modal
    const fbApiTokenModal = reactive({
      visible: false,
      model: null,
    });

    // spend cap modal
    const spendCapModal = reactive({
      visible: false,
      adAccountIds: [] as string[],
    });

    // topup modal
    const topupModal = reactive({
      visible: false,
      adAccounts: [] as any[],
    });

    // filters modal
    const filtersModal = reactive({
      visible: false,
      adAccountIds: [] as string[],
      currentFilters: [] as any[],
    });

    // 显示filters modal - 批量操作
    const showFiltersModal = () => {
      const selectedIds = toRaw(selectedState.selectedRowKeys);
      if (selectedIds.length === 0) {
        message.error(t('pages.adc.msg.select_acc'));
        return;
      }
      filtersModal.adAccountIds = selectedIds.map(id => String(id));
      filtersModal.currentFilters = [];
      filtersModal.visible = true;
    };

    // 处理单个广告账户的filters
    const handleFilters = (record: any) => {
      filtersModal.adAccountIds = [String(record.id)];
      filtersModal.currentFilters = record.filters || [];
      filtersModal.visible = true;
    };

    // filters成功回调
    const handleFiltersSuccess = () => {
      filtersModal.visible = false;
      // 过滤成功后清除选择
      selectedState.selectedRowKeys = [];
      selectedState.selectedRows = [];
      reload();
    };

    // filters取消回调
    const handleFiltersCancel = () => {
      filtersModal.visible = false;
    };

    // 获取过滤器按钮文本 - 不显示数字
    const getFiltersButtonText = () => {
      return t('pages.adc.filters');
    };

    // card modal
    const cardModal = reactive({
      visible: false,
      adAccount: null as any,
      cards: [] as any[],
    });

    // 复制payment信息
    const copyPaymentInfo = async (paymentInfo: string) => {
      try {
        // 根据需求，如果后4位是数字，就复制后4位，否则复制全部
        const last4 = paymentInfo.slice(-4);
        const copyText = /^\d{4}$/.test(last4) ? last4 : paymentInfo;
        await toClipboard(copyText);
        message.success(t('Copied'));
      } catch (error) {
        message.error(t('Copy failed'));
      }
    };

    // 显示card modal
    const showCardModal = (record: any) => {
      cardModal.adAccount = {
        id: record.id,
        name: record.name,
        source_id: record.source_id,
      };
      cardModal.cards = record.cards || [];
      cardModal.visible = true;
    };

    // card modal成功回调
    const handleCardModalSuccess = () => {
      cardModal.visible = false;
      reload();
    };

    // 判断是否显示卡片图标
    const shouldShowCardIcon = (record: any, paymentInfo: string) => {
      // 如果有绑定的卡片，显示图标
      if (record.cards && record.cards.length > 0) {
        return true;
      }
      // 如果有支付方式且后4位是数字，也显示图标
      if (paymentInfo) {
        const last4 = paymentInfo.slice(-4);
        return /^\d{4}$/.test(last4);
      }
      return false;
    };

    // 判断是否是卡片账户（VISA或Mastercard）
    const isCardAccount = (record: any) => {
      const funding = record.default_funding;
      if (!funding) return false;
      return funding.includes('VISA') || funding.includes('Mastercard');
    };

    // 处理单个充值状态切换
    const handleTopupToggle = async (record: any, checked: boolean) => {
      try {
        await toggleTopup({
          source_ids: [record.source_id],
          value: checked,
        });
        message.success(t('Topup status updated successfully'));
        // 更新本地数据
        record.is_topup = checked;
      } catch (error) {
        message.error(t('Operation failed'));
        console.error('Toggle topup failed:', error);
      }
    };

    const showSysUsersModal = record => {
      userListModal.model = {
        users: record['users'],
      };
      userListModal.visible = true;
    };

    const showFbApiTokenModal = record => {
      console.log('show fb api token modal');
      fbApiTokenModal.model = {
        bm_system_users: record['bm_system_users'],
      };
      fbApiTokenModal.visible = true;
    };

    const selectRow = (selectedTarget, record) => {
      console.log('select row, record: ', record);
      // console.log(selectedTarget);
      const selectedRowKeys = [...selectedTarget.selectedRowKeys];
      const selectedRows = [...selectedTarget.selectedRows];
      console.log(selectedRowKeys);

      const index = selectedRowKeys.indexOf(record.id);

      // 如果已经选中
      if (index !== -1) {
        console.log('已经选中了');
        selectedRowKeys.splice(index, 1);
        selectedRows.splice(index, 1);
      } else {
        console.log('没有选中');
        selectedRowKeys.push(record.id);
        selectedRows.push(record);
      }
      selectedTarget.selectedRowKeys = selectedRowKeys;
      selectedTarget.selectedRows = selectedRows;
    };

    const customAdAccountRow = record => {
      return {
        onClick: () => {
          // const currentRowKeys = [...selectedState.selectedRowKeys];
          // const selectedRows = [...selectedTarget.selectedRows];
          // selectedState.selectedRowKeys =
          selectRow(selectedState, record);
        },
      };
    };

    return {
      t,
      h,
      handleTableChange,
      handleSearch,
      handleReset,
      dynamicColumns,
      // columnState,
      // dynamicColumnItems,
      // handleColumnAllClick,
      // handleColumnChange,
      // reset,
      // move,
      router,
      reload,
      hasSelected,
      onSelectChange,
      clearSelection,
      ...toRefs(selectedState),
      state,
      pagination,
      customAdAccountRow,

      queryParam,
      inputParam,

      handleAssignRules,
      handleActionManuClick,
      handleMenuClick,

      copyCell,
      copyAccountAndId,
      dayjs,

      //标签
      inputVisible,
      inputValue,
      handleCloseTag,
      changeTag,
      showInput,
      handleInputConfirm,

      tagsData,

      // pixel modal
      openPixelModal,
      pixelColumns,
      pixelData,
      showPixelModal,

      // account modal
      openAccountModal,
      accountColumns,
      showAccountModal,
      accountData,

      // bm modal
      openBmModal,
      bmColumns,
      showBmModal,
      bmData,

      mergedColumns,
      actionItems,

      // tag Modal
      tagModal,
      tagOptions,

      // user Modal
      userModal,
      getQueryString,

      // user list modal
      UserListModal,
      userListModal,
      showSysUsersModal,

      role,
      userList,
      formItems,
      onSearch,
      appliedFilters,

      ReloadOutlined,
      tableHeight,

      // fb api token modal
      fbApiTokenModal,
      showFbApiTokenModal,

      // spend cap modal
      spendCapModal,

      // topup modal
      topupModal,
      isCardAccount,
      handleTopupToggle,

      // filters modal
      filtersModal,
      showFiltersModal,
      handleFilters,
      handleFiltersSuccess,
      handleFiltersCancel,
      getFiltersButtonText,
      FilterOutlined,

      // card modal
      cardModal,
      copyPaymentInfo,
      showCardModal,
      handleCardModalSuccess,
      shouldShowCardIcon,
      CreditCardOutlined,
    };
  },
  components: {
    DownOutlined,
    CopyOutlined,
    TagModal,
    UserModal,
    UserListModal,
    FbApiTokenModal,
    SpendCapModal,
    TopupModal,
    DynamicForm,
    AppliedFilters,
    ColumnOrgnizer,
    FiltersModal,
    CardModal,
  },
});
</script>

<style scoped>
.payment-cell {
  min-height: 48px;
  display: flex;
  align-items: center;
}

.payment-content {
  display: flex;
  flex-direction: column;
  gap: 4px;
  width: 100%;
}

.payment-text {
  display: flex;
  align-items: center;
}

.payment-info {
  font-weight: 500;
  color: #262626;
  font-size: 13px;
  max-width: 120px;
  overflow: hidden;
  text-overflow: ellipsis;
  white-space: nowrap;
}

.no-payment {
  color: #8c8c8c;
  font-style: italic;
  font-size: 12px;
}

.payment-actions {
  display: flex;
  gap: 2px;
  align-items: center;
}

.action-btn {
  display: flex;
  align-items: center;
  justify-content: center;
  width: 24px;
  height: 24px;
  border-radius: 4px;
  transition: all 0.2s ease;
  position: relative;
}

.action-btn:hover {
  background-color: #f5f5f5;
  transform: scale(1.1);
}

.copy-btn {
  color: #1677ff;
}

.copy-btn:hover {
  color: #0958d9;
  background-color: #e6f4ff;
}

.card-btn {
  color: #1677ff;
  position: relative;
}

.card-btn:hover {
  color: #4096ff;
  background-color: #f0f5ff;
}

.card-btn .ant-badge {
  position: absolute;
  top: -2px;
  right: -2px;
  z-index: 1;
}

.card-btn .ant-badge-count {
  min-width: 16px;
  height: 16px;
  line-height: 16px;
  font-size: 10px;
  padding: 0 4px;
}

/* 响应式调整 */
@media (max-width: 1200px) {
  .payment-info {
    max-width: 100px;
  }
}

@media (max-width: 768px) {
  .payment-content {
    gap: 2px;
  }

  .payment-info {
    font-size: 12px;
    max-width: 80px;
  }

  .action-btn {
    width: 20px;
    height: 20px;
  }

  .card-count {
    min-width: 10px;
    height: 10px;
    font-size: 7px;
  }
}

.card-icon-wrapper {
  position: relative;
  display: inline-block;
}

.card-count {
  position: absolute;
  top: -4px;
  right: -4px;
  background-color: #1677ff;
  color: white;
  font-size: 8px;
  font-weight: bold;
  min-width: 12px;
  height: 12px;
  border-radius: 6px;
  display: flex;
  align-items: center;
  justify-content: center;
  line-height: 1;
  border: 1px solid #fff;
  box-shadow: 0 0 0 1px #fff;
}
</style>
