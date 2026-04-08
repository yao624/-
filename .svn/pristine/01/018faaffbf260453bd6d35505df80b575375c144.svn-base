<template>
  <page-container :showPageHeader="false">
    <div class="ant-pro-table">
      <div class="ant-pro-table-search">
        <a-form layout="horizontal" :label-col="{ span: 6 }">
          <a-row :gutter="16" type="flex" justify="start">
            <a-col :xs="24" :sm="24" :md="12" :lg="7">
              <a-form-item label="卡号">
                <a-input v-model:value="queryParam.number" placeholder="请输入" />
              </a-form-item>
            </a-col>
            <a-col :xs="24" :sm="24" :md="12" :lg="7">
              <a-form-item label="卡片名称">
                <a-input v-model:value="queryParam.name" placeholder="请输入" />
              </a-form-item>
            </a-col>
            <a-col :xs="24" :sm="24" :md="12" :lg="3" :offset="1">
              <a-button @click="handleSearch" type="primary">搜索</a-button>
              <a-button @click="handleReset" class="ml-16px">重置</a-button>
            </a-col>
          </a-row>
        </a-form>
      </div>
      <a-card :body-style="{ padding: 0 }">
        <div class="ant-pro-table-list-toolbar">
          <div class="ant-pro-table-list-toolbar-container">
            <div class="ant-pro-table-list-toolbar-left">
              <div class="ant-pro-table-list-toolbar-title">查询表格</div>
              <div class="ant-pro-table-list-toolbar-setting-item">
                <span style="margin-left: 8px">
                  <template v-if="hasSelected">
                    {{ `Selected ${selectedRowKeys.length} items` }}
                  </template>
                </span>
              </div>
            </div>
            <div class="ant-pro-table-list-toolbar-right">
              <a-space>
                <a-button type="primary" @click="handleCreateCard">
                  <plus-outlined />
                  开卡
                </a-button>
              </a-space>
              <div class="ant-pro-table-list-toolbar-divider">
                <a-divider type="vertical" />
              </div>
              <a-space>
                <a-button type="primary" @click="reload">
                  <reload-outlined />
                  Refresh
                </a-button>
              </a-space>
              <div class="ant-pro-table-list-toolbar-divider">
                <a-divider type="vertical" />
              </div>
              <div
                class="ant-pro-table-list-toolbar-setting-item"
                v-if="selectedRowKeys.length > -1"
              >
                <a-dropdown>
                  <template #overlay>
                    <a-menu @click="handleMenuClick">
                      <a-menu-item key="sync-multiple">Sync Multiple</a-menu-item>
                      <a-menu-item key="sync-all">Sync All</a-menu-item>
                      <a-menu-item key="set-limits" disabled>Set Limits</a-menu-item>
                      <a-menu-item key="set-single-limit">Set Single Limit</a-menu-item>
                      <a-menu-item key="set-balance">Set Balance</a-menu-item>
                      <a-menu-divider />
                      <a-menu-item key="sync-3-days-transactions">Sync 3 Days Transactions</a-menu-item>
                      <a-menu-item key="custom-sync-transactions">Custom Sync Transactions</a-menu-item>
                    </a-menu>
                  </template>
                  <a-button type="primary">
                    Actions
                    <down-outlined />
                  </a-button>
                </a-dropdown>
              </div>
              <div class="ant-pro-table-list-toolbar-setting-item">
                <a-popover
                  placement="bottomRight"
                  arrowPointAtCenter
                  trigger="click"
                  overlayClassName="ant-pro-table-column-setting-overlay"
                >
                  <template #title>
                    <div class="ant-pro-table-column-setting-title">
                      <a-checkbox
                        v-model:checked="columnState.checkAll"
                        :indeterminate="columnState.indeterminate"
                        @change="handleColumnAllClick"
                      >
                        列展示 / 排序
                      </a-checkbox>
                      <a @click="reset">重置</a>
                    </div>
                  </template>
                  <template #content>
                    <span class="ant-pro-table-column-setting-list">
                      <drag-container
                        lockAxis="y"
                        dragClass="ant-pro-table-drag-ghost"
                        dropClass="ant-pro-table-drop-ghost"
                        @drop="({ removedIndex, addedIndex }) => move(removedIndex, addedIndex)"
                      >
                        <template v-for="column in dynamicColumnItems" :key="column.key">
                          <draggable>
                            <div class="ant-pro-table-column-setting-list-item">
                              <drag-icon />
                              <a-checkbox
                                :checked="column.checked"
                                @change="handleColumnChange($event, column)"
                              >
                                {{ column.label }}
                              </a-checkbox>
                            </div>
                          </draggable>
                        </template>
                      </drag-container>
                    </span>
                  </template>
                  <a-tooltip title="列设置">
                    <setting-outlined />
                  </a-tooltip>
                </a-popover>
              </div>
            </div>
          </div>
        </div>
        <a-table
          :columns="dynamicColumns as any"
          :data-source="state.dataSource"
          :row-key="record => record.id"
          :loading="state.loading"
          :pagination="{
            current: state.current,
            pageSize: state.pageSize,
            total: state.total,
            showTotal: total => `Total ${total} items`,
          }"
          :size="state.size"
          bordered
          sticky
          :custom-cell="customCell"
          @change="handleTableChange"
          :row-selection="{ selectedRowKeys: selectedRowKeys, onChange: onSelectChange }"
        >
          <template #bodyCell="{ column, text, record }">
            <template v-if="column['dataIndex'] === 'action'">
              <a @click="handleSyncOne(record)">Sync</a>
              <a-divider type="vertical" />
              <a-dropdown>
                <a>
                  more
                  <down-outlined />
                </a>
                <template #overlay>
                  <a-menu @click="e => handleActionManuClick(e, record)">
                    <!-- 根据卡片状态显示 Freeze 或 Unfreeze -->
                    <a-menu-item
                      key="freeze"
                      v-if="record['status'] === 'ACTIVE'"
                      :disabled="record['status'] === 'CLOSED'"
                    >
                      <a>Freeze</a>
                    </a-menu-item>
                    <a-menu-item
                      key="unfreeze"
                      v-if="record['status'] === 'INACTIVE'"
                      :disabled="record['status'] === 'CLOSED'"
                    >
                      <a>Unfreeze</a>
                    </a-menu-item>
                    <!-- Cancel 和 Set Limit 只在 Active 状态下可用 -->
                    <a-menu-item key="cancel" :disabled="record['status'] !== 'ACTIVE'">
                      <a>Cancel</a>
                    </a-menu-item>
                    <a-menu-item key="set-limit" disabled>
                      <a>Set Limit</a>
                    </a-menu-item>
                    <a-menu-item key="set-single-limit" :disabled="record['status'] !== 'ACTIVE'">
                      <a>Set Single Limit</a>
                    </a-menu-item>
                    <a-menu-item key="set-balance" :disabled="record['status'] !== 'ACTIVE'">
                      <a>Set Balance</a>
                    </a-menu-item>
                    <a-menu-item key="sync-3-days-transactions" :disabled="record['status'] !== 'ACTIVE'">
                      <a>Sync 3 Days Transactions</a>
                    </a-menu-item>
                    <a-menu-item key="custom-sync-transactions" :disabled="record['status'] !== 'ACTIVE'">
                      <a>Custom Sync Transactions</a>
                    </a-menu-item>
                  </a-menu>
                </template>
              </a-dropdown>
            </template>
            <template v-if="column['dataIndex'] === 'number'">
              <span>{{ record['number'] }}</span>
              <copy-outlined style="color: #1677ff" @click="copyCell(text)" />
            </template>
            <template v-if="column['dataIndex'] === 'cvv'">
              <span>{{ record['cvv'] }}</span>
              <copy-outlined style="color: #1677ff" @click="copyCell(text)" />
            </template>
            <template v-if="column['dataIndex'] === 'expiration'">
              <span>{{ record['expiration'] }}</span>
              <copy-outlined style="color: #1677ff" @click="copyCell(text)" />
            </template>
            <template v-if="['created_at', 'updated_at', 'applied_at'].includes(`${column['dataIndex']}`)">
              <span>{{ dayjs(text).format('YYYY-MM-DD HH:mm:ss') }}</span>
            </template>
          </template>
        </a-table>
      </a-card>
    </div>
    <div>
      <a-modal
        v-model:open="openCardModal"
        title="Create Card"
        ok-text="Create"
        cancel-text="Cancel"
        @ok="handleOpenCardOk"
      >
        <a-form
          ref="createCardFormRef"
          :model="createCardFormData"
          name="form_in_modal"
          :label-col="{ span: 4 }"
        >
          <a-form-item
            name="prefix"
            label="Prefix"
            :rules="[{ required: true, message: '卡片前缀' }]"
          >
            <a-input v-model:value="createCardFormData.prefix" />
          </a-form-item>
          <a-form-item
            name="number"
            label="Count"
            :rules="[{ required: true, message: '开卡张数' }]"
          >
            <a-input-number v-model:value="createCardFormData.number" min="1" />
          </a-form-item>
          <a-form-item
            name="balance"
            label="Balance"
            :rules="[{ required: true, message: '卡片余额' }]"
          >
            <a-input-number v-model:value="createCardFormData.balance" min="1" />
          </a-form-item>
        </a-form>
      </a-modal>

      <a-modal
        v-model:open="openSetLimitModal"
        title="Set Limit"
        @ok="handleSetLimitOk"
        @afterClose="resetLimitForm"
      >
        <a-form ref="setLimitFormRef" :model="setLimitFormData">
          <a-form-item
            name="limits"
            label="Limits"
            :rules="[{ required: true, message: '卡片限额' }]"
          >
            <a-input-number v-model:value="setLimitFormData.limits" min="1" />
          </a-form-item>
        </a-form>
      </a-modal>

      <a-modal
        v-model:open="openSetSingleLimitModal"
        title="Set Single Limit"
        @ok="handleSetSingleLimitOk"
        @afterClose="resetSingleLimitForm"
      >
        <a-form ref="setSingleLimitFormRef" :model="setSingleLimitFormData">
          <a-form-item
            name="limits"
            label="单次交易限额"
            :rules="[{ required: true, message: '请输入单次交易限额' }]"
          >
            <a-input-number v-model:value="setSingleLimitFormData.limits" min="1" />
          </a-form-item>
        </a-form>
      </a-modal>

      <a-modal
        v-model:open="openSetBalanceModal"
        title="Set Balance"
        @ok="handleSetBalanceOk"
        @afterClose="resetBalanceForm"
      >
        <a-form ref="setBalanceFormRef" :model="setBalanceFormData">
          <a-form-item
            name="balance"
            label="余额"
            :rules="[{ required: true, message: '请输入余额' }]"
          >
            <a-input-number v-model:value="setBalanceFormData.balance" min="0" />
          </a-form-item>
        </a-form>
      </a-modal>

      <!-- Custom Sync Transactions Modal -->
      <a-modal
        v-model:open="openCustomSyncModal"
        title="Custom Sync Transactions"
        @ok="handleCustomSyncOk"
        @afterClose="resetCustomSyncForm"
      >
        <a-form ref="customSyncFormRef" :model="customSyncFormData">
          <a-form-item
            name="dateRange"
            label="Time Range"
            :rules="[{ required: true, message: 'Please select time range' }]"
          >
            <a-range-picker
              v-model:value="customSyncFormData.dateRange"
              :disabledDate="disabledDate"
              format="YYYY-MM-DD"
            />
          </a-form-item>
          <a-form-item label="Quick Options">
            <a-space wrap>
              <a-button @click="setQuickDateRange('last3days')">Last 3 days</a-button>
              <a-button @click="setQuickDateRange('last7days')">Last 7 days</a-button>
              <a-button @click="setQuickDateRange('thisweek')">This week</a-button>
              <a-button @click="setQuickDateRange('lastweek')">Last week</a-button>
              <a-button @click="setQuickDateRange('thismonth')">This month</a-button>
              <a-button @click="setQuickDateRange('lastmonth')">Last month</a-button>
            </a-space>
          </a-form-item>
        </a-form>
      </a-modal>
    </div>
  </page-container>
