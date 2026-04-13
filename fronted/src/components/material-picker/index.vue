<template>
  <a-modal
    :open="open"
    :title="title"
    :width="width"
    :mask-closable="false"
    destroy-on-close
    wrap-class-name="material-picker-modal-wrap"
    @update:open="handleOpenChange"
    @cancel="handleCancel"
    @ok="handleConfirm"
  >
    <div class="material-picker">
      <div class="picker-layout">
        <div class="picker-sidebar">
          <div class="sidebar-toolbar">
            <div class="section-title">素材库</div>
            <a-radio-group v-model:value="libraryType" size="small" button-style="solid">
              <a-radio-button value="personal">我的素材库</a-radio-button>
              <a-radio-button value="enterprise">企业素材库</a-radio-button>
            </a-radio-group>
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
              placeholder="搜索素材名称 / Local ID / 备注"
              style="width: 280px"
              @press-enter="reloadItems"
            />
            <a-checkbox v-model:checked="includeChildren">包含子文件夹</a-checkbox>
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
              <template v-if="column.dataIndex === 'preview'">
                <div class="material-preview-cell">
                  <img
                    v-if="record.type === 'image' && getThumbnailUrl(record)"
                    :src="getThumbnailUrl(record)"
                    class="material-thumb"
                  />
                  <video
                    v-else-if="record.type === 'video' && getPreviewUrl(record)"
                    :src="getPreviewUrl(record)"
                    class="material-thumb"
                    muted
                  />
                  <div v-else class="material-thumb material-thumb--empty">
                    {{ record.type === 'video' ? '视频' : '文件' }}
                  </div>
                </div>
              </template>
              <template v-else-if="column.dataIndex === 'name'">
                <div class="cell-ellipsis">{{ getMaterialName(record) }}</div>
              </template>
              <template v-else-if="column.dataIndex === 'localId'">
                <div class="cell-ellipsis">{{ record.localId || '-' }}</div>
              </template>
              <template v-else-if="column.dataIndex === 'type'">
                {{ formatMaterialType(record.type) }}
              </template>
              <template v-else-if="column.dataIndex === 'size'">
                {{ formatSize(record.width, record.height) }}
              </template>
              <template v-else-if="column.dataIndex === 'duration'">
                {{ formatDuration(record.duration) }}
              </template>
              <template v-else-if="column.dataIndex === 'createTime'">
                {{ formatDate(record.createTime || record.created_at || record.createdAt) }}
              </template>
            </template>
          </a-table>

          <div v-if="previewRows.length" class="preview-panel">
            <div class="preview-header">
              <div>
                <div class="preview-title">素材预览</div>
                <div class="preview-subtitle">
                  <a-tag color="blue">已选 {{ previewRows.length }} 项</a-tag>
                  <span>{{ previewSummaryText }}</span>
                </div>
              </div>
            </div>

            <div class="preview-grid">
              <div class="preview-card preview-card-selection">
                <div class="preview-card-title">已选素材</div>
                <div class="selected-preview-list">
                  <button
                    v-for="previewItem in previewRows"
                    :key="previewItem.id"
                    type="button"
                    class="selected-preview-item"
                    :class="{ 'selected-preview-item--active': String(activePreviewId) === String(previewItem.id) }"
                    @click="setActivePreview(previewItem.id)"
                  >
                    <img
                      v-if="previewItem.type === 'image' && getThumbnailUrl(previewItem)"
                      :src="getThumbnailUrl(previewItem)"
                      class="selected-preview-thumb"
                    />
                    <div v-else class="selected-preview-thumb selected-preview-thumb--placeholder">
                      {{ previewItem.type === 'video' ? '视频' : '文件' }}
                    </div>
                    <div class="selected-preview-meta">
                      <div class="selected-preview-name">{{ getMaterialName(previewItem) }}</div>
                      <div class="selected-preview-submeta">
                        <span>{{ formatMaterialType(previewItem.type) }}</span>
                        <span>{{ previewItem.localId || '-' }}</span>
                      </div>
                    </div>
                    <span
                      class="selected-preview-remove"
                      @click.stop="removePreviewItem(previewItem.id)"
                    >
                      移除
                    </span>
                  </button>
                </div>
              </div>

              <div v-if="activePreviewRow" class="preview-grid-detail">
                <div class="preview-card preview-card-media">
                  <img
                    v-if="activePreviewRow.type === 'image' && getPreviewUrl(activePreviewRow)"
                    :src="getPreviewUrl(activePreviewRow)"
                    class="preview-media"
                  />
                  <video
                    v-else-if="activePreviewRow.type === 'video' && getPreviewUrl(activePreviewRow)"
                    :src="getPreviewUrl(activePreviewRow)"
                    class="preview-media"
                    controls
                  />
                  <a-empty v-else description="暂无预览" />
                </div>

                <div class="preview-card preview-card-detail">
                  <div class="preview-card-title">详情</div>
                  <div class="preview-field">
                    <div class="preview-label">名称</div>
                    <div class="preview-value">{{ getMaterialName(activePreviewRow) }}</div>
                  </div>
                  <div class="preview-field">
                    <div class="preview-label">Local ID</div>
                    <div class="preview-value">{{ activePreviewRow.localId || '-' }}</div>
                  </div>
                  <div class="preview-field">
                    <div class="preview-label">类型</div>
                    <div class="preview-value">{{ formatMaterialType(activePreviewRow.type) }}</div>
                  </div>
                  <div class="preview-field">
                    <div class="preview-label">尺寸</div>
                    <div class="preview-value">{{ formatSize(activePreviewRow.width, activePreviewRow.height) }}</div>
                  </div>
                  <div class="preview-field">
                    <div class="preview-label">时长</div>
                    <div class="preview-value">{{ formatDuration(activePreviewRow.duration) }}</div>
                  </div>
                  <div class="preview-field">
                    <div class="preview-label">创建时间</div>
                    <div class="preview-value">
                      {{ formatDate(activePreviewRow.createTime || activePreviewRow.created_at || activePreviewRow.createdAt) }}
                    </div>
                  </div>
                  <div class="preview-field">
                    <div class="preview-label">备注</div>
                    <div class="preview-value preview-rich-text">
                      {{ activePreviewRow.remarks || activePreviewRow.remark || '-' }}
                    </div>
                  </div>
                </div>
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
import { getMaterialLibraryFolders, getMaterialLibraryFolderChildren } from '@/api/material-library/folders';
import { getMaterialLibraryMaterials } from '@/api/material-library/materials';
import { useUserStore } from '@/store/user';

