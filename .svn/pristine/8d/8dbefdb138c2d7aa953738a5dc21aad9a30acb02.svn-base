<template>
  <page-container>
    <div class="ant-pro-table">
      <a-card :body-style="{ padding: 0 }">
        <div class="user-log-search">
          <a-form layout="horizontal">
            <a-row :gutter="[16, 12]">
              <a-col :xs="24" :sm="12" :lg="8" :xl="6">
                <a-form-item :label="t('pages.log.username')" style="margin-bottom: 0;">
                  <a-input
                    v-model:value="filters.username"
                    :placeholder="t('pages.log.username.placeholder')"
                    allow-clear
                    @pressEnter="handleSearch"
                  >
                    <template #prefix>
                      <UserOutlined style="color: #bfbfbf" />
                    </template>
                  </a-input>
                </a-form-item>
              </a-col>
              <a-col :xs="24" :sm="12" :lg="8" :xl="6">
                <a-form-item :label="t('pages.log.action')" style="margin-bottom: 0;">
                  <a-input
                    v-model:value="filters.action"
                    :placeholder="t('pages.log.action.placeholder')"
                    allow-clear
                    @pressEnter="handleSearch"
                  >
                    <template #prefix>
                      <FileTextOutlined style="color: #bfbfbf" />
                    </template>
                  </a-input>
                </a-form-item>
              </a-col>
              <a-col :xs="24" :sm="12" :lg="8" :xl="6">
                <a-form-item :label="t('pages.log.operationType')" style="margin-bottom: 0;">
                  <a-select
                    v-model:value="filters.type"
                    :placeholder="t('pages.log.operationType.placeholder')"
                    allow-clear
                    style="width: 100%"
                  >
                    <a-select-option value="create">
                      <a-space>
                        <PlusOutlined style="color: #1890ff" />
                        {{ t('pages.log.operationType.create') }}
                      </a-space>
                    </a-select-option>
                    <a-select-option value="update">
                      <a-space>
                        <EditOutlined style="color: #fa8c16" />
                        {{ t('pages.log.operationType.update') }}
                      </a-space>
                    </a-select-option>
                    <a-select-option value="delete">
                      <a-space>
                        <DeleteOutlined style="color: #ff4d4f" />
                        {{ t('pages.log.operationType.delete') }}
                      </a-space>
                    </a-select-option>
                    <a-select-option value="query">
                      <a-space>
                        <SearchOutlined style="color: #52c41a" />
                        {{ t('pages.log.operationType.query') }}
                      </a-space>
                    </a-select-option>
                    <a-select-option value="export">
                      <a-space>
                        <ExportOutlined style="color: #722ed1" />
                        {{ t('pages.log.operationType.export') }}
                      </a-space>
                    </a-select-option>
                    <a-select-option value="import">
                      <a-space>
                        <ImportOutlined style="color: #13c2c2" />
                        {{ t('pages.log.operationType.import') }}
                      </a-space>
                    </a-select-option>
                  </a-select>
                </a-form-item>
              </a-col>
              <a-col :xs="24" :sm="12" :lg="8" :xl="6">
                <a-form-item style="margin-bottom: 0;">
                  <a-space>
                    <a-button type="primary" class="search-btn" @click="handleSearch">
                      <template #icon><SearchOutlined /></template>
                      {{ t('pages.log.search') }}
                    </a-button>
                    <a-button class="reset-btn" @click="handleReset">
                      <template #icon><ClearOutlined /></template>
                      {{ t('pages.log.reset') }}
                    </a-button>
                  </a-space>
                </a-form-item>
              </a-col>
            </a-row>
          </a-form>
        </div>

        <a-table
          row-key="id"
          :size="state.tableSize"
          :loading="state.loading"
          :columns="columns"
          :data-source="filteredData"
          :pagination="{
            current: state.current,
            pageSize: state.pageSize,
            total: filteredData.length,
            showSizeChanger: true,
            showQuickJumper: true,
            showTotal: (total: number) => t('pages.log.total', { total }),
          }"
        >
          <template #bodyCell="{ text, record, column }">
            <template v-if="column.dataIndex === 'type'">
              <a-tag :color="getTypeColor(text)">{{ getTypeName(text) }}</a-tag>
            </template>
            <template v-else-if="column.dataIndex === 'actionTime'">
              {{ dayjs(text).format('YYYY-MM-DD HH:mm:ss') }}
            </template>
            <template v-else-if="column.dataIndex === 'status'">
              <a-tag :color="text === 'success' ? 'success' : 'error'">
                {{ text === 'success' ? t('pages.log.status.success') : t('pages.log.status.failed') }}
              </a-tag>
            </template>
          </template>
        </a-table>
      </a-card>
    </div>
  </page-container>
</template>

