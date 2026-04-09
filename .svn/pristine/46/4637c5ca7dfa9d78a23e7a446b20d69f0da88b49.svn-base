<!--
 * @Author: Stub Implementation
 * @Date: 2025-04-08
 * @Description: 我的素材 - 用户上传的素材管理
-->

<template>
  <div>
    <!-- 搜索组件 -->
    <div class="search-box">
      <Select
        class="select"
        v-model="typeValue"
        @on-change="changeSelectType"
        :disabled="pageLoading"
      >
        <Option v-for="item in typeList" :value="item.value" :key="item.value">
          {{ item.label }}
        </Option>
      </Select>
      <Input
        class="input"
        placeholder="搜索我的素材"
        v-model="searchKeyWord"
        search
        :disabled="pageLoading"
        @on-search="startGetList"
      />
    </div>

    <!-- 列表 -->
    <div style="height: calc(100vh - 108px)" id="myMaterialBox">
      <Scroll
        key="myMaterialScroll"
        v-if="showScroll"
        :on-reach-bottom="nextPage"
        :height="scrollHeight"
        :distance-to-edge="[-1, -1]"
      >
        <!-- 列表 -->
        <div class="list-box">
          <div v-if="pageData.length === 0 && !pageLoading" class="empty-state">
            <div class="empty-text">暂无素材</div>
            <div class="empty-hint">点击上传按钮添加素材</div>
          </div>
          <Tooltip
            v-else
            :content="info.name"
            v-for="info in pageData"
            :key="info.id"
            placement="top"
          >
            <div class="material-img-box">
              <Image
                lazy
                :src="info.previewSrc"
                fit="contain"
                height="100%"
                :alt="info.name"
                @click="addMaterial(info)"
              />
            </div>
          </Tooltip>
        </div>
        <Spin size="large" fix :show="pageLoading"></Spin>

        <Divider plain v-if="isDownBottom">已经到底了</Divider>
      </Scroll>
    </div>
  </div>
</template>

<script setup>
import { ref, onMounted } from 'vue';
import { Spin, Modal } from 'view-ui-plus';
import { debounce } from 'lodash-es';
import useSelect from '@/hooks/select';

const { canvasEditor } = useSelect();

const typeValue = ref('all');
const typeList = ref([
  { value: 'all', label: '全部' },
  { value: 'image', label: '图片' },
  { value: 'text', label: '文本' },
  { value: 'shape', label: '形状' },
]);

const pageData = ref([]);
const pageLoading = ref(false);
const searchKeyWord = ref('');
const isDownBottom = ref(false);
const showScroll = ref(false);
const scrollHeight = ref(0);

// 搜索类型改变
const changeSelectType = debounce(() => {
  startGetList();
}, 100);

// 开始获取列表
const startGetList = () => {
  // Stub implementation - in production, this would call the API
  console.log('startGetList called with:', {
    typeValue: typeValue.value,
    searchKeyWord: searchKeyWord.value,
  });
  pageLoading.value = true;

  // Simulate API call
  setTimeout(() => {
    pageData.value = [];
    pageLoading.value = false;
    isDownBottom.value = true;
    showScroll.value = true;
    scrollHeight.value = 500;
  }, 500);
};

// 下一页
const nextPage = () => {
  if (isDownBottom.value || pageLoading.value) return;
  // Stub implementation
  console.log('nextPage called');
};

// 添加素材到画布
const addMaterial = (info) => {
  Modal.confirm({
    title: '添加素材',
    content: `<p>是否将 "${info.name}" 添加到画布？</p>`,
    onOk: async () => {
      try {
        // Stub implementation - in production, this would add the material to canvas
        console.log('Adding material to canvas:', info);
        Spin.show({
          render: (h) => h('div', '加载中...'),
        });
        // TODO: Implement actual material loading
        setTimeout(() => {
          Spin.hide();
        }, 500);
      } catch (error) {
        console.error('Failed to add material:', error);
        Spin.hide();
      }
    },
  });
};

onMounted(() => {
  startGetList();
});
</script>

<style scoped lang="less">
.search-box {
  padding-top: 10px;
  padding-bottom: 10px;
  display: flex;
  .input {
    margin-left: 10px;
  }
  .select {
    width: 100px;
  }
}

.list-box {
  display: flex;
  flex-direction: row;
  flex-wrap: wrap;
  justify-content: space-between;
  padding: 10px;
}

.material-img-box {
  width: 140px;
  height: 140px;
  cursor: pointer;
  border-radius: 5px;
  overflow: hidden;
  border: 1px solid #e8e8e8;
  &:hover {
    opacity: 0.8;
    border-color: #2d8cf0;
  }
}

.empty-state {
  width: 100%;
  padding: 60px 20px;
  text-align: center;
}

.empty-text {
  color: #999;
  font-size: 14px;
  margin-bottom: 8px;
}

.empty-hint {
  color: #ccc;
  font-size: 12px;
}
</style>