type LibraryType = 'personal' | 'enterprise';

type FolderMeta = {
  id: string;
  name: string;
  parent_id: string | null;
  owner_id?: string | number;
  library_type?: string | number;
  childrenLoaded?: boolean;
};

type MaterialRow = Record<string, any> & {
  id: string | number;
  type?: string;
};

const props = withDefaults(
  defineProps<{
    open: boolean;
    title?: string;
    width?: string | number;
    multiple?: boolean;
    allowEmpty?: boolean;
    defaultSelectedRowKeys?: Array<string | number>;
    defaultSelectedRows?: MaterialRow[];
  }>(),
  {
    title: '选择素材',
    width: 1380,
    multiple: true,
    allowEmpty: true,
    defaultSelectedRowKeys: () => [],
    defaultSelectedRows: () => [],
  },
);

const emit = defineEmits<{
  (e: 'update:open', value: boolean): void;
  (e: 'cancel'): void;
  (e: 'confirm:items-selected', keys: Array<string | number>, rows: MaterialRow[]): void;
}>();

const userStore = useUserStore();

const libraryLoading = ref(false);
const treeLoading = ref(false);
const tableLoading = ref(false);

const libraries = ref<Array<Record<string, any>>>([]);
const libraryType = ref<LibraryType>('personal');
const selectedFolderId = ref<string>('');
const keyword = ref('');
const includeChildren = ref(false);
const items = ref<MaterialRow[]>([]);
const selectedRowMap = ref<Record<string, MaterialRow>>({});
const expandedTreeKeys = ref<string[]>([]);
const folderMetaMap = ref<Record<string, FolderMeta>>({});
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
  { title: '预览', dataIndex: 'preview', width: 96 },
  { title: '名称', dataIndex: 'name', ellipsis: true },
  { title: 'Local ID', dataIndex: 'localId', ellipsis: true, width: 180 },
  { title: '类型', dataIndex: 'type', width: 100 },
  { title: '尺寸', dataIndex: 'size', width: 120 },
  { title: '时长', dataIndex: 'duration', width: 120 },
  { title: '创建时间', dataIndex: 'createTime', width: 180 },
]);

