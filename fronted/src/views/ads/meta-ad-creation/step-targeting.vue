<template>
  <div class="step-targeting">
    <div class="pkg-page-head">
      <h3 class="section-title">{{ t('定向包') }}</h3>
      <div class="toolbar">
        <a-dropdown>
          <template #overlay>
            <a-menu @click="onBatchMenu">
              <a-menu-item key="reset">{{ t('重置为单个定向包') }}</a-menu-item>
            </a-menu>
          </template>
          <a-button>{{ t('批量操作') }} <down-outlined /></a-button>
        </a-dropdown>
        <a-button type="text" danger @click="clearActivePackage">{{ t('清空') }}</a-button>
        <span class="pkg-count">{{ packages.length }}/20</span>
      </div>
    </div>

    <a-tabs
      v-model:activeKey="activeTabKey"
      type="editable-card"
      class="targeting-tabs"
      :hide-add="packages.length >= MAX_PKGS"
      @edit="onTabsEdit"
    >
      <a-tab-pane
        v-for="(pkg, pkgIndex) in packages"
        :key="pkg.id"
        :closable="packages.length > 1"
      >
        <template #tab>
          <!-- 勿在整行 tab 上 @click.stop，否则会拦截 Tabs 切换；仅「⋯」上阻止冒泡 -->
          <span class="tab-title-wrap">
            <span class="tab-title-text" :title="pkg.name || `${t('定向包')}${pkgIndex + 1}`">
              {{ tabDisplayName(pkg, pkgIndex) }}
            </span>
            <span class="tab-more-wrap" @click.stop>
              <a-dropdown :trigger="['click']" placement="bottomRight">
                <ellipsis-outlined class="tab-more-icon" />
                <template #overlay>
                  <a-menu @click="(e) => onPkgMenu(e, pkgIndex)">
                    <a-menu-item key="duplicate" :disabled="packages.length >= MAX_PKGS">{{ t('复制') }}</a-menu-item>
                    <a-menu-item key="clear">{{ t('清空本包定向') }}</a-menu-item>
                    <a-menu-item key="remove" :disabled="packages.length <= 1" danger>{{ t('删除') }}</a-menu-item>
                  </a-menu>
                </template>
              </a-dropdown>
            </span>
          </span>
        </template>

        <div v-if="activeTabKey === pkg.id" class="tab-pane-inner">
          <a-form
            layout="horizontal"
            class="pkg-edit-form"
            :colon="false"
            :label-col="{ style: { width: '140px', flex: '0 0 140px' } }"
            :wrapper-col="{ flex: '1', style: { minWidth: '0', maxWidth: '880px' } }"
          >
            <a-form-item :wrapper-col="{ span: 24 }" class="pkg-row-existing">
              <a-space align="center" :size="12">
                <file-text-outlined class="pkg-existing-icon" />
                <a-checkbox v-model:checked="pkg.useExisting" @change="() => onToggleUseExisting(pkg)">
                  {{ t('选择已有定向包') }}
                </a-checkbox>
                <a-button v-if="pkg.useExisting" type="link" size="small" @click="openSavedPkgModal(pkg)">
                  {{ t('从列表选择') }}
                </a-button>
              </a-space>
            </a-form-item>

            <a-collapse v-model:activeKey="advancedCollapseKeys" ghost class="pkg-advanced-collapse">
              <a-collapse-panel key="bind" :header="t('绑定与地区')">
                <a-form-item :label="t('绑定对象') + ' *'">
                  <div class="bind-object-row">
                    <span>{{ bindObjectSummary(pkg) }}</span>
                    <a-button type="link" size="small" @click="openBindRuleModal(pkg)">
                      <template #icon><edit-outlined /></template>
                    </a-button>
                  </div>
                </a-form-item>
                <a-form-item :label="t('本广告组地区')">
                  <a-radio-group v-model:value="pkg.useCustomRegion" @change="() => onToggleCustomRegion(pkg)">
                    <a-radio :value="false">{{ t('与「地区」步骤一致（默认）') }}</a-radio>
                    <a-radio :value="true">{{ t('本广告组单独设置地区') }}</a-radio>
                  </a-radio-group>
                  <div class="hint">{{ t('单独设置时，该地区仅作用于本广告组，对应 Meta Ad Set 的 geo_locations。') }}</div>
                </a-form-item>
                <div v-if="pkg.useCustomRegion" class="pkg-region-embed">
                  <step-region-panel
                    compact
                    :form-data="pkg.stepRegion || {}"
                    @update:form-data="(v) => onPackageStepRegionUpdate(pkg, v)"
                  />
                </div>
              </a-collapse-panel>
            </a-collapse>

            <!-- 勿用 fieldset disabled：会连带禁用标签/名称/保存。仅对受众与设备区块做视觉锁定 -->
            <div
              class="targeting-fieldset targeting-fieldset--lockable"
              :class="{ 'targeting-fieldset--locked': pkg.useExisting }"
            >
              <a-form-item :label="t('进阶赋能型受众')">
                <a-switch v-model:checked="pkg.advancedAudience" />
              </a-form-item>

              <a-form-item :label="t('自定义受众')">
                <div class="btn-switch">
                  <a-button
                    size="small"
                    :type="pkg.customAudience === 'unlimited' ? 'primary' : 'default'"
                    @click="pkg.customAudience = 'unlimited'"
                  >
                    {{ t('不限') }}
                  </a-button>
                  <a-button
                    size="small"
                    :type="pkg.customAudience === 'custom' ? 'primary' : 'default'"
                    @click="pkg.customAudience = 'custom'"
                  >
                    {{ t('自定义') }}
                  </a-button>
                </div>
              </a-form-item>

              <a-form-item v-if="pkg.customAudience === 'custom'" :label="t('自定义受众') + ' *'">
                <div class="picker-panel">
                  <div class="picker-left">
                    <a-tabs v-model:activeKey="pkg.customAudienceTab" size="small">
                      <a-tab-pane key="all" :tab="t('全部受众')" />
                      <a-tab-pane key="lookalike" :tab="t('类似受众')" />
                      <a-tab-pane key="custom" :tab="t('自定义受众')" />
                    </a-tabs>
                    <div class="picker-search-row">
                      <a-input
                        v-model:value="pkg.customAudienceSearch"
                        :placeholder="t('搜索...')"
                        allow-clear
                      />
                      <a-button type="link" size="small" class="sync-link" disabled>{{ t('同步') }}</a-button>
                    </div>
                    <div class="picker-table">
                      <div class="picker-table-head">
                        <div class="col-name">{{ t('名称') }}</div>
                        <div class="col-action">{{ t('操作') }}</div>
                      </div>
                      <div class="picker-table-body">
                        <div v-if="filteredAudienceRows(pkg).length === 0" class="empty-row">
                          {{ t('暂无数据') }}
                        </div>
                        <div v-for="row in filteredAudienceRows(pkg)" :key="row.id" class="picker-row">
                          <div class="col-name">{{ row.name }}</div>
                          <div class="col-action">
                            <a-button type="link" size="small" @click="addAudience(pkg, 'include', row)">{{ t('定向') }}</a-button>
                            <a-button type="link" size="small" danger @click="addAudience(pkg, 'exclude', row)">{{ t('排除') }}</a-button>
                          </div>
                        </div>
                      </div>
                    </div>
                  </div>
                  <div class="picker-right">
                    <div class="selected-hd">
                      <span>{{ t('已选') }}</span>
                      <a-button type="link" size="small" @click="clearSelectedAudiences(pkg)">{{ t('清除') }}</a-button>
                    </div>
                    <a-tabs v-model:activeKey="pkg.customAudienceSelectedTab" size="small">
                      <a-tab-pane :key="'include'" :tab="`${t('定向')}(${pkg.customAudienceInclude.length})`" />
                      <a-tab-pane :key="'exclude'" :tab="`${t('排除')}(${pkg.customAudienceExclude.length})`" />
                    </a-tabs>
                    <div class="selected-list">
                      <template v-if="pkg.customAudienceSelectedTab === 'include'">
                        <div v-if="pkg.customAudienceInclude.length === 0" class="empty-hint">{{ t('暂无') }}</div>
                        <div v-for="item in pkg.customAudienceInclude" :key="item.id" class="selected-item">
                          <span class="selected-name">{{ item.name }}</span>
                          <a-button type="text" size="small" class="x-btn" @click="removeAudience(pkg, 'include', item.id)">×</a-button>
                        </div>
                      </template>
                      <template v-else>
                        <div v-if="pkg.customAudienceExclude.length === 0" class="empty-hint">{{ t('暂无') }}</div>
                        <div v-for="item in pkg.customAudienceExclude" :key="item.id" class="selected-item">
                          <span class="selected-name">{{ item.name }}</span>
                          <a-button type="text" size="small" class="x-btn" @click="removeAudience(pkg, 'exclude', item.id)">×</a-button>
                        </div>
                      </template>
                    </div>
                  </div>
                </div>
              </a-form-item>

              <a-form-item :label="t('最低年龄限制') + ' *'">
                <a-select v-model:value="pkg.minAge" style="width: 120px" :options="ageOptionsWithPlus" />
              </a-form-item>
              <a-form-item :label="t('年龄建议')">
                <span class="age-range-row">
                  <a-select v-model:value="pkg.ageFrom" class="age-select" :options="ageOptionsWithPlus" />
                  <span class="age-tilde">~</span>
                  <a-select v-model:value="pkg.ageTo" class="age-select" :options="ageOptionsWithPlus" />
                </span>
              </a-form-item>

              <a-form-item :label="t('性别')">
                <div class="btn-switch">
                  <a-button size="small" :type="pkg.gender === 'all' ? 'primary' : 'default'" @click="pkg.gender = 'all'">
                    {{ t('不限') }}
                  </a-button>
                  <a-button size="small" :type="pkg.gender === 'male' ? 'primary' : 'default'" @click="pkg.gender = 'male'">
                    {{ t('男性') }}
                  </a-button>
                  <a-button size="small" :type="pkg.gender === 'female' ? 'primary' : 'default'" @click="pkg.gender = 'female'">
                    {{ t('女性') }}
                  </a-button>
                </div>
              </a-form-item>
            </div>

            <!-- 细分定位～语言：不参与「已有定向包」的 pointer-events 锁定，保证始终可点、可拉 Meta -->
            <div class="targeting-fieldset targeting-fieldset-detail">
              <a-form-item :label="t('细分定位')">
                <div class="btn-switch">
                  <a-button
                    size="small"
                    :type="pkg.detailedTargeting === 'unlimited' ? 'primary' : 'default'"
                    @click="onDetailedTargetingChange(pkg, 'unlimited')"
                  >
                    {{ t('不限') }}
                  </a-button>
                  <a-button
                    size="small"
                    :type="pkg.detailedTargeting === 'custom' ? 'primary' : 'default'"
                    @click="onDetailedTargetingChange(pkg, 'custom')"
                  >
                    {{ t('自定义') }}
                  </a-button>
                </div>
                <div v-if="pkg.detailedTargeting === 'custom'" class="dt-dual-pane">
                  <div class="dt-left">
                    <div v-if="!adAccountIdList.length" class="hint browse-account-hint">
                      {{ t('请先在第一步选择广告账户，再浏览分类。') }}
                    </div>
                    <div v-if="adAccountIdList.length > 1" class="dt-account-row">
                      <span class="dt-account-label">{{ t('用于浏览的账户') }}</span>
                      <a-select v-model:value="activeBrowseAdAccountId" size="small" style="width: 240px" :options="adAccountIdList.map((id) => ({ label: id, value: id }))" />
                    </div>
                    <a-tabs v-model:activeKey="pkg.detailedInterestSubTab" size="small" class="detailed-interest-subtabs" @change="(k: string) => onDetailedInterestSubTabChange(pkg, k)">
                      <!-- 浏览 Tab -->
                      <a-tab-pane key="browse" :tab="t('浏览')">
                        <a-input
                          v-model:value="pkg.detailBrowseSearch"
                          class="dt-search-input"
                          :placeholder="t('搜索...')"
                          allow-clear
                          @update:value="() => onDetailBrowseSearchInput(pkg)"
                        >
                          <template #prefix><search-outlined style="color: #bfbfbf; font-size: 13px" /></template>
                        </a-input>

                        <!-- 搜索激活时：显示搜索结果 -->
                        <div v-if="pkg.browseLoading && !isBrowseMetaSearchActive(pkg)" class="interest-loading">
                          <a-spin size="small" />
                        </div>
                        <div v-else-if="isBrowseMetaSearchActive(pkg)" class="dt-scroll-list">
                          <div v-if="pkg.browseMetaSearchLoading" class="interest-loading"><a-spin size="small" /></div>
                          <template v-else>
                            <div v-if="!pkg.browseMetaSearchRows?.length" class="empty-hint interest-candidate-empty">{{ t('未找到匹配项，请换关键词') }}</div>
                            <div v-for="(row, idx) in pkg.browseMetaSearchRows" :key="'ms-' + row.id + '-' + idx" class="interest-candidate-row browse-row">
                              <div class="interest-candidate-main">
                                <span class="interest-candidate-name">{{ row.name }}</span>
                                <span v-if="row.type" class="interest-candidate-id">{{ row.type }}</span>
                              </div>
                              <a-button v-if="row.id != null && row.id !== ''" type="link" size="small" :disabled="isInterestSelected(pkg, Number(row.id))" @click="addInterestToPkg(pkg, { id: Number(row.id), name: String(row.name || '') })">
                                {{ isInterestSelected(pkg, Number(row.id)) ? t('已添加') : t('添加') }}
                              </a-button>
                            </div>
                          </template>
                        </div>

                        <!-- 未搜索时：展示三分类统一层级树 -->
                        <div v-else class="dt-scroll-list">
                          <div
                            v-for="row in allBrowseCatVisibleRows(pkg)"
                            :key="row.key"
                            class="dt-tree-item-row"
                            :class="{
                              'dt-tree-item-row--cat': row.isCatRoot || row.hasChildren,
                              'dt-tree-item-row--leaf': !row.isCatRoot && !row.hasChildren && !row.catLoading && !row.isEmpty,
                              'dt-tree-item-row--empty': row.isEmpty || row.catLoading,
                            }"
                            :style="{ paddingLeft: `${row.depth * 20 + 8}px` }"
                            @click.stop="row.isCatRoot ? toggleBrowseCat(pkg, row.cat) : (row.hasChildren ? toggleBrowseSubNode(pkg, row.cat, row.node) : null)"
                          >
                            <!-- 分类根节点 -->
                            <template v-if="row.isCatRoot">
                              <div class="dt-tree-cat-inner">
                                <span class="dt-tree-item-toggle">
                                  <a-spin v-if="row.catLoading" size="small" style="vertical-align: middle" />
                                  <down-outlined v-else-if="row.expanded" class="dt-tree-icon" />
                                  <right-outlined v-else class="dt-tree-icon" />
                                </span>
                                <span class="dt-tree-cat-name">{{ row.node.name }}</span>
                              </div>
                              <!-- 分类刷新按钮 -->
                              <a-button
                                v-if="row.expanded && row.catLoaded && !row.catLoading"
                                type="text"
                                size="small"
                                class="dt-cat-refresh-btn"
                                :title="t('刷新该分类')"
                                @click.stop="refreshBrowseCat(pkg, row.cat)"
                              >
                                <reload-outlined />
                              </a-button>
                            </template>

                            <!-- 父节点（展开箭头 + 名称） -->
                            <template v-else-if="row.hasChildren">
                              <div class="dt-tree-cat-inner">
                                <span class="dt-tree-item-toggle">
                                  <a-spin v-if="row.isChildLoading" size="small" style="vertical-align: middle" />
                                  <down-outlined v-else-if="row.expanded" class="dt-tree-icon" />
                                  <right-outlined v-else class="dt-tree-icon" />
                                </span>
                                <span class="dt-tree-cat-name" style="font-weight: 400; color: #333">{{ row.node.name }}</span>
                              </div>
                            </template>

                            <!-- 加载中 / 暂无数据占位 -->
                            <template v-else-if="row.catLoading || row.isEmpty">
                              <div class="dt-tree-cat-inner">
                                <a-spin v-if="row.catLoading" size="small" style="margin-right: 6px; vertical-align: middle" />
                                <span style="font-size: 13px; color: #bfbfbf">{{ row.node.name }}</span>
                              </div>
                            </template>

                            <!-- 叶子节点：名称 + 规模 + 复选框 -->
                            <template v-else-if="row.node.id != null && row.node.id !== ''">
                              <div class="dt-tree-leaf-info">
                                <span class="dt-tree-leaf-name">{{ row.node.name }}</span>
                                <span class="dt-tree-leaf-size">
                                  {{ t('规模') }}：{{ getNodeAudienceSize(row.node) || t('暂无数据') }}
                                </span>
                              </div>
                              <a-checkbox
                                :checked="isInterestSelected(pkg, Number(row.node.id))"
                                @change="(e: any) => e.target.checked ? addInterestToPkg(pkg, { id: Number(row.node.id), name: String(row.node.name || '') }) : removeInterestFromPkg(pkg, Number(row.node.id))"
                                @click.stop
                              />
                            </template>
                          </div>
                        </div>

                        <div class="dt-left-footer">
                          <a-button type="link" size="small" disabled>{{ t('缩小受众范围') }}</a-button>
                          <a-button type="link" size="small" disabled>{{ t('同步') }}</a-button>
                        </div>
                      </a-tab-pane>

                      <!-- 建议 Tab -->
                      <a-tab-pane key="suggest" :tab="t('建议')">
                        <a-input-search v-model:value="pkg.interestSearchInput" :placeholder="t('搜索...')" allow-clear enter-button @search="(v: string) => runInterestSearch(pkg, v)" />
                        <div v-if="pkg.interestSearchLoading" class="interest-loading"><a-spin size="small" /></div>
                        <div v-else class="interest-candidate-box dt-suggest-box">
                          <div v-if="!pkg.interestSearchResults?.length" class="empty-hint interest-candidate-empty">
                            {{ (pkg.interestSearchInput || '').trim() ? t('暂无匹配结果，请换关键词') : t('输入关键词后搜索') }}
                          </div>
                          <div v-for="row in pkg.interestSearchResults" :key="'cand-' + row.id" class="interest-candidate-row">
                            <div class="interest-candidate-main"><span class="interest-candidate-name">{{ row.name }}</span></div>
                            <a-button type="link" size="small" :disabled="isInterestSelected(pkg, row.id)" @click="addInterestToPkg(pkg, row)">
                              {{ isInterestSelected(pkg, row.id) ? t('已添加') : t('添加') }}
                            </a-button>
                          </div>
                        </div>
                      </a-tab-pane>

                      <!-- 操作 Tab -->
                      <a-tab-pane key="operation" :tab="t('操作')">
                        <p class="dt-op-hint">{{ t('排除或收窄受众等能力将随投放接口扩展；当前请在「浏览」中选择并添加。') }}</p>
                      </a-tab-pane>
                    </a-tabs>
                  </div>

                  <!-- 右侧已选区 -->
                  <div class="dt-right">
                    <div class="dt-right-hd">
                      <span>{{ t('已选') }}（{{ (pkg.interests || []).length }}）</span>
                      <a-button type="link" size="small" @click="clearInterests(pkg)">{{ t('清除') }}</a-button>
                    </div>
                    <div class="dt-right-body">
                      <div v-if="!(pkg.interests || []).length" class="empty-hint dt-right-empty">{{ t('暂无已选项') }}</div>
                      <div v-for="it in pkg.interests || []" :key="'sel-' + it.id" class="dt-right-row">
                        <span class="dt-right-name">{{ it.name }}</span>
                        <a-button type="text" size="small" class="x-btn" danger @click="removeInterestFromPkg(pkg, it.id)">×</a-button>
                      </div>
                    </div>
                  </div>
                </div>
              </a-form-item>

              <a-form-item :label="t('赋能型细分定位')">
                <a-switch v-model:checked="pkg.enableDetailedExpansion" />
              </a-form-item>

              <a-form-item :label="t('语言')">
                <a-select
                  v-model:value="pkg.languages"
                  mode="multiple"
                  :options="languageOptions"
                  :placeholder="t('请选择')"
                  style="width: 100%; max-width: 360px"
                  :max-tag-count="0"
                >
                  <template #dropdownRender>
                    <div class="two-pane-dd lang-dd" @mousedown.prevent>
                      <div class="two-pane-left">
                        <div class="dd-search">
                          <a-input v-model:value="pkg.languageSearch" :placeholder="t('请输入')" allow-clear />
                        </div>
                        <div class="dd-list">
                          <div
                            v-for="opt in filteredLanguageOptions(pkg)"
                            :key="opt.value"
                            class="dd-row"
                            @click="toggleLang(pkg, opt.value)"
                          >
                            <a-checkbox :checked="(pkg.languages || []).includes(opt.value)" @click.stop />
                            <span class="dd-row-label">{{ opt.label }}</span>
                          </div>
                        </div>
                      </div>
                      <div class="two-pane-right">
                        <div class="two-pane-right-hd">
                          <span>{{ t('已选') }} {{ (pkg.languages || []).length }} {{ t('个') }}</span>
                          <a-button type="link" size="small" @click="pkg.languages = []">{{ t('清除') }}</a-button>
                        </div>
                        <div class="dd-selected">
                          <div v-for="v in pkg.languages || []" :key="v" class="dd-selected-row">
                            <span class="dd-selected-label">{{ languageLabelMap[v] || v }}</span>
                            <a-button type="text" size="small" class="x-btn" @click="removeLang(pkg, v)">×</a-button>
                          </div>
                          <div v-if="!(pkg.languages || []).length" class="empty-hint">{{ t('暂无') }}</div>
                        </div>
                      </div>
                    </div>
                  </template>
                </a-select>
              </a-form-item>
            </div>

            <!-- 标签、名称、复制/保存：始终可编辑（不受「选择已有定向包」锁定） -->
              <a-form-item>
                <template #label>
                  <span class="pkg-field-label-with-tip">
                    {{ t('标签') }}
                    <a-tooltip>
                      <template #title>
                        {{
                          t(
                            '可输入自定义标签或从常用项选择；用于区分定向包、列表筛选与后续复用。提交草稿时一并保存。',
                          )
                        }}
                      </template>
                      <question-circle-outlined class="pkg-label-tip-icon" />
                    </a-tooltip>
                  </span>
                </template>
                <a-select
                  v-model:value="pkg.tags"
                  mode="tags"
                  :options="tagOptions"
                  :placeholder="t('请选择或输入标签')"
                  style="width: 100%; max-width: 480px"
                  :token-separators="[',']"
                  allow-clear
                />
              </a-form-item>

              <a-form-item :label="t('定向包名称')">
                <a-input
                  v-model:value="pkg.name"
                  :maxlength="50"
                  show-count
                  :placeholder="t('定向包1')"
                  style="width: 100%; max-width: 480px"
                />
              </a-form-item>

              <a-form-item :wrapper-col="{ span: 24 }" class="pkg-footer-actions">
                <a-space :size="12" wrap>
                  <a-button @click="duplicateCurrentGroup">{{ t('复制当前组到新建') }}</a-button>
                  <a-button type="primary" @click="saveAsTargetingPackage(pkg)">{{ t('保存为定向包') }}</a-button>
                </a-space>
              </a-form-item>

            <div
              class="targeting-fieldset targeting-fieldset--lockable"
              :class="{ 'targeting-fieldset--locked': pkg.useExisting }"
            >
              <a-divider orientation="left" class="pkg-more-divider">{{ t('更多设备与投放') }}</a-divider>

              <a-form-item :label="t('定位已安装你的移动应用的用户')">
                <a-switch v-model:checked="pkg.targetInstalled" />
                <div class="hint">{{ t('需在「投放内容」中已选应用；提交时写入投放备注供运营核对。') }}</div>
              </a-form-item>

              <a-form-item :label="t('移动设备')">
                <a-radio-group v-model:value="pkg.mobileDeviceMode" button-style="solid">
                  <a-radio-button value="all_mobile">{{ t('所有移动设备') }}</a-radio-button>
                  <a-radio-button value="android_only">{{ t('仅限 Android 设备') }}</a-radio-button>
                  <a-radio-button value="ios_only">{{ t('仅限 iOS 设备') }}</a-radio-button>
                  <a-radio-button value="feature_phone">{{ t('仅限非智能手机') }}</a-radio-button>
                </a-radio-group>
              </a-form-item>
              <template v-if="pkg.mobileDeviceMode === 'android_only' || pkg.mobileDeviceMode === 'ios_only'">
                <a-form-item :label="t('包含的设备') + ' *'">
                  <a-select
                    v-model:value="pkg.includedDevices"
                    mode="multiple"
                    :options="deviceModelOptions(pkg.mobileDeviceMode)"
                    :placeholder="t('请选择')"
                    style="width: 100%; max-width: 420px"
                  />
                </a-form-item>
                <a-form-item :label="t('排除的设备')">
                  <a-select
                    v-model:value="pkg.excludedDevices"
                    mode="multiple"
                    :options="deviceModelOptions(pkg.mobileDeviceMode)"
                    :placeholder="t('请选择')"
                    style="width: 100%; max-width: 420px"
                    allow-clear
                  />
                </a-form-item>
                <a-form-item :label="t('操作系统版本')">
                  <a-input
                    v-model:value="pkg.osVersionText"
                    :placeholder="t('如：Android 10+ / iOS 16+')"
                    style="width: 100%; max-width: 420px"
                  />
                </a-form-item>
              </template>

              <a-form-item :label="t('仅在连接 Wi-Fi 时')">
                <a-switch v-model:checked="pkg.wifiOnly" />
              </a-form-item>
            </div>
          </a-form>
        </div>
      </a-tab-pane>
    </a-tabs>

    <a-modal
      v-model:open="bindRuleModalVisible"
      :title="t('绑定规则')"
      width="760px"
      @ok="confirmBindRuleModal"
      @cancel="bindRuleModalVisible = false"
    >
      <a-form layout="vertical">
        <a-form-item :label="t('绑定对象')">
          <a-radio-group v-model:value="bindRuleDraft.bindTargetType" button-style="solid">
            <a-radio-button value="account">{{ t('广告账户') }}</a-radio-button>
            <a-radio-button value="region">{{ t('地区') }}</a-radio-button>
          </a-radio-group>
        </a-form-item>
        <a-form-item :label="t('绑定规则')">
          <a-select v-model:value="bindRuleDraft.bindRule" style="width: 220px">
            <a-select-option value="basic">{{ t('批量设置') }}</a-select-option>
          </a-select>
        </a-form-item>
        <a-table :columns="bindRuleColumns" :data-source="bindRuleRows" :pagination="false" row-key="key" size="small">
          <template #bodyCell="{ column }">
            <template v-if="column.key === 'target'">
              <a-select
                v-model:value="bindRuleDraft.boundItems"
                mode="multiple"
                :options="bindRuleTargetOptions"
                style="width: 100%"
                :placeholder="t('全部')"
              />
            </template>
          </template>
        </a-table>
      </a-form>
    </a-modal>

    <a-modal
      v-model:open="savedPkgModalVisible"
      :title="t('选择已有定向包')"
      width="900px"
      :footer="null"
      destroy-on-close
    >
      <div class="saved-pkg-toolbar">
        <a-input v-model:value="savedPkgKeyword" :placeholder="t('定向包：搜索...')" style="width: 240px" />
        <a-select v-model:value="savedPkgTag" :placeholder="t('标签：请选择')" style="width: 220px" disabled />
      </div>
      <a-table :columns="savedPkgColumns" :data-source="savedPkgRows" :pagination="savedPkgPagination" row-key="id" size="small">
        <template #bodyCell="{ column, record }">
          <template v-if="column.key === 'action'">
            <a-button type="link" size="small" @click="applySavedPackage(record)">{{ t('使用') }}</a-button>
          </template>
        </template>
      </a-table>
    </a-modal>
  </div>
