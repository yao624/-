<template>
  <a-modal
    title="任务"
    :open="visible"
    :width="800"
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
        <a-form-item label="名称" v-bind="validateInfos.name">
          <a-input v-model:value="modelRef.name" placeholder="名称"></a-input>
        </a-form-item>
        <a-form-item label="类型" v-bind="validateInfos.object_type">
          <a-select v-model:value="modelRef.object_type" style="width: 100%" placeholder="请选择">
            <a-select-option value="Campaign Tag">Campaign Tag</a-select-option>
            <a-select-option value="Adset Tag">Adset Tag</a-select-option>
            <a-select-option value="Campaign ID">Campaign ID</a-select-option>
            <a-select-option value="Adset ID">Adset ID</a-select-option>
            <a-select-option value="Ad ID">Ad ID</a-select-option>
          </a-select>
        </a-form-item>
        <a-form-item label="对象" v-bind="validateInfos.object_value">
          <a-select
            v-model:value="modelRef.object_value"
            mode="tags"
            style="width: 100%"
            placeholder="请选择"
          ></a-select>
        </a-form-item>
        <a-form-item label="时区" v-bind="validateInfos.timezone">
          <a-select v-model:value="modelRef.timezone" style="width: 100%" placeholder="请选择">
            <a-select-option value="Asia/Shanghai">Asia/Shanghai</a-select-option>
          </a-select>
        </a-form-item>
        <a-form-item label="时间">
          <a-time-range-picker
            v-model:value="modelRef.dateRange"
            :allow-empty="[true, true]"
            format="HH:mm"
            :order="false"
          />
        </a-form-item>
        <a-form-item label="状态" v-bind="validateInfos.active">
          <a-radio-group v-model:value="modelRef.active" button-style="solid">
            <a-radio-button :value="true">启用</a-radio-button>
            <a-radio-button :value="false">禁用</a-radio-button>
          </a-radio-group>
        </a-form-item>
        <a-form-item label="备注">
          <a-textarea v-model:value="modelRef.notes" />
        </a-form-item>
      </a-form>
    </a-spin>
  </a-modal>
</template>

<script lang="ts">
import { Form, message } from 'ant-design-vue';
import type { PropType } from 'vue';
import { defineComponent, ref, reactive, toRaw, watchEffect } from 'vue';
import { addJobApi } from '@/api/rule';
import dayjs from 'dayjs';

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

interface JobAction {
  id?: string;
  name?: string;
  timezone?: string;
  object_type?: string;
  object_value: string[];
  start_time: any;
  stop_time: any;
  active?: boolean;
  notes?: string;
  dateRange?: any[];
}

export default defineComponent({
  name: 'JObModal',
  props: {
    visible: {
      type: Boolean,
      required: true,
    },
    model: {
      type: Object as PropType<JobAction | null>,
      default: () => null,
    },
  },
  emits: ['ok', 'cancel'],
  setup(props, { emit }) {
    const loading = ref(false);
    const initValues = () => ({
      id: '',
      name: '',
      timezone: 'Asia/Shanghai',
      object_type: 'Campaign Tag',
      object_value: [],
      start_time: '',
      stop_time: '',
      active: true,
      notes: '',
      dateRange: [null, null],
    });
    const modelRef = reactive<JobAction>(initValues());

    const rulesRef = reactive({
      // 注意如果数据类型不相同，一定要指定数据类型 `type` 否则会校验失败
      name: [{ required: true, message: '不能为空', type: 'string' }],
      object_type: [{ required: true, message: '不能为空', type: 'string' }],
      object_value: [{ required: true, message: '不能为空', type: 'array' }],
      active: [{ required: true, message: '不能为空', type: 'boolean' }],
      timezone: [{ required: true, message: '不能为空', type: 'string' }],
    });

    watchEffect(() => {
      if (props.model) {
        const raw = toRaw(props.model);

        modelRef.id = raw.id;
        modelRef.name = raw.name;
        modelRef.timezone = raw.timezone;
        modelRef.object_type = raw.object_type;
        modelRef.object_value = raw.object_value;
        modelRef.start_time = raw.start_time;
        modelRef.stop_time = raw.stop_time;
        modelRef.active = raw.active;
        modelRef.notes = raw.notes;
        modelRef.dateRange = raw.dateRange;
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
          data.start_time = modelRef.dateRange[0]
            ? dayjs(modelRef.dateRange[0]).format('HH:mm')
            : '';
          data.stop_time = modelRef.dateRange[1]
            ? dayjs(modelRef.dateRange[1]).format('HH:mm')
            : '';
          addJobApi(data)
            .then(res => {
              message.success('保存成功');
              loading.value = false;
              resetFields();
              emit('ok', res);
            })
            .catch(err => {
              message.error(err.response.data.message);
            });
        })
        .catch(err => {
          console.log('error', err);
        })
        .finally(() => {
          loading.value = false;
        });
    };

    return {
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
