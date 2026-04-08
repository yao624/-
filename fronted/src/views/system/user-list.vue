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
          <a-button :loading="loading" type="primary" @click="fetchUsers">
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
        <template v-if="column['dataIndex'] === 'is_super'">
          <span>{{ text === 1 ? t('pages.yes') : t('pages.no') }}</span>
        </template>
        <template v-if="column['dataIndex'] === 'roles'">
          <a-tag v-for="role in text" :key="role.id" color="blue">{{ role.name }}</a-tag>
        </template>
        <template v-if="['created_at', 'updated_at'].includes(`${column['dataIndex']}`)">
          <span>{{ dayjs(text).format('YYYY-MM-DD HH:mm:ss') }}</span>
        </template>

        <template v-if="column['dataIndex'] === 'operation'">
          <a-button type="link" @click="showModal(record)" :icon="h(EditOutlined)"></a-button>
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
      <a-form ref="formRef" :label-col="{ span: 4 }" name="user_form" :model="dynamicValidateForm">
        <a-form-item name="name" :label="t('pages.name')" :rules="[{ required: true }]">
          <a-input v-model:value="dynamicValidateForm.name" />
        </a-form-item>
        <a-form-item name="email" :label="t('pages.email')" :rules="[{ required: true, type: 'email' }]">
          <a-input v-model:value="dynamicValidateForm.email" />
        </a-form-item>
        <a-form-item v-if="!dynamicValidateForm.id" name="password" :label="t('pages.password')" :rules="[{ required: true, min: 6 }]">
          <a-input-password v-model:value="dynamicValidateForm.password" />
        </a-form-item>
        <a-form-item name="is_super" :label="t('pages.isSuper')">
          <a-switch v-model:checked="dynamicValidateForm.is_super" />
        </a-form-item>
        <a-form-item v-if="!dynamicValidateForm.is_super" name="role_ids" :label="t('pages.roles.assign')">
          <a-select
            v-model:value="dynamicValidateForm.role_ids"
            mode="multiple"
            :options="roleOptions"
            placeholder=""
          />
        </a-form-item>
      </a-form>
    </a-modal>
  </page-container>
</template>
<script lang="ts">
import dayjs from 'dayjs';
import { cloneDeep } from 'lodash';
import { computed, defineComponent, ref, onMounted, watch, h } from 'vue';
import { message } from 'ant-design-vue';
import { PlusOutlined, EditOutlined } from '@ant-design/icons-vue';
import { getUsers, addUser, updateUser, getRoles } from '@/api/user/role_v2';
import { useI18n } from 'vue-i18n';

export default defineComponent({
  components: {
    PlusOutlined,
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
          title: t('pages.email'),
          dataIndex: 'email',
        },
        {
          title: t('pages.isSuper'),
          dataIndex: 'is_super',
        },
        {
          title: t('pages.roles'),
          dataIndex: 'roles',
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
      fetchUsers();
    };
    interface Query {
      name?: string;
      pageNo?: number;
      pageSize?: number;
    }
    const fetchUsers = () => {
      loading.value = true;
      const param = {
        name: pagination.value.name,
        pageNo: pagination.value.current,
        pageSize: pagination.value.pageSize,
      } as Query;

      getUsers(param)
        .then((res: any) => {
          dataSource.value = res.data;
          pagination.value.total = res.totalCount;
        })
        .finally(() => {
          loading.value = false;
        });
    };

    // 角色选项
    const roleOptions = ref<any[]>([]);
    const fetchRoleOptions = () => {
      getRoles()
        .then((res: any) => {
          roleOptions.value = res.data.map((role: any) => ({
            label: role.name,
            value: role.id,
          }));
        })
        .catch(console.error);
    };

    const open = ref<boolean>(false);
    const dialogTitle = ref<string>('');
    const formRef = ref<any>();
    const resetForm = {
      id: 0,
      name: '',
      email: '',
      password: '',
      is_super: false,
      role_ids: [],
    };

    const dynamicValidateForm = ref<any>(cloneDeep(resetForm));
    const showModal = (data: any) => {
      fetchRoleOptions();
      if (data) {
        dynamicValidateForm.value = {
          id: data.id,
          name: data.name,
          email: data.email,
          password: '',
          is_super: Boolean(data.is_super),
          role_ids: data.roles ? data.roles.map((r: any) => r.id) : [],
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
            email: dynamicValidateForm.value.email,
            is_super: dynamicValidateForm.value.is_super ? 1 : 0,
            role_ids: dynamicValidateForm.value.role_ids || [],
          };
          if (dynamicValidateForm.value.id) {
            params.id = dynamicValidateForm.value.id;
            updateUser(params)
              .then(() => {
                message.success(t('pages.opSuccessfully'));
                fetchUsers();
                open.value = false;
              })
              .catch(err => {
                message.error(err.message);
              })
              .finally(() => {
                submitting.value = false;
              });
          } else {
            params.password = dynamicValidateForm.value.password;
            addUser(params)
              .then(() => {
                message.success(t('pages.opSuccessfully'));
                fetchUsers();
                open.value = false;
              })
              .catch(err => {
                message.error(err.message);
              })
              .finally(() => {
                submitting.value = false;
              });
          }
        });
      }
    };
    let isInit = true;
    watch(
      () => pagination.value.current,
      () => {
        if (isInit) return;
        fetchUsers();
      },
    );
    watch(
      () => pagination.value.pageSize,
      () => {
        if (isInit) return;
        pagination.value.current = 1;
        fetchUsers();
      },
    );

    onMounted(() => {
      isInit = false;
      fetchUsers();
    });
    return {
      dataSource,
      loading,
      fetchUsers,
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
      handleTableChange,
      expand,
      tableHeight,
      EditOutlined,
      roleOptions,
    };
  },
});
</script>