</template>

<script lang="ts" setup>
import { ref, watch, nextTick, toRaw, computed, onMounted, onBeforeUnmount } from 'vue';
import { useI18n } from 'vue-i18n';
import { message } from 'ant-design-vue';
import {
  DownOutlined,
  EllipsisOutlined,
  EditOutlined,
  FileTextOutlined,
  QuestionCircleOutlined,
  ReloadOutlined,
  RightOutlined,
  SearchOutlined,
} from '@ant-design/icons-vue';
import { searchInterests } from '@/api/fb_ad_template';
import { targetingBrowseApi, targetingSearchDetailedApi } from '@/api/meta_ad_creation/index';
import StepRegionPanel from './step-region.vue';

const { t } = useI18n();

// -------- 多分类统一树 --------
const BROWSE_CATS = [
  { key: 'demographics', label: '' },
  { key: 'behaviors', label: '' },
  { key: 'interests', label: '' },
] as const;

function getBrowseCatLabel(catKey: string): string {
  const map: Record<string, string> = {
    demographics: t('人口统计数据'),
    behaviors: t('行为'),
    interests: t('兴趣'),
  };
  return map[catKey] || catKey;
}

function getCatState(pkg: any, cat: string) {
  if (!pkg.browseCatStates) pkg.browseCatStates = {};
  if (!pkg.browseCatStates[cat]) {
    pkg.browseCatStates[cat] = {
      expanded: false,
      loading: false,
      loaded: false,
      rootNodes: [] as any[],
      children: {} as Record<string, any[]>,
      expandedKeys: [] as string[],
      loadingKeys: [] as string[],
    };
  }
  return pkg.browseCatStates[cat];
}

