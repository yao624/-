<template>
  <a-modal
    v-model:open="visible"
    :title="t('batchGenerate.title')"
    width="1200px"
    :mask-closable="false"
    :footer="null"
    :destroy-on-close="true"
    @cancel="handleCancel"
    wrap-class-name="batch-generate-modal"
  >
    <div v-if="selectedTemplate" class="batch-modal-content">
      <!-- 步骤指示器 -->
      <div class="steps-indicator">
        <div class="step-item" :class="{ active: currentStep >= 1, completed: currentStep > 1 }">
          <div class="step-number">1</div>
          <div class="step-text">{{ t('batchGenerate.step1') }}</div>
        </div>
        <div class="step-line" :class="{ active: currentStep > 1 }"></div>
        <div class="step-item" :class="{ active: currentStep >= 2, completed: currentStep > 2 }">
          <div class="step-number">2</div>
          <div class="step-text">{{ t('batchGenerate.step2') }}</div>
        </div>
        <div class="step-line" :class="{ active: currentStep > 2 }"></div>
        <div class="step-item" :class="{ active: currentStep >= 3 }">
          <div class="step-number">3</div>
          <div class="step-text">{{ t('batchGenerate.step3') }}</div>
        </div>
      </div>

      <!-- 模板信息卡片 -->
      <div class="template-info-card">
        <div class="template-info-main">
          <div class="template-icon">
            <FileImageOutlined style="font-size: 36px; color: #1890ff;" />
          </div>
          <div class="template-details">
            <h3 class="template-name">{{ selectedTemplate.name }}</h3>
            <div class="template-meta">
              <span class="meta-item">
                <ScanOutlined style="font-size: 14px;" />
                {{ selectedTemplate.width }}×{{ selectedTemplate.height }}
              </span>
              <span class="meta-divider">|</span>
              <span class="meta-item">
                <CodeOutlined style="font-size: 14px;" />
                {{ selectedTemplate.dynamicVariables?.length || 0 }} {{ t('batchGenerate.variablesCount') }}
              </span>
            </div>
          </div>
        </div>
      </div>

      <!-- 快捷操作栏 -->
      <div class="quick-actions-bar">
        <div class="actions-group">
          <div class="action-item">
            <span class="action-label">{{ t('batchGenerate.count') }}:</span>
            <a-input-number v-model:value="batchCount" :min="1" :max="50" size="small" @change="onBatchCountChange" />
          </div>
          <div class="action-item">
            <span class="action-label">{{ t('batchGenerate.format') }}:</span>
            <a-radio-group v-model:value="exportFormat" size="small" button-style="solid">
              <a-radio-button value="png">PNG</a-radio-button>
              <a-radio-button value="jpg">JPG</a-radio-button>
            </a-radio-group>
          </div>
        </div>
        <div class="actions-group">
          <a-button size="small" @click="copyFirstToAll" :disabled="!hasFirstItemValues">
            <CopyOutlined />
            {{ t('batchGenerate.copyToAll') }}
          </a-button>
          <a-button size="small" @click="clearAllValues">
            <DeleteOutlined />
            {{ t('batchGenerate.clear') }}
          </a-button>
        </div>
      </div>

      <!-- 变量配置区域 -->
      <div class="variables-section">
        <div class="section-header-with-progress">
          <div class="header-left">
            <h4>{{ t('batchGenerate.variableConfig') }}</h4>
            <div class="progress-indicator">
              <a-progress
                :percent="getCompletionProgress()"
                :stroke-color="getProgressColor()"
                :show-info="false"
                size="small"
              />
              <span class="progress-text">{{ getCompletionText() }}</span>
            </div>
          </div>
          <div class="header-right">
            <a-button type="primary" ghost size="small" @click="previewBatch" :loading="previewLoading">
              <EyeOutlined />
              {{ t('batchGenerate.preview') }}
            </a-button>
          </div>
        </div>

        <!-- 使用 Tabs 组织每个生成项的配置 -->
        <a-tabs v-model:active-key="activeBatchTab" type="card" class="batch-tabs">
          <a-tab-pane
            v-for="index in batchCount"
            :key="`batch-${index}`"
            :tab="getTabLabel(index)"
          >
            <div class="batch-item-content">
              <div v-if="selectedTemplate.dynamicVariables && selectedTemplate.dynamicVariables.length > 0" class="variables-grid">
                <div
                  v-for="variable in selectedTemplate.dynamicVariables"
                  :key="`${index}-${variable.variableName}`"
                  class="variable-card"
                  :class="{
                    'has-value': batchValues[index - 1]?.[variable.variableName],
                    'is-required': !batchValues[index - 1]?.[variable.variableName]
                  }"
                >
                  <div class="variable-header">
                    <div class="variable-type-icon" :class="`type-${variable.variableType}`">
                      <component :is="variable.variableType === 'text' ? FontSizeOutlined : PictureOutlined" />
                    </div>
                    <div class="variable-info">
                      <span class="variable-name">{{ variable.variableName }}</span>
                      <a-tag v-if="variable.variableType === 'text'" color="blue" size="small">
                        {{ t('batchGenerate.text') }}
                      </a-tag>
                      <a-tag v-else color="green" size="small">
                        {{ t('batchGenerate.image') }}
                      </a-tag>
                    </div>
                    <div class="variable-status">
                      <CheckCircleOutlined
                        v-if="batchValues[index - 1]?.[variable.variableName]"
                        style="color: #52c41a; font-size: 20px;"
                      />
                      <BorderOutlined
                        v-else
                        style="color: #d9d9d9; font-size: 20px;"
                      />
                    </div>
                  </div>

                  <div v-if="variable.remark" class="variable-remark">
                    <InfoCircleOutlined style="font-size: 12px;" />
                    {{ variable.remark }}
                  </div>

                  <div class="variable-input-area">
                    <!-- 文本变量 -->
                    <a-input
                      v-if="variable.variableType === 'text'"
                      v-model:value="batchValues[index - 1][variable.variableName]"
                      :placeholder="t('batchGenerate.enterPlaceholder', { name: variable.variableName })"
                      size="small"
                      allow-clear
                    >
                      <template #prefix>
                        <EditOutlined style="font-size: 14px; color: #999;" />
                      </template>
                    </a-input>

                    <!-- 图片变量 -->
                    <div v-else class="image-input-area">
                      <a-upload
                        :before-upload="(file) => handleImageUpload(index - 1, variable.variableName, file)"
                        :show-upload-list="false"
                        accept="image/*"
                        :multiple="false"
                      >
                        <div
                          class="upload-trigger"
                          :class="{ 'has-image': batchValues[index - 1]?.[variable.variableName] }"
                        >
                          <div v-if="!batchValues[index - 1]?.[variable.variableName]" class="upload-placeholder">
                            <CloudUploadOutlined style="font-size: 32px;" />
                            <span>{{ t('batchGenerate.clickUpload') }}</span>
                          </div>
                          <div v-else class="upload-preview">
                            <img :src="batchValues[index - 1][variable.variableName]" alt="预览" />
                            <div class="upload-overlay">
                              <ReloadOutlined style="font-size: 20px;" />
                              <span>{{ t('batchGenerate.change') }}</span>
                            </div>
                          </div>
                        </div>
                      </a-upload>
                    </div>
                  </div>
                </div>
              </div>

              <div v-else class="empty-variables">
                <div class="empty-icon">
                  <WarningOutlined style="font-size: 64px; color: #ffec3d;" />
                </div>
                <h4>{{ t('batchGenerate.noVariables') }}</h4>
                <p>{{ t('batchGenerate.noVariablesTip') }}</p>
              </div>
            </div>
          </a-tab-pane>
        </a-tabs>
      </div>

      <!-- 预览区域 -->
      <div class="preview-section">
        <div class="section-header-with-actions">
          <h4>{{ t('batchGenerate.step2') }}</h4>
          <div class="header-actions">
            <a-tag v-if="previewImages.length > 0" color="success">
              <CheckOutlined />
              {{ t('batchGenerate.generated') }} {{ previewImages.length }} {{ t('batchGenerate.sheet') }}
            </a-tag>
            <a-button v-if="previewImages.length > 0" size="small" @click="downloadAllImages">
              <DownloadOutlined />
              {{ t('batchGenerate.downloadAll') }}
            </a-button>
          </div>
        </div>

        <div v-if="previewImages.length > 0" class="preview-grid-modern">
          <div
            v-for="(previewImg, idx) in previewImages"
            :key="`preview-${idx}`"
            class="preview-card"
          >
            <div class="preview-image-wrapper" @click="openImagePreview(previewImg, idx)">
              <img :src="previewImg" :alt="`${t('batchGenerate.preview')} ${idx + 1}`" class="preview-image" />
              <div class="preview-overlay">
                <div class="preview-number">#{{ idx + 1 }}</div>
                <div class="preview-hint">
                  <ZoomInOutlined style="font-size: 16px;" />
                  <span>{{ t('batchGenerate.clickEnlarge') }}</span>
                </div>
                <div class="preview-actions">
                  <a-button type="primary" size="small" ghost @click.stop="downloadImage(previewImg, idx)">
                    <DownloadOutlined />
                    {{ t('batchGenerate.download') }}
                  </a-button>
                </div>
              </div>
            </div>
            <div class="preview-footer">
              <span class="preview-name">{{ selectedTemplate.name }}_{{ idx + 1 }}</span>
            </div>
          </div>
        </div>

        <div v-else class="preview-empty-modern">
          <div class="empty-illustration">
            <div class="empty-icon-circle">
              <PictureOutlined style="font-size: 48px; color: #d9d9d9;" />
            </div>
          </div>
          <h4>{{ t('batchGenerate.noPreview') }}</h4>
          <p>{{ t('batchGenerate.noPreviewTip') }}</p>
        </div>
      </div>
    </div>

    <!-- 底部按钮 -->
    <div class="modal-footer-custom">
      <div class="footer-left">
        <a-button @click="handleCancel">{{ t('batchGenerate.cancel') }}</a-button>
      </div>
      <div class="footer-right">
        <a-button @click="previewBatch" :loading="previewLoading" :disabled="!canPreview">
          <EyeOutlined />
          {{ t('batchGenerate.step2') }}
        </a-button>
        <a-button type="primary" @click="handleBatchGenerate" :loading="generating" :disabled="!canGenerate">
          <DownloadOutlined />
          {{ t('batchGenerate.startGenerate') }}
        </a-button>
      </div>
    </div>
  </a-modal>

  <!-- 图片预览大图 -->
  <div v-if="imagePreviewVisible" class="image-preview-overlay" @click.self="imagePreviewVisible = false">
    <div class="preview-toolbar">
      <span class="preview-index">{{ currentPreviewIndex + 1 }} / {{ previewImages.length }}</span>
      <a-space>
        <a-button @click="previewPrevious" :disabled="currentPreviewIndex <= 0" size="small">
          <template #icon><LeftOutlined /></template>
          {{ t('batchGenerate.previous') }}
        </a-button>
        <a-button @click="previewNext" :disabled="currentPreviewIndex >= previewImages.length - 1" size="small">
          {{ t('batchGenerate.next') }}
          <template #icon><RightOutlined /></template>
        </a-button>
        <a-button type="primary" @click="downloadCurrentPreview" size="small">
          <DownloadOutlined />
          {{ t('batchGenerate.download') }}
        </a-button>
        <a-button @click="imagePreviewVisible = false" size="small">
          <CloseOutlined />
        </a-button>
      </a-space>
    </div>
    <img :src="previewImageUrl" alt="preview" class="preview-large-image" @click.self="imagePreviewVisible = false" />
  </div>
