<template>
  <!-- 本页以 src/views 为唯一维护源；路由通过 @/views/ads/meta-ad-creation 加载 -->
  <page-container :show-page-header="false" title="Meta广告创建">
    <div class="meta-ad-creation">
      <!-- 左侧步骤导航（9 步，与图示一致） -->
      <div class="steps-sidebar" v-show="entryMode !== null">
        <div
          v-for="(step, idx) in stepList"
          :key="idx"
          class="step-item"
          :class="{ active: currentStepIndex === idx }"
          role="button"
          tabindex="0"
          @click="goToStep(idx)"
          @keydown.enter.prevent="goToStep(idx)"
          @keydown.space.prevent="goToStep(idx)"
        >
          <span class="step-dot" />
          <span class="step-label">{{ step }}</span>
        </div>
      </div>

      <!-- 主内容区域 -->
      <div class="main-content">
        <!-- 入口选择：选择模版 / 复制历史任务 / 新创 -->
        <a-card v-show="entryMode === null" class="entry-choice-card">
          <h3 class="section-title">{{ t('新建广告') }}</h3>
          <div class="entry-choice-row">
            <a-button type="primary" size="large" @click="openTemplateModal">
              <template #icon><file-text-outlined /></template>
              {{ t('选择模版') }}
            </a-button>
            <a-button size="large" @click="openCopyHistoryModal">
              <template #icon><copy-outlined /></template>
              {{ t('复制历史任务') }}
            </a-button>
            <a-button size="large" @click="entryMode = 'new'">
              <template #icon><plus-outlined /></template>
              {{ t('新创') }}
            </a-button>
          </div>
          <p class="entry-hint">{{ t('可从模板中选择（如蓉城先锋）、复制历史任务或从零开始创建广告。') }}</p>
        </a-card>

        <a-card v-show="entryMode !== null" class="meta-ad-flow-card">
          <!-- 0 基础设置（v-if 只挂载当前步，避免全隐藏时 Card 塌成空白） -->
          <div v-if="currentStepIndex === 0" class="step-content">
            <step-one-account
              :form-data="formData.stepOne"
              :template-prefill="selectedTemplate"
              @update:form-data="formData.stepOne = { ...formData.stepOne, ...$event }"
              @sub-step-change="activeSubStep = $event"
            />
          </div>

          <!-- 1 广告系列 -->
          <div v-if="currentStepIndex === 1" class="step-content">
            <step-two-campaign
              :form-data="formData.stepTwo"
              @update:form-data="Object.assign(formData.stepTwo, $event)"
            />
          </div>

          <!-- 2 投放内容 -->
          <div v-if="currentStepIndex === 2" class="step-content">
            <step-delivery
              :form-data="formData.stepDelivery"
              :ad-account-id="formData.stepOne.adAccount"
              :conversion-location="formData.stepOne.conversionLocation"
              @update:form-data="formData.stepDelivery = $event"
            />
          </div>

          <!-- 3 地区组 -->
          <div v-if="currentStepIndex === 3" class="step-content">
            <step-region
              :ad-account-id="formData.stepOne.adAccount"
              :form-data="formData.stepRegion"
              @update:form-data="formData.stepRegion = $event"
            />
          </div>

          <!-- 4 版位 -->
          <div v-if="currentStepIndex === 4" class="step-content">
            <step-placement :form-data="formData.stepPlacement" @update:form-data="formData.stepPlacement = $event" />
          </div>

          <!-- 5 定向包 -->
          <div v-if="currentStepIndex === 5" class="step-content">
            <step-targeting
              :form-data="formData.stepTargeting"
              :step-region-defaults="formData.stepRegion"
              :ad-account-id="formData.stepOne.adAccount"
              @update:form-data="formData.stepTargeting = $event"
            />
          </div>

          <!-- 6 出价和预算 -->
          <div v-if="currentStepIndex === 6" class="step-content">
            <step-bid-budget
              :form-data="formData.stepBidBudget"
              :conversion-location="formData.stepOne.conversionLocation"
              :objective="formData.stepOne.objective"
              @update:form-data="formData.stepBidBudget = $event"
            />
          </div>

          <!-- 7 创意设置 -->
          <div v-if="currentStepIndex === 7" class="step-content">
            <step-creative-settings
              :form-data="formData.stepCreativeSettings"
              :ad-account-id="formData.stepOne.adAccount"
              :operator-id="formData.stepOne.operator"
              :conversion-location="formData.stepOne.conversionLocation"
              :objective="formData.stepOne.objective"
              :pixel-from-step-one="formData.stepOne.pixel"
              :creative-group="formData.stepCreativeGroup"
              @update:form-data="formData.stepCreativeSettings = $event"
              @go-creative-group="goToStep(8)"
            />
          </div>

          <!-- 8 创意组 -->
          <div v-if="currentStepIndex === 8" class="step-content">
            <step-creative-group
              :form-data="formData.stepCreativeGroup"
              :step-targeting="formData.stepTargeting"
              @update:form-data="formData.stepCreativeGroup = $event"
            />
          </div>
        </a-card>

        <!-- 底部操作按钮（与图示一致） -->
        <div class="footer-actions" v-show="entryMode !== null">
          <div class="footer-step-nav">
            <a-button :disabled="currentStepIndex <= 0" @click="goToStep(currentStepIndex - 1)">
              {{ t('上一步') }}
            </a-button>
            <a-button :disabled="currentStepIndex >= 8" @click="goToStep(currentStepIndex + 1)">
              {{ t('下一步') }}
            </a-button>
          </div>
          <div class="footer-actions-right">
            <a-button @click="onReset">{{ t('重置') }}</a-button>
            <a-button @click="onSaveDraft">{{ t('保存草稿并退出') }}</a-button>
            <a-button :loading="loading" @click="onPreview">
              {{ t('预览') }}
            </a-button>
            <a-button @click="onSaveTemplate">{{ t('保存模板') }}</a-button>
            <a-button type="primary" :loading="submitLoading" @click="onSubmitLaunch">
              {{ t('提交投放') }}
            </a-button>
          </div>
        </div>
      </div>

      <!-- 右侧 Meta 摘要（与图示一致） -->
      <div class="config-summary meta-panel" v-show="entryMode !== null">
        <a-card size="small">
          <template #title>
            <span>Meta</span>
            <a-button type="link" size="small" class="edit-btn" @click="openSplitRulesModal">{{ t('编辑') }}</a-button>
          </template>
          <div class="meta-account-count">{{ t('已选账户数量') }}: {{ selectedAccountCount }}</div>
          <a-table
            :data-source="metaSummaryData"
            :columns="metaSummaryColumns"
            :pagination="false"
            size="small"
            row-key="attr"
          />
        </a-card>
      </div>
    </div>

    <!-- 使用模版弹窗 -->
    <template-select-modal
      :visible="templateModalVisible"
      @cancel="templateModalVisible = false"
      @confirm="onTemplateConfirm"
    />
    <a-modal
      :open="copyHistoryModalVisible"
      :title="t('复制历史任务')"
      :width="900"
      :footer="null"
      @cancel="copyHistoryModalVisible = false"
    >
      <a-table
        :columns="copyColumns"
        :data-source="copyHistoryData"
        :loading="copyHistoryLoading"
        :pagination="false"
        :row-selection="{ type: 'radio', selectedRowKeys: copySelectedKeys, onChange: onCopySelectChange }"
        row-key="id"
      />
      <div class="copy-history-footer">
        <a-button @click="copyHistoryModalVisible = false">{{ t('取消') }}</a-button>
        <a-button type="primary" :disabled="!copySelectedRecord" @click="onCopyConfirm">{{ t('确定') }}</a-button>
      </div>
    </a-modal>

    <a-modal
      v-model:open="splitRulesModalVisible"
      class="split-rules-modal"
      :title="t('编辑拆分规则')"
      :width="760"
      :ok-text="t('确定')"
      :cancel-text="t('取消')"
      destroy-on-close
      :centered="true"
      @ok="onSplitRulesModalOk"
    >
      <p class="split-rules-intro">
        {{
          t(
            '全组合：各维度数量按叉乘生成结构；自由绑定：仅使用下方「定向包↔出价」配对，不再对定向×出价全叉乘。名称模板用于后续创建 Campaign/AdSet/Ad 时替换占位符。',
          )
        }}
      </p>
      <a-alert type="info" show-icon class="split-rules-preview-alert">
        <template #message>
          <span class="split-preview-line">
            {{ t('账户') }}≈{{ splitRulesEstimate.nAcc }} × {{ t('地区组') }}≈{{ splitRulesEstimate.nReg }} ×
            <template v-if="splitRulesEstimate.mode === 'free_binding'">
              {{ t('定向↔出价配对') }}={{ splitRulesEstimate.pairCount }}
            </template>
            <template v-else> {{ t('定向包') }}={{ splitRulesEstimate.nTp }} × {{ t('出价') }}={{ splitRulesEstimate.nBb }} </template>
            × {{ t('创意组') }}={{ splitRulesEstimate.nCg }}
          </span>
          <span class="split-preview-est">
            → {{ t('预估叶子广告量级') }} ≈ <strong>{{ splitRulesEstimate.estimated }}</strong>
            （{{ t('实际以投放任务为准') }}）
          </span>
        </template>
      </a-alert>
      <div class="split-rules-body">
        <div class="split-rule-row">
          <div class="split-rule-label">{{ t('生成模式') }}</div>
          <div class="split-rule-control">
            <a-radio-group v-model:value="splitRulesDraft.generationMode" button-style="outline" size="default" @change="onGenerationModeChange">
              <a-radio-button value="full">{{ t('全组合') }}</a-radio-button>
              <a-radio-button value="free_binding">{{ t('自由绑定') }}</a-radio-button>
            </a-radio-group>
          </div>
        </div>
        <div v-if="splitRulesDraft.generationMode === 'free_binding'" class="split-rules-free-binding">
          <div class="split-free-binding-head">
            <span>{{ t('定向包与出价包配对（MVP：逐行指定下标）') }}</span>
            <a-button type="dashed" size="small" html-type="button" @click="addFreeBindingPair">{{ t('添加一行') }}</a-button>
          </div>
          <table class="split-free-table" v-if="splitRulesDraft.freeBindingPairs.length">
            <thead>
              <tr>
                <th>{{ t('定向包序号') }}</th>
                <th>{{ t('出价包序号') }}</th>
                <th class="split-free-col-op">{{ t('操作') }}</th>
              </tr>
            </thead>
            <tbody>
              <tr v-for="(row, index) in splitRulesDraft.freeBindingPairs" :key="'fb-' + index">
                <td>
                  <a-select v-model:value="row.targetingIndex" style="width: 100%" :options="targetingIndexOptions" />
                </td>
                <td>
                  <a-select v-model:value="row.bidIndex" style="width: 100%" :options="bidIndexOptions" />
                </td>
                <td class="split-free-col-op">
                  <a-button type="link" danger size="small" html-type="button" @click="removeFreeBindingPair(index)">{{ t('删除') }}</a-button>
                </td>
              </tr>
            </tbody>
          </table>
          <p v-else class="split-free-binding-empty">{{ t('暂无配对，请点击「添加一行」') }}</p>
          <p class="split-free-binding-hint">{{ t('条数较少时可用 0、1、2… 与定向包/出价列表顺序对应；全组合模式下忽略此表。') }}</p>
        </div>
        <div class="split-rules-section-title">{{ t('拆分层级') }}</div>
        <div class="split-rule-row">
          <div class="split-rule-label">{{ t('地区组') }}</div>
          <div class="split-rule-control">
            <a-radio-group v-model:value="splitRulesDraft.regionGroupLevel" button-style="outline" size="default">
              <a-radio-button value="campaign">{{ t('广告系列') }}</a-radio-button>
              <a-radio-button value="adset">{{ t('广告组') }}</a-radio-button>
            </a-radio-group>
          </div>
        </div>
        <div class="split-rule-row">
          <div class="split-rule-label">{{ t('定向包') }}</div>
          <div class="split-rule-control">
            <a-radio-group v-model:value="splitRulesDraft.targetingLevel" button-style="outline" size="default">
              <a-radio-button value="campaign">{{ t('广告系列') }}</a-radio-button>
              <a-radio-button value="adset">{{ t('广告组') }}</a-radio-button>
            </a-radio-group>
          </div>
        </div>
        <div class="split-rule-row">
          <div class="split-rule-label">{{ t('出价和预算') }}</div>
          <div class="split-rule-control">
            <a-radio-group v-model:value="splitRulesDraft.bidBudgetLevel" button-style="outline" size="default">
              <a-radio-button value="campaign">{{ t('广告系列') }}</a-radio-button>
              <a-radio-button value="adset">{{ t('广告组') }}</a-radio-button>
            </a-radio-group>
          </div>
        </div>
        <div class="split-rule-row">
          <div class="split-rule-label">{{ t('创意组') }}</div>
          <div class="split-rule-control">
            <a-radio-group v-model:value="splitRulesDraft.creativeGroupLevel" button-style="outline" size="default">
              <a-radio-button value="campaign">{{ t('广告系列') }}</a-radio-button>
              <a-radio-button value="adset">{{ t('广告组') }}</a-radio-button>
              <a-radio-button value="ad">{{ t('广告') }}</a-radio-button>
            </a-radio-group>
          </div>
        </div>
        <div class="split-rules-section-title">{{ t('名称规则') }}</div>
        <div class="split-rule-row split-rule-row--block">
          <div class="split-rule-label">{{ t('广告系列名称') }}</div>
          <div class="split-rule-control split-rule-control--stack">
            <a-input v-model:value="splitRulesDraft.campaignNameTemplate" :placeholder="t('例：{campaignName} | {accountName}')" allow-clear />
            <div class="name-token-row">
              <span class="name-token-label">{{ t('插入') }}</span>
              <a-button
                v-for="tok in nameTemplateTokens"
                :key="tok"
                type="link"
                size="small"
                html-type="button"
                @click="appendSplitNameToken('campaignNameTemplate', tok)"
                >{{ tok }}</a-button
              >
            </div>
          </div>
        </div>
        <div class="split-rule-row split-rule-row--block">
          <div class="split-rule-label">{{ t('广告组名称') }}</div>
          <div class="split-rule-control split-rule-control--stack">
            <a-input v-model:value="splitRulesDraft.adsetNameTemplate" :placeholder="t('例：{targetingName} | {bidName}')" allow-clear />
            <div class="name-token-row">
              <span class="name-token-label">{{ t('插入') }}</span>
              <a-button
                v-for="tok in nameTemplateTokens"
                :key="'a-' + tok"
                type="link"
                size="small"
                html-type="button"
                @click="appendSplitNameToken('adsetNameTemplate', tok)"
                >{{ tok }}</a-button
              >
            </div>
          </div>
        </div>
        <div class="split-rule-row split-rule-row--block">
          <div class="split-rule-label">{{ t('广告名称') }}</div>
          <div class="split-rule-control split-rule-control--stack">
            <a-input v-model:value="splitRulesDraft.adNameTemplate" :placeholder="t('例：{creativeGroupName}')" allow-clear />
            <div class="name-token-row">
              <span class="name-token-label">{{ t('插入') }}</span>
              <a-button
                v-for="tok in nameTemplateTokens"
                :key="'b-' + tok"
                type="link"
                size="small"
                html-type="button"
                @click="appendSplitNameToken('adNameTemplate', tok)"
                >{{ tok }}</a-button
              >
            </div>
          </div>
        </div>
        <div class="split-rule-row">
          <div class="split-rule-label">{{ t('广告分批创建') }}</div>
          <div class="split-rule-control">
            <a-radio-group v-model:value="splitRulesDraft.batchAdCreation" button-style="outline" size="default">
              <a-radio-button :value="false">{{ t('关闭') }}</a-radio-button>
              <a-radio-button :value="true">{{ t('开启') }}</a-radio-button>
            </a-radio-group>
          </div>
        </div>
        <div v-if="splitRulesDraft.batchAdCreation" class="split-rules-batch-block">
          <div class="split-rule-row">
            <div class="split-rule-label">{{ t('每批提交最大广告数量') }}</div>
            <div class="split-rule-control">
              <a-input-number
                v-model:value="splitRulesDraft.batchMaxAdsPerBatch"
                :min="1"
                :max="9999"
                :precision="0"
                class="split-rule-input-num"
              />
            </div>
          </div>
          <div class="split-rule-row">
            <div class="split-rule-label">{{ t('每批时间间隔') }}</div>
            <div class="split-rule-control split-rule-interval">
              <a-input-number
                v-model:value="splitRulesDraft.batchIntervalMinSec"
                :min="0"
                :max="86400"
                :precision="0"
                class="split-rule-input-num split-rule-input-num--interval"
              />
              <span class="split-rule-unit">{{ t('秒') }}</span>
              <span class="split-rule-sep">~</span>
              <a-input-number
                v-model:value="splitRulesDraft.batchIntervalMaxSec"
                :min="0"
                :max="86400"
                :precision="0"
                class="split-rule-input-num split-rule-input-num--interval"
              />
              <span class="split-rule-unit">{{ t('秒') }}</span>
            </div>
          </div>
        </div>
      </div>
    </a-modal>
  </page-container>
