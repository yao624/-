<template>
  <div class="step-two-campaign">
    <h3 class="section-title">{{ t('广告系列') }}</h3>
    <a-form layout="vertical" @submit.prevent>
      <a-form-item :label="t('广告系列名称') + ' *'">
        <a-input v-model:value="localFormData.campaignName" :placeholder="t('请输入')" allow-clear />
        <div class="name-tags">
          <a-button
            type="default"
            html-type="button"
            size="small"
            v-for="tag in nameTagsShort"
            :key="tag"
            @click.prevent.stop="onNameTagClick(tag)"
          >
            {{ tag }}
          </a-button>
          <a
            type="link"
            size="small"
            @click="nameTagsExpanded = !nameTagsExpanded"
          >
            {{ nameTagsExpanded ? t('折叠') : t('展开') }}
          </a>
        </div>
        <div v-show="nameTagsExpanded" class="name-tags name-tags-expanded">
          <a-button
            type="default"
            html-type="button"
            size="small"
            v-for="tag in nameTagsFull"
            :key="tag"
            @click.prevent.stop="onNameTagClick(tag)"
          >
            {{ tag }}
          </a-button>
        </div>
      </a-form-item>
      <a-form-item :label="t('广告系列状态')">
        <a-switch v-model:checked="localFormData.campaignStatus" />
      </a-form-item>
      <a-form-item :label="t('特殊广告类别')">
        <a-select
          v-model:value="localFormData.specialCategory"
          :placeholder="t('请选择')"
          style="width: 100%"
          allow-clear
          :options="specialCategoryOptions"
        />
      </a-form-item>
      <a-form-item :label="t('赋能型广告系列预算优化')">
        <a-switch v-model:checked="localFormData.cbo" />
      </a-form-item>
      <a-form-item :label="t('广告系列预算')">
        <a-radio-group v-model:value="localFormData.budgetType">
          <a-radio-button value="daily">{{ t('单日预算') }}</a-radio-button>
          <a-radio-button value="lifetime">{{ t('总预算') }}</a-radio-button>
        </a-radio-group>
        <a-input-number
          v-model:value="localFormData.budget"
          :min="0"
          :precision="2"
          style="width: 200px; margin-top: 8px"
          :placeholder="t('请输入')"
        />
        <span style="margin-left: 8px">USD</span>
      </a-form-item>
      <a-form-item :label="t('广告系列竞价策略')">
        <a-radio-group v-model:value="localFormData.bidStrategy">
          <a-radio-button value="HIGHEST_VOLUME">{{ t('最高数量') }}</a-radio-button>
          <a-radio-button value="COST_PER_RESULT">{{ t('单次成效费用目标') }}</a-radio-button>
          <a-radio-button value="BID_CAP">{{ t('竞价上限') }}</a-radio-button>
          <a-radio-button value="ROAS">{{ t('广告花费回报目标') }}</a-radio-button>
        </a-radio-group>
        <!-- 与 Meta 一致：按策略展示必填/选填出价参数 -->
        <div v-if="localFormData.bidStrategy === 'COST_PER_RESULT'" class="bid-sub">
          <a-form-item :label="t('目标单次成效费用')" class="nested-item">
            <a-input-number
              v-model:value="localFormData.costPerResultTarget"
              :min="0.01"
              :precision="2"
              style="width: 200px"
              :placeholder="t('请输入')"
            />
            <span class="unit-suffix">USD</span>
          </a-form-item>
          <a-alert
            type="info"
            show-icon
            class="inline-tip"
            :message="t('系统会尽量将单次成效费用控制在该目标附近，实际成效可能波动。')"
          />
        </div>
        <div v-if="localFormData.bidStrategy === 'BID_CAP'" class="bid-sub">
          <a-form-item :label="t('竞价上限金额')" class="nested-item">
            <a-input-number
              v-model:value="localFormData.bidCapAmount"
              :min="0.01"
              :precision="2"
              style="width: 200px"
              :placeholder="t('请输入')"
            />
            <span class="unit-suffix">USD</span>
          </a-form-item>
        </div>
        <div v-if="localFormData.bidStrategy === 'ROAS'" class="bid-sub">
          <a-form-item :label="t('广告花费回报目标(ROAS)')" class="nested-item">
            <a-input-number
              v-model:value="localFormData.roasTarget"
              :min="0.01"
              :step="0.01"
              :precision="2"
              style="width: 200px"
              :placeholder="t('例如 2 表示 2:1')"
            />
          </a-form-item>
          <a-alert
            type="info"
            show-icon
            class="inline-tip"
            :message="t('ROAS 为广告带来的购物转化价值与广告花费之比，例如填 2 表示期望每花 1 元带来约 2 元转化价值。')"
          />
        </div>
      </a-form-item>
      <a-form-item :label="t('投放时段')">
        <a-radio-group v-model:value="localFormData.schedule">
          <a-radio-button value="all_day">{{ t('全天投放广告') }}</a-radio-button>
          <a-radio-button value="time_slot">{{ t('分时间段投放') }}</a-radio-button>
        </a-radio-group>
        <div v-if="localFormData.schedule === 'time_slot'" class="schedule-sub">
          <a-form-item :label="t('投放日期范围')" class="nested-item">
            <a-range-picker
              v-model:value="scheduleRangeValue"
              style="width: 100%; max-width: 360px"
              :placeholder="[t('开始日期'), t('结束日期')]"
            />
          </a-form-item>
          <a-form-item :label="t('每日投放时段')" class="nested-item">
            <a-space wrap>
              <a-time-picker
                v-model:value="dailyStartTimeModel"
                format="HH:mm"
                :placeholder="t('开始时间')"
              />
              <span>—</span>
              <a-time-picker
                v-model:value="dailyEndTimeModel"
                format="HH:mm"
                :placeholder="t('结束时间')"
              />
            </a-space>
          </a-form-item>
          <a-alert
            type="info"
            show-icon
            class="inline-tip"
            :message="t('仅在所选日期范围内、每日指定时段内投放；与 Meta 广告组排期对应，提交时由后端映射为 API 字段。')"
          />
        </div>
      </a-form-item>
      <a-form-item :label="t('投放类型')">
        <a-radio-group v-model:value="localFormData.deliveryType">
          <a-radio-button value="standard">{{ t('匀速') }}</a-radio-button>
          <a-radio-button value="accelerated">{{ t('加速') }}</a-radio-button>
        </a-radio-group>
        <a-alert
          v-if="localFormData.deliveryType === 'accelerated'"
          type="warning"
          show-icon
          class="inline-tip"
          :message="t('加速投放会尽快花完预算，适用于促销、直播等时间敏感场景；与「匀速」相比可能更快耗尽单日预算。')"
        />
      </a-form-item>
      <a-form-item :label="t('广告系列花费限额')">
        <a-radio-group v-model:value="localFormData.spendLimit">
          <a-radio-button value="unlimited">{{ t('不限') }}</a-radio-button>
          <a-radio-button value="custom">{{ t('自定义') }}</a-radio-button>
        </a-radio-group>
        <div v-if="localFormData.spendLimit === 'custom'" class="spend-sub">
          <a-form-item :label="t('花费限额上限')" class="nested-item">
            <a-input-number
              v-model:value="localFormData.customSpendCap"
              :min="0.01"
              :precision="2"
              style="width: 200px"
              :placeholder="t('请输入')"
            />
            <span class="unit-suffix">USD</span>
          </a-form-item>
          <a-alert
            type="info"
            show-icon
            class="inline-tip"
            :message="t('自定义限额为广告系列层级可花费的上限（与单日/总预算不同）；具体是否生效取决于账户是否开启 CBO 等条件。')"
          />
        </div>
        <a-alert
          type="warning"
          show-icon
          class="spend-tip"
          :message="t('花费限额不是预算,如需设置广告系列预算请开启赋能型广告系列预算优化(CBO)')"
        />
      </a-form-item>
    </a-form>
  </div>
