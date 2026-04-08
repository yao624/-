// 看板模拟数据

import dayjs from 'dayjs';

// KPI数据
export async function getKpiData() {
  await new Promise((resolve) => setTimeout(resolve, 300));
  return {
    todaySpend: 123456.78,
    yesterdaySpend: 98765.43,
    last7DaysSpend: 765432.10,
    last14DaysSpend: 1543210.20,
  };
}

// 花费趋势数据
export async function getSpendTrendData() {
  await new Promise((resolve) => setTimeout(resolve, 300));
  const dates = [];
  for (let i = 6; i >= 0; i--) {
    dates.push(dayjs().subtract(i, 'day').format('YYYY-MM-DD'));
  }
  
  return dates.map(date => ({
    date,
    spend: Math.floor(Math.random() * 50000) + 30000,
  }));
}

// 渠道分布数据
export async function getChannelDistributionData() {
  await new Promise((resolve) => setTimeout(resolve, 300));
  return [
    { name: 'Meta', value: 400000, color: '#1890ff' },
    { name: 'Google', value: 300000, color: '#52c41a' },
    { name: 'TikTok', value: 200000, color: '#fa8c16' },
    { name: 'Kwai', value: 100000, color: '#722ed1' },
  ];
}

// 商店分布数据
export async function getStoreDistributionData(params?: any) {
  await new Promise((resolve) => setTimeout(resolve, 300));
  const page = params?.page || 1;
  const pageSize = params?.pageSize || 20;
  
  const allData = Array.from({ length: 50 }, (_, i) => ({
    id: `store_${i + 1}`,
    storeName: `Tiktok Shop名称_${i + 1}`,
    storeId: `shop_${i + 1}`,
    spend: Math.floor(Math.random() * 100000) + 10000,
    impressions: Math.floor(Math.random() * 10000000) + 1000000,
    cpm: Math.random() * 20 + 5,
    clicks: Math.floor(Math.random() * 100000) + 10000,
    ctr: Math.random() * 0.05 + 0.01,
    conversions: Math.floor(Math.random() * 1000) + 100,
    conversionCost: Math.random() * 50 + 10,
    conversionRate: Math.random() * 0.1 + 0.01,
  }));
  
  const start = (page - 1) * pageSize;
  const end = start + pageSize;
  const data = allData.slice(start, end);
  
  return {
    data,
    total: allData.length,
  };
}

// 地区分布数据
export async function getRegionDistributionData() {
  await new Promise((resolve) => setTimeout(resolve, 300));
  return [
    { name: '美国', spend: 200000 },
    { name: '英国', spend: 150000 },
    { name: '加拿大', spend: 100000 },
    { name: '澳大利亚', spend: 80000 },
    { name: '德国', spend: 70000 },
    { name: '法国', spend: 60000 },
    { name: '日本', spend: 50000 },
    { name: '韩国', spend: 40000 },
  ];
}

// 优化师分布数据
export async function getOptimizerDistributionData() {
  await new Promise((resolve) => setTimeout(resolve, 300));
  return [
    { name: '优化师_1', spend: 180000 },
    { name: '优化师_2', spend: 150000 },
    { name: '优化师_3', spend: 120000 },
    { name: '优化师_4', spend: 100000 },
    { name: '优化师_5', spend: 80000 },
    { name: '优化师_6', spend: 60000 },
    { name: '优化师_7', spend: 50000 },
    { name: '优化师_8', spend: 40000 },
  ];
}

// 优质素材数据
export async function getQualityMaterialsData() {
  await new Promise((resolve) => setTimeout(resolve, 300));
  return Array.from({ length: 20 }, (_, i) => ({
    id: `material_${i + 1}`,
    name: `素材_${i + 1}`,
    thumbnail: `https://via.placeholder.com/200x200?text=Material+${i + 1}`,
    spend: Math.floor(Math.random() * 50000) + 10000,
    impressions: Math.floor(Math.random() * 5000000) + 1000000,
    clicks: Math.floor(Math.random() * 50000) + 10000,
    ctr: Math.random() * 0.05 + 0.01,
    conversions: Math.floor(Math.random() * 500) + 100,
    conversionRate: Math.random() * 0.1 + 0.01,
  }));
}

