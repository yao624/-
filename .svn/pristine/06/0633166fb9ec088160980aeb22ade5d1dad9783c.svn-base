<template>
  <page-container :showPageHeader="false">
    <div class="ant-pro-table">
      <div class="ant-pro-table-search">
        <a-form layout="horizontal" :label-col="{ span: 6 }">
          <a-row :gutter="16" type="flex" justify="start">
            <a-col :xs="24" :sm="24" :md="12" :lg="7">
              <a-form-item label="卡号">
                <a-input v-model:value="queryParam.card_number" placeholder="请输入" />
              </a-form-item>
            </a-col>
            <a-col :xs="24" :sm="24" :md="12" :lg="7">
              <a-form-item label="卡片名称">
                <a-input v-model:value="queryParam.card_name" placeholder="请输入" />
              </a-form-item>
            </a-col>
            <a-col :xs="24" :sm="24" :md="12" :lg="7">
              <a-form-item label="交易状态">
                <a-select
                  v-model:value="queryParam.status"
                  placeholder="请选择状态"
                  mode="multiple"
                >
                  <a-select-option value="PENDING">PENDING</a-select-option>
                  <a-select-option value="APPROVED">APPROVED</a-select-option>
                  <a-select-option value="FAILED">FAILED</a-select-option>
                </a-select>
              </a-form-item>
            </a-col>
            <a-col :xs="24" :sm="24" :md="12" :lg="7">
              <a-form-item label="交易类型">
                <a-select
                  v-model:value="queryParam.transaction_type"
                  placeholder="请选择交易类型"
                  mode="multiple"
                >
                  <a-select-option value="AUTHORIZATION">AUTHORIZATION</a-select-option>
                  <a-select-option value="CLEARING">CLEARING</a-select-option>
                </a-select>
              </a-form-item>
            </a-col>
            <a-col :xs="24" :sm="24" :md="12" :lg="7">
              <a-form-item label="交易时间">
                <a-range-picker
                  v-model:value="date_range"
                  :placeholder="['开始时间', '结束时间']"
                  :style="{ width: '100%' }"
                  :presets="rangePresets"
                />
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
            <div class="ant-pro-table-list-toolbar-left"></div>
            <div class="ant-pro-table-list-toolbar-right">
                            <div class="ant-pro-table-list-toolbar-setting-item">
                <a-tooltip title="刷新">
                  <a-button
                    type="text"
                    @click="handleRefresh"
                    :loading="state.loading"
                  >
                    <template #icon>
                      <reload-outlined />
                    </template>
                  </a-button>
                </a-tooltip>
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
            showSizeChanger: true,
            showQuickJumper: true,
            pageSizeOptions: ['10', '20', '50', '100', '200', '500', '1000'],
          }"
          bordered
          sticky
          @change="handleTableChange"
          :row-selection="{ selectedRowKeys: selectedRowKeys, onChange: onSelectChange }"
        >
          <template #bodyCell="{ column, text }">
            <template v-if="column['dataIndex'] === 'operation'">
              <a>Action</a>
            </template>
            <template v-if="['transaction_date'].includes(`${column['dataIndex']}`)">
              <span>{{ dayjs(text).format('YYYY-MM-DD HH:mm:ss') }}</span>
            </template>
          </template>
          <template #summary>
            <a-table-summary-row>
              <a-table-summary-cell :index="0" />
              <a-table-summary-cell :index="1">Total</a-table-summary-cell>
              <a-table-summary-cell :index="2">
                <span style="color: #ff4d4f">{{ transactionAmountTotal }}</span>
              </a-table-summary-cell>
            </a-table-summary-row>
          </template>
        </a-table>
      </a-card>
    </div>
  </page-container>
</template>
<script lang="ts">
import { SettingOutlined, ReloadOutlined } from '@ant-design/icons-vue';
import { defineComponent, ref, computed, watchEffect, reactive, toRefs, watch, h } from 'vue';

import { queryCardTransactionListAPI } from '@/api/virtual_cards';
import { Container as DragContainer, Draggable } from '@/components/draggable';
import DragIcon from '@/components/table/drag-icon.vue';
import { useTableDynamicColumns } from '@/utils/hooks/useTableColumn';
import type { Dayjs } from 'dayjs';
import dayjs from 'dayjs';
import { useFetchData } from '@/utils/hooks/useFetchData';
import type { Pagination } from '@/typing';

type APIParams = {
  pageSize?: any;
  pageNo?: any;
  sortField?: string;
  sortOrder?: number;
  [key: string]: any;
};

type Key = string | number;

type RangeValue = [Dayjs, Dayjs];

