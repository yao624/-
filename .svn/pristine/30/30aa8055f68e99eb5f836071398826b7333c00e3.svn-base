<template>
  <div>
    <div class="folder-item" :class="{ active: selectedFolder === folder.id, 'is-child': level > 0 }"
      :style="{ paddingLeft: `${8 + level * 18}px` }" @click="emit('select', folder.id)">
      <span class="folder-toggle" @click.stop="emit('toggle', folder)">
        <right-outlined v-if="!folder.isExpanded" />
        <down-outlined v-else />
      </span>
      <span class="folder-icon">
        <folder-filled />
      </span>
      <span class="folder-title" :title="String(folder.name || '')">{{ folder.name }}</span>
      <div class="folder-right">
        <a-button v-if="folder.canDelete !== false" type="link" size="small" class="folder-delete"
          @click.stop="emit('delete', String(folder.id))">
          {{ t('删除') }}
        </a-button>
        <div v-if="getSubfolderCount(folder) !== null || getMaterialCount(folder) !== null" class="folder-metrics">
          <span v-if="getSubfolderCount(folder) !== null" class="folder-count metric-item">
            <folder-filled class="metric-icon" />
            <span class="metric-value">{{ getSubfolderCount(folder) }}</span>
          </span>
          <span v-if="getMaterialCount(folder) !== null" class="material-count metric-item">
            <file-image-outlined class="metric-icon" />
            <span class="metric-value">{{ getMaterialCount(folder) }}</span>
          </span>
        </div>
      </div>
    </div>

    <div v-if="folder.isExpanded" class="folder-children">
      <FolderTreeNode v-for="child in folder.children || []" :key="child.id" :folder="child"
        :selected-folder="selectedFolder" :level="level + 1" @select="emit('select', $event)"
        @toggle="emit('toggle', $event)" />
    </div>
  </div>
</template>

<script setup lang="ts">
import { DownOutlined, FileImageOutlined, FolderFilled, RightOutlined } from '@ant-design/icons-vue';
import { useI18n } from 'vue-i18n';

defineOptions({ name: 'FolderTreeNode' });
const { t } = useI18n();

const props = defineProps<{
  folder: any;
  selectedFolder: any;
  level?: number;
}>();

const level = props.level ?? 0;

const emit = defineEmits<{
  (e: 'select', folderId: string): void;
  (e: 'toggle', folder: any): void;
  (e: 'delete', folderId: string): void;
}>();

const getSubfolderCount = (folder: any): number | null => {
  const candidates = [
    folder?.subfolder_count,
    folder?.children_count,
    folder?.child_count,
  ];
  for (const value of candidates) {
    if (value === null || value === undefined || value === '') continue;
    const num = Number(value);
    if (Number.isFinite(num) && num >= 0) return num;
  }

  if (Array.isArray(folder?.children)) {
    return folder.children.length;
  }
  return null;
};

const getMaterialCount = (folder: any): number | null => {
  const candidates = [
    folder?.material_count,
    folder?.materials_count,
    folder?.count,
  ];
  for (const value of candidates) {
    if (value === null || value === undefined || value === '') continue;
    const num = Number(value);
    if (Number.isFinite(num) && num >= 0) return num;
  }
  return null;
};
</script>

<style lang="less" scoped>
.folder-item {
  min-height: 34px;
  padding: 6px 10px;
  cursor: pointer;
  display: flex;
  align-items: center;
  gap: 6px;
  border-radius: 3px;
  margin: 2px 0;
  color: #2b2f36;
  min-width: 0;

  &:hover {
    background: #f5f7fb;
  }

  &.active {
    background: #dbe8ff;
    color: #1d4ea3;
  }

  &.is-child {
    border-left: 1px dashed #d8dde8;
  }
}

.folder-metrics {
  display: inline-flex;
  align-items: center;
  gap: 6px;
  flex-shrink: 0;
}

.folder-count {
  color: #999;
  font-size: 12px;
}

.material-count {
  color: #b0b7c3;
  font-size: 12px;
}

.metric-item {
  display: inline-flex;
  align-items: center;
  gap: 2px;
}

.metric-icon {
  font-size: 11px;
}

.metric-value {
  min-width: 10px;
  text-align: right;
}

.folder-toggle {
  width: 14px;
  display: flex;
  align-items: center;
  justify-content: center;
  color: #5d6472;
  font-size: 11px;
}

.folder-icon {
  display: inline-flex;
  align-items: center;
  color: #d8a227;
  font-size: 14px;
}

.folder-title {
  flex: 1;
  font-size: 14px;
  min-width: 0;
  overflow: hidden;
  text-overflow: ellipsis;
  white-space: nowrap;
}

.folder-right {
  margin-left: auto;
  display: inline-flex;
  align-items: center;
  gap: 4px;
  flex-shrink: 0;
}

.folder-delete {
  padding: 0;
  display: none;
}

.folder-item:hover .folder-delete,
.folder-item.active .folder-delete {
  display: inline-flex;
}

.folder-children {
  margin-left: 10px;
}

@media (max-width: 1366px) {
  .metric-label {
    display: none;
  }

  .folder-metrics {
    gap: 4px;
  }
}
</style>
