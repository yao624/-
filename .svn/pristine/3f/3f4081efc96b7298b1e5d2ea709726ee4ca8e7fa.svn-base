<template>
  <a-modal
    v-model:open="modalOpen"
    :title="dialogTitle"
    width="960px"
    :ok-text="readOnly ? '关闭' : '保存'"
    :cancel-button-props="readOnly ? { style: { display: 'none' } } : undefined"
    @ok="handleSubmit"
    @cancel="handleCancel"
  >
    <div class="permission-modal">
      <div class="permission-header">
        <div class="permission-title">设置对象</div>
        <div class="permission-targets">
          <a-tag v-for="item in materialTargets" :key="item.id" color="blue">
            {{ item.name || item.material_name || item.id }}
          </a-tag>
        </div>
      </div>

      <a-form :model="formData" layout="vertical">
        <a-form-item v-if="!readOnly" label="可见范围">
          <a-radio-group v-model:value="formData.scope">
            <a-radio value="enterprise">企业内可见</a-radio>
            <a-radio value="mixed">指定人员和部门</a-radio>
          </a-radio-group>
        </a-form-item>

        <a-form-item v-if="readOnly" label="可见范围">
          <div class="permission-preview permission-preview--compact">
            {{ scopeLabel }}
          </div>
        </a-form-item>

        <a-form-item v-if="needUserPicker && !readOnly" label="指定人员">
          <UserPicker
            v-model="formData.userIds"
            :org-tree="orgTree"
            :placeholder="'请选择人员'"
            :selected-empty-label="'暂无已选人员'"
            :remove-label="'移除'"
          />
        </a-form-item>

        <a-form-item v-if="needDepartmentPicker && !readOnly" label="指定部门">
          <div class="department-picker">
            <div class="department-tree-panel">
              <a-spin :spinning="loadingOrgTree">
                <a-tree
                  v-if="departmentTreeData.length"
                  checkable
                  block-node
                  :tree-data="departmentTreeData"
                  :checked-keys="formData.departmentIds"
                  :expanded-keys="expandedDeptKeys"
                  @check="handleDepartmentCheck"
                  @expand="handleDepartmentExpand"
                />
                <a-empty v-else description="暂无部门数据" />
              </a-spin>
            </div>
            <div class="department-selected-panel">
              <div class="department-selected-header">
                <span>已选部门 {{ selectedDepartmentNames.length }} 个</span>
                <a-button type="link" size="small" @click="clearDepartments">清空</a-button>
              </div>
              <div v-if="selectedDepartmentNames.length" class="department-selected-list">
                <a-tag
                  v-for="dept in selectedDepartmentNames"
                  :key="dept.id"
                  closable
                  @close.prevent="removeDepartment(dept.id)"
                >
                  {{ dept.name }}
                </a-tag>
              </div>
              <a-empty v-else description="暂无已选部门" />
            </div>
          </div>
          <a-checkbox v-model:checked="formData.includeSubDepartments" class="department-inherit">
            包含子部门
          </a-checkbox>
        </a-form-item>

        <a-form-item label="效果预览">
          <div class="permission-preview">
            <div class="permission-preview-summary">{{ previewText }}</div>
            <div v-if="selectedUserNames.length" class="permission-preview-block">
              <div class="permission-preview-label">指定人员</div>
              <div class="permission-preview-tags">
                <a-tag v-for="user in selectedUserNames" :key="user.id" color="blue">
                  {{ user.name }}
                </a-tag>
              </div>
            </div>
            <div v-if="selectedDepartmentNames.length" class="permission-preview-block">
              <div class="permission-preview-label">指定部门</div>
              <div class="permission-preview-tags">
                <a-tag v-for="dept in selectedDepartmentNames" :key="dept.id" color="cyan">
                  {{ dept.name }}
                </a-tag>
              </div>
            </div>
            <div v-if="showDepartmentRule" class="permission-preview-rule">
              {{ formData.includeSubDepartments ? '已包含子部门' : '不包含子部门' }}
            </div>
          </div>
        </a-form-item>
      </a-form>
    </div>
  </a-modal>
</template>

<script lang="ts" setup>
import { computed, ref, watch } from 'vue';
import { message } from 'ant-design-vue';
import { getOrganizationTree } from '@/api/system/organization';
import { UserPicker } from '@/components/user-picker';