</template>

<script setup lang="ts">
import { ref, computed } from 'vue';
import { useI18n } from 'vue-i18n';
import { message } from 'ant-design-vue';
import {
  FileImageOutlined,
  ScanOutlined,
  CodeOutlined,
  CopyOutlined,
  DeleteOutlined,
  EyeOutlined,
  FontSizeOutlined,
  PictureOutlined,
  CheckCircleOutlined,
  InfoCircleOutlined,
  EditOutlined,
  CloudUploadOutlined,
  ReloadOutlined,
  WarningOutlined,
  CheckOutlined,
  DownloadOutlined,
  BorderOutlined,
  LeftOutlined,
  RightOutlined,
  ZoomInOutlined,
  CloseOutlined,
} from '@ant-design/icons-vue';
import type { LocalTemplate } from '../types';
import { fabric } from 'fabric';

const { t } = useI18n();

const emit = defineEmits<{
  (e: 'close'): void;
}>();

// Props
interface Props {
  template?: LocalTemplate;
}

const props = defineProps<Props>();

// 状态
const visible = ref(false);
const selectedTemplate = ref<LocalTemplate | null>(null);
const batchCount = ref(1);
const activeBatchTab = ref('batch-1');
const exportFormat = ref<'png' | 'jpg'>('png');
const currentStep = ref(1);
const generating = ref(false);
const batchValues = ref<Record<string, any>[]>([{}]);
const previewImages = ref<string[]>([]);
const previewLoading = ref(false);

