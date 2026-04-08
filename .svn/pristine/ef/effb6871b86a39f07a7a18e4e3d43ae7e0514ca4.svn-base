<template>
  <a-card>
    <a-form layout="horizontal">
      <a-row :gutter="[12, 0]" justify="end">
        <a-col :span="8">
          <a-form-item :label="t('Name')">
            <a-input v-model:value="formData.name" />
          </a-form-item>
        </a-col>
        <a-col :span="8">
          <a-form-item :label="t('Page Source ID')">
            <a-input v-model:value="formData.page_source_id" />
          </a-form-item>
        </a-col>
        <a-col>
          <a-button :loading="loading" type="primary" @click="onSearch">
            {{ t('pages.acc.search') }}
          </a-button>
          <column-orgnizer
            :columns="columns"
            @change:columns="data => (dynamicColumns = data)"
          ></column-orgnizer>
        </a-col>
      </a-row>
    </a-form>
  </a-card>
  <a-table
    :scroll="scroll"
    :data-source="tableData"
    :pagination="false"
    :loading="loading"
    :columns="dynamicColumns"
    row-key="id"
  >
    <template #bodyCell="{ column, record }">
      <template v-if="column.key === 'action'">
        <a @click="updateNote(record as any)">{{ t('Update Note') }}</a>
      </template>
    </template>
  </a-table>

  <a-row justify="end">
    <a-pagination
      :current="currentPage"
      :page-size="pageSize"
      :total="total"
      @change="onPageChange"
    />
  </a-row>

  <a-modal v-model:visible="open" :title="t('Update Notes')" @ok="saveNote">
    <a-form layout="horizontal">
      <a-form-item :label="t('Note')">
        <a-input v-model:value="noteForm.notes" />
      </a-form-item>
    </a-form>
  </a-modal>
</template>

<script lang="ts">
import { defineComponent, onMounted, ref, h, reactive } from 'vue';
import { Button, Table, Pagination, message } from 'ant-design-vue';
import { useI18n } from 'vue-i18n';
import type { TableColumn } from '@/typing';
import { getPageForms, updatePageFormNote } from '@/api/pages';
import ColumnOrgnizer from '@/components/column-orgnizer/column-orgnizer.vue';
import { useTableHeight } from '@/utils/hooks/useTableHeight';

interface PageForm {
  id: string;
  created_at: string;
  updated_at: string;
  deleted_at: string;
  notes: string;
  source_id: string;
  locale: string;
  name: string;
  status: string;
  created_time: string;
  thank_you_page: string;
  privacy_policy_url: string;
  follow_up_action_url: string;
  leads_count: string;
  page_source_id: string;
  page_name: string;
  legal_content: any[];
  questions: any[];
}

export default defineComponent({
  name: 'PageForms',
  components: {
    'a-button': Button,
    'a-table': Table,
    'a-table-column': Table.Column,
    'a-pagination': Pagination,
    ColumnOrgnizer,
  },
  setup() {
    const { t } = useI18n();
    const currentPage = ref<number>(1);
    const pageSize = ref<number>(10);
    const total = ref<number>(100); // 根据实际数据更新
    const loading = ref(false);
    const tableData = ref<PageForm[]>([]);
    const tokenModal = ref(null);
    const open = ref(false);
    const selectedForm = ref<PageForm>();
    const formData = reactive({ name: '', page_source_id: '' });
    const noteForm = reactive({ notes: '' });
    const dynamicColumns = ref<TableColumn[]>([]);
    const { scroll } = useTableHeight(410);

    const columns: TableColumn[] = [
      // { title: t('Created At'), dataIndex: 'created_at', key: 'name' },
      // { title: t('Updated At'), dataIndex: 'updated_at', key: 'age' },
      // { title: t('Deleted At'), dataIndex: 'deleted_at', key: 'address' },
      { title: t('Notes'), dataIndex: 'notes', key: 'bm_id' },
      { title: t('ID'), dataIndex: 'source_id', key: 'age' },
      { title: t('Locale'), dataIndex: 'locale', key: 'address' },
      { title: t('Name'), dataIndex: 'name', key: 'notes' },
      { title: t('Status'), key: 'status' },
      { title: t('Created Time'), key: 'created_time' },
      { title: t('Thank You Page'), key: 'thank_you_page' },
      { title: t('Privacy Policy URL'), key: 'privacy_policy_url' },
      { title: t('Follow Up Action URL'), key: 'follow_up_action_url' },
      { title: t('Leads Count'), key: 'leads_count' },
      { title: t('Page Source ID'), key: 'page_source_id' },
      { title: t('Page Name'), key: 'page_name' },
      { title: t('Action'), key: 'action' },
    ];

    onMounted(() => getForms());

    const onSearch = () => {
      getForms();
    };

    const onPageChange = (page: number) => {
      currentPage.value = page;
      // 加载新一页的数据
      getForms();
    };

    const updateNote = record => {
      open.value = true;
      selectedForm.value = record;
    };

    const saveNote = () => {
      if (!selectedForm?.value) {
        return;
      }
      open.value = false;
      loading.value = true;
      updatePageFormNote(selectedForm.value.id, noteForm.notes)
        .catch(() => message.error('Operation Failed'))
        .then(getForms);
    };

    const getForms = () => {
      loading.value = true;
      getPageForms({ pageSize: pageSize.value, pageNo: currentPage.value, ...formData })
        .then(({ data, pageNo, pageSize: size, totalCount }: any) => {
          tableData.value = data;
          currentPage.value = pageNo;
          pageSize.value = size;
          total.value = totalCount;
        })
        .finally(() => (loading.value = false));
    };

    return {
      scroll,
      currentPage,
      pageSize,
      total,
      tableData,
      formData,
      columns,
      loading,
      open,
      noteForm,
      tokenModal,
      selectedForm,
      dynamicColumns,
      updateNote,
      onSearch,
      onPageChange,
      getForms,
      saveNote,
      h,
      t,
    };
  },
});
</script>

<style scoped>
.button-group {
  margin-bottom: 20px;
}

.margin-right {
  margin-right: 8px;
}
</style>
