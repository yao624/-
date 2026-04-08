import type { VNodeChild } from 'vue';
import type { RouteMeta, RouteComponent, RouteRecordRedirectOption } from 'vue-router';

type Lazy<T> = () => Promise<T>;

export interface MenuRouteMeta {
  hideChildrenInMenu?: boolean;
  hideInBreadcrumb?: boolean;
  hideInMenu?: boolean;
  icon?: string | VNodeChild;
  authority?: string | string[];
  target?: '_blank' | '_self' | '_parent' | '_top';
  keepAlive?: boolean;
  lock?: boolean;
  mergeTab?: boolean;
}

export type MenuDataItem = {
  path: string;
  name?: string;
  children?: MenuDataItem[];
  meta?: MenuRouteMeta & RouteMeta;
  redirect?: RouteRecordRedirectOption;
  component?: RouteComponent | Lazy<RouteComponent>;
};
