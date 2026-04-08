<template>
  <a-modal
    :open="open"
    :title="t('Adset Audience Targeting')"
    width="900px"
    :footer="null"
    @cancel="handleCancel"
    @update:open="handleCancel"
    class="audience-modal"
  >
    <div v-if="loading" class="text-center p-8">
      <a-spin size="large" />
    </div>
    <div v-else class="audience-content">
      <!-- Header Card -->
      <div class="header-card">
        <div class="adset-info">
          <div class="adset-label">{{ t('Adset ID') }}</div>
          <div class="adset-id">{{ adsetData?.adset_id }}</div>
        </div>
        <div class="adset-info">
          <div class="adset-label">{{ t('Adset Name') }}</div>
          <div class="adset-name">{{ adsetData?.adset_name }}</div>
        </div>
      </div>

      <div class="targeting-sections">
        <!-- Geographic Targeting -->
        <div class="section-card">
          <div class="section-header">
            <span class="section-icon">🌍</span>
            <span class="section-title">{{ t('Geographic Targeting') }}</span>
          </div>
          <div class="section-content">
            <div v-if="targeting?.geo_locations?.countries?.length" class="countries-grid">
              <div
                v-for="country in targeting.geo_locations.countries"
                :key="country"
                class="country-item"
              >
                {{ country }}
              </div>
            </div>
            <div v-else class="empty-state">{{ t('No geographic targeting specified') }}</div>
          </div>
        </div>

        <!-- Platform & Placements -->
        <div class="section-card">
          <div class="section-header">
            <span class="section-icon">📱</span>
            <span class="section-title">{{ t('Platform & Placements') }}</span>
          </div>
          <div class="section-content">
            <div v-if="targeting?.publisher_platforms?.length" class="platforms-container">

              <!-- Facebook Platform -->
              <div v-if="targeting.publisher_platforms.includes('facebook')" class="platform-block">
                <div class="platform-header">
                  <div class="platform-logo facebook-logo">f</div>
                  <span class="platform-name">Facebook</span>
                </div>
                <div v-if="targeting.facebook_positions?.length" class="placements-grid">
                  <div
                    v-for="position in targeting.facebook_positions"
                    :key="position"
                    class="placement-item facebook-placement"
                  >
                    {{ formatPlacementName(position) }}
                  </div>
                </div>
                <div v-else class="empty-placements">{{ t('All Facebook placements') }}</div>
              </div>

              <!-- Instagram Platform -->
              <div v-if="targeting.publisher_platforms.includes('instagram')" class="platform-block">
                <div class="platform-header">
                  <div class="platform-logo instagram-logo">
                    <instagram-outlined />
                  </div>
                  <span class="platform-name">Instagram</span>
                </div>
                <div v-if="targeting.instagram_positions?.length" class="placements-grid">
                  <div
                    v-for="position in targeting.instagram_positions"
                    :key="position"
                    class="placement-item instagram-placement"
                  >
                    {{ formatPlacementName(position) }}
                  </div>
                </div>
                <div v-else class="empty-placements">{{ t('All Instagram placements') }}</div>
              </div>

            </div>
            <div v-else class="empty-state">{{ t('No platform targeting specified') }}</div>
          </div>
        </div>

        <!-- Demographics -->
        <div class="section-card">
          <div class="section-header">
            <span class="section-icon">👥</span>
            <span class="section-title">{{ t('Demographics') }}</span>
          </div>
          <div class="section-content">
            <div class="demographics-grid">
              <div class="demo-item">
                <div class="demo-label">{{ t('Age Range') }}</div>
                <div class="demo-value">
                  <span v-if="targeting?.age_min || targeting?.age_max">
                    {{ targeting.age_min || '18' }} - {{ targeting.age_max || '65+' }} {{ t('years') }}
                  </span>
                  <span v-else class="text-gray-500">{{ t('All ages') }}</span>
                </div>
              </div>
              <div class="demo-item">
                <div class="demo-label">
                  {{ t('Languages') }}
                  <a-button
                    type="link"
                    size="small"
                    @click="showLanguageEditModal"
                    class="edit-button"
                  >
                    <edit-outlined />
                    {{ t('Edit') }}
                  </a-button>
                </div>
                <div class="demo-value">
                  <div v-if="languages.length" class="languages-container">
                    <span
                      v-for="lang in languages"
                      :key="lang.label_name"
                      class="language-tag"
                    >
                      {{ lang.english_name }}
                    </span>
                  </div>
                  <span v-else class="text-gray-500">{{ t('All languages') }}</span>
                </div>
              </div>
            </div>
          </div>
        </div>

        <!-- Device & Technical -->
        <div class="section-card">
          <div class="section-header">
            <span class="section-icon">💻</span>
            <span class="section-title">{{ t('Device & Technical Settings') }}</span>
          </div>
          <div class="section-content">
            <div class="tech-grid">
              <div class="tech-item">
                <div class="tech-label">{{ t('Device Platforms') }}</div>
                <div class="tech-value">
                  <div v-if="targeting?.device_platforms?.length" class="device-tags">
                    <span
                      v-for="device in targeting.device_platforms"
                      :key="device"
                      class="device-tag"
                      :class="{ 'mobile': device === 'mobile', 'desktop': device === 'desktop' }"
                    >
                      <span class="device-icon">{{ device === 'mobile' ? '📱' : '🖥️' }}</span>
                      {{ device.charAt(0).toUpperCase() + device.slice(1) }}
                    </span>
                  </div>
                  <span v-else class="text-gray-500">{{ t('All devices') }}</span>
                </div>
              </div>
              <div class="tech-item">
                <div class="tech-label">{{ t('Timezone') }}</div>
                <div class="tech-value">
                  <span v-if="adsetData?.timezone" class="timezone-value">{{ adsetData.timezone }}</span>
                  <span v-else class="text-gray-500">{{ t('Default timezone') }}</span>
                </div>
              </div>
            </div>
          </div>
        </div>
      </div>
    </div>

    <!-- Language Edit Modal -->
    <a-modal
      :open="languageEditModal.visible"
      :title="t('Edit Adset Languages')"
      width="600px"
      @cancel="handleLanguageEditCancel"
      @ok="handleLanguageEditOk"
      :confirm-loading="languageEditModal.loading"
    >
      <div class="language-edit-content">
        <div class="current-languages mb-4">
          <div class="label">{{ t('Current Languages') }}:</div>
          <div v-if="languages.length" class="current-tags">
            <a-tag v-for="lang in languages" :key="lang.label_name" color="blue">
              {{ lang.english_name }} ({{ lang.native_name }})
            </a-tag>
          </div>
          <div v-else class="empty-state">{{ t('No languages configured') }}</div>
        </div>

        <div class="language-selector">
          <div class="label mb-2">{{ t('Select Languages') }}:</div>
          <a-select
            v-model:value="languageEditModal.selectedLanguages"
            mode="multiple"
            :placeholder="t('Search and select languages')"
            :options="languageOptions"
            :filter-option="filterLanguages"
            style="width: 100%"
            :loading="languagesLoading"
            show-search
            allow-clear
          >
            <template #option="{ label, value, english_name, native_name }">
              <div class="language-option">
                <span class="english-name">{{ english_name }}</span>
                <span class="native-name">({{ native_name }})</span>
              </div>
            </template>
          </a-select>
        </div>
      </div>
    </a-modal>
  </a-modal>
