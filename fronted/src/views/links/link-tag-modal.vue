<template>
  <a-modal
    :open="open"
    title="标签"
    :confirm-loading="loading"
    @cancel="$emit('cancel')"
    @ok="handleSubmit"
  >
    <a-form :label-col="{ span: 4 }">
      <a-form-item label="操作">
        <a-radio-group v-model:value="action" button-style="solid">
          <a-radio-button value="add">增加</a-radio-button>
          <a-radio-button value="delete">删除</a-radio-button>
        </a-radio-group>
      </a-form-item>
      <a-form-item label="Tags">
        <tag-select
          v-model:modelValue="selectedTagOptionIds"
          :tag-folders="metaTagTree.tagFolders"
          :tags="metaTagTree.tags"
          :tag-options="metaTagTree.tagOptions"
          placeholder="请选择标签"
          :creatable="false"
        />
      </a-form-item>
    </a-form>
  </a-modal>
</template>

<script lang="ts">
import type { PropType } from 'vue';
import { defineComponent, reactive, ref, watch } from 'vue';
import { message } from 'ant-design-vue';
import TagSelect from '@/components/tag-select/index.vue';
import { getMetaTagsTree } from '@/api/promotion';
import { updateLinkTagsApi } from '@/api/links';
import type { LinkModel } from '@/utils/fb-interfaces';
import {
  normalizeMetaTagTreePayload,
  resolveNamesByOptionIds,
  resolveOptionIdsByNames,
  type LinkMetaTagTreePayload,
} from './tag-select-utils';

export default defineComponent({
  name: 'LinkTagModal',
  components: {
    TagSelect,
  },
  props: {
    open: { type: Boolean, required: true },
    model: {
      type: Object as PropType<LinkModel | null>,
      default: null,
    },
  },
  emits: ['cancel', 'ok'],
  setup(props, { emit }) {
    const loading = ref(false);
    const action = ref<'add' | 'delete'>('add');
    const selectedTagOptionIds = ref<number[]>([]);
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
        action.value = 'add';
        await loadMetaTags();
        const currentTagNames = Array.isArray(props.model?.tags)
          ? props.model?.tags.map((item: any) => String(item?.name ?? '')).filter(Boolean)
          : [];
        selectedTagOptionIds.value = resolveOptionIdsByNames(currentTagNames, metaTagTree.tagOptions);
      },
      { immediate: true }
    );

    const handleSubmit = async () => {
      if (!props.model?.id) {
        return;
      }

      const selectedNames = resolveNamesByOptionIds(selectedTagOptionIds.value, metaTagTree.tagOptions);

      if (!selectedNames.length) {
        message.warning('请至少填写一个标签');
        return;
      }

      try {
        loading.value = true;
        await updateLinkTagsApi(props.model.id, {
          action: action.value,
          names: selectedNames,
        });
        message.success('标签更新成功');
        emit('ok');
      } catch (err: any) {
        message.error(err?.response?.data?.message || err?.message || '标签更新失败');
      } finally {
        loading.value = false;
      }
    };

    return {
      loading,
      action,
      metaTagTree,
      selectedTagOptionIds,
      handleSubmit,
    };
  },
});
</script>
