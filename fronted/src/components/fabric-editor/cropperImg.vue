<template>
  <div v-if="isOne && type === 'image'" class="attr-item-box">
    <div class="bg-item">
      <Button @click="cropper" type="text" long>{{ $t('cropperImg') }}</Button>
    </div>
  </div>
  <cropperDialog ref="cropperDialogRef"></cropperDialog>
</template>

<script setup name="CropperImg">
import { ref, getCurrentInstance, onMounted, onBeforeUnmount } from 'vue';
import useSelect from '@/hooks/select.js';

import cropperDialog from '@/components/fabric-editor/cropperDialog.vue';
import { Utils } from '@kuaitu/core';
const { insertImgFile } = Utils;

const update = getCurrentInstance();
// const canvasEditor = inject('canvasEditor');
const { canvasEditor, isOne } = useSelect();
const type = ref('');
const cropperDialogRef = ref();
const cropper = () => {
  console.log('🚀 ~ cropper ~ cropper:');
  const activeObject = canvasEditor.canvas.getActiveObjects()[0];
  if (activeObject && activeObject.type === 'image') {
    console.log('🚀 ~ cropper ~ activeObject:', activeObject);
    cropperDialogRef.value.open({ img: activeObject._element.src }, async (data) => {
      console.log('🚀 ~ cropper ~ data:', data);
      const imgEl = await insertImgFile(data);
      // const width = activeObject.get('width');
      // const height = activeObject.get('height');
      // const scaleX = activeObject.get('scaleX');
      // const scaleY = activeObject.get('scaleY');
      // console.log('🚀 ~ cropper ~ scaleX:', scaleX);
      // console.log('🚀 ~ cropper ~ scaleY:', scaleY);
      activeObject.setSrc(imgEl.src, () => {
        // activeObject.set('scaleX', scaleX);
        // activeObject.set('scaleY', scaleY);
        canvasEditor.canvas.renderAll();
      });
      imgEl.remove();
    });
  }
};

// 替换图片
// const repleace = async () => {
//   const activeObject = canvasEditor.canvas.getActiveObjects()[0];
//   if (activeObject && activeObject.type === 'image') {
//     // 图片
//     const [file] = await selectFiles({ accept: 'image/*', multiple: false });
//     // 转字符串
//     const fileStr = await getImgStr(file);
//     // 字符串转El
//     const imgEl = await insertImgFile(fileStr);
//     const width = activeObject.get('width');
//     const height = activeObject.get('height');
//     const scaleX = activeObject.get('scaleX');
//     const scaleY = activeObject.get('scaleY');
//     activeObject.setSrc(imgEl.src, () => {
//       activeObject.set('scaleX', (width * scaleX) / imgEl.width);
//       activeObject.set('scaleY', (height * scaleY) / imgEl.height);
//       canvasEditor.canvas.renderAll();
//     });
//     imgEl.remove();
//   }
// };

const init = () => {
  const activeObject = canvasEditor.canvas.getActiveObjects()[0];
  if (activeObject) {
    type.value = activeObject.type;
    update?.proxy?.$forceUpdate();
  }
};

onMounted(() => {
  canvasEditor.on('selectOne', init);
});

onBeforeUnmount(() => {
  canvasEditor.off('selectOne', init);
});
</script>
<style lang="less" scoped>
.attr-item-box {
  margin-top: 8px;
}
</style>
