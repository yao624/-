<template>
  <div ref="chartDom" style="width: 100%; height: 100%;" />
</template>

<script lang="ts">
import { defineComponent, inject, onMounted, onUnmounted, ref, watch } from 'vue';
import type { ECharts } from 'echarts';
import echarts from 'echarts';
import { debounce } from 'lodash-es';
import { addListener, removeListener } from 'resize-detector';
import dark from './dark';

echarts.registerTheme('dark-pro', dark);

export default defineComponent({
  props: {
    option: {
      type: Object,
      default: () => {
        return {};
      },
    },
    renderer: {
      type: String,
      default: 'canvas',
    },
    devicePixelRatio: {
      type: Number,
      default: undefined,
    },
  },
  setup(props) {
    const chartDom = ref<HTMLDivElement>();
    let chart: ECharts | null = null;
    const isRealDark = inject('isRealDark', ref(false));
    const resizeChart = () => {
      chart?.resize();
    };

    const resize = debounce(resizeChart, 300);

    const disposeChart = () => {
      if (chartDom.value) {
        removeListener(chartDom.value, resize);
      }
      chart?.dispose();
      chart = null;
    };
    const initChart = () => {
      disposeChart();
      if (chartDom.value) {
        // init echarts
        chart = echarts.init(chartDom.value, isRealDark.value ? 'dark-pro' : undefined, {
          renderer: props.renderer as any,
          devicePixelRatio: props.devicePixelRatio,
        } as any);
        // 图表类型切换时必须全量替换 option，避免旧 series 残留
        chart.setOption(props.option, true);
        addListener(chartDom.value, resize);
      }
    };

    watch(
      isRealDark,
      () => {
        initChart();
      },
      { flush: 'post' },
    );
    onMounted(() => {
      watch(
        () => props.option,
        () => {
          // notMerge=true，防止 stack/bar/line 等类型切换时“看起来没变化”
          chart?.setOption(props.option, true);
        },
        { deep: true, flush: 'post' },
      );
      initChart();
    });

    onUnmounted(() => {
      disposeChart();
    });

    return {
      chartDom,
    };
  },
});
</script>
