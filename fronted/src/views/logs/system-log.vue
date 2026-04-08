<template>
  <page-container>
    <div class="ant-pro-table">
      <a-card :body-style="{ padding: 0 }">
        <div class="system-log-search">
          <a-form layout="horizontal">
            <a-row :gutter="[16, 12]">
              <a-col :xs="24" :sm="12" :lg="8" :xl="6">
                <a-form-item :label="t('pages.log.level')" style="margin-bottom: 0;">
                  <a-select v-model:value="filters.level" :placeholder="t('pages.log.level.placeholder')" allow-clear>
                    <template #suffixIcon><FilterOutlined /></template>
                    <a-select-option value="info">
                      <a-space>
                        <InfoCircleOutlined style="color: #1890ff" />
                        {{ t('pages.log.level.info') }}
                      </a-space>
                    </a-select-option>
                    <a-select-option value="warning">
                      <a-space>
                        <WarningOutlined style="color: #faad14" />
                        {{ t('pages.log.level.warning') }}
                      </a-space>
                    </a-select-option>
                    <a-select-option value="error">
                      <a-space>
                        <CloseCircleOutlined style="color: #ff4d4f" />
                        {{ t('pages.log.level.error') }}
                      </a-space>
                    </a-select-option>
                    <a-select-option value="debug">
                      <a-space>
                        <BugOutlined style="color: #722ed1" />
                        {{ t('pages.log.level.debug') }}
                      </a-space>
                    </a-select-option>
                  </a-select>
                </a-form-item>
              </a-col>
              <a-col :xs="24" :sm="12" :lg="8" :xl="6">
                <a-form-item :label="t('pages.log.module')" style="margin-bottom: 0;">
                  <a-input
                    v-model:value="filters.module"
                    :placeholder="t('pages.log.module.placeholder')"
                    allow-clear
                    @pressEnter="handleSearch"
                  >
                    <template #prefix>
                      <AppstoreOutlined style="color: #bfbfbf" />
                    </template>
                  </a-input>
                </a-form-item>
              </a-col>
              <a-col :xs="24" :sm="12" :lg="8" :xl="6">
                <a-form-item :label="t('pages.log.keyword')" style="margin-bottom: 0;">
                  <a-input
                    v-model:value="filters.keyword"
                    :placeholder="t('pages.log.keyword.placeholder')"
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
            <template v-if="column.dataIndex === 'level'">
              <a-tag :color="getLevelColor(text)">{{ getLevelName(text) }}</a-tag>
            </template>
            <template v-else-if="column.dataIndex === 'time'">
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
  import { SearchOutlined, ClearOutlined, FilterOutlined, InfoCircleOutlined, WarningOutlined, CloseCircleOutlined, BugOutlined, AppstoreOutlined, FileTextOutlined } from '@ant-design/icons-vue';
  import dayjs from 'dayjs';

  const { t } = useI18n();

  const state = reactive({
    loading: false,
    tableSize: 'middle' as const,
    current: 1,
    pageSize: 10,
  });

  const filters = reactive({
    level: undefined as string | undefined,
    module: '',
    keyword: '',
  });

  const systemLogData = [
    { id: 1, level: 'info', module: '系统', message: '系统启动成功', time: '2026-03-30 10:30:00', server: 'app-server-01' },
    { id: 2, level: 'info', module: '定时任务', message: '广告数据同步任务执行完成', time: '2026-03-30 10:25:00', server: 'task-server-01' },
    { id: 3, level: 'warning', module: '广告', message: '广告预算即将耗尽，提醒用户', time: '2026-03-30 10:20:00', server: 'app-server-02' },
    { id: 4, level: 'error', module: '数据库', message: '数据库连接超时，已重试3次', time: '2026-03-30 10:15:00', server: 'db-server-01' },
    { id: 5, level: 'info', module: '用户', message: '用户 admin 登录成功', time: '2026-03-30 10:10:00', server: 'app-server-01' },
    { id: 6, level: 'debug', module: 'API', message: 'API请求：GET /api/ads/stats', time: '2026-03-30 10:05:00', server: 'api-server-01' },
    { id: 7, level: 'warning', module: '缓存', message: '缓存命中率低于阈值 70%，当前命中率 65%', time: '2026-03-30 10:00:00', server: 'cache-server-01' },
    { id: 8, level: 'info', module: '消息队列', message: '广告素材处理队列已启动', time: '2026-03-30 09:55:00', server: 'mq-server-01' },
    { id: 9, level: 'error', module: '第三方API', message: 'Facebook API 调用失败，错误码：190', time: '2026-03-30 09:50:00', server: 'app-server-02' },
    { id: 10, level: 'info', module: '备份', message: '数据库自动备份完成，备份文件大小：2.3GB', time: '2026-03-30 09:45:00', server: 'backup-server-01' },
    { id: 11, level: 'warning', module: '安全', message: '检测到异常登录尝试，IP地址：192.168.1.200', time: '2026-03-30 09:40:00', server: 'security-server-01' },
    { id: 12, level: 'info', module: '定时任务', message: '广告报表生成任务执行完成', time: '2026-03-30 09:35:00', server: 'task-server-01' },
  ];

  const columns = [
    { title: () => t('pages.log.table.id'), dataIndex: 'id', width: 60, key: 'id' },
    { title: () => t('pages.log.table.level'), dataIndex: 'level', key: 'level' },
    { title: () => t('pages.log.table.module'), dataIndex: 'module', key: 'module' },
    { title: () => t('pages.log.table.message'), dataIndex: 'message', key: 'message', ellipsis: true },
    { title: () => t('pages.log.table.time'), dataIndex: 'time', key: 'time' },
    { title: () => t('pages.log.table.server'), dataIndex: 'server', key: 'server' },
  ];

  const getLevelColor = (level: string) => {
    const colors: Record<string, string> = {
      info: 'blue',
      warning: 'orange',
      error: 'red',
      debug: 'gray',
    };
    return colors[level] || 'default';
  };

  const getLevelName = (level: string) => {
    const names: Record<string, string> = {
      info: t('pages.log.level.info'),
      warning: t('pages.log.level.warning'),
      error: t('pages.log.level.error'),
      debug: t('pages.log.level.debug'),
    };
    return names[level] || level;
  };

  const filteredData = computed(() => {
    return systemLogData.filter((item) => {
      if (filters.level && item.level !== filters.level) return false;
      if (filters.module && !item.module.includes(filters.module)) return false;
      if (filters.keyword && !item.message.includes(filters.keyword)) return false;
      return true;
    });
  });

  const handleSearch = () => {
    state.current = 1;
  };

  const handleReset = () => {
    filters.level = undefined;
    filters.module = '';
    filters.keyword = '';
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

.system-log-search {
  padding: 20px 24px;
  background: linear-gradient(135deg, #f6f8fc 0%, #fff 100%);
  border-bottom: 1px solid #f0f0f0;
}

.system-log-search :deep(.ant-form-item-label > label) {
  color: #595959;
  font-weight: 500;
}

.system-log-search :deep(.ant-input),
.system-log-search :deep(.ant-select:not(.ant-select-customize-input) .ant-select-selector) {
  border-radius: 6px;
  border-color: #d9d9d9;
  transition: all 0.3s;
}

.system-log-search :deep(.ant-input:hover),
.system-log-search :deep(.ant-select:not(.ant-select-customize-input) .ant-select-selector:hover) {
  border-color: #1890ff;
}

.system-log-search :deep(.ant-input:focus),
.system-log-search :deep(.ant-select-focused .ant-select-selector) {
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

.system-log-search :deep(.ant-select-item-option-selected) {
  background: #e6f7ff;
}
</style>
