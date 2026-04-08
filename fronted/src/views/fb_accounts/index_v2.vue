<template>
  <page-container :showPageHeader="false">
    <div class="ant-pro-table">
      <a-card :body-style="{ padding: 0 }">
        <div class="ant-pro-table-list-toolbar">
          <div class="ant-pro-table-list-toolbar-container">
            <div class="ant-pro-table-list-toolbar-left">
              <a-space>
                <a-button type="primary" @click="showNewAccountModal">
                  <plus-outlined />
                  {{ t('pages.acc.new') }}
                </a-button>
              </a-space>
              <a-space>
                <applied-filters :filters="appliedFilters"></applied-filters>
              </a-space>
            </div>
            <div class="ant-pro-table-list-toolbar-right">
              <div
                class="ant-pro-table-list-toolbar-setting-item"
                v-if="selectedRowKeys.length > -1"
              >
                <a-dropdown>
                  <template #overlay>
                    <a-menu @click="handleMenuClick">
                      <a-menu-item key="sync-multiple">
                        {{ t('pages.acc.action.sync') }}
                      </a-menu-item>
                      <a-menu-item key="add-tags">{{ t('pages.acc.action.add_tags') }}</a-menu-item>
                      <a-menu-item key="delete-tags">
                        {{ t('pages.acc.action.remove_tags') }}
                      </a-menu-item>
                      <a-menu-item key="assign-user">
                        {{ t('pages.acc.action.assign_user') }}
                      </a-menu-item>
                    </a-menu>
                  </template>
                  <a-button type="primary">
                    {{ t('pages.acc.action') }}
                    <down-outlined />
                  </a-button>
                </a-dropdown>
              </div>
              <div class="ant-pro-table-list-toolbar-divider">
                <a-divider type="vertical" />
              </div>
              <div class="ant-pro-table-list-toolbar-setting-item">
                <a-tooltip :title="t('pages.acc.refresh')">
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
                        {{ t('pages.acc.column_sort') }}
                      </a-checkbox>
                      <a @click="reset">{{ t('pages.acc.reset') }}</a>
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
                  <a-tooltip :title="t('pages.acc.column_setting')">
                    <setting-outlined />
                  </a-tooltip>
                </a-popover>
              </div>
              <div class="ant-pro-table-list-toolbar-setting-item">
                <dynamic-form :form-items="formItems" @change:form-data="onSearch"></dynamic-form>
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
            showTotal: total => `Total ${total} items`,
            showSizeChanger: true,
          }"
          :scroll="{ y: tableHeight }"
          :size="state.size"
          bordered
          sticky
          @change="handleTableChange"
