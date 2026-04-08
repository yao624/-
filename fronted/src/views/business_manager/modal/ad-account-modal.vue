<template>
  <a-modal
    :title="t('Ad Account')"
    :open="visible"
    width="1200px"
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
      <template #bodyCell="{ column, text, record }">
        <template v-if="column['dataIndex'] == 'source_id'">
          <copy-outlined style="color: #1677ff" v-if="text" @click="copyCell(text)" />
          &nbsp;
          <sync-outlined style="color: #1677ff" v-if="text" @click="syncAdAccount(record['id'])" />
          {{ text }}
        </template>
        <template v-if="column['dataIndex'] == 'name'">
          <copy-outlined style="color: #1677ff" v-if="text" @click="copyCell(text)" />
          {{ text }}
        </template>
        <template v-if="column['dataIndex'] == 'account_status'">
          <a-badge :color="text === 'ACTIVE' ? 'green' : 'gray'" />
          {{ text }}
        </template>
        <template v-if="column['dataIndex'] == 'pixels'">
          <a-button type="link" @click="showFbPixelModal(record)">
            {{ record.pixels?.length }}
          </a-button>
        </template>
        <template v-if="column['dataIndex'] == 'subscribed_apps'">
          <a-button type="link" @click="showSubscribedAppsModal(record)">
            {{ record.subscribed_apps?.length || 0 }}
          </a-button>
        </template>
      </template>
    </a-table>
  </a-modal>

  <fb-pixel-modal
    :model="fbPixelModal.model"
    :visible="fbPixelModal.visible"
    :canShare="false"
    @cancel="
      () => {
        fbPixelModal.visible = false;
      }
    "
    @ok="
      () => {
        fbPixelModal.visible = false;
      }
    "
  />

  <subscribed-apps-modal
    :model="subscribedAppsModal.model"
    :visible="subscribedAppsModal.visible"
    @cancel="
      () => {
        subscribedAppsModal.visible = false;
      }
    "
    @ok="
      () => {
        subscribedAppsModal.visible = false;
      }
    "
  />
</template>

<script lang="ts">
import type { PropType } from 'vue';
import { defineComponent, ref, reactive, toRaw, watchEffect } from 'vue';
import { useI18n } from 'vue-i18n';
import FbPixelModal from './fb-pixel-modal.vue';
import SubscribedAppsModal from './subscribed-apps-modal.vue';
import useClipboard from 'vue-clipboard3';
import { message } from 'ant-design-vue';
import { CopyOutlined, SyncOutlined } from '@ant-design/icons-vue';
import { fetchAdAccountInfo } from '@/api/fb_ad_accounts';

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
  name: 'adAccountModal',
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
  components: {
    FbPixelModal,
    SubscribedAppsModal,
    CopyOutlined,
    SyncOutlined,
  },
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

    const sorterInfoMap = ref();
    const sorted = sorterInfoMap.value || {};
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
        title: t('pages.adc.name'),
        dataIndex: 'name',
        sorter: true,
        sortOrder: sorted.columnKey === 'name' && sorted.order,
        align: 'left',
        minWidth: 160,
        ellipsis: false,
        resizable: true,
      },
      {
        title: 'ID',
        dataIndex: 'source_id',
        sorter: true,
        sortOrder: sorted.columnKey === 'source_id' && sorted.order,
        align: 'left',
        minWidth: 170,
        ellipsis: false,
        resizable: true,
      },
      {
        title: t('Role'),
        dataIndex: 'role',
        minWidth: 100,
        resizable: true,
      },
      {
        title: t('pages.adc.status'),
        dataIndex: 'account_status',
        sorter: (a, b) => a.name.length - b.name.length,
        sortOrder: sorted.columnKey === 'name' && sorted.order,
        minWidth: 100,
      },
      {
        title: t('pages.adc.tz'),
        dataIndex: 'timezone_name',
        minWidth: 100,
        resizable: true,
      },
      {
        title: t('Pixel'),
        dataIndex: 'pixels',
        minWidth: 60,
        resizable: true,
      },
      {
        title: t('pages.adc.currency'),
        dataIndex: 'currency',
        minWidth: 60,
        elipellipsis: true,
      },
      {
        title: t('Payment'),
        dataIndex: 'default_funding',
        minWidth: 100,
      },
      {
        title: t('pages.adc.limit'),
        dataIndex: 'adtrust_dsl',
        minWidth: 100,
        sorter: true,
        sortOrder: sorted.columnKey === 'adtrust_dsl' && sorted.order,
      },
      {
        title: 'Apps',
        dataIndex: 'subscribed_apps',
        minWidth: 60,
        resizable: true,
      },
    ];

    // fb pixel modal
    const fbPixelModal = reactive({
      visible: false,
      model: null,
    });
    const showFbPixelModal = (record: any) => {
      fbPixelModal.model = {
        // data_source: { pixels: record },
        data_source: record,
      };
      fbPixelModal.visible = true;
    };

    // subscribed apps modal
    const subscribedAppsModal = reactive({
      visible: false,
      model: null,
    });
    const showSubscribedAppsModal = (record: any) => {
      subscribedAppsModal.model = {
        data_source: record.subscribed_apps || [],
      };
      subscribedAppsModal.visible = true;
    };

    const { toClipboard } = useClipboard();
    const copyCell = async (text: any) => {
      try {
        await toClipboard(text);
        message.success('copied');
      } catch (e) {
        console.error(e);
      }
    };
    const syncAdAccount = async (id: any) => {
      try {
        fetchAdAccountInfo({
          fb_ad_account_ids: [id],
          days: 3,
        })
          .then(res => {
            message.success(res['message']);
          })
          .catch(e => {
            console.log(e);
          });
      } catch (e) {
        console.error(e);
      }
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

      fbPixelModal,
      showFbPixelModal,

      subscribedAppsModal,
      showSubscribedAppsModal,

      copyCell,
      syncAdAccount,
    };
  },
});
</script>
