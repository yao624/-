import request from '@/utils/request';

export interface DateRange {
  date_start: string;
  date_stop: string;
}

export interface OverviewData {
  spend: number;
  link_clicks: number;
  offer_clicks: number;
  leads: number;
  sales: number;
  revenue: number;
  profit: number;
  roi: number;
  date_start: string;
  date_stop: string;
  ad_accounts_count?: number;
  accounts_with_spend?: number;
}

export interface UserOverviewData extends OverviewData {
  label: string;
  user_id?: string;
  user_email?: string;
}

export interface CampaignTagData extends OverviewData {
  tag_id: string;
  tag_name: string;
  campaigns_count: number;
}

export interface OfferData extends OverviewData {
  offer_name: string;
  offer_source_id: string;
  campaigns_count: number;
  conversions_count: number;
}

export interface AdAccountData extends OverviewData {
  account_id: string;
  account_name: string;
  account_status: string;
  currency: string;
  default_funding: string;
  users: any[];
}

export interface CampaignData extends OverviewData {
  campaign_id: string;
  campaign_name: string;
  campaign_status: string;
  effective_status: string;
  daily_budget: string | null;
  lifetime_budget: string | null;
  ad_account_id: string;
  ad_account_name: string;
  account_status: string;
  currency: string;
  default_funding: string;
  users: any[];
}

export interface AdsetData extends OverviewData {
  adset_id: string;
  adset_name: string;
  adset_status: string;
  effective_status: string;
  daily_budget: string | null;
  lifetime_budget: string | null;
  campaign_id: string;
  campaign_name: string;
  ad_account_id: string;
  ad_account_name: string;
  account_status: string;
  currency: string;
  default_funding: string;
  users: any[];
}

export interface TrendData {
  date: string;
  spend: number;
  link_clicks: number;
  offer_clicks: number;
  leads: number;
  sales: number;
  revenue: number;
  profit: number;
  roi: number;
  ad_accounts_count: number;
  accounts_with_spend: number;
}

export interface UserTrendData {
  date: string;
  users: Array<{
    spend: number;
    link_clicks: number;
    offer_clicks: number;
    leads: number;
    sales: number;
    revenue: number;
    profit: number;
    roi: number;
    ad_accounts_count: number;
    accounts_with_spend: number;
    user_id: string | null;
    user_name: string;
    user_email: string | null;
    type: string;
  }>;
}

export interface OfferTrendData {
  date: string;
  offers: OfferData[];
}

// Overview APIs
export async function getOverview(params: DateRange) {
  return request.post<DateRange, { success: boolean; data: OverviewData }>('/insights/overview', params);
}

export async function getOverviewUsers(params: DateRange) {
  return request.post<DateRange, {
    success: boolean;
    data: {
      all: UserOverviewData;
      users: UserOverviewData[];
      other: UserOverviewData;
      date_start: string;
      date_stop: string;
      total_users_count: number;
    }
  }>('/insights/overview/users', params);
}

export async function getOverviewCampaignTags(params: DateRange) {
  return request.post<DateRange, {
    success: boolean;
    data: {
      summary: OverviewData;
      tags: CampaignTagData[];
      total_tags: number;
    }
  }>('/insights/overview/campaign-tags', params);
}

export async function getOverviewOffers(params: DateRange) {
  return request.post<DateRange, {
    success: boolean;
    data: {
      summary: OverviewData;
      offers: OfferData[];
      total_offers: number;
    }
  }>('/insights/overview/offers', params);
}

export async function getOverviewAdAccounts(params: DateRange) {
  return request.post<DateRange, {
    success: boolean;
    data: {
      summary: OverviewData;
      accounts: AdAccountData[];
      total_accounts_with_spend: number;
    }
  }>('/insights/overview/ad-accounts', params);
}

export async function getOverviewCampaigns(params: DateRange) {
  return request.post<DateRange, {
    success: boolean;
    data: {
      summary: OverviewData;
      campaigns: CampaignData[];
      total_campaigns_with_spend: number;
    }
  }>('/insights/overview/campaigns', params);
}

export async function getOverviewAdsets(params: DateRange) {
  return request.post<DateRange, {
    success: boolean;
    data: {
      summary: OverviewData;
      adsets: AdsetData[];
      total_adsets_with_spend: number;
    }
  }>('/insights/overview/adsets', params);
}

// Trends APIs
export async function getTrends(params: DateRange) {
  return request.post<DateRange, {
    success: boolean;
    data: {
      summary: OverviewData;
      trends: TrendData[];
      total_days: number;
    }
  }>('/insights/trends', params);
}

export async function getTrendsUsers(params: DateRange) {
  return request.post<DateRange, {
    success: boolean;
    data: {
      summary: OverviewData;
      trends: UserTrendData[];
      total_days: number;
      total_users: number;
    }
  }>('/insights/trends/users', params);
}

export async function getTrendsOffers(params: DateRange) {
  return request.post<DateRange, {
    success: boolean;
    data: {
      summary: OverviewData;
      trends: OfferTrendData[];
      total_days: number;
    }
  }>('/insights/trends/offers', params);
}
