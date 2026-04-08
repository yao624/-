<template>
  <div class="action-config">
    <div class="action-top-row">
      <a-select
        v-model:value="localAction.type"
        class="action-type-select"
        @change="handleTypeChange"
      >
        <a-select-option value="status">{{ t('状态') }}</a-select-option>
        <a-select-option value="bid">{{ t('出价') }}</a-select-option>
        <a-select-option value="budget">{{ t('日预算') }}</a-select-option>
        <a-select-option value="total-budget">{{ t('总预算') }}</a-select-option>
        <a-select-option value="roas-target">{{ t('广告花费回报目标') }}</a-select-option>
        <a-select-option value="edit-tag">{{ t('编辑标签') }}</a-select-option>
        <a-select-option value="send-email">{{ t('发送邮件') }}</a-select-option>
        <a-select-option value="send-dingtalk">{{ t('发送钉钉群') }}</a-select-option>
        <a-select-option value="send-wecom">{{ t('发送企业微信群') }}</a-select-option>
        <a-select-option value="send-feishu">{{ t('发送飞书群') }}</a-select-option>
      </a-select>

      <!-- 首行：类型 + 主要输入/选项 -->
      <template v-if="localAction.type === 'send-email'">
        <div class="primary-area">
          <a-select
            v-model:value="localAction.config.recipients"
            mode="multiple"
            :placeholder="t('发送对象')"
            class="recipient-multi-select"
            :options="emailRecipientOptions.map((o) => ({ value: o, label: o }))"
            allow-clear
            show-search
            :filter-option="filterSendTargetOption"
          >
            <template #dropdownRender="{ menuNode: menu }">
              <VNodes :vnodes="menu" />
              <a-divider style="margin: 4px 0" />
              <a-space class="select-add-row">
                <a-input
                  ref="emailAddInputRef"
                  v-model:value="newEmailDraft"
                  :placeholder="t('请输入后添加')"
                  @press-enter="addEmailRecipientOption"
                />
                <a-button type="text" @click="addEmailRecipientOption">
                  <template #icon>
                    <PlusOutlined />
                  </template>
                </a-button>
              </a-space>
            </template>
          </a-select>
        </div>
      </template>

      <template v-if="localAction.type === 'status'">
        <div class="primary-area">

          <a-select v-model:value="localAction.config.status" class="primary-select">
            <a-select-option value="pause">{{ t('暂停') }}</a-select-option>
            <a-select-option value="enable">{{ t('开启') }}</a-select-option>
          </a-select>
        </div>
      </template>

      <template v-if="localAction.type === 'bid'">
        <div class="primary-area">
          <a-select v-model:value="localAction.config.bidAction" class="primary-select">
            <a-select-option value="increase">{{ t('增加数值') }}</a-select-option>
            <a-select-option value="decrease">{{ t('减少数值') }}</a-select-option>
          </a-select>
        </div>
      </template>

      <template v-if="localAction.type === 'budget' || localAction.type === 'total-budget'">
        <div class="primary-area">
          <a-select v-model:value="localAction.config.budgetAction" class="primary-select">
            <a-select-option value="increase">{{ t('增加数值') }}</a-select-option>
            <a-select-option value="decrease">{{ t('减少数值') }}</a-select-option>
          </a-select>
        </div>
      </template>

      <template v-if="localAction.type === 'roas-target'">
        <div class="primary-area">
          <a-select v-model:value="localAction.config.roasAction" class="primary-select">
            <a-select-option value="increase">{{ t('增加数值') }}</a-select-option>
            <a-select-option value="decrease">{{ t('减少数值') }}</a-select-option>
          </a-select>
        </div>
      </template>

      <template v-if="localAction.type === 'edit-tag'">
        <div class="primary-area">
          <a-select
            v-model:value="localAction.config.tag"
            :placeholder="t('请选择')"
            class="primary-select"
            allow-clear
          />
        </div>
      </template>

      <template
        v-if="
          localAction.type === 'send-dingtalk' ||
          localAction.type === 'send-wecom' ||
          localAction.type === 'send-feishu'
        "
      >
        <div class="primary-area">
          <a-select
            v-model:value="localAction.config.webhookUrls"
            mode="multiple"
            :placeholder="t('发送对象')"
            class="recipient-multi-select"
            :options="imTargetOptions.map((o) => ({ value: o, label: o }))"
            allow-clear
            show-search
            :filter-option="filterSendTargetOption"
          >
            <template #dropdownRender="{ menuNode: menu }">
              <VNodes :vnodes="menu" />
              <a-divider style="margin: 4px 0" />
              <a-space class="select-add-row">
                <a-input
                  ref="imAddInputRef"
                  v-model:value="newImTargetDraft"
                  :placeholder="t('请输入后添加')"
                  @press-enter="addImTargetOption"
                />
                <a-button type="text" @click="addImTargetOption">
                  <template #icon>
                    <PlusOutlined />
                  </template>
                </a-button>
              </a-space>
            </template>
          </a-select>
        </div>
      </template>
    </div>

    <!-- 详情：纵向堆叠，宽度占满 -->
    <div class="action-details">
      <template v-if="localAction.type === 'send-email'">
        <a-input
          v-model:value="localAction.config.subject"
          :placeholder="t('邮件主题')"
          class="full-width-input"
        />
        <a-textarea
          v-model:value="localAction.config.body"
          :placeholder="t('邮件正文')"
          :rows="3"
          class="full-width-textarea"
        />

        <div class="action-help-row">
          <a href="#" class="action-help-link" @click.prevent>
            {{ t('如何找到以上参数') }}
          </a>
        </div>
      </template>

      <template v-if="localAction.type === 'bid'">
        <div class="detail-stack">
          <a-input-number
            v-model:value="localAction.config.bidValue"
            :placeholder="t('数值')"
            class="full-width-input-number"
          />
          <div class="detail-text-row">
            <span>
              {{
                localAction.config.bidAction === 'increase'
                  ? t('且新最高不超过')
                  : t('且新最低不小于')
              }}
            </span>
          </div>
          <div class="detail-row">
            <a-input-number
              v-model:value="localAction.config.bidLimit"
              :placeholder="t('数值')"
              class="detail-input-number"
            />
            <span class="detail-suffix">USD</span>
          </div>
        </div>
      </template>

      <template v-if="localAction.type === 'budget' || localAction.type === 'total-budget'">
        <div class="detail-stack">
          <a-input-number
            v-model:value="localAction.config.budgetValue"
            :placeholder="t('数值')"
            class="full-width-input-number"
          />
          <div class="detail-text-row">
            <span>
              {{
                localAction.config.budgetAction === 'increase'
                  ? t('且新最高不超过')
                  : t('且新最低不小于')
              }}
            </span>
          </div>
          <div class="detail-row">
            <a-input-number
              v-model:value="localAction.config.budgetLimit"
              :placeholder="t('数值')"
              class="detail-input-number"
            />
            <span class="detail-suffix">USD</span>
          </div>

          <div v-if="localAction.type === 'budget'" class="budget-strategy">
            <div class="strategy-title">{{ t('第二天的预算策略') }}:</div>
            <a-radio-group v-model:value="localAction.config.budgetStrategy">
              <a-radio value="continue">
                {{ t('继续沿用调整后的预算') }}
              </a-radio>
              <a-radio value="restore">
                {{ t('还原为AI助手最后一次调整成功前的预算(可在任务中心批量编辑类型中查看定时调整任务)') }}
              </a-radio>
            </a-radio-group>
          </div>
        </div>
      </template>

      <template v-if="localAction.type === 'roas-target'">
        <div class="detail-stack">
          <a-input-number
            v-model:value="localAction.config.roasValue"
            :placeholder="t('数值')"
            class="full-width-input-number"
            :min="0"
            :step="0.01"
          />
          <div class="detail-text-row">
            <span>
              {{
                localAction.config.roasAction === 'increase'
                  ? t('且新最高不超过')
                  : t('且新最低不小于')
              }}
            </span>
          </div>
          <div class="detail-row">
            <a-input-number
              v-model:value="localAction.config.roasLimit"
              :placeholder="t('数值')"
              class="detail-input-number"
              :min="0"
              :step="0.01"
            />
          </div>
        </div>
      </template>

      <template v-if="localAction.type === 'edit-tag'">
        <div class="detail-stack">
          <a-select
            v-model:value="localAction.config.tagAction"
            :placeholder="t('设置方式')"
            class="full-width-select"
          >
            <a-select-option value="add">{{ t('添加标签') }}</a-select-option>
            <a-select-option value="remove">{{ t('移除标签') }}</a-select-option>
            <a-select-option value="replace">{{ t('替换标签') }}</a-select-option>
          </a-select>

          <div v-if="localAction.config.tagAction === 'replace'">
            <div class="detail-label">{{ t('将标签设置为') }}</div>
            <a-select
              v-model:value="localAction.config.newTag"
              :placeholder="t('请选择')"
              class="full-width-select"
              allow-clear
            />
          </div>
        </div>
      </template>

      <template
        v-if="
          localAction.type === 'send-dingtalk' ||
          localAction.type === 'send-wecom' ||
          localAction.type === 'send-feishu'
        "
      >
        <a-input
          v-model:value="localAction.config.imSubject"
          :placeholder="t('消息主题')"
          class="full-width-input"
        />
        <a-textarea
          v-model:value="localAction.config.imMessage"
          :placeholder="t('消息内容')"
          :rows="3"
          class="full-width-textarea"
        />
      </template>
    </div>
  </div>
