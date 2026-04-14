/**
 * 将素材图片的完整 URL 转换为走同域代理的相对路径，解决 canvas CORS 问题。
 *
 * http://shgkjl12knvsk.com/storage/xmp_materials/xxx.png → /storage/xmp_materials/xxx.png
 *
 * 非 http(s) 开头或不含 /storage/ 的地址原样返回。
 */
export function toProxiedUrl(url: string): string {
  if (!url || !/^https?:\/\//i.test(url)) return url;
  try {
    const u = new URL(url);
    // 匹配 /storage/ 开头的路径
    if (u.pathname.startsWith('/storage/')) {
      return u.pathname + u.search;
    }
  } catch {
    // url 解析失败，原样返回
  }
  return url;
}
