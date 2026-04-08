<template>
  <a-modal
    v-model:visible="visible"
    :title="t('Payment Information')"
    :width="1000"
    :footer="null"
    :mask-closable="true"
    class="payment-modal"
  >
    <div class="payment-content">
      <div class="payment-header">
        <a-row justify="space-between" align="middle">
          <a-col>
            <h3>
              {{ tabType === '2' ? t('Campaign Budget Summary') : t('Adset Budget Summary') }}
              <a-tag color="blue" style="margin-left: 8px">
                {{ totalRecords }} {{ t('records') }}
              </a-tag>
            </h3>
          </a-col>
          <a-col>
            <a-space>
              <a-button size="small" @click="expandAll">
                {{ t('Expand All') }}
              </a-button>
              <a-button size="small" @click="collapseAll">
                {{ t('Collapse All') }}
              </a-button>
            </a-space>
          </a-col>
        </a-row>
      </div>

      <!-- 简化的批量操作 -->
      <div class="batch-operations" v-if="hasValidCards">
        <a-space>
          <a-button
            :loading="batchOperations.refreshing"
            @click="handleBatchRefreshCards"
            size="small"
          >
            {{ t('Refresh cards') }}
          </a-button>
          <a-button
            :loading="batchOperations.settingBalance"
            @click="handleBatchSetBalance"
            size="small"
          >
            {{ t('Set balance') }}
          </a-button>
          <a-button
            :loading="batchOperations.settingLimit"
            @click="handleBatchSetSingleLimit"
            size="small"
          >
            {{ t('Set single limit') }}
          </a-button>
          <a-button
            :loading="batchOperations.freezing"
            @click="handleBatchFreezeCards"
            size="small"
          >
            {{ t('Freeze cards') }}
          </a-button>
          <a-button
            :loading="batchOperations.unfreezing"
            @click="handleBatchUnfreezeCards"
            size="small"
          >
            {{ t('Unfreeze cards') }}
          </a-button>
        </a-space>
      </div>

      <div class="payment-cards">
        <div v-for="(group, index) in groupedPayments" :key="index" class="payment-group">
          <a-card
            size="small"
            :class="['payment-card', { 'no-funding': group.paymentType === 'no_funding' }]"
          >
            <template #title>
              <div class="card-title-wrapper">
                <span class="card-title">{{ group.title }}</span>

                <!-- 简化的卡片操作 - 只在有效卡片时显示，但topup组不显示 -->
                <div
                  v-if="
                    !group.isTopupGroup &&
                    group.defaultCard &&
                    isValidCard(group.funding, group.defaultCard)
                  "
                  class="card-actions"
                >
                  <a-dropdown :trigger="['click']">
                    <a-button type="text" size="small">
                      {{ t('Actions') }}
                      <down-outlined />
                    </a-button>
                    <template #overlay>
                      <a-menu @click="(e) => handleCardAction(e.key as string, group)">
                        <a-menu-item key="refresh" :disabled="group.refreshing">
                          {{ t('Refresh card') }}
                        </a-menu-item>
                        <a-menu-item key="transactions" v-if="group.defaultCard.latest_transaction">
                          {{ t('View transactions') }}
                        </a-menu-item>
                      </a-menu>
                    </template>
                  </a-dropdown>
                </div>
              </div>
            </template>

            <template #extra>
              <div class="card-extra">
                <div class="budget-amount">
                  <span class="amount">{{ formatBudget(group.totalBudget) }}</span>
                  <span class="currency">USD</span>
                </div>
                <!-- Topup组不显示复制按钮 -->
                <div v-if="!group.isTopupGroup">
                  <!-- 对于月度账单，显示两个复制按钮 -->
                  <div v-if="group.paymentType === 'monthly invoicing'" class="copy-buttons">
                    <a-button type="primary" size="small" @click="copyGroupNeedRecharge(group)">
                      <copy-outlined />
                      {{ t('Copy Need Recharge') }}
                    </a-button>
                    <a-button type="text" size="small" @click="copyGroupPayment(group)">
                      <copy-outlined />
                      {{ t('Copy All') }}
                    </a-button>
                  </div>
                  <!-- 对于其他类型，显示单个复制按钮 -->
                  <a-button v-else type="text" size="small" @click="copyGroupPayment(group)">
                    <copy-outlined />
                    {{ t('Copy') }}
                  </a-button>
                </div>
              </div>
            </template>

            <!-- Topup组的特殊显示 -->
            <div v-if="group.isTopupGroup" class="card-info">
              <a-row :gutter="16" align="middle">
                <a-col span="6">
                  <div class="info-item">
                    <label>{{ t('Spend Cap') }}:</label>
                    <span>
                      {{ group.spendCap ? `${group.spendCap} ${group.currency || 'USD'}` : 'N/A' }}
                    </span>
                  </div>
                </a-col>
                <a-col span="5">
                  <div class="info-item">
                    <label>{{ t('Total Spend') }}:</label>
                    <span>
                      {{
                        group.totalSpent ? `${group.totalSpent} ${group.currency || 'USD'}` : 'N/A'
                      }}
                    </span>
                  </div>
                </a-col>
                <a-col span="5">
                  <div class="info-item">
                    <label>{{ t('Balance') }}:</label>
                    <span :class="{ 'negative-balance': calculateBalance(group) < 0 }">
                      {{ formatBalance(group) }}
                    </span>
                  </div>
                </a-col>
                <a-col span="8">
                  <div class="input-group">
                    <label>{{ t('Topup') }}:</label>
                    <div class="input-with-button">
                      <a-input-number
                        v-model:value="group.topupInput"
                        :min="0.01"
                        :max="9999999"
                        :precision="2"
                        size="small"
                        :formatter="value => `$ ${value}`.replace(/\B(?=(\d{3})+(?!\d))/g, ',')"
                        :parser="value => value.replace(/\$\s?|(,*)/g, '')"
                        style="width: 100px"
                        placeholder="Amount"
                      />
                      <a-button
                        type="text"
                        size="small"
                        :loading="group.settingTopup"
                        @click="handleSetTopup(group)"
                        class="mini-save-btn"
                      >
                        <template #icon><save-outlined /></template>
                      </a-button>
                      <a-button
                        type="text"
                        size="small"
                        :loading="group.syncing"
                        @click="handleSyncAccountInfo(group)"
                        class="mini-sync-btn"
                        :title="t('Sync account info')"
                      >
                        <template #icon>🔄</template>
                      </a-button>
                    </div>
                  </div>
                </a-col>
              </a-row>
            </div>

            <!-- 普通组的原有显示 -->
            <div
              v-else-if="group.defaultCard && isValidCard(group.funding, group.defaultCard)"
              class="card-info"
            >
              <a-row :gutter="16" align="middle">
                <a-col span="6">
                  <div class="input-group">
                    <label>{{ t('Balance') }}:</label>
                    <div class="input-with-button">
                      <a-input-number
                        v-model:value="group.balanceInput"
                        :min="0"
                        :max="9999999"
                        :precision="2"
                        size="small"
                        :formatter="value => `$ ${value}`.replace(/\B(?=(\d{3})+(?!\d))/g, ',')"
                        :parser="value => value.replace(/\$\s?|(,*)/g, '')"
                        style="width: 100px"
                      />
                      <a-button
                        type="text"
                        size="small"
                        :loading="group.settingBalance"
                        @click="handleSetBalance(group)"
                        class="mini-save-btn"
                      >
                        <template #icon><save-outlined /></template>
                      </a-button>
                    </div>
                  </div>
                </a-col>
                <a-col span="6">
                  <div class="input-group">
                    <label>{{ t('Single limit') }}:</label>
                    <div class="input-with-button">
                      <a-input-number
                        v-model:value="group.limitInput"
                        :min="0"
                        :max="9999999"
                        :precision="2"
                        size="small"
                        :formatter="value => `$ ${value}`.replace(/\B(?=(\d{3})+(?!\d))/g, ',')"
                        :parser="value => value.replace(/\$\s?|(,*)/g, '')"
                        style="width: 100px"
                      />
                      <a-button
                        type="text"
                        size="small"
                        :loading="group.settingLimit"
                        @click="handleSetSingleLimit(group)"
                        class="mini-save-btn"
                      >
                        <template #icon><save-outlined /></template>
                      </a-button>
                    </div>
                  </div>
                </a-col>
                <a-col span="6">
                  <div class="info-item">
                    <label>{{ t('Status') }}:</label>
                    <a-switch
                      :checked="group.defaultCard.status === 'ACTIVE'"
                      :loading="group.freezing"
                      size="small"
                      @change="checked => handleSwitchStatus(group, checked)"
                      class="status-switch"
                    />
                  </div>
                </a-col>
                <a-col span="6">
                  <div class="info-item" v-if="group.defaultCard.latest_transaction">
                    <label>{{ t('Last transaction') }}:</label>
                    <span>
                      $
                      <a
                        @click="handleShowTransactions(group.defaultCard)"
                        class="transaction-link"
                      >
                        {{ group.defaultCard.latest_transaction.transaction_amount }}
                      </a>
                    </span>
                  </div>
                </a-col>
              </a-row>
            </div>

            <div class="account-details" v-if="group.adAccountGroups.length > 0">
              <a-collapse size="small" v-model:activeKey="activeKeys">
                <a-collapse-panel
                  v-for="(adAccountGroup, adAccountIndex) in group.adAccountGroups"
                  :key="`group-${index}-${adAccountIndex}`"
                >
                  <template #header>
                    <div class="account-item panel-header">
                      <div class="account-name">
                        {{ adAccountGroup.adAccountName }}
                        <!-- 对于月度账单，显示余额 -->
                        <span
                          v-if="
                            group.paymentType === 'monthly invoicing' &&
                            adAccountGroup.accountBalance !== undefined
                          "
                          class="account-balance"
                        >
                          (余额: {{ formatBudget(adAccountGroup.accountBalance) }})
                        </span>
                      </div>
                      <div class="account-id">({{ adAccountGroup.adAccountId }})</div>
                      <a-badge
                        :color="getStatusColor(adAccountGroup.accountStatus)"
                        :text="adAccountGroup.accountStatus"
                        class="account-status"
                      />
                      <div class="account-budget">
                        {{ formatBudget(adAccountGroup.totalBudget) }}
                        <!-- 对于月度账单，显示充值金额和复制按钮 -->
                        <span
                          v-if="group.paymentType === 'monthly invoicing'"
                          class="account-actions"
                        >
                          <!-- 如果需要充值，显示充值金额 -->
                          <span
                            v-if="
                              adAccountGroup.rechargeAmount !== undefined &&
                              adAccountGroup.rechargeAmount > 0
                            "
                            class="recharge-info"
                          >
                            需充值: {{ formatBudget(adAccountGroup.rechargeAmount) }}
                          </span>
                          <!-- 每个账户都有复制按钮 -->
                          <a-button
                            type="text"
                            size="small"
                            @click.stop="copyRechargeInfo(adAccountGroup)"
                            class="copy-recharge-btn"
                          >
                            <copy-outlined />
                          </a-button>
                        </span>
                      </div>
                    </div>
                  </template>

                  <div class="account-campaigns">
                    <div
                      v-for="account in adAccountGroup.accounts"
                      :key="account.id"
                      class="campaign-item"
                    >
                      <div class="campaign-name">{{ account.name }}</div>
                      <div class="campaign-details">
                        <span class="campaign-id">{{ account.id }}</span>
                        <span class="campaign-budget">{{ formatBudget(account.budget) }}</span>
                      </div>
                    </div>
                  </div>
                </a-collapse-panel>
              </a-collapse>
            </div>
          </a-card>
        </div>
      </div>
    </div>

    <!-- 交易历史模态框 -->
    <a-modal
      v-model:visible="transactionModalVisible"
      :title="t('Transaction History')"
      :width="800"
      :footer="null"
    >
      <a-table
        :columns="transactionColumns"
        :data-source="transactionData"
        :pagination="transactionPagination"
        :loading="transactionLoading"
        @change="handleTransactionTableChange"
        size="small"
      >
        <template #bodyCell="{ column, record }">
          <template v-if="column.key === 'transaction_amount'">
            <span :class="{ 'negative-amount': record.transaction_amount < 0 }">
              ${{ record.transaction_amount }}
            </span>
          </template>
          <template v-else-if="column.key === 'status'">
            <a-tag :color="record.status === 'completed' ? 'green' : 'orange'">
              {{ record.status }}
            </a-tag>
          </template>
        </template>
      </a-table>
    </a-modal>
  </a-modal>
