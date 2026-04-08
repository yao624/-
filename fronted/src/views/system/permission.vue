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
          <a-button type="primary" @click="showModal('')">
            <template #icon>
              <plus-outlined />
            </template>
            {{ t('pages.add') }}
          </a-button>
        </a-col>
        <a-space>
          <a-button :loading="loading" type="primary" @click="fetchPages">
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
        :rangeSelection="false"
        :data-source="dataSource"
        @change="handleTableChange"
        :scroll="{ y: tableHeight, x: 2000 }"
        v-model:pagination="pagination"
        :loading="loading"
        :row-key="record => record.id"
    >
      <template #bodyCell="{ column, text, record }">
        <template v-if="column['dataIndex'] === 'active'">
          <span>{{ text === false ? t('pages.deActive') : t('pages.active') }}</span>
        </template>
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
          <a-button type="link" @click="showModal(record)" :icon="h(EditOutlined)"></a-button>
          <!--          <a-button type="link" @click="showModal(record)" :icon="h(DeleteOutlined)"></a-button>-->
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
      <a-form
          ref="formRef"
          :label-col="{ span: 4 }"
          name="dynamic_form_nest_item"
          :model="dynamicValidateForm"
      >
        <a-form-item name="pid" :label="t('pages.parent')" :rules="[{ required: true }]">
          <a-tree-select
              v-model:value="dynamicValidateForm.pid"
              :tree-data="parentTreeData"
              placeholder=""
              allow-clear
          />
        </a-form-item>
        <a-form-item name="name" :label="t('pages.name')" :rules="[{ required: true }]">
          <a-input v-model:value="dynamicValidateForm.name" />
        </a-form-item>
        <a-form-item name="alias" :label="t('pages.alias')" :rules="[{ required: true }]">
          <a-input v-model:value="dynamicValidateForm.alias" placeholder="pages.xxxx.name" />
        </a-form-item>

        <a-form-item name="slug" :label="t('pages.identifier')">
          <a-input v-model:value="dynamicValidateForm.slug" placeholder="user:query，user:create" />
        </a-form-item>

        <a-form-item name="type" :label="t('pages.type')" :rules="[{ required: true }]">
          <a-radio-group v-model:value="dynamicValidateForm.type">
            <a-radio value="menu">{{ t('pages.typeMenu') }}</a-radio>
            <a-radio value="button">{{ t('pages.typeButton') }}</a-radio>
            <a-radio value="data">{{ t('pages.typeData') }}</a-radio>
          </a-radio-group>
        </a-form-item>
        <a-form-item
            v-if="dynamicValidateForm.type === 'menu'"
            name="path"
            :label="t('pages.path')"
            :rules="[{ required: true }]"
        >
          <a-input v-model:value="dynamicValidateForm.path" placeholder="/ads/index" />
        </a-form-item>
        <a-form-item
            v-if="dynamicValidateForm.type === 'menu'"
            name="component"
            :label="t('pages.component')"
        >
          <a-input
              v-model:value="dynamicValidateForm.component"
              placeholder="@views/ads/index.vue"
          />
        </a-form-item>
        <a-form-item
            v-if="dynamicValidateForm.type === 'menu'"
            name="icon"
            :label="t('pages.icon')"
        >
          <a-input v-model:value="dynamicValidateForm.icon" />
        </a-form-item>

        <a-form-item name="sort" :label="t('pages.sort')">
          <a-input v-model:value="dynamicValidateForm.sort" />
        </a-form-item>
        <a-form-item name="redirect" :label="t('pages.redirect')">
          <a-input v-model:value="dynamicValidateForm.redirect" />
        </a-form-item>
        <a-form-item name="status" :label="t('pages.status')">
          <a-switch v-model:checked="dynamicValidateForm.status" />
        </a-form-item>
        <a-form-item name="hideInMenu" :label="t('pages.hideInMenu')">
          <a-switch v-model:checked="dynamicValidateForm.hideInMenu" />
        </a-form-item>
        <a-form-item name="hideChildrenInMenu" :label="t('pages.hideChildrenInMenu')">
          <a-switch v-model:checked="dynamicValidateForm.hideChildrenInMenu" />
        </a-form-item>
        <a-form-item name="hideInBreadcrumb" :label="t('pages.hideInBreadcrumb')">
          <a-switch v-model:checked="dynamicValidateForm.hideInBreadcrumb" />
        </a-form-item>
      </a-form>
    </a-modal>
  </page-container>
</template>
<script lang="ts">
import dayjs from 'dayjs';
import { cloneDeep } from 'lodash';
import { computed, defineComponent, ref, onMounted, watch, h } from 'vue';
import { message, TreeSelect } from 'ant-design-vue';
import { PlusOutlined, EditOutlined, DeleteOutlined } from '@ant-design/icons-vue';
import { getPermissions, savePermission, updatePermission } from '@/api/user/role_v2';
import { useI18n } from 'vue-i18n';