// 素材表格数据
export async function getMaterialsTableData(params?: any) {
  await new Promise((resolve) => setTimeout(resolve, 300));
  const page = params?.page || 1;
  const pageSize = params?.pageSize || 20;
  
  const allData = Array.from({ length: 100 }, (_, i) => ({
    id: `material_table_${i + 1}`,
    materialName: `素材名称_${i + 1}`,
    md5: `md5_${i + 1}`,
    spend: Math.floor(Math.random() * 50000) + 10000,
    impressions: Math.floor(Math.random() * 5000000) + 1000000,
    cpm: Math.random() * 20 + 5,
    clicks: Math.floor(Math.random() * 50000) + 10000,
    ctr: Math.random() * 0.05 + 0.01,
    conversions: Math.floor(Math.random() * 500) + 100,
    conversionRate: Math.random() * 0.1 + 0.01,
  }));
  
  const start = (page - 1) * pageSize;
  const end = start + pageSize;
  const data = allData.slice(start, end);
  
  return {
    data,
    total: allData.length,
  };
}

// TOP 广告账户数据
export async function getTopAdAccountsData(params?: any) {
  await new Promise((resolve) => setTimeout(resolve, 300));
  const page = params?.page || 1;
  const pageSize = params?.pageSize || 20;
  
  const allData = Array.from({ length: 50 }, (_, i) => ({
    id: `account_${i + 1}`,
    date: dayjs().subtract(i % 7, 'day').format('YYYY-MM-DD'),
    accountName: `广告账户名称_${i + 1}`,
    accountId: `account_${i + 1}`,
    channel: ['Meta', 'Google', 'TikTok'][i % 3],
    spend: Math.floor(Math.random() * 100000) + 20000,
    impressions: Math.floor(Math.random() * 10000000) + 2000000,
    cpm: Math.random() * 20 + 5,
    clicks: Math.floor(Math.random() * 100000) + 20000,
    cpc: Math.random() * 2 + 0.5,
    ctr: Math.random() * 0.05 + 0.01,
  }));
  
  const start = (page - 1) * pageSize;
  const end = start + pageSize;
  const data = allData.slice(start, end);
  
  return {
    data,
    total: allData.length,
  };
}

// TOP 广告账户趋势数据
export async function getTopAdAccountsTrendData() {
  await new Promise((resolve) => setTimeout(resolve, 300));
  const dates = [];
  for (let i = 6; i >= 0; i--) {
    dates.push(dayjs().subtract(i, 'day').format('YYYY-MM-DD'));
  }
  
  return {
    dates,
    accounts: Array.from({ length: 8 }, (_, i) => ({
      name: `广告账户名称_${i + 1}`,
      data: dates.map(() => Math.floor(Math.random() * 50000) + 20000),
    })),
  };
}

// TOP 素材数据
export async function getTopMaterialsData(params?: any) {
  await new Promise((resolve) => setTimeout(resolve, 300));
  const page = params?.page || 1;
  const pageSize = params?.pageSize || 20;
  
  const allData = Array.from({ length: 50 }, (_, i) => ({
    id: `top_material_${i + 1}`,
    materialName: `素材名称_${i + 1}`,
    md5: `md5_${i + 1}`,
    spend: Math.floor(Math.random() * 50000) + 10000,
    impressions: Math.floor(Math.random() * 5000000) + 1000000,
    cpm: Math.random() * 20 + 5,
    clicks: Math.floor(Math.random() * 50000) + 10000,
    ctr: Math.random() * 0.05 + 0.01,
    conversions: Math.floor(Math.random() * 500) + 100,
    conversionRate: Math.random() * 0.1 + 0.01,
  }));
  
  const start = (page - 1) * pageSize;
  const end = start + pageSize;
  const data = allData.slice(start, end);
  
  return {
    data,
    total: allData.length,
  };
}

// TOP 素材缩略图数据
export async function getTopMaterialsThumbnails() {
  await new Promise((resolve) => setTimeout(resolve, 300));
  return Array.from({ length: 20 }, (_, i) => ({
    id: `thumbnail_${i + 1}`,
    thumbnail: `https://via.placeholder.com/200x200?text=Material+${i + 1}`,
    spend: Math.floor(Math.random() * 50000) + 10000,
  }));
}

