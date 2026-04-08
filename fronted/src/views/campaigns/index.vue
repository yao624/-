<template>
  <page-container :title="t('pages.campaigns.title')">
    <a-card mb-4>
      <a-row :gutter="[15, 0]" v-if="expand">
        <a-col :span="6">
          <a-form-item :label="t('pages.name')">
            <a-input v-model:value="pagination.name" />
          </a-form-item>
        </a-col>
        <a-col :span="10">
          <a-form-item :label="t('pages.createdAt')">
            <a-range-picker v-model:value="pagination.created_at" show-time />
          </a-form-item>
        </a-col>
      </a-row>

      <a-row :span="24" style="text-align: right">
        <a-col :span="24">
          <a-space flex justify-end w-full>
            <a-button :loading="loading" type="primary" @click="fetchProxies">
              {{ t('pages.query') }}
            </a-button>
            <a-button :loading="loading" @click="resetPagination">{{ t('pages.reset') }}</a-button>
            <a-button type="link" @click="expand = !expand">
              {{ expand ? t('pages.collapse') : t('pages.expand') }}
              <up-outlined v-if="expand" />
              <down-outlined v-else />
            </a-button>
          </a-space>
        </a-col>
      </a-row>
    </a-card>
    <a-table
      :columns="columns"
      :data-source="dataSource"
      :scroll="{ y: tableHeight, x: 3000 }"
      :pagination="pagination"
      :loading="loading"
      :row-key="record => record.id"
      @change="handleTableChange"
    >
      <template #bodyCell="{ column, text }">
        <template v-if="column['dataIndex'] === 'active'">
          <span>{{ text === false ? t('pages.deActive') : t('pages.active') }}</span>
        </template>
        <template v-if="['endpoint'].includes(`${column['dataIndex']}`)">
          <a :href="text" target="_blank" v-html="text"></a>
          <copy-outlined @click="copyCell(text)" />
        </template>
        <template v-if="['tags'].includes(`${column['dataIndex']}`)">
          <a-tag v-for="m in text" :key="m.id">{{ m.name }}</a-tag>
        </template>
        <template v-if="['created_at', 'updated_at'].includes(`${column['dataIndex']}`)">
          <span>{{ dayjs(text).format('YYYY-MM-DD HH:mm:ss') }}</span>
        </template>
      </template>
    </a-table>
  </page-container>
</template>
<script lang="ts">
import dayjs from 'dayjs';
import { computed, defineComponent, ref, onMounted, watch } from 'vue';
import { message } from 'ant-design-vue';
import { CopyOutlined, PlusOutlined, UpOutlined, DownOutlined } from '@ant-design/icons-vue';
import { queryFBCampaignsApi } from '@/api/fb_campaigns';
import { useI18n } from 'vue-i18n';
import useClipboard from 'vue-clipboard3';

