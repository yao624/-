<template>
  <a-modal
    :title="`${t('Product Set Details')} - ${productSet?.name || ''}`"
    :open="visible"
    width="900px"
    @cancel="handleCancel"
    :footer="null"
    class="product-set-modal"
  >
    <a-tabs v-model:activeKey="activeTab" type="card" class="product-tabs">
            <a-tab-pane
        v-for="(product, index) in productSet?.products || []"
        :key="index"
        :tab="getProductTabName(product, index)"
      >
                <div class="product-detail-container">
          <a-form
            :label-col="{ span: 4 }"
            :wrapper-col="{ span: 20 }"
            layout="horizontal"
            class="product-form"
          >
                        <a-form-item :label="t('Product ID')">
              <a-input
                v-model:value="product.source_id"
                readonly
                class="readonly-input"
              />
            </a-form-item>

            <a-form-item :label="t('Product Name')" required>
              <a-input
                :value="getProductField(index, 'name')"
                @update:value="updateProductField(index, 'name', $event)"
                :placeholder="t('Please enter product name')"
              />
            </a-form-item>

            <a-form-item :label="t('Product Description')">
              <a-textarea
                :value="getProductField(index, 'description')"
                @update:value="updateProductField(index, 'description', $event)"
                :rows="3"
                :placeholder="t('Please enter product description')"
                show-count
                :maxlength="500"
              />
            </a-form-item>

            <a-form-item :label="t('Link URL')">
              <a-input
                :value="getProductField(index, 'url')"
                @update:value="updateProductField(index, 'url', $event)"
                :placeholder="t('Please enter link URL')"
              />
            </a-form-item>

            <a-form-item :label="t('Price')" required>
              <a-input
                :value="getProductField(index, 'price')"
                @update:value="updateProductField(index, 'price', $event)"
                :placeholder="t('Please enter price')"
              />
            </a-form-item>

            <a-form-item :label="t('Currency')">
              <a-input
                :value="getProductField(index, 'currency')"
                readonly
                class="readonly-input"
              />
            </a-form-item>

            <a-form-item :label="t('Image URL')">
              <a-input
                :value="getProductField(index, 'image_url')"
                @update:value="updateProductField(index, 'image_url', $event)"
                :placeholder="t('Please enter image URL')"
              />
              <div v-if="getProductField(index, 'image_url')" class="image-preview">
                <img
                  :src="getProductField(index, 'image_url')"
                  :alt="t('Product image')"
                  class="preview-image"
                  @error="handleImageError"
                  @click="previewImage(getProductField(index, 'image_url'))"
                />
              </div>
            </a-form-item>

            <a-form-item :label="t('Video URL')">
              <a-input
                :value="getProductField(index, 'video_url')"
                @update:value="updateProductField(index, 'video_url', $event)"
                :placeholder="t('Please enter video URL')"
              />
              <div class="video-actions">
                <a-button
                  type="link"
                  size="small"
                  @click="showVideoModal(index)"
                  :icon="h(VideoCameraOutlined)"
                >
                  {{ t('Edit Video') }}
                </a-button>
                <a
                  v-if="getProductField(index, 'video_url')"
                  :href="getProductField(index, 'video_url')"
                  target="_blank"
                  class="video-link"
                >
                  {{ t('Preview Video') }}
                </a>
              </div>
            </a-form-item>

            <a-form-item :label="t('Product Tags')">
              <div class="tags-container">
                <a-tag
                  v-for="tag in product.tags"
                  :key="tag.id"
                  color="blue"
                  class="product-tag"
                >
                  {{ tag.name }}
                </a-tag>
                <span v-if="!product.tags || product.tags.length === 0" class="no-tags">
                  {{ t('No tags') }}
                </span>
              </div>
            </a-form-item>

            <a-form-item :wrapper-col="{ offset: 4, span: 20 }">
              <a-space size="large">
                <a-button
                  type="primary"
                  size="large"
                  :loading="saveLoading[index]"
                  @click="saveProduct(index)"
                  :icon="h(SaveOutlined)"
                >
                  {{ t('Save Product') }}
                </a-button>
                <a-button
                  size="large"
                  :loading="refreshLoading[index]"
                  @click="refreshProduct(index)"
                  :icon="h(ReloadOutlined)"
                >
                  {{ t('Refresh Product') }}
                </a-button>
                <a-button
                  size="large"
                  @click="resetProduct(index)"
                  :icon="h(UndoOutlined)"
                >
                  {{ t('Reset Changes') }}
                </a-button>
              </a-space>
            </a-form-item>
          </a-form>
        </div>
      </a-tab-pane>
    </a-tabs>

        <!-- 视频编辑模态框 -->
    <a-modal
      v-model:open="videoModalVisible"
      :title="t('Edit Video URL')"
      @ok="handleVideoOk"
      @cancel="handleVideoCancel"
      :confirm-loading="videoModalLoading"
      width="600px"
    >
      <a-form :label-col="{ span: 6 }" :wrapper-col="{ span: 18 }">
        <a-form-item :label="t('Video URL')" required>
          <a-input
            v-model:value="editingVideoUrl"
            :placeholder="t('Please enter video URL')"
          />
        </a-form-item>
        <a-form-item
          v-if="currentEditingProduct?.video_url"
          :label="t('Current Video')"
        >
          <a
            :href="currentEditingProduct.video_url"
            target="_blank"
            class="current-video-link"
          >
            {{ currentEditingProduct.video_url }}
          </a>
        </a-form-item>
      </a-form>
    </a-modal>
  </a-modal>
