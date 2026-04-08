<template>
  <div class="ad-account-section">
    <a-form layout="vertical">
      <a-row :gutter="[12, 0]" :wrap="false" :style="{ width: '100%' }">
        <a-col :span="6">
          <a-form-item :label="t('Ad account')">
            <a-row :gutter="[12, 0]" :wrap="false">
              <a-col :flex="1">
                <a-input :value="adAccount.name" disabled class="custom-input" />
              </a-col>
              <a-col>
                <a-tooltip :title="t('Refresh ad account')" placement="top">
                  <a-button
                    type="primary"
                    :loading="loadings.account"
                    :icon="h(ReloadOutlined)"
                    @click="onReload"
                    class="refresh-btn"
                  ></a-button>
                </a-tooltip>
              </a-col>
            </a-row>
            <div class="account-status">
              ID: {{ adAccount.source_id }}
              <a-tag :color="getStatusColor(adAccount.account_status)" style="margin-left: 8px">
                {{ adAccount.account_status }}
              </a-tag>
            </div>
          </a-form-item>
        </a-col>
        <a-col :span="18">
          <a-row :gutter="[12, 0]" :wrap="true">
            <a-col :span="6" v-if="adAccount.campaigns && adAccount.campaigns.length > 0">
              <a-form-item :label="t('Campaign')">
                <a-select
                  v-model:value="selectedCampaignId"
                  :loading="loadings.account"
                  style="width: 100%"
                  allow-clear
                  :placeholder="t('Select campaign')"
                  @change="onCampaignChange"
                  class="custom-select"
                >
                  <a-select-option
                    v-for="campaign in adAccount.campaigns"
                    :key="campaign.id"
                    :value="campaign.id"
                  >
                    {{ campaign.name }}
                  </a-select-option>
                </a-select>
              </a-form-item>
            </a-col>
            <a-col
              :span="6"
              v-if="
                selectedCampaignId && selectedCampaign?.adsets && selectedCampaign.adsets.length > 0
              "
            >
              <a-form-item :label="t('Ad Set')">
                <a-select
                  v-model:value="selectedAdsetId"
                  :loading="loadings.account"
                  style="width: 100%"
                  allow-clear
                  :placeholder="t('Select ad set')"
                  @change="onAdsetChange"
                  class="custom-select"
                >
                  <a-select-option
                    v-for="adset in selectedCampaign.adsets"
                    :key="adset.id"
                    :value="adset.id"
                  >
                    {{ adset.name }}
                  </a-select-option>
                </a-select>
              </a-form-item>
            </a-col>
            <a-col :span="6">
              <a-form-item :label="t('Operator')">
                <a-select
                  v-model:value="form.operator"
                  :loading="loadings.account || loadings.operator"
                  :label-in-value="true"
                  @change="handleOperatorChange"
                  :options="operatorOptions"
                  class="custom-select"
                ></a-select>
              </a-form-item>
            </a-col>
            <a-col :span="6">
              <a-form-item :label="t('Page')">
                <a-select
                  v-model:value="form.page"
                  :loading="loadings.page"
                  :label-in-value="true"
                  @change="handlePageChange"
                  :options="pageOptions"
                  :disabled="!form.operator"
                  class="custom-select"
                ></a-select>
              </a-form-item>
            </a-col>
            <a-col
              :span="6"
              v-if="
                template.objective === 'OUTCOME_LEADS' &&
                template.conversion_location === 'INSTANT_FORMS'
              "
            >
              <a-form-item :label="t('Form')">
                <a-select
                  v-model:value="form.form"
                  :loading="loadings.form"
                  :label-in-value="true"
                  :options="formOptions"
                  class="custom-select"
                ></a-select>
              </a-form-item>
            </a-col>
            <a-col :span="6">
              <a-form-item :label="t('Pixel')">
                <a-select
                  v-model:value="form.pixel"
                  :loading="loadings.account"
                  :label-in-value="true"
                  :options="pixelOptions"
                  class="custom-select"
                ></a-select>
              </a-form-item>
            </a-col>
            <a-col :span="6">
              <a-form-item>
                <template #label>
                  <span v-if="templateRequiresForm" class="custom-label">
                    <a-tooltip placement="top" overlay-class-name="dark-tooltip">
                      <template #title>
                        {{ t('This template requires form and only supports Material ad setup') }}
                      </template>
                      <info-circle-outlined class="info-icon" />
                    </a-tooltip>
                    <span class="label-text">{{ t('Ad setup') }}</span>
                  </span>
                  <span v-else>{{ t('Ad setup') }}</span>
                </template>
                <a-select
                  v-model:value="form.ad_setup"
                  @change="handleAdSetupChange"
                  class="custom-select"
                >
                  <a-select-option value="material">{{ t('Material') }}</a-select-option>
                  <a-select-option value="post" :disabled="templateRequiresForm">
                    {{ t('Post') }}
                  </a-select-option>
                  <a-select-option
                    v-if="groupedProductSetOptions.length > 0"
                    value="catalog"
                    :disabled="templateRequiresForm"
                  >
                    {{ t('Catalog') }}
                  </a-select-option>
                </a-select>
              </a-form-item>
            </a-col>
            <a-col :span="6" v-if="form.ad_setup !== ''">
              <a-form-item :label="t('Launch Mode')">
                <a-select
                  v-model:value="form.launch_mode"
                  :options="launchModeOptions"
                  class="custom-select"
                ></a-select>
              </a-form-item>
            </a-col>
            <template v-if="form.ad_setup === 'material'">
              <a-col :span="6">
                <a-form-item :label="t('Materials')">
                  <a-row :gutter="[6, 0]" :wrap="false">
                    <a-col :flex="1">
                      <a-select :value="materialsDisplayText" class="custom-select">
                        <a-select-option
                          v-for="m in form.materials"
                          :value="m.id"
                          :key="m.id"
                          disabled
                        >
                          {{ m.name }}
                        </a-select-option>
                      </a-select>
                    </a-col>
                    <a-col>
                      <a-button
                        type="primary"
                        @click="materialPickerRef?.openModal()"
                        class="picker-btn"
                      >
                        <template #icon><select-outlined /></template>
                      </a-button>
                      <pick-objects
                        ref="materialPickerRef"
                        :api="materialsList"
                        :columns="materialColumns"
                        :title="t('Select Materials')"
                        :default-selected-rows="form.materials"
                        :default-selected-row-keys="form.materials.map(m => m.id)"
                        @confirm:items-selected="handleMaterialSelection"
                        style="display: none"
                        :hide-trigger-button="true"
                      ></pick-objects>
                    </a-col>
                  </a-row>
                </a-form-item>
              </a-col>
              <a-col :span="6">
                <a-form-item :label="t('Links')">
                  <a-row :gutter="[6, 0]" :wrap="false">
                    <a-col :flex="1">
                      <a-select :value="(form.links || [])[0]?.link || ''" class="custom-select">
                        <a-select-option v-for="l in form.links" :value="l.id" :key="l.id" disabled>
                          {{ l.link }}
                        </a-select-option>
                      </a-select>
                    </a-col>
                    <a-col>
                      <a-button
                        type="primary"
                        @click="linkPickerRef?.openModal()"
                        class="picker-btn"
                      >
                        <template #icon><select-outlined /></template>
                      </a-button>
                      <pick-objects
                        ref="linkPickerRef"
                        :multiple="false"
                        :api="queryLinksApi"
                        :allow-empty="false"
                        :columns="linkColumns"
                        :title="t('Select Link')"
                        :default-selected-rows="form.links"
                        :default-selected-row-keys="form.links.map(l => l.id)"
                        @confirm:items-selected="handleLinkSelection"
                        style="display: none"
                        :hide-trigger-button="true"
                      ></pick-objects>
                    </a-col>
                  </a-row>
                </a-form-item>
              </a-col>
              <a-col :span="6">
                <a-form-item :label="t('Copywriting')">
                  <a-row :gutter="[6, 0]" :wrap="false">
                    <a-col :flex="1">
                      <a-select
                        :value="(form.copywriting || [])[0]?.id || ''"
                        class="custom-select"
                      >
                        <a-select-option
                          v-for="c in form.copywriting"
                          :value="c.id"
                          :key="c.id"
                          disabled
                        >
                          {{ c.headline }}
                        </a-select-option>
                      </a-select>
                    </a-col>
                    <a-col>
                      <a-button
                        type="primary"
                        @click="copywritingPickerRef?.openModal()"
                        class="picker-btn"
                      >
                        <template #icon><select-outlined /></template>
                      </a-button>
                      <pick-objects
                        ref="copywritingPickerRef"
                        :multiple="false"
                        :api="queryCopywritingsApi"
                        :allow-empty="true"
                        :columns="copywritingColumns"
                        :title="t('Select Copywriting')"
                        :default-selected-rows="form.copywriting"
                        :default-selected-row-keys="form.copywriting.map(c => c.id)"
                        @confirm:items-selected="handleCopywritingSelection"
                        style="display: none"
                        :hide-trigger-button="true"
                      ></pick-objects>
                    </a-col>
                  </a-row>
                </a-form-item>
              </a-col>
            </template>
            <template v-else-if="form.ad_setup === 'post'">
              <a-col :span="6">
                <a-form-item :label="t('Post')">
                  <a-select
                    v-model:value="form.post"
                    mode="tags"
                    :placeholder="t('Enter Facebook post IDs and press Enter')"
                    :token-separators="[',', ' ']"
                    style="width: 100%"
                    :open="false"
                    class="custom-select"
                  ></a-select>
                </a-form-item>
              </a-col>
            </template>
            <template v-else-if="form.ad_setup === 'catalog'">
              <a-col :span="6">
                <a-form-item :label="t('Product Sets')">
                  <a-select
                    v-model:value="form.productSets"
                    mode="multiple"
                    :loading="loadings.productSets"
                    :options="groupedProductSetOptions"
                    :placeholder="t('Select Product Sets')"
                    :disabled="groupedProductSetOptions.length === 0"
                    @change="handleProductSetsChange"
                    class="custom-select"
                  ></a-select>
                </a-form-item>
              </a-col>
              <a-col :span="6">
                <a-form-item :label="t('Links')">
                  <a-row :gutter="[6, 0]" :wrap="false">
                    <a-col :flex="1">
                      <a-select :value="(form.links || [])[0]?.link || ''" class="custom-select">
                        <a-select-option v-for="l in form.links" :value="l.id" :key="l.id" disabled>
                          {{ l.link }}
                        </a-select-option>
                      </a-select>
                    </a-col>
                    <a-col>
                      <a-button
                        type="primary"
                        @click="linkPickerRef?.openModal()"
                        class="picker-btn"
                      >
                        <template #icon><select-outlined /></template>
                      </a-button>
                      <pick-objects
                        ref="linkPickerRef"
                        :multiple="false"
                        :api="queryLinksApi"
                        :allow-empty="false"
                        :columns="linkColumns"
                        :title="t('Select Link')"
                        :default-selected-rows="form.links"
                        :default-selected-row-keys="form.links.map(l => l.id)"
                        @confirm:items-selected="handleLinkSelection"
                        style="display: none"
                        :hide-trigger-button="true"
                      ></pick-objects>
                    </a-col>
                  </a-row>
                </a-form-item>
              </a-col>
            </template>
          </a-row>
        </a-col>
      </a-row>
    </a-form>
  </div>
