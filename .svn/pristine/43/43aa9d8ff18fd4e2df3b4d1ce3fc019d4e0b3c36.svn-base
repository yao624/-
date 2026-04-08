<template>
  <a-modal
    v-model:open="modalOpen"
    :title="t('上传素材')"
    width="800px"
    :confirm-loading="uploading"
    :ok-button-props="{ disabled: !canCommit }"
    @ok="handleSubmit"
    @cancel="handleCancel"
  >
    <div class="upload-breadcrumb" v-if="breadcrumbItemsSafe.length">
      <span class="upload-breadcrumb-label">{{ t('位置') }}:</span>
      <a-space size="small" class="upload-breadcrumb-text">
        <template v-for="(item, idx) in breadcrumbItemsSafe" :key="`${item.folderId}-${idx}`">
          <span v-if="idx === breadcrumbItemsSafe.length - 1" class="upload-breadcrumb-current">{{ item.label }}</span>
          <a v-else class="upload-breadcrumb-link" @click.prevent="handleNavigateBreadcrumb(item.folderId)">{{ item.label }}</a>
          <span v-if="idx < breadcrumbItemsSafe.length - 1" class="upload-breadcrumb-sep">/</span>
        </template>
      </a-space>
    </div>
    <a-form :model="formData" layout="vertical">
      <!-- 素材类型 -->
      <a-form-item :label="t('素材类型')">
        <a-radio-group v-model:value="formData.materialType">
          <a-radio-button value="regular">{{ t('常规') }}</a-radio-button>
          <a-radio-button value="playable">{{ t('试玩素材') }}</a-radio-button>
        </a-radio-group>
      </a-form-item>

      <!-- 文件夹 -->
      <a-form-item :label="t('文件夹') + ' *'" required>
        <div class="folder-picker-panel">
          <div class="folder-picker-selected" @click="toggleFolderTreePanel">
            {{ selectedFolderLabel || t('请选择文件夹') }}
          </div>
          <a-input
            v-if="uiState.showFolderTreePanel"
            v-model:value="folderTreeKeyword"
            class="folder-tree-search"
            :placeholder="t('搜索文件夹')"
            allow-clear
          >
            <template #prefix>
              <search-outlined />
            </template>
          </a-input>
          <tree
            v-if="uiState.showFolderTreePanel"
            class="upload-folder-tree"
            :tree-data="filteredFolderTreeData"
            :selected-keys="selectedFolderTreeKeys"
            :expanded-keys="expandedFolderTreeKeys"
            :field-names="{ title: 'title', key: 'key', children: 'children' }"
            block-node
            @select="handleFolderTreeSelect"
            @expand="handleFolderTreeExpand"
          >
            <template #title="{ dataRef }">
              <div class="upload-folder-tree-title">
                <template v-if="dataRef?.isRootGroup">
                  <user-outlined v-if="dataRef?.rootType === 'my'" class="root-icon" />
                  <bank-outlined v-else-if="dataRef?.rootType === 'enterprise'" class="root-icon" />
                  <folder-filled v-else class="root-icon" />
                </template>
                <folder-filled v-else class="folder-icon" />
                <span :class="{ 'is-root-title': !!dataRef?.isRootGroup }">{{ dataRef?.title }}</span>
              </div>
            </template>
          </tree>
        </div>
      </a-form-item>

      <!-- 上传素材 -->
      <a-form-item :label="t('上传素材') + ' *'" required>
        <div class="upload-info">
          <span>{{ t('已上传素材数') }}: {{ uploadedFilesCount }} / {{ fileUploads.length }}</span>
          <a-checkbox v-model:checked="formData.filterDuplicate">
            {{ t('过滤重复素材') }}
          </a-checkbox>
        </div>
        <a-upload-dragger
          v-model:file-list="fileList"
          :multiple="true"
          :directory="uploadMode === 'folder'"
          :before-upload="beforeUpload"
          @change="handleFileChange"
          :accept="'image/*,video/*'"
          :disabled="isUploadingChunks"
        >
          <p class="ant-upload-drag-icon">
            <cloud-upload-outlined />
          </p>
          <p class="ant-upload-text">{{ uploadMode === 'folder' ? t('将文件夹拖到此处,或点击上传文件夹') : t('将素材拖到此处,或点击上传') }}</p>
        </a-upload-dragger>
        <div v-if="fileUploads.length" class="upload-progress-list">
          <div v-for="f in fileUploads" :key="f.fileKey" class="upload-progress-item">
            <div class="upload-progress-top">
              <span class="upload-progress-filename" :title="f.fileName">{{ f.fileName }}</span>
              <span class="upload-progress-percent">{{ f.progress }}%</span>
            </div>
            <a-progress
              :percent="f.progress"
              :status="f.status === 'error' ? 'exception' : f.status === 'done' ? 'success' : 'active'"
            />
            <div v-if="f.status === 'error'" class="upload-progress-error">
              {{ f.errorMessage || t('上传失败') }}
            </div>
          </div>
        </div>
        <div class="upload-tips">
          <span>{{ t('Tips') }}: </span>
          <span>{{ t('素材上传中,新开标签页进行其他操作。') }}</span>
          <a @click="openNewTab">{{ t('新开标签页') }}</a>
        </div>
      </a-form-item>

      <!-- 标签 -->
      <a-form-item :label="t('标签')">
        <div class="setting-mode">
          <a-radio-group v-model:value="formData.tagMode">
            <a-radio-button value="unified">{{ t('统一设置') }}</a-radio-button>
            <a-radio-button value="smart">{{ t('智能识别') }}</a-radio-button>
          </a-radio-group>
        </div>
        <a-select
          v-model:value="formData.tags"
          mode="multiple"
          :placeholder="t('请选择')"
          style="width: 100%; margin-top: 8px"
        >
          <a-select-option v-for="tag in tags" :key="tag.id" :value="tag.id">
            {{ tag.name }}
          </a-select-option>
        </a-select>
      </a-form-item>

      <!-- 设计师 -->
      <a-form-item :label="t('设计师')">
        <div class="setting-mode">
          <a-radio-group v-model:value="formData.designerMode">
            <a-radio-button value="unified">{{ t('统一设置') }}</a-radio-button>
            <a-radio-button value="smart">{{ t('智能识别') }}</a-radio-button>
          </a-radio-group>
        </div>
        <a-select
          v-model:value="formData.designer"
          show-search
          :placeholder="t('Q 请搜索')"
          :filter-option="filterOption"
          style="width: 100%; margin-top: 8px"
        >
          <a-select-option v-for="d in designers" :key="d.id" :value="d.id">
            {{ d.name }}
          </a-select-option>
        </a-select>
      </a-form-item>

      <!-- 创意人 -->
      <a-form-item :label="t('创意人')">
        <div class="setting-mode">
          <a-radio-group v-model:value="formData.creatorMode">
            <a-radio-button value="unified">{{ t('统一设置') }}</a-radio-button>
            <a-radio-button value="smart">{{ t('智能识别') }}</a-radio-button>
          </a-radio-group>
        </div>
        <a-select
          v-model:value="formData.creator"
          show-search
          :placeholder="t('Q 请搜索')"
          :filter-option="filterOption"
          style="width: 100%; margin-top: 8px"
        >
          <a-select-option v-for="c in creators" :key="c.id" :value="c.id">
            {{ c.name }}
          </a-select-option>
        </a-select>
      </a-form-item>

      <!-- 批量前缀 -->
      <a-form-item :label="t('批量前缀')">
        <a-input v-model:value="formData.batchPrefix" :placeholder="t('请输入批量前缀')" />
        <div class="prefix-buttons">
          <a-button size="small" @click="addPrefix('batch')">{{ t('素材批次序号') }}</a-button>
          <a-button size="small" @click="addPrefix('date')">{{ t('日期') }}</a-button>
          <a-button size="small" @click="addPrefix('time')">{{ t('时间') }}</a-button>
        </div>
      </a-form-item>

      <!-- 素材组 -->
      <a-form-item :label="t('素材组')">
        <a-select
          v-model:value="formData.materialGroupIds"
          mode="multiple"
          :placeholder="t('请选择')"
          show-search
          :filter-option="false"
          :max-tag-count="3"
          :list-height="320"
          :virtual="true"
          :loading="groupLoading"
          @search="handleGroupSearch"
          @inputKeydown="handleGroupInputKeydown"
          :open="groupSelectOpen"
          @click="handleGroupSelectClick"
          @dropdownVisibleChange="handleGroupDropdownVisibleChange"
          style="width: 100%"
        >
          <template #dropdownRender="{ menuNode: menu }">
            <div class="group-create">
              <div class="group-create-title">
                <span>{{ t('pages.add') }}{{ t('素材组') }}</span>
                <a-button
                  size="small"
                  type="link"
                  class="group-create-clear"
                  @click="handleGroupClearSearch"
                  @mousedown.stop="groupSelectInteracting = true"
                >
                  {{ t('清空搜索') }}
                </a-button>
              </div>
              <a-input
                ref="groupCreateInputRef"
                v-model:value="groupCreateName"
                :placeholder="t('输入新素材组名称')"
                size="small"
                :maxlength="100"
                @pressEnter="handleCreateGroup"
                @mousedown.stop="groupSelectInteracting = true"
                @click.stop
              />
              <a-button
                type="primary"
                size="small"
                class="group-create-btn"
                :disabled="!canCreateGroup"
                :loading="createGroupLoading"
                @click="handleCreateGroup"
                @mousedown.stop="groupSelectInteracting = true"
              >
                {{ t('pages.add') }}
              </a-button>
              <div v-if="groupCreateName.trim()" class="group-create-hint">
                {{ t('回车可快速创建并选中') }}
              </div>
            </div>
            <a-divider style="margin: 4px 0" />
            <v-nodes :vnodes="menu" />
          </template>

          <a-select-option v-for="group in materialGroups" :key="group.id" :value="group.id">
            {{ group.name }}
          </a-select-option>
        </a-select>
      </a-form-item>
    </a-form>
  </a-modal>