const filteredLibraries = computed(() =>
  libraries.value.filter(item => item.pickerLibraryType === libraryType.value),
);

const folderTreeData = computed(() => ((treeData.value as any[]) ?? []));
const selectedTreeKeys = computed(() => (selectedFolderId.value ? [`folder:${selectedFolderId.value}`] : []));
const tableRows = computed(() => items.value);
const selectedRows = computed<MaterialRow[]>(() => Object.values(selectedRowMap.value));
const selectedRowKeys = computed<Array<string | number>>(() =>
  selectedRows.value.map(item => item.id),
);
const currentTableSelectedRowKeys = computed<Array<string | number>>(() => {
  const currentIds = new Set(items.value.map(item => String(item.id)));
  return selectedRowKeys.value.filter(key => currentIds.has(String(key)));
});
const previewRows = computed<MaterialRow[]>(() => selectedRows.value);
const activePreviewId = ref<string | number | null>(null);
const activePreviewRow = computed<MaterialRow | null>(() => {
  if (!previewRows.value.length) return null;
  if (activePreviewId.value !== null) {
    const matched = previewRows.value.find(item => String(item.id) === String(activePreviewId.value));
    if (matched) return matched;
  }
  return previewRows.value[0] ?? null;
});
const previewSummaryText = computed(() => {
  if (!previewRows.value.length) return '-';
  if (previewRows.value.length === 1) return getMaterialName(previewRows.value[0]);
  return `${getMaterialName(previewRows.value[0])} 等 ${previewRows.value.length} 个素材`;
});

const rowSelection = computed<TableProps['rowSelection']>(() => ({
  type: props.multiple ? 'checkbox' : 'radio',
  selectedRowKeys: currentTableSelectedRowKeys.value,
  onChange: (keys, rows) => {
    syncSelectionFromTable(keys, rows as MaterialRow[]);
  },
}));

const currentPathText = computed(() => {
  if (!selectedFolderId.value) return '未选择文件夹';

  const names: string[] = [];
  let cursor: string | null = selectedFolderId.value;
  while (cursor) {
    const current = folderMetaMap.value[cursor];
    if (!current) break;
    names.unshift(current.name);
    cursor = current.parent_id;
  }
  return names.length ? names.join(' / ') : '未选择文件夹';
});

function getMaterialName(record: MaterialRow) {
  return String(record.name || record.material_name || record.filename || record.id || '-');
}

function getThumbnailUrl(record: MaterialRow) {
  return (
    record.thumbnail ||
    record.thumb_url ||
    record.cover_url ||
    record.preview_image_url ||
    record.preview_url ||
    ''
  );
}

function getPreviewUrl(record: MaterialRow) {
  return (
    record.url ||
    record.material_url ||
    record.file_url ||
    record.preview_url ||
    record.download_url ||
    getThumbnailUrl(record)
  );
}

function formatMaterialType(type: any) {
  if (!type) return '-';
  if (String(type) === 'image') return '图片';
  if (String(type) === 'video') return '视频';
  return String(type);
}

function formatSize(width: any, height: any) {
  const w = width !== null && width !== undefined && width !== '' ? Number(width) : null;
  const h = height !== null && height !== undefined && height !== '' ? Number(height) : null;
  if (w === null || h === null || !Number.isFinite(w) || !Number.isFinite(h)) return '-';
  return `${w}x${h}`;
}

function formatDuration(duration: any) {
  if (duration === null || duration === undefined || duration === '') return '-';
  const value = Number(duration);
  if (!Number.isFinite(value)) return '-';
  return `${value} 秒`;
}

function formatDate(value: any) {
  if (!value) return '-';
  const parsed = dayjs(value);
  if (!parsed.isValid()) return '-';
  return parsed.format('YYYY-MM-DD HH:mm:ss');
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
    message.warning('请先选择素材');
    return;
  }
  emit('confirm:items-selected', selectedRowKeys.value, selectedRows.value);
  emit('update:open', false);
}

function setActivePreview(id: string | number) {
  activePreviewId.value = id;
}

function upsertSelectedRecord(record: MaterialRow) {
  if (record?.id === undefined || record?.id === null || record?.id === '') return;
  selectedRowMap.value = {
    ...selectedRowMap.value,
    [String(record.id)]: record,
  };
}