</template>

<script lang="ts">
import type { ComponentPublicInstance } from 'vue';
import { defineComponent, onMounted, reactive, ref, watch, computed, h, nextTick } from 'vue';
import { useI18n } from 'vue-i18n';
import { ReloadOutlined, SelectOutlined, InfoCircleOutlined } from '@ant-design/icons-vue';
import { queryFB_AD_AccountOneApi } from '@/api/fb_ad_accounts';
import { queryLinksApi } from '@/api/links';
import { materialsList } from '@/api/materials';
import { queryCopywritingsApi } from '@/api/copywritings';
import { getPageForms, queryPagesApi } from '@/api/pages';
import PickObjects from './pick-objects.vue';
import { message, Tag } from 'ant-design-vue';
import type { SelectProps } from 'ant-design-vue';

// Temporary type definitions using any, replace later
type FbAdAccount = any;
type FbAccount = any;
type BMSystemUser = any;
type FbBusinessUser = any;
type AssignedPage = any;
type FbApiToken = any;
type LinkModel = any;
type Material = any;
type Copywriting = any;
type FbPage = any;
type FbPageForm = any;

// 定义PickObjects组件实例类型
type PickObjectsInstance = ComponentPublicInstance & {
  openModal: () => void;
};

interface FormState {
  ad_account_id: string;
  operator: SelectProps['value'];
  operator_type: 'fb' | 'bm' | null;
  operator_detail: FbAccount | BMSystemUser | null;
  page: SelectProps['value'];
  page_source_id: string | null;
  pixel: SelectProps['value'];
  form: SelectProps['value'];
  ad_setup: 'material' | 'post' | 'catalog' | '';
  launch_mode: 1 | 2 | 3;
  materials: Material[];
  links: LinkModel[];
  copywriting: Copywriting[];
  post: string[];
  productSets: string[];
  productSetDetails: Array<{ id: string; name: string }>;
  campaignId?: string;
  adsetId?: string;
}