</template>

<script lang="ts" setup>
import { ref, watch, computed, nextTick, reactive } from 'vue';
import { useI18n } from 'vue-i18n';
import { message, Tree } from 'ant-design-vue';
import {
  CloudUploadOutlined,
  SearchOutlined,
  FolderFilled,
  UserOutlined,
  BankOutlined,
} from '@ant-design/icons-vue';
import { useUserStore } from '@/store/user';
import {
  getMaterialLibraryFolders,
  getMaterialLibraryFolderChildren,
} from '@/api/material-library/folders';
import {
  getMaterialLibraryTags,
  getMaterialLibraryDesigners,
  getMaterialLibraryCreators,
  getMaterialLibraryMaterialGroups,
  createMaterialLibraryMaterialGroup,
} from '@/api/material-library/options';
import {
  uploadTempSessionInit,
  uploadTempSessionChunk,
  uploadTempSessionFilesStatus,
  uploadTempSessionCommit,
  deleteUploadTempSession,
} from '@/api/material-library/materials';

interface Props {
  open: boolean;
  folderId?: string;
  folderName?: string;
  breadcrumb?: string;
  breadcrumbItems?: Array<{ label: string; folderId: any }>;
  uploadMode?: 'file' | 'folder';
}

interface Emits {
  (e: 'update:open', value: boolean): void;
  (e: 'success'): void;
  (e: 'navigate-breadcrumb', folderId: any): void;
}

const props = defineProps<Props>();
const emit = defineEmits<Emits>();

const { t } = useI18n();
const userStore = useUserStore();

const modalOpen = computed({
  get: () => props.open,
  set: (value) => emit('update:open', value),
});

const uploadMode = computed(() => props.uploadMode || 'file');
const breadcrumbItemsSafe = computed(() => {
  if (Array.isArray(props.breadcrumbItems) && props.breadcrumbItems.length > 0) return props.breadcrumbItems;
  const text = String(props.breadcrumb || '').trim();
  if (!text) return [];
  return text.split('/').map((x) => ({ label: String(x).trim(), folderId: undefined })).filter((x) => x.label);
});

const handleNavigateBreadcrumb = (folderId: any) => {
  if (folderId === null || folderId === undefined || folderId === '') return;
  emit('navigate-breadcrumb', folderId);
};

const normalizeFolderId = (id: string | number | null | undefined, _options: any[]): string | undefined => {
  if (id === null || id === undefined || id === '') return undefined;
  return String(id);
};