</template>

<script lang="ts" setup>
import { ref, computed, reactive, toRaw } from 'vue';
import { useI18n } from 'vue-i18n';
import { useRouter } from 'vue-router';
import { message } from 'ant-design-vue';
import { FileTextOutlined, PlusOutlined, CopyOutlined } from '@ant-design/icons-vue';
import PageContainer from '@/components/base-layouts/page-container/index.vue';
import StepOneAccount from './step-one-account.vue';
import TemplateSelectModal from './template-select-modal.vue';
import { createMetaAdCreationTemplateApi, getMetaAdCreationTemplateApi } from '@/api/meta_ad_creation_templates';
import {
  createMetaAdCreationDraftApi,
  queryMetaAdCreationDraftsApi,
  updateMetaAdCreationDraftApi,
} from '@/api/meta_ad_creation_drafts';
import { createMetaAdCreationRecordApi, queryMetaAdCreationRecordsApi } from '@/api/meta_ad_creation_records';
import StepTwoCampaign from './step-two-campaign.vue';
import StepDelivery from './step-delivery.vue';
import StepRegion from './step-region.vue';
import StepPlacement from './step-placement.vue';
import StepTargeting from './step-targeting.vue';
import StepBidBudget from './step-bid-budget.vue';
import { applyBidBudgetLinkage, validateBidBudgetPackage } from './bid-budget-rules';
import StepCreativeSettings from './step-creative-settings.vue';
import StepCreativeGroup from './step-creative-group.vue';