// 图片预览相关状态
const imagePreviewVisible = ref(false);
const previewImageUrl = ref('');
const currentPreviewIndex = ref(0);

let previewCanvas: fabric.StaticCanvas | null = null;
let isComponentMounted = true;

// 计算属性
const hasFirstItemValues = computed(() => {
  const firstItem = batchValues.value[0];
  return firstItem && Object.keys(firstItem).length > 0;
});

const canPreview = computed(() => {
  if (!selectedTemplate.value) return false;
  for (let i = 0; i < batchCount.value; i++) {
    const values = batchValues.value[i];
    const hasEmpty = selectedTemplate.value.dynamicVariables.some((v: any) => !values[v.variableName]);
    if (hasEmpty) return false;
  }
  return true;
});

const canGenerate = computed(() => previewImages.value.length > 0);

// 方法
const open = (template: LocalTemplate) => {
  selectedTemplate.value = template;
  batchCount.value = 1;
  initBatchValues();
  activeBatchTab.value = 'batch-1';
  currentStep.value = 1;
  previewImages.value = [];
  visible.value = true;
};

const handleCancel = () => {
  visible.value = false;
  currentStep.value = 1;
  previewImages.value = [];
  selectedTemplate.value = null;
  emit('close');
};

const initBatchValues = () => {
  const values: Record<string, any>[] = [];
  for (let i = 0; i < batchCount.value; i++) {
    const itemValues: Record<string, any> = {};
    // 填充默认值
    if (selectedTemplate.value?.dynamicVariables) {
      selectedTemplate.value.dynamicVariables.forEach((variable: any) => {
        if (variable.defaultValue) {
          itemValues[variable.variableName] = variable.defaultValue;
        }
      });
    }
    values.push(itemValues);
  }
  batchValues.value = values;
  previewImages.value = [];
};

