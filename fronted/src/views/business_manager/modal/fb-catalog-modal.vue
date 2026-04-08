<template>
  <a-modal
    :title="t('Catalogs')"
    :open="visible"
    width="1000px"
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
      :data-source="model.data_source['catalogs']"
      :row-key="record => record.id"
      bordered
      sticky
size="small"
    >
      <template #bodyCell="{ column, record }">
        <template v-if="column['dataIndex'] === 'products'">
          <a-button type="link" @click="showProductListModal(record)">
            {{ record.products?.length }}
          </a-button>

          <a-button type="link" @click="showCreateProductModal(record.id)">
            <plus-circle-outlined></plus-circle-outlined>
          </a-button>
        </template>
        <template v-if="column['dataIndex'] === 'product_sets'">
          <a-button type="link" @click="showProductSetListModal(record)">
            {{ record.product_sets?.length }}
          </a-button>
          <a-button type="link" @click="showCreateSetModal(record.id, record.products || [])">
            <plus-circle-outlined></plus-circle-outlined>
          </a-button>
        </template>
        <template v-if="column['dataIndex'] == 'pixels'">
          <a-button type="link" @click="showPixelListModal(record)">
            {{ record.pixels?.length }}
          </a-button>
        </template>
        <template v-if="column['dataIndex'] == 'owner_business'">
          <a-badge
            :text="
              record.owner_business?.id === model.data_source['source_id'] ? 'Owner' : 'Client'
            "
          />
        </template>
      </template>
    </a-table>

    <product-list-modal
      :model="productListModal.model"
      :visible="productListModal.visible"
      @cancel="
        () => {
          productListModal.visible = false;
        }
      "
      @ok="
        () => {
          productListModal.visible = false;
        }
      "
    />

    <product-set-list-modal
      :model="productSetListModal.model"
      :visible="productSetListModal.visible"
      @cancel="
        () => {
          productSetListModal.visible = false;
        }
      "
      @ok="
        () => {
          productSetListModal.visible = false;
        }
      "
    />

    <create-product-modal v-model:visible="isCreateModalVisible" @submit="handleProductSubmit" />

    <create-product-set-modal
      v-model:visible="isCreateSetModalVisible"
      @submit="handleProductSetSubmit"
      :products="availableProducts"
    />

    <!-- Pixel List Modal -->
    <fb-pixel-modal
      :model="pixelListModal.model"
      :visible="pixelListModal.visible"
      :canShare="false"
      @cancel="
        () => {
          pixelListModal.visible = false;
        }
      "
      @ok="
        () => {
          pixelListModal.visible = false;
        }
      "
    />
  </a-modal>
</template>

