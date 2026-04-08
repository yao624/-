<template>
  <a-modal
    :title="t('Delete Confirmation')"
    :open="visible"
    :width="600"
    :confirmLoading="loading"
    @ok="handleSubmit"
    @cancel="
      () => {
        $emit('cancel');
      }
    "
  >
    <a-spin :spinning="loading">
      <div class="delete-confirm-content">
        <p>{{ t('Are you sure you want to delete the following item(s)?') }}</p>
        <div v-if="modelRef.items && modelRef.items.length > 0">
          <div v-for="(item, index) in modelRef.items" :key="index" class="delete-item">
            <strong>{{ item.name }}</strong>
            <div>ID: {{ item.source_id }}</div>
          </div>
        </div>
        <a-alert
          v-if="modelRef.items && modelRef.items.length > 0"
          type="warning"
          show-icon
          :message="t('This action cannot be undone!')"
        />
      </div>
    </a-spin>
  </a-modal>
</template>

<script lang="ts">
import { message } from 'ant-design-vue';
import type { PropType } from 'vue';
import { defineComponent, ref, reactive, toRaw, watchEffect } from 'vue';
import { useI18n } from 'vue-i18n';
import { deleteFbObject } from '@/api/ads';

interface DeleteItem {
  id: string;
  name: string;
  source_id: string;
}

interface DeleteAction {
  type: string;
  ids: string[];
  items: DeleteItem[];
}

export default defineComponent({
  name: 'DeleteModal',
  props: {
    visible: {
      type: Boolean,
      required: true,
    },
    model: {
      type: Object as PropType<DeleteAction | null>,
      default: () => null,
    },
  },
  emits: ['ok', 'cancel'],
  setup(props, { emit }) {
    const { t } = useI18n();
    const loading = ref(false);
    const initValues = () => ({
      type: 'campaign',
      ids: [],
      items: [],
    });
    const modelRef = reactive<DeleteAction>(initValues());

    watchEffect(() => {
      if (props.model) {
        const raw = toRaw(props.model);
        modelRef.type = raw.type;
        modelRef.ids = raw.ids;
        modelRef.items = raw.items;
      } else if (props.model === null) {
        Object.assign(modelRef, initValues());
      }
    });

    const handleSubmit = (e: Event) => {
      e.preventDefault();
      loading.value = true;

      const data = toRaw(modelRef);
      const req_params = {
        type: data.type,
        ids: data.ids,
      };

      deleteFbObject(req_params)
        .then(res => {
          message.success(res['message']);
          loading.value = false;
          emit('ok', res);
        })
        .catch(err => {
          console.log(err);
          loading.value = false;
          message.error('Delete Failed');
        });
    };

    return {
      t,
      modelRef,
      loading,
      handleSubmit,
    };
  },
});
</script>

<style lang="less" scoped>
.delete-confirm-content {
  margin-bottom: 16px;
}

.delete-item {
  margin-bottom: 8px;
  padding: 8px;
  background-color: #f9f9f9;
  border-radius: 4px;
}
</style>
