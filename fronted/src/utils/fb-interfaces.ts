import type { AdsOperatorType } from './fb-enums';

export interface Tag {
  id: string;
  name: string;
  created_at: string;
  user_id: string;
  user_name: string;
}

export interface BusinessOwner {
  id: string;
  name: string;
}

export interface Creator {
  id: string;
  name: string;
}

export interface Pixel {
  id: string;
  created_at?: string;
  updated_at?: string;
  name?: string;
  pixel?: string;
  is_created_by_business?: boolean;
  is_unavailable?: boolean;
  owner_business?: BusinessOwner;
  creator?: Creator;
  notes?: string | null;
  tags?: Tag[];
}

export interface BmModel {
  id: string;
  source_id: string;
  created_at?: string;
  updated_at?: string;
  name?: string;
  created_time?: string;
  timezone_id?: string;
  verification_status?: string;
  two_factor_type?: string;
  notes?: string | null;
  users_count?: number;
  ad_accounts_count?: number;
  ad_accounts: FbAdAccount[];
  is_disabled_for_integrity_reasons?: boolean;
  pages?: FbPage[];
  pixels?: Pixel[];
  tags?: string[];
}

export interface BMSystemUser {
  id: string;
  name: string;
  active: boolean;
  bm: BmModel;
}

export interface FbAccount {
  id: string;
  created_at: string;
  updated_at: string;
  source_id: string;
  name: string;
  first_name: string;
  last_name: string;
  username: string | null;
  gender: string;
  picture: string | null;
  twofa_key: string | null;
  token_valid: boolean;
  useragent: string;
  notes: string | null;
  tags: Tag[];
  user_id: string;
  system_user_name: string;
}

export interface FbAdAccount {
  id: string;
  created_at: string;
  updated_at: string;
  source_id: string;
  adtrust_dsl: string | null;
  account_status: string;
  age: number;
  total_spent: string;
  balance: string;
  amount_spent: string;
  spend_cap: string;
  threshold_amount: string;
  created_time: string;
  currency: string;
  disable_reason: string;
  name: string;
  is_original: string | null; // 或 `boolean` 取决于实际数据
  is_prepay_account: boolean;
  timezone_name: string;
  enable_rule: boolean;
  bms: BmModel[];
  fb_accounts: FbAccount[];
  pixels: Pixel[];
  fb_business_users: any[]; // 假设 fb_business_users 的结构未定义
  bm_system_users: BMSystemUser[];
  notes: string | null;
  is_archived: boolean;
  tags: Tag[];
  users: any[]; // 假设 users 的结构未定义
}

export interface FbPageUser {
  source_id: string;
  fb_account_id: string;
  name: string;
  role_human: string | null;
  tasks: any | null; // 根据实际情况定义具体的数据结构
}

export interface FbPage {
  id: string;
  source_id?: string;
  name?: string;
  fan_count?: number;
  promotion_eligible?: boolean;
  verification_status?: string;
  picture?: string;
  users?: FbPageUser[];
  users_count?: number;
  notes?: string | null;
  tags?: Tag[];
  created_at?: string;
  updated_at?: string;
}

export interface FormLegalContent {
  id: string;
  privacy_policy: {
    url: string;
    link_text: string;
  };
}

export interface FbPageForm {
  id: string;
  source_id?: string;
  local?: string | null; // 假设 local 可以是字符串或 null
  name?: string;
  status?: string;
  created_time?: string; // 使用 ISO 8601 格式的字符串
  thank_you_page?: any; // 根据具体需求定义类型
  privacy_policy_url?: string;
  legal_content?: FormLegalContent;
  follow_up_action_url?: string;
  leads_count?: string; // 可以根据实际情况定义为 number
  page_source_id?: string;
  page_name?: string;
  notes?: string | null; // 假设 notes 可以是字符串或 null
}

export interface Material {
  id: string;
  link: string | null; // link 可能为 null
  created_at: string; // ISO 8601 格式的日期时间字符串
  updated_at: string; // ISO 8601 格式的日期时间字符串
  name: string;
  filename: string;
  url: string;
  notes: string;
  tags: Tag[]; // tags 是一个字符串数组
}

export interface LinkModel {
  id?: string;
  link?: string;
  created_at?: string; // ISO 8601 格式的日期时间字符串
  updated_at?: string; // ISO 8601 格式的日期时间字符串
  user_id?: string;
  user_name?: string;
  notes?: string;
  tags?: Tag[]; // tags 是一个字符串数组
}

export interface Copywriting {
  id: string;
  primary_text: string;
  headline: string;
  description: string | null; // description 可能为字符串或 null
  created_at: string; // ISO 8601 格式的日期时间字符串
  updated_at: string; // ISO 8601 格式的日期时间字符串
  notes: string;
  tags: string[]; // tags 是一个字符串数组
}

interface Country {
  key: string;
  name: string;
}

interface City {
  key: string;
  name: string;
}

interface Locale {
  key: number;
}

interface Interest {
  id: string;
  name: string;
}

interface TemplateUser {
  id: string;
  name: string;
  email: string;
}

// interface ResponseData {
//   data: AdAccount[];
//   pageSize: number;
//   pageNo: number;
//   totalPage: number;
//   totalCount: number;
// }

export interface AdAccountCreateAd {
  ad_account: FbAdAccount;
  operator: FbAccount | BMSystemUser;
  operator_type: string;
  page_source_id: string;
  form: FbPageForm;
  pixels: Pixel[];
  ad_setup: string;
  materials: Material[];
  links: LinkModel[];
  copywriting: Copywriting[];
  post: string;

  ad_account_id: string;
}

