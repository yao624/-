import request from '@/utils/request';

export async function queryNetworksApi(params?: { [key: string]: any }) {
  return request.get('/networks', {
    params,
  });
}

export async function addNetworksOneApi(params: Record<string, any>) {
  const url = params.id ? `networks/${params.id}` : '/networks';
  return request(url, {
    method: params.id ? 'PUT' : 'POST',
    data: params,
  });
}

export async function deleteNetworksOneApi(id?: string) {
  return request('/networks/' + id, {
    method: 'DELETE',
    data: {},
  });
}
export async function queryTagsApi(params?: { [key: string]: any }) {
  return request.get('/tags', {
    params,
  });
}
export async function addTagsOneApi(params: Record<string, any>) {
  return request('/tags', {
    method: 'POST',
    data: params,
  });
}
export async function deleteTagsOneApi(id?: string) {
  return request('/tags/' + id, {
    method: 'DELETE',
    data: {},
  });
}
export async function syncDataCheckRuleApi() {
  return request('/rule/sync-data-check-rule');
}

export async function fetchAll(params: Record<string, any>) {
  return request('/networks/fetch-all', {
    method: 'POST',
    data: params,
  });
}

export async function fetchKeitaro(params: Record<string, any>) {
  return request('/networks/fetch-keitaro', {
    method: 'POST',
    data: params,
  });
}

export async function testNetworkConnection(networkId: string) {
  return request('/networks/test-connection', {
    method: 'POST',
    data: { network_id: networkId },
  });
}