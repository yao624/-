import type { RouteRecordRaw } from 'vue-router';
import type { MenuDataItem } from './typing';
import type { RouteItem } from '@/api/user/login';
import { getCurrentUserNav } from '@/api/user/login';
import { STATIC_INSTRUMENT_ROUTE } from './static-instrument-merge';
import { STATIC_CREATIVE_ROUTE, STATIC_PROMOTION_DASHBOARD_ROUTE } from './static-fallback-merge';

// 根级菜单
const rootRouter: MenuDataItem = {
    name: 'index',
    path: '/',
    redirect: '/workplace',
    meta: {
        title: '首页',
    },
    component: () => import('@/layouts/index.vue'),
    children: [] as MenuDataItem[],
};

const defineRouteComponents: Record<string, any> = {
    BasicLayout: () => import('@/layouts/index.vue'),
    RouteView: () => import('@/layouts/route-view.vue'),
    PageView: () => import('@/layouts/route-view.vue'),
};

const defineRouteComponentKeys = Object.keys(defineRouteComponents);

export const generator = (
    routeMap: RouteItem[],
    parentId: string | number,
    routeItem?: RouteRecordRaw | MenuDataItem,
) => {
    return routeMap
        .filter(item => item.parentId === parentId)
        .map(item => {
            const { title, hideInMenu, hideChildrenInMenu, target, icon, authority } = item.meta || {};
            const currentRouter: MenuDataItem = {
                // 如果路由设置了 path，则作为默认 path，否则 路由地址 动态拼接生成如 /dashboard/workplace
                path: item.path || `${(routeItem && routeItem.path) || ''}/${item.name}`,
                // 路由名称，建议唯一
                name: item.name || `${item.id}`,
                // meta: 页面标题, 菜单图标, 页面权限(供指令权限用，可去掉)
                meta: {
                    title,
                    icon: icon || undefined,
                    hideInMenu,
                    hideChildrenInMenu,
                    target: target,
                    authority: authority,
                },
                // 该路由对应页面的 组件 (动态加载 @/views/ 下面的路径文件)
                component:
                    item.component && defineRouteComponentKeys.includes(item.component)
                        ? defineRouteComponents[item.component]
                        : () => import(/* @vite-ignore */ `/src/views/${item.component}`),
            };

            // 为了防止出现后端返回结果不规范，处理有可能出现拼接出两个 反斜杠
            if (currentRouter.path && !currentRouter.path.startsWith('http')) {
                currentRouter.path = currentRouter.path.replace('//', '/');
            }

            // 重定向
            item.redirect && (currentRouter.redirect = item.redirect);

            // 子菜单，递归处理
            currentRouter.children = generator(routeMap, item.id, currentRouter);
            if (currentRouter.children === undefined || currentRouter.children.length <= 0) {
                delete currentRouter.children;
            }
            return currentRouter;
        })
        .filter(item => item);
};

// 从树形结构生成路由
const generatorFromTree = (treeData: any[], parentRoute?: MenuDataItem): MenuDataItem[] => {
    return treeData
        .filter(item => !item.meta?.hideInMenu)
        .map(item => {
            const meta = item.meta || {};
            const { hideInMenu, hideChildrenInMenu, target, icon, authority } = meta;
            const title = meta.title || item.name; // 使用 name 作为 fallback
            const currentRouter: MenuDataItem = {
                path: item.path || (parentRoute ? `${parentRoute.path}/${item.name}` : `/${item.name}`),
                name: item.name || `${item.id}`,
                meta: {
                    title,
                    icon: icon || undefined,
                    hideInMenu,
                    hideChildrenInMenu,
                    target,
                    authority,
                },
            };

            // 处理组件
            if (item.component) {
                if (defineRouteComponentKeys.includes(item.component)) {
                    currentRouter.component = defineRouteComponents[item.component];
                } else {
                    // 移除 @/ 前缀，转换为相对路径
                    const componentPath = item.component.replace('@/', '');
                    currentRouter.component = () => import(/* @vite-ignore */ `/src/${componentPath}`);
                }
            }

            // 防止 path 重复斜杠
            if (currentRouter.path && !currentRouter.path.startsWith('http')) {
                currentRouter.path = currentRouter.path.replace('//', '/');
            }

            // 重定向
            if (item.redirect) {
                currentRouter.redirect = item.redirect;
            }

            // 递归处理子菜单
            if (item.children && item.children.length > 0 && !hideChildrenInMenu) {
                currentRouter.children = generatorFromTree(item.children, currentRouter);
            }

            return currentRouter;
        })
        .filter(item => item);
};

/**
 * 后端 currentUserNav 若未同步新增「工具」子菜单（如定时报表），对应路径会未注册而 404。
 * 将前端静态路由表里 /instrument 下已定义、但动态菜单缺失的项合并进来。
 */
const mergeStaticInstrumentRoutes = (dynamicChildren: MenuDataItem[]) => {
    const staticInstrument = STATIC_INSTRUMENT_ROUTE;
    if (!staticInstrument?.children?.length) return;

    const idx = dynamicChildren.findIndex(c => c.path === '/instrument' || c.name === 'instrument');
    if (idx >= 0) {
        const existing = dynamicChildren[idx];
        const merged = [...(existing.children || [])];
        const byPath = new Set(merged.map(c => c.path).filter(Boolean));
        const byName = new Set(merged.map(c => c.name).filter(Boolean));
        for (const ch of staticInstrument.children) {
            if (ch.path && byPath.has(ch.path)) continue;
            if (ch.name && byName.has(ch.name)) continue;
            merged.push(ch);
            if (ch.path) byPath.add(ch.path);
            if (ch.name) byName.add(ch.name);
        }
        dynamicChildren[idx] = { ...existing, children: merged };
    } else {
        dynamicChildren.push(staticInstrument as MenuDataItem);
    }
};

/** 合并关键静态菜单项（看板/文案库等） */
const mergeStaticFallbackRoutes = (dynamicChildren: MenuDataItem[]) => {
    const statics: MenuDataItem[] = [STATIC_PROMOTION_DASHBOARD_ROUTE, STATIC_CREATIVE_ROUTE];
    const byPath = new Set(dynamicChildren.map(c => c.path).filter(Boolean));
    const byName = new Set(dynamicChildren.map(c => c.name).filter(Boolean));
    for (const s of statics) {
        if (s.path && byPath.has(s.path)) continue;
        if (s.name && byName.has(s.name)) continue;
        dynamicChildren.push(s);
        if (s.path) byPath.add(s.path);
        if (s.name) byName.add(s.name);
    }
};

export const generatorDynamicRouter = () => {
    return new Promise<RouteRecordRaw>((resolve, reject) => {
        getCurrentUserNav()
            .then((res: any) => {
                // 接口返回的 data 已经是树结构（有 children）
                const menuData = res?.data || res || [];
                const routes = generatorFromTree(menuData);
                mergeStaticInstrumentRoutes(routes);
                mergeStaticFallbackRoutes(routes);
                rootRouter.children = routes;
                resolve(rootRouter as RouteRecordRaw);
            })
            .catch(err => {
                reject(err);
            });
    });
};