</template>

<script setup lang="ts">
import { ref, watch, computed, reactive } from 'vue';
import { useI18n } from 'vue-i18n';
import { InstagramOutlined, EditOutlined } from '@ant-design/icons-vue';
import { message } from 'ant-design-vue';
import { getLanguages, updateAdsetTargeting } from '@/api/ads';
import type { ApiLanguageItem } from '@/api/ads/types';

interface AdsetTargeting {
  age_max?: number;
  age_min?: number;
  locales?: number[];
  geo_locations?: {
    countries?: string[];
    location_types?: string[];
  };
  device_platforms?: string[];
  facebook_positions?: string[];
  instagram_positions?: string[];
  publisher_platforms?: string[];
  brand_safety_content_filter_levels?: string[];
}

interface AdsetData {
  adset_id: string;
  adset_name: string;
  timezone?: string;
  targeting?: AdsetTargeting;
}

interface Props {
  open: boolean;
  adsetData: AdsetData | null;
}

interface Emits {
  (e: 'cancel'): void;
  (e: 'update'): void;
}

const props = defineProps<Props>();
const emit = defineEmits<Emits>();
const { t } = useI18n();

const loading = ref(false);
const allLanguages = ref<ApiLanguageItem[]>([]);

// 添加语言编辑modal状态
const languageEditModal = reactive({
  visible: false,
  loading: false,
  selectedLanguages: [] as string[], // 存储选中的label_name数组
});

// 格式化版位名称
const formatPlacementName = (placement: string): string => {
  const placementMap: Record<string, string> = {
    feed: 'News Feed',
    facebook_reels: 'Reels',
    right_hand_column: 'Right Column',
    video_feeds: 'Video Feeds',
    instream_video: 'In-Stream Video',
    marketplace: 'Marketplace',
    story: 'Stories',
    stream: 'Feed',
    explore: 'Explore',
    reels: 'Reels',
  };

  return placementMap[placement] || placement.replace(/_/g, ' ').replace(/\b\w/g, l => l.toUpperCase());
};