</template>

<script lang="ts" setup>
import { ref, watch, nextTick, computed } from 'vue';
import { useI18n } from 'vue-i18n';
import type { Dayjs } from 'dayjs';
import dayjs from 'dayjs';

const { t } = useI18n();
const props = defineProps<{ formData: any }>();
const emit = defineEmits<{ (e: 'update:form-data', value: any): void }>();

/** 与父组件 index.vue 监听 @update:form-data 一致 */
function emitToParent() {
  emit('update:form-data', { ...localFormData.value });
}

const specialCategoryOptions = [
  { label: t('无'), value: 'none' },
  { label: t('就业'), value: 'employment' },
  { label: t('住房'), value: 'housing' },
  { label: t('信贷'), value: 'credit' },
  { label: t('社会议题、选举或政治'), value: 'issues_elections_politics' },
];

const nameTagsShort = [t('系统用户名'), t('账户名'), t('账户备注名'), t('地区组名称'), t('定向包名称')];
const nameTagsFull = [t('创意组名称'), t('首个素材名称(含格式)'), t('首个素材备注'), t('地区'), t('地区2(示例:CN)'), t('地区3(示例:CHN)'), t('语言'), t('创建日期(yyyy/mm/dd)'), t('开始日期(yyyy/mm/dd)'), t('时分秒'), t('开始时间'), t('广告目标'), 'App OS', t('原名称'), t('账户ID'), t('序号'), t('推广应用'), t('首个素材文件夹名称')];
const nameTagsExpanded = ref(false);
const defaultData = {
  campaignName: '',
  campaignStatus: true,
  specialCategory: undefined,
  cbo: true,
  budgetType: 'daily',
  budget: null as number | null,
  bidStrategy: 'HIGHEST_VOLUME',
  costPerResultTarget: null as number | null,
  bidCapAmount: null as number | null,
  roasTarget: null as number | null,
  schedule: 'all_day',
  scheduleStartDate: null as string | null,
  scheduleEndDate: null as string | null,
  dailyScheduleStart: null as string | null,
  dailyScheduleEnd: null as string | null,
  deliveryType: 'standard',
  spendLimit: 'unlimited',
  customSpendCap: null as number | null,
};
const localFormData = ref({ ...defaultData, ...props.formData });

