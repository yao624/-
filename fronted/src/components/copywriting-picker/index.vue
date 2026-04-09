<template>
  <a-modal
    :open="open"
    :title="title"
    :width="width"
    :mask-closable="false"
    destroy-on-close
    wrap-class-name="copywriting-picker-modal-wrap"
    @update:open="handleOpenChange"
    @cancel="handleCancel"
    @ok="handleConfirm"
  >
    <div class="copywriting-picker">
      <div class="picker-layout">
        <div class="picker-sidebar">
          <div class="sidebar-toolbar">
            <div class="section-title">文案库</div>
            <a-radio-group v-model:value="libraryType" size="small" button-style="solid">
              <a-radio-button value="personal">我的文案库</a-radio-button>
              <a-radio-button value="enterprise">企业文案库</a-radio-button>
            </a-radio-group>
            <a-select
              v-model:value="activeLibraryId"
              size="small"
              placeholder="请选择文案库"
              :options="libraryOptions"
              :loading="libraryLoading"
              style="width: 100%"
            />
          </div>

          <div class="tree-summary">
            <div class="section-title">文件夹</div>
            <div class="tree-path">{{ currentPathText }}</div>
          </div>

          <a-spin :spinning="treeLoading">
            <a-tree
              v-if="folderTreeData.length"
              class="folder-tree"
              block-node
              show-line
              :tree-data="folderTreeData"
              :selected-keys="selectedTreeKeys"
              :expanded-keys="expandedTreeKeys"
              @select="handleTreeSelect"
              @expand="handleTreeExpand"
            />
            <a-empty v-else class="tree-empty" description="暂无文件夹" />
          </a-spin>
        </div>

        <div class="picker-content">
          <div class="content-toolbar">
            <a-input
              v-model:value="keyword"
              allow-clear
              placeholder="搜索正文/标题/描述/备注"
              style="width: 240px"
              @press-enter="reloadItems"
            />
            <a-input
              v-model:value="countryCodesInput"
              allow-clear
              placeholder="预览国家，如 US,JP"
              style="width: 220px"
              @press-enter="reloadItems"
            />
            <a-checkbox v-model:checked="includeChildren">包含子文件夹文案</a-checkbox>
            <a-button type="primary" @click="reloadItems">搜索</a-button>
          </div>

          <a-table
            class="picker-table"
            size="small"
            :loading="tableLoading"
            :columns="columns"
            :data-source="tableRows"
            :pagination="pagination"
            :row-selection="rowSelection"
            :row-key="record => record.id"
            @change="handleTableChange"
            :custom-row="customRow"
          >
            <template #bodyCell="{ column, record }">
              <template v-if="column.dataIndex === 'primary_text'">
                <div class="cell-ellipsis">{{ record.primary_text || '-' }}</div>
              </template>
              <template v-else-if="column.dataIndex === 'headline'">
                <div class="cell-ellipsis">{{ record.headline || '-' }}</div>
              </template>
              <template v-else-if="column.dataIndex === 'description'">
                <div class="cell-ellipsis">{{ record.description || '-' }}</div>
              </template>
              <template v-else-if="column.dataIndex === 'remark'">
                <div class="cell-ellipsis">{{ record.remark || '-' }}</div>
              </template>
              <template v-else-if="column.dataIndex === 'created_at'">
                {{ record.created_at ? dayjs(record.created_at).format('YYYY-MM-DD HH:mm:ss') : '-' }}
              </template>
            </template>
          </a-table>

          <div v-if="previewRow" class="preview-panel">
            <div class="preview-header">
              <div>
                <div class="preview-title">文案预览</div>
                <div class="preview-subtitle">
                  当前预览
                  <a-tag color="blue">{{ previewRow.resolved_locale || '默认语言' }}</a-tag>
                  <span v-if="previewTranslations.length" class="preview-subtitle-text">
                    共 {{ previewTranslations.length }} 个多语言版本
                  </span>
                </div>
              </div>
            </div>

            <div class="preview-grid">
              <div class="preview-card">
                <div class="preview-card-title">默认文案</div>
                <div class="preview-field">
                  <div class="preview-label">正文</div>
                  <div class="preview-value preview-rich-text">{{ previewRow.primary_text || '-' }}</div>
                </div>
                <div class="preview-field">
                  <div class="preview-label">标题</div>
                  <div class="preview-value">{{ previewRow.headline || '-' }}</div>
                </div>
                <div class="preview-field">
                  <div class="preview-label">描述</div>
                  <div class="preview-value">{{ previewRow.description || '-' }}</div>
                </div>
                <div class="preview-field">
                  <div class="preview-label">备注</div>
                  <div class="preview-value">{{ previewRow.remark || '-' }}</div>
                </div>
              </div>

              <div class="preview-card preview-card-translations">
                <div class="preview-card-title">多语言内容</div>
                <div v-if="previewTranslations.length" class="translation-list">
                  <div
                    v-for="translation in previewTranslations"
                    :key="translation.locale"
                    class="translation-card"
                  >
                    <div class="translation-card-head">
                      <span class="translation-locale">{{ getLocaleLabel(translation.locale) }}</span>
                      <a-tag>{{ translation.locale }}</a-tag>
                    </div>
                    <div class="preview-field">
                      <div class="preview-label">正文</div>
                      <div class="preview-value preview-rich-text">
                        {{ translation.content.primary_text || '-' }}
                      </div>
                    </div>
                    <div class="preview-field">
                      <div class="preview-label">标题</div>
                      <div class="preview-value">{{ translation.content.headline || '-' }}</div>
                    </div>
                    <div class="preview-field">
                      <div class="preview-label">描述</div>
                      <div class="preview-value">{{ translation.content.description || '-' }}</div>
                    </div>
                  </div>
                </div>
                <a-empty v-else description="当前文案暂无多语言内容" />
              </div>
            </div>
          </div>
        </div>
      </div>
    </div>
  </a-modal>
