<template>
  <a-modal
    v-model:open="visible"
    :title="t('pages.materialEditorManage.materialPicker.title')"
    :width="1000"
    :footer="null"
    :destroy-on-close="true"
    @cancel="handleCancel"
  >
    <div class="material-picker-modal">
      <!-- Filter Section -->
      <div class="filter-section">
        <a-row :gutter="16">
          <a-col :span="8">
            <a-input
              v-model:value="searchText"
              :placeholder="t('pages.materialEditorManage.materialPicker.searchPlaceholder')"
              allow-clear
              @pressEnter="handleSearch"
            >
              <template #prefix>
                <SearchOutlined />
              </template>
            </a-input>
          </a-col>
          <a-col :span="6">
            <a-select
              v-model:value="materialTypeFilter"
              :placeholder="t('pages.materialEditorManage.materialPicker.materialType')"
              style="width: 100%"
              allow-clear
              @change="handleSearch"
            >
              <a-select-option value="image">{{ t('pages.materialEditorManage.materialPicker.type.image') }}</a-select-option>
              <a-select-option value="video">{{ t('pages.materialEditorManage.materialPicker.type.video') }}</a-select-option>
            </a-select>
          </a-col>
          <a-col :span="6">
            <a-button type="primary" @click="handleSearch">
              {{ t('pages.materialEditorManage.materialPicker.search') }}
            </a-button>
          </a-col>
        </a-row>
      </div>

      <!-- Selected Count -->
      <div class="selected-count">
        <span>{{ t('pages.materialEditorManage.materialPicker.selectedCount', { count: selectedMaterials.size }) }}</span>
      </div>

      <!-- Materials Grid/List -->
      <div class="materials-container">
        <a-spin :spinning="loading">
          <div v-if="materials.length > 0" class="materials-grid">
            <div
              v-for="material in materials"
              :key="material.id"
              class="material-item"
              :class="{ selected: selectedMaterials.has(material.id) }"
              @click="toggleMaterial(material)"
            >
              <!-- Preview Image -->
              <div class="material-preview">
                <img
                  v-if="material.thumbnail_url || material.file_url"
                  :src="material.thumbnail_url || material.file_url"
                  :alt="material.material_name"
                  @error="handleImageError"
                />
                <div v-else class="material-preview-placeholder">
                  <FileImageOutlined />
                </div>
                <!-- Selected Badge -->
                <div v-if="selectedMaterials.has(material.id)" class="selected-badge">
                  <CheckOutlined />
                </div>
              </div>
              <!-- Material Info -->
              <div class="material-info">
                <div class="material-name" :title="material.material_name">
                  {{ material.material_name }}
                </div>
                <div class="material-meta">
                  <a-tag v-if="material.material_type === 0" size="small">{{ t('pages.materialEditorManage.materialPicker.type.image') }}</a-tag>
                  <a-tag v-if="material.material_type === 1" size="small">{{ t('pages.materialEditorManage.materialPicker.type.video') }}</a-tag>
                  <span v-if="material.file_format" class="material-format">{{ material.file_format }}</span>
                </div>
              </div>
            </div>
          </div>
          <a-empty v-else :description="t('pages.materialEditorManage.materialPicker.noData')" />
        </a-spin>
      </div>

      <!-- Pagination -->
      <div v-if="totalCount > 0" class="pagination-container">
        <a-pagination
          v-model:current="currentPage"
          v-model:page-size="pageSize"
          :total="totalCount"
          :show-total="(total: number) => t('pages.materialEditorManage.materialPicker.paginationTotal', { total })"
          :show-size-changer="true"
          :show-quick-jumper="true"
          @change="handlePageChange"
        />
      </div>

      <!-- Footer Actions -->
      <div class="modal-footer">
        <a-space>
          <a-button @click="handleCancel">
            {{ t('pages.materialEditorManage.materialPicker.cancel') }}
          </a-button>
          <a-button type="primary" :disabled="selectedMaterials.size === 0" @click="handleConfirm">
            {{ t('pages.materialEditorManage.materialPicker.confirm') }}
          </a-button>
        </a-space>
      </div>
    </div>
  </a-modal>
</template>

<script setup lang="ts">
import { ref, computed, watch } from 'vue';
import { useI18n } from 'vue-i18n';
import { message } from 'ant-design-vue';
import { SearchOutlined, FileImageOutlined, CheckOutlined } from '@ant-design/icons-vue';
import type { MaterialType } from '../types';

interface MaterialItem {
  id: string | number;
  material_name: string;
  material_type: number; // 0: image, 1: video
  file_format: string;
  thumbnail_url?: string;
  file_url?: string;
  width?: number;
  height?: number;
}

const { t } = useI18n();

const props = defineProps<{
  open: boolean;
  materialType?: MaterialType; // Pre-filter by material type
  folderId?: string | number; // Filter by folder
}>();

const emit = defineEmits<{
  'update:open': [value: boolean];
  'confirm': [materials: MaterialItem[]];
}>();

