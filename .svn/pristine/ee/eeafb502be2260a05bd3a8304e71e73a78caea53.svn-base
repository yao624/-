<template>
  <div class="xmp-rating-multi-select">
    <a-popover
      v-model:open="open"
      trigger="click"
      placement="bottomLeft"
      overlayClassName="xmp-rating-multi-select-popover"
      :overlayStyle="{ padding: '0' }"
      :overlayInnerStyle="{ padding: '0' }"
    >
      <template #content>
        <div class="panel" @mousedown.prevent @click.stop>
          <div class="panel-top">
            <span class="selected-count">{{ selectedCountLabel }}</span>
            <a-button type="link" size="small" class="clear-btn" :disabled="modelValueSafe.length === 0" @click="handleClear">
              {{ clearLabel }}
            </a-button>
          </div>

          <div class="panel-body">
            <a-checkbox-group :value="modelValueSafe" class="options-group" @change="handleGroupChange">
              <div v-for="v in optionValues" :key="String(v)" class="opt-row">
                <a-checkbox :value="v">
                  <a-rate class="opt-rate" :value="v" :allow-half="true" :disabled="true" />
                </a-checkbox>
              </div>
            </a-checkbox-group>
          </div>
        </div>
      </template>

      <div class="trigger-box" @click="open = true">
        <span class="trigger-text" :class="{ 'is-placeholder': modelValueSafe.length === 0 }">
          {{ displayValue || placeholder }}
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

interface Props {
  modelValue?: number[];
  placeholder?: string;
  allowClear?: boolean;
  clearLabel?: string;
}

interface Emits {
  (e: 'update:modelValue', value: number[]): void;
  (e: 'change', value: number[]): void;
}

const props = withDefaults(defineProps<Props>(), {
  modelValue: () => [],
  placeholder: '评分',
  allowClear: true,
  clearLabel: '清除',
});

const emit = defineEmits<Emits>();

const open = ref(false);
const modelValueSafe = computed(() => (Array.isArray(props.modelValue) ? props.modelValue : []));

const optionValues = computed(() => {
  const out: number[] = [];
  for (let v = 5; v >= 0.5; v -= 0.5) out.push(Number(v.toFixed(1)));
  return out;
});

const selectedCountLabel = computed(() => `已选 ${modelValueSafe.value.length} 个`);
const displayValue = computed(() => (modelValueSafe.value.length ? selectedCountLabel.value : undefined));

const handleGroupChange = (vals: any) => {
  const next = Array.isArray(vals) ? vals.map((x) => Number(x)).filter((x) => !Number.isNaN(x)) : [];
  emit('update:modelValue', next);
  emit('change', next);
};

const handleClear = () => {
  emit('update:modelValue', []);
  emit('change', []);
};
</script>

<style lang="less" scoped>
.xmp-rating-multi-select {
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
  width: 260px;
  background: #fff;
  border: 1px solid #e6e6e6;
  border-radius: 6px;
  box-shadow: 0 8px 24px rgba(0, 0, 0, 0.08);
  padding: 10px;
}

::global(.xmp-rating-multi-select-popover .ant-popover-inner) {
  padding: 0 !important;
}
::global(.xmp-rating-multi-select-popover .ant-popover-inner-content) {
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
.opt-rate :deep(.ant-rate-star) {
  margin-right: 3px;
}
</style>

