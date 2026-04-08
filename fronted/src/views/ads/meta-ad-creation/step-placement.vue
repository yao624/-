<template>
  <div class="step-placement">
    <h3 class="section-title">{{ t('版位') }}</h3>
    <a-form layout="vertical">
      <a-form-item :label="t('版位设置')">
        <a-radio-group v-model:value="local.placementMode">
          <a-radio-button value="advanced">{{ t('进阶赋能型版位') }}</a-radio-button>
          <a-radio-button value="manual">{{ t('手动版位') }}</a-radio-button>
        </a-radio-group>
      </a-form-item>
      <a-form-item :label="t('设备')">
        <a-select v-model:value="local.deviceType" style="width: 240px" :disabled="isAdvanced">
          <a-select-option value="all">All Devices(Recommended)</a-select-option>
          <a-select-option value="mobile">Mobile</a-select-option>
          <a-select-option value="desktop">Desktop</a-select-option>
        </a-select>
      </a-form-item>
      <a-form-item :label="t('平台和版位') + ' *'">
        <div class="placement-box">
        <div class="platform-switch">
          <div
            v-for="tab in platformTabs"
            :key="tab.key"
            class="platform-item"
            :class="{ current: local.platform === tab.key }"
            @click="onPlatformTabClick(tab.key)"
          >
            <a-checkbox
              :checked="isPlatformChecked(tab.key)"
              :indeterminate="isPlatformIndeterminate(tab.key)"
              :disabled="isAdvanced"
              @click.stop
              @change="togglePlatform(tab.key, $event.target.checked)"
            >
              {{ tab.label }}
            </a-checkbox>
          </div>
        </div>
        <div class="placement-panel" :class="{ disabled: isAdvanced }">
          <div class="placement-col">
            <div class="placement-col-header">{{ t('名称') }}</div>
            <div class="placement-col-body">
              <div v-for="row in leftOptions" :key="row.key" class="placement-row">
                <a-checkbox
                  :checked="isSelected(local.platform, row.key)"
                  :disabled="isAdvanced"
                  @change="togglePlacement(local.platform, row.key, $event.target.checked)"
                >
                  {{ row.label }}
                </a-checkbox>
                <span class="row-arrow">></span>
              </div>
            </div>
          </div>
          <div class="placement-col">
            <div class="placement-col-header">{{ t('操作') }}</div>
            <div class="placement-col-body">
              <div v-for="row in rightOptions" :key="row.key" class="placement-row">
                <a-checkbox
                  :checked="isSelected(local.platform, row.key)"
                  :disabled="isAdvanced"
                  @change="togglePlacement(local.platform, row.key, $event.target.checked)"
                >
                  {{ row.label }}
                </a-checkbox>
              </div>
            </div>
          </div>
        </div>
        </div>
        <div class="placement-summary">{{ t('已选择') }} {{ selectedCount }} {{ t('个版位') }}</div>
      </a-form-item>
    </a-form>
  </div>
</template>

<script lang="ts" setup>
import { ref, computed, watch, nextTick } from 'vue';
import { useI18n } from 'vue-i18n';

const { t } = useI18n();
const props = defineProps<{ formData: any }>();
const emit = defineEmits<{ (e: 'update:form-data', v: any): void }>();

type PlatformKey = 'facebook' | 'messenger' | 'instagram' | 'audience_network';
type DeviceType = 'all' | 'mobile' | 'desktop';

interface PlacementItem {
  key: string;
  label: string;
  device: DeviceType | 'all';
}

const PLATFORM_ITEMS: Record<PlatformKey, PlacementItem[]> = {
  facebook: [
    { key: 'feed', label: t('动态'), device: 'all' },
    { key: 'story', label: t('快拍和 Reels'), device: 'mobile' },
    { key: 'instream_video', label: t('视频和 Reels 插播视频'), device: 'all' },
    { key: 'search', label: t('搜索结果'), device: 'all' },
    { key: 'video_feeds', label: t('Facebook 视频动态'), device: 'all' },
    { key: 'right_hand_column', label: t('Facebook 右边栏'), device: 'desktop' },
    { key: 'marketplace', label: 'Facebook Marketplace', device: 'all' },
  ],
  messenger: [
    { key: 'messenger_home', label: t('Messenger 主页'), device: 'mobile' },
    { key: 'messenger_inbox', label: 'Messenger 收件箱', device: 'all' },
    { key: 'story', label: t('Messenger 快拍'), device: 'mobile' },
  ],
  instagram: [
    { key: 'stream', label: 'Instagram Feed', device: 'all' },
    { key: 'story', label: t('快拍和 Reels'), device: 'mobile' },
    { key: 'explore', label: t('Instagram 发现'), device: 'mobile' },
    { key: 'explore_home', label: t('Instagram 发现首页'), device: 'mobile' },
    { key: 'reels', label: 'Instagram Reels', device: 'mobile' },
  ],
  audience_network: [
    { key: 'classic', label: t('原生、横幅和插屏'), device: 'all' },
    { key: 'rewarded_video', label: t('激励视频'), device: 'mobile' },
    { key: 'an_classic', label: t('应用和网站'), device: 'all' },
  ],
};

