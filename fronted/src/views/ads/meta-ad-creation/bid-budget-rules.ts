/**
 * 出价与预算：成效目标 ↔ 竞价策略 ↔ 计费方式 的联动与提交校验（与 step-bid-budget 展示逻辑一致）
 */

/** 仅允许「最高数量」的成效目标（与 bidStrategy*Disabled 一致） */
const GOAL_ONLY_HIGHEST_VOLUME = ['reach', 'link_clicks', 'app_installs'] as const;

export function isGoalOnlyHighestVolume(goal: string | undefined): boolean {
  return GOAL_ONLY_HIGHEST_VOLUME.includes(goal as any);
}

/** 当前成效目标下是否允许使用该竞价策略 */
export function isBidStrategyAllowedForGoal(goal: string | undefined, bidStrategy: string | undefined): boolean {
  if (isGoalOnlyHighestVolume(goal)) {
    return bidStrategy === 'HIGHEST_VOLUME';
  }
  return true;
}

/**
 * 按规则修正联动字段（应在变更成效目标后、以及合并草稿/提交前调用）
 * - 价值优化 → 默认 ROAS
 * - 覆盖/链接点击/应用安装 → 固定最高数量
 * - 非链接点击优化时不能使用 CPC 计费
 */
export function applyBidBudgetLinkage(pkg: any): void {
  if (!pkg || typeof pkg !== 'object') return;
  if (pkg.goal === 'conversion_value' && (!pkg.bidStrategy || pkg.bidStrategy === 'HIGHEST_VOLUME')) {
    pkg.bidStrategy = 'ROAS';
  }
  if (pkg.goal === 'reach' || pkg.goal === 'link_clicks' || pkg.goal === 'app_installs') {
    pkg.bidStrategy = 'HIGHEST_VOLUME';
  }
  if (pkg.goal !== 'link_clicks' && pkg.billing === 'cpc') {
    pkg.billing = 'impressions';
  }
}

export type BidBudgetValidateCtx = {
  /** 是否需要填写应用转化事件 */
  needsAppConversionEvent: boolean;
  /** 是否需要填写网站 Pixel 转化事件 */
  needsWebsitePixel: boolean;
  /** 是否需要填写归因 */
  needsAttribution: boolean;
  /** i18n */
  t: (key: string) => string;
};

function numAtLeast(v: unknown, min: number): boolean {
  if (v === undefined || v === null || v === '') return false;
  const n = Number(v);
  return !Number.isNaN(n) && n >= min;
}

/**
 * 单条出价配置的完整校验（先假定已执行 applyBidBudgetLinkage）
 * 返回首条错误文案，无错返回 null
 */
export function validateBidBudgetPackage(pkg: any, ctx: BidBudgetValidateCtx): string | null {
  const { t } = ctx;
  if (!String(pkg?.name ?? '').trim()) {
    return t('请为每条出价配置填写名称');
  }
  if (ctx.needsAppConversionEvent && !String(pkg?.appEvent ?? '').trim()) {
    return t('请为每条出价配置选择应用转化事件');
  }
  if (ctx.needsWebsitePixel && !String(pkg?.websitePixelEvent ?? '').trim()) {
    return t('请为每条出价配置选择网站 Pixel 转化事件');
  }
  if (ctx.needsAttribution && !String(pkg?.attribution ?? '').trim()) {
    return t('请为每条出价配置选择归因设置');
  }
  if (!isBidStrategyAllowedForGoal(pkg?.goal, pkg?.bidStrategy)) {
    return t('当前成效目标下仅支持「最高数量」竞价策略，请切换成效目标或竞价策略');
  }
  if (pkg?.bidStrategy === 'COST_PER_RESULT' && (pkg?.costPerResultTarget === undefined || pkg?.costPerResultTarget === null)) {
    return t('请填写单次成效费用目标');
  }
  if (pkg?.bidStrategy === 'BID_CAP' && (pkg?.bidCapAmount === undefined || pkg?.bidCapAmount === null)) {
    return t('请填写竞价上限');
  }
  if (pkg?.bidStrategy === 'ROAS' && (pkg?.roasTarget === undefined || pkg?.roasTarget === null)) {
    return t('请填写 ROAS 目标');
  }
  if (!numAtLeast(pkg?.bidControl, 0.01)) {
    return t('请填写竞价控制额（至少 0.01 CNY）');
  }
  if (pkg?.bidByRegion === true) {
    if (!numAtLeast(pkg?.regionBidG1, 0.01) || !numAtLeast(pkg?.regionBidG2, 0.01)) {
      return t('分地区出价时请为每个地区组填写竞价（至少 0.01）');
    }
  }
  if (pkg?.schedule === 'custom') {
    if (!pkg?.startDate || !pkg?.endDate || !pkg?.startTime || !pkg?.endTime) {
      return t('自定义排期请填写每条出价配置的完整开始与结束日期时间');
    }
  }
  const sm = pkg?.spendMin;
  const sx = pkg?.spendMax;
  if (sm != null && sx != null && Number(sm) > Number(sx)) {
    return t('广告组花费限额最小值不能大于最大值');
  }
  if (pkg?.goal !== 'link_clicks' && pkg?.billing === 'cpc') {
    return t('仅「链接点击量最大化」成效目标可使用链接点击（CPC）计费');
  }
  return null;
}
