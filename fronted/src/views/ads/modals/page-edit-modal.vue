<template>
  <a-modal
    :title="t('Edit Page')"
    :open="open"
    :width="600"
    :mask-closable="false"
    :z-index="1050"
    @ok="handleOk"
    @cancel="handleCancel"
  >
    <div class="page-edit-modal">
      <!-- 当前Page信息 -->
      <div class="current-page-info">
        <h4>{{ t('Current Page') }}</h4>
        <div class="page-info-row">
          <span class="page-name">{{ currentPageInfo.name }}</span>
          <span class="page-id">({{ currentPageInfo.id }})</span>
        </div>
      </div>

      <a-divider />

      <!-- 选择新的Page -->
      <div class="page-selection">
        <h4>{{ t('Select New Page') }}</h4>

        <a-spin :spinning="loading">
          <a-form layout="vertical">
            <a-form-item :label="t('Operator')" required>
              <a-select
                v-model:value="selectedOperator"
                :placeholder="t('Select operator')"
                :options="operatorOptions"
                @change="handleOperatorChange"
                show-search
                :filter-option="filterOperatorOption"
              />
            </a-form-item>

            <a-form-item :label="t('Page')" required>
              <a-select
                v-model:value="selectedPage"
                :placeholder="t('Select page')"
                :options="pageOptions"
                :disabled="!selectedOperator"
                show-search
                :filter-option="filterPageOption"
              />
            </a-form-item>
          </a-form>
        </a-spin>
      </div>
    </div>
  </a-modal>
</template>

<script setup lang="ts">
import { ref, computed, watch } from 'vue';
import { useI18n } from 'vue-i18n';
import { message } from 'ant-design-vue';
import { getAdAccountData } from '@/api/adaccount/table-list';

interface Props {
  open: boolean;
  adAccountId: string;
  currentPageInfo: {
    id: string;
    name: string;
  };
}

interface Emits {
  (event: 'cancel'): void;
  (event: 'confirm', pageInfo: { id: string; name: string }): void;
}

interface FbApiToken {
  id: string;
  name: string;
  active: boolean;
  token_type: number;
  bm: {
    id: string;
    source_id: string;
    name: string;
    users: Array<{
      id: string;
      source_id: string;
      name: string;
      role: string;
    }>;
  };
}

interface FbBusinessUser {
  id: string;
  source_id: string;
  email: string;
  name: string;
  role: string;
  is_operator: boolean;
  assigned_pages: Array<{
    id: string;
    source_id: string;
    name: string;
    picture?: string;
  }>;
}

interface OperatorOption {
  value: string;
  label: string;
  data: FbBusinessUser;
}

interface PageOption {
  value: string;
  label: string;
  data: {
    id: string;
    source_id: string;
    name: string;
  };
}

const props = defineProps<Props>();
const emit = defineEmits<Emits>();

const { t } = useI18n();
const loading = ref(false);
const selectedOperator = ref<string>('');
const selectedPage = ref<string>('');
const operatorOptions = ref<OperatorOption[]>([]);
const pageOptions = ref<PageOption[]>([]);

// 计算属性
const canConfirm = computed(() => {
  return selectedOperator.value && selectedPage.value;
});

// 获取广告账户数据
const fetchAdAccountData = async () => {
  if (!props.adAccountId) return;

  loading.value = true;
  try {
      const response = await getAdAccountData({
    ad_account_ids: [props.adAccountId],
  });

    if (response.data && response.data.length > 0) {
      const adAccountData = response.data[0];
      processAdAccountData(adAccountData);
    }
  } catch (error) {
    console.error('获取广告账户数据失败:', error);
    message.error(t('Failed to fetch ad account data'));
  } finally {
    loading.value = false;
  }
};

