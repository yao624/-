<template>
  <a-modal
    :title="t('Subscribed Apps')"
    :open="visible"
    width="800px"
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
      :row-key="record => record.source_id"
      bordered
      sticky
size="default"
    >
      <template #bodyCell="{ column, text, record }">
        <template v-if="column['dataIndex'] == 'source_id'">
          <copy-outlined style="color: #1677ff" v-if="text" @click="copyCell(text)" />
          {{ text }}
        </template>
        <template v-if="column['dataIndex'] == 'name'">
          <copy-outlined style="color: #1677ff" v-if="text" @click="copyCell(text)" />
          {{ text }}
        </template>
      </template>
    </a-table>
  </a-modal>
</template>

<script lang="ts">
import type { PropType } from 'vue';
import { defineComponent, ref, reactive, toRaw, watchEffect } from 'vue';
import { useI18n } from 'vue-i18n';
import useClipboard from 'vue-clipboard3';
import { message } from 'ant-design-vue';
import { CopyOutlined } from '@ant-design/icons-vue';

interface SubscribedAppsAction {
  data_source: any[];
}

export default defineComponent({
  name: 'SubscribedAppsModal',
  props: {
    visible: {
      type: Boolean,
      required: true,
    },
    model: {
      type: Object as PropType<SubscribedAppsAction | null>,
      default: () => null,
    },
  },
  emits: ['ok', 'cancel'],
  components: {
    CopyOutlined,
  },
  setup(props, { emit }) {
    const { t } = useI18n();
    const loading = ref(false);
    const initValues = () => ({
      data_source: [],
    });
    const modelRef = reactive<SubscribedAppsAction>(initValues());

    watchEffect(() => {
      if (props.model) {
        const raw = toRaw(props.model);
        modelRef.data_source = raw.data_source || [];
      } else if (props.model === null) {
        Object.assign(modelRef, initValues());
      }
    });

    const handleSubmit = () => {
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
        title: 'App ID',
        dataIndex: 'source_id',
        align: 'left',
        minWidth: 160,
        ellipsis: false,
        resizable: true,
      },
      {
        title: t('pages.adc.name'),
        dataIndex: 'name',
        align: 'left',
        minWidth: 160,
        ellipsis: false,
        resizable: true,
      },
    ];

    const { toClipboard } = useClipboard();
    const copyCell = async (text: any) => {
      try {
        await toClipboard(text);
        message.success('copied');
      } catch (e) {
        console.error(e);
      }
    };

    return {
      initValues,
      modelRef,
      loading,
      handleSubmit,
      emit,
      t,
      columns,
      copyCell,
    };
  },
});
</script>
