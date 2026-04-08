<template>
  <div class="auto-optimization">
    <create-rule v-if="showCreateRule" :edit-data="showCreateRule.mode === 'edit' ? showCreateRule.data : null" @close="exitCreateRule" />

    <template v-else>
      <!-- 顶部统计卡片 -->
      <div class="summary-cards">
        <div class="summary-title">AI助手过去14天为您做了哪些事情:</div>
        <div class="cards-grid">
          <div class="summary-card">
            <div class="card-icon" style="background: #e6f7ff; color: #1890ff">
              <bell-outlined />
            </div>
            <div class="card-content">
              <div class="card-value">{{ summaryData.sendNotification }}</div>
              <div class="card-label">{{ t('发送通知') }}</div>
              <div class="card-saved">节省 {{ summaryData.sendNotificationHours }} 小时</div>
            </div>
          </div>
          <div class="summary-card">
            <div class="card-icon" style="background: #f6ffed; color: #52c41a">
              <poweroff-outlined />
            </div>
            <div class="card-content">
              <div class="card-value">{{ summaryData.toggleAds }}</div>
              <div class="card-label">{{ t('开关广告') }}</div>
              <div class="card-saved">节省 {{ summaryData.toggleAdsHours }} 小时</div>
            </div>
          </div>
          <div class="summary-card">
            <div class="card-icon" style="background: #fff7e6; color: #fa8c16">
              <edit-outlined />
            </div>
            <div class="card-content">
              <div class="card-value">{{ summaryData.modifyBid }}</div>
              <div class="card-label">{{ t('修改出价') }}</div>
              <div class="card-saved">节省 {{ summaryData.modifyBidHours }} 小时</div>
            </div>
          </div>
          <div class="summary-card">
            <div class="card-icon" style="background: #fff1f0; color: #ff4d4f">
              <dollar-outlined />
            </div>
            <div class="card-content">
              <div class="card-value">{{ summaryData.modifyBudget }}</div>
              <div class="card-label">{{ t('修改预算') }}</div>
              <div class="card-saved">节省 {{ summaryData.modifyBudgetHours }} 小时</div>
            </div>
          </div>
          <div class="summary-card">
            <div class="card-icon" style="background: #f0f5ff; color: #2f54eb">
              <check-circle-outlined />
            </div>
            <div class="card-content">
              <div class="card-value">{{ summaryData.allActions }}</div>
              <div class="card-label">{{ t('所有执行动作') }}</div>
              <div class="card-saved">节省 {{ summaryData.allActionsHours }} 小时</div>
            </div>
          </div>
        </div>
        <div class="create-rule-btn">
          <a-button type="primary" size="large" @click="goToCreateRule">
            {{ t('创建规则') }}
          </a-button>
        </div>
      </div>

      <!-- 筛选和操作栏 -->
      <div class="filter-bar">
        <a-input
          v-model:value="filters.keyword"
          :placeholder="t('规则名称/关键词: 搜索...')"
          style="width: 200px"
          allow-clear
        />
        <a-select
          v-model:value="filters.channel"
          :placeholder="t('渠道: 请选择')"
          style="width: 150px"
          allow-clear
        >
          <a-select-option value="meta">Meta</a-select-option>
        </a-select>
        <a-select
          v-model:value="filters.monitoring_object"
          :placeholder="t('监控对象: 请选择')"
          style="width: 150px"
          allow-clear
        >
          <a-select-option value="ad-account">{{ t('广告账户') }}</a-select-option>
          <a-select-option value="campaign">{{ t('广告系列') }}</a-select-option>
          <a-select-option value="ad-group">{{ t('广告组') }}</a-select-option>
          <a-select-option value="ad">{{ t('广告') }}</a-select-option>
        </a-select>
        <a-select
          v-model:value="filters.user_id"
          :placeholder="t('创建人: 请选择')"
          style="width: 150px"
          allow-clear
        >
          <a-select-option v-for="creator in creators" :key="creator.id" :value="creator.id">
            {{ creator.name }}
          </a-select-option>
        </a-select>
        <a-button type="primary" @click="handleFilter">{{ t('筛选') }}</a-button>
        <a-button @click="handleReset">{{ t('重置') }}</a-button>
        <a-dropdown :disabled="selectedRowKeys.length === 0">
          <template #overlay>
            <a-menu>
              <a-menu-item
                @click="
                  () => {
                    confirmAction = 'active';
                    confirmVisible = true;
                  }
                "
              >
                {{ t('开启') }}
              </a-menu-item>
              <a-menu-item
                @click="
                  () => {
                    confirmAction = 'inactive';
                    confirmVisible = true;
                  }
                "
              >
                {{ t('暂停') }}
              </a-menu-item>
              <a-menu-item
                danger
                @click="
                  () => {
                    confirmAction = 'delete';
                    confirmVisible = true;
                  }
                "
              >
                {{ t('删除') }}
              </a-menu-item>
            </a-menu>
          </template>
          <a-button>
            {{ t('批量操作') }}
            <down-outlined />
          </a-button>
        </a-dropdown>
        <a-modal
          v-model:open="confirmVisible"
          :title="confirmTitle"
          :mask-closable="false"
          @ok="handleConfirmBatch"
        >
          <p>{{ t('确定要对选中的') + selectedRowKeys.length + t('条规则进行批量操作吗？') }}</p>
        </a-modal>
        <a-modal
          v-model:open="auditVisible"
          :title="t('审核规则')"
          :mask-closable="false"
          @ok="handleConfirmAudit"
        >
          <p>{{ t('确认操作该规则的审核吗？') }}</p>
          <a-radio-group v-model:value="auditAction" style="margin-top: 16px">
            <a-radio value="approve">{{ t('通过') }}</a-radio>
            <a-radio value="reject">{{ t('拒绝') }}</a-radio>
          </a-radio-group>
          <a-input
            v-if="auditAction === 'reject'"
            v-model:value="auditReason"
            :placeholder="t('请输入拒绝原因')"
            style="margin-top: 16px"
          />
        </a-modal>
      </div>

      <!-- 信息提示 -->
      <a-alert
        type="info"
        :message="
          t('当前公司主体已创建') +
          rulesData.length +
          t('条规则,上限') +
          ruleLimit +
          t('条。如有疑问,请联系您的运营经理')
        "
        show-icon
        style="margin-bottom: 16px"
      />

      <!-- 数据表格 -->
      <a-table
        :loading="loading"
        :columns="columns"
        :data-source="rulesData"
        :pagination="pagination"
        :row-key="record => record.id"
        :row-selection="rowSelection"
        @change="handleTableChange"
      >
        <template #emptyText>
          <a-empty :description="t('暂无数据, 请创建规则或等待已有规则的执行')" />
        </template>
        <template #bodyCell="{ column, record }">
          <template v-if="column.dataIndex === 'status'">
            <a-badge :status="record.status === 1 ? 'success' : 'default'" />
            {{ record.status === 1 ? t('启用') : t('暂停') }}
          </template>
          <template v-else-if="column.dataIndex === 'operation'">
            <a-space>
              <a-button type="link" size="small" @click="handleEdit(record)">
                {{ t('编辑') }}
              </a-button>
              <a-button type="link" size="small" @click="handleCopy(record)">
                {{ t('复制') }}
              </a-button>

              <a-popconfirm :title="t('确定要删除这条规则吗?')" @confirm="handleDelete(record)">
                <a-button type="link" size="small" danger>{{ t('删除') }}</a-button>
              </a-popconfirm>
              <a-button
                v-if="record.audit_status === 0 && isSuperAdmin"
                type="link"
                size="small"
                @click="handleAudit(record)"
              >
                {{ t('审核') }}
              </a-button>
            </a-space>
          </template>
        </template>
      </a-table>
    </template>
  </div>