</template>

<script setup lang="ts">
import { computed, reactive, ref, watch } from 'vue';
import dayjs from 'dayjs';
import type { TableColumnsType, TableProps, TreeProps } from 'ant-design-vue';
import { message } from 'ant-design-vue';
import {
  fetchMetaCopyFolderTree,
  fetchMetaCopyItems,
  fetchMetaCopyLibraries,
  META_COPY_LOCALE_OPTIONS,
  type MetaCopyFolderTreeNode,
  type MetaCopyItemRow,
  type MetaCopyLibraryRow,
} from '@/api/meta-copy-library';

type LibraryType = 'personal' | 'enterprise';

type PickerRow = MetaCopyItemRow & {
  resolved_locale?: string | null;
};

const props = withDefaults(
  defineProps<{
    open: boolean;
    title?: string;
    width?: string | number;
    multiple?: boolean;
    allowEmpty?: boolean;
    defaultSelectedRowKeys?: Array<string | number>;
    defaultSelectedRows?: PickerRow[];
    countryCodes?: string;
  }>(),
  {
    title: '选择文案',
    width: 1380,
    multiple: false,
    allowEmpty: true,
    defaultSelectedRowKeys: () => [],
    defaultSelectedRows: () => [],
    countryCodes: '',
  },
);

const emit = defineEmits<{
  (e: 'update:open', value: boolean): void;
  (e: 'cancel'): void;
  (e: 'confirm:items-selected', keys: Array<string | number>, rows: PickerRow[]): void;
}>();

const libraryLoading = ref(false);
const treeLoading = ref(false);
const tableLoading = ref(false);

const libraries = ref<MetaCopyLibraryRow[]>([]);
const libraryType = ref<LibraryType>('personal');
const activeLibraryId = ref<string>('');
const selectedFolderId = ref<string>('');
const keyword = ref('');
const includeChildren = ref(false);
const countryCodesInput = ref(props.countryCodes || '');
const items = ref<PickerRow[]>([]);
const selectedRowKeys = ref<Array<string | number>>([]);
const selectedRows = ref<PickerRow[]>([]);
const expandedTreeKeys = ref<string[]>([]);
const folderMetaMap = ref<Record<string, { id: string; name: string; parent_id: string | null }>>({});
const treeData = ref<TreeProps['treeData']>([]);

