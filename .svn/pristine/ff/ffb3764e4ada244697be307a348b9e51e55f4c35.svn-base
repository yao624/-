<template>
  <a-popover
    placement="bottomRight"
    arrowPointAtCenter
    trigger="click"
    overlayClassName="ant-pro-table-column-setting-overlay"
  >
    <template #title>
      <div class="ant-pro-table-column-setting-title">
        <a-checkbox
          v-model:checked="columnState.checkAll"
          :indeterminate="columnState.indeterminate"
          @change="handleColumnAllClick"
        >
          {{ t('pages.common.column_sort') }}
        </a-checkbox>
        <a @click="reset">{{ t('pages.common.reset') }}</a>
      </div>
    </template>
    <template #content>
      <div class="overlay">
        <span class="ant-pro-table-column-setting-list">
          <drag-container
            lockAxis="y"
            dragClass="ant-pro-table-drag-ghost"
            dropClass="ant-pro-table-drop-ghost"
            @drop="({ removedIndex, addedIndex }) => move(removedIndex, addedIndex)"
          >
            <template v-for="column in dynamicColumnItems" :key="column.key">
              <draggable>
                <div class="ant-pro-table-column-setting-list-item">
                  <drag-icon />
                  <a-checkbox
                    :checked="column.checked"
                    @change="handleColumnChange($event, column)"
                  >
                    {{ column.label }}
                  </a-checkbox>
                </div>
              </draggable>
            </template>
          </drag-container>
        </span>
      </div>
    </template>
    <a-tooltip :title="t('pages.common.column_setting')">
      <a-button shape="circle" :icon="h(SettingOutlined)" class="margin-sided"></a-button>
    </a-tooltip>
  </a-popover>
</template>
<script lang="ts">
import { Container as DragContainer, Draggable } from '@/components/draggable';
import { defineComponent, onMounted, ref, watch, h } from 'vue';
import { useTableDynamicColumns } from '@/utils/hooks/useTableColumn';
import { useI18n } from 'vue-i18n';
import type { TableColumn } from '@/typing';
import DragIcon from '@/components/table/drag-icon.vue';
import { SettingOutlined } from '@ant-design/icons-vue';

export default defineComponent({
  name: 'ColumnOrgnizer',
  components: {
    Draggable,
    DragContainer,
    DragIcon,
  },
  props: {
    columns: {
      type: Array<TableColumn>,
      required: true,
    },
  },
  emits: ['change:columns'],
  setup(props, { emit }) {
    const { t } = useI18n();

    const mergedColumns = ref<TableColumn[]>([]);
    const needRowIndex = ref(false);
    const {
      state: columnState,
      dynamicColumns,
      dynamicColumnItems,
      handleColumnAllClick,
      handleColumnChange,
      reset,
      move,
    } = useTableDynamicColumns(mergedColumns as any, {
      needRowIndex,
      defaultCheckedList: props.columns
        .map(({ dataIndex, key }) => {
          const keyValue = dataIndex || key;
          // 确保返回的是单个值而不是数组
          return Array.isArray(keyValue) ? keyValue[0] : keyValue;
        })
        .filter(Boolean),
    });

    onMounted(() => (mergedColumns.value = props.columns));

    watch(
      () => dynamicColumns,
      value => emit('change:columns', value),
      { deep: true },
    );

    return {
      columnState,
      dynamicColumns,
      dynamicColumnItems,
      handleColumnAllClick,
      handleColumnChange,
      SettingOutlined,
      reset,
      move,
      h,
      t,
    };
  },
});
</script>
<style scoped>
.margin-sided {
  margin: 0 8px;
}
.overlay {
  max-height: 392px;
  overflow-y: auto;
}
</style>
