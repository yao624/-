<template>
  <page-container :title="t('pages.pages.title')">
    <a-card mb-4>
      <a-row>
        <a-col :flex="1">
          <upload-material ref="uploadModal" @confirm:uploaded="reloadTable()"></upload-material>
        </a-col>
        <!-- <a-col :flex="1">
          <applied-filters :filters="appliedFilters"></applied-filters>
        </a-col> -->
        <a-col>
          <a-tooltip title="刷新">
            <a-button shape="circle" :icon="h(ReloadOutlined)" @click="reloadTable"></a-button>
          </a-tooltip>
        </a-col>
        <a-col>
          <column-orgnizer
            :columns="columns"
            @change:columns="data => (columns = data)"
          ></column-orgnizer>
          <dynamic-form :form-items="formItems" @change:form-data="onSearch"></dynamic-form>
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
        pageSizeOptions: ['10'],
        showTotal: total => `Total ${total} items`,
      }"
      :row-selection="rowSelection"
      :row-key="record => record.id"
      @change="handleTableChange"
    >
      <template #bodyCell="{ column, text, record }">
        <template v-if="column['dataIndex'] === 'operation'">
          <a-button @click="editOne(record)" :icon="h(EditOutlined)" type="link"></a-button>
          <a-button
            type="link"
            @click="handleShare(record)"
            v-if="record.is_owner"
            :icon="h(ShareAltOutlined)"
          ></a-button>
          <a-button
            type="link"
            @click="deleteOne(record)"
            v-if="record.is_owner"
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
        <!-- <template v-if="column['dataIndex'] === 'url'">
          <a-tooltip :title="text">
            <div class="ellipsis-container">
              <span>{{ text }}</span>
            </div>
          </a-tooltip>
        </template> -->
        <template v-if="column['dataIndex'] === 'url'">
          <template v-if="isImage(record.filename)">
            <img
              :src="text"
              :alt="record.filename"
              width="50"
              height="50"
              @click="handlePreview(text, 'image')"
            />
          </template>
          <template v-else-if="isVideo(record.filename)">
            <a class="href-btn" @click="handlePreview(text, 'video')">
              {{ t('pages.preview.video') }}
            </a>
          </template>
        </template>
        <template v-if="column['dataIndex'] === 'tags'">
          <div>
            <a-tag v-for="item in record.tags" :key="item.id">{{ item.name }}</a-tag>
          </div>
        </template>
      </template>
    </a-table>
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
import type { Material, ShareModel, MaterialEditModel } from '@/utils/fb-interfaces';
import {
  deletOneMaterialsApi,
  materialsList,
  shareMaterialsApi,
  unShareMaterialsApi,
} from '@/api/materials';
import type { TableRowSelection } from 'ant-design-vue/es/table/interface';
import type { DefaultRecordType } from 'ant-design-vue/es/vc-table/interface';
import { useTableHeight } from '@/utils/hooks/useTableHeight';
import dayjs from 'dayjs';
import { useFetchData } from '@/utils/hooks/useFetchData';
import { message, Modal } from 'ant-design-vue';
import type { Pagination } from '@/typing';
import 'viplayer/dist/index.css';
import { videoPlay } from 'viplayer';
import UploadMaterial from './upload-material.vue';
import {
  ReloadOutlined,
  EditOutlined,
  ShareAltOutlined,
  DeleteOutlined,
  TagOutlined,
} from '@ant-design/icons-vue';
import ShareResource from '../utils/share-resource.vue';
import EditItem from './edit-item.vue';
import TagModal from '../utils/tag-modal.vue';
// interface Query {
//   name?: string;
//   notes?: string;
//   pageNo: number;
//   pageSize: number;
//   sortOrder?: string;
//   sortField?: string;
//   tags?: string;
// }

export default defineComponent({
  components: {
    DynamicForm,
    ColumnOrgnizer,
    UploadMaterial,
    ShareResource,
    EditItem,
    TagModal,
  },
  setup() {
    const { t } = useI18n();
    const loading = ref(false);
    const sortedInfo = ref();
    const dataSource = ref<Material[]>([]);
    const appliedFilters = ref({});
    const uploadModal = ref(null);
    const shareApi = shareMaterialsApi;
    const unshareApi = unShareMaterialsApi;

    const { tableHeight } = useTableHeight();

    const shareModalRef = reactive({
      open: false,
      model: {} as ShareModel,
    });

    const formItems = ref([
      { label: 'pages.name', field: 'name' },
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

    const { context: state, reload: reloadTable } = useFetchData(materialsList, fetchDataContext, {
      onRequestError: onRequestError,
    });

    const columns = computed<any[]>(() => {
      const sorted = sortedInfo.value || {};
      return [
        {
          title: t('pages.name'),
          dataIndex: 'name',
        },
        {
          title: t('Preview'),
          dataIndex: 'url',
          ellipsis: true,
        },
        {
          title: t('pages.filename'),
          dataIndex: 'filename',
          ellipsis: true,
          resizable: true,
        },

        {
          title: t('pages.proxies.notes'),
          dataIndex: 'notes',
        },
        {
          title: t('pages.createdAt'),
          dataIndex: 'created_at',
          sorter: true,
          sortOrder: sorted.field === 'created_at' && sorted.order,
        },
        {
          title: t('pages.tag'),
          dataIndex: 'tags',
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

    const deleteOne = (record: Material) => {
      console.log(record);
      Modal.confirm({
        title: t('Delete'),
        content: `${t('Delete')}: ${record.name}`,
        onOk() {
          loading.value = true;
          deletOneMaterialsApi(record.id)
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

    const isImage = filename => {
      const imageExtensions = ['jpeg', 'jpg', 'png', 'gif'];
      const extension = filename.split('.').pop().toLowerCase();
      return imageExtensions.includes(extension);
    };
    const isVideo = filename => {
      const videoExtensions = ['mp4', 'mov', 'avi', 'wmv', 'zip'];
      const extension = filename.split('.').pop().toLowerCase();
      return videoExtensions.includes(extension);
    };

    const handlePreview = (src: string, type: string) => {
      const title = type === 'image' ? 'Image Preview' : 'Video Preview';
      let contentNode;

      if (type === 'image') {
        contentNode = h('img', { src, style: 'max-width: 100%;' });
      } else if (type === 'video') {
        contentNode = h(videoPlay, { src, width: '750px', autoPlay: true });
      }
      const modalContent = () => h('div', { style: 'text-align: center;' }, [contentNode]);

      Modal.info({
        title: title,
        content: modalContent,
        maskClosable: true,
        icon: null,
        width: 800,
      });
    };

    const handleShare = (record: Material) => {
      const ids = [];
      ids.push(record.id);
      shareModalRef.model = {
        action: 'share',
        resourceList: ids,
      };
      shareModalRef.open = true;
    };

    const editModalRef = reactive({
      model: null,
      open: false,
    });

    const editOne = (record: MaterialEditModel) => {
      console.log(record);
      editModalRef.model = record;
      editModalRef.open = true;
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
        modelType: 'materials',
      };
      tagModal.visible = true;
    };

    return {
      loading,
      columns,
      dataSource,
      rowSelection,
      formItems,
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
      isImage,
      isVideo,
      handlePreview,
      uploadModal,
      ReloadOutlined,
      h,
      shareModalRef,
      shareApi,
      unshareApi,
      handleShare,
      editModalRef,
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
