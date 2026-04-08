<template>
  <page-container :title="t('pages.pages.title')">
    <a-card mb-4>
      <a-row>
        <a-col :flex="1">
          <add-item ref="modalRef" @confirm:saved="reloadTable()"></add-item>
        </a-col>
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
        </a-col>
      </a-row>
    </a-card>
    <a-table
      :loading="state.loading"
      :scroll="{ y: tableHeight }"
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
      bordered
      sticky
    >
      <template #bodyCell="{ column, text, record }">
        <template v-if="column['dataIndex'] === 'type'">
          {{ text === 'domain_whitelist' ? 'Domain whitelist' : text === 'url_whitelist' ? 'Url whitelist' : text }}
        </template>
        <template v-if="column['dataIndex'] === 'actions'">
          <div style="display: flex; flex-wrap: wrap; gap: 4px;">
            <a-tag v-for="a in record.actions" :key="a" size="small" color="geekblue">
              {{ a === 'tg_alert' ? 'TG Alert' : a === 'pause_ad' ? 'Pause Ad' : a === 'lock_card' ? 'Lock Card' : a }}
            </a-tag>
          </div>
        </template>
        <template v-if="column['dataIndex'] === 'value'">
          <div style="max-width: 380px;">
            <template v-if="Array.isArray(record.value)">
              <div v-if="record.value.length > 2">
                <!-- 显示前2个URL -->
                <div v-for="(url, index) in record.value.slice(0, 2)" :key="index"
                     style="margin-bottom: 2px; font-size: 11px; color: #555;
                            white-space: nowrap; overflow: hidden; text-overflow: ellipsis;
                            max-width: 360px; background: #f5f5f5; padding: 2px 6px;
                            border-radius: 3px;">
                  {{ url }}
                </div>
                <!-- 更多标签和查看全部在同一行 -->
                <div style="margin-top: 4px; display: flex; align-items: center; gap: 4px; flex-wrap: nowrap;">
                  <a-tag size="small" color="processing" style="margin: 0; flex-shrink: 0;">
                    +{{ record.value.length - 2 }} more
                  </a-tag>
                  <a-button type="link" size="small"
                            style="padding: 0; height: 16px; font-size: 11px; flex-shrink: 0; min-width: 45px;"
                            @click="showAllUrls(record.value)">
                    View All
                  </a-button>
                </div>
              </div>
              <div v-else>
                <!-- 2个或以下直接显示 -->
                <div v-for="(url, index) in record.value" :key="index"
                     style="margin-bottom: 2px; font-size: 11px; color: #555;
                            white-space: nowrap; overflow: hidden; text-overflow: ellipsis;
                            max-width: 360px; background: #f5f5f5; padding: 2px 6px;
                            border-radius: 3px;">
                  {{ url }}
                </div>
              </div>
            </template>
          </div>
        </template>
        <template v-if="column['dataIndex'] === 'excluded_ads'">
          <div style="max-width: 230px;">
            <template v-if="Array.isArray(record.excluded_ads) && record.excluded_ads.length > 0">
              <div v-if="record.excluded_ads.length > 2">
                <!-- 显示前2个Ad ID -->
                <div v-for="(adId, index) in record.excluded_ads.slice(0, 2)" :key="index"
                     style="margin-bottom: 2px; font-size: 11px; color: #555;
                            white-space: nowrap; overflow: hidden; text-overflow: ellipsis;
                            max-width: 210px; background: #fff2e8; padding: 2px 6px;
                            border-radius: 3px; border: 1px solid #ffb366;">
                  {{ adId }}
                </div>
                <!-- 更多标签和查看全部在同一行 -->
                <div style="margin-top: 4px; display: flex; align-items: center; gap: 4px; flex-wrap: nowrap;">
                  <a-tag size="small" color="orange" style="margin: 0; flex-shrink: 0;">
                    +{{ record.excluded_ads.length - 2 }} more
                  </a-tag>
                  <a-button type="link" size="small"
                            style="padding: 0; height: 16px; font-size: 11px; flex-shrink: 0; min-width: 45px;"
                            @click="showAllExcludedAds(record.excluded_ads)">
                    View All
                  </a-button>
                </div>
              </div>
              <div v-else>
                <!-- 2个或以下直接显示 -->
                <div v-for="(adId, index) in record.excluded_ads" :key="index"
                     style="margin-bottom: 2px; font-size: 11px; color: #555;
                            white-space: nowrap; overflow: hidden; text-overflow: ellipsis;
                            max-width: 210px; background: #fff2e8; padding: 2px 6px;
                            border-radius: 3px; border: 1px solid #ffb366;">
                  {{ adId }}
                </div>
              </div>
            </template>
            <template v-else>
              <span style="color: #999; font-style: italic; font-size: 12px;">No excluded ads</span>
            </template>
          </div>
        </template>
        <template v-if="column['dataIndex'] === 'action'">
          <a @click="editOne(record)">
            {{ t('Edit') }}
          </a>
          <a-divider type="vertical"></a-divider>
          <a @click="deleteOne(record)">
            {{ t('Delete') }}
          </a>
        </template>
        <template v-if="column['dataIndex'] == 'active'">
          <a-badge :color="text === true ? 'green' : 'gray'" />
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
    <edit-item
      :model="editModalRef.model"
      :open="editModalRef.open"
      @cancel="
        () => {
          editModalRef.open = false;
        }
      "
      @ok="
        () => {
          editModalRef.open = false;
          reloadTable();
        }
      "
    ></edit-item>

    <!-- View All URLs Modal -->
    <a-modal
      v-model:open="urlModalRef.open"
      :title="`All URLs (${urlModalRef.urls.length} items)`"
      :footer="null"
      :width="800"
    >
      <div style="max-height: 400px; overflow-y: auto;">
        <div v-for="(url, index) in urlModalRef.urls" :key="index"
             style="margin-bottom: 8px; padding: 8px; background: #f5f5f5;
                    border-radius: 4px; word-break: break-all; font-size: 12px;">
          <span style="color: #666; margin-right: 8px;">{{ index + 1 }}.</span>
          {{ url }}
        </div>
      </div>
    </a-modal>

    <!-- View All Excluded Ads Modal -->
    <a-modal
      v-model:open="excludedAdsModalRef.open"
      :title="`All Excluded Ads (${excludedAdsModalRef.adIds.length} items)`"
      :footer="null"
      :width="800"
    >
      <div style="max-height: 400px; overflow-y: auto;">
        <div v-for="(adId, index) in excludedAdsModalRef.adIds" :key="index"
             style="margin-bottom: 8px; padding: 8px; background: #fff2e8;
                    border-radius: 4px; word-break: break-all; font-size: 12px;
                    border: 1px solid #ffb366;">
          <span style="color: #666; margin-right: 8px;">{{ index + 1 }}.</span>
          {{ adId }}
        </div>
      </div>
    </a-modal>
  </page-container>
