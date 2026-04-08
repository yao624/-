<template>
  <a-modal
    :open="visible"
    :title="t('使用模版')"
    :width="900"
    :mask-closable="false"
    :footer="null"
    @cancel="onCancel"
  >
    <div class="template-select-modal">
      <div class="filter-row">
        <a-input-search
          v-model:value="searchName"
          :placeholder="t('请输入模板名称')"
          allow-clear
          style="width: 240px"
          @search="loadTemplates"
        />
        <a-select
          v-model:value="filterAccountId"
          :placeholder="t('广告账户：请选择')"
          allow-clear
          style="width: 200px"
          @change="loadTemplates"
        >
          <a-select-option
            v-for="acc in adAccounts"
            :key="acc.id ?? acc.source_id"
            :value="acc.id ?? acc.source_id"
          >
            {{ acc.name }}
          </a-select-option>
        </a-select>
      </div>
      <a-table
        :columns="columns"
        :data-source="dataSource"
        :row-key="(r) => r.id"
        :loading="loading"
        :pagination="pagination"
        :row-selection="{
          type: 'radio',
          selectedRowKeys: selectedRowKeys,
          onChange: onSelectChange,
        }"
        @change="onTableChange"
      >
        <template #bodyCell="{ column, record, index }">
          <template v-if="column.dataIndex === 'index'">
            {{ (pagination.current - 1) * pagination.pageSize + index + 1 }}
          </template>
          <template v-else-if="column.dataIndex === 'description'">
            {{ record.notes || record.description || '-' }}
          </template>
          <template v-else-if="column.dataIndex === 'related_account'">
            {{ record.ad_account_name ?? record.related_account ?? '-' }}
          </template>
          <template v-else-if="column.dataIndex === 'created_at'">
            {{ formatTime(record.created_at) }}
          </template>
          <template v-else-if="column.dataIndex === 'creator'">
            {{ record.user?.name ?? record.creator ?? '-' }}
          </template>
        </template>
      </a-table>
      <div class="modal-footer">
        <a-button @click="onCancel">{{ t('取消') }}</a-button>
        <a-button type="primary" :disabled="!selectedTemplate" @click="onConfirm">
          {{ t('确定') }}
        </a-button>
      </div>
    </div>
  </a-modal>
</template>

<script lang="ts" setup>
import { ref, watch } from 'vue';
import { useI18n } from 'vue-i18n';
import { queryMetaAdCreationTemplatesApi } from '@/api/meta_ad_creation_templates';
import { queryFB_AD_AccountsApi } from '@/api/fb_ad_accounts';

interface TemplateRecord {
  id: string | number;
  name?: string;
  notes?: string;
  description?: string;
  ad_account_name?: string;
  related_account?: string;
  created_at?: string;
  user?: { name?: string };
  creator?: string;
  [key: string]: any;
}

const props = defineProps<{
  visible: boolean;
}>();

const emit = defineEmits<{
  (e: 'cancel'): void;
  (e: 'confirm', template: TemplateRecord): void;
}>();

const { t } = useI18n();

const searchName = ref('');
const filterAccountId = ref<string | undefined>(undefined);
const adAccounts = ref<any[]>([]);
const dataSource = ref<TemplateRecord[]>([]);
const loading = ref(false);
const selectedRowKeys = ref<(string | number)[]>([]);
const selectedTemplate = ref<TemplateRecord | null>(null);

const pagination = ref({
  current: 1,
  pageSize: 20,
  total: 0,
  showSizeChanger: true,
  pageSizeOptions: ['10', '20', '50'],
  showTotal: (total: number) => t('共 {{total}} 条', { total }),
});

const columns = [
  { title: t('序号'), dataIndex: 'index', key: 'index', width: 70 },
  { title: t('名称'), dataIndex: 'name', key: 'name' },
  { title: t('描述'), dataIndex: 'description', key: 'description' },
  { title: t('关联账户'), dataIndex: 'related_account', key: 'related_account' },
  { title: t('创建时间'), dataIndex: 'created_at', key: 'created_at', width: 180 },
  { title: t('创建人'), dataIndex: 'creator', key: 'creator' },
];

function formatTime(v: string | undefined) {
  if (!v) return '-';
  return v;
}

function onSelectChange(keys: (string | number)[], rows: TemplateRecord[]) {
  selectedRowKeys.value = keys;
  selectedTemplate.value = rows[0] ?? null;
}

function onTableChange(pag: any) {
  pagination.value.current = pag.current;
  pagination.value.pageSize = pag.pageSize;
  loadTemplates();
}

async function loadAdAccounts() {
  try {
    const res = await queryFB_AD_AccountsApi({ 'with-campaign': false, is_archived: false });
    const list = res?.data ?? [];
    adAccounts.value = Array.isArray(list) ? list : [];
  } catch {
    adAccounts.value = [];
  }
}

async function loadTemplates() {
  loading.value = true;
  try {
    const res = await queryMetaAdCreationTemplatesApi({
      pageNo: pagination.value.current,
      pageSize: pagination.value.pageSize,
      name: searchName.value || undefined,
      fb_ad_account_id: filterAccountId.value,
    } as any);
    const data = (res as any)?.data ?? [];
    const total = data.length;
    dataSource.value = Array.isArray(data) ? data : [];
    pagination.value.total = total;
  } catch {
    dataSource.value = [];
    pagination.value.total = 0;
  } finally {
    loading.value = false;
  }
}

function onCancel() {
  emit('cancel');
}

function onConfirm() {
  if (selectedTemplate.value) {
    emit('confirm', selectedTemplate.value);
  }
}

watch(
  () => props.visible,
  (v) => {
    if (v) {
      selectedRowKeys.value = [];
      selectedTemplate.value = null;
      searchName.value = '';
      filterAccountId.value = undefined;
      pagination.value.current = 1;
      loadAdAccounts();
      loadTemplates();
    }
  },
);
</script>

<style lang="less" scoped>
.template-select-modal {
  .filter-row {
    display: flex;
    gap: 12px;
    margin-bottom: 16px;
  }
  .modal-footer {
    display: flex;
    justify-content: flex-end;
    gap: 8px;
    margin-top: 16px;
    padding-top: 16px;
    border-top: 1px solid #f0f0f0;
  }
}
</style>
