<template>
  <Chart renderer="svg" :option="option" :style="{ height: `${height}px`, width: '100%' }" />
</template>

<script lang="ts" setup>
import { computed } from 'vue';
import type { EChartOption } from 'echarts';
import { Chart } from '@/components';

export type DonutDataItem = {
  name: string;
  value: number;
  color?: string;
};

const props = withDefaults(
  defineProps<{
    data: DonutDataItem[];
    height: number;
    /** 是否显示图例 */
    showLegend?: boolean;
    /** 是否显示 tooltip */
    showTooltip?: boolean;
  }>(),
  {
    showLegend: false,
    showTooltip: true,
  },
);

const option = computed<EChartOption>(() => {
  const safeData = Array.isArray(props.data) ? props.data : [];
  const legendData = safeData.map((d) => d.name);

  return {
    textStyle: {
      fontFamily:
        '-apple-system, BlinkMacSystemFont, "Segoe UI", "PingFang SC", "Hiragino Sans GB", "Microsoft YaHei", Arial, sans-serif',
      fontSize: 12,
      color: '#334155',
    },
    tooltip: props.showTooltip
      ? {
          trigger: 'item',
          confine: true,
          formatter: (params: any) => {
            const name = params?.name ?? '';
            const percent = typeof params?.percent === 'number' ? `${params.percent.toFixed(2)}%` : '';
            return percent ? `${name}<br/>${percent}` : name;
          },
        }
      : undefined,
    legend: props.showLegend
      ? {
          type: 'scroll',
          orient: 'horizontal',
          top: 0,
          data: legendData,
        }
      : undefined,
    series: [
      {
        type: 'pie',
        // 更大 + 更粗：扩大外半径、减小内半径
        radius: ['44%', '92%'],
        center: ['50%', props.showLegend ? '56%' : '50%'],
        avoidLabelOverlap: true,
        label: { show: false },
        labelLine: { show: false },
        itemStyle: { borderColor: '#fff', borderWidth: 1 },
        emphasis: { scale: false },
        data: safeData.map((d) => ({
          name: d.name,
          value: Number.isFinite(d.value) ? d.value : 0,
          itemStyle: d.color ? { color: d.color } : undefined,
        })),
      } as any,
    ],
  } as EChartOption;
});
</script>

