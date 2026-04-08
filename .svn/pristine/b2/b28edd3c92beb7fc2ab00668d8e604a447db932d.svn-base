<template>
  <div>
    <a-button
      v-if="!hideTriggerButton"
      type="primary"
      @click="onCreate"
    >
      {{ t('Upload') }}
    </a-button>

    <a-modal
      v-model:open="open"
      :title="getModalTitle()"
      @cancel="handleCancel"
      @ok="handleOk"
      :confirm-loading="loading"
      :z-index="1040"
    >
      <a-form
        ref="formRef"
        :label-col="{ span: 4 }"
        name="dynamic_form_nest_item"
        :model="modelRef"
      >
        <a-form-item name="name" :label="t('pages.name')" :rules="[{ required: true }]">
          <a-input v-model:value="modelRef.name" />
        </a-form-item>
        <a-form-item name="file" :label="t('pages.file')" :rules="[{ required: true }]">
          <a-upload-dragger
            v-model:file-list="modelRef.file"
            name="file"
            :max-count="1"
            list-type="text"
            :show-upload-list="true"
            :before-upload="beforeUpload"
            :custom-request="customRequest"
            @remove="handleRemoveFile"
            class="upload-dragger"
          >
            <p class="ant-upload-drag-icon">
              <inbox-outlined></inbox-outlined>
            </p>
            <p class="ant-upload-text">
              {{ getUploadText() }}
            </p>
            <p v-if="modelRef.file && modelRef.file.length > 0" class="ant-upload-hint">
              {{ t('Drop a new file here to replace the current one') }}
            </p>
          </a-upload-dragger>
        </a-form-item>
        <a-form-item name="notes" :label="t('pages.proxies.notes')">
          <a-input v-model:value="modelRef.notes" />
        </a-form-item>
      </a-form>
    </a-modal>
  </div>
</template>

<script lang="ts">
import { defineComponent, reactive, ref, watch } from 'vue';
import { message } from 'ant-design-vue';
import { useI18n } from 'vue-i18n';
import type { UploadProps } from 'ant-design-vue/es/vc-upload/interface';
import { InboxOutlined } from '@ant-design/icons-vue';
import { addMaterialsOneApi } from '@/api/materials';
import { Form } from 'ant-design-vue';

