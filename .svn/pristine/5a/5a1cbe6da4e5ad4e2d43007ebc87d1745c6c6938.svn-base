<template>
  <a-modal
    :open="visible"
    :title="t('Choose Video Thumbnail')"
    width="800px"
    :z-index="1020"
    @cancel="handleCancel"
    @ok="handleOk"
    :confirm-loading="saving"
    :mask-closable="false"
    destroy-on-close
  >
    <div class="thumbnail-modal-content">
      <!-- 系统生成的缩略图 -->
      <div class="thumbnail-section">
        <h4>{{ t('System Generated Thumbnails') }}</h4>
        <div v-if="thumbnailsLoading" class="loading-container">
          <a-spin size="large" />
          <div class="loading-text">{{ t('Loading thumbnails...') }}</div>
        </div>
        <div v-else-if="systemThumbnails.length > 0" class="thumbnails-grid">
          <div
            v-for="(thumbnail, index) in systemThumbnails"
            :key="thumbnail.id"
            class="thumbnail-item"
            :class="{ selected: selectedThumbnail?.id === thumbnail.id }"
            @click="selectThumbnail(thumbnail)"
          >
            <img :src="thumbnail.url" :alt="`Thumbnail ${index + 1}`" class="thumbnail-image" />
            <div v-if="thumbnail.is_preferred" class="preferred-badge">
              {{ t('Preferred') }}
            </div>
            <div class="thumbnail-info">
              <div class="dimensions">{{ thumbnail.width }}x{{ thumbnail.height }}</div>
            </div>
          </div>
        </div>
        <div v-else class="no-thumbnails">
          <inbox-outlined style="font-size: 48px; color: #ccc" />
          <div>{{ t('No system thumbnails available') }}</div>
        </div>
      </div>

      <!-- 自定义缩略图 -->
      <div class="thumbnail-section">
        <h4>{{ t('Custom Thumbnail') }}</h4>
        <div class="custom-thumbnail-area">
          <div v-if="customThumbnail" class="custom-thumbnail-preview">
            <div
              class="thumbnail-item"
              :class="{ selected: selectedThumbnail?.type === 'custom' }"
              @click="selectCustomThumbnail"
            >
              <img :src="customThumbnail.url" :alt="t('Custom thumbnail')" class="thumbnail-image" />
              <div class="custom-badge">{{ t('Custom') }}</div>
            </div>
          </div>
          <div class="custom-thumbnail-actions">
            <a-button
              type="primary"
              ghost
              @click="() => { customUploading = true; showCustomThumbnailModal = true; }"
              :loading="customUploading"
            >
              <upload-outlined />
              {{ customThumbnail ? t('Change Custom Thumbnail') : t('Upload Custom Thumbnail') }}
            </a-button>
          </div>
        </div>
      </div>

      <!-- 选中的缩略图信息 -->
      <div v-if="selectedThumbnail" class="selected-info">
        <a-alert :message="getSelectedThumbnailInfo()" type="info" show-icon />
      </div>
    </div>

    <!-- 自定义缩略图上传Modal -->
    <media-edit-modal
      :open="showCustomThumbnailModal"
      :media-type="'image'"
      :ad-account-id="adAccount"
      :ad-id="videoId"
      :ad-name="uploadNotes"
      :language-name="''"
      @cancel="() => { customUploading = false; showCustomThumbnailModal = false; }"
      @confirm="handleCustomThumbnailSelected"
    />
  </a-modal>
</template>

<script lang="ts" setup>
import { ref, computed, onMounted, watch } from 'vue';
import { message } from 'ant-design-vue';
import { useI18n } from 'vue-i18n';
import {
  InboxOutlined,
  UploadOutlined,
  FileImageOutlined,
} from '@ant-design/icons-vue';
import { getVideoThumbnails } from '@/api/ads';
import MediaEditModal from './media-edit-modal.vue';

interface SystemThumbnail {
  id: string;
  url: string;
  is_preferred: boolean;
  height: number;
  width: number;
  scale: number;
}

interface CustomThumbnail {
  type: 'custom';
  url: string;
  hash: string;
}

interface SelectedThumbnail extends SystemThumbnail {
  type?: 'system' | 'custom';
  hash?: string;
}

interface Props {
  visible: boolean;
  adAccount: string;
  videoId: string;
  uploadNotes?: string;
  isMultiLanguage?: boolean;
}

