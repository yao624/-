<template>
  <page-container :show-page-header="false" :title="t('Create Ads V2')">
    <a-card :style="{ marginBottom: '16px' }">
      <a-steps :current="currentStep" class="steps-header">
        <a-step
          :title="t('Pick Accounts & Template')"
          @click="currentStep > 0 && (currentStep = 0)"
        />
        <a-step :title="t('Configure Ads')" @click="currentStep > 1 && (currentStep = 1)" />
        <a-step :title="t('Review & Launch')" />
      </a-steps>
    </a-card>

    <div class="content">
      <!-- 不使用v-show, 而是使用CSS隐藏，确保组件始终挂载 -->
      <a-card>
        <!-- Step 1: Pick Accounts & Template -->
        <div :class="{ 'step-hidden': currentStep !== 0 }">
          <pick-accounts
            ref="accountsRef"
            :loading="loading.accountsLoading"
            :ad-accounts="adAccounts"
          />
        </div>

        <!-- Step 2: Configure Ads -->
        <div :class="{ 'step-hidden': currentStep !== 1 }">
          <div v-if="configBlocks.length > 0">
            <div
              v-for="(block, index) in configBlocks"
              :key="block.id"
              class="config-block"
              style="margin-bottom: 24px; position: relative"
            >
              <div
                class="config-block-header"
                style="
                  display: flex;
                  justify-content: space-between;
                  align-items: center;
                  margin-bottom: 8px;
                  padding: 8px 0;
                  border-bottom: 1px solid #f0f0f0;
                "
              >
                <h3 style="margin: 0; font-size: 16px; font-weight: 500">
                  {{ getConfigBlockTitle(block) }}
                </h3>
                <div class="config-block-controls">
                  <a-button type="link" @click="copyConfigBlock(index)" style="padding: 0 8px">
                    {{ t('Copy') }}
                  </a-button>
                  <a-button
                    type="link"
                    danger
                    @click="deleteConfigBlock(index)"
                    style="padding: 0 8px"
                  >
                    {{ t('Delete') }}
                  </a-button>
                </div>
              </div>
              <edit-account
                :template="template"
                :ad-account="block.adAccount"
                :config-block="block"
                @change:account-data="data => onDataChange(data, index)"
              />
            </div>
          </div>
          <a-empty v-else :description="t('Please select ad accounts in the previous step.')" />
        </div>

        <!-- Step 3: Review & Launch -->
        <div :class="{ 'step-hidden': currentStep !== 2 }">
          <review-launch
            v-if="configBlocks.length > 0 && template.id"
            :config-blocks="getProcessedConfigBlocks()"
            :accounts="[]"
            :template="template"
          />
          <a-empty v-else :description="t('Please complete the previous steps.')" />
        </div>
      </a-card>
    </div>

    <!-- Footer Actions -->
    <a-card>
      <a-row :gutter="[12, 0]" justify="end">
        <a-col>
          <a-button v-if="currentStep > 0" key="back" @click="onBack">{{ t('Back') }}</a-button>
        </a-col>
        <a-col>
          <a-button :loading="loading.submitLoading" key="submit" type="primary" @click="onNext">
            {{ getActionButtonText }}
          </a-button>
        </a-col>
      </a-row>
    </a-card>
  </page-container>
</template>

<script lang="ts">
import { defineComponent, onMounted, ref, computed, reactive, watch, nextTick } from 'vue';
import { useI18n } from 'vue-i18n';
import PickAccounts from './pick-accounts.vue';
import EditAccount from './edit-account.vue';
import ReviewLaunch from './review-launch.vue';
import { message, Steps, Empty } from 'ant-design-vue';
import { getFbAdTemplate } from '@/api/fb_ad_template';
import { launchAds } from '@/api/fb-ads';
import { useRoute } from 'vue-router';
import { queryFB_AD_AccountsApi } from '@/api/fb_ad_accounts'; // Assuming correct path
import PageContainer from '@/components/base-layouts/page-container/index.vue';

