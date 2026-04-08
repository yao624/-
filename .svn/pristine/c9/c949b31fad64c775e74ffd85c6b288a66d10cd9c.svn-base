<template>
  <a-modal
    :visible="visible"
    :title="t('Manage Cards')"
    :width="800"
    @ok="handleOk"
    @cancel="handleCancel"
    :confirm-loading="loading"
  >
    <div class="card-modal-content">
      <div class="card-header">
        <a-row justify="space-between" align="middle">
          <a-col>
            <h3>{{ adAccount?.name }} ({{ adAccount?.source_id }})</h3>
          </a-col>
          <a-col>
            <a-button type="primary" @click="showAddCardModal">
              {{ t('Add Card') }}
            </a-button>
          </a-col>
        </a-row>
      </div>

      <a-table
        :columns="columns"
        :data-source="cards"
        :loading="loading"
        :pagination="false"
        row-key="id"
        size="small"
        :row-selection="{ selectedRowKeys: selectedCardIds, onChange: onCardSelectionChange }"
      >
        <template #bodyCell="{ column, record }">
          <template v-if="column.dataIndex === 'name'">
            <div class="card-name-column">
              <div class="card-name-row">
                <a-button
                  type="text"
                  size="small"
                  class="copy-btn"
                  @click="() => copyText(record.name)"
                >
                  <copy-outlined />
                </a-button>
                <span class="card-name">{{ record.name }}</span>
              </div>
              <div class="card-number-row">
                <a-button
                  type="text"
                  size="small"
                  class="copy-btn"
                  @click="() => copyText(record.number)"
                >
                  <copy-outlined />
                </a-button>
                <span class="card-number">{{ record.number }}</span>
              </div>
            </div>
          </template>
          <template v-if="column.dataIndex === 'is_default'">
            <a-radio
              :checked="record.is_default"
              @change="() => handleSetDefault(record)"
            />
          </template>
          <template v-if="column.dataIndex === 'status'">
            <a-badge
              :color="getStatusColor(record.status)"
              :text="record.status"
            />
          </template>
          <template v-if="column.dataIndex === 'balance'">
            <span>{{ formatCurrency(record.balance) }}</span>
          </template>
          <template v-if="column.dataIndex === 'single_transaction_limit'">
            <span>{{ formatCurrency(record.single_transaction_limit) }}</span>
          </template>
          <template v-if="column.dataIndex === 'action'">
            <a-button
              type="text"
              danger
              size="small"
              @click="() => handleRemoveCard(record)"
            >
              {{ t('Remove') }}
            </a-button>
          </template>
        </template>
      </a-table>

      <div class="card-actions" v-if="selectedCardIds.length > 0">
        <a-button
          type="primary"
          danger
          @click="handleBatchRemove"
          :loading="batchRemoveLoading"
        >
          {{ t('Remove Selected') }} ({{ selectedCardIds.length }})
        </a-button>
      </div>
    </div>

    <!-- Add Card Modal -->
    <a-modal
      v-model:visible="addCardVisible"
      :title="t('Add Card')"
      :width="400"
      @ok="handleAddCard"
      @cancel="addCardVisible = false"
      :confirm-loading="addCardLoading"
    >
      <a-form :model="addCardForm" layout="vertical">
        <a-form-item :label="t('Search by Card Number')">
          <a-input
            v-model:value="addCardForm.cardNumber"
            :placeholder="t('Enter card number')"
            @input="(e) => handleSearchCard(e.target.value)"
          />
        </a-form-item>
        <div v-if="addCardForm.cardNumber && (searchResults.length > 0 || showNoResults)" class="search-results">
          <div class="search-results-header">
            <span>{{ t('Search Results') }}</span>
          </div>
          <a-list size="small" v-if="searchResults.length > 0">
            <a-list-item
              v-for="card in searchResults"
              :key="card.id"
              class="search-result-item"
              @click="() => selectSearchResult(card)"
            >
              <div class="card-info">
                <div class="card-details">
                  <div class="card-number">{{ card.number }}</div>
                  <div class="card-meta">
                    <span class="card-status">
                      <a-badge :color="getStatusColor(card.status)" :text="card.status" />
                    </span>
                    <span class="card-balance">{{ card.balance || '0' }} {{ card.currency || '' }}</span>
                  </div>
                </div>
              </div>
            </a-list-item>
          </a-list>
          <div v-else-if="showNoResults" class="no-results">
            <a-empty
              :description="t('No cards found matching the search criteria')"
              :image="null"
            >
              <template #image>
                <search-outlined style="font-size: 32px; color: #d9d9d9;" />
              </template>
            </a-empty>
          </div>
        </div>
        <div v-if="selectedCards.length > 0" class="selected-cards">
          <div class="selected-cards-header">
            <span>{{ t('Selected Cards') }}</span>
          </div>
          <a-list size="small">
            <a-list-item
              v-for="card in selectedCards"
              :key="card.id"
              class="selected-card-item"
            >
              <div class="card-info">
                <div class="card-details">
                  <div class="card-number">{{ card.number }}</div>
                  <div class="card-meta">
                    <span class="card-status">
                      <a-badge :color="getStatusColor(card.status)" :text="card.status" />
                    </span>
                    <span class="card-balance">{{ card.balance || '0' }} {{ card.currency || '' }}</span>
                  </div>
                </div>
                <a-button
                  type="text"
                  size="small"
                  @click="() => removeSelectedCard(card)"
                >
                  {{ t('Remove') }}
                </a-button>
              </div>
            </a-list-item>
          </a-list>
        </div>
      </a-form>
    </a-modal>
  </a-modal>
