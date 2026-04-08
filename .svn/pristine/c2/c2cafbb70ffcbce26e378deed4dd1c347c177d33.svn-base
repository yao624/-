// 模拟数据

// 获取文件夹列表
export async function getFolders() {
  await new Promise((resolve) => setTimeout(resolve, 200));

  return [
    {
      id: 1,
      name: '系统文件夹',
      count: 3,
      canDelete: false,
      isExpanded: false,
      childrenLoaded: true,
      children: [],
    },
    {
      id: 2,
      name: '业务文件夹',
      count: 2,
      canDelete: true,
      isExpanded: false,
      childrenLoaded: true,
      children: [],
    },
    {
      id: 3,
      name: '自定义文件夹',
      count: 1,
      canDelete: true,
      isExpanded: false,
      childrenLoaded: true,
      children: [],
    },
  ];
}

// 获取标签列表
export async function getTags() {
  await new Promise((resolve) => setTimeout(resolve, 200));

  return [
    {
      id: 1,
      name: '短视频',
      remark: '用于短视频素材',
      parent_id: 1,
      parent_name: '系统标签',
      options: [
        { id: 1, name: '竖版短视频', description: '9:16 竖屏视频素材', url: 'https://via.placeholder.com/100x100/1890ff/fff?text=video1', remark1: '时长15-60秒', remark2: '优先高清' },
        { id: 2, name: '横版短视频', description: '16:9 横屏视频素材', url: 'https://via.placeholder.com/100x100/52c41a/fff?text=video2', remark1: '时长10-30秒', remark2: '横版构图' },
        { id: 3, name: '方形短视频', description: '1:1 正方形视频素材', url: 'https://via.placeholder.com/100x100/fa8c16/fff?text=video3', remark1: '适用于信息流', remark2: '需配字幕' },
      ],
    },
    { id: 2, name: '图片广告', remark: '图片类广告素材', parent_id: 1, parent_name: '系统标签', options: [] },
    { id: 3, name: '轮播图', remark: '轮播图素材', parent_id: 1, parent_name: '系统标签', options: [] },
    { id: 4, name: '信息流', remark: '信息流广告', parent_id: 2, parent_name: '业务标签', options: [] },
    { id: 5, name: '品牌广告', remark: '品牌类广告', parent_id: 2, parent_name: '业务标签', options: [] },
    { id: 6, name: '促销标签', remark: '促销活动标签', parent_id: 3, parent_name: '自定义标签', options: [] },
  ];
}

// 获取子标签选项列表
// parent_id: 上级选项的ID，用于过滤下一层数据
// Level 1 选项的 parent_id = 最外层标签选项的 id（即 tag.options 里的选项 id）
export async function getSubTagOptions(parent_id?: number | string) {
  await new Promise((resolve) => setTimeout(resolve, 200));

  // 所有选项数据，带 parent_id 层级关系
  // 最外层选项: tag.options (id: 1, 2, 3 from tag id=1)
  // Level 1 sub-options: parent_id = 最外层选项 id (1, 2, 3)
  // Level 2 sub-options: parent_id = Level 1 sub-option id
  // Level 3 sub-options: parent_id = Level 2 sub-option id
  const allOptions = [
    // Level 1 子选项 (parent_id 为最外层标签选项的 id: 1, 2, 3)
    { id: 101, parent_id: 1, name: '竖版-高清', description: '1080P 竖屏视频', url: 'https://via.placeholder.com/100x100/1890ff/fff?text=hd1', remark1: '1080P', remark2: '竖屏' },
    { id: 102, parent_id: 1, name: '竖版-标清', description: '720P 竖屏视频', url: 'https://via.placeholder.com/100x100/52c41a/fff?text=sd1', remark1: '720P', remark2: '竖屏' },
    { id: 103, parent_id: 2, name: '横版-高清', description: '1080P 横屏视频', url: 'https://via.placeholder.com/100x100/fa8c16/fff?text=hd2', remark1: '1080P', remark2: '横版' },
    { id: 104, parent_id: 2, name: '横版-标清', description: '720P 横屏视频', url: 'https://via.placeholder.com/100x100/eb2f96/fff?text=sd2', remark1: '720P', remark2: '横版' },
    { id: 105, parent_id: 3, name: '方形-高清', description: '1080P 方形视频', url: 'https://via.placeholder.com/100x100/722ed1/fff?text=hd3', remark1: '1080P', remark2: '方形' },

    // Level 2 子选项 (parent_id 为 Level 1 子选项的 id)
    { id: 201, parent_id: 101, name: '竖版-4K', description: '4K 超高清竖屏', url: 'https://via.placeholder.com/100x100/13c2c2/fff?text=4k1', remark1: '4K', remark2: '顶级' },
    { id: 202, parent_id: 101, name: '竖版-1080P', description: '全高清竖屏', url: 'https://via.placeholder.com/100x100/f5222d/fff?text=fhd1', remark1: '1080P', remark2: '高清' },
    { id: 203, parent_id: 102, name: '竖版-720P', description: '高清竖屏', url: 'https://via.placeholder.com/100x100/fa541c/fff?text=hd4', remark1: '720P', remark2: '标准' },
    { id: 204, parent_id: 103, name: '横版-4K', description: '4K 超高清横屏', url: 'https://via.placeholder.com/100x100/1890ff/fff?text=4k2', remark1: '4K', remark2: '顶级' },

    // Level 3 子选项 (parent_id 为 Level 2 子选项的 id)
    { id: 301, parent_id: 201, name: '竖版-4K-电影级', description: '4K 电影级竖屏', url: 'https://via.placeholder.com/100x100/52c41a/fff?text=4kfilm', remark1: '4K', remark2: '电影级' },
    { id: 302, parent_id: 201, name: '竖版-4K-标准', description: '4K 标准竖屏', url: 'https://via.placeholder.com/100x100/fa8c16/fff?text=4kstd', remark1: '4K', remark2: '标准' },
  ];

  // 如果没有 parent_id，返回空（表示没有下一级）
  if (parent_id === undefined || parent_id === null) {
    return [];
  }

  // 根据 parent_id 过滤，返回直接子选项
  return allOptions.filter(opt => opt.parent_id === parent_id);
}
