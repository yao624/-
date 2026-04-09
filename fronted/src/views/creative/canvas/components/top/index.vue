<template>
  <Header>
    <div class="left">
      <logo></logo>
      <Divider type="vertical" />

      <!-- 导入 -->
      <import-Json></import-Json>
      <Divider type="vertical" />
      <import-file></import-file>
      <Divider type="vertical" />
      <Button type="text" to="/template" target="_blank">全部模板</Button>
      <Button type="text" to="/manage" target="_blank">
        <Icon type="md-list" />
        模版管理
      </Button>
      <Divider type="vertical" />

      <myTemplName></myTemplName>
      <!-- 标尺开关 -->
      <Tooltip :content="$t('grid')">
        <iSwitch v-model="toggleModel" size="small" class="switch"></iSwitch>
      </Tooltip>
      <Divider type="vertical" />
      <history></history>
    </div>

    <div class="right">
      <!-- 管理员模式 -->
      <admin />
      <!-- 预览 -->
      <previewCurrent />
      <!-- <waterMark /> -->
      <save></save>
      <saveTemplate
        :editing-template-id="editingTemplate?.id"
        :original-template="editingTemplate"
      />
      <!-- 注释登录 -->
      <!-- <login></login> -->

      <lang></lang>
    </div>
  </Header>
</template>

<script name="Top" setup lang="ts">
import { watch, computed } from 'vue';
import proIcon from '@/assets/icon/proIcon.png';
// 导入元素
import importJson from '@/components/fabric-editor/importJSON.vue';
import importFile from '@/components/fabric-editor/importFile.vue';

// 顶部组件
import logo from '@/components/fabric-editor/logo.vue';
import myTemplName from '@/components/fabric-editor/myTemplName.vue';
import previewCurrent from '@/components/fabric-editor/previewCurrent.vue';
import save from '@/components/fabric-editor/save.vue';
import saveTemplate from '@/components/fabric-editor/saveTemplate.vue';
import lang from '@/components/fabric-editor/lang.vue';
import admin from '@/components/fabric-editor/admin.vue';
import history from '@/components/fabric-editor/history.vue';

const props = defineProps([
  'ruler',
  'editingTemplate' // 当前编辑的模版信息
]);
const emit = defineEmits(['update:ruler']);

// 监听 editingTemplate 变化
watch(() => props.editingTemplate, (val) => {
  console.log('Top component editingTemplate changed:', val);
}, { immediate: true });

const toggleModel = computed({
  get() {
    return props.ruler;
  },
  set(value) {
    emit('update:ruler', value);
  },
});
</script>

<style lang="less" scoped>
.left,
.right {
  display: flex;
  align-items: center;
  img {
    display: block;
    margin-right: 10px;
  }
}
</style>