</template>

<script lang="ts" setup>
import { ref, computed, onMounted, h } from 'vue';
import { useUserStore } from '@/store/user';
import dayjs from 'dayjs';
import { useI18n } from 'vue-i18n';
import {
  BellOutlined,
  PoweroffOutlined,
  EditOutlined,
  DollarOutlined,
  CheckCircleOutlined,
  DownOutlined,
} from '@ant-design/icons-vue';
import { message, Badge as ABadge, Tooltip as ATooltip } from 'ant-design-vue';
import {
  getRulesList,
  getSummaryData,
  getCreators,
  batchActiveRule,
  batchInactiveRule,
  batchDeleteRule,
  auditRule,
} from '@/api/promotion/index';
import CreateRule from './create-rule.vue';

const { t } = useI18n();
const userStore = useUserStore();
const isSuperAdmin = computed(() => userStore.is_super === 1);

/** 父子组件切换：不通过路由 query，仅本地状态 */
const showCreateRule = ref(false);

const summaryData = ref({
  sendNotification: 0,
  sendNotificationHours: 0,
  toggleAds: 0,
  toggleAdsHours: 0,
  modifyBid: 0,
  modifyBidHours: 0,
  modifyBudget: 0,
  modifyBudgetHours: 0,
  allActions: 0,
  allActionsHours: 0,
});