</template>

<script lang="ts" setup>
import { PlusOutlined } from '@ant-design/icons-vue';
import { defineComponent, nextTick, onMounted, ref, watch, type Ref } from 'vue';
import { useI18n } from 'vue-i18n';

const VNodes = defineComponent({
  props: {
    vnodes: {
      type: Object,
      required: true,
    },
  },
  render() {
    return this.vnodes;
  },
});

interface Props {
  action: any;
}

const props = defineProps<Props>();
const emit = defineEmits(['update']);

const { t } = useI18n();

type ActionType =
  | 'status'
  | 'bid'
  | 'budget'
  | 'total-budget'
  | 'roas-target'
  | 'edit-tag'
  | 'send-email'
  | 'send-dingtalk'
  | 'send-wecom'
  | 'send-feishu';

type ActionConfig = {
  recipients: string[];
  subject: string;
  body: string;
  status: string;
  bidAction: string;
  bidValue?: number;
  bidLimit?: number;
  budgetAction: string;
  budgetValue?: number;
  budgetLimit?: number;
  budgetStrategy: string;
  roasAction: string;
  roasValue?: number;
  roasLimit?: number;
  tag?: string;
  tagAction: string;
  newTag?: string;
  /** 钉钉 / 企微 / 飞书 Webhook 或地址，可多选 */
  webhookUrls: string[];
  /** 群消息标题（与消息内容对应，切换 IM 类型时保留） */
  imSubject: string;
  imMessage: string;
};