const { t } = useI18n();
const router = useRouter();

const stepList = [
  t('基础设置'),
  t('广告系列'),
  t('投放内容'),
  t('地区组'),
  t('版位'),
  t('定向包'),
  t('出价和预算'),
  t('创意设置'),
  t('创意组'),
];

/** 入口选择：null=未选，'template'=从模板，'new'=新创 */
const entryMode = ref<'template' | 'new' | null>(null);
const templateModalVisible = ref(false);
const selectedTemplate = ref<any>(null);
const currentDraftId = ref<string | null>(null);
const currentTemplateId = ref<string | null>(null);

const currentStep = ref(0);
/** 草稿/接口可能回传字符串步骤，统一为数字，避免 v-if 与侧边高亮不一致导致整块表单不渲染 */
const currentStepIndex = computed(() => {
  const n = Number(currentStep.value);
  if (!Number.isFinite(n)) return 0;
  const i = Math.trunc(n);
  if (i < 0) return 0;
  if (i > 8) return 8;
  return i;
});
const activeSubStep = ref('goal');
const loading = ref(false);
const submitLoading = ref(false);
const selectedAccountCount = ref(1);
const copyHistoryModalVisible = ref(false);
const splitRulesModalVisible = ref(false);

type FreeBindingPair = { targetingIndex: number; bidIndex: number };

function defaultSplitRules() {
  return {
    /** 全组合 | 自由绑定（定向↔出价配对，不全叉乘） */
    generationMode: 'full' as 'full' | 'free_binding',
    freeBindingPairs: [] as FreeBindingPair[],
    /** 创建实体时的名称模板（占位符由后端/任务替换） */
    campaignNameTemplate: '{campaignName}',
    adsetNameTemplate: '{targetingName} | {bidName}',
    adNameTemplate: '{creativeGroupName}',
    /** 地区组拆分到的层级 */
    regionGroupLevel: 'adset' as 'campaign' | 'adset',
    targetingLevel: 'adset' as 'campaign' | 'adset',
    bidBudgetLevel: 'adset' as 'campaign' | 'adset',
    creativeGroupLevel: 'adset' as 'campaign' | 'adset' | 'ad',
    /** 广告分批创建（投放侧扩展，写入 form_data） */
    batchAdCreation: false,
    /** 每批最大广告数（开启分批时有效） */
    batchMaxAdsPerBatch: 5,
    /** 每批时间间隔区间（秒） */
    batchIntervalMinSec: 60,
    batchIntervalMaxSec: 120,
  };
}

function parsePositiveInt(v: unknown, fallback: number): number {
  const n = typeof v === 'number' ? v : parseInt(String(v ?? ''), 10);
  if (!Number.isFinite(n) || n < 0) return fallback;
  return Math.floor(n);
}

function mergeSplitRules(incoming: unknown) {
  const def = defaultSplitRules();
  if (!incoming || typeof incoming !== 'object') return def;
  const r = incoming as Record<string, unknown>;
  const cg = r.creativeGroupLevel;
  const creativeGroupLevel =
    cg === 'campaign' || cg === 'adset' || cg === 'ad' ? cg : def.creativeGroupLevel;
  const batchMax = parsePositiveInt(r.batchMaxAdsPerBatch, def.batchMaxAdsPerBatch);
  const minSec = parsePositiveInt(r.batchIntervalMinSec, def.batchIntervalMinSec);
  const maxSec = parsePositiveInt(r.batchIntervalMaxSec, def.batchIntervalMaxSec);
  let pairs: FreeBindingPair[] = def.freeBindingPairs;
  const rawPairs = r.freeBindingPairs ?? r.free_binding_pairs;
  if (Array.isArray(rawPairs)) {
    pairs = rawPairs
      .map((row: unknown) => {
        const x = row as Record<string, unknown>;
        return {
          targetingIndex: Math.max(0, parsePositiveInt(x.targetingIndex ?? x.targeting_index, 0)),
          bidIndex: Math.max(0, parsePositiveInt(x.bidIndex ?? x.bid_index, 0)),
        };
      })
      .filter((p) => Number.isFinite(p.targetingIndex) && Number.isFinite(p.bidIndex));
  }
  const genRaw = r.generationMode ?? r.generation_mode;
  return {
    generationMode: genRaw === 'free_binding' ? 'free_binding' : 'full',
    freeBindingPairs: pairs,
    campaignNameTemplate: String(r.campaignNameTemplate ?? r.campaign_name_template ?? def.campaignNameTemplate),
    adsetNameTemplate: String(r.adsetNameTemplate ?? r.adset_name_template ?? def.adsetNameTemplate),
    adNameTemplate: String(r.adNameTemplate ?? r.ad_name_template ?? def.adNameTemplate),
    regionGroupLevel: r.regionGroupLevel === 'campaign' ? 'campaign' : 'adset',
    targetingLevel: r.targetingLevel === 'campaign' ? 'campaign' : 'adset',
    bidBudgetLevel: r.bidBudgetLevel === 'campaign' ? 'campaign' : 'adset',
    creativeGroupLevel,
    batchAdCreation: r.batchAdCreation === true,
    batchMaxAdsPerBatch: Math.max(1, batchMax || def.batchMaxAdsPerBatch),
    batchIntervalMinSec: minSec,
    batchIntervalMaxSec: Math.max(minSec, maxSec),
  };
}

const splitRulesDraft = reactive(defaultSplitRules());

function appendSplitNameToken(field: 'campaignNameTemplate' | 'adsetNameTemplate' | 'adNameTemplate', token: string) {
  const cur = String((splitRulesDraft as Record<string, unknown>)[field] ?? '');
  (splitRulesDraft as Record<string, unknown>)[field] = cur + token;
}

function onGenerationModeChange() {
  if (splitRulesDraft.generationMode !== 'free_binding') {
    return;
  }
  const pkgs = formData.stepTargeting?.packages;
  const bids = formData.stepBidBudget?.packages;
  const nTp = Array.isArray(pkgs) && pkgs.length ? pkgs.length : 1;
  const nBb = Array.isArray(bids) && bids.length ? bids.length : 1;
  const m = Math.max(1, Math.min(nTp, nBb));
  if (!splitRulesDraft.freeBindingPairs.length) {
    splitRulesDraft.freeBindingPairs = Array.from({ length: m }, (_, i) => ({
      targetingIndex: Math.min(i, nTp - 1),
      bidIndex: Math.min(i, nBb - 1),
    }));
  }
}

function addFreeBindingPair() {
  splitRulesDraft.freeBindingPairs.push({ targetingIndex: 0, bidIndex: 0 });
}

function removeFreeBindingPair(index: number) {
  if (splitRulesDraft.freeBindingPairs.length <= 1) {
    message.warning(t('至少保留一行配对'));
    return;
  }
  splitRulesDraft.freeBindingPairs.splice(index, 1);
}

function openSplitRulesModal() {
  Object.assign(splitRulesDraft, mergeSplitRules(formData.splitRules));
  if (splitRulesDraft.generationMode === 'free_binding') {
    onGenerationModeChange();
  }
  splitRulesModalVisible.value = true;
}

