/*
 * @Author: 秦少卫
 * @Date: 2023-07-04 23:45:49
 * @LastEditors: 秦少卫
 * @LastEditTime: 2024-04-10 17:33:54
 * @Description: 标尺插件
 */

import { fabric } from 'fabric';
import type { IEditor, IPluginTempl } from '@kuaitu/core';

type IPlugin = Pick<
  RulerPlugin,
  'hideGuideline' | 'showGuideline' | 'rulerEnable' | 'rulerDisable'
>;

declare module '@kuaitu/core' {
  // eslint-disable-next-line @typescript-eslint/no-empty-interface
  interface IEditor extends IPlugin {}
}

import initRuler from '../ruler';

class RulerPlugin implements IPluginTempl {
  static pluginName = 'RulerPlugin';
  //  static events = ['sizeChange'];
  static apis = ['hideGuideline', 'showGuideline', 'rulerEnable', 'rulerDisable'];
  ruler: any;
  constructor(public canvas: fabric.Canvas, public editor: IEditor) {
    this.init();
  }

  hookSaveBefore() {
    return new Promise((resolve) => {
      this.hideGuideline();
      resolve(true);
    });
  }

  hookSaveAfter() {
    return new Promise((resolve) => {
      this.showGuideline();
      resolve(true);
    });
  }

  init() {
    this.ruler = initRuler(this.canvas);
  }

  hideGuideline() {
    this.ruler.hideGuideline();
  }

  showGuideline() {
    this.ruler.showGuideline();
  }

  rulerEnable() {
    try {
      // 检查 canvas 是否有有效尺寸
      if (!this.canvas.width || !this.canvas.height || this.canvas.width <= 0 || this.canvas.height <= 0) {
        console.warn('Canvas 尺寸无效，无法启用标尺:', { width: this.canvas.width, height: this.canvas.height });
        return;
      }
      this.ruler.enable();
    } catch (error) {
      console.error('启用标尺失败:', error);
    }
  }

  rulerDisable() {
    this.ruler.disable();
  }

  destroy() {
    console.log('pluginDestroy');
  }
}

export default RulerPlugin;