type LocalAction = {
  type: ActionType;
  config: ActionConfig;
};

const toOptionalNumber = (v: unknown): number | undefined => {
  if (v === undefined || v === null || v === '') return undefined;
  const n = Number(v);
  return Number.isFinite(n) ? n : undefined;
};

function normalizeWebhookUrls(config: any): string[] {
  if (config?.webhookUrls && Array.isArray(config.webhookUrls)) {
    return [...config.webhookUrls];
  }
  if (config?.webhookUrl) {
    return [String(config.webhookUrl)];
  }
  return [];
}

/** 完整默认配置；切换动作类型时在上一版 config 上合并，不丢字段 */
const createBaseConfig = (): ActionConfig => ({
  recipients: [],
  subject: '',
  body: '',
  status: 'pause',
  bidAction: 'increase',
  bidValue: undefined,
  bidLimit: undefined,
  budgetAction: 'increase',
  budgetValue: undefined,
  budgetLimit: undefined,
  budgetStrategy: 'continue',
  roasAction: 'increase',
  roasValue: undefined,
  roasLimit: undefined,
  tag: undefined,
  tagAction: 'add',
  newTag: undefined,
  webhookUrls: [],
  imSubject: '',
  imMessage: '',
});

const configFromProps = (): ActionConfig => {
  const c = props.action?.config;
  if (!c) return createBaseConfig();
  return {
    ...createBaseConfig(),
    ...c,
    recipients: Array.isArray(c.recipients) ? [...c.recipients] : [],
    webhookUrls: normalizeWebhookUrls(c),
    bidValue: toOptionalNumber(c.bidValue),
    bidLimit: toOptionalNumber(c.bidLimit),
    budgetValue: toOptionalNumber(c.budgetValue),
    budgetLimit: toOptionalNumber(c.budgetLimit),
    roasValue: toOptionalNumber(c.roasValue),
    roasLimit: toOptionalNumber(c.roasLimit),
  };
};

