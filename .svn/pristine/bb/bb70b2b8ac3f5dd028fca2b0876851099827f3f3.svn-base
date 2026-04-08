// 推广报表模拟数据

export async function getOptimizerReportData(_params: any) {
  await new Promise((resolve) => setTimeout(resolve, 300));
  const data = [
    {
      id: '1',
      optimizer: '优化师A',
      spend: 1250.5,
      impressions: 125000,
      cpm: 10.0,
      clicks: 1250,
      cpc: 1.0,
      ctr: 1.0,
      conversions: 125,
      conversionCost: 10.0,
      adGroupsCreated: 10,
      adsCreated: 50,
      revenue: 5000.0,
    },
    {
      id: '2',
      optimizer: '优化师B',
      spend: 2500.0,
      impressions: 250000,
      cpm: 10.0,
      clicks: 2500,
      cpc: 1.0,
      ctr: 1.0,
      conversions: 250,
      conversionCost: 10.0,
      adGroupsCreated: 20,
      adsCreated: 100,
      revenue: 10000.0,
    },
  ];
  return {
    data,
    total: data.length,
    top10: data.slice(0, 10).map((item) => ({
      name: item.optimizer,
      value: item.spend,
    })),
  };
}

export async function getAdAccountReportData(params: any) {
  await new Promise((resolve) => setTimeout(resolve, 300));
  const platform = params?.platform || 'meta';
  const currency = platform === 'meta' ? 'USD' : 'CNY';
  
  const allData = [
    {
      id: 'summary',
      accountName: '汇总',
      accountId: '-',
      operation: '-',
      authorizationStatus: '-',
      optimizer: '-',
      lastUpdateTime: '-',
      spend: 0,
      impressions: 0,
      cpm: null,
      cpc: null,
      ctr: null,
      currency,
    },
    {
      id: '1',
      accountName: 'Mingg Wengxing',
      accountId: '464423115976839',
      authorizationStatus: '已授权',
      optimizer: 'MediaPulse Group Limited',
      lastUpdateTime: '-',
      spend: 0,
      impressions: 0,
      cpm: null,
      cpc: null,
      ctr: null,
      currency: platform === 'meta' ? 'CNY' : currency,
    },
  ];
  
  return {
    data: allData,
    total: allData.length,
  };
}

export async function getCampaignReportData(_params: any) {
  await new Promise((resolve) => setTimeout(resolve, 300));
  return {
    data: [],
    total: 0,
  };
}

export async function getAdGroupReportData(_params: any) {
  await new Promise((resolve) => setTimeout(resolve, 300));
  return {
    data: [],
    total: 0,
  };
}

export async function getMaterialReportData(params: any) {
  await new Promise((resolve) => setTimeout(resolve, 300));
  const platform = params?.platform || 'meta';
  const subTab = params?.subTab || 'material';
  const currency = platform === 'meta' ? 'USD' : 'CNY';
  
  if (subTab === 'material') {
    return {
      data: [],
      total: 0,
    };
  }
  
  if (subTab === 'designer') {
    return {
      data: [],
      total: 0,
    };
  }
  
  if (subTab === 'tag') {
    return {
      data: [],
      total: 0,
    };
  }
  
  if (subTab === 'creator') {
    // 创意人报表数据
    const data = Array.from({ length: 100000 }, (_, index) => ({
      id: `creator-${index + 1}`,
      creator: `创意人${index + 1}`,
      spend: Math.random() * 10000,
      impressions: Math.floor(Math.random() * 1000000),
      cpm: Math.random() * 10,
      clicks: Math.floor(Math.random() * 10000),
      cpc: Math.random() * 2,
      ctr: Math.random() * 0.1,
      clickInstallRate: Math.random() * 0.05,
      mobileAppPurchases: Math.floor(Math.random() * 100),
      currency,
    }));
    
    const page = params?.page || 1;
    const pageSize = params?.pageSize || 20;
    const start = (page - 1) * pageSize;
    const end = start + pageSize;
    
    return {
      data: data.slice(start, end),
      total: data.length,
    };
  }
  
  return {
    data: [],
    total: 0,
  };
}

export async function getDepartments() {
  await new Promise((resolve) => setTimeout(resolve, 200));
  return [
    { id: '1', name: '部门A' },
    { id: '2', name: '部门B' },
  ];
}

export async function getOptimizers() {
  await new Promise((resolve) => setTimeout(resolve, 200));
  return [
    { id: '1', name: '优化师A' },
    { id: '2', name: '优化师B' },
  ];
}

export async function getAccounts() {
  await new Promise((resolve) => setTimeout(resolve, 200));
  return [
    { id: '1', name: 'Mingg Wengxing' },
    { id: '2', name: '轴承' },
    { id: '3', name: '账户C' },
  ];
}

export async function getProducts() {
  await new Promise((resolve) => setTimeout(resolve, 200));
  return [
    { id: '1', name: '产品A' },
    { id: '2', name: '产品B' },
    { id: '3', name: '产品C' },
  ];
}

export async function getAdReportData(_params: any) {
  await new Promise((resolve) => setTimeout(resolve, 300));
  return {
    data: [],
    total: 0,
  };
}