</template>
<script lang="ts">
import { PlusOutlined, SettingOutlined, DownOutlined, CopyOutlined, ReloadOutlined } from '@ant-design/icons-vue';
import { defineComponent, ref, computed, watchEffect, reactive, toRefs, toRaw, watch } from 'vue';
import { useI18n } from 'vue-i18n';

import {
  queryCardListAPI,
  syncMultipleCard,
  syncAllCard,
  unfreezeCard,
  freezeCard,
  createCard,
  setCardLimits,
  setCardSingleLimit,
  setCardBalance,
} from '@/api/virtual_cards';

import { syncCardTransactions } from '@/api/virtual_cards';

import { Container as DragContainer, Draggable } from '@/components/draggable';
import DragIcon from '@/components/table/drag-icon.vue';
import { useTableDynamicColumns } from '@/utils/hooks/useTableColumn';
import { message } from 'ant-design-vue';
import useClipboard from 'vue-clipboard3';
import dayjs from 'dayjs';
import type { Dayjs } from 'dayjs';
import type { FormInstance } from 'ant-design-vue';
import { useFetchData } from '@/utils/hooks/useFetchData';
import type { Pagination } from '@/typing';

type APIParams = {
  pageSize?: any;
  pageNo?: any;
  sortField?: string;
  sortOrder?: number;
  [key: string]: any;
};