async function toggleBrowseCat(pkg: any, cat: string) {
  const state = getCatState(pkg, cat);
  if (!state.expanded) {
    state.expanded = true;
    if (!state.loaded && !state.loading) {
      await browseLoadCatRoot(pkg, cat, state);
    }
  } else {
    state.expanded = false;
  }
}

async function browseLoadCatRoot(pkg: any, cat: string, state: any) {
  const aid = resolveBrowseAdAccountId();
  if (!aid) {
    message.warning(t('请先在第一步选择广告账户'));
    return;
  }
  state.loading = true;
  state.loaded = false;
  try {
    const res: any = await targetingBrowseApi({
      fb_ad_account_id: aid,
      targeting_category: cat as 'demographics' | 'behaviors' | 'interests',
      locale: 'zh_CN',
    });
    const ok = res?.success !== false;
    state.rootNodes = ok && Array.isArray(res?.data) ? res.data : [];
    state.loaded = true;
    if (!ok && res?.message) message.warning(String(res.message));
  } catch (e: any) {
    state.rootNodes = [];
    const msg = e?.response?.data?.message || e?.message;
    if (msg) message.error(String(msg));
  } finally {
    state.loading = false;
  }
}

function toggleBrowseSubNode(pkg: any, cat: string, node: any) {
  if (!node?.key) return;
  const state = getCatState(pkg, cat);
  const k = String(node.key);
  const list = Array.isArray(state.expandedKeys) ? [...state.expandedKeys.map(String)] : [];
  const idx = list.indexOf(k);
  const willExpand = idx < 0;
  if (willExpand) list.push(k);
  else list.splice(idx, 1);
  state.expandedKeys = list;

  if (willExpand) {
    if (!state.children) state.children = {};
    const loaded = Object.prototype.hasOwnProperty.call(state.children, k);
    if (!loaded) void browseLoadSubChildren(pkg, cat, state, k);
  }
}

async function browseLoadSubChildren(pkg: any, cat: string, state: any, parentKey: string) {
  const aid = resolveBrowseAdAccountId();
  if (!aid) return;
  if (!state.loadingKeys) state.loadingKeys = [];
  if (!state.loadingKeys.includes(parentKey)) state.loadingKeys = [...state.loadingKeys, parentKey];
  try {
    const res: any = await targetingBrowseApi({
      fb_ad_account_id: aid,
      targeting_category: cat as 'demographics' | 'behaviors' | 'interests',
      parent_key: parentKey,
      locale: 'zh_CN',
    });
    const ok = res?.success !== false;
    state.children = { ...state.children, [parentKey]: ok && Array.isArray(res?.data) ? res.data : [] };
    if (!ok && res?.message) message.warning(String(res.message));
  } catch (e: any) {
    state.children = { ...state.children, [parentKey]: [] };
    const msg = e?.response?.data?.message || e?.message;
    if (msg) message.error(String(msg));
  } finally {
    state.loadingKeys = (state.loadingKeys || []).filter((x: string) => x !== parentKey);
  }
}

function allBrowseCatVisibleRows(pkg: any) {
  const rows: any[] = [];
  for (const { key: cat } of BROWSE_CATS) {
    const state = getCatState(pkg, cat);
    rows.push({
      key: `cat-${cat}`,
      node: { key: cat, name: getBrowseCatLabel(cat) },
      depth: 0,
      expanded: state.expanded,
      isCatRoot: true,
      hasChildren: true,
      cat,
      catLoading: state.loading,
      catLoaded: state.loaded,
    });
    if (state.expanded) {
      if (state.loading) {
        rows.push({ key: `cat-loading-${cat}`, node: { name: t('加载中...') }, depth: 1, expanded: false, isCatRoot: false, hasChildren: false, cat, catLoading: true });
      } else if (!state.rootNodes?.length) {
        rows.push({ key: `cat-empty-${cat}`, node: { name: t('暂无数据，请刷新或更换账户') }, depth: 1, expanded: false, isCatRoot: false, hasChildren: false, cat, catLoading: false, isEmpty: true });
      } else {
        walkCatNodes(state, state.rootNodes, 1, cat, rows);
      }
    }
  }
  return rows;
}