const initialType = (props.action?.type as ActionType) || 'send-email';

const localAction = ref<LocalAction>({
  type: initialType,
  config: configFromProps(),
});

// 组件挂载后主动从props同步一次数据，确保编辑回显正确
onMounted(() => {
  const newType = (props.action?.type as ActionType) || 'send-email';
  const newConfig = configFromProps();
  localAction.value = {
    type: newType,
    config: newConfig,
  };
  if (props.action?.config?.recipients?.length) {
    mergeIntoOptions(emailRecipientOptions, props.action.config.recipients);
  }
  if (props.action?.config?.webhookUrls?.length) {
    mergeIntoOptions(imTargetOptions, props.action.config.webhookUrls);
  }
});

const emailRecipientOptions = ref<string[]>([]);
const newEmailDraft = ref('');
const emailAddInputRef = ref<{ focus?: () => void } | null>(null);

const imTargetOptions = ref<string[]>([]);
const newImTargetDraft = ref('');
const imAddInputRef = ref<{ focus?: () => void } | null>(null);

const mergeIntoOptions = (pool: Ref<string[]>, values: string[]) => {
  values.forEach((v) => {
    if (v && !pool.value.includes(v)) pool.value.push(v);
  });
};

const filterSendTargetOption = (input: string, option: { label?: string; value?: string }) => {
  const q = (input || '').toLowerCase();
  const label = String(option?.label ?? option?.value ?? '');
  return label.toLowerCase().includes(q);
};

const addEmailRecipientOption = (e?: Event) => {
  e?.preventDefault();
  const raw = newEmailDraft.value?.trim();
  if (!raw) return;
  mergeIntoOptions(emailRecipientOptions, [raw]);
  if (!localAction.value.config.recipients.includes(raw)) {
    localAction.value.config.recipients = [...localAction.value.config.recipients, raw];
  }
  newEmailDraft.value = '';
  nextTick(() => {
    emailAddInputRef.value?.focus?.();
  });
};

const addImTargetOption = (e?: Event) => {
  e?.preventDefault();
  const raw = newImTargetDraft.value?.trim();
  if (!raw) return;
  mergeIntoOptions(imTargetOptions, [raw]);
  if (!localAction.value.config.webhookUrls.includes(raw)) {
    localAction.value.config.webhookUrls = [...localAction.value.config.webhookUrls, raw];
  }
  newImTargetDraft.value = '';
  nextTick(() => {
    imAddInputRef.value?.focus?.();
  });
};

watch(
  () => localAction.value.config.recipients,
  (list) => mergeIntoOptions(emailRecipientOptions, list || []),
  { deep: true, immediate: true },
);

watch(
  () => localAction.value.config.webhookUrls,
  (list) => mergeIntoOptions(imTargetOptions, list || []),
  { deep: true, immediate: true },
);

const handleTypeChange = () => {
  const prev = localAction.value.config;
  localAction.value.config = {
    ...createBaseConfig(),
    ...prev,
    recipients: [...(prev.recipients ?? [])],
    webhookUrls: [...(prev.webhookUrls ?? [])],
  };
  newEmailDraft.value = '';
  newImTargetDraft.value = '';
  emit('update', localAction.value);
};