interface MaterialItem {
  id: string | number;
  name?: string;
  material_name?: string;
}

interface PermissionValue {
  scope: 'enterprise' | 'users' | 'departments' | 'mixed';
  userIds: Array<string | number>;
  userNames?: string[];
  departmentIds: Array<string | number>;
  departmentNames?: string[];
  includeSubDepartments: boolean;
}

interface Props {
  open: boolean;
  materials?: MaterialItem[];
  initialValue?: Partial<PermissionValue> | null;
  readOnly?: boolean;
}

interface Emits {
  (e: 'update:open', value: boolean): void;
  (e: 'success', value: PermissionValue): void;
}

const props = withDefaults(defineProps<Props>(), {
  materials: () => [],
  initialValue: null,
  readOnly: false,
});

const emit = defineEmits<Emits>();

const modalOpen = computed({
  get: () => props.open,
  set: (value) => emit('update:open', value),
});

const materialTargets = computed(() => props.materials || []);
const dialogTitle = computed(() => {
  if (props.readOnly) return materialTargets.value.length > 1 ? '查看素材可见范围' : '查看可见范围';
  return materialTargets.value.length > 1 ? '批量设置可见范围' : '设置素材可见范围';
});

const orgTree = ref<any[]>([]);
const loadingOrgTree = ref(false);
const expandedDeptKeys = ref<Array<string | number>>([]);

const formData = ref<PermissionValue>({
  scope: 'enterprise',
  userIds: [],
  userNames: [],
  departmentIds: [],
  departmentNames: [],
  includeSubDepartments: true,
});

const resetFormData = () => {
  const initialScope = props.initialValue?.scope === 'enterprise' ? 'enterprise' : 'mixed';
  formData.value = {
    scope: initialScope,
    userIds: [...(props.initialValue?.userIds || [])],
    userNames: [...(props.initialValue?.userNames || [])],
    departmentIds: [...(props.initialValue?.departmentIds || [])],
    departmentNames: [...(props.initialValue?.departmentNames || [])],
    includeSubDepartments: props.initialValue?.includeSubDepartments ?? true,
  };
};

const needUserPicker = computed(() => formData.value.scope === 'mixed');
const needDepartmentPicker = computed(() => formData.value.scope === 'mixed');

const buildDepartmentTree = (nodes: any[]): any[] => {
  return (nodes || []).map((node: any) => ({
    title: node.name,
    key: node.id,
    children: buildDepartmentTree((node.children || []).filter((child: any) => child?.type === 'org')),
  }));
};

const getAllDeptExpandKeys = (nodes: any[]): Array<string | number> => {
  const result: Array<string | number> = [];
  const walk = (items: any[]) => {
    (items || []).forEach((item: any) => {
      result.push(item.key);
      if (item.children?.length) walk(item.children);
    });
  };
  walk(nodes);
  return result;
};

const departmentTreeData = computed(() => buildDepartmentTree(orgTree.value));

const departmentMap = computed(() => {
  const map = new Map<string, { id: string | number; name: string }>();
  const walk = (nodes: any[]) => {
    (nodes || []).forEach((node: any) => {
      map.set(String(node.id), { id: node.id, name: String(node.name || '') });
      if (node.children?.length) walk(node.children);
    });
  };
  walk(orgTree.value);
  return map;
});

const selectedDepartmentNames = computed(() =>
  formData.value.departmentIds
    .map((id) => departmentMap.value.get(String(id)))
    .filter(Boolean) as Array<{ id: string | number; name: string }>,
);

const userMap = computed(() => {
  const map = new Map<string, { id: string | number; name: string }>();
  const walk = (nodes: any[]) => {
    (nodes || []).forEach((node: any) => {
      (node.users || []).forEach((user: any) => {
        map.set(String(user.id), { id: user.id, name: String(user.name || user.email || user.id) });
      });
      if (node.children?.length) walk(node.children);
    });
  };
  walk(orgTree.value);
  return map;
});

const selectedUserNames = computed(() => {
  const fallbackNames = props.initialValue?.userNames || [];
  return formData.value.userIds.map((id, index) => {
    const matched = userMap.value.get(String(id));
    if (matched) return matched;
    return {
      id,
      name: String(fallbackNames[index] || id),
    };
  });
});