</template>

<script lang="ts" setup>
import { computed, ref, reactive, watch, h } from 'vue';
import { useI18n } from 'vue-i18n';
import { message } from 'ant-design-vue';
import { CopyOutlined, SaveOutlined, DownOutlined } from '@ant-design/icons-vue';
import { useClipboard } from '@vueuse/core';
import {
  syncMultipleCard,
  setCardBalance,
  setCardSingleLimit,
  queryCardTransactionListAPI,
  freezeCard,
  unfreezeCard,
} from '@/api/virtual_cards';
import { setAccountSpendCap, syncAccountSpendInfo } from '@/api/fb_ad_accounts';

interface DefaultCard {
  id: string;
  name: string;
  is_default: boolean;
  number: string;
  status: string;
  balance: string;
  single_transaction_limit: string;
  currency: string;
  latest_transaction?: {
    id: string;
    transaction_amount: number;
    currency: string;
    transaction_date: string;
    merchant_name: string;
  };
}

interface PaymentData {
  id: string;
  ad_account_id: string;
  ad_account_ulid: string;
  ad_account_name: string;
  account_status: string;
  funding: string;
  daily_budget: string | null;
  currency: string;
  default_card?: DefaultCard;
  spend_cap?: string;
  balance?: string;
  total_spent?: string;
  is_topup?: boolean;
  // Campaign/Adset相关信息
  campaign_id?: string;
  campaign_name?: string;
  adset_id?: string;
  adset_name?: string;
}

