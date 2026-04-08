<template>
  <div>
    <a-modal
      v-if="modalVisible"
      v-model:open="modalVisible"
      :title="t('pages.modal.users')"
      @cancel="handleCancel"
      :width="1000"
    >
      <a-table
        :columns="userColumns"
        :data-source="modalUserData"
        :pagination="false"
        :scroll="{ y: 800, x: 800 }"
        :row-key="record => record.id"
      >
        <template #bodyCell="{ column, text }">
          <template v-if="['email'].includes(`${column['dataIndex']}`)">
            <span>{{ text }}</span>
            <copy-outlined @click="copyCell(text)" />
          </template>
        </template>
      </a-table>
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
import useClipboard from 'vue-clipboard3';
import { message } from 'ant-design-vue';
import { CopyOutlined } from '@ant-design/icons-vue';

interface UserDataItem {
  id: number;
  source_id: string;
  name: string;
  email: string;
  role: string;
  twofa: string;
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
  components: {
    CopyOutlined,
  },
  setup(props) {
    const { t } = useI18n();
    const modalVisible = ref(props.openModal);
    const modalUserData = ref(props.userData);
    const userColumns = ref([
      { title: t('pages.id'), dataIndex: 'fb_account_id' },
      { title: t('pages.name'), dataIndex: 'name' },
      { title: t('pages.role'), dataIndex: 'role' },
      { title: t('pages.email'), dataIndex: 'email' },
      { title: t('pages.bms.two_fac_status'), dataIndex: 'two_fac_status' },
    ]);

    const handleCancel = () => {
      props.closeUserModal();
    };
    const { toClipboard } = useClipboard();
    const copyCell = async (text: any) => {
      try {
        await toClipboard(text);
        message.success(t('pages.linkCopied'));
      } catch (e) {
        console.error(e);
      }
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
      copyCell,
    };
  },
});
</script>
