import { fabric } from 'fabric';
import { getPolygonVertices } from '../../../../src/utils/math';
import { get, set } from 'lodash-es';
import type { IEditor, IPluginTempl } from '@kuaitu/core';

type IPlugin = Pick<SimpleClipImagePlugin, 'addClipPathToImage' | 'removeClip'>;

declare module '@kuaitu/core' {
  // eslint-disable-next-line @typescript-eslint/no-empty-interface
  interface IEditor extends IPlugin {}
}

const getBounds = (activeObject: fabric.Object) => {
  const { left = 0, top = 0 } = activeObject;
  const width = activeObject.getScaledWidth();
  const height = activeObject.getScaledHeight();

  // 检查尺寸是否有效
  if (!width || !height || width <= 0 || height <= 0) {
    console.warn('对象尺寸无效，无法创建 clipPath:', { width, height });
    return { width: 0, height: 0, left, top };
  }

  return {
    width,
    height,
    left,
    top,
  };
};
const bindInfo = (shell: fabric.Object, activeObject: fabric.Object) => {
  bindFlagToObject(shell);
  bindFlagToObject(shell, 'targetId', get(activeObject, 'id'));
  bindFlagToObject(shell, 'targetType', get(activeObject, 'type'));
};
const bindFlagToObject = (activeObject: fabric.Object, key = 'clip', value: any = true) => {
  set(activeObject, key, value);
};
const createRectClip = (activeObject: fabric.Object, inverted: boolean) => {
  const { width = 0, height = 0, left = 0, top = 0 } = getBounds(activeObject);
  const clipW = Math.round(width / 2);
  const clipH = Math.round(height / 2);
  const shell = new fabric.Rect({
    width: clipW,
    height: clipH,
    fill: 'rgba(0,0,0,0)',
    originX: 'center',
    originY: 'center',
    left: left + width / 2,
    top: top + height / 2,
  });
  bindInfo(shell, activeObject);
  const clipPath = new fabric.Rect({
    absolutePositioned: true,
    width: shell.width,
    height: shell.height,
    originX: 'center',
    originY: 'center',
    left: shell.left,
    top: shell.top,
    inverted: inverted,
  });
  return { clipPath, shell };
};
const createCircleClip = (activeObject: fabric.Object, inverted: boolean) => {
  const point = activeObject.getCenterPoint();
  const { width } = getBounds(activeObject);
  const shell = new fabric.Ellipse({
    fill: 'rgba(0,0,0,0)',
    originX: 'center',
    originY: 'center',
    left: point.x,
    top: point.y,
    rx: width / 4,
    ry: width / 4,
  });
  bindInfo(shell, activeObject);
  const clipPath = new fabric.Ellipse({
    absolutePositioned: true,
    originX: 'center',
    originY: 'center',
    left: shell.left,
    top: shell.top,
    inverted: inverted,
    rx: shell.rx,
    ry: shell.ry,
  });
  return { shell, clipPath };
};
const createTriClip = (activeObject: fabric.Object, inverted: boolean) => {
  const point = activeObject.getCenterPoint();
  const { width = 0, height = 0 } = getBounds(activeObject);
  const clipW = Math.round(width / 2);
  const clipH = Math.round(height / 2);
  const shell = new fabric.Triangle({
    fill: 'rgba(0,0,0,0)',
    originX: 'center',
    originY: 'center',
    left: point.x,
    top: point.y,
    width: clipW,
    height: clipH,
  });
  bindInfo(shell, activeObject);
  const clipPath = new fabric.Triangle({
    absolutePositioned: true,
    originX: 'center',
    originY: 'center',
    left: shell.left,
    top: shell.top,
    width: shell.width,
    height: shell.height,
    inverted: inverted,
  });
  return { shell, clipPath };
};
const createPolygonClip = (activeObject: fabric.Object, inverted: boolean) => {
  const point = activeObject.getCenterPoint();
  const points = getPolygonVertices(5, 200);
  const shell = new fabric.Polygon(points, {
    fill: 'rgba(0,0,0,0)',
    originY: 'center',
    originX: 'center',
    left: point.x,
    top: point.y,
  });
  bindInfo(shell, activeObject);
  const clipPath = new fabric.Polygon([...points], {
    absolutePositioned: true,
    originX: 'center',
    originY: 'center',
    left: shell.left,
    top: shell.top,
    inverted: inverted,
  });
  return { shell, clipPath };
};
export default class SimpleClipImagePlugin implements IPluginTempl {
  static pluginName = 'SimpleClipImagePlugin';
  //  static events = ['sizeChange'];
  static apis = ['addClipPathToImage', 'removeClip'];
  constructor(public canvas: fabric.Canvas, public editor: IEditor) {}
  addClipPathToImage(value: string) {
    const activeObject = this.canvas.getActiveObjects()[0];
    if (activeObject && activeObject.type === 'image') {
      // 检查对象是否有有效尺寸
      const width = activeObject.getScaledWidth();
      const height = activeObject.getScaledHeight();

      if (!width || !height || width <= 0 || height <= 0) {
        console.warn('图片尺寸无效，请等待图片加载完成后再试', { width, height });

        // 如果是图片对象，等待图片加载完成后再尝试
        if (activeObject instanceof fabric.Image && activeObject.getElement()) {
          const imgElement = activeObject.getElement();
          if (!imgElement.complete || imgElement.naturalWidth === 0) {
            // 图片还在加载中，提示用户等待
            console.info('图片正在加载中，请稍后再试');
            return;
          }
        }

        return;
      }

      let clip: { shell: fabric.Object; clipPath: fabric.Object } | null = null;
      const [name, inverted] = value.split('-');
      const isInverted = !!inverted;
      switch (name) {
        case 'polygon':
          clip = createPolygonClip(activeObject, isInverted);
          break;
        case 'rect':
          clip = createRectClip(activeObject, isInverted);
          break;
        case 'circle':
          clip = createCircleClip(activeObject, isInverted);
          break;
        case 'triangle':
          clip = createTriClip(activeObject, isInverted);
          break;
      }
      if (clip == null) return;
      const { shell, clipPath } = clip;
      shell.on('moving', () => {
        clipPath.setPositionByOrigin(shell.getCenterPoint(), 'center', 'center');
        activeObject.set('dirty', true);
      });
      shell.on('rotating', () => {
        clipPath.set({ angle: shell.angle });
        activeObject.set('dirty', true);
      });
      shell.on('scaling', () => {
        clipPath.set({ scaleX: shell.scaleX, scaleY: shell.scaleY });
        clipPath.setPositionByOrigin(shell.getCenterPoint(), 'center', 'center');
        activeObject.set('dirty', true);
      });
      shell.on('deselected', () => {
        if (clipPath instanceof fabric.Ellipse && shell instanceof fabric.Ellipse) {
          clipPath.set({ rx: shell.getRx(), ry: shell.getRy() });
          this.correctPosition(activeObject, shell, clipPath);
        } else if (shell instanceof fabric.Polygon) {
          this.correctPosition(activeObject, shell, clipPath);
          const { scaleX: cSx = 1, scaleY: cSy = 1 } = clipPath;
          const { scaleX: sSx = 1, scaleY: sSy = 1 } = shell;
          clipPath.set('scaleX', cSx * sSx);
          clipPath.set('scaleY', cSy * sSy);
        } else {
          this.correctPosition(activeObject, shell, clipPath);
          clipPath.set('width', shell.getScaledWidth());
          clipPath.set('height', shell.getScaledHeight());
        }
        activeObject.set('dirty', true);
        this.canvas.remove(shell);
        this.canvas.requestRenderAll();
      });
      activeObject.set({ clipPath: clipPath });
      this.canvas.add(shell);
      this.canvas.setActiveObject(shell);
    }
  }
  correctPosition(activeObject: fabric.Object, shell: fabric.Object, clipPath: fabric.Object) {
    const position = activeObject.toLocalPoint(shell.getCenterPoint(), 'center', 'center');
    const { scaleX = 1, scaleY = 1 } = activeObject;
    clipPath.set({
      absolutePositioned: false,
      left: position.x / scaleX,
      top: position.y / scaleY,
      scaleX: 1 / scaleX,
      scaleY: 1 / scaleY,
    });
  }
  removeClip() {
    const activeObject = this.canvas.getActiveObjects()[0];
    if (activeObject && activeObject.type === 'image') {
      activeObject.set({ clipPath: undefined });
      activeObject.set('dirty', true);
      this.canvas.requestRenderAll();
    }
  }
}