// 表单数据
const formData = ref({
  materialType: 'regular',
  folderId: props.folderId ? String(props.folderId) : undefined as string | undefined,
  filterDuplicate: true,
  tagMode: 'unified',
  tags: [],
  designerMode: 'unified',
  designer: undefined,
  creatorMode: 'unified',
  creator: undefined,
  batchPrefix: '',
  materialGroupIds: [] as Array<string | number>,
});

// 文件列表
const fileList = ref<any[]>([]);
type UploadFileItem = {
  fileKey: string;
  fileIndex: number;
  fileName: string;
  fileSize: number;
  relativePath: string | null;
  chunkTotal: number;
  receivedCount: number;
  progress: number;
  status: 'pending' | 'uploading' | 'done' | 'error';
  errorMessage?: string;
  rawFile: File;
};

const fileUploads = ref<UploadFileItem[]>([]);
const uploading = ref(false); // commit loading
const sessionState = reactive({
  sessionId: null as string | null,
  sessionCode: null as string | null,
  uploadingChunks: false,
  cancelRequested: false,
  committed: false,
  resumeKey: null as string | null,
});

const isUploadingChunks = computed(() => sessionState.uploadingChunks);
const uploadedFilesCount = computed(() => fileUploads.value.filter((f) => f.status === 'done').length);
const canCommit = computed(() => {
  if (!sessionState.sessionId) return false;
  if (fileUploads.value.length === 0) return false;
  if (uploading.value) return false;
  return fileUploads.value.every((f) => f.status === 'done');
});

// 文件夹
const folders = ref<any[]>([]);
const folderTreeData = ref<any[]>([]);
const folderTreeKeyword = ref('');
const selectedFolderTreeKeys = ref<string[]>([]);
const expandedFolderTreeKeys = ref<Array<string | number>>([]);
const uiState = reactive({
  showFolderTreePanel: false,
});
const MAX_UPLOAD_COUNT = 100;
const MAX_VIDEO_COUNT = 30;

const getFolderDisplayName = (folder: any): string => {
  const raw = String(folder?.name || folder?.folder_name || folder?.folder_path || '').trim();
  if (!raw) return '';
  const parts = raw.split('/').filter(Boolean);
  return parts[parts.length - 1] || raw;
};

// 标签、设计师、创意人
const tags = ref<any[]>([]);
const designers = ref<any[]>([]);
const creators = ref<any[]>([]);

// 素材组
const materialGroups = ref<any[]>([]);
const groupKeyword = ref('');
let groupSearchTimer: ReturnType<typeof setTimeout> | null = null;
const groupLoading = ref(false);

// 素材组下拉：新建入口
const groupSelectOpen = ref(false);
const groupSelectInteracting = ref(false);
const groupCreateName = ref('');
const createGroupLoading = ref(false);
const groupCreateInputRef = ref<any>();
const VNodes = (_: any, { attrs }: any) => attrs.vnodes;

const canCreateGroupByName = (nameRaw: string) => {
  const name = String(nameRaw || '').trim();
  if (!name) return false;
  return !(materialGroups.value || []).some((g: any) => String(g?.name || '') === name);
};

const canCreateGroup = computed(() => canCreateGroupByName(groupCreateName.value));

// 监听文件夹ID变化
watch(
  () => props.folderId,
  (newVal) => {
    if (newVal) {
      formData.value.folderId = normalizeFolderId(newVal, folders.value);
    }
  },
  { immediate: true },
);

watch(
  () => formData.value.folderId,
  (val) => {
    const id = String(val || '').trim();
    selectedFolderTreeKeys.value = id ? [id] : [];
  },
  { immediate: true },
);

watch(
  [() => props.folderId, () => props.folderName],
  ([folderId, folderName]) => {
    if (!folderId) return;
    const id = String(folderId);
    const exists = (folders.value || []).some((f: any) => String(f?.id) === id);
    if (exists) return;
    const fallbackName = String(folderName || id);
    folders.value = [
      {
        id,
        name: fallbackName,
        displayName: fallbackName,
      },
      ...(folders.value || []),
    ];
  },
  { immediate: true },
);

watch(
  folderTreeKeyword,
  (keyword) => {
    const q = String(keyword || '').trim();
    if (q) {
      expandedFolderTreeKeys.value = collectNodeKeys(filteredFolderTreeData.value || []);
      return;
    }
    expandedFolderTreeKeys.value = ['__root_my__', '__root_enterprise__', '__root_department__'];
  },
  { immediate: false },
);

const buildTreeNode = (folder: any): any => {
  const id = String(folder?.id ?? '');
  return {
    title: getFolderDisplayName(folder),
    value: id,
    key: id,
    class: folder?.isRootGroup ? 'upload-folder-tree-root' : '',
    children: (folder?.children || []).map((c: any) => buildTreeNode(c)),
  };
};

const findTreeNodeByKey = (nodes: any[], key: string): any | null => {
  for (const node of nodes || []) {
    if (String(node?.key) === key || String(node?.value) === key) return node;
    const found = findTreeNodeByKey(node?.children || [], key);
    if (found) return found;
  }
  return null;
};

const selectedFolderLabel = computed(() => {
  const id = String(formData.value.folderId || '').trim();
  if (!id) return '';

  const treeNode = findTreeNodeByKey(folderTreeData.value || [], id);
  if (treeNode?.title) return String(treeNode.title);

  const found = (folders.value || []).find((f: any) => String(f?.id) === id);
  return String(found?.displayName || found?.name || id);
});

const filterFolderNodes = (nodes: any[], keyword: string): any[] => {
  const q = String(keyword || '').trim().toLowerCase();
  if (!q) return nodes;
  const out: any[] = [];
  (nodes || []).forEach((node: any) => {
    const children = filterFolderNodes(node?.children || [], q);
    const label = String(node?.title || '').toLowerCase();
    if (label.includes(q) || children.length > 0) {
      out.push({
        ...node,
        children,
      });
    }
  });
  return out;
};

const collectNodeKeys = (nodes: any[]): Array<string | number> => {
  const keys: Array<string | number> = [];
  const walk = (arr: any[]) => {
    (arr || []).forEach((n: any) => {
      if (n?.key !== undefined && n?.key !== null) keys.push(n.key);
      walk(n?.children || []);
    });
  };
  walk(nodes || []);
  return keys;
};

const filteredFolderTreeData = computed(() => {
  return filterFolderNodes(folderTreeData.value || [], folderTreeKeyword.value);
});

