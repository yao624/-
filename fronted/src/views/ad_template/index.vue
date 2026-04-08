<template>
  <page-container :showPageHeader="false">
    <div class="ad-template-page">
      <div class="content-card">
        <!-- 筛选区域 -->
        <div class="filter-area">
          <filter-section
            ref="filterSectionRef"
            @search="handleSearch"
          />
        </div>

        <!-- 批量操作栏 -->
        <div class="batch-action-bar">
          <a-dropdown :disabled="!hasSelected">
            <a-button :disabled="!hasSelected">
              <span>{{ t('pages.adTemplate.actions.batchOperation') }}</span>
              <span v-if="hasSelected" class="selected-count">({{ selectedRowKeys.length }})</span>
              <DownOutlined />
            </a-button>
            <template #overlay>
              <a-menu @click="handleBatchMenuClick">
                <a-menu-item key="share">
                  <ShareAltOutlined />
                  {{ t('pages.adTemplate.actions.batchShare') }}
                </a-menu-item>
                <a-menu-item key="delete">
                  <DeleteOutlined />
                  {{ t('pages.adTemplate.actions.batchDelete') }}
                </a-menu-item>
              </a-menu>
            </template>
          </a-dropdown>
        </div>

        <!-- 表格区域 -->
        <div class="table-area">
          <template-table
            :data-source="templateData"
            :loading="loading"
            :pagination="pagination"
            :selected-row-keys="selectedRowKeys"
            @update:selected-row-keys="handleSelectionChange"
            @page-change="handlePageChange"
            @share="handleShare"
            @delete="handleDelete"
            @edit="handleEdit"
          />
        </div>
      </div>
    </div>

    <!-- 分享弹窗 -->
    <a-modal
      v-model:open="shareModalVisible"
      :title="shareModalTitle"
      width="500px"
      @ok="handleShareModalOk"
      @cancel="shareModalVisible = false"
    >
      <a-form :label-col="{ span: 6 }" :wrapper-col="{ span: 16 }">
        <a-form-item :label="t('pages.adTemplate.form.shareWith')">
          <a-select
            v-model:value="shareUsers"
            mode="multiple"
            :placeholder="t('pages.adTemplate.form.selectUsers')"
            style="width: 100%"
            :options="userOptions"
            :field-names="{ label: 'label', value: 'value' }"
            show-search
            :filter-option="filterUserOption"
          />
        </a-form-item>
      </a-form>
    </a-modal>

    <!-- 编辑弹窗 -->
    <a-modal
      v-model:open="editModalVisible"
      :title="t('pages.adTemplate.actions.edit')"
      width="500px"
      @ok="handleEditModalOk"
      @cancel="editModalVisible = false"
    >
      <a-form :label-col="{ span: 6 }" :wrapper-col="{ span: 16 }">
        <a-form-item :label="t('pages.adTemplate.form.name')">
          <a-input
            v-model:value="editForm.name"
            :placeholder="t('pages.adTemplate.form.namePlaceholder')"
          />
        </a-form-item>
        <a-form-item :label="t('pages.adTemplate.form.description')">
          <a-textarea
            v-model:value="editForm.description"
            :placeholder="t('pages.adTemplate.form.descriptionPlaceholder')"
            :rows="4"
          />
        </a-form-item>
      </a-form>
    </a-modal>
  </page-container>
</template>

<script setup lang="ts">
import { ref, computed, onMounted } from 'vue';
import { useI18n } from 'vue-i18n';
import { message, Modal } from 'ant-design-vue';
import { ShareAltOutlined, DeleteOutlined, DownOutlined } from '@ant-design/icons-vue';
import FilterSection from './components/FilterSection.vue';
import TemplateTable from './components/TemplateTable.vue';
import { getTemplateList as getTemplateListApi, deleteTemplates, updateTemplate as updateTemplateApi } from '@/api/ad_template';
import { shareTemplates } from '@/api/template-shares';
import { getUserOptions } from '@/api/user/options';
import type { AdTemplate } from './types';
import type { TemplateListParams } from '@/api/ad_template';