function walkCatNodes(state: any, nodes: any[], depth: number, cat: string, rows: any[]) {
  for (let i = 0; i < nodes.length; i++) {
    const node = nodes[i];
    const nodeKey = node?.key != null ? String(node.key) : null;
    const hasChildren = nodeKey != null;

    // 顶层（depth=1）只显示父分类节点，过滤掉 API 错误地返回的叶子项
    if (depth === 1 && !hasChildren) continue;

    const expanded = hasChildren ? (state.expandedKeys || []).map(String).includes(nodeKey!) : false;
    const isChildLoading = hasChildren && (state.loadingKeys || []).includes(nodeKey!);
    rows.push({ key: `${cat}-${nodeKey ?? node?.id ?? i}-d${depth}`, node, depth, expanded, isCatRoot: false, hasChildren, cat, catLoading: false, isChildLoading });
    if (hasChildren && expanded) {
      const children = (state.children || {})[nodeKey!];
      if (isChildLoading) {
        rows.push({ key: `child-loading-${nodeKey}-d${depth}`, node: { name: t('加载中...') }, depth: depth + 1, expanded: false, isCatRoot: false, hasChildren: false, cat, catLoading: true });
      } else if (Array.isArray(children) && children.length) {
        walkCatNodes(state, children, depth + 1, cat, rows);
      } else if (Array.isArray(children) && !children.length) {
        rows.push({ key: `child-empty-${nodeKey}-d${depth}`, node: { name: t('暂无子项') }, depth: depth + 1, expanded: false, isCatRoot: false, hasChildren: false, cat, catLoading: false, isEmpty: true });
      }
    }
  }
}

/** 强制刷新某个分类的 API 数据 */
async function refreshBrowseCat(pkg: any, cat: string) {
  const state = getCatState(pkg, cat);
  state.loaded = false;
  state.rootNodes = [];
  state.children = {};
  state.expandedKeys = [];
  state.loadingKeys = [];
  await browseLoadCatRoot(pkg, cat, state);
}

/** 格式化节点规模信息 */
function getNodeAudienceSize(node: any): string {
  const lower =
    node?.audience_size_lower_bound ??
    node?.audienceSizeLowerBound ??
    node?.size_lower_bound ??
    node?.lower_bound;
  const upper =
    node?.audience_size_upper_bound ??
    node?.audienceSizeUpperBound ??
    node?.size_upper_bound ??
    node?.upper_bound;
  if (lower == null && upper == null) return '';
  const fmt = (n: number) => Number(n).toLocaleString('en-US');
  if (lower && upper) return `${fmt(Number(lower))} - ${fmt(Number(upper))}`;
  if (lower) return `${fmt(Number(lower))}+`;
  return '';
}
const props = defineProps<{
  formData: any;
  /** 地区步骤默认值；开启「单独地区」时用于预填 */
  stepRegionDefaults?: Record<string, any>;
  /** 九步「账户」所选广告账户（可多选）；用于 targetingbrowse（解析为 act_） */
  adAccountId?: string | string[] | null;
}>();

const browseCategoryOptions = [
  { label: t('人口统计数据'), value: 'demographics' },
  { label: t('行为'), value: 'behaviors' },
  { label: t('兴趣'), value: 'interests' },
];

/** 折叠：绑定与地区，默认收起以贴近参考界面主流程 */
const advancedCollapseKeys = ref<string[]>([]);
const emit = defineEmits<{ (e: 'update:form-data', v: any): void }>();

const MAX_PKGS = 20;

const adAccountIdList = computed<string[]>(() => {
  const v: any = props.adAccountId as any;
  if (Array.isArray(v)) return v.map(String).filter(Boolean);
  if (typeof v === 'string' && v) return [v];
  return [];
});
const activeBrowseAdAccountId = ref<string | null>(adAccountIdList.value[0] ?? null);

watch(
  adAccountIdList,
  (list) => {
    if (!list.length) {
      activeBrowseAdAccountId.value = null;
      return;
    }
    if (!activeBrowseAdAccountId.value || !list.includes(activeBrowseAdAccountId.value)) {
      activeBrowseAdAccountId.value = list[0] ?? null;
    }
  },
  { immediate: true },
);

function resolveBrowseAdAccountId(): string | null {
  return activeBrowseAdAccountId.value ?? adAccountIdList.value[0] ?? null;
}

function genPkgId() {
  return `pkg-${Date.now().toString(36)}-${Math.random().toString(36).slice(2, 9)}`;
}

function cloneRegionFromDefaults(src: Record<string, any> | undefined) {
  const g = src && typeof src === 'object' ? src : {};
  return {
    regionGroupName: '',
    useExisting: false,
    regionGroupId: null as string | null,
    regionSearch: '',
    tab: 'target' as 'target' | 'exclude',
    countries_included: Array.isArray(g.countries_included) ? [...g.countries_included] : [],
    countries_excluded: Array.isArray(g.countries_excluded) ? [...g.countries_excluded] : [],
    regions_included: Array.isArray(g.regions_included) ? [...g.regions_included] : [],
    regions_excluded: Array.isArray(g.regions_excluded) ? [...g.regions_excluded] : [],
    cities_included: Array.isArray(g.cities_included) ? [...g.cities_included] : [],
    cities_excluded: Array.isArray(g.cities_excluded) ? [...g.cities_excluded] : [],
    financialRegion: g.financialRegion,
    benefitTw: g.benefitTw,
    sponsorTw: g.sponsorTw,
    benefitAu: g.benefitAu,
    sponsorAu: g.sponsorAu,
    benefitSg: g.benefitSg,
    sponsorSg: g.sponsorSg,
  };
}

function cloneStepRegionFromRaw(r: Record<string, any>) {
  const accIds = r.fb_ad_account_ids ?? r.fbAdAccountIds;
  return {
    ...r,
    fb_ad_account_ids: Array.isArray(accIds) ? [...accIds] : [],
    countries_included: Array.isArray(r.countries_included) ? [...r.countries_included] : [],
    countries_excluded: Array.isArray(r.countries_excluded) ? [...r.countries_excluded] : [],
    regions_included: Array.isArray(r.regions_included) ? [...r.regions_included] : [],
    regions_excluded: Array.isArray(r.regions_excluded) ? [...r.regions_excluded] : [],
    cities_included: Array.isArray(r.cities_included) ? [...r.cities_included] : [],
    cities_excluded: Array.isArray(r.cities_excluded) ? [...r.cities_excluded] : [],
  };
}

function defaultPackageBody(seq: number) {
  return {
    id: genPkgId(),
    name: `${t('定向包')}${seq}`,
    cardOpen: true,
    useCustomRegion: false,
    useExisting: false,
    advancedAudience: false,
    customAudience: 'unlimited' as 'unlimited' | 'custom',
    customAudienceTab: 'custom' as 'all' | 'lookalike' | 'custom',
    customAudienceSearch: '',
    customAudienceSelectedTab: 'include' as 'include' | 'exclude',
    customAudienceInclude: [] as { id: string; name: string }[],
    customAudienceExclude: [] as { id: string; name: string }[],
    minAge: 18,
    ageFrom: 18,
    ageTo: 65,
    gender: 'all' as 'all' | 'male' | 'female',
    detailedTargeting: 'unlimited' as 'unlimited' | 'custom',
    enableDetailedExpansion: false,
    locales: [] as { key: number; name: string }[],
    interests: [] as { id: number; name: string }[],
    /** 细分定位子 Tab：浏览 | 建议 | 操作 */
    detailedInterestSubTab: 'browse' as 'browse' | 'suggest' | 'operation',
    /** 浏览列表本地筛选 */
    detailBrowseSearch: '',
    browseCategory: 'demographics' as 'demographics' | 'behaviors' | 'interests',
    browseRows: [] as Record<string, unknown>[],
    browseLoading: false,
    browseParentKey: null as string | null,
    browseParentStack: [] as { key: string | null; label: string }[],
    /** true：显示人口统计/行为/兴趣三个入口；false：已进入某类或子级列表 */
    browseShowRootCats: true,
    /** 真·树状结构：根节点 + 各节点子节点（按 parent_key 拉取后缓存） */
    browseTreeRootNodes: [] as any[],
    browseTreeChildren: {} as Record<string, any[]>,
    browseTreeExpandedKeys: [] as string[],
    browseTreeLoadingKeys: [] as string[],
    browseTreeRootLoading: false,
    browseTreeRootLoaded: false,
    /** 浏览 Tab 内：关键词走 targetingsearch 的结果（与分类树互斥展示） */
    browseMetaSearchRows: [] as { id: number; name: string; type?: string }[],
    browseMetaSearchLoading: false,
    /** 仅 UI：搜索兴趣候选，不写入草稿 */
    interestSearchInput: '',
    interestSearchResults: [] as { id: number; name: string }[],
    interestSearchLoading: false,
    languages: [] as string[],
    languageSearch: '',
    tags: [] as string[],
    targetInstalled: false,
    devices: ['mobile', 'desktop'] as string[],
    mobileDeviceMode: 'all_mobile' as 'all_mobile' | 'android_only' | 'ios_only' | 'feature_phone',
    includedDevices: [] as string[],
    excludedDevices: [] as string[],
    osVersionMin: undefined as string | undefined,
    osVersionMax: undefined as string | undefined,
    osVersionText: '',
    bindTargetType: 'region' as 'region' | 'account',
    bindRule: 'basic' as const,
    boundItems: [] as string[],
    wifiOnly: false,
  };
}

