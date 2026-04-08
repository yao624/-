<template>
  <a-modal
    v-model:open="modalOpen"
    :title="t('新建文件夹')"
    width="500px"
    @ok="handleSubmit"
    @cancel="handleCancel"
  >
    <a-form :model="formData" layout="vertical">
      <a-form-item
        :label="t('文件夹名称') + ' *'"
        required
        :validate-status="isNameInvalid ? 'error' : undefined"
        :help="nameInvalidMessage"
      >
        <a-input
          v-model:value="formData.name"
          :placeholder="t('请输入文件夹名称')"
          :maxlength="200"
          show-count
        />
      </a-form-item>
      <a-form-item :label="t('备注')">
        <a-textarea
          v-model:value="formData.notes"
          :placeholder="t('请输入备注')"
          :rows="4"
          :maxlength="500"
          show-count
        />
      </a-form-item>
    </a-form>
  </a-modal>
</template>

<script lang="ts" setup>
import { ref, computed } from 'vue';
import { useI18n } from 'vue-i18n';
import { message } from 'ant-design-vue';
import { useUserStore } from '@/store/user';
import { createMaterialLibraryFolder } from '@/api/material-library/folders';

interface Props {
  open: boolean;
  parentFolderId?: string;
  siblingNames?: string[];
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

const formData = ref({
  name: '',
  notes: '',
});

const normalizeNameForCompare = (name: string) => String(name || '').trim().toLowerCase();
const normalizedSiblingNameSet = computed(
  () => new Set((props.siblingNames || []).map((n) => normalizeNameForCompare(String(n)))),
);
const isDuplicateName = computed(() => {
  const current = normalizeNameForCompare(formData.value.name);
  if (!current) return false;
  return normalizedSiblingNameSet.value.has(current);
});
const isRootLevelCreate = computed(() => {
  const parentId = props.parentFolderId ?? 0;
  return String(parentId) === '0';
});
const isReservedRootName = computed(() => {
  if (!isRootLevelCreate.value) return false;
  return normalizeNameForCompare(formData.value.name) === normalizeNameForCompare('素材库');
});
const isNameInvalid = computed(() => isDuplicateName.value || isReservedRootName.value);
const nameInvalidMessage = computed(() => {
  if (isDuplicateName.value) return t('同级目录已存在同名文件夹');
  if (isReservedRootName.value) return t('该层级不允许新建名为“素材库”的文件夹');
  return undefined;
});

const handleSubmit = async () => {
  if (!formData.value.name.trim()) {
    message.error(t('请输入文件夹名称'));
    return;
  }
  if (isNameInvalid.value) {
    message.error(String(nameInvalidMessage.value || t('文件夹名称不合法')));
    return;
  }

  try {
    if (props.parentFolderId === 'favorites') {
      message.error(t('收藏视图下不支持新建文件夹'));
      return;
    }

    const parent_id = props.parentFolderId ?? 0;
    if (String(parent_id) === '0') {
      const ownerId = userStore.info?.id;
      if (ownerId === undefined || ownerId === null || ownerId === '') {
        message.error(t('未获取到当前用户信息，请重新登录后重试'));
        return;
      }
      await createMaterialLibraryFolder({
        parent_id,
        folder_name: formData.value.name,
        library_type: 0,
        owner_id: ownerId,
        notes: formData.value.notes,
      });
    } else {
    await createMaterialLibraryFolder({
      parent_id,
      folder_name: formData.value.name,
      notes: formData.value.notes,
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
    notes: '',
  };
};
</script>

