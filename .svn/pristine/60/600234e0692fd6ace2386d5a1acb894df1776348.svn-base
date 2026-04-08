<template>
  <Chart renderer="svg" :option="option" :style="{ height: `${height}px`, width: '100%' }" />
</template>

<script lang="ts" setup>
import { computed } from 'vue';
import type { EChartOption } from 'echarts';
import { Chart } from '@/components';

type SeriesInput = {
  name: string;
  data: number[];
  stack?: string;
  type?: 'line' | 'bar';
  area?: boolean;
};

const props = withDefaults(
  defineProps<{
    xAxis: string[];
    series: SeriesInput[];
    height: number;
    /** 是否显示图例 */
    showLegend?: boolean;
    /** 是否显示 tooltip */
    showTooltip?: boolean;
    /** 横向条形图（类目轴在 y） */
    horizontal?: boolean;
    /** 是否启用百分比堆叠（0~100） */
    percentStack?: boolean;
  }>(),
  {
    showLegend: true,
    showTooltip: true,
    horizontal: false,
    percentStack: false,
  },
);

const option = computed<EChartOption>(() => {
  const safeXAxis = Array.isArray(props.xAxis) ? props.xAxis : [];
  const safeSeries = Array.isArray(props.series) ? props.series : [];

  const series = safeSeries.map((s) => {
    const isBar = (s.type ?? 'line') === 'bar';
    const base: any = {
      name: s.name,
      type: s.type ?? 'line',
      data: Array.isArray(s.data) ? s.data : [],
    };

    if (s.stack) base.stack = s.stack;

    if (!isBar) {
      base.showSymbol = false;
      if (s.area) base.areaStyle = {};
    } else {
      // 不固定 50% 宽度，避免多系列并排时挤压导致“看起来和日期不对齐”
      base.barMaxWidth = 26;
      base.barMinHeight = 1;
      base.barGap = '20%';
      base.barCategoryGap = '34%';
    }

    return base;
  });

  const hasBar = series.some((s: any) => s.type === 'bar');

  return {
    textStyle: {
      fontFamily:
        '-apple-system, BlinkMacSystemFont, "Segoe UI", "PingFang SC", "Hiragino Sans GB", "Microsoft YaHei", Arial, sans-serif',
      fontSize: 12,
      color: '#334155',
    },
    color: ['#1790ff', '#12c2c2', '#2fc25b', '#f04864', '#8542e0', '#13c2c2', '#faad14', '#52c41a'],
    tooltip: props.showTooltip
      ? {
          trigger: 'axis',
          axisPointer: { type: 'cross' },
          confine: true,
        }
      : undefined,
    legend: props.showLegend
      ? {
          data: safeSeries.map((s) => s.name),
          textStyle: { color: '#475569', fontSize: 12 },
        }
      : undefined,
    grid: {
      left: '3%',
      right: '4%',
      top: props.showLegend ? 28 : 12,
      bottom: '3%',
      containLabel: true,
    },
    xAxis: props.horizontal
      ? {
          type: 'value',
          max: props.percentStack ? 100 : undefined,
          axisTick: { show: false },
          axisLine: { show: false },
          axisLabel: { color: '#94a3b8', fontSize: 11 },
          splitLine: { lineStyle: { type: 'dashed', color: '#e2e8f0' } },
        }
      : {
          type: 'category',
          boundaryGap: hasBar,
          data: safeXAxis,
          axisTick: { alignWithLabel: true },
          axisLine: { lineStyle: { color: '#e2e8f0' } },
          axisLabel: { color: '#94a3b8', fontSize: 11, interval: 0 },
        },
    yAxis: props.horizontal
      ? {
          type: 'category',
          boundaryGap: hasBar,
          data: safeXAxis,
          axisTick: { alignWithLabel: true },
          axisLine: { show: false },
          splitLine: { show: false },
          axisLabel: { color: '#94a3b8', fontSize: 11 },
        }
      : {
          type: 'value',
          max: props.percentStack ? 100 : undefined,
          axisTick: { show: false },
          axisLine: { show: false },
          axisLabel: { color: '#94a3b8', fontSize: 11 },
          splitLine: { lineStyle: { type: 'dashed', color: '#e2e8f0' } },
        },
    series,
  } as EChartOption;
});
</script>

