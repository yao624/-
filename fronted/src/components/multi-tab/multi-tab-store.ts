import type { DefineComponent, InjectionKey, UnwrapRef } from 'vue';
import {
  isRef,
  watchEffect,
  onBeforeUnmount,
  h,
  KeepAlive,
  reactive,
  createVNode,
  toRaw,
  defineComponent,
  watch,
  provide,
  inject,
  computed,
} from 'vue';
import type { RouteLocationNormalized, RouteRecordNormalized } from 'vue-router';
import { useRouter, useRoute } from 'vue-router';
import { message } from 'ant-design-vue';
import { flattenChildren } from '@/utils/vnode-util';
import { findLast, omit } from 'lodash-es';
import { useAppStore } from '@/store/app';
import type { MaybeRef } from '@/typing';
import { eagerComputed } from '@vueuse/core';
export type CacheKey = string;

export interface CacheItem {
  path: CacheKey;
  route: Omit<RouteLocationNormalized, 'matched'> | any;
  lastActiveTime: number;
  key?: string;
  lock?: boolean;
  tabTitle?: string;
  tabPath?: string;
}

export interface MultiTabStore {
  cacheList: CacheItem[];
  current: CacheKey;
  include: string[];
  exclude: string[];
  maxCache?: number; // 最大缓存数
}

export type CallerFunction = {
  close: (path: CacheKey) => void; // 关闭指定路径标签
  closeLeft: (selectedPath: CacheKey) => void; // 关闭指定路径左侧标签
  closeRight: (selectedPath: CacheKey) => void; // 关闭指定路径右侧标签
  closeOther: (selectedPath: CacheKey) => void; // // 关闭除指定路径之外的标签
  getCaches: () => void; // 获取缓存列表
  clearCache: (path: CacheKey) => void; // 清空缓存
  refresh: (path?: CacheKey | undefined) => void; // 刷新指定路径
  deleteCachesByKeys: (keys: CacheKey[]) => void; // 删除指定缓存
};

export type Options = {
  defaultHomePage?: string;
};

export type MultiTabType = [CallerFunction];

let g = 1;
const guid = () => {
  return `CacheKey_${++g}`;
};

const MULTI_TAB_STORE_KEY: InjectionKey<MultiTabStore> = Symbol('multi-tab-store');
export const useMultiTabStateProvider = ({
  initCacheList = [],
  maxCache,
}: {
  initCacheList?: Omit<CacheItem, 'component' | 'key'>[];
  maxCache?: MaybeRef<number>;
} = {}): UnwrapRef<MultiTabStore> => {
  // 定义保留的多标签状态
  const state = reactive<MultiTabStore>({
    cacheList: [],
    current: '',
    exclude: [],
    include: [],
  });
  watchEffect(() => {
    state.maxCache = isRef(maxCache) ? maxCache.value : maxCache;
  });
  state.cacheList.push(...initCacheList.map(item => ({ ...item, key: guid() } as CacheItem)));
  provide(MULTI_TAB_STORE_KEY, state);
  return state;
};

export const injectMultiTabStore = () => {
  return inject(MULTI_TAB_STORE_KEY)!;
};