interface Emits {
  (e: 'update:visible', value: boolean): void;
  (e: 'thumbnail-selected', data: { type: 'system' | 'custom'; thumbnail: SelectedThumbnail }): void;
}

const props = withDefaults(defineProps<Props>(), {
  visible: false,
  uploadNotes: '',
  isMultiLanguage: false,
});

const emit = defineEmits<Emits>();
const { t } = useI18n();

// 状态管理
const thumbnailsLoading = ref(false);
const saving = ref(false);
const customUploading = ref(false);
const showCustomThumbnailModal = ref(false);

// 缩略图数据
const systemThumbnails = ref<SystemThumbnail[]>([]);
const customThumbnail = ref<CustomThumbnail | null>(null);
const selectedThumbnail = ref<SelectedThumbnail | null>(null);

// 计算属性
const uploadNotes = computed(() => {
  return props.uploadNotes || `Video thumbnail for ${props.videoId}`;
});

// 获取系统缩略图
const fetchSystemThumbnails = async () => {
  if (!props.adAccount || !props.videoId) return;

  thumbnailsLoading.value = true;
  try {
    console.log('🔍 Fetching thumbnails for:', {
      adAccount: props.adAccount,
      videoId: props.videoId,
    });

    const response: any = await getVideoThumbnails(props.adAccount, props.videoId);

    console.log('🔍 Raw API Response:', response);
    console.log('🔍 Response Success:', response.success);
    console.log('🔍 Response Data:', response.data);
    console.log('🔍 Is data array:', Array.isArray(response.data));

    // 检查各个条件 - 直接从response访问（因为request拦截器已经返回了response.data）
    const hasSuccess = response.success === true;
    const hasDataArray = Array.isArray(response.data);

    console.log('🔍 Condition checks:', {
      hasSuccess,
      hasDataArray,
      allConditionsMet: hasSuccess && hasDataArray,
    });

    // 检查API响应格式
    if (hasSuccess && hasDataArray) {
      systemThumbnails.value = response.data;

      console.log('✅ Successfully parsed', systemThumbnails.value.length, 'thumbnails:', systemThumbnails.value);

      // 默认选中preferred的缩略图
      const preferredThumbnail = systemThumbnails.value.find(thumb => thumb.is_preferred);
      if (preferredThumbnail) {
        selectThumbnail(preferredThumbnail);
        console.log('✅ Auto-selected preferred thumbnail:', preferredThumbnail.id);
      } else {
        console.log('ℹ️ No preferred thumbnail found, selecting first thumbnail');
        if (systemThumbnails.value.length > 0) {
          selectThumbnail(systemThumbnails.value[0]);
        }
      }
    } else {
      console.error('❌ API response validation failed:', {
        hasSuccess,
        hasDataArray,
        responseSuccess: response.success,
        responseData: response.data,
        dataType: typeof response.data,
      });
      message.error(t('Invalid API response format'));
    }
  } catch (error) {
    console.error('❌ Failed to fetch thumbnails:', error);
    message.error(t('Failed to load system thumbnails'));
  } finally {
    thumbnailsLoading.value = false;
  }
};

// 选择系统缩略图
const selectThumbnail = (thumbnail: SystemThumbnail) => {
  selectedThumbnail.value = {
    ...thumbnail,
    type: 'system',
  } as SelectedThumbnail;
};

// 选择自定义缩略图
const selectCustomThumbnail = () => {
  if (customThumbnail.value) {
    selectedThumbnail.value = {
      id: 'custom',
      url: customThumbnail.value.url,
      hash: customThumbnail.value.hash,
      type: 'custom',
      is_preferred: false,
      height: 0,
      width: 0,
      scale: 1,
    };
  }
};

// 处理自定义缩略图选择
const handleCustomThumbnailSelected = (mediaData: any) => {
  console.log('🔍 Custom thumbnail upload completed:', mediaData);

  customUploading.value = false;

  if (mediaData && mediaData.mediaHash && mediaData.mediaUrl) {
    // 直接使用 media-edit-modal 返回的数据结构
    customThumbnail.value = {
      type: 'custom',
      url: mediaData.mediaUrl,
      hash: mediaData.mediaHash,
    };

    console.log('✅ Custom thumbnail set successfully:', customThumbnail.value);

    // 自动选中新上传的自定义缩略图
    selectCustomThumbnail();
  } else {
    console.error('❌ Invalid mediaData structure:', mediaData);
    message.error(t('Failed to process uploaded thumbnail'));
  }

  showCustomThumbnailModal.value = false;
};