function removeSelectedRecord(id: string | number) {
  const key = String(id);
  if (!(key in selectedRowMap.value)) return;
  const nextMap = { ...selectedRowMap.value };
  delete nextMap[key];
  selectedRowMap.value = nextMap;
}

function syncSelectionFromTable(keys: Array<string | number>, rows: MaterialRow[]) {
  const currentTableIds = new Set(items.value.map(item => String(item.id)));
  const currentSelectedIds = new Set(keys.map(key => String(key)));
  const nextMap = { ...selectedRowMap.value };

  currentTableIds.forEach(id => {
    if (!currentSelectedIds.has(id)) {
      delete nextMap[id];
    }
  });

  if (props.multiple) {
    rows.forEach(record => {
      if (record?.id !== undefined && record?.id !== null && record?.id !== '') {
        nextMap[String(record.id)] = record;
      }
    });
  } else {
    Object.keys(nextMap).forEach(id => {
      delete nextMap[id];
    });
    const firstRow = rows[0];
    if (firstRow?.id !== undefined && firstRow?.id !== null && firstRow?.id !== '') {
      nextMap[String(firstRow.id)] = firstRow;
    }
  }

  selectedRowMap.value = nextMap;
}

function removePreviewItem(id: string | number) {
  removeSelectedRecord(id);
}

function normalizeFolderRow(
  folder: Record<string, any>,
  parentId: string | null,
  pickerLibraryType: LibraryType,
): Record<string, any> {
  return {
    ...folder,
    id: String(folder.id),
    name: String(folder.name || folder.folder_name || folder.id),
    owner_id: folder.owner_id,
    library_type: folder.library_type,
    parent_id: parentId,
    pickerLibraryType,
  };
}

function findLibraryRowById(id: string) {
  return libraries.value.find(item => String(item.id) === id) || null;
}

async function loadLibraries() {
  libraryLoading.value = true;
  try {
    const userInfo: any = userStore.info || {};
    const userId = userInfo?.id;
    const enterpriseId = userInfo?.enterprise_id ?? userInfo?.enterpriseId;

    const [personalRes, enterpriseRes] = await Promise.all([
      getMaterialLibraryFolders({
        library_type: 0,
        ...(userId !== undefined && userId !== null && userId !== '' ? { owner_id: userId } : {}),
      }),
      getMaterialLibraryFolders({
        library_type: 1,
        ...(enterpriseId !== undefined && enterpriseId !== null && enterpriseId !== '' ? { owner_id: enterpriseId } : {}),
      }),
    ]);

    const personalRows = (personalRes?.data || []).map((item: Record<string, any>) =>
      normalizeFolderRow(item, null, 'personal'),
    );
    const enterpriseRows = (enterpriseRes?.data || []).map((item: Record<string, any>) =>
      normalizeFolderRow(item, null, 'enterprise'),
    );

    libraries.value = [...personalRows, ...enterpriseRows];

  } catch (error) {
    console.error('load material libraries failed:', error);
    message.error('加载素材库失败');
  } finally {
    libraryLoading.value = false;
  }
}

async function buildFolderBranch(
  folder: Record<string, any>,
  parentId: string | null,
  pickerLibraryType: LibraryType,
): Promise<any> {
  const normalized = normalizeFolderRow(folder, parentId, pickerLibraryType);
  const folderId = String(normalized.id);

  folderMetaMap.value[folderId] = {
    id: folderId,
    name: normalized.name,
    parent_id: parentId,
    owner_id: normalized.owner_id,
    library_type: normalized.library_type,
    childrenLoaded: true,
  };

  const response = await getMaterialLibraryFolderChildren(folderId, {
    owner_id: normalized.owner_id,
    library_type: normalized.library_type,
  });

  const childrenRows = (response?.data || []).map((item: Record<string, any>) => ({
    ...item,
    owner_id: item.owner_id ?? normalized.owner_id,
    library_type: item.library_type ?? normalized.library_type,
  }));

  const children = await Promise.all(
    childrenRows.map(child => buildFolderBranch(child, folderId, pickerLibraryType)),
  );

  return {
    title: normalized.name,
    key: `folder:${folderId}`,
    isLeaf: children.length === 0,
    children,
  };
}