</template>

<script lang="ts">
import { defineComponent, ref, watch, h, nextTick, getCurrentInstance } from 'vue';
import { message } from 'ant-design-vue';
import {
  VideoCameraOutlined,
  ReloadOutlined,
  SaveOutlined,
  UndoOutlined,
} from '@ant-design/icons-vue';
import { updateProductApi, updateProductVideoApi } from '@/api/fb_bms';

interface ProductTag {
  id: string;
  name: string;
  created_at: string;
  user_id: string;
  user_name: string;
}

interface Product {
  id: string;
  currency: string;
  name: string;
  source_id: string;
  description: string;
  url: string;
  image_url: string;
  retailer_id: string;
  catalog_id: string; // 添加catalog_id字段
  price: string;
  video_url: string | null;
  tags: ProductTag[];
}

interface ProductSet {
  id: string;
  source_id: string;
  name: string;
  filter: any;
  products: Product[];
  tags: any[];
}

export default defineComponent({
  name: 'ProductSetModal',
  components: {
    VideoCameraOutlined,
    ReloadOutlined,
  },
  props: {
    visible: {
      type: Boolean,
      default: false,
    },
    productSet: {
      type: Object as () => ProductSet | null,
      default: null,
    },
  },
  emits: ['cancel'],
    setup(props, { emit }) {
    const instance = getCurrentInstance();
    const t = instance?.appContext.config.globalProperties.$t || ((key: string) => key);
    const activeTab = ref(0);
    const editingProducts = ref<Product[]>([]);
    const originalProducts = ref<Product[]>([]);
    const refreshLoading = ref<boolean[]>([]);
    const saveLoading = ref<boolean[]>([]);

    // 视频编辑相关
    const videoModalVisible = ref(false);
    const videoModalLoading = ref(false);
    const editingVideoUrl = ref('');
    const currentEditingProduct = ref<Product | null>(null);
    const currentEditingIndex = ref(-1);

    // 监听productSet变化，初始化编辑数据
    watch(() => props.productSet, (newVal) => {
      if (newVal && newVal.products) {
        const products = JSON.parse(JSON.stringify(newVal.products));
        editingProducts.value = products;
        originalProducts.value = JSON.parse(JSON.stringify(products));
        refreshLoading.value = new Array(newVal.products.length).fill(false);
        saveLoading.value = new Array(newVal.products.length).fill(false);
        activeTab.value = 0;
      }
    }, { immediate: true });

    const handleCancel = () => {
      emit('cancel');
    };

    // 保存产品信息
    const saveProduct = async (index: number) => {
      const product = editingProducts.value[index];
      const originalProduct = originalProducts.value[index];
      if (!product || !originalProduct) return;

      // 检查是否有修改
      const hasChanges = JSON.stringify(product) !== JSON.stringify(originalProduct);
      if (!hasChanges) {
        message.info('没有修改需要保存');
        return;
      }

      // 验证必填字段
      if (!product.name || !product.price) {
        message.error(t('Product name and price are required'));
        return;
      }

                  saveLoading.value[index] = true;
      try {
        // 检查是否有修改
        const hasChanges = JSON.stringify(product) !== JSON.stringify(originalProduct);
        if (!hasChanges) {
          message.info(t('No changes to save'));
          return;
        }

        // 处理价格转换
        let priceInCents: number | undefined;
        if (product.price) {
          try {
            const priceString = product.price.replace(/[$,\s]/g, '');
            const priceNumber = parseFloat(priceString);
            if (!isNaN(priceNumber)) {
              priceInCents = Math.round(priceNumber * 100);
            }
                        } catch (error) {
                console.error(t('Price conversion failed'), error);
                throw new Error(t('Invalid price format'));
              }
        }

        // 构建完整的payload，包含所有必要字段
        const payload = {
          id: product.id,
          name: product.name,
          description: product.description,
          url: product.url,
          image_url: product.image_url,
          price: priceInCents,
        };

        console.log('保存产品payload:', payload);
        await updateProductApi(payload);

        // 如果视频URL有变化，单独处理
        if (product.video_url !== originalProduct.video_url) {
          const videoPayload = {
            catalog_id: product.catalog_id,
            retailer_id: product.retailer_id,
            video_url: product.video_url,
          };
          console.log('保存产品时更新视频URL参数:', videoPayload);
          await updateProductVideoApi(videoPayload);
        }

        // 使用 nextTick 确保在下一个事件循环中更新，避免触发不必要的重渲染
        await nextTick();
        // 直接替换指定索引的数据，避免整个数组的响应式更新
        originalProducts.value.splice(index, 1, JSON.parse(JSON.stringify(product)));
        message.success(t('Product information saved successfully'));
      } catch (error) {
        console.error(t('Failed to save product'), error);
        message.error(t('Failed to save product'));
      } finally {
        saveLoading.value[index] = false;
      }
    };

    // 重置产品修改
    const resetProduct = (index: number) => {
      const originalProduct = originalProducts.value[index];
      if (originalProduct) {
        editingProducts.value[index] = JSON.parse(JSON.stringify(originalProduct));
        message.success(t('Changes reset successfully'));
      }
    };

    // 获取产品字段值
    const getProductField = (index: number, field: string): string => {
      const product = editingProducts.value[index];
      if (!product) return '';

      const value = product[field as keyof Product];

      // 处理不同类型的字段值
      if (typeof value === 'string') {
        return value || '';
      }
      if (typeof value === 'number') {
        return String(value);
      }
      if (Array.isArray(value)) {
        // 对于tags等数组字段，返回空字符串
        return '';
      }
      if (value === null || value === undefined) {
        return '';
      }

      return String(value);
    };

    // 更新产品字段值
    const updateProductField = (index: number, field: string, value: string) => {
      const product = editingProducts.value[index];
      if (product && product[field as keyof Product] !== value) {
        // 使用 Object.assign 进行浅拷贝更新，避免直接修改响应式对象
        editingProducts.value[index] = Object.assign({}, product, { [field]: value });
      }
    };

    // 显示视频编辑模态框
    const showVideoModal = (index: number) => {
      const product = editingProducts.value[index];
      currentEditingProduct.value = product;
      currentEditingIndex.value = index;
      editingVideoUrl.value = product.video_url || '';
      videoModalVisible.value = true;
    };

    // 处理视频编辑确认
    const handleVideoOk = async () => {
      if (!editingVideoUrl.value) {
        message.error(t('Please enter video URL'));
        return;
      }

      videoModalLoading.value = true;
      try {
        const product = currentEditingProduct.value;
        if (!product) return;

        // 根据需求更新参数，包含catalog_id, retailer_id, video_url
        const payload = {
          catalog_id: product.catalog_id, // 从product对象中获取catalog_id
          retailer_id: product.retailer_id, // 从product对象中获取retailer_id
          video_url: editingVideoUrl.value,
        };

        console.log('更新视频URL参数:', payload);
        await updateProductVideoApi(payload);

                // 更新本地数据
        editingProducts.value[currentEditingIndex.value].video_url = editingVideoUrl.value;

        message.success(t('Video URL updated successfully'));
        videoModalVisible.value = false;
      } catch (error) {
        console.error(t('Failed to update video URL'), error);
        message.error(t('Failed to update video URL'));
      } finally {
        videoModalLoading.value = false;
      }
    };

    // 取消视频编辑
    const handleVideoCancel = () => {
      videoModalVisible.value = false;
      editingVideoUrl.value = '';
      currentEditingProduct.value = null;
      currentEditingIndex.value = -1;
    };

    // 刷新产品
    const refreshProduct = async (index: number) => {
      refreshLoading.value[index] = true;
      try {
        // 接口暂未实现，显示提示
        await new Promise(resolve => setTimeout(resolve, 1000)); // 模拟请求
        message.info(t('Feature coming soon'));
      } catch (error) {
        console.error(t('Failed to save product'), error);
        message.error(t('Failed to save product'));
      } finally {
        refreshLoading.value[index] = false;
      }
    };

    // 处理图片加载错误
    const handleImageError = (event: Event) => {
      const img = event.target as HTMLImageElement;
      img.src = 'data:image/svg+xml;base64,PHN2ZyB3aWR0aD0iMTIwIiBoZWlnaHQ9IjEyMCIgdmlld0JveD0iMCAwIDEyMCAxMjAiIGZpbGw9Im5vbmUiIHhtbG5zPSJodHRwOi8vd3d3LnczLm9yZy8yMDAwL3N2ZyI+CjxyZWN0IHdpZHRoPSIxMjAiIGhlaWdodD0iMTIwIiBmaWxsPSIjRjVGNUY1Ii8+CjxwYXRoIGQ9Ik02MCA4MEMzNy45MDg2IDgwIDIwIDYyLjA5MTQgMjAgNDBDMjAgMTcuOTA4NiAzNy45MDg2IDAgNjAgMEM4Mi4wOTE0IDAgMTAwIDE7LjkwODYgMTAwIDQwQzEwMCA2Mi4wOTE0IDgyLjA5MTQgODAgNjAgODBaTTYwIDcwQzc2LjU2ODUgNzAgOTAgNTYuNTY4NSA5MCA0MEM5MCAyMy40MzE1IDc2LjU2ODUgMTAgNjAgMTBDNDMuNDMxNSAxMCAzMCAyMy40MzE1IDMwIDQwQzMwIDU2LjU2ODUgNDMuNDMxNSA3MCA2MCA3MFoiIGZpbGw9IiNEOUQ5RDkiLz4KPHN0ZyB3aWR0aD0iMTIwIiBoZWlnaHQ9IjEyMCIgdmlld0JveD0iMCAwIDEyMCAxMjAiIGZpbGw9Im5vbmUiIHhtbG5zPSJodHRwOi8vd3d3LnczLm9yZy8yMDAwL3N2ZyI+Cjx0ZXh0IHg9IjYwIiB5PSI2NSIgZm9udC1mYW1pbHk9IkFyaWFsLCBzYW5zLXNlcmlmIiBmb250LXNpemU9IjE0IiBmaWxsPSIjOTk5OTk5IiB0ZXh0LWFuY2hvcj0ibWlkZGxlIj7lm77niYfpooTorr08L3RleHQ+Cjwvc3ZnPgo=';
    };

    // 预览图片
    const previewImage = (imageUrl: string) => {
      window.open(imageUrl, '_blank');
    };

    // 获取产品Tab名称
    const getProductTabName = (product: Product, index: number) => {
      // 如果有标签，使用第一个标签的名称
      if (product.tags && product.tags.length > 0) {
        return product.tags[0].name;
      }
      // 没有标签则使用 product-<index>，index从1开始
      return `product-${index + 1}`;
    };

    return {
      t,
      activeTab,
      editingProducts,
      originalProducts,
      refreshLoading,
      saveLoading,
      videoModalVisible,
      videoModalLoading,
      editingVideoUrl,
      currentEditingProduct,
      handleCancel,
      saveProduct,
      resetProduct,
      getProductField,
      updateProductField,
      showVideoModal,
      handleVideoOk,
      handleVideoCancel,
      refreshProduct,
      handleImageError,
      previewImage,
      getProductTabName,
      h,
      VideoCameraOutlined,
      ReloadOutlined,
      SaveOutlined,
      UndoOutlined,
    };
  },
});
</script>