const filters = ref({
  keyword: '',
  channel: undefined,
  monitoring_object: undefined,
  user_id: undefined,
});

const creators = ref<any[]>([]);
const creatorsMap = computed(() => {
  const map: Record<string, string> = {};
  creators.value.forEach(c => {
    map[c.id] = c.name;
  });
  return map;
});
const ruleLimit = ref(15);

const loading = ref(false);
const rulesData = ref<any[]>([]);
const selectedRowKeys = ref<string[]>([]);
const confirmVisible = ref(false);
const confirmAction = ref<'active' | 'inactive' | 'delete'>('active');
const confirmTitle = computed(() => {
  const map = { active: t('启用'), inactive: t('停用'), delete: t('删除') };
  return t('确认批量') + map[confirmAction.value] + '?';
});

// 审核相关
const auditRecord = ref<any>(null);
const auditVisible = ref(false);
const auditAction = ref<'approve' | 'reject'>('approve');
const auditReason = ref('');
const pagination = ref({
  current: 1,
  pageSize: 20,
  total: 0,
  showTotal: (total: number) => t('共') + total + t('条'),
});

const columns = ref([
  {
    title: t('状态'),
    dataIndex: 'status',
    key: 'status',
    width: 100,
    customRender: ({ text }: { text: number }) => (text === 1 ? t('启用') : t('暂停')),
  },
  { title: t('渠道'), dataIndex: 'channel', key: 'channel', width: 100 },
  {
    title: t('监控对象'),
    dataIndex: 'monitoring_object',
    key: 'monitoring_object',
    width: 120,
    customRender: ({ text }: { text: string }) => {
      const map: Record<string, string> = {
        'ad-account': t('广告账户'),
        campaign: t('广告系列'),
        'ad-group': t('广告组'),
        ad: t('广告'),
      };
      return map[text] || text;
    },
  },
  { title: t('规则名称'), dataIndex: 'name', key: 'name', width: 200 },
  { title: t('规则描述'), dataIndex: 'description', key: 'description', width: 250 },
  {
    title: t('创建人'),
    dataIndex: 'user_id',
    key: 'user_id',
    width: 100,
    customRender: ({ text }: { text: string | number }) => creatorsMap.value[String(text)] || text,
  },
  {
    title: t('最近执行时间'),
    dataIndex: 'updated_at',
    key: 'updated_at',
    width: 180,
    customRender: ({ text }: { text: string }) => {
      if (!text) return '-';
      return dayjs(text).format('YYYY-MM-DD HH:mm:ss');
    },
  },
  {
    title: t('编辑时间'),
    dataIndex: 'updated_at',
    key: 'updated_at',
    width: 180,
    customRender: ({ text }: { text: string }) => {
      if (!text) return '-';
      return dayjs(text).format('YYYY-MM-DD HH:mm:ss');
    },
  },
  {
    title: t('审核状态'),
    dataIndex: 'audit_status',
    key: 'audit_status',
    width: 100,
    customRender: ({ text, record }: { text: number; record: any }) => {
      const statusMap: Record<number, { text: string; status: 'default' | 'success' | 'error' }> = {
        0: { text: t('待审核'), status: 'default' },
        1: { text: t('审核通过'), status: 'success' },
        2: { text: t('已拒绝'), status: 'error' },
      };
      const { text: labelText, status } = statusMap[text] || { text, status: 'default' };
      if (text === 2 && record.audit_reason) {
        return h('span', [
          h(
            ATooltip,
            { title: record.audit_reason },
            { default: () => h(ABadge, { status, text: labelText }) },
          ),
        ]);
      }
      return h(ABadge, { status, text: labelText });
    },
  },
  {
    title: t('操作'),
    dataIndex: 'operation',
    key: 'operation',
    width: 180,
    fixed: 'right',
  },
]);

const rowSelection = computed(() => ({
  selectedRowKeys: selectedRowKeys.value,
  onChange: (keys: string[]) => {
    selectedRowKeys.value = keys;
  },
}));

const loadData = async () => {
  loading.value = true;
  try {
    const result = await getRulesList({
      page: pagination.value.current,
      pageSize: pagination.value.pageSize,
      ...filters.value,
    });
    rulesData.value = result.data;
    pagination.value.total = result.total;
  } catch (error) {
    console.error('加载数据失败:', error);
  } finally {
    loading.value = false;
  }
};

