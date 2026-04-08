<template>
  <div class="placement">
    <h2 class="section-title">{{ t('版位') }}</h2>
    <a-form :model="localFormData" layout="vertical">
      <a-form-item :label="t('版位设置')">
        <a-radio-group v-model:value="localFormData.placementType">
          <a-radio-button value="advanced">{{ t('进阶赋能型版位') }}</a-radio-button>
          <a-radio-button value="manual">{{ t('手动版位') }}</a-radio-button>
        </a-radio-group>
      </a-form-item>
      <a-form-item :label="t('平台和版位') + ' *'" required v-if="localFormData.placementType === 'manual'">
        <div class="platform-tabs">
          <a-tag
            v-for="platform in platforms"
            :key="platform.value"
            :color="selectedPlatforms.includes(platform.value) ? 'blue' : 'default'"
            @click="togglePlatform(platform.value)"
            class="platform-tag"
          >
            {{ platform.label }}
          </a-tag>
        </div>
        <div class="placement-options">
          <a-checkbox-group v-model:value="selectedPlacements">
            <div class="placement-grid">
              <div
                v-for="placement in availablePlacements"
                :key="placement.id"
                class="placement-item"
              >
                <a-checkbox :value="placement.id">
                  {{ placement.name }}
                  <right-outlined v-if="placement.hasChildren" class="arrow-icon" />
                </a-checkbox>
              </div>
            </div>
          </a-checkbox-group>
        </div>
        <div class="placement-summary">
          <a-spin v-if="loading" size="small" />
          <span v-else>{{ t('已选择') }} {{ selectedPlacements.length }} {{ t('个版位') }}</span>
        </div>
      </a-form-item>
    </a-form>
  </div>
</template>

<script lang="ts" setup>
import { ref, computed, watch } from 'vue';
import { useI18n } from 'vue-i18n';
import { RightOutlined } from '@ant-design/icons-vue';
import { getPlacements } from '../mock-data';

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
  placementType: 'advanced',
  platforms: [],
  placements: [],
  ...props.formData,
});

const platforms = ref([
  { value: 'facebook', label: 'Facebook' },
  { value: 'messenger', label: 'Messenger' },
  { value: 'instagram', label: 'Instagram' },
  { value: 'audience_network', label: 'Audience Network' },
]);
const selectedPlatforms = ref(['facebook', 'messenger', 'instagram', 'audience_network']);
const allPlacements = ref<any[]>([]);
const selectedPlacements = ref<string[]>([]);
const loading = ref(false);

const availablePlacements = computed(() => {
  return allPlacements.value.filter((p) => {
    const platformMap: Record<string, string> = {
      facebook: 'Facebook',
      messenger: 'Messenger',
      instagram: 'Instagram',
      audience_network: 'Audience Network',
    };
    return selectedPlatforms.value.some((sp) => platformMap[sp] === p.platform);
  });
});

const togglePlatform = (platform: string) => {
  const index = selectedPlatforms.value.indexOf(platform);
  if (index > -1) {
    selectedPlatforms.value.splice(index, 1);
  } else {
    selectedPlatforms.value.push(platform);
  }
};

watch(
  [selectedPlacements, localFormData],
  () => {
    localFormData.value.placements = selectedPlacements.value;
    emit('update:formData', { ...localFormData.value });
  },
  { deep: true },
);

const loadData = async () => {
  loading.value = true;
  try {
    allPlacements.value = await getPlacements();
    selectedPlacements.value = allPlacements.value.map((p) => p.id);
  } catch (error) {
    console.error('加载数据失败:', error);
  } finally {
    loading.value = false;
  }
};

loadData();
</script>

<style lang="less" scoped>
.placement {
  .section-title {
    font-size: 18px;
    font-weight: 500;
    margin-bottom: 24px;
    color: #333;
  }

  .platform-tabs {
    margin-bottom: 16px;
    display: flex;
    gap: 8px;
    flex-wrap: wrap;

    .platform-tag {
      cursor: pointer;
      user-select: none;
      padding: 4px 12px;
      font-size: 14px;
    }
  }

  .placement-options {
    max-height: 400px;
    overflow-y: auto;
    border: 1px solid #d9d9d9;
    border-radius: 4px;
    padding: 16px;

    .placement-grid {
      display: grid;
      grid-template-columns: repeat(2, 1fr);
      gap: 8px;

      .placement-item {
        display: flex;
        align-items: center;

        .arrow-icon {
          margin-left: 4px;
          color: #999;
        }
      }
    }
  }

  .placement-summary {
    margin-top: 16px;
    color: #666;
    font-size: 14px;
    display: flex;
    align-items: center;
    gap: 8px;
  }
}
</style>

