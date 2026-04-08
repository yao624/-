<template>
  <a-modal
    :visible="visible"
    title="添加商品 (Add items)"
    @ok="handleOk"
    @update:visible="handleUpdateVisible"
    @cancel="handleCancel"
    :confirm-loading="isSubmitting"
    :destroyOnClose="true"
    width="90%"
    style="top: 20px"
    :ok-text="t('Upload items')"
    :cancel-text="t('common.cancel')"
    :ok-button-props="{ disabled: formState.productList.length === 0 }"
  >
    <!-- 主表单 -->
    <a-form ref="formRef" :model="formState" layout="vertical" autocomplete="off">
      <!-- 表头 -->
      <a-row :gutter="16" class="form-header">
        <a-col :span="1" class="action-col-header"></a-col>
        <a-col :span="3">
          <a-typography-text strong>{{ t('Image URL') }}</a-typography-text>
          <a-tooltip :title="t('Enter the direct URL of the image')">
            <info-circle-outlined class="info-icon" />
          </a-tooltip>
        </a-col>
        <a-col :span="5">
          <a-typography-text strong>{{ t('Title') }}</a-typography-text>
          <a-tooltip :title="t('Enter a short, clear title')">
            <info-circle-outlined class="info-icon" />
          </a-tooltip>
        </a-col>
        <a-col :span="7">
          <a-typography-text strong>{{ t('Description') }}</a-typography-text>
          <a-tooltip :title="t('Describe the features and benefits')">
            <info-circle-outlined class="info-icon" />
          </a-tooltip>
        </a-col>
        <a-col :span="4">
          <a-typography-text strong>{{ t('Website link') }}</a-typography-text>
          <a-tooltip :title="t('Link to the product page')">
            <info-circle-outlined class="info-icon" />
          </a-tooltip>
        </a-col>
        <a-col :span="4">
          <a-typography-text strong>{{ t('Price') }}</a-typography-text>
          <!-- 移除 (Optional) 文本 -->
          <a-tooltip :title="t('Enter the product price')">
            <info-circle-outlined class="info-icon" />
          </a-tooltip>
        </a-col>
      </a-row>
      <a-divider class="header-divider" />

      <!-- 动态商品行 -->
      <div v-if="formState.productList.length === 0" class="empty-state">
        <a-empty :description="t('Click Add New Item to start')" />
      </div>
      <div v-else>
        <transition-group name="list" tag="div">
          <div
            v-for="(product, index) in formState.productList"
            :key="product.id"
            class="product-row"
          >
            <a-row :gutter="16" align="top">
              <!-- 列内容保持不变 -->
              <!-- 1. 操作列 -->
              <a-col :span="1" class="action-col">
                <a-space direction="vertical">
                  <a-tooltip :title="t('Delete item')">
                    <a-button
                      type="text"
                      danger
                      size="small"
                      @click="removeProduct(product.id)"
                      :disabled="formState.productList.length <= 1"
                    >
                      <template #icon><delete-outlined /></template>
                    </a-button>
                  </a-tooltip>
                  <a-tooltip :title="t('Duplicate item')">
                    <a-button type="text" size="small" @click="duplicateProduct(product.id)">
                      <template #icon><copy-outlined /></template>
                    </a-button>
                  </a-tooltip>
                </a-space>
              </a-col>
              <!-- 2. 图片 URL 输入框 -->
              <a-col :span="3">
                <a-form-item :name="['productList', index, 'image_url']" :rules="rules.image_url">
                  <a-input
                    v-model:value="product.image_url"
                    :placeholder="t('https://example.com/image.jpg')"
                  />
                </a-form-item>
              </a-col>
              <!-- 3. 标题 -->
              <a-col :span="5">
                <a-form-item :name="['productList', index, 'name']" :rules="rules.name">
                  <a-input
                    v-model:value="product.name"
                    :placeholder="t('Enter a short, clear title')"
                    show-count
                    :maxlength="200"
                  />
                </a-form-item>
              </a-col>
              <!-- 4. 描述 -->
              <a-col :span="7">
                <a-form-item
                  :name="['productList', index, 'description']"
                  :rules="rules.description"
                >
                  <a-textarea
                    v-model:value="product.description"
                    :placeholder="t('Describe the features and benefits')"
                    :rows="3"
                    show-count
                    :maxlength="5000"
                  />
                </a-form-item>
              </a-col>
              <!-- 5. 网站链接 -->
              <a-col :span="4">
                <a-form-item :name="['productList', index, 'url']" :rules="rules.url">
                  <a-input
                    v-model:value="product.url"
                    :placeholder="t('https://example.com/item')"
                  />
                </a-form-item>
              </a-col>
              <!-- 6. 价格 (必填) -->
              <a-col :span="4">
                <!-- 注意 :rules="rules.price" 应用在 a-form-item 上 -->
                <a-form-item :name="['productList', index, 'price']" :rules="rules.price">
                  <a-input-group compact>
                    <a-select v-model:value="product.currency" style="width: 35%">
                      <a-select-option v-for="c in currencies" :key="c" :value="c">
                        {{ c }}
                      </a-select-option>
                    </a-select>
                    <a-input
                      v-model:value="product.price"
                      style="width: 65%"
                      :placeholder="t('Amount')"
                    />
                  </a-input-group>
                </a-form-item>
              </a-col>
            </a-row>
            <a-divider v-if="index < formState.productList.length - 1" class="row-divider" />
          </div>
        </transition-group>
      </div>

      <!-- 添加按钮 -->
      <a-dropdown-button @click="addProduct(1)" type="primary" style="margin-top: 16px">
        <!-- Dropdown content remains the same -->
        <template #icon><plus-outlined /></template>
        {{ t('New item') }}
        <template #overlay>
          <a-menu @click="handleMenuClick">
            <a-menu-item key="1">{{ t('Add 1 item') }}</a-menu-item>
            <a-menu-item key="5">{{ t('Add 5 items') }}</a-menu-item>
            <a-menu-item key="10">{{ t('Add 10 items') }}</a-menu-item>
          </a-menu>
        </template>
      </a-dropdown-button>
    </a-form>

    <!-- 底部信息提示 -->
    <template #footer>
      <!-- Footer content remains the same -->
      <div style="display: flex; justify-content: space-between; align-items: center">
        <a-tooltip :title="t('Additional information or help text')">
          <info-circle-outlined
            style="font-size: 16px; color: rgba(0, 0, 0, 0.45); cursor: pointer"
          />
        </a-tooltip>
        <div>
          <a-button @click="handleCancel">{{ t('common.cancel') }}</a-button>
          <a-button
            type="primary"
            @click="handleOk"
            :loading="isSubmitting"
            :disabled="formState.productList.length === 0"
          >
            {{ t('Upload items') }}
          </a-button>
        </div>
      </div>
    </template>
  </a-modal>
