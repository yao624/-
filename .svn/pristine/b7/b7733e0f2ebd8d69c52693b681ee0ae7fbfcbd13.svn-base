<template>
  <page-container :title="t('pages.networks.title')">
    <a-card mb-4>
      <a-row :gutter="[15, 0]" v-if="expand">
        <a-col :span="6">
          <a-form-item :label="t('pages.name')">
            <a-input v-model:value="pagination.name" />
          </a-form-item>
        </a-col>
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
        <a-col :span="12">
          <a-button type="primary" @click="showModal('')">
            <template #icon>
              <plus-outlined />
            </template>
            {{ t('pages.add') }}
          </a-button>
        </a-col>
        <a-space>
          <a-button :loading="loading" type="primary" @click="fetchAccount">
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
      :pagination="pagination"
      :loading="loading"
      :row-key="record => record.id"
    >
      <template #bodyCell="{ column, text, record }">
        <template v-if="column['dataIndex'] === 'active'">
          <span>{{ text === false ? t('pages.deActive') : t('pages.active') }}</span>
        </template>
        <template v-if="['endpoint'].includes(`${column['dataIndex']}`)">
          <!-- <span>{{ text }}</span> -->
          <a :href="text" target="_blank" v-html="text"></a>
          <copy-outlined @click="copyCell(text)" />
        </template>
        <template v-if="['tags'].includes(`${column['dataIndex']}`)">
          <a-tag v-for="m in text" :key="m.id">{{ m.name }}</a-tag>
        </template>
        <template v-if="['created_at', 'updated_at'].includes(`${column['dataIndex']}`)">
          <span>{{ dayjs(text).format('YYYY-MM-DD HH:mm:ss') }}</span>
        </template>
        <template v-if="column['dataIndex'] === 'operation'">
          <a @click="openSyncNetworkModal(record.id)">{{ t('pages.networks.fetchData') }}</a>
          <a-divider type="vertical"></a-divider>
          <a class="href-btn" @click="showModal(record)">{{ t('pages.edit') }}</a>
          <a-divider type="vertical"></a-divider>
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
          name="name"
          :label="t('pages.name')"
          :rules="[{ required: true, message: 'Missing Name' }]"
        >
          <a-input v-model:value="dynamicValidateForm.name" />
        </a-form-item>
        <a-form-item
          name="system_type"
          :label="t('pages.networks.system_type')"
          :rules="[{ required: true, message: 'Missing SystemType' }]"
        >
          <a-select v-model:value="dynamicValidateForm.system_type">
            <a-select-option value="Cake">Cake</a-select-option>
            <a-select-option value="Everflow">Everflow</a-select-option>
            <a-select-option value="Jumb">Jumb</a-select-option>
            <a-select-option value="Keitaro">Keitaro</a-select-option>
          </a-select>
        </a-form-item>
        <a-form-item
          name="aff_id"
          :label="'Aff ID'"
          :rules="[{ required: true, message: 'Missing Aff ID' }]"
        >
          <a-input v-model:value="dynamicValidateForm.aff_id" />
        </a-form-item>
        <a-form-item
          name="endpoint"
          :label="'URL'"
          :rules="[{ required: true, message: 'Missing URL' }]"
        >
          <a-input v-model:value="dynamicValidateForm.endpoint" />
        </a-form-item>
        <a-form-item
          name="apikey"
          :label="'Apikey'"
          :rules="[{ required: true, message: 'Missing Apikey' }]"
        >
          <a-input v-model:value="dynamicValidateForm.apikey" />
          <a-button
            type="link"
            size="small"
            :loading="testingConnection"
            @click="testConnection"
            style="padding: 0; margin-top: 4px"
          >
            {{ t('测试连接') }}
          </a-button>
        </a-form-item>
        <a-form-item
          name="active"
          :label="'Active'"
          :rules="[{ required: true, message: 'Missing active' }]"
        >
          <a-switch v-model:checked="dynamicValidateForm.active" />
        </a-form-item>
        <a-form-item
          name="click_placeholder"
          :label="'Click Placeholder'"
          :rules="[{ required: true, message: 'Missing Click Placeholder' }]"
        >
          <a-input v-model:value="dynamicValidateForm.click_placeholder" />
        </a-form-item>
        <a-form-item name="tags" :label="'Tags'">
          <!--:rules="[{ required: true, message: 'Missing Tags' }]" -->
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

    <a-modal
      v-model:open="syncNetworkModal"
      title="选择时间"
      ok-text="Create"
      cancel-text="Cancel"
      @ok="handleSyncNetwork"
    >
      <a-form
        ref="createCardFormRef"
        :model="syncNetworkFormData"
        name="form_in_modal"
        :label-col="{ span: 4 }"
      >
        <a-form-item name="date_range" label="Datetime" :rules="[{ required: false, message: '' }]">
          <a-range-picker v-model:value="date_range" />
        </a-form-item>
      </a-form>
    </a-modal>
  </page-container>