// 获取语言数据
const fetchLanguages = async () => {
  try {
    loading.value = true;
    const response = await getLanguages();
    allLanguages.value = response.data;
  } catch (error) {
    console.error('Failed to fetch languages:', error);
  } finally {
    loading.value = false;
  }
};

// 获取targeting数据
const targeting = computed(() => props.adsetData?.targeting);

// 计算配置的语言
const languages = computed(() => {
  if (!targeting.value?.locales || allLanguages.value.length === 0) {
    return [];
  }

  const configuredLocales = targeting.value.locales;
  return allLanguages.value.filter(lang =>
    lang.locales.some(locale => configuredLocales.includes(locale)),
  );
});

// 获取语言选项
const languageOptions = computed(() => {
  return allLanguages.value.map(lang => ({
    label: `${lang.english_name} (${lang.native_name})`,
    value: lang.label_name,
    english_name: lang.english_name,
    native_name: lang.native_name,
    locales: lang.locales,
  }));
});

// 语言筛选函数
const filterLanguages = (input: string, option: any) => {
  const searchText = input.toLowerCase();
  return (
    option.english_name.toLowerCase().includes(searchText) ||
    option.native_name.toLowerCase().includes(searchText)
  );
};

// 显示语言编辑modal
const showLanguageEditModal = () => {
  // 设置当前已选择的语言
  const currentLocales = targeting.value?.locales || [];
  languageEditModal.selectedLanguages = allLanguages.value
    .filter(lang => lang.locales.some(locale => currentLocales.includes(locale)))
    .map(lang => lang.label_name);

  languageEditModal.visible = true;
};

// 取消语言编辑
const handleLanguageEditCancel = () => {
  languageEditModal.visible = false;
  languageEditModal.selectedLanguages = [];
};

// API调用函数
const updateAdsetTargetingApi = async (params: any) => {
  const response = await updateAdsetTargeting(params);
  return response;
};

// 确认语言编辑
const handleLanguageEditOk = async () => {
  try {
    languageEditModal.loading = true;

    // 获取选中语言对应的locales
    const selectedLocales: number[] = [];
    languageEditModal.selectedLanguages.forEach(labelName => {
      const lang = allLanguages.value.find(l => l.label_name === labelName);
      if (lang && lang.locales) {
        selectedLocales.push(...lang.locales);
      }
    });

    // 去重
    const uniqueLocales = [...new Set(selectedLocales)];

    // 构建请求参数
    const updateParams = {
      targeting: {
        ...targeting.value,
        locales: uniqueLocales,
      },
      ad_account: props.adsetData?.ad_account_id,
      adset_id: props.adsetData?.adset_id,
    };

    console.log('更新Adset语言参数:', updateParams);

    // 调用API更新
    await updateAdsetTargetingApi(updateParams);

    message.success(t('Languages updated successfully'));
    languageEditModal.visible = false;
    languageEditModal.selectedLanguages = [];

    // 更新本地数据
    if (props.adsetData?.targeting) {
      props.adsetData.targeting.locales = uniqueLocales;
    }

    // 发出更新事件，通知父组件刷新数据
    emit('update');

  } catch (error) {
    console.error('Failed to update languages:', error);
    message.error(t('Failed to update languages'));
  } finally {
    languageEditModal.loading = false;
  }
};

const handleCancel = () => {
  emit('cancel');
};

// 监听modal打开状态，打开时获取语言数据
watch(() => props.open, (newVal) => {
  if (newVal && allLanguages.value.length === 0) {
    fetchLanguages();
  }
});
</script>

<style scoped>
.audience-modal :deep(.ant-modal-content) {
  border-radius: 12px;
  overflow: hidden;
}

.audience-modal :deep(.ant-modal-header) {
  background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
  border-bottom: none;
  padding: 20px 24px;
}

.audience-modal :deep(.ant-modal-title) {
  color: white;
  font-size: 18px;
  font-weight: 600;
}

.audience-modal :deep(.ant-modal-close) {
  color: white;
}

.audience-modal :deep(.ant-modal-close:hover) {
  color: #f0f0f0;
}

.audience-content {
  padding: 24px;
  background: #f8fafc;
}

.header-card {
  background: white;
  border-radius: 8px;
  padding: 20px;
  margin-bottom: 20px;
  display: flex;
  gap: 40px;
  box-shadow: 0 2px 8px rgba(0, 0, 0, 0.05);
}

.adset-info {
  flex: 1;
}

.adset-label {
  font-size: 12px;
  color: #64748b;
  text-transform: uppercase;
  letter-spacing: 0.5px;
  margin-bottom: 4px;
  font-weight: 500;
}

.adset-id {
  font-family: 'Monaco', 'Menlo', monospace;
  font-size: 14px;
  color: #1e293b;
  font-weight: 600;
}

