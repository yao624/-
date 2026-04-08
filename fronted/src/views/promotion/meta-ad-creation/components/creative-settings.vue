<template>
  <div class="creative-settings">
    <h2 class="section-title">{{ t('创意设置') }}</h2>
    <a-form :model="localFormData" layout="vertical">
      <a-form-item :label="t('广告名称') + ' *'" required>
        <a-input v-model:value="localFormData.adName" :placeholder="t('请输入')" />
        <div class="dynamic-name-tags">
          <a-tag
            v-for="tag in dynamicNameTags"
            :key="tag"
            class="name-tag"
            @click="insertDynamicName(tag)"
          >
            {{ tag }}
          </a-tag>
          <a-button type="link" size="small" @click="toggleExpand">
            {{ isExpanded ? t('折叠') : t('展开') }}
          </a-button>
        </div>
      </a-form-item>
      <a-form-item :label="t('广告状态')">
        <a-switch v-model:checked="localFormData.adStatus" />
      </a-form-item>
      <a-alert
        :message="t('若您的主页没有全部授权,可能导致无法找到所需主页,建议授权现有和今后的所有主页。详细了解')"
        type="warning"
        show-icon
        style="margin-bottom: 16px"
      />
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
      <a-form-item :label="t('使用公共主页而不是应用名称作为广告发布身份')">
        <a-switch v-model:checked="localFormData.usePageInsteadOfApp" />
      </a-form-item>
      <a-form-item :label="t('多广告主广告')">
        <a-switch v-model:checked="localFormData.multiAdvertiser" />
      </a-form-item>
      <a-form-item :label="t('网站事件追踪')">
        <a-select v-model:value="localFormData.websiteEventTracking" :placeholder="t('请选择')">
          <a-select-option
            v-for="tracking in websiteEventTrackings"
            :key="tracking.id"
            :value="tracking.id"
          >
            {{ tracking.name }}
          </a-select-option>
        </a-select>
      </a-form-item>
      <a-form-item :label="t('创意组')">
        <a-input v-model:value="localFormData.creativeGroup" :placeholder="t('创意组1')" />
      </a-form-item>
    </a-form>
  </div>
</template>

<script lang="ts" setup>
import { ref, computed, watch } from 'vue';
import { useI18n } from 'vue-i18n';
import { getFbPages, getWebsiteEventTrackings } from '../mock-data';

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
  adName: '',
  adStatus: true,
  pageType: 'all',
  fbPage: undefined,
  usePageInsteadOfApp: false,
  multiAdvertiser: true,
  websiteEventTracking: undefined,
  creativeGroup: '创意组1',
  ...props.formData,
});

const isExpanded = ref(false);
const dynamicNameTags = computed(() => {
  const basic = [
    t('首个素材名称'),
    t('系统用户名'),
    t('账户名'),
    t('地区'),
    t('创建日期'),
    t('开始时间'),
    t('行动号召'),
    t('账户ID'),
    t('推广应用'),
    t('广告系列名称'),
    t('广告组名称'),
    t('成效目标'),
    t('首个素材文件夹名称'),
  ];
  return isExpanded.value ? [...basic, t('更多选项...')] : basic;
});

const fbPages = ref<any[]>([]);
const websiteEventTrackings = ref<any[]>([]);

const insertDynamicName = (tag: string) => {
  localFormData.value.adName += `{${tag}}`;
};

const toggleExpand = () => {
  isExpanded.value = !isExpanded.value;
};

watch(
  localFormData,
  (newVal) => {
    emit('update:formData', { ...newVal });
  },
  { deep: true },
);

const loadData = async () => {
  try {
    fbPages.value = await getFbPages();
    websiteEventTrackings.value = await getWebsiteEventTrackings();
  } catch (error) {
    console.error('加载数据失败:', error);
  }
};

loadData();
</script>

<style lang="less" scoped>
.creative-settings {
  .section-title {
    font-size: 18px;
    font-weight: 500;
    margin-bottom: 24px;
  }

  .dynamic-name-tags {
    margin-top: 8px;
    display: flex;
    flex-wrap: wrap;
    gap: 8px;

    .name-tag {
      cursor: pointer;
      user-select: none;

      &:hover {
        background: #e6f7ff;
        border-color: #1890ff;
      }
    }
  }
}
</style>