</template>
<script lang="ts">
import dayjs from 'dayjs';
import { cloneDeep } from 'lodash';
import { computed, defineComponent, ref, onMounted, watch, nextTick, h } from 'vue';
import { message, Modal } from 'ant-design-vue';
import { CopyOutlined, PlusOutlined, UpOutlined, DownOutlined } from '@ant-design/icons-vue';
import {
  queryNetworksApi,
  addNetworksOneApi,
  deleteNetworksOneApi,
  queryTagsApi,
  addTagsOneApi,
  deleteTagsOneApi,
  fetchAll,
  testNetworkConnection,
} from '@/api/networks';
import { useI18n } from 'vue-i18n';
import useClipboard from 'vue-clipboard3';
import type { Dayjs } from 'dayjs';

export default defineComponent({
  components: {
    CopyOutlined,
    PlusOutlined,
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
      return [
        {
          title: t('pages.name'),
          dataIndex: 'name',
          width: 100,
        },
        {
          title: t('pages.networks.system_type'),
          dataIndex: 'system_type',
          width: 150,
        },
        {
          title: 'Aff ID',
          dataIndex: 'aff_id',
          width: 100,
        },
        {
          title: 'URL',
          dataIndex: 'endpoint',
          resizable: true,
          width: 300,
        },
        {
          title: t('pages.networks.clickPlaceholder'),
          dataIndex: 'click_placeholder',
        },
        {
          title: t('pages.tag'),
          dataIndex: 'tags',
        },
        {
          title: 'Status',
          dataIndex: 'active',
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
      fetchAccount();
    };
    const loading = ref(true);
    const submitting = ref(false);
    const testingConnection = ref(false);
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
      pagination.value.name = '';
      pagination.value.tags = [];
      pagination.value.created_at = [];
      sortedInfo.value = null;
      fetchAccount();
    };
    interface NetworkQuery {
      name: string;
      date_start?: string;
      date_end?: string;
      pageNo: number;
      pageSize: number;
      sortOrder?: string;
      sortField?: string;
      tags?: string;
    }
    const fetchAccount = () => {
      loading.value = true;
      let sortParam = '';
      if (sortedInfo?.value?.order === 'ascend') sortParam = 'asc';
      else if (sortedInfo?.value?.order === 'descend') sortParam = 'desc';
      const param = {
        name: pagination.value.name,
        pageNo: pagination.value.current,
        pageSize: pagination.value.pageSize,
      } as NetworkQuery;
      if (sortedInfo?.value?.order) {
        param.sortOrder = sortParam;
        param.sortField = sortedInfo?.value?.field;
      }
      if (pagination.value.tags && pagination.value.tags.length > 0) {
        param.tags = pagination.value.tags.join(',');
      }
      if (pagination.value.created_at && pagination.value.created_at.length === 2) {
        param.date_start = dayjs(pagination.value.created_at[0]).format('YYYY-MM-DDTHH:mm:ss ZZ');
        param.date_end = dayjs(pagination.value.created_at[1]).format('YYYY-MM-DDTHH:mm:ss ZZ');
      }

      queryNetworksApi(param)
        .then((res: any) => {
          dataSource.value = res.data;
          pagination.value.total = res.totalCount;
        })
        .finally(() => {
          loading.value = false;
        });
    };
    const openSyncNetworkModal = id => {
      syncNetworkFormData.value.network_ids = [id];
      syncNetworkModal.value = true;
    };
    const deleteOne = (data: any) => {
      loading.value = true;
      deleteNetworksOneApi(data.id).then(() => {
        message.success(t('pages.opSuccessfully'));
        fetchAccount();
      });
    };
    const { toClipboard } = useClipboard();
    const copyCell = async (text: any) => {
      try {
        await toClipboard(text);
        message.success(t('pages.linkCopied'));
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
            .then(res => {
              message.success(t('pages.opSuccessfully'));
              tagsData.value.splice(index, 1);
              console.error(res);
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
      name: '',
      system_type: '',
      aff_id: '',
      endpoint: '',
      click_placeholder: '',
      tags: [],
      active: true,
      apikey: '',
    };
    const dynamicValidateForm = ref<any>(cloneDeep(resetForm));
    const showModal = (data: any) => {
      if (data) {
        dynamicValidateForm.value = cloneDeep(data);
      } else {
        dynamicValidateForm.value = cloneDeep(resetForm);
      }
      dialogTitle.value = data ? t('pages.edit') : t('pages.add');
      open.value = true;
    };
    const handleOk = () => {
      if (!submitting.value) {
        formRef.value.validateFields().then(() => {
          console.error(dynamicValidateForm);
          const params = {
            ...dynamicValidateForm.value,
          };
          params.tag_ids = params.tags.map(obj => obj.id);
          delete params.tags;
          submitting.value = true;
          addNetworksOneApi(params)
            .then(() => {
              message.success(t('pages.opSuccessfully'));
              fetchAccount();
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
          fetchAccount();
        }
      },
    );
    onMounted(() => {
      fetchAccount();
      queryTagsApi().then(res => {
        tagsData.value = res.data;
      });
    });

    const syncNetworkModal = ref(false);
    const handleSyncNetwork = () => {
      console.log('sync network');
      console.log(syncNetworkFormData.value);
      fetchAll({ ...syncNetworkFormData.value })
        .then(res => {
          message.success(res['message']);
        })
        .finally(() => {
          syncNetworkModal.value = false;
        });
    };

    const syncNetworkFormData = ref({
      network_ids: [],
      date_start: undefined,
      date_stop: undefined,
      date_range: undefined,
    });
    type RangeValue = [Dayjs, Dayjs];
    const date_range = ref<RangeValue>();
    watch(
      () => date_range.value,
      newValue => {
        console.log('changed');
        if (newValue && newValue.length === 2) {
          syncNetworkFormData.value.date_start = dayjs(newValue[0]).format('YYYY-MM-DD');
          // queryParam.transaction_date_start = newValue[0];
          syncNetworkFormData.value.date_stop = dayjs(newValue[1]).format('YYYY-MM-DD');
        }
      },
      { deep: true },
    );

    // 测试连接
    const testConnection = async () => {
      if (!dynamicValidateForm.value.id) {
        message.warning(t('请先保存网络配置'));
        return;
      }

      if (!dynamicValidateForm.value.endpoint || !dynamicValidateForm.value.aff_id || !dynamicValidateForm.value.apikey) {
        message.warning(t('请填写完整的网络配置信息'));
        return;
      }

      testingConnection.value = true;
      try {
        const res = await testNetworkConnection(dynamicValidateForm.value.id);
        console.log('测试结果:', res);
        
        const response = res as any;
        if (response.success) {
          message.success(t('连接成功！') + ' ' + (response.message || ''));
          Modal.info({
            title: t('测试结果'),
            width: 600,
            content: h('div', [
              h('p', { style: 'color: #52c41a; margin-bottom: 12px' }, '✅ ' + t('连接成功')),
              h('p', { style: 'margin-bottom: 8px' }, t('消息') + ': ' + (response.message || '')),
              h('details', { style: 'margin-top: 12px' }, [
                h('summary', { style: 'cursor: pointer; color: #1890ff' }, t('查看详细信息')),
                h('pre', {
                  style: 'background: #f5f5f5; padding: 12px; border-radius: 4px; margin-top: 8px; max-height: 300px; overflow: auto; font-size: 12px',
                }, JSON.stringify(response.data || {}, null, 2)),
              ]),
            ]),
          });
        } else {
          message.error(t('连接失败：') + (response.message || ''));
          Modal.error({
            title: t('测试结果'),
            width: 600,
            content: h('div', [
              h('p', { style: 'color: #ff4d4f; margin-bottom: 12px' }, '❌ ' + t('连接失败')),
              h('p', { style: 'margin-bottom: 8px' }, t('错误信息') + ': ' + (response.message || '')),
              h('details', { style: 'margin-top: 12px' }, [
                h('summary', { style: 'cursor: pointer; color: #1890ff' }, t('查看详细信息')),
                h('pre', {
                  style: 'background: #f5f5f5; padding: 12px; border-radius: 4px; margin-top: 8px; max-height: 300px; overflow: auto; font-size: 12px',
                }, JSON.stringify(response.data || response.error || {}, null, 2)),
              ]),
            ]),
          });
        }
      } catch (error: any) {
        console.error('测试连接失败:', error);
        message.error(t('测试失败：') + (error.message || '未知错误'));
        Modal.error({
          title: t('测试结果'),
          width: 600,
          content: h('div', [
            h('p', { style: 'color: #ff4d4f; margin-bottom: 12px' }, '❌ ' + t('测试失败')),
            h('p', { style: 'margin-bottom: 8px' }, t('错误信息') + ': ' + (error.message || '未知错误')),
            h('pre', {
              style: 'background: #f5f5f5; padding: 12px; border-radius: 4px; margin-top: 8px; max-height: 300px; overflow: auto; font-size: 12px',
            }, JSON.stringify(error, null, 2)),
          ]),
        });
      } finally {
        testingConnection.value = false;
      }
    };

    return {
      dataSource,
      loading,
      fetchAccount,
      resetPagination,
      dayjs,
      pagination,
      openSyncNetworkModal,
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
      expand,
      tableHeight,

      // 同步联盟数据
      syncNetworkModal,
      handleSyncNetwork,
      syncNetworkFormData,
      date_range,
      testConnection,
      testingConnection,
    };
  },
});
</script>