const scopeLabel = computed(() => {
  if (formData.value.scope === 'enterprise') return '企业内可见';
  return '指定人员和部门可见';
});

const showDepartmentRule = computed(() => formData.value.scope === 'mixed');

const previewText = computed(() => {
  if (formData.value.scope === 'enterprise') return '当前企业内所有用户可见';
  return '已指定 ' + formData.value.userIds.length + ' 人和 ' + formData.value.departmentIds.length + ' 个部门可见';
});

const loadOrgTree = async () => {
  loadingOrgTree.value = true;
  try {
    const res = await getOrganizationTree();
    const data = Array.isArray(res?.data) ? res.data : (Array.isArray((res as any)?.data?.data) ? (res as any).data.data : (res as any) || []);
    const normalize = (nodes: any[]): any[] => (nodes || [])
      .filter((node: any) => node && node.type !== 'user')
      .map((node: any) => ({
        ...node,
        children: normalize(node.children || []),
      }));
    orgTree.value = normalize(data);
    expandedDeptKeys.value = getAllDeptExpandKeys(departmentTreeData.value);
  } catch (error) {
    console.error('加载组织架构失败:', error);
    message.error('加载部门数据失败');
  } finally {
    loadingOrgTree.value = false;
  }
};

const handleDepartmentCheck = (checkedKeys: any) => {
  formData.value.departmentIds = Array.isArray(checkedKeys) ? checkedKeys : (checkedKeys?.checked || []);
};

const handleDepartmentExpand = (keys: Array<string | number>) => {
  expandedDeptKeys.value = keys;
};

const clearDepartments = () => {
  formData.value.departmentIds = [];
};

const removeDepartment = (id: string | number) => {
  formData.value.departmentIds = formData.value.departmentIds.filter((item) => String(item) !== String(id));
};

const handleSubmit = () => {
  if (props.readOnly) {
    modalOpen.value = false;
    return;
  }

  emit('success', {
    ...formData.value,
    userNames: selectedUserNames.value.map((item) => item.name),
    departmentNames: selectedDepartmentNames.value.map((item) => item.name),
  });
  modalOpen.value = false;
};

const handleCancel = () => {
  modalOpen.value = false;
};

watch(
  () => props.open,
  (value) => {
    if (!value) return;
    resetFormData();
    loadOrgTree();
  },
);
</script>

<style lang="less" scoped>
.permission-modal {
  display: flex;
  flex-direction: column;
  gap: 16px;
}

.permission-header {
  padding: 12px 14px;
  border-radius: 8px;
  background: #fafcff;
  border: 1px solid #edf2f7;
}

.permission-title {
  color: #667085;
  font-size: 12px;
  margin-bottom: 8px;
}

.permission-targets {
  display: flex;
  flex-wrap: wrap;
  gap: 8px;
}

.department-picker {
  display: grid;
  grid-template-columns: minmax(0, 1.2fr) minmax(260px, 0.8fr);
  gap: 12px;
}

.department-tree-panel,
.department-selected-panel {
  min-height: 280px;
  max-height: 320px;
  overflow: auto;
  border: 1px solid #edf2f7;
  border-radius: 8px;
  padding: 12px;
  background: #fff;
}

.department-selected-header {
  display: flex;
  align-items: center;
  justify-content: space-between;
  margin-bottom: 10px;
  color: #344054;
}

.department-selected-list {
  display: flex;
  flex-wrap: wrap;
  gap: 8px;
}

.department-inherit {
  margin-top: 12px;
}

.permission-preview {
  border: 1px solid #edf2f7;
  border-radius: 8px;
  background: #fafcff;
  color: #344054;
  padding: 12px 14px;
  line-height: 1.6;
}

.permission-preview--compact {
  padding: 10px 12px;
}

.permission-preview-summary {
  font-weight: 600;
}

.permission-preview-block + .permission-preview-block {
  margin-top: 12px;
}

.permission-preview-block {
  margin-top: 12px;
}

.permission-preview-label {
  font-size: 12px;
  color: #667085;
  margin-bottom: 8px;
}

.permission-preview-tags {
  display: flex;
  flex-wrap: wrap;
  gap: 8px;
}

.permission-preview-rule {
  margin-top: 12px;
  color: #1677ff;
}

@media (max-width: 900px) {
  .department-picker {
    grid-template-columns: 1fr;
  }
}
</style>
