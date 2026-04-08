import { defineConfig, presetUno } from 'unocss';

export default defineConfig({
  content: {
    pipeline: {
      include: [/\.vue$/, /\.vue\?vue/, /\.[jt]sx$/, /\.html$/],
    },
  },
  presets: [presetUno()],
  shortcuts: {
    'card-border': ['rounded', 'shadow-sm'],
  },
  theme: {
    breakpoints: {
      md: '768px',      // 平板（可保留，不影响）
      lg: '1024px',     // 小笔记本

      // 👇 你真正需要的 4 个核心断点
      xl: '1280px',     // ✅ 13寸 MacBook（核心基准）
      '2xl': '1440px',  // ✅ 14/16寸 MacBook
      '3xl': '1920px',  // ✅ 27寸 显示器
      '4xl': '2560px',  // ✅ 32寸 显示器
    },
    borderRadius: {
      sm: '4px',
      DEFAULT: '8px',
      md: '12px',
      lg: '16px',
    },
    colors: {
      blue: '#1055FF',
    },
    boxShadow: {
      DEFAULT: '0px 2px 14px 0px rgba(0,0,0,0.08)',
      small: '0px 2px 14px rgba(0,0,0,0.03)',
      card: '0px 0px 7px 0px rgba(0,0,0,0.08)',
    },
    // extend: {},
  },

  rules: [
    ['divider-top', { 'box-shadow': 'inset 0px 1px 0px var(--divider-color)' }],
    [
      'divider-top-bottom',
      {
        'box-shadow':
          'inset 0px 1px 0px var(--divider-color), inset 0px -1px 0px var(--divider-color)',
      },
    ],
    ['divider-bottom', { 'box-shadow': 'inset 0px -1px 0px var(--divider-color)' }],
    ['divider-right', { 'box-shadow': 'inset -1px 0px 0px var(--divider-color)' }],
    ['divider-left', { 'box-shadow': 'inset 1px 0px 0px var(--divider-color)' }],
    ['shadow-right', { 'box-shadow': 'var(--divider-color) -1px 0px inset' }],
    ['shadow-left', { 'box-shadow': 'var(--divider-color) 1px 0px inset' }],
  ],
});