:row-selection="{ selectedRowKeys: selectedRowKeys, onChange: onSelectChange }"
        >
          <template #bodyCell="{ column, text, record }">
            <template v-if="column['dataIndex'] === 'action'">
              <a @click="handleSyncOne(record)">{{ t('pages.acc.action.sync') }}</a>
              <a-divider type="vertical" />
              <a @click="handleShowEditModal(record)">{{ t('pages.common.edit') }}</a>
              <a-divider type="vertical" />
              <a-popconfirm
                :title="t('pages.common.delete.msg')"
                :ok-text="t('pages.common.delete.ok')"
                :cancel-text="t('pages.common.delete.cancel')"
                @confirm="deleteOne(record)"
              >
                <a class="href-btn" href="#">{{ t('pages.common.delete') }}</a>
              </a-popconfirm>
            </template>
            <template v-if="['source_id'].includes(`${column['dataIndex']}`)">
              {{ text }}
              <copy-outlined v-if="text" @click="copyCell(text)" />
            </template>

            <template v-if="['token_valid'].includes(`${column['dataIndex']}`)">
              <span>
                <a-switch
                  v-model:checked="record.token_valid"
                  @change="checked => handleSwitchChange(checked, record)"
                />
              </span>
            </template>
            <template v-if="column['dataIndex'] === 'ad_accounts'">
              <a-button type="link" @click="showAdAccountModal(record)">
                {{ record.ad_accounts.length }}
              </a-button>
            </template>
            <template v-if="column['dataIndex'] === 'business_users'">
              <a-button type="link" @click="showBmModal(record)">
                {{ record.business_users.length }}
              </a-button>
            </template>
            <template v-if="column['dataIndex'] === 'pages'">
              <a-button type="link" @click="showPageModal(record)">
                {{ record.pages.length }}
              </a-button>
            </template>
            <template v-if="column['dataIndex'] === 'proxy'">
              {{ record.proxy.host }}:{{ record.proxy.port }}
            </template>
            <template v-if="['created_at', 'updated_at'].includes(`${column['dataIndex']}`)">
              <span>{{ dayjs(text).format('YYYY-MM-DD HH:mm:ss') }}</span>
            </template>
            <template v-if="column['dataIndex'] === 'tags'">
              <a-tag v-for="item in record.tags" :key="item.id">{{ item.name }}</a-tag>
            </template>
          </template>
        </a-table>
      </a-card>
    </div>
    <div>
      <a-modal
        v-model:open="newAccountModal"
        :title="accountModalTitle"
        ok-text="Ok"
        cancel-text="Cancel"
        @ok="handleNewAccountOk"
        :width="800"
      >
        <a-form
          ref="newAccountFormRef"
          :model="newAccountForm"
          name="form_in_modal"
          :label-col="{ span: 4 }"
        >
          <a-form-item name="twofa_key" :label="t('pages.acc.2fa')" :rules="[{ required: false }]">
            <a-input v-model:value="newAccountForm.twofa_key" />
          </a-form-item>
          <a-form-item name="proxy_id" :label="t('pages.acc.proxy')" :rules="[{ required: true }]">
            <a-input-group compact>
              <a-select
                v-model:value="newAccountForm.proxy_id"
                :options="proxiesData"
                size="middle"
                placeholder=""
                style="width: calc(100% - 45px)"
              ></a-select>
              <a-button @click="queryProxies"><reload-outlined :spin="proxyLoading" /></a-button>
            </a-input-group>
          </a-form-item>
          <a-form-item name="useragent" label="User Agent" :rules="[{ required: true }]">
            <a-input v-model:value="newAccountForm.useragent" />
          </a-form-item>
          <a-form-item name="token" label="Token">
            <a-input v-model:value="newAccountForm.token" />
          </a-form-item>
          <a-form-item name="cookies" label="Cookies">
            <a-textarea :rows="4" v-model:value="newAccountForm.cookies" />
          </a-form-item>
          <a-form-item name="notes" :label="t('pages.acc.notes')">
            <a-input v-model:value="newAccountForm.notes" />
          </a-form-item>
          <a-form-item name="tags" :label="'Tags'">
            <a-select
              v-model:value="newAccountForm.tags"
              :options="tagOptions"
              mode="tags"
              placeholder="Please select"
              style="width: 100%"
              max-tag-count="responsive"
            ></a-select>
          </a-form-item>
        </a-form>
      </a-modal>

      <a-modal
        v-model:open="openAdAccountModal"
        :title="t('pages.acc.ad_acc')"
        width="900px"
        @ok="openAdAccountModal = false"
      >
        <a-table
          :columns="adAccountColumns"
          :data-source="adAccountData"
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

      <a-modal v-model:open="openPageModal" title="主页" width="900px" @ok="openPageModal = false">
        <a-table
          :columns="pageColumns"
          :data-source="pageData"
          :row-key="record => record.id"
          bordered
          sticky
size="small"
>
          promotion_eligible
          <template #bodyCell="{ column, text }">
            <template v-if="column['dataIndex'] === 'promotion_eligible'">
              <a-badge
                :color="text === true ? 'green' : 'red'"
                :text="
                  text === true
                    ? t('pages.acc.page.not_restricted')
                    : t('pages.acc.page.is_restricted')
                "
              />
            </template>
          </template>
        </a-table>
      </a-modal>

      <a-modal v-model:open="openBmModal" title="BM" width="1000px" @ok="openBmModal = false">
        <a-table
          :columns="bmColumns"
          :data-source="bmData"
          :row-key="record => record.id"
          bordered
          sticky
size="small"
>
          promotion_eligible
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

      <assign-user-modal
        :model="assignUserModal.model"
        :visible="assignUserModal.visible"
        @cancel="
          () => {
            assignUserModal.visible = false;
          }
        "
        @ok="
          () => {
            assignUserModal.visible = false;
            reload();
          }
        "
      />
    </div>
  </page-container>
</template>
<script lang="ts">
import {
  PlusOutlined,
  SettingOutlined,
  DownOutlined,
  CopyOutlined,
  ReloadOutlined,
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
  onMounted,
} from 'vue';

