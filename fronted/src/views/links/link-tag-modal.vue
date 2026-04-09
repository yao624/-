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
        <a-select
          v-model:value="selectedTags"
          mode="tags"
          :options="tagOptions"
          placeholder="请输入或选择标签"
          style="width: 100%"
        />
      </a-form-item>
    </a-form>
  </a-modal>
</template>

<script lang="ts">
import type { PropType } from 'vue';
import { defineComponent, ref, watch } from 'vue';
import { message } from 'ant-design-vue';
import { getLinksValidTags, updateLinkTagsApi } from '@/api/links';
import type { LinkModel } from '@/utils/fb-interfaces';

type TagOption = { label: string; value: string };

export default defineComponent({
  name: 'LinkTagModal',
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
    const selectedTags = ref<string[]>([]);
    const tagOptions = ref<TagOption[]>([]);

    const loadTags = async () => {
      const res: any = await getLinksValidTags();
      const list = Array.isArray(res?.data) ? res.data : [];
      tagOptions.value = list.map((tag: any) => ({
        label: tag.user_name ? `${tag.name} - ${tag.user_name}` : tag.name,
        value: tag.name,
      }));
    };

    watch(
      () => props.open,
      async (value) => {
        if (!value) return;
        action.value = 'add';
        selectedTags.value = [];
        await loadTags();
      },
      { immediate: true }
    );

    const handleSubmit = async () => {
      if (!props.model?.id) {
        return;
      }

      if (!selectedTags.value.length) {
        message.warning('请至少填写一个标签');
        return;
      }

      try {
        loading.value = true;
        await updateLinkTagsApi(props.model.id, {
          action: action.value,
          names: selectedTags.value,
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
      selectedTags,
      tagOptions,
      handleSubmit,
    };
  },
});
</script>