function onSplitRulesModalOk() {
  const d = toRaw(splitRulesDraft) as Record<string, unknown>;
  if (d.generationMode === 'free_binding') {
    const pairs = d.freeBindingPairs as FreeBindingPair[] | undefined;
    if (!Array.isArray(pairs) || pairs.length === 0) {
      message.warning(t('自由绑定至少保留一行配对'));
      return Promise.reject();
    }
    const nTp = targetingPackageCount();
    const nBb = bidBudgetPackageCount();
    for (const row of pairs) {
      if (
        row.targetingIndex < 0 ||
        row.targetingIndex >= nTp ||
        row.bidIndex < 0 ||
        row.bidIndex >= nBb
      ) {
        message.warning(t('配对序号超出定向包或出价包范围'));
        return Promise.reject();
      }
    }
  }
  if (d.batchAdCreation) {
    const lo = Number(d.batchIntervalMinSec) || 0;
    const hi = Number(d.batchIntervalMaxSec) || 0;
    if (lo > hi) {
      message.warning(t('每批时间间隔：最小值不能大于最大值'));
      return Promise.reject();
    }
    const n = Number(d.batchMaxAdsPerBatch);
    if (!Number.isFinite(n) || n < 1) {
      message.warning(t('请填写有效的每批最大广告数量'));
      return Promise.reject();
    }
  }
  Object.assign(formData.splitRules, { ...d });
  splitRulesModalVisible.value = false;
  message.success(t('拆分规则已保存'));
}

function goToStep(idx: number) {
  const n = Number(idx);
  if (!Number.isFinite(n)) return;
  const i = Math.trunc(n);
  if (i >= 0 && i <= 8) {
    currentStep.value = i;
  }
}
const copyHistoryLoading = ref(false);
const copyHistoryData = ref<any[]>([]);
const copySelectedKeys = ref<string[]>([]);
const copySelectedRecord = ref<any>(null);

function openTemplateModal() {
  templateModalVisible.value = true;
}

function openCopyHistoryModal() {
  copyHistoryModalVisible.value = true;
  loadCopyHistory();
}

const copyColumns = [
  { title: t('类型'), dataIndex: 'type', key: 'type', width: 90 },
  { title: t('名称'), dataIndex: 'name', key: 'name' },
  { title: t('创建时间'), dataIndex: 'createdAtText', key: 'createdAtText', width: 180 },
];

function onCopySelectChange(keys: string[], rows: any[]) {
  copySelectedKeys.value = keys;
  copySelectedRecord.value = rows[0] ?? null;
}

async function loadCopyHistory() {
  copyHistoryLoading.value = true;
  copySelectedKeys.value = [];
  copySelectedRecord.value = null;
  try {
    const [recordsRes, draftsRes] = await Promise.all([
      queryMetaAdCreationRecordsApi(),
      queryMetaAdCreationDraftsApi(),
    ]);
    const records = ((recordsRes as any)?.data ?? []).map((item: any) => ({
      id: `record-${item.id}`,
      sourceId: item.id,
      type: t('历史记录'),
      name: item?.formDataSnapshot?.stepTwo?.campaignName || '-',
      createdAtText: item.createdAtText || item.createdAt || '-',
      formData: item.formDataSnapshot || {},
      from: 'record',
    }));
    const drafts = ((draftsRes as any)?.data ?? []).map((item: any) => ({
      id: `draft-${item.id}`,
      sourceId: item.id,
      type: t('草稿'),
      name: item.name || item?.formData?.stepTwo?.campaignName || '-',
      createdAtText: item.updatedAtText || item.updatedAt || '-',
      formData: item.formData || {},
      from: 'draft',
    }));
    copyHistoryData.value = [...drafts, ...records];
  } catch {
    copyHistoryData.value = [];
  } finally {
    copyHistoryLoading.value = false;
  }
}

function onCopyConfirm() {
  if (!copySelectedRecord.value) return;
  const nextData = copySelectedRecord.value.formData || {};
  patchFormData(nextData);
  currentDraftId.value = null;
  currentTemplateId.value = null;
  entryMode.value = 'new';
  copyHistoryModalVisible.value = false;
  message.success(t('历史任务已复制'));
}

function defaultStepDelivery() {
  return {
    name: '',
    status: true,
    store: 'google',
    app: undefined as string | undefined,
    promoteWebsiteUrl: '',
  };
}

/** 与 step-region.vue 一致，写入 form_data / 模板后由 MetaAdCreationLaunchService 映射到 FbAdTemplate */
function defaultStepRegion() {
  return {
    regionGroupName: '',
    useExisting: true,
    regionGroupId: null as string | null,
    region_group_tags: [] as string[],
    regionSearch: '',
    fb_ad_account_ids: [] as string[],
    tab: 'target' as 'target' | 'exclude',
    countries_included: [] as { key: string; name: string }[],
    countries_excluded: [] as { key: string; name: string }[],
    regions_included: [] as { key: string; name: string }[],
    regions_excluded: [] as { key: string; name: string }[],
    cities_included: [] as { key: string; name: string; country_name?: string; radius?: number; distance_unit?: 'mile' | 'kilometer' }[],
    cities_excluded: [] as { key: string; name: string; country_name?: string; radius?: number; distance_unit?: 'mile' | 'kilometer' }[],
    financialRegion: undefined as string | undefined,
    benefitTw: undefined as string | undefined,
    sponsorTw: undefined as string | undefined,
    benefitAu: undefined as string | undefined,
    sponsorAu: undefined as string | undefined,
    benefitSg: undefined as string | undefined,
    sponsorSg: undefined as string | undefined,
  };
}

/** 单条定向包默认（与 step-targeting.vue 一致）；Meta 中每条对应一个 Ad Set 的受众 */
function defaultTargetingPackage(seq: number) {
  return {
    id: `pkg-${Date.now()}-${seq}-${Math.random().toString(36).slice(2, 9)}`,
    name: `${t('定向包')}${seq}`,
    cardOpen: true,
    useCustomRegion: false,
    useExisting: false,
    advancedAudience: false,
    customAudience: 'unlimited' as 'unlimited' | 'custom',
    customAudienceTab: 'custom' as 'all' | 'lookalike' | 'custom',
    customAudienceSearch: '',
    customAudienceSelectedTab: 'include' as 'include' | 'exclude',
    customAudienceInclude: [] as { id: string; name: string }[],
    customAudienceExclude: [] as { id: string; name: string }[],
    minAge: 18,
    ageFrom: 18,
    ageTo: 65,
    gender: 'all' as 'all' | 'male' | 'female',
    detailedTargeting: 'unlimited' as 'unlimited' | 'custom',
    enableDetailedExpansion: false,
    locales: [] as { key: number; name: string }[],
    interests: [] as { id: number; name: string }[],
    languages: [] as string[],
    tags: [] as string[],
    targetInstalled: false,
    devices: ['mobile', 'desktop'] as string[],
    mobileDeviceMode: 'all_mobile',
    includedDevices: [] as string[],
    excludedDevices: [] as string[],
    osVersionMin: undefined as string | undefined,
    osVersionMax: undefined as string | undefined,
    osVersionText: '',
    bindTargetType: 'region',
    bindRule: 'basic',
    boundItems: [] as string[],
    wifiOnly: false,
  };
}

function defaultStepTargeting() {
  return {
    packages: [defaultTargetingPackage(1)],
  };
}

function defaultStepPlacement() {
  return {
    placementMode: 'manual',
    deviceType: 'all',
    platform: 'facebook',
    publisher_platforms: ['facebook', 'messenger', 'instagram', 'audience_network'],
    facebook_positions: ['feed', 'story', 'instream_video', 'search', 'video_feeds', 'right_hand_column', 'marketplace'],
    messenger_positions: ['messenger_home', 'messenger_inbox', 'story'],
    instagram_positions: ['stream', 'story', 'explore', 'explore_home', 'reels'],
    audience_network_positions: ['classic', 'rewarded_video', 'an_classic'],
  };
}

/** 与 step-bid-budget.vue 一致；每条与定向包按索引对应同一 Ad Set */
function defaultBidBudgetPackage(seq: number) {
  return {
    id: `bb-${Date.now()}-${seq}-${Math.random().toString(36).slice(2, 9)}`,
    name: `${t('出价')}${seq}`,
    goal: 'link_clicks',
    appEvent: undefined as string | undefined,
    websitePixelEvent: undefined as string | undefined,
    bidStrategy: 'HIGHEST_VOLUME',
    costPerResultTarget: undefined as number | undefined,
    bidCapAmount: undefined as number | undefined,
    roasTarget: undefined as number | undefined,
    attribution: 'click_7d_view_1d',
    billing: 'impressions',
    deliveryType: 'standard',
    adSetBudgetType: 'daily',
    adSetBudget: undefined as number | undefined,
    budgetByRegion: false,
    regionBudgetT1: undefined as number | undefined,
    regionBudgetT2: undefined as number | undefined,
    regionBudgetT3: undefined as number | undefined,
    schedule: 'now',
    startDate: undefined as string | undefined,
    startTime: undefined as string | undefined,
    endDate: undefined as string | undefined,
    endTime: undefined as string | undefined,
    spendMin: undefined as number | undefined,
    spendMax: undefined as number | undefined,
    pixelAssignMode: 'uniform' as const,
    pixelId: undefined as string | undefined,
    pixelEvent: undefined as string | undefined,
    bidControl: undefined as number | undefined,
    bidByRegion: false,
    regionBidG1: undefined as number | undefined,
    regionBidG2: undefined as number | undefined,
  };
}