export default defineComponent({
  setup() {
    const queryCardTransactionList = (params: APIParams) => {
      return queryCardTransactionListAPI(params);
    };

    const filteredInfoMap = ref();
    const sorterInfoMap = ref();
    const mergedColumns = ref<any[]>([]);
    const queryParam = reactive({
      card_number: undefined,
      card_name: undefined,
      status: undefined,
      transaction_type: undefined,
      transaction_date_start: undefined,
      transaction_date_stop: undefined,
      sortOrder: undefined,
      sortField: undefined,
    });
    const fetchDataContext = reactive({
      current: 1,
      pageSize: 10,
      requestParams: { ...queryParam },
    });

    watch(
      filteredInfoMap,
      () => {
        console.log('update filter info map: ', filteredInfoMap.value);
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
      const sorted = sorterInfoMap.value || {};
      mergedColumns.value = [
        {
          title: '序号',
          dataIndex: 'index',
          customRender: ({ index }: { index: number }) => {
            return `${index + 1}`;
          },
          width: 80,
          align: 'center',
        },
        {
          title: 'Datetime',
          dataIndex: 'transaction_date',
          sorter: true,
          sortOrder: sorted.columnKey === 'transaction_date' && sorted.order,
          minWidth: 140,
        },
        {
          title: 'Amount',
          dataIndex: 'transaction_amount',
          sorter: true,
          sortOrder: sorted.columnKey === 'transaction_amount' && sorted.order,
          minWidth: 90,
        },
        {
          title: 'Type',
          dataIndex: 'transaction_type',
          sorter: true,
          sortOrder: sorted.columnKey === 'transaction_type' && sorted.order,
          minWidth: 120,
        },
        {
          title: 'Status',
          dataIndex: 'status',
          sorter: true,
          sortOrder: sorted.columnKey === 'status' && sorted.order,
          minWidth: 80,
        },
        {
          title: 'Currency',
          dataIndex: ['card', 'currency'],
          minWidth: 80,
        },
        {
          title: 'Merchant',
          dataIndex: 'merchant_name',
          ellipsis: false,
          minWidth: 190,
        },
        {
          title: 'Card',
          dataIndex: ['card', 'name'],
          minWidth: 160,
          customRender: ({ record }: { record: any }) => {
            const cardName = record.card?.name || '';
            const cardNumber = record.card?.number || '';
            return h('div', [
              h('div', cardName),
              h('div', { style: { color: '#666', fontSize: '12px' } }, cardNumber),
            ]);
          },
        },
      ];
    });

    const selectedState = reactive<{
      selectedRowKeys: Key[];
    }>({
      selectedRowKeys: [],
    });
    const hasSelected = computed(() => selectedState.selectedRowKeys.length > 0);

    //时间搜索
    const rangePresets = ref([
      { label: 'Last 7 Days', value: [dayjs().add(-7, 'd'), dayjs()] },
      { label: 'Last 14 Days', value: [dayjs().add(-14, 'd'), dayjs()] },
      { label: 'Last 30 Days', value: [dayjs().add(-30, 'd'), dayjs()] },
      { label: 'Last 90 Days', value: [dayjs().add(-90, 'd'), dayjs()] },
    ]);
    const date_range = ref<RangeValue>();
    watch(
      () => date_range.value,
      newValue => {
        console.log('changed');
        if (newValue && newValue.length === 2) {
          queryParam.transaction_date_start = dayjs(newValue[0]).format('YYYY-MM-DD');
          // queryParam.transaction_date_start = newValue[0];
          queryParam.transaction_date_stop = dayjs(newValue[1]).format('YYYY-MM-DD');
        }
      },
      { deep: true },
    );

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

    const { context: state, reload } = useFetchData(queryCardTransactionList as any, fetchDataContext);

    const transactionAmountTotal = computed(() => {
      const rows = state.dataSource;
      if (!Array.isArray(rows)) return 0;
      return rows.reduce((sum: number, r: any) => sum + Number(r?.transaction_amount ?? 0), 0);
    });

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
      queryParam.card_name = undefined;
      queryParam.card_number = undefined;
      queryParam.status = undefined;
      queryParam.transaction_type = undefined;
      queryParam.transaction_date_start = undefined;
      queryParam.transaction_date_stop = undefined;
      date_range.value = undefined;
      sorterInfoMap.value = undefined;
      filteredInfoMap.value = undefined;
      fetchDataContext.current = 1;
      fetchDataContext.requestParams = { ...queryParam };
    };

    const onSelectChange = (selectedRowKeys: Key[]) => {
      selectedState.selectedRowKeys = selectedRowKeys;
    };

    // 刷新表格数据
    const handleRefresh = () => {
      reload();
    };

    return {
      handleTableChange,

      handleSearch,
      handleReset,
      handleRefresh,

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
      state,

      queryParam,

      date_range,
      rangePresets,

      dayjs,
      transactionAmountTotal,
    };
  },
  components: {
    Draggable,
    DragContainer,
    DragIcon,
    SettingOutlined,
    ReloadOutlined,
  },
});
</script>
