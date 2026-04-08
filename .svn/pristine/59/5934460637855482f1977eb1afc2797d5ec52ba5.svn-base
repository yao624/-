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
        pageSizeOptions: ['10', '20', '50', '100', '200'],
        showTotal: total => `Total ${total} items`,
      }"
      :row-selection="rowSelection"
      :row-key="record => record.id"
      @change="handleTableChange"
      stripe
    >
      <template #bodyCell="{ column, text, record }">
        <template v-if="column['dataIndex'] === 'operation'">
          <a @click="handleSyncBm(record)">
            <sync-outlined />
          </a>
          <a-divider type="vertical" />
          <a @click="showTagModal(record)"><tag-outlined /></a>
          <a-divider type="vertical" v-if="record.is_owner" />
          <a @click="deleteOne(record)" v-if="record.is_owner">{{ t('Delete') }}</a>
        </template>
        <template v-if="column['dataIndex'] === 'created_time'">
          <span>{{ text ? dayjs(text).format('YYYY-MM-DD HH:mm:ss') : '' }}</span>
        </template>
        <template v-if="['source_id'].includes(`${column['dataIndex']}`)">
          <copy-outlined style="color: #1677ff" v-if="text" @click="copyCell(text)" />
          {{ text }}
        </template>
        <template v-if="['users'].includes(`${column['dataIndex']}`)">
          <a-button type="link" @click="showBmUserModal(record)">
            {{ record.users?.length }}
          </a-button>
        </template>
        <template v-if="['name'].includes(`${column['dataIndex']}`)">
          <copy-outlined style="color: #1677ff" v-if="text" @click="copyCell(text)" />
          {{ text }}
        </template>
        <template v-if="['ad_accounts'].includes(`${column['dataIndex']}`)">
          <a-button type="link" @click="showAdAccountModal(record)">
            {{ record.ad_accounts?.length }}
          </a-button>
        </template>
        <template v-if="['catalogs'].includes(`${column['dataIndex']}`)">
          <a-button type="link" @click="showFbCatalogModal(record)">
            {{ record.catalogs?.length }}
          </a-button>
          <a-button type="link" @click="showCreateCatalogModal(record.id)">
            <plus-circle-outlined></plus-circle-outlined>
          </a-button>
        </template>
        <template v-if="['pixels'].includes(`${column['dataIndex']}`)">
          <a-button type="link" @click="showFbPixelModal(record)">
            {{ record.pixels?.length }}
          </a-button>
        </template>
        <template v-if="['pages'].includes(`${column['dataIndex']}`)">
          <a-button type="link" @click="showFbPageModal(record)" size="small">
            {{ record.pages?.length }}
          </a-button>
          <!-- <a-button type="link" @click="showFbPageSearchModal()" size="small">
            <plus-circle-outlined style="color: #1677ff" />
          </a-button> -->
          <pick-objects
            :multiple="true"
            :api="queryPagesApi"
            :allow-empty="false"
            :columns="[
              { title: 'ID', dataIndex: 'source_id', key: 'id' },
              { title: t('Page Name'), dataIndex: 'name', key: 'name' },
            ]"
            :iconType="2"
            @confirm:items-selected="(_, rows) => handleClaimPages(record.id, rows)"
          ></pick-objects>
        </template>
        <template v-if="['fb_api_token'].includes(`${column['dataIndex']}`)">
          <a-button type="link" @click="showFbApiTokenModal(record)">
            {{ record.fb_api_token?.length }}
          </a-button>
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

    <ad-account-modal
      :model="adAccountModal.model"
      :visible="adAccountModal.visible"
      @cancel="
        () => {
          adAccountModal.visible = false;
        }
      "
      @ok="
        () => {
          adAccountModal.visible = false;
        }
      "
    />
    <fb-pixel-modal
      :model="fbPixelModal.model"
      :visible="fbPixelModal.visible"
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
    <fb-page-modal
      :model="fbPageModal.model"
      :visible="fbPageModal.visible"
      @cancel="
        () => {
          fbPageModal.visible = false;
        }
      "
      @ok="
        () => {
          fbPageModal.visible = false;
        }
      "
    />

    <fb-api-token-modal
      :model="fbApiTokenModal.model"
      :visible="fbApiTokenModal.visible"
      @cancel="
        () => {
          fbApiTokenModal.visible = false;
        }
      "
      @ok="
        () => {
          fbApiTokenModal.visible = false;
        }
      "
    />
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

    <bm-user-modal
      :model="bmUserModal.model"
      :visible="bmUserModal.visible"
      @cancel="
        () => {
          bmUserModal.visible = false;
        }
      "
      @ok="
        () => {
          bmUserModal.visible = false;
        }
      "
    />

    <fb-catalog-modal
      :model="fbCatalogModal.model"
      :visible="fbCatalogModal.visible"
      @cancel="
        () => {
          fbCatalogModal.visible = false;
        }
      "
      @ok="
        () => {
          fbCatalogModal.visible = false;
        }
      "
    />

    <!-- 创建单个 Catalog 的 Modal -->
    <a-modal
      v-model:visible="isCreateCatalogModalVisible"
      :title="t('Catalog')"
      :confirm-loading="isCreatingCatalog"
      :destroyOnClose="true"
      @ok="handleCreateCatalogOk"
      :ok-text="t('Create')"
      :cancel-text="t('Cancel')"
    >
      <a-form
        ref="catalogFormRef"
        :model="catalogFormState"
        layout="vertical"
        name="create_catalog_form"
      >
        <a-form-item
          :label="t('Name')"
          name="catalogName"
          :rules="[
            {
              required: true,
              message: t('Required'),
              whitespace: true,
              trigger: 'blur',
            },
          ]"
        >
          <a-input
            v-model:value="catalogFormState.catalogName"
            :placeholder="t('Enter catalog name')"
            ref="catalogNameInputRef"
          />
        </a-form-item>
      </a-form>
    </a-modal>
  </page-container>