</template>

<script lang="ts">
import { computed, defineComponent, ref, reactive, h } from 'vue';
import ColumnOrgnizer from '@/components/column-orgnizer/column-orgnizer.vue';
import { useI18n } from 'vue-i18n';
import type { LinkModel, ShareModel } from '@/utils/fb-interfaces';
import type { TableRowSelection } from 'ant-design-vue/es/table/interface';
import type { DefaultRecordType } from 'ant-design-vue/es/vc-table/interface';
import { useTableHeight } from '@/utils/hooks/useTableHeight';
import dayjs from 'dayjs';
import { useFetchData } from '@/utils/hooks/useFetchData';
import { message, Modal } from 'ant-design-vue';
import type { Pagination, TableColumn } from '@/typing';
import EditItem from './edit-item.vue';
import AddItem from './add-item.vue';

import { ReloadOutlined } from '@ant-design/icons-vue';
import { shareLinksApi, unShareLinksApi } from '@/api/links';
import useClipboard from 'vue-clipboard3';
import { deleteFraudConfig, getFraudConfigList } from '@/api/fraud_config';

export default defineComponent({
  components: {
    ColumnOrgnizer,
    EditItem,
    AddItem,
  },
  setup() {
    const { t } = useI18n();
    const loading = ref(false);
    const dataSource = ref<LinkModel[]>([]);
    const appliedFilters = ref({});
    const modalRef = ref(null);
    const editModalRef = reactive({
      model: null,
      open: false,
    });

    const urlModalRef = reactive({
      open: false,
      urls: [],
    });

    const excludedAdsModalRef = reactive({
      open: false,
      adIds: [],
    });
    const shareApi = shareLinksApi;
    const unshareApi = unShareLinksApi;
    const shareModalRef = reactive({
      open: false,
      model: {} as ShareModel,
    });

    const { tableHeight } = useTableHeight();

    const formSearchItems = ref([
      { label: t('Form name'), field: 'name' },
      { label: t('Notes'), field: 'notes' },
      { label: t('Page ID'), field: 'page_source_id' },
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

    const { context: state, reload: reloadTable } = useFetchData(
      getFraudConfigList,
      fetchDataContext,
      {
        onRequestError: onRequestError,
      },
    );

    const columns = computed<TableColumn[]>(() => {
      // const sorted = sortedInfo.value || {};
      return [
        { title: t('Type'), dataIndex: 'type', key: 'type', width: 120, align: 'center' },
        {
          title: t('Value'),
          dataIndex: 'value',
          key: 'value',
          width: 300,
          autoHeight: true as any,
          align: 'left',
        },
        {
          title: t('Actions'),
          dataIndex: 'actions',
          key: 'actions',
          width: 180,
          autoHeight: true as any,
          align: 'center',
        },
        {
          title: 'Excluded Ads',
          dataIndex: 'excluded_ads',
          key: 'excluded_ads',
          width: 250,
          autoHeight: true as any,
          align: 'left',
        },
        { title: t('Status'), dataIndex: 'active', key: 'active', width: 100, align: 'center' },
        { title: t('Action'), dataIndex: 'action', key: 'action', width: 120, fixed: 'right', align: 'center' },
      ];
    });

    const rowSelection = computed<TableRowSelection<DefaultRecordType>>(() => {
      return {
        type: 'checkbox',
      };
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
        content: `${t('Delete config')}`,
        onOk() {
          loading.value = true;
          deleteFraudConfig(record.id)
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

    const showAllUrls = (urls: string[]) => {
      urlModalRef.urls = urls || [];
      urlModalRef.open = true;
    };

    const showAllExcludedAds = (adIds: string[]) => {
      excludedAdsModalRef.adIds = adIds || [];
      excludedAdsModalRef.open = true;
    };

    return {
      loading,
      columns,
      dataSource,
      rowSelection,
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

      urlModalRef,
      showAllUrls,
      excludedAdsModalRef,
      showAllExcludedAds,
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

/* 表格行高度优化 */
:deep(.ant-table-tbody > tr > td) {
  vertical-align: top !important;
  line-height: 1.4 !important;
}

/* Value列内容优化 */
:deep(.ant-table-tbody > tr > td:nth-child(2)) {
  word-break: break-word;
}
</style>
