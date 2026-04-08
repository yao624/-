<template>
  <a-modal
    :title="t('pages.adc.action.sys-user')"
    :open="visible"
    :width="600"
    :confirmLoading="loading"
    @ok="handleSubmit"
    @cancel="
      () => {
        $emit('cancel');
      }
    "
  >
    <a-table
      :columns="columns"
      :data-source="model.users"
      :row-key="record => record.id"
      bordered
      sticky
size="small"
></a-table>
  </a-modal>
</template>

<script lang="ts">
import type { PropType } from 'vue';
import { defineComponent, ref, reactive, toRaw, watchEffect } from 'vue';
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
  users: any[];
}

export default defineComponent({
  name: 'userListModal',
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
      users: [],
    });
    const modelRef = reactive<TagAction>(initValues());

    watchEffect(() => {
      if (props.model) {
        const raw = toRaw(props.model);
        modelRef.users = raw.users;
        console.log('model ref');
        console.log(modelRef);
      } else if (props.model === null) {
        Object.assign(modelRef, initValues());
      }
    });

    const handleSubmit = (e: Event) => {
      e.preventDefault();
      loading.value = true;
    };

    const columns = [
      {
        title: t('pages.adc.index'),
        dataIndex: 'index',
        customRender: ({ index }) => {
          return `${index + 1}`;
        },
        width: 100,
        align: 'center',
      },
      {
        title: t('pages.adc.name'),
        dataIndex: 'name',
        minWidth: 160,
        align: 'center',
        resizable: true,
      },
    ];

    return {
      ...formLayout,
      initValues,
      modelRef,
      loading,
      handleSubmit,
      emit,
      t,
      columns,
    };
  },
});
</script>
