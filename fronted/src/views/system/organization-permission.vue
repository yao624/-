<template>
  <page-container>
    <a-row :gutter="16" style="height: 100%">
      <!-- 左侧：组织树 -->
      <a-col :span="8" style="height: 100%">
        <a-card :title="t('pages.orgTree')" style="height: 100%">
          <template #extra>
            <a-space>
              <a-input-search
                v-model:value="orgSearchValue"
                :placeholder="t('pages.searchOrg')"
                style="width: 200px"
                allow-clear
              />
              <a-button type="text" @click="expandAll" size="small">
                {{ expandedAll ? t('pages.foldAll') : t('pages.expandAll') }}
              </a-button>
            </a-space>
          </template>
          <div style="overflow: auto; height: calc(100vh - 220px)">
            <a-tree
              v-if="orgTreeData.length > 0"
              :tree-data="orgTreeData"
              :expanded-keys="expandedKeys"
              :selected-keys="selectedKeys"
              @expand="handleExpand"
              @select="handleSelect"
              :block-node="true"
            >
              <template #title="{ dataRef }">
                <div
                  :class="['tree-node-content', { 'user-node': dataRef.type === 'user', 'selected': selectedUser && selectedUser.id === dataRef.id }]"
                  @click.stop="handleNodeClick(dataRef)"
                >
                  <FolderOutlined v-if="dataRef.type === 'org'" class="node-icon" />
                  <UserOutlined v-else class="node-icon" />
                  <span :class="['node-name', { 'search-match': orgSearchValue && dataRef.name.toLowerCase().includes(orgSearchValue.toLowerCase()) }]">{{ dataRef.name }}</span>
                  <a-tag v-if="dataRef.type === 'user'" :color="dataRef.is_super ? 'red' : 'blue'" size="small">
                    {{ dataRef.is_super ? t('pages.superAdmin') : t('pages.normalUser') }}
                  </a-tag>
                  <span v-if="dataRef.type === 'org' && dataRef.children?.some((c: any) => c.type === 'user')" class="user-count">
                    ({{ dataRef.children.filter((c: any) => c.type === 'user').length }})
                  </span>
                  <EditOutlined v-if="dataRef.type === 'user'" class="edit-btn" @click.stop="handleEditUser(dataRef)" />
                  <EditOutlined v-if="dataRef.type === 'org' && dataRef.parent_id !== 0" class="edit-btn" @click.stop="handleEditOrg(dataRef)" />
                  <a-dropdown v-if="dataRef.type === 'org'" trigger="click" @click.stop>
                    <a-button type="text" size="small" class="add-btn" @click.stop>
                      <template #icon><PlusOutlined /></template>
                    </a-button>
                    <template #overlay>
                      <a-menu @click="({ key }) => handleAddClick(key, dataRef)">
                        <a-menu-item key="org">{{ t('pages.addOrg') }}</a-menu-item>
                        <a-menu-item key="user">{{ t('pages.addUser') }}</a-menu-item>
                      </a-menu>
                    </template>
                  </a-dropdown>
                </div>
              </template>
            </a-tree>
            <a-empty v-else-if="!loadingOrg" :description="t('pages.noData')" />
            <a-spin v-if="loadingOrg" />
          </div>
        </a-card>

        <!-- 新增组织/用户弹窗 -->
        <a-modal
          v-model:open="addModalVisible"
          :title="addType === 'org' ? t('pages.addOrg') : t('pages.addUser')"
          @ok="handleAddOk"
          :confirmLoading="addLoading"
          :mask-closable="false"
        >
          <!-- 新增组织表单 -->
          <a-form v-if="addType === 'org'" :model="addForm" layout="vertical">
            <a-form-item :label="t('pages.orgName')" name="name" :rules="[{ required: true, message: t('pages.plsInputOrgName') }]">
              <a-input v-model:value="addForm.name" :placeholder="t('pages.plsInputOrgName')" />
            </a-form-item>
            <a-form-item :label="t('pages.orgCode')" name="code">
              <a-input v-model:value="addForm.code" :placeholder="t('pages.plsInputOrgCode')" />
            </a-form-item>
          </a-form>

          <!-- 新增用户表单 -->
          <a-form v-else :model="addForm" layout="vertical">
            <a-form-item :label="t('pages.userName')" name="name" :rules="[{ required: true, message: t('pages.plsInputUserName') }]">
              <a-input v-model:value="addForm.name" :placeholder="t('pages.plsInputUserName')" />
            </a-form-item>
            <a-form-item :label="t('pages.email')" name="email" :rules="[{ required: true, type: 'email', message: t('pages.plsInputEmail') }]">
              <a-input v-model:value="addForm.email" :placeholder="t('pages.plsInputEmail')" />
            </a-form-item>
            <a-form-item :label="t('pages.password')" name="password" :rules="[{ required: true, min: 8, message: t('pages.passwordMinLength') }]">
              <a-input-password v-model:value="addForm.password" :placeholder="t('pages.plsInputPassword')" />
            </a-form-item>
            <a-form-item :label="t('pages.isSuper')">
              <a-switch v-model:checked="addForm.is_super" />
            </a-form-item>
          </a-form>
        </a-modal>

        <!-- 编辑组织弹窗 -->
        <a-modal
          v-model:open="editOrgModalVisible"
          :title="t('pages.editOrg')"
          @ok="handleEditOrgOk"
          :confirmLoading="editOrgLoading"
          :mask-closable="false"
        >
          <a-form :model="editOrgForm" layout="vertical">
            <a-form-item :label="t('pages.orgName')" name="name" :rules="[{ required: true, message: t('pages.plsInputOrgName') }]">
              <a-input v-model:value="editOrgForm.name" :placeholder="t('pages.plsInputOrgName')" />
            </a-form-item>
            <a-form-item :label="t('pages.orgCode')" name="code">
              <a-input v-model:value="editOrgForm.code" :placeholder="t('pages.plsInputOrgCode')" />
            </a-form-item>
            <a-form-item :label="t('pages.parentOrg')" name="parent_id" :rules="[{ required: true, message: t('pages.plsSelectParentOrg') }]">
              <a-tree-select
                v-model:value="editOrgForm.parent_id"
                :tree-data="editOrgTreeData"
                :placeholder="t('pages.plsSelectParentOrg')"
                tree-default-expand-all
                style="width: 100%"
              />
            </a-form-item>
          </a-form>
        </a-modal>

        <!-- 编辑用户弹窗 -->
        <a-modal
          v-model:open="editModalVisible"
          :title="t('pages.editUser')"
          @ok="handleEditOk"
          :confirmLoading="editLoading"
          :mask-closable="false"
        >
          <a-form :model="editForm" layout="vertical">
            <a-form-item :label="t('pages.userName')" name="name" :rules="[{ required: true, message: t('pages.plsInputUserName') }]">
              <a-input v-model:value="editForm.name" :placeholder="t('pages.plsInputUserName')" />
            </a-form-item>
            <a-form-item :label="t('pages.email')" name="email" :rules="[{ required: true, type: 'email', message: t('pages.plsInputEmail') }]">
              <a-input v-model:value="editForm.email" :placeholder="t('pages.plsInputEmail')" />
            </a-form-item>
            <a-form-item :label="t('pages.password')" name="password">
              <a-input-password v-model:value="editForm.password" :placeholder="t('pages.plsInputPasswordEdit')" />
            </a-form-item>
            <a-form-item :label="t('pages.belongOrg')" name="organization_id" :rules="[{ required: true, message: t('pages.plsSelectOrg') }]">
              <a-tree-select
                v-model:value="editForm.organization_id"
                :tree-data="orgList"
                :placeholder="t('pages.plsSelectOrg')"
                tree-default-expand-all
                style="width: 100%"
              />
            </a-form-item>
            <a-form-item :label="t('pages.isSuper')">
              <a-switch v-model:checked="editForm.is_super" />
            </a-form-item>
          </a-form>
        </a-modal>
      </a-col>

      <!-- 右侧：菜单权限分配 -->
      <a-col :span="16" style="height: 100%">
        <a-card :title="t('pages.menuPermissionAssign')">
          <template #extra v-if="!selectedUser?.is_super">
            <a-input-search
              v-model:value="menuSearchValue"
              :placeholder="t('pages.searchMenu')"
              style="width: 150px"
              allow-clear
            />
          </template>
          <div v-if="selectedUser">
            <div class="user-info-header">
              <UserOutlined style="font-size: 18px; margin-right: 8px" />
              <span class="user-name">{{ selectedUser.name }}</span>
              <a-tag :color="selectedUser.is_super ? 'red' : 'blue'" style="margin-left: 8px">
                {{ selectedUser.is_super ? t('pages.superAdmin') : 'ID: ' + selectedUser.id }}
              </a-tag>
              <a-space v-if="!selectedUser.is_super" style="margin-left: 16px">
                <a-button size="small" @click="checkAllMenus">
                  {{ t('pages.selectAll') }}
                </a-button>
                <a-button size="small" @click="uncheckAllMenus">
                  {{ t('pages.cancelAll') }}
                </a-button>
              </a-space>
            </div>

            <div v-if="selectedUser.is_super" style="text-align: center; padding: 60px 0">
              <SafetyCertificateOutlined style="font-size: 48px; color: #52c41a; margin-bottom: 16px" />
              <div style="color: #52c41a; font-size: 16px; font-weight: 500">{{ t('pages.superAdminTip') }}</div>
            </div>
            <div v-else style="overflow: auto; height: calc(100vh - 300px)">
              <div v-if="menuLoading" style="text-align: center; padding: 40px">
                <a-spin />
              </div>
              <div v-else-if="menuTreeData.length > 0" style="margin-top: 16px">
                <div class="menu-tree-wrapper">
                  <a-tree
                    ref="menuTreeRef"
                    v-model:checkedKeys="checkedMenuKeys"
                    :tree-data="menuTreeData"
                    checkable
                    :expanded-keys="menuExpandedKeys"
                    @expand="handleMenuExpand"
                  >
                    <template #title="{ dataRef }">
                      <div :class="['menu-node-content', 'level-' + dataRef.level]" @click.stop="toggleMenuExpand(dataRef)">
                        <MenuOutlined v-if="dataRef.level === 1" class="menu-icon level-1-icon" />
                        <SmallDashOutlined v-else-if="dataRef.level === 2" class="menu-icon level-2-icon" />
                        <NodeIndexOutlined v-else class="menu-icon level-3-icon" />
                        <span :class="['menu-name', { 'search-match': menuSearchValue && dataRef.title.includes(menuSearchValue) }]">{{ dataRef.title }}</span>
                      </div>
                    </template>
                  </a-tree>
                </div>
              </div>
              <a-empty v-else :description="t('pages.noMenus')" />
            </div>

            <div v-if="menuTreeData.length > 0 && !selectedUser.is_super" class="save-button-wrapper">
              <a-button type="primary" :loading="saving" @click="saveMenus">
                {{ t('pages.saveAssign') }}
              </a-button>
            </div>
          </div>
          <div v-else style="text-align: center; padding: 80px 0">
            <InfoCircleOutlined style="font-size: 48px; color: #dcdfe6; margin-bottom: 16px" />
            <a-empty :description="t('pages.selectUserFirst')" />
          </div>
        </a-card>
      </a-col>
    </a-row>
  </page-container>
