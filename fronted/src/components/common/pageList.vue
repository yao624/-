<template>
  <div class="page-list" :id="DOMId">
    <div class="items-grid" v-loading="pageLoading">
      <div
        v-for="item in pageData"
        :key="item.id"
        class="item-card"
        draggable="true"
        @click="handleClick(item)"
        @dragend="handleDragEnd(item, $event)"
      >
        <div class="item-image" :style="{ backgroundImage: `url(${item.src})` }"></div>
        <div class="item-name">{{ item.name }}</div>
      </div>
    </div>

    <!-- 加载更多 -->
    <div v-if="!isDownBottom" class="load-more">
      <Spin v-if="pageLoading"></Spin>
      <div v-else class="load-more-trigger" ref="loadMoreRef"></div>
    </div>

    <div v-else-if="pageData.length === 0" class="empty-state">
      <div class="empty-text">暂无数据</div>
    </div>
  </div>
</template>

<script setup>
import { ref, onMounted, computed } from 'vue';
import usePageList from '@/hooks/usePageList';

const props = defineProps({
  DOMId: {
    type: String,
    required: true,
  },
  pageListApi: {
    type: Function,
    required: true,
  },
  filters: {
    type: Object,
    default: () => ({}),
  },
  formatData: {
    type: Function,
    default: (data) => data,
  },
});

const emit = defineEmits(['click', 'dragend']);

const loadMoreRef = ref(null);

const {
  pageData,
  pageLoading,
  isDownBottom,
  startPage,
  nextPage,
} = usePageList({
  el: `#${props.DOMId}`,
  apiClient: props.pageListApi,
  filters: props.filters,
  formatData: props.formatData,
});

onMounted(async () => {
  await startPage();
  setupInfiniteScroll();
});

const handleClick = (item) => {
  emit('click', { item });
};

const handleDragEnd = (item, e) => {
  emit('dragend', { item, e });
};

const setupInfiniteScroll = () => {
  // Simple infinite scroll implementation
  const observer = new IntersectionObserver(
    (entries) => {
      if (entries[0].isIntersecting && !isDownBottom.value && !pageLoading.value) {
        nextPage();
      }
    },
    { threshold: 0.1 }
  );

  if (loadMoreRef.value) {
    observer.observe(loadMoreRef.value);
  }
};
</script>

<style scoped lang="less">
.page-list {
  padding: 10px;
  background: #fff;
  height: 100%;
  overflow-y: auto;
}

.items-grid {
  display: grid;
  grid-template-columns: repeat(auto-fill, minmax(80px, 1fr));
  gap: 8px;
}

.item-card {
  border: 1px solid #e8e8e8;
  border-radius: 4px;
  overflow: hidden;
  cursor: pointer;
  transition: all 0.3s;

  &:hover {
    border-color: #2d8cf0;
    box-shadow: 0 2px 8px rgba(45, 140, 240, 0.2);
  }
}

.item-image {
  width: 100%;
  padding-bottom: 100%;
  background-size: cover;
  background-position: center;
  background-color: #f5f5f5;
}

.item-name {
  padding: 4px;
  font-size: 12px;
  text-align: center;
  overflow: hidden;
  text-overflow: ellipsis;
  white-space: nowrap;
}

.load-more {
  padding: 16px;
  text-align: center;
}

.load-more-trigger {
  height: 1px;
}

.empty-state {
  padding: 40px;
  text-align: center;
}

.empty-text {
  color: #999;
  font-size: 14px;
}
</style>
