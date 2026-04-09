<template>
  <div class="type-list">
    <!-- 类型标签 -->
    <div class="type-tabs">
      <div
        v-for="type in types"
        :key="type.value"
        :class="['type-tab', { active: currentType === type.value }]"
        @click="selectType(type.value)"
      >
        {{ type.label }}
      </div>
    </div>

    <!-- 项目列表 -->
    <div class="items-grid" v-loading="loading">
      <div
        v-for="item in items"
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
    <div v-if="hasMore" class="load-more" ref="loadMoreRef">
      <Spin v-if="loading"></Spin>
      <div v-else class="load-more-trigger"></div>
    </div>
  </div>
</template>

<script setup>
import { ref, onMounted, watch } from 'vue';

const props = defineProps({
  typeApi: {
    type: Function,
    default: null,
  },
  typeListApi: {
    type: Function,
    default: null,
  },
  typeKey: {
    type: String,
    default: 'type',
  },
  formatData: {
    type: Function,
    default: (data) => data,
  },
});

const emit = defineEmits(['selectType', 'click', 'dragend']);

const types = ref([]);
const currentType = ref('');
const items = ref([]);
const loading = ref(false);
const hasMore = ref(true);
const page = ref(1);
const loadMoreRef = ref(null);

onMounted(async () => {
  await loadTypes();
  if (types.value.length > 0) {
    selectType(types.value[0].value);
  }
  setupInfiniteScroll();
});

const loadTypes = async () => {
  if (!props.typeApi) {
    types.value = [{ value: 'all', label: '全部' }];
    return;
  }

  try {
    const result = await props.typeApi();
    types.value = result || [{ value: 'all', label: '全部' }];
  } catch (error) {
    console.error('Failed to load types:', error);
    types.value = [{ value: 'all', label: '全部' }];
  }
};

const selectType = async (type) => {
  currentType.value = type;
  emit('selectType', type);
  await loadItems(true);
};

const loadItems = async (reset = false) => {
  if (loading.value) return;

  if (!props.typeListApi) {
    items.value = [];
    hasMore.value = false;
    return;
  }

  loading.value = true;

  if (reset) {
    items.value = [];
    page.value = 1;
    hasMore.value = true;
  }

  try {
    const filters = {};
    filters[props.typeKey] = currentType.value;

    const result = await props.typeListApi({
      page: page.value,
      filters,
    });

    const formattedData = props.formatData(result.data || result);

    if (reset) {
      items.value = formattedData;
    } else {
      items.value = [...items.value, ...formattedData];
    }

    hasMore.value = result.meta?.pagination?.page < result.meta?.pagination?.pageCount;
    if (hasMore.value) {
      page.value++;
    }
  } catch (error) {
    console.error('Failed to load items:', error);
    hasMore.value = false;
  } finally {
    loading.value = false;
  }
};

const handleClick = (item) => {
  emit('click', { item });
};

const handleDragEnd = (item, e) => {
  emit('dragend', { item, e });
};

const setupInfiniteScroll = () => {
  // Simple infinite scroll implementation
  // In production, use Intersection Observer
  const observer = new IntersectionObserver(
    (entries) => {
      if (entries[0].isIntersecting && hasMore.value && !loading.value) {
        loadItems();
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
.type-list {
  padding: 10px;
  background: #fff;
  height: 100%;
  overflow-y: auto;
}

.type-tabs {
  display: flex;
  gap: 8px;
  margin-bottom: 16px;
  overflow-x: auto;
  padding-bottom: 8px;
}

.type-tab {
  padding: 6px 12px;
  background: #f5f5f5;
  border-radius: 4px;
  cursor: pointer;
  white-space: nowrap;
  font-size: 14px;
  transition: all 0.3s;

  &.active {
    background: #2d8cf0;
    color: #fff;
  }
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
</style>