// 获取选中缩略图信息
const getSelectedThumbnailInfo = () => {
  if (!selectedThumbnail.value) return '';

  if (selectedThumbnail.value.type === 'custom') {
    return t('Selected: Custom uploaded thumbnail');
  } else {
    const info = selectedThumbnail.value.is_preferred
      ? t('Selected: System preferred thumbnail')
      : t('Selected: System generated thumbnail');
    return `${info} (${selectedThumbnail.value.width}x${selectedThumbnail.value.height})`;
  }
};

// 取消操作
const handleCancel = () => {
  emit('update:visible', false);
};

// 确认选择
const handleOk = () => {
  if (!selectedThumbnail.value) {
    message.warning(t('Please select a thumbnail'));
    return;
  }

  saving.value = true;

  try {
    emit('thumbnail-selected', {
      type: selectedThumbnail.value.type || 'system',
      thumbnail: selectedThumbnail.value,
    });

    emit('update:visible', false);
  } catch (error) {
    message.error(t('Failed to update thumbnail'));
  } finally {
    saving.value = false;
  }
};

// 监听visible变化
watch(() => props.visible, (newVal) => {
  if (newVal && props.adAccount && props.videoId) {
    console.log('🔍 Video thumbnail modal opened, current state:', {
      systemThumbnails: systemThumbnails.value.length,
      customThumbnail: customThumbnail.value,
      selectedThumbnail: selectedThumbnail.value,
    });

    // 重置系统缩略图状态，但保留自定义缩略图
    systemThumbnails.value = [];
    // 不重置 customThumbnail，保持用户上传的自定义缩略图
    // customThumbnail.value = null; // 移除这行，保持自定义缩略图
    selectedThumbnail.value = null;

    // 加载系统缩略图
    fetchSystemThumbnails();

    console.log('🔍 After reset, custom thumbnail preserved:', customThumbnail.value);
  }
});

onMounted(() => {
  if (props.visible && props.adAccount && props.videoId) {
    fetchSystemThumbnails();
  }
});
</script>

<style lang="less" scoped>
.thumbnail-modal-content {
  .thumbnail-section {
    margin-bottom: 24px;

    h4 {
      margin-bottom: 16px;
      font-weight: 600;
      color: #262626;
    }
  }

  .loading-container {
    display: flex;
    flex-direction: column;
    align-items: center;
    padding: 40px 0;

    .loading-text {
      margin-top: 16px;
      color: #666;
    }
  }

  .thumbnails-grid {
    display: grid;
    grid-template-columns: repeat(auto-fill, minmax(120px, 1fr));
    gap: 12px;
  }

  .thumbnail-item {
    position: relative;
    border: 2px solid #d9d9d9;
    border-radius: 8px;
    overflow: hidden;
    cursor: pointer;
    transition: all 0.2s ease;

    &:hover {
      border-color: #1890ff;
      transform: translateY(-2px);
      box-shadow: 0 4px 12px rgba(24, 144, 255, 0.2);
    }

    &.selected {
      border-color: #1890ff;
      box-shadow: 0 0 0 2px rgba(24, 144, 255, 0.2);
    }

    .thumbnail-image {
      width: 100%;
      height: 80px;
      object-fit: cover;
      display: block;
    }

    .preferred-badge {
      position: absolute;
      top: 4px;
      right: 4px;
      background: #52c41a;
      color: white;
      font-size: 10px;
      padding: 2px 6px;
      border-radius: 4px;
      font-weight: 500;
    }

    .custom-badge {
      position: absolute;
      top: 4px;
      right: 4px;
      background: #722ed1;
      color: white;
      font-size: 10px;
      padding: 2px 6px;
      border-radius: 4px;
      font-weight: 500;
    }

    .thumbnail-info {
      padding: 4px 8px;
      background: #fafafa;
      border-top: 1px solid #f0f0f0;

      .dimensions {
        font-size: 11px;
        color: #666;
        text-align: center;
      }
    }
  }

  .no-thumbnails {
    display: flex;
    flex-direction: column;
    align-items: center;
    padding: 40px 0;
    color: #666;

    div {
      margin-top: 16px;
    }
  }

  .custom-thumbnail-area {
    display: flex;
    align-items: center;
    gap: 16px;

    .custom-thumbnail-preview {
      .thumbnail-item {
        width: 120px;
      }
    }

    .custom-thumbnail-actions {
      flex: 1;
    }
  }

  .selected-info {
    margin-top: 16px;
  }
}
</style>