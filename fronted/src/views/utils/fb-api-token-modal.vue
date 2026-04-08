<template>
  <a-modal
    :title="t('FB API Token')"
    :open="visible"
    width="900px"
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
      <template #bodyCell="{ column, text, record }">
        <template v-if="column['dataIndex'] == 'active'">
          <a-badge :color="text === true ? 'green' : 'gray'" />
        </template>
        <template v-if="column['dataIndex'] == 'action'">
          <a-button
            type="primary"
            size="small"
            @click="showSubscribeModal(record)"
            :disabled="!record.app"
          >
            {{ t('Subscribe') }}
          </a-button>
        </template>
      </template>
    </a-table>
  </a-modal>

  <!-- Subscribe App Modal -->
  <a-modal
    :open="subscribeModal.visible"
    :title="t('Subscribe App to Ad Accounts')"
    @cancel="subscribeModal.visible = false"
    @ok="handleSubscribeApp"
    :confirm-loading="subscribeModal.loading"
    width="600px"
  >
    <a-form layout="vertical">
      <a-form-item :label="`App ID: ${subscribeModal.currentApp}`" v-if="subscribeModal.currentApp">
        <a-input :value="subscribeModal.currentApp" disabled />
      </a-form-item>
      <a-form-item :label="t('Select Ad Accounts')" required>
        <a-select
          v-model:value="subscribeModal.selectedAdAccounts"
          mode="multiple"
          placeholder="Please select ad accounts"
          :options="subscribeModal.adAccountOptions"
          :loading="subscribeModal.loadingAdAccounts"
        />
      </a-form-item>
    </a-form>
  </a-modal>
</template>

<script lang="ts">
import type { PropType } from 'vue';
import { defineComponent, ref, reactive, toRaw, watchEffect } from 'vue';
import { useI18n } from 'vue-i18n';
import { message } from 'ant-design-vue';
import { subscribeApp } from '@/api/fb_api_token';

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
  ad_accounts?: any[];
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
      ad_accounts: [],
    });
    const modelRef = reactive<TagAction>(initValues());

    watchEffect(() => {
      if (props.model) {
        const raw = toRaw(props.model);
        modelRef.bm_system_users = raw.bm_system_users;
        modelRef.ad_accounts = raw.ad_accounts || [];
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

    // Subscribe modal logic
    const subscribeModal = reactive({
      visible: false,
      loading: false,
      loadingAdAccounts: false,
      selectedAdAccounts: [],
      adAccountOptions: [],
      currentApp: '',
      currentTokenId: '',
    });

    const showSubscribeModal = (record: any) => {
      if (!record.app) {
        message.error('App ID is required');
        return;
      }

      subscribeModal.visible = true;
      subscribeModal.currentApp = record.app;
      subscribeModal.currentTokenId = record.id;
      subscribeModal.loadingAdAccounts = true;

      try {
        // Filter ad accounts that haven't subscribed to this app yet
        const adAccounts = modelRef.ad_accounts || [];
        console.log('Available ad accounts:', adAccounts);
        console.log('Current app:', record.app);

        subscribeModal.adAccountOptions = adAccounts
          .filter(account => {
            const subscribedApps = account.subscribed_apps || [];
            const isAlreadySubscribed = subscribedApps.some(app => app.source_id === record.app);
            console.log(`Account ${account.source_id} subscribed apps:`, subscribedApps, 'Already subscribed:', isAlreadySubscribed);
            return !isAlreadySubscribed;
          })
          .map(account => ({
            label: `${account.name} (${account.source_id})`,
            value: account.source_id,
          }));

        console.log('Filtered ad account options:', subscribeModal.adAccountOptions);
      } catch (error) {
        message.error('Failed to load ad accounts');
        console.error(error);
      } finally {
        subscribeModal.loadingAdAccounts = false;
      }
    };

    const handleSubscribeApp = async () => {
      if (subscribeModal.selectedAdAccounts.length === 0) {
        message.error('Please select at least one ad account');
        return;
      }

      subscribeModal.loading = true;

      try {
        const params = {
          ad_account_source_ids: subscribeModal.selectedAdAccounts,
          app_source_id: subscribeModal.currentApp,
          fb_api_token_id: subscribeModal.currentTokenId,
        };

        const response = await subscribeApp(params);
        message.success((response as any)?.message || 'Successfully subscribed app to ad accounts');
        subscribeModal.visible = false;
        subscribeModal.selectedAdAccounts = [];
      } catch (error) {
        message.error('Failed to subscribe app');
        console.error(error);
      } finally {
        subscribeModal.loading = false;
      }
    };

    const columns = [
      {
        title: t('pages.adc.index'),
        dataIndex: 'index',
        customRender: ({ index }) => {
          return `${index + 1}`;
        },
        width: 100,
        align: 'center' as const,
      },
      {
        title: t('Status'),
        dataIndex: 'active',
        minWidth: 50,
        align: 'center' as const,
        resizable: true,
      },
      {
        title: t('Token Type'),
        dataIndex: 'token_type',
        minWidth: 50,
        align: 'center' as const,
        resizable: true,
      },
      {
        title: t('Token Name'),
        dataIndex: 'name',
        minWidth: 100,
        align: 'center' as const,
        resizable: true,
      },
      {
        title: 'App',
        dataIndex: 'app',
        minWidth: 100,
        align: 'center' as const,
        resizable: true,
      },
      {
        title: t('Action'),
        dataIndex: 'action',
        minWidth: 100,
        align: 'center' as const,
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
      subscribeModal,
      showSubscribeModal,
      handleSubscribeApp,
    };
  },
});
</script>