interface PaymentGroup {
  title: string;
  paymentType: string;
  last4: string;
  funding: string;
  totalBudget: number;
  defaultCard?: DefaultCard;
  balanceInput: number;
  limitInput: number;
  refreshing: boolean;
  settingBalance: boolean;
  settingLimit: boolean;
  freezing: boolean;
  // Topup相关字段
  isTopupGroup?: boolean;
  topupInput?: number;
  settingTopup?: boolean;
  syncing?: boolean;
  spendCap?: string;
  totalSpent?: string;
  currency?: string;
  adAccountGroups: Array<{
    adAccountName: string;
    adAccountId: string;
    adAccountUlid: string;
    accountStatus: string;
    totalBudget: number;
    accountBalance?: number; // 对于月度账单，表示广告账户的余额
    rechargeAmount?: number; // 对于月度账单，表示需要充值的金额
    accounts: Array<{
      id: string;
      name: string;
      budget: string;
    }>;
  }>;
}

const props = defineProps<{
  visible: boolean;
  selectedData: PaymentData[];
  tabType: string; // '2' for campaign, '3' for adset
}>();

const emit = defineEmits<{
  'update:visible': [value: boolean];
}>();

const { t } = useI18n();
const { copy } = useClipboard();

// 批量操作状态
const batchOperations = reactive({
  refreshing: false,
  settingBalance: false,
  settingLimit: false,
  freezing: false,
  unfreezing: false,
});

// 交易记录modal状态
const transactionModalVisible = ref(false);
const transactionData = ref<any[]>([]);
const transactionLoading = ref(false);
const transactionPagination = reactive({
  current: 1,
  pageSize: 10,
  total: 0,
});
const currentCard = ref<DefaultCard | null>(null);

// 获取账户状态对应的颜色
const getStatusColor = (status: string) => {
  // 去除可能的前后空格并转换为大写
  const normalizedStatus = status?.toString().trim().toUpperCase();
  return normalizedStatus === 'ACTIVE' ? 'green' : normalizedStatus === 'DISABLED' ? 'red' : 'gray';
};

// 格式化金额，解决浮点数精度问题
const formatBudget = (amount: number | string) => {
  // 确保输入是数字
  const num = typeof amount === 'string' ? parseFloat(amount) : amount;

  // 检查是否是有效数字
  if (isNaN(num) || num === null || num === undefined) {
    return '0';
  }

  // 格式化为最多2位小数，去除尾随零
  return num.toFixed(2).replace(/\.?0+$/, '');
};

// 精确的浮点数加法，避免精度问题
const preciseAdd = (a: number, b: number) => {
  // 使用toFixed避免浮点数精度问题，然后转换回数字
  return parseFloat((a + b).toFixed(2));
};

// 计算月度账单的余额 (spend_cap - balance)
const calculateMonthlyInvoicingBalance = (spendCap?: string, balance?: string): number => {
  if (!spendCap || !balance) return 0;

  try {
    const spendCapNum = parseFloat(spendCap);
    const balanceNum = parseFloat(balance);

    if (isNaN(spendCapNum) || isNaN(balanceNum)) return 0;

    return parseFloat((spendCapNum - balanceNum).toFixed(2));
  } catch (error) {
    console.error('计算月度账单余额时出错:', error);
    return 0;
  }
};

// 安全地解析预算值
const parseBudget = (budget: string | number | null) => {
  if (budget === null || budget === undefined) {
    return 0;
  }

  const num = typeof budget === 'string' ? parseFloat(budget) : budget;
  const result = isNaN(num) ? 0 : num;

  return result;
};

// 检查是否是有效的卡片（funding后4位与default_card的number后4位匹配）
const isValidCard = (funding: string, defaultCard?: DefaultCard) => {
  if (!funding || !defaultCard) {
    return false;
  }

  // 提取funding的后4位
  const fundingMatch = funding.match(/\*(\d{4})/);
  if (!fundingMatch) {
    return false;
  }

  const fundingLast4 = fundingMatch[1];
  const cardLast4 = defaultCard.number.slice(-4);

  return fundingLast4 === cardLast4;
};

// 折叠面板状态管理
const activeKeys = ref<string[]>([]);

const visible = computed({
  get: () => props.visible,
  set: value => emit('update:visible', value),
});

// 解析funding信息
const parseFunding = (funding: string) => {
  if (!funding || funding.trim() === '') {
    return { type: 'no_funding', last4: '' };
  }

  const mastercardMatch = funding.match(/Mastercard \*(\d{4})/);
  if (mastercardMatch) {
    return { type: 'Mastercard', last4: mastercardMatch[1] };
  }

  const visaMatch = funding.match(/VISA \*(\d{4})/);
  if (visaMatch) {
    return { type: 'VISA', last4: visaMatch[1] };
  }

  if (funding.includes('monthly invoicing') || funding.includes('penagihan bulanan') || funding.includes('月度结算')) {
    return { type: 'monthly invoicing', last4: '' };
  }

  return { type: funding, last4: '' };
};

// 分组支付信息存储
const groupedPayments = ref<PaymentGroup[]>([]);

