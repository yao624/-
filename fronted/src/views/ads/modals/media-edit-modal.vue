<template>
  <a-modal
    v-model:open="visible"
    :title="t('Edit Media')"
    width="600px"
    :z-index="1020"
    @cancel="handleCancel"
    :footer="null"
    :mask-closable="false"
    destroy-on-close
  >
    <div class="media-edit-content">
      <div class="selection-options">
        <a-row :gutter="32" justify="center">
          <a-col :span="12">
            <div
              class="option-card"
              :class="{ active: selectedOption === 'library' }"
              @click="selectedOption = 'library'"
            >
              <div class="option-icon">
                <database-outlined />
              </div>
              <div class="option-title">{{ t('From Material Library') }}</div>
              <div class="option-description">{{ t('Select existing media from library') }}</div>
            </div>
          </a-col>
          <a-col :span="12">
            <div
              class="option-card"
              :class="{ active: selectedOption === 'upload' }"
              @click="selectedOption = 'upload'"
            >
              <div class="option-icon">
                <cloud-upload-outlined />
              </div>
              <div class="option-title">{{ t('Upload New Media') }}</div>
              <div class="option-description">{{ t('Upload a new media file') }}</div>
            </div>
          </a-col>
        </a-row>
      </div>

      <div class="action-buttons" v-if="selectedOption">
        <a-button
          type="primary"
          size="large"
          :loading="loading"
          @click="handleConfirm"
          :disabled="!selectedOption"
        >
          {{ selectedOption === 'library' ? t('Select from Library') : t('Upload Media') }}
        </a-button>
        <a-button @click="handleCancel" size="large">
          {{ t('Cancel') }}
        </a-button>
      </div>
    </div>

    <!-- Pick Objects组件 -->
    <pick-objects
      ref="materialPickerRef"
      :api="getMaterialsListWithType"
      :columns="materialColumns"
      :title="t('Select Media')"
      :multiple="false"
      :hide-trigger-button="true"
      @confirm:items-selected="handleMaterialSelected"
    />

    <!-- 上传组件 -->
    <upload-material
      ref="uploadModalRef"
      :hide-trigger-button="true"
      :media-type="mediaType"
      :auto-name-format="autoNameFormat"
      @confirm:uploaded="handleUploadCompleted"
    />
  </a-modal>
</template>

<script lang="ts">
import { defineComponent, ref, computed, h } from 'vue';
import { useI18n } from 'vue-i18n';
import { message } from 'ant-design-vue';
import {
  DatabaseOutlined,
  CloudUploadOutlined,
} from '@ant-design/icons-vue';
import { materialsList } from '@/api/materials';
import { uploadMaterialToFB } from '@/api/ads';
import PickObjects from '../create-ads-v2/pick-objects.vue';
import UploadMaterial from '../../materials/upload-material.vue';

interface MaterialItem {
  id: string;
  name: string;
  filename: string;
  url: string;
  notes: string;
  tags: any[];
}