const onBatchCountChange = () => {
  const oldValues = [...batchValues.value];
  const newValues: Record<string, any>[] = [];
  for (let i = 0; i < batchCount.value; i++) {
    if (oldValues[i]) {
      // 保留已有的值
      newValues.push(oldValues[i]);
    } else {
      // 新增的项填入默认值
      const itemValues: Record<string, any> = {};
      if (selectedTemplate.value?.dynamicVariables) {
        selectedTemplate.value.dynamicVariables.forEach((variable: any) => {
          if (variable.defaultValue) {
            itemValues[variable.variableName] = variable.defaultValue;
          }
        });
      }
      newValues.push(itemValues);
    }
  }
  batchValues.value = newValues;
  previewImages.value = [];
};

const copyFirstToAll = () => {
  const firstItem = batchValues.value[0];
  if (!firstItem) return;
  batchValues.value = Array(batchCount.value).fill(null).map(() => ({ ...firstItem }));
  message.success(t('batchGenerate.copiedToAll'));
};

const clearAllValues = () => {
  batchValues.value = Array(batchCount.value).fill({});
  message.success(t('batchGenerate.cleared'));
};


const handleImageUpload = (index: number, varName: string, file: File) => {
  if (!file.type.startsWith('image/')) {
    message.error(t('batchGenerate.uploadImageTip'));
    return false;
  }
  if (file.size > 10 * 1024 * 1024) {
    message.error(t('batchGenerate.imageSizeTip'));
    return false;
  }

  const reader = new FileReader();
  reader.onload = (e) => {
    batchValues.value[index][varName] = e.target?.result as string;
  };
  reader.readAsDataURL(file);
  return false;
};

const getCompletionProgress = () => {
  if (!selectedTemplate.value) return 0;
  let totalVars = 0;
  let filledVars = 0;

  for (let i = 0; i < batchCount.value; i++) {
    selectedTemplate.value.dynamicVariables.forEach((v: any) => {
      totalVars++;
      if (batchValues.value[i]?.[v.variableName]) {
        filledVars++;
      }
    });
  }

  return totalVars === 0 ? 0 : Math.round((filledVars / totalVars) * 100);
};

const getProgressColor = () => {
  const percent = getCompletionProgress();
  if (percent < 30) return '#ff4d4f';
  if (percent < 70) return '#faad14';
  return '#52c41a';
};

const getCompletionText = () => {
  if (!selectedTemplate.value) return '';
  let totalVars = 0;
  let filledVars = 0;

  for (let i = 0; i < batchCount.value; i++) {
    selectedTemplate.value.dynamicVariables.forEach((v: any) => {
      totalVars++;
      if (batchValues.value[i]?.[v.variableName]) {
        filledVars++;
      }
    });
  }

  return `${filledVars}/${totalVars}`;
};

const getTabLabel = (index: number) => {
  return `${t('batchGenerate.item')} #${index}`;
};

// 初始化预览 canvas
const initPreviewCanvas = async (): Promise<fabric.StaticCanvas | null> => {
  if (!selectedTemplate.value) return null;

  if (previewCanvas) {
    previewCanvas.dispose();
    previewCanvas = null;
  }

  const templateJson = selectedTemplate.value.json;
  // 统一尺寸获取逻辑，优先级：clipPath > 顶层 width/height > workspace
  const clipPath = templateJson?.clipPath;
  const workspaceObj = templateJson?.objects?.find((obj: any) => obj.id === 'workspace');
  const canvasWidth = clipPath?.width ?? templateJson?.width ?? workspaceObj?.width ?? 800;
  const canvasHeight = clipPath?.height ?? templateJson?.height ?? workspaceObj?.height ?? 600;

  previewCanvas = new fabric.StaticCanvas(null, {
    width: canvasWidth,
    height: canvasHeight,
    backgroundColor: '#ffffff',
    enableRetinaScaling: true,
  });

  return previewCanvas;
};

// 替换模版中的变量
const replaceVariablesInTemplate = (templateJson: any, values: Record<string, any>): any => {
  const json = JSON.parse(JSON.stringify(templateJson));

  const clipPath = json.clipPath;
  const workspaceObj = json.objects?.find((obj: any) => obj.id === 'workspace');
  // 统一从 json（深拷贝后的对象）获取尺寸，避免混合引用
  const jsonWidth = clipPath?.width ?? json.width ?? workspaceObj?.width ?? 800;
  const jsonHeight = clipPath?.height ?? json.height ?? workspaceObj?.height ?? 600;
  json.width = jsonWidth;
  json.height = jsonHeight;

  if (json.objects) {
    json.objects.forEach((obj: any) => {
      if (obj.dynamicConfig && obj.dynamicConfig.isDynamic) {
        const varName = obj.dynamicConfig.variableName;
        const varType = obj.dynamicConfig.variableType;
        const value = values[varName];

        if (value) {
          if (varType === 'text' && (obj.type === 'textbox' || obj.type === 'text' || obj.type === 'i-text')) {
            obj.text = value;
          }
        }
      }
    });
  }

  return json;
};

