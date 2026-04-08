<template>
  <a-modal
    v-model:open="visible"
    :title="t('Edit Product Set')"
    width="600px"
    :z-index="1020"
    @cancel="handleCancel"
    @ok="handleOk"
    :confirm-loading="loading"
  >
    <div class="product-set-edit-content">
      <!-- 当前Product Set信息 -->
      <div class="current-info">
        <h4>{{ t('Current Product Set') }}</h4>
        <div class="info-item">
          <span class="label">{{ t('Name') }}:</span>
          <span class="value">{{ currentProductSetInfo.name }}</span>
        </div>
        <div class="info-item">
          <span class="label">{{ t('ID') }}:</span>
          <span class="value">{{ currentProductSetInfo.id }}</span>
        </div>
      </div>

      <a-divider />

      <!-- 选择新的Product Set -->
      <div class="selection-area">
        <h4>{{ t('Select New Product Set') }}</h4>

        <a-form layout="vertical">
          <a-form-item :label="t('Operator')">
            <a-select
              v-model:value="selectedOperator"
              :placeholder="t('Select operator')"
              @change="handleOperatorChange"
              :loading="loadingOperators"
            >
              <a-select-option
                v-for="operator in availableOperators"
                :key="operator.value"
                :value="operator.value"
              >
                {{ operator.label }}
              </a-select-option>
            </a-select>
          </a-form-item>

          <a-form-item :label="t('Product Set')">
            <a-select
              v-model:value="selectedProductSet"
              :placeholder="t('Select product set')"
              :disabled="!selectedOperator"
              :loading="loadingProductSets"
            >
              <a-select-opt-group
                v-for="catalog in catalogGroups"
                :key="catalog.source_id"
                :label="catalog.name"
              >
                <a-select-option
                  v-for="productSet in catalog.product_sets"
                  :key="productSet.source_id"
                  :value="productSet.source_id"
                >
                  {{ productSet.name }} ({{ productSet.source_id }})
                </a-select-option>
              </a-select-opt-group>
            </a-select>
          </a-form-item>
        </a-form>
      </div>
    </div>
  </a-modal>
</template>

<script lang="ts">
import type { PropType } from 'vue';
import { defineComponent, ref, computed, watch } from 'vue';
import { useI18n } from 'vue-i18n';
import { message } from 'ant-design-vue';
import { getAdAccountData } from '@/api/adaccount/table-list';

interface ProductSetInfo {
  id: string;
  name: string;
}

interface OperatorOption {
  value: string;
  label: string;
  userId: string;
  data: any; // 使用any类型，直接使用businessUser对象
}

interface ProductSetOption {
  source_id: string;
  name: string;
  id?: string;
  products?: any[];
  tags?: any[];
  filter?: any;
}

