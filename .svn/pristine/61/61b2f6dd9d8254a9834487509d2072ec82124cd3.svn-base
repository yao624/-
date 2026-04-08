<template>
  <page-container :showPageHeader="false">
    <div class="ant-pro-table">
      <div class="ant-pro-table-search">
        <a-form layout="horizontal" :label-col="{ span: 6 }">
          <a-row :gutter="16" type="flex" justify="start">
            <a-col :xs="24" :sm="24" :md="12" :lg="7">
              <a-form-item label="Datetime">
                <a-range-picker
                  v-model:value="date_range"
                  :placeholder="['Start', 'End']"
                  :style="{ width: '100%' }"
                  :presets="rangePresets"
                  show-time
                />
              </a-form-item>
            </a-col>
            <a-col :xs="24" :sm="24" :md="12" :lg="7">
              <a-form-item label="Network">
                <a-select
                  v-model:value="queryParam.network_ids"
                  placeholder="请选择 Network"
                  max-tag-count="responsive"
                  mode="multiple"
                  :options="networks"
                  :filter-option="(input, option) => {
                    return option.label.toLowerCase().indexOf(input.toLowerCase()) >= 0;
                  }"
                ></a-select>
              </a-form-item>
            </a-col>
            <a-col :xs="24" :sm="24" :md="12" :lg="7">
              <a-form-item label="Offer">
                <a-select
                  v-model:value="queryParam.offer_names"
                  placeholder="请输入Offer名字"
                  max-tag-count="responsive"
                  mode="tags"
                ></a-select>
              </a-form-item>
            </a-col>
            <a-col :xs="24" :sm="24" :md="12" :lg="7">
              <a-form-item label="Campaign ID">
                <a-select
                  v-model:value="queryParam.campaign_ids"
                  placeholder="请输入 Campaign ID"
                  max-tag-count="responsive"
                  mode="tags"
                ></a-select>
              </a-form-item>
            </a-col>
            <a-col :xs="24" :sm="24" :md="12" :lg="7">
              <a-form-item label="Adset ID">
                <a-select
                  v-model:value="queryParam.adset_ids"
                  placeholder="请输入 Adset ID"
                  max-tag-count="responsive"
                  mode="tags"
                ></a-select>
              </a-form-item>
            </a-col>
            <a-col :xs="24" :sm="24" :md="12" :lg="7">
              <a-form-item label="Ad ID">
                <a-select
                  v-model:value="queryParam.ad_ids"
                  placeholder="请输入 Ad ID"
                  max-tag-count="responsive"
                  mode="tags"
                ></a-select>
              </a-form-item>
            </a-col>
            <a-col :xs="24" :sm="24" :md="12" :lg="7">
              <a-form-item label="GEO">
                <a-select
                  v-model:value="queryParam.geos"
                  placeholder="请输入国家代码"
                  max-tag-count="responsive"
                  mode="tags"
                ></a-select>
              </a-form-item>
            </a-col>

            <a-col :xs="24" :sm="24" :md="12" :lg="7">
              <a-form-item label="Sub2">
                <a-input
                  v-model:value="queryParam.sub_2"
                  placeholder="Sub2"
                ></a-input>
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
              <a-space>
                <a-button type="primary" size="small">
                  <plus-outlined />
                  同步联盟数据
                </a-button>
              </a-space>
              <div class="ant-pro-table-list-toolbar-divider">
                <a-divider type="vertical" />
              </div>
              <div class="ant-pro-table-list-toolbar-setting-item">
                <a-tooltip title="刷新">
                  <reload-outlined @click="reload" />
                </a-tooltip>
              </div>
              <div class="ant-pro-table-list-toolbar-divider">
                <a-divider type="vertical" />
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
          :columns="dynamicColumns"
          :data-source="state.dataSource"
          :row-key="record => record.id"
          :loading="state.loading"
          :pagination="{
            current: state.current,
            pageSize: state.pageSize,
            total: state.total,
            showSizeChanger: true,
            showTotal: total => `Total ${total} items`,
          }"
          bordered
          sticky
          @change="handleTableChange"
:animate-rows="false"
        >
          <template #bodyCell="{ column, text }">
            <template
              v-if="
                ['created_at', 'updated_at', 'click_datetime'].includes(`${column['dataIndex']}`)
              "
            >
              <span>{{ dayjs(text).format('YYYY-MM-DD HH:mm:ss') }}</span>
            </template>
          </template>
        </a-table>
      </a-card>
    </div>
  </page-container>
</template>
<script lang="ts">
import { PlusOutlined, SettingOutlined, ReloadOutlined } from '@ant-design/icons-vue';
import { defineComponent, ref, reactive, watch, watchEffect } from 'vue';

import { queryClicks as queryClicksAPI } from '@/api/clicks';

import { Container as DragContainer, Draggable } from '@/components/draggable';
import DragIcon from '@/components/table/drag-icon.vue';
import { useTableDynamicColumns } from '@/utils/hooks/useTableColumn';
import dayjs from 'dayjs';
import { queryNetworksApi } from '@/api/networks';
import type { Dayjs } from 'dayjs';
import { useFetchData } from '@/utils/hooks/useFetchData';
import type { Pagination } from '@/typing';

type APIParams = {
  pageSize?: any;
  pageNo?: any;
  sortField?: string;
  sortOrder?: number;
  [key: string]: any;
};

type RangeValue = [Dayjs, Dayjs];