</template>

<script lang="ts" setup>
import { ref, reactive, computed } from 'vue';
// Imports remain the same
import {
  Modal as AModal,
  Form as AForm,
  FormItem as AFormItem,
  Input as AInput,
  Textarea as ATextarea,
  Button as AButton,
  Row as ARow,
  Col as ACol,
  Divider as ADivider,
  Select as ASelect,
  SelectOption as ASelectOption,
  InputGroup as AInputGroup,
  DropdownButton as ADropdownButton,
  Menu as AMenu,
  MenuItem as AMenuItem,
  Tooltip as ATooltip,
  TypographyText as ATypographyText,
  Space as ASpace,
  Empty as AEmpty,
  message,
  type FormInstance,
} from 'ant-design-vue';
import {
  PlusOutlined,
  DeleteOutlined,
  CopyOutlined,
  InfoCircleOutlined,
} from '@ant-design/icons-vue';
import { useI18n } from 'vue-i18n';
import type { MenuInfo } from 'ant-design-vue/lib/menu/src/interface';

// --- Types ---
interface ProductFormState {
  id: number;
  name: string;
  description: string;
  url: string;
  image_url: string;
  price: string; // 默认值 '29.99'
  currency: string; // 默认值 'USD'
}

// 修改: ProcessedProductData 中的 price 现在是 number (不再是 number | null)
interface ProcessedProductData {
  name: string;
  description: string;
  url: string;
  image_url: string;
  currency: string;
  price: number; // 价格现在总是数字 (以分为单位)
}