export default defineComponent({
  name: 'EditAccount',
  components: {
    PickObjects,
    InfoCircleOutlined,
    SelectOutlined,
  },
  props: {
    adAccount: {
      type: Object,
      required: true,
    },
    template: {
      type: Object,
      required: true,
    },
    configBlock: {
      type: Object,
      required: false,
      default: () => ({}),
    },
  },
  emits: ['change:account-data'],
  setup(props, { emit }) {
    const { t } = useI18n();

    const internalAdAccount = ref<FbAdAccount>(props.adAccount);
    const pages = ref<AssignedPage[]>([]);
    const fbPages = ref<FbPage[]>([]);
    const forms = ref<FbPageForm[]>([]);
    const productSets = ref<any[]>([]);

    const loadings = reactive({
      account: false,
      operator: false,
      page: false,
      form: false,
      copywriting: false,
      materials: false,
      links: false,
      post: false,
      pixel: false,
      productSets: false,
    });

    const form = reactive<FormState>({
      ad_account_id: internalAdAccount.value.id,
      operator_type: 'fb',
      operator: null,
      operator_detail: null,
      page: null,
      page_source_id: null,
      pixel: null,
      form: null,
      ad_setup: '',
      launch_mode: 3,
      materials: [],
      links: [],
      copywriting: [],
      post: [],
      productSets: [],
      productSetDetails: [],
      campaignId: props.configBlock.campaignId || '',
      adsetId: props.configBlock.adsetId || '',
    });

    const materialColumns = [
      { title: t('Name'), dataIndex: 'name', key: 'name' },
      { title: t('Filename'), dataIndex: 'filename', key: 'filename' },
      { title: t('Notes'), dataIndex: 'notes', key: 'notes' },
      {
        title: t('Tags'),
        dataIndex: 'tags',
        key: 'tags',
        customRender: ({ value }) => value?.map(tag => h(Tag, tag.name)).join(', '),
      },
    ];
    const linkColumns = [
      { title: t('Link'), dataIndex: 'link', key: 'link' },
      { title: t('Notes'), dataIndex: 'notes', key: 'notes' },
      {
        title: t('Tags'),
        dataIndex: 'tags',
        key: 'tags',
        customRender: ({ value }) => value?.map(tag => h(Tag, tag.name)).join(', '),
      },
    ];
    const copywritingColumns = [
      { title: t('Headline'), dataIndex: 'headline', key: 'headline' },
      { title: t('Primary Text'), dataIndex: 'primary_text', key: 'primary_text' },
      { title: t('Description'), dataIndex: 'description', key: 'description' },
      { title: t('Notes'), dataIndex: 'notes', key: 'notes' },
    ];

    const operatorOptions = computed<SelectProps['options']>(() => {
      const fbAccountGroup: SelectProps['options'] = (
        internalAdAccount.value.fb_accounts || []
      ).map(fa => ({
        label: fa.name,
        value: fa.id,
        type: 'fb',
        detail: fa,
      }));
      const validBmTokens = (internalAdAccount.value.fb_api_token || []).filter(
        (token: FbApiToken) => {
          const bmUsers = internalAdAccount.value.fb_business_users || [];
          return (
            token.token_type === 1 &&
            token.active &&
            token.bm &&
            bmUsers.some(
              bu =>
                bu.role &&
                bu.role !== 'Reporting only' &&
                bu.is_operator === true &&
                token.bm?.users?.some(tbu => tbu.id === bu.id),
            )
          );
        },
      );
      const bmGroup: SelectProps['options'] = validBmTokens.map(token => {
        const businessUser = internalAdAccount.value.fb_business_users?.find(
          bu => bu.is_operator === true && token.bm?.users?.some(tbu => tbu.id === bu.id),
        );
        return {
          label: token.name,
          value: token.id,
          type: 'bm',
          detail: businessUser,
        };
      });
      const options: SelectProps['options'] = [];
      if (fbAccountGroup.length > 0)
        options.push({ label: t('Fb Accounts'), options: fbAccountGroup });
      if (bmGroup.length > 0)
        options.push({ label: t('BMs (Filtered API Tokens)'), options: bmGroup });
      return options;
    });

    const pageOptions = computed<SelectProps['options']>(() => {
      if (form.operator_type === 'fb') {
        return fbPages.value.map(p => ({
          label: `${p.name} (${p.source_id})`,
          value: p.id,
          source_id: p.source_id,
        }));
      } else if (form.operator_type === 'bm' && form.operator_detail) {
        return (
          (form.operator_detail as FbBusinessUser).assigned_pages?.map(p => ({
            label: `${p.name} (${p.source_id})`,
            value: p.id,
            source_id: p.source_id,
          })) || []
        );
      }
      return [];
    });

    const pixelOptions = computed<SelectProps['options']>(() =>
      (internalAdAccount.value.pixels || []).map(p => ({
        label: `${p.name} (${p.pixel})`,
        value: p.id,
        disabled: p.is_unavailable,
      })),
    );

    const formOptions = computed<SelectProps['options']>(() =>
      forms.value.map(f => ({ label: `${f.name} (${f.source_id})`, value: f.id })),
    );

    const groupedProductSetOptions = computed<SelectProps['options']>(() => {
      if (form.operator_type !== 'bm' || !form.operator_detail) return [];

      const assignedCatalogs = (form.operator_detail as FbBusinessUser).assigned_catalogs || [];

      return assignedCatalogs
        .map(catalog => {
          const setsForThisCatalog = catalog.product_sets || [];
          return {
            label: catalog.name,
            options: setsForThisCatalog.map((set: any) => ({
              label: set.name,
              value: set.id,
            })),
          };
        })
        .filter(group => group.options.length > 0);
    });

    // 根据所选campaign和adset来动态计算launchModeOptions
    const launchModeOptions = computed<SelectProps['options']>(() => {
      const hasCampaign = !!selectedCampaignId.value;
      const hasAdset = !!selectedAdsetId.value;

      const options = [
        { label: 'N-1-1', value: 1, disabled: hasCampaign },
        { label: '1-N-1', value: 2, disabled: hasAdset },
        { label: '1-1-N', value: 3, disabled: false }, // 1-1-N 始终可用
      ];

      return options;
    });

    // 监听 launchModeOptions 的变化，如果当前选中的launchMode被禁用了，自动更新为可用选项
    watch(
      [launchModeOptions, () => form.launch_mode],
      () => {
        const options = launchModeOptions.value;
        const currentModeDisabled = options.find(opt => opt.value === form.launch_mode)?.disabled;
        if (currentModeDisabled) {
          const hasCampaign = !!selectedCampaignId.value;
          const hasAdset = !!selectedAdsetId.value;
          // 如果选择了adset，只能用1-1-N
          if (hasAdset) {
            form.launch_mode = 3; // 1-1-N
          }
          // 如果只选择了campaign没选adset，可以用1-1-N或1-N-1
          else if (hasCampaign) {
            form.launch_mode = 3; // 默认使用1-1-N，也可以改为2(1-N-1)
          }
        }
      },
      { immediate: true },
    );

    const showFormSelector = computed(
      () =>
        props.template.objective === 'OUTCOME_LEADS' &&
        props.template.conversion_location === 'INSTANT_FORMS',
    );

    const templateRequiresForm = computed(
      () =>
        props.template.objective === 'OUTCOME_LEADS' &&
        props.template.conversion_location === 'INSTANT_FORMS',
    );

    const loadFbPages = async (fbAccountId: string) => {
      if (!fbAccountId) return;
      loadings.page = true;
      try {
        const res = await queryPagesApi({ current: 1, pageSize: 500, fb_account_id: fbAccountId });
        if (res.data && res.data.items) {
          fbPages.value = res.data.items;

          // 自动选择第一条页面记录，如果有数据且当前没有选择
          if (fbPages.value.length > 0 && !form.page) {
            const firstPage = fbPages.value[0];
            form.page = {
              value: firstPage.id,
              label: `${firstPage.name} (${firstPage.source_id})`,
            };
            form.page_source_id = firstPage.source_id;

            // 如果需要加载表单，则加载
            if (showFormSelector.value && firstPage.source_id) {
              loadForms(firstPage.source_id);
            }
          }
        } else {
          fbPages.value = [];
          message.error(t('Failed to load Facebook pages'));
        }
      } catch (error) {
        console.error('Error loading FB pages:', error);
        message.error(t('Error loading Facebook pages'));
        fbPages.value = [];
      } finally {
        loadings.page = false;
      }
    };
    const loadForms = async (pageSourceId: string) => {
      if (!pageSourceId || !form.operator_detail?.id) return;
      loadings.form = true;
      try {
        const res = await getPageForms({
          page_id: pageSourceId,
          operator_id: form.operator_detail.id,
        });
        if (res.data && Array.isArray(res.data)) {
          // Adjust based on actual API response structure
          forms.value = res.data;
        } else {
          forms.value = [];
          message.warn(t('No forms found for this page or failed to load.'));
        }
      } catch (error) {
        console.error('Error loading forms:', error);
        message.error(t('Error loading forms'));
        forms.value = [];
      } finally {
        loadings.form = false;
      }
    };

    const getStatusColor = (status: string) => {
      const statusMap: Record<string, string> = {
        ACTIVE: 'green',
        DISABLED: 'red',
        PENDING_REVIEW: 'orange',
        UNSETTLED: 'gold',
        CLOSED: 'red',
      };
      return statusMap[status] || 'default';
    };

    const onReload = async () => {
      loadings.account = true;

      try {
        // Add the 'with-campaign' parameter to the API call
        const { data } = await queryFB_AD_AccountOneApi({ id: props.adAccount.id });

        if (data) {
          internalAdAccount.value = data;
          initializeFormDefaults();

          // Check if selected campaign/adset still exists after reload
          if (selectedCampaignId.value && data.campaigns) {
            const campaignExists = data.campaigns.some(
              (c: any) => c.id === selectedCampaignId.value,
            );
            if (!campaignExists) {
              selectedCampaignId.value = '';
              selectedAdsetId.value = '';
            } else if (selectedAdsetId.value && selectedCampaign.value) {
              const adsetExists = selectedCampaign.value.adsets.some(
                (a: any) => a.id === selectedAdsetId.value,
              );
              if (!adsetExists) {
                selectedAdsetId.value = '';
              }
            }
          }

          message.success(t('Account data refreshed'));
        } else {
          message.error(t('Failed to refresh account data'));
        }
      } catch (error) {
        console.error('Error reloading account:', error);
        message.error(t('Error refreshing account data'));
      } finally {
        loadings.account = false;
      }
    };

    const handleOperatorChange = (_value: SelectProps['value'], option: any | any[]) => {
      const selectedOption = Array.isArray(option) ? option[0] : option;

      if (!selectedOption) {
        form.operator_type = null;
        form.operator_detail = null;
        form.page = undefined;
        fbPages.value = [];
        return;
      }

      form.operator_type = selectedOption.type;
      form.operator_detail = selectedOption.detail;

      form.page = undefined;
      form.page_source_id = null;
      form.form = undefined;
      forms.value = [];

      if (form.operator_type === 'fb' && form.operator_detail) {
        const fbAccountId = form.operator_detail.id;
        loadFbPages(fbAccountId);
      } else if (form.operator_type === 'bm') {
        fbPages.value = [];
        const bmPages = (form.operator_detail as FbBusinessUser).assigned_pages;
        if (bmPages && bmPages.length > 0) {
          // 自动选择第一个页面
          form.page = {
            value: bmPages[0].id,
            label: `${bmPages[0].name} (${bmPages[0].source_id})`,
          };
          form.page_source_id = bmPages[0].source_id;
          if (showFormSelector.value) {
            loadForms(bmPages[0].source_id);
          }
        }
      } else {
        fbPages.value = [];
      }

      form.productSets = [];
      productSets.value = [];

      if (form.operator_type === 'bm') {
        // TODO: Add BM operator logic
      }
    };

    const handlePageChange = (_value: SelectProps['value'], option: any | any[]) => {
      const selectedOption = Array.isArray(option) ? option[0] : option;
      form.page_source_id = selectedOption?.source_id || null;
      form.form = undefined;
      forms.value = [];
      if (showFormSelector.value && form.page_source_id) {
        loadForms(form.page_source_id);
      }
      if (form.ad_setup !== 'catalog') {
        form.productSets = [];
        productSets.value = [];
      }
    };

    const handleAdSetupChange = () => {
      if (form.ad_setup !== 'material' && form.ad_setup !== 'catalog') {
        form.links = [];
      }
      if (form.ad_setup !== 'material') {
        form.materials = [];
        form.copywriting = [];
      }
      if (form.ad_setup !== 'post') {
        form.post = [];
      }
      if (form.ad_setup !== 'catalog') {
        form.productSets = [];
        productSets.value = [];
      }

      // 明确记录当前的campaignId和adsetId，确保不会丢失
      console.log('Ad setup changed, preserving campaignId:', selectedCampaignId.value);
      console.log('Ad setup changed, preserving adsetId:', selectedAdsetId.value);

      // 显式调用emitChange确保数据更新
      emitChange();
    };

    const handleMaterialSelection = (_keys: any[], rows: Material[]) => {
      form.materials = rows;
    };
    const handleLinkSelection = (_keys: any[], rows: LinkModel[]) => {
      form.links = rows;
    };
    const handleCopywritingSelection = (_keys: any[], rows: Copywriting[]) => {
      form.copywriting = rows;
    };

    const handleProductSetsChange = (values: string[]) => {
      form.productSets = values;

      // 更新product set details，保存ID和名称信息
      form.productSetDetails = values.map(id => {
        // 查找该ID对应的名称
        let name = id; // 默认使用ID作为名称

        // 在所有catalog组中搜索该ID对应的产品集
        groupedProductSetOptions.value.forEach(group => {
          if (group.options) {
            const option = group.options.find((opt: any) => opt.value === id);
            if (option) {
              name = option.label || id;
            }
          }
        });

        return { id, name };
      });
    };

    const initializeFormDefaults = () => {
      let defaultOperatorOption: any = null;
      const bmOptions = operatorOptions.value.find(group =>
        group.label?.toString().includes('BMs'),
      )?.options;
      const fbOptions = operatorOptions.value.find(group =>
        group.label?.toString().includes('Fb Accounts'),
      )?.options;

      if (bmOptions && bmOptions.length > 0) {
        defaultOperatorOption = bmOptions[0];
      } else if (fbOptions && fbOptions.length > 0) {
        defaultOperatorOption = fbOptions[0];
      }

      console.log('defaultOperatorOption', defaultOperatorOption);
      if (defaultOperatorOption) {
        form.operator = { value: defaultOperatorOption.value, label: defaultOperatorOption.label };
        handleOperatorChange(form.operator.value, defaultOperatorOption);
      } else {
        form.operator = undefined;
        form.operator_type = null;
        form.operator_detail = null;
      }

      const availablePixels = pixelOptions.value?.filter(p => !p.disabled);
      if (availablePixels && availablePixels.length > 0) {
        form.pixel = { value: availablePixels[0].value, label: availablePixels[0].label };
      } else {
        form.pixel = undefined;
      }

      form.ad_setup = 'material';
      form.launch_mode = 3;

      if (props.template) {
        form.ad_setup = props.template.ad_setup || form.ad_setup;
        form.launch_mode = props.template.launch_mode || form.launch_mode;
      }
    };

    onMounted(() => {
      initializeFormDefaults();
      if (!form.ad_setup) {
        form.ad_setup = 'material';
      }

      // 初始化后立即发送当前配置数据，确保父组件能获取到初始的campaign/adset信息
      // 使用nextTick确保DOM更新后再发送数据
      nextTick(() => {
        console.log('组件已挂载，发送初始配置数据');

        // 初始挂载时设置的campaignId和adsetId
        console.log('初始campaignId:', selectedCampaignId.value);
        console.log('初始adsetId:', selectedAdsetId.value);

        // 发送初始数据
        emitChange();
      });
    });

    // 在这里先定义selectedCampaignId和selectedAdsetId
    const selectedCampaignId = ref(props.configBlock?.campaignId || '');
    const selectedAdsetId = ref(props.configBlock?.adsetId || '');

    // Helper function to collect current form data
    const collectFormData = () => {
      console.log('Collecting form data with campaignId:', selectedCampaignId.value);
      console.log('Collecting form data with adsetId:', selectedAdsetId.value);

      const data: any = {
        ad_account_id: props.adAccount.id,
        operator_type: form.operator_type,
        operator: form.operator,
        pixel: form.pixel,
        page: form.page,
        form: form.form,
        ad_setup: form.ad_setup,
        launch_mode: form.launch_mode,
        // 始终包含campaign和adset ID，注意直接使用.value而不是可能为null的值
        campaignId: selectedCampaignId.value || null,
        adsetId: selectedAdsetId.value || null,
      };

      // 添加campaign和adset的完整对象，用于在review页面显示
      if (selectedCampaignId.value && selectedCampaign.value) {
        data.campaign = selectedCampaign.value;
      }

      if (selectedAdsetId.value && selectedCampaign.value?.adsets) {
        data.adset = selectedCampaign.value.adsets.find((a: any) => a.id === selectedAdsetId.value);
      }

      if (form.ad_setup === 'material') {
        data.materials = form.materials;
        data.links = form.links;
        data.copywriting = form.copywriting;
      } else if (form.ad_setup === 'post') {
        data.post = form.post;
        // 明确在post模式下也保留campaignId值和campaign对象
        data.campaignId = selectedCampaignId.value || null;
        data.adsetId = selectedAdsetId.value || null;

        // 在post模式下也添加campaign和adset对象
        if (selectedCampaignId.value && selectedCampaign.value) {
          data.campaign = selectedCampaign.value;
        }

        if (selectedAdsetId.value && selectedCampaign.value?.adsets) {
          data.adset = selectedCampaign.value.adsets.find(
            (a: any) => a.id === selectedAdsetId.value,
          );
        }
      } else if (form.ad_setup === 'catalog') {
        data.productSets = form.productSets;
        data.productSetDetails = form.productSetDetails;
        data.links = form.links;
      }

      console.log('Final form data to emit:', data);
      return data;
    };

    // 表单数据变化时触发
    const emitChange = () => {
      const data = collectFormData();
      console.log('data changed, to emit:', data);
      emit('change:account-data', data);
    };

    // 选中的campaign
    const selectedCampaign = computed(() => {
      if (!selectedCampaignId.value || !props.adAccount.campaigns) return null;
      return props.adAccount.campaigns.find((c: any) => c.id === selectedCampaignId.value);
    });

    // Campaign选项
    const campaignOptions = computed(() => {
      if (!props.adAccount.campaigns) return [];
      return props.adAccount.campaigns.map((campaign: any) => ({
        label: campaign.name,
        value: campaign.id,
        sourceId: campaign.source_id,
      }));
    });

    // Adset选项
    const adsetOptions = computed(() => {
      if (!selectedCampaign.value || !selectedCampaign.value.adsets) return [];
      return selectedCampaign.value.adsets.map((adset: any) => ({
        label: adset.name,
        value: adset.id,
        sourceId: adset.source_id,
      }));
    });

    // Campaign变更处理
    const handleCampaignChange = (_value: string) => {
      // 当campaign改变时，清空adset选择
      form.adsetId = '';
      emitChange();
    };

    // Add these inside the setup function, alongside other refs
    const onCampaignChange = (value: string) => {
      selectedCampaignId.value = value;
      selectedAdsetId.value = '';

      // 优先清除adset相关数据
      form.adsetId = '';

      // 更新campaign相关数据
      if (value && selectedCampaign.value) {
        form.campaignId = selectedCampaign.value.source_id;
        // 确保campaign对象被传递，用于顶部层级显示
        console.log('选择了campaign:', selectedCampaign.value.name);

        // 当选择了campaign，检查launch_mode是否需要更新
        if (form.launch_mode === 1) {
          // N-1-1不能用于已选择campaign的情况
          // 默认改为1-1-N
          form.launch_mode = 3;
          console.log('已选择campaign，自动更新launch_mode为:', form.launch_mode);
        }
      } else {
        form.campaignId = '';
        console.log('清除了campaign选择');
      }

      // 使用collectFormData收集表单数据，确保字段名一致
      const data = collectFormData();
      console.log('campaign changed, data to emit:', data);
      emit('change:account-data', data);
    };

    const onAdsetChange = (value: string) => {
      selectedAdsetId.value = value;

      // 更新adset相关数据
      if (value && selectedCampaign.value) {
        const selectedAdset = selectedCampaign.value.adsets.find((a: any) => a.id === value);
        if (selectedAdset) {
          form.adsetId = selectedAdset.source_id;
          console.log('选择了adset:', selectedAdset.name);

          // 当选择了adset，只能使用1-1-N模式
          if (form.launch_mode !== 3) {
            form.launch_mode = 3; // 强制设为1-1-N
            console.log('已选择adset，自动更新launch_mode为1-1-N');
          }
        }
      } else {
        form.adsetId = '';
        console.log('清除了adset选择');
      }

      // 使用collectFormData收集表单数据，确保字段名一致
      const data = collectFormData();
      console.log('adset changed, data to emit:', data);
      emit('change:account-data', data);
    };

    // Refs for pick-objects components
    const materialPickerRef = ref<PickObjectsInstance | null>(null);
    const linkPickerRef = ref<PickObjectsInstance | null>(null);
    const copywritingPickerRef = ref<PickObjectsInstance | null>(null);

    // Re-introduce materialsDisplayText
    const materialsDisplayText = computed(() => {
      const count = form.materials.length;
      if (count === 0) return undefined;
      return `${count} ${count > 1 ? t('items') : t('item')} ${t('selected')}`;
    });

    // 监听templateRequiresForm变化
    watch(
      templateRequiresForm,
      requiresForm => {
        if (requiresForm && form.ad_setup !== 'material') {
          form.ad_setup = 'material';
          handleAdSetupChange();
          // 显示提示
          message.info(t('Ad setup has been set to Material as this template requires form'));
        }
      },
      { immediate: true },
    );

    // 监听selectedCampaignId和selectedAdsetId的变化，确保它们变化时也触发数据更新
    watch([selectedCampaignId, selectedAdsetId], () => {
      console.log('Campaign/Adset ID changed, emitting data change');
      console.log('Current campaignId:', selectedCampaignId.value);
      console.log('Current adsetId:', selectedAdsetId.value);

      // 确保前一次的表单更新完成后再调用emitChange
      nextTick(() => {
        emitChange();
      });
    });

    // 修改原有的form watch函数
    watch(
      () => ({ ...form }),
      _newValue => {
        // 避免直接emit，改为调用emitChange以确保一致性
        console.log('Form changed, will emit via emitChange');

        // 使用nextTick确保在DOM更新后执行
        nextTick(() => {
          emitChange();
        });
      },
      { deep: true, immediate: true },
    );

    return {
      t,
      h,
      ReloadOutlined,
      SelectOutlined,
      internalAdAccount,
      form,
      pages,
      fbPages,
      forms,
      productSets,
      loadings,
      operatorOptions,
      pageOptions,
      pixelOptions,
      formOptions,
      launchModeOptions,
      groupedProductSetOptions,
      showFormSelector,
      templateRequiresForm,
      onReload,
      handleOperatorChange,
      handlePageChange,
      handleAdSetupChange,
      queryLinksApi,
      materialsList,
      queryCopywritingsApi,
      materialColumns,
      linkColumns,
      copywritingColumns,
      handleMaterialSelection,
      handleLinkSelection,
      handleCopywritingSelection,
      getStatusColor,
      loadForms,
      // Refs for pickers
      materialPickerRef,
      linkPickerRef,
      copywritingPickerRef,
      // Computed display options
      materialsDisplayText,
      handleProductSetsChange,
      selectedCampaign,
      campaignOptions,
      adsetOptions,
      handleCampaignChange,
      selectedCampaignId,
      selectedAdsetId,
      onCampaignChange,
      onAdsetChange,
    };
  },
});
</script>

