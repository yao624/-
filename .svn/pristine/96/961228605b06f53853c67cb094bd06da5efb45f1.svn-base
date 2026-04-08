<template>
  <page-container :title="'通知中心'" :showPageHeader="true">
    <div class="notification-center-container 3xl:[zoom:0.9] 4xl:[zoom:1.1]">
      <div class="header-section">
        <div class="filter-row">
          <span class="filter-label">类型:</span>
          <div class="type-tabs">
            <div
              v-for="tab in typeTabs"
              :key="tab.value"
              :class="['type-tab', { active: activeType === tab.value }]"
              @click="activeType = tab.value"
            >
              {{ tab.label }}
              <!-- 只在“全部通知”和“未读通知”上显示角标，已读不显示数字 -->
              <a-badge
                v-if="(tab.value === 'all' || tab.value === 'unread') && tab.count > 0"
                :count="tab.count"
                :offset="[10, -10]"
                size="small"
              />
            </div>
          </div>
          <div class="header-actions">
            <a-button size="small" @click="handleMarkAllRead">全部标记为已读</a-button>
          </div>
        </div>
      </div>

      <div class="content-section">
        <div v-if="loading" class="loading-state">
          <a-spin />
        </div>
        <div v-else-if="dataSource.length > 0" class="notification-list">
          <div v-for="item in dataSource" :key="item.id" class="notification-item" @click="handleView(item)">
            <div class="item-left">
              <div class="item-icon">
                <info-circle-filled />
              </div>
              <div class="item-content">
                <span class="item-title">{{ item.title }}</span>
                <span class="item-message">{{ item.message }}</span>
              </div>
            </div>
            <div class="item-right">
              <span class="item-time">{{ item.time }}</span>
              <close-outlined class="close-icon" @click.stop="handleView(item)" />
            </div>
          </div>
        </div>
        <div v-else class="empty-state">
          <a-empty description="No Data" />
        </div>

        <div class="pagination-wrapper">
          <a-pagination
            v-model:current="pagination.current"
            v-model:pageSize="pagination.pageSize"
            :total="pagination.total"
            show-size-changer
            show-quick-jumper
            size="small"
            :page-size-options="['10', '20', '50', '100']"
            @change="handlePageChange"
          />
        </div>
      </div>
    </div>

    <a-modal
      v-model:visible="detailModalVisible"
      title="通知详情"
      :footer="null"
      width="800px"
    >
      <div v-if="detailData" class="detail-modal-content">
        <div class="detail-title">{{ detailData.title }}</div>
        <div class="detail-time">{{ detailData.time }}</div>
        <div class="detail-message">{{ detailData.message }}</div>
        <div v-if="detailData.extra" class="detail-extra">
          <span class="label">额外信息：</span>
          <span class="value">{{ detailData.extra }}</span>
        </div>
      </div>
    </a-modal>
  </page-container>
</template>

<script lang="ts">
import { defineComponent, ref, reactive, watch, onMounted } from 'vue';
import dayjs from 'dayjs';
import { InfoCircleFilled, CloseOutlined } from '@ant-design/icons-vue';
import { message } from 'ant-design-vue';
import { changeNoticeReadState, getNoticeDetail, queryNoticesPage } from '@/api/user/notice';

export default defineComponent({
  name: 'NotificationCenter',
  components: {
    InfoCircleFilled,
    CloseOutlined,
  },
  setup() {
    type TabValue = 'all' | 'unread' | 'read';
    const activeType = ref<TabValue>('all');
    const loading = ref(false);

    const typeTabs = ref([
      { label: '全部通知', value: 'all' as const, count: 0 },
      { label: '未读通知', value: 'unread' as const, count: 0 },
      { label: '已读通知', value: 'read' as const, count: 0 },
    ]);

    const dataSource = ref<
      Array<{
        id: string;
        title: string;
        message: string;
        time: string;
        status: 'unread' | 'read';
        extra?: string;
      }>
    >([]);

    const pagination = reactive({
      current: 1,
      pageSize: 20,
      total: 1,
    });

    const detailModalVisible = ref(false);
    const detailData = ref<null | { title: string; time: string; message: string; extra?: string }>(null);

    const formatTime = (dt?: string) => {
      if (!dt) return '-';
      const d = dayjs(dt);
      if (!d.isValid()) return '-';
      return d.format('YYYY-MM-DD HH:mm');
    };

    const loadCounts = async () => {
      try {
        const [allRes, unreadRes, readRes] = await Promise.all([
          queryNoticesPage({ pageNo: 1, pageSize: 1 }),
          queryNoticesPage({ pageNo: 1, pageSize: 1, status: 'unread' }),
          queryNoticesPage({ pageNo: 1, pageSize: 1, status: 'read' }),
        ]);

        const setCount = (value: TabValue, count: number) => {
          const tab = typeTabs.value.find(t => t.value === value);
          if (tab) tab.count = count;
        };

        setCount('all', allRes.totalCount);
        setCount('unread', unreadRes.totalCount);
        setCount('read', readRes.totalCount);
      } catch (_e) {
        // ignore
      }
    };

    const loadNotices = async () => {
      loading.value = true;
      try {
        const statusFilter = activeType.value === 'all' ? undefined : activeType.value;
        const res = await queryNoticesPage({
          pageNo: pagination.current,
          pageSize: pagination.pageSize,
          status: statusFilter,
        });

        dataSource.value = res.list.map(n => ({
          id: n.id,
          title: n.title || '-',
          message: n.description || '',
          time: formatTime(n.datetime),
          status: n.read ? 'read' : 'unread',
          extra: n.extra,
        }));
        pagination.total = res.totalCount;
      } finally {
        loading.value = false;
      }
    };

    const handleMarkAllRead = async () => {
      try {
        const unreadRes = await queryNoticesPage({ pageNo: 1, pageSize: 200, status: 'unread' });
        const ids = unreadRes.list.map(x => x.id);
        if (ids.length === 0) {
          message.info('当前没有未读通知');
          return;
        }

        await changeNoticeReadState(ids);
        message.success('已全部标记为已读');
        await loadCounts();
        await loadNotices();
      } catch (_e) {
        message.error('标记失败，请稍后重试');
      }
    };

    const handlePageChange = (page: number, pageSize: number) => {
      pagination.current = page;
      pagination.pageSize = pageSize;
      loadNotices();
    };

    const handleView = async (item: { id: string }) => {
      loading.value = true;
      try {
        const detail = await getNoticeDetail(item.id);
        if (!detail) {
          message.error('通知不存在');
          return;
        }

        detailData.value = {
          title: detail.title || '-',
          time: formatTime(detail.datetime),
          message: detail.description || '',
          extra: detail.extra,
        };
        detailModalVisible.value = true;

        // 查看详情会把通知标记为已读
        await loadCounts();
        await loadNotices();
      } finally {
        loading.value = false;
      }
    };

    watch(activeType, () => {
      pagination.current = 1;
      loadNotices();
    });

    onMounted(async () => {
      await loadCounts();
      await loadNotices();
    });

    return {
      activeType,
      typeTabs,
      loading,
      dataSource,
      pagination,
      handleMarkAllRead,
      handlePageChange,
      handleView,
      detailModalVisible,
      detailData,
    };
  },
});
</script>