function mergePackage(raw: any, index: number) {
  const d = defaultPackageBody(index + 1);
  if (!raw || typeof raw !== 'object') return d;
  const useCustom = raw.useCustomRegion === true;
  return {
    ...d,
    ...raw,
    id: raw.id || genPkgId(),
    // 空字符串是合法输入，勿用 || 否则会被默认名覆盖
    name: String(raw.name ?? d.name),
    cardOpen: raw.cardOpen !== false,
    useCustomRegion: useCustom,
    stepRegion:
      useCustom && raw.stepRegion && typeof raw.stepRegion === 'object'
        ? cloneStepRegionFromRaw(raw.stepRegion)
        : undefined,
    locales: Array.isArray(raw.locales) ? [...raw.locales] : [...d.locales],
    interests: Array.isArray(raw.interests)
      ? raw.interests
          .map((x: any) => ({
            id: Number(x?.id ?? x?.key),
            name: String(x?.name ?? x?.label ?? '').trim(),
          }))
          .filter((x: any) => x.id && x.name)
      : [...d.interests],
    interestSearchInput: '',
    interestSearchResults: [] as { id: number; name: string }[],
    interestSearchLoading: false,
    detailedInterestSubTab:
      raw.detailedInterestSubTab === 'browse' ||
      raw.detailedInterestSubTab === 'suggest' ||
      raw.detailedInterestSubTab === 'operation'
        ? raw.detailedInterestSubTab
        : 'browse',
    detailBrowseSearch: '',
    browseCategory: ['demographics', 'behaviors', 'interests'].includes(raw.browseCategory)
      ? raw.browseCategory
      : 'demographics',
    browseRows: [],
    browseLoading: false,
    browseParentKey: null,
    browseParentStack: [],
    browseShowRootCats: true,
    browseTreeRootNodes: [],
    browseTreeChildren: {},
    browseTreeExpandedKeys: [],
    browseTreeLoadingKeys: [],
    browseTreeRootLoading: false,
    browseTreeRootLoaded: false,
    browseMetaSearchRows: [],
    browseMetaSearchLoading: false,
    customAudienceTab: raw.customAudienceTab === 'all' || raw.customAudienceTab === 'lookalike' ? raw.customAudienceTab : 'custom',
    customAudienceSearch: String(raw.customAudienceSearch ?? ''),
    customAudienceSelectedTab: raw.customAudienceSelectedTab === 'exclude' ? 'exclude' : 'include',
    customAudienceInclude: Array.isArray(raw.customAudienceInclude) ? raw.customAudienceInclude : [...d.customAudienceInclude],
    customAudienceExclude: Array.isArray(raw.customAudienceExclude) ? raw.customAudienceExclude : [...d.customAudienceExclude],
    languages: Array.isArray(raw.languages) ? raw.languages.map(String) : [...d.languages],
    languageSearch: String(raw.languageSearch ?? ''),
    tags: Array.isArray(raw.tags) ? raw.tags.map(String) : [...d.tags],
    devices:
      Array.isArray(raw.devices) && raw.devices.length ? [...raw.devices] : [...d.devices],
    includedDevices: Array.isArray(raw.includedDevices) ? [...raw.includedDevices] : [...d.includedDevices],
    excludedDevices: Array.isArray(raw.excludedDevices) ? [...raw.excludedDevices] : [...d.excludedDevices],
  };
}

function normalizePackagesFromForm(fd: any): any[] {
  if (fd && Array.isArray(fd.packages) && fd.packages.length > 0) {
    return fd.packages.map((p: any, i: number) => mergePackage(p, i));
  }
  if (fd && typeof fd === 'object' && !fd.packages) {
    return [mergePackage(fd, 0)];
  }
  return [defaultPackageBody(1)];
}

const packages = ref<any[]>(normalizePackagesFromForm(props.formData));
const activeTabKey = ref<string>(packages.value[0]?.id ?? '');
const syncingFromParent = ref(false);
const lastPersistSig = ref<string>('');

function safeClone<T>(v: T): T {
  return JSON.parse(JSON.stringify(toRaw(v)));
}

function tabDisplayName(pkg: any, index: number) {
  const n = String(pkg?.name || '').trim();
  const label = n || `${t('定向包')}${index + 1}`;
  return label.length > 16 ? `${label.slice(0, 16)}…` : label;
}

function onTabsEdit(targetKey: string | MouseEvent, action: 'add' | 'remove') {
  if (action === 'add') {
    addPackage();
    return;
  }
  if (action === 'remove') {
    const key = typeof targetKey === 'string' ? targetKey : String(targetKey);
    const idx = packages.value.findIndex((p) => p.id === key);
    if (idx >= 0) removePackage(idx);
  }
}

const ageOptionsWithPlus = [13, 18, 21, 25, 35, 45, 55, 65].map((v) => ({
  label: v === 65 ? '65+' : String(v),
  value: v,
}));

const bindRuleModalVisible = ref(false);
const bindRuleEditingPkgId = ref<string>('');
const bindRuleDraft = ref({
  bindTargetType: 'region' as 'region' | 'account',
  bindRule: 'basic' as const,
  boundItems: [] as string[],
});
const bindRuleColumns = [
  { title: t('定向包'), dataIndex: 'name', key: 'name', width: 180 },
  { title: t('绑定对象'), key: 'target' },
];
const bindRuleRows = ref<{ key: string; name: string }[]>([]);
const bindRuleTargetOptions = ref<{ label: string; value: string }[]>([
  { label: t('全选'), value: 'all' },
  { label: t('地区组1'), value: 'region-group-1' },
  { label: t('地区组2'), value: 'region-group-2' },
]);

const savedPkgModalVisible = ref(false);
const savedPkgEditingId = ref<string>('');
const savedPkgKeyword = ref('');
const savedPkgTag = ref<string | undefined>(undefined);
const savedPkgColumns = [
  { title: t('定向包名称'), dataIndex: 'name', key: 'name' },
  { title: t('操作'), key: 'action', width: 100 },
  { title: t('标签'), dataIndex: 'tag', key: 'tag', width: 120 },
  { title: t('创建时间'), dataIndex: 'createdAtText', key: 'createdAtText', width: 180 },
];
const savedPkgRows = ref<any[]>([]);
const savedPkgPagination = { pageSize: 20, showSizeChanger: true };

const mockAudienceRows = [
  { id: 'aud-1', name: t('受众示例 1') },
  { id: 'aud-2', name: t('受众示例 2') },
  { id: 'aud-3', name: t('受众示例 3') },
];
const languageOptions = [
  { label: t('英语（美国）'), value: 'en_US' },
  { label: t('加泰罗尼亚语'), value: 'ca_ES' },
  { label: t('捷克语'), value: 'cs_CZ' },
  { label: t('荷兰语'), value: 'nl_NL' },
  { label: t('威尔士语'), value: 'cy_GB' },
  { label: t('丹麦语'), value: 'da_DK' },
  { label: t('德语'), value: 'de_DE' },
  { label: t('法语'), value: 'fr_FR' },
  { label: t('西班牙语'), value: 'es_ES' },
];
const languageLabelMap: Record<string, string> = Object.fromEntries(languageOptions.map((x) => [x.value, x.label]));

function filteredLanguageOptions(pkg: any) {
  const q = String(pkg?.languageSearch ?? '').trim().toLowerCase();
  if (!q) return languageOptions;
  return languageOptions.filter((x) => String(x.label).toLowerCase().includes(q));
}

function toggleLang(pkg: any, value: string) {
  const list = Array.isArray(pkg.languages) ? [...pkg.languages] : [];
  const i = list.indexOf(value);
  if (i >= 0) list.splice(i, 1);
  else list.push(value);
  pkg.languages = list;
}

function removeLang(pkg: any, value: string) {
  pkg.languages = (pkg.languages || []).filter((x: string) => x !== value);
}
const tagOptions = [
  { label: t('高价值用户'), value: 'high_value' },
  { label: t('广泛定向'), value: 'broad' },
  { label: t('测试包'), value: 'test' },
];

/** 按定向包 id 防抖，避免多 Tab 共用全局搜索状态 */
const interestSearchTimers = new Map<string, ReturnType<typeof setTimeout>>();
const browseMetaSearchTimers = new Map<string, ReturnType<typeof setTimeout>>();

function isBrowseMetaSearchActive(pkg: any) {
  return String(pkg.detailBrowseSearch ?? '').trim().length >= 2;
}

function onDetailBrowseSearchInput(pkg: any) {
  const id = pkg.id as string;
  const prev = browseMetaSearchTimers.get(id);
  if (prev) clearTimeout(prev);
  const t = setTimeout(() => void runBrowseMetaSearch(pkg), 380);
  browseMetaSearchTimers.set(id, t);
}

async function runBrowseMetaSearch(pkg: any) {
  const q = String(pkg.detailBrowseSearch ?? '').trim();
  if (q.length < 2) {
    pkg.browseMetaSearchRows = [];
    pkg.browseMetaSearchLoading = false;
    return;
  }
  const aid = resolveBrowseAdAccountId();
  if (!aid) {
    pkg.browseMetaSearchRows = [];
    return;
  }
  pkg.browseMetaSearchLoading = true;
  try {
    const res: any = await targetingSearchDetailedApi({
      fb_ad_account_id: aid,
      q,
      locale: 'zh_CN',
    });
    const ok = res?.success !== false;
    const raw = ok && Array.isArray(res?.data) ? res.data : [];
    pkg.browseMetaSearchRows = raw
      .map((r: any) => {
        const idNum = Number(r.id ?? r.key);
        return {
          id: idNum,
          name: String(r.name ?? '').trim(),
          type: r.type != null ? String(r.type) : undefined,
        };
      })
      .filter((r: { id: number; name: string }) => r.id && r.name);
    if (!ok && res?.message) {
      message.warning(String(res.message));
    }
  } catch (e: any) {
    pkg.browseMetaSearchRows = [];
    const msg = e?.response?.data?.message || e?.message;
    if (msg) message.error(String(msg));
  } finally {
    pkg.browseMetaSearchLoading = false;
  }
}

function onDetailedInterestSubTabChange(pkg: any, key: string) {
  const k = ['browse', 'suggest', 'operation'].includes(key) ? key : 'browse';
  pkg.detailedInterestSubTab = k as 'browse' | 'suggest' | 'operation';
  if (k === 'browse' && !pkg.browseShowRootCats && !pkg.browseTreeRootLoaded && !pkg.browseTreeRootLoading) {
    // 已选大类但树未加载时，补一次根节点
    browseTreeLoadRoot(pkg);
  }
}

function onPickRootCategory(pkg: any, value: string) {
  if (!['demographics', 'behaviors', 'interests'].includes(value)) return;
  if (!resolveBrowseAdAccountId()) {
    message.warning(t('请先在第一步选择广告账户'));
    return;
  }
  pkg.browseCategory = value as 'demographics' | 'behaviors' | 'interests';
  pkg.detailBrowseSearch = '';
  pkg.browseShowRootCats = false;
  // 初始化真·树状结构
  pkg.browseTreeExpandedKeys = [];
  pkg.browseTreeRootNodes = [];
  pkg.browseTreeChildren = {};
  pkg.browseTreeLoadingKeys = [];
  pkg.browseTreeRootLoaded = false;
  browseTreeLoadRoot(pkg);
}

function browseTreeStoreKey(pkg: any, parentKey: string | null) {
  const pk = parentKey && String(parentKey).trim().length ? String(parentKey) : 'root';
  return `${pkg.browseCategory}::${pk}`;
}

function browseTreeVisibleRows(pkg: any) {
  const expandedSet = new Set<string>((pkg.browseTreeExpandedKeys || []).map((x: any) => String(x)));
  const roots = Array.isArray(pkg.browseTreeRootNodes) ? pkg.browseTreeRootNodes : [];
  const childrenMap = pkg.browseTreeChildren || {};

  const out: Array<{ key: string; node: any; depth: number; expanded: boolean }> = [];

  const walk = (nodes: any[], depth: number) => {
    if (!Array.isArray(nodes)) return;
    for (let i = 0; i < nodes.length; i += 1) {
      const node = nodes[i];
      const nodeKey = node?.key != null ? String(node.key) : node?.id != null ? `id:${node.id}` : `idx:${i}`;
      const expanded = node?.key != null ? expandedSet.has(String(node.key)) : false;
      const rowKey = `${nodeKey}::${depth}::${i}`;
      out.push({ key: rowKey, node, depth, expanded });

      if (node?.key != null && expanded) {
        const storeKey = browseTreeStoreKey(pkg, String(node.key));
        const children = Array.isArray(childrenMap[storeKey]) ? childrenMap[storeKey] : [];
        if (children.length) walk(children, depth + 1);
      }
    }
  };

  walk(roots, 0);
  return out;
}

