<template>
  <a-modal
    v-model:open="modalOpen"
    :title="t('新建素材库')"
    width="600px"
    @ok="handleSubmit"
    @cancel="handleCancel"
  >
    <a-form :model="formData" layout="vertical">
      <a-form-item :label="t('路径')">
        <a-input :value="libraryPath" disabled />
      </a-form-item>

      <a-form-item :label="t('素材库名称') + ' *'" required>
        <a-input
          v-model:value="formData.name"
          :placeholder="t('请输入素材库名称')"
          :maxlength="200"
          show-count
        />
      </a-form-item>

      <a-form-item :label="t('可见范围')">
        <a-radio-group v-model:value="formData.visibility">
          <a-radio value="company">{{ t('全公司') }}</a-radio>
          <a-radio value="self">{{ t('仅自己可见') }}</a-radio>
          <a-radio value="specified">{{ t('指定用户可见') }}</a-radio>
        </a-radio-group>
        <a-select
          v-if="formData.visibility === 'specified'"
          v-model:value="formData.specifiedUsers"
          mode="multiple"
          :placeholder="t('Q 请搜索')"
          show-search
          :filter-option="filterOption"
          style="width: 100%; margin-top: 8px"
        >
          <a-select-option v-for="user in users" :key="user.id" :value="user.id">
            {{ user.name }} ({{ user.email }})
          </a-select-option>
        </a-select>
      </a-form-item>

      <a-form-item :label="t('开启审核状态')">
        <a-radio-group v-model:value="formData.reviewEnabled">
          <a-radio value="enabled">{{ t('开启审核') }}</a-radio>
          <a-radio value="disabled">{{ t('关闭审核') }}</a-radio>
        </a-radio-group>
      </a-form-item>

      <a-form-item>
        <div class="hint-text">
          {{ t('企业素材库允许可见范围内用户读取/写入/删除') }}
        </div>
      </a-form-item>
    </a-form>
  </a-modal>
</template>

<script lang="ts" setup>
import { ref, computed, watch } from 'vue';
import { useI18n } from 'vue-i18n';
import { message } from 'ant-design-vue';
import { getUsers } from '../mock-data';
import {
  createMaterialLibraryEnterpriseLibrary,
  createMaterialLibraryFolder,
} from '@/api/material-library/folders';
import { useUserStore } from '@/store/user';

interface Props {
  open: boolean;
  libraryType?: 'my' | 'enterprise';
}

interface Emits {
  (e: 'update:open', value: boolean): void;
  (e: 'success'): void;
}

const props = defineProps<Props>();
const emit = defineEmits<Emits>();

const { t } = useI18n();
const userStore = useUserStore();

const modalOpen = computed({
  get: () => props.open,
  set: (value) => emit('update:open', value),
});

const libraryPath = computed(() => {
  if (props.libraryType === 'enterprise') {
    return t('企业素材库');
  }
  return t('我的素材库');
});

const formData = ref({
  name: '',
  visibility: 'company',
  specifiedUsers: [],
  reviewEnabled: 'disabled',
});

const users = ref<any[]>([]);

// 过滤选项
const filterOption = (input: string, option: any) => {
  return option.children.toLowerCase().indexOf(input.toLowerCase()) >= 0;
};

// 加载用户列表
const loadUsers = async () => {
  try {
    users.value = await getUsers();
  } catch (error) {
    console.error('加载用户列表失败:', error);
  }
};

const handleSubmit = async () => {
  if (!formData.value.name.trim()) {
    message.error(t('请输入素材库名称'));
    return;
  }

  if (formData.value.visibility === 'specified' && formData.value.specifiedUsers.length === 0) {
    message.error(t('请至少选择一个用户'));
    return;
  }

  try {
    const reviewEnabled = formData.value.reviewEnabled === 'enabled' ? 'enabled' : 'disabled';

    if (props.libraryType === 'enterprise') {
      await createMaterialLibraryEnterpriseLibrary({
        library_name: formData.value.name,
        visibility: formData.value.visibility as 'company' | 'self' | 'specified',
        specifiedUsers: formData.value.specifiedUsers,
        reviewEnabled,
        // MVP：企业维度 id 目前从登录态兜底
        enterprise_id: (userStore.info as any)?.enterprise_id ?? (userStore.info as any)?.enterpriseId ?? userStore.info?.id,
        manager_id: userStore.info?.id,
      });
    } else {
      // MVP：个人素材库用根文件夹落库
      const ownerId = userStore.info?.id;
      if (ownerId === undefined || ownerId === null || ownerId === '') {
        message.error(t('未获取到当前用户信息，请重新登录后重试'));
        return;
      }
      await createMaterialLibraryFolder({
        parent_id: 0,
        folder_name: formData.value.name,
        library_type: 0,
        owner_id: ownerId,
      });
    }

    message.success(t('创建成功'));
    emit('success');
    handleCancel();
  } catch (error) {
    message.error(t('创建失败'));
  }
};

const handleCancel = () => {
  modalOpen.value = false;
  formData.value = {
    name: '',
    visibility: 'company',
    specifiedUsers: [],
    reviewEnabled: 'disabled',
  };
};

// 监听弹窗显示，加载用户列表
watch(modalOpen, (newVal) => {
  if (newVal && formData.value.visibility === 'specified') {
    loadUsers();
  }
});
</script>

<style lang="less" scoped>
.hint-text {
  color: #999;
  font-size: 12px;
}
</style>

