<template>
  <page-container :showPageHeader="false">
    <div class="ant-pro-table">
      <div class="ant-pro-table-search">
        <a-form layout="horizontal" :label-col="{ span: 4 }">
          <a-row :gutter="16" type="flex" justify="start">
            <a-col :xs="24" :sm="24" :md="12" :lg="7">
              <a-form-item label="名称">
                <a-input v-model:value="queryParam.name" placeholder="请输入" />
              </a-form-item>
            </a-col>
            <a-col :xs="24" :sm="24" :md="12" :lg="7">
              <a-form-item label="状态">
                <a-select v-model:value="queryParam.is_active" placeholder="请选择状态">
                  <a-select-option value="">全部</a-select-option>
                  <a-select-option value="true">启用</a-select-option>
                  <a-select-option value="false">禁用</a-select-option>
                </a-select>
              </a-form-item>
            </a-col>
            <a-col :xs="24" :sm="24" :md="12" :lg="7">
              <a-form-item label="标签">
                <a-select v-model:value="queryParam.tags" placeholder="请选择标签" mode="multiple">
                  <a-select-option value="">全部</a-select-option>
                  <a-select-option value="true">启用</a-select-option>
                  <a-select-option value="false">禁用</a-select-option>
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
              <div class="ant-pro-table-list-toolbar-title">
                <a-space>
                  <a-button
                    type="primary"
                    @click="
                      () => {
                        openRuleModal = true;
                        dialogTitle = '创建规则';
                      }
                    "
                  >
                    <plus-outlined />
                    创建规则
                  </a-button>
                  <div class="ant-pro-table-list-toolbar-divider">
                    <a-divider type="vertical" />
                  </div>
                  <a-button type="primary" @click="handleTriggerAutomationPipeline">
                    <sync-outlined />
                    同步数据并检查规则
                  </a-button>
                  <div class="ant-pro-table-list-toolbar-divider">
                    <a-divider type="vertical" />
                  </div>
                  <a-button type="primary" @click="handleSyncKeitaroToKv">
                    <sync-outlined />
                    同步 Keitaro 到 Kv
                  </a-button>
                </a-space>
              </div>
              <div class="ant-pro-table-list-toolbar-setting-item">
                <span style="margin-left: 8px">
                  <template v-if="hasSelected">
                    {{ `Selected ${selectedRowKeys.length} items` }}
                  </template>
                </span>
              </div>
            </div>
            <div class="ant-pro-table-list-toolbar-right">
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
                      <a-menu-item key="batch-enable">启用</a-menu-item>
                      <a-menu-item key="batch-disable">禁用</a-menu-item>
                      <a-menu-item key="batch-delete">删除</a-menu-item>
                    </a-menu>
                  </template>
                  <a-button type="primary">
                    操作
                    <down-outlined />
                  </a-button>
                </a-dropdown>
              </div>
              <div class="ant-pro-table-list-toolbar-divider">
                <a-divider type="vertical" />
              </div>
              <div class="ant-pro-table-list-toolbar-setting-item">
                <a-tooltip title="刷新">
                  <reload-outlined @click="reload" />
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
          }"
          bordered
          sticky
          @change="handleTableChange"