import {
  queryFBAccountsApi,
  addFBAccountsOneApi,
  deletFBAccountsApi,
  syncResources,
  setTokenValid,
  batchSyncResources,
  getFbAccountsValidTags,
} from '@/api/fb_accounts';
import { queryProxiesApi } from '@/api/proxies';

import { Container as DragContainer, Draggable } from '@/components/draggable';
import DragIcon from '@/components/table/drag-icon.vue';
import { useTableDynamicColumns } from '@/utils/hooks/useTableColumn';
import { message } from 'ant-design-vue';
import useClipboard from 'vue-clipboard3';
import dayjs from 'dayjs';
import type { FormInstance } from 'ant-design-vue';
import { useFetchData } from '@/utils/hooks/useFetchData';
import type { Pagination } from '@/typing';
import { useI18n } from 'vue-i18n';
import type { DefaultRecordType } from 'ant-design-vue/es/vc-table/interface';
import { Form } from 'ant-design-vue';

import TagModal from './tag-modal.vue';
import AssignUserModal from './assign-user-modal.vue';
import { useUserStore } from '@/store/user';
import DynamicForm from '@/components/dynamic-form/dynamic-form.vue';
import AppliedFilters from '@/components/applied-filters/applied-filters.vue';
import { useTableHeight } from '@/utils/hooks/useTableHeight';

type Key = string | number;

