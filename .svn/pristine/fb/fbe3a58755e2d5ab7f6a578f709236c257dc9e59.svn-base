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
      :row-key="record => record.id"
      @change="handleTableChange"
    >
      <template #bodyCell="{ column, text, record }">
        <template v-if="column['dataIndex'] === 'actions'">
          <a-tag v-for="a in record.actions" :key="a">{{ a }}</a-tag>
        </template>
        <template v-if="column['dataIndex'] === 'value'">
          <a-tag v-for="a in record.value" :key="a">{{ a }}</a-tag>
        </template>
        <template v-if="column['dataIndex'] === 'action'">
          <a @click="editOne(record)">
            {{ t('Edit') }}
          </a>
          <a-divider type="vertical"></a-divider>
          <a @click="syncResource(record)">
            {{ t('Sync') }}
          </a>
          <a-divider type="vertical"></a-divider>
          <a @click="deleteOne(record)">
            {{ t('Delete') }}
          </a>
        </template>
        <template v-if="column['dataIndex'] == 'active'">
          <a-badge :color="text === true ? 'green' : 'gray'" />
        </template>
        <template v-if="column['dataIndex'] == 'token_type'">
          <span v-if="text === 1">BM</span>
          <span v-if="text === 3">BM(2)</span>
          <span v-if="text === 2">App</span>
        </template>

        <template v-if="column['dataIndex'] === 'token'">
          <a-tooltip :title="text">
            <div class="ellipsis-container">
              <span>{{ text }}</span>
            </div>
          </a-tooltip>
        </template>
        <template v-if="column['dataIndex'] === 'created_at'">
          <span>{{ dayjs(text).format('YYYY-MM-DD HH:mm:ss') }}</span>
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
  </page-container>
</template>

<script lang="ts">
import { computed, defineComponent, ref, reactive, h } from 'vue';
import ColumnOrgnizer from '@/components/column-orgnizer/column-orgnizer.vue';
import DynamicForm from '@/components/dynamic-form/dynamic-form.vue';
import { useI18n } from 'vue-i18n';
import type { FbApiTokenModel, LinkModel } from '@/utils/fb-interfaces';
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
import useClipboard from 'vue-clipboard3';
import { deleteFbApiToken, getFbApiTokens, syncTokenResourceApi } from '@/api/fb_api_token';

export default defineComponent({
  components: {
    ColumnOrgnizer,
    DynamicForm,
    EditItem,
    AddItem,
  },
  setup() {
    const { t } = useI18n();
    const loading = ref(false);
    const dataSource = ref<any[]>([]);
    const appliedFilters = ref({});
    const modalRef = ref(null);
    const editModalRef = reactive({
      model: null,
      open: false,
    });

    const { tableHeight } = useTableHeight();

    const formSearchItems = ref([
      { label: t('Name'), field: 'name' },
      { label: t('Notes'), field: 'notes' },
      { label: t('BM ID'), field: 'bm_id' },
    ]);

    const queryParam = reactive({
      name: null,
      notes: null,
      bm_id: null,
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

    const { context: state, reload: reloadTable } = useFetchData(getFbApiTokens, fetchDataContext, {
      onRequestError: onRequestError,
    });

    const columns = computed<TableColumn[]>(() => {
      // const sorted = sortedInfo.value || {};
      return [
        { title: t('Created Time'), dataIndex: 'created_at', key: 'created_at' },
        // { title: t('Updated At'), dataIndex: 'updated_at', key: 'updated_at' },
        { title: t('Name'), dataIndex: 'name', key: 'name' },
        { title: t('BM ID'), dataIndex: 'bm_id', key: 'bm_id' },
        { title: t('Token'), dataIndex: 'token', key: 'token', ellipsis: true },
        { title: t('Active'), dataIndex: 'active', key: 'active' },
        { title: t('Type'), dataIndex: 'token_type', key: 'token_type' },
        { title: 'App', dataIndex: 'app', key: 'app' },
        { title: t('Notes'), dataIndex: 'notes', key: 'notes' },
        { title: t('Action'), dataIndex: 'action', key: 'action', fixed: 'right' },
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

    const deleteOne = (record: FbApiTokenModel) => {
      console.log(record);
      Modal.confirm({
        title: t('Delete'),
        content: `${t('Delete')} ${record.name}`,
        onOk() {
          loading.value = true;
          deleteFbApiToken(record.id)
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

    const syncResource = (record: FbApiTokenModel) => {
      syncTokenResourceApi({
        ids: [record.id],
      })
        .then(() => {
          message.success(t('Task sumbmitted'));
        })
        .catch(e => {
          console.log(e);
          message.error(t('Request Error'));
        });
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
      syncResource,
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