const platformTabs = [
  { key: 'facebook' as PlatformKey, label: 'Facebook' },
  { key: 'messenger' as PlatformKey, label: 'Messenger' },
  { key: 'instagram' as PlatformKey, label: 'Instagram' },
  { key: 'audience_network' as PlatformKey, label: 'Audience Network' },
];

function uniqueArray(v: unknown) {
  if (!Array.isArray(v)) return [] as string[];
  return [...new Set(v.map((x) => String(x).trim()).filter(Boolean))];
}

function defaultPlacementState() {
  return {
    placementMode: 'manual' as 'manual' | 'advanced',
    deviceType: 'all' as DeviceType,
    platform: 'facebook' as PlatformKey,
    publisher_platforms: ['facebook', 'messenger', 'instagram', 'audience_network'] as string[],
    facebook_positions: ['feed', 'story', 'instream_video', 'search', 'video_feeds', 'right_hand_column', 'marketplace'] as string[],
    messenger_positions: ['messenger_home', 'messenger_inbox', 'story'] as string[],
    instagram_positions: ['stream', 'story', 'explore', 'explore_home', 'reels'] as string[],
    audience_network_positions: ['classic', 'rewarded_video', 'an_classic'] as string[],
  };
}

function mergeLocalState(incoming: any) {
  const d = defaultPlacementState();
  const src = incoming && typeof incoming === 'object' ? incoming : {};
  const mode = src.placementMode === 'advanced' ? 'advanced' : 'manual';
  return {
    ...d,
    ...src,
    placementMode: mode,
    deviceType: src.deviceType === 'mobile' || src.deviceType === 'desktop' ? src.deviceType : 'all',
    platform: (platformTabs.some((x) => x.key === src.platform) ? src.platform : d.platform) as PlatformKey,
    publisher_platforms: uniqueArray(src.publisher_platforms?.length ? src.publisher_platforms : d.publisher_platforms),
    facebook_positions: uniqueArray(src.facebook_positions?.length ? src.facebook_positions : d.facebook_positions),
    messenger_positions: uniqueArray(src.messenger_positions?.length ? src.messenger_positions : d.messenger_positions),
    instagram_positions: uniqueArray(src.instagram_positions?.length ? src.instagram_positions : d.instagram_positions),
    audience_network_positions: uniqueArray(
      src.audience_network_positions?.length ? src.audience_network_positions : d.audience_network_positions,
    ),
  };
}

const local = ref(mergeLocalState(props.formData));
const isAdvanced = computed(() => local.value.placementMode === 'advanced');
const syncingFromParent = ref(false);

function currentItems(platform: PlatformKey) {
  const device = local.value.deviceType;
  const rows = PLATFORM_ITEMS[platform] || [];
  if (device === 'all') return rows;
  return rows.filter((x) => x.device === 'all' || x.device === device);
}

const leftOptions = computed(() => {
  const rows = currentItems(local.value.platform);
  const mid = Math.ceil(rows.length / 2);
  return rows.slice(0, mid);
});
const rightOptions = computed(() => {
  const rows = currentItems(local.value.platform);
  const mid = Math.ceil(rows.length / 2);
  return rows.slice(mid);
});

function syncPublisherPlatformsFromPositions() {
  const next: PlatformKey[] = [];
  if (uniqueArray(local.value.facebook_positions).length > 0) next.push('facebook');
  if (uniqueArray(local.value.messenger_positions).length > 0) next.push('messenger');
  if (uniqueArray(local.value.instagram_positions).length > 0) next.push('instagram');
  if (uniqueArray(local.value.audience_network_positions).length > 0) next.push('audience_network');
  local.value.publisher_platforms = next;
}

function getPositionKey(platform: PlatformKey) {
  if (platform === 'facebook') return 'facebook_positions';
  if (platform === 'messenger') return 'messenger_positions';
  if (platform === 'instagram') return 'instagram_positions';
  return 'audience_network_positions';
}