// 处理广告账户数据
const processAdAccountData = (adAccountData: any) => {
  const fbApiTokens: FbApiToken[] = adAccountData.fb_api_token || [];
  const fbBusinessUsers: FbBusinessUser[] = adAccountData.fb_business_users || [];

  // 只处理token_type为1的
  const validTokens = fbApiTokens.filter(token => token.token_type === 1);

  const operators: OperatorOption[] = [];

  validTokens.forEach(token => {
    if (token.bm && token.bm.users) {
      token.bm.users.forEach(user => {
        // 在fb_business_users中找到匹配的用户
                const businessUser = fbBusinessUsers.find(bu =>
          bu.source_id === user.source_id && bu.is_operator === true,
        );

        if (businessUser) {
          operators.push({
            value: businessUser.source_id,
            label: `${token.name} - ${businessUser.name}`,
            data: businessUser,
          });
        }
      });
    }
  });

  operatorOptions.value = operators;

  // 如果有操作员，默认选择第一个
  if (operators.length > 0) {
    selectedOperator.value = operators[0].value;
    handleOperatorChange(operators[0].value);
  }
};

// 处理操作员变化
const handleOperatorChange = (operatorId: string) => {
  selectedPage.value = '';
  pageOptions.value = [];

  if (!operatorId) return;

  const operator = operatorOptions.value.find(op => op.value === operatorId);
  if (operator && operator.data.assigned_pages) {
    const pages: PageOption[] = operator.data.assigned_pages.map(page => ({
      value: page.source_id,
      label: `${page.name} (${page.source_id})`,
      data: {
        id: page.id,
        source_id: page.source_id,
        name: page.name,
      },
    }));

    pageOptions.value = pages;

    // 设置默认选择的页面
    if (pages.length > 0) {
      // 优先选择当前的主页（如果在列表中）
      const currentPageOption = pages.find(page => page.value === props.currentPageInfo.id);
      if (currentPageOption) {
        selectedPage.value = currentPageOption.value;
      } else {
        // 如果当前主页不在列表中，选择第一个选项
        selectedPage.value = pages[0].value;
      }
    }
  }
};

// 过滤操作员选项
const filterOperatorOption = (input: string, option: any) => {
  return option.label.toLowerCase().includes(input.toLowerCase());
};

// 过滤页面选项
const filterPageOption = (input: string, option: any) => {
  return option.label.toLowerCase().includes(input.toLowerCase());
};

// 处理确认
const handleOk = () => {
  if (!canConfirm.value) {
    message.warning(t('Please select operator and page'));
    return;
  }

  // 获取选择的页面信息
  const selectedPageInfo = pageOptions.value.find(page => page.value === selectedPage.value);

  emit('confirm', {
    id: selectedPage.value,
    name: selectedPageInfo?.data.name || 'Unknown Page',
  });
};

// 处理取消
const handleCancel = () => {
  emit('cancel');
};

// 监听弹窗打开状态
watch(
  () => props.open,
  (newVal) => {
    if (newVal) {
      // 重置状态
      selectedOperator.value = '';
      selectedPage.value = '';
      operatorOptions.value = [];
      pageOptions.value = [];

      // 获取数据
      fetchAdAccountData();
    }
  },
  { immediate: true },
);
</script>

<style scoped lang="less">
.page-edit-modal {
  .current-page-info {
    margin-bottom: 16px;

    h4 {
      margin-bottom: 8px;
      color: rgba(0, 0, 0, 0.85);
      font-weight: 600;
    }

    .page-info-row {
      padding: 8px 12px;
      background: #f5f5f5;
      border-radius: 6px;
      border-left: 3px solid #1890ff;

      .page-name {
        font-weight: 500;
        color: rgba(0, 0, 0, 0.85);
      }

      .page-id {
        margin-left: 8px;
        color: #666;
        font-size: 12px;
      }
    }
  }

  .page-selection {
    h4 {
      margin-bottom: 16px;
      color: rgba(0, 0, 0, 0.85);
      font-weight: 600;
    }
  }
}
</style>