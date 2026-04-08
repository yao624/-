import type { FormItem } from '@/components/dynamic-form/types';

export enum Objective {
  OUTCOME_SALES = 'OUTCOME_SALES',
  OUTCOME_LEADS = 'OUTCOME_LEADS',
}

export enum BidStrategy {
  LOWEST_COST_WITHOUT_CAP = 'LOWEST_COST_WITHOUT_CAP',
  LOWEST_COST_WITH_BID_CAP = 'LOWEST_COST_WITH_BID_CAP',
  COST_CAP = 'COST_CAP',
}

export enum ConversionLocation {
  WEBSITE = 'WEBSITE',
  INSTANT_FORMS = 'INSTANT_FORMS',
}

export enum OptimizationGoal {
  OFFSITE_CONVERSIONS = 'OFFSITE_CONVERSIONS',
  LEAD_GENERATION = 'LEAD_GENERATION',
  QUALITY_LEAD = 'QUALITY_LEAD',
}

const requiredRule = { required: true, message: 'Required' };

export const compaignForm: FormItem[] = [
  { label: 'Template Name', field: 'name', rules: [requiredRule] },
  { label: 'Campaign Name', field: 'campaign_name', rules: [requiredRule] },
  {
    label: 'Objective',
    field: 'objective',
    options: [
      { label: 'Sales', value: Objective.OUTCOME_SALES },
      { label: 'Leads', value: Objective.OUTCOME_LEADS },
    ],
    value: Objective.OUTCOME_SALES,
  },
  {
    label: 'Bid Strategy',
    field: 'bid_strategy',
    options: [
      { label: 'Highest volume', value: BidStrategy.LOWEST_COST_WITHOUT_CAP },
      { label: 'Bid Cap', value: BidStrategy.LOWEST_COST_WITH_BID_CAP },
      { label: 'Cost per result goal', value: BidStrategy.COST_CAP },
    ],
    value: BidStrategy.LOWEST_COST_WITHOUT_CAP,
  },
  { label: 'Bid Amount', field: 'bid_amount' },
  { label: 'Accelerated', field: 'accelerated', isBoolean: true, value: false },
  { label: 'Budget', field: 'budget' },
  {
    label: 'Budget Type',
    field: 'budget_type',
    options: ['daily', 'lifetime'],
    value: 'daily',
    mode: 'radio',
  },
  {
    label: 'Budget Level',
    field: 'budget_level',
    options: ['campaign', 'adset'],
    value: 'campaign',
    mode: 'radio',
  },
];

export const adsetsForm: FormItem[] = [
  { label: 'Adset Name', field: 'adset_name', rules: [requiredRule] },
  {
    label: 'Conversion Location',
    field: 'conversion_location',
    options: [
      { label: 'Website', value: ConversionLocation.WEBSITE },
      { label: 'Instant forms', value: ConversionLocation.INSTANT_FORMS },
    ],
    mode: 'radio',
  },
  {
    label: 'Optimization Goal',
    field: 'optimization_goal',
    options: [
      { label: 'Maximize number of conversions', value: OptimizationGoal.OFFSITE_CONVERSIONS },
      { label: 'Maximize number of leads', value: OptimizationGoal.LEAD_GENERATION },
      { label: 'Maximize number of conversion leads', value: OptimizationGoal.QUALITY_LEAD },
    ],
    mode: 'radio',
  },
  { label: 'Pixel Event', field: 'pixel_event', options: [], mode: 'select' },
  {
    label: 'Advantage+ audience',
    field: 'advantage_plus_audience',
    isBoolean: true,
    checkBoxLabel: 'Activate',
  },
  {
    label: 'Gender',
    field: 'genders',
    options: [
      { label: 'All', value: 0 },
      { label: 'Male', value: 1 },
      { label: 'Female', value: 2 },
    ],
    mode: 'radio',
    value: 0,
  },
  {
    label: 'Age min',
    field: 'age_min',
    options: [
      ...Array.from({ length: 47 })
        .map((_, i) => i + 18)
        .map(age => ({ label: age, value: age })),
      { label: '65+', value: 65 },
    ],
    value: 18,
  },
  {
    label: 'Age max',
    field: 'age_max',
    options: [
      ...Array.from({ length: 47 })
        .map((_, i) => i + 18)
        .map(age => ({ label: age, value: age })),
      { label: '65+', value: 65 },
    ],
    value: 65,
    isOptionDisabled: (_, option, form) => option.value <= form.age_min,
  },
  // { label: 'Engaged views (video only)', field: 'engagedViews', options: ['1 day', 'Missing'], mode: 'select' },
  { label: 'Location', field: 'location' },

  // placeholder, 不显示
  { label: 'countries', field: 'countries' },
  { label: 'countries_excluded', field: 'countries_excluded' },
  { label: 'regions', field: 'regions' },
  { label: 'regions_excluded', field: 'regions_excluded' },
  { label: 'cities', field: 'cities' },
  { label: 'cities_excluded', field: 'cities_excluded' },

  { label: 'Languages', field: 'locales' },
  { label: 'Interests', field: 'interests' },
  { label: 'Interests', field: 'interests_excluded' },
  {
    label: 'Platforms',
    field: 'publisher_platforms',
    options: [
      { label: 'Facebook', value: 'facebook' },
      { label: 'Instagram', value: 'instagram' },
      { label: 'Audience Network', value: 'audience network' },
      { label: 'Messenger', value: 'Messenger' },
    ],
    multiple: true,
    rules: [
      requiredRule,
      {
        validator: (_, value, callback) => {
          if (Array.isArray(value) && value.length === 1 && value[0] === 'audience network') {
            callback(new Error('Audience Network cannot be selected individually'));
          } else {
            callback(); // 验证成功，调用 callback
          }
        },
        trigger: 'change',
        message: 'Audience Network cannot be selected individually',
      },
    ],
  },
  {
    label: 'Devices',
    field: 'device_platforms',
    options: [
      { label: 'All', value: '' },
      { label: 'Desktop', value: 'desktop' },
      { label: 'Mobile', value: 'mobile' },
    ],
    mode: 'radio',
    value: '',
  },
  {
    label: 'Wireless Carrier',
    field: 'wireless_carrier',
    isBoolean: true,
    checkBoxLabel: 'Only when connected to Wifi',
  },
];

export const adsForm: FormItem[] = [
  { label: 'Ad name', field: 'ad_name', rules: [requiredRule] },
  {
    label: 'Call to action',
    field: 'call_to_action',
    options: [
      { label: 'Learn more', value: 'LEARN_MORE' },
      { label: 'Sign up', value: 'SIGN_UP' },
      { label: 'Apply now', value: 'APPLY_NOW' },
      { label: 'Get quote', value: 'GET_QUOTE' },
      { label: 'Subscribe', value: 'SUBSCRIBE' },
    ],
    mode: 'select',
    value: 'LEARN_MORE',
    rules: [requiredRule],
  },
  { label: 'URL Params', field: 'url_params' },
  // { label: 'Headline', field: 'headline_text' },
  // { label: 'Primary text', field: 'primary_text', text: true },
  // { label: 'Description', field: 'description_text', text: true },
  // { label: 'Call to action', field: 'callToAction', options: ['Download', 'Get offer', 'Get quota'], mode: 'select' },
];
