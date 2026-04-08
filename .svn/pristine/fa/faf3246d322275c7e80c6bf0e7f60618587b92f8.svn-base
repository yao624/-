<template>
  <page-container>
    <a-card mb-4>
      <a-row :gutter="[15, 0]" v-if="expand">
        <a-col :span="6">
          <a-form-item :label="t('pages.name')">
            <a-input v-model:value="pagination.name" />
          </a-form-item>
        </a-col>
      </a-row>

      <a-row :span="24" style="text-align: left; display: flex; justify-content: space-between">
        <a-col :span="12">
          <a-button type="primary" @click="showModal('')" v-hasPermission="'role:add'">
            <template #icon>
              <plus-outlined />
            </template>
            {{ t('pages.add') }}
          </a-button>
        </a-col>
        <a-space>
          <a-button :loading="loading" type="primary" @click="fetchRoles">
            {{ t('pages.query') }}
          </a-button>
          <a-button @click="resetPagination">
            {{ t('pages.reset') }}
          </a-button>
        </a-space>
      </a-row>
    </a-card>
    <a-table
      :columns="columns"
      :data-source="dataSource"
      @change="handleTableChange"
      :scroll="{ y: tableHeight, x: 2000 }"
      :pagination="pagination"
      :loading="loading"
      :row-key="record => record.id"
    >
      <template #bodyCell="{ column, text, record }">
        <template v-if="column['dataIndex'] === 'status'">
          <span v-if="text === 1">
            <a-badge color="green" text="启用" />
          </span>
          <span v-else>
            <a-badge color="red" text="禁用" />
          </span>
        </template>
        <template v-if="['created_at', 'updated_at'].includes(`${column['dataIndex']}`)">
          <span>{{ dayjs(text).format('YYYY-MM-DD HH:mm:ss') }}</span>
        </template>

        <template v-if="column['dataIndex'] === 'operation'">
          <a-button
            type="link"
            @click="showModal(record)"
            :icon="h(EditOutlined)"
            v-hasPermission="'role:edit'"
          ></a-button>
          <!--          <a-button type="link" @click="handleDelete(record)" :icon="h(DeleteOutlined)"></a-button>-->
        </template>
      </template>
    </a-table>
    <a-modal
      v-if="open"
      v-model:open="open"
      :title="dialogTitle"
      :maskClosable="false"
      @ok="handleOk"
      :width="800"
    >
      <a-form ref="formRef" :label-col="{ span: 4 }" name="role_form" :model="dynamicValidateForm">
        <a-form-item name="name" :label="t('pages.name')" :rules="[{ required: true }]">
          <a-input v-model:value="dynamicValidateForm.name" />
        </a-form-item>
        <a-form-item name="description" :label="t('pages.description')">
          <a-input v-model:value="dynamicValidateForm.description" />
        </a-form-item>

        <a-form-item name="status" :label="t('pages.status')">
          <a-switch v-model:checked="dynamicValidateForm.status" />
        </a-form-item>
        <a-form-item name="permission_ids" :label="t('pages.permissions.assign')">
          <a-space direction="vertical" style="width: 100%; margin-top: 5px">
            <a-space>
              <a-button size="small" @click="checkAllPermissions">
                {{ t('pages.selectAll') }}
              </a-button>
              <a-button size="small" @click="uncheckAllPermissions">
                {{ t('pages.cancelAll') }}
              </a-button>
            </a-space>
            <ATree
              ref="treeRef"
              v-model:checkedKeys="dynamicValidateForm.permission_ids"
              :tree-data="permissionTreeData"
              checkable
              default-expand-all
            />
          </a-space>
        </a-form-item>
      </a-form>
    </a-modal>
  </page-container>
</template>
<script lang="ts">
import dayjs from 'dayjs';
import { cloneDeep } from 'lodash';
import { computed, defineComponent, ref, onMounted, watch, h } from 'vue';
import { message, Modal, Tree } from 'ant-design-vue';
import { PlusOutlined, EditOutlined, DeleteOutlined } from '@ant-design/icons-vue';
import { getRoles, addRole, updateRole, deleteRole, getPermissions } from '@/api/user/role_v2';
import { useI18n } from 'vue-i18n';