const handleFolderTreeSelect = (selectedKeys: Array<string | number>, e: any) => {
  const node = e?.node?.dataRef || e?.node || {};
  if (node?.isRootGroup) return;
  const id = selectedKeys?.[0] !== undefined ? String(selectedKeys[0]) : '';
  formData.value.folderId = id || undefined;
  selectedFolderTreeKeys.value = id ? [id] : [];
  uiState.showFolderTreePanel = false;
};

const handleFolderTreeExpand = (keys: Array<string | number>) => {
  expandedFolderTreeKeys.value = keys;
};

const toggleFolderTreePanel = () => {
  uiState.showFolderTreePanel = !uiState.showFolderTreePanel;
};

const normalizeFolderNode = (folder: any) => {
  return {
    ...folder,
    children: Array.isArray(folder?.children) ? folder.children : [],
  };
};

const loadFolderChildrenRecursively = async (folder: any, fallback: { owner_id?: any; library_type?: any }) => {
  const current = normalizeFolderNode(folder);
  let children = Array.isArray(current.children) ? current.children : [];

  if (!children.length && current?.id !== undefined && current?.id !== null && current?.id !== '') {
    try {
      const res = await getMaterialLibraryFolderChildren(current.id, {
        owner_id: current?.owner_id ?? fallback.owner_id,
        library_type: current?.library_type ?? fallback.library_type,
      } as any);
      children = (res?.data || []).map((x: any) => normalizeFolderNode(x));
    } catch {
      children = [];
    }
  } else {
    children = children.map((x: any) => normalizeFolderNode(x));
  }

  current.children = children;
  if (children.length) {
    current.children = await Promise.all(
      children.map((c: any) =>
        loadFolderChildrenRecursively(c, {
          owner_id: current?.owner_id ?? fallback.owner_id,
          library_type: current?.library_type ?? fallback.library_type,
        }),
      ),
    );
  }
  return current;
};

// 加载数据
const loadData = async () => {
  try {
    const userId = userStore?.userInfo?.id;
    const enterpriseId = userStore?.userInfo?.enterpriseId ?? userStore?.userInfo?.enterprise_id;
    const departmentId = userStore?.userInfo?.departmentId ?? userStore?.userInfo?.department_id;
    const myParams: Record<string, any> = { library_type: 0 };
    if (userId !== undefined && userId !== null && userId !== '') myParams.owner_id = userId;

    const enterpriseParams: Record<string, any> = { library_type: 1 };
    if (enterpriseId !== undefined && enterpriseId !== null && enterpriseId !== '') enterpriseParams.owner_id = enterpriseId;

    const departmentParams: Record<string, any> = { library_type: 0 };
    if (departmentId !== undefined && departmentId !== null && departmentId !== '') departmentParams.owner_id = departmentId;

    const [myRes, enterpriseRes, departmentRes, tRes, dRes, cRes] = await Promise.all([
      getMaterialLibraryFolders(myParams as any),
      getMaterialLibraryFolders(enterpriseParams as any),
      departmentId ? getMaterialLibraryFolders(departmentParams as any) : Promise.resolve({ data: [], totalCount: 0 }),
      getMaterialLibraryTags(),
      getMaterialLibraryDesigners(),
      getMaterialLibraryCreators(),
    ]);

    const rawMyFolders = (myRes.data || []).map((f: any) => normalizeFolderNode(f));
    const rawEnterpriseFolders = (enterpriseRes.data || []).map((f: any) => normalizeFolderNode(f));
    const rawDepartmentFolders = (departmentRes.data || []).map((f: any) => normalizeFolderNode(f));

    const mergedMyFolders = await Promise.all(
      rawMyFolders.map((f: any) =>
        loadFolderChildrenRecursively(f, {
          owner_id: f?.owner_id ?? userId,
          library_type: f?.library_type ?? 0,
        }),
      ),
    );
    const fullEnterpriseFolders = await Promise.all(
      rawEnterpriseFolders.map((f: any) =>
        loadFolderChildrenRecursively(f, {
          owner_id: f?.owner_id ?? enterpriseId,
          library_type: f?.library_type ?? 1,
        }),
      ),
    );
    const fullDepartmentFolders = await Promise.all(
      rawDepartmentFolders.map((f: any) =>
        loadFolderChildrenRecursively(f, {
          owner_id: f?.owner_id ?? departmentId,
          library_type: f?.library_type ?? 0,
        }),
      ),
    );

    const mergedFolders = [...mergedMyFolders, ...fullEnterpriseFolders, ...fullDepartmentFolders];
    const uniqueFolders: any[] = [];
    const seen = new Set<string>();
    mergedFolders.forEach((f: any) => {
      const key = String(f?.id ?? '');
      if (!key || seen.has(key)) return;
      seen.add(key);
      uniqueFolders.push(f);
    });

    folders.value = uniqueFolders.map((f: any) => ({
      ...f,
      displayName: getFolderDisplayName(f),
    }));

    const myTree = mergedMyFolders.map((f: any) => buildTreeNode(f));
    const enterpriseTree = fullEnterpriseFolders.map((f: any) => buildTreeNode(f));
    const departmentTree = fullDepartmentFolders.map((f: any) => buildTreeNode(f));
    folderTreeData.value = [
      {
        title: t('我的素材库'),
        value: '__root_my__',
        key: '__root_my__',
        isRootGroup: true,
        rootType: 'my',
        selectable: false,
        disabled: true,
        disableCheckbox: true,
        children: myTree,
      },
      {
        title: t('企业素材库'),
        value: '__root_enterprise__',
        key: '__root_enterprise__',
        isRootGroup: true,
        rootType: 'enterprise',
        selectable: false,
        disabled: true,
        disableCheckbox: true,
        children: enterpriseTree,
      },
      {
        title: t('我的部门'),
        value: '__root_department__',
        key: '__root_department__',
        isRootGroup: true,
        rootType: 'department',
        selectable: false,
        disabled: true,
        disableCheckbox: true,
        children: departmentTree,
      },
    ];
    expandedFolderTreeKeys.value = ['__root_my__', '__root_enterprise__', '__root_department__'];

    formData.value.folderId = normalizeFolderId(formData.value.folderId, folders.value);
    tags.value = tRes.data || [];
    designers.value = dRes.data || [];
    creators.value = cRes.data || [];
    await loadMaterialGroups(groupKeyword.value);
  } catch (error) {
    console.error('加载数据失败:', error);
  }
};

