<template>
  <div class="color-picker-wrapper">
    <div class="color-input-group">
      <div class="color-preview" :style="{ background: colorValue }" @click="openColorPicker"></div>
      <a-input
        :value="displayColor"
        readonly
        @click="openColorPicker"
        class="color-input"
      />
      <input
        type="color"
        ref="colorInputRef"
        v-model="nativeColorValue"
        @input="handleNativeInput"
        style="visibility: hidden; position: absolute; width: 0; height: 0;"
      />
    </div>
    <div v-if="showPicker" class="color-picker-dropdown" @click.stop>
      <div class="color-picker-header">
        <span>选择颜色</span>
        <a-button type="link" size="small" @click="closeColorPicker">关闭</a-button>
      </div>
      <div class="color-picker-body">
        <div class="preset-colors">
          <div
            v-for="preset in presetColors"
            :key="preset"
            class="preset-color-item"
            :class="{ selected: colorValue === preset }"
            :style="{ background: preset }"
            @click="selectColor(preset)"
          ></div>
        </div>
        <div class="custom-color-section">
          <span>自定义颜色:</span>
          <div class="color-input-wrapper">
            <input
              type="color"
              v-model="nativeColorValue"
              @input="handleNativeInput"
              class="native-color-input"
            />
            <span class="color-value-display">{{ nativeColorValue }}</span>
          </div>
        </div>
      </div>
      <div class="color-picker-footer">
        <a-button size="small" @click="closeColorPicker">取消</a-button>
        <a-button type="primary" size="small" @click="confirmColor">确定</a-button>
      </div>
    </div>
    <!-- 渐变模式选择器 - 暂时注释
    <div v-if="modes && modes.length > 1" class="mode-selector">
      <div
        v-for="mode in modes"
        :key="mode"
        :class="['mode-item', { active: currentMode === mode }]"
        @click="switchMode(mode)"
      >
        {{ mode }}
      </div>
    </div>
    -->
  </div>
</template>

<script setup>
import { ref, computed, watch, onMounted, onUnmounted } from 'vue';

const props = defineProps({
  value: {
    type: String,
    default: '#ffffff',
  },
  modelValue: {
    type: String,
    default: '#ffffff',
  },
  modes: {
    type: Array,
    default: () => [],
  },
  alpha: {
    type: Boolean,
    default: true,
  },
});

const emit = defineEmits(['update:value', 'update:modelValue', 'change', 'nativePick', 'ok']);

// 监听 props 变化
watch(() => props.value, (newVal) => {
  console.log('props.value changed:', newVal);
});
watch(() => props.modelValue, (newVal) => {
  console.log('props.modelValue changed:', newVal);
});

const colorValue = computed({
  get: () => {
    const val = props.modelValue !== undefined ? props.modelValue : props.value;
    console.log('colorValue getter called, val:', val);
    return val;
  },
  set: (val) => {
    console.log('colorValue setter called, val:', val);
    if (props.modelValue !== undefined) {
      emit('update:modelValue', val);
    } else {
      emit('update:value', val);
    }
  },
});

const showPicker = ref(false);
const colorInputRef = ref(null);
// const currentMode = ref(props.modes && props.modes.length > 0 ? props.modes[0] : '');

// 显示值（渐变色显示为"渐变"，纯色显示颜色值）
const displayColor = computed(() => {
  if (!colorValue.value) return '#ffffff';
  // 渐变功能暂时注释
  // if (colorValue.value.startsWith('linear-gradient')) {
  //   return '渐变';
  // }
  return colorValue.value;
});

// 预设颜色
const presetColors = ref([
  '#ffffff', '#000000', '#ff0000', '#ff8000', '#ffff00',
  '#00ff00', '#00ffff', '#0080ff', '#0000ff', '#8000ff',
  '#ff00ff', '#ff0080', '#808080', '#c0c0c0', '#800000',
  '#808000', '#008000', '#008080', '#000080', '#800080',
]);

// 原生颜色输入值 (只支持 hex)
const nativeColorValue = ref('#ffffff');

// 监听颜色值变化，同步到原生输入
watch(() => colorValue.value, (newVal) => {
  console.log('watch 触发, newVal:', newVal);
  // 只有在有有效颜色值时才更新
  if (newVal && typeof newVal === 'string' && newVal.trim() !== '') {
    const hexColor = rgbaToHex(newVal);
    console.log('转换后的 hexColor:', hexColor);
    nativeColorValue.value = hexColor;
  }
}, { immediate: true });