export default defineComponent({
  components: {
    PlusOutlined,
    ATreeSelect: TreeSelect,
  },
  setup() {
    const { t } = useI18n();
    const sortedInfo = ref();
    const expand = ref(true);
    const screenHeight = ref<number>(window.innerHeight);
    const tableHeight = screenHeight.value < 1010 ? ref('55vh') : ref('65vh');

    watch(expand, newValue => {
      if (screenHeight.value < 1010) {
        tableHeight.value = newValue ? '55vh' : '70vh';
      } else {
        tableHeight.value = newValue ? '55vh' : '70vh';
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
          title: t('pages.identifier'),
          dataIndex: 'slug',
        },
        {
          title: t('pages.type'),
          dataIndex: 'type',
        },
        {
          title: t('pages.status'),
          dataIndex: 'status',
        },
        {
          title: t('pages.sort'),
          dataIndex: 'sort',
          sorter: true,
          sortOrder: sorted.field === 'sort' && sorted.order,
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
    const handleTableChange: any['onChange'] = (_, __, sorter) => {
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
      fetchPages();
    };
    interface Query {
      name?: string;
    }
    const fetchPages = () => {
      loading.value = true;
      const param = {
        name: pagination.value.name,
        pageNo: pagination.value.current,
        pageSize: pagination.value.pageSize,
      } as Query;

      getPermissions(param)
          .then((res: any) => {
            dataSource.value = res.data;
            pagination.value.total = res.totalCount;
          })
          .finally(() => {
            loading.value = false;
          });
    };

    const inputVisible = ref<boolean>(false);
    const inputValue = ref<string>('');
    const inputRef = ref();

    const open = ref<boolean>(false);
    const dialogTitle = ref<string>('');
    const formRef = ref<any>();
    const resetForm = {
      id: 0,
      name: '',
      slug: '',
      alias: '',
      type: '',
      component: '',
      redirect: '',
      icon: '',
      path: '',
      sort: 0,
      pid: 0,
      hideInMenu: false,
      hideChildrenInMenu: false,
      hideInBreadcrumb: false,
      status: true,
    };

    // 父级权限选项（树形）
    const parentTreeData = ref<any[]>([]);
    const fetchParentOptions = () => {
      getPermissions()
          .then((res: any) => {
            const list = res.data || res || [];
            // 数据已是树形结构，直接转换格式
            parentTreeData.value = [
              {
                id: 0,
                pid: 0,
                name: t('pages.topLevel'),
                key: 0,
                title: t('pages.topLevel'),
                value: 0,
                children: [],
              },
              ...list.map((item: any) => ({
                id: item.id,
                pid: item.pid,
                name: item.name,
                key: item.id,
                title: item.name,
                value: item.id,
                children: (item.children || []).map((child: any) => ({
                  id: child.id,
                  pid: child.pid,
                  name: child.name,
                  key: child.id,
                  title: child.name,
                  value: child.id,
                  children: [],
                })),
              })),
            ];
          })
          .catch(console.error);
    };
    fetchParentOptions();

    const dynamicValidateForm = ref<any>(cloneDeep(resetForm));
    const showModal = (data: any) => {
      if (data) {
        // 编辑时处理数据类型转换
        dynamicValidateForm.value = {
          ...cloneDeep(data),
          status: Boolean(data.status), // 1/0 -> true/false
          pid: data.pid === 0 ? 0 : (data.pid || undefined), // 0 -> 0 (顶级菜单)
          hideInMenu: Boolean(data.hide_in_menu),
          hideChildrenInMenu: Boolean(data.hide_children_in_menu),
          hideInBreadcrumb: Boolean(data.hide_in_breadcrumb),
        };
      } else {
        dynamicValidateForm.value = cloneDeep(resetForm);
      }
      dialogTitle.value = data ? t('pages.edit') : t('pages.add');
      open.value = true;
    };

    // 处理表单数据提交
    const handleOk = () => {
      if (!submitting.value) {
        formRef.value.validateFields().then(() => {
          submitting.value = true;
          const params: Record<string, any> = {
            name: dynamicValidateForm.value.name,
            slug: dynamicValidateForm.value.slug,
            pid: dynamicValidateForm.value.pid || 0,
            alias: dynamicValidateForm.value.alias || '',
            type: dynamicValidateForm.value.type || '',
            component: dynamicValidateForm.value.component || '',
            redirect: dynamicValidateForm.value.redirect || '',
            icon: dynamicValidateForm.value.icon || '',
            path: dynamicValidateForm.value.path || '',
            sort: dynamicValidateForm.value.sort || 0,
            hide_in_menu: dynamicValidateForm.value.hideInMenu ? 1 : 0,
            hide_children_in_menu: dynamicValidateForm.value.hideChildrenInMenu ? 1 : 0,
            hide_in_breadcrumb: dynamicValidateForm.value.hideInBreadcrumb ? 1 : 0,
            status: dynamicValidateForm.value.status ? 1 : 0,
          };
          // 根据是否有 id 决定新增还是编辑
          const apiMethod = dynamicValidateForm.value.id ? updatePermission : savePermission;
          if (dynamicValidateForm.value.id) {
            params.id = dynamicValidateForm.value.id;
          }
          apiMethod(params as any)
              .then(() => {
                message.success(t('pages.opSuccessfully'));
                fetchPages();
                open.value = false;
              })
              .catch(err => {
                message.error(err.response.data.message || err.message);
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
          fetchPages();
        },
    );
    watch(
        () => pagination.value.pageSize,
        () => {
          if (isInit) return;
          pagination.value.current = 1;
          fetchPages();
        },
    );

    onMounted(() => {
      isInit = false;
      fetchPages();
    });
    return {
      dataSource,
      loading,
      fetchPages,
      resetPagination,
      dayjs,
      pagination,
      columns,
      t,
      h,
      open,
      showModal,
      handleOk,
      formRef,
      dialogTitle,
      dynamicValidateForm,
      inputVisible,
      inputValue,
      inputRef,
      handleTableChange,
      expand,
      tableHeight,
      EditOutlined,
      DeleteOutlined,
      parentTreeData,
    };
  },
});
</script>