export default defineComponent({
  name: 'UploadMaterial',
  components: {
    InboxOutlined,
  },
  props: {
    hideTriggerButton: {
      type: Boolean,
      default: false,
    },
    mediaType: {
      type: String as () => 'image' | 'video' | 'all',
      default: 'all',
    },
    autoNameFormat: {
      type: Object as () => { name?: string; notes?: string } | null,
      default: () => null,
    },
  },
  emits: ['confirm:uploaded'],
  setup(props, { emit }) {
    const { t } = useI18n();
    const loading = ref(false);
    const inputValue = ref('');
    const formRef = ref<any>();
    const open = ref(false);
    const useForm = Form.useForm;

    const formItems = ref([
      {
        label: t('Active'),
        field: 'active',
        options: [
          { label: 'True', value: true },
          { label: 'False', value: false },
        ],
        value: true,
        mode: 'radio',
      },
      { label: t('Bm ID'), field: 'bm_id' },
      { label: t('Name'), field: 'name' },
      { label: t('Token'), field: 'token' },
      { label: t('Notes'), field: 'notes' },
    ]);

    // 根据媒体类型获取允许的文件格式
    const getAllowedFormats = () => {
      switch (props.mediaType) {
        case 'image':
          return ['image/jpeg', 'image/jpg', 'image/png', 'image/gif'];
        case 'video':
          return ['video/mp4', 'video/mov', 'video/avi', 'video/wmv'];
        case 'all':
        default:
          return ['application/zip', 'image/jpeg', 'image/jpg', 'image/png', 'video/mp4'];
      }
    };

    // 获取模态框标题
    const getModalTitle = () => {
      switch (props.mediaType) {
        case 'image':
          return t('Upload Image');
        case 'video':
          return t('Upload Video');
        default:
          return t('Upload file');
      }
    };

    // 获取上传提示文本
    const getUploadText = () => {
      switch (props.mediaType) {
        case 'image':
          return t('Click or drag image file to this area to upload') + ' (JPG, PNG, GIF)';
        case 'video':
          return t('Click or drag video file to this area to upload') + ' (MP4, MOV, AVI, WMV)';
        default:
          return t('Click or drag file to this area to upload');
      }
    };

    const beforeUpload: UploadProps['beforeUpload'] = file => {
      const validFormats = getAllowedFormats();
      const isValidFormat = validFormats.includes(file.type);
      if (!isValidFormat) {
        let errorMessage = '';
        if (props.mediaType === 'image') {
          errorMessage = t('Please select a valid image file') + ' (JPG, PNG, GIF)';
        } else if (props.mediaType === 'video') {
          errorMessage = t('Please select a valid video file') + ' (MP4, MOV, AVI, WMV)';
        } else {
          errorMessage = t('Please select a valid file');
        }
        message.error(errorMessage);
        return false;
      }

      return false; // 阻止自动上传，我们会在表单提交时处理
    };

    const modelRef = reactive({
      name: '',
      file: null,
      notes: '',
    });

    const rulesRef = reactive({
      name: [
        {
          required: true,
          message: t('Please input name'),
        },
      ],
    });

    const { resetFields, validate } = useForm(modelRef, rulesRef);

    // 监听autoNameFormat的变化，自动填充表单
    watch(() => props.autoNameFormat, (newFormat) => {
      if (newFormat?.name) {
        modelRef.name = newFormat.name;
      }
      if (newFormat?.notes) {
        modelRef.notes = newFormat.notes;
      }
    }, { immediate: true });

    const onCreate = () => {
      showModal();
    };

    const showModal = () => {
      // 每次打开时应用自动命名格式
      if (props.autoNameFormat?.name) {
        modelRef.name = props.autoNameFormat.name;
      }
      if (props.autoNameFormat?.notes) {
        modelRef.notes = props.autoNameFormat.notes;
      }
      open.value = true;
    };

    const handleCancel = () => {
      open.value = false;
    };

    const handleOk = () => {
      // 在这里处理确认逻辑
      loading.value = true;
      console.log('formRef: ', formRef);
      validate().then(() => {
        const params = {
          ...modelRef,
        };
        params.file = modelRef.file[0];
        addMaterialsOneApi(params)
          .then((response) => {
            // 传递上传成功的材料数据，但不显示成功消息
            emit('confirm:uploaded', response.data);
          })
          .finally(() => {
            resetFields();
            loading.value = false;
            open.value = false;
          });
      }).catch(() => {
        loading.value = false;
      });
    };

    const customRequest = (options: any) => {
      // 设置文件状态为done，避免显示loading
      if (options.onSuccess) {
        options.onSuccess({}, options.file);
      }
    };

    const handleRemoveFile = (file: any) => {
      // 从文件列表中移除文件
      if (modelRef.file && Array.isArray(modelRef.file)) {
        const index = modelRef.file.findIndex(f => f.uid === file.uid);
        if (index > -1) {
          modelRef.file.splice(index, 1);
        }
      }
      return true; // 返回 true 允许删除
    };

    return {
      open,
      inputValue,
      formItems,
      loading,
      onCreate,
      showModal,
      handleCancel,
      handleOk,
      t,
      beforeUpload,
      customRequest,
      modelRef,
      formRef,
      getModalTitle,
      getUploadText,
      handleRemoveFile,
    };
  },
});
</script>

<style scoped>
.upload-dragger {
  margin-bottom: 16px;
}

.upload-dragger :deep(.ant-upload-list) {
  margin-top: 16px;
}

.upload-dragger :deep(.ant-upload-list-item) {
  padding: 12px 16px;
  background: #fafafa;
  border-radius: 6px;
  border: 1px solid #d9d9d9;
}

.upload-dragger :deep(.ant-upload-list-item:hover) {
  background: #f0f8ff;
  border-color: #1890ff;
}

.upload-dragger :deep(.ant-upload-list-item-name) {
  color: rgba(0, 0, 0, 0.85);
  font-weight: 500;
}

.upload-dragger :deep(.ant-upload-list-item-actions) {
  opacity: 1;
}

.ant-upload-hint {
  font-size: 12px;
  color: #1890ff;
  margin-top: 8px;
  margin-bottom: 0;
}
</style>
