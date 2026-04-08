<template>
  <page-container :title="t('pages.accounts.title')">
    <a-card mb-4>
      <a-row :gutter="[15, 0]" v-if="expand">
        <a-col :span="6">
          <a-form-item :label="t('pages.profile.id')">
            <a-input v-model:value="pagination.sourceId" />
          </a-form-item>
        </a-col>
        <a-col :span="6">
          <a-form-item :label="t('pages.name')">
            <a-input v-model:value="pagination.name" />
          </a-form-item>
        </a-col>
        <a-col :span="6">
          <a-form-item :label="t('pages.username')">
            <a-input v-model:value="pagination.username" />
          </a-form-item>
        </a-col>
        <a-col :span="6">
          <a-form-item :label="t('pages.notes')">
            <a-input v-model:value="pagination.notes" />
          </a-form-item>
        </a-col>
      </a-row>
      <a-row :gutter="[15, 0]" v-if="expand">
        <a-col :span="6">
          <a-form-item :label="t('pages.tag')">
            <a-select
              v-model:value="pagination.tags"
              :options="tagOptions"
              mode="multiple"
              size="middle"
              :placeholder="t('pages.plsSelect')"
            ></a-select>
          </a-form-item>
        </a-col>
        <a-col :span="10">
          <a-form-item :label="t('pages.createdAt')">
            <a-range-picker v-model:value="pagination.created_at" show-time />
          </a-form-item>
        </a-col>
      </a-row>

      <a-row :span="24" style="text-align: left; display: flex; justify-content: space-between">
        <a-col>
          <a-button type="primary" @click="showModal('')">
            <template #icon>
              <plus-outlined />
            </template>
            {{ t('pages.add') }}
          </a-button>
        </a-col>

        <a-space>
          <a-button :loading="loading" type="primary" @click="fetchFBAccounts">
            {{ t('pages.query') }}
          </a-button>
          <a-button @click="resetPagination">
            {{ t('pages.reset') }}
          </a-button>
          <a-button type="link" @click="expand = !expand">
            {{ expand ? t('pages.collapse') : t('pages.expand') }}
            <up-outlined v-if="expand" />
            <down-outlined v-else />
          </a-button>
        </a-space>
      </a-row>
    </a-card>
    <a-table
      :columns="columns"
      :data-source="dataSource"
      @change="handleTableChange"
      :scroll="{ y: tableHeight, x: 2000 }"
      :pagination="pagination"
      :loading="loading"
      :row-key="record => record.id"
      :row-selection="{ selectedRowKeys: selectedRowKeys, onChange: onSelectChange }"
      bordered
      sticky