<style lang="less" scoped>
.ad-account-section {
  margin-bottom: 24px;
  padding: 18px 20px;
  border-radius: 10px;
  box-shadow: 0 3px 15px rgba(0, 0, 0, 0.06);
  background: linear-gradient(to right, #fcfcfc, #ffffff);
  position: relative;
  border: 1px solid rgba(240, 240, 240, 0.8);
  transition: all 0.3s cubic-bezier(0.25, 0.8, 0.25, 1);

  &:hover {
    box-shadow: 0 6px 20px rgba(24, 144, 255, 0.1);
    transform: translateY(-2px);
  }

  &::before {
    content: '';
    position: absolute;
    left: 0;
    top: 10px;
    height: calc(100% - 20px);
    width: 4px;
    background: linear-gradient(to bottom, #1890ff, #36cfc9);
    border-radius: 4px 0 0 4px;
    transition: all 0.3s ease;
  }

  &:hover::before {
    top: 5px;
    height: calc(100% - 10px);
  }
}

.account-status {
  font-size: 12px;
  color: #888;
  margin-top: 4px;
  display: flex;
  align-items: center;

  :deep(.ant-tag) {
    margin-left: 8px;
    border-radius: 4px;
    padding: 0 6px;
    line-height: 18px;
    height: 20px;
    font-size: 11px;
    font-weight: 500;
    border: none;
    box-shadow: 0 2px 4px rgba(0, 0, 0, 0.06);
    text-transform: uppercase;
  }
}

:deep(.ant-form-item) {
  margin-bottom: 14px;
  position: relative;

  &:hover {
    .ant-form-item-label > label {
      color: #1890ff;
    }
  }
}

:deep(.ant-form-item-label) {
  padding-bottom: 5px;
  transition: all 0.3s ease;

  label {
    color: #555;
    font-weight: 500;
    font-size: 14px;
    transition: color 0.3s ease;
    position: relative;

    &::after {
      content: '';
      position: absolute;
      left: 0;
      bottom: -2px;
      width: 0;
      height: 1px;
      background: #1890ff;
      transition: width 0.3s ease;
    }
  }
}

.label-info {
  margin-left: 4px;
  color: #1890ff;
  font-size: 14px;
  position: relative;
  top: -1px;
  cursor: pointer;
  transition: color 0.3s ease;

  &:hover {
    color: #40a9ff;
  }
}

.custom-input {
  border-radius: 6px;
  transition: all 0.3s ease;

  &:disabled {
    background-color: #f9f9f9 !important;
    color: #333 !important;
    border-color: rgba(232, 232, 232, 0.8);
    box-shadow: inset 0 1px 3px rgba(0, 0, 0, 0.02);
  }
}

.custom-select {
  width: 100%;

  :deep(.ant-select-selector) {
    border-radius: 6px;
    transition: all 0.3s ease;
    border-color: #e8e8e8;

    &:hover {
      border-color: #40a9ff;
    }
  }

  :deep(.ant-select-selection-item) {
    transition: all 0.3s ease;
  }

  &:hover {
    :deep(.ant-select-arrow) {
      color: #1890ff;
    }
  }
}

.refresh-btn,
.picker-btn {
  border-radius: 6px;
  display: flex;
  align-items: center;
  justify-content: center;
  transition: all 0.3s ease;
  box-shadow: 0 2px 4px rgba(24, 144, 255, 0.1);

  &:hover {
    transform: translateY(-1px);
    box-shadow: 0 4px 8px rgba(24, 144, 255, 0.15);
  }

  &:active {
    transform: translateY(0);
  }
}

.refresh-btn {
  background: linear-gradient(to bottom right, #1890ff, #2c88ff);
  border: none;

  &:hover {
    background: linear-gradient(to bottom right, #40a9ff, #4096ff);
  }
}

.picker-btn {
  background: linear-gradient(to bottom right, #1890ff, #36cfc9);
  border: none;
  border-radius: 6px;

  &:hover {
    background: linear-gradient(to bottom right, #40a9ff, #40d3cc);
    border-color: transparent;
  }
}

// 为材料选择按钮添加不同颜色的渐变
:deep(pick-objects[ref='materialPickerRef'] .custom-select-btn) {
  background: linear-gradient(to bottom right, #1890ff, #36cfc9);
  &:hover {
    background: linear-gradient(to bottom right, #40a9ff, #40d3cc);
  }
}

// 为链接选择按钮添加不同颜色的渐变
:deep(pick-objects[ref='linkPickerRef'] .custom-select-btn) {
  background: linear-gradient(to bottom right, #13c2c2, #1890ff);
  &:hover {
    background: linear-gradient(to bottom right, #36cfc9, #40a9ff);
  }
}

// 为文案选择按钮添加不同颜色的渐变
:deep(pick-objects[ref='copywritingPickerRef'] .custom-select-btn) {
  background: linear-gradient(to bottom right, #1890ff, #fa8c16);
  &:hover {
    background: linear-gradient(to bottom right, #40a9ff, #ffa940);
  }
}

// 保留隐藏 pick-objects 样式
:deep(.ant-form-item-control-input-content > pick-objects) {
  display: block;
  height: 0;
  overflow: hidden;
  opacity: 0;
  position: absolute;
}

// 响应式调整
@media (max-width: 992px) {
  .ad-account-section {
    padding: 16px;
  }
}

// 添加滚动条样式
:deep(*::-webkit-scrollbar) {
  width: 6px;
  height: 6px;
}

:deep(*::-webkit-scrollbar-thumb) {
  background: rgba(0, 0, 0, 0.15);
  border-radius: 10px;
}

:deep(*::-webkit-scrollbar-thumb:hover) {
  background: rgba(0, 0, 0, 0.2);
}

:deep(*::-webkit-scrollbar-track) {
  background: rgba(0, 0, 0, 0.05);
  border-radius: 10px;
}

// 添加全局样式，为黑色tooltip提供样式
:global(.dark-tooltip) {
  max-width: 400px;

  :deep(.ant-tooltip-inner) {
    background-color: rgba(0, 0, 0, 0.85);
    color: white;
    border-radius: 4px;
    font-size: 12px;
    padding: 8px 12px;
    box-shadow: 0 3px 6px rgba(0, 0, 0, 0.2);
  }

  :deep(.ant-tooltip-arrow) {
    &::before {
      background-color: rgba(0, 0, 0, 0.85);
    }
  }
}

.custom-label {
  display: flex;
  align-items: center;

  .info-icon {
    font-size: 14px;
    margin-right: 4px;
    color: #8c8c8c;
    transition: all 0.3s ease;

    &:hover {
      color: #1890ff;
    }
  }

  .label-text {
    color: #555;
    font-weight: 500;
    font-size: 14px;
  }
}
</style>
