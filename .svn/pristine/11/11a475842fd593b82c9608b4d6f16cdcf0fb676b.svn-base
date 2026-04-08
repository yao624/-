<template>
  <div class="step-delivery">
    <h3 class="section-title">{{ t('投放内容') }}</h3>
    <a-form layout="vertical" @submit.prevent>
      <a-form-item :label="t('广告组名称') + ' *'">
        <a-input v-model:value="local.name" :placeholder="t('请输入')" allow-clear />
        <div class="name-tags">
          <a-button
            v-for="tag in nameTagsShort"
            :key="tag"
            type="default"
            size="small"
            html-type="button"
            @click.prevent.stop="onNameTagClick(tag)"
          >
            {{ tag }}
          </a-button>
          <a type="link" size="small" @click="nameTagsExpanded = !nameTagsExpanded">
            {{ nameTagsExpanded ? t('折叠') : t('展开') }}
          </a>
        </div>
        <div v-show="nameTagsExpanded" class="name-tags name-tags-expanded">
          <a-button
            v-for="tag in nameTagsFull"
            :key="tag"
            type="default"
            size="small"
            html-type="button"
            @click.prevent.stop="onNameTagClick(tag)"
          >
            {{ tag }}
          </a-button>
        </div>
      </a-form-item>

      <a-form-item :label="t('广告组状态')">
        <a-switch v-model:checked="local.status" :checked-children="t('开启')" :un-checked-children="t('关闭')" />
        <div class="field-hint">{{ t('对应 Meta 广告组创建后的状态：开启=ACTIVE，关闭=PAUSED') }}</div>
      </a-form-item>

      <!-- 网站转化：像素/主页在「基础设置」；此处补充说明与可选推广链接（写入模板备注与 launch options） -->
      <template v-if="!isAppConversion">
        <a-alert type="info" show-icon class="block-alert">
          <template #message>
            {{ t('当前为「网站」转化：请在第 1 步「基础设置」中选择像素与公共主页；网站转化事件在第 7 步「创意设置」中选择。') }}
          </template>
        </a-alert>
        <a-form-item :label="t('推广网址（可选）')">
          <a-input
            v-model:value="local.promoteWebsiteUrl"
            :placeholder="t('https://example.com/landing')"
            allow-clear
          />
          <div class="field-hint">
            {{ t('用于运营备注与后续扩展；创建广告创意时仍可能需单独配置落地链接。') }}
          </div>
        </a-form-item>
      </template>

      <!-- 应用转化：应用商店 + 已订阅应用 -->
      <template v-else>
        <a-form-item :label="t('应用商店')">
          <a-radio-group v-model:value="local.store">
            <a-radio-button value="google">Google Play</a-radio-button>
            <a-radio-button value="apple">App Store</a-radio-button>
            <a-radio-button value="samsung">Samsung Galaxy Store</a-radio-button>
            <a-radio-button value="amazon">{{ t('亚马逊应用商店') }}</a-radio-button>
          </a-radio-group>
        </a-form-item>
        <a-form-item :label="t('应用') + ' *'">
          <a-select
            v-model:value="local.app"
            :placeholder="t('请选择')"
            style="width: 100%"
            allow-clear
            show-search
            :filter-option="filterAppOption"
            :loading="appsLoading"
          >
            <a-select-option v-for="a in appList" :key="a.id" :value="a.id">
              {{ a.name }} ({{ a.source_id }})
            </a-select-option>
          </a-select>
          <div v-if="!appsLoading && appList.length === 0 && adAccountId" class="hint">
            {{ t('当前广告账户暂无已订阅应用，请先在后台将应用订阅到该广告账户') }}
          </div>
        </a-form-item>
      </template>
    </a-form>
  </div>
</template>

<script lang="ts" setup>
import { ref, watch, onMounted, computed, nextTick } from 'vue';
import { useI18n } from 'vue-i18n';
import { queryFB_AD_AccountOneApi } from '@/api/fb_ad_accounts';

const { t } = useI18n();

const props = defineProps<{
  formData: any;
  adAccountId?: string | null;
  /** 与 stepOne.conversionLocation 一致：app / website */
  conversionLocation?: string;
}>();
const emit = defineEmits<{ (e: 'update:form-data', v: any): void }>();

/** 仅明确为 app 时展示应用商店/应用；勿用 undefined 默认成 app（否则父级未同步时会误显应用区） */
const isAppConversion = computed(() => {
  const loc = (props.conversionLocation ?? '').toString().trim().toLowerCase();
  return loc === 'app';
});

