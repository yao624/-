<template>
  <page-container :title="t('pages.materials.title')">
    <a-card mb-4>
      <a-row :gutter="[15, 0]" v-if="expand">
        <a-col :span="6">
          <a-form-item :label="t('pages.name')">
            <a-input v-model:value="pagination.name" />
          </a-form-item>
        </a-col>
        <a-col :span="6">
          <a-form-item :label="t('pages.notes')">
            <a-input v-model:value="pagination.notes" />
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
      </a-row>

      <a-row :gutter="[15, 0]" v-if="expand">
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
          <a-button :loading="loading" type="primary" @click="fetchProxies">
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
        <template v-if="column['dataIndex'] === 'url'">
          <template v-if="isImage(record.filename)">
            <img
              :src="text"
              :alt="record.filename"
              width="50"
              height="50"
              @click="handlePreview(text, 'image')"
            />
          </template>
          <template v-else-if="isVideo(record.filename)">
            <a class="href-btn" @click="handlePreview(text, 'video')">
              {{ t('pages.preview.video') }}
            </a>
          </template>
        </template>
        <template v-if="['tags'].includes(`${column['dataIndex']}`)">
          <a-tag v-for="m in text" :key="m.id">{{ m.name }}</a-tag>
        </template>
        <template v-if="['created_at', 'updated_at'].includes(`${column['dataIndex']}`)">
          <span>{{ dayjs(text).format('YYYY-MM-DD HH:mm:ss') }}</span>
        </template>
        <template v-if="column['dataIndex'] === 'operation'">
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

    <a-modal v-if="open" v-model:open="open" :title="dialogTitle" :width="800">
      <a-form
        ref="formRef"
        :label-col="{ span: 4 }"
        name="dynamic_form_nest_item"
        :model="dynamicValidateForm"
      >
        <a-form-item name="name" :label="t('pages.name')" :rules="[{ required: true }]">
          <a-input v-model:value="dynamicValidateForm.name" />
        </a-form-item>
        <a-form-item name="file" :label="t('pages.file')" :rules="[{ required: true }]">
          <a-upload-dragger
            v-model:file-list="dynamicValidateForm.file"
            name="file"
            :max-count="1"
            list-type="picture-card"
            :show-upload-list="false"
            :before-upload="beforeUpload"
            :custom-request="customRequest"
          >
            <p class="ant-upload-drag-icon">
              <inbox-outlined></inbox-outlined>
            </p>
            <p class="ant-upload-text">Click or drag file to this area to upload</p>
          </a-upload-dragger>
          <div v-if="dynamicValidateForm.file?.length">
            <p>Selected Files:</p>
            <ul>
              <li v-for="file in dynamicValidateForm.file" :key="file.uid">{{ file.name }}</li>
            </ul>
          </div>
        </a-form-item>
        <a-form-item name="notes" :label="t('pages.proxies.notes')">
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

      <template #footer>
        <div>
          <a-button v-if="!submitting" @click="open = false">Cancel</a-button>
          <a-button v-else :key="'submitting'" type="primary" disabled>SUMMITTING</a-button>
          <a-button
            v-if="!submitting"
            @click="handleOk"
            key="submit"
            type="primary"
            :loading="submitting"
          >
            OK
          </a-button>
        </div>
      </template>
    </a-modal>
  </page-container>
</template>
<script lang="ts">
import dayjs from 'dayjs';
import { cloneDeep } from 'lodash';
import { computed, defineComponent, ref, onMounted, watch, nextTick, h } from 'vue';
import type { UploadProps } from 'ant-design-vue';
import { message, Modal } from 'ant-design-vue';
import { CopyOutlined, PlusOutlined, UpOutlined, DownOutlined } from '@ant-design/icons-vue';
import { queryTagsApi, addTagsOneApi, deleteTagsOneApi } from '@/api/networks';
import { materialsList, addMaterialsOneApi, deletOneMaterialsApi } from '@/api/materials';
import { useI18n } from 'vue-i18n';
import useClipboard from 'vue-clipboard3';
import 'viplayer/dist/index.css';
import { videoPlay } from 'viplayer';

