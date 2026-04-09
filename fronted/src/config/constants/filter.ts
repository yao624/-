// Filter UI types
export enum uiType {
  SELECT = 'SELECT',
  NUMBER = 'NUMBER',
  COLOR = 'COLOR',
}

// Parameters for filters
export const paramsFilters = [
  {
    type: 'blur',
    status: false,
    params: [
      {
        key: 'blur',
        value: 0.1,
        min: 0,
        max: 1,
        step: 0.01,
        uiType: uiType.NUMBER,
      },
    ],
  },
  {
    type: 'brightness',
    status: false,
    params: [
      {
        key: 'brightness',
        value: 0.1,
        min: -1,
        max: 1,
        step: 0.01,
        uiType: uiType.NUMBER,
      },
    ],
  },
  {
    type: 'contrast',
    status: false,
    params: [
      {
        key: 'contrast',
        value: 0,
        min: -1,
        max: 1,
        step: 0.01,
        uiType: uiType.NUMBER,
      },
    ],
  },
  {
    type: 'saturation',
    status: false,
    params: [
      {
        key: 'saturation',
        value: 0,
        min: -1,
        max: 1,
        step: 0.01,
        uiType: uiType.NUMBER,
      },
    ],
  },
  {
    type: 'noise',
    status: false,
    params: [
      {
        key: 'noise',
        value: 0,
        min: 0,
        max: 1000,
        step: 1,
        uiType: uiType.NUMBER,
      },
    ],
  },
  {
    type: 'pixelate',
    status: false,
    params: [
      {
        key: 'blocksize',
        value: 10,
        min: 2,
        max: 100,
        step: 1,
        uiType: uiType.NUMBER,
      },
    ],
  },
];

// Combination filters
export const combinationFilters = [
  {
    type: 'blend',
    status: false,
    params: [
      {
        key: 'mode',
        value: 'add',
        list: ['add', 'multiply', 'screen', 'overlay', 'darken', 'lighten'],
        uiType: uiType.SELECT,
      },
      {
        key: 'alpha',
        value: 0.5,
        min: 0,
        max: 1,
        step: 0.01,
        uiType: uiType.NUMBER,
      },
    ],
  },
  {
    type: 'tint',
    status: false,
    params: [
      {
        key: 'color',
        value: '#000000',
        uiType: uiType.COLOR,
      },
      {
        key: 'opacity',
        value: 0.5,
        min: 0,
        max: 1,
        step: 0.01,
        uiType: uiType.NUMBER,
      },
    ],
  },
];