const nameTagsShort = [t('系统用户名'), t('账户名'), t('账户备注名'), t('地区组名称'), t('定向包名称'), t('创意组名称')];
const nameTagsFull = [
  t('首个素材名称'),
  t('首个素材名称(含格式)'),
  t('首个素材备注'),
  t('地区'),
  t('地区2(示例:CN)'),
  t('地区3(示例:CHN)'),
  t('语言'),
  t('创建日期(yyyy/mm/dd)'),
  t('创建日期(yyyymmdd)'),
  t('时分秒'),
  t('开始日期(yyyy/mm/dd)'),
  t('开始日期(yyyymmdd)'),
  t('开始时间'),
  'App OS',
  t('性别'),
  t('年龄'),
  t('出价'),
  t('成效目标'),
  t('版位'),
  t('原名称'),
  t('账户ID'),
  t('序号'),
  t('推广应用'),
  t('广告系列名称'),
  t('首个素材文件夹名称'),
];
const nameTagsExpanded = ref(false);

const defaultLocal = () => ({
  name: '',
  status: true,
  store: 'google',
  app: undefined as string | undefined,
  promoteWebsiteUrl: '',
});

const local = ref({ ...defaultLocal(), ...props.formData });

const appList = ref<{ id: string; name: string; source_id: string }[]>([]);
const appsLoading = ref(false);

const filterAppOption = (input: string, option: any) => {
  const val = option?.value as string | undefined;
  const a = appList.value.find((x) => x.id === val);
  if (!a) return false;
  const q = input.toLowerCase();
  return a.name.toLowerCase().includes(q) || String(a.source_id).includes(q);
};

function onNameTagClick(tag: string) {
  const cur = (local.value.name || '').trim();
  const piece = `[${tag}]`;
  local.value.name = cur ? `${cur}${piece}` : piece;
}

const setupMockApps = () => {
  const mockAppId = '00000000000000000000000006';
  appList.value = [{ id: mockAppId, name: t('测试应用（本地模拟）'), source_id: '123456789012345' }];
  if (!local.value.app) {
    local.value.app = mockAppId;
  }
};

const loadSubscribedApps = async (accountId: string | null | undefined) => {
  if (!isAppConversion.value || !accountId) {
    appList.value = [];
    return;
  }
  appsLoading.value = true;
  try {
    const detail: any = await queryFB_AD_AccountOneApi({
      id: accountId,
      'with-campaign': false,
      'with-apps': true,
    });
    const acc = detail?.data ?? detail;
    const raw = acc?.subscribed_apps ?? detail?.subscribed_apps ?? [];
    const list = Array.isArray(raw) ? raw : [];
    appList.value = list
      .filter((x: any) => x?.id && x?.source_id)
      .map((x: any) => ({ id: x.id, name: x.name || x.source_id, source_id: x.source_id }));
    if (appList.value.length === 0) {
      setupMockApps();
    } else if (!appList.value.find((a) => a.id === local.value.app)) {
      local.value.app = appList.value[0]?.id;
    }
  } catch {
    setupMockApps();
  } finally {
    appsLoading.value = false;
  }
};

const syncingFromParent = ref(false);

watch(
  () => props.formData,
  (v) => {
    if (!v || typeof v !== 'object') return;
    syncingFromParent.value = true;
    local.value = { ...defaultLocal(), ...v };
    // 必须在下一 tick 再关闭开关：否则 local 的 deep watch 会在同步阶段之后执行，此时已为 false，会再次 emit → 父级回写 → 死循环（Maximum recursive updates）
    nextTick(() => {
      syncingFromParent.value = false;
    });
  },
);

watch(
  local,
  (v) => {
    if (syncingFromParent.value) return;
    emit('update:form-data', { ...v });
  },
  { deep: true },
);

watch(
  () => props.adAccountId,
  (id) => {
    loadSubscribedApps(id);
  },
);

watch(
  isAppConversion,
  (app) => {
    if (!app) {
      local.value.app = undefined;
      appList.value = [];
    } else {
      loadSubscribedApps(props.adAccountId);
    }
  },
);

onMounted(() => {
  if (isAppConversion.value) {
    loadSubscribedApps(props.adAccountId);
  }
});
</script>

<style lang="less" scoped>
.step-delivery {
  .section-title {
    font-size: 16px;
    font-weight: 500;
    margin-bottom: 16px;
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
  .hint {
    margin-top: 8px;
    color: #8c8c8c;
    font-size: 12px;
  }
  .field-hint {
    margin-top: 6px;
    color: #8c8c8c;
    font-size: 12px;
  }
  .block-alert {
    margin-bottom: 16px;
  }
}
</style>