interface ConfigBlock {
  id: string;
  adAccount: any;
  campaign?: any;
  campaignId?: string;
  adset?: any;
  adsetId?: string;
  type: 'account' | 'campaign' | 'adset';
  data?: any;
}

export default defineComponent({
  name: 'CreateAdsV2',
  components: {
    PickAccounts,
    EditAccount,
    ReviewLaunch,
    PageContainer,
    'a-steps': Steps,
    'a-step': Steps.Step,
    'a-empty': Empty,
  },
  setup() {
    const { t } = useI18n();
    const route = useRoute();

    // --- State ---
    const currentStep = ref(0);
    const adAccounts = ref<any[]>([]); // Use more specific type if available
    const accountsRef = ref<InstanceType<typeof PickAccounts> | null>(null); // Ref for PickAccounts component
    const template = ref<any>({}); // Use specific template type
    const configBlocks = ref<ConfigBlock[]>([]); // Store configuration blocks

    // 使用普通数组存储配置块数据而不是响应式数组
    let configBlocksData: any[] = [];

    // Loading states
    const loading = reactive({
      accountsLoading: false,
      templateLoading: false,
      submitLoading: false,
    });

    // --- Computed Properties ---
    const getActionButtonText = computed(() => {
      if (currentStep.value === 2) return t('Launch Ads');
      return t('Next');
    });

    // 生成配置块的标题
    const getConfigBlockTitle = (block: ConfigBlock) => {
      // 如果是adset类型并且adset对象存在，显示完整路径
      if (block.type === 'adset' && block.adset && block.campaign) {
        return `${block.adAccount.name} > ${block.campaign.name} > ${block.adset.name}`;
      }
      // 如果是campaign类型或者有campaign对象，显示账户+campaign
      else if ((block.type === 'campaign' || block.type === 'adset') && block.campaign) {
        return `${block.adAccount.name} > ${block.campaign.name}`;
      }
      // 其他情况只显示账户名
      else {
        return block.adAccount.name;
      }
    };

    // --- Methods ---
    // 解析URL中的aid参数
    const parseAidParam = (aidParam: string) => {
      const parts = aidParam.split('-');
      if (parts.length === 1) {
        // 只有广告账户ID
        return {
          accountSourceId: parts[0],
          type: 'account',
        };
      } else if (parts.length === 2) {
        // 广告账户ID + campaign ID
        return {
          accountSourceId: parts[0],
          campaignSourceId: parts[1],
          type: 'campaign',
        };
      } else if (parts.length === 3) {
        // 广告账户ID + campaign ID + adset ID
        return {
          accountSourceId: parts[0],
          campaignSourceId: parts[1],
          adsetSourceId: parts[2],
          type: 'adset',
        };
      }

      // 默认返回只有账户ID的情况
      return {
        accountSourceId: parts[0],
        type: 'account',
      };
    };

    // 从URL加载广告账户及相关配置
    const loadAdAccountsFromUrl = () => {
      const accountIds = route.query.aid;
      if (accountIds) {
        loading.accountsLoading = true;

        // 处理aid参数，可能是单个字符串或数组
        const idParams: string[] = Array.isArray(accountIds) ? accountIds : [accountIds];

        // 收集所有需要查询的账户ID
        const uniqueAccountSourceIds = new Set<string>();
        idParams.forEach(param => {
          const parsed = parseAidParam(param.toString());
          uniqueAccountSourceIds.add(parsed.accountSourceId);
        });

        const params = {
          ad_account_ids: Array.from(uniqueAccountSourceIds),
          pageNo: 1,
          pageSize: uniqueAccountSourceIds.size,
          'with-campaign': true, // 添加获取campaign数据的参数
          is_archived: false,
        };

        queryFB_AD_AccountsApi(params)
          .then(response => {
            const accounts = response.data || [];
            adAccounts.value = accounts;

            // 处理配置块
            processConfigBlocks(accounts, idParams);
          })
          .catch(err => {
            console.error('Error loading ad accounts from URL:', err);
            message.error(t('Failed to load initial ad accounts'));
            adAccounts.value = [];
            configBlocks.value = [];
          })
          .finally(() => (loading.accountsLoading = false));
      } else {
        adAccounts.value = [];
        configBlocks.value = [];
      }
    };

    // 处理配置块数据
    const processConfigBlocks = (accounts: any[], idParams: string[]) => {
      const blocks: ConfigBlock[] = [];

      // 为每个aid参数创建配置块
      idParams.forEach(param => {
        const parsed = parseAidParam(param.toString());

        // 查找对应的账户
        const account = accounts.find(acc => acc.source_id === parsed.accountSourceId);
        if (!account) return;

        // 创建基础配置块
        const configBlock: ConfigBlock = {
          id: `block-${Date.now()}-${Math.random().toString(36).substring(2, 11)}`,
          adAccount: account,
          type: parsed.type as 'account' | 'campaign' | 'adset',
        };

        // 如果是campaign类型，找到对应的campaign
        if (parsed.type === 'campaign' && parsed.campaignSourceId) {
          const campaign = account.campaigns?.find(
            (c: any) => c.source_id === parsed.campaignSourceId,
          );

          if (campaign) {
            configBlock.campaign = campaign;
            configBlock.campaignId = campaign.id;
          }
        }

        // 如果是adset类型，找到对应的campaign和adset
        if (parsed.type === 'adset' && parsed.campaignSourceId && parsed.adsetSourceId) {
          const campaign = account.campaigns?.find(
            (c: any) => c.source_id === parsed.campaignSourceId,
          );

          if (campaign) {
            configBlock.campaign = campaign;
            configBlock.campaignId = campaign.id;

            const adset = campaign.adsets?.find((a: any) => a.source_id === parsed.adsetSourceId);

            if (adset) {
              configBlock.adset = adset;
              configBlock.adsetId = adset.id;
            }
          }
        }

        blocks.push(configBlock);
      });

      configBlocks.value = blocks;
      // 确保configBlocksData数组大小匹配
      configBlocksData = new Array(blocks.length);
    };

    // 处理配置块的同步和监控
    const syncConfigBlocks = () => {
      // 确保每个配置块的数据都是最新的
      configBlocks.value.forEach((block, index) => {
        // 如果configBlocksData中缺少某个index的数据，但configBlocks中有，则创建初始数据
        if (!configBlocksData[index] && block) {
          console.log(`初始化配置块${index}的数据`);
          configBlocksData[index] = {
            ad_account_id: block.adAccount.id,
            ad_setup: 'material', // 默认设置
            launch_mode: 3, // 默认设置
            // 复制campaign和adset信息
            campaignId: block.campaignId || null,
            adsetId: block.adsetId || null,
            campaign: block.campaign || null,
            adset: block.adset || null,
          };
        }
      });
    };

    // 复制配置块
    const copyConfigBlock = (index: number) => {
      if (index >= 0 && index < configBlocks.value.length) {
        // 获取原始配置块
        const originalBlock = configBlocks.value[index];
        // 获取原始数据
        const originalData = configBlocksData[index];

        console.log('开始复制配置块，原始块：', originalBlock);
        console.log('开始复制配置块，原始数据：', originalData);

        // 创建新的配置块ID
        const newBlockId = `block-${Date.now()}-${Math.random().toString(36).substring(2, 11)}`;

        // 深度复制配置块，确保不影响原始块
        const newBlock: ConfigBlock = JSON.parse(
          JSON.stringify({
            ...originalBlock,
            id: newBlockId,
          }),
        );

        console.log('创建新块：', newBlock);

        // 在原始块后面插入新块
        configBlocks.value.splice(index + 1, 0, newBlock);

        // 深度复制配置数据
        let newData = null;
        if (originalData) {
          // 如果有原始数据，复制它
          newData = JSON.parse(JSON.stringify(originalData));
          console.log('复制的数据：', newData);
        } else {
          // 如果没有原始数据，创建默认数据
          newData = {
            ad_account_id: originalBlock.adAccount.id,
            ad_setup: 'material',
            launch_mode: 3,
          };
          console.log('创建默认数据：', newData);
        }

        // 确保明确复制campaign和adset信息
        if (originalBlock.campaignId) {
          newData.campaignId = originalBlock.campaignId;
          console.log('从原始块复制campaignId:', originalBlock.campaignId);
        }

        if (originalBlock.adsetId) {
          newData.adsetId = originalBlock.adsetId;
          console.log('从原始块复制adsetId:', originalBlock.adsetId);
        }

        if (originalBlock.campaign) {
          newData.campaign = JSON.parse(JSON.stringify(originalBlock.campaign));
          console.log('从原始块复制campaign对象');
        }

        if (originalBlock.adset) {
          newData.adset = JSON.parse(JSON.stringify(originalBlock.adset));
          console.log('从原始块复制adset对象');
        }

        // 如果原始数据中也有campaign/adset信息，优先使用它们（可能更新）
        if (originalData?.campaignId) {
          newData.campaignId = originalData.campaignId;
          console.log('从原始数据复制campaignId:', originalData.campaignId);
        }

        if (originalData?.adsetId) {
          newData.adsetId = originalData.adsetId;
          console.log('从原始数据复制adsetId:', originalData.adsetId);
        }

        if (originalData?.campaign) {
          newData.campaign = JSON.parse(JSON.stringify(originalData.campaign));
          console.log('从原始数据复制campaign对象');
        }

        if (originalData?.adset) {
          newData.adset = JSON.parse(JSON.stringify(originalData.adset));
          console.log('从原始数据复制adset对象');
        }

        // 在数据数组中插入新数据
        const newConfigBlocksData = [...configBlocksData];
        newConfigBlocksData.splice(index + 1, 0, newData);
        configBlocksData = newConfigBlocksData;

        console.log('复制完成，新的configBlocksData:', configBlocksData);
        message.success(t('Configuration copied successfully'));

        // 使用nextTick确保DOM更新后再次同步
        nextTick(() => {
          // 强制重新渲染和同步
          syncConfigBlocks();
        });
      }
    };

    // 删除配置块
    const deleteConfigBlock = (index: number) => {
      if (configBlocks.value.length <= 1) {
        message.warning(t('Cannot delete the last configuration block'));
        return;
      }

      if (index >= 0 && index < configBlocks.value.length) {
        configBlocks.value.splice(index, 1);

        // 同时从数据数组中删除
        const newConfigBlocksData = [...configBlocksData];
        newConfigBlocksData.splice(index, 1);
        configBlocksData = newConfigBlocksData;
      }
    };

    // 将数据直接存储到普通数组，避免响应式系统重置
    const onDataChange = (data: any, index: number) => {
      console.log('new data', data);

      // 保存当前数据到configBlocksData
      configBlocksData[index] = data;
      console.log('configBlocksData', configBlocksData);

      // 同步更新configBlocks中的campaign和adset信息，确保标题能及时更新
      if (configBlocks.value[index]) {
        // 更新campaign信息
        if (data.campaign) {
          configBlocks.value[index].campaign = data.campaign;
          configBlocks.value[index].campaignId = data.campaignId || data.campaign.id;
          console.log('更新configBlocks campaign:', data.campaign.name);
        } else if (data.campaignId === null || data.campaignId === undefined) {
          // 如果campaign被清除
          configBlocks.value[index].campaign = null;
          configBlocks.value[index].campaignId = null;
          console.log('清除configBlocks campaign');
        }

        // 更新adset信息
        if (data.adset) {
          configBlocks.value[index].adset = data.adset;
          configBlocks.value[index].adsetId = data.adsetId || data.adset.id;
          console.log('更新configBlocks adset:', data.adset.name);
        } else if (data.adsetId === null || data.adsetId === undefined) {
          // 如果adset被清除
          configBlocks.value[index].adset = null;
          configBlocks.value[index].adsetId = null;
          console.log('清除configBlocks adset');
        }
      }
    };

    // 当配置块列表变化时，确保configBlocksData数组大小同步更新
    watch(
      () => configBlocks.value.length,
      newLength => {
        // 调整configBlocksData数组大小以匹配configBlocks
        if (configBlocksData.length !== newLength) {
          const newConfigBlocksData = [...configBlocksData];
          newConfigBlocksData.length = newLength;
          configBlocksData = newConfigBlocksData;
        }
      },
    );

    // 获取处理后的配置块，用于传递给审核页面
    const getProcessedConfigBlocks = () => {
      console.log('getProcessedConfigBlocks configBlocksData原始数据:', configBlocksData);

      return configBlocksData.filter(Boolean).map((data, index) => {
        const block = configBlocks.value[index];

        // 结合配置块信息和表单数据
        const processedData = {
          ...data,
          configBlock: block,
        };

        // 如果数据对象中没有campaign/adset对象，但configBlock中有，则添加到processedData中
        if (!processedData.campaign && block.campaign) {
          processedData.campaign = block.campaign;
        }

        if (!processedData.adset && block.adset) {
          processedData.adset = block.adset;
        }

        // 确保campaignId和adsetId被正确保留
        console.log(`配置块${index}的campaignId:`, processedData.campaignId);
        console.log(`配置块${index}的adsetId:`, processedData.adsetId);
        console.log(`配置块${index}的campaign对象:`, processedData.campaign);
        console.log(`配置块${index}的adset对象:`, processedData.adset);

        return processedData;
      });
    };

    const onBack = () => {
      if (currentStep.value > 0) {
        currentStep.value--;
      }
    };

    const onNext = async () => {
      if (currentStep.value === 0) {
        // Validate Step 1: Accounts selected and Template selected
        if (!adAccounts.value?.length) {
          message.warning(t('Please select at least one ad account'));
          return;
        }
        const formData = accountsRef.value?.getData(); // Use optional chaining
        if (!formData?.template) {
          message.warning(t('Please select a template'));
          return;
        }

        loading.templateLoading = true;
        try {
          const response = await getFbAdTemplate(formData.template);
          template.value = response.data || {};
          currentStep.value++;
        } catch (err) {
          console.error('Error loading template:', err);
          message.error(t('Failed to load template data'));
        } finally {
          loading.templateLoading = false;
        }
      } else if (currentStep.value === 1) {
        // Validate Step 2: All configuration blocks are valid
        const isValid = validateConfigBlocks();
        if (!isValid) {
          // Message already shown in validation function
          return;
        }
        currentStep.value++;
      } else if (currentStep.value === 2) {
        // Final Step: Launch Ads
        await launchAdsToFacebook();
      }
    };

    const validateConfigBlocks = (): boolean => {
      const processedConfigBlocks = getProcessedConfigBlocks();

      if (processedConfigBlocks.length !== configBlocks.value.length) {
        message.error('Configuration data mismatch. Please try again.');
        return false;
      }

      for (let i = 0; i < processedConfigBlocks.length; i++) {
        const configData = processedConfigBlocks[i];
        const block = configBlocks.value[i];
        const blockName = getConfigBlockTitle(block);

        if (!configData) {
          message.warning(`${t('Configuration missing for')}: ${blockName}`);
          return false;
        }

        // 检查ad_setup是否存在
        if (!configData.ad_setup) {
          message.warning(`${t('Ad setup not selected for')}: ${blockName}`);
          return false;
        }

        // Basic required fields
        if (!configData.operator || !configData.pixel || !configData.page) {
          message.warning(`${t('Missing Operator, Pixel, or Page for')}: ${blockName}`);
          return false;
        }

        // Material specific validation
        if (configData.ad_setup === 'material') {
          if (
            !configData.materials?.length ||
            !configData.links?.length ||
            !configData.copywriting?.length
          ) {
            message.warning(`${t('Missing Materials, Link, or Copywriting for')}: ${blockName}`);
            return false;
          }
        }
        // Post specific validation
        else if (configData.ad_setup === 'post') {
          if (!configData.post?.length) {
            message.warning(`${t('Missing Post ID(s) for')}: ${blockName}`);
            return false;
          }
        }
        // Catalog specific validation
        else if (configData.ad_setup === 'catalog') {
          if (!configData.productSets?.length) {
            message.warning(`${t('Missing Product Sets for')}: ${blockName}`);
            return false;
          }
          if (!configData.links?.length) {
            message.warning(`${t('Missing Link for')}: ${blockName}`);
            return false;
          }
        }
        // Form validation (if required by template)
        const templateRequiresForm =
          template.value.objective === 'OUTCOME_LEADS' &&
          template.value.conversion_location === 'INSTANT_FORMS';
        if (templateRequiresForm && !configData.form) {
          message.warning(`${t('Missing Form for')}: ${blockName}`);
          return false;
        }
      }
      return true; // All configurations seem valid
    };

    const launchAdsToFacebook = async () => {
      if (!validateConfigBlocks()) return; // Final validation before launch

      loading.submitLoading = true;

      const payload = getProcessedConfigBlocks().map(data => {
        console.log('处理广告配置:', data);
        const block = data.configBlock;
        console.log('配置块:', block);
        const options: any = {
          launch_mode: data.launch_mode ?? 3, // Default to N-1-1 if not set
          pixel_id: (data.pixel as any)?.value ?? data.pixel,
          page_id: (data.page as any)?.value ?? data.page,
        };

        // 增强campaign和adset信息传递
        // 1. 优先使用data中的campaignId/adsetId
        // 2. 如果data中没有，则使用block中的campaignId/adsetId
        // 3. 如果block中也没有，但有campaign/adset对象，则尝试从对象中获取id

        // 处理campaign_id
        if (data.campaignId) {
          options.campaign_id = data.campaignId;
          console.log('使用data中的campaignId:', data.campaignId);
        } else if (block.campaignId) {
          options.campaign_id = block.campaignId;
          console.log('使用block中的campaignId:', block.campaignId);
        } else if (data.campaign?.id) {
          options.campaign_id = data.campaign.id;
          console.log('从data.campaign对象中提取campaignId:', data.campaign.id);
        } else if (block.campaign?.id) {
          options.campaign_id = block.campaign.id;
          console.log('从block.campaign对象中提取campaignId:', block.campaign.id);
        }

        // 处理adset_id
        if (data.adsetId) {
          options.adset_id = data.adsetId;
          console.log('使用data中的adsetId:', data.adsetId);
        } else if (block.adsetId) {
          options.adset_id = block.adsetId;
          console.log('使用block中的adsetId:', block.adsetId);
        } else if (data.adset?.id) {
          options.adset_id = data.adset.id;
          console.log('从data.adset对象中提取adsetId:', data.adset.id);
        } else if (block.adset?.id) {
          options.adset_id = block.adset.id;
          console.log('从block.adset对象中提取adsetId:', block.adset.id);
        }

        // 输出最终的campaign_id和adset_id以及来源
        console.log(
          '最终设置的campaign_id:',
          options.campaign_id,
          options.campaign_id
            ? `(来源: ${
                data.campaignId
                  ? 'data.campaignId'
                  : block.campaignId
                  ? 'block.campaignId'
                  : data.campaign?.id
                  ? 'data.campaign.id'
                  : 'block.campaign.id'
              })`
            : '',
        );

        console.log(
          '最终设置的adset_id:',
          options.adset_id,
          options.adset_id
            ? `(来源: ${
                data.adsetId
                  ? 'data.adsetId'
                  : block.adsetId
                  ? 'block.adsetId'
                  : data.adset?.id
                  ? 'data.adset.id'
                  : 'block.adset.id'
              })`
            : '',
        );

        // 确保API调用包含有效的campaign_id
        if (options.campaign_id) {
          console.log('向API发送campaign_id:', options.campaign_id);
        }

        // 确保API调用包含有效的adset_id
        if (options.adset_id) {
          console.log('向API发送adset_id:', options.adset_id);
        }

        // 只有表单存在时才添加form_id字段
        if (data.form) {
          options.form_id = (data.form as any)?.value ?? data.form;
        }

        if (data.ad_setup === 'material') {
          // 确保materials是数组并且有内容
          if (Array.isArray(data.materials) && data.materials.length > 0) {
            options.material_id_list = data.materials.map((m: any) => m?.id).filter(Boolean);
          } else {
            console.warn('材料列表为空或无效:', data.materials);
            options.material_id_list = [];
          }

          // 确保links是数组并且有内容
          if (Array.isArray(data.links) && data.links.length > 0 && data.links[0]?.id) {
            options.link_id = data.links[0]?.id;
          } else {
            console.warn('链接列表为空或无效:', data.links);
          }

          // 确保copywriting是数组并且有内容
          if (
            Array.isArray(data.copywriting) &&
            data.copywriting.length > 0 &&
            data.copywriting[0]?.id
          ) {
            options.copywriting_id = data.copywriting[0]?.id;
          } else {
            console.warn('文案列表为空或无效:', data.copywriting);
          }
        } else if (data.ad_setup === 'post') {
          // 确保post是有效值
          if (data.post && data.post.length > 0) {
            options.post_id_list = data.post; // Send array of post IDs
          } else {
            console.warn('帖子列表为空或无效:', data.post);
            options.post_id_list = [];
          }
        } else if (data.ad_setup === 'catalog') {
          // 使用所有选择的product sets
          if (data.productSets?.length > 0) {
            options.product_set_ids = data.productSets; // 传递所有product set ids

            // 如果有productSetDetails，也可以将名称信息记录到日志中，方便调试
            if (data.productSetDetails?.length > 0) {
              console.log('Product Set Details:', data.productSetDetails);
            }
          } else {
            console.warn('产品集列表为空或无效:', data.productSets);
            options.product_set_ids = [];
          }

          // Catalog类型也需要处理link_id
          if (Array.isArray(data.links) && data.links.length > 0 && data.links[0]?.id) {
            options.link_id = data.links[0]?.id;
          } else {
            console.warn('链接列表为空或无效:', data.links);
          }
        }

        return {
          fb_ad_account_id: data.ad_account_id || block.adAccount.id,
          fb_ad_template_id: template.value.id,
          operator_type: data.operator_type === 'fb' ? 'facebook-user' : 'bm-user',
          operator_id: (data.operator as any)?.value ?? data.operator, // Use selected operator ID
          options: options,
        };
      });

      try {
        // 记录请求信息到控制台(用于调试)
        console.log('API Request Payload:', JSON.stringify(payload, null, 2));

        // 调用实际API
        const response = await launchAds(payload);
        console.log('API Response:', response);

        message.success(t('Ads submitted!'));

        // 可以添加跳转到广告管理页面
        // router.push('/ads/management');
      } catch (error: any) {
        console.error('Failed to launch ads:', error);
        message.error(t('Failed to launch ads: ') + (error.message || t('Unknown error')));
      } finally {
        loading.submitLoading = false;
      }
    };

    // --- Lifecycle Hooks ---
    onMounted(() => {
      loadAdAccountsFromUrl();
      // 初始化后尝试同步配置块数据
      nextTick(() => {
        syncConfigBlocks();
      });
    });

    return {
      t,
      currentStep,
      adAccounts,
      accountsRef,
      template,
      loading,
      configBlocks,
      getActionButtonText,
      onBack,
      onNext,
      onDataChange,
      getProcessedConfigBlocks,
      copyConfigBlock,
      deleteConfigBlock,
      getConfigBlockTitle,
    };
  },
});
</script>

<style lang="less" scoped>
.content {
  margin: 16px 0;
}

.steps-header {
  cursor: pointer;
  .ant-steps-item-active
    > .ant-steps-item-container
    > .ant-steps-item-content
    > .ant-steps-item-title {
    font-weight: 600; // Make active step title bolder
  }
}

.step-hidden {
  display: none; // 添加缺失的CSS定义来隐藏非当前步骤
}

.config-block {
  border: 1px solid #f0f0f0;
  border-radius: 4px;
  padding: 16px;
  background-color: #fafafa;
  transition: all 0.3s;

  &:hover {
    box-shadow: 0 2px 8px rgba(0, 0, 0, 0.09);
  }

  .config-block-header {
    margin-bottom: 16px;
    border-bottom: 1px solid #f0f0f0;
    padding-bottom: 12px;

    h3 {
      margin: 0;
      font-size: 16px;
      font-weight: 500;
    }
  }
}
</style>
