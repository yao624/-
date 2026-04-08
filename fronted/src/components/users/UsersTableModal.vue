<template>
  <div>
    <a-modal
      v-if="modalVisible"
      v-model:open="modalVisible"
      :title="t('pages.modal.users')"
      @cancel="handleCancel"
      :width="800"
    >
      <a-table
        :columns="userColumns"
        :data-source="modalUserData"
        :pagination="false"
        :scroll="{ y: 800, x: 800 }"
      />
      <template #footer>
        <a-button type="primary" @click="handleCancel">{{ t('pages.cancel') }}</a-button>
      </template>
    </a-modal>
  </div>
</template>

<script lang="ts">
import type { PropType } from 'vue';
import { defineComponent, ref, watchEffect } from 'vue';
import { useI18n } from 'vue-i18n';

interface UserDataItem {
  id: number;
  source_id: string;
  name: string;
  role_human: string;
}

export default defineComponent({
  props: {
    userData: {
      type: Array as PropType<UserDataItem[]>,
      default: () => [],
    },
    openModal: {
      type: Boolean,
      default: false,
    },
    closeUserModal: {
      type: Function as PropType<() => void>,
      required: true,
    },
  },

  setup(props) {
    const { t } = useI18n();
    const modalVisible = ref(props.openModal);
    const modalUserData = ref(props.userData);
    const userColumns = ref([
      { title: t('pages.id'), dataIndex: 'fb_account_id' },
      { title: t('pages.modal.source.id'), dataIndex: 'source_id' },
      { title: t('pages.name'), dataIndex: 'name' },
      { title: t('pages.modal.role.human'), dataIndex: 'role_human' },
    ]);

    const handleCancel = () => {
      props.closeUserModal();
    };

    watchEffect(() => {
      modalVisible.value = props.openModal;
      modalUserData.value = props.userData;
    });

    return {
      modalVisible,
      modalUserData,
      userColumns,
      handleCancel,
      t,
    };
  },
});
</script>