</template>

<script lang="ts">
import { computed, defineComponent, ref, reactive, h, nextTick } from 'vue';
import DynamicForm from '@/components/dynamic-form/dynamic-form.vue';
import ColumnOrgnizer from '@/components/column-orgnizer/column-orgnizer.vue';
import { useI18n } from 'vue-i18n';
import type { CopywritingModel, Material, ShareModel } from '@/utils/fb-interfaces';
import type { TableRowSelection } from 'ant-design-vue/es/table/interface';
import type { DefaultRecordType } from 'ant-design-vue/es/vc-table/interface';
import { useTableHeight } from '@/utils/hooks/useTableHeight';
import dayjs from 'dayjs';
import { useFetchData } from '@/utils/hooks/useFetchData';
import type { FormInstance } from 'ant-design-vue';
import { message, Modal } from 'ant-design-vue';
import type { Pagination, TableColumn } from '@/typing';
import 'viplayer/dist/index.css';
import ShareResource from '../utils/share-resource.vue';
import FbApiTokenModal from '../utils/fb-api-token-modal.vue';
import FbPixelModal from './modal/fb-pixel-modal.vue';
import AdAccountModal from './modal/ad-account-modal.vue';
import FbPageModal from './modal/fb-page-modal.vue';
import BmUserModal from './modal/bm-user-modal.vue';
import FbCatalogModal from './modal/fb-catalog-modal.vue';
import PickObjects from '../utils/pick-objects.vue';
import TagModal from '../utils/tag-modal.vue';
import useClipboard from 'vue-clipboard3';
import { queryPagesApi } from '@/api/pages';

