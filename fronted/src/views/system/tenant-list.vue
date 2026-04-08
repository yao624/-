<template>
  <page-container>
    <div class="ant-pro-table">
      <div class="ant-pro-table-search">
        <a-form layout="horizontal">
          <a-row :gutter="16" type="flex" justify="start">
            <a-col :xs="24" :sm="24" :md="12">
              <a-form-item label="Search">
                <a-input
                  v-model:value="searchText"
                  placeholder="Search by name, email, or database name"
                  @pressEnter="reload"
                />
              </a-form-item>
            </a-col>
            <a-col :xs="24" :sm="24" :md="12">
              <a-form-item label="Status">
                <a-select
                  v-model:value="statusFilter"
                  placeholder="All Status"
                  allow-clear
                  @change="reload"
                >
                  <a-select-option value="active">Active</a-select-option>
                  <a-select-option value="inactive">Inactive</a-select-option>
                  <a-select-option value="suspended">Suspended</a-select-option>
                </a-select>
              </a-form-item>
            </a-col>
          </a-row>
        </a-form>
      </div>
      <a-card :body-style="{ padding: 0 }" ref="elRef">
        <div class="ant-pro-table-list-toolbar">
          <div class="ant-pro-table-list-toolbar-container">
            <div class="ant-pro-table-list-toolbar-left">
              <div class="ant-pro-table-list-toolbar-title">Tenants</div>
            </div>
            <div class="ant-pro-table-list-toolbar-right">
              <a-space align="center">
                <a-button
                  type="primary"
                  @click="
                    () => {
                      editModal.model = null;
                      editModal.visible = true;
                    }
                  "
                >
                  <plus-outlined />
                  Add Tenant
                </a-button>
                <a-button
                  danger
                  :disabled="!selectedRowKeys.length"
                  @click="handleBatchDelete"
                >
                  Batch Delete
                </a-button>
              </a-space>
              <div class="ant-pro-table-list-toolbar-divider">
                <a-divider type="vertical" />
              </div>
              <div class="ant-pro-table-list-toolbar-setting-item">
                <a-tooltip title="Refresh">
                  <reload-outlined @click="reload" />
                </a-tooltip>
              </div>
              <div class="ant-pro-table-list-toolbar-setting-item">
                <a-tooltip :title="screenState ? 'Exit Fullscreen' : 'Fullscreen'">
                  <fullscreen-outlined v-if="!screenState" @click="setFull" />
                  <fullscreen-exit-outlined v-else @click="exitFull" />
                </a-tooltip>
              </div>
            </div>
          </div>
        </div>
        <a-table
          row-key="id"
          :size="state.tableSize"
          :loading="state.loading"
          :columns="columns"
          :data-source="state.dataSource"
          :pagination="{
            current: state.current,
            pageSize: state.pageSize,
            total: state.total,
          }"
          :row-selection="{
            selectedRowKeys: selectedRowKeys,
            onChange: onSelectChange,
          }"
          @change="handleTableChange"
        >
          <template #bodyCell="{ text, record, column }">
            <template v-if="column.dataIndex === 'status'">
              <a-tag
                :color="
                  record.status === 'active'
                    ? 'green'
                    : record.status === 'suspended'
                    ? 'red'
                    : 'default'
                "
              >
                {{ record.status }}
              </a-tag>
            </template>
            <template v-else-if="column.dataIndex === 'database_exists'">
              <a-tag :color="record.database_exists ? 'green' : 'orange'">
                {{ record.database_exists ? 'Exists' : 'Not Exists' }}
              </a-tag>
            </template>
            <template v-else-if="column.dataIndex === 'action'">
              <a-space>
                <a @click="() => handleOpenEdit(record)">Edit</a>
                <a-divider type="vertical" />
                <a @click="() => handleTestConnection(record.id)">Test Connection</a>
                <a-divider type="vertical" />
                <a
                  v-if="!record.database_exists"
                  @click="() => handleCreateDatabase(record.id)"
                >
                  Create DB
                </a>
                <template v-if="record.database_exists">
                  <a-divider type="vertical" />
                </template>
                <a-popconfirm
                  title="Are you sure to delete this tenant?"
                  ok-text="Yes"
                  cancel-text="No"
                  @confirm="handleDelete(record.id)"
                >
                  <a style="color: red">Delete</a>
                </a-popconfirm>
              </a-space>
            </template>
          </template>
        </a-table>
      </a-card>
    </div>

    <tenant-modal
      :model="editModal.model"
      :visible="editModal.visible"
      @cancel="
        () => {
          editModal.visible = false;
        }
      "
      @ok="
        () => {
          editModal.visible = false;
          reload();
        }
      "
    />
  </page-container>
</template>