const loadMaterialGroups = async (keyword = '') => {
  groupLoading.value = true;
  try {
    const res = await getMaterialLibraryMaterialGroups(keyword ? { search: keyword } : undefined);
    materialGroups.value = res.data || [];
  } catch (error) {
    console.error('加载素材组失败:', error);
  } finally {
    groupLoading.value = false;
  }
};

const handleGroupSearch = (value: string) => {
  groupKeyword.value = value;
  // 让“搜索词”和“新建名”联动：用户搜什么，回车创建就创建什么
  groupCreateName.value = value;
  if (groupSearchTimer) clearTimeout(groupSearchTimer);
  groupSearchTimer = setTimeout(() => {
    loadMaterialGroups(value);
  }, 300);
};

watch(groupSelectOpen, (open) => {
  if (!open) return;
  if (!String(groupCreateName.value || '').trim()) {
    groupCreateName.value = groupKeyword.value ? String(groupKeyword.value) : '';
  }
  // 打开下拉时自动聚焦新建输入框，减少一次点击
  nextTick(() => {
    try {
      groupCreateInputRef.value?.focus?.();
    } catch {
      // ignore
    }
  });
});

const handleGroupDropdownVisibleChange = (open: boolean) => {
  // 当用户在下拉自定义区域里操作（输入/创建）时，Select 内部可能触发关闭。
  // 这里阻止下拉在“自定义交互”期间关闭，确保输入框可正常输入。
  if (open) {
    groupSelectOpen.value = true;
    loadMaterialGroups(groupKeyword.value);
    return;
  }

  if (groupSelectInteracting.value) {
    groupSelectOpen.value = true;
    // 下一轮事件循环后再释放标记，避免影响正常关闭
    setTimeout(() => {
      groupSelectInteracting.value = false;
    }, 0);
    return;
  }

  groupSelectOpen.value = false;
};

const handleGroupSelectClick = () => {
  groupSelectOpen.value = true;
  loadMaterialGroups(groupKeyword.value);
};

const handleCreateGroup = async () => {
  const name = String(groupCreateName.value || '').trim();
  if (!name) return;
  if (!canCreateGroup.value) return;

  groupSelectInteracting.value = true;

  createGroupLoading.value = true;
  try {
    const res = await createMaterialLibraryMaterialGroup({ name });
    const id = res?.data?.id ?? res?.data?.groupId;
    if (!id) throw new Error('createMaterialLibraryMaterialGroup: missing id');

    // 更新下拉列表（让新建项立刻可选）
    if (!(materialGroups.value || []).some((g: any) => String(g?.id || '') === String(id))) {
      materialGroups.value = [
        {
          id: String(id),
          name: res?.data?.name ?? name,
          description: res?.data?.description ?? null,
          createTime: res?.data?.createTime ?? null,
        },
        ...(materialGroups.value || []),
      ];
    }

    const idStr = String(id);
    if (!formData.value.materialGroupIds.includes(idStr)) {
      formData.value.materialGroupIds = [...formData.value.materialGroupIds, idStr];
    }

    // 创建成功后清空搜索条件，避免下拉列表仅显示新建项
    groupKeyword.value = '';
    groupCreateName.value = '';
    message.success(t('pages.op.successfully'));

    // 刷新全量列表：同时保留已自动选中的新建项
    await loadMaterialGroups('');

    // 保持下拉打开，便于继续多选
    groupSelectOpen.value = true;
  } catch (e) {
    console.error('创建素材组失败:', e);
    message.error(t('pages.op.failed'));
  } finally {
    createGroupLoading.value = false;
  }
};

const handleGroupInputKeydown = (e: KeyboardEvent) => {
  // 让用户“在搜索框里按 Enter 直接创建”
  if (e.key !== 'Enter') return;
  const keyword = String(groupKeyword.value || '').trim();
  if (!keyword) return;
  if (!canCreateGroupByName(keyword)) return;

  e.preventDefault();
  groupCreateName.value = keyword;
  // 交给统一创建逻辑：创建 + 自动选中 + 保持下拉打开
  handleCreateGroup();
};

const handleGroupClearSearch = () => {
  groupSelectInteracting.value = true;
  groupKeyword.value = '';
  groupCreateName.value = '';
  loadMaterialGroups('');
  // 清空后继续保持下拉打开，且把焦点给输入框
  groupSelectOpen.value = true;
  nextTick(() => {
    try {
      groupCreateInputRef.value?.focus?.();
    } catch {
      // ignore
    }
  });
};

// 通用下拉过滤（用于标签/设计师/创意人/素材组）
const filterOption = (input: string, option: any) => {
  const text = String(option?.children?.[0]?.children ?? option?.children ?? '').toLowerCase();
  return text.includes(input.toLowerCase());
};

const isVideoFile = (file: any): boolean => {
  const f = file?.originFileObj || file;
  const type = String(f?.type || '').toLowerCase();
  if (type.startsWith('video/')) return true;
  const name = String(f?.name || file?.name || '').toLowerCase();
  return ['.mp4', '.mov', '.webm', '.m4v', '.avi', '.mkv'].some((ext) => name.endsWith(ext));
};

const getUploadRawFile = (item: any): File | null => {
  const candidate = item?.originFileObj || item?.file || item;
  if (candidate instanceof File) return candidate;
  return null;
};

// 文件上传前
const beforeUpload = (_file: File) => {
  // 可以在这里添加文件验证逻辑
  return false; // 阻止自动上传
};

// 文件变化
const CHUNK_SIZE = 2 * 1024 * 1024; // 2MB：平衡上传速度与断点续传开销
const MAX_CONCURRENT_FILES = 3;
const MAX_CHUNK_RETRIES = 5;
const RETRY_BASE_DELAY_MS = 800;
const RETRY_MAX_DELAY_MS = 6000;

const calcChunkTotal = (size: number) => {
  const s = Number.isFinite(size) ? size : 0;
  if (s <= 0) return 1;
  return Math.max(1, Math.ceil(s / CHUNK_SIZE));
};

const makeFileKey = (rawFile: File, relativePath: string | null) => {
  const size = Number(rawFile.size || 0);
  const lastModified = Number((rawFile as any).lastModified || 0);
  if (relativePath) {
    return `p:${relativePath}|s:${size}|t:${lastModified}`;
  }
  return `f:${rawFile.name}|s:${size}|t:${lastModified}`;
};

