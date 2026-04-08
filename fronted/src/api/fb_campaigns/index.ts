import request from '@/utils/request';

export async function queryFBCampaignsApi(params?: { [key: string]: any }) {
  return request.get('/fb-campaigns', {
    params,
  });
}

export async function archiveFbCampaigns(params: Record<string, any>) {
  const url = 'fb-campaigns/archive';
  return request(url, {
    method: 'POST',
    data: params,
  });
}

export async function unarchiveFbCampaigns(params: Record<string, any>) {
  const url = 'fb-campaigns/unarchive';
  return request(url, {
    method: 'POST',
    data: params,
  });
}

export async function cbo2Abo(params: Record<string, any>) {
  const url = 'fb-campaigns/cbo-2-abo';
  return request(url, {
    method: 'POST',
    data: params,
  });
}