// 构建分组支付信息
const buildGroupedPayments = () => {
  const groups: { [key: string]: PaymentGroup } = {};

  props.selectedData.forEach(item => {
    // 只处理有预算的项目
    if (!item.daily_budget) return;

    const fundingInfo = parseFunding(item.funding);

    // 对于启用topup的绑卡账户，使用特殊的key和显示逻辑
    let paymentKey: string;
    let title: string;
    let isTopupGroup = false;

    if (item.is_topup && (fundingInfo.type === 'Mastercard' || fundingInfo.type === 'VISA' || fundingInfo.type === 'monthly invoicing')) {
      // Topup账户使用广告账户ID作为key，确保每个账户单独显示
      paymentKey = `topup_${item.ad_account_id}`;
      title = `${item.ad_account_name} (${item.ad_account_id})`;
      isTopupGroup = true;
    } else {
      // 普通账户使用原有逻辑
      paymentKey = `${fundingInfo.type}_${fundingInfo.last4}`;
      if (fundingInfo.type === 'monthly invoicing') {
        title = t('Monthly Invoicing');
      } else if (fundingInfo.type === 'no_funding') {
        title = t('No Funding');
      } else {
        title = `${fundingInfo.type} *${fundingInfo.last4}`;
      }
    }

    if (!groups[paymentKey]) {
      groups[paymentKey] = reactive({
        title,
        paymentType: fundingInfo.type,
        last4: fundingInfo.last4,
        funding: item.funding,
        totalBudget: 0,
        defaultCard: item.default_card,
        balanceInput: parseFloat(item.default_card?.balance || '0'),
        limitInput: parseFloat(item.default_card?.single_transaction_limit || '0'),
        refreshing: false,
        settingBalance: false,
        settingLimit: false,
        freezing: false,
        adAccountGroups: [],
        // Topup相关字段
        isTopupGroup,
        topupInput: 0,
        settingTopup: false,
        syncing: false,
        spendCap: item.spend_cap,
        totalSpent: item.total_spent,
        currency: item.currency,
      });
    } else {
      // 如果组已存在，但当前item有default_card而组没有，则更新
      if (!groups[paymentKey].defaultCard && item.default_card) {
        groups[paymentKey].defaultCard = item.default_card;
        groups[paymentKey].balanceInput = parseFloat(item.default_card.balance || '0');
        groups[paymentKey].limitInput = parseFloat(
          item.default_card.single_transaction_limit || '0',
        );
      }
    }

    const budget = parseBudget(item.daily_budget);
    groups[paymentKey].totalBudget = preciseAdd(groups[paymentKey].totalBudget, budget);

    // 在当前支付方式组内查找或创建ad account分组
    let adAccountGroup = groups[paymentKey].adAccountGroups.find(
      group =>
        group.adAccountName === item.ad_account_name && group.adAccountId === item.ad_account_id,
    );

    if (!adAccountGroup) {
      adAccountGroup = {
        adAccountName: item.ad_account_name,
        adAccountId: item.ad_account_id,
        adAccountUlid: item.ad_account_ulid,
        accountStatus: item.account_status,
        totalBudget: 0,
        accounts: [],
      };

      // 对于月度账单，计算余额和充值金额
      if (fundingInfo.type === 'monthly invoicing') {
        const accountBalance = calculateMonthlyInvoicingBalance(item.spend_cap, item.balance);
        adAccountGroup.accountBalance = accountBalance;
      }

      groups[paymentKey].adAccountGroups.push(adAccountGroup);
    }

    adAccountGroup.totalBudget = preciseAdd(adAccountGroup.totalBudget, budget);

    // 根据tab类型决定显示什么信息
    let displayName = item.ad_account_name;
    let displayId = item.ad_account_id;

    if (props.tabType === '2' && item.campaign_name && item.campaign_id) {
      // Campaign tab - 显示Campaign信息
      displayName = item.campaign_name;
      displayId = item.campaign_id;
    } else if (props.tabType === '3' && item.adset_name && item.adset_id) {
      // Adset tab - 显示Adset信息
      displayName = item.adset_name;
      displayId = item.adset_id;
    }

    adAccountGroup.accounts.push({
      id: displayId,
      name: displayName,
      budget: item.daily_budget,
    });
  });

  // 对于月度账单，计算每个广告账户的充值金额
  Object.values(groups).forEach(group => {
    if (group.paymentType === 'monthly invoicing') {
      group.adAccountGroups.forEach(adAccountGroup => {
        if (adAccountGroup.accountBalance !== undefined) {
          // 充值金额 = 总预算 - 余额，如果结果为负数则设为0
          const rechargeAmount = Math.max(
            0,
            adAccountGroup.totalBudget - adAccountGroup.accountBalance,
          );
          adAccountGroup.rechargeAmount = parseFloat(rechargeAmount.toFixed(2));
        }
      });
    }
  });

  groupedPayments.value = Object.values(groups);
};

// 检查是否有有效的卡片
const hasValidCards = computed(() => {
  return groupedPayments.value.some(
    group => group.defaultCard && isValidCard(group.funding, group.defaultCard),
  );
});

// 计算总记录数
const totalRecords = computed(() => {
  let count = 0;
  groupedPayments.value.forEach(group => {
    group.adAccountGroups.forEach(adAccountGroup => {
      count += adAccountGroup.accounts.length;
    });
  });
  return count;
});

// 单个卡片操作
const handleRefreshCard = async (group: PaymentGroup) => {
  if (!group.defaultCard) return;

  group.refreshing = true;
  try {
    const response: any = await syncMultipleCard({
      ids: [group.defaultCard.id],
      sync: true,
    });

    // 响应拦截器已经返回了response.data，直接使用
    if (response.results?.[0]?.success) {
      const updatedCard = response.results[0].card;
      // 更新卡片信息
      group.defaultCard = {
        ...group.defaultCard,
        balance: updatedCard.balance,
        single_transaction_limit: updatedCard.limits,
        status: updatedCard.status,
        // 保持原有的latest_transaction或更新（如果API返回了新的）
        latest_transaction: updatedCard.latest_transaction || group.defaultCard.latest_transaction,
      };
      // 更新输入框的值
      group.balanceInput = parseFloat(updatedCard.balance || '0');
      group.limitInput = parseFloat(updatedCard.limits || '0');

      message.success(`${group.title} ${t('Card refreshed successfully')}`);
    } else {
      message.error(`${group.title} ${t('Failed to refresh card')}`);
    }
  } catch (error) {
    console.error('Error refreshing card:', error);
    message.error(`${group.title} ${t('Failed to refresh card')}`);
  } finally {
    group.refreshing = false;
  }
};

