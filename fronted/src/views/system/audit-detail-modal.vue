<template>
  <a-modal
    :visible="visible"
    :title="t('pages.audit.detail.title')"
    width="800px"
    :footer="null"
    @cancel="handleCancel"
  >
    <a-spin :spinning="loading">
      <a-descriptions v-if="detail" bordered :column="1" size="small">
        <a-descriptions-item :label="t('pages.audit.field.id')">
          {{ detail.id }}
        </a-descriptions-item>
        <a-descriptions-item :label="t('pages.audit.field.user_name')">
          {{ detail.user_name || '-' }}
        </a-descriptions-item>
        <a-descriptions-item :label="t('pages.audit.field.ip_address')">
          {{ detail.ip_address }}
        </a-descriptions-item>
        <a-descriptions-item :label="t('pages.audit.field.request_method')">
          <a-tag :color="getMethodColor(detail.request_method)">
            {{ detail.request_method }}
          </a-tag>
        </a-descriptions-item>
        <a-descriptions-item :label="t('pages.audit.field.request_path')">
          {{ detail.request_path }}
        </a-descriptions-item>
        <a-descriptions-item :label="t('pages.audit.field.response_status')">
          <a-tag :color="getStatusColor(detail.response_status)">
            {{ detail.response_status }}
          </a-tag>
        </a-descriptions-item>
        <a-descriptions-item :label="t('pages.audit.field.response_time')">
          {{ detail.response_time_formatted }}
        </a-descriptions-item>
        <a-descriptions-item :label="t('pages.audit.field.requested_at')">
          {{ detail.requested_at_formatted }}
        </a-descriptions-item>
        <a-descriptions-item :label="t('pages.audit.field.user_agent')">
          <div style="max-height: 100px; overflow-y: auto; word-break: break-all;">
            {{ detail.full_user_agent }}
          </div>
        </a-descriptions-item>
        <a-descriptions-item
          v-if="detail.query_parameters && Object.keys(detail.query_parameters).length > 0"
          :label="t('pages.audit.field.query_parameters')"
        >
          <pre style="max-height: 200px; overflow-y: auto; margin: 0;">{{ JSON.stringify(detail.query_parameters, null, 2) }}</pre>
        </a-descriptions-item>
        <a-descriptions-item
          v-if="detail.request_body && Object.keys(detail.request_body).length > 0"
          :label="t('pages.audit.field.request_body')"
        >
          <pre style="max-height: 300px; overflow-y: auto; margin: 0;">{{ JSON.stringify(detail.request_body, null, 2) }}</pre>
        </a-descriptions-item>
      </a-descriptions>
    </a-spin>
  </a-modal>
</template>

<script lang="ts" setup>
import { ref, watch } from 'vue';
import { useI18n } from 'vue-i18n';
import { message } from 'ant-design-vue';
import dayjs from 'dayjs';
import { getRequestLog } from '@/api/request_logs';
import type { RequestLog } from '@/api/request_logs';

const { t } = useI18n();

const props = defineProps<{
  visible: boolean;
  logId: string | null;
}>();

const emit = defineEmits<{
  (e: 'cancel'): void;
}>();

const loading = ref(false);
const detail = ref<RequestLog | null>(null);

const handleCancel = () => {
  emit('cancel');
};

const getMethodColor = (method: string) => {
  const colors: Record<string, string> = {
    GET: 'blue',
    POST: 'green',
    PUT: 'orange',
    DELETE: 'red',
    PATCH: 'purple',
  };
  return colors[method] || 'default';
};

const getStatusColor = (status: number) => {
  if (status >= 200 && status < 300) return 'success';
  if (status >= 300 && status < 400) return 'warning';
  if (status >= 400 && status < 500) return 'error';
  if (status >= 500) return 'error';
  return 'default';
};

const fetchDetail = async (id: string) => {
  loading.value = true;
  try {
    const res = await getRequestLog(id);
    // 将 UTC 时间转换为 UTC+8
    if (res.data && res.data.requested_at) {
      // 使用 dayjs.utc() 解析 UTC 时间，然后加8小时转为 UTC+8
      const utcTime = dayjs.utc(res.data.requested_at);
      res.data.requested_at_formatted = utcTime.add(8, 'hour').format('YYYY-MM-DD HH:mm:ss');
    }
    detail.value = res.data;
  } catch (error: any) {
    message.error(error.response?.data?.message || t('pages.audit.error.fetch_detail'));
  } finally {
    loading.value = false;
  }
};

watch(
  () => props.logId,
  (newId) => {
    if (newId && props.visible) {
      fetchDetail(newId);
    }
  },
  { immediate: true },
);

watch(
  () => props.visible,
  (newVisible) => {
    if (!newVisible) {
      detail.value = null;
    }
  },
);
</script>

<style scoped>
:deep(.ant-descriptions-item-label) {
  width: 150px;
  font-weight: 600;
}
</style>

