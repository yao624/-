<template>
  <a-modal
    :title="t('FB API Token')"
    :open="visible"
    :minWidth="600"
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
      :data-source="model.bm_system_users"
      :row-key="record => record.id"
      bordered
      sticky
size="small"
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
  bm_system_users: any[];
}

export default defineComponent({
  name: 'fbApiTokenModal',
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
      bm_system_users: [],
    });
    const modelRef = reactive<TagAction>(initValues());

    watchEffect(() => {
      if (props.model) {
        const raw = toRaw(props.model);
        modelRef.bm_system_users = raw.bm_system_users;
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
        title: t('Status'),
        dataIndex: 'active',
        minWidth: 50,
        align: 'center',
        resizable: true,
      },
      {
        title: t('Token Type'),
        dataIndex: 'token_type',
        minWidth: 50,
        align: 'center',
        resizable: true,
      },
      {
        title: t('Token Name'),
        dataIndex: 'name',
        minWidth: 100,
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
