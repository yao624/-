<template>
  <div>
    <Divider plain orientation="left">{{ $t('common_elements') }}</Divider>
    <div class="tool-box">
      <span @click="() => addText()" :draggable="true" @dragend="addText">
        <img :src="textIcon" width="26" height="26" alt="text" />
      </span>
      <span @click="() => addTextBox()" :draggable="true" @dragend="addTextBox">
        <img :src="textBoxIcon" width="26" height="26" alt="textbox" />
      </span>
      <span @click="() => addRect()" :draggable="true" @dragend="addRect">
        <img :src="rectIcon" width="26" height="26" alt="rect" />
      </span>
      <span @click="() => addCircle()" :draggable="true" @dragend="addCircle">
        <img :src="circleIcon" width="26" height="26" alt="circle" />
      </span>
      <span @click="() => addTriangle()" :draggable="true" @dragend="addTriangle">
        <img :src="triangleIcon" width="26" height="26" alt="triangle" />
      </span>
      <!-- 多边形按钮 -->
      <span @click="() => addPolygon()" :draggable="true" @dragend="addPolygon">
        <img :src="polygonIcon" width="26" height="26" alt="polygon" />
      </span>
    </div>
    <Divider plain orientation="left">{{ $t('draw_elements') }}</Divider>
    <div class="tool-box">
      <span
        @click="drawingLineModeSwitch('line')"
        :class="state.isDrawingLineMode && state.lineType === 'line' && 'bg'"
      >
        <img :src="draw1Icon" width="20" height="20" alt="line" />
      </span>
      <span
        @click="drawingLineModeSwitch('arrow')"
        :class="state.isDrawingLineMode && state.lineType === 'arrow' && 'bg'"
      >
        <img :src="draw2Icon" width="20" height="20" alt="arrow" />
      </span>
      <span
        @click="drawingLineModeSwitch('thinTailArrow')"
        :class="state.isDrawingLineMode && state.lineType === 'thinTailArrow' && 'bg'"
      >
        <img :src="draw3Icon" width="20" height="20" alt="thinTailArrow" />
      </span>
      <span
        @click="drawPolygon"
        :class="state.isDrawingLineMode && state.lineType === 'polygon' && 'bg'"
      >
        <img :src="draw4Icon" width="20" height="20" alt="polygon" />
      </span>
      <!-- 隐藏功能入口（路径文本） -->
      <!-- <span
        @click="drawPathText"
        :class="state.isDrawingLineMode && state.lineType === 'pathText' && 'bg'"
      >
        <Icon type="logo-tumblr" :size="22" />
      </span> -->
      <span
        @click="freeDraw"
        :class="state.isDrawingLineMode && state.lineType === 'freeDraw' && 'bg'"
      >
        <!-- 临时注释掉 Icon 组件 -->
        <!-- <Icon type="md-brush" :size="22" /> -->
        <span style="font-size: 22px;">🖌️</span>
      </span>
    </div>
    <Divider plain orientation="left">{{ $t('code_img') }}</Divider>
    <div class="tool-box">
      <span @click="canvasEditor.addQrCode">
        <img :src="qrCodeIcon" width="26" height="26" alt="qrcode" />
      </span>
      <span @click="canvasEditor.addBarcode">
        <img :src="barCodeIcon" width="26" height="26" alt="barcode" />
      </span>
    </div>
  </div>
</template>

<script setup name="Tools">
import { reactive, onDeactivated } from 'vue';
import { getPolygonVertices } from '@/utils/math';
import useSelect from '@/hooks/select';
// 使用 ?url 后缀导入 SVG 为 URL
import circleIcon from '@/assets/icon/tools/circle.svg?url';
import draw1Icon from '@/assets/icon/tools/draw1.svg?url';
import draw2Icon from '@/assets/icon/tools/draw2.svg?url';
import draw3Icon from '@/assets/icon/tools/draw3.svg?url';
import draw4Icon from '@/assets/icon/tools/draw4.svg?url';

import polygonIcon from '@/assets/icon/tools/polygon.svg?url';
import rectIcon from '@/assets/icon/tools/rect.svg?url';
import textIcon from '@/assets/icon/tools/text.svg?url';
import textBoxIcon from '@/assets/icon/tools/textBox.svg?url';
import triangleIcon from '@/assets/icon/tools/triangle.svg?url';

import qrCodeIcon from '@/assets/icon/tools/qrCode.svg?url';
import barCodeIcon from '@/assets/icon/tools/barCode.svg?url';

// import useCalculate from '@/hooks/useCalculate';
// const { getCanvasBound, isOutsideCanvas } = useCalculate();

import { useI18n } from 'vue-i18n';

const LINE_TYPE = {
  polygon: 'polygon',
  freeDraw: 'freeDraw',
  pathText: 'pathText',
};
// 默认属性
const defaultPosition = { shadow: '', fontFamily: 'arial' };

const { t } = useI18n();
const { fabric, canvasEditor } = useSelect();
const state = reactive({
  isDrawingLineMode: false,
  lineType: false,
});

const addText = (event) => {
  cancelDraw();
  const text = new fabric.IText(t('everything_is_fine'), {
    ...defaultPosition,
    fontSize: 80,
    fill: '#000000FF',
  });

  canvasEditor.addBaseType(text, { center: true, event });
};