function defaultStepBidBudget() {
  return {
    packages: [defaultBidBudgetPackage(1)],
  };
}

function defaultStepCreativeSettings() {
  return {
    adName: '',
    adStatus: true,
    pageType: 'all' as 'all' | 'personal' | 'adaccount',
    fbPage: null as string | null,
    usePageAsIdentity: false,
    multiAdvertiser: true,
    /** 网站 Pixel；null 表示沿用「基础设置」中的像素 */
    websitePixelId: null as string | null,
    websiteEvent: 'PURCHASE',
    /** 应用事件追踪（与网站事件追踪并列，写入投放扩展/追踪相关字段） */
    appEventTracking: 'PURCHASE',
    /** 落地页网址参数（utm 等，可含 Meta 动态参数与 XMP 通配符） */
    urlParams: '',
  };
}

function mergeStepCreativeSettings(incoming: any) {
  const def = defaultStepCreativeSettings();
  if (!incoming || typeof incoming !== 'object') return def;
  return {
    ...def,
    ...incoming,
    adName: String(incoming.adName ?? def.adName),
    adStatus: incoming.adStatus !== false,
    pageType: (incoming.pageType as any) || def.pageType,
    fbPage: incoming.fbPage ?? def.fbPage,
    usePageAsIdentity: incoming.usePageAsIdentity === true,
    multiAdvertiser: incoming.multiAdvertiser !== false,
    websitePixelId: incoming.websitePixelId != null && incoming.websitePixelId !== '' ? String(incoming.websitePixelId) : null,
    websiteEvent: String(incoming.websiteEvent ?? def.websiteEvent),
    appEventTracking: String(incoming.appEventTracking ?? def.appEventTracking),
    urlParams: String(incoming.urlParams ?? def.urlParams ?? ''),
  };
}

function defaultStepCreativeGroup() {
  const id = `cg-${Date.now()}`;
  const baseGroup = {
    id,
    name: `${t('创意组')}1`,
    creativeGroupName: '',
    useExisting: false,
    dynamicCreative: false,
    creativeType: 'create' as const,
    format: 'single' as const,
    settingMode: 'by_group' as const,
    deepLink: '',
    linkUrl: '',
    displayLink: '',
    videoOptimization: 'full',
    imageOptimization: 'full',
    videoMaterialIds: [] as string[],
    imageMaterialIds: [] as string[],
    materialIds: [] as string[],
    multilang: false,
    defaultLang: 'en',
    altLangs: [] as string[],
    body: '',
    title: '',
    description: '',
    cta: 'LEARN_MORE',
    tags: [] as string[],
    postIds: [] as string[],
    materialSlots: [] as Record<string, unknown>[],
    bindingRule: 'by_account' as const,
    bindingAdAccountMode: 'all' as const,
    bindingAdAccountIds: [] as string[],
    bindingRegionGroupIds: [] as string[],
  };
  return {
    activeGroupId: id,
    groups: [baseGroup],
    groupName: baseGroup.name,
    creativeGroupName: '',
    materialIds: [] as string[],
    postIds: [] as string[],
  };
}

function mergeStepCreativeGroup(incoming: any) {
  const def = defaultStepCreativeGroup();
  if (!incoming || typeof incoming !== 'object') return def;
  if (Array.isArray(incoming.groups) && incoming.groups.length > 0) {
    return {
      ...def,
      ...incoming,
      activeGroupId: String(incoming.activeGroupId || incoming.groups[0]?.id || def.activeGroupId),
      groups: incoming.groups.map((g: any, i: number) => ({
        ...def.groups[0],
        ...g,
        id: String(g.id || `cg-${i}-${Date.now()}`),
        name: String(g.name ?? `${t('创意组')}${i + 1}`),
        videoMaterialIds: Array.isArray(g.videoMaterialIds) ? [...g.videoMaterialIds] : [],
        imageMaterialIds: Array.isArray(g.imageMaterialIds) ? [...g.imageMaterialIds] : [],
        materialIds: Array.isArray(g.materialIds) ? [...g.materialIds] : [],
        postIds: Array.isArray(g.postIds) ? [...g.postIds] : [],
        tags: Array.isArray(g.tags) ? [...g.tags] : [],
        materialSlots: Array.isArray(g.materialSlots) ? [...g.materialSlots] : [],
        bindingRule: g.bindingRule === 'by_region_group' ? 'by_region_group' : 'by_account',
        bindingAdAccountMode: g.bindingAdAccountMode === 'selected' ? 'selected' : 'all',
        bindingAdAccountIds: Array.isArray(g.bindingAdAccountIds) ? [...g.bindingAdAccountIds] : [],
        bindingRegionGroupIds: Array.isArray(g.bindingRegionGroupIds) ? [...g.bindingRegionGroupIds] : [],
        displayLink: String(g.displayLink ?? ''),
        defaultLang: g.defaultLang != null && String(g.defaultLang).trim() !== '' ? String(g.defaultLang) : 'en',
        altLangs: Array.isArray(g.altLangs) ? g.altLangs.map((a: unknown) => String(a)) : [],
      })),
    };
  }
  return { ...def, ...incoming };
}

function mergeStepBidBudget(incoming: any) {
  const def = defaultStepBidBudget();
  if (!incoming || typeof incoming !== 'object') return def;
  if (Array.isArray(incoming.packages) && incoming.packages.length > 0) {
    return {
      packages: incoming.packages.map((p: any, i: number) => ({
        ...defaultBidBudgetPackage(i + 1),
        ...p,
        id: p.id || `bb-${i}-${Date.now()}`,
        name: String(p.name ?? `${t('出价')}${i + 1}`),
        pixelAssignMode: p.pixelAssignMode === 'by_account' ? 'by_account' : 'uniform',
        pixelId: p.pixelId,
        pixelEvent: p.pixelEvent,
        bidControl: p.bidControl,
        bidByRegion: p.bidByRegion === true,
        regionBidG1: p.regionBidG1,
        regionBidG2: p.regionBidG2,
      })),
    };
  }
  const { packages: _bp, ...rest } = incoming;
  return {
    packages: [{ ...defaultBidBudgetPackage(1), ...rest }],
  };
}

function mergeStepTargeting(incoming: any) {
  const def = defaultStepTargeting();
  if (!incoming || typeof incoming !== 'object') return def;
  if (Array.isArray(incoming.packages) && incoming.packages.length > 0) {
    return {
      packages: incoming.packages.map((p: any, i: number) => ({
        ...defaultTargetingPackage(i + 1),
        ...p,
        id: p.id || `pkg-${i}-${Date.now()}`,
        name: String(p.name ?? `${t('定向包')}${i + 1}`),
        cardOpen: p.cardOpen !== false,
        useCustomRegion: p.useCustomRegion === true,
        stepRegion:
          p.useCustomRegion === true && p.stepRegion && typeof p.stepRegion === 'object'
            ? JSON.parse(JSON.stringify(p.stepRegion))
            : undefined,
        locales: Array.isArray(p.locales) ? [...p.locales] : [],
        interests: Array.isArray(p.interests) ? [...p.interests] : [],
        customAudienceTab: p.customAudienceTab,
        customAudienceSearch: p.customAudienceSearch,
        customAudienceSelectedTab: p.customAudienceSelectedTab,
        customAudienceInclude: Array.isArray(p.customAudienceInclude) ? [...p.customAudienceInclude] : [],
        customAudienceExclude: Array.isArray(p.customAudienceExclude) ? [...p.customAudienceExclude] : [],
        languages: Array.isArray(p.languages) ? p.languages.map(String) : [],
        tags: Array.isArray(p.tags) ? p.tags.map(String) : [],
        devices:
          Array.isArray(p.devices) && p.devices.length ? [...p.devices] : ['mobile', 'desktop'],
        includedDevices: Array.isArray(p.includedDevices) ? [...p.includedDevices] : [],
        excludedDevices: Array.isArray(p.excludedDevices) ? [...p.excludedDevices] : [],
      })),
    };
  }
  const { packages: _p, ...rest } = incoming;
  return {
    packages: [{ ...defaultTargetingPackage(1), ...rest }],
  };
}

function patchFormData(nextData: Record<string, any>) {
  Object.assign(formData.stepOne, nextData.stepOne || {});
  Object.assign(formData.stepTwo, nextData.stepTwo || {});
  formData.stepDelivery = { ...defaultStepDelivery(), ...(nextData.stepDelivery || {}) };
  formData.stepRegion = { ...defaultStepRegion(), ...(nextData.stepRegion || {}) };
  formData.stepPlacement = { ...defaultStepPlacement(), ...(nextData.stepPlacement || {}) };
  formData.stepTargeting = mergeStepTargeting(nextData.stepTargeting);
  formData.stepBidBudget = mergeStepBidBudget(nextData.stepBidBudget);
  formData.stepCreativeSettings = mergeStepCreativeSettings(nextData.stepCreativeSettings);
  formData.stepCreativeGroup = mergeStepCreativeGroup(nextData.stepCreativeGroup);
  Object.assign(formData.splitRules, mergeSplitRules(nextData.splitRules));
}