</template>

<script lang="ts" setup>
import { ref, reactive, computed, watch } from 'vue';
import { useI18n } from 'vue-i18n';
import { message } from 'ant-design-vue';
import { SearchOutlined, CopyOutlined } from '@ant-design/icons-vue';
import useClipboard from 'vue-clipboard3';
import {
  attachCardsToFbAdAccount,
  detachCardsFromFbAdAccount,
  setDefaultCardForFbAdAccount,
  searchCardsByNumber,
} from '@/api/virtual_cards';
import debounce from '@/utils/debonce';

interface Card {
  id: string;
  name: string;
  number: string;
  status: string;
  balance: string;
  single_transaction_limit: string;
  currency: string;
  is_default: boolean;
}

interface AdAccount {
  id: string;
  name: string;
  source_id: string;
}

interface Props {
  visible: boolean;
  adAccount: AdAccount | null;
  cards: Card[];
}

const props = defineProps<Props>();
const emit = defineEmits<{
  (e: 'update:visible', value: boolean): void;
  (e: 'success'): void;
}>();

const { t } = useI18n();
const loading = ref(false);
const batchRemoveLoading = ref(false);
const selectedCardIds = ref<string[]>([]);
const addCardVisible = ref(false);
const addCardLoading = ref(false);
const searchResults = ref<Card[]>([]);
const selectedCards = ref<Card[]>([]);
const showNoResults = ref(false);

const addCardForm = reactive({
  cardNumber: '',
});

const columns = computed(() => [
  {
    title: t('Card Name'),
    dataIndex: 'name',
    key: 'name',
  },
  {
    title: t('Status'),
    dataIndex: 'status',
    key: 'status',
    width: 100,
  },
  {
    title: t('Balance'),
    dataIndex: 'balance',
    key: 'balance',
    width: 120,
  },
  {
    title: t('Single Limit'),
    dataIndex: 'single_transaction_limit',
    key: 'single_transaction_limit',
    width: 120,
  },
  {
    title: t('Default'),
    dataIndex: 'is_default',
    key: 'is_default',
    width: 80,
  },
  {
    title: t('Action'),
    dataIndex: 'action',
    key: 'action',
    width: 80,
  },
]);

const onCardSelectionChange = (selectedRowKeys: string[]) => {
  selectedCardIds.value = selectedRowKeys;
};

const handleSetDefault = async (card: Card) => {
  if (!props.adAccount) return;

  try {
    loading.value = true;
    await setDefaultCardForFbAdAccount({
      fb_ad_account_id: props.adAccount.id,
      card_id: card.id,
    });
    message.success(t('Default card set successfully'));
    emit('success');
  } catch (error) {
    message.error(t('Failed to set default card'));
  } finally {
    loading.value = false;
  }
};

const handleRemoveCard = async (card: Card) => {
  if (!props.adAccount) return;

  try {
    loading.value = true;
    await detachCardsFromFbAdAccount({
      fb_ad_account_id: props.adAccount.id,
      card_ids: [card.id],
    });
    message.success(t('Card removed successfully'));
    emit('success');
  } catch (error) {
    message.error(t('Failed to remove card'));
  } finally {
    loading.value = false;
  }
};

const handleBatchRemove = async () => {
  if (!props.adAccount || selectedCardIds.value.length === 0) return;

  try {
    batchRemoveLoading.value = true;
    await detachCardsFromFbAdAccount({
      fb_ad_account_id: props.adAccount.id,
      card_ids: selectedCardIds.value,
    });
    message.success(t('Cards removed successfully'));
    selectedCardIds.value = [];
    emit('success');
  } catch (error) {
    message.error(t('Failed to remove cards'));
  } finally {
    batchRemoveLoading.value = false;
  }
};

