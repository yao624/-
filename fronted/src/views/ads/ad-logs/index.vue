<template>
  <page-container :title="t('pages.pages.title')">
    <a-card mb-4>
      <a-row>
        <a-col :flex="1">
          <!-- <add-item ref="modalRef" @confirm:saved="reloadTable()"></add-item> -->
        </a-col>
        <!-- <a-col :flex="1">
          <applied-filters :filters="appliedFilters"></applied-filters>
        </a-col> -->
        <a-col>
          <a-tooltip :title="t('Refresh')">
            <a-button shape="circle" :icon="h(ReloadOutlined)" @click="reloadTable"></a-button>
          </a-tooltip>
        </a-col>
        <a-col>
          <column-orgnizer
            :columns="columns"
            @change:columns="data => (columns = data)"
          ></column-orgnizer>
          <!-- <dynamic-form :form-items="formSearchItems" @change:form-data="onSearch"></dynamic-form> -->
        </a-col>
      </a-row>
    </a-card>
    <a-table
      :loading="state.loading"
      :scroll="{ y: tableHeight, x: 2000 }"
      :columns="columns"
      :data-source="state.dataSource"
      :pagination="{
        current: state.current,
        pageSize: state.pageSize,
        total: state.total,
        defaultPageSize: 10,
        showSizeChanger: true,
        pageSizeOptions: ['10', '20', '50', '100'],
        showTotal: total => `Total ${total} items`,
      }"
      :row-key="record => record.id"
      @change="handleTableChange"
    >
      <template #bodyCell="{ column, text, record }">
        <template v-if="column['dataIndex'] === 'operation'">
          <a @click="editOne(record)" v-if="record.is_owner">
            {{ t('Edit') }}
          </a>
          <a-divider type="vertical" v-if="record.is_owner" />
          <a @click="handleShare(record)" v-if="record.is_owner">{{ t('Share') }}</a>
          <a-divider type="vertical" v-if="record.is_owner" />
          <a @click="deleteOne(record)" v-if="record.is_owner">{{ t('Delete') }}</a>
        </template>
        <template v-if="column['dataIndex'] === 'created_at'">
          <a-badge :color="record.is_success ? 'green' : 'red'" />
          <span>{{ dayjs(text).format('YYYY-MM-DD HH:mm:ss') }}</span>
        </template>
        <template v-if="column['dataIndex'] === 'user'">
          <span>{{ text }}</span>
        </template>
        <template v-if="column['dataIndex'] === 'ad_account'">
          <div>
            <copy-outlined style="color: #1677ff" @click="copyCell(text.source_id)" />
            {{ text['source_id'] }}
          </div>
          <div>{{ text['name'] || '' }}</div>
        </template>
        <template v-if="column['dataIndex'] === 'campaigns'">
          <div v-for="(c, index) in text" :key="index">
            <copy-outlined style="color: #1677ff" @click="copyCell(c.campaign_source_id)" />
            {{ c.campaign_source_id }}
          </div>
        </template>
        <template v-if="column['dataIndex'] === 'adsets'">
          <div v-for="(c, index) in text" :key="index">
            <copy-outlined style="color: #1677ff" @click="copyCell(c.adset_source_id)" />
            {{ c.adset_source_id }}
          </div>
        </template>
        <template v-if="column['dataIndex'] === 'ads'">
          <div v-for="(c, index) in text" :key="index">
            <copy-outlined style="color: #1677ff" @click="copyCell(c.ad_source_id)" />
            {{ c.ad_source_id }}
          </div>
        </template>
        <template v-if="column['dataIndex'] === 'template'">
          <div v-if="text">{{ text['name'] || '' }}</div>
        </template>
        <template v-if="column['dataIndex'] === 'page'">
          <div v-if="text">{{ text['name'] || '' }}</div>
        </template>
        <template v-if="column['dataIndex'] === 'pixel'">
          <div v-if="text">{{ text['name'] || '' }}</div>
        </template>
        <template v-if="column['dataIndex'] === 'link'">
          <copy-outlined style="color: #1677ff" v-if="text" @click="copyCell(text.link)" />
          <a-tooltip :title="text.link">
            {{ text.link }}
          </a-tooltip>
        </template>
        <template v-if="column['dataIndex'] === 'materials'">
          <div v-for="(m, index) in text" :key="index">{{ m.name || '' }}</div>
        </template>
        <template v-if="column['dataIndex'] === 'copywriting'">
          <a-tooltip :title="text.primary_text">
            {{ text.primary_text }}
          </a-tooltip>
        </template>
        <!-- <template v-if="column['dataIndex'] === 'url'">
          <a-tooltip :title="text">
            <div class="ellipsis-container">
              <span>{{ text }}</span>
            </div>
          </a-tooltip>
        </template> -->
      </template>
    </a-table>
  </page-container>
</template>

<script lang="ts">
import { computed, defineComponent, ref, reactive, h } from 'vue';
import ColumnOrgnizer from '@/components/column-orgnizer/column-orgnizer.vue';
import { useI18n } from 'vue-i18n';
import type { LinkModel, ShareModel } from '@/utils/fb-interfaces';
import { useTableHeight } from '@/utils/hooks/useTableHeight';
import dayjs from 'dayjs';
import { useFetchData } from '@/utils/hooks/useFetchData';
import { message, Modal } from 'ant-design-vue';
import type { Pagination, TableColumn } from '@/typing';
import 'viplayer/dist/index.css';

