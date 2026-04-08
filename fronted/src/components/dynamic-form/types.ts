interface Option {
  label: string;
  value: string | number | boolean;
}

type AsyncOption = (keyword: string) => Promise<Array<Option>>;

export interface FormItem {
  label: string;
  field: string;
  multiple?: boolean | unknown;
  options?: Array<Option> | Promise<Array<Option>> | unknown | AsyncOption;
  mode?: 'radio' | 'select' | unknown;
  isBoolean?: boolean | unknown;
  checkBoxLabel?: string | unknown;
  isDate?: boolean | unknown;
  value?: any | unknown;
  text?: boolean | unknown;
  rules?: Array<any>;
  isOptionDisabled?: (item: FormItem, option: Option, form: any) => boolean;
}