</template>

<script lang="ts">
import { defineComponent, ref, onMounted, watch } from 'vue';
import { message } from 'ant-design-vue';
import {
  MenuOutlined,
  SmallDashOutlined,
  NodeIndexOutlined,
  UserOutlined,
  InfoCircleOutlined,
  PlusOutlined,
  SafetyCertificateOutlined,
  EditOutlined,
} from '@ant-design/icons-vue';
import { useI18n } from 'vue-i18n';
import {
  getOrganizationTree,
  getUserMenus,
  assignUserMenus,
  createOrganization,
  createUser,
  updateUser,
  getOrganizationList,
  updateOrganization,
  type OrganizationNode,
  type OrganizationUser,
  type MenuNode,
} from '@/api/system/organization';

export default defineComponent({
  components: {
    MenuOutlined,
    SmallDashOutlined,
    NodeIndexOutlined,
    UserOutlined,
    InfoCircleOutlined,
    PlusOutlined,
    SafetyCertificateOutlined,
    EditOutlined,
  },
  setup() {
    const { t } = useI18n();

    // 组织树数据
    const orgTreeData = ref<any[]>([]);
    const expandedKeys = ref<number[]>([]);
    const selectedKeys = ref<any[]>([]);
    const selectedUser = ref<OrganizationUser | null>(null);
    const loadingOrg = ref(false);

    // 菜单权限数据
    const menuTreeData = ref<any[]>([]);
    const menuExpandedKeys = ref<number[]>([]);
    const checkedMenuKeys = ref<number[]>([]);
    const allMenuKeys = ref<number[]>([]);
    const menuLoading = ref(false);
    const saving = ref(false);
    const expandedAll = ref(false);
    const menuTreeRef = ref<any>();
    const orgList = ref<any[]>([]);
    const orgSearchValue = ref('');
    const orgSearchResult = ref<number[]>([]);
    const menuSearchValue = ref('');
    const menuSearchResult = ref<number[]>([]);

    // 新增组织/用户
    const addModalVisible = ref(false);
    const addType = ref<'org' | 'user'>('org');
    const addLoading = ref(false);
    const addForm = ref({
      name: '',
      code: '',
      email: '',
      password: '',
      is_super: false,
    });
    const currentParentOrg = ref<any>(null);

    // 编辑用户
    const editModalVisible = ref(false);
    const editLoading = ref(false);
    const editUserId = ref<number | null>(null);
    const editForm = ref({
      name: '',
      email: '',
      password: '',
      is_super: false,
      organization_id: 0,
    });

    const handleEditUser = (user: any) => {
      editUserId.value = user.id;
      editForm.value = {
        name: user.name,
        email: user.email || '',
        password: '',
        is_super: !!user.is_super,
        organization_id: user.orgId || 0,
      };
      editModalVisible.value = true;
    };

    const handleEditOk = () => {
      if (!editForm.value.name) {
        message.warning(t('pages.plsInputUserName'));
        return;
      }
      editLoading.value = true;
      const data: any = {
        name: editForm.value.name,
        is_super: editForm.value.is_super ? 1 : 0,
        organization_id: editForm.value.organization_id,
      };
      if (editForm.value.email) {
        data.email = editForm.value.email;
      }
      if (editForm.value.password) {
        data.password = editForm.value.password;
      }
      updateUser(editUserId.value!, data)
        .then(() => {
          message.success(t('pages.editUserSuccess'));
          editModalVisible.value = false;
          fetchOrgTree();
        })
        .catch((err: any) => {
          message.error(err.message || t('pages.editUserFailed'));
          editForm.value.password = '';
        })
        .finally(() => {
          editLoading.value = false;
        });
    };

    // 编辑组织
    const editOrgModalVisible = ref(false);
    const editOrgLoading = ref(false);
    const editOrgId = ref<number | null>(null);
    const editOrgForm = ref({
      name: '',
      code: '',
      parent_id: 0,
    });
    const editOrgTreeData = ref<any[]>([]);

    const handleEditOrg = async (org: any) => {
      editOrgId.value = org.id;
      editOrgForm.value = {
        name: org.name,
        code: org.code || '',
        parent_id: Number(org.parent_id) || 0,
      };
      // 刷新组织列表后再打开弹窗，且过滤掉自己
      await fetchOrgList();
      editOrgTreeData.value = filterOutOrg(orgList.value, org.id);
      editOrgModalVisible.value = true;
    };

    const handleEditOrgOk = () => {
      if (!editOrgForm.value.name) {
        message.warning(t('pages.plsInputOrgName'));
        return;
      }
      editOrgLoading.value = true;
      const data: any = {
        name: editOrgForm.value.name,
        parent_id: editOrgForm.value.parent_id,
      };
      if (editOrgForm.value.code) {
        data.code = editOrgForm.value.code;
      }
      updateOrganization(editOrgId.value!, data)
        .then(() => {
          message.success(t('pages.editOrgSuccess'));
          editOrgModalVisible.value = false;
          fetchOrgTree();
        })
        .catch((err: any) => {
          message.error(err.message || t('pages.editOrgFailed'));
        })
        .finally(() => {
          editOrgLoading.value = false;
        });
    };

    const handleAddClick = (key: string, dataRef: any) => {
      addType.value = key as 'org' | 'user';
      currentParentOrg.value = dataRef;
      addForm.value = { name: '', code: '', email: '', password: '', is_super: false };
      addModalVisible.value = true;
    };

    const handleAddOk = () => {
      if (addType.value === 'org') {
        // 新增组织
        if (!addForm.value.name) {
          message.warning(t('pages.plsInputOrgName'));
          return;
        }
        addLoading.value = true;
        createOrganization({
          parent_id: currentParentOrg.value.id,
          name: addForm.value.name,
          code: addForm.value.code,
          sort: 0,
        })
          .then(() => {
            message.success(t('pages.addOrgSuccess'));
            addModalVisible.value = false;
            fetchOrgTree();
          })
          .catch((err: any) => {
            message.error(err.message || t('pages.addOrgFailed'));
          })
          .finally(() => {
            addLoading.value = false;
          });
      } else {
        // 新增用户
        if (!addForm.value.name || !addForm.value.email || !addForm.value.password) {
          message.warning(t('pages.plsFillAllFields'));
          return;
        }
        addLoading.value = true;
        createUser({
          name: addForm.value.name,
          email: addForm.value.email,
          password: addForm.value.password,
          organization_id: currentParentOrg.value.id,
          is_super: addForm.value.is_super ? 1 : 0,
        })
          .then(() => {
            message.success(t('pages.addUserSuccess'));
            addModalVisible.value = false;
            fetchOrgTree();
          })
          .catch((err: any) => {
            message.error(err.message || t('pages.addUserFailed'));
          })
          .finally(() => {
            addLoading.value = false;
          });
      }
    };

    // 转换组织数据，将用户作为子节点加入树
    const convertOrgTree = (orgs: OrganizationNode[]): any[] => {
      return orgs.map(org => {
        const children: any[] = [];

        // 添加子组织
        if (org.children && org.children.length > 0) {
          children.push(...convertOrgTree(org.children));
        }

        // 添加用户作为子节点
        if (org.users && org.users.length > 0) {
          org.users.forEach(user => {
            children.push({
              id: user.id,
              key: 'user_' + user.id,  // 使用前缀避免与org id冲突
              title: user.name,
              name: user.name,
              email: user.email,
              type: 'user',
              is_super: user.is_super ?? 0,
              orgId: org.id,
              isUser: true,
              children: [],
            });
          });
        }

        return {
          id: org.id,
          key: org.id,
          title: org.name,
          name: org.name,
          code: org.code,
          parent_id: org.parent_id,
          type: 'org',
          children: children,
        };
      });
    };

    // 加载组织树
    const fetchOrgTree = () => {
      loadingOrg.value = true;
      getOrganizationTree()
        .then((res: any) => {
          const data = res?.data || res || [];
          orgTreeData.value = convertOrgTree(data);

          // 收集所有需要展开的org key
          const keysToExpand: number[] = [];
          // 第一层全部展开
          orgTreeData.value.forEach((org: any) => {
            keysToExpand.push(org.id);
          });

          // 查找第一个用户及其所有父级org
          let firstUser: any = null;
          let orgPath: any[] = [];

          const findFirstUser = (nodes: any[], path: any[]): boolean => {
            for (const node of nodes) {
              if (node.type === 'org') {
                const newPath = [...path, node];
                const userChild = node.children?.find((c: any) => c.type === 'user');
                if (userChild) {
                  firstUser = userChild;
                  orgPath = newPath;
                  return true;
                }
                if (node.children && findFirstUser(node.children, newPath)) {
                  return true;
                }
              }
            }
            return false;
          };
          findFirstUser(orgTreeData.value, []);

          // 添加路径上所有org到展开列表
          if (orgPath.length > 0) {
            orgPath.forEach((org: any) => {
              keysToExpand.push(org.id);
            });
          }

          expandedKeys.value = keysToExpand;

          // 选中第一个用户并加载菜单
          if (firstUser) {
            selectedKeys.value = [firstUser.key];
            selectedUser.value = {
              id: firstUser.id,
              name: firstUser.name,
              is_super: firstUser.is_super ?? 0,
            };
            if (firstUser.is_super) {
              menuTreeData.value = [];
              checkedMenuKeys.value = [];
            } else {
              fetchUserMenus(firstUser.id);
            }
          }
        })
        .catch((err: any) => {
          message.error(err.message || t('pages.loadOrgFailed'));
        })
        .finally(() => {
          loadingOrg.value = false;
        });
    };

    // 展开/折叠所有
    const expandAll = () => {
      if (expandedAll.value) {
        expandedKeys.value = [];
      } else {
        const getAllKeys = (nodes: any[]): number[] => {
          const keys: number[] = [];
          nodes.forEach(node => {
            if (node.type === 'org') {
              keys.push(node.id);
              if (node.children && node.children.length > 0) {
                keys.push(...getAllKeys(node.children));
              }
            }
          });
          return keys;
        };
        expandedKeys.value = getAllKeys(orgTreeData.value);
      }
      expandedAll.value = !expandedAll.value;
    };

    // 组织树展开
    const handleExpand = (keys: any) => {
      expandedKeys.value = keys as number[];
    };

    // 组织树搜索 - 展开全部节点然后高亮匹配项
    const handleOrgSearch = (value: string) => {
      const searchValue = (value || '').trim().toLowerCase();
      if (!searchValue) {
        orgSearchResult.value = [];
        return;
      }
      const keys: number[] = [];

      const searchNodes = (nodes: any[]) => {
        nodes.forEach((node: any) => {
          if ((node.name || '').toLowerCase().includes(searchValue)) {
            keys.push(node.id);
          }
          if (node.children && node.children.length > 0) {
            searchNodes(node.children);
          }
        });
      };

      searchNodes(orgTreeData.value);
      orgSearchResult.value = keys;

      // 搜索时展开全部组织节点
      const allOrgKeys: number[] = [];
      const collectOrgKeys = (nodes: any[]) => {
        nodes.forEach((node: any) => {
          if (node.type === 'org') {
            allOrgKeys.push(node.id);
          }
          if (node.children && node.children.length > 0) {
            collectOrgKeys(node.children);
          }
        });
      };
      collectOrgKeys(orgTreeData.value);
      expandedKeys.value = allOrgKeys;
    };

    // 处理节点点击
    const handleNodeClick = (node: any) => {
      if (node.type === 'user') {
        // 点击用户节点
        selectedUser.value = {
          id: node.id,
          name: node.name,
          is_super: node.is_super ?? 0,
        };
        selectedKeys.value = [node.key];
        if (node.is_super) {
          menuTreeData.value = [];
          checkedMenuKeys.value = [];
        } else {
          fetchUserMenus(node.id);
        }
      } else {
        // 点击组织节点，展开/折叠
        const key = node.id;
        if (expandedKeys.value.includes(key)) {
          expandedKeys.value = expandedKeys.value.filter(k => k !== key);
        } else {
          expandedKeys.value = [...expandedKeys.value, key];
        }
        selectedUser.value = null;
        selectedKeys.value = [node.id];
      }
    };

    // 选择组织节点（用于处理tree本身的select事件）
    const handleSelect = (keys: any, e: any) => {
      // 如果选中的是用户节点，不处理（用户点击由handleNodeClick处理）
      if (keys.some((k: any) => String(k).startsWith('user_'))) {
        return;
      }
      selectedKeys.value = keys;
    };

    // 加载用户菜单权限
    const fetchUserMenus = (userId: number) => {
      menuLoading.value = true;
      checkedMenuKeys.value = [];
      getUserMenus(userId)
        .then((res: any) => {
          const data = res?.data || res || [];
          menuTreeData.value = convertMenuToTree(data);
          extractCheckedKeys(data);
          // 默认展开1级
          if (menuTreeData.value.length > 0) {
            menuExpandedKeys.value = getLevelKeys(menuTreeData.value, 1);
          }
        })
        .catch((err: any) => {
          message.error(err.message || t('pages.loadMenuFailed'));
          menuTreeData.value = [];
        })
        .finally(() => {
          menuLoading.value = false;
        });
    };

    // 获取1-2级的所有key
    const getLevelKeys = (menus: any[], maxLevel: number, currentLevel = 1): number[] => {
      const keys: number[] = [];
      menus.forEach(menu => {
        if (currentLevel <= maxLevel) {
          keys.push(menu.key);
          if (menu.children && menu.children.length > 0) {
            keys.push(...getLevelKeys(menu.children, maxLevel, currentLevel + 1));
          }
        }
      });
      return keys;
    };

    // 转换菜单数据为树形结构
    const convertMenuToTree = (menus: MenuNode[], level = 1): any[] => {
      return menus.map((menu) => ({
        key: menu.id,
        title: menu.name,
        checked: menu.checked,
        level: level,
        isLeaf: !menu.children || menu.children.length === 0,
        children:
          menu.children && menu.children.length > 0
            ? convertMenuToTree(menu.children, level + 1)
            : [],
      }));
    };

    // 提取所有选中的菜单ID
    const extractCheckedKeys = (menus: MenuNode[]) => {
      menus.forEach((menu) => {
        if (menu.checked) {
          checkedMenuKeys.value.push(menu.id);
        }
        if (menu.children && menu.children.length > 0) {
          extractCheckedKeys(menu.children);
        }
      });
    };

    // 提取所有菜单ID
    const extractAllKeys = (menus: any[]) => {
      menus.forEach((menu) => {
        allMenuKeys.value.push(menu.key);
        if (menu.children && menu.children.length > 0) {
          extractAllKeys(menu.children);
        }
      });
    };

    // 菜单树展开
    const handleMenuExpand = (keys: any) => {
      menuExpandedKeys.value = keys as number[];
    };

    // 点击菜单名称切换展开
    const toggleMenuExpand = (dataRef: any) => {
      const key = dataRef.key;
      if (menuExpandedKeys.value.includes(key)) {
        menuExpandedKeys.value = menuExpandedKeys.value.filter(k => k !== key);
      } else {
        menuExpandedKeys.value = [...menuExpandedKeys.value, key];
      }
    };

    // 菜单树搜索 - 展开全部节点然后高亮匹配项
    const handleMenuSearch = (value: string) => {
      const searchValue = (value || '').trim().toLowerCase();
      if (!searchValue) {
        menuSearchResult.value = [];
        return;
      }
      const keys: number[] = [];

      const searchMenus = (menus: any[]) => {
        menus.forEach((menu: any) => {
          if ((menu.title || '').toLowerCase().includes(searchValue)) {
            keys.push(menu.key);
          }
          if (menu.children && menu.children.length > 0) {
            searchMenus(menu.children);
          }
        });
      };

      searchMenus(menuTreeData.value);
      menuSearchResult.value = keys;

      // 搜索时展开全部节点
      const allMenuKeys: number[] = [];
      const collectMenuKeys = (menus: any[]) => {
        menus.forEach((menu: any) => {
          allMenuKeys.push(menu.key);
          if (menu.children && menu.children.length > 0) {
            collectMenuKeys(menu.children);
          }
        });
      };
      collectMenuKeys(menuTreeData.value);
      menuExpandedKeys.value = allMenuKeys;
    };

    // 监听搜索值变化
    watch(orgSearchValue, (val) => {
      if (orgTreeData.value.length > 0) {
        handleOrgSearch(val);
      }
    });

    watch(menuSearchValue, (val) => {
      if (menuTreeData.value.length > 0) {
        handleMenuSearch(val);
      }
    });

    // 全选菜单
    const checkAllMenus = () => {
      checkedMenuKeys.value = [...allMenuKeys.value];
    };

    // 取消全选菜单
    const uncheckAllMenus = () => {
      checkedMenuKeys.value = [];
    };

    // 获取所有具有至少一个选中后代的节点ID（用于处理半选状态的父节点）
    const getKeysWithCheckedDescendants = (menus: any[], checkedKeys: Set<number>): number[] => {
      const result: number[] = [];

      const traverse = (nodes: any[]) => {
        nodes.forEach(node => {
          // 检查该节点是否有选中的后代
          const hasCheckedDescendant = (n: any): boolean => {
            if (checkedKeys.has(n.key)) return true;
            if (n.children && n.children.length > 0) {
              return n.children.some((child: any) => hasCheckedDescendant(child));
            }
            return false;
          };

          // 如果该节点本身未选中，但有选中后代，则需要提交该节点
          if (!checkedKeys.has(node.key) && hasCheckedDescendant(node)) {
            result.push(node.key);
          }

          // 继续遍历子节点
          if (node.children && node.children.length > 0) {
            traverse(node.children);
          }
        });
      };

      traverse(menus);
      return result;
    };

    // 保存菜单分配
    const saveMenus = () => {
      if (!selectedUser.value) {
        message.warning(t('pages.selectUserFirst'));
        return;
      }

      saving.value = true;

      // 合并完全选中的节点和具有半选状态（有选中后代但自身未全选）的父节点
      const checkedSet = new Set(checkedMenuKeys.value);
      const parentKeysWithCheckedDescendants = getKeysWithCheckedDescendants(menuTreeData.value, checkedSet);
      const allMenuKeysToSubmit = [...checkedMenuKeys.value, ...parentKeysWithCheckedDescendants];

      assignUserMenus(selectedUser.value.id, allMenuKeysToSubmit)
        .then(() => {
          message.success(t('pages.assignSuccess'));
        })
        .catch((err: any) => {
          message.error(err.message || t('pages.assignFailed'));
        })
        .finally(() => {
          saving.value = false;
        });
    };

    // 监听菜单数据变化
    watch(menuTreeData, (newVal) => {
      if (newVal.length > 0) {
        allMenuKeys.value = [];
        extractAllKeys(newVal);
      }
    });

    onMounted(() => {
      fetchOrgTree();
      fetchOrgList();
    });

    const fetchOrgList = () => {
      return getOrganizationList()
        .then((res: any) => {
          const data = res?.data || res || [];
          orgList.value = convertToTreeSelect(data);
        })
        .catch((err: any) => {
          console.error('Failed to load org list', err);
        });
    };

    const convertToTreeSelect = (list: any[]): any[] => {
      const map: any = {};
      const result: any[] = [];

      list.forEach((item: any) => {
        map[item.id] = { value: Number(item.id), label: item.name, children: [] };
      });

      list.forEach((item: any) => {
        if (item.parent_id === 0) {
          result.push(map[item.id]);
        } else if (map[item.parent_id]) {
          map[item.parent_id].children.push(map[item.id]);
        }
      });

      return result;
    };

    const filterOutOrg = (list: any[], orgId: number): any[] => {
      return list
        .filter((item: any) => item.value !== orgId)
        .map((item: any) => ({
          ...item,
          children: item.children ? filterOutOrg(item.children, orgId) : [],
        }));
    };

    return {
      t,
      orgTreeData,
      expandedKeys,
      selectedKeys,
      selectedUser,
      loadingOrg,
      menuTreeData,
      menuExpandedKeys,
      checkedMenuKeys,
      menuLoading,
      saving,
      expandedAll,
      menuTreeRef,
      orgList,
      expandAll,
      handleExpand,
      handleSelect,
      handleNodeClick,
      handleMenuExpand,
      toggleMenuExpand,
      checkAllMenus,
      uncheckAllMenus,
      saveMenus,
      addModalVisible,
      addType,
      addLoading,
      addForm,
      handleAddClick,
      handleAddOk,
      editModalVisible,
      editLoading,
      editForm,
      handleEditUser,
      handleEditOk,
      editOrgModalVisible,
      editOrgLoading,
      editOrgForm,
      editOrgTreeData,
      handleEditOrg,
      handleEditOrgOk,
      orgSearchValue,
      menuSearchValue,
    };
  },
});
</script>

