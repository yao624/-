<template>
  <div class="review-container">
    <a-alert
      type="info"
      show-icon
      :message="t('Review your ad configuration before launching')"
      class="review-alert"
    />

    <!-- Template Information -->
    <a-card :title="t('Template Information')" :bordered="false" class="review-card">
      <a-descriptions :column="3" bordered size="small">
        <a-descriptions-item :label="t('Template Name')">
          {{ template.name || '-' }}
        </a-descriptions-item>
        <a-descriptions-item :label="t('Campaign Name')">
          {{ template.campaign_name || '-' }}
        </a-descriptions-item>
        <a-descriptions-item :label="t('Ad Set Name')">
          {{ template.adset_name || '-' }}
        </a-descriptions-item>
        <a-descriptions-item :label="t('Ad Name')">
          {{ template.ad_name || '-' }}
        </a-descriptions-item>
        <a-descriptions-item :label="t('Objective')">
          {{ template.objective || '-' }}
        </a-descriptions-item>
        <a-descriptions-item :label="t('Billing Event')">
          {{ template.billing_event || '-' }}
        </a-descriptions-item>
      </a-descriptions>
    </a-card>

    <!-- Configuration Blocks -->
    <div
      v-for="(block, index) in configBlocks"
      :key="block.configBlock?.id || index"
      class="config-block-section"
    >
      <div class="config-block-header">
        <div class="account-highlight">
          {{ getAccountName(block.configBlock) }}
        </div>
        <h2 class="config-block-title">{{ getConfigBlockTitle(block.configBlock) }}</h2>
      </div>
      <a-card :bordered="false" class="review-card account-card">
        <a-descriptions :column="3" bordered size="small">
          <a-descriptions-item :label="t('Operator')">
            {{ getOperatorDisplay(block) }}
          </a-descriptions-item>
          <a-descriptions-item :label="t('Operator Type')">
            {{ getOperatorTypeDisplay(block?.operator_type) }}
          </a-descriptions-item>
          <a-descriptions-item :label="t('Page')">{{ getPageDisplay(block) }}</a-descriptions-item>
          <a-descriptions-item :label="t('Pixel')">
            {{ getPixelDisplay(block) }}
          </a-descriptions-item>
          <a-descriptions-item :label="t('Ad Setup')">
            {{ getAdSetupType(block?.ad_setup) }}
          </a-descriptions-item>
          <a-descriptions-item :label="t('Launch Mode')">
            {{ getLaunchModeDisplay(block?.launch_mode) }}
          </a-descriptions-item>
          <a-descriptions-item :label="t('Form')" v-if="block?.form">
            {{ getFormDisplay(block) }}
          </a-descriptions-item>

          <!-- Display Campaign and Adset when available -->
          <a-descriptions-item
            :label="t('Campaign')"
            v-if="block.campaignId || block.configBlock?.campaignId || block.configBlock?.campaign"
          >
            <template v-if="block.configBlock?.campaign">
              {{ block.configBlock.campaign.name }}
              <a-tag v-if="block.configBlock.campaign.source_id" color="blue">
                {{ block.configBlock.campaign.source_id }}
              </a-tag>
            </template>
            <template v-else-if="block.campaign">
              {{ block.campaign.name }}
              <a-tag v-if="block.campaign.source_id" color="blue">
                {{ block.campaign.source_id }}
              </a-tag>
            </template>
            <template v-else>
              {{ block.campaignId || block.configBlock?.campaignId || '-' }}
            </template>
          </a-descriptions-item>
          <a-descriptions-item
            :label="t('Ad Set')"
            v-if="block.adsetId || block.configBlock?.adsetId || block.configBlock?.adset"
          >
            <template v-if="block.configBlock?.adset">
              {{ block.configBlock.adset.name }}
              <a-tag v-if="block.configBlock.adset.source_id" color="green">
                {{ block.configBlock.adset.source_id }}
              </a-tag>
            </template>
            <template v-else-if="block.adset">
              {{ block.adset.name }}
              <a-tag v-if="block.adset.source_id" color="green">{{ block.adset.source_id }}</a-tag>
            </template>
            <template v-else>
              {{ block.adsetId || block.configBlock?.adsetId || '-' }}
            </template>
          </a-descriptions-item>
        </a-descriptions>

        <!-- Material Details -->
        <template v-if="block?.ad_setup === 'material'">
          <a-divider />
          <h3 class="section-title">{{ t('Creative Information') }}</h3>
          <a-row :gutter="[16, 16]">
            <a-col :span="8">
              <a-card :title="t('Materials')" size="small">
                <a-list
                  item-layout="horizontal"
                  :data-source="block.materials || []"
                  :locale="{ emptyText: t('No materials selected') }"
                >
                  <template #renderItem="{ item }">
                    <a-list-item>{{ item.name }}</a-list-item>
                  </template>
                </a-list>
              </a-card>
            </a-col>
            <a-col :span="8">
              <a-card :title="t('Links')" size="small">
                <a-list
                  item-layout="horizontal"
                  :data-source="block.links || []"
                  :locale="{ emptyText: t('No link selected') }"
                >
                  <template #renderItem="{ item }">
                    <a-list-item>{{ item.link }}</a-list-item>
                  </template>
                </a-list>
              </a-card>
            </a-col>
            <a-col :span="8">
              <a-card :title="t('Copywriting')" size="small">
                <a-list
                  item-layout="horizontal"
                  :data-source="block.copywriting || []"
                  :locale="{ emptyText: t('No copywriting selected') }"
                >
                  <template #renderItem="{ item }">
                    <a-list-item>{{ item.headline }}</a-list-item>
                  </template>
                </a-list>
              </a-card>
            </a-col>
          </a-row>
        </template>

        <!-- Post Details -->
        <template v-else-if="block?.ad_setup === 'post'">
          <a-divider />
          <h3 class="section-title">{{ t('Post Information') }}</h3>
          <a-descriptions :column="1" bordered size="small">
            <a-descriptions-item :label="t('Post IDs')">
              <div v-if="block.post && block.post.length">
                <a-tag v-for="postId in block.post" :key="postId">{{ postId }}</a-tag>
              </div>
              <span v-else>-</span>
            </a-descriptions-item>
          </a-descriptions>
        </template>

        <!-- Catalog Details -->
        <template v-else-if="block?.ad_setup === 'catalog'">
          <a-divider />
          <h3 class="section-title">{{ t('Catalog Information') }}</h3>
          <a-descriptions :column="1" bordered size="small">
            <a-descriptions-item :label="t('Product Sets')">
              <div v-if="block.productSetDetails && block.productSetDetails.length">
                <a-tag
                  v-for="item in block.productSetDetails"
                  :key="item.id"
                  class="product-set-tag"
                >
                  {{ item.name }}
                </a-tag>
              </div>
              <div v-else-if="block.productSets && block.productSets.length">
                <a-tag
                  v-for="productSetId in block.productSets"
                  :key="productSetId"
                  class="product-set-tag"
                >
                  {{ productSetId }}
                </a-tag>
              </div>
              <span v-else>-</span>
            </a-descriptions-item>
          </a-descriptions>

          <!-- 添加Links显示 -->
          <a-row :gutter="[16, 16]" style="margin-top: 16px">
            <a-col :span="8">
              <a-card :title="t('Links')" size="small">
                <a-list
                  item-layout="horizontal"
                  :data-source="block.links || []"
                  :locale="{ emptyText: t('No link selected') }"
                >
                  <template #renderItem="{ item }">
                    <a-list-item>{{ item.link }}</a-list-item>
                  </template>
                </a-list>
              </a-card>
            </a-col>
          </a-row>
        </template>
      </a-card>
    </div>
  </div>
