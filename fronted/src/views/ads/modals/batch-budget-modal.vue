<template>
  <a-modal
    :visible="visible"
    :title="modalTitle"
    :width="900"
    :footer="null"
    :mask-closable="false"
    destroy-on-close
    @cancel="handleCancel"
  >
    <div class="batch-budget-content">
      <div class="summary-info">
        <a-alert
          :message="summaryMessage"
          type="info"
          show-icon
          style="margin-bottom: 16px"
        />
      </div>

      <div class="budget-form">
        <a-form
          :model="formData"
          layout="vertical"
          @finish="handleSubmit"
        >
          <!-- 全局预算设置 -->
          <a-card
            size="small"
            :title="t('Global Budget Settings')"
            style="margin-bottom: 16px"
          >
            <a-row :gutter="16">
              <a-col :span="8" style="display: none;">
                <a-form-item :label="t('Budget Type')">
                  <a-select
                    v-model:value="globalBudgetType"
                    @change="handleGlobalBudgetTypeChange"
                  >
                    <a-select-option value="daily_budget">
                      {{ t('Daily Budget') }}
                    </a-select-option>
                    <a-select-option value="lifetime_budget">
                      {{ t('Lifetime Budget') }}
                    </a-select-option>
                  </a-select>
                </a-form-item>
              </a-col>
              <a-col :span="8">
                <a-form-item :label="t('Budget Amount')">
                  <a-input-number
                    v-model:value="globalBudgetAmount"
                    :min="0"
                    :precision="2"
                    style="width: 100%"
                    :placeholder="t('Enter budget amount')"
                  />
                </a-form-item>
              </a-col>
              <a-col :span="8">
                <a-form-item :label="t('Apply to All')">
                  <a-button
                    type="primary"
                    @click="applyGlobalBudget"
                    :disabled="!globalBudgetAmount"
                  >
                    {{ t('Apply to All') }}
                  </a-button>
                </a-form-item>
              </a-col>
            </a-row>
          </a-card>

          <!-- 详细预算列表 -->
          <a-card size="small" :title="t('Individual Budget Settings')">
            <div class="budget-table">
              <a-table
                :columns="columns"
                :data-source="budgetItems"
                :pagination="false"
                :scroll="{ y: 400 }"
                size="small"
                bordered
              >
                <template #bodyCell="{ column, record, index }">
                  <template v-if="column.key === 'index'">
                    {{ index + 1 }}
                  </template>
                  <template v-else-if="column.key === 'name'">
                    <div class="name-cell">
                      <div class="main-name">{{ record.name }}</div>
                      <div class="sub-info">
                        <span class="ad-account">{{ record.ad_account_name }}</span>
                        <span class="object-id">{{ record.object_id }}</span>
                      </div>
                    </div>
                  </template>
                  <template v-else-if="column.key === 'current_budget'">
                    <div class="budget-cell">
                      <span v-if="record.current_budget">
                        {{ formatBudget(record.current_budget) }}
                      </span>
                      <span v-else class="no-budget">
                        {{ t('No Budget') }}
                      </span>
                      <div class="budget-type">
                        {{ record.budget_type === 'daily_budget' ? t('Daily') : t('Lifetime') }}
                      </div>
                    </div>
                  </template>
                  <template v-else-if="column.key === 'budget_type'">
                    <span class="budget-type-display">
                      {{ record.budget_type === 'daily_budget' ? t('Daily Budget') : t('Lifetime Budget') }}
                    </span>
                    <a-select
                      v-model:value="record.budget_type"
                      size="small"
                      style="width: 100%; display: none;"
                    >
                      <a-select-option value="daily_budget">
                        {{ t('Daily Budget') }}
                      </a-select-option>
                      <a-select-option value="lifetime_budget">
                        {{ t('Lifetime Budget') }}
                      </a-select-option>
                    </a-select>
                  </template>
                  <template v-else-if="column.key === 'new_budget'">
                    <a-input-number
                      v-model:value="record.new_budget"
                      :min="0"
                      :precision="2"
                      size="small"
                      style="width: 100%"
                      :placeholder="t('Enter new budget')"
                    />
                  </template>
                  <template v-else-if="column.key === 'status'">
                    <a-tag
                      :color="getStatusColor(record.status)"
                      size="small"
                    >
                      {{ record.status }}
                    </a-tag>
                  </template>
                </template>
              </a-table>
            </div>
          </a-card>

          <!-- 提交按钮 -->
          <div class="form-actions">
            <a-space>
              <a-button @click="handleCancel">
                {{ t('Cancel') }}
              </a-button>
              <a-button
                type="primary"
                html-type="submit"
                :loading="submitting"
                :disabled="!isFormValid"
              >
                {{ t('Update Budget') }}
              </a-button>
            </a-space>
          </div>
        </a-form>
      </div>
    </div>
  </a-modal>