async function loadTree() {
  if (!filteredLibraries.value.length) {
    treeData.value = [];
    folderMetaMap.value = {};
    selectedFolderId.value = '';
    return;
  }

  treeLoading.value = true;
  try {
    folderMetaMap.value = {};
    treeData.value = await Promise.all(
      filteredLibraries.value.map(library =>
        buildFolderBranch(library, null, library.pickerLibraryType as LibraryType),
      ),
    );

    const allKeys: string[] = [];
    const walk = (nodes: any[]) => {
      (nodes || []).forEach(node => {
        allKeys.push(String(node.key));
        if (Array.isArray(node.children) && node.children.length) {
          walk(node.children);
        }
      });
    };
    walk((treeData.value as any[]) || []);
    expandedTreeKeys.value = allKeys;

    const selectedStillExists = selectedFolderId.value && !!folderMetaMap.value[selectedFolderId.value];
    if (!selectedStillExists) {
      selectedFolderId.value = String(filteredLibraries.value[0]?.id || '');
    }
  } catch (error) {
    console.error('load material folder tree failed:', error);
    message.error('加载文件夹失败');
  } finally {
    treeLoading.value = false;
  }
}

async function reloadItems() {
  if (!selectedFolderId.value) {
    items.value = [];
    pagination.total = 0;
    return;
  }

  const meta = folderMetaMap.value[selectedFolderId.value];
  tableLoading.value = true;
  try {
    const response = await getMaterialLibraryMaterials({
      folder_id: selectedFolderId.value,
      owner_id: meta?.owner_id,
      pageNo: pagination.current,
      pageSize: pagination.pageSize,
      global_search: keyword.value || undefined,
      include_subfolders: includeChildren.value ? 1 : 0,
    });

    items.value = (response?.data || []).filter((item: MaterialRow) => item?.type !== 'folder');
    pagination.total = response?.totalCount ?? items.value.length;
  } catch (error) {
    console.error('load material items failed:', error);
    message.error('加载素材失败');
  } finally {
    tableLoading.value = false;
  }
}

function resetSelection() {
  const nextMap: Record<string, MaterialRow> = {};
  props.defaultSelectedRows.forEach(item => {
    if (item?.id !== undefined && item?.id !== null && item?.id !== '') {
      nextMap[String(item.id)] = item;
    }
  });
  props.defaultSelectedRowKeys.forEach(id => {
    const key = String(id);
    if (!nextMap[key]) {
      nextMap[key] = { id };
    }
  });
  selectedRowMap.value = nextMap;
  activePreviewId.value = props.defaultSelectedRows[0]?.id ?? null;
}

