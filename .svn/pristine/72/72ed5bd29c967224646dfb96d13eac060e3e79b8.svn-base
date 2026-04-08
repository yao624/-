<template>
  <a-modal
    :title="t('Tags')"
    :open="visible"
    :width="600"
    :confirmLoading="loading"
    @ok="handleSubmit"
    @cancel="
      () => {
        resetFields();
        $emit('cancel');
      }
    "
  >
    <a-spin :spinning="loading">
      <a-form :label-col="labelCol" :wrapper-col="wrapperCol">
        <a-form-item :label="t('Action')">
          <a-radio-group v-model:value="modelRef.action" button-style="solid">
            <a-radio-button value="add">{{ t('Add') }}</a-radio-button>
            <a-radio-button value="delete">{{ t('Delete') }}</a-radio-button>
          </a-radio-group>
        </a-form-item>
        <a-form-item label="Tags">
          <a-select
            v-model:value="modelRef.tags"
            mode="tags"
            style="width: 100%"
            :placeholder="t('Please input or select tags')"
          >
            <a-select-option v-for="action in modelRef.tagList" :key="action">
              {{ action }}
            </a-select-option>
          </a-select>
        </a-form-item>
      </a-form>
    </a-spin>
  </a-modal>
</template>

<script lang="ts">
import { Form, message } from 'ant-design-vue';
import type { PropType } from 'vue';
import { defineComponent, ref, reactive, toRaw, watchEffect } from 'vue';
import { manageTags } from '@/api/tags';
import { useI18n } from 'vue-i18n';

const formLayout = {
  labelCol: {
    xs: { span: 24 },
    sm: { span: 7 },
  },
  wrapperCol: {
    xs: { span: 24 },
    sm: { span: 13 },
  },
};

interface TagAction {
  ids?: string[];
  modelType?: string;
  action?: string;
  tagList?: string[];
  tags?: string[];
}

export default defineComponent({
  name: 'TagModal',
  props: {
    visible: {
      type: Boolean,
      required: true,
    },
    model: {
      type: Object as PropType<TagAction | null>,
      default: () => null,
    },
  },
  emits: ['ok', 'cancel'],
  setup(props, { emit }) {
    const { t } = useI18n();
    const loading = ref(false);
    const initValues = () => ({
      ids: [],
      action: 'add',
      tagList: [],
      tags: [],
      modelType: '',
    });
    const modelRef = reactive<TagAction>(initValues());

    const rulesRef = reactive({
      // 注意如果数据类型不相同，一定要指定数据类型 `type` 否则会校验失败
    });

    watchEffect(() => {
      if (props.model) {
        const raw = toRaw(props.model);
        modelRef.action = raw.action;
        modelRef.tagList = raw.tagList;
        modelRef.ids = raw.ids;
        modelRef.modelType = raw.modelType;
        console.log('model ref');
        console.log(modelRef);
      } else if (props.model === null) {
        Object.assign(modelRef, initValues());
      }
    });

    const { resetFields, validate, validateInfos } = Form.useForm(modelRef, rulesRef);
    const handleSubmit = (e: Event) => {
      e.preventDefault();
      loading.value = true;
      validate()
        .then(() => {
          const data = toRaw(modelRef);

          manageTags(modelRef.modelType, {
            action: data.action,
            names: data.tags,
            ids: data.ids,
          })
            .then(res => {
              message.success(t('success'));
              loading.value = false;
              resetFields();
              emit('ok', res);
            })
            .catch(err => {
              message.error(err.response.data.message);
              loading.value = false;
            });
        })
        .catch(err => {
          console.log('error', err);
          loading.value = false;
        });
    };

    return {
      t,
      ...formLayout,
      initValues,
      modelRef,
      loading,
      handleSubmit,
      resetFields,
      validateInfos,
    };
  },
});
</script>
