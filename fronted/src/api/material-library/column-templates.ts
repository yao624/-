import request from '@/utils/request';

const getMaterialLibraryBaseURL = () => {
  const base = String(process.env.VUE_APP_API_BASE_URL || '');
  if (base.endsWith('/v2')) return base.slice(0, -3);
  return base.replace('/v2', '');
};

export type MaterialLibraryColumnTemplate = {
  id?: string | number;
  name: string;
  selectedKeys: string[];
  fixedLeftKeys: string[];
};

// 后端接口（建议实现）：
// GET    /material-library/column-templates
// POST   /material-library/column-templates
// - 返回值：{ data: { templates: Array<...> } } 或 { data: Array<...> }
export async function listMaterialLibraryColumnTemplates() {
  return request.get<any, any>('/material-library/column-templates', {
    baseURL: getMaterialLibraryBaseURL(),
  });
}

export async function saveMaterialLibraryColumnTemplate(payload: MaterialLibraryColumnTemplate) {
  return request.post<any, any>('/material-library/column-templates', payload, {
    baseURL: getMaterialLibraryBaseURL(),
  });
}