async function initialize() {
  keyword.value = '';
  includeChildren.value = false;
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

async function handleTreeExpand(keys: Array<string | number>, info: any) {
  expandedTreeKeys.value = keys.map(key => String(key));
  if (!info?.expanded) return;
}

const handleTableChange: TableProps['onChange'] = pag => {
  pagination.current = pag.current ?? 1;
  pagination.pageSize = pag.pageSize ?? 10;
  reloadItems();
};

function customRow(record: MaterialRow) {
  return {
    onClick: () => {
      const id = record.id;
      if (!id) return;

      if (props.multiple) {
        const exists = String(id) in selectedRowMap.value;
        if (exists) {
          removeSelectedRecord(id);
        } else {
          upsertSelectedRecord(record);
        }
        return;
      }

      selectedRowMap.value = {
        [String(id)]: record,
      };
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
  previewRows,
  value => {
    if (!value.length) {
      activePreviewId.value = null;
      return;
    }
    const exists = value.some(item => String(item.id) === String(activePreviewId.value));
    if (!exists) {
      activePreviewId.value = value[0]?.id ?? null;
    }
  },
  { deep: true },
);

watch(libraryType, async () => {
  if (!props.open) return;
  pagination.current = 1;
  await loadTree();
  await reloadItems();
});
</script>

<style scoped>
.material-picker {
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
  word-break: break-word;
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

.material-preview-cell {
  display: flex;
  align-items: center;
}

.material-thumb {
  width: 56px;
  height: 56px;
  border-radius: 10px;
  object-fit: cover;
  background: #f3f6fa;
  border: 1px solid #e8eef6;
}

.material-thumb--empty {
  display: flex;
  align-items: center;
  justify-content: center;
  color: #8a94a6;
  font-size: 11px;
  font-weight: 700;
}

.cell-ellipsis {
  display: -webkit-box;
  -webkit-line-clamp: 2;
  -webkit-box-orient: vertical;
  overflow: hidden;
  word-break: break-word;
}

.preview-panel {
  margin-top: 16px;
  padding-top: 16px;
  border-top: 1px solid #eef2f7;
  max-height: 520px;
  overflow: hidden;
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

.preview-grid {
  display: grid;
  grid-template-columns: 320px minmax(0, 1fr);
  gap: 16px;
  align-items: start;
  max-height: 100%;
  overflow: hidden;
}

.preview-grid-detail {
  display: grid;
  grid-template-columns: minmax(0, 1.2fr) minmax(0, 1fr);
  gap: 16px;
  align-items: start;
  max-height: 100%;
  overflow: hidden;
}

.preview-card {
  border: 1px solid #e8eef6;
  border-radius: 14px;
  background: linear-gradient(180deg, #fcfdff 0%, #f7faff 100%);
  padding: 16px;
  min-width: 0;
  min-height: 0;
}

.preview-card-selection {
  max-height: 460px;
  overflow: hidden;
}

.selected-preview-list {
  display: flex;
  flex-direction: column;
  gap: 12px;
  max-height: 390px;
  overflow-y: auto;
  padding-right: 6px;
}

.selected-preview-list::-webkit-scrollbar {
  width: 8px;
}

.selected-preview-list::-webkit-scrollbar-thumb {
  background: rgba(139, 152, 170, 0.45);
  border-radius: 999px;
}

.selected-preview-list::-webkit-scrollbar-track {
  background: transparent;
}

.selected-preview-item {
  width: 100%;
  display: flex;
  align-items: center;
  gap: 12px;
  padding: 10px;
  border: 1px solid #e8eef6;
  border-radius: 12px;
  background: #fff;
  cursor: pointer;
  transition: all 0.2s ease;
  text-align: left;
}

.selected-preview-remove {
  flex-shrink: 0;
  color: #ff4d4f;
  font-size: 12px;
  line-height: 1;
}

.selected-preview-remove:hover {
  color: #ff7875;
}

.selected-preview-item:hover {
  border-color: #b9d4ff;
  background: #f8fbff;
}

.selected-preview-item--active {
  border-color: #1677ff;
  background: rgba(22, 119, 255, 0.08);
  box-shadow: inset 0 0 0 1px rgba(22, 119, 255, 0.08);
}

.selected-preview-thumb {
  width: 56px;
  height: 56px;
  border-radius: 10px;
  object-fit: cover;
  flex-shrink: 0;
  background: #f3f6fa;
  border: 1px solid #e8eef6;
}

.selected-preview-thumb--placeholder {
  display: flex;
  align-items: center;
  justify-content: center;
  color: #8a94a6;
  font-size: 11px;
  font-weight: 700;
}

.selected-preview-meta {
  min-width: 0;
  flex: 1;
}

.selected-preview-name {
  color: #223046;
  font-weight: 600;
  line-height: 1.5;
  word-break: break-word;
}

.selected-preview-submeta {
  margin-top: 6px;
  display: flex;
  flex-direction: column;
  gap: 4px;
  color: #7a8699;
  font-size: 12px;
  word-break: break-word;
}

.preview-card-media {
  min-height: 0;
  max-height: 460px;
  display: flex;
  align-items: center;
  justify-content: center;
  overflow: auto;
}

.preview-media {
  width: 100%;
  max-height: 420px;
  border-radius: 12px;
  object-fit: contain;
  background: #f6f8fb;
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

.preview-card-detail {
  max-height: 460px;
  overflow-y: auto;
  padding-right: 12px;
  padding-bottom: 24px;
  scrollbar-gutter: stable;
}

.preview-card-detail .preview-field:last-child {
  padding-bottom: 8px;
}

:global(.material-picker-modal-wrap .ant-modal) {
  max-width: 1420px;
  top: 34px;
}

:global(.material-picker-modal-wrap .ant-modal-content) {
  border-radius: 18px;
  overflow: hidden;
}

:global(.material-picker-modal-wrap .ant-modal-header) {
  padding: 18px 22px 14px;
  border-bottom: 1px solid #eef2f7;
  background: linear-gradient(180deg, #ffffff 0%, #fbfcfe 100%);
}

:global(.material-picker-modal-wrap .ant-modal-title) {
  font-size: 18px;
  font-weight: 700;
  color: #1f2d3d;
}

:global(.material-picker-modal-wrap .ant-modal-body) {
  padding: 18px 20px 12px;
}

:global(.material-picker-modal-wrap .ant-modal-footer) {
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