<style scoped>
.product-set-modal {
  :deep(.ant-modal-body) {
    padding: 24px;
    max-height: 80vh;
    overflow-y: auto;
  }
}

.product-tabs {
  :deep(.ant-tabs-card .ant-tabs-tab) {
    max-width: 200px;
    overflow: hidden;
    text-overflow: ellipsis;
    white-space: nowrap;
    border-radius: 8px 8px 0 0;
  }

  :deep(.ant-tabs-content-holder) {
    border: 1px solid #d9d9d9;
    border-radius: 0 8px 8px 8px;
    background: #fafafa;
  }
}

.product-detail-container {
  padding: 24px;
  background: white;
  border-radius: 8px;
  margin: 16px;
}

.product-form {
  .ant-form-item {
    margin-bottom: 24px;
  }

  .ant-form-item-label > label {
    font-weight: 500;
    color: #262626;
  }
}

.readonly-input {
  background-color: #f5f5f5 !important;
  color: #666;
  cursor: not-allowed;
}

.image-preview {
  margin-top: 12px;
  text-align: center;

  .preview-image {
    width: 120px;
    height: 120px;
    object-fit: cover;
    border-radius: 8px;
    border: 1px solid #d9d9d9;
    box-shadow: 0 2px 8px rgba(0, 0, 0, 0.1);
    cursor: pointer;
    transition: transform 0.2s ease;

    &:hover {
      transform: scale(1.05);
      box-shadow: 0 4px 12px rgba(0, 0, 0, 0.15);
    }
  }
}