// 汇总数据
export async function getSummaryData(params?: any) {
  await new Promise((resolve) => setTimeout(resolve, 300));
  const page = params?.page || 1;
  const pageSize = params?.pageSize || 20;
  const tab = params?.tab || 'product';

  let allData: any[] = [];

  if (tab === 'product') {
    allData = [
      {
        id: 'summary',
        product: '汇总',
        spend: 1234567.89,
        impressions: 1234567890,
        cpm: 10.0,
        clicks: 12345678,
        cpc: 0.1,
        ctr: 0.01,
        conversions: 123456,
        costPerConversion: 10.0,
      },
      ...Array.from({ length: 30 }, (_, i) => ({
        id: `product_${i + 1}`,
        product: `产品名称_${i + 1}`,
        spend: Math.floor(Math.random() * 500000) + 100000,
        impressions: Math.floor(Math.random() * 500000000) + 100000000,
        cpm: Math.random() * 20 + 5,
        clicks: Math.floor(Math.random() * 5000000) + 1000000,
        cpc: Math.random() * 2 + 0.1,
        ctr: Math.random() * 0.05 + 0.01,
        conversions: Math.floor(Math.random() * 50000) + 10000,
        costPerConversion: Math.random() * 20 + 5,
      })),
    ];
  } else if (tab === 'optimizer') {
    allData = [
      {
        id: 'summary',
        optimizer: '汇总',
        spend: 1234567.89,
        impressions: 1234567890,
        cpm: 10.0,
        clicks: 12345678,
        cpc: 0.1,
        ctr: 0.01,
        conversions: 123456,
        costPerConversion: 10.0,
      },
      ...Array.from({ length: 30 }, (_, i) => ({
        id: `optimizer_${i + 1}`,
        optimizer: `优化师_${i + 1}`,
        spend: Math.floor(Math.random() * 500000) + 100000,
        impressions: Math.floor(Math.random() * 500000000) + 100000000,
        cpm: Math.random() * 20 + 5,
        clicks: Math.floor(Math.random() * 5000000) + 1000000,
        cpc: Math.random() * 2 + 0.1,
        ctr: Math.random() * 0.05 + 0.01,
        conversions: Math.floor(Math.random() * 50000) + 10000,
        costPerConversion: Math.random() * 20 + 5,
      })),
    ];
  } else if (tab === 'ad-account') {
    allData = [
      {
        id: 'summary',
        accountName: '汇总',
        accountId: '-',
        spend: 1234567.89,
        impressions: 1234567890,
        cpm: 10.0,
        clicks: 12345678,
        cpc: 0.1,
        ctr: 0.01,
        conversions: 123456,
        costPerConversion: 10.0,
      },
      ...Array.from({ length: 30 }, (_, i) => ({
        id: `account_${i + 1}`,
        accountName: `广告账户名称_${i + 1}`,
        accountId: `account_${i + 1}`,
        spend: Math.floor(Math.random() * 500000) + 100000,
        impressions: Math.floor(Math.random() * 500000000) + 100000000,
        cpm: Math.random() * 20 + 5,
        clicks: Math.floor(Math.random() * 5000000) + 1000000,
        cpc: Math.random() * 2 + 0.1,
        ctr: Math.random() * 0.05 + 0.01,
        conversions: Math.floor(Math.random() * 50000) + 10000,
        costPerConversion: Math.random() * 20 + 5,
      })),
    ];
  } else if (tab === 'campaign') {
    allData = [
      {
        id: 'summary',
        campaign: '汇总',
        spend: 1234567.89,
        impressions: 1234567890,
        cpm: 10.0,
        clicks: 12345678,
        cpc: 0.1,
        ctr: 0.01,
        conversions: 123456,
        costPerConversion: 10.0,
      },
      ...Array.from({ length: 30 }, (_, i) => ({
        id: `campaign_${i + 1}`,
        campaign: `广告系列_${i + 1}`,
        spend: Math.floor(Math.random() * 500000) + 100000,
        impressions: Math.floor(Math.random() * 500000000) + 100000000,
        cpm: Math.random() * 20 + 5,
        clicks: Math.floor(Math.random() * 5000000) + 1000000,
        cpc: Math.random() * 2 + 0.1,
        ctr: Math.random() * 0.05 + 0.01,
        conversions: Math.floor(Math.random() * 50000) + 10000,
        costPerConversion: Math.random() * 20 + 5,
      })),
    ];
  } else if (tab === 'ad-group') {
    allData = [
      {
        id: 'summary',
        adGroup: '汇总',
        spend: 1234567.89,
        impressions: 1234567890,
        cpm: 10.0,
        clicks: 12345678,
        cpc: 0.1,
        ctr: 0.01,
        conversions: 123456,
        costPerConversion: 10.0,
      },
      ...Array.from({ length: 30 }, (_, i) => ({
        id: `adgroup_${i + 1}`,
        adGroup: `广告组_${i + 1}`,
        spend: Math.floor(Math.random() * 500000) + 100000,
        impressions: Math.floor(Math.random() * 500000000) + 100000000,
        cpm: Math.random() * 20 + 5,
        clicks: Math.floor(Math.random() * 5000000) + 1000000,
        cpc: Math.random() * 2 + 0.1,
        ctr: Math.random() * 0.05 + 0.01,
        conversions: Math.floor(Math.random() * 50000) + 10000,
        costPerConversion: Math.random() * 20 + 5,
      })),
    ];
  } else if (tab === 'material') {
    allData = [
      {
        id: 'summary',
        material: '汇总',
        spend: 1234567.89,
        impressions: 1234567890,
        cpm: 10.0,
        clicks: 12345678,
        cpc: 0.1,
        ctr: 0.01,
        conversions: 123456,
        costPerConversion: 10.0,
      },
      ...Array.from({ length: 30 }, (_, i) => ({
        id: `material_${i + 1}`,
        material: `素材_${i + 1}`,
        spend: Math.floor(Math.random() * 500000) + 100000,
        impressions: Math.floor(Math.random() * 500000000) + 100000000,
        cpm: Math.random() * 20 + 5,
        clicks: Math.floor(Math.random() * 5000000) + 1000000,
        cpc: Math.random() * 2 + 0.1,
        ctr: Math.random() * 0.05 + 0.01,
        conversions: Math.floor(Math.random() * 50000) + 10000,
        costPerConversion: Math.random() * 20 + 5,
      })),
    ];
  }

  const start = (page - 1) * pageSize;
  const end = start + pageSize;
  const data = allData.slice(start, end);

  return {
    data,
    total: allData.length,
  };
}