const validateAndGetFolderIdOrError = async (): Promise<string | null> => {
  const folderIdRaw = String(formData.value.folderId || '');
  if (!folderIdRaw) return null;

  const mappedFolderId = normalizeFolderId(folderIdRaw, folders.value);
  const effectiveFolderId = String(mappedFolderId || folderIdRaw);

  if (effectiveFolderId === 'favorites') {
    message.error(t('收藏视图下不支持上传'));
    return null;
  }

  return effectiveFolderId;
};

const resetUploadState = () => {
  sessionState.sessionId = null;
  sessionState.sessionCode = null;
  sessionState.uploadingChunks = false;
  sessionState.committed = false;
  sessionState.resumeKey = null;
  fileList.value = [];
  fileUploads.value = [];
};

const simpleHash = (input: string) => {
  // FNV-1a 32bit：足够用于生成本地断点续传 key
  let h = 2166136261;
  for (let i = 0; i < input.length; i++) {
    h ^= input.charCodeAt(i);
    h = Math.imul(h, 16777619);
  }
  return (h >>> 0).toString(16);
};

const computeResumeKey = (normalizedFolderId: string) => {
  const tagIds =
    formData.value.tagMode === 'unified' ? (formData.value.tags || []).map((id: any) => String(id)).sort().join(',') : '';

  const designerId =
    formData.value.designerMode === 'unified' && formData.value.designer ? String(formData.value.designer) : '';
  const creatorId = formData.value.creatorMode === 'unified' && formData.value.creator ? String(formData.value.creator) : '';

  const groupIds = (formData.value.materialGroupIds || []).map((id: any) => String(id)).sort().join(',');
  const fileKeys = fileUploads.value.map((f) => f.fileKey).join(',');

  const fingerprint = [
    normalizedFolderId,
    uploadMode.value,
    String(formData.value.materialType || ''),
    String(formData.value.tagMode || ''),
    tagIds,
    String(formData.value.designerMode || ''),
    designerId,
    String(formData.value.creatorMode || ''),
    creatorId,
    String(formData.value.batchPrefix || ''),
    groupIds,
    fileKeys,
  ].join('::');

  return `xmp_upload_temp_resume_v1:${simpleHash(fingerprint)}`;
};

const buildFilesManifest = () => {
  const rawFiles = (fileList.value || [])
    .map((item: any) => getUploadRawFile(item))
    .filter((f: File | null): f is File => !!f);

  const uploadModeValue = uploadMode.value;
  return rawFiles.map((rawFile, idx) => {
    const relativePathRaw = uploadModeValue === 'folder' ? String((rawFile as any).webkitRelativePath || '') : '';
    const relativePath = relativePathRaw ? relativePathRaw : null;
    const fileKey = makeFileKey(rawFile, relativePath);
    const chunkTotal = calcChunkTotal(rawFile.size || 0);
    return {
      file_key: fileKey,
      file_name: rawFile.name,
      file_size: rawFile.size || 0,
      file_index: idx,
      chunk_total: chunkTotal,
      relative_path: uploadModeValue === 'folder' ? relativePath : null,
    };
  });
};

const uploadChunkForFile = async (fileItem: UploadFileItem, chunkIndex: number) => {
  if (sessionState.cancelRequested || !sessionState.sessionId) {
    throw new Error('upload cancelled');
  }
  const start = chunkIndex * CHUNK_SIZE;
  const end = Math.min(fileItem.fileSize, start + CHUNK_SIZE);
  const blob = fileItem.rawFile.slice(start, end);

  const form = new FormData();
  form.append('file_key', fileItem.fileKey);
  form.append('chunk_index', String(chunkIndex));
  form.append('chunk_total', String(fileItem.chunkTotal));
  // 给后端一个文件名，便于抓取扩展名/调试（不依赖）
  form.append('chunk', blob, fileItem.fileName);

  let lastErr: any = null;
  for (let attempt = 1; attempt <= MAX_CHUNK_RETRIES; attempt++) {
    if (sessionState.cancelRequested || !sessionState.sessionId) {
      throw new Error('upload cancelled');
    }
    try {
      await uploadTempSessionChunk(sessionState.sessionId, form);
      return;
    } catch (e: any) {
      lastErr = e;
      if (attempt >= MAX_CHUNK_RETRIES) break;
      const delay = Math.min(RETRY_MAX_DELAY_MS, RETRY_BASE_DELAY_MS * Math.pow(2, attempt - 1));
      await new Promise((r) => setTimeout(r, delay));
    }
  }
  throw lastErr;
};

const startChunkUploads = async (sessionId: string) => {
  sessionState.sessionId = sessionId;
  sessionState.uploadingChunks = true;

  // 拉取已存在的 chunk，支持断点续传
  const statusRes = await uploadTempSessionFilesStatus(sessionId);
  const serverFiles: any[] = statusRes?.data?.files || [];
  const receivedMap = new Map<string, Set<number>>();
  serverFiles.forEach((sf: any) => {
    const indices: number[] = Array.isArray(sf?.received_chunk_indices) ? sf.received_chunk_indices : [];
    receivedMap.set(String(sf.file_key), new Set(indices.map((x) => Number(x))));
  });

  // 根据服务端状态初始化进度条
  for (const item of fileUploads.value) {
    const set = receivedMap.get(item.fileKey) || new Set<number>();
    item.receivedCount = set.size;
    item.progress = Math.min(100, Math.floor((item.receivedCount / item.chunkTotal) * 100));
    if (item.receivedCount >= item.chunkTotal) {
      item.status = 'done';
    } else if (item.receivedCount > 0) {
      item.status = 'uploading';
    } else {
      item.status = 'pending';
    }
  }

  const uploadOneFile = async (item: UploadFileItem) => {
    const receivedSet = receivedMap.get(item.fileKey) || new Set<number>();
    // 重新确保引用一致：receivedMap 保存的 Set 在此阶段可能已创建
    receivedMap.set(item.fileKey, receivedSet);

    for (let ci = 0; ci < item.chunkTotal; ci++) {
      if (sessionState.cancelRequested) return;
      if (receivedSet.has(ci)) continue;

      try {
        item.status = 'uploading';
        await uploadChunkForFile(item, ci);
        if (sessionState.cancelRequested) return;
        receivedSet.add(ci);
        item.receivedCount = receivedSet.size;
        item.progress = Math.min(100, Math.floor((item.receivedCount / item.chunkTotal) * 100));
      } catch (e: any) {
        if (sessionState.cancelRequested) return;
        item.status = 'error';
        item.errorMessage = e?.message || t('上传失败');
        return;
      }
    }

    if (item.receivedCount >= item.chunkTotal) {
      item.status = 'done';
      item.progress = 100;
    }
  };

  // 并发限制：按“文件”并发上传，chunk 在单文件内顺序执行
  let cursor = 0;
  const workers = Array.from({ length: Math.min(MAX_CONCURRENT_FILES, fileUploads.value.length) }, async () => {
    while (cursor < fileUploads.value.length) {
      const myIndex = cursor++;
      const item = fileUploads.value[myIndex];
      if (!item) return;
      await uploadOneFile(item);
    }
  });

  await Promise.all(workers);
  sessionState.uploadingChunks = false;
};

