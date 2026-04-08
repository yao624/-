<template>
  <page-container :title="t('pages.agents.title')">
    <a-card mb-4>
      <a-row :gutter="[15, 0]" v-if="expand">
        <a-col :span="6">
          <a-form-item :label="t('pages.name')">
            <a-input v-model:value="pagination.name" />
          </a-form-item>
        </a-col>
        <a-col :span="6">
          <a-form-item :label="t('pages.ip')">
            <a-input v-model:value="pagination.ip" />
          </a-form-item>
        </a-col>
        <a-col :span="6">
          <a-form-item :label="t('pages.domain')">
            <a-input v-model:value="pagination.domain" />
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
        <a-col :span="12">
          <a-button type="primary" @click="showModal('')">
            <template #icon>
              <plus-outlined />
            </template>
            {{ t('pages.add') }}
          </a-button>
        </a-col>
        <a-space>
          <a-button :loading="loading" type="primary" @click="fetchLinks">
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
    >
      <template #bodyCell="{ column, text, record }">
        <template v-if="column['dataIndex'] === 'active'">
          <span>{{ text === false ? t('pages.deActive') : t('pages.active') }}</span>
        </template>
        <template v-if="['link'].includes(`${column['dataIndex']}`)">
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
          <a class="href-btn" @click="showModal(record)">{{ t('pages.edit') }}</a>
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
        <a-form-item name="name" :label="t('pages.name')" :rules="[{ required: true }]">
          <a-input v-model:value="dynamicValidateForm.name" />
        </a-form-item>
        <a-form-item name="ip" :label="t('pages.ip')" :rules="ipRules">
          <a-input v-model:value="dynamicValidateForm.ip" />
        </a-form-item>
        <a-form-item name="domain" :label="t('pages.domain')" :rules="urlRules">
          <a-input v-model:value="dynamicValidateForm.domain">
            <template #addonBefore>
              <a-select v-model:value="dynamicValidateForm.protocol" style="width: 90px">
                <a-select-option value="http">http</a-select-option>
                <a-select-option value="https">https</a-select-option>
              </a-select>
            </template>
          </a-input>
        </a-form-item>
        <a-form-item name="port" :label="t('pages.port')" :rules="[{ required: true }]">
          <a-input v-model:value="dynamicValidateForm.port" />
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
import { computed, defineComponent, ref, onMounted, watch, nextTick, watchEffect } from 'vue';
import { message, Modal } from 'ant-design-vue';
import { CopyOutlined, PlusOutlined, UpOutlined, DownOutlined } from '@ant-design/icons-vue';
import { queryTagsApi, addTagsOneApi, deleteTagsOneApi } from '@/api/networks';
import { queryAgentsApi, addAgentsOneApi, deletAgentsApi } from '@/api/agents';
import { useI18n } from 'vue-i18n';
import useClipboard from 'vue-clipboard3';

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
    const urlRules = [
      {
        pattern: /^(https?|http):\/\/[^\s/$.?#].[^\s]*$/i,
        message: t('pages.url.not.supported'),
        trigger: 'blur',
      },
    ];

    const ipRules = [
      {
        pattern: /\b(?:\d{1,3}\.){3}\d{1,3}\b|\b(?:[0-9a-fA-F]{1,4}:){7}[0-9a-fA-F]{1,4}\b/,
        message: t('pages.ip.not.supported'),
        trigger: 'blur',
      },
    ];
    const columns = computed<any[]>(() => {
      const sorted = sortedInfo.value || {};
      return [
        {
          title: t('pages.name'),
          dataIndex: 'name',
        },
        {
          title: t('pages.ip'),
          dataIndex: 'ip',
        },
        {
          title: t('pages.domain'),
          dataIndex: 'domain',
        },
        {
          title: t('pages.port'),
          dataIndex: 'port',
        },
        {
          title: t('pages.notes'),
          dataIndex: 'notes',
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
      fetchLinks();
    };
    const loading = ref(true);
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
      pagination.value.name = '';
      pagination.value.port = '';
      pagination.value.ip = '';
      pagination.value.domain = '';
      sortedInfo.value = null;
      pagination.value.tags = [];
      pagination.value.created_at = [];
      fetchLinks();
    };
    interface Query {
      name?: string;
      date_start?: string;
      date_end?: string;
      pageNo: number;
      pageSize: number;
      sortOrder?: string;
      sortField?: string;
      tags?: string;
      port?: string;
      ip?: string;
      domain?: string;
    }
    const fetchLinks = () => {
      loading.value = true;
      let sortParam = '';
      if (sortedInfo?.value?.order === 'ascend') sortParam = 'asc';
      else if (sortedInfo?.value?.order === 'descend') sortParam = 'desc';
      const param = {
        link: pagination.value.link,
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
      if (pagination.value.created_at && pagination.value.created_at.length === 2) {
        param.date_start = dayjs(pagination.value.created_at[0]).format('YYYY-MM-DDTHH:mm:ss ZZ');
        param.date_end = dayjs(pagination.value.created_at[1]).format('YYYY-MM-DDTHH:mm:ss ZZ');
      }
      if (pagination.value.ip) {
        param.ip = pagination.value.ip;
      }
      if (pagination.value.domain) {
        param.domain = pagination.value.domain;
      }
      if (pagination.value.name) {
        param.name = pagination.value.name;
      }
      if (pagination.value.port) {
        param.port = pagination.value.port;
      }

      queryAgentsApi(param)
        .then((res: any) => {
          dataSource.value = res.data;
          pagination.value.total = res.totalCount;
        })
        .finally(() => {
          loading.value = false;
        });
    };

    const deleteOne = (data: any) => {
      loading.value = true;
      deletAgentsApi(data.id).then(() => {
        message.success(t('pages.opSuccessfully'));
        fetchLinks();
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
      ip: '',
      domain: '',
      notes: '',
      tags: [],
    };
    const dynamicValidateForm = ref<any>(cloneDeep(resetForm));
    watchEffect(() => {
      const newValue = dynamicValidateForm.value;
      if (newValue?.domain) {
        if (newValue.domain.startsWith('https')) {
          newValue.protocol = 'https';
        } else if (newValue.domain.startsWith('http')) {
          newValue.protocol = 'http';
        } else {
          newValue.protocol = '';
        }
      }
    });
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
          const params = {
            ...dynamicValidateForm.value,
          };
          params.tag_ids = params.tags.map(obj => obj.id);
          if (!params.ip && !params.domain) {
            message.error(t('pages.ipordomain.cannotbe.both.empty'));
            return;
          }
          submitting.value = true;
          addAgentsOneApi(params)
            .then(() => {
              message.success(t('pages.opSuccessfully'));
              fetchLinks();
              open.value = false;
            })
            .catch(err => {
              message.error(err.message);
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
          fetchLinks();
        }
      },
    );
    onMounted(() => {
      fetchLinks();
      queryTagsApi().then(res => {
        tagsData.value = res.data;
      });
    });
    return {
      dataSource,
      loading,
      fetchLinks,
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
      urlRules,
      ipRules,
      expand,
      tableHeight,
    };
  },
});
</script>
