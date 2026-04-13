import type { ConfigEnv, UserConfig } from 'vite';
import { loadEnv } from 'vite';
import vue from '@vitejs/plugin-vue';
import vueJsx from '@vitejs/plugin-vue-jsx';
import path, { resolve } from 'path';
import legacy from '@vitejs/plugin-legacy';
import UnoCss from 'unocss/vite';
const mock = require('./build/mock/createMockServer');

export default ({ mode }: ConfigEnv): UserConfig => {
  const root = process.cwd();
  const env = loadEnv(mode, root, '');
  const apiBase = env.VITE_APP_API_BASE_URL || '/api/v2';
  const publicPath = env.VITE_APP_PUBLIC_PATH || '/';
  // 与 backend .env APP_URL 对齐；勿默认指向外网域名，否则代理到错误站点会全接口 404
  const proxyTarget = env.VITE_PROXY_TARGET || env.VUE_APP_PROXY_TARGET || 'http://www.toufang.com/';
  return {
    base: publicPath,
    // 兼容 Cli
    define: {
      __VUE_I18N_FULL_INSTALL__: 'true',
      __VUE_I18N_LEGACY_API__: 'true',
      __VUE_I18N_PROD_DEVTOOLS__: 'false',
      'process.env.VUE_APP_API_BASE_URL': JSON.stringify(apiBase),
      'process.env.VUE_APP_PUBLIC_PATH': JSON.stringify(publicPath),
    },
    plugins: [
      legacy({
        targets: ['defaults', 'not IE 11', 'Chrome 63', 'Firefox > 20'],
        modernPolyfills: true,
      }),
      vue(),
      vueJsx(),
      UnoCss(),
      mock({
        watch: true,
        mockUrlList: [/api/],
        cwd: process.cwd(),
        enable: env.VITE_HTTP_MOCK === 'true' && process.env.NODE_ENV !== 'production',
      }),
    ],
    build: {
      cssCodeSplit: false,
      chunkSizeWarningLimit: 2048,
      rollupOptions: {
        input: {
          main: resolve(__dirname, 'index.html'),
          subpage: resolve(__dirname, 'pages/index.html'),
        },
        output: {
          manualChunks: {
            vue: ['vue', 'pinia', 'vue-router'],
            antdv: ['ant-design-vue', '@ant-design/icons-vue'],
          },
        },
      },
    },
    resolve: {
      alias: {
        '~@': path.join(__dirname, './src'),
        '@': path.join(__dirname, './src'),
        '~': path.join(__dirname, './src/assets'),
        vue: 'vue/dist/vue.esm-bundler.js',
        dayjs: resolve(__dirname, 'node_modules', 'dayjs'),
        'ant-design-vue': resolve(__dirname, 'node_modules', 'ant-design-vue'),
      },
    },
    optimizeDeps: {
      include: [
        'ant-design-vue/es/locale/en_US',
        'ant-design-vue/es/locale/zh_CN',
        'store/plugins/expire',
        'ant-design-vue/es/form',
        'dayjs',
        'dayjs/locale/en',
        'dayjs/locale/zh-cn',
        '@ant-design/icons-vue',
        'lodash-es',
        'pinia',
        'vue-router',
        'vue',
        'vue-i18n',
        '@vueuse/core',
      ],
    },
    css: {
      preprocessorOptions: {
        less: {
          modifyVars: {
            hack: 'true; @import "~/styles/variables.less";',
          },
          // DO NOT REMOVE THIS LINE
          javascriptEnabled: true,
        },
      },
    },
    server: {
      host: true,
      proxy:
        env.VITE_HTTP_MOCK === 'true'
          ? undefined
          : {
              '/api': {
                target: proxyTarget,
                ws: false,
                changeOrigin: true,
              },
            },
    },
  };
};