>
      <template #bodyCell="{ column, text, record }">
        <template v-if="column['dataIndex'] === 'active'">
          <span>{{ text === false ? t('pages.deActive') : t('pages.active') }}</span>
        </template>
        <template v-if="['endpoint'].includes(`${column['dataIndex']}`)">
          <a :href="text" target="_blank" v-html="text"></a>
          <copy-outlined @click="copyCell(text)" />
        </template>
        <template v-if="['tags'].includes(`${column['dataIndex']}`)">
          <a-tag v-for="m in text" :key="m.id">{{ m.name }}</a-tag>
        </template>
        <template v-if="['proxy'].includes(`${column['dataIndex']}`)">
          <span>
            {{ text?.protocol + '://' + text?.host + ':' + text?.port }}
          </span>
        </template>
        <template v-if="['token_valid'].includes(`${column['dataIndex']}`)">
          <span>
            <a-switch
              v-model:checked="record.token_valid"
              @change="checked => handleSwitchChange(checked, record)"
            />
          </span>
        </template>
        <template v-if="['created_at', 'updated_at'].includes(`${column['dataIndex']}`)">
          <span>{{ dayjs(text).format('YYYY-MM-DD HH:mm:ss') }}</span>
        </template>
        <template v-if="column['dataIndex'] === 'operation'">
          <a class="href-btn" @click="syncResource(record)">{{ t('pages.sync.resource') }}</a>
          <a-divider type="vertical" />
          <a class="href-btn" @click="showModal(record)">{{ t('pages.edit') }}</a>
          <a-divider type="vertical" />
          <a-popconfirm
            :title="t('pages.doubleConfirmDel')"
            :ok-text="t('pages.confirm')"
            :cancel-text="t('pages.cancel')"
            @confirm="deleteOne(record)"
          >
            <a class="href-btn" href="#">{{ t('pages.del') }}</a>
          </a-popconfirm>
        </template>
      </template>
    </a-table>

    <a-modal v-if="open" v-model:open="open" :title="dialogTitle" @ok="handleOk" :width="800">
      <a-form
        ref="formRef"
        :label-col="{ span: 4 }"
        name="dynamic_form_nest_item"
        :model="dynamicValidateForm"
      >
        <a-form-item
          name="twofa_key"
          :label="t('pages.twofactor.key')"
          :rules="[{ required: true }]"
        >
          <a-input v-model:value="dynamicValidateForm.twofa_key" />
        </a-form-item>
        <a-form-item name="proxy_id" :label="t('pages.proxy')" :rules="[{ required: true }]">
          <div class="container">
            <a-select
              v-model:value="dynamicValidateForm.proxy_id"
              :options="proxiesOptions"
              size="middle"
              @change="handleProxyChange"
              :placeholder="t('pages.plsSelect')"
            >
              >
            </a-select>
            <sync-outlined :spin="proxiesLoading" @click="reloadProxies" />
          </div>
        </a-form-item>
        <a-form-item name="useragent" :label="t('pages.useragent')" :rules="[{ required: true }]">
          <a-input v-model:value="dynamicValidateForm.useragent" />
        </a-form-item>
        <a-form-item name="token" :label="t('pages.token')">
          <a-input v-model:value="dynamicValidateForm.token" />
        </a-form-item>
        <a-form-item name="cookies" label="Cookies">
          <a-textarea :rows="4" v-model:value="dynamicValidateForm.cookies" />
        </a-form-item>
        <a-form-item name="notes" :label="t('pages.notes')">
          <a-input v-model:value="dynamicValidateForm.notes" />
        </a-form-item>
        <a-form-item name="tags" :label="'Tags'">
          <template v-for="tag in tagsData" :key="tag">
            <a-tag
              :color="dynamicValidateForm.tags.some(i => i.name === tag.name) ? 'green' : ''"
              @close="handleClose(tag)"
              @click="changeTag(tag)"
              closable
            >
              {{ tag.name }}
            </a-tag>
          </template>
          <a-input
            v-if="inputVisible"
            ref="inputRef"
            v-model:value="inputValue"
            type="text"
            size="small"
            :style="{ width: '78px' }"
            @blur="handleInputConfirm"
            @keyup.enter="handleInputConfirm"
          />
          <a-tag v-else style="background: #fff; border-style: dashed" @click="showInput">
            <plus-outlined />
            New Tag
          </a-tag>
        </a-form-item>
      </a-form>
    </a-modal>
  </page-container>
</template>
<script lang="ts">
import dayjs from 'dayjs';
import { cloneDeep } from 'lodash';
import { computed, defineComponent, ref, onMounted, watch, nextTick, reactive, toRefs } from 'vue';
import { message, Modal } from 'ant-design-vue';
import {
  CopyOutlined,
  PlusOutlined,
  SyncOutlined,
  UpOutlined,
  DownOutlined,
} from '@ant-design/icons-vue';
import { queryTagsApi, addTagsOneApi, deleteTagsOneApi } from '@/api/networks';
import { queryProxiesApi } from '@/api/proxies';
import {
  queryFBAccountsApi,
  addFBAccountsOneApi,
  deletFBAccountsApi,
  syncResources,
  setTokenValid,
} from '@/api/fb_accounts';
import { useI18n } from 'vue-i18n';
import useClipboard from 'vue-clipboard3';