export interface AdCreationPayload {
  ad_account: FbAdAccount;
  operator: FbAccount | BMSystemUser;
  operator_type: AdsOperatorType;
  page: FbPage;
  page_form: FbPageForm;
  pixel: Pixel;
  ad_setup: string;
  materials: Material[];
  links: LinkModel[];
  copywriting: Copywriting[];
  post: string;
  template: FbAdTemplate;
}

export interface FormForCreateAds {
  ad_account: FbAdAccount;
  operator_type: string;
  operator: FbAccount | BMSystemUser;
  page: FbPage;
  page_form: FbPageForm | null;
  pixel: Pixel;
  ad_setup: string;
  materials: Material[] | [];
  links: LinkModel[] | [];
  copywriting: Copywriting[];
  post: string;
}

export interface FbAdTemplate {
  id: string;
  name?: string;
  created_at?: string; // ISO 8601 日期字符串
  updated_at?: string; // ISO 8601 日期字符串
  campaign_name?: string;
  adset_name?: string;
  ad_name?: string;
  bid_strategy?: string;
  bid_amount?: number | null; // 可以是数字或 null
  budget_level?: string;
  budget_type?: string;
  budget?: string; // 如果预算总是字符串形式
  objective?: string;
  accelerated?: string; // 字符串形式（可能是 '0' 或 '1'）
  conversion_location?: string;
  optimization_goal?: string;
  pixel_event?: string;
  advantage_plus_audience?: any | null; // 根据需要可以更具体
  genders?: number;
  age_min?: number;
  age_max?: number;
  countries_included?: Country[] | null;
  countries_excluded?: Country[] | null; // 如果返回可能为 null
  regions_included?: any[] | null; // 如果有特定结构，可以更具体
  regions_excluded?: any[] | null; // 如果有特定结构，可以更具体
  cities_included?: City[] | null;
  cities_excluded?: City[] | null; // 如果返回可能为 null
  locales?: Locale[];
  interests?: Interest[];
  publisher_platforms?: string[];
  device_platforms?: string[] | null; // 如果返回可能为 null
  wireless_carrier?: any | null; // 根据需要可以更具体
  call_to_action?: string;
  url_params?: any | null; // 根据需要可以更具体
  notes?: any | null; // 根据需要可以更具体
  user?: TemplateUser;
}

export interface CopywritingModel {
  id?: string;
  primary_text?: string;
  headline?: string;
  description?: string;
  notes?: string;
}

export interface ShareModel {
  action?: string;
  emailList?: string[];
  resourceList?: string[];
}

export interface FraudConfigModel {
  id?: string;
  value?: string[];
  type?: string;
  active?: boolean | string;
  actions?: string[];
  excluded_ads?: string[] | null;
  valueText?: string; // 临时字段，用于textarea输入
  excludedAdsText?: string; // 临时字段，用于excluded_ads的textarea输入
}

export interface FbApiTokenModel {
  id?: string;
  active?: string;
  ad_accounts?: FbAccount[];
  bm?: BmModel[];
  bm_id?: string;
  created_at?: string;
  name?: string;
  notes?: string;
  pages?: FbPage[];
  token?: string;
  token_type?: number;
  app?: string;
}

export interface PostInfoModel {
  url?: string;
  url_tags?: string;
  primary_text?: string;
  headline?: string;
  description?: string;
  // 多语言相关字段
  isMultiLanguage?: boolean;
  languages?: LanguageInfoModel[];
}

export interface LanguageInfoModel {
  languageCode: string;
  languageName: string;
  nativeName: string;
  primary_text: string;
  headline: string;
  description: string;
  url: string;
  url_tags: string;
  isDefault?: boolean;
}

export interface ApiLanguageItem {
  english_name: string;
  native_name: string;
  label_name: string;
  locales: number[];
}

export interface MaterialEditModel {
  id: string;
  name: string;
  notes: string;
}

export interface Tag {
  id: string;
  name: string;
  created_at: string;
  user_id: string;
  user_name: string;
}

export interface ProductListModel {
  id: string;
  currency: string;
  name: string;
  source_id: string;
  description: string;
  url: string;
  image_url: string;
  retailer_id: string;
  price: string;
  video_url?: string;
  tags: Array<{ name: string }>;
}

export interface ProductListCreateModdel {
  name: string;
  description: string;
  url: string;
  image_url: string;
  currency: string;
  price: number; // 价格是处理后的整数（分）
}

// product set
// 作为 prop 接收的 Product 数据结构
export interface Product {
  source_id: string; // 对应 filter 中的 product_item_id
  retailer_id: string;
  tags: Array<{ name: string }>; // 用于显示标签信息
  // 如果需要，可以添加其他用于显示或逻辑的字段
}

// 组件内部，用于表示单个商品组表单行的状态结构
export interface ProductSetFormState {
  id: number; // 用于 v-for 的唯一 key
  name: string; // 商品组名称
  filter_object: 'product_item_id' | 'retailer_id'; // 过滤对象类型
  filter_condition: 'eq' | 'is_any'; // 过滤条件类型
  filter_value: string | string[] | undefined; // 选中的值 (单选为字符串，多选为数组，未选为 undefined)
}

// 组件最终处理后，通过 submit 事件发出的数据结构
export interface ProcessedProductSet {
  name: string;
  filter: {
    // 外部 key (过滤对象) 可能是 product_item_id 或 retailer_id
    [key in 'product_item_id' | 'retailer_id']?: {
      // 内部 key (条件) 可能是 eq 或 is_any
      [key in 'eq' | 'is_any']?: string | string[]; // 值可能是字符串或字符串数组
    };
  };
}

// 用于 Select 组件的选项结构
export interface SelectOption {
  value: string; // 选项的值
  label: string; // 选项显示的文本
}
