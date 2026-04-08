/**
 * 解析 API 前缀。若误配为完整生产地址，在本地开发 / vite preview 时改为相对路径，
 * 使请求走当前页同源并由 devServer 代理，避免跨域 CORS。
 */
function stripAbsoluteApiBaseToPathname(value: string): string {
  try {
    const pathname = new URL(String(value)).pathname.replace(/\/$/, '') || '/api/v2';
    return pathname.startsWith('/') ? pathname : `/${pathname}`;
  } catch {
    return '/api/v2';
  }
}

export function resolveApiBaseUrl(): string {
  const raw =
    process.env.VUE_APP_API_BASE_URL ||
    process.env.VUE_APP_API_URL ||
    '/api/v2';

  const isDev =
    (typeof import.meta !== 'undefined' && (import.meta as ImportMeta).env?.DEV) ||
    process.env.NODE_ENV === 'development';

  // localhost 上无论 dev 还是 vite preview（生产包），import.meta.env.DEV 常为 false，
  // 若仍使用 .env 里的生产完整 URL，会直连线上导致跨域；此处强制改为同源相对路径走代理。
  if (typeof window !== 'undefined') {
    const h = window.location.hostname;
    if (h === 'localhost' || h === '127.0.0.1' || h === '[::1]') {
      if (/^https?:\/\//i.test(String(raw))) {
        return stripAbsoluteApiBaseToPathname(String(raw));
      }
      return raw;
    }
  }

  if (!isDev) {
    return raw;
  }

  if (/^https?:\/\//i.test(String(raw))) {
    return stripAbsoluteApiBaseToPathname(String(raw));
  }

  return raw;
}
