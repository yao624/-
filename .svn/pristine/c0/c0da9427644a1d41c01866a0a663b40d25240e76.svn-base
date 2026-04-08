<template>
  <div>
    <a-card>
      <a-row :gutter="[12, 0]">
        <a-col :flex="1">
          <add-config ref="configModal" @confirm:token-saved="() => getFraudConfigs()"></add-config>
        </a-col>
        <a-col>
          <column-orgnizer
            :columns="columns"
            @change:columns="data => (dynamicColumns = data)"
          ></column-orgnizer>
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
import { Table, Pagination, message, Modal } from 'ant-design-vue';
import { EditOutlined, DeleteOutlined } from '@ant-design/icons-vue';
import { useI18n } from 'vue-i18n';
import type { TableColumn } from '@/typing';
import AddConfig from './add-config.vue';
import dayjs from 'dayjs';
import ColumnOrgnizer from '@/components/column-orgnizer/column-orgnizer.vue';
import { deleteFraudConfig, getFraudConfigList } from '@/api/fraud_config';

interface FraudConfig {
  id?: string;
  value: string[];
  actions: string[];
  type: string;
  active: boolean;
  notes: string;
}

export default defineComponent({
  name: 'FraudConfig',
  components: {
    'a-table': Table,
    'a-pagination': Pagination,
    AddConfig,
    ColumnOrgnizer,
  },
  setup() {
    const { t } = useI18n();
    const currentPage = ref<number>(1);
    const pageSize = ref<number>(10);
    const total = ref<number>(100); // 根据实际数据更新
    const loading = ref(false);
    const tableData = ref<FraudConfig[]>([]);
    const configModal = ref(null);
    const queryParams = ref<Pick<FraudConfig, 'type' | 'value' | 'notes' | 'actions'>>({} as any);

    const columns: TableColumn[] = [
      { title: t('Type'), dataIndex: 'type', key: 'type' },
      { title: t('Value'), dataIndex: 'value', key: 'value' },
      { title: t('Actions'), dataIndex: 'actions', key: 'actions' },
      { title: t('Status'), dataIndex: 'active', key: 'active' },
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
      { label: t('Type'), field: 'type', multiple: true },
      { label: t('Notes'), field: 'notes', multiple: true },
    ]);

    const dynamicColumns = ref<TableColumn[]>([]);

    onMounted(() => getFraudConfigs());

    watch(
      () => queryParams,
      () => getFraudConfigs(),
    );

    const onSearch = data => {
      Object.keys(data).forEach(key => (queryParams.value[key] = data[key]));
      getFraudConfigs();
    };

    const editTemplate = (record: FraudConfig) => configModal.value.editToken(record);

    const deleteTemplate = (record: FraudConfig) => {
      Modal.confirm({
        title: t('Delete'),
        content: `${t('Fraud Config Type')}: ${record.type}`,
        onOk() {
          loading.value = true;
          deleteFraudConfig(record.id)
            .then(() => {
              getFraudConfigs();
              message.success(t('Token deleted'));
            })
            .catch(() => message.error(t('Operation failed')));
        },
      });
    };

    const onPageChange = (page: number) => {
      currentPage.value = page;
      // 加载新一页的数据
      getFraudConfigs();
    };

    const getFraudConfigs = () => {
      loading.value = true;
      getFraudConfigList({
        pageSize: pageSize.value,
        pageNo: currentPage.value,
        ...queryParams.value,
      })
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
      configModal,
      dynamicColumns,
      onSearch,
      editTemplate,
      deleteTemplate,
      onPageChange,
      getFraudConfigs,
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