import {
  ReloadOutlined,
  CopyOutlined,
  SyncOutlined,
  TagOutlined,
  PlusCircleOutlined,
} from '@ant-design/icons-vue';
import {
  deleteCopywritingsApi,
  shareCopywritingsApi,
  unShareCopywritingsApi,
} from '@/api/copywritings';
import { claimPagesApi, createCatalogApi, queryBMsApi, syncBmApi } from '@/api/fb_bms';
export default defineComponent({
  components: {
    DynamicForm,
    ColumnOrgnizer,
    ShareResource,
    CopyOutlined,
    FbApiTokenModal,
    AdAccountModal,
    FbPixelModal,
    FbPageModal,
    SyncOutlined,
    TagOutlined,
    PickObjects,
    TagModal,
    BmUserModal,
    FbCatalogModal,
    PlusCircleOutlined,
  },
  setup() {
    const { t } = useI18n();
    const loading = ref(false);
    const sortedInfo = ref();
    const dataSource = ref<Material[]>([]);
    const appliedFilters = ref({});
    const modalRef = ref(null);
    const editModalRef = reactive({
      model: null,
      open: false,
    });
    const shareApi = shareCopywritingsApi;
    const unshareApi = unShareCopywritingsApi;
    const shareModalRef = reactive({
      open: false,
      model: {} as ShareModel,
    });

    const { tableHeight } = useTableHeight();

    // tagModal
    const tagModal = reactive({
      visible: false,
      model: null,
    });

    const formSearchItems = ref([
      { label: t('ID'), field: 'source_id' },
      { label: t('Name'), field: 'name' },
      { label: t('Ad Account ID'), field: 'ad_account_id' },
      { label: t('Pixel ID'), field: 'pixel_id' },
      { label: t('Page ID'), field: 'page_id' },
      { label: t('Tags'), field: 'tags', multiple: true },
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

    const { context: state, reload: reloadTable } = useFetchData(queryBMsApi, fetchDataContext, {
      onRequestError: onRequestError,
    });

    const columns = computed<TableColumn[]>(() => {
      const sorted = sortedInfo.value || {};
      return [
        {
          title: t('#'),
          dataIndex: 'index',
          customRender: ({ index }) => {
            return `${index + 1}`;
          },
          width: 100,
          align: 'center',
          fixed: 'left',
        },
        {
          title: t('ID'),
          dataIndex: 'source_id',
          ellipsis: true,
          resizable: true,
          minWidth: 170,
        },
        {
          title: t('Name'),
          dataIndex: 'name',
          ellipsis: true,
          resizable: true,
          minWidth: 150,
        },
        {
          title: t('People'),
          dataIndex: 'users',
          minWidth: 120,
          align: 'center',
        },
        {
          title: t('Ad Account'),
          dataIndex: 'ad_accounts',
          minWidth: 120,
          align: 'center',
        },
        {
          title: t('Pages'),
          dataIndex: 'pages',
          minWidth: 100,
          align: 'center',
        },
        {
          title: t('Catalogs'),
          dataIndex: 'catalogs',
          minWidth: 100,
          align: 'center',
        },
        {
          title: t('Pixels'),
          dataIndex: 'pixels',
          minWidth: 100,
          align: 'center',
        },
        {
          title: t('FB API Token'),
          dataIndex: 'fb_api_token',
          minWidth: 120,
          align: 'center',
        },
        {
          title: t('Created Time'),
          dataIndex: 'created_time',
          sorter: true,
          sortOrder: sorted.field === 'created_at' && sorted.order,
          minWidth: 180,
        },
        {
          title: t('pages.tag'),
          dataIndex: 'tags',
          minWidth: 100,
        },
        {
          title: t('pages.operation'),
          width: 120,
          dataIndex: 'operation',
          fixed: 'right',
          align: 'center',
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

    const editOne = (record: CopywritingModel) => {
      console.log(record);
      editModalRef.model = record;
      editModalRef.open = true;
    };

    const deleteOne = (record: Material) => {
      console.log(record);
      Modal.confirm({
        title: t('Delete'),
        content: `${t('Delete')}: ${record.name}`,
        onOk() {
          loading.value = true;
          deleteCopywritingsApi(record.id)
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

    const handleShare = (record: CopywritingModel) => {
      const ids = [];
      ids.push(record.id);
      shareModalRef.model = {
        action: 'share',
        resourceList: ids,
      };
      shareModalRef.open = true;
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

    // fb api token modal
    const fbApiTokenModal = reactive({
      visible: false,
      model: null,
    });
    const showFbApiTokenModal = record => {
      console.log('show fb api token modal');
      fbApiTokenModal.model = {
        bm_system_users: record['fb_api_token'],
        ad_accounts: record['ad_accounts'],
      };
      fbApiTokenModal.visible = true;
    };

    // ad account modal
    const adAccountModal = reactive({
      visible: false,
      model: null,
    });
    const showAdAccountModal = record => {
      adAccountModal.model = {
        data_source: record['ad_accounts'],
      };
      adAccountModal.visible = true;
    };

    // fb pixel modal
    const fbPixelModal = reactive({
      visible: false,
      model: null,
    });
    const showFbPixelModal = record => {
      fbPixelModal.model = {
        data_source: record,
      };
      fbPixelModal.visible = true;
    };

    // fb page modal
    const fbPageModal = reactive({
      visible: false,
      model: null,
    });
    const showFbPageModal = record => {
      fbPageModal.model = {
        data_source: record['pages'],
      };
      fbPageModal.visible = true;
    };

    // fb
    const handleSyncBm = record => {
      syncBmApi({
        ids: [record['id']],
      })
        .then(res => {
          message.success(res['message']);
          reloadTable();
        })
        .catch(() => message.error(t('Operation failed')));
    };

    // fb page search modal
    const fbPageSearchModal = reactive({
      visible: false,
      model: null,
    });
    const showFbPageSearchModal = () => {
      fbPageSearchModal.model = {
        data_source: [],
      };
      fbPageSearchModal.visible = true;
    };

    const handleClaimPages = (bm_id, rows) => {
      claimPagesApi({
        bm_id,
        ids: rows.map(item => item.id),
      })
        .then(res => {
          message.success(res['message']);
          reloadTable();
        })
        .catch(() => message.error(t('Operation failed')));
    };

    const showTagModal = record => {
      tagModal.model = {
        ids: [record['id']],
        action: 'add',
        tagList: record['tags'].map(item => item.name),
        modelType: 'fbbm',
      };
      tagModal.visible = true;
    };

    // fb page search modal
    const bmUserModal = reactive({
      visible: false,
      model: null,
    });
    const showBmUserModal = record => {
      bmUserModal.model = {
        data_source: record,
      };
      bmUserModal.visible = true;
    };

    // fb catalog list
    const fbCatalogModal = reactive({
      visible: false,
      model: null,
    });
    const showFbCatalogModal = record => {
      fbCatalogModal.model = {
        data_source: record,
      };
      fbCatalogModal.visible = true;
    };

    // create catalog
    const isCreateCatalogModalVisible = ref(false);
    const isCreatingCatalog = ref(false);
    const catalogFormRef = ref<FormInstance>(); // a-form 的引用
    const catalogNameInputRef = ref(null); // Input 引用，用于聚焦
    const selectedBmId = ref('');

    const catalogFormState = reactive({
      catalogName: '',
    });
    const showCreateCatalogModal = bm_id => {
      isCreateCatalogModalVisible.value = true;
      selectedBmId.value = bm_id;
      // 由于 destroyOnClose=true, 表单状态会自动重置
      // catalogFormState.catalogName = ''; // 如果不用 destroyOnClose 则需要手动重置
      // formRef.value?.resetFields(); // 同样，如果不用 destroyOnClose

      // 模态框显示后自动聚焦到输入框
      nextTick(() => {
        catalogNameInputRef.value?.focus();
      });
    };
    const handleCreateCatalogOk = async () => {
      console.log('create catalog');
      const payload = {
        bm_id: selectedBmId.value,
        name: catalogFormState.catalogName,
      };
      isCreatingCatalog.value = true;
      try {
        // 调用 API 保存数据
        await createCatalogApi(payload);
        message.success(t('Submitted')); // 显示成功消息
        isCreateCatalogModalVisible.value = false; // API 调用成功后关闭模态框
        catalogFormState.catalogName = '';
        // 在此可以添加刷新列表等成功后的操作
      } catch (error: any) {
        // 处理 API 调用错误
        console.error('创建catalog失败:', error);
        message.error(t('Failed')); // 显示通用失败消息
        message.error(error.message || t('error.unknownApiError')); // 显示具体错误信息
        // API 调用失败时，保持模态框打开，让用户可以修改或重试
      } finally {
        isCreatingCatalog.value = false;
      }
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

      // share
      shareModalRef,
      shareApi,
      unshareApi,
      handleShare,

      // copy
      copyCell,

      // fb api token modal
      fbApiTokenModal,
      showFbApiTokenModal,

      // ad account modal
      adAccountModal,
      showAdAccountModal,

      // fb pixel modal
      fbPixelModal,
      showFbPixelModal,

      // fb page modal
      fbPageModal,
      showFbPageModal,

      // sync bm
      handleSyncBm,

      // fb page search modal
      fbPageSearchModal,
      showFbPageSearchModal,
      queryPagesApi,

      // claim pages
      handleClaimPages,

      // tag modal
      tagModal,
      showTagModal,

      // bm user modal
      bmUserModal,
      showBmUserModal,

      // fb catalog modal
      fbCatalogModal,
      showFbCatalogModal,

      // create catalog
      isCreateCatalogModalVisible,
      isCreatingCatalog,
      catalogFormRef,
      catalogNameInputRef,
      catalogFormState,
      showCreateCatalogModal,
      handleCreateCatalogOk,
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