const pagination = reactive({
  current: 1,
  pageSize: 10,
  total: 0,
  showSizeChanger: true,
  pageSizeOptions: ['10', '20', '50', '100'],
  showTotal: (total: number) => `共 ${total} 条`,
});

const columns = computed<TableColumnsType>(() => [
  { title: '命中语言', dataIndex: 'resolved_locale', width: 100 },
  { title: '正文', dataIndex: 'primary_text', ellipsis: true },
  { title: '标题', dataIndex: 'headline', ellipsis: true, width: 220 },
  { title: '描述', dataIndex: 'description', ellipsis: true, width: 220 },
  { title: '备注', dataIndex: 'remark', ellipsis: true, width: 180 },
  { title: '创建时间', dataIndex: 'created_at', width: 180 },
]);

const filteredLibraries = computed(() =>
  libraries.value.filter(l => l.type === libraryType.value),
);

const libraryOptions = computed(() =>
  filteredLibraries.value.map(item => ({
    label: item.name,
    value: item.id,
  })),
);

const rootFolderId = computed(() => {
  const roots = Object.values(folderMetaMap.value).filter(item => item.parent_id === null);
  return roots[0]?.id ?? '';
});

const effectiveFolderId = computed(() => selectedFolderId.value || rootFolderId.value);
const folderTreeData = computed(() => ((treeData.value as any[]) ?? []));

const selectedTreeKeys = computed(() => (selectedFolderId.value ? [`folder:${selectedFolderId.value}`] : []));

const currentPathText = computed(() => {
  const library = libraries.value.find(item => item.id === activeLibraryId.value);
  if (!library) return '未选择文案库';
  if (!selectedFolderId.value) return `${library.name} / 根目录`;

  const names: string[] = [];
  let cursor: string | null = selectedFolderId.value;
  while (cursor) {
    const current = folderMetaMap.value[cursor];
    if (!current) break;
    names.unshift(current.name);
    cursor = current.parent_id;
  }
  return `${library.name} / ${names.join(' / ')}`;
});

const rowSelection = computed<TableProps['rowSelection']>(() => ({
  type: props.multiple ? 'checkbox' : 'radio',
  selectedRowKeys: selectedRowKeys.value,
  onChange: (keys, rows) => {
    selectedRowKeys.value = keys;
    selectedRows.value = rows as PickerRow[];
  },
}));

const tableRows = computed(() => items.value);
const previewRow = computed<PickerRow | null>(() => selectedRows.value[0] ?? null);
const localeLabelMap = Object.fromEntries(META_COPY_LOCALE_OPTIONS.map(item => [item.value, item.label]));
const previewTranslations = computed(() => {
  const translations = previewRow.value?.translations;
  if (!translations || typeof translations !== 'object') return [];
  return Object.entries(translations).map(([locale, content]) => ({
    locale,
    content: content ?? {},
  }));
});

function getLocaleLabel(locale: string) {
  return localeLabelMap[locale] || locale;
}

function handleOpenChange(value: boolean) {
  emit('update:open', value);
}

function handleCancel() {
  emit('update:open', false);
  emit('cancel');
}

function handleConfirm() {
  if (!props.allowEmpty && selectedRowKeys.value.length === 0) {
    message.warning('请先选择文案');
    return;
  }
  emit('confirm:items-selected', selectedRowKeys.value, selectedRows.value);
  emit('update:open', false);
}

function mapTree(
  nodes: MetaCopyFolderTreeNode[],
  parentId: string | null = null,
  logicalRootId?: string,
): TreeProps['treeData'] {
  return nodes.map(node => ({
    title: logicalRootId && node.id === logicalRootId ? '根目录' : node.name,
    key: `folder:${node.id}`,
    parentId,
    children: node.children?.length ? mapTree(node.children, node.id, logicalRootId) : undefined,
  }));
}