const ensureSessionInitAndStart = async () => {
  const folderId = await validateAndGetFolderIdOrError();
  if (!folderId) return;

  if (fileUploads.value.length === 0) return;

  // 初始化前先清理上一次会话（如果还在本弹窗生命周期里）
  if (sessionState.sessionId) {
    try {
      await deleteUploadTempSession(sessionState.sessionId);
    } catch {
      // ignore
    }
  }

  const groupIds = (formData.value.materialGroupIds || []).map((id: any) => String(id));

  const payload: Record<string, any> = {
    folder_id: folderId,
    upload_mode: uploadMode.value,
    material_type: formData.value.materialType === 'playable' ? 'playable' : 'regular',
    tag_mode: formData.value.tagMode,
    designer_mode: formData.value.designerMode,
    creator_mode: formData.value.creatorMode,
    batch_prefix: formData.value.batchPrefix || '',
    files_manifest: buildFilesManifest(),
  };

  if (formData.value.tagMode === 'unified') {
    payload.tag_ids = (formData.value.tags || []).map((id: any) => String(id));
  }
  if (formData.value.designerMode === 'unified' && formData.value.designer) {
    payload.designer_id = String(formData.value.designer);
  }
  if (formData.value.creatorMode === 'unified' && formData.value.creator) {
    payload.creator_id = String(formData.value.creator);
  }
  if (groupIds.length > 0) {
    payload.material_group_ids = groupIds;
    payload.material_group_id = groupIds[0];
  }

  const initRes = await uploadTempSessionInit(payload);
  const newSessionId = String(initRes?.data?.session_id || '');
  const newSessionCode = String(initRes?.data?.session_code || '');
  if (!newSessionId) throw new Error('uploadTempSessionInit: missing session_id');

  sessionState.sessionId = newSessionId;
  sessionState.sessionCode = newSessionCode;

  if (sessionState.resumeKey) {
    try {
      window.localStorage.setItem(
        sessionState.resumeKey,
        JSON.stringify({
          sessionId: newSessionId,
          sessionCode: newSessionCode,
          files: fileUploads.value.map((f) => ({ fileKey: f.fileKey, chunkTotal: f.chunkTotal })),
        }),
      );
    } catch {
      // ignore：localStorage 可能禁用
    }
  }

  await startChunkUploads(newSessionId);
};

const handleFileChange = async (info: any) => {
  if (sessionState.uploadingChunks) {
    message.warning(t('正在上传中，请稍后再选择文件'));
    return;
  }

  const inputFiles = info.fileList || [];
  const next = inputFiles.slice(0, MAX_UPLOAD_COUNT);
  if (inputFiles.length > MAX_UPLOAD_COUNT) {
    message.warning(t(`单次最多上传 ${MAX_UPLOAD_COUNT} 条素材`));
  }

  fileList.value = next;
  sessionState.cancelRequested = false;
  sessionState.committed = false;

  const rawFiles = (fileList.value || []).map((item: any) => getUploadRawFile(item)).filter((f: File | null): f is File => !!f);
  const videoCount = rawFiles.filter((f) => isVideoFile(f)).length;
  if (videoCount > MAX_VIDEO_COUNT) {
    message.error(t(`单次最多上传 ${MAX_VIDEO_COUNT} 条视频素材`));
    return;
  }

  // 生成 fileUploads：用于进度条展示与 chunk 上传
  const uploadModeValue = uploadMode.value;
  fileUploads.value = rawFiles.map((rawFile, idx) => {
    const relativePathRaw = uploadModeValue === 'folder' ? String((rawFile as any).webkitRelativePath || '') : '';
    const relativePath = relativePathRaw ? relativePathRaw : null;
    const fileKey = makeFileKey(rawFile, relativePath);
    const chunkTotal = calcChunkTotal(rawFile.size || 0);
    return {
      fileKey,
      fileIndex: idx,
      fileName: rawFile.name,
      fileSize: rawFile.size || 0,
      relativePath,
      chunkTotal,
      receivedCount: 0,
      progress: 0,
      status: 'pending',
      errorMessage: undefined,
      rawFile,
    };
  });

  const folderId = await validateAndGetFolderIdOrError();
  if (!folderId) return;

  sessionState.resumeKey = computeResumeKey(folderId);
  const resumeRaw = window.localStorage.getItem(sessionState.resumeKey);
  if (resumeRaw) {
    try {
      const saved = JSON.parse(resumeRaw);
      const savedFiles: Array<any> = Array.isArray(saved?.files) ? saved.files : [];
      const match =
        savedFiles.length === fileUploads.value.length &&
        savedFiles.every((sf: any, idx: number) => {
          const item = fileUploads.value[idx];
          return String(sf?.fileKey || '') === item.fileKey && Number(sf?.chunkTotal || 0) === item.chunkTotal;
        });

      if (match && saved?.sessionId) {
        sessionState.sessionId = String(saved.sessionId);
        sessionState.sessionCode = String(saved?.sessionCode || '');
        try {
          await startChunkUploads(String(saved.sessionId));
          return;
        } catch (err) {
          // session 可能已过期/已被删除：清理 resume key 后重新 init
          window.localStorage.removeItem(sessionState.resumeKey as string);
        }
      }
    } catch {
      // ignore parse error
    }
  }

  try {
    await ensureSessionInitAndStart();
  } catch (e: any) {
    message.error(e?.message || t('上传初始化失败'));
    sessionState.uploadingChunks = false;
  }
};