<style scoped>
.tree-node-content {
  display: inline-flex;
  align-items: center;
  gap: 6px;
  padding: 2px 4px;
  border-radius: 4px;
  transition: all 0.2s;
}

/* 用户节点hover效果 */
.tree-node-content.user-node:hover {
  background: #f5f5f5;
}

.tree-node-content.selected {
  background: #e6f7ff;
}

.node-icon {
  font-size: 14px;
  color: #1890ff;
}

.node-name.search-match,
.menu-name.search-match {
  color: #ff4d4f;
  font-weight: bold;
}

.user-node .node-icon {
  color: #52c41a;
}

.node-name {
  font-weight: 500;
}

.user-count {
  color: #999;
  font-size: 12px;
  margin-left: 4px;
}

.add-btn {
  margin-left: auto;
  opacity: 0;
  transition: opacity 0.2s;
}

.tree-node-content:hover .add-btn {
  opacity: 1;
}

.user-node .add-btn {
  display: none;
}

.edit-btn {
  margin-left: 8px;
  color: #1890ff;
  cursor: pointer;
  opacity: 0;
  transition: opacity 0.2s;
}

.tree-node-content:hover .edit-btn {
  opacity: 1;
}

.edit-btn:hover {
  color: #40a9ff;
}

.user-info-header {
  color: #1890ff;
  font-weight: 400;
}