function toggleBrowseTreeNode(pkg: any, node: any) {
  if (!node?.key) return;
  const k = String(node.key);
  const list = Array.isArray(pkg.browseTreeExpandedKeys) ? [...pkg.browseTreeExpandedKeys].map(String) : [];
  const idx = list.indexOf(k);
  const willExpand = idx < 0;
  if (willExpand) list.push(k);
  else list.splice(idx, 1);
  pkg.browseTreeExpandedKeys = list;

  if (willExpand) {
    pkg.browseTreeChildren = pkg.browseTreeChildren || {};
    const storeKey = browseTreeStoreKey(pkg, k);
    const loaded = Object.prototype.hasOwnProperty.call(pkg.browseTreeChildren, storeKey);
    if (!loaded) void browseTreeLoadChildren(pkg, k);
  }
}

function refreshBrowseTree(pkg: any) {
  pkg.browseTreeExpandedKeys = [];
  pkg.browseTreeChildren = {};
  pkg.browseTreeRootNodes = [];
  pkg.browseTreeRootLoaded = false;
  void browseTreeLoadRoot(pkg);
}

async function browseTreeLoadRoot(pkg: any) {
  const aid = resolveBrowseAdAccountId();
  if (!aid) {
    message.warning(t('请先在第一步选择广告账户'));
    return;
  }
  pkg.browseTreeRootLoading = true;
  pkg.browseTreeRootLoaded = false;
  pkg.browseTreeExpandedKeys = [];
  pkg.browseTreeChildren = {};
  try {
    const res: any = await targetingBrowseApi({
      fb_ad_account_id: aid,
      targeting_category: pkg.browseCategory as 'demographics' | 'behaviors' | 'interests',
      locale: 'zh_CN',
    });
    const ok = res?.success !== false;
    pkg.browseTreeRootNodes = ok && Array.isArray(res?.data) ? res.data : [];
    pkg.browseTreeRootLoaded = true;
    if (!ok && res?.message) message.warning(String(res.message));
  } catch (e: any) {
    pkg.browseTreeRootNodes = [];
    const msg = e?.response?.data?.message || e?.message;
    if (msg) message.error(String(msg));
  } finally {
    pkg.browseTreeRootLoading = false;
  }
}

async function browseTreeLoadChildren(pkg: any, parentKey: string) {
  const aid = resolveBrowseAdAccountId();
  if (!aid) return;
  pkg.browseTreeChildren = pkg.browseTreeChildren || {};
  const storeKey = browseTreeStoreKey(pkg, parentKey);
  const loaded = Object.prototype.hasOwnProperty.call(pkg.browseTreeChildren, storeKey);
  if (loaded) return;

  const loadingList = Array.isArray(pkg.browseTreeLoadingKeys) ? [...pkg.browseTreeLoadingKeys].map(String) : [];
  if (!loadingList.includes(parentKey)) loadingList.push(parentKey);
  pkg.browseTreeLoadingKeys = loadingList;
  try {
    const res: any = await targetingBrowseApi({
      fb_ad_account_id: aid,
      targeting_category: pkg.browseCategory as 'demographics' | 'behaviors' | 'interests',
      parent_key: parentKey,
      locale: 'zh_CN',
    });
    const ok = res?.success !== false;
    pkg.browseTreeChildren[storeKey] = ok && Array.isArray(res?.data) ? res.data : [];
    if (!ok && res?.message) message.warning(String(res.message));
  } catch (e: any) {
    pkg.browseTreeChildren[storeKey] = [];
    const msg = e?.response?.data?.message || e?.message;
    if (msg) message.error(String(msg));
  } finally {
    pkg.browseTreeLoadingKeys = (pkg.browseTreeLoadingKeys || []).filter((x: any) => String(x) !== parentKey);
  }
}

function onDetailedTargetingChange(pkg: any, v: 'unlimited' | 'custom') {
  pkg.detailedTargeting = v;
  if (v === 'unlimited') {
    pkg.interests = [];
    pkg.interestSearchInput = '';
    pkg.interestSearchResults = [];
    pkg.interestSearchLoading = false;
    pkg.detailedInterestSubTab = 'browse';
    pkg.detailBrowseSearch = '';
    pkg.browseRows = [];
    pkg.browseLoading = false;
    pkg.browseParentKey = null;
    pkg.browseParentStack = [];
    pkg.browseShowRootCats = true;
    pkg.browseTreeRootNodes = [];
    pkg.browseTreeChildren = {};
    pkg.browseTreeExpandedKeys = [];
    pkg.browseTreeLoadingKeys = [];
    pkg.browseTreeRootLoaded = false;
    pkg.browseTreeRootLoading = false;
    pkg.browseMetaSearchRows = [];
    pkg.browseMetaSearchLoading = false;
  } else {
    nextTick(() => {
      if (
        pkg.detailedInterestSubTab === 'browse' &&
        !pkg.browseShowRootCats &&
        !pkg.browseTreeRootLoaded &&
        !pkg.browseTreeRootLoading
      ) {
        browseTreeLoadRoot(pkg);
      }
    });
  }
}

function runInterestSearch(pkg: any, q?: string) {
  const keyword = String(q ?? pkg.interestSearchInput ?? '').trim();
  const id = pkg.id as string;
  const prev = interestSearchTimers.get(id);
  if (prev) clearTimeout(prev);
  const t = setTimeout(() => void fetchInterestCandidates(pkg, keyword), 280);
  interestSearchTimers.set(id, t);
}

async function fetchInterestCandidates(pkg: any, keyword: string) {
  if (!keyword) {
    pkg.interestSearchResults = [];
    pkg.interestSearchLoading = false;
    return;
  }
  pkg.interestSearchLoading = true;
  try {
    const aid = resolveBrowseAdAccountId();
    if (aid) {
      const res: any = await targetingSearchDetailedApi({
        fb_ad_account_id: aid,
        q: keyword,
        locale: 'zh_CN',
      });
      const ok = res?.success !== false;
      const raw = ok && Array.isArray(res?.data) ? res.data : [];
      pkg.interestSearchResults = raw
        .map((r: any) => ({
          id: Number(r.id ?? r.key),
          name: String(r.name ?? '').trim(),
        }))
        .filter((r: { id: number; name: string }) => r.id && r.name);
      if (!ok && res?.message) {
        message.warning(String(res.message));
      }
    } else {
      const res: any = await searchInterests(keyword);
      const rows = Array.isArray(res) ? res : res?.data ?? [];
      pkg.interestSearchResults = rows
        .map((r: any) => ({
          id: Number(r.id ?? r.key),
          name: String(r.name ?? '').trim(),
        }))
        .filter((r: { id: number; name: string }) => r.id && r.name);
    }
  } catch {
    pkg.interestSearchResults = [];
  } finally {
    pkg.interestSearchLoading = false;
  }
}

function isInterestSelected(pkg: any, id: number) {
  return (pkg.interests || []).some((x: any) => Number(x.id) === Number(id));
}

function addInterestToPkg(pkg: any, row: { id: number; name: string }) {
  if (isInterestSelected(pkg, row.id)) return;
  const list = Array.isArray(pkg.interests) ? [...pkg.interests] : [];
  list.push({ id: Number(row.id), name: String(row.name) });
  pkg.interests = list;
}

function removeInterestFromPkg(pkg: any, id: number) {
  pkg.interests = (pkg.interests || []).filter((x: any) => Number(x.id) !== Number(id));
}

function clearInterests(pkg: any) {
  pkg.interests = [];
}

function addPackage() {
  if (packages.value.length >= MAX_PKGS) {
    message.warning(t('最多 20 个定向包'));
    return;
  }
  const n = packages.value.length + 1;
  const np = defaultPackageBody(n);
  packages.value.push(np);
  activeTabKey.value = np.id;
}

function onToggleCustomRegion(pkg: any) {
  if (pkg.useCustomRegion) {
    if (!pkg.stepRegion || typeof pkg.stepRegion !== 'object') {
      pkg.stepRegion = cloneRegionFromDefaults(props.stepRegionDefaults);
    }
  } else {
    pkg.stepRegion = undefined;
  }
}

function onPackageStepRegionUpdate(pkg: any, v: Record<string, any>) {
  pkg.stepRegion = { ...v };
}

function duplicateCurrentGroup() {
  const idx = packages.value.findIndex((p) => p.id === activeTabKey.value);
  if (idx >= 0) duplicatePackage(idx);
}

function duplicatePackage(index: number) {
  if (packages.value.length >= MAX_PKGS) {
    message.warning(t('最多 20 个定向包'));
    return;
  }
  const src = packages.value[index];
  const copy = safeClone(src);
  copy.id = genPkgId();
  copy.name = `${src.name}-${t('副本')}`;
  copy.cardOpen = true;
  if (copy.useCustomRegion && copy.stepRegion && typeof copy.stepRegion === 'object') {
    copy.stepRegion = cloneStepRegionFromRaw(copy.stepRegion);
  }
  copy.interestSearchInput = '';
  copy.interestSearchResults = [];
  copy.interestSearchLoading = false;
  copy.detailedInterestSubTab = 'browse';
  copy.detailBrowseSearch = '';
  copy.browseRows = [];
  copy.browseLoading = false;
  copy.browseParentKey = null;
  copy.browseParentStack = [];
  copy.browseShowRootCats = true;
  copy.browseTreeRootNodes = [];
  copy.browseTreeChildren = {};
  copy.browseTreeExpandedKeys = [];
  copy.browseTreeLoadingKeys = [];
  copy.browseTreeRootLoading = false;
  copy.browseTreeRootLoaded = false;
  copy.browseMetaSearchRows = [];
  copy.browseMetaSearchLoading = false;
  packages.value.splice(index + 1, 0, copy);
  activeTabKey.value = copy.id;
}

function removePackage(index: number) {
  if (packages.value.length <= 1) return;
  const removedId = packages.value[index].id;
  packages.value.splice(index, 1);
  if (activeTabKey.value === removedId) {
    const next = packages.value[Math.min(index, packages.value.length - 1)];
    activeTabKey.value = next?.id ?? '';
  }
}

function clearPackage(index: number) {
  const seq = index + 1;
  const id = packages.value[index]?.id;
  packages.value[index] = defaultPackageBody(seq);
  if (id) {
    packages.value[index].id = id;
  }
  message.success(t('已清空'));
}

/** 顶部工具栏「清空」：重置当前 Tab 对应定向包 */
function clearActivePackage() {
  const idx = packages.value.findIndex((p) => p.id === activeTabKey.value);
  if (idx >= 0) clearPackage(idx);
}

function resetToSingle() {
  const np = defaultPackageBody(1);
  packages.value = [np];
  activeTabKey.value = np.id;
  message.success(t('已重置为单个定向包'));
}