const handleSetBalance = async (group: PaymentGroup) => {
  if (!group.defaultCard) return;

  group.settingBalance = true;
  try {
    const response: any = await setCardBalance({
      ids: [group.defaultCard.id],
      balance: group.balanceInput,
      sync: true,
    });

    // 响应拦截器已经返回了response.data，直接使用
    if (response.results?.[0]?.success) {
      const updatedCard = response.results[0].card;
      // 更新余额信息
      group.defaultCard = {
        ...group.defaultCard,
        balance: updatedCard.balance,
        // 同时更新其他可能返回的字段
        single_transaction_limit: updatedCard.limits || group.defaultCard.single_transaction_limit,
        status: updatedCard.status || group.defaultCard.status,
        latest_transaction: updatedCard.latest_transaction || group.defaultCard.latest_transaction,
      };
      group.balanceInput = parseFloat(updatedCard.balance || '0');

      message.success(`${group.title} ${t('Balance updated successfully')}`);
    } else {
      message.error(`${group.title} ${t('Failed to update balance')}`);
    }
  } catch (error) {
    console.error('Error updating balance:', error);
    message.error(`${group.title} ${t('Failed to update balance')}`);
  } finally {
    group.settingBalance = false;
  }
};

const handleSetSingleLimit = async (group: PaymentGroup) => {
  if (!group.defaultCard) return;

  group.settingLimit = true;
  try {
    const response: any = await setCardSingleLimit({
      ids: [group.defaultCard.id],
      limits: group.limitInput,
      sync: true,
    });

    // 响应拦截器已经返回了response.data，直接使用
    if (response.results?.[0]?.success) {
      const updatedCard = response.results[0].card;

      // 更新单次限额信息
      group.defaultCard = {
        ...group.defaultCard,
        single_transaction_limit: updatedCard.limits,
        // 同时更新其他可能返回的字段
        balance: updatedCard.balance || group.defaultCard.balance,
        status: updatedCard.status || group.defaultCard.status,
        latest_transaction: updatedCard.latest_transaction || group.defaultCard.latest_transaction,
      };

      // 更新输入框显示值
      group.limitInput = parseFloat(updatedCard.limits || '0');

      message.success(`${group.title} ${t('Single limit updated successfully')}`);
    } else {
      message.error(`${group.title} ${t('Failed to update single limit')}`);
    }
  } catch (error) {
    console.error('Error updating single limit:', error);
    message.error(`${group.title} ${t('Failed to update single limit')}`);
  } finally {
    group.settingLimit = false;
  }
};

// 显示交易记录modal
const handleShowTransactions = (card: DefaultCard) => {
  currentCard.value = card;
  transactionData.value = []; // Clear previous data
  transactionLoading.value = true;
  transactionModalVisible.value = true;
  transactionPagination.current = 1;
  fetchTransactions();
};

// 获取交易记录
const fetchTransactions = async () => {
  if (!currentCard.value) return;

  transactionLoading.value = true;
  try {
    const params = {
      card_number: currentCard.value.number,
      pageNo: transactionPagination.current,
      pageSize: transactionPagination.pageSize,
    };

    const response: any = await queryCardTransactionListAPI(params);

    // 直接使用响应数据，因为响应拦截器已经返回了response.data
    transactionData.value = response.data || [];
    transactionPagination.total = response.totalCount || 0;
  } catch (error) {
    console.error('Error fetching transactions:', error);
    message.error(t('Failed to fetch transactions'));
  } finally {
    transactionLoading.value = false;
  }
};

// 处理交易记录表格分页变化
const handleTransactionTableChange = (pagination: any) => {
  transactionPagination.current = pagination.current;
  transactionPagination.pageSize = pagination.pageSize;
  fetchTransactions();
};

// 处理Topup充值
const handleSetTopup = async (group: PaymentGroup) => {
  if (!group.topupInput || group.topupInput <= 0) {
    message.error(t('Please enter a valid topup amount'));
    return;
  }

  if (!group.spendCap) {
    message.error(t('Spend cap not available'));
    return;
  }

  const adAccountUlid = group.adAccountGroups[0]?.adAccountUlid;
  if (!adAccountUlid) {
    message.error(t('Ad account ULID not found'));
    return;
  }

  group.settingTopup = true;
  try {
    // 计算新的spend cap = 当前spend cap + topup金额
    const currentSpendCap = parseFloat(group.spendCap);
    const newSpendCap = currentSpendCap + group.topupInput;

    await setAccountSpendCap({
      ids: [adAccountUlid],
      cap_type: 'amount',
      cap_value: newSpendCap,
    });

    message.success(t('Topup request submitted successfully'));
    // 清空输入
    group.topupInput = 0;
  } catch (error) {
    console.error('Error setting topup:', error);
    message.error(t('Failed to submit topup request'));
  } finally {
    group.settingTopup = false;
  }
};

// 同步账户消费信息
const handleSyncAccountInfo = async (group: PaymentGroup) => {
  const adAccountId = group.adAccountGroups[0]?.adAccountId;
  if (!adAccountId) {
    message.error(t('Ad account ID not found'));
    return;
  }

  group.syncing = true;
  try {
    const response: any = await syncAccountSpendInfo({
      source_id: adAccountId,
    });

    if (response.success && response.data) {
      // 更新组数据
      group.spendCap = response.data.spend_cap?.toString();
      group.totalSpent = response.data.total_spent?.toString();
      group.currency = response.data.currency;

      message.success(t('Account information synced successfully'));
    } else {
      message.error(t('Failed to sync account information'));
    }
  } catch (error) {
    console.error('Error syncing account info:', error);
    message.error(t('Failed to sync account information'));
  } finally {
    group.syncing = false;
  }
};

// 计算余额 (Spend Cap - Total Spent)
const calculateBalance = (group: PaymentGroup): number => {
  if (!group.spendCap || !group.totalSpent) {
    return 0;
  }

  const spendCap = parseFloat(group.spendCap);
  const totalSpent = parseFloat(group.totalSpent);

  if (isNaN(spendCap) || isNaN(totalSpent)) {
    return 0;
  }

  return spendCap - totalSpent;
};

// 格式化余额显示
const formatBalance = (group: PaymentGroup): string => {
  const balance = calculateBalance(group);
  const currency = group.currency || 'USD';

  if (balance === 0 && (!group.spendCap || !group.totalSpent)) {
    return 'N/A';
  }

  return `${balance.toFixed(2)} ${currency}`;
};