// 数据预览图表数据
export async function getPreviewChartData(params?: any) {
  await new Promise((resolve) => setTimeout(resolve, 200));
  const metric1 = params?.metric1 || 'spend';
  const metric2 = params?.metric2;
  const topN = params?.topN || 10;
  const chartType = params?.chartType || 'line';
  
  // 生成示例数据
  const categories = ['产品_1', '产品_2', '产品_3', '产品_4', '产品_5', '产品_6', '产品_7', '产品_8', '产品_9', '产品_10'].slice(0, topN);
  
  const metric1Data = categories.map((_, index) => {
    const baseValue = [1234567, 987654, 765432, 654321, 543210, 432109, 321098, 210987, 109876, 98765][index] || 50000;
    if (metric1 === 'spend') return baseValue;
    if (metric1 === 'impressions') return baseValue * 1000;
    if (metric1 === 'cpm') return baseValue / 100;
    if (metric1 === 'clicks') return baseValue * 100;
    if (metric1 === 'cpc') return baseValue / 1000;
    if (metric1 === 'ctr') return baseValue / 100000;
    if (metric1 === 'conversions') return baseValue / 10;
    if (metric1 === 'cpa') return baseValue / 50;
    if (metric1 === 'conversionRate') return baseValue / 1000000;
    if (metric1 === 'downloads') return baseValue / 5;
    return baseValue;
  });
  
  const metric2Data = metric2 ? categories.map((_, index) => {
    const baseValue = [987654, 876543, 765432, 654321, 543210, 432109, 321098, 210987, 109876, 98765][index] || 40000;
    if (metric2 === 'spend') return baseValue;
    if (metric2 === 'impressions') return baseValue * 1000;
    if (metric2 === 'cpm') return baseValue / 100;
    if (metric2 === 'clicks') return baseValue * 100;
    if (metric2 === 'cpc') return baseValue / 1000;
    if (metric2 === 'ctr') return baseValue / 100000;
    if (metric2 === 'conversions') return baseValue / 10;
    if (metric2 === 'cpa') return baseValue / 50;
    if (metric2 === 'conversionRate') return baseValue / 1000000;
    if (metric2 === 'downloads') return baseValue / 5;
    return baseValue;
  }) : null;
  
  const metricNames: Record<string, string> = {
    spend: '花费',
    impressions: '展示数',
    cpm: '千次展示成本',
    clicks: '点击数',
    cpc: '点击成本',
    ctr: '点击率',
    conversions: '转化数',
    cpa: '转化成本',
    conversionRate: '转化率',
    downloads: '下载数',
  };
  
  const series: any[] = [
    {
      name: metricNames[metric1] || metric1,
      type: chartType === 'bar' ? 'bar' : 'line',
      data: metric1Data,
      itemStyle: {
        color: '#1890ff',
      },
    },
  ];
  
  if (metric2 && metric2Data) {
    series.push({
      name: metricNames[metric2] || metric2,
      type: chartType === 'bar' ? 'bar' : 'line',
      data: metric2Data,
      itemStyle: {
        color: '#52c41a',
      },
    });
  }
  
  return {
    categories,
    legend: series.map(s => s.name),
    yAxisName: metricNames[metric1] || metric1,
    series,
  };
}