const handleSearchCard = debounce(async (value: string) => {
  if (!value.trim()) {
    searchResults.value = [];
    showNoResults.value = false;
    return;
  }

  try {
    const response = await searchCardsByNumber({ number: value });
    const cards = response.data || [];
    searchResults.value = cards;
    showNoResults.value = cards.length === 0;
  } catch (error) {
    console.error('Failed to search cards:', error);
    searchResults.value = [];
    showNoResults.value = true;
  }
}, 300);

const selectSearchResult = (card: Card) => {
  const isAlreadySelected = selectedCards.value.some(c => c.id === card.id);
  const isAlreadyAttached = props.cards.some(c => c.id === card.id);

  if (isAlreadyAttached) {
    message.warning(t('Card is already attached to this account'));
    return;
  }

  if (!isAlreadySelected) {
    selectedCards.value.push(card);
  }
};

const removeSelectedCard = (card: Card) => {
  const index = selectedCards.value.findIndex(c => c.id === card.id);
  if (index > -1) {
    selectedCards.value.splice(index, 1);
  }
};

const handleAddCard = async () => {
  if (!props.adAccount || selectedCards.value.length === 0) {
    message.warning(t('Please select at least one card'));
    return;
  }

  try {
    addCardLoading.value = true;
    await attachCardsToFbAdAccount({
      fb_ad_account_id: props.adAccount.id,
      card_ids: selectedCards.value.map(c => c.id),
    });
    message.success(t('Cards added successfully'));
    addCardVisible.value = false;
    selectedCards.value = [];
    addCardForm.cardNumber = '';
    searchResults.value = [];
    emit('success');
  } catch (error) {
    message.error(t('Failed to add cards'));
  } finally {
    addCardLoading.value = false;
  }
};

const showAddCardModal = () => {
  addCardVisible.value = true;
};

const handleOk = () => {
  handleCancel();
};

const handleCancel = () => {
  emit('update:visible', false);
  selectedCardIds.value = [];
};

const getStatusColor = (status: string) => {
  switch (status) {
    case 'ACTIVE': return 'green';
    case 'INACTIVE': return 'red';
    case 'FROZEN': return 'orange';
    default: return 'gray';
  }
};

const copyText = async (text: string) => {
  try {
    const { toClipboard } = useClipboard();
    await toClipboard(text);
    message.success(t('Copied'));
  } catch (error) {
    message.error(t('Copy failed'));
  }
};

const formatCurrency = (value: string) => {
  return `${value} USD`;
};

// Watch for visible changes to reset state
watch(() => props.visible, (newValue) => {
  if (!newValue) {
    selectedCardIds.value = [];
    addCardVisible.value = false;
    selectedCards.value = [];
    addCardForm.cardNumber = '';
    searchResults.value = [];
    showNoResults.value = false;
  }
});
</script>

<style scoped>
.card-modal-content {
  min-height: 400px;
}

.card-header {
  margin-bottom: 16px;
}

.card-actions {
  margin-top: 16px;
  text-align: right;
}

.search-results, .selected-cards {
  margin-top: 16px;
}

.search-results-header, .selected-cards-header {
  font-weight: 500;
  margin-bottom: 8px;
}

.search-result-item {
  cursor: pointer;
  padding: 8px;
  border-radius: 4px;
  transition: background-color 0.2s;
}

.search-result-item:hover {
  background-color: #f5f5f5;
}

.selected-card-item {
  padding: 8px;
  border-radius: 4px;
  background-color: #f9f9f9;
}

.card-info {
  display: flex;
  justify-content: space-between;
  align-items: center;
  width: 100%;
}

.card-details {
  flex-grow: 1;
}

.card-number {
  font-weight: 500;
  margin-bottom: 4px;
}

.card-meta {
  display: flex;
  gap: 12px;
  align-items: center;
}

.card-status {
  flex-shrink: 0;
}

.card-balance {
  color: #666;
  font-size: 12px;
}

.no-results {
  padding: 20px;
  text-align: center;
  background-color: #fafafa;
  border-radius: 6px;
  border: 1px dashed #d9d9d9;
}

.card-name-column {
  display: flex;
  flex-direction: column;
  gap: 4px;
}

.card-name-row,
.card-number-row {
  display: flex;
  align-items: center;
  gap: 8px;
}

.card-name {
  font-weight: 500;
  color: #262626;
  font-size: 14px;
}

.card-number {
  color: #666;
  font-size: 12px;
  font-family: monospace;
}

.copy-btn {
  display: flex;
  align-items: center;
  justify-content: center;
  width: 20px;
  height: 20px;
  border-radius: 4px;
  transition: all 0.2s ease;
  color: #1677ff;
  flex-shrink: 0;
}

.copy-btn:hover {
  background-color: #f0f5ff;
  transform: scale(1.1);
}

.copy-btn .anticon {
  font-size: 12px;
}
</style>