async function onTemplateConfirm(template: any) {
  templateModalVisible.value = false;
  selectedTemplate.value = template;
  entryMode.value = 'template';
  try {
    const detail = await getMetaAdCreationTemplateApi(String(template.id));
    const data = (detail as any)?.data ?? {};
    currentTemplateId.value = data.id ?? String(template.id);
    patchFormData(data.formData || {});
  } catch (_) {
    currentTemplateId.value = String(template.id);
  }
}

const metaSummaryColumns = [
  { title: t('属性'), dataIndex: 'attr', key: 'attr' },
  { title: t('数量'), dataIndex: 'qty', key: 'qty', width: 70 },
  { title: t('拆分规则'), dataIndex: 'rule', key: 'rule' },
];
function bidBudgetPackageCount(): number {
  const p = formData.stepBidBudget?.packages;
  return Array.isArray(p) && p.length > 0 ? p.length : 1;
}

function creativeGroupPackageCount(): number {
  const g = formData.stepCreativeGroup?.groups;
  return Array.isArray(g) && g.length > 0 ? g.length : 1;
}

/** 右侧摘要「拆分规则」列展示文案 */
function splitRuleDisplayLabel(level: string): string {
  if (level === 'campaign') return t('广告系列');
  if (level === 'ad') return t('广告');
  return t('广告组');
}

/** 地区组步骤当前为单套配置，数量固定为 1；多地区组列表对接后可改为列表长度 */
function regionGroupSummaryCount(): number {
  return 1;
}

const metaSummaryData = computed(() => {
  const sr = formData.splitRules;
  const tp = formData.stepTargeting?.packages;
  const targetingQty = Array.isArray(tp) && tp.length > 0 ? tp.length : 1;
  const bidQty = bidBudgetPackageCount();
  const cgQty = creativeGroupPackageCount();
  return [
    { attr: t('地区组'), qty: regionGroupSummaryCount(), rule: splitRuleDisplayLabel(sr.regionGroupLevel) },
    { attr: t('定向包'), qty: targetingQty, rule: splitRuleDisplayLabel(sr.targetingLevel) },
    { attr: t('出价和预算'), qty: bidQty, rule: splitRuleDisplayLabel(sr.bidBudgetLevel) },
    { attr: t('创意组'), qty: cgQty, rule: splitRuleDisplayLabel(sr.creativeGroupLevel) },
  ];
});

interface StepTwoForm {
  campaignName: string;
  campaignStatus: boolean;
  specialCategory: any;
  cbo: boolean;
  budgetType: string;
  budget: number | null;
  bidStrategy: string;
  /** 单次成效费用目标（竞价策略为 COST_PER_RESULT 时） */
  costPerResultTarget?: number | null;
  /** 竞价上限金额（竞价策略为 BID_CAP 时） */
  bidCapAmount?: number | null;
  /** 广告花费回报目标 ROAS（竞价策略为 ROAS 时，如 2 表示 2:1） */
  roasTarget?: number | null;
  schedule: string;
  /** 分时段投放：开始/结束日期 YYYY-MM-DD */
  scheduleStartDate?: string | null;
  scheduleEndDate?: string | null;
  /** 分时段投放：每日投放时段 HH:mm */
  dailyScheduleStart?: string | null;
  dailyScheduleEnd?: string | null;
  deliveryType: string;
  spendLimit: string;
  /** 自定义花费限额时的上限金额（USD） */
  customSpendCap?: number | null;
}

const formData = reactive({
  stepOne: {
    purchaseType: 'auction',
    objective: 'OUTCOME_SALES',
    adAccountIds: [] as string[],
    adAccount: null as string | null,
    operator: null as string | null,
    page: null as string | null,
    pixel: null as string | null,
    form: null as string | null,
    creationMode: 'standard',
    advancedCatalogAds: false,
    conversionLocation: 'app',
  },
  stepTwo: {
    campaignName: '',
    campaignStatus: true,
    specialCategory: undefined,
    cbo: true,
    budgetType: 'daily',
    budget: null as number | null,
    bidStrategy: 'HIGHEST_VOLUME',
    costPerResultTarget: null,
    bidCapAmount: null,
    roasTarget: null,
    schedule: 'all_day',
    scheduleStartDate: null,
    scheduleEndDate: null,
    dailyScheduleStart: null,
    dailyScheduleEnd: null,
    deliveryType: 'standard',
    spendLimit: 'unlimited',
    customSpendCap: null,
  } as StepTwoForm,
  stepDelivery: defaultStepDelivery(),
  stepRegion: defaultStepRegion() as any,
  stepPlacement: defaultStepPlacement() as any,
  stepTargeting: defaultStepTargeting() as any,
  stepBidBudget: defaultStepBidBudget() as any,
  stepCreativeSettings: defaultStepCreativeSettings() as any,
  stepCreativeGroup: defaultStepCreativeGroup() as any,
  splitRules: defaultSplitRules(),
});

function onReset() {
  patchFormData({
    stepOne: {
      purchaseType: 'auction',
      objective: 'OUTCOME_SALES',
      adAccountIds: [],
      adAccount: null,
      operator: null,
      page: null,
      pixel: null,
      form: null,
      creationMode: 'standard',
      advancedCatalogAds: false,
      conversionLocation: 'app',
    },
    stepTwo: {
      campaignName: '',
      campaignStatus: true,
      specialCategory: undefined,
      cbo: true,
      budgetType: 'daily',
      budget: null,
      bidStrategy: 'HIGHEST_VOLUME',
      costPerResultTarget: null,
      bidCapAmount: null,
      roasTarget: null,
      schedule: 'all_day',
      scheduleStartDate: null,
      scheduleEndDate: null,
      dailyScheduleStart: null,
      dailyScheduleEnd: null,
      deliveryType: 'standard',
      spendLimit: 'unlimited',
      customSpendCap: null,
    },
    stepDelivery: defaultStepDelivery(),
    stepRegion: defaultStepRegion(),
    stepPlacement: defaultStepPlacement(),
    stepTargeting: defaultStepTargeting(),
    stepBidBudget: defaultStepBidBudget(),
    stepCreativeSettings: defaultStepCreativeSettings(),
    stepCreativeGroup: defaultStepCreativeGroup(),
  });
  currentStep.value = 0;
  message.success(t('已重置'));
}
async function onSaveDraft() {
  const payload = {
    tag: 'default',
    name: formData.stepTwo.campaignName || t('未命名草稿'),
    form_data: JSON.parse(JSON.stringify(formData)),
    meta_counts: {
      regionGroup: 1,
      targeting: targetingPackageCount(),
      bidBudget: bidBudgetPackageCount(),
      creativeGroup: creativeGroupPackageCount(),
    },
    current_step: currentStepIndex.value,
  };
  try {
    if (currentDraftId.value) {
      await updateMetaAdCreationDraftApi(currentDraftId.value, payload);
    } else {
      const saved = await createMetaAdCreationDraftApi(payload);
      currentDraftId.value = (saved as any)?.data?.id ?? null;
    }
    message.success(t('草稿已保存'));
    router.back();
  } catch {
    message.error(t('保存草稿失败'));
  }
}
function onPreview() {
  loading.value = true;
  setTimeout(() => {
    loading.value = false;
    message.info(t('预览功能已就绪，可继续提交投放'));
  }, 500);
}

function normalizePlacementForSubmit(raw: any) {
  const d = raw && typeof raw === 'object' ? { ...raw } : {};
  const mode = d.placementMode === 'advanced' ? 'advanced' : 'manual';
  const out: any = {
    ...d,
    placementMode: mode,
  };
  const asArray = (v: any) => (Array.isArray(v) ? [...v] : []);
  if (mode === 'advanced') {
    out.publisher_platforms = [];
    out.facebook_positions = [];
    out.instagram_positions = [];
    out.messenger_positions = [];
    out.audience_network_positions = [];
    return out;
  }
  out.publisher_platforms = asArray(d.publisher_platforms);
  out.facebook_positions = asArray(d.facebook_positions);
  out.instagram_positions = asArray(d.instagram_positions);
  out.messenger_positions = asArray(d.messenger_positions);
  out.audience_network_positions = asArray(d.audience_network_positions);
  return out;
}