export default defineComponent({
  components: {
    PlusOutlined,
    ATree: Tree,
  },
  setup() {
    const { t } = useI18n();
    const sortedInfo = ref();
    const expand = ref(true);
    const screenHeight = ref<number>(window.innerHeight);
    const tableHeight = screenHeight.value < 1010 ? ref('48vh') : ref('48vh');

    watch(expand, newValue => {
      if (screenHeight.value < 1010) {
        tableHeight.value = newValue ? '48vh' : '65vh';
      } else {
        tableHeight.value = newValue ? '48vh' : '65vh';
      }
    });
    const columns = computed<any[]>(() => {
      const sorted = sortedInfo.value || {};
      return [
        {
          title: t('pages.name'),
          dataIndex: 'name',
        },
        {
          title: t('pages.description'),
          dataIndex: 'description',
        },
        {
          title: t('pages.status'),
          dataIndex: 'status',
        },
        {
          title: t('pages.createdAt'),
          dataIndex: 'created_at',
          sorter: true,
          sortOrder: sorted.field === 'created_at' && sorted.order,
        },
        {
          title: t('pages.operation'),
          width: 180,
          dataIndex: 'operation',
          fixed: 'right',
        },
      ];
    });
    const handleTableChange: any['onChange'] = (pag, _filters, sorter) => {
      if (pag) {
        if (pag.current != null) pagination.value.current = pag.current;
        if (pag.pageSize != null) pagination.value.pageSize = pag.pageSize;
      }
      sortedInfo.value = sorter;
    };
    const loading = ref(true);
    const submitting = ref(false);
    const dataSource = ref<any>([]);
    const pagination = ref<any>({
      name: '',
      showQuickJumper: true,
      showSizeChanger: true,
      current: 1,
      total: 0,
      showTotal: total => `Total ${total} items`,
      pageSize: 20,
    });
    const resetPagination = () => {
      pagination.value.name = '';
      sortedInfo.value = null;
      fetchRoles();
    };
    interface Query {
      name?: string;
      pageNo?: number;
      pageSize?: number;
    }
    const fetchRoles = () => {
      loading.value = true;
      const param = {
        name: pagination.value.name,
        pageNo: pagination.value.current,
        pageSize: pagination.value.pageSize,
      } as Query;

      getRoles(param)
        .then((res: any) => {
          dataSource.value = res.data;
          pagination.value.total = res.totalCount;
        })
        .finally(() => {
          loading.value = false;
        });
    };

    const open = ref<boolean>(false);
    const dialogTitle = ref<string>('');
    const formRef = ref<any>();
    const treeRef = ref<any>();
    const resetForm = {
      id: 0,
      name: '',
      description: '',
      sort: 0,
      status: true,
      permission_ids: [],
    };

    // 权限树形选项
    const permissionTreeData = ref<any[]>([]);
    const allPermissionKeys = ref<any[]>([]);
    const allLeafKeys = ref<string[]>([]); // 只存储叶子节点ID
    let permissionLoaded = false;

    const fetchPermissionOptions = () => {
      return getPermissions()
        .then((res: any) => {
          // 递归转换树形数据的 id 为 string 类型，ant-design-vue Tree 需要 string 类型的 key
          const convertTree = (data: any[]): any[] => {
            return data.map(item => ({
              key: String(item.id),
              title: item.name,
              children: item.children ? convertTree(item.children) : [],
            }));
          };
          permissionTreeData.value = convertTree(res.data || []);
          // 获取所有权限的 key (字符串)
          const getKeys = (data: any[]): string[] => {
            const keys: string[] = [];
            data.forEach((item: any) => {
              keys.push(String(item.id));
              if (item.children && item.children.length) {
                keys.push(...getKeys(item.children));
              }
            });
            return keys;
          };
          // 获取所有叶子节点的 key
          const getLeafKeys = (data: any[]): string[] => {
            const keys: string[] = [];
            data.forEach((item: any) => {
              if (!item.children || item.children.length === 0) {
                keys.push(String(item.id));
              } else {
                keys.push(...getLeafKeys(item.children));
              }
            });
            return keys;
          };
          allPermissionKeys.value = getKeys(res.data || []);
          allLeafKeys.value = getLeafKeys(res.data || []);
          permissionLoaded = true;
        })
        .catch(console.error);
    };
    fetchPermissionOptions();

    // 全选/取消全选
    const checkAllPermissions = () => {
      dynamicValidateForm.value.permission_ids = [...allPermissionKeys.value];
    };
    const uncheckAllPermissions = () => {
      dynamicValidateForm.value.permission_ids = [];
    };

    const dynamicValidateForm = ref<any>(cloneDeep(resetForm));
    const showModal = async (data: any) => {
      // 确保权限数据已加载
      if (!permissionLoaded) {
        await fetchPermissionOptions();
      }

      if (data) {
        // 直接使用传入的数据填充
        // 只保留叶子节点ID，父节点ID会被Tree的联动自动处理
        const permissionIds = data.permissions
          ? data.permissions
              .map((p: any) => String(p.id))
              .filter((id: string) => allLeafKeys.value.includes(id))
          : [];
        dynamicValidateForm.value = {
          id: data.id,
          name: data.name,
          description: data.description || '',
          sort: data.sort || 0,
          status: Boolean(data.status),
          permission_ids: permissionIds,
        };
      } else {
        dynamicValidateForm.value = {
          ...cloneDeep(resetForm),
        };
      }
      dialogTitle.value = data ? t('pages.edit') : t('pages.add');
      open.value = true;
    };

    // 处理删除
    const handleDelete = (record: any) => {
      Modal.confirm({
        title: t('pages.hint'),
        content: t('pages.doubleConfirmDel'),
        okText: t('pages.confirm'),
        cancelText: t('pages.cancel'),
        wrapClassName: 'confirm-dialog',
        onOk() {
          deleteRole(record.id)
            .then(() => {
              message.success(t('pages.opSuccessfully'));
              fetchRoles();
            })
            .catch(err => {
              message.error(err.message);
            });
        },
      });
    };

    // 处理表单数据提交
    const handleOk = () => {
      if (!submitting.value) {
        formRef.value.validateFields().then(() => {
          submitting.value = true;
          // 从树组件获取所有选中的 key（包括半选状态的父节点）
          const checkedKeys = treeRef.value?.checkedKeys || [];
          const halfCheckedKeys = treeRef.value?.halfCheckedKeys || [];
          const allPermissionIds = [...new Set([...checkedKeys, ...halfCheckedKeys])];
          const params: Record<string, any> = {
            name: dynamicValidateForm.value.name,
            description: dynamicValidateForm.value.description || '',
            sort: dynamicValidateForm.value.sort || 0,
            status: dynamicValidateForm.value.status ? 1 : 0,
            permission_ids: allPermissionIds,
          };
          const apiMethod = dynamicValidateForm.value.id ? updateRole : addRole;
          if (dynamicValidateForm.value.id) {
            params.id = dynamicValidateForm.value.id;
          }
          apiMethod(params as any)
            .then(() => {
              message.success(t('pages.opSuccessfully'));
              fetchRoles();
              open.value = false;
            })
            .catch(err => {
              message.error(err.message);
            })
            .finally(() => {
              submitting.value = false;
            });
        });
      }
    };
    let isInit = true;
    watch(
      () => pagination.value.current,
      () => {
        if (isInit) return;
        fetchRoles();
      },
    );
    watch(
      () => pagination.value.pageSize,
      () => {
        if (isInit) return;
        pagination.value.current = 1;
        fetchRoles();
      },
    );

    onMounted(() => {
      isInit = false;
      fetchRoles();
    });

    return {
      dataSource,
      loading,
      fetchRoles,
      resetPagination,
      dayjs,
      pagination,
      columns,
      t,
      h,
      open,
      showModal,
      handleOk,
      handleDelete,
      formRef,
      dialogTitle,
      dynamicValidateForm,
      handleTableChange,
      expand,
      tableHeight,
      EditOutlined,
      DeleteOutlined,
      permissionTreeData,
      checkAllPermissions,
      uncheckAllPermissions,
      treeRef,
    };
  },
});
</script>