import { CopyOutlined, ReloadOutlined } from '@ant-design/icons-vue';
import { deleteLinksOneApi, shareLinksApi, unShareLinksApi } from '@/api/links';
import useClipboard from 'vue-clipboard3';
import { getAdLogs } from '@/api/ads';

export default defineComponent({
  components: {
    // DynamicForm,
    ColumnOrgnizer,
    CopyOutlined,
  },
  setup() {
    const { t } = useI18n();
    const loading = ref(false);
    const sortedInfo = ref();
    const dataSource = ref<LinkModel[]>([]);
    const appliedFilters = ref({});
    const modalRef = ref(null);
    const editModalRef = reactive({
      model: null,
      open: false,
    });
    const shareApi = shareLinksApi;
    const unshareApi = unShareLinksApi;
    const shareModalRef = reactive({
      open: false,
      model: {} as ShareModel,
    });

    const { tableHeight } = useTableHeight();

    const formSearchItems = ref([
      { label: t('Link'), field: 'link' },
      { label: 'pages.notes', field: 'notes' },
    ]);

    const queryParam = reactive({
      name: null,
      notes: null,
      sortOrder: undefined,
      sortField: undefined,
    });

    const fetchDataContext = reactive({
      current: 1,
      pageSize: 10,
      requestParams: { ...queryParam },
    });

    const onRequestError = e => {
      console.error('请求错误: ', e);
      message.error(t('Request Error'));
    };

    const { context: state, reload: reloadTable } = useFetchData(getAdLogs, fetchDataContext, {
      onRequestError: onRequestError,
    });

    const columns = computed<TableColumn[]>(() => {
      const sorted = sortedInfo.value || {};
      return [
        {
          title: t('User'),
          dataIndex: 'user',
          resizable: true,
          ellipsis: true,
        },
        {
          title: t('Created date'),
          dataIndex: 'created_at',
          resizable: true,
          ellipsis: true,
          sorter: true,
          sortOrder: sorted.field === 'created_at' && sorted.order,
        },
        {
          title: t('Ad Account'),
          dataIndex: 'ad_account',
          resizable: true,
          ellipsis: true,
        },
        {
          title: t('Campaigns'),
          dataIndex: 'campaigns',
          resizable: true,
          ellipsis: true,
        },
        {
          title: t('Adset'),
          dataIndex: 'adsets',
          resizable: true,
          ellipsis: true,
        },
        {
          title: t('Ads'),
          dataIndex: 'ads',
          resizable: true,
          ellipsis: true,
        },
        {
          title: t('Template'),
          dataIndex: 'template',
          resizable: true,
        },
        {
          title: t('Page'),
          dataIndex: 'page',
          resizable: true,
        },
        {
          title: t('Pixel'),
          dataIndex: 'pixel',
          resizable: true,
        },
        {
          title: t('Materials'),
          dataIndex: 'materials',
          resizable: true,
        },
        {
          title: t('Copywriting'),
          dataIndex: 'copywriting',
          resizable: true,
          ellipsis: true,
        },
        {
          title: t('Link'),
          dataIndex: 'link',
          ellipsis: true,
          resizable: true,
        },
      ];
    });

    const onSearch = data => {
      Object.entries(data).forEach(([key, value]) => (queryParam[key] = value));
      appliedFilters.value = data;
      fetchDataContext.requestParams = { ...queryParam };
    };

    const editOne = (record: LinkModel) => {
      console.log(record);
      editModalRef.model = record;
      editModalRef.open = true;
    };

    const deleteOne = (record: LinkModel) => {
      console.log(record);
      Modal.confirm({
        title: t('Delete'),
        content: `${t('Delete')}: ${record.notes}`,
        onOk() {
          loading.value = true;
          deleteLinksOneApi(record.id)
            .then(() => {
              message.success(t('Delete Success'));
              reloadTable();
            })
            .catch(() => message.error(t('Operation failed')));
        },
      });
    };

    const handleTableChange = ({ current, pageSize }: Pagination, _filters: any, _sorter: any) => {
      // filteredInfoMap.value = filters;
      // sorterInfoMap.value = sorter;
      fetchDataContext.current = current;
      fetchDataContext.pageSize = pageSize;
    };

    const { toClipboard } = useClipboard();
    const copyCell = async (text: any) => {
      try {
        await toClipboard(text);
        message.success(t('Copied'));
      } catch (e) {
        console.error(e);
      }
    };

    const handleShare = (record: LinkModel) => {
      const ids = [];
      ids.push(record.id);
      shareModalRef.model = {
        action: 'share',
        resourceList: ids,
      };
      shareModalRef.open = true;
    };

    return {
      loading,
      columns,
      dataSource,
      formSearchItems,
      onSearch,
      appliedFilters,
      tableHeight,
      t,
      dayjs,
      editOne,
      deleteOne,
      state,
      reloadTable,
      handleTableChange,
      modalRef,
      ReloadOutlined,
      h,
      editModalRef,
      copyCell,

      shareModalRef,
      shareApi,
      unshareApi,
      handleShare,
    };
  },
});
</script>

<style scoped>
.button-group {
  margin-bottom: 20px;
}
.margin-right {
  margin-right: 8px;
}
.ellipsis-container {
  max-width: 180px; /* 设置容器的宽度 */
  overflow: hidden; /* 隐藏超出内容 */
  white-space: nowrap; /* 不换行 */
  text-overflow: ellipsis; /* 添加省略号 */
  display: inline-block;
}
</style>
