<template>
  <a-modal
    :title="t('Pages')"
    :open="visible"
    width="600px"
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
      :data-source="model.data_source"
      :row-key="record => record.id"
      bordered
      sticky
size="default"
    >
      <template #bodyCell="{ column, text }">
        <template v-if="column['dataIndex'] == 'active'">
          <a-badge :color="text === true ? 'green' : 'gray'" />
        </template>
      </template>
    </a-table>
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
  data_source: any[];
}

export default defineComponent({
  name: 'fbPageModal',
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
      data_source: [],
    });
    const modelRef = reactive<TagAction>(initValues());

    watchEffect(() => {
      if (props.model) {
        const raw = toRaw(props.model);
        modelRef.data_source = raw.data_source;
        console.log('model ref');
        console.log(modelRef);
      } else if (props.model === null) {
        Object.assign(modelRef, initValues());
      }
    });

    const handleSubmit = () => {
      // e.preventDefault();
      // loading.value = true;
      emit('ok');
    };

    const columns = [
      {
        title: t('pages.adc.index'),
        dataIndex: 'index',
        customRender: ({ index }) => {
          return `${index + 1}`;
        },
        width: 80,
        align: 'center',
      },
      {
        title: t('ID'),
        dataIndex: 'source_id',
        align: 'center',
        resizable: true,
      },
      {
        title: t('Page name'),
        dataIndex: 'name',
        align: 'center',
        resizable: true,
      },
      {
        title: t('Role'),
        dataIndex: 'role',
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
