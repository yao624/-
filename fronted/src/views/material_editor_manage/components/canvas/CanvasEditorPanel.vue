<template>
  <div class="canvas-editor-panel">
    <div class="editor-body">
      <MaterialList
        :materials="materials"
        :active-id="activeId"
        :checked-ids="checkedIds"
        @update:active-id="handleActiveChange"
        @update:checked-ids="handleCheckedChange"
      />
      <MiniCanvas ref="canvasRef" />
      <EditorControls
        :width="currentSettings.width"
        :height="currentSettings.height"
        :bg-color="currentSettings.bgColor"
        :bg-mode="currentSettings.bgMode"
        :bg-image-url="currentSettings.bgImageUrl"
        :blur-amount="currentSettings.blurAmount"
        :fill-mode="currentSettings.fillMode"
        :crop-x="currentSettings.cropX"
        :crop-y="currentSettings.cropY"
        :crop-w="currentSettings.cropW"
        :crop-h="currentSettings.cropH"
        :apply-all="applyAll"
        @update:width="handleControlChange('width', $event)"
        @update:height="handleControlChange('height', $event)"
        @update:bg-color="handleControlChange('bgColor', $event)"
        @update:bg-mode="handleControlChange('bgMode', $event)"
        @update:bg-image-url="handleControlChange('bgImageUrl', $event)"
        @update:blur-amount="handleControlChange('blurAmount', $event)"
        @update:fill-mode="handleControlChange('fillMode', $event)"
        @update:crop-x="handleControlChange('cropX', $event)"
        @update:crop-y="handleControlChange('cropY', $event)"
        @update:crop-w="handleControlChange('cropW', $event)"
        @update:crop-h="handleControlChange('cropH', $event)"
        @update:apply-all="(val: boolean) => (applyAll = val)"
        @preset="handlePreset"
      />
    </div>
  </div>
</template>

<script setup lang="ts">
import { ref, reactive, watch, nextTick, onMounted } from 'vue';
import MiniCanvas from './MiniCanvas.vue';
import MaterialList from './MaterialList.vue';
import EditorControls from './EditorControls.vue';
import type { MaterialItem, MaterialEditState } from '../../types';

const props = defineProps<{
  materials: MaterialItem[];
}>();

const emit = defineEmits<{
  'update:materials': [materials: (MaterialItem & { settings: MaterialEditState })[]];
}>();

const canvasRef = ref<InstanceType<typeof MiniCanvas> | null>(null);
const activeId = ref<string | null>(null);
const checkedIds = ref<string[]>([]);
const applyAll = ref(false);
const isExporting = ref(false);

// Per-material settings
const materialSettingsMap = reactive<Map<string, MaterialEditState>>(new Map());

// Default settings
const defaultSettings = (): MaterialEditState => ({
  width: 1920,
  height: 1080,
  bgColor: '#ffffff',
  bgMode: 'solid',
  bgImageUrl: '',
  blurAmount: 0,
  fillMode: 'contain',
  cropX: 0,
  cropY: 0,
  cropW: 100,
  cropH: 100,
});

// Get or create settings for a material
function getSettings(id: string): MaterialEditState {
  if (!materialSettingsMap.has(id)) {
    materialSettingsMap.set(id, defaultSettings());
  }
  return materialSettingsMap.get(id)!;
}

// Current active material's settings for controls
const currentSettings = reactive<MaterialEditState>(defaultSettings());

// Sync controls to active material's settings
function syncControlsFromActive() {
  if (activeId.value) {
    const s = getSettings(activeId.value);
    Object.assign(currentSettings, s);
  }
}

// Target IDs: applyAll -> all, else -> active only
function getTargetIds(): string[] {
  if (applyAll.value) {
    return props.materials.map((m) => m.id);
  }
  return activeId.value ? [activeId.value] : [];
}

// Update settings and apply to canvas
function updateSettingsAndApply(id: string, partial: Partial<MaterialEditState>) {
  const s = getSettings(id);
  Object.assign(s, partial);

  if (id === activeId.value) {
    Object.assign(currentSettings, partial);
    applyToCanvas();
  }

  emitMaterials();
}

// Unified handler for all control changes
function handleControlChange(key: keyof MaterialEditState, value: any) {
  getTargetIds().forEach((id) => updateSettingsAndApply(id, { [key]: value }));
}

function applyToCanvas() {
  if (!canvasRef.value) return;
  canvasRef.value.setSize(currentSettings.width, currentSettings.height);

  const crop = {
    x: currentSettings.cropX,
    y: currentSettings.cropY,
    w: currentSettings.cropW,
    h: currentSettings.cropH,
  };
  canvasRef.value.applyFillMode(currentSettings.fillMode, crop);

  // Apply background by mode
  if (currentSettings.bgMode === 'image' && currentSettings.bgImageUrl) {
    canvasRef.value.setBgImage(currentSettings.bgImageUrl, currentSettings.blurAmount);
  } else {
    canvasRef.value.setBgColor(currentSettings.bgColor, currentSettings.blurAmount);
  }
}

