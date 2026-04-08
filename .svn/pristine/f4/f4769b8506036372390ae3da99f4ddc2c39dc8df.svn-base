/**
 * 与 router/index 中「工具」菜单一致，单独抽出供 generator-routers 合并动态路由，
 * 避免 generator-routers 再 import ./index 造成循环依赖。
 */
import { defineAsyncComponent } from 'vue';
import type { MenuDataItem } from './typing';
import RouteView from '@/layouts/route-view.vue';

const AsyncFacebookPages = defineAsyncComponent(
  () => import('@/views/instrument/facebook-pages.vue'),
);
const AsyncTaskCenter = defineAsyncComponent(() => import('@/views/instrument/task-center.vue'));
const AsyncNotificationCenter = defineAsyncComponent(
  () => import('@/views/instrument/notification-center.vue'),
);
const AsyncScheduledReport = defineAsyncComponent(
  () => import('@/views/instrument/scheduled-report.vue'),
);
const AsyncScheduledReportEdit = defineAsyncComponent(
  () => import('@/views/instrument/scheduled-report-edit.vue'),
);

/** 供动态路由合并：与静态路由表 /instrument 子树一致 */
export const STATIC_INSTRUMENT_ROUTE: MenuDataItem = {
  path: '/instrument',
  name: 'instrument',
  meta: { title: '工具', icon: 'ToolOutlined' },
  component: RouteView,
  redirect: '/instrument/facebook-pages',
  children: [
    {
      path: '/instrument/facebook-pages',
      name: 'instrument-facebook-pages',
      meta: { title: 'Facebook主页', icon: 'FacebookOutlined' },
      component: AsyncFacebookPages,
    },
    {
      path: '/instrument/task-center',
      name: 'instrument-task-center',
      meta: { title: '任务中心', icon: 'ProjectOutlined' },
      component: AsyncTaskCenter,
    },
    {
      path: '/instrument/notification-center',
      name: 'instrument-notification-center',
      meta: { title: '通知中心', icon: 'BellOutlined' },
      component: AsyncNotificationCenter,
    },
    {
      path: '/instrument/scheduled-report',
      name: 'instrument-scheduled-report',
      meta: { title: '定时报表', icon: 'FileTextOutlined' },
      component: AsyncScheduledReport,
    },
    {
      path: '/instrument/scheduled-report/edit',
      name: 'instrument-scheduled-report-edit',
      meta: { title: '编辑报表', hideInMenu: true },
      component: AsyncScheduledReportEdit,
    },
  ],
};