export default defineComponent({
  setup() {
    const queryClicks = (params: APIParams) => {
      return queryClicksAPI(params);
    };

    const queryParam = reactive({
      click_date_start: undefined,
      click_date_stop: undefined,
      offer_names: undefined,
      network_ids: undefined,
      campaign_ids: undefined,
      adset_ids: undefined,
      ad_ids: undefined,
      sortOrder: undefined,
      sortField: undefined,
      geos: undefined,
      sub_2: undefined,
    });
    const fetchDataContext = reactive({
      current: 1,
      pageSize: 10,
      requestParams: { ...queryParam },
    });
    const sorterInfoMap = ref();
    const mergedColumns = ref<any[]>([]);

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
          customRender: ({ index }) => {
            return `${index + 1}`;
          },
          width: 80,
          align: 'center',
        },
        {
          title: 'Datetime',
          dataIndex: 'click_datetime',
          sorter: true,
          sortOrder: sorted.columnKey === 'click_datetime' && sorted.order,
          align: 'center',
          minWidth: 160,
          ellipsis: false,
        },
        {
          title: 'Offer',
          dataIndex: 'offer_source_name',
          minWidth: 160,
          ellipsis: true,
          resizable: true,
          sorter: true,
          sortOrder: sorted.columnKey === 'offer_source_name' && sorted.order,
        },
        {
          title: 'GEO',
          dataIndex: 'country_code',
          minWidth: 70,
          ellipsis: true,
        },
        {
          title: 'Network',
          dataIndex: 'network_name',
          minWidth: 160,
          ellipsis: true,
        },
        {
          title: 'Campaign ID',
          dataIndex: 'fb_campaign_source_id',
          minWidth: 170,
          sorter: true,
          sortOrder: sorted.columnKey === 'fb_campaign_source_id' && sorted.order,
          resizable: true,
        },
        {
          title: 'Adset ID',
          dataIndex: 'fb_adset_source_id',
          minWidth: 170,
          sorter: true,
          sortOrder: sorted.columnKey === '1fb_adset_source_id70' && sorted.order,
          resizable: true,
        },
        {
          title: 'Ad ID',
          dataIndex: 'fb_ad_source_id',
          minWidth: 170,
          sorter: true,
          sortOrder: sorted.columnKey === 'fb_ad_source_id' && sorted.order,
          resizable: true,
        },
        {
          title: 'Pixel',
          dataIndex: 'fb_pixel_number',
          minWidth: 150,
          sorter: true,
          sortOrder: sorted.columnKey === 'fb_pixel_number' && sorted.order,
          resizable: true,
        },
        {
          title: 'sub_1',
          dataIndex: 'sub_1',
          minWidth: 150,
          sorter: true,
          sortOrder: sorted.columnKey === 'sub_1' && sorted.order,
          resizable: true,
          ellipsis: true,
        },
        {
          title: 'sub_2',
          dataIndex: 'sub_2',
          minWidth: 150,
          sorter: true,
          sortOrder: sorted.columnKey === 'sub_2' && sorted.order,
          resizable: true,
        },
        {
          title: 'sub_3',
          dataIndex: 'sub_3',
          sorter: true,
          sortOrder: sorted.columnKey === 'sub_3' && sorted.order,
          minWidth: 80,
          resizable: true,
        },
        {
          title: 'sub_4',
          dataIndex: 'sub_4',
          sorter: true,
          sortOrder: sorted.columnKey === 'sub_4' && sorted.order,
          minWidth: 80,
          ellipsis: true,
          resizable: true,
        },
        {
          title: 'sub_5',
          dataIndex: 'sub_5',
          minWidth: 80,
          ellipsis: true,
          sorter: true,
          sortOrder: sorted.columnKey === 'sub_5' && sorted.order,
          resizable: true,
        },
        {
          title: 'Updated Date',
          dataIndex: 'updated_at',
          minWidth: 160,
          sorter: true,
          sortOrder: sorted.columnKey === 'updated_at' && sorted.order,
        },
      ];
    });

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

    const { context: state, reload } = useFetchData(queryClicks, fetchDataContext);

    const handleTableChange = ({ current, pageSize }: Pagination, _filters: any, sorter: any) => {
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
      queryParam.click_date_start = undefined;
      queryParam.click_date_stop = undefined;
      queryParam.offer_names = undefined;
      queryParam.network_ids = undefined;
      queryParam.campaign_ids = undefined;
      queryParam.adset_ids = undefined;
      queryParam.ad_ids = undefined;
      queryParam.sortOrder = undefined;
      queryParam.sortField = undefined;
      queryParam.geos = undefined;
      queryParam.sub_2 = undefined;
      sorterInfoMap.value = undefined;
      date_range.value = undefined;
      fetchDataContext.current = 1;
      fetchDataContext.requestParams = { ...queryParam };
    };

    const networks = ref<any>([]);
    queryNetworksApi({
      pageNo: 1,
      pageSize: 9999,
    })
      .then(res => {
        res.data.forEach((network: any) => {
          networks.value.push({
            value: network['id'],
            label: network['name'],
          });
        });
      })
      .catch(e => {
        console.log(e);
      });

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
          queryParam.click_date_start = dayjs(newValue[0]).unix();
          queryParam.click_date_stop = dayjs(newValue[1]).unix();
        }
      },
      { deep: true },
    );

    return {
      state,
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
      reload,

      queryParam,

      dayjs,

      networks,

      date_range,
      rangePresets,
    };
  },
  components: {
    PlusOutlined,
    Draggable,
    DragContainer,
    DragIcon,
    SettingOutlined,
    ReloadOutlined,
  },
});
</script>
