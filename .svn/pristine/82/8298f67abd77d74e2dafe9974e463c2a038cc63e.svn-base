<template>
  <page-container>
    <div class="ant-pro-table">
      <a-card :body-style="{ padding: 0 }">
        <div class="login-log-search">
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
                <a-form-item :label="t('pages.log.ip')" style="margin-bottom: 0;">
                  <a-input
                    v-model:value="filters.ip"
                    :placeholder="t('pages.log.ip.placeholder')"
                    allow-clear
                    @pressEnter="handleSearch"
                  >
                    <template #prefix>
                      <GlobalOutlined style="color: #bfbfbf" />
                    </template>
                  </a-input>
                </a-form-item>
              </a-col>
              <a-col :xs="24" :sm="12" :lg="8" :xl="6">
                <a-form-item :label="t('pages.log.status')" style="margin-bottom: 0;">
                  <a-select
                    v-model:value="filters.status"
                    :placeholder="t('pages.log.status.placeholder')"
                    allow-clear
                    style="width: 100%"
                  >
                    <a-select-option value="success">
                      <a-space>
                        <CheckCircleOutlined style="color: #52c41a" />
                        {{ t('pages.log.status.success') }}
                      </a-space>
                    </a-select-option>
                    <a-select-option value="failed">
                      <a-space>
                        <CloseCircleOutlined style="color: #ff4d4f" />
                        {{ t('pages.log.status.failed') }}
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
            <template v-if="column.dataIndex === 'status'">
              <a-tag :color="text === 'success' ? 'success' : 'error'">
                {{ text === 'success' ? t('pages.log.status.success') : t('pages.log.status.failed') }}
              </a-tag>
            </template>
            <template v-else-if="column.dataIndex === 'loginTime'">
              {{ dayjs(text).format('YYYY-MM-DD HH:mm:ss') }}
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
  import { SearchOutlined, ClearOutlined, UserOutlined, GlobalOutlined, CheckCircleOutlined, CloseCircleOutlined } from '@ant-design/icons-vue';
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
    ip: '',
    status: undefined as string | undefined,
  });

  const loginLogData = [
    { id: 1, username: 'admin', ip: '192.168.1.100', location: '北京市', status: 'success', loginTime: '2026-03-30 10:30:00', browser: 'Chrome 120', os: 'Windows 11' },
    { id: 2, username: 'user01', ip: '192.168.1.101', location: '上海市', status: 'success', loginTime: '2026-03-30 10:25:00', browser: 'Firefox 121', os: 'macOS Sonoma' },
    { id: 3, username: 'test', ip: '192.168.1.102', location: '广州市', status: 'failed', loginTime: '2026-03-30 10:20:00', browser: 'Safari 17', os: 'macOS Ventura' },
    { id: 4, username: 'admin', ip: '192.168.1.103', location: '深圳市', status: 'success', loginTime: '2026-03-30 10:15:00', browser: 'Edge 120', os: 'Windows 10' },
    { id: 5, username: 'demo', ip: '192.168.1.104', location: '杭州市', status: 'failed', loginTime: '2026-03-30 10:10:00', browser: 'Chrome 120', os: 'Windows 11' },
    { id: 6, username: 'manager', ip: '192.168.1.105', location: '成都市', status: 'success', loginTime: '2026-03-30 10:05:00', browser: 'Chrome 119', os: 'Windows 11' },
    { id: 7, username: 'operator', ip: '192.168.1.106', location: '武汉市', status: 'success', loginTime: '2026-03-30 10:00:00', browser: 'Firefox 120', os: 'Ubuntu 22.04' },
    { id: 8, username: 'viewer', ip: '192.168.1.107', location: '西安市', status: 'success', loginTime: '2026-03-30 09:55:00', browser: 'Safari 17', os: 'iOS 17' },
    { id: 9, username: 'admin', ip: '192.168.1.108', location: '南京市', status: 'failed', loginTime: '2026-03-30 09:50:00', browser: 'Chrome 120', os: 'Windows 11' },
    { id: 10, username: 'developer', ip: '192.168.1.109', location: '重庆市', status: 'success', loginTime: '2026-03-30 09:45:00', browser: 'VS Code', os: 'macOS Sonoma' },
  ];

  const columns = [
    { title: () => t('pages.log.table.id'), dataIndex: 'id', width: 60, key: 'id' },
    { title: () => t('pages.log.table.username'), dataIndex: 'username', key: 'username' },
    { title: () => t('pages.log.table.ip'), dataIndex: 'ip', key: 'ip' },
    { title: () => t('pages.log.table.location'), dataIndex: 'location', key: 'location' },
    { title: () => t('pages.log.table.status'), dataIndex: 'status', key: 'status' },
    { title: () => t('pages.log.table.loginTime'), dataIndex: 'loginTime', key: 'loginTime' },
    { title: () => t('pages.log.table.browser'), dataIndex: 'browser', key: 'browser' },
    { title: () => t('pages.log.table.os'), dataIndex: 'os', key: 'os' },
  ];

  const filteredData = computed(() => {
    return loginLogData.filter((item) => {
      if (filters.username && !item.username.includes(filters.username)) return false;
      if (filters.ip && !item.ip.includes(filters.ip)) return false;
      if (filters.status && item.status !== filters.status) return false;
      return true;
    });
  });

  const handleSearch = () => {
    state.current = 1;
  };

  const handleReset = () => {
    filters.username = '';
    filters.ip = '';
    filters.status = undefined;
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

.login-log-search {
  padding: 20px 24px;
  background: linear-gradient(135deg, #f6f8fc 0%, #fff 100%);
  border-bottom: 1px solid #f0f0f0;
}

.login-log-search :deep(.ant-form-item-label > label) {
  color: #595959;
  font-weight: 500;
}

.login-log-search :deep(.ant-input),
.login-log-search :deep(.ant-select:not(.ant-select-customize-input) .ant-select-selector) {
  border-radius: 6px;
  border-color: #d9d9d9;
  transition: all 0.3s;
}

.login-log-search :deep(.ant-input:hover),
.login-log-search :deep(.ant-select:not(.ant-select-customize-input) .ant-select-selector:hover) {
  border-color: #1890ff;
}

.login-log-search :deep(.ant-input:focus),
.login-log-search :deep(.ant-select-focused .ant-select-selector) {
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

.login-log-search :deep(.ant-select-item-option-selected) {
  background: #e6f7ff;
}
</style>
