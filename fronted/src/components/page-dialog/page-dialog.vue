<template>
  <a-button type="primary" @click="showModal('')">
    <template #icon>
      <plus-outlined />
    </template>
    {{ t('pages.add') }}
  </a-button>
  <a-modal
    v-if="open"
    v-model:open="open"
    :title="dialogTitle"
    :confirm-loading="submitting"
    @ok="onConfirm"
    :width="800"
  >
    <a-form
      ref="formRef"
      :label-col="{ span: 4 }"
      name="dynamic_form_nest_item"
      :model="dynamicValidateForm"
    >
      <a-form-item name="notes" :label="t('pages.links.note')">
        <a-input v-model:value="dynamicValidateForm.notes" />
      </a-form-item>
      <a-form-item name="tags" :label="'Tags'">
        <template v-for="tag in tagsData" :key="tag">
          <a-tag
            :color="dynamicValidateForm.tags.some(i => i.name === tag.name) ? 'green' : ''"
            @close="onRemoveTag(tag)"
            @click="onChangeTag(tag)"
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
          @blur="onInputConfirm"
          @keyup.enter="onInputConfirm"
        />
        <a-tag v-else style="background: #fff; border-style: dashed" @click="showInput">
          <plus-outlined />
          New Tag
        </a-tag>
      </a-form-item>
    </a-form>
  </a-modal>
</template>

<script lang="ts">
import { PlusOutlined } from '@ant-design/icons-vue';
import { updatePagesOneApi } from '@/api/pages';
import { addTagsOneApi, deleteTagsOneApi } from '@/api/tags';
import { message, Modal } from 'ant-design-vue';
import { cloneDeep } from 'lodash';
import { defineComponent, nextTick, ref, watch } from 'vue';
import { useI18n } from 'vue-i18n';

export default defineComponent({
  name: 'PageDialog',
  components: {
    PlusOutlined,
  },
  props: {
    tagsData: {
      type: Array<any>,
      required: true,
    },
  },
  emits: ['change:tags-changed'],
  setup(props, { emit }) {
    const { t } = useI18n();
    const resetForm = {
      id: 0,
      notes: '',
      tags: [],
    };
    const open = ref<boolean>(false);
    const dialogTitle = ref<string>('');
    const formRef = ref<any>();
    const dynamicValidateForm = ref(resetForm);
    const tagsData = ref(props.tagsData);
    const inputVisible = ref<boolean>(false);
    const inputValue = ref<string>('');
    const inputRef = ref();
    const submitting = ref(false);

    watch(
      () => props.tagsData, // 监听的对象
      newValue => {
        tagsData.value = newValue;
      },
    );

    const showInput = () => {
      inputVisible.value = true;
      nextTick(() => {
        inputRef.value.focus();
      });
    };

    const onInputConfirm = () => {
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

    const showModal = (data: any) => {
      if (data) {
        dynamicValidateForm.value = cloneDeep(data);
      } else {
        dynamicValidateForm.value = cloneDeep(resetForm);
      }
      dialogTitle.value = data ? t('pages.edit') : t('pages.add');
      open.value = true;
    };

    const onRemoveTag = (tag: any) => {
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

    const onChangeTag = (tag: any) => {
      const index = dynamicValidateForm.value.tags.findIndex(i => i.name === tag.name);
      if (index >= 0) {
        dynamicValidateForm.value.tags.splice(index, 1);
      } else {
        dynamicValidateForm.value.tags.push(tag);
      }
    };

    const onConfirm = () => {
      if (!submitting.value) {
        formRef.value.validateFields().then(() => {
          const params = {
            id: dynamicValidateForm.value.id,
            notes: '',
            tag_ids: [],
          };
          params.tag_ids = dynamicValidateForm.value.tags.map(obj => obj.id);
          if (dynamicValidateForm.value.notes) params.notes = dynamicValidateForm.value.notes;

          submitting.value = true;
          updatePagesOneApi(params)
            .then(() => {
              message.success(t('pages.opSuccessfully'));
              // fetchPages();
              emit('change:tags-changed');
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
    return {
      open,
      dialogTitle,
      formRef,
      dynamicValidateForm,
      tagsData,
      inputVisible,
      inputValue,
      submitting,
      showModal,
      showInput,
      onConfirm,
      onRemoveTag,
      onChangeTag,
      onInputConfirm,
      t,
    };
  },
});
</script>