// 将 rgba 转换为 hex
function rgbaToHex(color) {
  console.log('rgbaToHex 输入:', color);

  // 处理 null、undefined 或空值
  if (!color || typeof color !== 'string') {
    return '#ffffff';
  }

  // 处理渐变色，返回第一个颜色
  if (color.startsWith('linear-gradient')) {
    // 匹配 hex 颜色或 rgb/rgba 颜色
    const hexMatch = color.match(/#[0-9a-fA-F]{6}/gi);
    if (hexMatch) {
      return hexMatch[0];
    }
    const rgbMatch = color.match(/rgba?\(\s*\d+\s*,\s*\d+\s*,\s*\d+\s*(,\s*[\d.]+\s*)?\)/g);
    if (rgbMatch) {
      return rgbaToHex(rgbMatch[0]);
    }
    return '#ffffff';
  }

  // 已经是 hex 格式（包括 8 位 hex）
  if (color.startsWith('#')) {
    // 如果是 8 位 hex（如 #ffffffff），转换为 6 位
    if (color.length === 9) {
      const hex6 = color.substring(0, 7);
      console.log('8位hex转6位:', color, '->', hex6);
      return hex6;
    }
    console.log('返回 hex:', color);
    return color;
  }

  // 处理 rgb/rgba 格式
  if (color.startsWith('rgb')) {
    const parts = color.match(/\d+/g);
    if (parts && parts.length >= 3) {
      const r = parseInt(parts[0]).toString(16).padStart(2, '0');
      const g = parseInt(parts[1]).toString(16).padStart(2, '0');
      const b = parseInt(parts[2]).toString(16).padStart(2, '0');
      const hex = `#${r}${g}${b}`;
      console.log('RGB 转换为 HEX:', color, '->', hex);
      return hex;
    }
  }

  return '#ffffff';
}

function openColorPicker() {
  showPicker.value = true;
  // 打开颜色选择器时，强制同步当前颜色值到原生输入框
  // 使用 setTimeout 确保 DOM 更新后再设置
  setTimeout(() => {
    if (colorValue.value && colorValue.value.trim() !== '') {
      nativeColorValue.value = rgbaToHex(colorValue.value);
    }
  }, 0);
}

function closeColorPicker() {
  showPicker.value = false;
}

function selectColor(color) {
  colorValue.value = color;
  nativeColorValue.value = color; // 同步更新原生颜色输入框
  emit('change', { color, mode: '纯色' });
}

function handleNativeInput(e) {
  const color = e.target.value;
  colorValue.value = color;
  nativeColorValue.value = color; // 同步更新
  emit('nativePick', { color, mode: '纯色' });
}

function confirmColor() {
  emit('ok', { color: colorValue.value, mode: '纯色' });
  emit('change', { color: colorValue.value, mode: '纯色' });
  closeColorPicker();
}

// 渐变模式切换 - 暂时注释
// function switchMode(mode) {
//   currentMode.value = mode;
// }

// 点击外部关闭弹窗
function handleClickOutside(e) {
  const pickerEl = document.querySelector('.color-picker-dropdown');
  if (pickerEl && !pickerEl.contains(e.target) && !e.target.closest('.color-input-group')) {
    closeColorPicker();
  }
}

onMounted(() => {
  document.addEventListener('click', handleClickOutside);
  // 初始化原生颜色值
  nativeColorValue.value = rgbaToHex(colorValue.value);
});

onUnmounted(() => {
  document.removeEventListener('click', handleClickOutside);
});
</script>

<style scoped lang="less">
.color-picker-wrapper {
  padding: 10px;
  position: relative;
}

.color-input-group {
  display: flex;
  align-items: center;
  gap: 8px;
}

.color-preview {
  width: 32px;
  height: 32px;
  border: 1px solid #d9d9d9;
  border-radius: 4px;
  cursor: pointer;
  flex-shrink: 0;
}

.color-input {
  flex: 1;
  cursor: pointer;
}

.color-picker-dropdown {
  position: absolute;
  top: 100%;
  left: 0;
  right: 0;
  background: white;
  border: 1px solid #d9d9d9;
  border-radius: 6px;
  box-shadow: 0 2px 8px rgba(0, 0, 0, 0.15);
  z-index: 1000;
  margin-top: 4px;
}

.color-picker-header {
  display: flex;
  justify-content: space-between;
  align-items: center;
  padding: 8px 12px;
  border-bottom: 1px solid #f0f0f0;
  font-weight: 500;
}

.color-picker-body {
  padding: 12px;
}

.preset-colors {
  display: grid;
  grid-template-columns: repeat(5, 1fr);
  gap: 6px;
  margin-bottom: 12px;
}

.preset-color-item {
  width: 100%;
  aspect-ratio: 1;
  border: 2px solid transparent;
  border-radius: 4px;
  cursor: pointer;
  transition: all 0.2s;

  &:hover {
    transform: scale(1.1);
  }

  &.selected {
    border-color: #1890ff;
    box-shadow: 0 0 0 2px rgba(24, 144, 255, 0.2);
  }
}

.custom-color-section {
  display: flex;
  align-items: center;
  gap: 8px;
  padding-top: 8px;
  border-top: 1px solid #f0f0f0;

  .color-input-wrapper {
    display: flex;
    align-items: center;
    gap: 8px;
    flex: 1;
  }

  .native-color-input {
    width: 60px;
    height: 32px;
    cursor: pointer;
  }

  .color-value-display {
    font-size: 12px;
    color: #666;
    font-family: monospace;
  }
}

.color-picker-footer {
  display: flex;
  justify-content: flex-end;
  gap: 8px;
  padding: 8px 12px;
  border-top: 1px solid #f0f0f0;
}

// 渐变模式选择器样式 - 暂时注释
// .mode-selector {
//   display: flex;
//   gap: 8px;
//   margin-top: 10px;
// }

// .mode-item {
//   padding: 4px 12px;
//   border: 1px solid #dcdee2;
//   border-radius: 4px;
//   cursor: pointer;
//   font-size: 12px;
//   transition: all 0.3s;

//   &:hover {
//     border-color: #2d8cf0;
//   }

//   &.active {
//     background: #2d8cf0;
//     color: #fff;
//     border-color: #2d8cf0;
//   }
// }
</style>