// 生成单个预览图片
const generatePreviewImage = async (
  templateJson: any,
  values: Record<string, any>
): Promise<string> => {
  return new Promise(async (resolve, reject) => {
    const canvas = previewCanvas;
    if (!canvas) {
      reject(new Error('Canvas not initialized'));
      return;
    }

    try {
      const modifiedJson = replaceVariablesInTemplate(templateJson, values);

      const tempCanvas = new fabric.StaticCanvas(null, {
        width: modifiedJson.width,
        height: modifiedJson.height,
        backgroundColor: '#ffffff',
      });

      await new Promise((res) => tempCanvas.loadFromJSON(modifiedJson, res));

      const objects = tempCanvas.getObjects();
      const replacements: Promise<void>[] = [];

      for (const obj of objects) {
        const config = (obj as any).dynamicConfig;
        if (!config || !config.isDynamic) continue;

        const value = values[config.variableName];
        if (!value) continue;

        if (config.variableType === 'text') {
          obj.set('text', value);
          obj.setCoords();
          continue;
        }

        if (config.variableType === 'image') {
          const promise = new Promise<void>((resolveImg) => {
            fabric.Image.fromURL(
              value,
              (img) => {
                if (!img) {
                  console.warn('图片加载失败:', value);
                  resolveImg();
                  return;
                }

                // 计算新图片需要的基础缩放比例（使其尺寸匹配原对象的原始尺寸）
                const baseScaleX = (obj.width || 1) / (img.width || 1);
                const baseScaleY = (obj.height || 1) / (img.height || 1);

                // 深度复制 clipPath（如果存在）
                let clonedClipPath = obj.clipPath;
                if (clonedClipPath && typeof clonedClipPath.toJSON === 'function') {
                  try {
                    clonedClipPath = fabric.util.object.clone(clonedClipPath);
                  } catch (e) {
                    console.warn('clipPath 克隆失败，使用原引用');
                  }
                }

                // 深度复制 shadow（如果存在）
                let clonedShadow = obj.shadow;
                if (clonedShadow && typeof clonedShadow === 'object') {
                  clonedShadow = { ...clonedShadow };
                }

                // 获取原对象的所有需要复制的属性
                const propsToCopy: any = {
                  // 基础位置属性
                  left: obj.left,
                  top: obj.top,
                  angle: obj.angle,
                  originX: obj.originX || 'left',
                  originY: obj.originY || 'top',
                  // 保持原对象的缩放比例（这是关键！）
                  scaleX: baseScaleX * (obj.scaleX || 1),
                  scaleY: baseScaleY * (obj.scaleY || 1),
                  // 复制裁剪路径（深度复制）
                  clipPath: clonedClipPath,
                  // 复制裁剪偏移
                  cropX: obj.cropX || 0,
                  cropY: obj.cropY || 0,
                  // 复制倾斜
                  skewX: obj.skewX || 0,
                  skewY: obj.skewY || 0,
                  // 复制翻转
                  flipX: obj.flipX || false,
                  flipY: obj.flipY || false,
                  // 复制透明度
                  opacity: obj.opacity ?? 1,
                  // 复制阴影（深度复制）
                  shadow: clonedShadow,
                  // 复制混合模式
                  composite: obj.composite,
                  globalCompositeOperation: obj.globalCompositeOperation,
                  // 复制其他样式属性
                  stroke: obj.stroke,
                  strokeWidth: obj.strokeWidth,
                  strokeDashArray: obj.strokeDashArray,
                  strokeLineCap: obj.strokeLineCap,
                  strokeLineJoin: obj.strokeLineJoin,
                  strokeMiterLimit: obj.strokeMiterLimit,
                  strokeUniform: obj.strokeUniform,
                  // 性能和渲染相关
                  dirty: true,
                  objectCaching: obj.objectCaching,
                  noScaleCache: obj.noScaleCache,
                  // 复制可选择性
                  selectable: obj.selectable,
                  evented: obj.evented,
                  moveable: obj.moveable,
                  hasControls: obj.hasControls,
                  // 复制自定义属性
                  id: (obj as any).id,
                  name: (obj as any).name,
                  description: (obj as any).description,
                  data: (obj as any).data,
                  dynamicConfig: (obj as any).dynamicConfig,
                };

                // 应用所有属性到新图片
                Object.keys(propsToCopy).forEach(key => {
                  if (propsToCopy[key] !== undefined) {
                    (img as any)[key] = propsToCopy[key];
                  }
                });

                // 更新坐标（重要！）
                img.setCoords();

                // 找到原对象在 canvas 中的位置并替换
                const index = tempCanvas.getObjects().indexOf(obj);
                if (index !== -1) {
                  tempCanvas.remove(obj);
                  tempCanvas.insertAt(img, index, false);
                }

                resolveImg();
              },
              null,
              { crossOrigin: 'anonymous' }
            );
          });

          replacements.push(promise);
        }
      }

      await Promise.all(replacements);
      tempCanvas.renderAll();

      const dataURL = tempCanvas.toDataURL({
        format: exportFormat.value === 'png' ? 'png' : 'jpeg',
        quality: exportFormat.value === 'jpg' ? 0.9 : 1,
      });

      tempCanvas.dispose();
      resolve(dataURL);
    } catch (error) {
      reject(error);
    }
  });
};