<script setup lang="ts">
  import { reactive, computed } from 'vue';
  import { useI18n } from 'vue-i18n';
  import { SearchOutlined, ClearOutlined, UserOutlined, FileTextOutlined, PlusOutlined, EditOutlined, DeleteOutlined, ExportOutlined, ImportOutlined } from '@ant-design/icons-vue';
  import dayjs from 'dayjs';

  const { t } = useI18n();

  const state = reactive({
    loading: false,
    tableSize: 'middle' as const,
    current: 1,
    pageSize: 10,
  });

  const filters = reactive({
    username: '',
    action: '',
    type: undefined as string | undefined,
  });

  const userLogData = [
    { id: 1, username: 'admin', action: '登录系统', type: 'query', module: '系统', status: 'success', ip: '192.168.1.100', actionTime: '2026-03-30 10:30:00', detail: '用户登录成功' },
    { id: 2, username: 'admin', action: '创建广告计划', type: 'create', module: '广告管理', status: 'success', ip: '192.168.1.100', actionTime: '2026-03-30 10:25:00', detail: '创建广告计划：春季促销活动' },
    { id: 3, username: 'user01', action: '修改广告素材', type: 'update', module: '素材管理', status: 'success', ip: '192.168.1.101', actionTime: '2026-03-30 10:20:00', detail: '更新素材图片' },
    { id: 4, username: 'test', action: '删除用户', type: 'delete', module: '用户管理', status: 'failed', ip: '192.168.1.102', actionTime: '2026-03-30 10:15:00', detail: '权限不足，无法删除' },
    { id: 5, username: 'manager', action: '导出报表', type: 'export', module: '报表', status: 'success', ip: '192.168.1.103', actionTime: '2026-03-30 10:10:00', detail: '导出月度报表' },
    { id: 6, username: 'operator', action: '导入广告数据', type: 'import', module: '广告管理', status: 'success', ip: '192.168.1.104', actionTime: '2026-03-30 10:05:00', detail: '批量导入广告计划' },
    { id: 7, username: 'admin', action: '查询广告数据', type: 'query', module: '广告管理', status: 'success', ip: '192.168.1.100', actionTime: '2026-03-30 10:00:00', detail: '查询广告效果数据' },
    { id: 8, username: 'viewer', action: '查看报表', type: 'query', module: '报表', status: 'success', ip: '192.168.1.105', actionTime: '2026-03-30 09:55:00', detail: '查看周报表' },
    { id: 9, username: 'developer', action: '更新配置', type: 'update', module: '系统配置', status: 'success', ip: '192.168.1.106', actionTime: '2026-03-30 09:50:00', detail: '更新系统参数' },
    { id: 10, username: 'admin', action: '删除广告计划', type: 'delete', module: '广告管理', status: 'failed', ip: '192.168.1.100', actionTime: '2026-03-30 09:45:00', detail: '计划正在投放中，无法删除' },
  ];

  const columns = [
    { title: () => t('pages.log.table.id'), dataIndex: 'id', width: 60, key: 'id' },
    { title: () => t('pages.log.table.username'), dataIndex: 'username', key: 'username' },
    { title: () => t('pages.log.table.action'), dataIndex: 'action', key: 'action' },
    { title: () => t('pages.log.table.type'), dataIndex: 'type', key: 'type' },
    { title: () => t('pages.log.table.module'), dataIndex: 'module', key: 'module' },
    { title: () => t('pages.log.table.status'), dataIndex: 'status', key: 'status' },
    { title: () => t('pages.log.table.ip'), dataIndex: 'ip', key: 'ip' },
    { title: () => t('pages.log.table.actionTime'), dataIndex: 'actionTime', key: 'actionTime' },
  ];

  const getTypeColor = (type: string) => {
    const colors: Record<string, string> = {
      create: 'blue',
      update: 'orange',
      delete: 'red',
      query: 'green',
      export: 'purple',
      import: 'cyan',
    };
    return colors[type] || 'default';
  };

  const getTypeName = (type: string) => {
    const names: Record<string, string> = {
      create: t('pages.log.operationType.create'),
      update: t('pages.log.operationType.update'),
      delete: t('pages.log.operationType.delete'),
      query: t('pages.log.operationType.query'),
      export: t('pages.log.operationType.export'),
      import: t('pages.log.operationType.import'),
    };
    return names[type] || type;
  };

  const filteredData = computed(() => {
    return userLogData.filter((item) => {
      if (filters.username && !item.username.includes(filters.username)) return false;
      if (filters.action && !item.action.includes(filters.action)) return false;
      if (filters.type && item.type !== filters.type) return false;
      return true;
    });
  });

  const handleSearch = () => {
    state.current = 1;
  };

  const handleReset = () => {
    filters.username = '';
    filters.action = '';
    filters.type = undefined;
    state.current = 1;
  };

  const reload = () => {
    state.loading = true;
    setTimeout(() => {
      state.loading = false;
    }, 500);
  };
</script>

<style scoped>
.ant-pro-table {
  background: #fff;
}

.user-log-search {
  padding: 20px 24px;
  background: linear-gradient(135deg, #f6f8fc 0%, #fff 100%);
  border-bottom: 1px solid #f0f0f0;
}

.user-log-search :deep(.ant-form-item-label > label) {
  color: #595959;
  font-weight: 500;
}

.user-log-search :deep(.ant-input),
.user-log-search :deep(.ant-select:not(.ant-select-customize-input) .ant-select-selector) {
  border-radius: 6px;
  border-color: #d9d9d9;
  transition: all 0.3s;
}

.user-log-search :deep(.ant-input:hover),
.user-log-search :deep(.ant-select:not(.ant-select-customize-input) .ant-select-selector:hover) {
  border-color: #1890ff;
}

.user-log-search :deep(.ant-input:focus),
.user-log-search :deep(.ant-select-focused .ant-select-selector) {
  border-color: #1890ff;
  box-shadow: 0 0 0 2px rgba(24, 144, 255, 0.1);
}

.search-btn {
  background: var(--primary-color);
  border: none;
  border-radius: 6px;
  box-shadow: 0 2px 4px rgba(0, 0, 0, 0.15);
  transition: all 0.3s;
}

.search-btn:hover {
  background: color-mix(in srgb, var(--primary-color), #fff 10%);
  box-shadow: 0 4px 8px rgba(0, 0, 0, 0.2);
  transform: translateY(-1px);
}

.reset-btn {
  border-radius: 6px;
  border-color: #d9d9d9;
  color: #595959;
  transition: all 0.3s;
}

.reset-btn:hover {
  border-color: #1890ff;
  color: #1890ff;
}

.user-log-search :deep(.ant-select-item-option-selected) {
  background: #e6f7ff;
}
</style>
