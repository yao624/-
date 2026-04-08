<template>
  <a-modal
    :title="t('Products')"
    :open="visible"
    width="1200px"
    :confirmLoading="loading"
    @ok="handleSubmit"
    @cancel="
      () => {
        $emit('cancel');
      }
    "
  >
    <a-table
      :columns="columns"
      :data-source="model.data_source['products']"
      :row-key="record => record.id"
      bordered
      sticky
size="small"
    >
      <template #bodyCell="{ column, text, record }">
        <template
          v-if="
            ['name', 'description', 'url', 'image_url', 'video_url'].includes(
              `${column['dataIndex']}`,
            )
          "
        >
          <a-popover trigger="click" placement="topLeft">
            <template #content>
              {{ text }}
            </template>
            <copy-outlined
              style="color: #1677ff"
              v-if="text"
              @click="copyCell(text)"
              @click.stop=""
            />
            {{ text }}
          </a-popover>
        </template>
        <template v-if="column['dataIndex'] === 'operation'">
          <a>
            <a @click="showEditModal(record)"><edit-outlined /></a>
            <a-divider type="vertical" />
            <a @click="showTagModal(record)"><tag-outlined /></a>
            <a-divider type="vertical" />
            <a @click="showVideoModal(record)"><video-camera-outlined /></a>
          </a>
        </template>
        <template v-if="column['dataIndex'] === 'tags'">
          <div>
            <a-tag v-for="item in record.tags" :key="item.id">{{ item.name }}</a-tag>
          </div>
        </template>
      </template>
    </a-table>

    <tag-modal
      :model="tagModal.model"
      :visible="tagModal.visible"
      @cancel="
        () => {
          tagModal.visible = false;
        }
      "
      @ok="
        () => {
          tagModal.visible = false;
        }
      "
    />

    <a-modal
      v-model:visible="isEditModalVisible"
      title="Edit Item"
      @ok="handleEditOk"
      @cancel="handleEditCancel"
      :confirm-loading="isEditModalSaving"
      :destroyOnClose="true"
    >
      <edit-product-modal
        ref="editModalRef"
        :name="currentProduct.name"
        :description="currentProduct.description"
        :url="currentProduct.url"
        :image_url="currentProduct.image_url"
        :price="currentProduct.price"
      ></edit-product-modal>
    </a-modal>

    <!-- 添加视频 Modal -->
    <a-modal
      v-model:visible="isVideoModalVisible"
      :title="t('Add Video')"
      @ok="handleVideoOk"
      @cancel="handleVideoCancel"
      :confirm-loading="isVideoModalSaving"
      :destroyOnClose="true"
    >
      <a-form :label-col="{ span: 6 }" :wrapper-col="{ span: 18 }">
        <a-form-item :label="t('Video URL')" required>
          <a-input v-model:value="videoUrl" :placeholder="t('Please input video URL')" />
        </a-form-item>
        <a-form-item v-if="currentVideoProduct?.video_url" :label="t('Current Video')">
          <a :href="currentVideoProduct.video_url" target="_blank">
            {{ currentVideoProduct.video_url }}
          </a>
        </a-form-item>
      </a-form>
    </a-modal>
  </a-modal>
</template>

<script lang="ts">
import type { PropType } from 'vue';
import { defineComponent, ref, reactive, toRaw, watchEffect } from 'vue';
import { useI18n } from 'vue-i18n';
// import SharePixelModal from './share-pixel-modal.vue';
import {
  CopyOutlined,
  EditOutlined,
  TagOutlined,
  VideoCameraOutlined,
} from '@ant-design/icons-vue';
import useClipboard from 'vue-clipboard3';
import { message } from 'ant-design-vue';
import TagModal from '../../utils/tag-modal.vue';
import EditProductModal from './edit-product-modal.vue';
import type { ProductListModel } from '@/utils/fb-interfaces';
import { updateProductApi, updateProductVideoApi } from '@/api/fb_bms';

const formLayout = {
  labelCol: {
    xs: { span: 24 },
    sm: { span: 7 },
  },
  wrapperCol: {
    xs: { span: 24 },
    sm: { span: 13 },
  },
};

interface TagAction {
  data_source: any[];
}

