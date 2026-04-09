import type Editor from '../Editor';

// IEditor类型包含插件实例，Editor不包含插件实例
// eslint-disable-next-line @typescript-eslint/no-empty-interface
export interface IEditor extends Editor {}

// 生命周期事件类型
export type IEditorHooksType =
  | 'hookImportBefore'
  | 'hookImportAfter'
  | 'hookSaveBefore'
  | 'hookSaveAfter'
  | 'hookTransform';

// 插件实例
export declare class IPluginTempl {
  constructor(canvas: fabric.Canvas, editor: IEditor, options?: IPluginOption);
  static pluginName: string;
  static events: string[];
  static apis: string[];
  hotkeyEvent?: (name: string, e: KeyboardEvent) => void;
  hookImportBefore?: (...args: unknown[]) => Promise<unknown>;
  hookImportAfter?: (...args: unknown[]) => Promise<unknown>;
  hookSaveBefore?: (...args: unknown[]) => Promise<unknown>;
  hookSaveAfter?: (...args: unknown[]) => Promise<unknown>;
  hookTransform?: (...args: unknown[]) => Promise<unknown>;
  [propName: string]: any;
  canvas?: fabric.Canvas;
  editor?: IEditor;
}

export declare interface IPluginOption {
  [propName: string]: unknown | undefined;
}

declare class IPluginClass2 extends IPluginTempl {
  constructor();
}
// 插件class
export declare interface IPluginClass {
  new (canvas: fabric.Canvas, editor: Editor, options?: IPluginOption): IPluginClass2;
}

export declare interface IPluginMenu {
  text: string;
  command?: () => void;
  child?: IPluginMenu[];
}

// 动态变量配置类型
export interface DynamicConfig {
  isDynamic: boolean;
  variableName?: string;
  variableType?: 'text' | 'image';
  categoryId?: string;
  defaultValue?: any;
  remark?: string; // 备注说明
}

// 动态变量信息
export interface DynamicVariable {
  id: string;
  variableName: string;
  variableType: 'text' | 'image';
  categoryId?: string;
  defaultValue?: any;
  objectType: string;
  remark?: string; // 备注说明
}