.video-actions {
  margin-top: 8px;
  display: flex;
  align-items: center;
  gap: 12px;

  .video-link {
    color: #1890ff;
    text-decoration: none;
    font-size: 12px;

    &:hover {
      text-decoration: underline;
    }
  }
}

.tags-container {
  .product-tag {
    margin-right: 8px;
    margin-bottom: 4px;
    border-radius: 16px;
    padding: 4px 12px;
    font-size: 12px;
  }

  .no-tags {
    color: #999;
    font-style: italic;
    font-size: 14px;
  }
}



.current-video-link {
  color: #1890ff;
  word-break: break-all;

  &:hover {
    text-decoration: underline;
  }
}

/* 响应式设计 */
@media (max-width: 768px) {
  .product-detail-container {
    padding: 16px;
    margin: 8px;
  }

  .form-actions .ant-btn {
    min-width: 100px;
    height: 36px;
  }

  .product-set-modal :deep(.ant-modal-body) {
    padding: 16px;
  }
}

/* 滚动条样式 */
.product-set-modal :deep(.ant-modal-body)::-webkit-scrollbar {
  width: 6px;
}

.product-set-modal :deep(.ant-modal-body)::-webkit-scrollbar-track {
  background: #f1f1f1;
  border-radius: 3px;
}

.product-set-modal :deep(.ant-modal-body)::-webkit-scrollbar-thumb {
  background: #c1c1c1;
  border-radius: 3px;
}

.product-set-modal :deep(.ant-modal-body)::-webkit-scrollbar-thumb:hover {
  background: #a8a8a8;
}
</style>