</template>

<script lang="ts">
import { defineComponent, ref, computed, reactive, watch } from 'vue';
import { useI18n } from 'vue-i18n';
import { message } from 'ant-design-vue';
import { batchUpdateObjectBudget } from '@/api/ads';

interface BudgetItem {
  id: string;
  object_id: string;
  name: string;
  ad_account_name: string;
  object_type: 'campaign' | 'adset';
  current_budget: string | null;
  budget_type: 'daily_budget' | 'lifetime_budget';
  new_budget: number | null;
  status: string;
}

interface Props {
  visible: boolean;
  selectedData: Array<{
    id: string;
    name: string;
    ad_account_name: string;
    object_type: 'campaign' | 'adset';
    current_budget: string | null;
    daily_budget: string | null;
    lifetime_budget: string | null;
    status: string;
    object_id: string;
  }>;
  tabType: '2' | '3'; // '2' for campaign, '3' for adset
}

export default defineComponent({
  name: 'BatchBudgetModal',
  props: {
    visible: {
      type: Boolean,
      default: false,
    },
    selectedData: {
      type: Array as () => Props['selectedData'],
      default: () => [],
    },
    tabType: {
      type: String as () => Props['tabType'],
      default: '2',
    },
  },
  emits: ['update:visible', 'success'],
  setup(props, { emit }) {
    const { t } = useI18n();

    const formData = reactive({});
    const submitting = ref(false);
    const globalBudgetType = ref<'daily_budget' | 'lifetime_budget'>('daily_budget');
    const globalBudgetAmount = ref<number | null>(null);

    // 预算项目列表
    const budgetItems = ref<BudgetItem[]>([]);

    // 模态框标题
    const modalTitle = computed(() => {
      const objectType = props.tabType === '2' ? 'Campaign' : 'Adset';
      return t('Batch Update {type} Budget', { type: objectType });
    });

    // 汇总信息
    const summaryMessage = computed(() => {
      const objectType = props.tabType === '2' ? 'Campaign' : 'Adset';
      const selectedCount = props.selectedData.length;
      const validCount = budgetItems.value.length;

      if (validCount === 0) {
        return t('No {type} with budget settings found. Budget may be set at {otherLevel} level.', {
          type: objectType,
          otherLevel: props.tabType === '2' ? 'Adset' : 'Campaign',
        });
      }

      if (selectedCount === validCount) {
        return t('Selected {count} {type} for budget update', { count: validCount, type: objectType });
      } else {
        return t('Selected {validCount} of {totalCount} {type} for budget update (others have no budget settings)', {
          validCount,
          totalCount: selectedCount,
          type: objectType,
        });
      }
    });

    // 表格列定义
    const columns = computed(() => [
      {
        title: t('Index'),
        key: 'index',
        width: 60,
        align: 'center' as const,
      },
      {
        title: props.tabType === '2' ? t('Campaign') : t('Adset'),
        key: 'name',
        width: 250,
        ellipsis: true,
      },
      {
        title: t('Current Budget'),
        key: 'current_budget',
        width: 140,
        align: 'right' as const,
      },
      {
        title: t('Budget Type'),
        key: 'budget_type',
        width: 130,
      },
      {
        title: t('New Budget'),
        key: 'new_budget',
        width: 130,
      },
      {
        title: t('Status'),
        key: 'status',
        width: 100,
        align: 'center' as const,
      },
    ]);

    // 判断预算类型和过滤有效的预算对象
    const getValidBudgetItems = (data: Props['selectedData']) => {
      const validItems: BudgetItem[] = [];

      data.forEach(item => {
        let budgetType: 'daily_budget' | 'lifetime_budget' | null = null;
        let currentBudget: string | null = null;

        // 判断预算类型 - 根据对象类型优先判断
        if (item.object_type === 'adset') {
          // 对于Adset，优先检查daily_budget
          if (item.daily_budget && item.daily_budget !== '0' && parseFloat(item.daily_budget) > 0) {
            budgetType = 'daily_budget';
            currentBudget = item.daily_budget;
          } else if (item.lifetime_budget && item.lifetime_budget !== '0' && parseFloat(item.lifetime_budget) > 0) {
            budgetType = 'lifetime_budget';
            currentBudget = item.lifetime_budget;
          }
        } else {
          // 对于Campaign，优先检查lifetime_budget
          if (item.lifetime_budget && item.lifetime_budget !== '0' && parseFloat(item.lifetime_budget) > 0) {
            budgetType = 'lifetime_budget';
            currentBudget = item.lifetime_budget;
          } else if (item.daily_budget && item.daily_budget !== '0' && parseFloat(item.daily_budget) > 0) {
            budgetType = 'daily_budget';
            currentBudget = item.daily_budget;
          }
        }

        // 只有有预算设置的对象才添加到列表中
        if (budgetType && currentBudget) {
          validItems.push({
            id: item.id,
            object_id: item.object_id,
            name: item.name,
            ad_account_name: item.ad_account_name,
            object_type: item.object_type,
            current_budget: currentBudget,
            budget_type: budgetType,
            new_budget: parseFloat(currentBudget) || 0, // 默认值设置为当前预算，转换为数字
            status: item.status,
          });
        }
      });

      return validItems;
    };

    // 监听选中数据变化
    watch(
      () => props.selectedData,
      (newData) => {
        if (newData && newData.length > 0) {
          budgetItems.value = getValidBudgetItems(newData);
        } else {
          budgetItems.value = [];
        }
      },
      { immediate: true },
    );

    // 格式化预算显示
    const formatBudget = (budget: string | number | null) => {
      if (!budget) return '0';
      const num = typeof budget === 'string' ? parseFloat(budget) : budget;
      return num.toFixed(2);
    };

    // 获取状态颜色
    const getStatusColor = (status: string) => {
      const normalizedStatus = status?.toString().trim().toUpperCase();
      switch (normalizedStatus) {
        case 'ACTIVE':
          return 'green';
        case 'PAUSED':
          return 'orange';
        case 'DELETED':
        case 'ARCHIVED':
          return 'red';
        default:
          return 'gray';
      }
    };

    // 应用全局预算设置
    const applyGlobalBudget = () => {
      if (!globalBudgetAmount.value) {
        message.warning(t('Please enter budget amount'));
        return;
      }

      budgetItems.value.forEach(item => {
        item.budget_type = globalBudgetType.value;
        item.new_budget = globalBudgetAmount.value;
      });

      message.success(t('Global budget settings applied'));
    };

    // 全局预算类型变化
    const handleGlobalBudgetTypeChange = (value: 'daily_budget' | 'lifetime_budget') => {
      globalBudgetType.value = value;
    };

    // 表单验证
    const isFormValid = computed(() => {
      return budgetItems.value.every(item =>
        item.new_budget !== null && item.new_budget > 0,
      );
    });

    // 提交表单
    const handleSubmit = async () => {
      if (!isFormValid.value) {
        message.error(t('Please fill in all budget amounts'));
        return;
      }

      submitting.value = true;

      try {
        const items = budgetItems.value.map(item => ({
          id: item.object_id,
          object_type: item.object_type,
          budget_type: item.budget_type,
          budget: item.new_budget!.toString(),
        }));

        const response = await batchUpdateObjectBudget({ items }) as any;

        if (response.success) {
          message.success(response.message || t('Budget update submitted successfully'));
          emit('success', response);
          handleCancel();
        } else {
          message.error(response.message || t('Update failed'));
        }
      } catch (error) {
        console.error('批量更新预算失败:', error);
        message.error(t('Update failed'));
      } finally {
        submitting.value = false;
      }
    };

    // 取消操作
    const handleCancel = () => {
      // 重置表单
      budgetItems.value = [];
      globalBudgetAmount.value = null;
      globalBudgetType.value = 'daily_budget';

      emit('update:visible', false);
    };

    return {
      t,
      formData,
      submitting,
      globalBudgetType,
      globalBudgetAmount,
      budgetItems,
      modalTitle,
      summaryMessage,
      columns,
      formatBudget,
      getStatusColor,
      applyGlobalBudget,
      handleGlobalBudgetTypeChange,
      isFormValid,
      handleSubmit,
      handleCancel,
    };
  },
});
</script>

<style lang="less" scoped>
.batch-budget-content {
  .summary-info {
    margin-bottom: 16px;
  }

  .budget-form {
    .form-actions {
      display: flex;
      justify-content: flex-end;
      margin-top: 24px;
      padding-top: 16px;
      border-top: 1px solid #f0f0f0;
    }
  }

      .budget-table {
      .name-cell {
        .main-name {
          font-weight: 500;
          margin-bottom: 4px;
        }

        .sub-info {
          font-size: 12px;
          color: #666;

          .ad-account {
            margin-right: 8px;
          }

          .object-id {
            color: #1890ff;
          }
        }
      }

      .budget-type-display {
        font-size: 12px;
        color: #666;
        background: #f5f5f5;
        padding: 2px 8px;
        border-radius: 4px;
        display: inline-block;
      }

    .budget-cell {
      text-align: right;

      .budget-type {
        font-size: 12px;
        color: #666;
        margin-top: 2px;
      }

      .no-budget {
        color: #999;
      }
    }
  }
}
</style>