export default defineComponent({
  components: {
    CopyOutlined,
    PlusOutlined,
    UpOutlined,
    DownOutlined,
  },
  setup() {
    const { t } = useI18n();
    const sortedInfo = ref();
    const expand = ref(true);
    const screenHeight = ref<number>(window.innerHeight);
    const tableHeight = screenHeight.value < 1010 ? ref('48vh') : ref('54vh');

    watch(expand, newValue => {
      if (screenHeight.value < 1010) {
        tableHeight.value = newValue ? '48vh' : '65vh';
      } else {
        tableHeight.value = newValue ? '54vh' : '65vh';
      }
    });
    const columns = computed<any[]>(() => {
      const sorted = sortedInfo.value || {};
      return [
        {
          title: t('pages.account.id'),
          dataIndex: 'account_id',
        },
        {
          title: t('pages.bid.strategy'),
          dataIndex: 'bid_strategy',
          resizable: true,
        },
        {
          title: t('pages.budget.remaining'),
          dataIndex: 'budget_remaining',
          resizable: true,
        },
        {
          title: t('pages.configured.status'),
          dataIndex: 'configured_status',
          resizable: true,
        },
        {
          title: t('pages.daily.budget'),
          dataIndex: 'daily_budget',
          resizable: true,
        },
        {
          title: t('pages.effective.status'),
          dataIndex: 'effective_status',
          resizable: true,
        },
        {
          title: t('pages.source.id'),
          dataIndex: 'source_id',
        },
        {
          title: t('pages.name'),
          dataIndex: 'name',
        },
        {
          title: t('pages.objective'),
          dataIndex: 'objective',
        },
        {
          title: t('pages.source.campaign.id'),
          dataIndex: 'source_campaign_id',
          resizable: true,
        },
        {
          title: t('pages.start.time'),
          dataIndex: 'start_time',
          resizable: true,
        },
        {
          title: t('pages.status'),
          dataIndex: 'status',
        },
        {
          title: t('pages.updated.time'),
          dataIndex: 'updated_time',
          resizable: true,
        },
        {
          title: t('pages.createdAt'),
          dataIndex: 'created_at',
          sorter: true,
          sortOrder: sorted.field === 'created_at' && sorted.order,
          resizable: true,
        },
        {
          title: t('pages.updatedAt'),
          dataIndex: 'updated_at',
          sorter: true,
          sortOrder: sorted.field === 'updated_at' && sorted.order,
          resizable: true,
        },
      ];
    });

    const loading = ref(true);
    const dataSource = ref<any>([]);
    const pagination = ref<any>({
      name: '',
      showQuickJumper: true,
      showSizeChanger: true,
      current: 1,
      total: 0,
      showTotal: total => `Total ${total} items`,
      pageSize: 20,
    });
    const resetPagination = () => {
      pagination.value.name = '';
      pagination.value.created_at = [];
      sortedInfo.value = null;
      fetchProxies();
    };
    interface Query {
      name?: string;
      date_start?: string;
      date_end?: string;
      pageNo: number;
      pageSize: number;
      sortOrder?: string;
      sortField?: string;
    }
    const fetchProxies = () => {
      loading.value = true;
      let sortParam = '';
      if (sortedInfo?.value?.order === 'ascend') sortParam = 'asc';
      else if (sortedInfo?.value?.order === 'descend') sortParam = 'desc';
      const param = {
        link: pagination.value.link,
        pageNo: pagination.value.current,
        pageSize: pagination.value.pageSize,
      } as Query;
      if (sortedInfo?.value?.order) {
        param.sortOrder = sortParam;
        param.sortField = sortedInfo?.value?.field;
      }
      // if (pagination.value.tags && pagination.value.tags.length > 0) {
      //   param.tags = pagination.value.tags.join(',');
      // }
      // if (pagination.value.protocols && pagination.value.protocols.length > 0) {
      //   param.protocol = pagination.value.protocols.join(',');
      // }
      if (pagination.value.created_at && pagination.value.created_at.length === 2) {
        param.date_start = dayjs(pagination.value.created_at[0]).format('YYYY-MM-DDTHH:mm:ss ZZ');
        param.date_end = dayjs(pagination.value.created_at[1]).format('YYYY-MM-DDTHH:mm:ss ZZ');
      }
      if (pagination.value.name) {
        param.name = pagination.value.name;
      }

      queryFBCampaignsApi(param)
        .then((res: any) => {
          dataSource.value = res.data;
          pagination.value.total = res.totalCount;
        })
        .finally(() => {
          loading.value = false;
        });
    };

    const { toClipboard } = useClipboard();
    const copyCell = async (text: any) => {
      try {
        await toClipboard(text);
        message.success(t('pages.linkCopiedw'));
      } catch (e) {
        console.error(e);
      }
    };

    const inputRef = ref();

    const handleTableChange: any['onChange'] = (pag, _filters, sorter) => {
      if (pag) {
        if (pag.current != null) pagination.value.current = pag.current;
        if (pag.pageSize != null) pagination.value.pageSize = pag.pageSize;
      }
      sortedInfo.value = sorter;
      fetchProxies();
    };

    onMounted(() => {
      fetchProxies();
    });
    return {
      dataSource,
      loading,
      fetchProxies,
      resetPagination,
      dayjs,
      pagination,
      columns,
      copyCell,
      handleTableChange,
      t,
      open,
      inputRef,
      expand,
      tableHeight,
    };
  },
});
</script>