const findMatchedRoute = (
  route: RouteLocationNormalized,
): RouteRecordNormalized | RouteLocationNormalized => {
  const matched: RouteRecordNormalized[] = route.matched || [];
  return (
    (findLast(matched, m => {
      return m.meta && m.meta.mergeTab;
    }) as RouteRecordNormalized) || route
  );
};
const componentMap: Record<string, DefineComponent> = {};
// 创建消费端
export const MultiTabStoreConsumer = defineComponent({
  name: 'MultiTabStoreConsumer',
  setup(_props, { slots = {} }) {
    const route = useRoute();
    const state = inject(MULTI_TAB_STORE_KEY)!;
    const [{ deleteCachesByKeys }] = useMultiTab();
    const appStore = useAppStore();
    const multiTab = computed(() => appStore.multiTab);
    const hasCache = (path: CacheKey) => {
      return state.cacheList.find(item => item.tabPath === path);
    };
    const cacheListLength = eagerComputed(() => state.cacheList?.length || 0);
    watch(
      [() => state.maxCache, cacheListLength],
      () => {
        if (state.maxCache && cacheListLength && state.cacheList.length > state.maxCache) {
          const clearCount = state.cacheList.length - state.maxCache;
          const sortCacheList = state.cacheList
            .filter(item => !item.lock && state.current !== item.path)
            .sort((a, b) => a.lastActiveTime - b.lastActiveTime);
          const clearList = sortCacheList.slice(0, clearCount);
          deleteCachesByKeys(clearList.map(item => item.path));
        }
      },
      { immediate: true, flush: 'post' },
    );
    watch(
      () => route.fullPath,
      () => {
        state.current = route.path;
        const index = state.cacheList.findIndex(item => item.path === route.path);
        if (state.cacheList[index]) {
          state.cacheList[index].route = { ...omit(route, ['matched']) };
        }
      },
      { immediate: true },
    );
    onBeforeUnmount(() => {
      Object.keys(componentMap).forEach(key => {
        delete componentMap[key];
      });
    });
    return () => {
      const component = flattenChildren((slots.default && slots.default()) || [])[0] as any;
      if (!component) {
        return null;
      }
      const tabRoute = findMatchedRoute(route);
      // 是否存在 cache
      let cacheItem = hasCache(tabRoute.path);
      if (!cacheItem) {
        cacheItem = {
          path: route.path,
          route: { ...omit(route, ['matched']) },
          key: guid(),
          tabTitle: tabRoute?.meta?.title as string,
          tabPath: tabRoute.path,
          lock: !!route.meta.lock,
          lastActiveTime: Date.now(),
        };
        multiTab.value ? state.cacheList.push(cacheItem) : (state.cacheList = [cacheItem]);
      } else if (cacheItem.path !== route.path) {
        // 处理 mergeTab 逻辑
        Object.assign(cacheItem, {
          path: route.path,
          route: { ...omit(route, ['matched']) },
          key: guid(),
          tabTitle: tabRoute?.meta?.title,
          tabPath: tabRoute.path,
          lock: !!route.meta.lock,
          lastActiveTime: Date.now(),
        } as CacheItem);
      } else {
        cacheItem.lastActiveTime = Date.now();
      }
      const exclude = [...state.exclude];
      if (route.meta.keepAlive === false) {
        exclude.push(cacheItem.key!);
      }
      const newCom =
        componentMap[cacheItem.key] ||
        defineComponent({
          name: cacheItem.key,
          setup(props, { attrs }) {
            return () => h(component, { ...props, ...attrs });
          },
        });
      // if (exclude.find(k => k === cacheItem.key)) {
      //   delete componentMap[cacheItem.key];
      // }
      componentMap[cacheItem.key] = newCom;
      return createVNode(KeepAlive, multiTab.value ? { exclude } : { include: [] }, {
        default: () =>
          h(newCom, { key: cacheItem!.key + route.fullPath.replace(route.hash || '', '') }),
      });
    };
  },
});
export const useMultiTab = (/*options?: Options*/): MultiTabType => {
  const router = useRouter();
  const route = useRoute();
  const state = inject(MULTI_TAB_STORE_KEY)!;
  const clearCache = async (path: CacheKey) => {
    const cacheItem = state.cacheList.find(item => item.path === path);
    state.exclude = [cacheItem?.key as string];
    new Promise<void>(resolve => {
      setTimeout(() => {
        state.exclude = [];
        resolve();
      });
    });
  };

  const close = (path?: CacheKey) => {
    if (!path) {
      path = state.current;
    }
    const currentPageIndex = state.cacheList.findIndex(item => item.path === path);
    if (state.cacheList.length === 1) {
      message.info('这是最后一个标签了, 无法被关闭');
      return;
    }
    clearCache(path);
    if (path !== state.current) {
      state.cacheList.splice(currentPageIndex, 1);
      return;
    }
    const targetIndex = currentPageIndex === 0 ? currentPageIndex + 1 : currentPageIndex - 1;
    router
      .replace(state.cacheList[targetIndex].route)
      .then(() => {
        state.cacheList.splice(currentPageIndex, 1);
      })
      .catch();
  };

  const getCaches = () => {
    return state.cacheList;
  };

  // alias
  const refresh = async (path?: CacheKey | undefined) => {
    if (!path) {
      path = state.current;
    }
    await clearCache(path);
    const cacheItemIndex = state.cacheList.findIndex(item => item.path === path);
    const cacheItem = state.cacheList[cacheItemIndex];
    state.cacheList[cacheItemIndex] = { ...toRaw(cacheItem), key: guid() };
    return new Promise<void>(resolve => {
      router.replace(cacheItem?.route || { path }).finally(() => {
        // 模拟loading效果，加载太快，loading 不明显，主动加个延时 ，如不需要可删除延迟
        setTimeout(() => {
          resolve();
        }, 900);
      });
    });
  };

  const deleteCaches = async (start: number, num: number) => {
    const list = state.cacheList;
    const end = start + num;
    const newList: CacheItem[] = [];
    const deleteKeyList: string[] = [];
    for (let i = 0; i < list.length; i++) {
      const item = list[i];
      if (i < start || i >= end || item.lock) {
        newList.push(item);
      } else {
        deleteKeyList.push(item.key);
        delete componentMap[item.key];
      }
    }
    state.exclude = deleteKeyList;
    state.cacheList = newList;
    return new Promise<void>(resolve => {
      setTimeout(() => {
        state.exclude = [];
        resolve();
      });
    });
  };

  const deleteCachesByKeys = async (paths: string[]) => {
    const list = state.cacheList;
    const newList: CacheItem[] = [];
    const deleteKeyList: string[] = [];
    for (let i = 0; i < list.length; i++) {
      const item = list[i];
      if (paths.indexOf(item.path) === -1) {
        newList.push(item);
      } else {
        deleteKeyList.push(item.key);
        delete componentMap[item.key];
      }
    }
    state.exclude = deleteKeyList;
    state.cacheList = newList;
    return new Promise<void>(resolve => {
      setTimeout(() => {
        state.exclude = [];
        resolve();
      });
    });
  };

  const closeLeft = (selectedPath: CacheKey) => {
    const index = state.cacheList.findIndex(item => item.path === selectedPath);
    const currentIndex = state.cacheList.findIndex(item => item.path === route.path);
    if (currentIndex < index) {
      router
        .replace(state.cacheList[index].route)
        .then(() => {
          deleteCaches(0, index);
        })
        .catch();
    } else {
      deleteCaches(0, index);
    }
  };

  const closeRight = (selectedPath: CacheKey) => {
    const index = state.cacheList.findIndex(item => item.path === selectedPath);
    const currentIndex = state.cacheList.findIndex(item => item.path === route.path);
    if (currentIndex > index) {
      router
        .replace(state.cacheList[index].route)
        .then(() => {
          deleteCaches(index + 1, state.cacheList.length - index - 1);
        })
        .catch();
    } else {
      deleteCaches(index + 1, state.cacheList.length - index - 1);
    }
  };

  const closeOther = (selectedPath: CacheKey) => {
    const index = state.cacheList.findIndex(cached => cached.path === selectedPath);
    router
      .replace(state.cacheList[index].route)
      .then(async () => {
        await deleteCaches(index + 1, state.cacheList.length - index - 1);
        await deleteCaches(0, index);
      })
      .catch();
  };

  return [
    {
      deleteCachesByKeys,
      close,
      getCaches,
      clearCache,
      closeLeft,
      closeRight,
      closeOther,
      refresh,
    },
  ];
};

export default useMultiTab;
