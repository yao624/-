/**
 * 本地模版类型定义
 */

export interface LocalTemplate {
  id: string;
  name: string;
  width: number;
  height: number;
  json: any;
  dynamicVariables: Array<{
    variableName: string;
    variableType: 'text' | 'image';
    remark?: string;
  }>;
  createdAt: string;
  updatedAt: string;
}

export interface LocalTemplateFilter {
  name?: string;
  dimension?: string;
}
