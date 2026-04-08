<template>
  <div>
    <h1>Fb API Token</h1>
    <a-card>
      <a-row :gutter="[12, 0]">
        <a-col :flex="1">
          <add-token ref="tokenModal" @confirm:token-saved="() => getTokens()"></add-token>
        </a-col>
        <a-col>
          <column-orgnizer
            :columns="columns"
            @change:columns="data => (dynamicColumns = data)"
          ></column-orgnizer>
          <dynamic-form :form-items="formItems" @change:form-data="onSearch"></dynamic-form>
        </a-col>
      </a-row>
    </a-card>
    <a-table
      :data-source="tableData"
      :pagination="false"
      :loading="loading"
      :columns="dynamicColumns"
      row-key="id"
    >
      <template #bodyCell="{ column, text, record }">
        <template v-if="column.key === 'action'">
          <a @click="editTemplate(record as any)" class="margin-right">{{ t('Edit') }}</a>
          <a @click="deleteTemplate(record as any)">{{ t('Delete') }}</a>
        </template>
        <template v-if="['created_at'].includes(`${column['dataIndex']}`)">
          <span>{{ dayjs(text).format('YYYY-MM-DD HH:mm:ss') }}</span>
        </template>
        <template v-if="column.key === 'token'">
          <a-tooltip :title="text">
            <div class="ellipsis-container">
              <span>{{ text }}</span>
            </div>
          </a-tooltip>
        </template>
      </template>
    </a-table>

    <a-row justify="end">
      <a-pagination
        :current="currentPage"
        :page-size="pageSize"
        :total="total"
        @change="onPageChange"
      />
    </a-row>
  </div>
</template>

<script lang="ts">
import { defineComponent, onMounted, ref, h, watch } from 'vue';
import { Button, Table, Pagination, message, Modal } from 'ant-design-vue';
import { deleteFbApiToken, getFbApiTokens } from '@/api/fb_api_token';
import { EditOutlined, DeleteOutlined } from '@ant-design/icons-vue';
import { useI18n } from 'vue-i18n';
import DynamicForm from '@/components/dynamic-form/dynamic-form.vue';
import type { TableColumn } from '@/typing';
import AddToken from './add-token.vue';
import dayjs from 'dayjs';
import ColumnOrgnizer from '@/components/column-orgnizer/column-orgnizer.vue';

interface Token {
  id?: string;
  created_at: string;
  updated_at: string;
  name: string;
  bm_id: string;
  token: string;
  active: string;
  notes: string;
}

export default defineComponent({
  name: 'FbAPIToken',
  components: {
    'a-button': Button,
    'a-table': Table,
    'a-table-column': Table.Column,
    'a-pagination': Pagination,
    DynamicForm,
    AddToken,
    ColumnOrgnizer,
  },
  setup() {
    const { t } = useI18n();
    const currentPage = ref<number>(1);
    const pageSize = ref<number>(10);
    const total = ref<number>(100); // 根据实际数据更新
    const loading = ref(false);
    const tableData = ref<Token[]>([]);
    const tokenModal = ref(null);
    const queryParams = ref<Pick<Token, 'active' | 'name' | 'notes' | 'token'>>({} as any);

    const columns: TableColumn[] = [
      { title: t('Created Time'), dataIndex: 'created_at', key: 'created_at' },
      // { title: t('Updated At'), dataIndex: 'updated_at', key: 'updated_at' },
      { title: t('Name'), dataIndex: 'name', key: 'name' },
      { title: t('BM ID'), dataIndex: 'bm_id', key: 'bm_id' },
      { title: t('Token'), dataIndex: 'token', key: 'token' },
      { title: t('Active'), dataIndex: 'active', key: 'active' },
      { title: t('Notes'), dataIndex: 'notes', key: 'notes' },
      { title: t('Action'), key: 'action', fixed: 'right' },
    ];

    const formItems = ref([
      {
        label: t('Active'),
        field: 'active',
        options: [
          { label: t('All'), value: '' },
          { label: t('true'), value: true },
          { label: t('false'), value: false },
        ],
        mode: 'radio',
      },
      { label: t('Name'), field: 'name', multiple: true },
      { label: t('Token'), field: 'token', multiple: true },
      { label: t('Notes'), field: 'notes', multiple: true },
    ]);

    const dynamicColumns = ref<TableColumn[]>([]);

    onMounted(() => getTokens());

    watch(
      () => queryParams,
      () => getTokens(),
    );

    const onSearch = data => {
      Object.keys(data).forEach(key => (queryParams.value[key] = data[key]));
      getTokens();
    };

    const editTemplate = (record: Token) => tokenModal.value.editToken(record);

    const deleteTemplate = (record: Token) => {
      Modal.confirm({
        title: t('Delete'),
        content: `${t('Delete token')}: ${record.name}`,
        onOk() {
          loading.value = true;
          deleteFbApiToken(record.id)
            .then(() => {
              getTokens();
              message.success(t('Token deleted'));
            })
            .catch(() => message.error(t('Operation failed')));
        },
      });
    };

    const onPageChange = (page: number) => {
      currentPage.value = page;
      // 加载新一页的数据
      getTokens();
    };

    const getTokens = () => {
      loading.value = true;
      getFbApiTokens({ pageSize: pageSize.value, pageNo: currentPage.value, ...queryParams.value })
        .then(({ data, pageNo, pageSize: size, totalCount }: any) => {
          tableData.value = data;
          currentPage.value = pageNo;
          pageSize.value = size;
          total.value = totalCount;
        })
        .finally(() => (loading.value = false));
    };

    return {
      currentPage,
      pageSize,
      total,
      tableData,
      formItems,
      columns,
      loading,
      tokenModal,
      dynamicColumns,
      onSearch,
      editTemplate,
      deleteTemplate,
      onPageChange,
      getTokens,
      EditOutlined,
      DeleteOutlined,
      dayjs,
      h,
      t,
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
