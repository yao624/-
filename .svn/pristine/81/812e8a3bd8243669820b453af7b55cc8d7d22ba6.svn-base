/* eslint-disable @typescript-eslint/consistent-type-imports */
/**
 *  此文件注册全局组件类型定义
 */
import type { AnchorHTMLAttributes as InnerAnchorHTMLAttributes } from 'vue';
declare module 'vue' {
  export interface GlobalComponents {
    RouterView: typeof import('vue-router')['RouterView'];
    RouterLink: typeof import('vue-router')['RouterLink'];
    Component: import('vue').Component<{
      is: any;
    }>;
  }
  export interface ComponentCustomProperties {
    document?: Document;
  }
  interface AnchorHTMLAttributes extends InnerAnchorHTMLAttributes {
    disabled?: boolean;
  }
}
export {};