<script lang="ts">
import { defineComponent, reactive, ref } from 'vue';
import {
  PlusOutlined,
  ReloadOutlined,
  FullscreenOutlined,
  FullscreenExitOutlined,
} from '@ant-design/icons-vue';
import {
  queryTenantsApi,
  deleteTenantApi,
  batchDeleteTenantsApi,
  testTenantConnectionApi,
  createTenantDatabaseApi,
} from '@/api/tenants';
import type { Pagination } from '@/typing';
import { useFetchData } from '@/utils/hooks/useFetchData';
import { useFullscreen } from '@/utils/hooks/useFullscreen';
import { message } from 'ant-design-vue';
import TenantModal from './tenant-modal.vue';

export default defineComponent({
  name: 'TenantList',
  setup() {
    const searchText = ref('');
    const statusFilter = ref<string | undefined>(undefined);
    const selectedRowKeys = ref<string[]>([]);

    const columns = [
      {
        title: 'Name',
        dataIndex: 'name',
        key: 'name',
      },
      {
        title: 'Email',
        dataIndex: 'email',
        key: 'email',
      },
      {
        title: 'Database Name',
        dataIndex: 'database_name',
        key: 'database_name',
      },
      {
        title: 'Database Host',
        dataIndex: 'database_host',
        key: 'database_host',
      },
      {
        title: 'Status',
        dataIndex: 'status',
        key: 'status',
      },
      {
        title: 'Database Exists',
        dataIndex: 'database_exists',
        key: 'database_exists',
      },
      {
        title: 'Created At',
        dataIndex: 'created_at',
        key: 'created_at',
      },
      {
        title: 'Action',
        dataIndex: 'action',
        key: 'action',
        width: 300,
      },
    ];

    const fetchDataContext = reactive({
      current: 1,
      pageSize: 10,
      tableSize: 'middle',
    });

    const fetchData = async () => {
      const params: any = {
        pageNo: fetchDataContext.current,
        pageSize: fetchDataContext.pageSize,
      };
      if (searchText.value) {
        params.search = searchText.value;
      }
      if (statusFilter.value) {
        params.status = statusFilter.value;
      }
      const response = await queryTenantsApi(params);
      // 检查每个租户的数据库是否存在
      const data = response.data.map((item: any) => ({
        ...item,
        database_exists: item.database_exists ?? false,
      }));
      return {
        ...response,
        data,
      };
    };

    const { reload, context: state } = useFetchData(fetchData, fetchDataContext);
    const [elRef, screenState, { setFull, exitFull }] = useFullscreen();

    const handleTableChange = ({ current, pageSize }: Pagination) => {
      fetchDataContext.current = current;
      fetchDataContext.pageSize = pageSize;
    };

    const onSelectChange = (keys: string[]) => {
      selectedRowKeys.value = keys;
    };

    // Edit
    const editModal = reactive({
      visible: false,
      model: null as any,
    });

    const handleOpenEdit = (record: any) => {
      editModal.visible = true;
      editModal.model = { ...record };
    };

    // Delete
    const handleDelete = async (id: string) => {
      try {
        await deleteTenantApi(id);
        message.success('Tenant deleted successfully');
        reload();
      } catch (error: any) {
        message.error(error.response?.data?.message || 'Failed to delete tenant');
      }
    };

    // Batch Delete
    const handleBatchDelete = () => {
      if (selectedRowKeys.value.length === 0) {
        message.warning('Please select tenants to delete');
        return;
      }
      batchDeleteTenantsApi(selectedRowKeys.value)
        .then(() => {
          message.success('Tenants deleted successfully');
          selectedRowKeys.value = [];
          reload();
        })
        .catch((error: any) => {
          message.error(error.response?.data?.message || 'Failed to delete tenants');
        });
    };

    // Test Connection
    const handleTestConnection = async (id: string) => {
      try {
        const response = await testTenantConnectionApi(id);
        if (response.status) {
          message.success('Database connection successful');
        } else {
          message.warning('Database does not exist or connection failed');
        }
        reload();
      } catch (error: any) {
        message.error(error.response?.data?.message || 'Connection test failed');
      }
    };

    // Create Database
    const handleCreateDatabase = async (id: string) => {
      try {
        await createTenantDatabaseApi(id);
        message.success('Database created successfully');
        reload();
      } catch (error: any) {
        message.error(error.response?.data?.message || 'Failed to create database');
      }
    };

    return {
      state,
      columns,
      reload,
      elRef,
      screenState,
      setFull,
      exitFull,
      handleTableChange,
      editModal,
      handleOpenEdit,
      handleDelete,
      handleBatchDelete,
      handleTestConnection,
      handleCreateDatabase,
      searchText,
      statusFilter,
      selectedRowKeys,
      onSelectChange,
    };
  },
  components: {
    PlusOutlined,
    ReloadOutlined,
    FullscreenOutlined,
    FullscreenExitOutlined,
    TenantModal,
  },
});
</script>

