<template>
  <div class="mini-canvas">
    <div id="workspace" class="mini-canvas-workspace">
      <canvas id="mini-canvas-el"></canvas>
    </div>
  </div>
</template>

<script setup lang="ts">
import { onMounted, onBeforeUnmount } from 'vue';
import { useMiniEditor } from './useMiniEditor';

const {
  initCanvas,
  destroyCanvas,
  loadImage,
  setSize,
  setBgColor,
  setBgImage,
  applyFillMode,
  clearCanvas,
  exportCanvasAsBlob,
  exportCanvasAsDataURL,
} = useMiniEditor();

onMounted(() => {
  initCanvas('mini-canvas-el');
});

onBeforeUnmount(() => {
  destroyCanvas();
});

defineExpose({
  loadImage,
  setSize,
  setBgColor,
  setBgImage,
  applyFillMode,
  clearCanvas,
  exportCanvasAsBlob,
  exportCanvasAsDataURL,
});
</script>

<style scoped lang="less">
.mini-canvas {
  flex: 1;
  display: flex;
  overflow: hidden;
}

.mini-canvas-workspace {
  flex: 1;
  width: 100%;
  position: relative;
  background: #f1f1f1;
  overflow: hidden;
}

#mini-canvas-el {
  width: 300px;
  height: 300px;
  margin: 0 auto;
}
</style>