// Modal visibility
const visible = computed({
  get: () => props.open,
  set: (val) => emit('update:open', val),
});

// State
const loading = ref(false);
const materials = ref<MaterialItem[]>([]);
const selectedMaterials = ref<Map<string | number, MaterialItem>>(new Map());
const searchText = ref('');
const materialTypeFilter = ref<number | undefined>();
const currentPage = ref(1);
const pageSize = ref(20);
const totalCount = ref(0);

// Convert material type string to number (0: image, 1: video)
const getMaterialTypeValue = (type?: MaterialType): number | undefined => {
  if (type === 'image') return 0;
  if (type === 'video') return 1;
  return undefined;
};

// Mock materials data (后续对接接口)
const mockMaterialsData: MaterialItem[] = [
  {
    id: '1',
    material_name: '夏日促销海报_001.jpg',
    material_type: 0,
    file_format: 'jpg',
    thumbnail_url: 'https://via.placeholder.com/200x150/FF6B6B/FFFFFF?text=Summer+001',
    file_url: 'https://via.placeholder.com/800x600/FF6B6B/FFFFFF?text=Summer+001',
    width: 800,
    height: 600,
  },
  {
    id: '2',
    material_name: '产品宣传视频_001.mp4',
    material_type: 1,
    file_format: 'mp4',
    thumbnail_url: 'https://via.placeholder.com/200x150/4ECDC4/FFFFFF?text=Video+001',
    file_url: 'https://sample-videos.com/video123/mp4/720/big_buck_bunny_720p_1mb.mp4',
    width: 1280,
    height: 720,
  },
  {
    id: '3',
    material_name: '节日促销海报_002.jpg',
    material_type: 0,
    file_format: 'jpg',
    thumbnail_url: 'https://via.placeholder.com/200x150/4ECDC4/FFFFFF?text=Holiday+002',
    file_url: 'https://via.placeholder.com/800x600/4ECDC4/FFFFFF?text=Holiday+002',
    width: 800,
    height: 600,
  },
  {
    id: '4',
    material_name: '品牌宣传图_003.png',
    material_type: 0,
    file_format: 'png',
    thumbnail_url: 'https://via.placeholder.com/200x150/95E1D3/FFFFFF?text=Brand+003',
    file_url: 'https://via.placeholder.com/800x600/95E1D3/FFFFFF?text=Brand+003',
    width: 800,
    height: 600,
  },
  {
    id: '5',
    material_name: '活动横幅_004.jpg',
    material_type: 0,
    file_format: 'jpg',
    thumbnail_url: 'https://via.placeholder.com/200x150/F38181/FFFFFF?text=Banner+004',
    file_url: 'https://via.placeholder.com/800x600/F38181/FFFFFF?text=Banner+004',
    width: 800,
    height: 600,
  },
  {
    id: '6',
    material_name: '新品发布视频_002.mp4',
    material_type: 1,
    file_format: 'mp4',
    thumbnail_url: 'https://via.placeholder.com/200x150/AA96DA/FFFFFF?text=Video+002',
    file_url: 'https://sample-videos.com/video123/mp4/720/big_buck_bunny_720p_2mb.mp4',
    width: 1280,
    height: 720,
  },
  {
    id: '7',
    material_name: '促销活动图_005.jpg',
    material_type: 0,
    file_format: 'jpg',
    thumbnail_url: 'https://via.placeholder.com/200x150/FCBAD3/FFFFFF?text=Promo+005',
    file_url: 'https://via.placeholder.com/800x600/FCBAD3/FFFFFF?text=Promo+005',
    width: 800,
    height: 600,
  },
  {
    id: '8',
    material_name: '品牌故事视频_003.mp4',
    material_type: 1,
    file_format: 'mp4',
    thumbnail_url: 'https://via.placeholder.com/200x150/FFFFD2/333333?text=Video+003',
    file_url: 'https://sample-videos.com/video123/mp4/720/big_buck_bunny_720p_3mb.mp4',
    width: 1920,
    height: 1080,
  },
];

// Load materials (使用 mock 数据，后续对接接口)
const loadMaterials = async () => {
  loading.value = true;
  try {
    // 模拟 API 延迟
    await new Promise(resolve => setTimeout(resolve, 300));

    // 过滤素材类型
    let filteredData = mockMaterialsData;
    const typeFilter = materialTypeFilter.value ?? getMaterialTypeValue(props.materialType);
    if (typeFilter !== undefined) {
      filteredData = filteredData.filter(m => m.material_type === typeFilter);
    }

    // 搜索过滤
    if (searchText.value) {
      const searchLower = searchText.value.toLowerCase();
      filteredData = filteredData.filter(m =>
        m.material_name.toLowerCase().includes(searchLower)
      );
    }

    // 分页
    totalCount.value = filteredData.length;
    const start = (currentPage.value - 1) * pageSize.value;
    const end = start + pageSize.value;
    materials.value = filteredData.slice(start, end);

    // TODO: 后续对接真实接口
    // const response = await getMaterialLibraryMaterials({
    //   folder_id: props.folderId || 14,
    //   material_type: materialTypeFilter.value ?? getMaterialTypeValue(props.materialType),
    //   global_search: searchText.value,
    //   pageNo: currentPage.value,
    //   pageSize: pageSize.value,
    // });
    // if (response.data) {
    //   materials.value = response.data.data || [];
    //   totalCount.value = response.data.totalCount || 0;
    // }
  } catch (error) {
    console.error('Failed to load materials:', error);
    message.error(t('pages.materialEditorManage.materialPicker.loadFailed'));
  } finally {
    loading.value = false;
  }
};