function normalizeSingleTargetingPackage(p: any) {
  const d = p && typeof p === 'object' ? { ...p } : {};
  const out: any = { ...d };
  if (d.detailedTargeting !== 'custom') {
    out.interests = [];
  }
  const mode = String(d.mobileDeviceMode || 'all_mobile');
  if (mode === 'all_mobile') {
    out.devices = ['mobile'];
    out.includedDevices = [];
  } else if (mode === 'feature_phone') {
    out.devices = ['feature_phone'];
    out.includedDevices = [];
  } else if (mode === 'android_only') {
    out.devices = ['mobile'];
    out.user_os = ['Android'];
  } else if (mode === 'ios_only') {
    out.devices = ['mobile'];
    out.user_os = ['iOS'];
  }
  return out;
}

function normalizeTargetingForSubmit(raw: any) {
  const d = raw && typeof raw === 'object' ? raw : {};
  if (Array.isArray(d.packages)) {
    return {
      packages: d.packages.map((p: any) => normalizeSingleTargetingPackage(p)),
    };
  }
  return { packages: [normalizeSingleTargetingPackage(d)] };
}

function targetingPackageCount(): number {
  const p = formData.stepTargeting?.packages;
  return Array.isArray(p) && p.length > 0 ? p.length : 1;
}

/** 名称模板占位符（与后端 MetaAdCreationSplitRules 约定一致） */
const nameTemplateTokens = ['{campaignName}', '{targetingName}', '{bidName}', '{creativeGroupName}', '{accountName}', '{regionLabel}'];

const targetingIndexOptions = computed(() => {
  const n = targetingPackageCount();
  return Array.from({ length: n }, (_, i) => ({ label: `${t('定向包')} #${i + 1}`, value: i }));
});

const bidIndexOptions = computed(() => {
  const n = bidBudgetPackageCount();
  return Array.from({ length: n }, (_, i) => ({ label: `${t('出价')}${i + 1}`, value: i }));
});

/** 弹窗内：全组合叉乘 vs 自由绑定配对 × 创意组，估算叶子广告量级 */
const splitRulesEstimate = computed(() => {
  const nAcc = Math.max(1, selectedAccountCount.value);
  const nReg = regionGroupSummaryCount();
  const nTp = targetingPackageCount();
  const nBb = bidBudgetPackageCount();
  const nCg = creativeGroupPackageCount();
  const mode = splitRulesDraft.generationMode ?? 'full';
  let pairCount = 1;
  if (mode === 'free_binding') {
    const pairs = splitRulesDraft.freeBindingPairs;
    pairCount = Array.isArray(pairs) && pairs.length > 0 ? pairs.length : Math.min(nTp, nBb);
    pairCount = Math.max(1, pairCount);
  }
  const estimated =
    mode === 'free_binding' ? nAcc * nReg * pairCount * nCg : nAcc * nReg * nTp * nBb * nCg;
  return {
    nAcc,
    nReg,
    nTp,
    nBb,
    nCg,
    mode,
    pairCount,
    estimated,
  };
});

/** 定向包内单独地区是否至少包含一个国家/大区/城市 */
function hasGeoInPackageStepRegion(region: any) {
  if (!region || typeof region !== 'object') return false;
  const ci = region.countries_included?.length ?? 0;
  const ri = region.regions_included?.length ?? 0;
  const ct = region.cities_included?.length ?? 0;
  return ci + ri + ct > 0;
}

/** 与 step-bid-budget 中「应用转化事件」展示条件一致 */
function bidBudgetNeedsAppConversionEvent() {
  const loc = formData.stepOne.conversionLocation;
  const obj = formData.stepOne.objective;
  if (loc !== 'app') return false;
  if (obj === 'OUTCOME_APP_PROMOTION' || obj === 'OUTCOME_TRAFFIC') return false;
  return true;
}

function bidBudgetNeedsWebsitePixel(b: any) {
  return (
    formData.stepOne.conversionLocation === 'website' &&
    formData.stepOne.objective === 'OUTCOME_SALES' &&
    ['offsite_conversions', 'conversion_value'].includes(b?.goal)
  );
}

function bidBudgetNeedsAttribution(b: any) {
  const obj = formData.stepOne.objective;
  return ['OUTCOME_SALES', 'OUTCOME_LEADS'].includes(obj) && !['link_clicks', 'reach'].includes(b?.goal);
}

/** 与 step-creative-settings 中网站 Pixel / 转化事件展示条件一致 */
function creativeSettingsNeedsWebsitePixel() {
  const loc = formData.stepOne.conversionLocation;
  const obj = formData.stepOne.objective;
  if (loc !== 'website') return false;
  return !['OUTCOME_TRAFFIC', 'OUTCOME_AWARENESS', 'OUTCOME_ENGAGEMENT'].includes(obj);
}

async function onSaveTemplate() {
  try {
    const normalized = JSON.parse(JSON.stringify(formData));
    normalized.stepPlacement = normalizePlacementForSubmit(normalized.stepPlacement);
    normalized.stepTargeting = normalizeTargetingForSubmit(normalized.stepTargeting);
    const saved = await createMetaAdCreationTemplateApi({
      name: formData.stepTwo.campaignName || t('未命名模板'),
      form_data: normalized,
      meta_counts: {
        regionGroup: 1,
        targeting: targetingPackageCount(),
        bidBudget: bidBudgetPackageCount(),
        creativeGroup: creativeGroupPackageCount(),
      },
    });
    currentTemplateId.value = (saved as any)?.data?.id ?? null;
    message.success(t('模板已保存'));
  } catch {
    message.error(t('保存模板失败'));
  }
}

async function onSubmitLaunch() {
  const d = formData.stepDelivery;
  if (!String(d?.name ?? '').trim()) {
    message.warning(t('请在「投放内容」中填写广告组名称'));
    goToStep(2);
    return;
  }
  if (formData.stepOne.objective === 'OUTCOME_APP_PROMOTION' && formData.stepOne.conversionLocation !== 'app') {
    message.warning(t('「应用推广」须将转化发生位置设为「应用」'));
    goToStep(0);
    return;
  }
  if (formData.stepOne.conversionLocation === 'app' && !d?.app) {
    message.warning(t('应用转化请在「投放内容」中选择应用'));
    goToStep(2);
    return;
  }
  if (
    formData.stepOne.conversionLocation === 'form'
    && formData.stepOne.objective === 'OUTCOME_LEADS'
    && !formData.stepOne.form
  ) {
    message.warning(t('请在「基础设置」中选择表单'));
    goToStep(0);
    return;
  }
  if (formData.stepOne.conversionLocation === 'website' && !formData.stepOne.pixel) {
    if (!['OUTCOME_TRAFFIC', 'OUTCOME_AWARENESS', 'OUTCOME_ENGAGEMENT'].includes(formData.stepOne.objective)) {
      const creativePx = formData.stepCreativeSettings?.websitePixelId;
      if (!creativePx) {
        message.warning(t('网站转化请在「基础设置」中选择像素，或在「创意设置」中选择网站 Pixel'));
        goToStep(0);
        return;
      }
    }
  }
  if (!formData.stepOne.page) {
    message.warning(t('请在「基础设置」中选择 Facebook 主页'));
    goToStep(0);
    return;
  }
  const pkgs = formData.stepTargeting?.packages;
  const pkgList =
    Array.isArray(pkgs) && pkgs.length > 0 ? pkgs : [formData.stepTargeting as any];
  for (const p of pkgList) {
    if (!String(p?.name ?? '').trim()) {
      message.warning(t('请为每个定向包填写名称'));
      goToStep(5);
      return;
    }
    if (!p?.useExisting && (!Array.isArray(p?.devices) || p.devices.length === 0)) {
      message.warning(t('请在每个定向包中选择至少一种包含的设备'));
      goToStep(5);
      return;
    }
    if (
      p?.useCustomRegion
      && !p?.useExisting
      && !hasGeoInPackageStepRegion(p?.stepRegion)
    ) {
      message.warning(
        t('已开启「本广告组单独地区」的定向包请至少选择一个国家、大区或城市'),
      );
      goToStep(5);
      return;
    }
  }

  const bidPkgs = formData.stepBidBudget?.packages;
  const bidList =
    Array.isArray(bidPkgs) && bidPkgs.length > 0 ? bidPkgs : [formData.stepBidBudget as any];
  if (bidList.length !== pkgList.length) {
    message.warning(t('「出价和预算」条数必须与定向包条数一致'));
    goToStep(6);
    return;
  }
  for (const b of bidList) {
    applyBidBudgetLinkage(b);
    const bidErr = validateBidBudgetPackage(b, {
      needsAppConversionEvent: bidBudgetNeedsAppConversionEvent(),
      needsWebsitePixel: bidBudgetNeedsWebsitePixel(b),
      needsAttribution: bidBudgetNeedsAttribution(b),
      t,
    });
    if (bidErr) {
      message.warning(bidErr);
      goToStep(6);
      return;
    }
  }

  const cs = formData.stepCreativeSettings;
  if (!String(cs?.adName ?? '').trim()) {
    message.warning(t('请在「创意设置」中填写广告名称'));
    goToStep(7);
    return;
  }
  const creativePageId = cs?.fbPage || formData.stepOne.page;
  if (!creativePageId) {
    message.warning(t('请在「创意设置」或「基础设置」中选择 Facebook 公共主页'));
    goToStep(7);
    return;
  }
  if (creativeSettingsNeedsWebsitePixel()) {
    const wp = cs?.websitePixelId ?? formData.stepOne.pixel;
    if (wp == null || wp === '') {
      message.warning(t('请在「创意设置」中选择网站 Pixel，或在「基础设置」中选择像素'));
      goToStep(7);
      return;
    }
  }

  submitLoading.value = true;
  try {
    const normalized = JSON.parse(JSON.stringify(formData));
    normalized.stepPlacement = normalizePlacementForSubmit(normalized.stepPlacement);
    normalized.stepTargeting = normalizeTargetingForSubmit(normalized.stepTargeting);
    const res = await createMetaAdCreationRecordApi({
      form_data: normalized,
      meta_counts: {
        regionGroup: 1,
        targeting: targetingPackageCount(),
        bidBudget: bidBudgetPackageCount(),
        creativeGroup: creativeGroupPackageCount(),
      },
      draft_id: currentDraftId.value,
      template_id: currentTemplateId.value,
    });
    const id = (res as any)?.data?.id;
    message.success(id ? `${t('提交成功')} #${id}` : t('提交成功'));
  } catch (e: any) {
    message.error(e?.response?.data?.message || t('提交失败'));
  } finally {
    submitLoading.value = false;
  }
}
</script>