:scroll="{ y: tableHeight }"
          :row-selection="{ selectedRowKeys: selectedRowKeys, onChange: onSelectChange }"
        >
          <template #bodyCell="{ column, text, record }">
            <template v-if="column['dataIndex'] == 'is_active'">
              <a-badge :color="text ? 'green' : 'gray'" :text="text ? 'Active' : 'Inactive'" />
            </template>
            <template v-if="column['dataIndex'] == 'conditions'">
              <a-space direction="vertical">
                <a-tag v-for="(condition, index) in text" :key="index">
                  {{ `${condition.metric} ${condition.operator} ${condition.value}` }}
                </a-tag>
              </a-space>
            </template>
            <template v-if="column['dataIndex'] == 'actions'">
              <a-space direction="vertical">
                <a-tag v-for="(action, index) in text" :key="index">
                  {{ action.name }} {{ action.value }}
                </a-tag>
              </a-space>
            </template>
            <template v-if="column['dataIndex'] == 'resource_ids'">
              <a-space direction="vertical">
                <a-tag v-for="(res, index) in text" :key="index">
                  {{ res }}
                </a-tag>
              </a-space>
            </template>
            <template v-if="column['dataIndex'] == 'white_list'">
              <a-space direction="vertical">
                <a-tag v-for="(res, index) in text" :key="index">
                  {{ res }}
                </a-tag>
              </a-space>
            </template>
            <template v-if="column['dataIndex'] == 'fb_ad_accounts'">
              <a-space direction="vertical">
                <div v-for="(ad_account, index) in text" :key="index">
                  <a-tag>
                    {{ `${ad_account.source_id} - ${ad_account.name}` }}
                  </a-tag>
                  <a-badge
                    :color="
                      ad_account.account_status === 'ACTIVE'
                        ? 'green'
                        : ad_account.account_status === 'DISABLED'
                        ? 'red'
                        : 'gray'
                    "
                    :text="ad_account.account_status"
                  />
                </div>
              </a-space>
            </template>
            <template v-if="column['dataIndex'] == 'operation'">
              <a-space>
                <a href="#" @click.prevent="handleEdit(record)">编辑</a>
                <a href="#" @click.prevent="handleEnable(record)" v-if="!record['is_active']">
                  启用
                </a>
                <a href="#" @click.prevent="handleDisable(record)" v-if="record['is_active']">
                  禁用
                </a>
                <a-popconfirm
                  title="确认删除此条数据"
                  ok-text="确定"
                  cancel-text="取消"
                  @confirm="handleDelete(record)"
                >
                  <a href="#">删除</a>
                </a-popconfirm>
              </a-space>
            </template>
          </template>
        </a-table>
      </a-card>
    </div>
    <div>
      <a-modal
        v-model:open="openRuleModal"
        :title="dialogTitle"
        @ok="handleOk"
        @cancel="handleCancel"
        :width="800"
      >
        <a-form
          ref="formRef"
          :label-col="{ span: 4 }"
          name="dynamic_form_nest_item"
          :model="dynamicValidateForm"
        >
          <a-form-item
            name="name"
            label="名称"
            :rules="[{ required: true, message: 'Missing Name' }]"
          >
            <a-input v-model:value="dynamicValidateForm.name" />
          </a-form-item>
          <a-form-item name="ad_account_ids" label="广告账户">
            <a-input-group compact>
              <a-select
                v-model:value="dynamicValidateForm.ad_account_ids"
                :options="adAccountOptions"
                mode="multiple"
                optionFilterProp="name"
                style="width: calc(100% - 50px)"
                max-tag-count="responsive"
              >
                <template #option="{ label, status }">
                  {{ label }}
                  <a-badge
                    :color="status === 'ACTIVE' ? 'green' : status === 'DISABLED' ? 'red' : 'gray'"
                  />
                </template>
              </a-select>
              <a-button @click="openAdAccountModal = true"><ellipsis-outlined /></a-button>
            </a-input-group>
          </a-form-item>
          <a-form-item
            name="date_preset"
            label="时间范围"
            :rules="[{ required: true, message: 'Missing Date Preset' }]"
          >
            <a-radio-group v-model:value="dynamicValidateForm.date_preset">
              <a-radio value="lifetime" disabled>Lifetime</a-radio>
              <a-radio value="today">Today</a-radio>
              <a-radio value="last_2_days" disabled>Last 2 Days</a-radio>
              <a-radio value="last_3_days" disabled>Last 3 Days</a-radio>
            </a-radio-group>
          </a-form-item>
          <a-form-item
            name="scope"
            label="对象"
            :rules="[{ required: true, message: 'Missing Scope' }]"
          >
            <a-select v-model:value="dynamicValidateForm.scope">
              <a-select-option value="ad_account">Ad Account</a-select-option>
              <a-select-option value="campaign">Campaign</a-select-option>
              <a-select-option value="adset">Adset</a-select-option>
              <a-select-option value="ad">Ad</a-select-option>
              <a-select-option value="camp_tag">Camp Tag</a-select-option>
              <a-select-option value="adset_tag">Adset Tag</a-select-option>
              <a-select-option value="ad_tag">Ad Tag</a-select-option>
              <a-select-option value="network" disabled>Network</a-select-option>
              <a-select-option value="offer" disabled>Offer</a-select-option>
            </a-select>
          </a-form-item>
          <a-form-item
            name="resource_ids"
            label="资源 ID"
            v-if="dynamicValidateForm.scope !== 'ad_account'"
          >
            <a-select
              v-model:value="dynamicValidateForm.resource_ids"
              mode="tags"
              placeholder=""
              max-tag-count="responsive"
            ></a-select>
          </a-form-item>
          <a-divider dashed />
          <a-form-item
            name="relation"
            label="条件匹配"
            :rules="[{ required: true, message: 'Missing Relation' }]"
          >
            <a-radio-group v-model:value="dynamicValidateForm.relation">
              <a-radio :value="true">All</a-radio>
              <a-radio :value="false">Any</a-radio>
            </a-radio-group>
          </a-form-item>
          <a-space
            v-for="(condition, index) in dynamicValidateForm.conditions"
            :key="index"
            style="display: flex; margin-bottom: 8px"
            align="baseline"
          >
            <a-form-item
              :name="['conditions', index, 'metric']"
              :label="`条件 ${index + 1}`"
              :label-col="{ span: 10 }"
              :rules="{
                required: true,
                message: 'Missing metric',
              }"
            >
              <a-select
                v-model:value="condition.metric"
                style="width: 190px; margin-right: 20px; margin-left: 10px"
                placeholder="metric"
                :options="metricsList"
              ></a-select>
            </a-form-item>
            <a-form-item
              :name="['conditions', index, 'operator']"
              :rules="{
                required: true,
                message: 'Missing operator',
              }"
            >
              <a-select
                v-model:value="condition.operator"
                style="width: 150px; margin-left: 30px"
                placeholder="operator"
              >
                <a-select-option value=">">大于</a-select-option>
                <a-select-option value="<">小于</a-select-option>
                <a-select-option value="=">等于</a-select-option>
              </a-select>
            </a-form-item>
            <a-form-item
              :name="['conditions', index, 'value']"
              :rules="{
                required: true,
                message: 'Missing value',
              }"
            >
              <a-input v-model:value="condition.value" style="width: 190px" placeholder="value" />
            </a-form-item>
            <minus-circle-outlined
              @click="removeCondition(condition)"
              v-if="dynamicValidateForm.conditions.length > 1"
            />
          </a-space>
          <a-form-item>
            <a-button
              type="dashed"
              style="margin-left: 130px; width: 320px"
              block
              @click="addCondition"
            >
              <plus-outlined />
              添加条件
            </a-button>
          </a-form-item>
          <a-space
            v-for="(action, index) in dynamicValidateForm.actions"
            :key="index"
            style="display: flex; margin-bottom: 8px"
            align="baseline"
          >
            <a-form-item
              :name="['actions', index, 'name']"
              :label="`操作 ${index + 1}`"
              :label-col="{ span: 10 }"
              :rules="[
                {
                  required: true,
                  message: 'Missing action',
                },
              ]"
            >
              <a-select
                v-model:value="dynamicValidateForm.actions[index].name"
                style="width: 190px; margin-right: 50px"
                placeholder="action"
                :options="actionList"
                :fieldNames="{ label: 'label', value: 'value' }"
                @change="handleActionChange(index, $event)"
              ></a-select>
            </a-form-item>
            <a-form-item
              v-if="action.addition_value"
              :name="['actions', index, 'value']"
              :rules="[
                {
                  required: true,
                  message: 'Missing value',
                },
              ]"
            >
              <a-input
                v-model:value="dynamicValidateForm.actions[index].value"
                style="width: 190px; margin-left: 8px"
                placeholder="value"
              />
              <a-tooltip :title="action.message">
                <info-circle-outlined
                  :style="{ color: '#faad14', 'margin-left': '8px' }"
                  v-if="action.message"
                />
              </a-tooltip>
            </a-form-item>
            <minus-circle-outlined
              @click="removeAction(action)"
              :style="{ 'margin-left': '8px' }"
              v-if="dynamicValidateForm.actions.length > 1"
            />
          </a-space>
          <a-form-item>
            <a-button
              type="dashed"
              style="margin-left: 130px; width: 320px"
              block
              @click="addAction"
            >
              <plus-outlined />
              添加操作
            </a-button>
          </a-form-item>

          <a-form-item name="white_list" label="白名单">
            <a-select
              v-model:value="dynamicValidateForm.white_list"
              mode="tags"
              placeholder=""
              max-tag-count="responsive"
            ></a-select>
          </a-form-item>
          <a-form-item
            name="is_active"
            label="状态"
            :rules="[{ required: true, message: 'Missing Relation' }]"
          >
            <a-radio-group v-model:value="dynamicValidateForm.is_active">
              <a-radio :value="true">Active</a-radio>
              <a-radio :value="false">Inactive</a-radio>
            </a-radio-group>
          </a-form-item>
          <a-form-item name="notes" label="备注">
            <a-textarea v-model:value="dynamicValidateForm.notes" :rows="4" />
          </a-form-item>
        </a-form>
      </a-modal>

      <a-modal
        v-model:open="openAdAccountModal"
        title="广告账户"
        width="1200px"
        @ok="saveSelectedAdAccount"
      >
        <a-table
          :columns="adAccountColumns as any"
          :data-source="adAccountDataState.dataSource"
          :row-key="record => record.id"
          :loading="adAccountDataState.loading"
          :pagination="{
            current: adAccountDataState.current,
            pageSize: adAccountDataState.pageSize,
            total: adAccountDataState.total,
            showTotal: total => `Total ${total} items`,
            pageSizeOptions: ['10', '20', '50', '100', '200', '500', '1000'],
            showSizeChanger: true,
            showQuickJumper: true,
          }"
          bordered
          sticky