const { t, locale } = useI18n();

const isZh = computed(() => locale.value.startsWith('zh'));

// Data
const templateData = ref<AdTemplate[]>([]);
const loading = ref(false);
const selectedRowKeys = ref<string[]>([]);
const currentPage = ref(1);
const pageSize = ref(20);
const total = ref(0);

// Computed
const pagination = computed(() => ({
  current: currentPage.value,
  pageSize: pageSize.value,
  total: total.value,
  showSizeChanger: true,
  showQuickJumper: true,
  showTotal: (total: number) => t('pages.adTemplate.messages.total', { count: total }),
}));

const hasSelected = computed(() => selectedRowKeys.value.length > 0);

const shareModalVisible = ref(false);
const shareUsers = ref<string[]>([]);
const shareTemplateIds = ref<string[]>([]);
const userOptions = ref<Array<{ label: string; value: string }>>([]);

// 分享弹窗标题
const shareModalTitle = computed(() => {
  const isBatch = shareTemplateIds.value.length > 1;
  if (isBatch) {
    return t('pages.adTemplate.actions.batchShare', { count: shareTemplateIds.value.length });
  }
  const template = templateData.value.find(t => t.id === shareTemplateIds.value[0]);
  if (template) {
    return isZh.value
      ? `分享模板：${template.name}`
      : `Share Template: ${template.name}`;
  }
  return t('pages.adTemplate.actions.share');
});

// 用户搜索过滤
const filterUserOption = (input: string, option: any) => {
  return option?.label?.toLowerCase().includes(input.toLowerCase());
};

const editModalVisible = ref(false);
const editForm = ref({
  id: '',
  name: '',
  description: '',
});

// 筛选条件
const currentFilters = ref<Partial<TemplateListParams>>({});

// Methods
const loadData = async () => {
  loading.value = true;
  try {
    const response = await getTemplateListApi({
      page: currentPage.value,
      pageSize: pageSize.value,
      ...currentFilters.value,
    });
    console.log('Load response:', response);
    if (response.status) {
      templateData.value = response.data.data;
      // 计算总数（根据返回的数据）
      total.value = response.data.data.length;
    }
  } catch (error) {
    message.error('加载数据失败');
    console.error('Load data error:', error);
  } finally {
    loading.value = false;
  }
};

const handleSearch = (params: { channel?: string; name?: string; description?: string; creator?: string }) => {
  // 将前端参数转换为 API 参数
  currentFilters.value = {
    channel: params.channel,
    name: params.name,
    description: params.description,
    user_id: params.creator, // 前端的 creator 对应 API 的 user_id
  };
  currentPage.value = 1;
  loadData();
};


const handleSelectionChange = (keys: string[]) => {
  selectedRowKeys.value = keys;
};


const handleShare = (record: AdTemplate) => {
  shareTemplateIds.value = [record.id];
  shareUsers.value = [];
  shareModalVisible.value = true;
};

const handleEdit = (record: AdTemplate) => {
  editForm.value.id = record.id;
  editForm.value.name = record.name;
  editForm.value.description = record.description || '';
  editModalVisible.value = true;
};

const handleEditModalOk = async () => {
  if (!editForm.value.name.trim()) {
    message.warning(t('pages.adTemplate.messages.nameRequired'));
    return;
  }

  try {
    const response = await updateTemplateApi({
      template_id: editForm.value.id,
      name: editForm.value.name,
      description: editForm.value.description,
    });

    console.log('Update response:', response);
    if (response.status) {
      message.success(t('pages.adTemplate.messages.editSuccess'));
      editModalVisible.value = false;
      loadData(); // 重新加载数据
    } else {
      message.error(response.message || t('pages.adTemplate.messages.editError'));
    }
  } catch (error) {
    message.error(t('pages.adTemplate.messages.editError'));
    console.error('Update error:', error);
  }
};