export default defineComponent({
  name: 'productListModal',
  components: {
    // SharePixelModal,
    CopyOutlined,
    EditOutlined,
    TagOutlined,
    VideoCameraOutlined,
    TagModal,
    EditProductModal,
  },
  props: {
    visible: {
      type: Boolean,
      required: true,
    },
    model: {
      type: Object as PropType<TagAction | null>,
      default: () => null,
    },
    canShare: {
      type: Boolean,
      default: true,
    },
  },
  emits: ['ok', 'cancel'],
  setup(props, { emit }) {
    const { t } = useI18n();
    const loading = ref(false);
    const initValues = () => ({
      data_source: [],
    });
    const modelRef = reactive<TagAction>(initValues());
    const { toClipboard } = useClipboard();

    watchEffect(() => {
      if (props.model) {
        const raw = toRaw(props.model);
        modelRef.data_source = raw.data_source;
        console.log('model ref');
        console.log(modelRef);
        console.log(raw);
      } else if (props.model === null) {
        Object.assign(modelRef, initValues());
      }
    });

    const handleSubmit = () => {
      // e.preventDefault();
      // loading.value = true;
      emit('ok');
    };

    const columns = [
      {
        title: t('pages.adc.index'),
        dataIndex: 'index',
        customRender: ({ index }) => {
          return `${index + 1}`;
        },
        width: 100,
        align: 'center',
      },
      {
        title: t('ID'),
        dataIndex: 'source_id',
        width: 150,
        align: 'center',
        resizable: true,
      },
      {
        title: t('Retailer'),
        dataIndex: 'retailer_id',
        minWidth: 120,
        align: 'center',
        resizable: true,
      },
      {
        title: t('pages.tag'),
        dataIndex: 'tags',
        align: 'center',
        minWidth: 80,
      },
      {
        title: t('Name'),
        dataIndex: 'name',
        minWidth: 100,
        align: 'center',
        resizable: true,
        ellipsis: true,
      },
      {
        title: t('Desc'),
        dataIndex: 'description',
        minWidth: 100,
        align: 'center',
        resizable: true,
        ellipsis: true,
      },
      {
        title: t('URL'),
        dataIndex: 'url',
        minWidth: 160,
        align: 'center',
        resizable: true,
        ellipsis: true,
      },
      {
        title: t('Image url'),
        dataIndex: 'image_url',
        minWidth: 120,
        align: 'center',
        resizable: true,
        ellipsis: true,
      },
      {
        title: t('Video url'),
        dataIndex: 'video_url',
        minWidth: 120,
        align: 'center',
        resizable: true,
        ellipsis: true,
      },
      {
        title: t('Price'),
        dataIndex: 'price',
        width: 100,
        align: 'center',
        resizable: true,
      },
      {
        title: t('pages.operation'),
        width: 100,
        dataIndex: 'operation',
        align: 'center',
        fixed: 'right',
      },
    ];

    const copyCell = async (text: any) => {
      try {
        await toClipboard(text);
        message.success('copied');
      } catch (e) {
        console.error(e);
      }
    };

    // tagModal
    const tagModal = reactive({
      visible: false,
      model: null,
    });
    const showTagModal = record => {
      tagModal.model = {
        ids: [record['id']],
        action: 'add',
        tagList: record['tags']?.map(item => item.name),
        modelType: 'fbcatalogproduct',
      };
      tagModal.visible = true;
    };

    // edit product modal
    const editModalRef = ref<InstanceType<typeof EditProductModal> | null>(null);
    const currentProduct = ref<ProductListModel | null>(null);
    const isEditModalVisible = ref(false);
    const isEditModalSaving = ref(false);

    const showEditModal = record => {
      console.log('edit product');
      currentProduct.value = record;
      isEditModalVisible.value = true;
    };

    const handleEditOk = async () => {
      console.log('handle edit ok');

      if (!editModalRef.value) return; // 防御性检查

      isEditModalSaving.value = true;
      try {
        const formData = await editModalRef.value.getFormDataAndValidate();
        if (formData) {
          console.log(formData);
          // --- 价格转换逻辑 开始 ---
          let priceInCents: number | undefined;
          try {
            // a. 获取原始价格字符串，例如 "$24.99" 或 "24.99"
            const priceString = formData.price;

            // b. 清理字符串：移除货币符号($)和任何非数字、非小数点的字符
            //    这个正则表达式会移除除了数字(0-9)和小数点(.)之外的所有字符
            const cleanedPriceString = priceString.replace(/[^0-9.]/g, '');

            // c. 将清理后的字符串转换为浮点数
            const numericPrice = parseFloat(cleanedPriceString);

            // d. 检查转换结果是否为有效数字
            if (isNaN(numericPrice)) {
              // 如果转换失败（例如输入了无效字符 "abc"），抛出错误
              throw new Error('Invalid price format');
            }

            // e. 乘以 100 并四舍五入得到整数（以分为单位）
            //    使用 Math.round() 处理可能的浮点数精度问题
            priceInCents = Math.round(numericPrice * 100);
          } catch (parseError) {
            console.error('Error parsing price:', formData.price, parseError);
            message.error(t('Invalid price format. Please enter a valid number like 24.99.')); // 提示用户
            isEditModalSaving.value = false; // 停止 loading
            return; // 阻止 API 调用
          }

          const payload = {
            ...formData,
            id: currentProduct.value?.id,
            price: priceInCents,
            bm_id: modelRef.data_source['fb_bm_id'],
          };
          try {
            await updateProductApi(payload);
            message.success(t('pages.op.successfully'));
            isEditModalVisible.value = false; // 成功后再关闭 Modal
          } catch (err) {
            console.error('API Error:', err);
            message.error(t('pages.op.failed'));
          } finally {
            isEditModalSaving.value = false;
          }
        }
        // 如果 formData 为 null (校验失败)，不需要关闭模态框或停止loading
        else {
          // --- 表单验证失败 ---
          console.log('Form validation failed.');
          // 验证失败时，停止 loading，Modal 保持打开状态
          isEditModalSaving.value = false;
        }
      } catch (error) {
        // --- 捕获 getFormDataAndValidate 内部可能发生的意外错误 ---
        console.log('error when saving: ', error);
        message.error('Error');
        isEditModalSaving.value = false;
      }
    };

    const handleEditCancel = () => {
      isEditModalVisible.value = false;
      console.log('handle edit cancel');
    };

    // 视频 modal
    const videoUrl = ref<string>('');
    const currentVideoProduct = ref<ProductListModel | null>(null);
    const isVideoModalVisible = ref(false);
    const isVideoModalSaving = ref(false);

    const showVideoModal = record => {
      console.log('show video modal');
      currentVideoProduct.value = record;
      videoUrl.value = record.video_url || ''; // 加载已有的 video_url 或设为空
      isVideoModalVisible.value = true;
    };

    const handleVideoOk = async () => {
      if (!videoUrl.value) {
        message.error(t('Please input video URL'));
        return;
      }

      isVideoModalSaving.value = true;

      try {
        const payload = {
          bm_id: modelRef.data_source['fb_bm_id'],
          catalog_id: modelRef.data_source['id'],
          retailer_id: currentVideoProduct.value?.retailer_id,
          video_url: videoUrl.value,
        };

        // 调用 API 添加视频
        await updateProductVideoApi(payload);

        message.success(t('Video added successfully'));
        isVideoModalVisible.value = false;
      } catch (error) {
        console.error('Error adding video:', error);
        message.error(t('Failed to add video'));
      } finally {
        isVideoModalSaving.value = false;
      }
    };

    const handleVideoCancel = () => {
      isVideoModalVisible.value = false;
    };

    return {
      ...formLayout,
      initValues,
      modelRef,
      loading,
      handleSubmit,
      emit,
      t,
      columns,
      copyCell,

      tagModal,
      showTagModal,

      // edit modal
      isEditModalVisible,
      isEditModalSaving,
      handleEditOk,
      handleEditCancel,
      currentProduct,
      showEditModal,
      editModalRef,

      // 视频 modal
      videoUrl,
      currentVideoProduct,
      isVideoModalVisible,
      isVideoModalSaving,
      showVideoModal,
      handleVideoOk,
      handleVideoCancel,
    };
  },
});
</script>
