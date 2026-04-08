<template>
  <page-container :title="t('pages.pages.title')">
    <a-card mb-4>
      <a-row>
        <a-col :flex="1"></a-col>
        <a-col>
          <a-tooltip :title="t('表单数据来自主页同步，请先在主页管理中对主页执行「Sync forms」')">
            <a-button type="primary" :icon="h(PlusOutlined)" @click="onSyncFormsHint">
              {{ t('同步表单') }}
            </a-button>
          </a-tooltip>
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
          <dynamic-form :form-items="formSearchItems" @change:form-data="onSearch"></dynamic-form>
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
      :row-selection="rowSelection"
      :row-key="record => record.id"
      @change="handleTableChange"
    >
      <template #bodyCell="{ column, text, record }">
        <template v-if="column['dataIndex'] === 'follow_up_action_url'">
          <copy-outlined style="color: #1677ff" v-if="text" @click="copyCell(text)" />
          <a-tooltip :title="text">
            {{ text }}
          </a-tooltip>
        </template>
        <template v-if="column['dataIndex'] === 'action'">
          <a @click="editOne(record)">
            {{ t('Edit Notes') }}
          </a>
        </template>
        <template v-if="column['dataIndex'] === 'created_time'">
          <span>{{ dayjs(text).format('YYYY-MM-DD HH:mm:ss') }}</span>
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

    <share-resource
      :model="shareModalRef.model"
      :open="shareModalRef.open"
      :share-api="shareApi"
      :unshare-api="unshareApi"
      @cancel="
        () => {
          shareModalRef.open = false;
        }
      "
      @ok="
        () => {
          shareModalRef.open = false;
          reloadTable();
        }
      "
    ></share-resource>
  </page-container>
</template>

<script lang="ts">
import { computed, defineComponent, ref, reactive, h } from 'vue';
import DynamicForm from '@/components/dynamic-form/dynamic-form.vue';
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
import 'viplayer/dist/index.css';
import EditItem from './edit-item.vue';

import { CopyOutlined, ReloadOutlined, PlusOutlined } from '@ant-design/icons-vue';
import { deleteLinksOneApi, shareLinksApi, unShareLinksApi } from '@/api/links';
import useClipboard from 'vue-clipboard3';
import ShareResource from '../utils/share-resource.vue';
import { getPageForms } from '@/api/pages';

export default defineComponent({
  components: {
    DynamicForm,
    ColumnOrgnizer,
    EditItem,
    CopyOutlined,
    ShareResource,
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

    const { context: state, reload: reloadTable } = useFetchData(getPageForms, fetchDataContext, {
      onRequestError: onRequestError,
    });

    const columns = computed<TableColumn[]>(() => {
      // const sorted = sortedInfo.value || {};
      return [
        { title: t('Name'), dataIndex: 'name', key: 'name', resizable: true },
        { title: t('ID'), dataIndex: 'source_id', key: 'age' },
        { title: t('Status'), dataIndex: 'status', key: 'status', width: 80 },
        // { title: t('Thank You Page'), dataIndex: 'thank_you_page', key: 'thank_you_page' },
        {
          title: t('Follow Up Action URL'),
          dataIndex: 'follow_up_action_url',
          key: 'follow_up_action_url',
          resizable: true,
        },
        { title: t('Page ID'), dataIndex: 'page_source_id', key: 'page_source_id' },
        // { title: t('Page Name'), dataIndex: 'page_name', key: 'page_name' },
        { title: t('Notes'), dataIndex: 'notes', key: 'notes', resizable: true },
        { title: t('Created Time'), dataIndex: 'created_time', key: 'created_time' },
        { title: t('Action'), dataIndex: 'action', key: 'action' },
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

    const onSyncFormsHint = () => {
      message.info(t('表单数据来自主页同步，请先在「主页管理」中对相应主页执行「Sync forms」'));
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
      rowSelection,
      formSearchItems,
      onSearch,
      appliedFilters,
      tableHeight,
      t,
      dayjs,
      onSyncFormsHint,
      editOne,
      deleteOne,
      state,
      reloadTable,
      handleTableChange,
      modalRef,
      ReloadOutlined,
      PlusOutlined,
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