.adset-name {
  font-size: 14px;
  color: #1e293b;
  font-weight: 500;
}

.targeting-sections {
  display: flex;
  flex-direction: column;
  gap: 16px;
}

.section-card {
  background: white;
  border-radius: 8px;
  overflow: hidden;
  box-shadow: 0 2px 8px rgba(0, 0, 0, 0.05);
}

.section-header {
  background: #f1f5f9;
  padding: 16px 20px;
  display: flex;
  align-items: center;
  gap: 12px;
  border-bottom: 1px solid #e2e8f0;
}

.section-icon {
  font-size: 18px;
}

.section-title {
  font-size: 16px;
  font-weight: 600;
  color: #1e293b;
}

.section-content {
  padding: 20px;
}

.countries-grid {
  display: flex;
  flex-wrap: wrap;
  gap: 8px;
}

.country-item {
  background: #10b981;
  color: white;
  padding: 6px 12px;
  border-radius: 20px;
  font-size: 14px;
  font-weight: 500;
}

.platforms-container {
  display: flex;
  flex-direction: column;
  gap: 24px;
}

.platform-block {
  border: 1px solid #e2e8f0;
  border-radius: 8px;
  overflow: hidden;
}

.platform-header {
  background: #f8fafc;
  padding: 12px 16px;
  display: flex;
  align-items: center;
  gap: 12px;
  border-bottom: 1px solid #e2e8f0;
}

.platform-logo {
  width: 32px;
  height: 32px;
  border-radius: 6px;
  display: flex;
  align-items: center;
  justify-content: center;
  font-weight: bold;
  font-size: 16px;
  color: white;
}

.facebook-logo {
  background: #1877f2;
}

.instagram-logo {
  background: linear-gradient(45deg, #f09433 0%, #e6683c 25%, #dc2743 50%, #cc2366 75%, #bc1888 100%);
  position: relative;
}

.platform-name {
  font-size: 16px;
  font-weight: 600;
  color: #1e293b;
}

.placements-grid {
  padding: 16px;
  display: flex;
  flex-wrap: wrap;
  gap: 8px;
}

.placement-item {
  padding: 6px 12px;
  border-radius: 16px;
  font-size: 13px;
  font-weight: 500;
}

.facebook-placement {
  background: #e3f2fd;
  color: #1565c0;
}

.instagram-placement {
  background: #fce4ec;
  color: #ad1457;
}

.empty-placements {
  padding: 16px;
  color: #64748b;
  font-style: italic;
}

.demographics-grid,
.tech-grid {
  display: grid;
  grid-template-columns: 1fr 1fr;
  gap: 20px;
}

.demo-item,
.tech-item {
  display: flex;
  flex-direction: column;
  gap: 8px;
}

.demo-label,
.tech-label {
  font-size: 13px;
  color: #64748b;
  font-weight: 500;
  text-transform: uppercase;
  letter-spacing: 0.5px;
}

.demo-value,
.tech-value {
  font-size: 14px;
  color: #1e293b;
}

.languages-container {
  display: flex;
  flex-wrap: wrap;
  gap: 6px;
}

.language-tag {
  background: #0ea5e9;
  color: white;
  padding: 4px 8px;
  border-radius: 12px;
  font-size: 12px;
  font-weight: 500;
}

.device-tags {
  display: flex;
  flex-wrap: wrap;
  gap: 8px;
}

.device-tag {
  padding: 6px 12px;
  border-radius: 16px;
  font-size: 13px;
  font-weight: 500;
  display: flex;
  align-items: center;
  gap: 6px;
}

.device-tag.mobile {
  background: #f0f9ff;
  color: #0369a1;
}

.device-tag.desktop {
  background: #f0fdf4;
  color: #166534;
}

.device-icon {
  font-size: 14px;
}

.timezone-value {
  font-family: 'Monaco', 'Menlo', monospace;
  background: #f1f5f9;
  padding: 4px 8px;
  border-radius: 4px;
  font-size: 13px;
}

.empty-state {
  color: #94a3b8;
  font-style: italic;
  text-align: center;
  padding: 20px;
}

.text-gray-500 {
  color: #64748b;
}

.edit-button {
  padding: 0;
  margin-left: 8px;
}

.language-edit-content {
  padding: 20px;
}

.current-languages {
  margin-bottom: 20px;
}

.label {
  font-weight: 500;
  margin-bottom: 8px;
}

.current-tags {
  display: flex;
  flex-wrap: wrap;
  gap: 6px;
}

.language-option {
  display: flex;
  align-items: center;
  gap: 8px;
}

.english-name {
  font-weight: 500;
}

.native-name {
  font-size: 12px;
  color: #64748b;
}

.language-selector {
  margin-bottom: 20px;
}
</style>