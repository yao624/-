// 模拟数据

export async function getAdAccounts() {
  await new Promise((resolve) => setTimeout(resolve, 200));
  return [
    { id: '1', name: 'Mingg Wengxing', sourceId: '464423115976839' },
    { id: '2', name: '轴承', sourceId: '518216481363001' },
  ];
}

export async function getFbPersonalAccounts() {
  await new Promise((resolve) => setTimeout(resolve, 200));
  return [
    { id: '1', name: 'Mingg Wengxing', fbId: '842562958777047' },
    { id: '2', name: 'Test Account', fbId: '123456789' },
  ];
}

export async function getSpecialAdCategories() {
  await new Promise((resolve) => setTimeout(resolve, 200));
  return [
    {
      value: 'credit',
      label: '金融产品和服务(旧称"信贷")',
    },
    { value: 'employment', label: '就业' },
    { value: 'housing', label: '住房' },
  ];
}

export async function getApps() {
  await new Promise((resolve) => setTimeout(resolve, 200));
  return [
    { id: '1', name: 'My App 1', bundleId: 'com.example.app1' },
    { id: '2', name: 'My App 2', bundleId: 'com.example.app2' },
  ];
}

export async function getRegions() {
  await new Promise((resolve) => setTimeout(resolve, 200));
  return [
    { id: '1', name: 'Albany, GA 市场' },
    { id: '2', name: '非洲地区' },
    { id: '3', name: 'San Antonio 市场' },
    { id: '4', name: '欧洲经济区(EEA) 地区' },
    { id: '5', name: '中国' },
    { id: '6', name: '美国' },
    { id: '7', name: '日本' },
  ];
}

export async function getPlacements() {
  await new Promise((resolve) => setTimeout(resolve, 200));
  return [
    { id: '1', name: '动态', platform: 'Facebook', hasChildren: false },
    { id: '2', name: '快拍和 Reels', platform: 'Facebook', hasChildren: false },
    { id: '3', name: '视频和 Reels 插播...', platform: 'Facebook', hasChildren: false },
    { id: '4', name: '搜索结果', platform: 'Facebook', hasChildren: false },
    { id: '5', name: '应用和网站', platform: 'Facebook', hasChildren: false },
    { id: '6', name: 'Facebook Marketplace', platform: 'Facebook', hasChildren: false },
    { id: '7', name: 'Facebook 视频动态', platform: 'Facebook', hasChildren: false },
    { id: '8', name: 'Instagram 发现', platform: 'Instagram', hasChildren: false },
    { id: '9', name: 'Instagram 发现首页', platform: 'Instagram', hasChildren: false },
    { id: '10', name: 'Messenger 收件箱', platform: 'Messenger', hasChildren: false },
    { id: '11', name: '发现 Facebook 商家', platform: 'Facebook', hasChildren: false },
  ];
}

export async function getAppEvents() {
  await new Promise((resolve) => setTimeout(resolve, 200));
  return [
    { id: '1', name: '应用安装' },
    { id: '2', name: '应用内购买' },
    { id: '3', name: '应用内注册' },
  ];
}

export async function getFbPages() {
  await new Promise((resolve) => setTimeout(resolve, 200));
  return [
    { id: '1', name: 'My Facebook Page 1' },
    { id: '2', name: 'My Facebook Page 2' },
  ];
}

export async function getWebsiteEventTrackings() {
  await new Promise((resolve) => setTimeout(resolve, 200));
  return [
    { id: '1', name: '像素追踪' },
    { id: '2', name: '转化API' },
  ];
}

export async function getCallToActions() {
  await new Promise((resolve) => setTimeout(resolve, 200));
  return [
    { value: 'learn_more', label: '了解更多' },
    { value: 'shop_now', label: '立即购买' },
    { value: 'download', label: '下载' },
    { value: 'sign_up', label: '注册' },
  ];
}

export async function getTags() {
  await new Promise((resolve) => setTimeout(resolve, 200));
  return [
    { id: '1', name: '电商' },
    { id: '2', name: '促销' },
    { id: '3', name: '品牌' },
  ];
}

