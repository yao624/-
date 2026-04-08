<template>
  <a-form layout="vertical">
    <a-form-item name="template" :label="t('Select Template')">
      <!-- <searchable-select
        :form="formData"
        field="template"
        :searchFunc="(name) => getFbAdTemplates({ name }).then(({ data }) => data)"
        :multiple="false"
        :placeholder="t('Select template')"
        label-field="name"
        value-field="id">
      </searchable-select> -->
      <a-row :gutter="[6, 0]" :wrap="false">
        <a-col :flex="1">
          <a-input
            :value="formData.template[0]?.name"
            disabled
            :placeholder="t('Select template')"
          />
        </a-col>
        <a-col>
          <pick-objects
            :multiple="false"
            :api="getFbAdTemplates"
            :allow-empty="false"
            :columns="[
              { title: 'Name', dataIndex: 'name', key: 'name' },
              { title: 'Ad name', dataIndex: 'ad_name', key: 'ad_name' },
              { title: 'Ad set name', dataIndex: 'adset_name', key: 'adset_name' },
              { title: 'Campaign name', dataIndex: 'campaign_name', key: 'campaign_name' },
            ]"
            @confirm:items-selected="(_, rows) => (formData.template = rows)"
          ></pick-objects>
        </a-col>
      </a-row>
    </a-form-item>
    <a-form-item :label="t('Ad Accounts')">
      <a-table
        :loading="loading"
        :columns="[...columns, actionColumn]"
        :data-source="selectedAccounts"
        :row-key="record => record.id"
        :pagination="false"
      >
        <template #bodyCell="{ column, record }">
          <a-row v-if="column.key === 'action'" :gutter="[8, 0]">
            <a-col>
              <a @click="onRemove(record)">{{ t('Remove') }}</a>
            </a-col>
          </a-row>
        </template>
      </a-table>
    </a-form-item>
  </a-form>
</template>
<script lang="ts">
import { getFbAdTemplates } from '@/api/fb_ad_template';
import { defineComponent, reactive, ref, watchEffect } from 'vue';
import { useI18n } from 'vue-i18n';
import { useRouter } from 'vue-router';
import PickObjects from './pick-objects.vue';

export default defineComponent({
  name: 'PickAccounts',
  components: {
    PickObjects,
  },
  props: {
    adAccounts: {
      type: Array<object>,
      required: false,
    },
    loading: {
      type: Boolean,
      default: false,
    },
  },
  setup(props) {
    const { t } = useI18n();
    const router = useRouter();

    const loading = ref(false);
    const open = ref(false);
    const formData = reactive({
      template: [],
      ad_accounts: [],
    });
    const selectedAccounts = ref<any[]>([]);
    const columns = [
      { title: 'Name', dataIndex: 'name', key: 'name' },
      { title: 'Notes', dataIndex: 'notes', key: 'notes' },
      { title: 'Status', dataIndex: 'account_status', key: 'account_status' },
      { title: 'Currency', dataIndex: 'currency', key: 'currency' },
      { title: 'Balance', dataIndex: 'balance', key: 'balance' },
      { title: 'Action', dataIndex: 'Action' },
    ];
    const actionColumn = ref({ title: 'Action', dataIndex: 'action', key: 'action' });
    const dataSource = ref([]);
    const pagination = ref({
      current: 1,
      pageSize: 100,
      total: 0,
    });

    watchEffect(() => {
      selectedAccounts.value = props.adAccounts;
      loading.value = props.loading;
    });

    const getData = () => {
      return {
        template: ((formData.template || [])[0] as any)?.id,
        ad_accounts: selectedAccounts.value.map(({ id }) => id),
      };
    };

    const onRemove = row => {
      // selectedAccounts.value = selectedAccounts.value.filter(({ id }) => id !== row.id);
      const ids = selectedAccounts.value
        .map(({ source_id }) => source_id)
        .filter(id => id !== row.source_id)
        .map(id => `aid=${id}`);
      if (ids.length) {
        router.push('/ads/create_ads?' + ids.join('&'));
      } else {
        router.push('/adaccount');
      }
    };

    return {
      loading,
      open,
      formData,
      selectedAccounts,
      columns,
      dataSource,
      pagination,
      actionColumn,
      getData,
      onRemove,
      getFbAdTemplates,
      t,
    };
  },
});
</script>
