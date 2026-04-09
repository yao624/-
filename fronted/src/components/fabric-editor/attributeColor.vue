<!--
 * @Author: 秦少卫
 * @Date: 2024-05-21 10:59:48
 * @LastEditors: 秦少卫
 * @LastEditTime: 2024-10-07 17:32:19
 * @Description: 渐变
-->

<template>
  <div class="box attr-item-box" v-if="isOne && selectType !== 'image' && selectType !== 'group'">
    <Divider plain orientation="left"><h4>颜色</h4></Divider>
    <!-- 通用属性 -->
    <div class="bg-item">
      <div class="color-preview-wrapper">
        <div class="color-bar" :style="{ background: baseAttr.fill }"></div>
        <color-picker
          v-model:value="baseAttr.fill"
          @change="colorChange"
          @nativePick="dropColor"
          @ok="colorChange"
        ></color-picker>
      </div>
    </div>
  </div>
</template>

<script setup name="AttrBute">
import useSelect from '@/hooks/select.js';
import { Divider } from 'view-ui-plus';
import colorPicker from '@/components/fabric-editor/color-picker.vue';
import { ref, reactive, onMounted, onBeforeUnmount, computed, watch, getCurrentInstance, toRaw } from 'vue';

const update = getCurrentInstance();
const { fabric, selectType, canvasEditor, isOne } = useSelect();
const angleKey = 'gradientAngle';
// 属性值
const baseAttr = reactive({
  fill: '#ffffffff',
});

// 属性获取
const getObjectAttr = (e) => {
  const activeObject = canvasEditor.canvas.getActiveObject();
  // 不是当前obj，跳过
  if (e && e.target && e.target !== activeObject) return;
  if (activeObject && isOne) {
    const fill = activeObject.get('fill');
    console.log('getObjectAttr - fill:', fill);
    if (typeof fill === 'string') {
      baseAttr.fill = fill;
    } else {
      baseAttr.fill = fabricGradientToCss(fill, activeObject);
    }
    console.log('getObjectAttr - baseAttr.fill:', baseAttr.fill);
  }
};

const colorChange = (value) => {
  const activeObject = canvasEditor.canvas.getActiveObjects()[0];
  if (activeObject) {
    const color = String(value.color).replace('NaN', '');
    // 暂时只处理纯色，渐变功能后续添加
    // if (value.mode === '纯色') {
    activeObject.set('fill', color);
    // } else if (value.mode === '渐变' && value.stops) {
    //   // 确保有 stops 数据才处理渐变
    //   const currentGradient = cssToFabricGradient(
    //     toRaw(value.stops),
    //     activeObject.width,
    //     activeObject.height,
    //     value.angle || 0
    //   );
    //   activeObject.set('fill', currentGradient, value.angle || 0);
    //   activeObject.set(angleKey, value.angle || 0);
    // }
    canvasEditor.canvas.renderAll();
  }
};

const dropColor = (value) => {
  colorChange(value);
};

const fabricGradientToCss = (val, activeObject) => {
  // 渐变类型
  if (!val) return;
  const angle = activeObject.get(angleKey, val.degree);
  const colorStops = val.colorStops.map((item) => {
    return item.color + ' ' + item.offset * 100 + '%';
  });
  return `linear-gradient(${angle}deg, ${colorStops})`;
};
// css转Fabric渐变
const cssToFabricGradient = (stops, width, height, angle) => {
  const gradAngleToCoords = (paramsAngle) => {
    const anglePI = -parseInt(paramsAngle, 10) * (Math.PI / 180);
    return {
      x1: Math.round(50 + Math.sin(anglePI) * 50) / 100,
      y1: Math.round(50 + Math.cos(anglePI) * 50) / 100,
      x2: Math.round(50 + Math.sin(anglePI + Math.PI) * 50) / 100,
      y2: Math.round(50 + Math.cos(anglePI + Math.PI) * 50) / 100,
    };
  };

  const angleCoords = gradAngleToCoords(angle);
  return new fabric.Gradient({
    type: 'linear',
    gradientUnits: 'pencentage', // pixels or pencentage 像素 或者 百分比
    coords: {
      x1: angleCoords.x1 * width,
      y1: angleCoords.y1 * height,
      x2: angleCoords.x2 * width,
      y2: angleCoords.y2 * height,
    },
    colorStops: [...stops],
  });
};

const selectCancel = () => {
  update?.proxy?.$forceUpdate();
};

onMounted(() => {
  // 获取字体数据
  getObjectAttr();
  canvasEditor.on('selectCancel', selectCancel);
  canvasEditor.on('selectOne', getObjectAttr);
  canvasEditor.canvas.on('object:modified', getObjectAttr);
});

onBeforeUnmount(() => {
  canvasEditor.off('selectCancel', selectCancel);
  canvasEditor.off('selectOne', getObjectAttr);
  canvasEditor.canvas.off('object:modified', getObjectAttr);
});
</script>

<style scoped lang="less">
.bg-item {
  padding: 8px;
}

.color-preview-wrapper {
  display: flex;
  align-items: center;
  gap: 8px;
}

.color-bar {
  width: 40px;
  height: 30px;
  cursor: pointer;
  border: 2px solid #e8e8e8;
  border-radius: 4px;
  flex-shrink: 0;
}

:deep(.color-picker-wrapper) {
  flex: 1;
}

:deep(.ivu-input-number) {
  display: block;
  width: 100%;
}
</style>