// 添加前缀
const addPrefix = (type: string) => {
  const prefixMap: Record<string, string> = {
    batch: '{batch}',
    date: '{date}',
    time: '{time}',
  };
  formData.value.batchPrefix += prefixMap[type] || '';
};

// 打开新标签页
const openNewTab = () => {
  window.open(window.location.href, '_blank');
};

// 提交
const handleSubmit = async () => {
  if (!sessionState.sessionId) {
    message.error(t('请先选择文件并开始上传'));
    return;
  }
  if (!canCommit.value) {
    message.warning(t('请等待所有文件上传完成后再提交'));
    return;
  }

  uploading.value = true;
  try {
    await uploadTempSessionCommit(sessionState.sessionId);
    message.success(t('上传成功'));
    emit('success');
    sessionState.committed = true;
    await handleCancel();
  } catch (error: any) {
    message.error(t(error?.message || '上传失败'));
  } finally {
    uploading.value = false;
  }
};

// 取消
const handleCancel = async () => {
  modalOpen.value = false;

  sessionState.cancelRequested = true;
  const resumeKey = sessionState.resumeKey;

  const sessionId = sessionState.sessionId;
  const shouldDelete = !!sessionId && !sessionState.committed;
  if (shouldDelete) {
    try {
      await deleteUploadTempSession(sessionId as string);
    } catch {
      // ignore（取消时只需要尽力清理临时数据）
    }
  }

  if (resumeKey) {
    try {
      window.localStorage.removeItem(resumeKey);
    } catch {
      // ignore
    }
  }

  resetUploadState();
  sessionState.committed = false;

  formData.value = {
    materialType: 'regular',
    folderId: normalizeFolderId(props.folderId, folders.value),
    filterDuplicate: true,
    tagMode: 'unified',
    tags: [],
    designerMode: 'unified',
    designer: undefined,
    creatorMode: 'unified',
    creator: undefined,
    batchPrefix: '',
    materialGroupIds: [],
  };
  folderTreeKeyword.value = '';
  expandedFolderTreeKeys.value = ['__root_my__', '__root_enterprise__', '__root_department__'];
};

// 初始化
loadData();

// 每次打开弹窗时刷新文件夹树，确保拖拽/移动后的结构及时同步
watch(
  () => props.open,
  (open) => {
    if (!open) return;
    loadData();
  },
  { immediate: false },
);
</script>

<style lang="less" scoped>
.upload-breadcrumb {
  display: flex;
  align-items: center;
  gap: 6px;
  padding: 10px 12px;
  margin: -8px -24px 12px;
  border-bottom: 1px solid #f0f0f0;
  color: #666;
  font-size: 12px;
  background: #fff;
}

.upload-breadcrumb-label {
  color: #888;
}

.upload-breadcrumb-text {
  flex: 1;
  min-width: 0;
}

.upload-breadcrumb-link {
  color: #1677ff;
}

.upload-breadcrumb-current {
  color: #999;
  cursor: default;
}

.upload-breadcrumb-sep {
  color: #bfbfbf;
}

.upload-info {
  display: flex;
  justify-content: space-between;
  align-items: center;
  margin-bottom: 8px;
}

.upload-progress-list {
  margin-top: 12px;
}

.upload-progress-item {
  margin-top: 10px;
}

.upload-progress-top {
  display: flex;
  justify-content: space-between;
  align-items: center;
  margin-bottom: 6px;
  gap: 12px;
}

.upload-progress-filename {
  flex: 1;
  min-width: 0;
  overflow: hidden;
  text-overflow: ellipsis;
  white-space: nowrap;
}

.upload-progress-percent {
  color: #666;
  font-size: 12px;
}

.upload-progress-error {
  color: #ff4d4f;
  font-size: 12px;
  margin-top: 6px;
}

.upload-tips {
  margin-top: 8px;
  color: #999;
  font-size: 12px;

  a {
    color: #1890ff;
    cursor: pointer;
  }
}

.setting-mode {
  margin-bottom: 8px;
}

.prefix-buttons {
  display: flex;
  gap: 8px;
  margin-top: 8px;
}

.folder-picker-panel {
  border: 1px solid #d9d9d9;
  border-radius: 6px;
  padding: 8px;
  background: #fff;
}

.folder-picker-selected {
  height: 32px;
  line-height: 32px;
  border: 1px solid #d9d9d9;
  border-radius: 4px;
  padding: 0 10px;
  margin-bottom: 8px;
  color: #333;
  background: #fff;
  cursor: pointer;
}

.folder-tree-search {
  margin-bottom: 8px;
}

.upload-folder-tree {
  border: 1px solid #f0f0f0;
  border-radius: 4px;
  max-height: 320px;
  overflow: auto;
  padding: 6px 4px;
  background: #fff;
}

.upload-folder-tree-title {
  display: inline-flex;
  align-items: center;
  gap: 6px;

  .root-icon {
    color: #d4a017;
    font-size: 14px;
  }

  .folder-icon {
    color: #f4b73f;
    font-size: 14px;
  }

  .is-root-title {
    font-weight: 600;
    color: #333;
  }
}

:deep(.upload-folder-tree .ant-tree-treenode) {
  width: 100%;
  padding: 1px 0;
}

:deep(.upload-folder-tree .ant-tree-node-content-wrapper) {
  border-radius: 4px;
}

:deep(.upload-folder-tree .ant-tree-node-content-wrapper.ant-tree-node-selected) {
  background: #dbeafe;
  color: #1d4ed8;
}

:deep(.upload-folder-tree .upload-folder-tree-root > .ant-tree-node-content-wrapper) {
  background: #f5f5f5;
}

.empty-group {
  margin-top: 8px;
  padding: 16px;
  text-align: center;
  color: #999;
  border: 1px dashed #d9d9d9;
  border-radius: 4px;

  div {
    margin-top: 8px;
    display: flex;
    gap: 8px;
    justify-content: center;
  }
}

.group-create {
  padding: 8px 12px;
  display: flex;
  flex-direction: column;
  gap: 8px;
}

.group-create-title {
  display: flex;
  align-items: center;
  justify-content: space-between;
  color: rgba(0, 0, 0, 0.65);
  font-size: 12px;
}

.group-create-clear {
  padding: 0;
  height: 20px;
  line-height: 20px;
}

.group-create-hint {
  color: rgba(0, 0, 0, 0.45);
  font-size: 12px;
}

.group-create-btn {
  width: 100%;
}
</style>