async function loadLibraries() {
  libraryLoading.value = true;
  try {
    libraries.value = await fetchMetaCopyLibraries();
    const currentInGroup = filteredLibraries.value.some(item => item.id === activeLibraryId.value);
    if (!currentInGroup) {
      activeLibraryId.value = filteredLibraries.value[0]?.id ?? '';
    }
  } catch {
    message.error('加载文案库失败');
  } finally {
    libraryLoading.value = false;
  }
}

async function loadTree() {
  if (!activeLibraryId.value) {
    treeData.value = [];
    folderMetaMap.value = {};
    selectedFolderId.value = '';
    return;
  }

  treeLoading.value = true;
  try {
    const raw = await fetchMetaCopyFolderTree(activeLibraryId.value);
    const meta: Record<string, { id: string; name: string; parent_id: string | null }> = {};
    const walk = (arr: MetaCopyFolderTreeNode[], parent: string | null) => {
      arr.forEach(item => {
        meta[item.id] = { id: item.id, name: item.name, parent_id: parent };
        if (item.children?.length) {
          walk(item.children, item.id);
        }
      });
    };
    walk(raw, null);
    folderMetaMap.value = meta;
    const logicalRoot = Object.values(meta).find(item => item.parent_id === null)?.id;
    treeData.value = mapTree(raw, null, logicalRoot);

    const allKeys: string[] = [];
    const collectKeys = (arr: TreeProps['treeData']) => {
      (arr || []).forEach((item: any) => {
        allKeys.push(String(item.key));
        if (item.children?.length) collectKeys(item.children);
      });
    };
    collectKeys(treeData.value);
    expandedTreeKeys.value = allKeys;
    selectedFolderId.value = logicalRoot ?? '';
  } catch {
    message.error('加载文件夹失败');
  } finally {
    treeLoading.value = false;
  }
}

async function reloadItems() {
  if (!effectiveFolderId.value) {
    items.value = [];
    pagination.total = 0;
    return;
  }

  tableLoading.value = true;
  try {
    const response = await fetchMetaCopyItems({
      folder_id: effectiveFolderId.value,
      include_children: includeChildren.value ? 1 : 0,
      keyword: keyword.value || undefined,
      pageNo: pagination.current,
      pageSize: pagination.pageSize,
      ...(countryCodesInput.value.trim() ? ({ country_codes: countryCodesInput.value.trim() } as any) : {}),
    } as any);

    items.value = response.data ?? [];
    pagination.total = response.totalCount ?? 0;
  } catch {
    message.error('加载文案列表失败');
  } finally {
    tableLoading.value = false;
  }
}

function resetSelection() {
  selectedRowKeys.value = [...props.defaultSelectedRowKeys];
  selectedRows.value = [...props.defaultSelectedRows];
}

async function initialize() {
  keyword.value = '';
  includeChildren.value = false;
  countryCodesInput.value = props.countryCodes || '';
  pagination.current = 1;
  pagination.pageSize = 10;
  await loadLibraries();
  await loadTree();
  resetSelection();
  await reloadItems();
}

function handleTreeSelect(keys: Array<string | number>) {
  const key = String(keys[0] ?? '');
  selectedFolderId.value = key.startsWith('folder:') ? key.slice('folder:'.length) : '';
  pagination.current = 1;
  reloadItems();
}

function handleTreeExpand(keys: Array<string | number>) {
  expandedTreeKeys.value = keys.map(key => String(key));
}

const handleTableChange: TableProps['onChange'] = pag => {
  pagination.current = pag.current ?? 1;
  pagination.pageSize = pag.pageSize ?? 10;
  reloadItems();
};

function customRow(record: PickerRow) {
  return {
    onClick: () => {
      const id = record.id;
      if (!id) return;

      if (props.multiple) {
        const exists = selectedRowKeys.value.includes(id);
        if (exists) {
          selectedRowKeys.value = selectedRowKeys.value.filter(key => key !== id);
          selectedRows.value = selectedRows.value.filter(item => item.id !== id);
        } else {
          selectedRowKeys.value = [...selectedRowKeys.value, id];
          selectedRows.value = [...selectedRows.value, record];
        }
        return;
      }

      selectedRowKeys.value = [id];
      selectedRows.value = [record];
    },
    style: { cursor: 'pointer' },
  };
}

