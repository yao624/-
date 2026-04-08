import request from '@/utils/request';

export async function queryListApi(params?: { [key: string]: any }) {
  return request.get('/rules', {
    params,
  });
}

export async function addOneApi(params: Record<string, any>) {
  const url = params.id ? `rules/${params.id}` : '/rules';
  return request(url, {
    method: params.id ? 'PUT' : 'POST',
    data: params,
  });
}

export async function deleteOneApi(id?: string) {
  return request('/rules/' + id, {
    method: 'DELETE',
    data: {},
  });
}
export async function syncDataCheckRuleApi() {
  return request('/rules/sync-data-check-rule');
}

export async function batchDelete(params: Record<string, any>) {
  const url = '/rules/batch-delete';
  return request(url, {
    method: 'POST',
    data: params,
  });
}

export async function batchActive(params: Record<string, any>) {
  const url = '/rules/batch-active';
  return request(url, {
    method: 'POST',
    data: params,
  });
}

export async function batchInactive(params: Record<string, any>) {
  const url = '/rules/batch-inactive';
  return request(url, {
    method: 'POST',
    data: params,
  });
}

export async function triggerAutomationPipeline() {
  return request('/rules/trigger-automation-pipeline', {
    method: 'POST',
    data: {},
  });
}

export async function getJobsApi(params?: { [key: string]: any }) {
  return request.get('/jobs', {
    params,
  });
}

export async function addJobApi(params: Record<string, any>) {
  const url = params.id ? `jobs/${params.id}` : '/jobs';
  return request(url, {
    method: params.id ? 'PUT' : 'POST',
    data: params,
  });
}

export async function deleteOneJobApi(id?: string) {
  return request('/jobs/' + id, {
    method: 'DELETE',
    data: {},
  });
}

export async function batchDeleteJobApi(params: Record<string, any>) {
  const url = '/jobs/batch-delete';
  return request(url, {
    method: 'POST',
    data: params,
  });
}

export async function triggerSyncKeitaroToKv() {
  return request('/misc/sync-lander-path-to-kv', {
    method: 'POST',
    data: {},
  });
}