// 交易记录表格列定义
const transactionColumns = [
  {
    title: t('Transaction Date'),
    dataIndex: 'transaction_date',
    width: 180,
    customRender: ({ text }: { text: string }) => {
      return text ? new Date(text).toLocaleString() : '-';
    },
  },
  {
    title: t('Amount'),
    dataIndex: 'transaction_amount',
    width: 100,
    customRender: ({ text }: { text: number }) => {
      return `$${text || 0}`;
    },
  },
  {
    title: t('Status'),
    dataIndex: 'status',
    width: 100,
    customRender: ({ text }: { text: string }) => {
      const statusMap: { [key: string]: { color: string; text: string } } = {
        approved: { color: 'green', text: 'Approved' },
        pending: { color: 'orange', text: 'Pending' },
        failed: { color: 'red', text: 'Failed' },
      };
      const status = statusMap[text?.toLowerCase()] || { color: 'default', text: text };
      return h('span', { style: { color: status.color } }, status.text);
    },
  },
  {
    title: t('Merchant'),
    dataIndex: 'merchant_name',
    ellipsis: true,
    customRender: ({ text }: { text: string }) => {
      return text || '-';
    },
  },
];

// 批量操作
const handleBatchRefreshCards = async () => {
  const validGroups = groupedPayments.value.filter(
    group => group.defaultCard && isValidCard(group.funding, group.defaultCard),
  );

  if (validGroups.length === 0) return;

  batchOperations.refreshing = true;

  try {
    for (let i = 0; i < validGroups.length; i++) {
      const group = validGroups[i];
      if (group.defaultCard) {
        await handleRefreshCard(group);
        // 除了最后一个，其他都需要等待1秒
        if (i < validGroups.length - 1) {
          await new Promise(resolve => setTimeout(resolve, 1000));
        }
      }
    }
    message.success(t('All cards refreshed successfully'));
  } catch (error) {
    console.error('Error in batch refresh:', error);
    message.error(t('Batch refresh failed'));
  } finally {
    batchOperations.refreshing = false;
  }
};

const handleBatchSetBalance = async () => {
  // 只处理余额输入值与当前余额不同的卡片
  const validGroups = groupedPayments.value.filter(
    group =>
      group.defaultCard &&
      isValidCard(group.funding, group.defaultCard) &&
      group.balanceInput !== parseFloat(group.defaultCard.balance),
  );

  if (validGroups.length === 0) {
    message.info(t('No cards need balance update'));
    return;
  }

  batchOperations.settingBalance = true;

  try {
    for (let i = 0; i < validGroups.length; i++) {
      const group = validGroups[i];
      if (group.defaultCard) {
        await handleSetBalance(group);
        // 除了最后一个，其他都需要等待1秒
        if (i < validGroups.length - 1) {
          await new Promise(resolve => setTimeout(resolve, 1000));
        }
      }
    }
    message.success(t('All balances updated successfully'));
  } catch (error) {
    console.error('Error in batch balance update:', error);
    message.error(t('Batch balance update failed'));
  } finally {
    batchOperations.settingBalance = false;
  }
};

const handleBatchSetSingleLimit = async () => {
  // 只处理单次限额输入值与当前限额不同的卡片
  const validGroups = groupedPayments.value.filter(
    group =>
      group.defaultCard &&
      isValidCard(group.funding, group.defaultCard) &&
      group.limitInput !== parseFloat(group.defaultCard.single_transaction_limit),
  );

  if (validGroups.length === 0) {
    message.info(t('No cards need single limit update'));
    return;
  }

  batchOperations.settingLimit = true;

  try {
    for (let i = 0; i < validGroups.length; i++) {
      const group = validGroups[i];
      if (group.defaultCard) {
        await handleSetSingleLimit(group);
        // 除了最后一个，其他都需要等待1秒
        if (i < validGroups.length - 1) {
          await new Promise(resolve => setTimeout(resolve, 1000));
        }
      }
    }
    message.success(t('All single limits updated successfully'));
  } catch (error) {
    console.error('Error in batch single limit update:', error);
    message.error(t('Batch single limit update failed'));
  } finally {
    batchOperations.settingLimit = false;
  }
};

// 批量冻结卡片
const handleBatchFreezeCards = async () => {
  const validGroups = groupedPayments.value.filter(
    group =>
      group.defaultCard &&
      isValidCard(group.funding, group.defaultCard) &&
      group.defaultCard.status === 'ACTIVE',
  );

  if (validGroups.length === 0) {
    message.info(t('No active cards to freeze'));
    return;
  }

  batchOperations.freezing = true;

  try {
    for (let i = 0; i < validGroups.length; i++) {
      const group = validGroups[i];
      if (group.defaultCard) {
        await freezeCard({
          ids: [group.defaultCard.id],
          sync: true,
        });
        // 更新状态
        group.defaultCard.status = 'FROZEN';
        // 除了最后一个，其他都需要等待1秒
        if (i < validGroups.length - 1) {
          await new Promise(resolve => setTimeout(resolve, 1000));
        }
      }
    }
    message.success(t('All cards frozen successfully'));
  } catch (error) {
    console.error('Error in batch freeze:', error);
    message.error(t('Batch freeze failed'));
  } finally {
    batchOperations.freezing = false;
  }
};

// 批量解冻卡片
const handleBatchUnfreezeCards = async () => {
  const validGroups = groupedPayments.value.filter(
    group =>
      group.defaultCard &&
      isValidCard(group.funding, group.defaultCard) &&
      group.defaultCard.status === 'FROZEN',
  );

  if (validGroups.length === 0) {
    message.info(t('No frozen cards to unfreeze'));
    return;
  }

  batchOperations.unfreezing = true;

  try {
    for (let i = 0; i < validGroups.length; i++) {
      const group = validGroups[i];
      if (group.defaultCard) {
        await unfreezeCard({
          ids: [group.defaultCard.id],
          sync: true,
        });
        // 更新状态
        group.defaultCard.status = 'ACTIVE';
        // 除了最后一个，其他都需要等待1秒
        if (i < validGroups.length - 1) {
          await new Promise(resolve => setTimeout(resolve, 1000));
        }
      }
    }
    message.success(t('All cards unfrozen successfully'));
  } catch (error) {
    console.error('Error in batch unfreeze:', error);
    message.error(t('Batch unfreeze failed'));
  } finally {
    batchOperations.unfreezing = false;
  }
};

// 处理卡片操作
const handleCardAction = (key: string, group: PaymentGroup) => {
  if (!group.defaultCard) return;

  if (key === 'refresh') {
    handleRefreshCard(group);
  } else if (key === 'transactions') {
    handleShowTransactions(group.defaultCard);
  }
};

