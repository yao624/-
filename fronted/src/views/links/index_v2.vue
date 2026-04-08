<template>
  <page-container :title="t('pages.links.title')">
    <a-card mb-4>
      <a-row>
        <a-col :flex="1">
          <add-item ref="modalRef" @confirm:saved="reloadTable()"></add-item>
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
        <template v-if="column['dataIndex'] === 'link'">
          <copy-outlined style="color: #1677ff" v-if="text" @click="copyCell(text)" />
          <a-tooltip :title="text">
            {{ text }}
          </a-tooltip>
        </template>
        <template v-if="column['dataIndex'] === 'operation'">
          <a-button
            @click="editOne(record)"
            v-if="record.is_owner"
            type="link"
            :icon="h(EditOutlined)"
          ></a-button>
          <a-button
            @click="handleShare(record)"
            v-if="record.is_owner"
            type="link"
            :icon="h(ShareAltOutlined)"
          ></a-button>
          <a-button
            @click="deleteOne(record)"
            v-if="record.is_owner"
            type="link"
            :icon="h(DeleteOutlined)"
          ></a-button>
          <a-button
            type="link"
            @click="showTagModal(record)"
            v-if="record.is_owner"
            :icon="h(TagOutlined)"
          ></a-button>
        </template>
        <template v-if="column['dataIndex'] === 'created_at'">
          <span>{{ dayjs(text).format('YYYY-MM-DD HH:mm:ss') }}</span>
        </template>
        <template v-if="column['dataIndex'] === 'tags'">
          <div>
            <a-tag v-for="item in record.tags" :key="item.id">{{ item.name }}</a-tag>
          </div>
        </template>
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
    <tag-modal
      :model="tagModal.model"
      :visible="tagModal.visible"
      @cancel="
        () => {
          tagModal.visible = false;
        }
      "
      @ok="
        () => {
          tagModal.visible = false;
          reloadTable();
        }
      "
    />
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
import AddItem from './add-item.vue';
import EditItem from './edit-item.vue';
import TagModal from '../utils/tag-modal.vue';

import {
  CopyOutlined,
  ReloadOutlined,
  EditOutlined,
  ShareAltOutlined,
  DeleteOutlined,
  TagOutlined,
} from '@ant-design/icons-vue';
import { deleteLinksOneApi, queryLinksApi, shareLinksApi, unShareLinksApi } from '@/api/links';
import useClipboard from 'vue-clipboard3';
import ShareResource from '../utils/share-resource.vue';

export default defineComponent({
  components: {
    DynamicForm,
    ColumnOrgnizer,
    AddItem,
    EditItem,
    CopyOutlined,
    ShareResource,
    TagModal,
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
      { label: t('pages.links.link'), field: 'link' },
      { label: 'pages.notes', field: 'notes' },
    ]);

    const queryParam = reactive({
      link: null,
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

    const { context: state, reload: reloadTable } = useFetchData(queryLinksApi, fetchDataContext, {
      onRequestError: onRequestError,
    });

    const columns = computed<TableColumn[]>(() => {
      const sorted = sortedInfo.value || {};
      return [
        {
          title: t('pages.links.link'),
          dataIndex: 'link',
          resizable: true,
          ellipsis: true,
        },
        {
          title: t('pages.links.note'),
          dataIndex: 'notes',
          maxWidth: 300,
        },
        {
          title: t('pages.createdAt'),
          dataIndex: 'created_at',
          sorter: true,
          sortOrder: sorted.field === 'created_at' && sorted.order,
          maxWidth: 160,
        },
        {
          title: t('pages.tag'),
          dataIndex: 'tags',
          maxWidth: 200,
        },
        {
          title: t('pages.operation'),
          width: 180,
          dataIndex: 'operation',
          fixed: 'right',
        },
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
      fetchDataContext.current = current;
      fetchDataContext.pageSize = pageSize;
      sortedInfo.value = _sorter;
      fetchDataContext.requestParams = {
        ...queryParam,
        sortField: _sorter?.field,
        sortOrder: _sorter?.order
          ? _sorter.order === 'ascend'
            ? 'asc'
            : 'desc'
          : undefined,
      };
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

    // tagModal
    const tagModal = reactive({
      visible: false,
      model: null,
    });
    const showTagModal = record => {
      tagModal.model = {
        ids: [record['id']],
        action: 'add',
        tagList: record['tags'].map(item => item.name),
        modelType: 'links',
      };
      tagModal.visible = true;
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

      EditOutlined,
      ShareAltOutlined,
      DeleteOutlined,
      TagOutlined,

      // tag modal
      tagModal,
      showTagModal,
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
