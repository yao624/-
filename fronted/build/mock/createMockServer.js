/**
 * Vite 插件：在开发环境将匹配 mockUrlList 的请求交给 umi-mock-middleware 处理。
 * mock 数据目录为项目根下的 `mock/`，规则与 umi 一致。
 */
const { createMockMiddleware } = require('umi-mock-middleware');

/**
 * @param {object} [options]
 * @param {boolean} [options.watch]
 * @param {RegExp[]} [options.mockUrlList]
 * @param {string} [options.cwd]
 * @param {boolean} [options.enable]
 */
module.exports = function createMockServer(options = {}) {
  const {
    mockUrlList = [],
    enable = false,
  } = options;

  return {
    name: 'vite-plugin-umi-mock',
    configureServer(server) {
      if (!enable) {
        return;
      }
      const middleware = createMockMiddleware();
      server.middlewares.use((req, res, next) => {
        const pathname = (req.url || '').split('?')[0] || '';
        const hit = mockUrlList.some((re) => re.test(pathname));
        if (!hit) {
          return next();
        }
        req.path = pathname;
        return middleware(req, res, next);
      });
    },
  };
};
