import request from '@/utils/request';

export async function quickLaunchAds(params: Record<string, any>) {
  return request('/fb-ads/quick-launch-ads', {
    method: 'POST',
    data: params,
  });
}

export async function launchAds(params: Record<string, any>) {
  return request('/fb-ads/launch', {
    method: 'POST',
    data: params,
  });
}
