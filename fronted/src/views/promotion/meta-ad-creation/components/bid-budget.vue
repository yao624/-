<template>
  <div class="bid-budget">
    <h2 class="section-title">{{ t('出价和预算') }}</h2>
    <a-form :model="localFormData" layout="vertical">
      <a-form-item :label="t('成效目标')">
        <a-radio-group v-model:value="localFormData.performanceGoal">
          <a-radio-button value="app_events">{{ t('应用事件数量最大化') }}</a-radio-button>
          <a-radio-button value="link_clicks">{{ t('链接点击量最大化') }}</a-radio-button>
          <a-radio-button value="reach">{{ t('单日独立覆盖人数最大化') }}</a-radio-button>
        </a-radio-group>
      </a-form-item>
      <a-form-item :label="t('应用事件') + ' *'" required>
        <a-select v-model:value="localFormData.appEvent" :placeholder="t('请选择推广应用')">
          <a-select-option v-for="event in appEvents" :key="event.id" :value="event.id">
            {{ event.name }}
          </a-select-option>
        </a-select>
      </a-form-item>
      <a-form-item :label="t('计费方式')">
        <a-radio-group v-model:value="localFormData.billingMethod">
          <a-radio-button value="impressions">{{ t('展示次数') }}</a-radio-button>
          <a-radio-button value="cpc">{{ t('链接点击量(CPC)') }}</a-radio-button>
        </a-radio-group>
      </a-form-item>
      <a-form-item :label="t('排期')">
        <a-radio-group v-model:value="localFormData.schedule">
          <a-radio-button value="now">{{ t('现在开始') }}</a-radio-button>
          <a-radio-button value="custom">{{ t('自定义') }}</a-radio-button>
        </a-radio-group>
        <div v-if="localFormData.schedule === 'custom'" class="schedule-custom">
          <a-form-item :label="t('开始时间') + ' *'" required>
            <a-space>
              <a-date-picker
                v-model:value="localFormData.startDate"
                :placeholder="t('选择日期')"
                style="width: 150px"
              />
              <a-time-picker
                v-model:value="localFormData.startTime"
                :placeholder="t('选择时间')"
                format="HH:mm"
                style="width: 120px"
              />
              <span class="timezone-label">Asia/Shanghai</span>
            </a-space>
          </a-form-item>
          <a-form-item :label="t('结束时间') + ' *'" required>
            <a-space>
              <a-date-picker
                v-model:value="localFormData.endDate"
                :placeholder="t('选择日期')"
                style="width: 150px"
              />
              <a-time-picker
                v-model:value="localFormData.endTime"
                :placeholder="t('选择时间')"
                format="HH:mm"
                style="width: 120px"
              />
              <span class="timezone-label">Asia/Shanghai</span>
            </a-space>
          </a-form-item>
        </div>
      </a-form-item>
      <a-form-item :label="t('广告组花费限额')">
        <a-input-group compact>
          <a-input-number
            v-model:value="localFormData.lowerLimit"
            :min="0"
            :precision="2"
            style="width: 45%"
            :placeholder="t('下限')"
          />
          <span style="width: 10%; text-align: center; line-height: 32px">~</span>
          <a-input-number
            v-model:value="localFormData.upperLimit"
            :min="0"
            :precision="2"
            style="width: 45%"
            :placeholder="t('上限')"
          />
        </a-input-group>
        <span style="margin-left: 8px">CNY</span>
      </a-form-item>
    </a-form>

    <h2 class="section-title" style="margin-top: 32px">{{ t('创意设置') }}</h2>
    <a-form :model="localFormData" layout="vertical">
      <a-form-item :label="t('广告名称') + ' *'" required>
        <a-input v-model:value="localFormData.adName" :placeholder="t('请输入')" />
      </a-form-item>
      <a-form-item :label="t('广告状态')">
        <a-switch v-model:checked="localFormData.adStatus" />
      </a-form-item>
      <a-form-item :label="t('主页类型')">
        <a-radio-group v-model:value="localFormData.pageType">
          <a-radio value="all">{{ t('全部主页') }}</a-radio>
          <a-radio value="personal">{{ t('个人号主页') }}</a-radio>
          <a-radio value="ad_account">{{ t('广告账户主页') }}</a-radio>
        </a-radio-group>
      </a-form-item>
      <a-form-item :label="t('Facebook公共主页') + ' *'" required>
        <a-select v-model:value="localFormData.fbPage" :placeholder="t('请选择')">
          <a-select-option v-for="page in fbPages" :key="page.id" :value="page.id">
            {{ page.name }}
          </a-select-option>
        </a-select>
        <a-button type="link" size="small">{{ t('找不到主页?') }}</a-button>
      </a-form-item>
    </a-form>
  </div>
</template>

<script lang="ts" setup>
import { ref, watch } from 'vue';
import { useI18n } from 'vue-i18n';
import dayjs, { Dayjs } from 'dayjs';
import { getAppEvents, getFbPages } from '../mock-data';

interface Props {
  formData: any;
}

interface Emits {
  (e: 'update:formData', value: any): void;
}

const props = defineProps<Props>();
const emit = defineEmits<Emits>();
const { t } = useI18n();

const localFormData = ref({
  performanceGoal: 'app_events',
  appEvent: undefined,
  billingMethod: 'impressions',
  schedule: 'now',
  startDate: dayjs('2026-01-05'),
  startTime: dayjs('16:07', 'HH:mm'),
  endDate: undefined,
  endTime: undefined,
  lowerLimit: undefined,
  upperLimit: undefined,
  adName: '',
  adStatus: true,
  pageType: 'all',
  fbPage: undefined,
  ...props.formData,
});

const appEvents = ref<any[]>([]);
const fbPages = ref<any[]>([]);

watch(
  localFormData,
  (newVal) => {
    emit('update:formData', { ...newVal });
  },
  { deep: true },
);

const loadData = async () => {
  try {
    appEvents.value = await getAppEvents();
    fbPages.value = await getFbPages();
  } catch (error) {
    console.error('加载数据失败:', error);
  }
};

loadData();
</script>

<style lang="less" scoped>
.bid-budget {
  .section-title {
    font-size: 18px;
    font-weight: 500;
    margin-bottom: 24px;
  }

  .schedule-custom {
    margin-top: 16px;
    padding: 16px;
    background: #fafafa;
    border-radius: 4px;
  }

  .timezone-label {
    color: #666;
    font-size: 12px;
    line-height: 32px;
  }
}
</style>