</template>

<script lang="ts">
import type { PropType } from 'vue';
import { defineComponent } from 'vue';
import { useI18n } from 'vue-i18n';

export default defineComponent({
  name: 'ReviewLaunchV2',
  props: {
    configBlocks: {
      type: Array as PropType<any[]>, // Use a more specific type if available
      required: true,
    },
    template: {
      type: Object,
      required: true,
    },
    // 已在create-ads.vue中传入了空数组，需要保留以兼容
    accounts: {
      type: Array as PropType<any[]>,
      default: () => [],
    },
    // Pass necessary lookups (e.g., catalogs, product sets) if names aren't in account data
    catalogs: { type: Array as PropType<any[]>, default: () => [] },
    productSets: { type: Array as PropType<any[]>, default: () => [] },
  },
  setup(props) {
    const { t } = useI18n();

    // 直接使用props，不创建未使用的计算属性
    // 检查props是否有效，以消除未使用警告
    if (!props.configBlocks || !props.template) {
      console.warn('配置块或模板缺失');
    }

    // Helper function to get a block title based on configuration
    const getConfigBlockTitle = (configBlock: any) => {
      if (!configBlock) return '';

      if (configBlock.type === 'adset' && configBlock.adset && configBlock.campaign) {
        // 只显示campaign > adset，账户名已经单独显示
        return `${configBlock.campaign.name} > ${configBlock.adset.name}`;
      } else if (configBlock.type === 'campaign' && configBlock.campaign) {
        // 只显示campaign，账户名已经单独显示
        return configBlock.campaign.name;
      } else {
        // 如果没有campaign和adset，则返回空字符串
        return '';
      }
    };

    // Helper function to safely get display label from label-in-value object or string
    const getDisplayValue = (value: any) => {
      if (typeof value === 'object' && value !== null && value.label) {
        return value.label;
      }
      return value || '-';
    };

    const getOperatorDisplay = (account: any) => getDisplayValue(account?.operator);
    const getPageDisplay = (account: any) => getDisplayValue(account?.page);
    const getPixelDisplay = (account: any) => getDisplayValue(account?.pixel);
    const getFormDisplay = (account: any) => getDisplayValue(account?.form);

    const getOperatorTypeDisplay = (type: 'fb' | 'bm' | null) => {
      if (type === 'fb') return 'Facebook User';
      if (type === 'bm') return 'BM User';
      return '-';
    };

    const getAdSetupType = (type: string | undefined) => {
      const types: Record<string, string> = {
        material: t('Material'),
        post: t('Post'),
        catalog: t('Catalog'),
      };
      return type ? types[type] || type : '-';
    };

    const getLaunchModeDisplay = (mode: number | undefined) => {
      const modes: Record<number, string> = {
        1: 'N-1-1',
        2: '1-N-1',
        3: '1-1-N',
      };
      return mode ? modes[mode] || '-' : '-';
    };

    // Helper function to get just the account name
    const getAccountName = (configBlock: any) => {
      if (!configBlock) return t('Account');
      return configBlock.adAccount.name;
    };

    return {
      t,
      getConfigBlockTitle,
      getAccountName,
      getOperatorDisplay,
      getPageDisplay,
      getPixelDisplay,
      getFormDisplay,
      getOperatorTypeDisplay,
      getAdSetupType,
      getLaunchModeDisplay,
    };
  },
});
</script>

<style lang="less" scoped>
.review-container {
  padding-top: 16px;
}
.review-alert {
  margin-bottom: 16px;
}
.review-card {
  margin-bottom: 16px;
}

.config-block-section {
  margin-bottom: 30px;

  .config-block-header {
    margin-bottom: 16px;
    display: flex;
    flex-direction: column;

    .account-highlight {
      display: inline-block;
      background-color: #f0f7ff;
      border-left: 4px solid #1890ff;
      padding: 8px 16px;
      font-weight: 500;
      font-size: 14px;
      color: #1890ff;
      border-radius: 2px;
      margin-bottom: 8px;
      box-shadow: 0 2px 6px rgba(24, 144, 255, 0.1);
      width: fit-content;
    }

    .config-block-title {
      font-size: 15px;
      font-weight: 500;
      margin: 0;
      color: #555;
      padding-left: 16px;
      opacity: 0.9;
    }
  }
}

.account-card {
  background-color: #fafafa;
}

.section-title {
  font-size: 14px;
  margin-bottom: 16px;
}

.product-set-tag {
  margin-bottom: 8px;
}
</style>
