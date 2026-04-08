<template>
  <a-modal
    :title="t('FB Pixels')"
    :open="visible"
    width="1000px"
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
      :data-source="model.data_source['pixels']"
      :row-key="record => record.id"
      bordered
      sticky
size="small"
    >
      <template #bodyCell="{ column, record }">
        <template v-if="column['dataIndex'] == 'is_unavailable'">
          <a-badge :color="record.is_unavailable !== true ? 'green' : 'gray'" />
        </template>
        <template v-if="column['dataIndex'] == 'is_dataset'">
          <a-badge :text="record.is_dataset !== true ? 'Dataset' : 'Pixel'" />
        </template>
        <template v-if="column['dataIndex'] == 'owner_business'">
          <a-badge
            :text="
              record.owner_business?.id === model.data_source['source_id'] ? 'Owner' : 'Client'
            "
          />
        </template>
        <template v-if="column['dataIndex'] === 'operation'">
          <a @click="showSharePixelModel(record)" v-show="canShare">
            {{ t('Share') }}
          </a>
        </template>
      </template>
    </a-table>

    <share-pixel-modal
      :model="sharePixelModal.model"
      :visible="sharePixelModal.visible"
      @cancel="
        () => {
          sharePixelModal.visible = false;
        }
      "
      @ok="
        () => {
          sharePixelModal.visible = false;
        }
      "
    />
  </a-modal>
</template>

<script lang="ts">
import type { PropType } from 'vue';
import { defineComponent, ref, reactive, toRaw, watchEffect } from 'vue';
import { useI18n } from 'vue-i18n';
import SharePixelModal from './share-pixel-modal.vue';

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
  name: 'fbPixelModel',
  components: {
    SharePixelModal,
  },
  props: {
    visible: {
      type: Boolean,
      required: true,
    },
    model: {
      type: Object as PropType<TagAction | null>,
      default: () => null,
    },
    canShare: {
      type: Boolean,
      default: true,
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
        console.log(raw);
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
        width: 100,
        align: 'center',
      },
      {
        title: t('ID'),
        dataIndex: 'pixel',
        minWidth: 100,
        align: 'center',
        resizable: true,
      },
      {
        title: t('Name'),
        dataIndex: 'name',
        minWidth: 100,
        align: 'center',
        resizable: true,
      },
      {
        title: t('Status'),
        dataIndex: 'is_unavailable',
        minWidth: 50,
        align: 'center',
        resizable: true,
      },
      {
        title: t('Type'),
        dataIndex: 'is_dataset',
        minWidth: 50,
        align: 'center',
        resizable: true,
      },
      {
        title: t('Owned'),
        dataIndex: 'owner_business',
        minWidth: 50,
        align: 'center',
        resizable: true,
      },
      ...(props.canShare === true
        ? [
            {
              title: t('pages.operation'),
              width: 120,
              dataIndex: 'operation',
              fixed: 'right',
            } as any,
          ]
        : []),
    ];

    // fb pixel modal
    const sharePixelModal = reactive({
      visible: false,
      model: null,
    });
    const showSharePixelModel = record => {
      sharePixelModal.model = {
        data_source: modelRef.data_source['ad_accounts'],
        catalogs: modelRef.data_source['catalogs'],
        pixel_id: record.id,
        bm_id: modelRef.data_source['id'],
      };
      sharePixelModal.visible = true;
    };

    return {
      ...formLayout,
      initValues,
      modelRef,
      loading,
      handleSubmit,
      emit,
      t,
      columns,

      showSharePixelModel,
      sharePixelModal,
    };
  },
});
</script>