// 预览批量生成
const previewBatch = async () => {
  if (!selectedTemplate.value) return;

  for (let i = 0; i < batchCount.value; i++) {
    const values = batchValues.value[i];
    const hasEmpty = selectedTemplate.value.dynamicVariables.some((v: any) => !values[v.variableName]);

    if (hasEmpty) {
      message.warning(`${t('batchGenerate.item')} #${i + 1} ${t('batchGenerate.hasEmptyVars')}`);
      activeBatchTab.value = `batch-${i + 1}`;
      return;
    }
  }

  previewLoading.value = true;
  currentStep.value = 2;
  previewImages.value = [];

  try {
    const canvas = await initPreviewCanvas();
    if (!canvas) {
      message.error(t('batchGenerate.previewInitFailed'));
      currentStep.value = 1;
      return;
    }

    const images: string[] = [];
    for (let i = 0; i < batchCount.value; i++) {
      if (!isComponentMounted) break;
      try {
        const img = await generatePreviewImage(selectedTemplate.value.json, batchValues.value[i]);
        images.push(img);
        if (isComponentMounted) {
          previewImages.value = images;
        }
      } catch (error: any) {
        console.error(`生成预览 #${i + 1} 失败:`, error);
        if (error.message && error.message.includes('CORS')) {
          message.error(`${t('batchGenerate.item')} #${i + 1} ${t('batchGenerate.corsFailed')}`);
          break;
        }
      }
    }

    if (!isComponentMounted) return;

    if (images.length > 0) {
      message.success(`${t('batchGenerate.generated')} ${images.length} ${t('batchGenerate.generatedCount')}`);
      currentStep.value = 2;
    } else {
      message.warning(t('batchGenerate.previewFailed'));
    }
  } catch (error) {
    console.error('预览生成失败:', error);
    message.error(t('batchGenerate.previewFailed'));
  } finally {
    if (isComponentMounted) {
      previewLoading.value = false;
    }
  }
};

// 处理批量生成
const handleBatchGenerate = async () => {
  if (!selectedTemplate.value) return;

  for (let i = 0; i < batchCount.value; i++) {
    const values = batchValues.value[i];
    const hasEmpty = selectedTemplate.value.dynamicVariables.some((v: any) => !values[v.variableName]);

    if (hasEmpty) {
      message.warning(`${t('batchGenerate.item')} #${i + 1} ${t('batchGenerate.hasEmptyVars')}`);
      activeBatchTab.value = `batch-${i + 1}`;
      return;
    }
  }

  if (previewImages.value.length === 0) {
    await previewBatch();
  }

  currentStep.value = 3;
  generating.value = true;

  try {
    for (let i = 0; i < previewImages.value.length; i++) {
      downloadImage(previewImages.value[i], i);
      await new Promise(resolve => setTimeout(resolve, 300));
    }

    message.success(`${t('batchGenerate.generatedAndDownloaded')} ${batchCount.value} ${t('batchGenerate.images')}`);

    setTimeout(() => {
      handleCancel();
    }, 1500);
  } catch (error) {
    message.error(t('batchGenerate.generateFailed'));
  } finally {
    generating.value = false;
  }
};

// 下载单张图片
const downloadImage = (dataUrl: string, index: number) => {
  const link = document.createElement('a');
  link.href = dataUrl;
  link.download = `${selectedTemplate.value?.name}_${index + 1}.${exportFormat.value}`;
  link.click();
};

// 下载全部图片
const downloadAllImages = () => {
  previewImages.value.forEach((img, idx) => {
    setTimeout(() => {
      downloadImage(img, idx);
    }, idx * 300);
  });
};

// 打开图片预览
const openImagePreview = (imageUrl: string, index: number) => {
  previewImageUrl.value = imageUrl;
  currentPreviewIndex.value = index;
  imagePreviewVisible.value = true;
};

// 上一张预览
const previewPrevious = () => {
  if (currentPreviewIndex.value > 0) {
    currentPreviewIndex.value--;
    previewImageUrl.value = previewImages.value[currentPreviewIndex.value];
  }
};

// 下一张预览
const previewNext = () => {
  if (currentPreviewIndex.value < previewImages.value.length - 1) {
    currentPreviewIndex.value++;
    previewImageUrl.value = previewImages.value[currentPreviewIndex.value];
  }
};