watch(
  () => localAction.value.config.recipients,
  () => { emit('update', localAction.value); },
  { deep: true },
);
watch(
  () => localAction.value.config.subject,
  () => { emit('update', localAction.value); },
);
watch(
  () => localAction.value.config.body,
  () => { emit('update', localAction.value); },
);
watch(
  () => localAction.value.config.status,
  () => { emit('update', localAction.value); },
);
watch(
  () => localAction.value.config.bidAction,
  () => { emit('update', localAction.value); },
);
watch(
  () => localAction.value.config.bidValue,
  () => { emit('update', localAction.value); },
);
watch(
  () => localAction.value.config.bidLimit,
  () => { emit('update', localAction.value); },
);
watch(
  () => localAction.value.config.budgetAction,
  () => { emit('update', localAction.value); },
);
watch(
  () => localAction.value.config.budgetValue,
  () => { emit('update', localAction.value); },
);
watch(
  () => localAction.value.config.budgetLimit,
  () => { emit('update', localAction.value); },
);
watch(
  () => localAction.value.config.budgetStrategy,
  () => { emit('update', localAction.value); },
);
watch(
  () => localAction.value.config.roasAction,
  () => { emit('update', localAction.value); },
);
watch(
  () => localAction.value.config.roasValue,
  () => { emit('update', localAction.value); },
);
watch(
  () => localAction.value.config.roasLimit,
  () => { emit('update', localAction.value); },
);
watch(
  () => localAction.value.config.tag,
  () => { emit('update', localAction.value); },
);
watch(
  () => localAction.value.config.tagAction,
  () => { emit('update', localAction.value); },
);
watch(
  () => localAction.value.config.newTag,
  () => { emit('update', localAction.value); },
);
watch(
  () => localAction.value.config.webhookUrls,
  () => { emit('update', localAction.value); },
  { deep: true },
);
watch(
  () => localAction.value.config.imSubject,
  () => { emit('update', localAction.value); },
);
watch(
  () => localAction.value.config.imMessage,
  () => { emit('update', localAction.value); },
);
</script>

<style lang="less" scoped>
.action-config {
  width: 100%;

  .action-top-row {
    display: flex;
    align-items: flex-start;
    gap: 16px;
  }

  .action-type-select {
    width: 180px;
    flex-shrink: 0;
  }

  .primary-area {
    flex: 1;
    min-width: 0;
  }

  .primary-prefix {
    display: inline-block;
    margin-right: 8px;
    color: #666;
  }

  .primary-select {
    width: 100%;
  }

  .recipient-multi-select {
    width: 100%;
  }

  .select-add-row {
    width: 100%;
    padding: 4px 8px;
    display: flex;
    align-items: center;
    gap: 4px;

    :deep(.ant-input) {
      flex: 1;
      min-width: 0;
    }
  }

  .action-details {
    margin-top: 12px;
    display: flex;
    flex-direction: column;
    gap: 12px;
  }

  .full-width-input {
    width: 100%;
  }

  .full-width-textarea {
    width: 100%;
  }

  .full-width-input-number {
    width: 100%;
  }

  .full-width-select {
    width: 100%;
  }

  .detail-stack {
    display: flex;
    flex-direction: column;
    gap: 12px;
  }

  .detail-text-row {
    color: #666;
    font-size: 12px;
    line-height: 1.4;
  }

  .detail-row {
    display: flex;
    align-items: center;
    gap: 8px;
  }

  .detail-input-number {
    flex: 1;
    width: 100%;
  }

  .detail-suffix {
    color: #666;
    flex-shrink: 0;
  }

  .budget-strategy {
    .strategy-title {
      font-weight: 500;
      margin-bottom: 8px;
      color: #333;
    }
  }

  .detail-label {
    color: #666;
    font-size: 12px;
    margin-bottom: 6px;
  }

  .action-help-row {
    margin-top: -6px; // 贴近上方正文输入，但保持视觉层次
  }

  .action-help-link {
    font-size: 12px;
    color: #999;
    text-decoration: none;

    &:hover {
      color: #666;
      text-decoration: underline;
    }
  }
}
</style>

