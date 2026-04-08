/**
 * 动态路由（currentUserNav）可能缺失部分静态菜单项。
 * 在不影响后端菜单优先级的前提下，为关键页面做兜底合并，避免左侧菜单“消失”。
 */
import { defineAsyncComponent } from 'vue';
import type { MenuDataItem } from './typing';
import RouteView from '@/layouts/route-view.vue';

const AsyncPromotionDashboard = defineAsyncComponent(
  () => import('@/views/promotion/dashboard/index.vue'),
);

const AsyncCopywritings = defineAsyncComponent(() => import('@/views/copywritings/index_v2.vue'));
const AsyncLinks = defineAsyncComponent(() => import('@/views/links/index_v2.vue'));

export const STATIC_PROMOTION_DASHBOARD_ROUTE: MenuDataItem = {
  path: '/promotion/dashboard',
  name: 'PromotionDashboard',
  meta: {
    title: 'pages.promotionDashboard.title',
    icon: 'DashboardOutlined',
    authority: ['ads'],
  },
  component: AsyncPromotionDashboard,
};

export const STATIC_CREATIVE_ROUTE: MenuDataItem = {
  path: '/creative',
  name: 'creative',
  meta: {
    title: 'pages.creative.title',
    icon: 'BulbOutlined',
    authority: ['materials'],
  },
  component: RouteView,
  children: [
    {
      path: '/creative/copywritings',
      name: 'copywritings',
      meta: {
        title: 'pages.copywritings.title',
        icon: 'FileTextOutlined',
        authority: ['copywritings'],
      },
      component: AsyncCopywritings,
    },
    {
      path: '/creative/links',
      name: 'links',
      meta: { title: 'pages.links.title', icon: 'LinkOutlined', authority: ['links'] },
      component: AsyncLinks,
    },
  ],
};

