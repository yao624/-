<template>
  <div v-if="!isSelect">
    <div class="attr-item-box">
      <!-- <h3>{{ $t('bgSeting.color') }}</h3> -->
      <Divider plain orientation="left">
        <h4>{{ $t('bgSeting.color') }}</h4>
      </Divider>
      <div class="bg-color-picker">
        <color-picker v-model="color" @change="setThisColor" @ok="setThisColor"></color-picker>
      </div>
      <!-- <Divider plain></Divider> -->
    </div>
    <div class="attr-item-box">
      <!-- <h3>{{ $t('bgSeting.colorMacthing') }}</h3> -->
      <Divider plain orientation="left">
        <h4>{{ $t('bgSeting.colorMacthing') }}</h4>
      </Divider>
      <div class="color-list">
        <template v-for="(item, i) in colorList" :key="item + i">
          <span :style="`background:${item}`" @click="setColor(item)"></span>
        </template>
      </div>
    </div>

    <!-- <div>
      <Divider plain orientation="left">
        <h4>蒙版</h4>
      </Divider>

      <workspaceMask />
    </div> -->
  </div>
</template>

<script setup name="BgBar">
// import workspaceMask from './workspaceMask.vue';
import { ref, onMounted, onUnmounted } from 'vue';
import { Divider } from 'view-ui-plus';
import colorPicker from './color-picker.vue';
import useSelect from '@/hooks/select';
const { isSelect, canvasEditor } = useSelect();

const colorList = ref([
  '#5F2B63',
  '#B23554',
  '#F27E56',
  '#FCE766',
  '#86DCCD',
  '#E7FDCB',
  '#FFDC84',
  '#F57677',
  '#5FC2C7',
  '#98DFE5',
  '#C2EFF3',
  '#DDFDFD',
  '#9EE9D3',
  '#2FC6C8',
  '#2D7A9D',
  '#48466d',
  '#61c0bf',
  '#bbded6',
  '#fae3d9',
  '#ffb6b9',
  '#ffaaa5',
  '#ffd3b6',
  '#dcedc1',
  '#a8e6cf',
]);

const color = ref('rgba(255, 255, 255, 1)');
// 背景颜色设置
const setThisColor = (colorValue) => {
  console.log('setThisColor 被调用:', colorValue);
  if (colorValue && colorValue.color) {
    setColor(colorValue.color);
  } else if (colorValue) {
    setColor(colorValue);
  } else {
    setColor(color.value);
  }
};
// 背景颜色设置
function setColor(c) {
  console.log('setColor 被调用:', c);
  if (!canvasEditor || !canvasEditor.canvas) {
    console.error('canvasEditor 或 canvas 不存在');
    return;
  }
  const workspace = canvasEditor.canvas.getObjects().find((item) => item.id === 'workspace');
  if (workspace) {
    workspace.set('fill', c);
    canvasEditor.canvas.renderAll();
    color.value = c;
    console.log('背景颜色已设置:', c);
  } else {
    console.error('找不到 workspace 对象');
  }
}

// 加载模板时回显颜色值
const handleChangeColor = () => {
  if (!canvasEditor || !canvasEditor.canvas) return;
  const workspace = canvasEditor.canvas.getObjects().find((item) => item.id === 'workspace');
  if (workspace) {
    const fill = workspace.get('fill');
    console.log('初始化背景颜色:', fill);
    color.value = fill || 'rgba(255, 255, 255, 1)';
  }
};

onMounted(() => {
  console.log('bgBar mounted');
  // 确保初始化时获取当前背景颜色
  handleChangeColor();
  canvasEditor.on('loadJson', handleChangeColor);
});

onUnmounted(() => {
  canvasEditor.off('loadJson', handleChangeColor);
});
</script>

<style scoped lang="less">
.bg-color-picker {
  padding: 8px;
}

.color-list {
  display: flex;
  flex-wrap: wrap;
  span {
    height: 30px;
    width: 30px;
    border-radius: 15px;
    border: 3px solid #fff;
    vertical-align: middle;
    cursor: pointer;
  }
}
</style>