// Toggle material selection
const toggleMaterial = (material: MaterialItem) => {
  const newMap = new Map(selectedMaterials.value);
  if (newMap.has(material.id)) {
    newMap.delete(material.id);
  } else {
    newMap.set(material.id, material);
  }
  selectedMaterials.value = newMap;
};

// Handle search
const handleSearch = () => {
  currentPage.value = 1;
  loadMaterials();
};

// Handle page change
const handlePageChange = () => {
  loadMaterials();
};

// Handle cancel
const handleCancel = () => {
  selectedMaterials.value = new Map();
  visible.value = false;
};

// Handle confirm
const handleConfirm = () => {
  const selected = Array.from(selectedMaterials.value.values());
  emit('confirm', selected);
  selectedMaterials.value = new Map();
  visible.value = false;
};

// Handle image error - 使用 SVG 占位图替代缺失的图片
const handleImageError = (e: Event) => {
  const img = e.target as HTMLImageElement;
  // 使用 data URL 的 SVG 作为占位图
  img.src = 'data:image/svg+xml,%3Csvg xmlns="http://www.w3.org/2000/svg" width="150" height="120" viewBox="0 0 150 120"%3E%3Crect width="150" height="120" fill="%23f0f0f0"/%3E%3Ctext x="50%25" y="50%25" dominant-baseline="middle" text-anchor="middle" font-size="14" fill="%23999"%3E图片加载失败%3C/text%3E%3C/svg%3E';
  // 移除 error 事件监听，避免无限循环
  img.onerror = null;
};

// Set initial material type filter based on props
watch(() => props.materialType, (newType) => {
  materialTypeFilter.value = getMaterialTypeValue(newType);
}, { immediate: true });

// Load materials when modal opens
watch(visible, (val) => {
  if (val) {
    materialTypeFilter.value = getMaterialTypeValue(props.materialType);
    loadMaterials();
  }
});
</script>

<style scoped lang="less">
.material-picker-modal {
  .filter-section {
    margin-bottom: 16px;
    padding-bottom: 16px;
    border-bottom: 1px solid #f0f0f0;
  }

  .selected-count {
    margin-bottom: 16px;
    font-size: 14px;
    color: #52c41a;
    font-weight: 500;
  }

  .materials-container {
    min-height: 300px;
    max-height: 500px;
    overflow-y: auto;

    .materials-grid {
      display: grid;
      grid-template-columns: repeat(auto-fill, minmax(150px, 1fr));
      gap: 12px;

      .material-item {
        border: 2px solid #f0f0f0;
        border-radius: 8px;
        overflow: hidden;
        cursor: pointer;
        transition: all 0.2s;

        &:hover {
          border-color: #d9d9d9;
          box-shadow: 0 2px 8px rgba(0, 0, 0, 0.1);
        }

        &.selected {
          border-color: #1890ff;
          background-color: #e6f7ff;
        }

        .material-preview {
          position: relative;
          width: 100%;
          height: 120px;
          background-color: #fafafa;
          display: flex;
          align-items: center;
          justify-content: center;

          img {
            width: 100%;
            height: 100%;
            object-fit: cover;
          }

          .material-preview-placeholder {
            font-size: 32px;
            color: #d9d9d9;
          }

          .selected-badge {
            position: absolute;
            top: 4px;
            right: 4px;
            width: 20px;
            height: 20px;
            background-color: #1890ff;
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            color: white;
            font-size: 12px;
          }
        }

        .material-info {
          padding: 8px;

          .material-name {
            font-size: 12px;
            font-weight: 500;
            color: #262626;
            white-space: nowrap;
            overflow: hidden;
            text-overflow: ellipsis;
          }

          .material-meta {
            display: flex;
            align-items: center;
            gap: 4px;
            margin-top: 4px;

            .material-format {
              font-size: 11px;
              color: #8c8c8c;
            }
          }
        }
      }
    }
  }

  .pagination-container {
    margin-top: 16px;
    padding-top: 16px;
    border-top: 1px solid #f0f0f0;
    display: flex;
    justify-content: center;
  }

  .modal-footer {
    margin-top: 16px;
    padding-top: 16px;
    border-top: 1px solid #f0f0f0;
    display: flex;
    justify-content: flex-end;
  }
}
</style>