<script lang="ts">
import type { PropType } from 'vue';
import { defineComponent, ref, reactive, toRaw, watchEffect } from 'vue';
import { useI18n } from 'vue-i18n';
import ProductListModal from './product-list-modal.vue';
import ProductSetListModal from './product-set-list-modal.vue';
import FbPixelModal from './fb-pixel-modal.vue';
import { PlusCircleOutlined } from '@ant-design/icons-vue';
import CreateProductModal from './create-product-modal.vue';
import CreateProductSetModal from './create-product-set-modal.vue';
import type {
  ProductListCreateModdel,
  ProductListModel,
  ProcessedProductSet,
} from '@/utils/fb-interfaces';
import { bulkCreateProductsApi, bulkCreateProductSetApi } from '@/api/fb_bms';
import { message } from 'ant-design-vue';

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
  name: 'fbCatalogModel',
  components: {
    ProductListModal,
    ProductSetListModal,
    PlusCircleOutlined,
    CreateProductModal,
    CreateProductSetModal,
    FbPixelModal,
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
    const currentCatalogId = ref<string>('');

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
        minWidth: 100,
        align: 'center',
        resizable: true,
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
        title: t('Products'),
        dataIndex: 'products',
        minWidth: 80,
        align: 'center',
        resizable: true,
      },
      {
        title: t('Product sets'),
        dataIndex: 'product_sets',
        minWidth: 80,
        align: 'center',
        resizable: true,
      },
      {
        title: t('Pixels'),
        dataIndex: 'pixels',
        minWidth: 50,
        align: 'center',
        resizable: true,
      },
      {
        title: t('Relation'),
        dataIndex: 'relation',
        minWidth: 50,
        align: 'center',
        resizable: true,
      },
      {
        title: t('Role'),
        dataIndex: 'role',
        minWidth: 50,
        align: 'center',
        resizable: true,
      },
    ];

    // product list modal
    const productListModal = reactive({
      visible: false,
      model: null,
    });
    const showProductListModal = record => {
      productListModal.model = {
        data_source: record,
      };
      productListModal.visible = true;
    };

    // product set list modal
    const productSetListModal = reactive({
      visible: false,
      model: null,
    });
    const showProductSetListModal = record => {
      productSetListModal.model = {
        data_source: record,
      };
      productSetListModal.visible = true;
    };

    // pixel list modal
    const pixelListModal = reactive({
      visible: false,
      model: null,
    });
    const showPixelListModal = record => {
      pixelListModal.model = {
        data_source: record,
      };
      pixelListModal.visible = true;
    };

    const isCreateModalVisible = ref(false);

    const showCreateProductModal = id => {
      isCreateModalVisible.value = true;
      currentCatalogId.value = id;
    };

    const handleProductSubmit = async (products: ProductListCreateModdel[]) => {
      console.log('接收到来自模态框的数据:', products);

      try {
        const payload = {
          bm_id: modelRef.data_source['id'],
          catalog_id: currentCatalogId.value,
          products,
        };
        const response = await bulkCreateProductsApi(payload);
        console.log('API 响应:', response);

        message.success(t('pages.op.successfullyCreated', { count: products.length })); // 使用 i18n
        isCreateModalVisible.value = false; // **关键: API 成功后关闭模态框**

        // 4. (可选) 刷新页面数据或执行其他成功后的操作
        // e.g., refreshProductList();
      } catch (error: any) {
        // 5. 处理错误响应
        console.error('创建商品时出错:', error);
        message.error(t('pages.op.failed')); // 通用失败消息
        message.error(error.message || t('error.unknownApiError')); // 更具体的错误消息

        // **关键: API 失败时不关闭模态框**
        // 让用户可以检查输入或重试
      } finally {
        // 6. 清除加载状态
      }
    };

    // create product set
    const isCreateSetModalVisible = ref(false);
    const availableProducts = ref<ProductListModel[]>([]);
    const showCreateSetModal = (catalog_id, products) => {
      isCreateSetModalVisible.value = true;
      currentCatalogId.value = catalog_id;
      availableProducts.value = products;
    };
    const handleProductSetSubmit = async (productSets: ProcessedProductSet[]) => {
      console.log('submit create product sets');
      console.log(productSets);
      try {
        // 调用 API 保存数据
        const payload = {
          bm_id: modelRef.data_source['id'],
          catalog_id: currentCatalogId.value,
          product_sets: productSets,
        };
        await bulkCreateProductSetApi(payload);
        message.success(t('Submitted ', { count: productSets.length })); // 显示成功消息
        isCreateSetModalVisible.value = false; // API 调用成功后关闭模态框
        // 在此可以添加刷新列表等成功后的操作
      } catch (error: any) {
        // 处理 API 调用错误
        console.error('创建商品组失败:', error);
        message.error(t('pages.op.failed')); // 显示通用失败消息
        message.error(error.message || t('error.unknownApiError')); // 显示具体错误信息
        // API 调用失败时，保持模态框打开，让用户可以修改或重试
      }
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

      showProductListModal,
      productListModal,

      productSetListModal,
      showProductSetListModal,
      isCreateModalVisible,
      showCreateProductModal,
      handleProductSubmit,

      // pixel list modal
      pixelListModal,
      showPixelListModal,

      // create product set
      availableProducts,
      isCreateSetModalVisible,
      showCreateSetModal,
      handleProductSetSubmit,
    };
  },
});
</script>