// 下载当前预览的图片
const downloadCurrentPreview = () => {
  downloadImage(previewImageUrl.value, currentPreviewIndex.value);
};

// 监听组件卸载
import { onUnmounted } from 'vue';
onUnmounted(() => {
  isComponentMounted = false;
  if (previewCanvas) {
    previewCanvas.dispose();
    previewCanvas = null;
  }
});

defineExpose({
  open,
});
</script>

<style scoped lang="less">
.batch-modal-content {
  max-height: 70vh;
  overflow-y: auto;
}

.steps-indicator {
  display: flex;
  align-items: center;
  justify-content: center;
  margin-bottom: 24px;
  padding: 0 40px;

  .step-item {
    display: flex;
    flex-direction: column;
    align-items: center;
    gap: 8px;

    .step-number {
      width: 32px;
      height: 32px;
      border-radius: 50%;
      background: #e8e8e8;
      color: #999;
      display: flex;
      align-items: center;
      justify-content: center;
      font-weight: 600;
      transition: all 0.3s;
    }

    .step-text {
      font-size: 12px;
      color: #999;
      transition: all 0.3s;
    }

    &.active .step-number {
      background: #1890ff;
      color: #fff;
    }

    &.active .step-text {
      color: #1890ff;
    }

    &.completed .step-number {
      background: #52c41a;
      color: #fff;
    }
  }

  .step-line {
    width: 60px;
    height: 2px;
    background: #e8e8e8;
    margin: 0 8px;
    margin-top: -16px;

    &.active {
      background: #52c41a;
    }
  }
}

.template-info-card {
  background: #f0f9ff;
  border: 1px solid #bae7ff;
  border-radius: 8px;
  padding: 16px;
  margin-bottom: 20px;

  .template-info-main {
    display: flex;
    align-items: center;
    gap: 16px;

    .template-icon {
      flex-shrink: 0;
    }

    .template-details {
      flex: 1;

      .template-name {
        margin: 0 0 8px 0;
        font-size: 16px;
        font-weight: 600;
      }

      .template-meta {
        display: flex;
        align-items: center;
        gap: 8px;
        font-size: 14px;
        color: #666;

        .meta-item {
          display: flex;
          align-items: center;
          gap: 4px;
        }

        .meta-divider {
          color: #d9d9d9;
        }
      }
    }
  }
}

.quick-actions-bar {
  display: flex;
  justify-content: space-between;
  align-items: center;
  padding: 16px;
  background: #fafafa;
  border-radius: 8px;
  margin-bottom: 20px;

  .actions-group {
    display: flex;
    align-items: center;
    gap: 16px;

    .action-item {
      display: flex;
      align-items: center;
      gap: 8px;

      .action-label {
        white-space: nowrap;
        font-size: 14px;
      }
    }
  }
}

.variables-section {
  margin-bottom: 20px;

  .section-header-with-progress {
    display: flex;
    justify-content: space-between;
    align-items: center;
    margin-bottom: 16px;

    .header-left {
      display: flex;
      align-items: center;
      gap: 16px;

      h4 {
        margin: 0;
      }

      .progress-indicator {
        display: flex;
        align-items: center;
        gap: 8px;

        .progress-text {
          font-size: 12px;
          color: #666;
        }
      }
    }
  }

  .batch-tabs {
    :deep(.ant-tabs-nav) {
      margin-bottom: 16px;
    }
  }

  .batch-item-content {
    .variables-grid {
      display: grid;
      grid-template-columns: repeat(auto-fill, minmax(300px, 1fr));
      gap: 16px;
    }

    .variable-card {
      border: 1px solid #e8e8e8;
      border-radius: 8px;
      padding: 12px;
      background: #fff;

      &.has-value {
        border-color: #52c41a;
        background: #f6ffed;
      }

      &.is-required {
        border-color: #ff4d4f;
      }

      .variable-header {
        display: flex;
        align-items: center;
        gap: 8px;
        margin-bottom: 8px;

        .variable-type-icon {
          width: 28px;
          height: 28px;
          border-radius: 4px;
          display: flex;
          align-items: center;
          justify-content: center;

          &.type-text {
            background: #e6f7ff;
            color: #1890ff;
          }

          &.type-image {
            background: #f6ffed;
            color: #52c41a;
          }
        }

        .variable-info {
          flex: 1;
          display: flex;
          align-items: center;
          gap: 8px;

          .variable-name {
            font-weight: 500;
          }
        }
      }

      .variable-remark {
        display: flex;
        align-items: center;
        gap: 4px;
        font-size: 12px;
        color: #999;
        margin-bottom: 8px;
      }

      .variable-input-area {
        .image-input-area {
          .upload-trigger {
            width: 100%;
            min-height: 120px;
            border: 2px dashed #d9d9d9;
            border-radius: 8px;
            cursor: pointer;
            transition: all 0.3s;
            overflow: hidden;

            &:hover {
              border-color: #1890ff;
            }

            .upload-placeholder {
              display: flex;
              flex-direction: column;
              align-items: center;
              justify-content: center;
              padding: 24px;
              color: #999;
            }

            .upload-preview {
              position: relative;

              img {
                width: 100%;
                height: 120px;
                object-fit: cover;
              }

              .upload-overlay {
                position: absolute;
                top: 0;
                left: 0;
                right: 0;
                bottom: 0;
                background: rgba(0, 0, 0, 0.5);
                display: flex;
                flex-direction: column;
                align-items: center;
                justify-content: center;
                color: #fff;
                opacity: 0;
                transition: opacity 0.3s;
              }

              &:hover .upload-overlay {
                opacity: 1;
              }
            }
          }
        }
      }
    }

    .empty-variables {
      text-align: center;
      padding: 60px 20px;
      color: #999;
    }
  }
}