function onPkgMenu(info: { key: string | number }, index: number) {
  const key = String(info.key);
  if (key === 'duplicate') duplicatePackage(index);
  else if (key === 'clear') clearPackage(index);
  else if (key === 'remove') removePackage(index);
}

function onBatchMenu(info: { key: string | number }) {
  if (String(info.key) === 'reset') resetToSingle();
}

function onToggleUseExisting(pkg: any) {
  if (pkg.useExisting) {
    openSavedPkgModal(pkg);
  }
}

function bindObjectSummary(pkg: any) {
  const typeText = pkg.bindTargetType === 'account' ? t('广告账户') : t('地区');
  const count = Array.isArray(pkg.boundItems) ? pkg.boundItems.length : 0;
  return count > 0 ? `${typeText}（${count}${t('项')}）` : `${typeText}（${t('全部')}）`;
}

function openBindRuleModal(pkg: any) {
  bindRuleEditingPkgId.value = pkg.id;
  bindRuleDraft.value = {
    bindTargetType: pkg.bindTargetType || 'region',
    bindRule: pkg.bindRule || 'basic',
    boundItems: Array.isArray(pkg.boundItems) ? [...pkg.boundItems] : [],
  };
  bindRuleRows.value = [{ key: pkg.id, name: pkg.name || t('定向包') }];
  bindRuleModalVisible.value = true;
}

function confirmBindRuleModal() {
  const pkg = packages.value.find((x) => x.id === bindRuleEditingPkgId.value);
  if (!pkg) {
    bindRuleModalVisible.value = false;
    return;
  }
  pkg.bindTargetType = bindRuleDraft.value.bindTargetType;
  pkg.bindRule = bindRuleDraft.value.bindRule;
  pkg.boundItems = [...bindRuleDraft.value.boundItems];
  bindRuleModalVisible.value = false;
}

function openSavedPkgModal(pkg: any) {
  savedPkgEditingId.value = pkg.id;
  savedPkgRows.value = packages.value
    .filter((x) => x.id !== pkg.id)
    .map((x) => ({
      ...safeClone(x),
      createdAtText: '-',
      tag: '-',
    }));
  savedPkgModalVisible.value = true;
}

function applySavedPackage(record: any) {
  const idx = packages.value.findIndex((x) => x.id === savedPkgEditingId.value);
  if (idx < 0) return;
  const merged = mergePackage(record, idx);
  merged.id = packages.value[idx].id;
  packages.value[idx] = merged;
  savedPkgModalVisible.value = false;
  message.success(t('已载入定向包'));
}

function saveAsTargetingPackage(pkg: any) {
  if (!String(pkg?.name ?? '').trim()) {
    message.warning(t('请先填写定向包名称'));
    return;
  }
  message.success(t('定向包已保存'));
}

function deviceModelOptions(mode: string) {
  if (mode === 'android_only') {
    return [
      { label: 'Android Smartphones (all)', value: 'android_smartphones_all' },
      { label: 'Android Tablets (all)', value: 'android_tablets_all' },
      { label: 'Oneplus 10R', value: 'oneplus_10r' },
    ];
  }
  if (mode === 'ios_only') {
    return [
      { label: 'iPads (all)', value: 'ipads_all' },
      { label: 'iPhones (all)', value: 'iphones_all' },
      { label: 'iPods (all)', value: 'ipods_all' },
    ];
  }
  return [];
}

function filteredAudienceRows(pkg: any) {
  const q = String(pkg.customAudienceSearch || '').trim().toLowerCase();
  const base = mockAudienceRows;
  if (!q) return base;
  return base.filter((x) => String(x.name).toLowerCase().includes(q));
}

function addAudience(pkg: any, kind: 'include' | 'exclude', row: { id: string; name: string }) {
  const include = Array.isArray(pkg.customAudienceInclude) ? pkg.customAudienceInclude : [];
  const exclude = Array.isArray(pkg.customAudienceExclude) ? pkg.customAudienceExclude : [];
  if (kind === 'include') {
    if (!include.find((x: any) => x.id === row.id)) include.push({ ...row });
    pkg.customAudienceInclude = include;
    pkg.customAudienceExclude = exclude.filter((x: any) => x.id !== row.id);
    return;
  }
  if (!exclude.find((x: any) => x.id === row.id)) exclude.push({ ...row });
  pkg.customAudienceExclude = exclude;
  pkg.customAudienceInclude = include.filter((x: any) => x.id !== row.id);
}

function removeAudience(pkg: any, kind: 'include' | 'exclude', id: string) {
  if (kind === 'include') {
    pkg.customAudienceInclude = (pkg.customAudienceInclude || []).filter((x: any) => x.id !== id);
    return;
  }
  pkg.customAudienceExclude = (pkg.customAudienceExclude || []).filter((x: any) => x.id !== id);
}

function clearSelectedAudiences(pkg: any) {
  pkg.customAudienceInclude = [];
  pkg.customAudienceExclude = [];
}

watch(
  () => props.formData,
  (v) => {
    syncingFromParent.value = true;
    packages.value = normalizePackagesFromForm(v);
    nextTick(() => {
      syncingFromParent.value = false;
      // 同步一次持久化签名，避免回灌后下一次 UI 操作误触发重复 emit
      try {
        const persist = packages.value.map((p) => packageForPersist({ ...p }));
        lastPersistSig.value = JSON.stringify(persist);
      } catch {
        // ignore
      }
      const ids = new Set(packages.value.map((p) => p.id));
      if (!activeTabKey.value || !ids.has(activeTabKey.value)) {
        activeTabKey.value = packages.value[0]?.id ?? '';
      }
    });
  },
  { deep: true },
);

function packageForPersist(p: Record<string, unknown>) {
  const {
    interestSearchInput,
    interestSearchResults,
    interestSearchLoading,
    detailedInterestSubTab,
    browseCategory,
    browseRows,
    browseLoading,
    browseParentKey,
    browseParentStack,
    detailBrowseSearch,
    browseShowRootCats,
    browseTreeRootNodes,
    browseTreeChildren,
    browseTreeExpandedKeys,
    browseTreeLoadingKeys,
    browseTreeRootLoading,
    browseTreeRootLoaded,
    browseMetaSearchRows,
    browseMetaSearchLoading,
    ...rest
  } = p;
  return rest;
}

watch(
  packages,
  (list) => {
    if (syncingFromParent.value) return;
    const persist = list.map((p) => packageForPersist({ ...p }));
    const sig = JSON.stringify(persist);
    // 防止仅 UI 字段变化（如切换「建议/操作」Tab）导致 emit → 父组件回灌 → UI 状态被重置
    if (sig === lastPersistSig.value) return;
    lastPersistSig.value = sig;
    emit('update:form-data', { packages: persist });
  },
  { deep: true },
);
</script>

<style lang="less" scoped>
.pkg-page-head {
  display: flex;
  align-items: center;
  justify-content: space-between;
  flex-wrap: wrap;
  gap: 8px;
  margin-bottom: 4px;
}
.section-title {
  font-size: 16px;
  font-weight: 500;
  margin-bottom: 0;
  color: #262626;
}
.section-hint {
  margin: 0 0 16px;
  font-size: 13px;
  color: #8c8c8c;
  line-height: 1.5;
}
.toolbar {
  display: flex;
  align-items: center;
  gap: 12px;
  margin-bottom: 0;
}
.pkg-count {
  font-size: 13px;
  color: #8c8c8c;
}
.targeting-tabs {
  :deep(.ant-tabs-nav) {
    margin-bottom: 0;
  }
  :deep(.ant-tabs-content) {
    padding-top: 16px;
  }
}
.tab-title-wrap {
  display: inline-flex;
  align-items: center;
  gap: 4px;
  max-width: 200px;
}
.tab-title-text {
  overflow: hidden;
  text-overflow: ellipsis;
  white-space: nowrap;
  vertical-align: bottom;
}
.tab-more-icon {
  font-size: 14px;
  color: #8c8c8c;
  cursor: pointer;
  padding: 0 2px;
}
.tab-more-icon:hover {
  color: #1890ff;
}
.tab-pane-inner {
  min-height: 120px;
}
.pkg-edit-form {
  padding-top: 4px;
}
.pkg-edit-form :deep(.ant-form-item-label) {
  padding-bottom: 8px;
}
.pkg-row-existing {
  margin-bottom: 8px !important;
}
.pkg-existing-icon {
  color: #8c8c8c;
  font-size: 16px;
}
.pkg-advanced-collapse {
  margin-bottom: 12px;
  border: 1px solid #f0f0f0;
  border-radius: 8px;
  padding: 0 12px;
  background: #fafafa;
}
.pkg-advanced-collapse :deep(.ant-collapse-header) {
  padding: 10px 0 !important;
  font-size: 13px;
  color: #595959;
}
.pkg-footer-actions {
  margin-top: 8px;
}
.age-range-row {
  display: inline-flex;
  align-items: center;
  gap: 10px;
}
.age-tilde {
  color: #8c8c8c;
  font-size: 14px;
}
.age-select {
  width: 88px;
}
// 分类刷新按钮
.dt-cat-refresh-btn {
  flex-shrink: 0;
  padding: 0 4px;
  color: #8c8c8c;
  font-size: 13px;
  opacity: 0;
  transition: opacity 0.15s;
}
.dt-tree-item-row--cat:hover .dt-cat-refresh-btn {
  opacity: 1;
}
// 占位 / 暂无数据行
.dt-tree-item-row--empty {
  pointer-events: none;
}

  .dt-dual-pane {
  display: flex;
  width: 100%;
  max-width: 880px;
  min-height: 320px;
  margin-top: 10px;
  border: 1px solid #e8e8e8;
  border-radius: 8px;
  background: #fff;
  overflow: hidden;
}
.dt-left {
  flex: 1;
  min-width: 0;
  padding: 10px 12px;
  border-right: 1px solid #f0f0f0;
  display: flex;
  flex-direction: column;
}
.dt-search-input {
  margin-bottom: 8px;
}
.dt-account-row {
  display: flex;
  align-items: center;
  gap: 8px;
  margin: 0 0 8px;
}
.dt-account-label {
  font-size: 12px;
  color: #8c8c8c;
  flex-shrink: 0;
}
.dt-scroll-list {
  flex: 1;
  min-height: 200px;
  max-height: 320px;
  overflow: auto;
}
// 分类根节点行
.browse-cat-root-row {
  padding: 8px 6px !important;
  cursor: pointer;
  background: transparent;
  &:hover {
    background: #f5faff;
  }
  .browse-cat-root-name {
    font-weight: 500;
    color: #262626;
  }
}
// 子节点行
.browse-tree-row {
  cursor: default;
}
.browse-tree-leaf {
  .browse-tree-toggle {
    opacity: 0.3;
    cursor: default;
  }
}
.browse-tree-toggle {
  display: inline-flex;
  width: 18px;
  align-items: center;
  justify-content: center;
  margin-right: 6px;
  cursor: pointer;
  vertical-align: middle;
}
.dt-tree-icon {
  font-size: 12px;
  color: #595959;
  transition: transform 0.15s;
}
.dt-root-cat-row {
  display: flex;
  align-items: center;
  justify-content: space-between;
  padding: 10px 12px;
  margin-bottom: 4px;
  border: 1px solid #f0f0f0;
  border-radius: 6px;
  cursor: pointer;
  transition: background 0.15s;
}
.dt-root-cat-row:hover {
  background: #f5faff;
  border-color: #d6e4ff;
}
.dt-root-cat-name {
  font-size: 14px;
  color: #262626;
}
.dt-chevron {
  color: #8c8c8c;
  font-size: 12px;
}
.dt-browse-toolbar {
  margin-bottom: 8px;
}
.dt-left-footer {
  margin-top: 8px;
  padding-top: 8px;
  border-top: 1px solid #f0f0f0;
  display: flex;
  flex-wrap: wrap;
  gap: 8px;
}
.dt-op-hint {
  margin: 0;
  font-size: 13px;
  color: #8c8c8c;
  line-height: 1.5;
}