// Props and Emits remain the same
const props = defineProps({
  visible: { type: Boolean, required: true },
});
const emit = defineEmits<{
  (e: 'update:visible', value: boolean): void;
  (e: 'submit', products: ProcessedProductData[]): void;
}>();

const { t } = useI18n();
const formRef = ref<FormInstance>();
const isSubmitting = ref(false);
// 确保 'USD' 在列表中
const currencies = ref(['USD', 'VND', 'EUR', 'JPY']); // 将 USD 放在前面或确保它存在
const formState = reactive<{ productList: ProductFormState[] }>({
  productList: [],
});

// --- 修改: Validation Rules ---
const rules = computed(() => ({
  name: [
    {
      required: true,
      message: t('validation.required', { field: t('Title') }),
      trigger: 'blur',
      whitespace: true,
    },
  ],
  description: [
    {
      required: true,
      message: t('validation.required', { field: t('Description') }),
      trigger: 'blur',
      whitespace: true,
    },
  ],
  url: [
    {
      required: true,
      message: t('validation.required', { field: t('Website link') }),
      trigger: 'blur',
    },
    { type: 'url', message: t('validation.invalidUrl'), trigger: ['change', 'blur'] },
  ],
  image_url: [
    {
      required: true,
      message: t('validation.required', { field: t('Image URL') }),
      trigger: 'blur',
    },
    { type: 'url', message: t('validation.invalidUrl'), trigger: ['change', 'blur'] },
  ],
  // 修改: Price 规则 - 添加 required，并保留格式校验
  price: [
    // 1. 必填规则
    {
      required: true,
      message: t('validation.required', { field: t('Price') }),
      whitespace: true, // 不允许仅输入空格
      trigger: 'blur', // 或 ['change', 'blur']
    },
    // 2. 格式验证规则 (如果值存在)
    {
      validator: async (_rule: any, value: string) => {
        // 必填规则已经处理了空值情况，这里只需验证非空值的格式
        if (value && value.trim() !== '') {
          const cleanedValue = value.replace(/[^0-9.,]/g, '').replace(',', '.');
          if (isNaN(parseFloat(cleanedValue))) {
            return Promise.reject(t('validation.invalidNumberFormat'));
          }
          // 允许整数或最多两位小数
          if (!/^\d+(\.\d{1,2})?$/.test(cleanedValue)) {
            return Promise.reject(t('validation.invalidPriceDecimal'));
          }
        }
        // 如果为空（理论上不应该到这里，因为有 required 规则）或格式正确，则通过
        return Promise.resolve();
      },
      trigger: ['change', 'blur'],
    },
  ],
}));

// --- 修改: Functions ---

// 修改: 创建一个带默认值的产品对象
const createEmptyProduct = (): ProductFormState => ({
  id: Date.now() + Math.random(),
  name: '',
  description: '',
  url: '',
  image_url: '',
  price: '29.99', // 设置默认价格
  currency: 'USD', // 设置默认货币
});

// addProduct, handleMenuClick, removeProduct, duplicateProduct 保持不变
const addProduct = (count: number = 1) => {
  for (let i = 0; i < count; i++) {
    formState.productList.push(createEmptyProduct());
  }
};
const handleMenuClick = (e: MenuInfo) => {
  const count = parseInt(e.key as string, 10);
  addProduct(count);
};
const removeProduct = (idToRemove: number) => {
  formState.productList = formState.productList.filter(product => product.id !== idToRemove);
};
const duplicateProduct = (idToDuplicate: number) => {
  const productToCopy = formState.productList.find(product => product.id === idToDuplicate);
  if (productToCopy) {
    const index = formState.productList.indexOf(productToCopy);
    const newProduct = {
      ...productToCopy,
      id: Date.now() + Math.random(),
    };
    formState.productList.splice(index + 1, 0, newProduct);
  }
};