const handleShareModalOk = async () => {
  if (shareUsers.value.length === 0) {
    message.warning(t('pages.adTemplate.messages.selectUsersRequired'));
    return;
  }

  try {
    const response = await shareTemplates({
      template_ids: shareTemplateIds.value,
      user_ids: shareUsers.value,
    });

    if (response.status) {
      message.success(t('pages.adTemplate.messages.shareSuccess', {
        templateCount: shareTemplateIds.value.length,
        userCount: shareUsers.value.length,
      }));
      shareModalVisible.value = false;
      shareUsers.value = [];
      shareTemplateIds.value = [];
      selectedRowKeys.value = [];
    } else {
      message.error(response.message || t('pages.adTemplate.messages.shareError'));
    }
  } catch (error) {
    message.error(t('pages.adTemplate.messages.shareError'));
    console.error('Share error:', error);
  }
};

const handleDelete = async (record: AdTemplate) => {
  Modal.confirm({
    title: t('pages.adTemplate.messages.deleteConfirm'),
    content: t('pages.adTemplate.messages.deleteConfirmContent', { name: record.name }),
    onOk: async () => {
      try {
        const response = await deleteTemplates({ template_ids: [record.id] });
        if (response.status) {
          message.success(t('pages.adTemplate.messages.deleteSuccess'));
          selectedRowKeys.value = selectedRowKeys.value.filter(key => key !== record.id);
          loadData();
        } else {
          message.error(response.message || t('pages.adTemplate.messages.deleteError'));
        }
      } catch (error) {
        message.error(t('pages.adTemplate.messages.deleteError'));
        console.error('Delete error:', error);
      }
    },
  });
};

const handleBatchDelete = () => {
  Modal.confirm({
    title: t('pages.adTemplate.messages.batchDeleteConfirm'),
    content: t('pages.adTemplate.messages.batchDeleteConfirmContent', { count: selectedRowKeys.value.length }),
    onOk: async () => {
      try {
        const response = await deleteTemplates({ template_ids: [...selectedRowKeys.value] });
        if (response.status) {
          message.success(t('pages.adTemplate.messages.batchDeleteSuccess'));
          selectedRowKeys.value = [];
          loadData();
        } else {
          message.error(response.message || t('pages.adTemplate.messages.batchDeleteError'));
        }
      } catch (error) {
        message.error(t('pages.adTemplate.messages.batchDeleteError'));
        console.error('Batch delete error:', error);
      }
    },
  });
};

const handleBatchShare = () => {
  shareTemplateIds.value = [...selectedRowKeys.value];
  shareUsers.value = [];
  shareModalVisible.value = true;
};

// 批量操作菜单点击处理
const handleBatchMenuClick = async ({ key }: { key: string | number }) => {
  switch (key) {
    case 'share':
      handleBatchShare();
      break;
    case 'delete':
      handleBatchDelete();
      break;
  }
};

const handlePageChange = (page: number, size: number) => {
  currentPage.value = page;
  pageSize.value = size;
  loadData();
};

// 加载用户选项列表
const loadUserOptions = async () => {
  try {
    const response = await getUserOptions();
    if (response.status && response.data) {
      userOptions.value = response.data;
    }
  } catch (error) {
    console.error('Failed to load user options:', error);
  }
};

// Lifecycle
onMounted(() => {
  loadData();
  loadUserOptions();
});
</script>

<style scoped lang="less">
.ad-template-page {
  min-height: 500px;

  .content-card {
    background: #fff;
    border-radius: 8px;
    padding: 16px;
    height: 100%;
    display: flex;
    flex-direction: column;
    gap: 16px;

    .filter-area {
      flex-shrink: 0;
    }

    .batch-action-bar {
      flex-shrink: 0;
      display: flex;
      justify-content: flex-start;
      padding: 0;

      .selected-count {
        margin-left: 4px;
        color: #1890ff;
      }
    }

    .table-area {
      flex: 1;
      overflow: hidden;
      min-height: 0;
    }
  }
}
</style>