<style lang="less" scoped>
.meta-ad-creation {
  display: flex;
  gap: 16px;
  min-height: calc(100vh - 200px);

  .steps-sidebar {
    width: 200px;
    flex-shrink: 0;
    background: #fff;
    padding: 24px 16px;
    border-radius: 4px;

    .step-item {
      display: flex;
      align-items: center;
      gap: 10px;
      padding: 10px 0;
      cursor: pointer;
      font-size: 14px;
      color: #8c8c8c;

      .step-dot {
        width: 8px;
        height: 8px;
        border-radius: 50%;
        background: #d9d9d9;
      }

      &.active {
        color: #1890ff;
        font-weight: 500;
        .step-dot {
          background: #1890ff;
        }
      }
    }
  }

  .main-content {
    flex: 1;
    display: flex;
    flex-direction: column;
    gap: 16px;

    .meta-ad-flow-card {
      :deep(.ant-card-body) {
        min-height: 360px;
      }
    }

    .step-content {
      min-height: 400px;
    }

    .footer-actions {
      display: flex;
      justify-content: space-between;
      align-items: center;
      gap: 16px;
      padding: 16px 0;
      background: #fff;
      border-radius: 4px;

      .footer-step-nav {
        display: flex;
        gap: 8px;
      }
      .footer-actions-right {
        display: flex;
        gap: 8px;
      }
    }
  }

  .entry-choice-card {
    .section-title {
      font-size: 16px;
      font-weight: 500;
      margin-bottom: 24px;
      color: #262626;
    }
    .entry-choice-row {
      display: flex;
      gap: 16px;
      margin-bottom: 16px;
    }
    .entry-hint {
      color: #8c8c8c;
      font-size: 13px;
      margin: 0;
    }
  }

  .config-summary.meta-panel {
    width: 280px;
    flex-shrink: 0;

    :deep(.ant-card-body) {
      padding: 16px;
    }

    .edit-btn {
      float: right;
      padding: 0;
    }

    .meta-account-count {
      margin-bottom: 12px;
      font-size: 13px;
    }
  }
}

.copy-history-footer {
  display: flex;
  justify-content: flex-end;
  gap: 8px;
  margin-top: 16px;
  padding-top: 16px;
  border-top: 1px solid #f0f0f0;
}

.split-rules-intro {
  margin: 0 0 24px;
  padding-right: 8px;
  font-size: 13px;
  color: #595959;
  line-height: 1.65;
}
.split-rules-preview-alert {
  margin-bottom: 20px;
  :deep(.ant-alert-message) {
    width: 100%;
  }
}
.split-preview-line {
  display: block;
  font-size: 13px;
  line-height: 1.6;
  color: rgba(0, 0, 0, 0.85);
}
.split-preview-est {
  display: block;
  margin-top: 6px;
  font-size: 13px;
  color: #595959;
}
.split-rules-section-title {
  margin: 4px 0 4px;
  padding-bottom: 6px;
  font-size: 14px;
  font-weight: 600;
  color: rgba(0, 0, 0, 0.88);
  border-bottom: 1px solid #f0f0f0;
}
.split-rules-free-binding {
  display: flex;
  flex-direction: column;
  gap: 10px;
  padding: 12px 14px;
  background: #fafafa;
  border: 1px solid #f0f0f0;
  border-radius: 6px;
}
.split-free-binding-head {
  display: flex;
  flex-wrap: wrap;
  align-items: center;
  justify-content: space-between;
  gap: 8px;
  font-size: 13px;
  color: rgba(0, 0, 0, 0.75);
}
.split-free-table {
  width: 100%;
  border-collapse: collapse;
  font-size: 13px;
  th,
  td {
    padding: 8px 10px;
    border: 1px solid #f0f0f0;
    text-align: left;
    vertical-align: middle;
  }
  th {
    background: #fff;
    font-weight: 500;
    color: rgba(0, 0, 0, 0.65);
  }
}
.split-free-col-op {
  width: 88px;
  text-align: center;
}
.split-free-binding-empty {
  margin: 0;
  font-size: 13px;
  color: #8c8c8c;
}
.split-free-binding-hint {
  margin: 0;
  font-size: 12px;
  color: #8c8c8c;
  line-height: 1.5;
}
.split-rule-row--block {
  align-items: flex-start;
  .split-rule-label {
    line-height: 22px;
  }
}
.split-rule-control--stack {
  flex-direction: column;
  align-items: stretch;
  gap: 8px;
}
.name-token-row {
  display: flex;
  flex-wrap: wrap;
  align-items: center;
  gap: 4px 8px;
}
.name-token-label {
  font-size: 12px;
  color: #8c8c8c;
  margin-right: 4px;
}
.split-rules-body {
  display: flex;
  flex-direction: column;
  gap: 26px;
}
.split-rules-batch-block {
  display: flex;
  flex-direction: column;
  gap: 26px;
  margin-top: 2px;
}
.split-rule-row {
  display: flex;
  flex-direction: row;
  align-items: flex-start;
  gap: 28px;
  min-height: 34px;
}
.split-rule-label {
  flex: 0 0 184px;
  width: 184px;
  text-align: right;
  font-size: 14px;
  color: rgba(0, 0, 0, 0.88);
  line-height: 34px;
  font-weight: 400;
  padding-right: 4px;
}
.split-rule-control {
  flex: 1;
  min-width: 0;
  display: flex;
  align-items: center;
  flex-wrap: wrap;
  gap: 10px;
}
.split-rule-interval {
  align-items: center;
  gap: 12px;
}
.split-rule-sep {
  color: #8c8c8c;
  line-height: 34px;
  user-select: none;
  padding: 0 2px;
}
.split-rule-unit {
  font-size: 14px;
  color: rgba(0, 0, 0, 0.88);
  line-height: 34px;
  white-space: nowrap;
}
.split-rule-input-num {
  width: 120px;
}
.split-rule-input-num--interval {
  width: 88px;
}

/* 弹窗内：分段按钮独立留白、行距与图示对齐 */
.split-rules-modal {
  :deep(.ant-modal-header) {
    margin-bottom: 0;
    padding-bottom: 12px;
  }
  :deep(.ant-modal-body) {
    padding: 20px 24px 24px;
  }
  :deep(.ant-modal-footer) {
    padding: 12px 24px 20px;
  }
  /* 单选按钮组：按钮之间留出空隙，避免连成一条 */
  :deep(.ant-radio-group) {
    display: inline-flex !important;
    flex-wrap: wrap;
    gap: 10px;
    row-gap: 10px;
  }
  :deep(.ant-radio-group .ant-radio-button-wrapper) {
    margin-inline-start: 0 !important;
    border-radius: 4px !important;
    line-height: 30px;
    height: 32px;
    padding-inline: 16px;
    /* 与相邻按钮拉开后，每条边都保留边框，避免 Ant Design 默认「中间无左框」导致缺口 */
    border-left-width: 1px !important;
  }
  :deep(.ant-radio-group .ant-radio-button-wrapper::before) {
    display: none !important;
  }
  :deep(.ant-radio-group .ant-radio-button-wrapper:first-child) {
    border-radius: 4px !important;
  }
  :deep(.ant-radio-group .ant-radio-button-wrapper:last-child) {
    border-radius: 4px !important;
  }
}
</style>