watch(
  () => props.open,
  value => {
    if (value) {
      initialize();
    }
  },
);

watch(
  () => props.countryCodes,
  value => {
    if (!props.open) return;
    countryCodesInput.value = value || '';
  },
);

watch(libraryType, async () => {
  if (!props.open) return;
  activeLibraryId.value = filteredLibraries.value[0]?.id ?? '';
});

watch(activeLibraryId, async (value, oldValue) => {
  if (!props.open) return;
  if (!value || value === oldValue) return;
  pagination.current = 1;
  await loadTree();
  await reloadItems();
});
</script>

<style scoped>
.copywriting-picker {
  min-height: 700px;
}

.picker-layout {
  display: grid;
  grid-template-columns: 320px minmax(0, 1fr);
  gap: 18px;
  min-height: 700px;
}

.picker-sidebar {
  border: 1px solid #e8eef6;
  border-radius: 14px;
  background: linear-gradient(180deg, #fbfdff 0%, #f7faff 100%);
  padding: 14px;
  display: flex;
  flex-direction: column;
  min-height: 0;
  box-shadow: inset 0 1px 0 rgba(255, 255, 255, 0.85);
}

.sidebar-toolbar {
  display: flex;
  flex-direction: column;
  gap: 12px;
  margin-bottom: 14px;
  padding-bottom: 14px;
  border-bottom: 1px solid #edf2f7;
}

.section-title {
  font-size: 12px;
  font-weight: 700;
  color: #7a8699;
  letter-spacing: 0.08em;
  text-transform: uppercase;
}

.tree-summary {
  margin-bottom: 10px;
}

.tree-path {
  color: #4b5565;
  font-size: 12px;
  line-height: 1.5;
  margin-top: 6px;
  padding: 8px 10px;
  border-radius: 10px;
  background: rgba(24, 144, 255, 0.06);
}

.folder-tree {
  flex: 1;
  min-height: 0;
  overflow: auto;
  border: 1px solid #edf2f7;
  border-radius: 12px;
  padding: 10px 8px;
  background: #fff;
}

.tree-empty {
  margin-top: 80px;
}

.picker-content {
  border: 1px solid #e8eef6;
  border-radius: 14px;
  background: #fff;
  padding: 14px;
  min-width: 0;
  box-shadow: 0 6px 18px rgba(31, 55, 88, 0.04);
}

.preview-panel {
  margin-top: 16px;
  padding-top: 16px;
  border-top: 1px solid #eef2f7;
}

.preview-header {
  display: flex;
  align-items: center;
  justify-content: space-between;
  margin-bottom: 14px;
}

.preview-title {
  font-size: 16px;
  font-weight: 700;
  color: #223046;
}

.preview-subtitle {
  margin-top: 6px;
  display: flex;
  align-items: center;
  gap: 8px;
  flex-wrap: wrap;
  color: #6b778c;
  font-size: 12px;
}

.preview-subtitle-text {
  color: #8a94a6;
}

.preview-grid {
  display: grid;
  grid-template-columns: minmax(0, 1fr) minmax(0, 1fr);
  gap: 16px;
  align-items: start;
}

.preview-card {
  border: 1px solid #e8eef6;
  border-radius: 14px;
  background: linear-gradient(180deg, #fcfdff 0%, #f7faff 100%);
  padding: 16px;
  min-width: 0;
}

.preview-card-translations {
  max-height: 520px;
  overflow: hidden;
  display: flex;
  flex-direction: column;
}

.preview-card-title {
  font-size: 14px;
  font-weight: 700;
  color: #243042;
  margin-bottom: 12px;
}

.preview-field + .preview-field {
  margin-top: 12px;
}

.preview-label {
  font-size: 12px;
  font-weight: 700;
  color: #7a8699;
  margin-bottom: 6px;
}

.preview-value {
  color: #243042;
  line-height: 1.7;
  word-break: break-word;
  padding: 10px 12px;
  border-radius: 10px;
  background: rgba(255, 255, 255, 0.88);
  border: 1px solid #edf2f7;
}

.preview-rich-text {
  min-height: 72px;
  white-space: pre-wrap;
}

.translation-list {
  display: flex;
  flex-direction: column;
  gap: 12px;
  min-height: 0;
  overflow-y: auto;
  padding-right: 6px;
}

.translation-list::-webkit-scrollbar {
  width: 8px;
}

.translation-list::-webkit-scrollbar-thumb {
  background: rgba(139, 152, 170, 0.45);
  border-radius: 999px;
}

.translation-list::-webkit-scrollbar-track {
  background: transparent;
}

.translation-card {
  border: 1px solid #e9edf3;
  border-radius: 12px;
  background: #fff;
  padding: 14px;
}

.translation-card-head {
  display: flex;
  align-items: center;
  justify-content: space-between;
  gap: 10px;
  margin-bottom: 12px;
}

.translation-locale {
  font-size: 13px;
  font-weight: 700;
  color: #223046;
}

.content-toolbar {
  display: flex;
  flex-wrap: wrap;
  gap: 10px;
  margin-bottom: 14px;
  padding-bottom: 14px;
  border-bottom: 1px solid #f0f3f8;
}

.picker-table :deep(.ant-table-tbody > tr > td) {
  vertical-align: top;
}

.picker-table :deep(.ant-table) {
  border-radius: 12px;
  overflow: hidden;
}

.picker-table :deep(.ant-table-thead > tr > th) {
  background: #f8fbff;
  font-weight: 700;
  color: #243042;
}

.picker-table :deep(.ant-table-row:hover > td) {
  background: #f6fbff !important;
}

.folder-tree :deep(.ant-tree-switcher) {
  color: #8da0b8;
}

.folder-tree :deep(.ant-tree-node-content-wrapper) {
  min-height: 34px;
  border-radius: 10px;
  display: flex;
  align-items: center;
  padding: 4px 10px;
  transition: all 0.2s ease;
}

.folder-tree :deep(.ant-tree-node-content-wrapper:hover) {
  background: #f5f9ff;
}

.folder-tree :deep(.ant-tree-node-selected) {
  background: linear-gradient(90deg, rgba(24, 144, 255, 0.12), rgba(24, 144, 255, 0.04)) !important;
  color: #1677ff;
  font-weight: 700;
}

.folder-tree :deep(.ant-tree-indent-unit) {
  width: 18px;
}

.cell-ellipsis {
  display: -webkit-box;
  -webkit-line-clamp: 2;
  -webkit-box-orient: vertical;
  overflow: hidden;
  word-break: break-word;
}

:global(.copywriting-picker-modal-wrap .ant-modal) {
  max-width: 1420px;
  top: 34px;
}

:global(.copywriting-picker-modal-wrap .ant-modal-content) {
  border-radius: 18px;
  overflow: hidden;
}

:global(.copywriting-picker-modal-wrap .ant-modal-header) {
  padding: 18px 22px 14px;
  border-bottom: 1px solid #eef2f7;
  background: linear-gradient(180deg, #ffffff 0%, #fbfcfe 100%);
}

:global(.copywriting-picker-modal-wrap .ant-modal-title) {
  font-size: 18px;
  font-weight: 700;
  color: #1f2d3d;
}

:global(.copywriting-picker-modal-wrap .ant-modal-body) {
  padding: 18px 20px 12px;
}

:global(.copywriting-picker-modal-wrap .ant-modal-footer) {
  padding: 14px 20px 18px;
  border-top: 1px solid #eef2f7;
}

@media (max-width: 960px) {
  .picker-layout {
    grid-template-columns: 1fr;
  }

  .picker-sidebar {
    min-height: 220px;
  }

  .preview-grid {
    grid-template-columns: 1fr;
  }
}
</style>