/** 分时段：日期范围 ↔ YYYY-MM-DD */
const scheduleRangeValue = computed<[Dayjs, Dayjs] | null>({
  get() {
    const s = localFormData.value.scheduleStartDate;
    const e = localFormData.value.scheduleEndDate;
    if (s && e) return [dayjs(s), dayjs(e)];
    return null;
  },
  set(v) {
    if (!v?.[0] || !v?.[1]) {
      localFormData.value.scheduleStartDate = null;
      localFormData.value.scheduleEndDate = null;
    } else {
      localFormData.value.scheduleStartDate = v[0].format('YYYY-MM-DD');
      localFormData.value.scheduleEndDate = v[1].format('YYYY-MM-DD');
    }
  },
});

const TIME_ANCHOR = '2000-01-01';
/** 分时段：每日开始时间 ↔ HH:mm */
const dailyStartTimeModel = computed<Dayjs | null>({
  get() {
    const s = localFormData.value.dailyScheduleStart;
    return s ? dayjs(`${TIME_ANCHOR} ${s}`) : null;
  },
  set(v) {
    localFormData.value.dailyScheduleStart = v ? v.format('HH:mm') : null;
  },
});
const dailyEndTimeModel = computed<Dayjs | null>({
  get() {
    const e = localFormData.value.dailyScheduleEnd;
    return e ? dayjs(`${TIME_ANCHOR} ${e}`) : null;
  },
  set(v) {
    localFormData.value.dailyScheduleEnd = v ? v.format('HH:mm') : null;
  },
});

/** 从父级回填时不同步 emit，避免 watch 死循环 */
const syncingFromParent = ref(false);

watch(
  () => props.formData,
  (newVal) => {
    if (!newVal || typeof newVal !== 'object') return;
    syncingFromParent.value = true;
    localFormData.value = { ...defaultData, ...newVal };
    nextTick(() => {
      syncingFromParent.value = false;
    });
  },
  { deep: true },
);

watch(
  localFormData,
  () => {
    if (syncingFromParent.value) return;
    emitToParent();
  },
  { deep: true },
);

// 点击标签：追加占位片段到名称（与 Meta 动态命名习惯接近）
const onNameTagClick = (tag: string) => {
  const cur = (localFormData.value.campaignName || '').trim();
  const piece = `[${tag}]`;
  localFormData.value.campaignName = cur ? `${cur}${piece}` : piece;
};
</script>

<style lang="less" scoped>
.step-two-campaign {
  position: relative;
  z-index: 1;
  pointer-events: auto;

  .section-title {
    font-size: 16px;
    font-weight: 500;
    margin-bottom: 24px;
    color: #262626;
  }

  .name-tags {
    margin-top: 8px;
    display: flex;
    flex-wrap: wrap;
    gap: 8px;
    align-items: center;
  }

  .name-tags-expanded {
    margin-top: 4px;
  }

  .spend-tip {
    margin-top: 8px;
  }

  .bid-sub,
  .schedule-sub,
  .spend-sub {
    margin-top: 12px;
    width: 100%;
  }

  .nested-item {
    margin-bottom: 8px;
  }

  .inline-tip {
    margin-top: 8px;
  }

  .unit-suffix {
    margin-left: 8px;
    color: #8c8c8c;
  }
}
</style>