export default defineComponent({
  components: {
    CopyOutlined,
    PlusOutlined,
    SyncOutlined,
    UpOutlined,
    DownOutlined,
  },
  setup() {
    const { t } = useI18n();
    const sortedInfo = ref();
    const expand = ref(true);
    const screenHeight = ref<number>(window.innerHeight);
    const tableHeight = screenHeight.value < 1010 ? ref('48vh') : ref('54vh');

    watch(expand, newValue => {
      if (screenHeight.value < 1010) {
        tableHeight.value = newValue ? '48vh' : '65vh';
      } else {
        tableHeight.value = newValue ? '54vh' : '65vh';
      }
    });
    const columns = computed<any[]>(() => {
      const sorted = sortedInfo.value || {};
      return [
        {
          title: t('pages.profile.id'),
          dataIndex: 'source_id',
          width: 200,
        },
        {
          title: t('pages.name'),
          dataIndex: 'name',
          width: 180,
        },
        {
          title: t('pages.accounts.token_valid'),
          dataIndex: 'token_valid',
        },
        // {
        //   title: t('pages.avatar'),
        //   dataIndex: 'fingerbrowser_id',
        // },
        {
          title: t('pages.notes'),
          dataIndex: 'notes',
          width: 200,
        },
        {
          title: t('pages.proxy'),
          dataIndex: 'proxy',
        },
        {
          title: t('pages.createdAt'),
          dataIndex: 'created_at',
          sorter: true,
          sortOrder: sorted.field === 'created_at' && sorted.order,
        },
        {
          title: t('pages.updatedAt'),
          dataIndex: 'updated_at',
          sorter: true,
          sortOrder: sorted.field === 'updated_at' && sorted.order,
        },
        {
          title: t('pages.notes'),
          dataIndex: 'notes',
          width: 200,
        },
        {
          title: t('pages.tag'),
          dataIndex: 'tags',
        },
        {
          title: t('pages.operation'),
          width: 180,
          dataIndex: 'operation',
          fixed: 'right',
        },
      ];
    });
    const handleTableChange: any['onChange'] = (pag, _filters, sorter) => {
      if (pag) {
        if (pag.current != null) pagination.value.current = pag.current;
        if (pag.pageSize != null) pagination.value.pageSize = pag.pageSize;
      }
      sortedInfo.value = sorter;
      fetchFBAccounts();
    };
    const loading = ref(true);

    const state = reactive<{
      selectedRowKeys: string[];
    }>({
      selectedRowKeys: [], // Check here to configure the default column
    });
    const submitting = ref(false);
    const dataSource = ref<any>([]);
    const pagination = ref<any>({
      name: '',
      showQuickJumper: true,
      showSizeChanger: true,
      current: 1,
      total: 0,
      showTotal: total => `Total ${total} items`,
      pageSize: 20,
    });
    const resetPagination = () => {
      pagination.value.sourceId = '';
      pagination.value.username = '';
      pagination.value.name = '';
      pagination.value.notes = '';
      pagination.value.proxy = null;
      pagination.value.tags = [];
      pagination.value.created_at = [];

      fetchFBAccounts();
    };
    interface Query {
      source_id?: string;
      username?: string;
      notes?: string;
      name?: string;
      proxy?: {};
      proxy_id: '';
      pageNo: number;
      pageSize: number;
      sortOrder?: string;
      sortField?: string;
      date_start?: string;
      date_end?: string;
      tags?: string;
    }
    const fetchFBAccounts = () => {
      loading.value = true;
      let sortParam = '';
      if (sortedInfo?.value?.order === 'ascend') sortParam = 'asc';
      else if (sortedInfo?.value?.order === 'descend') sortParam = 'desc';
      const param = {
        pageNo: pagination.value.current,
        pageSize: pagination.value.pageSize,
      } as Query;
      if (sortedInfo?.value?.order) {
        param.sortOrder = sortParam;
        param.sortField = sortedInfo?.value?.field;
      }
      if (pagination.value.tags && pagination.value.tags.length > 0) {
        param.tags = pagination.value.tags.join(',');
      }
      if (pagination.value.sourceId) {
        param.source_id = pagination.value.sourceId;
      }
      if (pagination.value.created_at && pagination.value.created_at.length === 2) {
        param.date_start = dayjs(pagination.value.created_at[0]).format('YYYY-MM-DDTHH:mm:ss ZZ');
        param.date_end = dayjs(pagination.value.created_at[1]).format('YYYY-MM-DDTHH:mm:ss ZZ');
      }
      if (pagination.value.proxy) {
        param.proxy_id = pagination.value.proxy;
      }
      if (pagination.value.name) {
        param.name = pagination.value.name;
      }
      if (pagination.value.username) {
        param.username = pagination.value.username;
      }
      if (pagination.value.notes) {
        param.notes = pagination.value.notes;
      }

      queryFBAccountsApi(param)
        .then((res: any) => {
          dataSource.value = res.data;
          pagination.value.total = res.totalCount;
          state.selectedRowKeys = [];
        })
        .finally(() => {
          loading.value = false;
        });
    };

    const deleteOne = (data: any) => {
      loading.value = true;
      deletFBAccountsApi(data.id).then(() => {
        message.success(t('pages.opSuccessfully'));
        fetchFBAccounts();
      });
    };
    const { toClipboard } = useClipboard();
    const copyCell = async (text: any) => {
      try {
        await toClipboard(text);
        message.success(t('pages.linkCopiedw'));
      } catch (e) {
        console.error(e);
      }
    };
    interface TagOption {
      label: string;
      value: string;
    }
    const tagsData = ref<any>([]);
    const tagOptions = ref<TagOption[]>([]);
    const proxiesData = ref<any>([]);
    const proxiesLoading = ref<boolean>(false);
    const proxiesOptions = ref<TagOption[]>([]);
    watch(proxiesData, newValue => {
      proxiesOptions.value = newValue.map(proxy => ({
        label: proxy.protocol + '://' + proxy.host + ':' + proxy.port,
        value: proxy.id,
      }));
    });
    watch(tagsData, newValue => {
      tagOptions.value = newValue.map(tag => ({
        label: tag.name,
        value: tag.name,
      }));
    });
    const inputVisible = ref<boolean>(false);
    const inputValue = ref<string>('');
    const inputRef = ref();
    const handleClose = (tag: any) => {
      const index = tagsData.value.findIndex(i => i.name === tag.name);
      tagsData.value.splice(index, 0, tag);
      Modal.confirm({
        title: t('pages.hint'),
        content: t('pages.doubleConfirmDel'),
        okText: t('pages.confirm'),
        cancelText: t('pages.cancel'),
        wrapClassName: 'confirm-dialog',
        onOk() {
          deleteTagsOneApi(tag.id)
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
      const index = dynamicValidateForm.value.tags.findIndex(i => i.name === tag.name);
      if (index >= 0) {
        dynamicValidateForm.value.tags.splice(index, 1);
      } else {
        dynamicValidateForm.value.tags.push(tag);
      }
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
          message.success(t('pages.opSuccessfully'));
          tags.push(res);
          inputVisible.value = false;
          tagsData.value = tags;
          inputValue.value = '';
        });
      }
    };

    const open = ref<boolean>(false);
    const dialogTitle = ref<string>('');
    const formRef = ref<any>();
    const resetForm = {
      id: 0,
      twofa_key: '',
      proxy_id: '',
      useragent: '',
      token: '',
      cookies: '',
      notes: '',
      tags: [],
    };
    const dynamicValidateForm = ref<any>(cloneDeep(resetForm));
    const showModal = (data: any) => {
      if (data) {
        dynamicValidateForm.value = cloneDeep(data);
        dynamicValidateForm.value.proxy_id = data?.proxy?.id;
      } else {
        dynamicValidateForm.value = cloneDeep(resetForm);
      }
      dialogTitle.value = data ? t('pages.edit') : t('pages.add');
      open.value = true;
    };
    const handleOk = () => {
      if (!submitting.value) {
        formRef.value.validateFields().then(() => {
          const params = {
            ...dynamicValidateForm.value,
          };

          params.tag_ids = params.tags.map(obj => obj.id);
          delete params.tags;
          delete params.proxy;

          submitting.value = true;
          addFBAccountsOneApi(params)
            .then(() => {
              message.success(t('pages.opSuccessfully'));
              fetchFBAccounts();
              open.value = false;
            })
            .finally(() => {
              submitting.value = false;
            });
        });
      }
    };
    watch(
      () => ({ ...pagination.value }),
      (cur, pre) => {
        if (cur.current !== pre.current || cur.pageSize !== pre.pageSize) {
          fetchFBAccounts();
        }
      },
    );

    const handleSwitchChange = (checked, record) => {
      const params = {
        token_valid: checked,
      };
      setTokenValid(record.id, params).then((res: any) => {
        if (res?.message === 'Token valid status updated successfully') fetchFBAccounts();
      });
    };

    const syncResource = record => {
      syncResources(record.id).then((res: any) => {
        if (res && res.success && res.message) {
          message.success(res.message);
        }
      });
    };
    const handleProxyChange = value => {
      dynamicValidateForm.value.proxy_id = value;
    };
    const reloadProxies = () => {
      proxiesLoading.value = true;
      const param = {
        pageNo: 1,
        pageSize: 9999,
      } as Query;
      queryProxiesApi(param).then(res => {
        proxiesData.value = res.data;
        proxiesLoading.value = false;
      });
    };

    // const rowSelection: STableProps['rowSelection'] = {
    //   onChange: (_selectedRowKeys: string[], selectedRows: any[]) => {
    //     console.log(`selectedRowKeys: ${_selectedRowKeys}`, 'selectedRows: ', selectedRows);
    //     selectedRowKeys.value = _selectedRowKeys;
    //   },
    // };
    const onSelectChange = (_selectedRowKeys: string[]) => {
      console.log('selectedRowKeys changed: ', _selectedRowKeys);
      state.selectedRowKeys = _selectedRowKeys;
    };

    onMounted(() => {
      fetchFBAccounts();
      queryTagsApi().then(res => {
        tagsData.value = res.data;
      });
      queryProxiesApi().then(res => {
        proxiesData.value = res.data;
      });
    });
    return {
      dataSource,
      loading,
      fetchFBAccounts,
      resetPagination,
      dayjs,
      pagination,
      deleteOne,
      columns,
      copyCell,
      t,
      open,
      showModal,
      handleOk,
      formRef,
      dialogTitle,
      dynamicValidateForm,
      tagsData,
      showInput,
      inputVisible,
      inputValue,
      handleClose,
      handleInputConfirm,
      inputRef,
      changeTag,
      handleTableChange,
      tagOptions,
      proxiesOptions,
      handleSwitchChange,
      syncResource,
      handleProxyChange,
      proxiesLoading,
      reloadProxies,
      expand,
      onSelectChange,
      ...toRefs(state),
      tableHeight,
    };
  },
});
</script>
<style>
.container {
  display: flex;
  align-items: center;
}
</style>