size="small"
          @change="handleAdAccountTableChange"
          :row-selection="{
            selectedRowKeys: selectedAdAccountRowKeys,
            onChange: onSelectedAdAccountChange,
          }"
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
    </div>
  </page-container>
</template>
<script lang="ts">
import {
  SettingOutlined,
  PlusOutlined,
  ReloadOutlined,
  DownOutlined,
  MinusCircleOutlined,
  InfoCircleOutlined,
  EllipsisOutlined,
  SyncOutlined,
} from '@ant-design/icons-vue';
import { defineComponent, ref, computed, watchEffect, reactive, toRefs, watch } from 'vue';
import {
  queryListApi,
  addOneApi,
  batchDelete,
  batchActive,
  batchInactive,
  triggerAutomationPipeline,
  triggerSyncKeitaroToKv,
} from '@/api/rule';
import { queryFB_AD_AccountsApi as queryAdAccountList } from '@/api/fb_ad_accounts';
import { Container as DragContainer, Draggable } from '@/components/draggable';
import DragIcon from '@/components/table/drag-icon.vue';
import { useTableDynamicColumns } from '@/utils/hooks/useTableColumn';
import { useFetchData } from '@/utils/hooks/useFetchData';
import type { Pagination } from '@/typing';
import { message } from 'ant-design-vue';
import { useTableHeight } from '@/utils/hooks/useTableHeight';