export default defineComponent({
  name: 'MediaEditModal',
  components: {
    DatabaseOutlined,
    CloudUploadOutlined,
    PickObjects,
    UploadMaterial,
  },
  props: {
    open: {
      type: Boolean,
      default: false,
    },
    mediaType: {
      type: String as () => 'image' | 'video',
      required: true,
    },
    adAccountId: {
      type: String,
      required: true,
    },
    adId: {
      type: String,
      required: true,
    },
    adName: {
      type: String,
      default: '',
    },
    languageName: {
      type: String,
      default: '',
    },
  },
  emits: ['cancel', 'confirm'],
  setup(props, { emit }) {
    const { t } = useI18n();

    const visible = computed({
      get: () => props.open,
      set: value => {
        if (!value) {
          emit('cancel');
        }
      },
    });

    const selectedOption = ref<'library' | 'upload' | null>(null);
    const loading = ref(false);
    const materialPickerRef = ref(null);
    const uploadModalRef = ref(null);

    // 根据媒体类型生成自动命名格式
    const autoNameFormat = computed(() => {
      let notes = props.adName;
      if (props.languageName) {
        notes += ` (${props.languageName})`;
      }
      return {
        name: `edit ad (${props.adId})`,
        notes: notes,
      };
    });

    // 素材列表的列定义
    const materialColumns = computed(() => [
      { title: t('Name'), dataIndex: 'name', key: 'name' },
      { title: t('Filename'), dataIndex: 'filename', key: 'filename' },
      { title: t('Notes'), dataIndex: 'notes', key: 'notes' },
      {
        title: t('Preview'),
        dataIndex: 'url',
        key: 'url',
        customRender: ({ record }) => {
          if (isImage(record.filename)) {
            return h('img', {
              src: record.url,
              alt: record.filename,
              style: 'width: 50px; height: 50px; object-fit: cover;',
            });
          } else {
            return h('span', t('Video'));
          }
        },
      },
    ]);

    // 根据类型过滤的素材列表API
    const getMaterialsListWithType = async (params: any) => {
      // 🔧 修复：在API调用时传递type参数，让后端进行过滤
      const apiParams = {
        ...params,
        type: props.mediaType, // 传递媒体类型参数
      };

      console.log('🔍 Media Edit Modal - API call params:', {
        mediaType: props.mediaType,
        apiParams: apiParams,
      });

      const result = await materialsList(apiParams);

      // 🔧 优化：如果后端支持type过滤，就不需要客户端过滤
      // 但为了兼容性，保留客户端过滤作为备选
      if (result.data) {
        const originalCount = result.data.length;
        result.data = result.data.filter((item: MaterialItem) => {
          if (props.mediaType === 'image') {
            return isImage(item.filename);
          } else if (props.mediaType === 'video') {
            return isVideo(item.filename);
          }
          return false;
        });

        console.log('🔍 Media Edit Modal - Filtering results:', {
          mediaType: props.mediaType,
          originalCount: originalCount,
          filteredCount: result.data.length,
        });
      }

      return result;
    };

    // 判断是否为图片
    const isImage = (filename: string) => {
      const imageExtensions = ['jpeg', 'jpg', 'png', 'gif'];
      const extension = filename.split('.').pop()?.toLowerCase();
      return extension ? imageExtensions.includes(extension) : false;
    };

    // 判断是否为视频
    const isVideo = (filename: string) => {
      const videoExtensions = ['mp4', 'mov', 'avi', 'wmv'];
      const extension = filename.split('.').pop()?.toLowerCase();
      return extension ? videoExtensions.includes(extension) : false;
    };

    // 上传素材到FB
    const uploadMaterialToFBAPI = async (materialId: string) => {
      try {
        const uploadData = [{
          ad_account: props.adAccountId,
          material_id: materialId,
        }];

        const response = await uploadMaterialToFB(uploadData);
        return response;
      } catch (error) {
        console.error('上传到FB失败:', error);
        throw error;
      }
    };

    // 处理确认按钮点击
    const handleConfirm = async () => {
      if (selectedOption.value === 'library') {
        // 打开素材库选择
        materialPickerRef.value?.openModal();
      } else if (selectedOption.value === 'upload') {
        // 打开上传组件
        uploadModalRef.value?.onCreate();
      }
    };

    // 处理素材库选择完成
    const handleMaterialSelected = async (_keys: (string | number)[], rows: MaterialItem[]) => {
      if (rows.length === 0) return;

      const selectedMaterial = rows[0];
      loading.value = true;

      try {
        const response = await uploadMaterialToFBAPI(selectedMaterial.id);

        console.log('🔍 Upload to FB response:', response);

        // 修复：正确访问API响应结构 - 直接访问response字段
        const apiResponse = response as any; // 类型注解：这是API响应数据，不是Axios响应
        const success = apiResponse.success;
        const data = apiResponse.data || [];
        const message_text = apiResponse.message;

        if (success && Array.isArray(data) && data.length > 0) {
          const uploadedMedia = data[0];

          if (uploadedMedia.success) {
            message.success(message_text || t('Media selected and synchronized to Facebook successfully'));

            // 返回更新后的媒体信息
            emit('confirm', {
              materialId: selectedMaterial.id,
              mediaHash: uploadedMedia.hash,
              mediaUrl: uploadedMedia.url || '', // 视频URL可能为空，这是正常的
              mediaName: selectedMaterial.name,
            });
          } else {
            console.error('Upload failed for material:', uploadedMedia);
            message.error(t('Material selected successfully, but failed to synchronize to Facebook'));
          }
        } else {
          console.error('Invalid response structure:', { success, data, response });
          message.error(t('Material selected successfully, but failed to synchronize to Facebook'));
        }
      } catch (error) {
        console.error('上传素材到FB失败:', error);
        message.error(t('Material selected successfully, but failed to synchronize to Facebook'));
      } finally {
        loading.value = false;
      }
    };

    // 处理上传完成
    const handleUploadCompleted = async (materialData: any) => {
      console.log('Upload completed, material data:', materialData);

      // 检查是否有有效的ID
      if (!materialData || !materialData.id) {
        console.error('Material data is missing or does not contain ID:', materialData);
        message.warning(t('File uploaded successfully, but cannot upload to Facebook due to missing material ID'));
        // 即使没有ID，也可以关闭modal，因为文件上传是成功的
        emit('cancel');
        return;
      }

      loading.value = true;

      try {
        const response = await uploadMaterialToFBAPI(materialData.id);

        console.log('🔍 Upload to FB response:', response);

        // 修复：正确访问API响应结构 - 直接访问response字段
        const apiResponse = response as any; // 类型注解：这是API响应数据，不是Axios响应
        const success = apiResponse.success;
        const data = apiResponse.data || [];
        const message_text = apiResponse.message;

        if (success && Array.isArray(data) && data.length > 0) {
          const uploadedMedia = data[0];

          if (uploadedMedia.success) {
            message.success(message_text || t('Media uploaded and synchronized to Facebook successfully'));

            // 返回上传后的媒体信息
            emit('confirm', {
              materialId: materialData.id,
              mediaHash: uploadedMedia.hash,
              mediaUrl: uploadedMedia.url || '', // 视频URL可能为空，这是正常的
              mediaName: materialData.name,
            });
          } else {
            console.error('Upload failed for material:', uploadedMedia);
            message.error(t('File uploaded successfully, but failed to synchronize to Facebook'));
          }
        } else {
          console.error('Invalid response structure:', { success, data, response });
          message.error(t('File uploaded successfully, but failed to synchronize to Facebook'));
        }
      } catch (error) {
        console.error('上传素材到FB失败:', error);
        message.error(t('File uploaded successfully, but failed to synchronize to Facebook'));
      } finally {
        loading.value = false;
      }
    };

    // 处理取消
    const handleCancel = () => {
      selectedOption.value = null;
      loading.value = false;
      emit('cancel');
    };

    return {
      t,
      visible,
      selectedOption,
      loading,
      materialPickerRef,
      uploadModalRef,
      autoNameFormat,
      materialColumns,
      getMaterialsListWithType,
      handleConfirm,
      handleMaterialSelected,
      handleUploadCompleted,
      handleCancel,
    };
  },
});
</script>

<style lang="less" scoped>
.media-edit-content {
  padding: 20px 0;
}

.selection-options {
  margin-bottom: 32px;
}

.option-card {
  padding: 24px;
  border: 2px solid #f0f0f0;
  border-radius: 12px;
  text-align: center;
  cursor: pointer;
  transition: all 0.3s ease;
  height: 180px;
  display: flex;
  flex-direction: column;
  justify-content: center;

  &:hover {
    border-color: #1890ff;
    background-color: #fafbff;
  }

  &.active {
    border-color: #1890ff;
    background-color: #f6f9ff;
    box-shadow: 0 4px 12px rgba(24, 144, 255, 0.15);
  }
}

.option-icon {
  font-size: 36px;
  color: #1890ff;
  margin-bottom: 16px;
}

.option-title {
  font-size: 16px;
  font-weight: 600;
  color: #333;
  margin-bottom: 8px;
}

.option-description {
  font-size: 14px;
  color: #666;
  line-height: 1.5;
}

.action-buttons {
  display: flex;
  justify-content: center;
  gap: 16px;
}
</style>