const addTextBox = (event) => {
  cancelDraw();
  const text = new fabric.Textbox(t('everything_goes_well'), {
    ...defaultPosition,
    splitByGrapheme: true,
    width: 400,
    fontSize: 80,
    fill: '#000000FF',
  });

  canvasEditor.addBaseType(text, { center: true, event });
};

const addTriangle = (event) => {
  cancelDraw();
  const triangle = new fabric.Triangle({
    ...defaultPosition,
    width: 400,
    height: 400,
    fill: '#92706BFF',
    name: '三角形',
  });
  canvasEditor.addBaseType(triangle, { center: true, event });
};

const addPolygon = (event) => {
  cancelDraw();
  const polygon = new fabric.Polygon(getPolygonVertices(5, 200), {
    ...defaultPosition,
    fill: '#CCCCCCFF',
    name: '多边形',
  });
  polygon.set({
    // 创建完设置宽高，不然宽高会变成自动的值
    width: 400,
    height: 400,
    // 关闭偏移
    pathOffset: {
      x: 0,
      y: 0,
    },
  });
  canvasEditor.addBaseType(polygon, { center: true, event });
};

const addCircle = (event) => {
  cancelDraw();
  const circle = new fabric.Circle({
    ...defaultPosition,
    radius: 150,
    fill: '#57606BFF',
    // id: uuid(),
    name: '圆形',
  });
  canvasEditor.addBaseType(circle, { center: true, event });
};

const addRect = (event) => {
  cancelDraw();
  const rect = new fabric.Rect({
    ...defaultPosition,
    fill: '#F57274FF',
    width: 400,
    height: 400,
    name: '矩形',
  });

  canvasEditor.addBaseType(rect, { center: true, event });
};
const drawPolygon = () => {
  const onEnd = () => {
    state.lineType = false;
    state.isDrawingLineMode = false;
    ensureObjectSelEvStatus(!state.isDrawingLineMode, !state.isDrawingLineMode);
  };
  if (state.lineType !== LINE_TYPE.polygon) {
    endConflictTools();
    endDrawingLineMode();
    state.lineType = LINE_TYPE.polygon;
    state.isDrawingLineMode = true;
    canvasEditor.beginDrawPolygon(onEnd);
    canvasEditor.endDraw();
    ensureObjectSelEvStatus(!state.isDrawingLineMode, !state.isDrawingLineMode);
  } else {
    canvasEditor.discardPolygon();
  }
};

const drawPathText = () => {
  if (state.lineType === LINE_TYPE.pathText) {
    state.lineType = false;
    state.isDrawingLineMode = false;
    canvasEditor.endTextPathDraw();
  } else {
    endConflictTools();
    endDrawingLineMode();
    state.lineType = LINE_TYPE.pathText;
    state.isDrawingLineMode = true;
    canvasEditor.startTextPathDraw();
  }
};

const freeDraw = () => {
  if (state.lineType === LINE_TYPE.freeDraw) {
    canvasEditor.endDraw();
    state.lineType = false;
    state.isDrawingLineMode = false;
  } else {
    endConflictTools();
    endDrawingLineMode();
    state.lineType = LINE_TYPE.freeDraw;
    state.isDrawingLineMode = true;
    canvasEditor.startDraw({ width: 20 });
  }
};

const endConflictTools = () => {
  canvasEditor.discardPolygon();
  canvasEditor.endDraw();
  canvasEditor.endTextPathDraw();
};
const endDrawingLineMode = () => {
  state.isDrawingLineMode = false;
  state.lineType = '';
  canvasEditor.setMode(state.isDrawingLineMode);
  canvasEditor.setLineType(state.lineType);
};
const drawingLineModeSwitch = (type) => {
  if ([LINE_TYPE.polygon, LINE_TYPE.freeDraw, LINE_TYPE.pathText].includes(state.lineType)) {
    endConflictTools();
  }
  if (state.lineType === type) {
    state.isDrawingLineMode = false;
    state.lineType = '';
  } else {
    state.isDrawingLineMode = true;
    state.lineType = type;
  }
  canvasEditor.setMode(state.isDrawingLineMode);
  canvasEditor.setLineType(type);
  // this.canvasEditor.setMode(this.isDrawingLineMode);
  // this.canvasEditor.setArrow(isArrow);
  ensureObjectSelEvStatus(!state.isDrawingLineMode, !state.isDrawingLineMode);
};

const ensureObjectSelEvStatus = (evented, selectable) => {
  canvasEditor.canvas.forEachObject((obj) => {
    if (obj.id !== 'workspace') {
      obj.selectable = selectable;
      obj.evented = evented;
    }
  });
};

// 退出绘制状态
const cancelDraw = () => {
  if (!state.isDrawingLineMode) return;
  state.isDrawingLineMode = false;
  state.lineType = '';
  canvasEditor.setMode(false);
  endConflictTools();
  ensureObjectSelEvStatus(true, true);
};

onDeactivated(() => {
  cancelDraw();
});
</script>

<style scoped lang="less">
.tool-box {
  display: flex;
  justify-content: space-around;
  span {
    flex: 1;
    text-align: center;
    padding: 5px 0;
    background: #f6f6f6;
    margin-left: 2px;
    cursor: pointer;
    &:hover {
      background: #edf9ff;
      svg {
        fill: #2d8cf0;
      }
    }
  }
  .bg {
    background: #d8d8d8;

    &:hover {
      svg {
        fill: #2d8cf0;
      }
    }
  }
}
.img {
  width: 20px;
}
</style>
