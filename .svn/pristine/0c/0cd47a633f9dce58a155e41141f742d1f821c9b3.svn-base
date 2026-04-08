<template>
  <page-container>
    <div class="ant-pro-table">
      <div class="ant-pro-table-search">
        <a-form layout="horizontal" :label-col="{ span: 4 }">
          <a-row :gutter="16" type="flex" justify="start">
            <a-col :xs="24" :sm="24" :md="12" :lg="7">
              <a-form-item label="名称">
                <a-input v-model:value="queryParam.name" placeholder="请输入名称" />
              </a-form-item>
            </a-col>
            <a-col :xs="24" :sm="24" :md="12" :lg="7">
              <a-form-item label="状态" name="active">
                <a-radio-group v-model:value="queryParam.active">
                  <a-radio value="">全部</a-radio>
                  <a-radio value="true">启用</a-radio>
                  <a-radio value="false">禁用</a-radio>
                </a-radio-group>
              </a-form-item>
            </a-col>
            <a-col :xs="24" :sm="24" :md="12" :lg="7">
              <a-form-item label="类型">
                <a-select
                  v-model:value="queryParam.object_type"
                  mode="multiple"
                  placeholder="请选择类型"
                >
                  <a-select-option value="Campaign Tag">Campaign Tag</a-select-option>
                  <a-select-option value="Adset Tag">Adset Tag</a-select-option>
                  <a-select-option value="Campaign ID">Campaign ID</a-select-option>
                  <a-select-option value="Adset ID">Adset ID</a-select-option>
                  <a-select-option value="Ad ID">Ad ID</a-select-option>
                </a-select>
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
              <a-space align="center">
                <a-button type="primary" @click="showModal">
                  <plus-outlined />
                  创建任务
                </a-button>
              </a-space>
            </div>
            <div class="ant-pro-table-list-toolbar-right">
              <div class="ant-pro-table-list-toolbar-setting-item">
                <a-tooltip title="刷新">
                  <reload-outlined @click="reload" />
                </a-tooltip>
              </div>
              <div class="ant-pro-table-list-toolbar-divider">
                <a-divider type="vertical" />
              </div>
              <div class="ant-pro-table-list-toolbar-setting-item">
                <a-dropdown>
                  <a-button type="primary">
                    操作
                    <down-outlined />
                  </a-button>
                  <template #overlay>
                    <a-menu @click="handleMenuClick" :items="actionItems"></a-menu>
                  </template>
                </a-dropdown>
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
                        <template :key="column.key" v-for="column in dynamicColumnItems">
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
          row-key="id"
          :size="state.tableSize"
          :loading="state.loading"
          :columns="dynamicColumns"
          :data-source="state.dataSource"
          bordered
          sticky
@resizeColumn="handleResizeColumn"
          :row-selection="rowSelection"
          :pagination="{
            current: state.current,
            pageSize: state.pageSize,
            total: state.total,
            showTotal: total => `Total ${total} items`,
            pageSizeOptions: ['10', '20', '50', '100', '200', '500', '1000'],
            showSizeChanger: true,
            // showQuickJumper: true,
          }"
          @change="handleTableChange"
        >
          <template #bodyCell="{ text, record, column }">
            <template v-if="column.dataIndex === 'action'">
              <a :title="text" @click="() => handleEditJob(record)">编辑</a>
              <a-divider type="vertical" />
              <a :title="text" @click="() => handleToggleJobStatus(record)" v-if="!record.active">
                启用
              </a>
              <a :title="text" @click="() => handleToggleJobStatus(record)" v-if="record.active">
                禁用
              </a>
              <a-divider type="vertical" />
              <a-popconfirm
                :title="t('pages.doubleConfirmDel')"
                :ok-text="t('pages.confirm')"
                :cancel-text="t('pages.cancel')"
                @confirm="handleDeleteJob(record.id)"
              >
                <a>删除</a>
              </a-popconfirm>
            </template>
            <template v-if="column['dataIndex'] == 'active'">
              <a-badge :color="text ? 'green' : 'gray'" :text="text ? 'Active' : 'Inactive'" />
            </template>
            <template v-if="column['dataIndex'] == 'object_value'">
              <a-tag v-for="(item, index) in record.object_value" :key="index">{{ item }}</a-tag>
            </template>
          </template>
        </a-table>
      </a-card>
    </div>
    <job-modal
      :model="jobModal.model"
      :visible="jobModal.visible"
      @cancel="
        () => {
          jobModal.visible = false;
        }
      "
      @ok="
        () => {
          jobModal.visible = false;
          reload();
        }
      "
    />
  </page-container>
</template>

<script lang="ts">
import { addJobApi, batchDeleteJobApi, deleteOneJobApi, getJobsApi } from '@/api/rule';
import { useFetchData } from '@/utils/hooks/useFetchData';
import { useTableDynamicColumns } from '@/utils/hooks/useTableColumn';
import { DownOutlined, PlusOutlined, ReloadOutlined, SettingOutlined } from '@ant-design/icons-vue';
import { message } from 'ant-design-vue';
import { defineComponent, reactive, ref, watch, watchEffect } from 'vue';
import { Container as DragContainer, Draggable } from '@/components/draggable';
import DragIcon from '@/components/table/drag-icon.vue';
import type { Pagination } from '@/typing';

import JobModal from './job-modal.vue';
import { useI18n } from 'vue-i18n';
import dayjs from 'dayjs';