export default defineComponent({
  components: {
    videoPlay,
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
      const sorted = sortedInfo.value || {};
      return [
        {
          title: t('pages.name'),
          dataIndex: 'name',
        },
        {
          title: t('pages.filename'),
          dataIndex: 'filename',
        },
        {
          title: 'URL',
          dataIndex: 'url',
        },
        {
          title: t('pages.proxies.notes'),
          dataIndex: 'notes',
        },
        {
          title: t('pages.createdAt'),
          dataIndex: 'created_at',
          sorter: true,
          sortOrder: sorted.field === 'created_at' && sorted.order,
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
      fetchProxies();
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
      pagination.value.notes = '';
      pagination.value.tags = [];
      pagination.value.created_at = [];
      sortedInfo.value = null;
      fetchProxies();
    };
    interface Query {
      name?: string;
      notes?: string;
      date_start?: string;
      date_end?: string;
      pageNo: number;
      pageSize: number;
      sortOrder?: string;
      sortField?: string;
      tags?: string;
    }
    const fetchProxies = () => {
      loading.value = true;
      let sortParam = '';
      if (sortedInfo?.value?.order === 'ascend') sortParam = 'asc';
      else if (sortedInfo?.value?.order === 'descend') sortParam = 'desc';
      const param = {
        name: pagination.value.name,
        notes: pagination.value.notes,
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

      materialsList(param)
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
      deletOneMaterialsApi(data.id).then(() => {
        message.success(t('pages.opSuccessfully'));
        fetchProxies();
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
    interface UploadForm {
      id: number;
      name: string;
      file: File | null;
      notes: string;
      tags: string[];
    }
    const resetForm: UploadForm = {
      id: 0,
      name: '',
      file: null,
      notes: '',
      tags: [],
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
          const params = {
            ...dynamicValidateForm.value,
          };
          params.file = dynamicValidateForm.value.file[0];
          params.tag_ids = params.tags.map(obj => obj.id);
          submitting.value = true;
          addMaterialsOneApi(params)
            .then(() => {
              message.success(t('pages.opSuccessfully'));
              fetchProxies();
              open.value = false;
            })
            .finally(() => {
              submitting.value = false;
            });
        });
      }
    };

    const beforeUpload: UploadProps['beforeUpload'] = file => {
      const validFormats = ['application/zip', 'image/jpeg', 'image/jpg', 'image/png', 'video/mp4'];
      const isValidFormat = validFormats.includes(file.type);
      if (!isValidFormat) {
        message.error(t('pages.file.upload.error'));
      }
      return isValidFormat;
    };

    const customRequest = () => {
      //Do nothing , will upload while form submitting
    };

    const isImage = filename => {
      const imageExtensions = ['jpeg', 'jpg', 'png', 'gif'];
      const extension = filename.split('.').pop().toLowerCase();
      return imageExtensions.includes(extension);
    };
    const isVideo = filename => {
      const videoExtensions = ['mp4', 'mov', 'avi', 'wmv', 'zip'];
      const extension = filename.split('.').pop().toLowerCase();
      return videoExtensions.includes(extension);
    };

    const handlePreview = (src: string, type: string) => {
      const title = type === 'image' ? 'Image Preview' : 'Video Preview';
      let contentNode;

      if (type === 'image') {
        contentNode = h('img', { src, style: 'max-width: 100%;' });
      } else if (type === 'video') {
        contentNode = h(videoPlay, { src, width: '750px', autoPlay: true });
      }
      const modalContent = () => h('div', { style: 'text-align: center;' }, [contentNode]);

      Modal.info({
        title: title,
        content: modalContent,
        maskClosable: true,
        icon: null,
        width: 800,
      });
    };

    watch(
      () => ({ ...pagination.value }),
      (cur, pre) => {
        if (cur.current !== pre.current || cur.pageSize !== pre.pageSize) {
          fetchProxies();
        }
      },
    );
    onMounted(() => {
      fetchProxies();
      queryTagsApi().then(res => {
        tagsData.value = res.data;
      });
    });
    return {
      dataSource,
      loading,
      fetchProxies,
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
      beforeUpload,
      customRequest,
      isImage,
      isVideo,
      handlePreview,
      submitting,
      expand,
      tableHeight,
    };
  },
});
</script>