export default defineComponent({
  setup() {
    const { t } = useI18n();
    const sorterInfoMap = ref();
    const userStore = useUserStore();

    const { tableHeight } = useTableHeight();

    const role = userStore.currentUser.role.name;

    const formItems = ref([
      { label: 'FB Account Name', field: 'account_names', multiple: true },
      { label: 'FB Account ID', field: 'account_ids', multiple: true },
      { label: 'pages.ads.ad.acc_name', field: 'ad_account_names', multiple: true },
      { label: 'pages.ads.ad.acc_id', field: 'ad_account_ids', multiple: true },
      { label: 'Page Name', field: 'page_names', multiple: true },
      { label: 'Page ID', field: 'page_ids', multiple: true },
      { label: 'BM Name', field: 'bm_names', multiple: true },
      { label: 'BM ID', field: 'bm_ids', multiple: true },
      { label: 'pages.acc.tags', field: 'tags', multiple: true },
      { label: 'pages.acc.notes', field: 'notes', multiple: true },
    ]);
    const appliedFilters = ref({});
    const onSearch = data => {
      Object.entries(data).forEach(([key, value]) => (queryParam[key] = value));
      appliedFilters.value = data;
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
      page_names: undefined,
      page_ids: undefined,
      sortOrder: undefined,
      sortField: undefined,
      tags: [],
      notes: [],
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

      if (inputParam.page_type === 'name') {
        queryParam.page_names = inputParam.page_value;
        queryParam.page_ids = undefined;
      } else if (inputParam.page_type === 'id') {
        queryParam.page_ids = inputParam.page_value;
        queryParam.page_names = undefined;
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

    const fetchDataContext = reactive({
      current: 1,
      pageSize: 10,
      requestParams: { ...queryParam },
    });
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
          title: t('pages.acc.index'),
          dataIndex: 'index',
          customRender: ({ index }: any) => {
            return `${index + 1}`;
          },
          width: 100,
          align: 'center',
          fixed: 'left',
        },
        {
          title: t('pages.acc.name'),
          dataIndex: 'name',
          sorter: true,
          sortOrder: sorted.columnKey === 'name' && sorted.order,
          align: 'center',
          minWidth: 160,
          ellipsis: false,
        },
        {
          title: 'ID',
          dataIndex: 'source_id',
          sorter: true,
          sortOrder: sorted.columnKey === 'source_id' && sorted.order,
          align: 'center',
          minWidth: 160,
          ellipsis: false,
        },
        {
          title: 'Token',
          dataIndex: 'token_valid',
          minWidth: 160,
        },
        {
          title: t('pages.acc.ad_acc'),
          dataIndex: 'ad_accounts',
          minWidth: 100,
        },
        {
          title: 'BM',
          dataIndex: 'business_users',
          minWidth: 100,
        },
        {
          title: t('pages.acc.page'),
          dataIndex: 'pages',
          minWidth: 100,
        },
        {
          title: t('pages.acc.proxy'),
          dataIndex: 'proxy',
          minWidth: 100,
          resizable: true,
        },
        {
          title: t('pages.acc.notes'),
          dataIndex: 'notes',
          minWidth: 100,
          resizable: true,
        },
        {
          title: t('pages.acc.tags'),
          dataIndex: 'tags',
          minWidth: 100,
          resizable: true,
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
          width: 150,
        },
      ];

      if (role === 'admin') {
        mergedColumns.value.splice(1, 0, {
          title: t('pages.acc.sys_user'),
          dataIndex: 'system_user_name',
          width: 100,
          align: 'center',
        });
      }
    });

    const selectedState = reactive<{
      selectedRowKeys: Key[];
      selectedRows: any[];
    }>({
      selectedRowKeys: [],
      selectedRows: [],
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

    const { context: state, reload } = useFetchData(queryFBAccountsApi, fetchDataContext);

    // 同步一个账户
    const handleSyncOne = (record: any) => {
      syncResources(record.id)
        .then(res => {
          message.success(res['message']);
        })
        .catch(error => {
          message.error('请求失败');
          console.error(error);
        })
        .finally(() => {
          reload();
        });
    };

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
      queryParam.page_ids = undefined;
      queryParam.page_names = undefined;
      queryParam.bm_ids = undefined;
      queryParam.bm_names = undefined;
      queryParam.tags = [];
      queryParam.notes = [];

      sorterInfoMap.value = undefined;
      fetchDataContext.current = 1;
      fetchDataContext.requestParams = { ...queryParam };
    };

    const onSelectChange = (selectedRowKeys: Key[], selectedRows) => {
      // console.log('selectedRowKeys changed: ', selectedRowKeys);
      selectedState.selectedRowKeys = selectedRowKeys;
      selectedState.selectedRows = selectedRows;
    };

    const handleMenuClick = (e: any) => {
      const selectedIds = toRaw(selectedState.selectedRowKeys);
      if (selectedIds.length === 0) {
        message.error(t('pages.acc.msg.select_acc'));
        return;
      }

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

      const key = e.key;
      if (key === 'sync-multiple') {
        batchSyncResources({
          ids: toRaw(selectedState.selectedRowKeys),
        })
          .then(res => {
            message.success(res['message']);
          })
          .catch(error => {
            console.error(error);
          });
      } else if (key === 'add-tags') {
        console.log(selectedState);
        tagModal.model = {
          ids: selectedIds,
          action: 'add',
          tagList: uniqueTags,
          modelType: 'fbaccounts',
        };
        tagModal.visible = true;
      } else if (key === 'delete-tags') {
        tagModal.model = {
          ids: selectedIds,
          action: 'delete',
          tagList: uniqueTags,
          modelType: 'fbaccounts',
        };
        tagModal.visible = true;
      } else if (key === 'assign-user') {
        assignUserModal.model = {
          ids: selectedIds,
        };
        console.log(selectedIds);
        assignUserModal.visible = true;
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

    const proxiesData = ref<any>([]);

    const proxyLoading = ref(false);
    const queryProxies = (refresh: boolean) => {
      if (refresh) {
        proxyLoading.value = true;
      }
      queryProxiesApi({ pageNo: 1, pageSize: 9999 })
        .then(res => {
          proxiesData.value = [];
          res.data.forEach((proxy: any) => {
            proxiesData.value.push({
              value: proxy['id'],
              label: `${proxy['notes']}-${proxy['protocol']}//${proxy['host']}:${proxy['port']}`,
            });
          });
        })
        .catch(e => {
          console.log(e);
        })
        .finally(() => {
          if (refresh) proxyLoading.value = false;
        });
    };
    queryProxies(false);

    //新建账户的 form
    const newAccountForm = reactive({
      twofa_key: '',
      proxy_id: undefined,
      useragent: undefined,
      token: undefined,
      cookies: undefined,
      notes: undefined,
      tag_ids: [],
      id: undefined,
      tags: [],
    });

    // 创建账户的 modal
    const newAccountFormRef = ref<FormInstance>();
    const newAccountModal = ref<boolean>(false);
    const accountModalTitle = ref('新建帐户');

    // watch(
    //   newAccountModal,
    //   () => {
    //     // 直接重置对象的每个属性
    //     newAccountForm.twofa_key = undefined;
    //     newAccountForm.proxy_id = undefined;
    //     newAccountForm.useragent = undefined;
    //     newAccountForm.token = undefined;
    //     newAccountForm.cookies = undefined;
    //     newAccountForm.notes = undefined;
    //     newAccountForm.tag_ids = [];
    //   },
    //   { flush: 'post' },
    // );
    const showNewAccountModal = () => {
      console.log('creat new account');
      accountModalTitle.value = t('pages.acc.new');
      newAccountForm.twofa_key = undefined;
      newAccountForm.proxy_id = undefined;
      newAccountForm.useragent = undefined;
      newAccountForm.token = undefined;
      newAccountForm.cookies = undefined;
      newAccountForm.notes = undefined;
      newAccountForm.tag_ids = [];
      newAccountForm.id = undefined;
      newAccountModal.value = true;
      console.log(newAccountModal.value);
    };
    const { resetFields: resetNewAccountForm } = Form.useForm(newAccountFormRef);
    const handleNewAccountOk = () => {
      newAccountFormRef.value
        .validateFields()
        .then(values => {
          console.log('Received values of form: ', values);
          newAccountModal.value = false;
          console.log('raw:', toRaw(newAccountForm));
          // 从 newAccountForm 获取数值,不从 values里面取, 因为value 里面没有 id
          // add 接口里面会判断是新建还是更新
          const formValue = toRaw(newAccountForm);
          addFBAccountsOneApi({
            ...formValue,
          })
            .then(res => {
              message.success(res['message']);
            })
            .catch(e => {
              message.success(e.response.data.message);
              console.log(e);
            })
            .finally(() => {
              // newAccountFormRef
              console.log('reset form');
              resetNewAccountForm();
              newAccountForm.twofa_key = undefined;
              newAccountForm.proxy_id = undefined;
              newAccountForm.useragent = undefined;
              newAccountForm.token = undefined;
              newAccountForm.cookies = undefined;
              newAccountForm.notes = undefined;
              newAccountForm.tag_ids = [];
              newAccountForm.tags = [];
              reload();
            });
        })
        .catch(info => {
          console.log(info);
        });
    };
    const deleteOne = (record: any) => {
      deletFBAccountsApi(record.id)
        .then(() => {
          message.success(t('pages.opSuccessfully'));
        })
        .finally(() => {
          reload();
        });
    };
    const handleShowEditModal = (record: any) => {
      console.log(record);
      accountModalTitle.value = t('pages.acc.edit_title');

      newAccountForm.twofa_key = record.twofa_key;
      newAccountForm.useragent = record.useragent;
      newAccountForm.notes = record.notes;
      newAccountForm.proxy_id = record.proxy.id;
      newAccountForm.tag_ids = record.tags.map(tag => tag.id);
      newAccountForm.id = record.id;
      newAccountForm.tags = record.tags.map(tag => tag.name);
      console.log(newAccountForm);
      newAccountModal.value = true;
      getFbAccountsValidTags().then(res => {
        tagsData.value = res.data;
      });
    };

    // 显示广告账户
    const openAdAccountModal = ref(false);
    const adAccountColumns = [
      {
        title: t('pages.acc.index'),
        dataIndex: 'index',
        customRender: ({ index }) => {
          return `${index + 1}`;
        },
        width: 120,
        align: 'center',
        resizable: true,
      },
      {
        title: t('pages.acc.ad_acc_name'),
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
        title: t('pages.acc.ad_acc_status'),
        dataIndex: 'account_status',
        sorter: (a, b) => a.account_status.length - b.account_status.length,
        minWidth: 100,
        align: 'center',
      },
      {
        title: t('pages.acc.ad_acc_spent'),
        dataIndex: 'amount_spent',
        minWidth: 140,
        align: 'center',
        customRender: ({ text, record }) => {
          const amount = parseInt(text, 10);
          return `${record.currency} ${amount.toFixed(2)}`;
        },
        resizable: true,
      },
      {
        title: t('pages.acc.ad_acc_balance'),
        dataIndex: 'balance',
        minWidth: 150,
        align: 'center',
        customRender: ({ text, record }) => {
          return `${text ? text : 'NA'}/${record.threshold_amount}`;
        },
        resizable: true,
      },
      {
        title: t('pages.acc.ad_acc_limit'),
        dataIndex: 'adtrust_dsl',
        minWidth: 100,
        align: 'center',
      },
      {
        title: t('pages.acc.ad_acc_tz'),
        dataIndex: 'timezone_name',
        minWidth: 140,
        align: 'center',
      },
      {
        title: 'Tags',
        dataIndex: 'tags',
        minWidth: 100,
        align: 'center',
        resizable: true,
      },
    ];
    const adAccountData = ref<DefaultRecordType[]>([]);
    const showAdAccountModal = record => {
      adAccountData.value = record.ad_accounts;
      openAdAccountModal.value = true;
    };

    // 显示主页的 modal
    const openPageModal = ref(false);
    const pageColumns = [
      {
        title: t('pages.acc.index'),
        dataIndex: 'index',
        customRender: ({ index }) => {
          return `${index + 1}`;
        },
        width: 100,
        align: 'center',
      },
      {
        title: t('pages.acc.page.name'),
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
        title: t('pages.acc.page.fans'),
        dataIndex: 'fan_count',
        sorter: true,
        minWidth: 100,
        align: 'center',
      },
      {
        title: t('pages.acc.page.restricted'),
        dataIndex: 'promotion_eligible',
        minWidth: 140,
        align: 'center',
      },
      {
        title: t('pages.acc.page.users'),
        dataIndex: 'users',
        minWidth: 100,
        align: 'center',
        customRender: ({ record }) => {
          return `${record.users_count}`;
        },
      },
    ];
    const pageData = ref<DefaultRecordType[]>([]);
    const showPageModal = record => {
      pageData.value = record.pages;
      openPageModal.value = true;
    };

    const handleSwitchChange = (checked, record) => {
      console.log(checked);
      console.log(record);
      const params = {
        token_valid: checked,
      };
      setTokenValid(record.id, params).then((res: any) => {
        if (res?.message === 'Token valid status updated successfully') reload();
      });
    };

    // 显示BM的 modal
    const openBmModal = ref(false);
    const bmColumns = [
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
        title: t('pages.acc.bm.name'),
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
        title: t('pages.acc.bm.ad_acc'),
        dataIndex: 'ad_accounts',
        sorter: true,
        minWidth: 100,
        align: 'center',
        customRender: ({ record }) => {
          return `${record.ad_accounts_count}`;
        },
      },
      {
        title: t('pages.acc.bm.user_name'),
        dataIndex: 'users',
        minWidth: 140,
        align: 'center',
        customRender: ({ record }) => {
          return `${record.myName}`;
        },
      },
      {
        title: t('pages.acc.bm.users'),
        dataIndex: 'users',
        minWidth: 100,
        align: 'center',
        customRender: ({ record }) => {
          return `${record.users_count}`;
        },
      },
    ];
    const bmData = ref<DefaultRecordType[]>([]);
    const showBmModal = record => {
      const newData = [];
      record.bms.forEach(item => {
        const users = item.users;
        let myName = '';
        users.forEach(user => {
          record.business_users.forEach(bm_user => {
            if (bm_user.source_id === user.source_id) {
              myName = user.name;
            }
          });
        });
        item['myName'] = myName;
        newData.push(item);
      });
      bmData.value = newData;
      openBmModal.value = true;
    };

    onMounted(() => {
      getFbAccountsValidTags().then(res => {
        tagsData.value = res.data;
      });
    });

    // tagModal
    const tagModal = reactive({
      visible: false,
      model: null,
    });

    // assignUserModal
    const assignUserModal = reactive({
      visible: false,
      model: null,
    });

    return {
      t,
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
      hasSelected,
      onSelectChange,
      ...toRefs(selectedState),
      state,

      queryParam,
      inputParam,

      handleSyncOne,
      handleMenuClick,

      copyCell,
      dayjs,

      //账户相关
      showNewAccountModal,
      newAccountModal,
      handleNewAccountOk,
      newAccountForm,
      newAccountFormRef,
      tagsData,
      tagOptions,
      proxiesData,
      deleteOne,
      accountModalTitle,
      handleSwitchChange,
      tableHeight,

      // account modal
      openAdAccountModal,
      adAccountColumns,
      adAccountData,
      showAdAccountModal,
      handleShowEditModal,

      // page modal
      openPageModal,
      pageColumns,
      showPageModal,
      pageData,

      // bm modal
      openBmModal,
      bmColumns,
      showBmModal,
      bmData,

      queryProxies,
      proxyLoading,

      // tag modal
      tagModal,

      // assign user modal
      assignUserModal,
      formItems,
      onSearch,
      appliedFilters,
    };
  },
  components: {
    PlusOutlined,
    Draggable,
    DynamicForm,
    DragContainer,
    DragIcon,
    SettingOutlined,
    DownOutlined,
    CopyOutlined,
    ReloadOutlined,
    TagModal,
    AssignUserModal,
    AppliedFilters,
  },
});
</script>