// 处理开关状态变化
const handleSwitchStatus = async (group: PaymentGroup, checked: any) => {
  if (!group.defaultCard) return;

  const isChecked = Boolean(checked);
  group.freezing = true;
  try {
    if (isChecked) {
      // 激活卡片
      await unfreezeCard({
        ids: [group.defaultCard.id],
        sync: true,
      });
      group.defaultCard.status = 'ACTIVE';
      message.success(`${group.title} ${t('Card unfrozen successfully')}`);
    } else {
      // 冻结卡片
      await freezeCard({
        ids: [group.defaultCard.id],
        sync: true,
      });
      group.defaultCard.status = 'FROZEN';
      message.success(`${group.title} ${t('Card frozen successfully')}`);
    }
  } catch (error) {
    console.error('Error switching card status:', error);
    message.error(
      `${group.title} ${t(isChecked ? 'Failed to unfreeze card' : 'Failed to freeze card')}`,
    );
  } finally {
    group.freezing = false;
  }
};

// 复制单个广告账户的信息
const copyRechargeInfo = async (adAccountGroup: any) => {
  try {
    let text = '';
    // 如果需要充值，复制充值信息
    if (adAccountGroup.rechargeAmount !== undefined && adAccountGroup.rechargeAmount > 0) {
      text = `${adAccountGroup.adAccountName} - ${adAccountGroup.adAccountId} - ${formatBudget(
        adAccountGroup.rechargeAmount,
      )}`;
    } else {
      // 如果不需要充值，只复制账户名和ID
      text = `${adAccountGroup.adAccountName} - ${adAccountGroup.adAccountId}`;
    }
    await copy(text);
    message.success(t('Copied'));
  } catch (e) {
    console.error(e);
    message.error(t('Copy failed'));
  }
};

// 复制单个组中需要充值的支付信息
const copyGroupNeedRecharge = async (group: PaymentGroup) => {
  try {
    let text = '';
    if (group.paymentType === 'monthly invoicing') {
      group.adAccountGroups.forEach(adAccountGroup => {
        // 对于月度账单，只复制需要充值的账户
        if (adAccountGroup.rechargeAmount !== undefined && adAccountGroup.rechargeAmount > 0) {
          text += `${adAccountGroup.adAccountName} - ${adAccountGroup.adAccountId} - ${formatBudget(
            adAccountGroup.rechargeAmount,
          )}\n`;
        }
      });
    }

    if (text.trim() === '') {
      message.info(t('No accounts need recharge'));
      return;
    }

    await copy(text);
    message.success(t('Copied'));
  } catch (e) {
    console.error(e);
    message.error(t('Copy failed'));
  }
};

// 复制单个组的支付信息
const copyGroupPayment = async (group: PaymentGroup) => {
  try {
    let text = '';
    if (group.paymentType === 'monthly invoicing') {
      group.adAccountGroups.forEach(adAccountGroup => {
        // 对于月度账单，如果需要充值则复制充值信息，否则只复制账户名和ID
        if (adAccountGroup.rechargeAmount !== undefined && adAccountGroup.rechargeAmount > 0) {
          text += `${adAccountGroup.adAccountName} - ${adAccountGroup.adAccountId} - ${formatBudget(
            adAccountGroup.rechargeAmount,
          )}\n`;
        } else {
          text += `${adAccountGroup.adAccountName} - ${adAccountGroup.adAccountId}\n`;
        }
      });
    } else if (group.paymentType === 'no_funding') {
      group.adAccountGroups.forEach(adAccountGroup => {
        adAccountGroup.accounts.forEach(account => {
          text += `${account.name} (${account.id}): no funding: ${formatBudget(account.budget)}\n`;
        });
      });
    } else {
      text = `${group.paymentType} *${group.last4}: ${formatBudget(group.totalBudget)}\n`;
    }

    await copy(text);
    message.success(t('Copied'));
  } catch (e) {
    console.error(e);
    message.error(t('Copy failed'));
  }
};

// 展开所有折叠面板
const expandAll = () => {
  const allKeys: string[] = [];
  groupedPayments.value.forEach((group, index) => {
    group.adAccountGroups.forEach((_, adAccountIndex) => {
      allKeys.push(`group-${index}-${adAccountIndex}`);
    });
  });
  activeKeys.value = allKeys;
};

// 折叠所有面板
const collapseAll = () => {
  activeKeys.value = [];
};

// 监听数据变化，重新构建分组
watch(
  () => props.selectedData,
  () => {
    buildGroupedPayments();
  },
  { immediate: true, deep: true },
);
</script>