.user-info-header {
  display: flex;
  align-items: center;
  flex-wrap: wrap;
  gap: 12px;
  padding: 16px;
  background: linear-gradient(135deg, #e6f7ff 0%, #f0f9ff 100%);
  border-radius: 8px;
  margin-bottom: 16px;
}

.user-name {
  font-size: 16px;
  font-weight: 600;
  color: #333;
}

/* 菜单树样式 */
.menu-tree-wrapper {
  background: #fafafa;
  border-radius: 8px;
  padding: 16px;
  border: 1px solid #f0f0f0;
}

.menu-node-content {
  display: inline-flex;
  align-items: center;
  gap: 8px;
  padding: 2px 4px;
  border-radius: 4px;
  transition: all 0.2s;
}

.menu-node-content:hover {
  background: #f0f0f0;
}

.menu-icon {
  font-size: 14px;
}

.level-1-icon {
  color: #1890ff;
}

.level-2-icon {
  color: #999;
  font-size: 12px;
}

.level-3-icon {
  color: #bbb;
  font-size: 11px;
}

.menu-name {
  font-size: 14px;
  color: #333;
}

/* 一级菜单 */
.menu-node-content.level-1 {
  padding-left: 0;
  font-weight: 500;
}

/* 二级菜单 */
.menu-node-content.level-2 {
  padding-left: 12px;
}

/* 三级菜单 */
.menu-node-content.level-3 {
  padding-left: 24px;
}

.menu-node-content.level-3 .menu-name {
  color: #666;
}

/* 选中菜单项样式 */
.menu-node-content :deep(.ant-tree-checkbox-checked .menu-name),
.menu-node-content :deep(.ant-tree-checkbox-indeterminate .menu-name) {
  color: #1890ff;
  font-weight: 500;
}

/* 自定义树节点样式 */
:deep(.ant-tree-treenode) {
  padding: 1px 0;
}

:deep(.ant-tree-node-content-wrapper) {
  padding: 0;
}

:deep(.ant-tree-switcher) {
  background: transparent;
}

:deep(.ant-tree-checkbox) {
  margin-right: 8px;
}

:deep(.ant-tree-title) {
  display: inline-block;
}

:deep(.ant-tree-child-tree) {
  padding-left: 0;
}

.save-button-wrapper {
  margin-top: 16px;
  text-align: center;
  padding: 16px 0;
  background: #fff;
  border-top: 1px solid #f0f0f0;
  position: sticky;
  bottom: 0;
}
</style>