const loadSummary = async () => {
  try {
    const data = await getSummaryData();
    summaryData.value = data;
  } catch (error) {
    console.error('加载统计数据失败:', error);
  }
};

const loadCreators = async () => {
  try {
    creators.value = await getCreators();
  } catch (error) {
    console.error('加载创建人列表失败:', error);
  }
};

const handleTableChange = (pag: any) => {
  pagination.value.current = pag.current;
  pagination.value.pageSize = pag.pageSize;
  loadData();
};

const handleFilter = () => {
  pagination.value.current = 1;
  loadData();
};

const handleReset = () => {
  filters.value = {
    keyword: '',
    channel: undefined,
    monitoring_object: undefined,
    user_id: undefined,
  };
  pagination.value.current = 1;
  loadData();
};

const handleEdit = (record: any) => {
  showCreateRule.value = { mode: 'edit', data: record };
};

const handleCopy = (record: any) => {
  void record;
  showCreateRule.value = true;
};

const handleDelete = async (record: any) => {
  try {
    await batchDeleteRule([record.id]);
    message.success(t('删除成功'));
    loadData();
  } catch (error) {
    console.error('删除失败:', error);
    message.error(t('删除失败'));
  }
};

const handleAudit = (record: any) => {
  auditRecord.value = record;
  auditAction.value = 'approve';
  auditReason.value = '';
  auditVisible.value = true;
};

const handleConfirmAudit = async () => {
  if (!auditRecord.value) return;
  if (auditAction.value === 'reject' && !auditReason.value) {
    message.warning(t('请输入拒绝原因'));
    return;
  }
  const auditStatus = auditAction.value === 'approve' ? 1 : 2;
  try {
    await auditRule(auditRecord.value.id, auditStatus, auditReason.value || undefined);
    message.success(auditAction.value === 'approve' ? t('审核通过') : t('审核已拒绝'));
    auditVisible.value = false;
    auditReason.value = '';
    loadData();
  } catch (error) {
    console.error('审核失败:', error);
    message.error(t('审核失败'));
  }
};

const handleConfirmBatch = async () => {
  confirmVisible.value = false;
  try {
    if (confirmAction.value === 'active') {
      await batchActiveRule(selectedRowKeys.value as unknown as number[]);
      message.success(t('启用成功'));
    } else if (confirmAction.value === 'inactive') {
      await batchInactiveRule(selectedRowKeys.value as unknown as number[]);
      message.success(t('停用成功'));
    } else if (confirmAction.value === 'delete') {
      await batchDeleteRule(selectedRowKeys.value as unknown as number[]);
      message.success(t('删除成功'));
    }
    selectedRowKeys.value = [];
    loadData();
  } catch (error) {
    console.error('批量操作失败:', error);
    message.error(t('操作失败'));
  }
};

const goToCreateRule = () => {
  showCreateRule.value = true;
};

const exitCreateRule = () => {
  showCreateRule.value = false;
  loadData();
};

onMounted(() => {
  loadData();
  loadSummary();
  loadCreators();
});
</script>

<style lang="less" scoped>
.auto-optimization {
  .summary-cards {
    background: #fff;
    padding: 24px;
    border-radius: 4px;
    margin-bottom: 16px;

    .summary-title {
      font-size: 16px;
      font-weight: 500;
      margin-bottom: 16px;
      color: #333;
    }

    .cards-grid {
      display: grid;
      grid-template-columns: repeat(5, 1fr);
      gap: 16px;
      margin-bottom: 16px;

      .summary-card {
        display: flex;
        align-items: center;
        gap: 12px;
        padding: 16px;
        background: #fafafa;
        border-radius: 4px;
        border: 1px solid #e8e8e8;

        .card-icon {
          width: 48px;
          height: 48px;
          border-radius: 4px;
          display: flex;
          align-items: center;
          justify-content: center;
          font-size: 24px;
          flex-shrink: 0;
        }

        .card-content {
          flex: 1;

          .card-value {
            font-size: 24px;
            font-weight: 600;
            color: #333;
            line-height: 1.2;
          }

          .card-label {
            font-size: 14px;
            color: #666;
            margin-top: 4px;
          }

          .card-saved {
            font-size: 12px;
            color: #999;
            margin-top: 4px;
          }
        }
      }
    }

    .create-rule-btn {
      text-align: left;
    }
  }

  .filter-bar {
    display: flex;
    gap: 8px;
    margin-bottom: 16px;
    align-items: center;
    flex-wrap: wrap;
  }
}
</style>
