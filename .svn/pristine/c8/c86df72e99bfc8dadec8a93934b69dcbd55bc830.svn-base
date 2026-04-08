<template>
  <div class="xmp-system-tag-select">
    <a-popover
      v-model:open="open"
      trigger="click"
      placement="bottomLeft"
      overlayClassName="xmp-system-tag-select-popover"
      :overlayStyle="{ padding: '0' }"
      :overlayInnerStyle="{ padding: '0' }"
    >
      <template #content>
        <div class="panel" @mousedown.prevent @click.stop>
          <div class="panel-top">
            <a-radio-group v-model:value="modeInner" button-style="solid" size="small">
              <a-radio-button value="include">{{ includeLabel }}</a-radio-button>
              <a-radio-button value="exclude">{{ excludeLabel }}</a-radio-button>
            </a-radio-group>

            <div class="panel-top-right">
              <span class="selected-count">{{ selectedCountLabel }}</span>
              <a-button type="link" size="small" class="clear-btn" :disabled="modelValueSafe.length === 0" @click="handleClear">
                {{ clearLabel }}
              </a-button>
            </div>
          </div>

          <div class="panel-body">
            <a-checkbox-group :value="modelValueSafe" class="options-group" @change="handleGroupChange">
              <div v-for="opt in options" :key="String(opt.value)" class="opt-row">
                <a-checkbox :value="opt.value">{{ opt.label }}</a-checkbox>
              </div>
            </a-checkbox-group>
          </div>
        </div>
      </template>

      <div class="trigger-box" @click="open = true">
        <span class="trigger-text" :class="{ 'is-placeholder': !displayText }">
          {{ displayText || placeholder }}
        </span>
        <span class="trigger-icons">
          <close-circle-filled
            v-if="allowClear && modelValueSafe.length > 0"
            class="trigger-clear"
            @click.stop="handleClear"
          />
          <down-outlined class="trigger-arrow" />
        </span>
      </div>
    </a-popover>
  </div>
</template>

<script lang="ts" setup>
import { computed, ref } from 'vue';
import { CloseCircleFilled, DownOutlined } from '@ant-design/icons-vue';

export type SystemTagMode = 'include' | 'exclude';

export interface SystemTagOption {
  value: number;
  label: string;
}

interface Props {
  modelValue?: number[];
  mode?: SystemTagMode;
  options?: SystemTagOption[];
  placeholder?: string;
  allowClear?: boolean;
  includeLabel?: string;
  excludeLabel?: string;
  clearLabel?: string;
}

interface Emits {
  (e: 'update:modelValue', value: number[]): void;
  (e: 'update:mode', value: SystemTagMode): void;
  (e: 'change', payload: { values: number[]; mode: SystemTagMode }): void;
}

const props = withDefaults(defineProps<Props>(), {
  modelValue: () => [],
  mode: 'exclude',
  options: () => [],
  placeholder: '系统标签',
  allowClear: true,
  includeLabel: '包含',
  excludeLabel: '不含',
  clearLabel: '清除',
});

const emit = defineEmits<Emits>();

const open = ref(false);
const modelValueSafe = computed(() => (Array.isArray(props.modelValue) ? props.modelValue : []));
const options = computed(() => (Array.isArray(props.options) ? props.options : []));

const modeInner = computed<SystemTagMode>({
  get() {
    return props.mode;
  },
  set(v) {
    emit('update:mode', v);
    emit('change', { values: modelValueSafe.value, mode: v });
  },
});

const selectedCountLabel = computed(() => `已选 ${modelValueSafe.value.length} 个`);

const displayText = computed(() => {
  if (!modelValueSafe.value.length) return undefined;
  const labels = new Map(options.value.map((o) => [String(o.value), o.label]));
  const picked = modelValueSafe.value.map((v) => labels.get(String(v)) || String(v)).filter(Boolean);
  return `${modeInner.value === 'include' ? props.includeLabel : props.excludeLabel}: ${picked.join('、')}`;
});

const handleGroupChange = (vals: any) => {
  const next = Array.isArray(vals)
    ? vals
        .map((x) => Number(x))
        .filter((x) => Number.isFinite(x))
    : [];
  emit('update:modelValue', next);
  emit('change', { values: next, mode: modeInner.value });
};

const handleClear = () => {
  emit('update:modelValue', []);
  emit('change', { values: [], mode: modeInner.value });
};
</script>

<style lang="less" scoped>
.xmp-system-tag-select {
  width: 100%;
}

.trigger-box {
  width: 100%;
  height: 34px;
  border: 1px solid #d9d9d9;
  border-radius: 4px;
  background: #fff;
  padding: 0 11px;
  display: flex;
  align-items: center;
  justify-content: space-between;
  cursor: pointer;
}
.trigger-box:hover {
  border-color: #4096ff;
}
.trigger-text {
  font-size: 14px;
  color: #333;
  overflow: hidden;
  text-overflow: ellipsis;
  white-space: nowrap;
  padding-right: 8px;
}
.trigger-text.is-placeholder {
  color: #bfbfbf;
}
.trigger-icons {
  display: inline-flex;
  align-items: center;
  gap: 8px;
}
.trigger-clear {
  color: #bfbfbf;
  font-size: 12px;
}
.trigger-clear:hover {
  color: #8c8c8c;
}
.trigger-arrow {
  color: #8c8c8c;
  font-size: 12px;
}

.panel {
  width: 280px;
  background: #fff;
  border: 1px solid #e6e6e6;
  border-radius: 6px;
  box-shadow: 0 8px 24px rgba(0, 0, 0, 0.08);
  padding: 10px;
}

::global(.xmp-system-tag-select-popover .ant-popover-inner) {
  padding: 0 !important;
}
::global(.xmp-system-tag-select-popover .ant-popover-inner-content) {
  padding: 0 !important;
  width: 100%;
  height: 100%;
}

.panel-top {
  display: flex;
  align-items: center;
  justify-content: space-between;
  gap: 10px;
  padding-bottom: 8px;
}
.panel-top-right {
  display: inline-flex;
  align-items: center;
  gap: 10px;
  min-width: 0;
}
.selected-count {
  font-size: 14px;
  color: #333;
}
.clear-btn {
  padding: 0;
  color: #1890ff;
}

.panel-body {
  max-height: 360px;
  overflow: auto;
  border-top: 1px solid #f0f0f0;
  padding-top: 8px;
}
.options-group {
  width: 100%;
  display: block;
}
.opt-row {
  height: 34px;
  display: flex;
  align-items: center;
  padding: 0 4px;
}
.opt-row:hover {
  background: #fafafa;
}
</style>

