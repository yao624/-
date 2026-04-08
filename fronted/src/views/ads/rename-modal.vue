<template>
  <a-modal
    :title="t('pages.common.rename.title')"
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
        <a-form-item :label="t('pages.common.rename.new_name')">
          <a-input v-model:value="modelRef.object_name" style="width: 100%" />
        </a-form-item>
      </a-form>
    </a-spin>
  </a-modal>
</template>

<script lang="ts">
import { Form, message } from 'ant-design-vue';
import type { PropType } from 'vue';
import { defineComponent, ref, reactive, toRaw, watchEffect } from 'vue';
import { useI18n } from 'vue-i18n';
import { RenameFbObject } from '@/api/ads';

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
  id: string;
  object_type: string;
  object_name: string;
}

export default defineComponent({
  name: 'RenameModal',
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
      id: '',
      object_type: 'adset',
      object_name: '',
    });
    const modelRef = reactive<TagAction>(initValues());

    const rulesRef = reactive({
      // 注意如果数据类型不相同，一定要指定数据类型 `type` 否则会校验失败
    });

    watchEffect(() => {
      if (props.model) {
        const raw = toRaw(props.model);
        modelRef.id = raw.id;
        modelRef.object_type = raw.object_type;
        modelRef.object_name = raw.object_name;

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
          console.log(data);
          const req_params = {
            id: data['id'],
            object_type: data['object_type'],
            object_name: data['object_name'],
          };
          RenameFbObject(req_params)
            .then(res => {
              message.success(res['message']);
              loading.value = false;
              resetFields();
              emit('ok', res);
            })
            .catch(err => {
              console.log(err);
              loading.value = false;
              message.error('Request Failed');
            });
          // manageTags(modelRef.modelType, {
          //   action: data.action,
          //   names: data.tags,
          //   ids: data.ids,
          // })
          //   .then(res => {
          //     message.success('Success');
          //     loading.value = false;
          //     resetFields();
          //     emit('ok', res);
          //   })
          //   .catch(err => {
          //     message.error(err.response.data.message);
          //     loading.value = false;
          //   });
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