// ---- 树节点（分类行 / 叶子行）----
.dt-tree-item-row {
  display: flex;
  flex-direction: row;
  align-items: center;
  justify-content: space-between;
  padding: 10px 12px;
  border-bottom: 1px solid #f5f5f5;
  gap: 8px;
  min-height: 40px;
}
.dt-tree-item-row:last-child {
  border-bottom: 0;
}
.dt-tree-item-row--cat {
  cursor: pointer;
}
.dt-tree-item-row--cat:hover {
  background: #f5faff;
}
.dt-tree-item-row--leaf {
  cursor: default;
}
.dt-tree-item-row--leaf:hover {
  background: #fafafa;
}
.dt-tree-cat-inner {
  display: flex;
  flex-direction: row;
  align-items: center;
  gap: 4px;
  flex: 1;
  min-width: 0;
}
.dt-tree-item-toggle {
  flex-shrink: 0;
  width: 16px;
  display: inline-flex;
  align-items: center;
  justify-content: center;
}
.dt-tree-cat-name {
  font-size: 14px;
  color: #1d1d1f;
  font-weight: 500;
  word-break: break-word;
  vertical-align: middle;
}
.dt-tree-leaf-info {
  flex: 1;
  min-width: 0;
  display: flex;
  flex-direction: column;
  gap: 2px;
}
.dt-tree-leaf-name {
  display: block;
  font-size: 14px;
  color: #1d1d1f;
  word-break: break-word;
  line-height: 1.4;
}
.dt-tree-leaf-size {
  display: block;
  font-size: 12px;
  color: #8c8c8c;
  line-height: 1.4;
}
.dt-right {
  width: 260px;
  flex-shrink: 0;
  display: flex;
  flex-direction: column;
  background: #fafafa;
  border-left: 1px solid #f0f0f0;
}
.dt-right-hd {
  display: flex;
  align-items: center;
  justify-content: space-between;
  padding: 10px 12px;
  font-size: 13px;
  font-weight: 500;
  color: #262626;
  border-bottom: 1px solid #f0f0f0;
}
.dt-right-body {
  flex: 1;
  padding: 8px 10px;
  overflow: auto;
  max-height: 320px;
}
.dt-right-empty {
  padding: 16px 8px;
  text-align: center;
}
.dt-right-row {
  display: flex;
  align-items: flex-start;
  justify-content: space-between;
  gap: 8px;
  padding: 6px 4px;
  font-size: 13px;
  border-bottom: 1px solid #f0f0f0;
}
.dt-right-row:last-child {
  border-bottom: 0;
}
.dt-right-name {
  min-width: 0;
  word-break: break-word;
  color: #262626;
}
.dt-suggest-box {
  margin-top: 8px;
  max-height: 260px;
}
.dt-tree-chevron {
  transition: transform 0.15s;
}
.dt-tree-chevron-expanded {
  transform: rotate(90deg);
}
.pkg-region-embed {
  margin-bottom: 16px;
  padding: 12px;
  background: #fafafa;
  border: 1px solid #f0f0f0;
  border-radius: 6px;
}
.mb-12 {
  margin-bottom: 12px;
}
.mt-8 {
  margin-top: 8px;
}
.btn-switch {
  display: inline-flex;
  gap: 8px;
  flex-wrap: wrap;
}
.targeting-fieldset {
  border: 0;
  margin: 0;
  padding: 0;
  min-width: 0;
}
.targeting-fieldset--lockable {
  border-radius: 4px;
  transition: opacity 0.15s;
}
.targeting-fieldset--locked {
  pointer-events: none;
  opacity: 0.62;
  user-select: none;
}
.targeting-fieldset--locked .hint {
  opacity: 0.85;
}
.targeting-fieldset-detail {
  margin-bottom: 4px;
}
.pkg-field-label-with-tip {
  display: inline-flex;
  align-items: center;
  gap: 4px;
}
.pkg-label-tip-icon {
  color: #8c8c8c;
  cursor: help;
  font-size: 14px;
}
.pkg-label-tip-icon:hover {
  color: #1890ff;
}
.pkg-more-divider {
  margin: 20px 0 16px;
  font-size: 13px;
  color: #8c8c8c;
}
.detailed-targeting-hint {
  margin-top: 8px;
}
.browse-account-hint {
  margin-bottom: 8px;
  color: #d46b08;
}
.detailed-interest-subtabs {
  :deep(.ant-tabs-nav) {
    margin-bottom: 8px;
  }
}
.browse-toolbar {
  display: flex;
  flex-wrap: wrap;
  align-items: center;
  gap: 8px;
  margin-bottom: 8px;
}
.browse-row-actions {
  display: flex;
  flex-shrink: 0;
  gap: 4px;
  align-items: center;
}
.browse-row-clickable {
  cursor: pointer;
}
.browse-row-clickable:hover {
  background: #f5f5f5;
}
.browse-tree-row {
  cursor: default;
}
.browse-tree-toggle {
  display: inline-flex;
  width: 18px;
  align-items: center;
  justify-content: center;
  margin-right: 6px;
  cursor: pointer;
  vertical-align: middle;
}
.browse-tree-row .interest-candidate-main {
  flex-direction: row;
  align-items: center;
  gap: 6px;
}
.dt-tree-chevron {
  transition: transform 0.15s;
}
.dt-tree-chevron-expanded {
  transform: rotate(90deg);
}
.interest-detail-panel {
  max-width: 640px;
}
.interest-loading {
  padding: 12px 0;
}
.interest-candidate-box {
  margin-top: 10px;
  border: 1px solid #f0f0f0;
  border-radius: 6px;
  max-height: 220px;
  overflow: auto;
  background: #fafafa;
}
.interest-candidate-empty {
  padding: 12px 16px;
}
.interest-candidate-row {
  display: flex;
  align-items: center;
  justify-content: space-between;
  gap: 8px;
  padding: 8px 12px;
  border-bottom: 1px solid #f0f0f0;
  font-size: 13px;
}
.interest-candidate-row:last-child {
  border-bottom: 0;
}
.interest-candidate-main {
  flex: 1;
  min-width: 0;
  display: flex;
  flex-direction: column;
  gap: 2px;
}
.interest-candidate-name {
  color: #262626;
  word-break: break-word;
}
.interest-candidate-id {
  font-size: 12px;
  color: #8c8c8c;
}
.interest-selected-hd {
  display: flex;
  align-items: center;
  justify-content: space-between;
  margin-top: 12px;
  font-size: 13px;
  color: #595959;
}
.interest-selected-tags {
  margin-top: 8px;
  display: flex;
  flex-wrap: wrap;
  gap: 8px;
  align-items: center;
}
.interest-tag-id {
  font-size: 12px;
  opacity: 0.85;
}
.hint {
  margin-top: 6px;
  font-size: 12px;
  color: #8c8c8c;
}
.bind-object-row {
  display: inline-flex;
  align-items: center;
}
.ml-8 {
  margin-left: 8px;
}
.saved-pkg-toolbar {
  display: flex;
  gap: 12px;
  margin-bottom: 12px;
}

.two-pane-dd {
  display: flex;
  gap: 0;
  min-height: 240px;
}
.two-pane-left {
  flex: 1;
  min-width: 360px;
  max-width: 440px;
  border-right: 1px solid #f0f0f0;
  padding: 8px 12px;
}
.two-pane-right {
  width: 260px;
  padding: 8px 12px;
}
.two-pane-right-hd {
  display: flex;
  justify-content: space-between;
  align-items: center;
  color: #8c8c8c;
  font-size: 12px;
}
.lang-dd {
  min-height: 280px;
}
.dd-search {
  margin-bottom: 8px;
}
.dd-list {
  max-height: 230px;
  overflow: auto;
}
.dd-row {
  display: flex;
  align-items: center;
  gap: 10px;
  padding: 8px 6px;
  border-radius: 4px;
  cursor: pointer;
}
.dd-row:hover {
  background: #f5f5f5;
}
.dd-row-label {
  min-width: 0;
  word-break: break-word;
}
.dd-selected {
  margin-top: 8px;
  max-height: 240px;
  overflow: auto;
  padding-top: 8px;
  border-top: 1px solid #f0f0f0;
}
.dd-selected-row {
  display: flex;
  align-items: center;
  justify-content: space-between;
  gap: 8px;
  padding: 6px 0;
}
.dd-selected-label {
  min-width: 0;
  word-break: break-word;
}

.picker-panel {
  display: flex;
  border: 1px solid #f0f0f0;
  border-radius: 4px;
  overflow: hidden;
  background: #fff;
}
.picker-left {
  flex: 1;
  min-width: 0;
  padding: 8px 12px;
  border-right: 1px solid #f0f0f0;
}
.picker-right {
  width: 320px;
  padding: 8px 12px;
}
.picker-search-row {
  display: flex;
  align-items: center;
  gap: 8px;
  margin: 8px 0 10px;
}
.sync-link {
  padding: 0;
}
.picker-table {
  border: 1px solid #f0f0f0;
  border-radius: 4px;
}
.picker-table-head {
  display: grid;
  grid-template-columns: 1fr 140px;
  background: #fafafa;
  border-bottom: 1px solid #f0f0f0;
  padding: 8px 12px;
  font-size: 12px;
  color: #8c8c8c;
}
.picker-table-body {
  max-height: 220px;
  overflow: auto;
}
.picker-row {
  display: grid;
  grid-template-columns: 1fr 140px;
  padding: 8px 12px;
  border-bottom: 1px solid #fafafa;
  align-items: center;
}
.picker-row:last-child {
  border-bottom: none;
}
.col-name {
  min-width: 0;
  word-break: break-word;
}
.col-action {
  text-align: right;
  white-space: nowrap;
}
.empty-row {
  padding: 24px 12px;
  text-align: center;
  color: #8c8c8c;
  font-size: 12px;
}
.selected-hd {
  display: flex;
  align-items: center;
  justify-content: space-between;
  margin-bottom: 6px;
}
.selected-list {
  max-height: 240px;
  overflow: auto;
  border-top: 1px solid #f0f0f0;
  padding-top: 8px;
}
.selected-item {
  display: flex;
  align-items: center;
  justify-content: space-between;
  gap: 8px;
  padding: 6px 0;
}
.selected-name {
  min-width: 0;
  word-break: break-word;
}
.x-btn {
  padding: 0 4px;
  line-height: 1;
}
</style>
