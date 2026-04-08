<template>
  <a-modal
    :visible="visible"
    :title="model ? 'Edit Tenant' : 'Add Tenant'"
    :confirm-loading="loading"
    @ok="handleSubmit"
    @cancel="handleCancel"
    width="800px"
  >
    <a-form
      ref="formRef"
      :model="formData"
      :rules="rules"
      :label-col="{ span: 6 }"
      :wrapper-col="{ span: 18 }"
    >
      <a-form-item label="Email" name="email" required>
        <a-input
          v-model:value="formData.email"
          placeholder="Enter tenant email"
          :disabled="!!model"
        />
        <div class="ant-form-item-explain">
          This email will be used for login. Must match the user email in tenant database.
        </div>
      </a-form-item>

      <a-form-item label="Name" name="name">
        <a-input v-model:value="formData.name" placeholder="Enter tenant name" />
      </a-form-item>

      <a-form-item label="Database Name" name="database_name" required>
        <a-input
          v-model:value="formData.database_name"
          placeholder="Enter database name (lowercase, numbers, underscore only)"
          :disabled="!!model"
        />
        <div class="ant-form-item-explain">
          Only lowercase letters, numbers, and underscores are allowed.
        </div>
      </a-form-item>

      <a-form-item label="Database Host" name="database_host">
        <a-input
          v-model:value="formData.database_host"
          placeholder="127.0.0.1"
        />
      </a-form-item>

      <a-form-item label="Database Port" name="database_port">
        <a-input-number
          v-model:value="formData.database_port"
          :min="1"
          :max="65535"
          placeholder="3306"
          style="width: 100%"
        />
      </a-form-item>

      <a-form-item label="Database Username" name="database_username" required>
        <a-input
          v-model:value="formData.database_username"
          placeholder="Enter database username"
        />
      </a-form-item>

      <a-form-item label="Database Password" name="database_password" required>
        <a-input-password
          v-model:value="formData.database_password"
          placeholder="Enter database password"
        />
      </a-form-item>

      <a-form-item label="Status" name="status">
        <a-select v-model:value="formData.status" placeholder="Select status">
          <a-select-option value="active">Active</a-select-option>
          <a-select-option value="inactive">Inactive</a-select-option>
          <a-select-option value="suspended">Suspended</a-select-option>
        </a-select>
      </a-form-item>

      <a-form-item v-if="!model" label="Options">
        <a-space direction="vertical">
          <a-checkbox v-model:checked="formData.create_database">
            Create database automatically
          </a-checkbox>
          <a-checkbox v-model:checked="formData.run_migrations" :disabled="!formData.create_database">
            Run migrations after creating database
          </a-checkbox>
        </a-space>
      </a-form-item>
    </a-form>
  </a-modal>
</template>

<script lang="ts">
import { defineComponent, reactive, ref, watch } from 'vue';
import { createTenantApi, updateTenantApi } from '@/api/tenants';
import { message } from 'ant-design-vue';

export default defineComponent({
  name: 'TenantModal',
  props: {
    visible: {
      type: Boolean,
      default: false,
    },
    model: {
      type: Object,
      default: null,
    },
  },
  emits: ['ok', 'cancel'],
  setup(props, { emit }) {
    const formRef = ref();
    const loading = ref(false);

    const formData = reactive({
      email: '',
      name: '',
      database_name: '',
      database_host: '127.0.0.1',
      database_port: 3306,
      database_username: '',
      database_password: '',
      status: 'active',
      create_database: true,
      run_migrations: false,
    });

    const rules = {
      email: [
        { required: true, message: 'Please enter email', trigger: 'blur' },
        { type: 'email', message: 'Please enter a valid email', trigger: 'blur' },
      ],
      database_name: [
        { required: true, message: 'Please enter database name', trigger: 'blur' },
        {
          pattern: /^[a-z0-9_]+$/,
          message: 'Only lowercase letters, numbers, and underscores are allowed',
          trigger: 'blur',
        },
      ],
      database_host: [{ required: false }],
      database_port: [{ required: false }],
      database_username: [
        { required: true, message: 'Please enter database username', trigger: 'blur' },
      ],
      database_password: [
        { required: true, message: 'Please enter database password', trigger: 'blur' },
      ],
      status: [{ required: false }],
    } as any;

    // Watch for model changes
    watch(
      () => props.model,
      (newModel) => {
        if (newModel) {
          Object.assign(formData, {
            email: newModel.email || '',
            name: newModel.name || '',
            database_name: newModel.database_name || '',
            database_host: newModel.database_host || '127.0.0.1',
            database_port: newModel.database_port || 3306,
            database_username: newModel.database_username || '',
            database_password: newModel.database_password || '',
            status: newModel.status || 'active',
            create_database: false,
            run_migrations: false,
          });
        } else {
          // Reset form
          Object.assign(formData, {
            email: '',
            name: '',
            database_name: '',
            database_host: '127.0.0.1',
            database_port: 3306,
            database_username: '',
            database_password: '',
            status: 'active',
            create_database: true,
            run_migrations: false,
          });
        }
        formRef.value?.resetFields();
      },
      { immediate: true },
    );

    const handleSubmit = async () => {
      try {
        await formRef.value.validate();
        loading.value = true;

        const submitData = { ...formData };
        // Remove password from update if not changed (optional)
        if (props.model && !submitData.database_password) {
          delete submitData.database_password;
        }

        if (props.model) {
          // Update
          await updateTenantApi(props.model.id, submitData);
          message.success('Tenant updated successfully');
        } else {
          // Create
          await createTenantApi(submitData);
          message.success('Tenant created successfully');
        }

        emit('ok');
      } catch (error: any) {
        if (error.errorFields) {
          // Validation errors
          return;
        }
        message.error(error.response?.data?.message || 'Operation failed');
      } finally {
        loading.value = false;
      }
    };

    const handleCancel = () => {
      formRef.value?.resetFields();
      emit('cancel');
    };

    return {
      formRef,
      formData,
      rules,
      loading,
      handleSubmit,
      handleCancel,
    };
  },
});
</script>