export default defineComponent({
  name: 'ProductSetEditModal',
  props: {
    open: {
      type: Boolean,
      default: false,
    },
    adAccountId: {
      type: String,
      required: true,
    },
    currentProductSetInfo: {
      type: Object as PropType<ProductSetInfo>,
      required: true,
    },
  },
  emits: ['cancel', 'confirm'],
  setup(props, { emit }) {
    const { t } = useI18n();

    const visible = computed({
      get: () => props.open,
      set: (value) => {
        if (!value) {
          emit('cancel');
        }
      },
    });

    const loading = ref(false);
    const loadingOperators = ref(false);
    const loadingProductSets = ref(false);

        const selectedOperator = ref<string>('');
    const selectedProductSet = ref<string>('');

    const availableOperators = ref<OperatorOption[]>([]);
    const availableProductSets = ref<ProductSetOption[]>([]);
    const catalogGroups = ref<any[]>([]); // 按catalog分组的product sets

    // 监听modal打开，加载数据
    watch(() => props.open, (newOpen) => {
      if (newOpen && props.adAccountId) {
        loadOperators();
        selectedOperator.value = '';
        selectedProductSet.value = '';
      }
    });

        // 加载操作员数据
    const loadOperators = async () => {
      try {
        loadingOperators.value = true;

        // 使用与Page编辑Modal相同的API
        const response = await getAdAccountData({
          ad_account_ids: [props.adAccountId],
        });
        console.log('🔍 获取广告账户信息:', response);

        if (response.data && response.data.length > 0) {
          const adAccountData = response.data[0];
          console.log('🔍 Product Set Modal - 广告账户数据:', adAccountData);

          const fbApiTokens = adAccountData.fb_api_token || [];
          const fbBusinessUsers = adAccountData.fb_business_users || [];

          console.log('🔍 fb_api_token数量:', fbApiTokens.length);
          console.log('🔍 fb_business_users数量:', fbBusinessUsers.length);

          // 只处理token_type为1的
          const validTokens = fbApiTokens.filter((token: any) => token.token_type === 1);

          const operators: OperatorOption[] = [];

          validTokens.forEach((token: any) => {
            if (token.bm && token.bm.users) {
              token.bm.users.forEach((user: any) => {
                // 在fb_business_users中找到匹配的用户
                const businessUser = fbBusinessUsers.find((bu: any) =>
                  bu.source_id === user.source_id && bu.is_operator === true,
                );

                if (businessUser) {
                  operators.push({
                    value: businessUser.source_id,
                    label: `${token.name} - ${businessUser.name}`,
                    userId: businessUser.source_id,
                    data: businessUser,
                  });
                }
              });
            }
          });

          availableOperators.value = operators;

          console.log('🔍 处理后的操作员列表:', operators);

          // 默认选择第一个操作员
          if (operators.length > 0) {
            selectedOperator.value = operators[0].value;
            handleOperatorChange(operators[0].value);
          } else {
            console.log('⚠️ 没有找到可用的操作员');
          }
        }
      } catch (error) {
        console.error('Failed to load operators:', error);
        message.error(t('Failed to load operators'));
      } finally {
        loadingOperators.value = false;
      }
    };

            // 处理操作员选择变化
    const handleOperatorChange = (operatorId: string) => {
      selectedProductSet.value = '';
      availableProductSets.value = [];
      catalogGroups.value = [];

      if (!operatorId) return;

      // 从已加载的操作员数据中查找
      console.log('🔍 处理操作员变化:', operatorId);
      console.log('🔍 可用操作员:', availableOperators.value);

      const operator = availableOperators.value.find(op => op.value === operatorId);
      console.log('🔍 找到的操作员:', operator);

      if (operator && operator.data.assigned_catalogs) {
        console.log('🔍 操作员的assigned_catalogs:', operator.data.assigned_catalogs);

        const productSets: ProductSetOption[] = [];
        const catalogs: any[] = [];

                // 按catalog分组处理，保留完整的Product Set数据
        operator.data.assigned_catalogs.forEach((catalog: any) => {
          if (catalog.product_sets && Array.isArray(catalog.product_sets)) {
            const catalogGroup = {
              source_id: catalog.source_id,
              name: catalog.name,
              product_sets: catalog.product_sets.map((ps: any) => ({
                source_id: ps.source_id,
                name: ps.name,
                // 保留完整的Product Set数据
                products: ps.products || [],
                tags: ps.tags || [],
                filter: ps.filter || null,
                id: ps.id || ps.source_id,
              })),
            };

            catalogs.push(catalogGroup);

            // 同时添加到平面列表中（用于默认选择）
            catalog.product_sets.forEach((ps: any) => {
              productSets.push({
                source_id: ps.source_id,
                name: ps.name,
                products: ps.products || [],
                tags: ps.tags || [],
                filter: ps.filter || null,
                id: ps.id || ps.source_id,
              });
            });
          }
        });

        catalogGroups.value = catalogs;
        availableProductSets.value = productSets;

        console.log('🔍 按catalog分组的数据:', catalogs);
        console.log('🔍 处理后的Product Sets:', productSets);

        // 设置默认选择的Product Set
        if (productSets.length > 0) {
          // 优先选择当前Product Set（如果在列表中）
          const currentProductSetOption = productSets.find(ps => ps.source_id === props.currentProductSetInfo.id);
          if (currentProductSetOption) {
            selectedProductSet.value = currentProductSetOption.source_id;
          } else {
            // 如果当前Product Set不在列表中，选择第一个选项
            selectedProductSet.value = productSets[0].source_id;
          }
        }
      }
    };

    const handleCancel = () => {
      emit('cancel');
    };

    const handleOk = () => {
      if (!selectedProductSet.value) {
        message.warning(t('Please select a product set'));
        return;
      }

      // 从分组数据中查找完整的Product Set信息（包括products数据）
      let selectedProductSetData = null;
      for (const catalog of catalogGroups.value) {
        const foundProductSet = catalog.product_sets.find(
          (ps: any) => ps.source_id === selectedProductSet.value,
        );
        if (foundProductSet) {
          selectedProductSetData = {
            id: foundProductSet.source_id,
            source_id: foundProductSet.source_id,
            name: foundProductSet.name,
            catalog: {
              id: catalog.source_id,
              name: catalog.name,
            },
            // 返回完整的Product Set数据（包括products）
            products: foundProductSet.products || [],
            tags: foundProductSet.tags || [],
            filter: foundProductSet.filter || null,
          };
          break;
        }
      }

      if (selectedProductSetData) {
        console.log('📦 返回完整的Product Set数据:', selectedProductSetData);
        emit('confirm', selectedProductSetData);
      }
    };

    return {
      t,
      visible,
      loading,
      loadingOperators,
      loadingProductSets,
      selectedOperator,
      selectedProductSet,
      availableOperators,
      availableProductSets,
      catalogGroups,
      handleOperatorChange,
      handleCancel,
      handleOk,
    };
  },
});
</script>

<style scoped>
.product-set-edit-content {
  padding: 16px 0;
}

.current-info {
  background: #f5f5f5;
  padding: 16px;
  border-radius: 6px;
  margin-bottom: 16px;
}

.current-info h4 {
  margin: 0 0 12px 0;
  color: #262626;
}

.info-item {
  display: flex;
  margin-bottom: 8px;
}

.info-item:last-child {
  margin-bottom: 0;
}

.label {
  font-weight: 500;
  width: 60px;
  color: #595959;
}

.value {
  color: #262626;
  word-break: break-all;
}

.selection-area h4 {
  margin: 0 0 16px 0;
  color: #262626;
}

.product-set-tag {
  cursor: pointer;
  transition: all 0.2s;
}

.product-set-tag:hover {
  transform: scale(1.05);
}
</style>