.preview-section {
  .section-header-with-actions {
    display: flex;
    justify-content: space-between;
    align-items: center;
    margin-bottom: 16px;

    h4 {
      margin: 0;
    }

    .header-actions {
      display: flex;
      align-items: center;
      gap: 12px;
    }
  }

  .preview-grid-modern {
    display: grid;
    grid-template-columns: repeat(auto-fill, minmax(200px, 1fr));
    gap: 16px;

    .preview-card {
      border: 1px solid #e8e8e8;
      border-radius: 8px;
      overflow: hidden;
      background: #fff;

      .preview-image-wrapper {
        position: relative;
        width: 100%;
        height: 200px;
        background: #f5f5f5;
        cursor: pointer;

        .preview-image {
          width: 100%;
          height: 100%;
          object-fit: contain;
        }

        .preview-overlay {
          position: absolute;
          top: 0;
          left: 0;
          right: 0;
          bottom: 0;
          background: rgba(0, 0, 0, 0.7);
          display: flex;
          flex-direction: column;
          align-items: center;
          justify-content: center;
          opacity: 0;
          transition: opacity 0.3s;

          .preview-number {
            color: #fff;
            font-size: 24px;
            font-weight: 600;
            margin-bottom: 12px;
          }

          .preview-hint {
            display: flex;
            align-items: center;
            gap: 8px;
            color: #fff;
            font-size: 14px;
            opacity: 0.8;
          }
        }

        &:hover .preview-overlay {
          opacity: 1;
        }
      }

      .preview-footer {
        padding: 8px 12px;
        border-top: 1px solid #e8e8e8;

        .preview-name {
          font-size: 12px;
          color: #666;
          white-space: nowrap;
          overflow: hidden;
          text-overflow: ellipsis;
        }
      }
    }
  }

  .preview-empty-modern {
    text-align: center;
    padding: 60px 20px;

    .empty-illustration {
      .empty-icon-circle {
        width: 100px;
        height: 100px;
        border-radius: 50%;
        background: #f5f5f5;
        display: flex;
        align-items: center;
        justify-content: center;
        margin: 0 auto 16px;
      }
    }

    h4 {
      margin: 0 0 8px 0;
      color: #999;
    }

    p {
      margin: 0;
      color: #ccc;
    }
  }
}

.modal-footer-custom {
  display: flex;
  justify-content: space-between;
  align-items: center;
  padding: 16px 0 0;
  border-top: 1px solid #e8e8e8;
  margin-top: 16px;

  .footer-left,
  .footer-right {
    display: flex;
    gap: 12px;
  }
}
</style>

<style lang="less">
// 图片预览 Modal 样式（非 scoped）
.image-preview-modal {
  .ant-modal-body {
    padding: 0;
  }

  .ant-modal-close {
    top: 8px;
    right: 8px;
  }
}

.image-preview-container {
  display: flex;
  flex-direction: column;
  align-items: center;
  padding: 24px;
  background: #000;

  .preview-large-image {
    max-width: 100%;
    max-height: 70vh;
    object-fit: contain;
    border-radius: 4px;
  }

  .preview-info {
    display: flex;
    justify-content: space-between;
    align-items: center;
    width: 100%;
    margin-top: 16px;
    padding-top: 16px;
    border-top: 1px solid #333;

    .preview-index {
      color: #fff;
      font-size: 14px;
    }
  }
}

.image-preview-overlay {
  position: fixed;
  top: 0;
  left: 0;
  right: 0;
  bottom: 0;
  z-index: 2000;
  display: flex;
  flex-direction: column;
  align-items: center;
  justify-content: center;
  background: rgba(255, 255, 255, 0.95);

  .preview-toolbar {
    display: flex;
    align-items: center;
    gap: 16px;
    margin-bottom: 16px;

    .preview-index {
      font-size: 14px;
      color: #666;
    }
  }

  .preview-large-image {
    max-width: 85%;
    max-height: 80vh;
    object-fit: contain;
    border-radius: 4px;
    box-shadow: 0 4px 20px rgba(0, 0, 0, 0.15);
  }
}
</style>