export default defineComponent({
  setup() {
    const { t } = useI18n();
    const sorterInfoMap = ref();
    const baseColumns = ref<any[]>([]);

    const queryParam = reactive({
      name: undefined,
      active: '',
      object_type: [],
      sortOrder: undefined,
      sortField: undefined,
    });
    const showModal = () => {
      console.log('show modal');
      jobModal.model = null;
      jobModal.visible = true;
    };

    const fetchDataContext = reactive({
      current: 1,
      pageSize: 10,
      tableSize: 'middle',
      stripe: false,
      requestParams: { ...queryParam },
    });

    const onRequestError = e => {
      console.error('请求错误: ', e);
      message.error('请求出错');
    };
    const { reload, context: state } = useFetchData(getJobsApi, fetchDataContext, {
      onRequestError: onRequestError,
    });

    const handleSearch = () => {
      fetchDataContext.requestParams = { ...queryParam };
    };
    const handleReset = () => {
      queryParam.name = undefined;
      queryParam.active = '';
      queryParam.sortOrder = undefined;
      queryParam.sortField = undefined;
      queryParam.object_type = [];
      fetchDataContext.requestParams = { ...queryParam };
    };

    watchEffect(() => {
      const sorted = sorterInfoMap.value || {};
      baseColumns.value = [
        {
          title: '名称',
          dataIndex: 'name',
          sorter: true,
          sortOrder: sorted.columnKey === 'name' && sorted.order,
        },
        {
          title: '状态',
          dataIndex: 'active',
          sorter: true,
          sortOrder: sorted.columnKey === 'active' && sorted.order,
        },
        {
          title: '时区',
          dataIndex: 'timezone',
        },
        {
          title: '类型',
          dataIndex: 'object_type',
          sorter: true,
          sortOrder: sorted.columnKey === 'object_type' && sorted.order,
        },
        {
          title: '对象',
          dataIndex: 'object_value',
          resizable: true,
          width: 100,
        },
        {
          title: '开启时间',
          dataIndex: 'start_time',
        },
        {
          title: '关闭时间',
          dataIndex: 'stop_time',
        },
        {
          title: '备注',
          dataIndex: 'notes',
        },
        {
          title: '操作',
          dataIndex: 'action',
          width: 150,
          minWidth: 150,
          fixed: 'right',
        },
      ];
    });

    const {
      state: columnState,
      dynamicColumns,
      dynamicColumnItems,
      handleColumnAllClick,
      handleColumnChange,
      reset,
      move,
    } = useTableDynamicColumns(baseColumns as any, { needRowIndex: true });

    const handleResizeColumn = (w, col) => {
      col.width = w;
    };

    const handleTableChange = ({ current, pageSize }: Pagination, _filters: any, sorter: any) => {
      sorterInfoMap.value = sorter;
      fetchDataContext.current = current;
      fetchDataContext.pageSize = pageSize;
    };

    watch(
      sorterInfoMap,
      () => {
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

    const jobModal = reactive({
      visible: false,
      model: null,
    });

    const handleEditJob = record => {
      jobModal.visible = true;
      jobModal.model = { ...record };
      jobModal.model.dateRange = [];
      jobModal.model.dateRange[0] = record.start_time ? dayjs(record.start_time, 'HH:mm') : null;
      jobModal.model.dateRange[1] = record.stop_time ? dayjs(record.stop_time, 'HH:mm') : null;
    };

    const handleToggleJobStatus = record => {
      console.log(record);
      record.active = !record.active;
      addJobApi(record)
        .then(() => {
          message.info('更新成功');
          reload();
        })
        .catch(e => {
          console.log(e);
          message.warning('更新失败');
        });
    };

    const handleDeleteJob = id => {
      deleteOneJobApi(id)
        .then(() => {
          message.info('删除成功');
          reload();
        })
        .catch(e => {
          console.log(e);
          message.warning('删除失败');
        });
    };

    let selectedJobIds = [];

    const rowSelection = {
      onChange: (selectedRowKeys, selectedRows) => {
        console.log(`selectedRowKeys: ${selectedRowKeys}`, 'selectedRows: ', selectedRows);
        selectedJobIds = selectedRowKeys;
      },
      getCheckboxProps: record => ({
        disabled: record.name === 'Disabled User',
        // Column configuration not to be checked
        name: record.name,
      }),
    };

    const actionItems = ref([
      {
        key: 'batch-delete',
        label: '批量删除',
        title: '批量删除',
      },
    ]);

    const handleMenuClick = (e: any) => {
      const key = e.key;
      if (key === 'batch-delete') {
        console.log('batch-delete');
        batchDeleteJobApi({
          ids: selectedJobIds,
        })
          .then(() => {
            message.info('删除成功');
            reload();
          })
          .catch(e => {
            console.log(e);
            message.warning('删除失败');
          });
      }
    };

    return {
      t,
      queryParam,
      handleSearch,
      handleReset,
      showModal,

      reload,
      state,

      // menu
      handleMenuClick,
      actionItems,

      // table
      columnState,
      dynamicColumns,
      dynamicColumnItems,
      handleColumnAllClick,
      handleColumnChange,
      reset,
      move,
      handleResizeColumn,
      rowSelection,
      handleTableChange,

      // job modal
      jobModal,
      handleEditJob,
      handleToggleJobStatus,
      handleDeleteJob,
    };
  },
  components: {
    PlusOutlined,
    ReloadOutlined,
    SettingOutlined,
    DownOutlined,
    DragContainer,
    Draggable,
    DragIcon,
    JobModal,
  },
});
</script>

<!-- <style scoped>
.auto-width-column {
  width: auto !important;
}

.ant-form-item {
  margin-bottom: 16px;
}
</style> -->