function isSelected(platform: PlatformKey, key: string) {
  const field = getPositionKey(platform);
  const list = uniqueArray((local.value as any)[field]);
  return list.includes(key);
}

function togglePlacement(platform: PlatformKey, key: string, checked: boolean) {
  const field = getPositionKey(platform);
  const list = uniqueArray((local.value as any)[field]);
  const next = checked ? [...new Set([...list, key])] : list.filter((x) => x !== key);
  (local.value as any)[field] = next;
  syncPublisherPlatformsFromPositions();
}

function isPlatformChecked(platform: PlatformKey) {
  const rows = currentItems(platform);
  if (!rows.length) return false;
  return rows.every((x) => isSelected(platform, x.key));
}

function isPlatformIndeterminate(platform: PlatformKey) {
  const rows = currentItems(platform);
  if (!rows.length) return false;
  const selected = rows.filter((x) => isSelected(platform, x.key)).length;
  return selected > 0 && selected < rows.length;
}

function togglePlatform(platform: PlatformKey, checked: boolean) {
  if (isAdvanced.value) return;
  const field = getPositionKey(platform);
  // 按你的要求：点上方平台只做联动，不做“全选下面”
  if (!checked) {
    (local.value as any)[field] = [];
  }
  syncPublisherPlatformsFromPositions();
}

function onPlatformTabClick(platform: PlatformKey) {
  if (isAdvanced.value) return;
  local.value.platform = platform;
}

const selectedCount = computed(() => {
  const fields = ['facebook_positions', 'messenger_positions', 'instagram_positions', 'audience_network_positions'];
  return fields.reduce((sum, field) => sum + uniqueArray((local.value as any)[field]).length, 0);
});

watch(
  () => props.formData,
  (v) => {
    syncingFromParent.value = true;
    local.value = mergeLocalState(v);
    nextTick(() => {
      syncingFromParent.value = false;
    });
  },
  { deep: true },
);

watch(
  () => local.value.placementMode,
  (mode) => {
    if (mode === 'advanced') {
      local.value.publisher_platforms = [];
      local.value.facebook_positions = [];
      local.value.messenger_positions = [];
      local.value.instagram_positions = [];
      local.value.audience_network_positions = [];
      return;
    }
    const def = defaultPlacementState();
    if (selectedCount.value === 0) {
      local.value.publisher_platforms = [...def.publisher_platforms];
      local.value.facebook_positions = [...def.facebook_positions];
      local.value.messenger_positions = [...def.messenger_positions];
      local.value.instagram_positions = [...def.instagram_positions];
      local.value.audience_network_positions = [...def.audience_network_positions];
    }
    syncPublisherPlatformsFromPositions();
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
</script>

<style lang="less" scoped>
.section-title {
  font-size: 16px;
  font-weight: 500;
  margin-bottom: 16px;
  color: #262626;
}

.placement-box {
  max-width: 760px;
  border: 1px solid #e5e5e5;
  border-radius: 4px;
  background: #fff;
}

.platform-switch {
  display: inline-flex;
  border-bottom: 1px solid #f0f0f0;
  width: 100%;

  .platform-item {
    border: none;
    border-right: 1px solid #f0f0f0;
    background: #fafafa;
    color: #262626;
    padding: 6px 16px;
    cursor: pointer;
    user-select: none;

    &:last-child {
      border-right: none;
    }

    &.current {
      background: #fff;
    }

    :deep(.ant-checkbox-wrapper) {
      color: inherit;
    }
    :deep(.ant-checkbox + span) {
      color: inherit;
    }
  }
}

.placement-panel {
  display: grid;
  grid-template-columns: 1fr 1fr;
  border-radius: 0 0 4px 4px;

  &.disabled {
    opacity: 0.6;
    pointer-events: none;
  }
}

.placement-col {
  border-right: 1px solid #f0f0f0;

  &:last-child {
    border-right: none;
  }
}

.placement-col-header {
  padding: 8px 12px;
  font-size: 13px;
  color: #595959;
  background: #fafafa;
  border-bottom: 1px solid #f0f0f0;
}

.placement-col-body {
  min-height: 180px;
  max-height: 220px;
  overflow-y: auto;
}

.placement-row {
  display: flex;
  align-items: center;
  justify-content: space-between;
  padding: 8px 12px;
  border-bottom: 1px solid #fafafa;
}

.row-arrow {
  color: #8c8c8c;
  margin-left: 8px;
}

.placement-summary {
  margin-top: 8px;
  color: #666;
  font-size: 13px;
}
</style>