type CreateCardAPIParams = {
  prefix: string;
  number: number;
  balance: number;
};

type SetLimitParams = {
  limits: number;
  ids: string[];
};

type SetSingleLimitParams = {
  limits: number;
  ids: string[];
};

type SetBalanceParams = {
  balance: number;
  ids: string[];
};

type Key = string | number;

export default defineComponent({
  setup() {
    const { t } = useI18n();

    const queryCardList = (params: APIParams) => {
      return queryCardListAPI(params);
    };

    // 可以筛选的项目
    const status = ['ACTIVE', 'INACTIVE', 'CLOSED', 'PENDING'];
    const filteredInfoMap = ref();
    const sorterInfoMap = ref();

    const queryParam = reactive({
      number: undefined,
      name: undefined,
      status: undefined,
      sortOrder: undefined,
      sortField: undefined,
    });
    const fetchDataContext = reactive({
      current: 1,
      pageSize: 10,
      // tableSize: 'middle', // 'default' | 'middle' | 'small'
      // stripe: false,
      requestParams: { ...queryParam },
    });
    const mergedColumns = ref<any[]>([]);

    watch(
      filteredInfoMap,
      () => {
        fetchDataContext.requestParams.status = filteredInfoMap.value?.status;
      },
      { immediate: true },
    );
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
    watchEffect(() => {
      const filtered = filteredInfoMap.value || {};
      const sorted = sorterInfoMap.value || {};
      mergedColumns.value = [
        {
          title: '序号',
          dataIndex: 'index',
          customRender: ({ index }) => {
            return `${index + 1}`;
          },
          width: 100,
          align: 'center',
        },
        {
          title: 'Name',
          dataIndex: 'name',
          sorter: true,
          sortOrder: sorted.columnKey === 'name' && sorted.order,
          align: 'center',
          minWidth: 160,
          ellipsis: false,
        },
        {
          title: 'Card',
          dataIndex: 'number',
          minWidth: 160,
        },
        {
          title: 'CVV',
          dataIndex: 'cvv',
          minWidth: 60,
        },
        {
          title: 'Expiration',
          dataIndex: 'expiration',
          minWidth: 60,
        },
        {
          title: 'Status',
          dataIndex: 'status',
          sorter: true,
          sortOrder: sorted.columnKey === 'status' && sorted.order,
          minWidth: 100,
          filteredValue: filtered.status || null,
          filters: [
            { text: status[0], value: status[0] },
            { text: status[1], value: status[1] },
            { text: status[2], value: status[2] },
            { text: status[3], value: status[3] },
          ],
        },
        {
          title: 'Balance',
          dataIndex: 'balance',
          sorter: true,
          sortOrder: sorted.columnKey === 'balance' && sorted.order,
          minWidth: 100,
        },
        {
          title: 'Limits',
          dataIndex: 'limits',
          sorter: true,
          sortOrder: sorted.columnKey === 'limits' && sorted.order,
          minWidth: 100,
        },
        {
          title: 'Currency',
          dataIndex: 'currency',
          minWidth: 100,
        },
        {
          title: 'Trans Count',
          dataIndex: 'transactions_count',
          ellipsis: false,
          minWidth: 100,
        },
        {
          title: 'Trans Amount',
          dataIndex: 'total_transactions_amount',
          ellipsis: false,
          minWidth: 100,
        },
        {
          title: 'Apply Date',
          dataIndex: 'applied_at',
          minWidth: 160,
          sorter: true,
          sortOrder: sorted.columnKey === 'apply_date' && sorted.order,
        },
        {
          title: 'Updated Date',
          dataIndex: 'updated_at',
          minWidth: 160,
          sorter: true,
          sortOrder: sorted.columnKey === 'updated_at' && sorted.order,
        },
        {
          title: 'Action',
          dataIndex: 'action',
          fixed: 'right',
          width: 120,
        },
      ];
    });

    const selectedState = reactive<{
      selectedRowKeys: Key[];
    }>({
      selectedRowKeys: [],
    });
    const hasSelected = computed(() => selectedState.selectedRowKeys.length > 0);

    const needRowIndex = ref(false);
    const {
      state: columnState,
      dynamicColumns,
      dynamicColumnItems,
      handleColumnAllClick,
      handleColumnChange,
      reset,
      move,
    } = useTableDynamicColumns(mergedColumns as any, { needRowIndex });

    const handleSyncOne = (record: any) => {
      console.log('sync one');
      console.log(record);
      const params = {
        ids: [record['id']],
        sync: true,
      };
      syncMultipleCard(params)
        .then(res => {
          message.success(t('pages.virtual_cards.sync.success'));
          console.log(res);
          reload();
        })
        .catch(error => {
          message.error('请求失败');
          console.error(error);
        });
    };

    const { context: state, reload } = useFetchData(queryCardList, fetchDataContext);

    const customCell = ({ column }) => {
      if (column.dataIndex === 'my-custom-show-index') {
        return {};
      }
      // console.log(column);
      return {};
    };

    const handleTableChange = ({ current, pageSize }: Pagination, filters: any, sorter: any) => {
      filteredInfoMap.value = filters;
      sorterInfoMap.value = sorter;
      fetchDataContext.current = current;
      fetchDataContext.pageSize = pageSize;
    };

    // 搜索
    const handleSearch = () => {
      fetchDataContext.current = 1;
      fetchDataContext.requestParams = { ...queryParam };
    };

    // 重置
    const handleReset = () => {
      queryParam.name = undefined;
      queryParam.number = undefined;
      queryParam.status = undefined;
      queryParam.status = undefined;
      sorterInfoMap.value = undefined;
      filteredInfoMap.value = undefined;
      fetchDataContext.current = 1;
      fetchDataContext.requestParams = { ...queryParam };
    };

    const onSelectChange = (selectedRowKeys: Key[]) => {
      console.log('selectedRowKeys changed: ', selectedRowKeys);
      selectedState.selectedRowKeys = selectedRowKeys;
    };

    // 设置限额的modal要在这里定义
    const openSetLimitModal = ref<boolean>(false);
    const setLimitFormData = reactive<SetLimitParams>({
      limits: 0,
      ids: [],
    });

    // 设置单次限额的modal
    const openSetSingleLimitModal = ref<boolean>(false);
    const setSingleLimitFormData = reactive<SetSingleLimitParams>({
      limits: 0,
      ids: [],
    });

    // 设置余额的modal
    const openSetBalanceModal = ref<boolean>(false);
    const setBalanceFormData = reactive<SetBalanceParams>({
      balance: 0,
      ids: [],
    });

    // 自定义同步交易的modal
    const openCustomSyncModal = ref<boolean>(false);
    const customSyncFormRef = ref<FormInstance>();
    const customSyncFormData = reactive<{
      dateRange: [Dayjs, Dayjs] | null;
      ids: string[];
    }>({
      dateRange: null,
      ids: [],
    });

    // 禁用未来日期
    const disabledDate = (current: any) => {
      return current && current.isAfter(dayjs().endOf('day'));
    };

    // 快速选择日期范围
    const setQuickDateRange = (type: string) => {
      const today = dayjs();
      let startDate = today;
      let endDate = today;

      switch (type) {
        case 'last3days':
          startDate = today.subtract(2, 'day');
          endDate = today;
          break;
        case 'last7days':
          startDate = today.subtract(6, 'day');
          endDate = today;
          break;
        case 'thisweek':
          startDate = today.startOf('week');
          endDate = today.endOf('week');
          break;
        case 'lastweek':
          startDate = today.subtract(1, 'week').startOf('week');
          endDate = today.subtract(1, 'week').endOf('week');
          break;
        case 'thismonth':
          startDate = today.startOf('month');
          endDate = today.endOf('month');
          break;
        case 'lastmonth':
          startDate = today.subtract(1, 'month').startOf('month');
          endDate = today.subtract(1, 'month').endOf('month');
          break;
      }

      customSyncFormData.dateRange = [startDate, endDate];
    };

    const handleActionManuClick = (e: any, record: any) => {
      const key = e.key;
      if (key === 'unfreeze') {
        unfreezeCard({
          ids: [record['id']],
          sync: true,
        })
          .then(_res => {
            message.success(t('pages.virtual_cards.unfreeze.success'));
            reload();
          })
          .catch(error => {
            console.error(error);
          });
      } else if (key === 'freeze') {
        freezeCard({
          ids: [record['id']],
          sync: true,
        })
          .then(_res => {
            message.success(t('pages.virtual_cards.freeze.success'));
            reload();
          })
          .catch(error => {
            console.error(error);
          });
      } else if (key === 'set-limit') {
        console.log('set limit');
        console.log(setLimitFormData.limits);
        console.log(record);
        console.log(`current limit: ${record['limits']}`);
        setLimitFormData.limits = record['limits'];
        setLimitFormData.ids = [record['id']];
        openSetLimitModal.value = true;
      } else if (key === 'set-single-limit') {
        setSingleLimitFormData.limits = record['limits'];
        setSingleLimitFormData.ids = [record['id']];
        openSetSingleLimitModal.value = true;
      } else if (key === 'set-balance') {
        setBalanceFormData.balance = record['balance'];
        setBalanceFormData.ids = [record['id']];
        openSetBalanceModal.value = true;
      } else if (key === 'sync-3-days-transactions') {
        syncCardTransactions({
          ids: [record['id']],
        })
          .then(_res => {
            message.success(t('pages.virtual_cards.sync.success'));
            reload();
          })
          .catch(error => {
            console.error(error);
          });
      } else if (key === 'custom-sync-transactions') {
        customSyncFormData.ids = [record['id']];
        openCustomSyncModal.value = true;
      }
    };

    const handleMenuClick = (e: any) => {
      const key = e.key;
      if (key === 'sync-multiple') {
        if (selectedState.selectedRowKeys.length === 0) {
          message.warning(t('pages.virtual_cards.select.required'));
          return;
        }
        syncMultipleCard({
          ids: toRaw(state.selectedRowKeys),
          sync: true,
        })
          .then(_res => {
            message.success(t('pages.virtual_cards.sync.success'));
            reload();
          })
          .catch(error => {
            console.error(error);
          });
      } else if (key === 'sync-all') {
        syncAllCard()
          .then(_res => {
            message.success(t('pages.virtual_cards.sync.success'));
            reload();
          })
          .catch(error => {
            message.error('请求失败');
            console.error(error);
          });
      } else if (key === 'set-limits') {
        if (selectedState.selectedRowKeys.length === 0) {
          message.warning(t('pages.virtual_cards.select.required'));
          return;
        }
        // toRaw(state.selectedRowKeys.values)
        setLimitFormData.ids = Array.from(
          toRaw(selectedState.selectedRowKeys).values(),
        ) as string[];
        openSetLimitModal.value = true;
      } else if (key === 'set-single-limit') {
        if (selectedState.selectedRowKeys.length === 0) {
          message.warning(t('pages.virtual_cards.select.required'));
          return;
        }
        setSingleLimitFormData.ids = Array.from(
          toRaw(selectedState.selectedRowKeys).values(),
        ) as string[];
        openSetSingleLimitModal.value = true;
      } else if (key === 'set-balance') {
        if (selectedState.selectedRowKeys.length === 0) {
          message.warning(t('pages.virtual_cards.select.required'));
          return;
        }
        setBalanceFormData.ids = Array.from(
          toRaw(selectedState.selectedRowKeys).values(),
        ) as string[];
        openSetBalanceModal.value = true;
      } else if (key === 'sync-3-days-transactions') {
        if (selectedState.selectedRowKeys.length === 0) {
          message.warning(t('pages.virtual_cards.select.required'));
          return;
        }
        syncCardTransactions({
          ids: Array.from(toRaw(selectedState.selectedRowKeys).values()) as string[],
        })
          .then(_res => {
            message.success(t('pages.virtual_cards.sync.success'));
            reload();
          })
          .catch(error => {
            console.error(error);
          });
      } else if (key === 'custom-sync-transactions') {
        if (selectedState.selectedRowKeys.length === 0) {
          message.warning(t('pages.virtual_cards.select.required'));
          return;
        }
        customSyncFormData.ids = Array.from(
          toRaw(selectedState.selectedRowKeys).values(),
        ) as string[];
        openCustomSyncModal.value = true;
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

    // 开卡的 modal
    const createCardFormRef = ref<FormInstance>();
    const openCardModal = ref<boolean>(false);
    const createCardFormData = reactive<CreateCardAPIParams>({
      prefix: '',
      number: 1,
      balance: 100,
    });
    watch(
      openCardModal,
      () => {
        // 直接重置对象的每个属性
        createCardFormData.prefix = '';
        createCardFormData.number = 1; // 或其他初始值
        createCardFormData.balance = 100; // 或其他初始值
      },
      { flush: 'post' },
    );
    const handleCreateCard = () => {
      console.log('creat card');
      openCardModal.value = true;
      console.log(openCardModal.value);
    };
    const handleOpenCardOk = () => {
      console.log('open create card modal, click ok');
      createCardFormRef.value
        .validateFields()
        .then(values => {
          console.log('Received values of form: ', values);
          openCardModal.value = false;
          console.log(toRaw(createCardFormData));
          createCard({
            ...values,
          })
            .then(_res => {
              message.success(t('pages.virtual_cards.create.success'));
            })
            .catch(e => {
              console.log(e);
            });
        })
        .catch(info => {
          console.log(info);
        });
    };

    // 设置限额的moal
    const setLimitFormRef = ref<FormInstance>();
    const handleSetLimitOk = () => {
      setLimitFormRef.value
        .validateFields()
        .then(_res => {
          console.log(_res);
          setCardLimits({
            ...setLimitFormData,
          })
            .then(_res => {
              message.success(t('pages.virtual_cards.set.success'));
            })
            .catch(e => {
              console.log(e);
            });
          openSetLimitModal.value = false;
        })
        .catch(e => {
          console.log(e);
        });
    };
    const resetLimitForm = () => {
      console.log('reset limit form');
      setLimitFormData.limits = 0;
      setLimitFormData.ids = [];
    };

    // 设置单次限额的modal
    const setSingleLimitFormRef = ref<FormInstance>();
    const handleSetSingleLimitOk = () => {
      setSingleLimitFormRef.value
        .validateFields()
        .then(_res => {
          console.log(_res);
          setCardSingleLimit({
            ...setSingleLimitFormData,
            sync: true,
          })
            .then(_res => {
              message.success(t('pages.virtual_cards.set.success'));
              reload();
            })
            .catch(e => {
              console.log(e);
            });
          openSetSingleLimitModal.value = false;
        })
        .catch(e => {
          console.log(e);
        });
    };
    const resetSingleLimitForm = () => {
      console.log('reset single limit form');
      setSingleLimitFormData.limits = 0;
      setSingleLimitFormData.ids = [];
    };

    // 设置余额的modal
    const setBalanceFormRef = ref<FormInstance>();
    const handleSetBalanceOk = () => {
      setBalanceFormRef.value
        .validateFields()
        .then(_res => {
          console.log(_res);
          setCardBalance({
            ...setBalanceFormData,
            sync: true,
          })
            .then(_res => {
              message.success(t('pages.virtual_cards.set.success'));
              reload();
            })
            .catch(e => {
              console.log(e);
            });
          openSetBalanceModal.value = false;
        })
        .catch(e => {
          console.log(e);
        });
    };
    const resetBalanceForm = () => {
      console.log('reset balance form');
      setBalanceFormData.balance = 0;
      setBalanceFormData.ids = [];
    };

    // 自定义同步交易的modal
    const handleCustomSyncOk = () => {
      customSyncFormRef.value
        .validateFields()
        .then(_res => {
          console.log(_res);
          const startTime = customSyncFormData.dateRange?.[0]?.format('YYYY-MM-DD') || '';
          const stopTime = customSyncFormData.dateRange?.[1]?.format('YYYY-MM-DD') || '';

          syncCardTransactions({
            ids: customSyncFormData.ids,
            start_time: startTime,
            stop_time: stopTime,
          })
            .then(_res => {
              message.success(t('pages.virtual_cards.sync.success'));
              reload();
            })
            .catch(e => {
              console.log(e);
            });
          openCustomSyncModal.value = false;
        })
        .catch(e => {
          console.log(e);
        });
    };

    const resetCustomSyncForm = () => {
      console.log('reset custom sync form');
      customSyncFormData.dateRange = null;
      customSyncFormData.ids = [];
    };

    return {
      handleTableChange,
      handleSearch,
      handleReset,
      columnState,
      dynamicColumns,
      dynamicColumnItems,
      handleColumnAllClick,
      handleColumnChange,
      reset,
      move,
      hasSelected,
      onSelectChange,
      ...toRefs(selectedState),
      customCell,
      state,
      reload,

      queryParam,

      handleSyncOne,
      handleActionManuClick,
      handleMenuClick,

      copyCell,
      dayjs,

      handleCreateCard,
      openCardModal,
      handleOpenCardOk,
      createCardFormData,
      createCardFormRef,

      openSetLimitModal,
      handleSetLimitOk,
      setLimitFormData,
      setLimitFormRef,
      resetLimitForm,

      openSetSingleLimitModal,
      handleSetSingleLimitOk,
      setSingleLimitFormData,
      setSingleLimitFormRef,
      resetSingleLimitForm,

      openSetBalanceModal,
      handleSetBalanceOk,
      setBalanceFormData,
      setBalanceFormRef,
      resetBalanceForm,

      openCustomSyncModal,
      handleCustomSyncOk,
      customSyncFormData,
      customSyncFormRef,
      resetCustomSyncForm,
      disabledDate,
      setQuickDateRange,

      t,
    };
  },
  components: {
    PlusOutlined,
    Draggable,
    DragContainer,
    DragIcon,
    SettingOutlined,
    DownOutlined,
    CopyOutlined,
    ReloadOutlined,
  },
});
</script>