<style scoped lang="less">
.notification-center-container {
  background-color: transparent;
  padding: 12px 0;
  min-height: 100%;
  box-sizing: border-box;
  font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, 'Helvetica Neue', Arial, sans-serif;

  .header-section {
    background: #fff;
    padding: 16px 24px;
    border-bottom: 1px solid #f0f0f0;

    .filter-row {
      display: flex;
      align-items: center;
      justify-content: space-between;

      .filter-label {
        width: 48px;
        font-size: 14px;
        color: #333;
        font-weight: 500;
        flex-shrink: 0;
      }

      .type-tabs {
        display: flex;
        gap: 0;
        border: 1px solid #d9d9d9;
        border-radius: 2px;
        overflow: hidden;

        .type-tab {
          padding: 6px 20px;
          cursor: pointer;
          font-size: 14px;
          color: #666;
          background: #fff;
          border-right: 1px solid #d9d9d9;
          transition: all 0.2s;
          display: flex;
          align-items: center;
          gap: 4px;

          &:last-child {
            border-right: none;
          }

          &:hover {
            color: #1890ff;
          }

          &.active {
            background: #e6f7ff;
            color: #1890ff;
            font-weight: 500;
          }

          :deep(.ant-badge-count) {
            box-shadow: none;
          }
        }
      }

      .header-actions {
        :deep(.ant-btn) {
          border-radius: 2px;
          font-size: 14px;
          color: #333;
          height: 32px;
        }
      }
    }
  }

  .content-section {
    background: #fff;
    padding: 0;
    min-height: 400px;
    display: flex;
    flex-direction: column;

    .notification-item {
      display: flex;
      align-items: center;
      justify-content: space-between;
      padding: 12px 24px;
      border-bottom: 1px solid #f0f0f0;

      &:last-child {
        border-bottom: none;
      }

      .item-left {
        display: flex;
        align-items: center;
        gap: 12px;

        .item-icon {
          width: 20px;
          height: 20px;
          display: flex;
          align-items: center;
          justify-content: center;
          font-size: 14px;
          
          &.yellow {
            color: #faad14;
            font-weight: bold;
          }
        }

        .item-content {
          display: flex;
          align-items: center;
          gap: 8px;
          font-size: 14px;

          .item-title {
            color: #333;
            font-weight: 500;
          }

          .item-message {
            color: #666;
          }
        }
      }

      .item-right {
        display: flex;
        align-items: center;
        gap: 16px;

        .item-time {
          color: #999;
          font-size: 13px;
        }

        .close-icon {
          color: #ccc;
          cursor: pointer;
          font-size: 12px;

          &:hover {
            color: #666;
          }
        }
      }
    }

    .pagination-wrapper {
      padding: 16px 24px;
      display: flex;
      justify-content: flex-end;
      border-top: 1px solid #f0f0f0;
    }
  }
}

/* 超宽屏适配：增大左右留白 */
@media (min-width: 1280px) {
  .notification-center-container {
    .header-section {
      padding: 18px 32px;
    }
    .notification-item,
    .pagination-wrapper {
      padding-left: 32px;
      padding-right: 32px;
    }
  }
}

@media (min-width: 1440px) {
  .notification-center-container {
    .header-section {
      padding: 20px 36px;
    }
    .notification-item,
    .pagination-wrapper {
      padding-left: 36px;
      padding-right: 36px;
    }
  }
}

@media (min-width: 1920px) {
  .notification-center-container {
    .header-section {
      padding: 22px 40px;
    }
    .notification-item,
    .pagination-wrapper {
      padding-left: 40px;
      padding-right: 40px;
    }
  }
}

@media (min-width: 2560px) {
  .notification-center-container {
    .header-section {
      padding: 24px 48px;
    }
    .notification-item,
    .pagination-wrapper {
      padding-left: 48px;
      padding-right: 48px;
    }
  }
}
</style>



