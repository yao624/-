<template>
  <a-modal
    :open="open"
    title="批量导入链接"
    width="860px"
    :confirm-loading="loading"
    @cancel="$emit('cancel')"
    @ok="handleSubmit"
  >
    <a-alert
      type="info"
      show-icon
      message="支持两种方式：粘贴多行文本，或者上传 CSV 文件。CSV 支持表头：link, notes, tags"
      style="margin-bottom: 16px"
    />
    <a-form :label-col="{ span: 4 }">
      <a-form-item label="批量文本">
        <a-textarea
          v-model:value="rawText"
          :auto-size="{ minRows: 8, maxRows: 14 }"
          placeholder="每行一条，格式：链接,备注,标签1|标签2"
        />
      </a-form-item>
      <a-form-item label="CSV 文件">
        <a-upload :before-upload="beforeUpload" :show-upload-list="!!file">
          <a-button>选择 CSV</a-button>
        </a-upload>
        <div v-if="file" style="margin-top: 8px; color: #64748b">{{ file.name }}</div>
      </a-form-item>
      <a-form-item label="默认标签">
        <tag-select
          v-model:modelValue="defaultTagOptionIds"
          :tag-folders="metaTagTree.tagFolders"
          :tags="metaTagTree.tags"
          :tag-options="metaTagTree.tagOptions"
          placeholder="导入到所有链接的默认标签"
          :creatable="false"
        />
      </a-form-item>
      <a-form-item label="默认备注">
        <a-input v-model:value="defaultNotes" />
      </a-form-item>
      <a-form-item label="默认语言">
        <a-input v-model:value="defaultLocale" placeholder="en_US / zh_CN" />
      </a-form-item>
    </a-form>
  </a-modal>
</template>

<script lang="ts">
import { defineComponent, reactive, ref, watch } from 'vue';
import { message } from 'ant-design-vue';
import TagSelect from '@/components/tag-select/index.vue';
import { getMetaTagsTree } from '@/api/promotion';
import { importLinksApi } from '@/api/links';
import {
  normalizeMetaTagTreePayload,
  resolveNamesByOptionIds,
  type LinkMetaTagTreePayload,
} from './tag-select-utils';

export default defineComponent({
  name: 'ImportLinksModal',
  components: {
    TagSelect,
  },
  props: {
    open: { type: Boolean, required: true },
  },
  emits: ['cancel', 'ok'],
  setup(props, { emit }) {
    const loading = ref(false);
    const rawText = ref('');
    const file = ref<File | null>(null);
    const defaultTagOptionIds = ref<number[]>([]);
    const defaultNotes = ref('');
    const defaultLocale = ref('');
    const metaTagTree = reactive<LinkMetaTagTreePayload>({
      tagFolders: [],
      tags: [],
      tagOptions: [],
    });

    const loadMetaTags = async () => {
      const res: any = await getMetaTagsTree();
      const payload = normalizeMetaTagTreePayload(res?.data);
      metaTagTree.tagFolders = payload.tagFolders;
      metaTagTree.tags = payload.tags;
      metaTagTree.tagOptions = payload.tagOptions;
    };

    watch(
      () => props.open,
      async (value) => {
        if (!value) return;
        await loadMetaTags();
      },
      { immediate: true },
    );

    const beforeUpload = (selected: File) => {
      file.value = selected;
      return false;
    };

    const handleSubmit = async () => {
      if (!rawText.value.trim() && !file.value) {
        message.warning('请先粘贴内容或选择 CSV 文件');
        return;
      }

      const formData = new FormData();
      formData.append('raw_text', rawText.value);
      resolveNamesByOptionIds(defaultTagOptionIds.value, metaTagTree.tagOptions)
        .forEach((tag) => formData.append('default_tags[]', tag));
      if (defaultNotes.value) formData.append('default_notes', defaultNotes.value);
      if (defaultLocale.value) formData.append('default_locale', defaultLocale.value);
      if (file.value) formData.append('file', file.value);

      try {
        loading.value = true;
        const res: any = await importLinksApi(formData);
        const summary = res?.summary || {};
        message.success(`导入完成：新增${summary.created || 0}，更新${summary.updated || 0}，跳过${summary.skipped || 0}`);
        rawText.value = '';
        file.value = null;
        defaultTagOptionIds.value = [];
        defaultNotes.value = '';
        defaultLocale.value = '';
        emit('ok');
      } catch (err: any) {
        message.error(err?.message || '导入失败');
      } finally {
        loading.value = false;
      }
    };

    return {
      loading,
      rawText,
      file,
      defaultTagOptionIds,
      defaultNotes,
      defaultLocale,
      metaTagTree,
      beforeUpload,
      handleSubmit,
    };
  },
});
</script>