type Key = string | number;

export default defineComponent({
  setup() {
    const sorterInfoMap = ref();
    const mergedColumns = ref<any[]>([]);
    const queryParam = reactive({
      name: undefined,
      is_active: undefined,
      tags: undefined,
      sortOrder: undefined,
      sortField: undefined,
    });

    const { tableHeight } = useTableHeight(338);

    const fetchDataContext = reactive({
      current: 1,
      pageSize: 10,
      requestParams: { ...queryParam },
    });
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
          title: '名称',
          dataIndex: 'name',
          minWidth: 140,
          resizable: true,
          align: 'center',
        },
        {
          title: '状态',
          dataIndex: 'is_active',
          minWidth: 80,
          align: 'center',
        },
        {
          title: '对象',
          dataIndex: 'scope',
          sorter: true,
          sortOrder: sorted.columnKey === 'scope' && sorted.order,
          minWidth: 80,
          align: 'center',
        },
        {
          title: '时间范围',
          dataIndex: 'date_preset',
          sorter: true,
          sortOrder: sorted.columnKey === 'date_preset' && sorted.order,
          minWidth: 100,
          align: 'center',
        },
        {
          title: '条件',
          dataIndex: 'conditions',
          minWidth: 120,
          resizable: true,
          align: 'center' as any,
          autoHeight: true,
        } as any,
        {
          title: '操作',
          dataIndex: 'actions',
          minWidth: 100,
          resizable: true,
          align: 'center' as any,
          autoHeight: true,
        } as any,
        {
          title: '资源',
          dataIndex: 'resource_ids',
          minWidth: 100,
          resizable: true,
          align: 'center' as any,
          autoHeight: true,
        } as any,
        {
          title: '白名单',
          dataIndex: 'white_list',
          ellipsis: false,
          minWidth: 80,
          resizable: true,
        },
        {
          title: '账户',
          dataIndex: 'fb_ad_accounts',
          key: 'ad_accounts',
          ellipsis: false,
          minWidth: 350,
          resizable: true,
          align: 'left' as any,
          autoHeight: true,
        } as any,
        {
          title: '广告',
          dataIndex: 'fb_ads',
          key: 'ads',
          ellipsis: false,
          minWidth: 80,
        },
        {
          title: '备注',
          dataIndex: 'notes',
          minWidth: 120,
          align: 'center',
        },
        {
          title: 'Tag',
          dataIndex: 'tags',
          minWidth: 120,
          align: 'center',
        },
        {
          title: '',
          dataIndex: 'operation',
          fixed: 'right',
          className: 'auto-width-column',
          minWidth: 160,
          align: 'center',
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

    const onRequestError = e => {
      console.error('请求错误: ', e);
      message.error('请求出错');
    };
    const { context: state, reload } = useFetchData(queryListApi as any, fetchDataContext, {
      onRequestError: onRequestError,
    });

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
      queryParam.name = undefined;
      queryParam.is_active = undefined;
      queryParam.tags = undefined;
      sorterInfoMap.value = undefined;
      fetchDataContext.current = 1;
      fetchDataContext.requestParams = { ...queryParam };
    };

    const onSelectChange = (selectedRowKeys: Key[]) => {
      selectedState.selectedRowKeys = selectedRowKeys;
    };

    const handleEdit = record => {
      console.log(record);
      dialogTitle.value = '编辑规则';
      // 填充下拉的 options
      adAccountOptions.value = record.fb_ad_accounts.map(item => ({
        value: item.id,
        status: item.account_status,
        name: item.name,
        id: item.source_id,
        label: `${item.name} - ${item.source_id} - ${item.account_status}`,
      }));

      selectedAdAccountRowKeys.value = record.fb_ad_accounts.map(a => a.id);

      dynamicValidateForm.id = record.id; // 一定要加这一行,加这一行, api 就变put了
      dynamicValidateForm.name = record.name;
      dynamicValidateForm.date_preset = record.date_preset;
      dynamicValidateForm.scope = record.scope;
      dynamicValidateForm.resource_ids = record.resource_ids;
      dynamicValidateForm.ad_account_ids = record.ad_account_ids;
      dynamicValidateForm.relation = record.relation;
      dynamicValidateForm.white_list = record.white_list;
      dynamicValidateForm.actions = record.actions.map(recordAction => {
        // 找到actionList中与recordAction匹配的项
        const actionOption = actionList.value.find(option => option.value === recordAction.name);
        // 如果actionList中有匹配的项，则使用该项的addition_value和message
        // 如果recordAction中有value属性，则addition_value为true
        return {
          name: recordAction.name,
          value: recordAction.value,
          addition_value:
            recordAction.value !== undefined || (actionOption && actionOption.addition_value),
          message: actionOption ? actionOption.message : '',
        };
      });
      dynamicValidateForm.conditions = record.conditions;
      dynamicValidateForm.is_active = record.is_active;

      openRuleModal.value = true;
    };

    const handleEnable = record => {
      batchActive({ ids: [record['id']] })
        .then(res => {
          message.info(res['message']);
        })
        .finally(() => reload());
    };

    const handleDisable = record => {
      batchInactive({ ids: [record['id']] })
        .then(res => {
          message.info(res['message']);
        })
        .finally(() => reload());
    };

    const handleDelete = record => {
      batchDelete({ ids: [record['id']] })
        .then(res => {
          message.info(res['message']);
        })
        .finally(() => reload());
    };
    const handleMenuClick = e => {
      if (selectedState.selectedRowKeys.length === 0) {
        message.warning('请选择一条或多条规则');
        return;
      }
      const key = e.key;
      if (key == 'batch-enable') {
        batchActive({ ids: selectedState.selectedRowKeys })
          .then(res => {
            message.info(res['message']);
          })
          .finally(() => reload());
      } else if (key === 'batch-disable') {
        batchInactive({ ids: selectedState.selectedRowKeys })
          .then(res => {
            message.info(res['message']);
          })
          .finally(() => reload());
      } else if (key === 'batch-delete') {
        batchDelete({ ids: selectedState.selectedRowKeys })
          .then(res => {
            message.info(res['message']);
          })
          .finally(() => reload());
      }
    };

    // 创建规则的 modal
    const formRef = ref<any>();
    const openRuleModal = ref<boolean>(false);
    const dialogTitle = ref('创建规则');
    const adAccountOptions = ref<any[]>([]);
    const metricsList = ref([
      { value: 'spend', label: 'Spend' },
      { value: 'offer_conversions', label: 'Sales' },
      { value: 'profit', label: 'Profit' },
      { value: 'offer_leads', label: 'Leads' },
      { value: 'roi', label: 'ROI' },
      { value: 'offer_cpc', label: 'Offer CPC' },
      { value: 'offer_epc', label: 'Offer EPC' },
      { value: 'offer_cpl', label: 'Offer CPL' },
      { value: 'offer_epl', label: 'Offer EPL' },
      { value: 'offer_clicks', label: 'Offer Clicks' },
      { value: 'link_clicks', label: 'FB Link Clicks' },
      { value: 'link_cpc', label: 'FB Link CPC' },
      { value: 'link_ctr', label: 'FB Link CTR' },
      { value: 'cpm', label: 'FB CPM' },
      { value: 'taken_rate', label: 'Taken Rate' },
      { value: 'impressions', label: 'Impressions' },
      { value: 'reach', label: 'Reach' },
    ]);
    const actionList = ref([
      { value: 'tg_alert', label: 'TG Notification', addition_value: true, message: '' },
      {
        value: 'turn_on_campaigns',
        label: 'Turn On Campaigns',
        addition_value: false,
        message: '',
      },
      { value: 'turn_on_adsets', label: 'Turn On Adsets', addition_value: false, message: '' },
      { value: 'turn_on_ads', label: 'Turn On Ads', addition_value: false, message: '' },
      {
        value: 'turn_off_campaigns',
        label: 'Turn Off Campaigns',
        addition_value: false,
        message: '',
      },
      { value: 'turn_off_adsets', label: 'Turn Off Adsets', addition_value: false, message: '' },
      { value: 'turn_off_adss', label: 'Turn Off adss', addition_value: false, message: '' },
      {
        value: 'increase_campaigns_budget',
        label: 'Inc Campaigns Budget',
        addition_value: true,
        message: '数字:10, 表示加$10, 10% 表示增加 10% 的预算. 统一单位是美金',
      },
      {
        value: 'decrease_campaigns_budget',
        label: 'Dec Campaigns Budget',
        addition_value: true,
        message: '数字:10, 表示加$10, 10% 表示增加 10% 的预算. 统一单位是美金',
      },
      {
        value: 'increase_adsets_budget',
        label: 'Inc Adsets Budget',
        addition_value: true,
        message: '数字:10, 表示加$10, 10% 表示增加 10% 的预算. 统一单位是美金',
      },
      {
        value: 'decrease_adsets_budget',
        label: 'Dec Adsets Budget',
        addition_value: true,
        message: '数字:10, 表示加$10, 10% 表示增加 10%的预算. 统一单位是美金',
      },
    ]);
    type RuleModel = {
      name: string;
      date_preset: string;
      scope: string;
      resource_ids?: any[];
      ad_account_ids?: any[];
      relation: boolean;
      conditions: any[];
      actions: any[];
      white_list: any[];
      is_active: boolean;
      notes: string;
      id?: string;
    };
    const dynamicValidateForm = reactive<RuleModel>({
      name: '',
      date_preset: 'today',
      scope: 'camp_tag',
      resource_ids: [],
      ad_account_ids: [],
      relation: true,
      conditions: [
        {
          metric: undefined,
          operator: undefined,
          value: undefined,
        },
      ],
      actions: [
        {
          name: undefined,
          value: undefined,
          addition_value: false,
          message: undefined,
        },
      ],
      white_list: [],
      is_active: true,
      notes: '',
    });

    const handleCancel = record => {
      console.log('cancel', record);
      formRef.value.resetFields();
      dynamicValidateForm.actions = [];
      dynamicValidateForm.conditions = [];
      dynamicValidateForm.resource_ids = [];
      dynamicValidateForm.ad_account_ids = [];
      dynamicValidateForm.white_list = [];
      dynamicValidateForm.id = null;
      dynamicValidateForm.name = '';
      reload();
    };

    const handleOk = () => {
      console.log('creating a rule');
      formRef.value.validateFields().then(() => {
        console.error(dynamicValidateForm);
        openRuleModal.value = false;
        const formDataCopy = { ...dynamicValidateForm };
        formDataCopy.actions = formDataCopy.actions.map(action => ({
          name: action.name,
          value: action.value,
        }));
        addOneApi(formDataCopy)
          .then(() => {
            message.success('操作成功！');
          })
          .finally(() => {
            reload();
            openRuleModal.value = false;
            formRef.value.resetFields();

            dynamicValidateForm.actions = [];
            dynamicValidateForm.conditions = [];
            dynamicValidateForm.resource_ids = [];
            dynamicValidateForm.ad_account_ids = [];
            dynamicValidateForm.white_list = [];
            adAccountOptions.value = []; // 清空下拉
            dynamicValidateForm.id = null;
            dynamicValidateForm.name = '';
          });
      });
    };

    const removeCondition = (item: any) => {
      console.log('remove condition');
      const index = dynamicValidateForm.conditions.indexOf(item);
      if (index !== -1) {
        dynamicValidateForm.conditions.splice(index, 1);
      }
    };

    const addCondition = () => {
      console.log('add conditon');
      dynamicValidateForm.conditions.push({
        metric: undefined,
        operator: undefined,
        value: undefined,
      });
    };

    const removeAction = (item: any) => {
      console.log('remove action');
      const index = dynamicValidateForm.actions.indexOf(item);
      if (index !== -1) {
        dynamicValidateForm.actions.splice(index, 1);
      }
    };

    const addAction = () => {
      console.log('add action');
      dynamicValidateForm.actions.push({
        name: undefined,
        value: undefined,
      });
    };

    const handleActionChange = (index, value) => {
      const selectedAction = actionList.value.find(action => action.value === value);
      if (selectedAction) {
        dynamicValidateForm.actions[index].message = selectedAction.message;
        dynamicValidateForm.actions[index].addition_value = selectedAction.addition_value;
      }
    };

    // 显示广告账户的 modal
    const openAdAccountModal = ref(false);
    const adAccountColumns = [
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
        title: '名称',
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
        title: '时区',
        dataIndex: 'timezone_name',
        minWidth: 140,
        align: 'center',
        resizable: true,
      },
      {
        title: '状态',
        dataIndex: 'account_status',
        sorter: (a, b) => a.account_status.length - b.account_status.length,
        minWidth: 100,
        align: 'center',
      },
      {
        title: '限额',
        dataIndex: 'adtrust_dsl',
        minWidth: 100,
        align: 'center',
      },
      {
        title: '花费',
        dataIndex: 'amount_spent',
        minWidth: 140,
        align: 'center',
        customRender: ({ text, record }) => {
          const amount = parseInt(text, 10);
          return `${record.currency} ${(amount / 100).toFixed(2)}`;
        },
      },
      {
        title: '余额',
        dataIndex: 'balance',
        minWidth: 100,
        align: 'center',
      },
      {
        title: 'Tag',
        dataIndex: 'tag',
        minWidth: 100,
        align: 'center',
      },
    ];

    const queryAdAccountParam = reactive({
      number: undefined,
      name: undefined,
      status: undefined,
      sortOrder: undefined,
      sortField: undefined,
      mode: 2,
    });
    const fetchAdAccountContext = reactive({
      current: 1,
      pageSize: 10,
      requestParams: { ...queryAdAccountParam },
    });
    const { context: adAccountDataState } = useFetchData(
      queryAdAccountList,
      fetchAdAccountContext,
      {
        onRequestError: onRequestError,
      },
    );
    const handleAdAccountTableChange = ({ current, pageSize }, _filters: any, _sorter: any) => {
      // sorterInfoMap.value = sorter;
      fetchAdAccountContext.current = current;
      fetchAdAccountContext.pageSize = pageSize;
    };

    const selectedAdAccountRowKeys = ref([]);
    const onSelectedAdAccountChange = selectedRowKeys => {
      console.log('onSelectedAdAccountChange changed: ', selectedRowKeys);
      selectedAdAccountRowKeys.value = selectedRowKeys;
    };
    // 关闭 AdAccount modal 的时候把数据放在 adAccountOptions 里面,下拉就可以展示了
    const saveSelectedAdAccount = () => {
      adAccountOptions.value = adAccountDataState.dataSource
        .filter(item => selectedAdAccountRowKeys.value.includes(item.id))
        .map(item => ({
          value: item.id,
          status: item.account_status,
          name: item.name,
          id: item.source_id,
          label: `${item.name} - ${item.source_id} - ${item.account_status}`,
        }));
      console.log('adAccountOptions:', adAccountOptions.value);
      dynamicValidateForm.ad_account_ids = selectedAdAccountRowKeys.value;
      openAdAccountModal.value = false;
    };

    const handleTriggerAutomationPipeline = () => {
      triggerAutomationPipeline()
        .then(res => {
          message.success(res['message']);
        })
        .catch(e => {
          console.log(e);
        });
    };

    const handleSyncKeitaroToKv = () => {
      triggerSyncKeitaroToKv()
        .then(res => {
          message.success(res['message']);
        })
        .catch(e => {
          console.log(e);
        });
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
      state,
      reload,
      tableHeight,

      queryParam,

      // 菜单处理
      handleEdit,
      handleEnable,
      handleDisable,
      handleDelete,
      handleMenuClick,

      // 创建规则的modal
      formRef,
      dynamicValidateForm,
      openRuleModal,
      dialogTitle,
      handleOk,
      handleCancel,
      adAccountOptions,
      removeCondition,
      addCondition,
      removeAction,
      addAction,
      metricsList,
      actionList,
      handleActionChange,

      // 选择广告户的 modal1
      openAdAccountModal,
      adAccountColumns,
      adAccountDataState,
      handleAdAccountTableChange,
      selectedAdAccountRowKeys,
      onSelectedAdAccountChange,
      saveSelectedAdAccount,

      // 同步检查规则
      handleTriggerAutomationPipeline,

      // 同步 Keitaro 到 KV
      handleSyncKeitaroToKv,
    };
  },
  components: {
    Draggable,
    DragContainer,
    DragIcon,
    SettingOutlined,
    PlusOutlined,
    ReloadOutlined,
    DownOutlined,
    MinusCircleOutlined,
    InfoCircleOutlined,
    EllipsisOutlined,
    SyncOutlined,
  },
});
</script>
<style scoped>
.auto-width-column {
  width: auto !important;
}

.ant-form-item {
  margin-bottom: 16px;
}
</style>