// Emit materials with settings
function emitMaterials() {
  const updated = props.materials.map((m) => ({
    ...m,
    settings: getSettings(m.id),
  }));
  emit('update:materials', updated);
}

// When applyAll toggled ON, sync current settings to all materials
watch(applyAll, (val) => {
  if (val) {
    const snapshot = { ...currentSettings };
    props.materials.forEach((m) => {
      const s = getSettings(m.id);
      Object.assign(s, snapshot);
    });
    emitMaterials();
  }
});

// Initialize settings for all materials when they change
watch(
  () => props.materials,
  (materials) => {
    const currentIds = new Set(materials.map((m) => m.id));
    for (const key of materialSettingsMap.keys()) {
      if (!currentIds.has(key)) {
        materialSettingsMap.delete(key);
      }
    }
    if (!activeId.value || !currentIds.has(activeId.value)) {
      activeId.value = materials.length > 0 ? materials[0].id : null;
    }
  },
  { immediate: true }
);

// Load active material's image to canvas
async function loadActiveMaterial() {
  if (!canvasRef.value || !activeId.value) return;

  syncControlsFromActive();
  const material = props.materials.find((m) => m.id === activeId.value);
  if (!material) return;

  await nextTick();
  const settings = getSettings(activeId.value);
  canvasRef.value.setSize(settings.width, settings.height);
  // Use applyToCanvas to properly handle bgMode/bgImage/blur for each material
  applyToCanvas();
  await canvasRef.value.loadImage(material.previewUrl, settings.fillMode, {
    x: settings.cropX,
    y: settings.cropY,
    w: settings.cropW,
    h: settings.cropH,
  });
}

// When active material changes, load its image and sync controls
watch(activeId, async (newId) => {
  if (!canvasRef.value || !newId || isExporting.value) return;
  await loadActiveMaterial();
});

// After mount, canvas is ready — load the initial active material
onMounted(async () => {
  await nextTick();
  await loadActiveMaterial();
});

function handlePreset(width: number, height: number) {
  getTargetIds().forEach((id) => updateSettingsAndApply(id, { width, height }));
}

function handleActiveChange(id: string | null) {
  activeId.value = id;
}

function handleCheckedChange(ids: string[]) {
  checkedIds.value = ids;
}

// 导出当前canvas为Blob
async function exportCurrentCanvasAsBlob(): Promise<Blob | null> {
  if (!canvasRef.value) return null;
  return canvasRef.value.exportCanvasAsBlob();
}

// 导出当前canvas为DataURL
function exportCurrentCanvasAsDataURL(): string {
  if (!canvasRef.value) return '';
  return canvasRef.value.exportCanvasAsDataURL();
}

// 批量导出所有素材为Blob
async function exportAllMaterialsAsBlobs(): Promise<Array<{ id: string; name: string; blob: Blob; settings: MaterialEditState }>> {
  if (!canvasRef.value) return [];

  isExporting.value = true;
  const results: Array<{ id: string; name: string; blob: Blob; settings: MaterialEditState }> = [];

  try {
    for (const material of props.materials) {
      const settings = getSettings(material.id);

      // Sync currentSettings so applyToCanvas uses this material's config
      Object.assign(currentSettings, settings);

      // Set canvas size and apply background
      canvasRef.value.setSize(settings.width, settings.height);
      applyToCanvas();

      // Load the material image with its fill mode and crop
      await canvasRef.value.loadImage(material.previewUrl, settings.fillMode, {
        x: settings.cropX,
        y: settings.cropY,
        w: settings.cropW,
        h: settings.cropH,
      });

      // Export as blob
      const blob = await canvasRef.value.exportCanvasAsBlob();
      if (blob) {
        results.push({
          id: material.id,
          name: material.name,
          blob,
          settings: { ...settings },
        });
      }
    }
  } finally {
    isExporting.value = false;
    // Restore the active material's view
    if (activeId.value) {
      await loadActiveMaterial();
    }
  }

  return results;
}

defineExpose({
  exportCurrentCanvasAsBlob,
  exportCurrentCanvasAsDataURL,
  exportAllMaterialsAsBlobs,
});
</script>

<style scoped lang="less">
.canvas-editor-panel {
  display: flex;
  flex-direction: column;
  height: 100%;
  border: 1px solid #f0f0f0;
  border-radius: 8px;
  overflow: hidden;
  background: #fff;
}

.editor-body {
  display: flex;
  flex: 1;
  min-height: 0;
}
</style>