<style lang="less" scoped>
.payment-modal {
  .payment-content {
    padding: 16px 0;
  }

  .payment-header {
    margin-bottom: 24px;

    h3 {
      margin: 0;
      font-size: 16px;
      font-weight: 600;
      color: #262626;
    }
  }

  .batch-operations {
    margin-bottom: 24px;
    padding: 20px;
    background: linear-gradient(135deg, #f6f8fa 0%, #e9ecef 100%);
    border-radius: 12px;
    border: 1px solid #e1e8ed;
    box-shadow: 0 2px 8px rgba(0, 0, 0, 0.06);

    .batch-title {
      margin-bottom: 16px;

      span {
        font-size: 16px;
        font-weight: 600;
        color: #2c3e50;
        display: block;
      }

      small {
        color: #6c757d;
        font-size: 12px;
        margin-top: 4px;
        display: block;
      }
    }

    .batch-button {
      border-radius: 8px;
      height: 44px;
      font-weight: 500;
      transition: all 0.3s ease;
      box-shadow: 0 2px 4px rgba(0, 0, 0, 0.1);

      &:hover {
        transform: translateY(-2px);
        box-shadow: 0 4px 12px rgba(0, 0, 0, 0.15);
      }

      &.refresh-batch {
        background: linear-gradient(135deg, #1890ff, #40a9ff);
        border: none;
        color: white;

        &:hover {
          background: linear-gradient(135deg, #0050b3, #1890ff);
        }
      }

      &.balance-batch {
        background: linear-gradient(135deg, #52c41a, #73d13d);
        border: none;
        color: white;

        &:hover {
          background: linear-gradient(135deg, #389e0d, #52c41a);
        }
      }

      &.limit-batch {
        background: linear-gradient(135deg, #faad14, #ffc53d);
        border: none;
        color: white;

        &:hover {
          background: linear-gradient(135deg, #d48806, #faad14);
        }
      }
    }
  }

  // 交易记录Modal样式
  .transaction-modal {
    .transaction-modal-title {
      display: flex;
      align-items: center;
      gap: 12px;

      .title-icon {
        font-size: 20px;
        color: #1890ff;
      }

      .title-content {
        .title-text {
          font-size: 16px;
          font-weight: 600;
          color: #2c3e50;
          display: block;
        }

        .card-number {
          font-size: 12px;
          color: #6c757d;
          font-weight: normal;
        }
      }
    }

    :deep(.ant-table) {
      border-radius: 8px;
      overflow: hidden;
    }

    :deep(.ant-table-thead > tr > th) {
      background: linear-gradient(135deg, #f8f9fa, #e9ecef);
      font-weight: 600;
      color: #2c3e50;
    }

    :deep(.ant-table-tbody > tr:hover > td) {
      background-color: #f0f7ff !important;
    }
  }

  .payment-cards {
    display: flex;
    flex-direction: column;
    gap: 16px;
  }

  .payment-group {
    .payment-card {
      border: 1px solid #e8e8e8;
      border-radius: 12px;
      box-shadow: 0 2px 8px rgba(0, 0, 0, 0.06);
      transition: all 0.3s ease;
      overflow: hidden;

      &:hover {
        box-shadow: 0 4px 16px rgba(0, 0, 0, 0.1);
        transform: translateY(-2px);
      }

      &.no-funding {
        border-color: #ffccc7;
        background: linear-gradient(135deg, #fff2f0, #ffeaea);
      }

      // 为不同卡片类型添加不同的边框颜色
      &:first-child {
        border-left: 4px solid #1890ff;
      }

      &:nth-child(2) {
        border-left: 4px solid #52c41a;
      }

      &:nth-child(3) {
        border-left: 4px solid #faad14;
      }

      &:nth-child(4) {
        border-left: 4px solid #f5222d;
      }

      .card-title-wrapper {
        display: flex;
        align-items: center;
        justify-content: space-between;
        width: 100%;
        padding: 4px 0;

        .card-title {
          font-weight: 600;
          color: #262626;
          flex: 1;
        }

        .card-actions {
          flex-shrink: 0;
        }
      }

      .card-info {
        margin-bottom: 16px;
        padding: 12px;
        background: #fafafa;
        border-radius: 8px;
        border: 1px solid #e8e8e8;

        .input-group {
          display: flex;
          flex-direction: column;
          gap: 4px;

          label {
            font-weight: 500;
            color: #666;
            font-size: 12px;
          }

          .input-with-button {
            display: flex;
            align-items: center;
            gap: 4px;

            .mini-save-btn {
              width: 24px;
              height: 24px;
              padding: 0;
              min-width: 24px;
              border-radius: 4px;
              color: #52c41a;

              &:hover {
                background-color: #f6ffed;
                color: #389e0d;
              }
            }

            .mini-sync-btn {
              width: 24px;
              height: 24px;
              padding: 0;
              min-width: 24px;
              border-radius: 4px;
              color: #1890ff;

              &:hover {
                background-color: #e6f7ff;
                color: #0050b3;
              }
            }
          }
        }

        .info-item {
          display: flex;
          flex-direction: column;
          gap: 4px;

          label {
            font-weight: 500;
            color: #666;
            font-size: 12px;
          }

          .transaction-link {
            color: #1890ff;
            cursor: pointer;
            text-decoration: none;
            font-weight: 600;
            font-size: 13px;
            padding: 2px 4px;
            border-radius: 3px;
            transition: all 0.2s ease;
            display: inline-block;

            &:hover {
              background-color: #e6f7ff;
              color: #0050b3;
              text-decoration: none;
            }
          }

          .status-switch {
            min-width: 28px !important;
            width: 28px !important;
          }

          .negative-balance {
            color: #ff4d4f;
            font-weight: 600;
          }
        }
      }

      .card-extra {
        display: flex;
        align-items: center;
        gap: 12px;

        .budget-amount {
          font-size: 18px;
          font-weight: 600;
          color: #52c41a;

          .currency {
            font-size: 14px;
            font-weight: 400;
            color: #8c8c8c;
            margin-left: 4px;
          }
        }

        .copy-buttons {
          display: flex;
          align-items: center;
          gap: 8px;
        }
      }

      .account-details {
        .account-item {
          display: flex;
          align-items: center;
          padding: 8px 12px;
          border-bottom: 1px solid #f0f0f0;
          gap: 8px;

          &:last-child {
            border-bottom: none;
          }

          &.panel-header {
            padding: 0; /* panel header不需要额外的padding */
            border-bottom: none;
            font-weight: 600;

            .account-name {
              font-weight: 600;
            }
          }

          .account-name {
            font-size: 14px;
            font-weight: 500;
            color: #262626;
            flex: 1;
            min-width: 0; /* 允许flexbox项目收缩到其内容以下 */
            overflow: hidden;
            text-overflow: ellipsis;
            white-space: nowrap;
            margin-right: 8px;

            .account-balance {
              font-size: 12px;
              color: #52c41a;
              font-weight: 600;
              margin-left: 8px;
            }
          }

          .account-id {
            font-size: 12px;
            color: #8c8c8c;
            flex-shrink: 0;
            max-width: 150px;
            overflow: hidden;
            text-overflow: ellipsis;
            white-space: nowrap;
          }

          .account-status {
            flex-shrink: 0;
            margin: 0 8px;
          }

          .account-budget {
            font-size: 14px;
            font-weight: 600;
            color: #52c41a;
            flex-shrink: 0;
            text-align: right;
            min-width: 60px;

            .account-actions {
              display: inline-flex;
              align-items: center;
              gap: 4px;
              margin-left: 8px;

              .recharge-info {
                font-size: 12px;
                color: #fa8c16;
                font-weight: 600;
              }

              .copy-recharge-btn {
                padding: 0 4px;
                height: 20px;
                min-width: 20px;
                color: #1890ff;

                &:hover {
                  background-color: #e6f7ff;
                  color: #0050b3;
                }
              }
            }
          }
        }

        .campaign-item {
          display: flex;
          align-items: center;
          padding: 8px 12px;
          border-bottom: 1px solid #f0f0f0;
          gap: 8px;

          &:last-child {
            border-bottom: none;
          }

          .campaign-name {
            font-size: 14px;
            font-weight: 500;
            color: #262626;
            flex: 1;
            min-width: 0;
            overflow: hidden;
            text-overflow: ellipsis;
            white-space: nowrap;
            margin-right: 8px;
          }

          .campaign-details {
            display: flex;
            align-items: center;
            gap: 12px;
            flex-shrink: 0;

            .campaign-id {
              font-size: 12px;
              color: #8c8c8c;
              max-width: 150px;
              overflow: hidden;
              text-overflow: ellipsis;
              white-space: nowrap;
            }

            .campaign-budget {
              font-size: 14px;
              font-weight: 600;
              color: #52c41a;
              text-align: right;
              min-width: 60px;
            }
          }
        }
      }
    }
  }
}
</style>