// 修改: 处理 Modal OK 点击 - 价格处理逻辑简化
const handleOk = async () => {
  if (!formRef.value) return;
  if (formState.productList.length === 0) {
    message.warn(t('prompt.addAtLeastOneProduct'));
    return;
  }

  isSubmitting.value = true;
  try {
    // 1. 表单验证 (现在会检查价格是否填写且格式正确)
    await formRef.value.validate();
    console.log('表单验证通过');

    // 2. 处理数据
    const processedProducts: ProcessedProductData[] = [];
    let processingErrorOccurred = false; // 用于标记内部处理错误

    for (const product of formState.productList) {
      let priceInCents: number;
      try {
        // 因为验证已通过，可以假设 product.price 是有效格式的字符串
        const cleanedPriceString = product.price.replace(/[^0-9.,]/g, '').replace(',', '.');
        const numericPrice = parseFloat(cleanedPriceString);

        // 再次检查 (防御性编程，理论上不应触发)
        if (isNaN(numericPrice) || !/^\d+(\.\d{1,2})?$/.test(cleanedPriceString)) {
          console.error(`内部错误：验证通过但价格 '${product.price}' 解析失败`);
          throw new Error(t('validation.internalPriceError')); // 内部错误
        }
        priceInCents = Math.round(numericPrice * 100);
      } catch (parseError: any) {
        // 这个 catch 主要捕获上面防御性检查抛出的内部错误
        console.error('内部价格处理错误:', parseError);
        const errorIndex = formState.productList.findIndex(p => p.id === product.id);
        message.error(t('validation.internalProcessingError', { index: errorIndex + 1 }));
        processingErrorOccurred = true;
        break; // 停止处理
      }

      processedProducts.push({
        name: product.name,
        description: product.description,
        url: product.url,
        image_url: product.image_url,
        currency: product.currency,
        price: priceInCents, // 现在总是 number
      });
    } // end for loop

    // 3. 提交数据 (仅当没有内部处理错误时)
    if (!processingErrorOccurred) {
      console.log('处理后的商品数据:', processedProducts);
      emit('submit', processedProducts);
      // 成功提交后，父组件通常会关闭 Modal
    }
  } catch (errorInfo) {
    // 捕获 formRef.value.validate() 抛出的验证失败错误
    console.log('表单验证失败:', errorInfo);
    message.error(t('validation.checkForm')); // 提示用户检查表单
  } finally {
    isSubmitting.value = false;
  }
};

// handleCancel, handleUpdateVisible 保持不变
const handleCancel = () => {
  emit('update:visible', false);
};
const handleUpdateVisible = (value: boolean) => {
  emit('update:visible', value);
  if (value && formState.productList.length === 0) {
    addProduct(1); // 打开时添加带默认值的项
  }
};
</script>

<style scoped>
/* Styles remain the same */
.form-header {
  margin-bottom: 8px;
  padding: 0 8px;
  color: rgba(0, 0, 0, 0.65);
}
.header-divider {
  margin-top: 0;
  margin-bottom: 16px;
}
.info-icon {
  margin-left: 4px;
  color: rgba(0, 0, 0, 0.45);
  cursor: help;
}
.product-row {
  margin-bottom: 16px;
}
.row-divider {
  margin-top: 0;
  margin-bottom: 16px;
}
.action-col,
.action-col-header {
  text-align: center;
  padding-top: 8px;
}
.action-col .ant-space-item {
  margin-bottom: 4px !important;
}
.ant-form-item {
  margin-bottom: 0;
}
.ant-form-item-has-error .ant-input,
.ant-form-item-has-error .ant-select-selector,
.ant-form-item-has-error .ant-input-number,
.ant-form-item-has-error .ant-input-group .ant-input {
  border-color: #ff4d4f !important;
}
.ant-form-item-has-error .ant-input-group-compact > .ant-select > .ant-select-selector,
.ant-form-item-has-error .ant-input-group-compact > .ant-input {
  border-color: #ff4d4f !important;
  z-index: 1;
}
.empty-state {
  padding: 40px 0;
  text-align: center;
}
.list-enter-active,
.list-leave-active {
  transition: all 0.3s ease;
}
.list-enter-from,
.list-leave-to {
  opacity: 0;
  transform: translateY(-10px);
}
</style>
