<template>
  <page-container :showPageHeader="false">
    <div class="ant-pro-table">
      <a-card :body-style="{ padding: 0 }" class="my-wrapper">
        <a-card>
          <div class="action-toolbar">
            <!-- 左侧：过滤器 -->
            <div class="toolbar-left">
              <applied-filters
                :filters="appliedFilters"
                :bookmarks="bookmarks"
                @remove-filter="removeFilter"
                @clear-all="clearAllFilters"
                @save-bookmark="showSaveBookmarkModal"
              />
            </div>

            <!-- 右侧：操作按钮组 -->
            <div class="toolbar-right">
              <!-- 日期选择器 -->
              <div class="toolbar-item">
                <a-range-picker
                  v-model:value="date_range"
                  :placeholder="[t('Start date'), t('End date')]"
                  @panelChange="onDatePickerPanelChange"
                  @change="onDatePickerChange"
                  :open="openDatePicker"
                  @openChange="onDatePickerOpenChange"
                  :presets="rangePresets"
                >
                  <template #renderExtraFooter>
                    <div class="date-picker">
                      <a-button size="small" class="cancel" @click="openDatePicker = false">
                        {{ t('Cancel') }}
                      </a-button>
                      <a-button size="small" type="primary" @click="onDatePickerOk">
                        {{ t('Ok') }}
                      </a-button>
                    </div>
                  </template>
                </a-range-picker>
              </div>

              <!-- 刷新按钮 -->
              <div class="toolbar-item">
                <a-tooltip :title="t('Refresh')">
                  <a-button shape="circle" :icon="h(ReloadOutlined)" @click="reload" />
                </a-tooltip>
              </div>

              <!-- 标签管理按钮 -->
              <div class="toolbar-item">
                <a-tooltip :title="t('Tags')">
                  <a-button
                    shape="circle"
                    :icon="h(TagOutlined)"
                    @click="showManageTagsModal('add')"
                  />
                </a-tooltip>
              </div>

              <!-- 创建广告按钮 -->
              <div class="toolbar-item" v-if="canCreateAds && activeTab !== '4'">
                <a-tooltip :title="t('Launch')">
                  <a-button shape="circle" :icon="h(RocketOutlined)" @click="gotoCreateAd" />
                </a-tooltip>
              </div>

              <!-- 开启按钮 -->
              <div class="toolbar-item" v-if="activeTab !== '1'">
                <a-tooltip :title="t('Turn On')">
                  <a-button
                    shape="circle"
                    :icon="h(PlayCircleOutlined)"
                    @click="updateFbItemStatus('ACTIVE')"
                  />
                </a-tooltip>
              </div>

              <!-- 关闭按钮 -->
              <div class="toolbar-item" v-if="activeTab !== '1'">
                <a-tooltip :title="t('Turn Off')">
                  <a-button
                    shape="circle"
                    :icon="h(PauseCircleOutlined)"
                    @click="updateFbItemStatus('PAUSED')"
                  />
                </a-tooltip>
              </div>

              <!-- 列管理器 -->
              <div class="toolbar-item">
                <column-orgnizer
                  v-if="activeTab === '1'"
                  :columns="adAccountColumns"
                  @change:columns="columns => (dynamicColumnsTab1 = columns)"
                />
                <column-orgnizer
                  v-else-if="activeTab === '2'"
                  :columns="campaignColumns"
                  @change:columns="columns => (dynamicColumnsTab2 = columns)"
                />
                <column-orgnizer
                  v-else-if="activeTab === '3'"
                  :columns="adsetColumns"
                  @change:columns="columns => (dynamicColumnsTab3 = columns)"
                />
                <column-orgnizer
                  v-else-if="activeTab === '4'"
                  :columns="adColumns"
                  @change:columns="columns => (dynamicColumnsTab4 = columns)"
                />
              </div>

              <!-- 多语言编辑按钮 -->
              <div class="toolbar-item" v-if="activeTab === '4' && showMultiLanguageManagement">
                <a-dropdown>
                  <template #overlay>
                    <a-menu @click="handleMultiLanguageMenuClick">
                      <a-menu-item key="manual">
                        {{ t('Manual') }}
                      </a-menu-item>
                      <a-menu-divider />
                      <a-menu-item key="add-one-language">
                        {{ t('Randomly add 1 language') }}
                      </a-menu-item>
                      <a-menu-item key="add-multiple-languages">
                        {{ t('Randomly add languages') }}
                      </a-menu-item>
                      <a-menu-divider />
                      <a-menu-item key="enable-auto-add">
                        {{ t('Enable auto-add multi-language') }}
                      </a-menu-item>
                      <a-menu-item key="disable-auto-add">
                        {{ t('Disable auto-add multi-language') }}
                      </a-menu-item>
                    </a-menu>
                  </template>
                  <a-button>
                    <edit-outlined />
                    <down-outlined />
                  </a-button>
                </a-dropdown>
              </div>

              <!-- 书签按钮 -->
              <div class="toolbar-item">
                <a-dropdown trigger="hover">
                  <template #overlay>
                    <a-menu class="bookmark-dropdown-menu">
                      <template v-for="bookmark in bookmarks" :key="bookmark.id">
                        <a-menu-item @click="applyBookmark(bookmark)">
                          <div class="bookmark-menu-item">
                            <span class="bookmark-name">{{ bookmark.name }}</span>
                            <a-tooltip :title="t('Delete Bookmark')">
                              <delete-outlined
                                class="bookmark-delete-icon"
                                @click.stop="deleteBookmark(bookmark.id)"
                              />
                            </a-tooltip>
                          </div>
                        </a-menu-item>
                      </template>
                      <a-menu-item v-if="bookmarks.length === 0" disabled>
                        <span class="bookmark-empty-text">{{ t('No bookmarks') }}</span>
                      </a-menu-item>
                    </a-menu>
                  </template>
                  <a-button class="bookmark-btn">
                    <star-outlined />
                    <down-outlined />
                  </a-button>
                </a-dropdown>
              </div>

              <!-- 操作菜单按钮 -->
              <div class="toolbar-item">
                <a-dropdown>
                  <template #overlay>
                    <a-menu @click="handleMenuClick">
                      <a-menu-item key="sync-data-mode-1">{{ t('Sync Spending') }}</a-menu-item>
                      <a-sub-menu key="sync-submenu" :title="t('Sync Others')">
                        <a-menu-item key="sync-data-mode-2">
                          <span v-if="activeTab === '1'">{{ t('Sync Account Status') }}</span>
                          <span v-if="activeTab === '2'">{{ t('Sync Campaign Status') }}</span>
                          <span v-if="activeTab === '3'">{{ t('Sync Adset Status') }}</span>
                          <span v-if="activeTab === '4'">{{ t('Sync Ad Status') }}</span>
                        </a-menu-item>
                        <a-menu-item key="sync-data-mode-3" v-if="activeTab !== '4'">
                          <span v-if="activeTab === '1'">{{ t("Sync Account's Campaign") }}</span>
                          <span v-if="activeTab === '2'">{{ t("Sync Campaign's Adset") }}</span>
                          <span v-if="activeTab === '3'">{{ t("Sync Adset's Ad") }}</span>
                        </a-menu-item>
                        <a-menu-item key="sync-data-mode-4">
                          <span>{{ t('Sync All') }}</span>
                        </a-menu-item>
                      </a-sub-menu>
                      <a-menu-item
                        key="payment"
                        v-if="activeTab === '1' || activeTab === '2' || activeTab === '3'"
                      >
                        {{ t('Payment') }}
                      </a-menu-item>
                      <a-menu-item
                        key="batch-update-budget"
                        v-if="activeTab === '2' || activeTab === '3'"
                      >
                        {{ t('Batch Update Budget') }}
                      </a-menu-item>
                      <a-menu-item key="copy-campaign-id" v-if="activeTab === '2'">
                        {{ t('Copy ID') }}
                      </a-menu-item>
                      <a-menu-item key="copy-all-campaign-id" v-if="activeTab === '2'">
                        {{ t('Copy All ID') }}
                      </a-menu-item>
                      <a-menu-item key="cbo-2-abo" v-if="activeTab === '2'">
                        {{ t('CBO 2 ABO') }}
                      </a-menu-item>
                      <a-menu-item key="abo-2-cbo" v-if="activeTab === '2'">
                        {{ t('ABO 2 CBO') }}
                      </a-menu-item>
                      <a-menu-item key="archive-campaign" v-if="activeTab === '2'">
                        {{ t('Archive Campaign') }}
                      </a-menu-item>
                      <a-menu-item key="unarchive-campaign" v-if="activeTab === '2'">
                        {{ t('Unarchive Campaign') }}
                      </a-menu-item>
                      <a-menu-item key="turn-on-campaign" v-if="activeTab === '2'">
                        {{ t('Turn On Campaign') }}
                      </a-menu-item>
                      <a-menu-item key="turn-off-campaign" v-if="activeTab === '2'">
                        {{ t('Turn Off Campaign') }}
                      </a-menu-item>
                      <a-menu-item key="turn-on-adset" v-if="activeTab === '3'">
                        {{ t('Turn On Adset') }}
                      </a-menu-item>
                      <a-menu-item key="turn-off-adset" v-if="activeTab === '3'">
                        {{ t('Turn Off Adset') }}
                      </a-menu-item>
                      <a-menu-item key="turn-on-ad" v-if="activeTab === '4'">
                        {{ t('Turn On Ad') }}
                      </a-menu-item>
                      <a-menu-item key="turn-off-ad" v-if="activeTab === '4'">
                        {{ t('Turn Off Ad') }}
                      </a-menu-item>
                      <a-menu-item key="copy-campaign" v-if="activeTab === '2'" disabled>
                        {{ t('Copy Campaign') }}
                      </a-menu-item>
                      <a-menu-item key="copy-adset" v-if="activeTab === '3'" disabled>
                        {{ t('Copy Adset') }}
                      </a-menu-item>
                      <a-menu-item key="copy-ad-to-adsets" v-if="activeTab === '3'">
                        {{ t('Copy Ad To Adsets') }}
                      </a-menu-item>
                      <a-menu-item key="copy-ads" v-if="activeTab === '4'">
                        {{ t('Copy Ads') }}
                      </a-menu-item>
                      <a-menu-item key="add-tags">
                        {{ t('Add Tag') }}
                      </a-menu-item>
                      <a-menu-item key="delete-tags">
                        {{ t('Delete Tag') }}
                      </a-menu-item>
                      <a-menu-item key="export">{{ t('Export') }}</a-menu-item>
                      <a-menu-item key="view-ad-account">{{ t('View Ad Account') }}</a-menu-item>
                      <a-menu-divider v-if="activeTab === '1'" />
                      <a-menu-item
                        key="sync-card-3days"
                        v-if="activeTab === '1'"
                      >
                        {{ t('pages.adc.action.sync.card.3days') }}
                      </a-menu-item>
                      <a-menu-item
                        key="sync-card-custom"
                        v-if="activeTab === '1'"
                      >
                        {{ t('pages.adc.action.sync.card.custom') }}
                      </a-menu-item>
                      <a-menu-divider />
                      <a-menu-item key="sync-keitaro">
                        {{ t('Sync Keitaro') }}
                      </a-menu-item>
                      <a-menu-item key="sync-keitaro-custom">
                        {{ t('Custom sync Keitaro') }}
                      </a-menu-item>
                      <a-menu-divider />
                      <a-menu-item key="delete" v-if="activeTab !== '1'">
                        {{ t('Delete') }}
                      </a-menu-item>
                    </a-menu>
                  </template>
                  <a-button type="primary">
                    {{ t('Actions') }}
                    <down-outlined />
                  </a-button>
                </a-dropdown>
              </div>

              <!-- 搜索表单 -->
              <div class="toolbar-item">
                <dynamic-form :form-items="formItems" @change:form-data="onSearch" />
              </div>
            </div>
          </div>
        </a-card>
        <a-tabs
          v-model:activeKey="activeTab"
          size="large"
          :animated="false"
          @change="onChangeTab"
          type="editable-card"
          @edit="onEditTab"
          hide-add
        >
          <!-- ad account tab -->
          <a-tab-pane key="1" :closable="adAccountClosable">
            <template #tab>
              <span>
                <profile-outlined />
                {{ adAccountTabTitle }}
              </span>
            </template>
            <a-table
              :columns="dynamicColumnsTab1"
              :scroll="scroll"
              :data-source="adAccountTableDataState.dataSource"
              :row-key="record => record.id"
              :loading="adAccountTabLoading"
              :pagination="{
                current: AdAccountDataState.current,
                pageSize: AdAccountDataState.pageSize,
                total: AdAccountDataState.total,
                defaultPageSize: 10,
                showSizeChanger: true,
                pageSizeOptions: ['3', '10', '20', '50', '100', '200', '500', '1000', '2000'],
                showTotal: total => `Total ${total} items`,
              }"
              :size="adAccountTableDataState.size"
              bordered
              sticky
              @change="handleTableChange"
              :row-selection="{
                selectedRowKeys: selectedAdAccountState.selectedRowKeys,
                onChange: onSelectedAdAccountChange,
              }"
              :custom-row="customAdAccountRow"
              :customCell="adAccCellClick"
            >
              <template #bodyCell="{ column, text, record }">
                <template v-if="column['dataIndex'] === 'action'">
                  <a @click="handleSyncOne(record)">Sync</a>
                  <a-divider type="vertical" />
                  <a-dropdown>
                    <a>
                      more
                      <down-outlined />
                    </a>
                    <template #overlay>
                      <a-menu @click="e => handleActionManuClick(e, record)">
                        <!-- 根据卡片状态显示 Freeze 或 Unfreeze -->
                        <a-menu-item
                          key="freeze"
                          v-if="record['status'] === 'ACTIVE'"
                          :disabled="record['status'] === 'CLOSED'"
                        >
                          <a>Freeze</a>
                        </a-menu-item>
                        <a-menu-item
                          key="unfreeze"
                          v-if="record['status'] === 'INACTIVE'"
                          :disabled="record['status'] === 'CLOSED'"
                        >
                          <a>Unfreeze</a>
                        </a-menu-item>
                        <!-- Cancel 和 Set Limit 只在 Active 状态下可用 -->
                        <a-menu-item key="cancel" :disabled="record['status'] !== 'ACTIVE'">
                          <a>Cancel</a>
                        </a-menu-item>
                        <a-menu-item key="set-limit" :disabled="record['status'] !== 'ACTIVE'">
                          <a>Set Limit</a>
                        </a-menu-item>
                      </a-menu>
                    </template>
                  </a-dropdown>
                </template>
                <template v-if="column['dataIndex'] == 'ad_account_id'">
                  <copy-outlined
                    style="color: #1677ff"
                    v-if="text"
                    @click.stop=""
                    @click="copyCell(text)"
                  />
                  <info-circle-outlined
                    style="color: #1677ff; margin-left: 4px"
                    v-if="text"
                    @click.stop=""
                    @click="showAdAccountInfoModal(record)"
                  />
                  {{ text }}
                  <br />
                  <copy-outlined
                    style="color: #1677ff"
                    v-if="text"
                    @click.stop=""
                    @click="copyCell(record['ad_account_name'])"
                  />
                  {{ record['ad_account_name'] }}
                </template>
                <template v-if="column['dataIndex'] == 'account_status'">
                  <a-badge
                    :color="text === 'ACTIVE' ? 'green' : text === 'DISABLED' ? 'red' : 'gray'"
                    :text="text"
                  />
                </template>

                <template v-if="['created_at', 'updated_at', 'refresh_time'].includes(`${column['dataIndex']}`)">
                  <span>{{ dayjs(text).format('YYYY-MM-DD HH:mm:ss') }}</span>
                </template>
                <template v-if="column['dataIndex'] == 'tags'">
                  <a-tag v-for="item in record.tags" :key="item.id">{{ item.name }}</a-tag>
                </template>
                <template v-if="column['dataIndex'] == 'spend'">
                  <div>{{ text }}</div>
                  <div style="font-size: 12px; color: '#888'" v-if="record.currency !== 'USD'">
                    ({{ record.original_spend }})
                  </div>
                </template>
              </template>
              <template #summary>
                <a-table-summary-row>
                  <a-table-summary-cell
                    v-for="item in newAdAccountSummaryItems"
                    :key="item.dataIndex"
                    :index="item.index"
                  >
                    <span>{{ item.value }}</span>
                  </a-table-summary-cell>
                </a-table-summary-row>
              </template>
            </a-table>
          </a-tab-pane>
          <!-- campaign tab -->
          <a-tab-pane key="2" :closable="campaignClosable" :disabled="campaignDisable">
            <template #tab>
              <span>
                <up-square-outlined />
                {{ campaignTabTitle }}
              </span>
            </template>
            <a-table
              :columns="dynamicColumnsTab2"
              :scroll="scroll"
              :data-source="campaignTableDataState.dataSource"
              :row-key="record => record.id"
              :loading="campaignTableDataState.loading"
              :pagination="campaignPagination"
              :size="campaignTableDataState.size"
              bordered
              sticky
              :row-selection="{
                selectedRowKeys: selectedCampaignState.selectedRowKeys,
                onChange: onSelectedCampaignChange,
              }"
              @change="handleCampaignChange"
              :custom-row="customCampaignRow"
              :customCell="campaignCellClick"
            >
              <template #bodyCell="{ column, record, text }">
                <template v-if="column['dataIndex'] == 'ad_account_id'">
                  <copy-outlined
                    style="color: #1677ff"
                    v-if="text"
                    @click.stop=""
                    @click="copyCell(text)"
                  />
                  <info-circle-outlined
                    style="color: #1677ff; margin-left: 4px"
                    v-if="text"
                    @click.stop=""
                    @click="showAdAccountInfoModal(record)"
                  />
                  {{ text }}
                  <br />
                  <copy-outlined
                    style="color: #1677ff"
                    v-if="text"
                    @click.stop=""
                    @click="copyCell(record['ad_account_name'])"
                  />
                  {{ record['ad_account_name'] }}
                </template>
                <template v-if="column['dataIndex'] == 'account_status'">
                  <a-badge
                    :color="text === 'ACTIVE' ? 'green' : text === 'DISABLED' ? 'red' : 'gray'"
                    :text="text"
                  />
                </template>
                <template v-if="column['dataIndex'] == 'campaign_id'">
                  <span :style="{ color: record.is_deleted_on_fb ? 'gray' : '' }">
                    {{ record.campaign_id }}
                  </span>
                </template>
                <template v-if="column['dataIndex'] == 'campaign_name'">
                  <span :style="{ color: record.is_deleted_on_fb ? 'gray' : '' }">
                    {{ record.campaign_name }}
                  </span>
                </template>
                <template v-if="column['dataIndex'] == 'effective_status'">
                  <a-badge
                    :color="
                      ['DISAPPROVED', 'WITH_ISSUES', 'ARCHIVED', 'DELETED'].includes(text)
                        ? 'red'
                        : text === 'ACTIVE'
                        ? 'green'
                        : 'gray'
                    "
                    :text="text"
                  />
                </template>
                <template v-if="column['dataIndex'] == 'tags'">
                  <a-tag v-for="item in record.tags" :key="item.id">{{ item.name }}</a-tag>
                </template>
                <template v-if="column['dataIndex'] == 'spend'">
                  <div>{{ text }}</div>
                  <div style="font-size: 12px; color: '#888'" v-if="record.currency !== 'USD'">
                    ({{ record.original_spend }})
                  </div>
                </template>
                <template v-if="['refresh_time'].includes(`${column['dataIndex']}`)">
                  <span>{{ dayjs(text).format('YYYY-MM-DD HH:mm:ss') }}</span>
                </template>
              </template>
              <template #summary>
                <a-table-summary-row>
                  <a-table-summary-cell
                    v-for="item in newCampaignSummaryItems"
                    :key="item.dataIndex"
                    :index="item.index"
                  >
                    <span>{{ item.value }}</span>
                  </a-table-summary-cell>
                </a-table-summary-row>
              </template>
            </a-table>
          </a-tab-pane>

          <!-- adset tab -->
          <a-tab-pane key="3" :closable="adsetClosable" :disabled="adsetDisable">
            <template #tab>
              <span>
                <shop-outlined />
                {{ adsetTabTitle }}
              </span>
            </template>
            <a-table
              :columns="dynamicColumnsTab3"
              :scroll="scroll"
              :data-source="adsetTableDataState.dataSource"
              :row-key="record => record.id"
              :loading="adsetTableDataState.loading"
              :pagination="adsetPagination"
              :size="adsetTableDataState.size"
              bordered
              sticky
              :row-selection="{
                selectedRowKeys: selectedAdsetState.selectedRowKeys,
                onChange: onSelectedAdsetChange,
              }"
              @change="handleAdsetChange"
              :customCell="adsetCellClick"
              :custom-row="customAdsetRow"
            >
              <template #bodyCell="{ column, text, record }">
                <template v-if="column['dataIndex'] == 'ad_account_id'">
                  <copy-outlined
                    style="color: #1677ff"
                    v-if="text"
                    @click.stop=""
                    @click="copyCell(text)"
                  />
                  <info-circle-outlined
                    style="color: #1677ff; margin-left: 4px"
                    v-if="text"
                    @click.stop=""
                    @click="showAdAccountInfoModal(record)"
                  />
                  {{ text }}
                  <br />
                  <copy-outlined
                    style="color: #1677ff"
                    v-if="text"
                    @click.stop=""
                    @click="copyCell(record['ad_account_name'])"
                  />
                  {{ record['ad_account_name'] }}
                </template>
                <template v-if="column['dataIndex'] == 'account_status'">
                  <a-badge
                    :color="text === 'ACTIVE' ? 'green' : text === 'DISABLED' ? 'red' : 'gray'"
                    :text="text"
                  />
                </template>
                <template v-if="column['dataIndex'] == 'effective_status'">
                  <a-badge
                    :color="
                      ['DISAPPROVED', 'WITH_ISSUES', 'ARCHIVED'].includes(text)
                        ? 'red'
                        : text === 'ACTIVE'
                        ? 'green'
                        : 'gray'
                    "
                    :text="text"
                  />
                </template>
                <template v-if="column['dataIndex'] == 'campaign_id'">
                  <span :style="{ color: record.is_deleted_on_fb ? 'gray' : '' }">
                    {{ record.campaign_id }}
                  </span>
                </template>
                <template v-if="column['dataIndex'] == 'campaign_name'">
                  <span :style="{ color: record.is_deleted_on_fb ? 'gray' : '' }">
                    {{ record.campaign_name }}
                  </span>
                </template>
                <template v-if="column['dataIndex'] == 'adset_id'">
                  <div style="display: flex; align-items: center; gap: 4px">
                    <!-- 语言图标：只有当设置了语言时才显示 -->
                    <a-tooltip
                      v-if="record.targeting?.locales?.length > 0"
                      :title="t('View Languages')"
                    >
                      <global-outlined
                        style="color: #1677ff; cursor: pointer; font-size: 14px"
                        @click.stop="showAdsetLanguages(record)"
                      />
                    </a-tooltip>

                    <!-- 受众图标 -->
                    <a-tooltip :title="t('View Audience')">
                      <team-outlined
                        style="color: #52c41a; cursor: pointer; font-size: 14px"
                        @click.stop="showAdsetAudience(record)"
                      />
                    </a-tooltip>

                    <!-- Adset ID -->
                    <span :style="{ color: record.is_deleted_on_fb ? 'gray' : '' }">
                      {{ record.adset_id }}
                    </span>
                  </div>
                </template>
                <template v-if="column['dataIndex'] == 'adset_name'">
                  <span :style="{ color: record.is_deleted_on_fb ? 'gray' : '' }">
                    {{ record.adset_name }}
                  </span>
                </template>
                <template v-if="column['dataIndex'] == 'tags'">
                  <a-tag v-for="item in record.tags" :key="item.id">{{ item.name }}</a-tag>
                </template>
                <template v-if="column['dataIndex'] == 'spend'">
                  <div>{{ text }}</div>
                  <div style="font-size: 12px; color: '#888'" v-if="record.currency !== 'USD'">
                    ({{ record.original_spend }})
                  </div>
                </template>
                <template v-if="['refresh_time'].includes(`${column['dataIndex']}`)">
                  <span>{{ dayjs(text).format('YYYY-MM-DD HH:mm:ss') }}</span>
                </template>
              </template>
              <template #summary>
                <a-table-summary-row>
                  <a-table-summary-cell
                    v-for="item in newAdsetSummaryItems"
                    :key="item.dataIndex"
                    :index="item.index"
                  >
                    <span>{{ item.value }}</span>
                  </a-table-summary-cell>
                </a-table-summary-row>
              </template>
            </a-table>
          </a-tab-pane>

          <!-- ad tab -->
          <a-tab-pane key="4" :closable="adClosable" :disabled="adDisable">
            <template #tab>
              <span>
                <tablet-outlined />
                {{ adTabTitle }}
              </span>
            </template>
            <a-table
              :columns="dynamicColumnsTab4"
              :scroll="scroll"
              :data-source="adTableDataState.dataSource"
              :row-key="record => record.id"
              :loading="adTableDataState.loading"
              :pagination="adPagination"
              :size="adTableDataState.size"
              bordered
              sticky
              :row-selection="{
                selectedRowKeys: selectedAdState.selectedRowKeys,
                onChange: onSelectedAdChange,
              }"
              @change="handleAdChange"
              :customCell="adCellClick"
              :custom-row="customAdRow"
            >
              <template #bodyCell="{ column, text, record }">
                <template v-if="column['dataIndex'] == 'ad_account_id'">
                  <copy-outlined
                    style="color: #1677ff"
                    v-if="text"
                    @click.stop=""
                    @click="copyCell(text)"
                  />
                  <info-circle-outlined
                    style="color: #1677ff; margin-left: 4px"
                    v-if="text"
                    @click.stop=""
                    @click="showAdAccountInfoModal(record)"
                  />
                  {{ text }}
                  <br />
                  <copy-outlined
                    style="color: #1677ff"
                    v-if="text"
                    @click.stop=""
                    @click="copyCell(record['ad_account_name'])"
                  />
                  {{ record['ad_account_name'] }}
                </template>
                <template v-if="column['dataIndex'] == 'account_status'">
                  <a-badge
                    :color="text === 'ACTIVE' ? 'green' : text === 'DISABLED' ? 'red' : 'gray'"
                    :text="text"
                  />
                </template>
                <template v-if="column['dataIndex'] == 'effective_status'">
                  <a-badge
                    :color="
                      ['DISAPPROVED', 'WITH_ISSUES', 'ARCHIVED'].includes(text)
                        ? 'red'
                        : text === 'ACTIVE'
                        ? 'green'
                        : 'gray'
                    "
                    :text="text"
                  />
                </template>
                <template v-if="column['dataIndex'] == 'creative'">
                  <div v-if="text && text.effective_object_story_id">
                    {{ text.effective_object_story_id.split('_')[1] }}
                    <a
                      :href="`https://www.facebook.com/${text.actor_id}/posts/${
                        text.effective_object_story_id.split('_')[1]
                      }`"
                      target="_blank"
                      @click.stop=""
                    >
                      <arrows-alt-outlined />
                    </a>
                    &nbsp;
                    <copy-outlined
                      style="color: #1677ff"
                      @click.stop=""
                      @click="copyCell(text.effective_object_story_id.split('_')[1])"
                    />
                    <template v-if="record.preview_shareable_link && canPreviewAds">
                      &nbsp;
                      <a
                        :href="`${record.preview_shareable_link}`"
                        target="_blank"
                        @click.stop=""
                      >
                        <eye-outlined />
                      </a>
                    </template>
                    &nbsp;
                    <a
                      v-if="isMultiLanguageAd(record)"
                      @click.stop=""
                      @click="showMultiLanguagePreview(record)"
                      style="color: #1677ff; cursor: pointer"
                      :title="t('Multi-language Preview')"
                    >
                      <global-outlined />
                    </a>
                    &nbsp;
                    <file-text-two-tone
                      style="color: #1677ff"
                      @click.stop=""
                      @click="showCopywriting(record)"
                    />
                    &nbsp;
                    <a-tooltip
                      v-if="record.auto_add_languages"
                      :title="t('Auto-add languages enabled')"
                    >
                      <robot-outlined style="color: #52c41a; cursor: pointer" />
                    </a-tooltip>
                    <!-- Product Set Tag -->
                    <a-tag
                      v-if="record.product_set && record.product_set.name"
                      color="blue"
                      style="margin-left: 8px; cursor: pointer;"
                      @click="showProductSetModal(record.product_set)"
                    >
                      {{ record.product_set.name }}
                    </a-tag>
                  </div>
                  <span v-else>-</span>
                </template>
                <template v-if="column['dataIndex'] == 'campaign_id'">
                  <span :style="{ color: record.is_deleted_on_fb ? 'gray' : '' }">
                    {{ record.campaign_id }}
                  </span>
                </template>
                <template v-if="column['dataIndex'] == 'campaign_name'">
                  <span :style="{ color: record.is_deleted_on_fb ? 'gray' : '' }">
                    {{ record.campaign_name }}
                  </span>
                </template>
                <template v-if="column['dataIndex'] == 'adset_id'">
                  <span :style="{ color: record.is_deleted_on_fb ? 'gray' : '' }">
                    {{ record.adset_id }}
                  </span>
                </template>
                <template v-if="column['dataIndex'] == 'adset_name'">
                  <span :style="{ color: record.is_deleted_on_fb ? 'gray' : '' }">
                    {{ record.adset_name }}
                  </span>
                </template>
                <template v-if="column['dataIndex'] == 'ad_id'">
                  <span :style="{ color: record.is_deleted_on_fb ? 'gray' : '' }">
                    {{ record.ad_id }}
                  </span>
                </template>
                <template v-if="column['dataIndex'] == 'ad_name'">
                  <span :style="{ color: record.is_deleted_on_fb ? 'gray' : '' }">
                    {{ record.ad_name }}
                  </span>
                </template>
                <template v-if="column['dataIndex'] == 'tags'">
                  <a-tag v-for="item in record.tags" :key="item.id">{{ item.name }}</a-tag>
                </template>
                <template v-if="column['dataIndex'] == 'spend'">
                  <div>{{ text }}</div>
                  <div style="font-size: 12px; color: '#888'" v-if="record.currency !== 'USD'">
                    ({{ record.original_spend }})
                  </div>
                </template>
                <template v-if="['refresh_time'].includes(`${column['dataIndex']}`)">
                  <span>{{ dayjs(text).format('YYYY-MM-DD HH:mm:ss') }}</span>
                </template>
              </template>
              <template #summary>
                <a-table-summary-row>
                  <a-table-summary-cell
                    v-for="item in newAdSummaryItems"
                    :key="item.dataIndex"
                    :index="item.index"
                  >
                    <span>{{ item.value }}</span>
                  </a-table-summary-cell>
                </a-table-summary-row>
              </template>
            </a-table>
          </a-tab-pane>
        </a-tabs>
      </a-card>

      <tag-modal
        :model="tagModal.model"
        :visible="tagModal.visible"
        @cancel="
          () => {
            tagModal.visible = false;
          }
        "
        @ok="
          () => {
            tagModal.visible = false;
            reload();
          }
        "
      />

      <budget-modal
        :model="budgetModal.model"
        :visible="budgetModal.visible"
        @cancel="
          () => {
            budgetModal.visible = false;
          }
        "
        @ok="
          () => {
            budgetModal.visible = false;
            reload();
          }
        "
      />

      <copy-modal
        :model="copyModal.model"
        :visible="copyModal.visible"
        @cancel="
          () => {
            copyModal.visible = false;
          }
        "
        @ok="
          () => {
            copyModal.visible = false;
            reload();
          }
        "
      />

      <rename-modal
        :model="renameModal.model"
        :visible="renameModal.visible"
        @cancel="
          () => {
            renameModal.visible = false;
          }
        "
        @ok="
          () => {
            renameModal.visible = false;
            reload();
          }
        "
      />

      <delete-modal
        :model="deleteModal.model"
        :visible="deleteModal.visible"
        @cancel="
          () => {
            deleteModal.visible = false;
          }
        "
        @ok="
          () => {
            deleteModal.visible = false;
            reload();
          }
        "
      />

      <info-modal
        :model="infoModalRef.model"
        :open="infoModalRef.open"
        @cancel="
          () => {
            infoModalRef.open = false;
          }
        "
        @ok="
          () => {
            infoModalRef.open = false;
          }
        "
      />

      <edit-bid-strategy-modal
        :model="editBidStrategyModalRef.model"
        :visible="editBidStrategyModalRef.visible"
        @cancel="
          () => {
            editBidStrategyModalRef.visible = false;
          }
        "
        @ok="
          () => {
            editBidStrategyModalRef.visible = false;
          }
        "
      />

      <multi-language-modal
        :open="multiLanguageModal.visible"
        :selected-ads="multiLanguageModal.selectedAds"
        @cancel="
          () => {
            multiLanguageModal.visible = false;
          }
        "
        @ok="
          () => {
            multiLanguageModal.visible = false;
            reload();
          }
        "
      />

      <multi-language-preview-modal
        :open="multiLanguagePreviewModal.visible"
        :ad-data="multiLanguagePreviewModal.adData"
        @cancel="
          () => {
            multiLanguagePreviewModal.visible = false;
          }
        "
      />

      <adset-language-modal
        :open="adsetLanguageModal.visible"
        :adset-data="adsetLanguageModal.adsetData"
        @cancel="
          () => {
            adsetLanguageModal.visible = false;
          }
        "
      />

      <adset-audience-modal
        :open="adsetAudienceModal.visible"
        :adset-data="adsetAudienceModal.adsetData"
        @cancel="
          () => {
            adsetAudienceModal.visible = false;
          }
        "
        @update="
          () => {
            adsetAudienceModal.visible = false;
            reload();
          }
        "
      />

      <payment-modal
        :visible="paymentModal.visible"
        :selected-data="paymentModal.selectedData"
        :tab-type="paymentModal.tabType"
        @update:visible="
          value => {
            paymentModal.visible = value;
          }
        "
      />

      <batch-budget-modal
        :visible="batchBudgetModal.visible"
        :selected-data="batchBudgetModal.selectedData"
        :tab-type="batchBudgetModal.tabType"
        @update:visible="
          value => {
            batchBudgetModal.visible = value;
          }
        "
        @success="
          () => {
            batchBudgetModal.visible = false;
            reload();
          }
        "
      />

      <!-- 保存书签Modal -->
      <a-modal
        v-model:open="saveBookmarkModal.visible"
        :title="t('Save Search Bookmark')"
        :width="580"
        :body-style="{ padding: '24px 24px 12px 24px' }"
        @ok="handleSaveBookmark"
        @cancel="
          () => {
            saveBookmarkModal.visible = false;
            saveBookmarkModal.name = '';
            saveBookmarkModal.description = '';
          }
        "
      >
        <a-form :label-col="{ span: 6 }" :wrapper-col="{ span: 18 }" :label-wrap="true">
          <a-form-item :label="t('Bookmark Name')" required :style="{ marginBottom: '24px' }">
            <a-input
              v-model:value="saveBookmarkModal.name"
              :placeholder="t('Please enter bookmark name')"
              :maxlength="50"
              size="large"
              :style="{ borderRadius: '6px' }"
            />
          </a-form-item>
          <a-form-item :label="t('Description')" :style="{ marginBottom: '16px' }">
            <a-textarea
              v-model:value="saveBookmarkModal.description"
              :placeholder="t('Please enter description (optional)')"
              :rows="4"
              :maxlength="200"
              :style="{ borderRadius: '6px' }"
              :auto-size="{ minRows: 4, maxRows: 6 }"
            />
          </a-form-item>
        </a-form>
      </a-modal>

      <!-- 批量复制广告Modal -->
      <a-modal
        v-model:open="copyAdsModal.visible"
        :title="t('Copy Ads Modal Title')"
        :width="900"
        :body-style="{ padding: '0' }"
        @ok="handleCopyAds"
        @cancel="
          () => {
            copyAdsModal.visible = false;
            copyAdsModal.selectedAds = [];
            copyAdsModal.adCounts = {};
            copyAdsModal.selectedMode = 'N-1-1';
          }
        "
        :confirm-loading="copyAdsModal.loading"
        :ok-text="t('Ad Copy Submit')"
        :cancel-text="t('Ad Copy Cancel')"
        class="copy-ads-modal"
      >
        <!-- Modal内容容器 -->
        <div class="copy-ads-content">
          <!-- 顶部信息区域 -->
          <div class="copy-ads-header">
            <!-- 复制模式选择 -->
            <div class="copy-settings-section">
              <!-- 模式选择：标签与Radio在同一行 -->
              <div class="copy-mode-selection">
                <span class="label">{{ t('Copy Mode') }}:</span>
                <a-radio-group v-model:value="copyAdsModal.selectedMode" class="mode-radio-group">
                  <a-tooltip :title="t('Copy Mode N-1-1 Detail')" placement="top">
                    <a-radio value="N-1-1" class="mode-radio">
                      {{ t('Copy Mode N-1-1') }}
                    </a-radio>
                  </a-tooltip>
                  <a-tooltip :title="t('Copy Mode 1-N-1 Detail')" placement="top">
                    <a-radio value="1-N-1" class="mode-radio">
                      {{ t('Copy Mode 1-N-1') }}
                    </a-radio>
                  </a-tooltip>
                  <a-tooltip :title="t('Copy Mode 1-1-N Detail')" placement="top">
                    <a-radio value="1-1-N" class="mode-radio">
                      {{ t('Copy Mode 1-1-N') }}
                    </a-radio>
                  </a-tooltip>
                </a-radio-group>
              </div>

              <div class="global-count-section">
                <span class="label">{{ t('Global Copy Count') }}:</span>
                <a-input-number
                  v-model:value="copyAdsModal.globalCount"
                  :min="1"
                  :max="50"
                  class="count-input"
                  @change="handleGlobalCountChange"
                />
                <a-button
                  type="primary"
                  ghost
                  size="small"
                  @click="applyGlobalCount"
                  class="apply-btn"
                >
                  {{ t('Apply to All') }}
                </a-button>
              </div>
            </div>
          </div>

          <!-- 广告列表区域 -->
          <div class="ads-list-container">
            <template v-for="(accountGroup, _accountId) in groupedAds" :key="_accountId">
              <div class="account-group">
                <div class="account-header">
                  <h4 class="account-title">{{ accountGroup.name }}</h4>
                  <span class="ads-count">{{ accountGroup.ads.length }} {{ t('ads') }}</span>
                </div>

                <div class="ads-list">
                  <template v-for="(ad, index) in accountGroup.ads" :key="ad.ad_id">
                    <div class="ad-item" :class="{ last: index === accountGroup.ads.length - 1 }">
                      <div class="ad-info">
                        <div class="ad-id">{{ ad.ad_id }}</div>
                        <div class="ad-status">
                          <span class="status-label">{{ t('Ad Status') }}:</span>
                          <a-tag
                            :color="ad.effective_status === 'ACTIVE' ? 'green' : 'orange'"
                            class="status-tag"
                          >
                            {{ ad.effective_status }}
                          </a-tag>
                        </div>
                      </div>
                      <div class="copy-count-section">
                        <span class="count-label">{{ t('Copy Count') }}:</span>
                        <a-input-number
                          v-model:value="copyAdsModal.adCounts[ad.ad_id]"
                          :min="1"
                          :max="50"
                          class="ad-count-input"
                        />
                      </div>
                    </div>
                  </template>
                </div>
              </div>
            </template>
          </div>
        </div>
      </a-modal>

      <!-- 复制广告到广告组Modal -->
      <a-modal
        v-model:open="copyAdToAdsetsModal.visible"
        :title="t('Copy Ad To Adsets Modal Title')"
        :width="600"
        @ok="handleCopyAdToAdsets"
        @cancel="
          () => {
            copyAdToAdsetsModal.visible = false;
            copyAdToAdsetsModal.adSourceId = '';
            copyAdToAdsetsModal.selectedAdsets = [];
          }
        "
        :confirm-loading="copyAdToAdsetsModal.loading"
        :ok-text="t('Copy Ad To Adsets Submit')"
        :cancel-text="t('Copy Ad To Adsets Cancel')"
      >
        <div style="margin-bottom: 16px;">
          <label style="display: block; margin-bottom: 8px; font-weight: 500;">
            {{ t('Ad Source ID') }}:
          </label>
          <a-input
            v-model:value="copyAdToAdsetsModal.adSourceId"
            :placeholder="t('Ad Source ID Placeholder')"
            style="width: 100%;"
          />
        </div>

        <div>
          <label style="display: block; margin-bottom: 8px; font-weight: 500;">
            {{ t('Selected Adsets') }} ({{ copyAdToAdsetsModal.selectedAdsets.length }}):
          </label>
          <div style="max-height: 300px; overflow-y: auto; border: 1px solid #d9d9d9; border-radius: 6px; padding: 8px;">
            <template v-for="adset in copyAdToAdsetsModal.selectedAdsets" :key="adset.adset_id">
              <div style="padding: 8px; border-bottom: 1px solid #f0f0f0; display: flex; justify-content: space-between; align-items: center;">
                <div>
                  <div style="font-weight: 500;">{{ adset.adset_id }}</div>
                  <div style="color: #666; font-size: 12px;">{{ adset.adset_name || 'N/A' }}</div>
                </div>
                <a-tag
                  :color="adset.effective_status === 'ACTIVE' ? 'green' : 'orange'"
                  size="small"
                >
                  {{ adset.effective_status }}
                </a-tag>
              </div>
            </template>
          </div>
        </div>
      </a-modal>

      <!-- 随机添加语言Modal -->
      <a-modal
        v-model:open="addLanguagesModal.visible"
        :title="t('Randomly Add Languages')"
        :width="450"
        :body-style="{ padding: '24px' }"
        @ok="handleAddLanguages"
        @cancel="
          () => {
            addLanguagesModal.visible = false;
            addLanguagesModal.languageCount = 1;
          }
        "
        :confirm-loading="addLanguagesModal.loading"
      >
        <a-form :label-col="{ span: 10 }" :wrapper-col="{ span: 14 }">
          <a-form-item :label="t('Language Count')" required>
            <a-input-number
              v-model:value="addLanguagesModal.languageCount"
              :min="1"
              :max="30"
              :placeholder="t('Enter number of languages (1-30)')"
              style="width: 100%"
            />
          </a-form-item>
          <a-form-item :label="t('Selected Ads')">
            <span>
              {{ t('{count} ads selected', { count: addLanguagesModal.selectedAds.length }) }}
            </span>
          </a-form-item>
        </a-form>
      </a-modal>

      <!-- 自定义同步交易Modal -->
      <a-modal
        v-model:open="customSyncTransactionsModal.visible"
        :title="t('pages.adc.sync.card.modal.title')"
        :confirm-loading="customSyncTransactionsModal.loading"
        @ok="handleCustomSyncTransactions"
        @cancel="customSyncTransactionsModal.visible = false"
      >
        <a-form layout="vertical">
          <a-form-item :label="t('pages.adc.sync.card.modal.time.range')">
            <a-range-picker
              v-model:value="customSyncTransactionsModal.timeRange"
              :placeholder="[t('Start date'), t('End date')]"
              :presets="[
                { label: t('pages.adc.sync.card.modal.preset.3days'), value: [dayjs().subtract(3, 'day'), dayjs()] },
                { label: t('pages.adc.sync.card.modal.preset.7days'), value: [dayjs().subtract(7, 'day'), dayjs()] },
                { label: t('pages.adc.sync.card.modal.preset.this.week'), value: [dayjs().startOf('week'), dayjs().endOf('week')] },
                { label: t('pages.adc.sync.card.modal.preset.last.week'), value: [dayjs().subtract(1, 'week').startOf('week'), dayjs().subtract(1, 'week').endOf('week')] },
                { label: t('pages.adc.sync.card.modal.preset.this.month'), value: [dayjs().startOf('month'), dayjs().endOf('month')] },
                { label: t('pages.adc.sync.card.modal.preset.last.month'), value: [dayjs().subtract(1, 'month').startOf('month'), dayjs().subtract(1, 'month').endOf('month')] },
              ]"
            />
          </a-form-item>
        </a-form>
      </a-modal>

      <!-- 自定义同步Keitaro Modal -->
      <a-modal
        v-model:open="customSyncKeitaroModal.visible"
        :title="t('pages.adc.sync.keitaro.modal.title')"
        :confirm-loading="customSyncKeitaroModal.loading"
        @ok="handleCustomSyncKeitaro"
        @cancel="customSyncKeitaroModal.visible = false"
      >
        <a-form layout="vertical">
          <a-form-item :label="t('pages.adc.sync.keitaro.modal.time.range')">
            <a-range-picker
              v-model:value="customSyncKeitaroModal.timeRange"
              :placeholder="[t('Start date'), t('End date')]"
              :presets="[
                { label: t('pages.adc.sync.keitaro.modal.preset.3days'), value: [dayjs().subtract(3, 'day'), dayjs()] },
                { label: t('pages.adc.sync.keitaro.modal.preset.7days'), value: [dayjs().subtract(7, 'day'), dayjs()] },
                { label: t('pages.adc.sync.keitaro.modal.preset.this.week'), value: [dayjs().startOf('week'), dayjs().endOf('week')] },
                { label: t('pages.adc.sync.keitaro.modal.preset.last.week'), value: [dayjs().subtract(1, 'week').startOf('week'), dayjs().subtract(1, 'week').endOf('week')] },
                { label: t('pages.adc.sync.keitaro.modal.preset.this.month'), value: [dayjs().startOf('month'), dayjs().endOf('month')] },
                { label: t('pages.adc.sync.keitaro.modal.preset.last.month'), value: [dayjs().subtract(1, 'month').startOf('month'), dayjs().subtract(1, 'month').endOf('month')] },
              ]"
            />
          </a-form-item>
        </a-form>
      </a-modal>

      <!-- 广告账户详情Modal -->
      <a-modal
        v-model:open="adAccountInfoModal.visible"
        :title="t('Ad Account Details')"
        :loading="adAccountInfoModal.loading"
        :footer="null"
        width="800px"
        @cancel="adAccountInfoModal.visible = false"
      >
        <a-spin :spinning="adAccountInfoModal.loading">
          <div v-if="adAccountInfoModal.adAccountData">
            <a-descriptions :column="1" bordered>
              <a-descriptions-item :label="t('System User')">
                {{ adAccountInfoModal.adAccountData.bms?.[0]?.name || '-' }}
              </a-descriptions-item>
              <a-descriptions-item :label="t('Name')">
                {{ adAccountInfoModal.adAccountData.name || '-' }}
              </a-descriptions-item>
              <a-descriptions-item :label="t('ID')">
                <copy-outlined
                  style="color: #1677ff; margin-right: 8px"
                  @click="copyCell(adAccountInfoModal.adAccountData.source_id)"
                />
                {{ adAccountInfoModal.adAccountData.source_id || '-' }}
              </a-descriptions-item>
              <a-descriptions-item :label="t('Tag')">
                <a-tag v-for="tag in adAccountInfoModal.adAccountData.tags" :key="tag.id">
                  {{ tag.name }}
                </a-tag>
                <span v-if="!adAccountInfoModal.adAccountData.tags?.length">-</span>
              </a-descriptions-item>
              <a-descriptions-item :label="t('Status')">
                <a-badge
                  :color="adAccountInfoModal.adAccountData.account_status === 'ACTIVE' ? 'green' : 'red'"
                  :text="adAccountInfoModal.adAccountData.account_status"
                />
              </a-descriptions-item>
              <a-descriptions-item :label="t('Timezone')">
                {{ adAccountInfoModal.adAccountData.timezone_name || '-' }}
              </a-descriptions-item>
              <a-descriptions-item :label="t('Currency')">
                {{ adAccountInfoModal.adAccountData.currency || '-' }}
              </a-descriptions-item>
              <a-descriptions-item :label="t('Spend Cap')">
                {{ adAccountInfoModal.adAccountData.spend_cap || '-' }}
              </a-descriptions-item>
              <a-descriptions-item :label="t('Balance')">
                {{ getAdAccountBalance(adAccountInfoModal.adAccountData) }}
              </a-descriptions-item>
              <a-descriptions-item :label="t('Total Spend (USD)')">
                {{ adAccountInfoModal.adAccountData.total_spent || '-' }}
              </a-descriptions-item>
              <a-descriptions-item :label="t('Payment Method')">
                {{ adAccountInfoModal.adAccountData.default_funding || '-' }}
              </a-descriptions-item>
              <a-descriptions-item :label="t('BM')">
                <div v-if="adAccountInfoModal.adAccountData.bms?.length">
                  <div v-for="bm in adAccountInfoModal.adAccountData.bms" :key="bm.id" style="margin-bottom: 4px;">
                    {{ bm.name }} ({{ bm.source_id }})
                    <copy-outlined
                      style="color: #1677ff; margin-left: 8px; cursor: pointer;"
                      @click="copyCell(bm.source_id)"
                      :title="'Copy BM ID: ' + bm.source_id"
                    />
                  </div>
                </div>
                <span v-else>-</span>
              </a-descriptions-item>
              <a-descriptions-item :label="t('Pixels')">
                <div v-if="adAccountInfoModal.adAccountData.pixels?.length">
                  <div v-for="pixel in adAccountInfoModal.adAccountData.pixels" :key="pixel.id" style="margin-bottom: 4px;">
                    <span :style="{ color: pixel.is_unavailable ? '#999' : 'inherit' }">
                      {{ pixel.name }}({{ pixel.pixel }})
                    </span>
                    <copy-outlined
                      :style="{
                        color: pixel.is_unavailable ? '#ccc' : '#1677ff',
                        marginLeft: '8px',
                        cursor: 'pointer'
                      }"
                      @click="copyCell(pixel.pixel)"
                      :title="'Copy Pixel ID: ' + pixel.pixel"
                    />
                  </div>
                </div>
                <span v-else>-</span>
              </a-descriptions-item>
              <a-descriptions-item :label="t('Auto Sync')">
                <a-switch
                  :checked="adAccountInfoModal.adAccountData.auto_sync"
                  disabled
                />
              </a-descriptions-item>
              <a-descriptions-item :label="t('Notes')">
                {{ adAccountInfoModal.adAccountData.notes || '-' }}
              </a-descriptions-item>
            </a-descriptions>
          </div>
        </a-spin>
      </a-modal>

      <!-- 产品集详情Modal -->
      <product-set-modal
        :visible="productSetModal.visible"
        :productSet="productSetModal.productSetData"
        @cancel="productSetModal.visible = false"
      />

      <!-- CBO 2 ABO Modal -->
      <a-modal
        v-model:open="cbo2AboModal.visible"
        :title="t('CBO 2 ABO Conversion')"
        :confirm-loading="cbo2AboModal.loading"
        @ok="handleCbo2Abo"
      >
        <div class="cbo-2-abo-modal-content">
          <p>{{ t('Selected campaigns') }}: {{ cbo2AboModal.campaignIds.length }}</p>
          <a-form layout="vertical">
            <a-form-item :label="t('Budget (USD)')">
              <a-input-number
                v-model:value="cbo2AboModal.budget"
                :min="1"
                :precision="1"
                :step="1"
                style="width: 100%"
                :placeholder="t('Enter budget amount')"
              />
              <div class="form-help-text">
                {{ t('Budget must be greater than or equal to 1 USD') }}
              </div>
            </a-form-item>
          </a-form>
        </div>
      </a-modal>

      <!-- ABO 2 CBO Modal -->
      <a-modal
        v-model:open="abo2CboModal.visible"
        :title="t('ABO 2 CBO Conversion')"
        :confirm-loading="abo2CboModal.loading"
        @ok="handleAbo2Cbo"
      >
        <div class="abo-2-cbo-modal-content">
          <div class="conversion-warning">
            <a-alert
              :message="t('Conversion Time Interval Warning')"
              :description="t('Please note that there must be at least 90 minutes between CBO and ABO conversions')"
              type="warning"
              show-icon
              style="margin-bottom: 16px"
            />
          </div>

          <p>{{ t('Selected campaigns') }}: {{ abo2CboModal.selectedCampaigns.length }}</p>

          <a-form layout="vertical">
            <a-form-item :label="t('Budget (USD)')">
              <a-input-number
                v-model:value="abo2CboModal.budget"
                :min="1"
                :precision="1"
                :step="1"
                style="width: 100%"
                :placeholder="t('Enter budget amount')"
              />
              <div class="form-help-text">
                {{ t('Budget must be greater than or equal to 1 USD') }}
              </div>
            </a-form-item>
          </a-form>
        </div>
      </a-modal>
    </div>
  </page-container>
</template>
<script lang="ts">
import {
  DownOutlined,
  ProfileOutlined,
  UpSquareOutlined,
  ShopOutlined,
  TabletOutlined,
  ReloadOutlined,
  CopyOutlined,
  ArrowsAltOutlined,
  FileTextTwoTone,
  EyeOutlined,
  TagOutlined,
  PlayCircleOutlined,
  PauseCircleOutlined,
  RocketOutlined,
  GlobalOutlined,
  EditOutlined,
  TeamOutlined,
  StarOutlined,
  DeleteOutlined,
  RobotOutlined,
  InfoCircleOutlined,
} from '@ant-design/icons-vue';
import {
  defineComponent,
  ref,
  computed,
  watchEffect,
  reactive,
  toRaw,
  watch,
  nextTick,
  onMounted,
  h,
} from 'vue';

import {
  queryAdAccountInsight,
  queryCampaignInsight,
  queryAdsetInsight,
  queryAdInsight,
  batchUpdateFbObjectStatus,
  getSearchBookmarks,
  createSearchBookmark,
  deleteSearchBookmark,
  addLanguagesToAds,
  setAutoAddLanguages,
  batchUpdateObjectBudget,
} from '@/api/ads';
import { archiveFbCampaigns, unarchiveFbCampaigns, cbo2Abo } from '@/api/fb_campaigns';
import {
  fetchDataRecently,
  getFbAdAccountsValidTags,
  queryFB_AD_AccountsApi,
  syncAdAccountData,
  syncAdData,
  syncAdsetData,
  syncCampaignData,
  getCampaignTags,
} from '@/api/fb_ad_accounts';
import { useTableDynamicColumns } from '@/utils/hooks/useTableColumn';
import { message } from 'ant-design-vue';
import useClipboard from 'vue-clipboard3';
import dayjs from 'dayjs';
import { useFetchData } from '@/utils/hooks/useFetchData';
import type { Pagination, TableColumn } from '@/typing';
import type { Dayjs } from 'dayjs';
import { useAuth } from '@/utils/authority';
import { Action } from '@/api/user/login';
import { useRouter } from 'vue-router';
import { getFbAccountsValidTags } from '@/api/fb_accounts';
import { useUserStore } from '@/store/user';
import { getUsers } from '@/api/user/role_v2';
import TagModal from './tag-modal.vue';
import BudgetModal from './budget-modal.vue';
import CopyModal from './copy-modal.vue';
import RenameModal from './rename-modal.vue';
import EditBidStrategyModal from './modals/edit-bid-strategy-modal.vue';
import DeleteModal from './modals/delete-modal.vue';
import MultiLanguageModal from './modals/multi-language-modal.vue';
import MultiLanguagePreviewModal from './modals/multi-language-preview-modal.vue';
import AdsetLanguageModal from './modals/adset-language-modal.vue';
import AdsetAudienceModal from './modals/adset-audience-modal.vue';
import PaymentModal from './modals/payment-modal.vue';
import BatchBudgetModal from './modals/batch-budget-modal.vue';
import { syncCardTransactions } from '@/api/virtual_cards';
import { fetchKeitaro } from '@/api/networks';

import { useI18n } from 'vue-i18n';
import { Switch } from 'ant-design-vue';
import DynamicForm from '@/components/dynamic-form/dynamic-form.vue';
import ColumnOrgnizer from '@/components/column-orgnizer/column-orgnizer.vue';
import { useTableHeight } from '@/utils/hooks/useTableHeight';
import InfoModal from './info-modal.vue';
import ProductSetModal from './modals/product-set-modal.vue';
import type { PostInfoModel, LanguageInfoModel, ApiLanguageItem } from '@/utils/fb-interfaces';
import { getLanguages, copyAds, copyAdToAdsets } from '@/api/ads';
import AppliedFilters from '@/components/applied-filters/applied-filters.vue';


type APIParams = {
  pageSize?: any;
  pageNo?: any;
  sortField?: string;
  sortOrder?: number;
  [key: string]: any;
};

type Key = string | number;

export default defineComponent({
  setup() {
    const { t } = useI18n();
    const router = useRouter();
    const activeTab = ref<string>('1');

    const userStore = useUserStore();
    const role = userStore.currentUser.role.name;
    const { tableHeight } = useTableHeight(410);
    const scroll = ref({ y: tableHeight });

    const dynamicColumnsTab1 = ref([]);
    const dynamicColumnsTab2 = ref([]);
    const dynamicColumnsTab3 = ref([]);
    const dynamicColumnsTab4 = ref([]);

    // 初始化动态列并保持同步
    watchEffect(() => {
      if (
        dynamicColumns &&
        dynamicColumns.value &&
        dynamicColumns.value.length > 0 &&
        dynamicColumnsTab1.value.length === 0
      ) {
        dynamicColumnsTab1.value = [...dynamicColumns.value];
      }
    });

    const formItems = ref([
      {
        label: 'pages.compaign.tag',
        field: 'campaign_tags',
        multiple: true,
        options: getCampaignTags().then(({ data }) =>
          data.map(tag => ({
            label: `${tag.name}`,
            value: tag.name,
          })),
        ),
      },
      {
        label: 'pages.ads.acc.tags',
        field: 'ad_account_tags',
        multiple: true,
        options: getFbAdAccountsValidTags().then(({ data }) =>
          data.map(tag => ({
            label: `${tag.name} - ${tag.user_name}`,
            value: tag.name,
          })),
        ),
      },
      {
        label: 'pages.ads.fb_acc.tags',
        field: 'fb_account_tags',
        multiple: true,
        options: getFbAccountsValidTags().then(({ data }) =>
          data.map(tag => ({
            label: `${tag.name} - ${tag.user_name}`,
            value: tag.name,
          })),
        ),
      },
      { label: 'pages.ads.ad.acc_name', field: 'ad_account_names', multiple: true },
      { label: 'pages.ads.ad.acc_id', field: 'ad_account_ids', multiple: true },
      { label: 'Camp/Adst/Ad ID', field: 'child_source_ids', multiple: true },
      { label: 'pages.compaign.name', field: 'campaign_names', multiple: true },
      { label: 'BM Name', field: 'bm_names', multiple: true },
      { label: 'BM ID', field: 'bm_ids', multiple: true },
      { label: 'Cards', field: 'cards', multiple: true },

      { label: 'FB Account Name', field: 'account_names', multiple: true },
      { label: 'FB Account ID', field: 'account_ids', multiple: true },
      { label: 'Page Name', field: 'page_names', multiple: true },
      { label: 'Page ID', field: 'page_ids', multiple: true },

      {
        label: 'pages.adc.status',
        field: 'account_status',
        multiple: true,
        options: [
          { label: 'ACTIVE', value: 'ACTIVE' },
          { label: 'DISABLED', value: 'DISABLED' },
          { label: 'UNSETTLED', value: 'UNSETTLED' },
        ],
      },
      {
        label: 'pages.adc.is_archived',
        field: 'is_archived',
        mode: 'radio',
        options: [
          { label: 'pages.adc.is_archived.all', value: '' },
          { label: 'pages.adc.is_archived.yes', value: 'true' },
          { label: 'pages.adc.is_archived.no', value: 'false' },
        ],
      },
      // {
      //   label: 'pages.ads.sysuser',
      //   field: 'user_ids',
      //   multiple: true,
      //   options: getUsers().then(({ data }) =>
      //     data.map(({ name, id }) => ({
      //       label: name,
      //       value: id,
      //     })),
      //   ),
      // },
      { label: 'pages.compaign.name', field: 'campaign_names', multiple: true },
      {
        label: 'pages.ads.others',
        field: 'others',
        multiple: true,
        options: [{ label: 'Exclude Archived Camp', value: 'exclude_archived_campaign' }],
      },
    ]);
    const appliedFilters = ref({});
    const onSearch = data => {
      Object.entries(data).forEach(([key, value]) => (queryAdAccountParam[key] = value));
      appliedFilters.value = data;
      handleSearch(true); // 用户主动搜索，重置分页
    };

    const getAdAccountInsight = (params: APIParams) => {
      if (!params['ad_account_ids']) {
        return Promise.resolve({ data: [], totalCount: 0 });
      }
      return queryAdAccountInsight(params);
    };

    const getCampaignInsight = (params: APIParams) => {
      if (!params['campaign_ids']) {
        console.log('没有 campaign_ids参数');
        return Promise.resolve({ data: [], totalCount: 0 });
      }
      return queryCampaignInsight(params);
    };

    const getAdsetInsight = (params: APIParams) => {
      if (!params['adset_ids']) {
        return Promise.resolve({ data: [], totalCount: 0 });
      }
      return queryAdsetInsight(params);
    };

    const getAdInsight = (params: APIParams) => {
      if (!params['ad_ids']) {
        return Promise.resolve({ data: [], totalCount: 0 });
      }
      return queryAdInsight(params);
    };

    const onRequestError = e => {
      console.error('请求错误: ', e);
      message.error(t('Request error'));
    };

    const queryParam = reactive({
      date_start: undefined,
      date_stop: undefined,
      ad_account_ids: [],
      sortOrder: undefined,
      sortField: undefined,
      campaign_names: [],
      campaign_tags: [],
      others: ['exclude_archived_campaign'],
    });

    const effective_status_map = [
      { text: 'ACTIVE', value: 'ACTIVE' },
      { text: 'PAUSED', value: 'PAUSED' },
      { text: 'DELETED', value: 'DELETED' },
      { text: 'PENDING_REVIEW', value: 'PENDING_REVIEW' },
      { text: 'DISAPPROVED', value: 'DISAPPROVED' },
      { text: 'PREAPPROVED', value: 'PREAPPROVED' },
      { text: 'PENDING_BILLING_INFO', value: 'PENDING_BILLING_INFO' },
      { text: 'CAMPAIGN_PAUSED', value: 'CAMPAIGN_PAUSED' },
      { text: 'ARCHIVED', value: 'ARCHIVED' },
      { text: 'ADSET_PAUSED', value: 'ADSET_PAUSED' },
      { text: 'IN_PROCESS', value: 'IN_PROCESS' },
      { text: 'WITH_ISSUES', value: 'WITH_ISSUES' },
    ];

    // 根据 ad account id fetch campaign id
    const fetchDataContext = reactive({
      current: 1,
      pageSize: 10,
      // tableSize: 'middle', // 'default' | 'middle' | 'small'
      // stripe: false,
      requestParams: { ...queryParam },
    });
    const adAccountColumns = ref<TableColumn[]>([
      {
        title: t('pages.ads.index'),
        dataIndex: 'index',
        customRender: ({ index }) => {
          return `${index + 1}`;
        },
        width: 80,
        align: 'center',
        fixed: 'left',
      },
      {
        title: t('pages.ads.ad.acc'),
        dataIndex: 'ad_account_id',
        ellipsis: true,
        align: 'left',
        minWidth: 190,
        resizable: true,
        sorter: (a, b) => a.ad_account_name - b.ad_account_name,
        fixed: 'left',
      },
      {
        title: 'Tags',
        dataIndex: 'tags',
        minWidth: 100,
        resizable: true,
        ellipsis: true,
      },
      {
        title: t('pages.ads.currency'),
        dataIndex: 'currency',
        align: 'center',
        minWidth: 80,
      },
      {
        title: t('pages.ads.acc.status'),
        dataIndex: 'account_status',
        align: 'center',
        minWidth: 140,
      },
      {
        title: 'Spend',
        dataIndex: 'spend',
        sorter: (a, b) => a.spend - b.spend,
        minWidth: 100,
        resizable: true,
        align: 'center',
        customRender: ({ text }) => {
          if (text !== '0') {
            return `${text}`;
          } else {
            return '-';
          }
        },
      },
      {
        title: 'Revenue',
        dataIndex: 'offer_conversions_value',
        sorter: (a, b) => a.offer_conversions_value - b.offer_conversions_value,
        minWidth: 140,
        resizable: true,
        align: 'center',
        customRender: ({ text }) => {
          return `${text} USD`;
        },
      },
      {
        title: 'ROI',
        dataIndex: 'roi',
        sorter: (a, b) => a.roi - b.roi,
        minWidth: 80,
        resizable: true,
        align: 'center',
      },
      {
        title: 'Roas(FB)',
        dataIndex: 'purchase_roas',
        sorter: (a, b) => a.purchase_roas - b.purchase_roas,
        minWidth: 120,
        resizable: true,
        align: 'center',
      },
      {
        title: 'Lead(FB)',
        dataIndex: 'lead',
        sorter: (a, b) => a.lead - b.lead,
        minWidth: 110,
        resizable: true,
        align: 'center',
        ellipsis: false,
      },
      {
        title: 'CPL(FB)',
        dataIndex: 'cost_per_lead',
        sorter: (a, b) => a.cost_per_lead - b.cost_per_lead,
        minWidth: 120,
        resizable: true,
        align: 'center',
      },
      {
        title: 'Purchase',
        dataIndex: 'offer_conversions',
        sorter: (a, b) => a.offer_conversions - b.offer_conversions,
        minWidth: 140,
        resizable: true,
        align: 'center',
      },
      {
        title: 'Offer Clicks',
        dataIndex: 'offer_clicks',
        sorter: (a, b) => a.offer_clicks - b.offer_clicks,
        minWidth: 140,
        resizable: true,
        align: 'center',
      },
      {
        title: 'Offer Leads',
        dataIndex: 'offer_leads',
        sorter: (a, b) => a.offer_leads - b.offer_leads,
        minWidth: 140,
        resizable: true,
        align: 'center',
      },
      {
        title: 'Offer EPC',
        dataIndex: 'offer_epc',
        sorter: (a, b) => a.offer_epc - b.offer_epc,
        minWidth: 140,
        resizable: true,
        align: 'center',
      },
      {
        title: 'Offer CPC',
        dataIndex: 'offer_cpc',
        sorter: (a, b) => a.offer_cpc - b.offer_cpc,
        minWidth: 140,
        resizable: true,
        align: 'center',
      },
      {
        title: 'Offer CPL',
        dataIndex: 'offer_cpl',
        sorter: (a, b) => a.offer_cpl - b.offer_cpl,
        minWidth: 140,
        resizable: true,
        align: 'center',
      },
      {
        title: 'Offer EPL',
        dataIndex: 'offer_epl',
        sorter: (a, b) => a.offer_epl - b.offer_epl,
        minWidth: 140,
        resizable: true,
        align: 'center',
      },
      {
        title: 'Link Clicks',
        dataIndex: 'link_clicks',
        sorter: (a, b) => a.link_clicks - b.link_clicks,
        minWidth: 140,
        resizable: true,
        align: 'center',
      },
      {
        title: 'Link CTR',
        dataIndex: 'link_ctr',
        sorter: (a, b) => a.link_ctr - b.link_ctr,
        minWidth: 130,
        resizable: true,
        align: 'center',
      },
      {
        title: 'Link CPC',
        dataIndex: 'link_cpc',
        sorter: (a, b) => a.link_cpc - b.link_cpc,
        minWidth: 130,
        resizable: true,
        align: 'center',
      },
      {
        title: 'Taken Rate',
        dataIndex: 'taken_rate',
        sorter: (a, b) => a.taken_rate - b.taken_rate,
        minWidth: 130,
        resizable: true,
        align: 'center',
      },
      {
        title: 'CPM',
        dataIndex: 'cpm',
        sorter: (a, b) => a.cpm - b.cpm,
        minWidth: 130,
        resizable: true,
        align: 'center',
      },
      {
          title: t('Refresh Time'),
          dataIndex: 'refresh_time',
          sorter: (a, b) => {
            return new Date(a.refresh_time).getTime() - new Date(b.refresh_time).getTime();
          },
          minWidth: 160,
          resizable: true,
          align: 'center',
      },
    ]);

    const needRowIndex = ref(false);
    const {
      state: columnState,
      dynamicColumns,
      dynamicColumnItems,
      handleColumnAllClick,
      handleColumnChange,
      reset,
      move,
    } = useTableDynamicColumns(adAccountColumns as any, { needRowIndex });

    const handleSyncOne = (record: any) => {
      console.log('sync one');
      console.log(record);
    };

    // 选中 AdAccount
    const selectedAdAccountState = reactive<{
      selectedRowKeys: Key[];
      selectedRows: any[];
    }>({
      selectedRowKeys: [],
      selectedRows: [],
    });

    const all_campaign_ids = ref([]);

    const { context: adAccountTableDataState, reload: reloadAdAccountTabe } = useFetchData(
      getAdAccountInsight as any,
      fetchDataContext,
      {
        onRequestError: onRequestError,
      },
    );
    // 监听 ad account insight 的变量,有变化了就更新 campaign_id, 但是不更新到 campaignInsight 的context里面
    // 只有用户切到了 campaign insight 的 tab 后才更新
    // TODO: 如果用户点击了clear
    watch(adAccountTableDataState, newState => {
      if (newState) {
        console.log('ad account insight state 变化了');
        console.log(newState);
        let tmp_ids = [];
        newState.dataSource.forEach(item => {
          tmp_ids = tmp_ids.concat(item['campaign_ids']);
        });
        all_campaign_ids.value = tmp_ids;

        // 根据新的状态更新 selectedAdAccountState.selectedRows
        selectedAdAccountState.selectedRows = newState.dataSource.filter(
          item => selectedAdAccountState.selectedRowKeys.includes(item.id), // 假设每个 item 有一个 id 属性
        );
      }
    });

    // 广告账户页面的回调 - 分页是针对第一层的Ad Account获取
    const handleTableChange = ({ current, pageSize }: Pagination, _filters: any, _sorter: any) => {
      // filteredInfoMap.value = filters;
      // sorterInfoMap.value = sorter;
      fetchAdAccountContext.current = current;
      fetchAdAccountContext.pageSize = pageSize;

      // **关键修复：排除分页参数，让useFetchData完全控制分页**
      // 从queryAdAccountParam中过滤掉分页相关参数，避免覆盖useFetchData的分页控制
      const { pageNo, pageSize: _, ...searchParams } = queryAdAccountParam as any;
      fetchAdAccountContext.requestParams = searchParams;
    };

    const handleActionManuClick = (e: any, record: any) => {
      console.log(e, record);
    };

    const showManageTagsModal = action => {
      let uniqueTags = [];
      if (activeTab.value === '1') {
        // 获取所有选中行的tags
        const selectedIds = selectedAdAccountState.selectedRowKeys;
        if (selectedIds.length === 0) {
          message.error('Please select ad account');
          return;
        }
        const allTags = selectedAdAccountState.selectedRows.reduce((prev, curr) => {
          return prev.concat(curr.tags);
        }, []);

        // 去重
        const uniqueTags = allTags.reduce((prev, curr) => {
          if (curr.name && !prev.includes(curr.name)) {
            prev.push(curr.name);
          }
          return prev;
        }, []);
        tagModal.model = {
          ids: selectedIds,
          action: action,
          tagList: uniqueTags,
          modelType: 'fbadaccount',
        };
      } else if (activeTab.value === '2') {
        // 获取所有选中行的tags
        const selectedIds = selectedCampaignState.selectedRowKeys;
        if (selectedIds.length === 0) {
          message.error('Please select campaign');
          return;
        }
        console.log('selected rows: ', selectedCampaignState.selectedRows);

        const allTags = selectedCampaignState.selectedRows.reduce((prev, curr) => {
          return prev.concat(curr.tags);
        }, []);
        console.log('all tags: ', allTags);

        // 去重
        const uniqueTags = allTags.reduce((prev, curr) => {
          if (!prev.includes(curr.name)) {
            prev.push(curr.name);
          }
          return prev;
        }, []);
        tagModal.model = {
          ids: selectedIds,
          action: action,
          tagList: uniqueTags,
          modelType: 'fbcampaigns',
        };
      } else if (activeTab.value === '3') {
        // 获取所有选中行的tags
        const selectedIds = selectedAdsetState.selectedRowKeys;
        if (selectedIds.length === 0) {
          message.error('Please select adset');
          return;
        }
        const allTags = selectedAdsetState.selectedRows.reduce((prev, curr) => {
          return prev.concat(curr.tags);
        }, []);

        // 去重
        uniqueTags = allTags.reduce((prev, curr) => {
          if (!prev.includes(curr.name)) {
            prev.push(curr.name);
          }
          return prev;
        }, []);
        tagModal.model = {
          ids: selectedIds,
          action: action,
          tagList: uniqueTags,
          modelType: 'fbadset',
        };
      } else if (activeTab.value === '4') {
        // 获取所有选中行的tags
        const selectedIds = selectedAdState.selectedRowKeys;
        if (selectedIds.length === 0) {
          message.error('Please select ad');
          return;
        }
        const allTags = selectedAdState.selectedRows.reduce((prev, curr) => {
          return prev.concat(curr.tags);
        }, []);

        // 去重
        uniqueTags = allTags.reduce((prev, curr) => {
          if (!prev.includes(curr.name)) {
            prev.push(curr.name);
          }
          return prev;
        }, []);
        tagModal.model = {
          ids: selectedIds,
          action: action,
          tagList: uniqueTags,
          modelType: 'fbads',
        };
      }
      tagModal.visible = true;
    };

    const updateCampaignStatus = targetStatus => {
      const oppositeStatus = targetStatus === 'ACTIVE' ? 'PAUSED' : 'ACTIVE';
      const selectedIds = selectedCampaignState.selectedRowKeys;
      if (selectedIds.length === 0) {
        message.error('Please select campaign');
        return;
      }
      const allCampaignIds = selectedCampaignState.selectedRows.reduce((prev, curr) => {
        if (
          !curr.is_deleted_on_fb &&
          curr.account_status === 'ACTIVE' &&
          curr.status === oppositeStatus
        ) {
          return prev.concat(curr.campaign_id);
        }
        return prev;
      }, []);
      batchUpdateFbObjectStatus({
        ids: allCampaignIds, // 使用解构赋值直接访问 campaign_id
        object_type: 'campaign',
        status: targetStatus,
      })
        .then(res => {
          message.info(res['message']);
        })
        .catch(err => {
          console.error(err);
          message.error('Request failed');
        })
        .finally(() => {
          reloadCampaignTable();
        });
    };

    const updateAdsetStatus = targetStatus => {
      const oppositeStatus = targetStatus === 'ACTIVE' ? 'PAUSED' : 'ACTIVE';
      const selectedIds = selectedAdsetState.selectedRowKeys;
      if (selectedIds.length === 0) {
        message.error('Please select adset');
        return;
      }
      const allAdsetIds = selectedAdsetState.selectedRows.reduce((prev, curr) => {
        if (
          !curr.is_deleted_on_fb &&
          curr.account_status === 'ACTIVE' &&
          curr.status === oppositeStatus
        ) {
          return prev.concat(curr.adset_id);
        }
        return prev;
      }, []);
      batchUpdateFbObjectStatus({
        ids: allAdsetIds,
        object_type: 'adset',
        status: targetStatus,
      })
        .then(res => {
          message.info(res['message']);
        })
        .catch(err => {
          console.error(err);
          message.error('Request failed');
        })
        .finally(() => {
          reloadAdsetTable();
        });
    };

    const updateAdStatus = targetStatus => {
      const oppositeStatus = targetStatus === 'ACTIVE' ? 'PAUSED' : 'ACTIVE';
      const selectedIds = selectedAdState.selectedRowKeys;
      if (selectedIds.length === 0) {
        message.error('Please select ad');
        return;
      }
      const allAdIds = selectedAdState.selectedRows.reduce((prev, curr) => {
        if (
          !curr.is_deleted_on_fb &&
          curr.account_status === 'ACTIVE' &&
          curr.status === oppositeStatus
        ) {
          return prev.concat(curr.ad_id);
        }
        return prev;
      }, []);
      batchUpdateFbObjectStatus({
        ids: allAdIds,
        object_type: 'ad',
        status: targetStatus,
      })
        .then(res => {
          message.info(res['message']);
        })
        .catch(err => {
          console.error(err);
          message.error('Request failed');
        })
        .finally(() => {
          reloadAdTable();
        });
    };

    const updateFbItemStatus = targetStatus => {
      if (activeTab.value === '2') {
        updateCampaignStatus(targetStatus);
      } else if (activeTab.value === '3') {
        updateAdsetStatus(targetStatus);
      } else if (activeTab.value === '4') {
        updateAdStatus(targetStatus);
      }
    };

    const handleMenuClick = (e: any) => {
      const key = e.key;

      if (key === 'archive-campaign') {
        const selectedIds = selectedCampaignState.selectedRowKeys;
        if (selectedIds.length === 0) {
          message.error('Please select campaign');
          return;
        }
        archiveFbCampaigns({
          ids: selectedIds,
        })
          .then(res => {
            message.info(res['message']);
            reloadCampaignTable();
          })
          .catch(() => {
            message.error('Error');
          });
      } else if (key === 'unarchive-campaign') {
        const selectedIds = selectedCampaignState.selectedRowKeys;
        if (selectedIds.length === 0) {
          message.error('Please select campaign');
          return;
        }
        unarchiveFbCampaigns({
          ids: selectedIds,
        })
          .then(res => {
            message.info(res['message']);
            reloadCampaignTable();
          })
          .catch(() => {
            message.error('Error');
          });
      } else if (key === 'turn-on-campaign') {
        updateCampaignStatus('ACTIVE');
      } else if (key === 'turn-off-campaign') {
        updateCampaignStatus('PAUSED');
      } else if (key === 'turn-on-adset') {
        updateAdsetStatus('ACTIVE');
      } else if (key === 'turn-off-adset') {
        updateAdsetStatus('PAUSED');
      } else if (key === 'turn-on-ad') {
        updateAdStatus('ACTIVE');
      } else if (key === 'turn-off-ad') {
        updateAdStatus('PAUSED');
      } else if (key === 'add-tags') {
        showManageTagsModal('add');
      } else if (key === 'delete-tags') {
        showManageTagsModal('delete');
      } else if (key === 'sync-3-days') {
        let selectedAdAccountIds = [];
        if (activeTab.value === '1') {
          if (selectedAdAccountState.selectedRowKeys.length === 0) {
            message.error('Please select ad account');
            return;
          }
          selectedAdAccountIds = selectedAdAccountState.selectedRowKeys;
        } else if (activeTab.value === '2') {
          const selectedIds = selectedCampaignState.selectedRowKeys;
          if (selectedIds.length === 0) {
            message.error('Please select campaign');
            return;
          }
          // 获取所有选中行的 ad account ulid
          selectedAdAccountIds = selectedCampaignState.selectedRows.reduce((prev, curr) => {
            return prev.concat(curr.ad_account_ulid);
          }, []);
        } else if (activeTab.value === '3') {
          const selectedIds = selectedAdsetState.selectedRowKeys;
          if (selectedIds.length === 0) {
            message.error('Please select adset');
            return;
          }
          // 获取所有选中行的 ad account ulid
          selectedAdAccountIds = selectedAdsetState.selectedRows.reduce((prev, curr) => {
            return prev.concat(curr.ad_account_ulid);
          }, []);
        } else if (activeTab.value === '4') {
          const selectedIds = selectedAdState.selectedRowKeys;
          if (selectedIds.length === 0) {
            message.error('Please select ad');
            return;
          }
          // 获取所有选中行的 ad account ulid
          selectedAdAccountIds = selectedAdState.selectedRows.reduce((prev, curr) => {
            return prev.concat(curr.ad_account_ulid);
          }, []);
        }
        const uniqAdAccountIds = [...new Set(selectedAdAccountIds)];
        fetchDataRecently({
          fb_ad_account_ids: uniqAdAccountIds,
          days: 3,
        })
          .then(res => {
            message.success(res['message']);
          })
          .catch(e => {
            console.log(e);
          });
      } else if (key === 'copy-adset') {
        console.log('copy-adset');
        if (selectedAdsetState.selectedRowKeys.length === 0) {
          message.warning('Please select adset');
          return;
        }

        const allAdsetIds = selectedAdsetState.selectedRows.reduce((prev, curr) => {
          if (!curr.is_deleted_on_fb && curr.account_status === 'ACTIVE') {
            return prev.concat(curr.adset_id);
          }
          return prev;
        }, []);

        copyModal.model = {
          ids: allAdsetIds,
          object_type: 'adset',
        };

        copyModal.visible = true;
      } else if (key === 'copy-ad-to-adsets') {
        console.log('copy-ad-to-adsets');
        if (selectedAdsetState.selectedRowKeys.length === 0) {
          message.warning(t('Please Select Adsets'));
          return;
        }

        const validAdsets = selectedAdsetState.selectedRows.filter(adset => {
          return !adset.is_deleted_on_fb && adset.account_status === 'ACTIVE';
        });

        if (validAdsets.length === 0) {
          message.warning(t('Please Select Adsets'));
          return;
        }

        copyAdToAdsetsModal.selectedAdsets = validAdsets;
        copyAdToAdsetsModal.adSourceId = '';
        copyAdToAdsetsModal.visible = true;
      } else if (key === 'copy-campaign') {
        console.log('copy-campaign');
        if (selectedCampaignState.selectedRowKeys.length === 0) {
          message.warning('Please select campaign');
          return;
        }

        const allCampaignIds = selectedCampaignState.selectedRows.reduce((prev, curr) => {
          if (!curr.is_deleted_on_fb && curr.account_status === 'ACTIVE') {
            return prev.concat(curr.campaign_id);
          }
          return prev;
        }, []);

        copyModal.model = {
          ids: allCampaignIds,
          object_type: 'campaign',
        };

        copyModal.visible = true;
      } else if (key === 'copy-ads') {
        console.log('copy-ads');
        if (selectedAdState.selectedRowKeys.length === 0) {
          message.warning(t('Copy Ads Please Select'));
          return;
        }

        const validAds = selectedAdState.selectedRows.filter(ad => {
          return !ad.is_deleted_on_fb && ad.account_status === 'ACTIVE';
        });

        if (validAds.length === 0) {
          message.warning(t('No valid ads selected'));
          return;
        }

        copyAdsModal.selectedAds = validAds;
        copyAdsModal.selectedMode = 'N-1-1';
        copyAdsModal.visible = true;
      } else if (key === 'export') {
        let reqParams = {};
        let func;
        let reportFilename = '';

        if (activeTab.value === '1') {
          func = queryAdAccountInsight;
          reportFilename = `export-ad-account-report-${dayjs().format('YYYY-MM-DD-HH-mm')}.csv`;
          reqParams = toRaw(fetchDataContext.requestParams);
        } else if (activeTab.value === '2') {
          func = queryCampaignInsight;
          reportFilename = `export-campaign-report-${dayjs().format('YYYY-MM-DD-HH-mm')}.csv`;
          reqParams = toRaw(fetchCampaignInsightContext.requestParams);
        } else if (activeTab.value === '3') {
          func = queryAdsetInsight;
          reportFilename = `export-adset-report-${dayjs().format('YYYY-MM-DD-HH-mm')}.csv`;
          reqParams = toRaw(fetchAdsetInsightContext.requestParams);
        } else if (activeTab.value === '4') {
          func = queryAdInsight;
          reportFilename = `export-ad-report-${dayjs().format('YYYY-MM-DD-HH-mm')}.csv`;
          reqParams = toRaw(fetchAdInsightContext.requestParams);
        }
        reqParams['export'] = true;

        func(reqParams)
          .then(res => {
            const url = window.URL.createObjectURL(new Blob([res as unknown as BlobPart]));
            const link = document.createElement('a');
            link.href = url;
            link.setAttribute('download', reportFilename);
            document.body.appendChild(link);
            link.click();
            message.success(t('Report will download soon'));
          })
          .catch(err => {
            console.error(err);
            message.error('An error occurred while downloading the file');
          });
      } else if (key === 'view-ad-account') {
        viewAdAccount();
      } else if (key === 'sync-ad-account-data') {
        console.log('sync ad account only');
        if (selectedAdAccountState.selectedRowKeys.length === 0) {
          message.error(t('Please select ad account'));
          return;
        }
        if (dateDifference.value > 15) {
          message.error(t('时间范围最大不能超过15天,请减小时间范围'));
          return;
        }
        syncAdAccountData({
          fb_ad_account_ids: selectedAdAccountState.selectedRowKeys,
          date_start: dayjs(date_range.value[0]).format('YYYY-MM-DD'),
          date_stop: dayjs(date_range.value[1]).format('YYYY-MM-DD'),
        })
          .then(res => {
            message.success(res['message']);
          })
          .catch(err => {
            console.log(err);
            message.error(t('Error occured'));
          });
      } else if (key === 'sync-campaign-data') {
        console.log('sync campaign only');
        if (selectedCampaignState.selectedRowKeys.length === 0) {
          message.error(t('Please select campaign'));
          return;
        }
        if (dateDifference.value > 15) {
          message.error(t('时间范围最大不能超过15天,请减小时间范围'));
          return;
        }
        syncCampaignData({
          campaign_ids: selectedCampaignState.selectedRowKeys,
          date_start: dayjs(date_range.value[0]).format('YYYY-MM-DD'),
          date_stop: dayjs(date_range.value[1]).format('YYYY-MM-DD'),
        })
          .then(res => {
            message.success(res['message']);
          })
          .catch(err => {
            console.log(err);
            message.error(t('Error occured'));
          });
      } else if (key === 'sync-adset-data') {
        console.log('sync adset only');
        if (selectedAdsetState.selectedRowKeys.length === 0) {
          message.error(t('Please select adset'));
          return;
        }
        if (dateDifference.value > 15) {
          message.error(t('时间范围最大不能超过15天,请减小时间范围'));
          return;
        }
        syncAdsetData({
          adset_ids: selectedAdsetState.selectedRowKeys,
          date_start: dayjs(date_range.value[0]).format('YYYY-MM-DD'),
          date_stop: dayjs(date_range.value[1]).format('YYYY-MM-DD'),
        })
          .then(res => {
            message.success(res['message']);
          })
          .catch(err => {
            console.log(err);
            message.error(t('Error occured'));
          });
      } else if (key === 'sync-ad-data') {
        console.log('sync ad only');
        if (selectedAdState.selectedRowKeys.length === 0) {
          message.error(t('Please select ad'));
          return;
        }
        if (dateDifference.value > 15) {
          message.error(t('时间范围最大不能超过15天,请减小时间范围'));
          return;
        }
        syncAdData({
          ad_ids: selectedAdState.selectedRowKeys,
          date_start: dayjs(date_range.value[0]).format('YYYY-MM-DD'),
          date_stop: dayjs(date_range.value[1]).format('YYYY-MM-DD'),
        })
          .then(res => {
            message.success(res['message']);
          })
          .catch(err => {
            console.log(err);
            message.error(t('Error occured'));
          });
      } else if (key === 'sync-data-mode-1') {
        //同步花费
        console.log('sync mode 1');
        if (dateDifference.value > 15) {
          message.error(t('Duration should less than 15 days'));
          return;
        }
        if (activeTab.value === '1') {
          // 同步广告帐号花费
          if (selectedAdAccountState.selectedRowKeys.length === 0) {
            message.error(t('Please select ad account'));
            return;
          } else {
            syncAdAccountData({
              fb_ad_account_ids: selectedAdAccountState.selectedRowKeys,
              date_start: dayjs(date_range.value[0]).format('YYYY-MM-DD'),
              date_stop: dayjs(date_range.value[1]).format('YYYY-MM-DD'),
              mode: 1,
            })
              .then(res => {
                message.success(res['message']);
              })
              .catch(err => {
                console.log(err);
                message.error(t('Error occured'));
              });
          }
        } else if (activeTab.value === '2') {
          // 同步 Campaign 花费
          if (selectedCampaignState.selectedRowKeys.length === 0) {
            message.error(t('Please select ad account'));
            return;
          } else {
            syncCampaignData({
              campaign_ids: selectedCampaignState.selectedRowKeys,
              date_start: dayjs(date_range.value[0]).format('YYYY-MM-DD'),
              date_stop: dayjs(date_range.value[1]).format('YYYY-MM-DD'),
              mode: 1,
            })
              .then(res => {
                message.success(res['message']);
              })
              .catch(err => {
                console.log(err);
                message.error(t('Error occured'));
              });
          }
        } else if (activeTab.value === '3') {
          // 同步 Adset 花费
          if (selectedAdsetState.selectedRowKeys.length === 0) {
            message.error(t('Please select ad account'));
            return;
          } else {
            syncAdsetData({
              adset_ids: selectedAdsetState.selectedRowKeys,
              date_start: dayjs(date_range.value[0]).format('YYYY-MM-DD'),
              date_stop: dayjs(date_range.value[1]).format('YYYY-MM-DD'),
              mode: 1,
            })
              .then(res => {
                message.success(res['message']);
              })
              .catch(err => {
                console.log(err);
                message.error(t('Error occured'));
              });
          }
        } else if (activeTab.value === '4') {
          // 同步 Ad 花费
          if (selectedAdState.selectedRowKeys.length === 0) {
            message.error(t('Please select ad account'));
            return;
          } else {
            syncAdData({
              ad_ids: selectedAdState.selectedRowKeys,
              date_start: dayjs(date_range.value[0]).format('YYYY-MM-DD'),
              date_stop: dayjs(date_range.value[1]).format('YYYY-MM-DD'),
              mode: 1,
            })
              .then(res => {
                message.success(res['message']);
              })
              .catch(err => {
                console.log(err);
                message.error(t('Error occured'));
              });
          }
        }
      } else if (key === 'sync-data-mode-2') {
        // 只同步状态
        if (activeTab.value === '1') {
          if (selectedAdAccountState.selectedRowKeys.length === 0) {
            message.error(t('Please select ad account'));
            return;
          } else {
            syncAdAccountData({
              fb_ad_account_ids: selectedAdAccountState.selectedRowKeys,
              date_start: dayjs(date_range.value[0]).format('YYYY-MM-DD'),
              date_stop: dayjs(date_range.value[1]).format('YYYY-MM-DD'),
              mode: 2,
            })
              .then(res => {
                message.success(res['message']);
              })
              .catch(err => {
                console.log(err);
                message.error(t('Error occured'));
              });
          }
        } else if (activeTab.value === '2') {
          if (selectedCampaignState.selectedRowKeys.length === 0) {
            message.error(t('Please select ad account'));
            return;
          } else {
            syncCampaignData({
              campaign_ids: selectedCampaignState.selectedRowKeys,
              date_start: dayjs(date_range.value[0]).format('YYYY-MM-DD'),
              date_stop: dayjs(date_range.value[1]).format('YYYY-MM-DD'),
              mode: 2,
            })
              .then(res => {
                message.success(res['message']);
              })
              .catch(err => {
                console.log(err);
                message.error(t('Error occured'));
              });
          }
        } else if (activeTab.value === '3') {
          if (selectedAdsetState.selectedRowKeys.length === 0) {
            message.error(t('Please select ad account'));
            return;
          } else {
            syncAdsetData({
              adset_ids: selectedAdsetState.selectedRowKeys,
              date_start: dayjs(date_range.value[0]).format('YYYY-MM-DD'),
              date_stop: dayjs(date_range.value[1]).format('YYYY-MM-DD'),
              mode: 2,
            })
              .then(res => {
                message.success(res['message']);
              })
              .catch(err => {
                console.log(err);
                message.error(t('Error occured'));
              });
          }
        } else if (activeTab.value === '4') {
          if (selectedAdState.selectedRowKeys.length === 0) {
            message.error(t('Please select ad account'));
            return;
          } else {
            syncAdData({
              ad_ids: selectedAdState.selectedRowKeys,
              date_start: dayjs(date_range.value[0]).format('YYYY-MM-DD'),
              date_stop: dayjs(date_range.value[1]).format('YYYY-MM-DD'),
              mode: 2,
            })
              .then(res => {
                message.success(res['message']);
              })
              .catch(err => {
                console.log(err);
                message.error(t('Error occured'));
              });
          }
        }
      } else if (key === 'sync-data-mode-3') {
        // 只同步状态
        if (activeTab.value === '1') {
          if (selectedAdAccountState.selectedRowKeys.length === 0) {
            message.error(t('Please select ad account'));
            return;
          } else {
            syncAdAccountData({
              fb_ad_account_ids: selectedAdAccountState.selectedRowKeys,
              date_start: dayjs(date_range.value[0]).format('YYYY-MM-DD'),
              date_stop: dayjs(date_range.value[1]).format('YYYY-MM-DD'),
              mode: 3,
            })
              .then(res => {
                message.success(res['message']);
              })
              .catch(err => {
                console.log(err);
                message.error(t('Error occured'));
              });
          }
        } else if (activeTab.value === '2') {
          if (selectedCampaignState.selectedRowKeys.length === 0) {
            message.error(t('Please select ad account'));
            return;
          } else {
            syncCampaignData({
              campaign_ids: selectedCampaignState.selectedRowKeys,
              date_start: dayjs(date_range.value[0]).format('YYYY-MM-DD'),
              date_stop: dayjs(date_range.value[1]).format('YYYY-MM-DD'),
              mode: 3,
            })
              .then(res => {
                message.success(res['message']);
              })
              .catch(err => {
                console.log(err);
                message.error(t('Error occured'));
              });
          }
        } else if (activeTab.value === '3') {
          if (selectedAdsetState.selectedRowKeys.length === 0) {
            message.error(t('Please select ad account'));
            return;
          } else {
            syncAdsetData({
              adset_ids: selectedAdsetState.selectedRowKeys,
              date_start: dayjs(date_range.value[0]).format('YYYY-MM-DD'),
              date_stop: dayjs(date_range.value[1]).format('YYYY-MM-DD'),
              mode: 3,
            })
              .then(res => {
                message.success(res['message']);
              })
              .catch(err => {
                console.log(err);
                message.error(t('Error occured'));
              });
          }
        } else if (activeTab.value === '4') {
          if (selectedAdState.selectedRowKeys.length === 0) {
            message.error(t('Please select ad account'));
            return;
          } else {
            syncAdData({
              ad_ids: selectedAdState.selectedRowKeys,
              date_start: dayjs(date_range.value[0]).format('YYYY-MM-DD'),
              date_stop: dayjs(date_range.value[1]).format('YYYY-MM-DD'),
              mode: 3,
            })
              .then(res => {
                message.success(res['message']);
              })
              .catch(err => {
                console.log(err);
                message.error(t('Error occured'));
              });
          }
        }
      } else if (key === 'sync-data-mode-4') {
        if (dateDifference.value > 15) {
          message.error(t('Duration should less than 15 days'));
          return;
        }
        // 只同步状态
        if (activeTab.value === '1') {
          if (selectedAdAccountState.selectedRowKeys.length === 0) {
            message.error(t('Please select ad account'));
            return;
          } else {
            syncAdAccountData({
              fb_ad_account_ids: selectedAdAccountState.selectedRowKeys,
              date_start: dayjs(date_range.value[0]).format('YYYY-MM-DD'),
              date_stop: dayjs(date_range.value[1]).format('YYYY-MM-DD'),
              mode: 4,
            })
              .then(res => {
                message.success(res['message']);
              })
              .catch(err => {
                console.log(err);
                message.error(t('Error occured'));
              });
          }
        } else if (activeTab.value === '2') {
          if (selectedCampaignState.selectedRowKeys.length === 0) {
            message.error(t('Please select ad account'));
            return;
          } else {
            syncCampaignData({
              campaign_ids: selectedCampaignState.selectedRowKeys,
              date_start: dayjs(date_range.value[0]).format('YYYY-MM-DD'),
              date_stop: dayjs(date_range.value[1]).format('YYYY-MM-DD'),
              mode: 4,
            })
              .then(res => {
                message.success(res['message']);
              })
              .catch(err => {
                console.log(err);
                message.error(t('Error occured'));
              });
          }
        } else if (activeTab.value === '3') {
          if (selectedAdsetState.selectedRowKeys.length === 0) {
            message.error(t('Please select ad account'));
            return;
          } else {
            syncAdsetData({
              adset_ids: selectedAdsetState.selectedRowKeys,
              date_start: dayjs(date_range.value[0]).format('YYYY-MM-DD'),
              date_stop: dayjs(date_range.value[1]).format('YYYY-MM-DD'),
              mode: 4,
            })
              .then(res => {
                message.success(res['message']);
              })
              .catch(err => {
                console.log(err);
                message.error(t('Error occured'));
              });
          }
        } else if (activeTab.value === '4') {
          if (selectedAdState.selectedRowKeys.length === 0) {
            message.error(t('Please select ad account'));
            return;
          } else {
            syncAdData({
              ad_ids: selectedAdState.selectedRowKeys,
              date_start: dayjs(date_range.value[0]).format('YYYY-MM-DD'),
              date_stop: dayjs(date_range.value[1]).format('YYYY-MM-DD'),
              mode: 4,
            })
              .then(res => {
                message.success(res['message']);
              })
              .catch(err => {
                console.log(err);
                message.error(t('Error occured'));
              });
          }
        }
      } else if (key === 'payment') {
        if (activeTab.value === '1') {
          // Ad Account tab - 保持原来的逻辑
          let payment_text = '';
          selectedAdAccountState.selectedRows.forEach(record => {
            const adaccount_name = record.ad_account_name;
            const adaccount_source_id = record.ad_account_id;
            const funding = record.funding;
            payment_text += `${adaccount_name} (${adaccount_source_id}): ${funding}\r\n`;
          });
          try {
            toClipboard(payment_text);
            message.success('copied');
          } catch (e) {
            console.error(e);
          }
        } else if (activeTab.value === '2') {
          // Campaign tab - 显示payment modal
          if (selectedCampaignState.selectedRowKeys.length === 0) {
            message.error(t('Please select campaign'));
            return;
          }

          paymentModal.selectedData = selectedCampaignState.selectedRows.map(record => ({
            id: record.id,
            ad_account_id: record.ad_account_id,
            ad_account_ulid: record.ad_account_ulid,
            ad_account_name: record.ad_account_name,
            account_status: record.account_status,
            funding: record.funding,
            daily_budget: record.daily_budget,
            currency: record.currency,
            campaign_id: record.campaign_id,
            campaign_name: record.campaign_name,
            default_card: record.default_card,
            spend_cap: record.spend_cap,
            balance: record.balance,
            is_topup: record.is_topup,
            total_spent: record.total_spent,
          }));
          paymentModal.tabType = '2';
          paymentModal.visible = true;
        } else if (activeTab.value === '3') {
          // Adset tab - 显示payment modal
          if (selectedAdsetState.selectedRowKeys.length === 0) {
            message.error(t('Please select adset'));
            return;
          }

          paymentModal.selectedData = selectedAdsetState.selectedRows.map(record => ({
            id: record.id,
            ad_account_id: record.ad_account_id,
            ad_account_ulid: record.ad_account_ulid,
            ad_account_name: record.ad_account_name,
            account_status: record.account_status,
            funding: record.funding,
            daily_budget: record.daily_budget,
            currency: record.currency,
            adset_id: record.adset_id,
            adset_name: record.adset_name,
            default_card: record.default_card,
            spend_cap: record.spend_cap,
            balance: record.balance,
            is_topup: record.is_topup,
            total_spent: record.total_spent,
          }));
          paymentModal.tabType = '3';
          paymentModal.visible = true;
        }
      } else if (key === 'batch-update-budget') {
        // 批量更新预算
        if (activeTab.value === '2') {
          // Campaign tab
          if (selectedCampaignState.selectedRowKeys.length === 0) {
            message.error(t('Please select campaign'));
            return;
          }

          batchBudgetModal.selectedData = selectedCampaignState.selectedRows.map(record => ({
            id: record.id,
            object_id: record.campaign_id,
            name: record.campaign_name,
            ad_account_name: record.ad_account_name,
            object_type: 'campaign',
            current_budget: record.daily_budget,
            daily_budget: record.daily_budget,
            lifetime_budget: record.lifetime_budget,
            status: record.effective_status,
          }));
          batchBudgetModal.tabType = '2';
          batchBudgetModal.visible = true;
        } else if (activeTab.value === '3') {
          // Adset tab
          if (selectedAdsetState.selectedRowKeys.length === 0) {
            message.error(t('Please select adset'));
            return;
          }

          batchBudgetModal.selectedData = selectedAdsetState.selectedRows.map(record => ({
            id: record.id,
            object_id: record.adset_id,
            name: record.adset_name,
            ad_account_name: record.ad_account_name,
            object_type: 'adset',
            current_budget: record.daily_budget,
            daily_budget: record.daily_budget,
            lifetime_budget: record.lifetime_budget,
            status: record.effective_status,
          }));
          batchBudgetModal.tabType = '3';
          batchBudgetModal.visible = true;
        }
      } else if (key === 'copy-campaign-id') {
        // 复制Campaign ID
        if (activeTab.value === '2') {
          if (selectedCampaignState.selectedRowKeys.length === 0) {
            message.error(t('Please select campaign'));
            return;
          }

          // 获取所选campaign的campaign_id，每个id一行
          const campaignIds = selectedCampaignState.selectedRows.map(record => record.campaign_id);
          const copyText = campaignIds.join('\n');

          try {
            toClipboard(copyText);
            message.success(t('Campaign IDs copied successfully') + ` (${campaignIds.length})`);
          } catch (e) {
            console.error(e);
            message.error(t('Failed to copy'));
          }
        }
      } else if (key === 'copy-all-campaign-id') {
        // 复制所有Campaign ID
        if (activeTab.value === '2') {
          const dataSource = campaignTableDataState.dataSource || [];

          if (dataSource.length === 0) {
            message.warning(t('No campaigns to copy'));
            return;
          }

          // 获取所有campaign的campaign_id，每个id一行
          const allCampaignIds = dataSource.map(record => record.campaign_id);
          const copyText = allCampaignIds.join('\n');

          try {
            toClipboard(copyText);
            message.success(t('All Campaign IDs copied successfully') + ` (${allCampaignIds.length})`);
          } catch (e) {
            console.error(e);
            message.error(t('Failed to copy'));
          }
        }
      } else if (key === 'cbo-2-abo') {
        // CBO 2 ABO
        if (activeTab.value === '2') {
          if (selectedCampaignState.selectedRowKeys.length === 0) {
            message.error(t('Please select campaign'));
            return;
          }

          // 验证所选campaign的daily_budget不能为空
          const invalidCampaigns = selectedCampaignState.selectedRows.filter(campaign =>
            !campaign.daily_budget || parseFloat(campaign.daily_budget) <= 0,
          );

          if (invalidCampaigns.length > 0) {
            message.error(t('Selected campaigns must have valid daily budget'));
            return;
          }

          // 获取有效的campaign source ids
          const validCampaignIds = selectedCampaignState.selectedRows
            .filter(campaign => campaign.daily_budget && parseFloat(campaign.daily_budget) > 0)
            .map(campaign => campaign.campaign_id);

          if (validCampaignIds.length === 0) {
            message.error(t('No valid campaigns selected'));
            return;
          }

          cbo2AboModal.campaignIds = validCampaignIds;
          cbo2AboModal.budget = 5.0;
          cbo2AboModal.visible = true;
        }
      } else if (key === 'abo-2-cbo') {
        // ABO 2 CBO
        if (activeTab.value === '2') {
          if (selectedCampaignState.selectedRowKeys.length === 0) {
            message.error(t('Please select campaign'));
            return;
          }

          // 获取选中的campaign数据，不需要验证daily_budget，因为ABO转CBO是设置campaign预算
          const selectedCampaigns = selectedCampaignState.selectedRows.map(campaign => ({
            id: campaign.id,
            campaign_id: campaign.campaign_id,
            campaign_name: campaign.campaign_name,
            current_budget: campaign.daily_budget || 0,
          }));

          abo2CboModal.selectedCampaigns = selectedCampaigns;
          abo2CboModal.budget = 5.0;
          abo2CboModal.visible = true;
        }
      } else if (key === 'delete') {
        let selectedItems = [];
        let selectedIds = [];
        let objectType = '';

        if (activeTab.value === '2') {
          // Campaign 标签页
          if (selectedCampaignState.selectedRowKeys.length === 0) {
            message.error('Please select campaign');
            return;
          }

          objectType = 'campaign';
          selectedIds = selectedCampaignState.selectedRowKeys;
          selectedItems = selectedCampaignState.selectedRows.map(item => ({
            id: item.id,
            name: item.campaign_name,
            source_id: item.campaign_id,
          }));
        } else if (activeTab.value === '3') {
          // Adset 标签页
          if (selectedAdsetState.selectedRowKeys.length === 0) {
            message.error('Please select adset');
            return;
          }

          objectType = 'adset';
          selectedIds = selectedAdsetState.selectedRowKeys;
          selectedItems = selectedAdsetState.selectedRows.map(item => ({
            id: item.id,
            name: item.adset_name,
            source_id: item.adset_id,
          }));
        } else if (activeTab.value === '4') {
          // Ad 标签页
          if (selectedAdState.selectedRowKeys.length === 0) {
            message.error('Please select ad');
            return;
          }

          objectType = 'ad';
          selectedIds = selectedAdState.selectedRowKeys;
          selectedItems = selectedAdState.selectedRows.map(item => ({
            id: item.id,
            name: item.ad_name,
            source_id: item.ad_id,
          }));
        }

        deleteModal.model = {
          type: objectType,
          ids: selectedIds,
          items: selectedItems,
        };

        deleteModal.visible = true;
      } else if (key === 'sync-card-3days') {
        // 同步最近3天交易
        if (activeTab.value === '1') {
          handleSyncCardTransactions();
        }
      } else if (key === 'sync-card-custom') {
        // 自定义同步交易
        if (activeTab.value === '1') {
          customSyncTransactionsModal.visible = true;
        }
      } else if (key === 'sync-keitaro') {
        // 同步Keitaro
        handleSyncKeitaro();
      } else if (key === 'sync-keitaro-custom') {
        // 自定义同步Keitaro
        customSyncKeitaroModal.visible = true;
      }
    };

    // 同步卡片交易
    const handleSyncCardTransactions = async (timeRange?: [Dayjs, Dayjs]) => {
      if (selectedAdAccountState.selectedRowKeys.length === 0) {
        message.error(t('pages.adc.sync.card.no.accounts'));
        return;
      }

      // 获取选中的广告账户的default_card id
      const cardIds = selectedAdAccountState.selectedRows
        .filter(row => row.default_card && row.default_card.id)
        .map(row => row.default_card.id);

      if (cardIds.length === 0) {
        message.error(t('pages.adc.sync.card.no.cards'));
        return;
      }

      try {
        const params: any = {
          ids: cardIds,
        };

        // 如果有时间范围，添加时间参数
        if (timeRange && timeRange[0] && timeRange[1]) {
          params.start_time = timeRange[0].format('YYYY-MM-DD');
          params.stop_time = timeRange[1].format('YYYY-MM-DD');
        }

        const response = await syncCardTransactions(params);
        message.success((response as any)?.message || t('pages.adc.sync.card.completed'));
      } catch (error) {
        console.error('Sync card transactions error:', error);
        message.error(t('pages.adc.sync.card.failed'));
      }
    };

    // 处理自定义同步交易
    const handleCustomSyncTransactions = async () => {
      if (!customSyncTransactionsModal.timeRange) {
        message.error(t('pages.adc.sync.card.select.time.range'));
        return;
      }

      customSyncTransactionsModal.loading = true;
      try {
        await handleSyncCardTransactions(customSyncTransactionsModal.timeRange);
        customSyncTransactionsModal.visible = false;
        customSyncTransactionsModal.timeRange = null;
      } catch (error) {
        console.error('Custom sync transactions error:', error);
      } finally {
        customSyncTransactionsModal.loading = false;
      }
    };

    // 同步Keitaro API调用
    const syncKeitaro = async (params?: any) => {
      const defaultParams = {
        date_start: dayjs().subtract(1, 'day').format('YYYY-MM-DD'),
        date_stop: dayjs().format('YYYY-MM-DD'),
      };

      const requestParams = params ? { ...defaultParams, ...params } : defaultParams;
      return await fetchKeitaro(requestParams);
    };

    // 同步Keitaro
    const handleSyncKeitaro = async () => {
      try {
        const response = await syncKeitaro();
        message.success((response as any)?.message || t('pages.adc.sync.keitaro.completed'));
      } catch (error) {
        console.error('Sync Keitaro error:', error);
        message.error(t('pages.adc.sync.keitaro.failed'));
      }
    };

    // 处理自定义同步Keitaro
    const handleCustomSyncKeitaro = async () => {
      if (!customSyncKeitaroModal.timeRange) {
        message.error(t('pages.adc.sync.keitaro.select.time.range'));
        return;
      }

      customSyncKeitaroModal.loading = true;
      try {
        const params: any = {
          date_start: customSyncKeitaroModal.timeRange[0].format('YYYY-MM-DD'),
          date_stop: customSyncKeitaroModal.timeRange[1].format('YYYY-MM-DD'),
        };
        const response = await syncKeitaro(params);
        message.success((response as any)?.message || t('pages.adc.sync.keitaro.completed'));
        customSyncKeitaroModal.visible = false;
        customSyncKeitaroModal.timeRange = null;
      } catch (error) {
        console.error('Custom sync Keitaro error:', error);
        message.error(t('pages.adc.sync.keitaro.failed'));
      } finally {
        customSyncKeitaroModal.loading = false;
      }
    };

    // 显示广告账户详情Modal
    const showAdAccountInfoModal = async (record: any) => {
      if (!record.ad_account_id) return;

      adAccountInfoModal.loading = true;
      adAccountInfoModal.visible = true;

      try {
        const response = await queryFB_AD_AccountsApi({
          pageNo: 1,
          pageSize: 10,
          ad_account_ids: [record.ad_account_id],
          'with-campaign': false,
        });

        if (response.data && response.data.length > 0) {
          adAccountInfoModal.adAccountData = response.data[0];
        } else {
          throw new Error('No ad account data found');
        }
      } catch (error) {
        console.error('获取广告账户详情失败:', error);
        message.error(t('Failed to fetch ad account details'));
        adAccountInfoModal.visible = false;
      } finally {
        adAccountInfoModal.loading = false;
      }
    };

    // 跳转到广告账户页面并搜索
    const viewAdAccount = () => {
      let adAccountIds: string[] = [];

      if (activeTab.value === '1') {
        // 统一使用ad_account_id字段
        adAccountIds = selectedAdAccountState.selectedRows.map(row => row.ad_account_id);
      } else if (activeTab.value === '2') {
        // 统一使用ad_account_id字段
        adAccountIds = [...new Set(selectedCampaignState.selectedRows.map(row => row.ad_account_id))];
      } else if (activeTab.value === '3') {
        // 统一使用ad_account_id字段
        adAccountIds = [...new Set(selectedAdsetState.selectedRows.map(row => row.ad_account_id))];
      } else if (activeTab.value === '4') {
        // 统一使用ad_account_id字段
        adAccountIds = [...new Set(selectedAdState.selectedRows.map(row => row.ad_account_id))];
      }

      if (adAccountIds.length === 0) {
        message.error(t('Please select items first'));
        return;
      }

      // 只传ad_account_ids参数
      const queryString = adAccountIds.map(id => `ad_account_ids[]=${id}`).join('&');
      router.push(`/adaccount?${queryString}`);
    };

    const { toClipboard } = useClipboard();
    const copyCell = async (text: any) => {
      try {
        await toClipboard(text);
        message.success('copied');
      } catch (e) {
        console.error(e);
      }
    };

    // 计算广告账户余额（针对月度账单特殊处理）
    const getAdAccountBalance = (adAccountData: any) => {
      if (!adAccountData) return '-';

      const defaultFunding = adAccountData.default_funding;
      const balance = adAccountData.balance;
      const spendCap = adAccountData.spend_cap;

      // 如果是月度账单，使用 spend_cap - balance 计算余额
      if (defaultFunding === 'monthly invoicing' || defaultFunding === 'penagihan bulanan' || defaultFunding === '月度结算') {
        if (spendCap && balance) {
          const calculatedBalance = parseFloat(spendCap) - parseFloat(balance);
          return calculatedBalance.toFixed(2);
        }
        return '-';
      }

      // 其他情况直接返回 balance
      return balance || '-';
    };

    const onChangeTab = active => {
      console.log('onChangeTab, active tab is: ', active);
      if (active === '2') {
        // 根据 selected ad account, 获取 campaign, 根据 capaign id 获取 campaign insight
        // 更新 campaign 的参数, 会触发获取 campaign insight 的 hook
        // 这里要检查一下,是否有选中有的 ad account, 如果有的话,则只获取选中的ad account 的数据
        if (hasAdAccountSelected.value) {
          let selectedAdAccountCampaigns = [];
          // 遍历 dataSource, 根据选中的id找出 ad account, 添加其campaign ids 到数组中
          adAccountTableDataState.dataSource.forEach(item => {
            if (selectedAdAccountState.selectedRowKeys.includes(item.id)) {
              selectedAdAccountCampaigns = selectedAdAccountCampaigns.concat(item.campaign_ids);
            }
          });
          // 更新请求参数里面的数据
          fetchCampaignInsightContext.requestParams.campaign_ids = selectedAdAccountCampaigns;
        } else {
          // 如果没有选择一个ad account, 就把所有的ad account 的 campaign ids 添加到请求参数里
          fetchCampaignInsightContext.requestParams.campaign_ids = toRaw(all_campaign_ids.value);
        }
        fetchCampaignInsightContext.requestParams.date_start = dayjs(date_range.value[0]).format(
          'YYYY-MM-DD',
        );
        fetchCampaignInsightContext.requestParams.date_stop = dayjs(date_range.value[1]).format(
          'YYYY-MM-DD',
        );
        console.log(fetchCampaignInsightContext);
      } else if (active === '3') {
        // 切换到 Adset 时
        if (hasCampaignSelected.value) {
          // 如果有选中 campaign, 则获取这些选中的 campaign 的 adset_id, 加到 context 的请求参数中, 触发请求
          let selectedCampaignAdsetIDs = [];
          campaignTableDataState.dataSource.forEach(item => {
            if (selectedCampaignState.selectedRowKeys.includes(item.id)) {
              selectedCampaignAdsetIDs = selectedCampaignAdsetIDs.concat(item.adset_ids);
            }
          });
          fetchAdsetInsightContext.requestParams.adset_ids = selectedCampaignAdsetIDs;
        } else {
          // 没有选 campaign,就把所有的 adset_ids 添加到 context 中, 触发请求
          fetchAdsetInsightContext.requestParams.adset_ids = toRaw(all_adset_ids.value);
        }
        fetchAdsetInsightContext.requestParams.date_start = dayjs(date_range.value[0]).format(
          'YYYY-MM-DD',
        );
        fetchAdsetInsightContext.requestParams.date_stop = dayjs(date_range.value[1]).format(
          'YYYY-MM-DD',
        );
        console.log(fetchAdsetInsightContext);
      } else if (active === '4') {
        if (hasAdsetSelected.value) {
          let selectedAdsetAdIDs = [];
          adsetTableDataState.dataSource.forEach(item => {
            if (selectedAdsetState.selectedRowKeys.includes(item.id)) {
              selectedAdsetAdIDs = selectedAdsetAdIDs.concat(item.ad_ids);
            }
          });
          fetchAdInsightContext.requestParams.ad_ids = selectedAdsetAdIDs;
        } else {
          fetchAdInsightContext.requestParams.ad_ids = toRaw(all_ad_ids.value);
        }
        fetchAdInsightContext.requestParams.date_start = dayjs(date_range.value[0]).format(
          'YYYY-MM-DD',
        );
        fetchAdInsightContext.requestParams.date_stop = dayjs(date_range.value[1]).format(
          'YYYY-MM-DD',
        );
        console.log(fetchAdInsightContext);
      } else {
        console.log('why are you here');
      }
    };

    // 选中表格后的动态处理
    const adAccountTabTitle = computed(() => {
      const count = selectedAdAccountState.selectedRowKeys.length;
      return count > 0 ? t('Ad Account') + ` - ${count}` : t('Ad Account');
    });
    // const selectedAdAccountRowKeys = ref([]);
    const hasAdAccountSelected = computed(() => selectedAdAccountState.selectedRowKeys.length > 0);
    const onSelectedAdAccountChange = (selectedRowKeys, selectedRows) => {
      console.log('onSelectedAdAccountChange changed: ', selectedRowKeys);
      selectedAdAccountState.selectedRowKeys = selectedRowKeys;
      selectedAdAccountState.selectedRows = selectedRows;
    };
    // 清除按钮只在有选中的数据个数大于 0 的时候才显示
    const adAccountClosable = computed(() => {
      return selectedAdAccountState.selectedRowKeys.length > 0;
    });
    const campaignClosable = computed(() => {
      return selectedCampaignState.selectedRowKeys.length > 0;
    });
    const adsetClosable = computed(() => {
      return selectedAdsetState.selectedRowKeys.length > 0;
    });
    const adClosable = computed(() => {
      return selectedAdState.selectedRowKeys.length > 0;
    });

    // 点击 Tab 的关闭按钮
    const onEditTab = (targetKey: string | MouseEvent, action: string) => {
      console.log('targetKey: ', targetKey);
      if (action === 'add') {
        console.log('add');
      } else {
        console.log('not add: ', action);
        if (targetKey === '1') {
          selectedAdAccountState.selectedRowKeys = [];
          selectedAdAccountState.selectedRows = [];
        } else if (targetKey === '2') {
          selectedCampaignState.selectedRowKeys = [];
          selectedCampaignState.selectedRows = [];
        } else if (targetKey === '3') {
          selectedAdsetState.selectedRowKeys = [];
          selectedAdsetState.selectedRows = [];
        } else if (targetKey === '4') {
          selectedAdState.selectedRowKeys = [];
          selectedAdState.selectedRows = [];
        }
      }
    };

    // 请求 campaign insight 的参数
    const queryCampaignInsightParam = reactive({
      date_start: '2024-02-15',
      date_stop: '2024-02-15',
      campaign_ids: undefined,
      sortOrder: undefined,
      sortField: undefined,
    });

    // 请求 campaign insight 的上下文
    const fetchCampaignInsightContext = reactive({
      current: 1,
      pageSize: 10,
      requestParams: { ...queryCampaignInsightParam },
    });
    // 注册 获取 campaign insight 的 hook, 在 tab click 事件里面更新 fetchCampaignInsightContext 的参数,就能触发 hook 执行
    const { context: campaignTableDataState, reload: reloadCampaignTable } = useFetchData(
      getCampaignInsight as any,
      fetchCampaignInsightContext,
      {
        onRequestError: onRequestError,
        pagination: false, // Campaign tab使用前端分页，不发送pageNo/pageSize到后端
      },
    );
    // 为表格的 hook 注册准备,配置列
    const campaignColumns = ref<any[]>([]);
    const filteredCampaignInfo = ref();
    filteredCampaignInfo.value = {
      effective_status: [],
    };
    watchEffect(() => {
      const filteredCampaign = filteredCampaignInfo.value || {};
      campaignColumns.value = [
        {
          title: t('pages.ads.index'),
          dataIndex: 'index',
          customRender: ({ index }) => {
            return `${index + 1}`;
          },
          width: 80,
          align: 'center',
          fixed: 'left',
        },
        {
          title: t('pages.ads.ad.acc'),
          dataIndex: 'ad_account_id',
          ellipsis: true,
          align: 'left',
          minWidth: 190,
          resizable: true,
          sorter: (a, b) => a.ad_account_name - b.ad_account_name,
          fixed: 'left',
        },
        {
          title: t('pages.ads.currency'),
          dataIndex: 'currency',
          align: 'center',
          minWidth: 80,
        },
        {
          title: t('pages.ads.acc.status'),
          dataIndex: 'account_status',
          align: 'center',
          minWidth: 140,
        },
        {
          title: t('Campaign ID'),
          dataIndex: 'campaign_id',
          align: 'center',
          minWidth: 170,
          resizable: true,
          filters: [
            // { text: '全部', value: '0' },
            { text: 'FB 已删除', value: '1' },
            { text: 'FB 未删除', value: '2' },
          ],
          filterMultiple: false,
          filteredValue: filteredCampaign.campaign_id || null,
          onFilter: (value: string, record) => {
            switch (value) {
              // case null:
              //   return true; // 对于'0'，我们接受任何记录
              case '1':
                return record.is_deleted_on_fb === true; // 对于'1'，我们只接受is_deleted_on_fb为true的记录
              case '2':
                return record.is_deleted_on_fb !== true; // 对于'2'，我们只接受is_deleted_on_fb不为true的记录
              default:
                return true;
            }
          },
        },
        {
          title: t('Campaign Name'),
          dataIndex: 'campaign_name',
          sorter: (a, b) => a.link_clicks - b.link_clicks,
          align: 'center',
          minWidth: 160,
          resizable: true,
          ellipsis: true,
        },
        {
          title: 'Campaign Tags',
          dataIndex: 'tags',
          minWidth: 100,
          resizable: true,
        },
        {
          title: 'Off/On',
          dataIndex: 'status',
          align: 'center',
          minWidth: 100,
          resizable: true,
          ellipsis: true,
          customRender: ({ record }) => {
            const { is_deleted_on_fb, account_status, status, campaign_id } = record;

            // 计算 switch 的是否禁用
            let isDisabled = is_deleted_on_fb || account_status !== 'ACTIVE';
            if (status === 'ARCHIVED' || status === 'DELETED') {
              isDisabled = true;
            }

            // 计算 switch 的当前状态
            const switchChecked = status === 'ACTIVE';

            // 返回 ASwitch 组件的虚拟节点
            return h(Switch, {
              checked: switchChecked,
              disabled: isDisabled,
              onChange: (checked: boolean, event) => {
                // 使用闭包访问 record
                console.log('Switch toggled:', checked);
                console.log('Current record:', record);
                const target_status = checked ? 'ACTIVE' : 'PAUSED';

                batchUpdateFbObjectStatus({
                  ids: [campaign_id], // 使用解构赋值直接访问 campaign_id
                  object_type: 'campaign',
                  status: target_status,
                })
                  .then(res => {
                    message.info(res['message']);
                  })
                  .catch(err => {
                    console.error(err);
                    message.error('Request failed');
                  });
                event.stopPropagation();
              },
            });
          },
        },
        {
          title: t('Delivery'),
          dataIndex: 'effective_status',
          sorter: (a, b) => a.effective_status.localeCompare(b.effective_status),
          align: 'center',
          minWidth: 140,
          resizable: true,
          ellipsis: true,
          filters: effective_status_map,
          // filterMultiple: true,
          filteredValue: filteredCampaign.effective_status || null,
          onFilter: (value: string, record) => record.effective_status.includes(value),
        },
        {
          title: t('Bid strategy'),
          dataIndex: 'campaign_bid_strategy',
          sorter: (a, b) => a.campaign_bid_strategy.localeCompare(b.campaign_bid_strategy),
          align: 'center',
          minWidth: 140,
          resizable: true,
          ellipsis: true,
          customRender: ({ record }: { record: any }) => {
            const bid_strategy_value = record?.campaign_bid_strategy;
            const bid_amount = record?.bid_amount;
            let bid_stragety_human_str = '';

            if (bid_strategy_value === 'LOWEST_COST_WITHOUT_CAP') {
              bid_stragety_human_str = 'High volume';
            } else if (bid_strategy_value === 'LOWEST_COST_WITH_BID_CAP') {
              bid_stragety_human_str = 'Bid cap';
            } else if (bid_strategy_value === 'COST_CAP') {
              bid_stragety_human_str = 'Cost Per Result';
            }
            if (bid_stragety_human_str !== '') {
              return h('div', [
                h('div', bid_stragety_human_str),
                h('div', { style: { fontSize: '12px', color: '#888' } }, bid_amount),
              ]);
            }
            return h('div', '-');
          },
        },
        {
          title: t('Campaign Budget'),
          dataIndex: 'daily_budget',
          align: 'center',
          minWidth: 130,
          resizable: true,
          ellipsis: true,
          sorter: (a: any, b: any) => {
            // 获取预算值的辅助函数
            const getBudgetValue = (record: any): number => {
              if (record.daily_budget !== null && record.daily_budget !== undefined) {
                return parseFloat(record.daily_budget) || 0;
              } else if (record.lifetime_budget !== null && record.lifetime_budget !== undefined) {
                return parseFloat(record.lifetime_budget) || 0;
              }
              return 0; // 没有预算时返回0
            };

            const budgetA = getBudgetValue(a);
            const budgetB = getBudgetValue(b);

            return budgetA - budgetB;
          },
          customRender: ({ record }: { record: any }) => {
            const dailyBudget = record.daily_budget;
            const lifetimeBudget = record.lifetime_budget;

            // 用于存储预算信息
            let budgetValue: string | null = null;
            let budgetSource: string = '';

            if (dailyBudget !== null) {
              budgetValue = dailyBudget.toString();
              budgetSource = '(daily budget)';
            } else if (lifetimeBudget !== null) {
              budgetValue = lifetimeBudget.toString();
              budgetSource = '(lifetime budget)';
            }

            // 检查预算值是否存在
            if (budgetValue !== null) {
              return h('div', [
                h('div', budgetValue),
                h('div', { style: { fontSize: '12px', color: '#888' } }, budgetSource),
              ]);
            }

            // 当没有预算时返回 '-'
            return h('div', '-');
          },
        },
        {
          title: 'Spend',
          dataIndex: 'spend',
          sorter: (a, b) => a.spend - b.spend,
          minWidth: 100,
          resizable: true,
          align: 'center',
          customRender: ({ text }) => {
            if (text !== '0') {
              return `${text}`;
            } else {
              return '-';
            }
          },
        },
        {
          title: 'Revenue',
          dataIndex: 'offer_conversions_value',
          sorter: (a, b) => a.offer_conversions_value - b.offer_conversions_value,
          minWidth: 100,
          resizable: true,
          align: 'center',
          customRender: ({ text }) => {
            return `${text} USD`;
          },
        },
        {
          title: 'ROI',
          dataIndex: 'roi',
          sorter: (a, b) => a.roi - b.roi,
          minWidth: 80,
          resizable: true,
          align: 'center',
        },
        {
          title: 'Roas(FB)',
          dataIndex: 'purchase_roas',
          sorter: (a, b) => a.purchase_roas - b.purchase_roas,
          minWidth: 100,
          resizable: true,
          align: 'center',
        },
        {
          title: 'Lead(FB)',
          dataIndex: 'lead',
          sorter: (a, b) => a.lead - b.lead,
          minWidth: 110,
          resizable: true,
          align: 'center',
          ellipsis: false,
        },
        {
          title: 'CPL(FB)',
          dataIndex: 'cost_per_lead',
          sorter: (a, b) => a.cost_per_lead - b.cost_per_lead,
          minWidth: 120,
          resizable: true,
          align: 'center',
        },
        {
          title: 'Offer Clicks',
          dataIndex: 'offer_clicks',
          sorter: (a, b) => a.offer_clicks - b.offer_clicks,
          minWidth: 100,
          resizable: true,
          align: 'center',
        },
        {
          title: 'Offer Leads',
          dataIndex: 'offer_leads',
          sorter: (a, b) => a.offer_leads - b.offer_leads,
          minWidth: 100,
          resizable: true,
          align: 'center',
        },
        {
          title: 'Purchase',
          dataIndex: 'offer_conversions',
          sorter: (a, b) => a.offer_conversions - b.offer_conversions,
          minWidth: 100,
          resizable: true,
          align: 'center',
        },
        {
          title: 'Offer CPC',
          dataIndex: 'offer_cpc',
          sorter: (a, b) => a.offer_cpc - b.offer_cpc,
          minWidth: 100,
          resizable: true,
          align: 'center',
        },
        {
          title: 'Offer EPC',
          dataIndex: 'offer_epc',
          sorter: (a, b) => a.offer_epc - b.offer_epc,
          minWidth: 100,
          resizable: true,
          align: 'center',
        },
        {
          title: 'Offer CPL',
          dataIndex: 'offer_cpl',
          sorter: (a, b) => a.offer_cpl - b.offer_cpl,
          minWidth: 140,
          resizable: true,
          align: 'center',
        },
        {
          title: 'Offer EPL',
          dataIndex: 'offer_epl',
          sorter: (a, b) => a.offer_epl - b.offer_epl,
          minWidth: 140,
          resizable: true,
          align: 'center',
        },
        {
          title: 'Link Clicks',
          dataIndex: 'link_clicks',
          sorter: (a, b) => a.link_clicks - b.link_clicks,
          minWidth: 100,
          resizable: true,
          align: 'center',
        },
        {
          title: 'Link CTR',
          dataIndex: 'link_ctr',
          sorter: (a, b) => a.link_ctr - b.link_ctr,
          minWidth: 100,
          resizable: true,
          align: 'center',
        },
        {
          title: 'Link CPC',
          dataIndex: 'link_cpc',
          sorter: (a, b) => a.link_cpc - b.link_cpc,
          minWidth: 100,
          resizable: true,
          align: 'center',
        },
        {
          title: 'Taken Rate',
          dataIndex: 'taken_rate',
          sorter: (a, b) => a.taken_rate - b.taken_rate,
          minWidth: 100,
          resizable: true,
          align: 'center',
        },
        {
          title: 'CPM',
          dataIndex: 'cpm',
          sorter: (a, b) => a.cpm - b.cpm,
          minWidth: 130,
          resizable: true,
          align: 'center',
        },
        {
          title: t('Created Time'),
          dataIndex: 'created_time',
          sorter: (a, b) => {
            return new Date(a.created_time).getTime() - new Date(b.created_time).getTime();
          },
          minWidth: 160,
          resizable: true,
          align: 'center',
        },
        {
          title: t('Refresh Time'),
          dataIndex: 'refresh_time',
          sorter: (a, b) => {
            return new Date(a.refresh_time).getTime() - new Date(b.refresh_time).getTime();
          },
          minWidth: 160,
          resizable: true,
          align: 'center',
        },
      ];
    });
    // 注册表格的 hook,
    const {
      state: campaignColumnState,
      dynamicColumns: campaignDynaColumns,
      dynamicColumnItems: campaignDynamicColumnItems,
      handleColumnAllClick: handleCampaignColumnAllClick,
      handleColumnChange: handleCampaignColumnChange,
      reset: resetCampaign,
      move: moveCampaign,
    } = useTableDynamicColumns(campaignColumns as any);

    // 初始化Campaign动态列并保持同步
    watchEffect(() => {
      if (
        campaignDynaColumns &&
        campaignDynaColumns.value &&
        campaignDynaColumns.value.length > 0 &&
        dynamicColumnsTab2.value.length === 0
      ) {
        dynamicColumnsTab2.value = [...campaignDynaColumns.value];
      }
    });

    // 选中 Campaign
    const selectedCampaignState = reactive<{
      selectedRowKeys: Key[];
      selectedRows: any[];
    }>({
      selectedRowKeys: [],
      selectedRows: [],
    });
    // const selectedCampaignRowKeys = ref([]);
    // const selectedCampaignRows = ref([]);
    const hasCampaignSelected = computed(() => selectedCampaignState.selectedRowKeys.length > 0);
    const onSelectedCampaignChange = (selectedRowKeys: Key[], selectedRows) => {
      console.log('onSelectedCampaignChange changed: ', selectedRowKeys);
      // selectedCampaignRowKeys.value = selectedRowKeys;
      selectedCampaignState.selectedRowKeys = selectedRowKeys;
      selectedCampaignState.selectedRows = selectedRows;
    };
    // Tab 的标签根据选中的 campaign数量变化
    const campaignTabTitle = computed(() => {
      console.log('campaign title changed');
      const count = selectedCampaignState.selectedRowKeys.length;
      return count > 0 ? t('Campaign') + `- ${count}` : t('Campaign');
    });

    // 排序状态存储
    const campaignSorter = ref(null);
    const adsetSorter = ref(null);
    const adSorter = ref(null);

    // 前端分页状态存储（用于汇总计算）
    const campaignCurrentPage = ref(1);
    const campaignPageSize = ref(10);
    const adsetCurrentPage = ref(1);
    const adsetPageSize = ref(10);
    const adCurrentPage = ref(1);
    const adPageSize = ref(10);

    // 响应式分页配置 - 不绑定current和pageSize，避免状态冲突
    const campaignPagination = computed(() => ({
      showQuickJumper: true,
      showSizeChanger: true,
      total: campaignTableDataState.total,
      showTotal: total => `Total ${total} items`,
      pageSizeOptions: ['10', '20', '50', '100', '200', '500'],
    }));

    const adsetPagination = computed(() => ({
      showQuickJumper: true,
      showSizeChanger: true,
      total: adsetTableDataState.total,
      showTotal: total => `Total ${total} items`,
      pageSizeOptions: ['10', '20', '50', '100', '200', '500'],
    }));

    const adPagination = computed(() => ({
      showQuickJumper: true,
      showSizeChanger: true,
      total: adTableDataState.total,
      showTotal: total => `Total ${total} items`,
      pageSizeOptions: ['10', '20', '50', '100', '200', '500'],
    }));

    // 排序辅助函数
    const applySorting = (data, sorter) => {
      if (!sorter || !sorter.field || !sorter.order) {
        return data;
      }

      const field = sorter.field;
      const order = sorter.order; // 'ascend' or 'descend'

      return [...data].sort((a, b) => {
        let aVal = a[field];
        let bVal = b[field];

        // 处理数值类型
        if (typeof aVal === 'string' && !isNaN(parseFloat(aVal))) {
          aVal = parseFloat(aVal) || 0;
        }
        if (typeof bVal === 'string' && !isNaN(parseFloat(bVal))) {
          bVal = parseFloat(bVal) || 0;
        }

        // 处理null/undefined
        if (aVal == null) aVal = 0;
        if (bVal == null) bVal = 0;

        if (order === 'ascend') {
          return aVal > bVal ? 1 : aVal < bVal ? -1 : 0;
        } else {
          return aVal < bVal ? 1 : aVal > bVal ? -1 : 0;
        }
      });
    };

    // Campaign Tab 变化
    const handleCampaignChange = (pagination, filters, sorter) => {
      console.log('Various parameters', pagination, filters, sorter);

      // 前端分页：不更新context的current和pageSize，避免触发API请求
      // 分页逻辑完全由Ant Design表格组件处理

      // 但是我们需要保存分页信息用于汇总计算
      if (pagination) {
        campaignCurrentPage.value = pagination.current || 1;
        campaignPageSize.value = pagination.pageSize || 10;
      }

      // 保存排序状态
      campaignSorter.value = sorter;

      filteredCampaignInfo.value = filters;
      if (filters.campaign_id) {
        switch (filters.campaign_id[0]) {
          case '0':
            all_adset_ids.value = campaignTableDataState.dataSource
              .map(item => item.adset_ids)
              .flat();
            return;
          case '1':
            all_adset_ids.value = campaignTableDataState.dataSource
              .filter(item => item.is_deleted_on_fb === true)
              .map(item => item.adset_ids)
              .flat();
            return;
          case '2':
            all_adset_ids.value = campaignTableDataState.dataSource
              .filter(item => item.is_deleted_on_fb != true)
              .map(item => item.adset_ids)
              .flat();
            return;
        }
      } else {
        // 重置 filter 后
        all_adset_ids.value = campaignTableDataState.dataSource.map(item => item.adset_ids).flat();
      }
    };

    // 监听 campaign table 数据变化, 更新 all_adset_ids, 但不更新 context 中的 request params
    const all_adset_ids = ref<string[]>([]);
    watch(campaignTableDataState, newState => {
      if (newState) {
        console.log('campaign table data 更新了');
        console.log(newState);
        let tmp_ids = [];
        newState.dataSource.forEach(item => {
          tmp_ids = tmp_ids.concat(item.adset_ids);
        });
        all_adset_ids.value = tmp_ids;

        // 根据新的状态更新 selectedCampaignState.selectedRows
        selectedCampaignState.selectedRows = newState.dataSource.filter(
          item => selectedCampaignState.selectedRowKeys.includes(item.id), // 假设每个 item 有一个 id 属性
        );
      }
    });

    // 请求 adset insight 的参数
    const queryAdsetInsightParam = reactive({
      date_start: '2024-02-15',
      date_stop: '2024-02-15',
      adset_ids: undefined,
      sortOrder: undefined,
      sortField: undefined,
    });

    // 请求 adset insight 的上下文
    const fetchAdsetInsightContext = reactive({
      current: 1,
      pageSize: 10,
      requestParams: { ...queryAdsetInsightParam },
    });
    // 注册 获取 adset insight 的 hook, 在 tab click 事件里面更新 fetchAdsetInsightContext 的参数,就能触发 hook 执行
    const { context: adsetTableDataState, reload: reloadAdsetTable } = useFetchData(
      getAdsetInsight as any,
      fetchAdsetInsightContext,
      {
        onRequestError: onRequestError,
        pagination: false, // Adset tab使用前端分页，不发送pageNo/pageSize到后端
      },
    );

    // 为 adset 表格的 hook 注册准备,配置列
    const adsetColumns = ref<any[]>([]);
    const filteredAdsetInfo = ref();
    filteredAdsetInfo.value = {
      effective_status: [],
    };
    watchEffect(() => {
      const filteredAdset = filteredAdsetInfo.value || {};
      adsetColumns.value = [
        {
          title: t('pages.ads.index'),
          dataIndex: 'index',
          customRender: ({ index }: any) => {
            return `${index + 1}`;
          },
          width: 80,
          align: 'center',
          fixed: 'left',
        },
        {
          title: t('pages.ads.ad.acc'),
          dataIndex: 'ad_account_id',
          ellipsis: true,
          align: 'left',
          minWidth: 190,
          resizable: true,
          sorter: (a, b) => a.ad_account_name - b.ad_account_name,
          fixed: 'left',
        },
        {
          title: t('pages.ads.currency'),
          dataIndex: 'currency',
          align: 'center',
          minWidth: 80,
        },
        {
          title: t('pages.ads.acc.status'),
          dataIndex: 'account_status',
          align: 'center',
          minWidth: 140,
        },
        {
          title: t('Campaign ID'),
          dataIndex: 'campaign_id',
          align: 'center',
          minWidth: 170,
          resizable: true,
        },
        {
          title: t('Campaign Name'),
          dataIndex: 'campaign_name',
          sorter: (a, b) => a.link_clicks - b.link_clicks,
          align: 'center',
          minWidth: 160,
          resizable: true,
          ellipsis: true,
        },
        {
          title: t('Adset ID'),
          dataIndex: 'adset_id',
          align: 'center',
          minWidth: 170,
          resizable: true,
          filters: [
            // { text: '全部', value: '0' },
            { text: 'FB 已删除', value: '1' },
            { text: 'FB 未删除', value: '2' },
          ],
          filterMultiple: false,
          filteredValue: filteredAdset.adset_id || null,
          onFilter: (value: string, record) => {
            switch (value) {
              // case null:
              //   return true; // 对于'0'，我们接受任何记录
              case '1':
                return record.is_deleted_on_fb === true; // 对于'1'，我们只接受is_deleted_on_fb为true的记录
              case '2':
                return record.is_deleted_on_fb !== true; // 对于'2'，我们只接受is_deleted_on_fb不为true的记录
              default:
                return true;
            }
          },
        },
        {
          title: t('Adset Name'),
          dataIndex: 'adset_name',
          align: 'center',
          minWidth: 160,
          resizable: true,
          ellipsis: true,
        },
        {
          title: 'Adset Tags',
          dataIndex: 'tags',
          minWidth: 100,
          resizable: true,
        },
        {
          title: 'Off/On',
          dataIndex: 'status',
          align: 'center',
          minWidth: 100,
          resizable: true,
          ellipsis: true,
          customRender: ({ record }) => {
            const { is_deleted_on_fb, account_status, status, adset_id } = record;

            // 计算 switch 的是否禁用
            let isDisabled = is_deleted_on_fb || account_status !== 'ACTIVE';
            if (status === 'ARCHIVED' || status === 'DELETED') {
              isDisabled = true;
            }

            // 计算 switch 的当前状态
            const switchChecked = status === 'ACTIVE';

            // 返回 ASwitch 组件的虚拟节点
            return h(Switch, {
              checked: switchChecked,
              disabled: isDisabled,
              onChange: (checked: boolean, event) => {
                // 使用闭包访问 record
                console.log('Switch toggled:', checked);
                console.log('Current record:', record);
                const target_status = checked ? 'ACTIVE' : 'PAUSED';

                batchUpdateFbObjectStatus({
                  ids: [adset_id], // 使用解构赋值直接访问 campaign_id
                  object_type: 'adset',
                  status: target_status,
                })
                  .then(res => {
                    message.info(res['message']);
                  })
                  .catch(err => {
                    console.error(err);
                    message.error('Request failed');
                  });
                event.stopPropagation();
              },
            });
          },
        },
        {
          title: t('Delivery'),
          dataIndex: 'effective_status',
          sorter: (a, b) => a.effective_status.localeCompare(b.effective_status),
          align: 'center',
          minWidth: 160,
          resizable: true,
          ellipsis: true,
          filters: effective_status_map,
          filterMultiple: true,
          filteredValue: filteredAdset.effective_status || null,
          onFilter: (value: string, record) => record.effective_status === value,
        },
        {
          title: t('Bid strategy'),
          dataIndex: 'adset_bid_strategy',
          sorter: (a, b) => a.adset_bid_strategy.localeCompare(b.adset_bid_strategy),
          align: 'center',
          minWidth: 140,
          resizable: true,
          ellipsis: true,
          customRender: ({ record }: { record: any }) => {
            const adset_bid_strategy_value = record?.adset_bid_strategy;
            let bid_amount = record?.bid_amount;
            let bid_stragety_human_str = '';
            const camp_bid_strategy_value = record?.campaign_bid_strategy;

            let bid_strategy_value = '';
            if (!adset_bid_strategy_value) {
              bid_strategy_value = camp_bid_strategy_value;
            } else if (!camp_bid_strategy_value) {
              bid_strategy_value = adset_bid_strategy_value;
            }

            if (bid_strategy_value === 'LOWEST_COST_WITHOUT_CAP') {
              bid_stragety_human_str = 'High volume';
              bid_amount = '';
            } else if (bid_strategy_value === 'LOWEST_COST_WITH_BID_CAP') {
              bid_stragety_human_str = 'Bid cap';
            } else if (bid_strategy_value === 'COST_CAP') {
              bid_stragety_human_str = 'Cost Per Result';
            }
            if (bid_stragety_human_str !== '') {
              return h('div', [
                h('div', bid_stragety_human_str),
                h('div', { style: { fontSize: '12px', color: '#888' } }, bid_amount),
              ]);
            }
            return h('div', '-');
          },
        },
        {
          title: t('Adset Budget'),
          dataIndex: 'daily_budget',
          sorter: (a: any, b: any) => {
            // 获取预算值的辅助函数
            const getBudgetValue = (record: any): number => {
              if (record.daily_budget !== null && record.daily_budget !== undefined) {
                return parseFloat(record.daily_budget) || 0;
              } else if (record.lifetime_budget !== null && record.lifetime_budget !== undefined) {
                return parseFloat(record.lifetime_budget) || 0;
              }
              return 0; // 没有预算时返回0
            };

            const budgetA = getBudgetValue(a);
            const budgetB = getBudgetValue(b);

            return budgetA - budgetB;
          },
          align: 'center',
          minWidth: 160,
          resizable: true,
          ellipsis: true,
          customRender: ({ record }: { record: any }) => {
            const dailyBudget = record.daily_budget;
            const lifetimeBudget = record.lifetime_budget;

            // 用于存储预算信息
            let budgetValue: string | null = null;
            let budgetSource: string = '';

            if (dailyBudget !== null) {
              budgetValue = dailyBudget.toString();
              budgetSource = '(daily budget)';
            } else if (lifetimeBudget !== null) {
              budgetValue = lifetimeBudget.toString();
              budgetSource = '(lifetime budget)';
            }

            // 检查预算值是否存在
            if (budgetValue !== null) {
              return h('div', [
                h('div', budgetValue),
                h('div', { style: { fontSize: '12px', color: '#888' } }, budgetSource),
              ]);
            }

            // 当没有预算时返回 '-'
            return h('div', '-');
          },
        },
        {
          title: 'Spend',
          dataIndex: 'spend',
          sorter: (a, b) => a.spend - b.spend,
          minWidth: 100,
          resizable: true,
          align: 'center',
          customRender: ({ text }) => {
            if (text !== '0') {
              return `${text}`;
            } else {
              return '-';
            }
          },
        },
        {
          title: 'Revenue',
          dataIndex: 'offer_conversions_value',
          sorter: (a, b) => a.offer_conversions_value - b.offer_conversions_value,
          minWidth: 100,
          resizable: true,
          align: 'center',
          customRender: ({ text }) => {
            return `${text} USD`;
          },
          ellipsis: false,
        },
        {
          title: 'ROI',
          dataIndex: 'roi',
          sorter: (a, b) => a.roi - b.roi,
          minWidth: 80,
          resizable: true,
          align: 'center',
          ellipsis: false,
        },
        {
          title: 'Roas(FB)',
          dataIndex: 'purchase_roas',
          sorter: (a, b) => a.purchase_roas - b.purchase_roas,
          minWidth: 120,
          resizable: true,
          align: 'center',
          ellipsis: false,
        },
        {
          title: 'Lead(FB)',
          dataIndex: 'lead',
          sorter: (a, b) => a.lead - b.lead,
          minWidth: 110,
          resizable: true,
          align: 'center',
          ellipsis: false,
        },
        {
          title: 'CPL(FB)',
          dataIndex: 'cost_per_lead',
          sorter: (a, b) => a.cost_per_lead - b.cost_per_lead,
          minWidth: 120,
          resizable: true,
          align: 'center',
        },
        {
          title: 'Offer Clicks',
          dataIndex: 'offer_clicks',
          sorter: (a, b) => a.offer_clicks - b.offer_clicks,
          minWidth: 130,
          resizable: true,
          align: 'center',
          ellipsis: false,
        },
        {
          title: 'Offer Leads',
          dataIndex: 'offer_leads',
          sorter: (a, b) => a.offer_leads - b.offer_leads,
          minWidth: 130,
          resizable: true,
          align: 'center',
          ellipsis: false,
        },
        {
          title: 'Purchase',
          dataIndex: 'offer_conversions',
          sorter: (a, b) => a.offer_conversions - b.offer_conversions,
          minWidth: 120,
          resizable: true,
          align: 'center',
          ellipsis: false,
        },
        {
          title: 'Offer CPC',
          dataIndex: 'offer_cpc',
          sorter: (a, b) => a.offer_cpc - b.offer_cpc,
          minWidth: 130,
          resizable: true,
          align: 'center',
          ellipsis: false,
        },
        {
          title: 'Offer EPC',
          dataIndex: 'offer_epc',
          sorter: (a, b) => a.offer_epc - b.offer_epc,
          minWidth: 120,
          resizable: true,
          align: 'center',
          ellipsis: false,
        },
        {
          title: 'Offer CPL',
          dataIndex: 'offer_cpl',
          sorter: (a, b) => a.offer_cpl - b.offer_cpl,
          minWidth: 140,
          resizable: true,
          align: 'center',
        },
        {
          title: 'Offer EPL',
          dataIndex: 'offer_epl',
          sorter: (a, b) => a.offer_epl - b.offer_epl,
          minWidth: 140,
          resizable: true,
          align: 'center',
        },
        {
          title: 'Link Clicks',
          dataIndex: 'link_clicks',
          sorter: (a, b) => a.link_clicks - b.link_clicks,
          minWidth: 130,
          resizable: true,
          align: 'center',
          ellipsis: false,
        },
        {
          title: 'Link CTR',
          dataIndex: 'link_ctr',
          sorter: (a, b) => a.link_ctr - b.link_ctr,
          minWidth: 120,
          resizable: true,
          align: 'center',
          ellipsis: false,
        },
        {
          title: 'Link CPC',
          dataIndex: 'link_cpc',
          sorter: (a, b) => a.link_cpc - b.link_cpc,
          minWidth: 120,
          resizable: true,
          align: 'center',
          ellipsis: false,
        },
        {
          title: 'CPM',
          dataIndex: 'cpm',
          sorter: (a, b) => a.cpm - b.cpm,
          minWidth: 130,
          resizable: true,
          align: 'center',
          ellipsis: false,
        },
        {
          title: 'Taken Rate',
          dataIndex: 'taken_rate',
          sorter: (a, b) => a.taken_rate - b.taken_rate,
          minWidth: 130,
          resizable: true,
          align: 'center',
          ellipsis: false,
        },
        {
          title: t('Created Time'),
          dataIndex: 'created_time',
          sorter: (a, b) => {
            return new Date(a.created_time).getTime() - new Date(b.created_time).getTime();
          },
          minWidth: 160,
          resizable: true,
          align: 'center',
        },
        {
          title: t('Refresh Time'),
          dataIndex: 'refresh_time',
          sorter: (a, b) => {
            return new Date(a.refresh_time).getTime() - new Date(b.refresh_time).getTime();
          },
          minWidth: 160,
          resizable: true,
          align: 'center',
        },
      ];
    });
    // 注册 adset 表格的 hook,
    const {
      state: adsetColumnState,
      dynamicColumns: adsetDynaColumns,
      dynamicColumnItems: adsetDynamicColumnItems,
      handleColumnAllClick: handleAdsetColumnAllClick,
      handleColumnChange: handleAdsetColumnChange,
      reset: resetAdset,
      move: moveAdset,
    } = useTableDynamicColumns(adsetColumns as any);

    // 初始化Adset动态列并保持同步
    watchEffect(() => {
      if (
        adsetDynaColumns &&
        adsetDynaColumns.value &&
        adsetDynaColumns.value.length > 0 &&
        dynamicColumnsTab3.value.length === 0
      ) {
        dynamicColumnsTab3.value = [...adsetDynaColumns.value];
      }
    });

    // 选中 Adset
    const selectedAdsetState = reactive<{
      selectedRowKeys: Key[];
      selectedRows: any[];
    }>({
      selectedRowKeys: [],
      selectedRows: [],
    });
    // const selectedAdsetRowKeys = ref([]);
    const hasAdsetSelected = computed(() => selectedAdsetState.selectedRowKeys.length > 0);
    const onSelectedAdsetChange = (selectedRowKeys, selectedRows) => {
      console.log('onSelectedAdsetChange changed: ', selectedRowKeys);
      selectedAdsetState.selectedRowKeys = selectedRowKeys;
      selectedAdsetState.selectedRows = selectedRows;
    };
    // Adset Tab 的标签根据选中的 adset 数量变化
    const adsetTabTitle = computed(() => {
      console.log('adset title changed');
      const count = selectedAdsetState.selectedRowKeys.length;
      return count > 0 ? t('Adset') + `- ${count}` : t('Adset');
    });

    // Adset Tab 变化
    const handleAdsetChange = (pagination, filters, sorter) => {
      console.log('Various parameters', pagination, filters, sorter);

      // 前端分页：不更新context的current和pageSize，避免触发API请求
      // 分页逻辑完全由Ant Design表格组件处理

      // 但是我们需要保存分页信息用于汇总计算
      if (pagination) {
        console.log('Adset分页变化 - 原始参数:', pagination);
        adsetCurrentPage.value = pagination.current || 1;
        adsetPageSize.value = pagination.pageSize || 10;
        console.log('Adset分页状态直接更新后:', {
          current: adsetCurrentPage.value,
          pageSize: adsetPageSize.value,
        });

        // 强制触发汇总重新计算
        console.log('触发汇总重新计算');
      }

      // 保存排序状态
      adsetSorter.value = sorter;

      filteredAdsetInfo.value = filters;
      if (filters.adset_id) {
        switch (filters.adset_id[0]) {
          case '0':
            all_ad_ids.value = adsetTableDataState.dataSource.map(item => item.ad_ids).flat();
            return;
          case '1':
            all_ad_ids.value = adsetTableDataState.dataSource
              .filter(item => item.is_deleted_on_fb === true)
              .map(item => item.ad_ids)
              .flat();
            return;
          case '2':
            all_ad_ids.value = adsetTableDataState.dataSource
              .filter(item => item.is_deleted_on_fb != true)
              .map(item => item.ad_ids)
              .flat();
            return;
        }
      } else {
        // 重置 filter 后
        all_ad_ids.value = adsetTableDataState.dataSource.map(item => item.ad_ids).flat();
      }
      console.log(all_ad_ids.value.length);
    };

    // ---- ad tab --- //

    // 监听 adset table 数据变化, 更新 all_ad_ids, 但不更新 context 中的 request params
    const all_ad_ids = ref<string[]>([]);
    watch(adsetTableDataState, newState => {
      if (newState) {
        console.log('adset table data 更新了');
        console.log(newState);
        let tmp_ids = [];
        newState.dataSource.forEach(item => {
          tmp_ids = tmp_ids.concat(item.ad_ids);
        });
        all_ad_ids.value = tmp_ids;

        // 根据新的状态更新 selectedAdsetState.selectedRows
        selectedAdsetState.selectedRows = newState.dataSource.filter(
          item => selectedAdsetState.selectedRowKeys.includes(item.id), // 假设每个 item 有一个 id 属性
        );
      }
    });

    // 请求 ad insight 的参数
    const queryAdInsightParam = reactive({
      date_start: '2024-02-15',
      date_stop: '2024-02-15',
      ad_ids: undefined,
    });

    // 请求 adset insight 的上下文
    const fetchAdInsightContext = reactive({
      current: 1,
      pageSize: 10,
      requestParams: { ...queryAdInsightParam },
    });
    // 注册 获取 ad insight 的 hook, 在 tab click 事件里面更新 fetchAdsetInsightContext 的参数,就能触发 hook 执行
    const { context: adTableDataState, reload: reloadAdTable } = useFetchData(
      getAdInsight as any,
      fetchAdInsightContext,
      {
        onRequestError: onRequestError,
        pagination: false, // Ad tab使用前端分页，不发送pageNo/pageSize到后端
      },
    );

    watch(adTableDataState, newState => {
      if (newState) {
        console.log('ad table data 更新了');
        console.log(newState);

        // 根据新的状态更新 selectedAdState.selectedRows
        selectedAdState.selectedRows = newState.dataSource.filter(
          item => selectedAdState.selectedRowKeys.includes(item.id), // 假设每个 item 有一个 id 属性
        );
      }
    });

    // 为 ad 表格的 hook 注册准备,配置列
    const filteredAdInfo = ref();
    filteredAdInfo.value = {
      effective_status: [],
    };
    const adColumns = ref<any[]>([]);
    watchEffect(() => {
      const filteredAd = filteredAdInfo.value || {};
      adColumns.value = [
        {
          title: t('pages.ads.index'),
          dataIndex: 'index',
          customRender: ({ index }: any) => {
            return `${index + 1}`;
          },
          width: 80,
          align: 'center',
          fixed: 'left',
        },
        {
          title: t('pages.ads.ad.acc'),
          dataIndex: 'ad_account_id',
          ellipsis: true,
          align: 'left',
          minWidth: 190,
          resizable: true,
          sorter: (a, b) => a.ad_account_name - b.ad_account_name,
          fixed: 'left',
        },
        {
          title: t('pages.ads.currency'),
          dataIndex: 'currency',
          align: 'center',
          minWidth: 80,
        },
        {
          title: t('pages.ads.acc.status'),
          dataIndex: 'account_status',
          align: 'center',
          minWidth: 140,
        },
        {
          title: t('Campaign ID'),
          dataIndex: 'campaign_id',
          align: 'center',
          minWidth: 170,
          resizable: true,
        },
        {
          title: t('Campaign Name'),
          dataIndex: 'campaign_name',
          sorter: (a, b) => a.link_clicks - b.link_clicks,
          align: 'center',
          minWidth: 160,
          resizable: true,
          ellipsis: true,
        },
        {
          title: t('Adset ID'),
          dataIndex: 'adset_id',
          align: 'center',
          minWidth: 170,
          resizable: true,
        },
        {
          title: t('Adset Name'),
          dataIndex: 'adset_name',
          align: 'center',
          minWidth: 160,
          resizable: true,
          ellipsis: true,
        },
        {
          title: t('Ad ID'),
          dataIndex: 'ad_id',
          align: 'center',
          minWidth: 170,
          resizable: true,
          filters: [
            // { text: '全部', value: '0' },
            { text: 'FB 已删除', value: '1' },
            { text: 'FB 未删除', value: '2' },
          ],
          filterMultiple: false,
          filteredValue: filteredAd.ad_id || null,
          onFilter: (value: string, record) => {
            switch (value) {
              // case null:
              //   return true; // 对于'0'，我们接受任何记录
              case '1':
                return record.is_deleted_on_fb === true; // 对于'1'，我们只接受is_deleted_on_fb为true的记录
              case '2':
                return record.is_deleted_on_fb !== true; // 对于'2'，我们只接受is_deleted_on_fb不为true的记录
              default:
                return true;
            }
          },
        },
        {
          title: t('Ad Name'),
          dataIndex: 'ad_name',
          align: 'center',
          minWidth: 160,
          resizable: true,
          ellipsis: true,
        },
        {
          title: 'Ad Tags',
          dataIndex: 'tags',
          minWidth: 100,
          resizable: true,
        },
        {
          title: 'Off/On',
          dataIndex: 'status',
          align: 'center',
          minWidth: 100,
          resizable: true,
          ellipsis: true,
          customRender: ({ record }) => {
            const { is_deleted_on_fb, account_status, status, ad_id } = record;

            // 计算 switch 的是否禁用
            let isDisabled = is_deleted_on_fb || account_status !== 'ACTIVE';
            if (status === 'ARCHIVED' || status === 'DELETED') {
              isDisabled = true;
            }

            // 计算 switch 的当前状态
            const switchChecked = status === 'ACTIVE';

            // 返回 ASwitch 组件的虚拟节点
            return h(Switch, {
              checked: switchChecked,
              disabled: isDisabled,
              onChange: (checked: boolean, event) => {
                // 使用闭包访问 record
                console.log('Switch toggled:', checked);
                console.log('Current record:', record);
                const target_status = checked ? 'ACTIVE' : 'PAUSED';

                batchUpdateFbObjectStatus({
                  ids: [ad_id], // 使用解构赋值直接访问 campaign_id
                  object_type: 'ad',
                  status: target_status,
                })
                  .then(res => {
                    message.info(res['message']);
                  })
                  .catch(err => {
                    console.error(err);
                    message.error('Request failed');
                  });
                event.stopPropagation();
              },
            });
          },
        },
        {
          title: t('Delivery'),
          dataIndex: 'effective_status',
          sorter: (a, b) => a.effective_status.localeCompare(b.effective_status),
          align: 'center',
          minWidth: 160,
          resizable: true,
          ellipsis: true,
          filters: effective_status_map,
          filterMultiple: true,
          filteredValue: filteredAd.effective_status || null,
          onFilter: (value: string, record) => record.effective_status === value,
        },
        {
          title: t('Page'),
          dataIndex: 'pages',
          minWidth: 180,
          resizable: true,
          ellipsis: true,
          customRender: ({ record }: any) => {
            // 优先使用 creative.object_story_spec.page_id 在 pages 中查找匹配的页面
            const creativePageId: string = record.creative?.object_story_spec?.page_id;
            if (creativePageId && record.pages && Array.isArray(record.pages)) {
              const matchedPage: any = record.pages.find((page: any) => page.source_id === creativePageId);
              if (matchedPage) {
                return h(
                  'a',
                  {
                    href: `https://www.facebook.com/profile.php?id=${matchedPage.source_id}`,
                    target: '_blank',
                    onClick: (event: Event) => {
                      event.stopPropagation();
                    },
                  },
                  matchedPage.name,
                );
              }
            }

            // 如果没有找到匹配的页面，使用原有逻辑
            if (record.current_page_name && record.current_page_id) {
              return h(
                'a',
                {
                  href: `https://www.facebook.com/profile.php?id=${record.current_page_id}`,
                  target: '_blank',
                  onClick: (event: Event) => {
                    event.stopPropagation();
                  },
                },
                record.current_page_name,
              );
            } else {
              if (record.pages) {
                const pageName = record.pages[0]?.name;
                const pageId = record.pages[0]?.source_id;
                return h(
                  'a',
                  {
                    href: `https://www.facebook.com/profile.php?id=${pageId}`,
                    target: '_blank',
                    onClick: (event: Event) => {
                      event.stopPropagation();
                    },
                  },
                  pageName,
                );
              } else {
                return '';
              }
            }
          },
        },
        {
          title: t('Post'),
          dataIndex: 'creative',
          key: 'creative',
          minWidth: 365,
          resizable: true,
          ellipsis: true,
        },
        {
          title: 'Spend',
          dataIndex: 'spend',
          sorter: (a, b) => a.spend - b.spend,
          minWidth: 100,
          resizable: true,
          align: 'center',
          customRender: ({ text }) => {
            if (text !== '0') {
              return `${text}`;
            } else {
              return '-';
            }
          },
        },
        {
          title: 'Revenue',
          dataIndex: 'offer_conversions_value',
          sorter: (a, b) => a.offer_conversions_value - b.offer_conversions_value,
          minWidth: 120,
          resizable: true,
          align: 'center',
          customRender: ({ text }) => {
            return `${text} USD`;
          },
        },
        {
          title: 'ROI',
          dataIndex: 'roi',
          sorter: (a, b) => a.roi - b.roi,
          minWidth: 80,
          resizable: true,
          align: 'center',
          ellipsis: false,
        },
        {
          title: 'Roas(FB)',
          dataIndex: 'purchase_roas',
          sorter: (a, b) => a.purchase_roas - b.purchase_roas,
          minWidth: 120,
          resizable: true,
          align: 'center',
          ellipsis: false,
        },
        {
          title: 'Lead(FB)',
          dataIndex: 'lead',
          sorter: (a, b) => a.lead - b.lead,
          minWidth: 110,
          resizable: true,
          align: 'center',
          ellipsis: false,
        },
        {
          title: 'CPL(FB)',
          dataIndex: 'cost_per_lead',
          sorter: (a, b) => a.cost_per_lead - b.cost_per_lead,
          minWidth: 120,
          resizable: true,
          align: 'center',
        },
        {
          title: 'Offer Clicks',
          dataIndex: 'offer_clicks',
          sorter: (a, b) => a.offer_clicks - b.offer_clicks,
          minWidth: 130,
          resizable: true,
          align: 'center',
          ellipsis: false,
        },
        {
          title: 'Offer Leads',
          dataIndex: 'offer_leads',
          sorter: (a, b) => a.offer_leads - b.offer_leads,
          minWidth: 130,
          resizable: true,
          align: 'center',
          ellipsis: false,
        },
        {
          title: 'Purchase',
          dataIndex: 'offer_conversions',
          sorter: (a, b) => a.offer_conversions - b.offer_conversions,
          minWidth: 130,
          resizable: true,
          align: 'center',
          ellipsis: false,
        },
        {
          title: 'Offer CPC',
          dataIndex: 'offer_cpc',
          sorter: (a, b) => a.offer_cpc - b.offer_cpc,
          minWidth: 130,
          resizable: true,
          align: 'center',
          ellipsis: false,
        },
        {
          title: 'Offer EPC',
          dataIndex: 'offer_epc',
          sorter: (a, b) => a.offer_epc - b.offer_epc,
          minWidth: 130,
          resizable: true,
          align: 'center',
          ellipsis: false,
        },
        {
          title: 'Offer CPL',
          dataIndex: 'offer_cpl',
          sorter: (a, b) => a.offer_cpl - b.offer_cpl,
          minWidth: 140,
          resizable: true,
          align: 'center',
        },
        {
          title: 'Offer EPL',
          dataIndex: 'offer_epl',
          sorter: (a, b) => a.offer_epl - b.offer_epl,
          minWidth: 140,
          resizable: true,
          align: 'center',
        },
        {
          title: 'Link Clicks',
          dataIndex: 'link_clicks',
          sorter: (a, b) => a.link_clicks - b.link_clicks,
          minWidth: 130,
          resizable: true,
          align: 'center',
          ellipsis: false,
        },
        {
          title: 'Link CTR',
          dataIndex: 'link_ctr',
          sorter: (a, b) => a.link_ctr - b.link_ctr,
          minWidth: 130,
          resizable: true,
          align: 'center',
          ellipsis: false,
        },
        {
          title: 'Link CPC',
          dataIndex: 'link_cpc',
          sorter: (a, b) => a.link_cpc - b.link_cpc,
          minWidth: 130,
          resizable: true,
          align: 'center',
          ellipsis: false,
        },
        {
          title: 'CPM',
          dataIndex: 'cpm',
          sorter: (a, b) => a.cpm - b.cpm,
          minWidth: 130,
          resizable: true,
          align: 'center',
        },
        {
          title: 'Taken Rate',
          dataIndex: 'taken_rate',
          sorter: (a, b) => a.taken_rate - b.taken_rate,
          minWidth: 130,
          resizable: true,
          align: 'center',
          ellipsis: false,
        },
        {
          title: t('Created Time'),
          dataIndex: 'created_time',
          sorter: (a, b) => {
            return new Date(a.created_time).getTime() - new Date(b.created_time).getTime();
          },
          minWidth: 160,
          resizable: true,
          align: 'center',
        },
        {
          title: t('Refresh Time'),
          dataIndex: 'refresh_time',
          sorter: (a, b) => {
            return new Date(a.refresh_time).getTime() - new Date(b.refresh_time).getTime();
          },
          minWidth: 160,
          resizable: true,
          align: 'center',
        },
      ];
    });

    // Ad Tab 变化
    const handleAdChange = (pagination, filters, sorter) => {
      console.log('Various parameters', pagination, filters, sorter);

      // 前端分页：不更新context的current和pageSize，避免触发API请求
      // 分页逻辑完全由Ant Design表格组件处理

      // 但是我们需要保存分页信息用于汇总计算
      if (pagination) {
        adCurrentPage.value = pagination.current || 1;
        adPageSize.value = pagination.pageSize || 10;
      }

      // 保存排序状态
      adSorter.value = sorter;

      filteredAdInfo.value = filters;
    };

    // 注册 adset 表格的 hook,
    const {
      state: adColumnState,
      dynamicColumns: adDynaColumns,
      dynamicColumnItems: adDynamicColumnItems,
      handleColumnAllClick: handleAdColumnAllClick,
      handleColumnChange: handleAdColumnChange,
      reset: resetAd,
      move: moveAd,
    } = useTableDynamicColumns(adColumns as any);

    // 初始化Ad动态列并保持同步
    watchEffect(() => {
      if (
        adDynaColumns &&
        adDynaColumns.value &&
        adDynaColumns.value.length > 0 &&
        dynamicColumnsTab4.value.length === 0
      ) {
        dynamicColumnsTab4.value = [...adDynaColumns.value];
      }
    });

    // 选中 Adset
    // const selectedAdRowKeys = ref([]);
    const selectedAdState = reactive<{
      selectedRowKeys: Key[];
      selectedRows: any[];
    }>({
      selectedRowKeys: [],
      selectedRows: [],
    });
    const hasAdSelected = computed(() => selectedAdState.selectedRowKeys.length > 0);
    // Adset Tab 的标签根据选中的 adset 数量变化
    const adTabTitle = computed(() => {
      console.log('ad title changed');
      const count = selectedAdState.selectedRowKeys.length;
      return count > 0 ? t('Ad') + `- ${count}` : t('Ad');
    });

    // ad account 数据没有加载出来之前, campaign, adset, ad 不能点击
    const campaignDisable = computed(() => {
      return !(adAccountTableDataState.dataSource && adAccountTableDataState.dataSource.length > 0);
    });
    const adsetDisable = computed(() => {
      return !(all_adset_ids.value.length > 0);
    });
    const adDisable = computed(() => {
      return !(all_ad_ids.value.length > 0);
    });

    // -- 搜索相关的配置 --- //
    const rangePresets = ref([
      { label: 'Today', value: [dayjs(), dayjs()] },
      { label: 'Yesterday', value: [dayjs().subtract(1, 'd'), dayjs().subtract(1, 'd')] },
      { label: 'Last 7 Days', value: [dayjs().subtract(6, 'd'), dayjs()] },
      { label: 'Last 30 Days', value: [dayjs().subtract(29, 'd'), dayjs()] },
      { label: 'This Week', value: [dayjs().startOf('week'), dayjs().endOf('week')] },
      {
        label: 'Last Week',
        value: [
          dayjs().subtract(1, 'week').startOf('week'),
          dayjs().subtract(1, 'week').endOf('week'),
        ],
      },
      { label: 'This Month', value: [dayjs().startOf('month'), dayjs().endOf('month')] },
      {
        label: 'Last Month',
        value: [
          dayjs().subtract(1, 'month').startOf('month'),
          dayjs().subtract(1, 'month').endOf('month'),
        ],
      },
      { label: 'This Year', value: [dayjs().startOf('year'), dayjs().endOf('year')] },
    ]);
    const openDatePicker = ref(false);
    const date_range = ref<[Dayjs, Dayjs]>([dayjs(), dayjs()]); // 默认是今天时间

    const onDatePickerPanelChange = (val: [Dayjs, Dayjs], mode: any) => {
      console.log('date picker panel change:', val, mode);
    };
    const onDatePickerChange = (val: [Dayjs, Dayjs]) => {
      console.log('date picker change');
      console.log(val);
      // 如果选择的是预设时间范围，自动执行确认操作
      if (val && val.length === 2) {
        // 检查是否匹配预设的时间范围
        const isPresetSelection = rangePresets.value.some(preset => {
          const [presetStart, presetEnd] = preset.value;
          return val[0].isSame(presetStart, 'day') && val[1].isSame(presetEnd, 'day');
        });

        if (isPresetSelection) {
          // 自动执行确认操作
          date_range.value = val;
          onDatePickerOk();
        }
      }
    };
    const onDatePickerOpenChange = (status: boolean) => {
      console.log('date picker open change:', status);
      if (status) {
        openDatePicker.value = status;
      }
    };
    const dateDifference = computed(() => {
      if (date_range.value[0] && date_range.value[1]) {
        return date_range.value[1].diff(date_range.value[0], 'day');
      }
      return 0;
    });

    //  修改时间后, 检查是哪个 tab, 就修改哪个 tab 的 date_start 和 date_stop
    const onDatePickerOk = () => {
      console.log('date range ok');
      if (date_range.value) {
        if (activeTab.value === '1') {
          queryParam.date_start = dayjs(date_range.value[0]).format('YYYY-MM-DD');
          queryParam.date_stop = dayjs(date_range.value[1]).format('YYYY-MM-DD');
          fetchDataContext.requestParams.date_start = queryParam.date_start;
          fetchDataContext.requestParams.date_stop = queryParam.date_stop;
        } else if (activeTab.value === '2') {
          queryParam.date_start = dayjs(date_range.value[0]).format('YYYY-MM-DD');
          queryParam.date_stop = dayjs(date_range.value[1]).format('YYYY-MM-DD');
          fetchCampaignInsightContext.requestParams.date_start = queryParam.date_start;
          fetchCampaignInsightContext.requestParams.date_stop = queryParam.date_stop;
        } else if (activeTab.value === '3') {
          queryParam.date_start = dayjs(date_range.value[0]).format('YYYY-MM-DD');
          queryParam.date_stop = dayjs(date_range.value[1]).format('YYYY-MM-DD');
          fetchAdsetInsightContext.requestParams.date_start = queryParam.date_start;
          fetchAdsetInsightContext.requestParams.date_stop = queryParam.date_stop;
        } else if (activeTab.value === '4') {
          queryParam.date_start = dayjs(date_range.value[0]).format('YYYY-MM-DD');
          queryParam.date_stop = dayjs(date_range.value[1]).format('YYYY-MM-DD');
          fetchAdInsightContext.requestParams.date_start = queryParam.date_start;
          fetchAdInsightContext.requestParams.date_stop = queryParam.date_stop;
        }
        openDatePicker.value = false;
      }
    };

    // -- 流程控制相关 -- //
    const ad_account_ids = ref<string[]>([]);
    // inputParam 绑定 Form 的 account, ad account, bm, page, 因为有处理逻辑, 处理后再放到 queryAdAccountParam
    const inputParam = reactive({
      account_type: 'name',
      account_value: undefined,
      bm_type: 'name',
      bm_value: undefined,
      page_type: 'name',
      page_value: undefined,
      ad_account_type: 'name',
      ad_account_value: undefined,
    });
    const queryAdAccountParam = reactive({
      account_ids: undefined,
      account_names: undefined,
      ad_account_ids: undefined,
      ad_account_names: undefined,
      bm_ids: undefined,
      bm_names: undefined,
      page_names: undefined,
      page_ids: undefined,
      account_status: undefined,
      is_archived: 'false',
      enable_rule: '',
      tag_ids: undefined,
      fb_account_tags: [],
      ad_account_tags: [],
      user_ids: [],
      campaign_names: [],
      campaign_tags: [],
      others: ['exclude_archived_campaign'],
    });

    // watch(
    //   () => queryAdAccountParam.campaign_names,
    //   newVal => {
    //     queryParam.campaign_names = newVal;
    //   },
    // );

    const fetchAdAccountContext = reactive({
      current: 1,
      pageSize: 10,
      requestParams: { ...queryAdAccountParam },
    });
    const { context: AdAccountDataState } = useFetchData(
      queryFB_AD_AccountsApi,
      fetchAdAccountContext,
      { onRequestError: onRequestError },
    );

    // **用户体验优化：合并两层loading状态**
    // 当第一层(Ad Account获取)或第二层(Insight获取)任何一个在loading时都显示loading
    const adAccountTabLoading = computed(() => {
      return AdAccountDataState.loading || adAccountTableDataState.loading;
    });

    // watch(AdAccountDataState, newValue => {
    //   if (newValue) {
    //     console.log('ad account data state 更新了:', newValue);
    //     let tmp_ids = [];
    //     newValue.dataSource.forEach(item => {
    //       tmp_ids = tmp_ids.concat(item.id);
    //     });
    //     ad_account_ids.value = tmp_ids;
    //     console.log('新的 ad account id:');
    //     console.log(ad_account_ids);
    //   }
    // });

    // watch(ad_account_ids, newValue => {
    //   if (newValue) {
    //     console.log('ad account id 更新了:', newValue);
    //     queryParam.ad_account_ids = newValue;
    //     queryParam.date_start = dayjs(date_range.value[0]).format('YYYY-MM-DD');
    //     queryParam.date_stop = dayjs(date_range.value[1]).format('YYYY-MM-DD');
    //     const campaign_names = queryAdAccountParam.campaign_names;
    //     console.log('campaign names: ', campaign_names);
    //     queryParam.campaign_names = campaign_names;
    //     fetchDataContext.requestParams = { ...queryParam };
    //   }
    // });
    watchEffect(() => {
      // 确保我们访问了响应式属性以建立依赖关系
      const { dataSource } = AdAccountDataState;

      if (dataSource) {
        console.log('ad account data state 更新了:', dataSource);
        const tmp_ids = dataSource.map(item => item.id); // 使用 map 简化代码
        ad_account_ids.value = tmp_ids;
        console.log('新的 ad account id:');
        console.log(ad_account_ids.value);

        // 由于 Vue 3 的更新是异步的，我们可以使用 nextTick 来确保更新发生后再执行代码
        nextTick(() => {
          if (ad_account_ids.value) {
            console.log('ad account id 更新了:', ad_account_ids.value);
            queryParam.ad_account_ids = ad_account_ids.value;
            queryParam.date_start = dayjs(date_range.value[0]).format('YYYY-MM-DD');
            queryParam.date_stop = dayjs(date_range.value[1]).format('YYYY-MM-DD');
            queryParam.campaign_names = queryAdAccountParam.campaign_names;
            queryParam.campaign_tags = queryAdAccountParam.campaign_tags;
            queryParam.others = queryAdAccountParam.others;

            fetchDataContext.requestParams = { ...queryParam };
          }
        });
      }
    });

    // 搜索 - 更新第一层过滤条件，获取符合条件的Ad Account列表
    const handleSearch = (resetPagination = true) => {
      activeTab.value = '1';
      // 清空选中的数据
      selectedAdAccountState.selectedRowKeys = [];
      selectedCampaignState.selectedRowKeys = [];
      selectedAdsetState.selectedRowKeys = [];
      selectedAdState.selectedRowKeys = [];

      // 清空现有的id
      all_campaign_ids.value = [];
      all_adset_ids.value = [];
      all_ad_ids.value = [];

      // 更新 获取 Ad Account 的请求参数,触发获取 AdAccount 的请求
      console.log(queryAdAccountParam);

      // **关键修复**: 只有在真正的搜索操作时才重置分页，分页操作时保持当前页
      if (resetPagination) {
        fetchAdAccountContext.current = 1;
      }

      // **排除分页参数，让useFetchData完全控制分页**
      const { pageNo, pageSize: _, ...searchParams } = queryAdAccountParam as any;
      fetchAdAccountContext.requestParams = searchParams;
    };

    // 重置
    const handleReset = () => {
      // 清空输入框的内容
      inputParam.account_value = undefined;
      inputParam.ad_account_value = undefined;
      inputParam.bm_value = undefined;
      inputParam.page_value = undefined;

      queryAdAccountParam.account_ids = undefined;
      queryAdAccountParam.account_names = undefined;
      queryAdAccountParam.ad_account_ids = undefined;
      queryAdAccountParam.ad_account_names = undefined;
      queryAdAccountParam.bm_ids = undefined;
      queryAdAccountParam.bm_names = undefined;

      queryAdAccountParam.account_status = undefined;
      queryAdAccountParam.is_archived = 'false';
      queryAdAccountParam.enable_rule = '';

      queryAdAccountParam.fb_account_tags = [];
      queryAdAccountParam.ad_account_tags = [];
      queryAdAccountParam.user_ids = [];

      queryAdAccountParam.campaign_names = [];
      queryAdAccountParam.campaign_tags = [];
      queryAdAccountParam.others = ['exclude_archived_campaign'];

      // 清空选中的数据
      selectedAdAccountState.selectedRowKeys = [];
      selectedCampaignState.selectedRowKeys = [];
      selectedAdsetState.selectedRowKeys = [];
      selectedAdState.selectedRowKeys = [];

      // 清空现有的id
      all_campaign_ids.value = [];
      all_adset_ids.value = [];
      all_ad_ids.value = [];

      activeTab.value = '1';

      fetchAdAccountContext.current = 1;
      fetchAdAccountContext.requestParams = { ...queryAdAccountParam };
    };

    // 切换 name 和 id 时,清空原来的值
    watchEffect(() => {
      if (inputParam.account_type === 'name') {
        queryAdAccountParam.account_names = inputParam.account_value;
        queryAdAccountParam.account_ids = undefined;
      } else if (inputParam.account_type === 'id') {
        queryAdAccountParam.account_ids = inputParam.account_value;
        queryAdAccountParam.account_names = undefined;
      }

      if (inputParam.bm_type === 'name') {
        queryAdAccountParam.bm_names = inputParam.bm_value;
        queryAdAccountParam.bm_ids = undefined;
      } else if (inputParam.bm_type === 'id') {
        queryAdAccountParam.bm_ids = inputParam.bm_value;
        queryAdAccountParam.bm_names = undefined;
      }

      if (inputParam.ad_account_type === 'name') {
        queryAdAccountParam.ad_account_names = inputParam.ad_account_value;
        queryAdAccountParam.ad_account_ids = undefined;
      } else if (inputParam.ad_account_type === 'id') {
        queryAdAccountParam.ad_account_ids = inputParam.ad_account_value;
        queryAdAccountParam.ad_account_names = undefined;
      }

      if (inputParam.page_type === 'name') {
        queryAdAccountParam.page_names = inputParam.page_value;
        queryAdAccountParam.page_ids = undefined;
      } else if (inputParam.page_type === 'id') {
        queryAdAccountParam.page_ids = inputParam.page_value;
        queryAdAccountParam.page_names = undefined;
      }
    });

    watch(
      () => inputParam.account_type,
      (newVal, oldVal) => {
        console.log('type change: ', 'new val: ', newVal, ', old val: ', oldVal);
        if (newVal !== oldVal) {
          console.log('changed');
          inputParam.account_value = undefined;
          if (newVal === 'name') {
            queryAdAccountParam.account_names = undefined;
            queryAdAccountParam.account_ids = undefined;
          } else if (newVal === 'id') {
            queryAdAccountParam.account_ids = undefined;
            queryAdAccountParam.account_names = undefined;
          }
        }
      },
      { immediate: true },
    );

    watch(
      () => inputParam.ad_account_type,
      (newVal, oldVal) => {
        if (newVal !== oldVal) {
          inputParam.ad_account_value = undefined;
          if (newVal === 'name') {
            queryAdAccountParam.ad_account_names = undefined;
            queryAdAccountParam.ad_account_ids = undefined;
          } else if (newVal === 'id') {
            queryAdAccountParam.ad_account_ids = undefined;
            queryAdAccountParam.ad_account_names = undefined;
          }
        }
      },
      { immediate: true },
    );

    watch(
      () => inputParam.bm_type,
      (newVal, oldVal) => {
        if (newVal !== oldVal) {
          inputParam.bm_value = undefined;
          if (newVal === 'name') {
            queryAdAccountParam.bm_names = undefined;
            queryAdAccountParam.bm_ids = undefined;
          } else if (newVal === 'id') {
            queryAdAccountParam.bm_ids = undefined;
            queryAdAccountParam.bm_names = undefined;
          }
        }
      },
      { immediate: true },
    );

    watch(
      () => inputParam.page_type,
      (newVal, oldVal) => {
        if (newVal !== oldVal) {
          inputParam.page_value = undefined;
          if (newVal === 'name') {
            queryAdAccountParam.page_names = undefined;
            queryAdAccountParam.page_ids = undefined;
          } else if (newVal === 'id') {
            queryAdAccountParam.page_ids = undefined;
            queryAdAccountParam.page_names = undefined;
          }
        }
      },
      { immediate: true },
    );

    // 权限
    const canCreateAds = useAuth([Action.ADD]);
    const canPreviewAds = useAuth([Action.PREVIEW]);

    // reload
    const reload = () => {
      if (activeTab.value === '1') {
        reloadAdAccountTabe();
      } else if (activeTab.value === '2') {
        reloadCampaignTable();
      } else if (activeTab.value === '3') {
        reloadAdsetTable();
      } else if (activeTab.value === '4') {
        reloadAdTable();
      }
    };

    const getQueryString = acc => acc.map(({ ad_account_id }) => `aid=${ad_account_id}`).join('&');

    const gotoCreateAd = () => {
      if (activeTab.value === '1') {
        // 处理Ad Account标签页选择的情况
        if (selectedAdAccountState.selectedRows.length > 0) {
          router.push(`/ads/create-ads-v2?${getQueryString(selectedAdAccountState.selectedRows)}`);
        } else {
          message.warning(t('Please select at least one ad account'));
        }
      } else if (activeTab.value === '2') {
        // 处理Campaign标签页选择的情况
        if (selectedCampaignState.selectedRows.length > 0) {
          const params = selectedCampaignState.selectedRows
            .map(row => {
              return `aid=${row.ad_account_id}-${row.campaign_id}`;
            })
            .join('&');

          router.push(`/ads/create-ads-v2?${params}`);
        } else {
          message.warning(t('Please select at least one campaign'));
        }
      } else if (activeTab.value === '3') {
        // 处理Adset标签页选择的情况
        if (selectedAdsetState.selectedRows.length > 0) {
          const params = selectedAdsetState.selectedRows
            .map(row => {
              return `aid=${row.ad_account_id}-${row.campaign_id}-${row.adset_id}`;
            })
            .join('&');

          router.push(`/ads/create-ads-v2?${params}`);
        } else {
          message.warning(t('Please select at least one adset'));
        }
      }
    };

    // 获取 tags
    const fbAccountTagsData = ref<any>([]);
    interface TagOption {
      label: string;
      value: string;
    }
    const fbAccountTagOptions = ref<TagOption[]>([]);
    watch(fbAccountTagsData, newValue => {
      fbAccountTagOptions.value = newValue.map(tag => ({
        label: `${tag.name} - ${tag.user_name}`,
        value: tag.name,
      }));
    });

    const fbAdAccountTagsData = ref<any>([]);
    const fbAdAccountTagOptions = ref<TagOption[]>([]);
    watch(fbAdAccountTagsData, newValue => {
      fbAdAccountTagOptions.value = newValue.map(tag => ({
        label: `${tag.name} - ${tag.user_name}`,
        value: tag.name,
      }));
    });

    const userList = ref([]);
    onMounted(() => {
      getFbAccountsValidTags().then(res => {
        fbAccountTagsData.value = res.data;
      });
      getFbAdAccountsValidTags().then(res => {
        fbAdAccountTagsData.value = res.data;
      });

      console.log('role: ', role);
      if (role === 'admin') {
        getUsers().then(res => {
          userList.value = res.data;
        });
      }
    });

    // tagModal
    const tagModal = reactive({
      visible: false,
      model: null,
    });

    // budgetModal
    const budgetModal = reactive({
      visible: false,
      model: null,
    });

    const editBidStrategyModalRef = reactive({
      model: null,
      visible: false,
    });

    const showEditBidStrategyModal = record => {
      editBidStrategyModalRef.model = record;
      editBidStrategyModalRef.visible = true;
    };

    const campaignCellClick = ({ record, column }) => {
      return {
        onDblclick: () => {
          if (column.key === 'daily_budget') {
            const { is_deleted_on_fb, account_status } = record;
            // 计算 switch 的是否禁用
            const isDisabled = is_deleted_on_fb || account_status !== 'ACTIVE';

            if (isDisabled) {
              message.warning('Can not update budget');
              return;
            }

            const dailyBudget = record.daily_budget;
            const lifetimeBudget = record.lifetime_budget;

            // 用于存储预算信息
            let budgetValue: string | null = null;
            let budgetSource: string = '';

            if (dailyBudget !== null) {
              budgetValue = dailyBudget.toString();
              budgetSource = 'daily_budget';
            } else if (lifetimeBudget !== null) {
              budgetValue = lifetimeBudget.toString();
              budgetSource = 'lifetime_budget';
            }

            if (budgetValue === null) {
              return;
            }

            budgetModal.model = {
              budget_value: budgetValue,
              budget_source: budgetSource,
              object_type: 'campaign',
              object_id: record.campaign_id,
            };

            budgetModal.visible = true;
          } else if (column.key === 'campaign_name') {
            const { status } = record;

            if (status === 'ARCHIVED' || status === 'DELETED') {
              return;
            }
            renameModal.model = {
              id: record.campaign_id,
              object_name: record.campaign_name,
              object_type: 'campaign',
            };
            renameModal.visible = true;
          } else if (column.key === 'campaign_bid_strategy') {
            if (!record.campaign_bid_strategy) {
              return;
            }
            console.log('change campaign bid_startegy');
            showEditBidStrategyModal(record);
          }
        },
      };
    };

    const adsetCellClick = ({ record, column }) => {
      return {
        onDblclick: () => {
          if (column.key === 'daily_budget') {
            const { is_deleted_on_fb, account_status } = record;
            const isDisabled = is_deleted_on_fb || account_status !== 'ACTIVE';

            if (isDisabled) {
              message.warning('Can not update budget');
              return;
            }

            const dailyBudget = record.daily_budget;
            const lifetimeBudget = record.lifetime_budget;

            // 用于存储预算信息
            let budgetValue: string | null = null;
            let budgetSource: string = '';

            if (dailyBudget !== null) {
              budgetValue = dailyBudget.toString();
              budgetSource = 'daily_budget';
            } else if (lifetimeBudget !== null) {
              budgetValue = lifetimeBudget.toString();
              budgetSource = 'lifetime_budget';
            }

            if (budgetValue === null) {
              return;
            }

            budgetModal.model = {
              budget_value: budgetValue,
              budget_source: budgetSource,
              object_type: 'adset',
              object_id: record.adset_id,
            };

            budgetModal.visible = true;
          } else if (column.key === 'adset_name') {
            const { status } = record;
            if (status === 'ARCHIVED' || status === 'DELETED') {
              return;
            }
            renameModal.model = {
              id: record.adset_id,
              object_name: record.adset_name,
              object_type: 'adset',
            };
            renameModal.visible = true;
          } else if (column.key === 'campaign_name') {
            const { status } = record;
            if (status === 'ARCHIVED' || status === 'DELETED') {
              return;
            }
            renameModal.model = {
              id: record.campaign_id,
              object_name: record.campaign_name,
              object_type: 'campaign',
            };
            renameModal.visible = true;
          } else if (column.key === 'adset_bid_strategy') {
            console.log('change bid_startegy');
            showEditBidStrategyModal(record);
          }
        },
      };
    };

    const adCellClick = ({ record, column }) => {
      return {
        onDblclick: () => {
          const { is_deleted_on_fb, account_status } = record;
          const isDisabled = is_deleted_on_fb || account_status !== 'ACTIVE';

          if (isDisabled) {
            return;
          }

          if (column.key === 'ad_name') {
            const { status } = record;
            if (status === 'ARCHIVED' || status === 'DELETED') {
              return;
            }
            renameModal.model = {
              id: record.ad_id,
              object_name: record.ad_name,
              object_type: 'ad',
            };
            renameModal.visible = true;
          } else if (column.key === 'adset_name') {
            const { status } = record;
            if (status === 'ARCHIVED' || status === 'DELETED') {
              return;
            }
            renameModal.model = {
              id: record.adset_id,
              object_name: record.adset_name,
              object_type: 'adset',
            };
            renameModal.visible = true;
          } else if (column.key === 'campaign_name') {
            const { status } = record;
            if (status === 'ARCHIVED' || status === 'DELETED') {
              return;
            }
            renameModal.model = {
              id: record.campaign_id,
              object_name: record.campaign_name,
              object_type: 'campaign',
            };
            renameModal.visible = true;
          }
        },
      };
    };

    // budgetModal
    const copyModal = reactive({
      visible: false,
      model: null,
    });

    const copyAdsModal = reactive({
      visible: false,
      selectedAds: [],
      globalCount: 1,
      adCounts: {},
      loading: false,
      selectedMode: 'N-1-1',
    });

    const copyAdToAdsetsModal = reactive({
      visible: false,
      adSourceId: '',
      selectedAdsets: [],
      loading: false,
    });

    const renameModal = reactive({
      visible: false,
      model: null,
    });

    const infoModalRef = reactive({
      open: false,
      model: {} as PostInfoModel,
    });

    const languagesLoading = ref(false);
    const availableLanguages = ref<ApiLanguageItem[]>([]);

    // 获取语言列表
    const fetchLanguages = async () => {
      if (availableLanguages.value.length > 0) return;

      languagesLoading.value = true;
      try {
        const response = await getLanguages();
        if ((response as any).success && (response as any).data) {
          availableLanguages.value = (response as any).data;
        }
      } catch (error) {
        console.error('Failed to fetch languages:', error);
      } finally {
        languagesLoading.value = false;
      }
    };

    // 映射语言标签名称到语言代码（处理特殊情况）
    const mapLanguageLabelToCode = (labelName: string): string => {
      // 处理特殊情况
      if (labelName === 'swedish') {
        const lang = availableLanguages.value.find(l => l.english_name === 'Swedish');
        return lang ? lang.label_name : 'sv_SE';
      }
      if (labelName === 'afrikaans') {
        const lang = availableLanguages.value.find(l => l.english_name === 'Afrikaans');
        return lang ? lang.label_name : 'af_ZA';
      }

      // 查找匹配的语言
      const lang = availableLanguages.value.find(l => l.label_name === labelName);
      return lang ? lang.label_name : labelName;
    };

    // 获取语言显示名称
    const getLanguageName = (labelName: string): string => {
      const mappedCode = mapLanguageLabelToCode(labelName);
      const lang = availableLanguages.value.find(l => l.label_name === mappedCode);
      return lang ? lang.english_name : labelName;
    };

    // 获取语言本地名称
    const getLanguageNativeName = (labelName: string): string => {
      const mappedCode = mapLanguageLabelToCode(labelName);
      const lang = availableLanguages.value.find(l => l.label_name === mappedCode);
      return lang ? lang.native_name : labelName;
    };

    // 检查是否为多语言广告
    const isMultiLanguageAd = (record: any): boolean => {
      // 普通多语言广告：检查asset_feed_spec
      if (record?.creative?.asset_feed_spec) {
        return true;
      }

      // 目录多语言广告：检查customization_rules_spec
      if (record?.creative?.object_story_spec?.template_data?.customization_rules_spec) {
        const customizationRules = record.creative.object_story_spec.template_data.customization_rules_spec;
        return Array.isArray(customizationRules) && customizationRules.length > 0;
      }

      return false;
    };

    // 解析单语言图片广告
    const parseSingleImageAd = (record: any): PostInfoModel => {
      const creative = record.creative;
      const post = record.post;

      let primary_text = '';
      let headline = '';
      let description = '';
      let url = '';
      let url_tags = '';

      if (creative?.object_story_spec?.link_data) {
        const linkData = creative.object_story_spec.link_data;
        primary_text = linkData.message || '';
        headline = linkData.name || '';
        description = linkData.description || '';
        url = linkData.link || '';
      } else if (post?.object_story_spec?.link_data) {
        const linkData = post.object_story_spec.link_data;
        primary_text = linkData.message || '';
        headline = linkData.name || '';
        description = linkData.description || '';
        url = linkData.link || '';
      }

      // 修复 url_tags 逻辑：如果 creative 存在就只从 creative 获取
      if (creative) {
        url_tags = creative.url_tags || '';
      } else {
        url_tags = post?.url_tags || '';
      }

      return {
        primary_text,
        headline,
        description,
        url,
        url_tags,
        isMultiLanguage: false,
      };
    };

    // 解析单语言视频广告
    const parseSingleVideoAd = (record: any): PostInfoModel => {
      const creative = record.creative;
      const post = record.post;

      let primary_text = '';
      let headline = '';
      let description = '';
      let url = '';
      let url_tags = '';

      if (creative?.object_story_spec?.video_data) {
        const videoData = creative.object_story_spec.video_data;
        primary_text = videoData.message || '';
        headline = videoData.title || '';
        description = videoData.link_description || '';
        url = videoData.call_to_action?.value?.link || '';
      } else if (post?.object_story_spec?.video_data) {
        const videoData = post.object_story_spec.video_data;
        primary_text = videoData.message || '';
        headline = videoData.title || '';
        description = videoData.link_description || '';
        url = videoData.call_to_action?.value?.link || '';
      }

      // 修复 url_tags 逻辑：如果 creative 存在就只从 creative 获取
      if (creative) {
        url_tags = creative.url_tags || '';
      } else {
        url_tags = post?.url_tags || '';
      }

      return {
        primary_text,
        headline,
        description,
        url,
        url_tags,
        isMultiLanguage: false,
      };
    };

    // 解析多语言广告
    const parseMultiLanguageAd = (record: any): PostInfoModel => {
      const assetFeedSpec = record.creative?.asset_feed_spec;
      console.log('🔍 parseMultiLanguageAd - assetFeedSpec:', assetFeedSpec);

      if (!assetFeedSpec) {
        console.log('❌ 没有找到 asset_feed_spec，返回单语言');
        return { isMultiLanguage: false };
      }

      const {
        bodies = [],
        titles = [],
        descriptions = [],
        link_urls: linkUrls = [],
        asset_customization_rules: customizationRules = [],
      } = assetFeedSpec;

      console.log('🔍 解析多语言数据：');
      console.log('  - bodies 数量:', bodies.length);
      console.log('  - titles 数量:', titles.length);
      console.log('  - descriptions 数量:', descriptions.length);
      console.log('  - linkUrls 数量:', linkUrls.length);
      console.log('  - customizationRules 数量:', customizationRules.length);

      const languageData: Record<string, any> = {};
      const languages: LanguageInfoModel[] = [];

      // 确定默认语言
      let defaultLanguage = 'en_XX';
      const defaultRule = customizationRules.find((rule: any) => rule.is_default === true);
      if (defaultRule?.body_label?.name) {
        defaultLanguage = mapLanguageLabelToCode(defaultRule.body_label.name);
      }
      console.log('🔍 默认语言:', defaultLanguage);

      // 处理每种语言的内容
      bodies.forEach((body: any, index: number) => {
        console.log(`🔍 处理 body ${index}:`, body);
        if (body.adlabels && body.adlabels.length > 0) {
          const labelName = body.adlabels[0].name;
          const langCode = mapLanguageLabelToCode(labelName);
          console.log(`  - labelName: ${labelName} -> langCode: ${langCode}`);

          if (!languageData[langCode]) {
            languageData[langCode] = {
              languageCode: langCode,
              languageName: getLanguageName(langCode),
              nativeName: getLanguageNativeName(langCode),
              primary_text: '',
              headline: '',
              description: '',
              url: '',
              url_tags: record.creative ? record.creative.url_tags || '' : '',
              isDefault: langCode === defaultLanguage,
            };
            console.log(`  - 创建新语言数据: ${langCode}`, languageData[langCode]);
          }

          languageData[langCode].primary_text = body.text || '';
        }
      });

      // 处理标题
      titles.forEach((title: any, index: number) => {
        console.log(`🔍 处理 title ${index}:`, title);
        if (title.adlabels && title.adlabels.length > 0) {
          const labelName = title.adlabels[0].name;
          const langCode = mapLanguageLabelToCode(labelName);

          if (languageData[langCode]) {
            languageData[langCode].headline = title.text || '';
          }
        }
      });

      // 处理描述
      descriptions.forEach((desc: any, index: number) => {
        console.log(`🔍 处理 description ${index}:`, desc);
        if (desc.adlabels && desc.adlabels.length > 0) {
          const labelName = desc.adlabels[0].name;
          const langCode = mapLanguageLabelToCode(labelName);

          if (languageData[langCode]) {
            languageData[langCode].description = desc.text || '';
          }
        }
      });

      // 处理链接URL
      linkUrls.forEach((linkUrl: any, index: number) => {
        console.log(`🔍 处理 linkUrl ${index}:`, linkUrl);
        if (linkUrl.adlabels && linkUrl.adlabels.length > 0) {
          const labelName = linkUrl.adlabels[0].name;
          const langCode = mapLanguageLabelToCode(labelName);

          if (languageData[langCode]) {
            languageData[langCode].url = linkUrl.website_url || '';
          }
        }
      });

      console.log('🔍 处理后的语言数据:', languageData);

      // 转换为数组
      Object.keys(languageData).forEach(langCode => {
        languages.push(languageData[langCode] as LanguageInfoModel);
      });

      // 按默认语言排序（默认语言排在第一位）
      languages.sort((a, b) => {
        if (a.isDefault) return -1;
        if (b.isDefault) return 1;
        return a.languageName.localeCompare(b.languageName);
      });

      console.log('🔍 最终语言列表:', languages);

      const result = {
        isMultiLanguage: true,
        languages,
        url_tags: record.creative ? record.creative.url_tags || '' : '',
      };

      console.log('🔍 parseMultiLanguageAd 最终结果:', result);
      return result;
    };

    const showCopywriting = async (record: any) => {
      console.log('📍 showCopywriting - 原始广告数据:', record);

      // 确保语言列表已加载
      await fetchLanguages();

      // 检查数据结构
      console.log('📍 检查广告数据结构:');
      console.log('  - record.post:', !!record.post);
      console.log('  - record.creative:', !!record.creative);
      console.log('  - record.creative?.asset_feed_spec:', !!record.creative?.asset_feed_spec);
      console.log('  - record.creative?.object_story_spec:', !!record.creative?.object_story_spec);

      // 优先检查是否为多语言广告
      if (isMultiLanguageAd(record)) {
        console.log('📍 识别为多语言广告，开始解析...');
        console.log('  - asset_feed_spec:', record.creative?.asset_feed_spec);

        const parseResult = parseMultiLanguageAd(record);
        console.log('📍 多语言解析结果:', parseResult);
        console.log('  - isMultiLanguage:', parseResult.isMultiLanguage);
        console.log('  - languages 数量:', parseResult.languages?.length || 0);

        infoModalRef.model = parseResult;
      } else if (record.post) {
        console.log('📍 使用 post 数据（单语言）');
        // 如果有 post 数据，直接使用
        infoModalRef.model = record.post;
      } else {
        console.log('📍 识别为单语言广告');
        // 单语言广告，需要判断类型
        const hasVideoData =
          record?.creative?.object_story_spec?.video_data ||
          record?.post?.object_story_spec?.video_data;

        if (hasVideoData) {
          console.log('📍 解析为单语言视频广告');
          // 视频广告
          infoModalRef.model = parseSingleVideoAd(record);
        } else {
          console.log('📍 解析为单语言图片广告');
          // 图片广告
          infoModalRef.model = parseSingleImageAd(record);
        }
      }

      console.log('📍 最终传递给 Modal 的数据:', infoModalRef.model);
      infoModalRef.open = true;
    };

    const accountTotals = computed(() => {
      let totalFbLead = 0;
      let totalOfferLead = 0;
      let totalSpend = 0;
      let totalOfferClicks = 0;

      let fbAvgCpl = 0;
      let offerAvgCpl = 0;
      let offerCpc = null;

      adAccountTableDataState.dataSource.forEach(({ spend, lead, offer_leads, offer_clicks }) => {
        totalSpend += spend;
        totalFbLead += lead;
        totalOfferLead += offer_leads;
        totalOfferClicks += offer_clicks;
      });
      if (totalFbLead != 0) {
        fbAvgCpl = totalSpend / totalFbLead;
        fbAvgCpl = parseFloat(fbAvgCpl.toFixed(2));
      }
      if (totalOfferLead != 0) {
        offerAvgCpl = totalSpend / totalOfferLead;
        offerAvgCpl = parseFloat(offerAvgCpl.toFixed(2));
      }
      if (totalOfferClicks != 0) {
        offerCpc = totalSpend / totalOfferClicks;
        offerCpc = parseFloat(offerCpc.toFixed(2));
      }
      totalSpend = parseFloat(totalSpend.toFixed(2));

      console.log('totals: ', totalSpend, totalFbLead, totalOfferLead, fbAvgCpl, offerAvgCpl);
      return { totalSpend, fbAvgCpl, offerAvgCpl, offerCpc };
    });

    const campaignTotals = computed(() => {
      let totalFbLead = 0;
      let totalOfferLead = 0;
      let totalSpend = 0;
      let totalOfferClicks = 0;
      let totalRevenue = 0;

      let fbAvgCpl = 0;
      let offerAvgCpl = 0;
      let offerCpc = null;

      // 获取当前页面的数据（前端分页）
      // 添加对分页状态的依赖以确保响应式更新
      const currentPage = fetchCampaignInsightContext.current || 1;
      const pageSize = fetchCampaignInsightContext.pageSize || 10;
      const dataSource = campaignTableDataState.dataSource || [];

      // 应用排序
      const sortedData = applySorting(dataSource, campaignSorter.value);

      const startIndex = (currentPage - 1) * pageSize;
      const endIndex = startIndex + pageSize;
      const currentPageData = sortedData.slice(startIndex, endIndex);

      currentPageData.forEach(
        ({ spend, lead, offer_leads, offer_clicks, offer_conversions_value }) => {
          totalSpend += spend || 0;
          totalFbLead += lead || 0;
          totalOfferLead += offer_leads || 0;
          totalOfferClicks += offer_clicks || 0;
          totalRevenue += offer_conversions_value || 0;
        },
      );
      if (totalFbLead != 0) {
        fbAvgCpl = totalSpend / totalFbLead;
        fbAvgCpl = parseFloat(fbAvgCpl.toFixed(2));
      }
      if (totalOfferLead != 0) {
        offerAvgCpl = totalSpend / totalOfferLead;
        offerAvgCpl = parseFloat(offerAvgCpl.toFixed(2));
      }
      if (totalOfferClicks != 0) {
        offerCpc = totalSpend / totalOfferClicks;
        offerCpc = parseFloat(offerCpc.toFixed(2));
      }
      totalSpend = parseFloat(totalSpend.toFixed(2));
      totalRevenue = parseFloat(totalRevenue.toFixed(2));

      return { totalSpend, totalRevenue, fbAvgCpl, offerAvgCpl, offerCpc };
    });

    // Adset tab汇总计算
    const adsetTotals = computed(() => {
      let totalFbLead = 0;
      let totalOfferLead = 0;
      let totalSpend = 0;
      let totalOfferClicks = 0;
      let totalRevenue = 0;

      let fbAvgCpl = 0;
      let offerAvgCpl = 0;
      let offerCpc = null;

      // 获取当前页面的数据（前端分页）
      // 添加对分页状态的依赖以确保响应式更新
      const currentPage = fetchAdsetInsightContext.current || 1;
      const pageSize = fetchAdsetInsightContext.pageSize || 10;
      const dataSource = adsetTableDataState.dataSource || [];

      // 应用排序
      const sortedData = applySorting(dataSource, adsetSorter.value);

      const startIndex = (currentPage - 1) * pageSize;
      const endIndex = startIndex + pageSize;
      const currentPageData = sortedData.slice(startIndex, endIndex);

      currentPageData.forEach(
        ({ spend, lead, offer_leads, offer_clicks, offer_conversions_value }) => {
          totalSpend += spend || 0;
          totalFbLead += lead || 0;
          totalOfferLead += offer_leads || 0;
          totalOfferClicks += offer_clicks || 0;
          totalRevenue += offer_conversions_value || 0;
        },
      );
      if (totalFbLead != 0) {
        fbAvgCpl = totalSpend / totalFbLead;
        fbAvgCpl = parseFloat(fbAvgCpl.toFixed(2));
      }
      if (totalOfferLead != 0) {
        offerAvgCpl = totalSpend / totalOfferLead;
        offerAvgCpl = parseFloat(offerAvgCpl.toFixed(2));
      }
      if (totalOfferClicks != 0) {
        offerCpc = totalSpend / totalOfferClicks;
        offerCpc = parseFloat(offerCpc.toFixed(2));
      }
      totalSpend = parseFloat(totalSpend.toFixed(2));
      totalRevenue = parseFloat(totalRevenue.toFixed(2));

      return { totalSpend, totalRevenue, fbAvgCpl, offerAvgCpl, offerCpc };
    });

    // Ad tab汇总计算
    const adTotals = computed(() => {
      let totalFbLead = 0;
      let totalOfferLead = 0;
      let totalSpend = 0;
      let totalOfferClicks = 0;
      let totalRevenue = 0;

      let fbAvgCpl = 0;
      let offerAvgCpl = 0;
      let offerCpc = null;

      // 获取当前页面的数据（前端分页）
      // 添加对分页状态的依赖以确保响应式更新
      const currentPage = fetchAdInsightContext.current || 1;
      const pageSize = fetchAdInsightContext.pageSize || 10;
      const dataSource = adTableDataState.dataSource || [];

      // 应用排序
      const sortedData = applySorting(dataSource, adSorter.value);

      const startIndex = (currentPage - 1) * pageSize;
      const endIndex = startIndex + pageSize;
      const currentPageData = sortedData.slice(startIndex, endIndex);

      currentPageData.forEach(
        ({ spend, lead, offer_leads, offer_clicks, offer_conversions_value }) => {
          totalSpend += spend || 0;
          totalFbLead += lead || 0;
          totalOfferLead += offer_leads || 0;
          totalOfferClicks += offer_clicks || 0;
          totalRevenue += offer_conversions_value || 0;
        },
      );
      if (totalFbLead != 0) {
        fbAvgCpl = totalSpend / totalFbLead;
        fbAvgCpl = parseFloat(fbAvgCpl.toFixed(2));
      }
      if (totalOfferLead != 0) {
        offerAvgCpl = totalSpend / totalOfferLead;
        offerAvgCpl = parseFloat(offerAvgCpl.toFixed(2));
      }
      if (totalOfferClicks != 0) {
        offerCpc = totalSpend / totalOfferClicks;
        offerCpc = parseFloat(offerCpc.toFixed(2));
      }
      totalSpend = parseFloat(totalSpend.toFixed(2));
      totalRevenue = parseFloat(totalRevenue.toFixed(2));

      return { totalSpend, totalRevenue, fbAvgCpl, offerAvgCpl, offerCpc };
    });

    // ========== 新的动态汇总系统 ==========

    // 计算动态汇总的工具函数
    const getSummaryColumnIndex = (dynamicColumns, dataIndex) => {
      if (!dynamicColumns || !dynamicColumns.value || !Array.isArray(dynamicColumns.value)) {
        return -1;
      }
      const index = dynamicColumns.value.findIndex(col => col.dataIndex === dataIndex);
      // 考虑checkbox列占用索引0，所以实际索引需要+1
      return index >= 0 ? index + 1 : -1;
    };

    // 汇总配置
    const summaryConfig = {
      adAccount: [
        { dataIndex: 'spend', type: 'sum', format: val => val.toFixed(2) },
        {
          dataIndex: 'offer_conversions_value',
          type: 'sum',
          format: val => val.toFixed(2) + ' USD',
        },
        { dataIndex: 'roi', type: 'custom', customFn: 'calculateROI' },
        { dataIndex: 'lead', type: 'sum', format: val => val },
        { dataIndex: 'cost_per_lead', type: 'custom', customFn: 'calculateCostPerLead' },
        { dataIndex: 'offer_conversions', type: 'sum', format: val => val },
        { dataIndex: 'offer_clicks', type: 'sum', format: val => val },
        { dataIndex: 'offer_leads', type: 'sum', format: val => val },
        { dataIndex: 'offer_epc', type: 'custom', customFn: 'calculateOfferEPC' },
        { dataIndex: 'offer_cpc', type: 'custom', customFn: 'calculateOfferCPC' },
        { dataIndex: 'offer_cpl', type: 'custom', customFn: 'calculateOfferCPL' },
        { dataIndex: 'offer_epl', type: 'custom', customFn: 'calculateOfferEPL' },
        { dataIndex: 'link_clicks', type: 'sum', format: val => val },
        { dataIndex: 'link_ctr', type: 'average', format: val => val.toFixed(2) + '%' },
        { dataIndex: 'link_cpc', type: 'average', format: val => val.toFixed(2) },
        { dataIndex: 'taken_rate', type: 'average', format: val => val.toFixed(2) + '%' },
        { dataIndex: 'cpm', type: 'average', format: val => val.toFixed(2) },
      ],
      campaign: [
        { dataIndex: 'daily_budget', type: 'sum', format: val => val.toFixed(2) },
        { dataIndex: 'spend', type: 'sum', format: val => val.toFixed(2) },
        {
          dataIndex: 'offer_conversions_value',
          type: 'sum',
          format: val => val.toFixed(2) + ' USD',
        },
        { dataIndex: 'roi', type: 'custom', customFn: 'calculateROI' },
        { dataIndex: 'lead', type: 'sum', format: val => val },
        { dataIndex: 'cost_per_lead', type: 'custom', customFn: 'calculateCostPerLead' },
        { dataIndex: 'offer_conversions', type: 'sum', format: val => val },
        { dataIndex: 'offer_clicks', type: 'sum', format: val => val },
        { dataIndex: 'offer_leads', type: 'sum', format: val => val },
        { dataIndex: 'offer_epc', type: 'custom', customFn: 'calculateOfferEPC' },
        { dataIndex: 'offer_cpc', type: 'custom', customFn: 'calculateOfferCPC' },
        { dataIndex: 'offer_cpl', type: 'custom', customFn: 'calculateOfferCPL' },
        { dataIndex: 'offer_epl', type: 'custom', customFn: 'calculateOfferEPL' },
        { dataIndex: 'link_clicks', type: 'sum', format: val => val },
        { dataIndex: 'link_ctr', type: 'average', format: val => val.toFixed(2) + '%' },
        { dataIndex: 'link_cpc', type: 'average', format: val => val.toFixed(2) },
        { dataIndex: 'taken_rate', type: 'average', format: val => val.toFixed(2) + '%' },
        { dataIndex: 'cpm', type: 'average', format: val => val.toFixed(2) },
      ],
      adset: [
        { dataIndex: 'daily_budget', type: 'sum', format: val => val.toFixed(2) },
        { dataIndex: 'spend', type: 'sum', format: val => val.toFixed(2) },
        {
          dataIndex: 'offer_conversions_value',
          type: 'sum',
          format: val => val.toFixed(2) + ' USD',
        },
        { dataIndex: 'roi', type: 'custom', customFn: 'calculateROI' },
        { dataIndex: 'lead', type: 'sum', format: val => val },
        { dataIndex: 'cost_per_lead', type: 'custom', customFn: 'calculateCostPerLead' },
        { dataIndex: 'offer_conversions', type: 'sum', format: val => val },
        { dataIndex: 'offer_clicks', type: 'sum', format: val => val },
        { dataIndex: 'offer_leads', type: 'sum', format: val => val },
        { dataIndex: 'offer_epc', type: 'custom', customFn: 'calculateOfferEPC' },
        { dataIndex: 'offer_cpc', type: 'custom', customFn: 'calculateOfferCPC' },
        { dataIndex: 'offer_cpl', type: 'custom', customFn: 'calculateOfferCPL' },
        { dataIndex: 'offer_epl', type: 'custom', customFn: 'calculateOfferEPL' },
        { dataIndex: 'link_clicks', type: 'sum', format: val => val },
        { dataIndex: 'link_ctr', type: 'average', format: val => val.toFixed(2) + '%' },
        { dataIndex: 'link_cpc', type: 'average', format: val => val.toFixed(2) },
        { dataIndex: 'taken_rate', type: 'average', format: val => val.toFixed(2) + '%' },
        { dataIndex: 'cpm', type: 'average', format: val => val.toFixed(2) },
      ],
      ad: [
        { dataIndex: 'spend', type: 'sum', format: val => val.toFixed(2) },
        {
          dataIndex: 'offer_conversions_value',
          type: 'sum',
          format: val => val.toFixed(2) + ' USD',
        },
        { dataIndex: 'roi', type: 'custom', customFn: 'calculateROI' },
        { dataIndex: 'lead', type: 'sum', format: val => val },
        { dataIndex: 'cost_per_lead', type: 'custom', customFn: 'calculateCostPerLead' },
        { dataIndex: 'offer_conversions', type: 'sum', format: val => val },
        { dataIndex: 'offer_clicks', type: 'sum', format: val => val },
        { dataIndex: 'offer_leads', type: 'sum', format: val => val },
        { dataIndex: 'offer_epc', type: 'custom', customFn: 'calculateOfferEPC' },
        { dataIndex: 'offer_cpc', type: 'custom', customFn: 'calculateOfferCPC' },
        { dataIndex: 'offer_cpl', type: 'custom', customFn: 'calculateOfferCPL' },
        { dataIndex: 'offer_epl', type: 'custom', customFn: 'calculateOfferEPL' },
        { dataIndex: 'link_clicks', type: 'sum', format: val => val },
        { dataIndex: 'link_ctr', type: 'average', format: val => val.toFixed(2) + '%' },
        { dataIndex: 'link_cpc', type: 'average', format: val => val.toFixed(2) },
        { dataIndex: 'taken_rate', type: 'average', format: val => val.toFixed(2) + '%' },
        { dataIndex: 'cpm', type: 'average', format: val => val.toFixed(2) },
      ],
    };

    // 自定义汇总计算函数
    const summaryCalculations = {
      calculateROI: data => {
        const totalSpend = data.reduce((sum, item) => sum + (Number(item.spend) || 0), 0);
        const totalRevenue = data.reduce(
          (sum, item) => sum + (Number(item.offer_conversions_value) || 0),
          0,
        );
        if (totalSpend === 0) return '0%';
        return (((totalRevenue - totalSpend) / totalSpend) * 100).toFixed(2) + '%';
      },
      calculateCostPerLead: data => {
        const totalSpend = data.reduce((sum, item) => sum + (Number(item.spend) || 0), 0);
        const totalLead = data.reduce((sum, item) => sum + (Number(item.lead) || 0), 0);
        if (totalLead === 0) return '0';
        return (totalSpend / totalLead).toFixed(2);
      },
      calculateOfferEPC: data => {
        const totalRevenue = data.reduce(
          (sum, item) => sum + (Number(item.offer_conversions_value) || 0),
          0,
        );
        const totalClicks = data.reduce((sum, item) => sum + (Number(item.offer_clicks) || 0), 0);
        if (totalClicks === 0) return '0';
        return (totalRevenue / totalClicks).toFixed(2);
      },
      calculateOfferCPC: data => {
        const totalSpend = data.reduce((sum, item) => sum + (Number(item.spend) || 0), 0);
        const totalClicks = data.reduce((sum, item) => sum + (Number(item.offer_clicks) || 0), 0);
        if (totalClicks === 0) return '0';
        return (totalSpend / totalClicks).toFixed(2);
      },
      calculateOfferCPL: data => {
        const totalSpend = data.reduce((sum, item) => sum + (Number(item.spend) || 0), 0);
        const totalLeads = data.reduce((sum, item) => sum + (Number(item.offer_leads) || 0), 0);
        if (totalLeads === 0) return '0';
        return (totalSpend / totalLeads).toFixed(2);
      },
      calculateOfferEPL: data => {
        const totalRevenue = data.reduce(
          (sum, item) => sum + (Number(item.offer_conversions_value) || 0),
          0,
        );
        const totalLeads = data.reduce((sum, item) => sum + (Number(item.offer_leads) || 0), 0);
        if (totalLeads === 0) return '0';
        return (totalRevenue / totalLeads).toFixed(2);
      },
    };

    // 计算汇总值的通用函数
    const calculateSummaryValue = (data, config) => {
      if (!data || data.length === 0) return '0';

      switch (config.type) {
        case 'sum': {
          const sum = data.reduce((total, item) => {
            const value = Number(item[config.dataIndex]);
            return total + (isNaN(value) ? 0 : value);
          }, 0);
          return config.format ? config.format(sum) : sum.toString();
        }

        case 'average': {
          const validValues = data.filter(item => {
            const value = Number(item[config.dataIndex]);
            return !isNaN(value) && value > 0;
          });
          if (validValues.length === 0) return '0';
          const avg =
            validValues.reduce((total, item) => total + Number(item[config.dataIndex]), 0) /
            validValues.length;
          return config.format ? config.format(avg) : avg.toFixed(2);
        }

        case 'custom':
          if (summaryCalculations[config.customFn]) {
            return summaryCalculations[config.customFn](data);
          }
          return '0';

        default:
          return '0';
      }
    };

    // 获取当前页数据的函数（针对前端分页）
    const getCurrentPageData = (dataSource, sorter, current, pageSize) => {
      if (!dataSource || dataSource.length === 0) return [];

      // 应用排序（使用现有的applySorting函数）
      let sortedData = dataSource;
      try {
        // applySorting函数在文件前面定义，应该可以使用
        if (sorter && typeof applySorting === 'function') {
          sortedData = applySorting(dataSource, sorter);
        }
      } catch (error) {
        // 排序失败时使用原始数据
        sortedData = dataSource;
      }

      // 应用分页
      const startIndex = (current - 1) * pageSize;
      const endIndex = startIndex + pageSize;
      return sortedData.slice(startIndex, endIndex);
    };

    // 为每个tab计算汇总数据
    const newAdAccountSummaryItems = computed(() => {
      // 安全检查
      if (!dynamicColumns || !dynamicColumns.value || !adAccountTableDataState.dataSource) {
        return [];
      }

      const config = summaryConfig.adAccount;
      const data = adAccountTableDataState.dataSource || [];

      const items = config.map(item => {
        const index = getSummaryColumnIndex(dynamicColumns, item.dataIndex);
        const value = calculateSummaryValue(data, item);
        return {
          ...item,
          index,
          value,
        };
      });

      const filteredItems = items.filter(item => item.index >= 0);

      return filteredItems;
    });

    const newCampaignSummaryItems = computed(() => {
      // 安全检查
      if (
        !campaignDynaColumns ||
        !campaignDynaColumns.value ||
        !campaignTableDataState.dataSource
      ) {
        return [];
      }

      const config = summaryConfig.campaign;
      const dataSource = campaignTableDataState.dataSource || [];

      // 获取当前页数据（前端分页）- 使用实际的分页状态
      const currentPage = campaignCurrentPage.value;
      const pageSize = campaignPageSize.value;
      const data = getCurrentPageData(dataSource, campaignSorter.value, currentPage, pageSize);

      return config
        .map(item => ({
          ...item,
          index: getSummaryColumnIndex(campaignDynaColumns, item.dataIndex),
          value: calculateSummaryValue(data, item),
        }))
        .filter(item => item.index >= 0);
    });

    const newAdsetSummaryItems = computed(() => {
      // 安全检查
      if (!adsetDynaColumns || !adsetDynaColumns.value || !adsetTableDataState.dataSource) {
        return [];
      }

      const config = summaryConfig.adset;
      const dataSource = adsetTableDataState.dataSource || [];

      // 获取当前页数据（前端分页）- 使用实际的分页状态
      const currentPage = adsetCurrentPage.value;
      const pageSize = adsetPageSize.value;
      console.log('Adset汇总计算 - 分页状态:', {
        currentPage,
        pageSize,
        totalData: dataSource.length,
      });
      const data = getCurrentPageData(dataSource, adsetSorter.value, currentPage, pageSize);
      console.log('Adset汇总计算 - 当前页数据:', data.length);

      return config
        .map(item => ({
          ...item,
          index: getSummaryColumnIndex(adsetDynaColumns, item.dataIndex),
          value: calculateSummaryValue(data, item),
        }))
        .filter(item => item.index >= 0);
    });

    const newAdSummaryItems = computed(() => {
      // 安全检查
      if (!adDynaColumns || !adDynaColumns.value || !adTableDataState.dataSource) {
        return [];
      }

      const config = summaryConfig.ad;
      const dataSource = adTableDataState.dataSource || [];

      // 获取当前页数据（前端分页）- 使用实际的分页状态
      const currentPage = adCurrentPage.value;
      const pageSize = adPageSize.value;
      const data = getCurrentPageData(dataSource, adSorter.value, currentPage, pageSize);

      return config
        .map(item => ({
          ...item,
          index: getSummaryColumnIndex(adDynaColumns, item.dataIndex),
          value: calculateSummaryValue(data, item),
        }))
        .filter(item => item.index >= 0);
    });

    const selectRow = (selectedTarget, record) => {
      console.log('select row, record: ', record);
      // console.log(selectedTarget);
      const selectedRowKeys = [...selectedTarget.selectedRowKeys];
      const selectedRows = [...selectedTarget.selectedRows];
      // console.log(selectedRowKeys, selectedRows);

      const index = selectedRowKeys.indexOf(record.id);

      // 如果已经选中
      if (index !== -1) {
        console.log('已经选中了');
        selectedRowKeys.splice(index, 1);
        selectedRows.splice(index, 1);
      } else {
        console.log('没有选中');
        selectedRowKeys.push(record.id);
        selectedRows.push(record);
      }
      // selectedTarget.value = selectedRowKeys;
      selectedTarget.selectedRowKeys = selectedRowKeys;
      selectedTarget.selectedRows = selectedRows;
      console.log(selectedTarget);
      // return selectedRowKeys;
    };

    const customAdAccountRow = record => {
      return {
        onClick: () => {
          selectRow(selectedAdAccountState, record);
        },
      };
    };

    const customCampaignRow = record => {
      return {
        onClick: () => {
          selectRow(selectedCampaignState, record);
        },
      };
    };

    const customAdsetRow = record => {
      return {
        onClick: () => {
          selectRow(selectedAdsetState, record);
        },
      };
    };

    const customAdRow = record => {
      return {
        onClick: () => {
          // const currentRowKeys = [...selectedAdState.selectedRowKeys];
          // selectedAdState.selectedRowKeys = selectRow(currentRowKeys, record);
          selectRow(selectedAdState, record);
        },
      };
    };

    const adAccCellClick = () => {
      return {
        // onClick: event => {
        //   console.log('cell click');
        //   // event.stopPropagation();
        // },
        onDblclick: event => {
          event.stopPropagation();
        },
      };
    };

    // 添加 deleteModal 响应式变量
    const deleteModal = reactive({
      visible: false,
      model: null,
    });

    // 添加 multiLanguageModal 响应式变量
    const multiLanguageModal = reactive({
      visible: false,
      selectedAds: [],
    });

    // 添加多语言预览模态框状态
    const multiLanguagePreviewModal = reactive({
      visible: false,
      adData: null,
    });

    // payment modal 状态
    const paymentModal = reactive({
      visible: false,
      selectedData: [],
      tabType: '1', // '1' for ad account, '2' for campaign, '3' for adset
    });

    // batch budget modal 状态
    const batchBudgetModal = reactive({
      visible: false,
      selectedData: [],
      tabType: '2' as '2' | '3', // '2' for campaign, '3' for adset
    });

    // 多语言管理相关逻辑
    const checkAdMultiLanguageSupport = (ad: any) => {
      const creative = ad.creative;
      if (!creative || !creative.object_story_spec) {
        return {
          supportsMultiLanguage: false,
          hasAssetFeedSpec: false,
          adType: null,
        };
      }

      const hasAssetFeedSpec = !!creative.asset_feed_spec;
      let adType = null;

      if (hasAssetFeedSpec) {
        // 如果有 asset_feed_spec，通过 ad_formats 判断类型
        const adFormats = creative.asset_feed_spec.ad_formats;
        if (adFormats && Array.isArray(adFormats)) {
          if (adFormats.includes('SINGLE_VIDEO')) {
            adType = 'video';
          } else if (adFormats.includes('SINGLE_IMAGE')) {
            adType = 'image';
          }
        }
      } else {
        // 如果没有 asset_feed_spec，通过 object_story_spec 的 video_data 判断
        if (creative.object_story_spec.video_data) {
          adType = 'video';
        } else {
          adType = 'image';
        }
      }

      return {
        supportsMultiLanguage: true,
        hasAssetFeedSpec,
        adType,
      };
    };

    // 检查选中的广告中是否有支持多语言的
    const showMultiLanguageManagement = computed(() => {
      if (activeTab.value !== '4' || selectedAdState.selectedRowKeys.length === 0) {
        return false;
      }

      return selectedAdState.selectedRows.some(ad => {
        const { supportsMultiLanguage } = checkAdMultiLanguageSupport(ad);
        return supportsMultiLanguage;
      });
    });

    // 按广告账户分组显示广告
    const groupedAds = computed(() => {
      const groups: Record<string, { name: string; ads: any[] }> = {};
      copyAdsModal.selectedAds.forEach((ad: any) => {
        const accountId = ad.ad_account_id;
        if (!groups[accountId]) {
          groups[accountId] = {
            name: `${ad.ad_account_name} (${accountId})`,
            ads: [],
          };
        }
        groups[accountId].ads.push(ad);

        // 初始化广告复制数量
        if (!copyAdsModal.adCounts[ad.ad_id]) {
          copyAdsModal.adCounts[ad.ad_id] = copyAdsModal.globalCount;
        }
      });
      return groups;
    });

    // 应用全局复制数量到所有广告
    const applyGlobalCount = () => {
      copyAdsModal.selectedAds.forEach(ad => {
        copyAdsModal.adCounts[ad.ad_id] = copyAdsModal.globalCount;
      });
    };

    // 处理全局数量变化
    const handleGlobalCountChange = (value: number) => {
      if (value && value >= 1 && value <= 50) {
        copyAdsModal.globalCount = value;
      }
    };

    // 处理复制广告到广告组
    const handleCopyAdToAdsets = async () => {
      try {
        if (!copyAdToAdsetsModal.adSourceId) {
          message.error(t('Please Input Ad Source ID'));
          return;
        }

        if (copyAdToAdsetsModal.selectedAdsets.length === 0) {
          message.error(t('Please Select Adsets'));
          return;
        }

        copyAdToAdsetsModal.loading = true;

        const params = {
          ad_source_id: copyAdToAdsetsModal.adSourceId,
          adset_source_ids: copyAdToAdsetsModal.selectedAdsets.map(adset => adset.adset_id),
        };

        console.log('🚀 Copy ad to adsets params:', params);

        const response = (await copyAdToAdsets(params)) as any;

        if (response.success) {
          message.success(response.message || t('Copy Ad Success'));
          copyAdToAdsetsModal.visible = false;
          copyAdToAdsetsModal.adSourceId = '';
          copyAdToAdsetsModal.selectedAdsets = [];
          reload(); // 重新加载数据
        } else {
          message.error(response.message || t('Copy Ad Failed'));
        }
      } catch (error) {
        console.error('Copy ad to adsets error:', error);
        message.error(t('Copy Ad Failed'));
      } finally {
        copyAdToAdsetsModal.loading = false;
      }
    };

    // 处理复制广告
    const handleCopyAds = async () => {
      try {
        // 验证复制数量
        const invalidCounts = copyAdsModal.selectedAds.filter(ad => {
          const count = copyAdsModal.adCounts[ad.ad_id];
          return !count || count < 1 || count > 50;
        });

        if (invalidCounts.length > 0) {
          message.error(t('Copy Count Invalid'));
          return;
        }

        copyAdsModal.loading = true;

        // 构建API参数
        const params = copyAdsModal.selectedAds.map(ad => ({
          ad_id: ad.ad_id,
          count: copyAdsModal.adCounts[ad.ad_id],
        }));

        // 将模式转换为数字
        const modeMap = {
          'N-1-1': 1,
          '1-N-1': 2,
          '1-1-N': 3,
        };
        const mode = modeMap[copyAdsModal.selectedMode] || 1;

        console.log('🚀 Copy ads params:', params, 'Mode:', mode);

        const response = (await copyAds(params, mode)) as any;

        if (response.success) {
          message.success(response.message || t('Copy Success'));
          copyAdsModal.visible = false;
          copyAdsModal.selectedAds = [];
          copyAdsModal.adCounts = {};
          copyAdsModal.selectedMode = 'N-1-1';
          reload(); // 重新加载数据
        } else {
          message.error(response.message || t('Copy Failed'));
        }
      } catch (error) {
        console.error('Copy ads error:', error);
        message.error(t('Copy Failed'));
      } finally {
        copyAdsModal.loading = false;
      }
    };

    // 处理多语言菜单点击
    const handleMultiLanguageMenuClick = async (info: any) => {
      const key = info.key;
      console.log('多语言管理模式:', key);

      // 获取支持多语言的广告
      const supportedAds = selectedAdState.selectedRows.filter(ad => {
        const { supportsMultiLanguage } = checkAdMultiLanguageSupport(ad);
        return supportsMultiLanguage;
      });

      if (supportedAds.length === 0) {
        message.warning(t('No ads support multi-language'));
        return;
      }

      const adSourceIds = supportedAds.map(ad => ad.ad_id).filter(id => id != null);

      // 添加调试日志
      console.log('支持多语言的广告:', supportedAds);
      console.log('广告ID列表:', adSourceIds);

      // 检查广告ID列表是否为空
      if (adSourceIds.length === 0) {
        message.error(t('No valid ads selected'));
        return;
      }

      if (key === 'manual') {
        // 手动模式：打开多语言管理Modal
        multiLanguageModal.selectedAds = supportedAds;
        multiLanguageModal.visible = true;
        console.log('打开多语言管理Modal，选中的广告数量:', supportedAds.length);
      } else if (key === 'add-one-language') {
        // 随机添加1种语言
        try {
          const response = (await addLanguagesToAds({
            ad_source_ids: adSourceIds,
            language_count: 1,
          })) as any;

          if (response.success) {
            const { success_count, failure_count } = response.data;
            message.success(
              t('Add languages completed: Success {success}, Failed {failed}', {
                success: success_count,
                failed: failure_count,
              }),
            );
            reload(); // 刷新数据
          }
        } catch (error: any) {
          console.error('添加语言失败:', error);
          message.error(t('Failed to add languages'));
        }
      } else if (key === 'add-multiple-languages') {
        // 随机添加多种语言：显示Modal让用户输入数量
        addLanguagesModal.selectedAds = supportedAds;
        addLanguagesModal.visible = true;
      } else if (key === 'enable-auto-add') {
        // 开启自动添加多语言
        try {
          const response = (await setAutoAddLanguages({
            ad_source_ids: adSourceIds,
            auto_add_languages: true,
          })) as any;

          if (response.success) {
            const { success_count, failure_count } = response.data;
            message.success(
              t('Enable auto-add completed: Success {success}, Failed {failed}', {
                success: success_count,
                failed: failure_count,
              }),
            );
            reload(); // 刷新数据
          }
        } catch (error: any) {
          console.error('开启自动添加失败:', error);
          message.error(t('Failed to enable auto-add'));
        }
      } else if (key === 'disable-auto-add') {
        // 关闭自动添加多语言
        try {
          const response = (await setAutoAddLanguages({
            ad_source_ids: adSourceIds,
            auto_add_languages: false,
          })) as any;

          if (response.success) {
            const { success_count, failure_count } = response.data;
            message.success(
              t('Disable auto-add completed: Success {success}, Failed {failed}', {
                success: success_count,
                failed: failure_count,
              }),
            );
            reload(); // 刷新数据
          }
        } catch (error: any) {
          console.error('关闭自动添加失败:', error);
          message.error(t('Failed to disable auto-add'));
        }
      }

      // 输出选中广告的多语言支持信息（保留原有逻辑）
      console.log('支持多语言的广告数量:', supportedAds.length);
      supportedAds.forEach(ad => {
        const multiLangInfo = checkAdMultiLanguageSupport(ad);
        console.log(`广告 ${ad.ad_name} (${ad.ad_id}):`, multiLangInfo);
      });
    };

    // 监听广告选择变化，在控制台输出信息
    const onSelectedAdChange = (selectedRowKeys, selectedRows) => {
      console.log('onSelectedAdChange changed: ', selectedRowKeys);
      selectedAdState.selectedRowKeys = selectedRowKeys;
      selectedAdState.selectedRows = selectedRows;

      // 输出选中广告的多语言支持信息
      if (selectedRows.length > 0) {
        console.log('=== 选中的广告多语言支持信息 ===');
        selectedRows.forEach(ad => {
          const multiLangInfo = checkAdMultiLanguageSupport(ad);
          console.log(`广告: ${ad.ad_name} (${ad.ad_id})`);
          console.log(`  - 是否支持创建/编辑多语言: ${multiLangInfo.supportsMultiLanguage}`);
          if (multiLangInfo.supportsMultiLanguage) {
            console.log(`  - 广告类型: ${multiLangInfo.adType}`);
            console.log(`  - 已配置多语言: ${multiLangInfo.hasAssetFeedSpec ? '是' : '否'}`);
          }
          console.log('---');
        });
      }
    };

    // 显示多语言预览模态框
    const showMultiLanguagePreview = (record: any) => {
      console.log('打开多语言预览模态框, 广告数据:', record);
      multiLanguagePreviewModal.adData = record;
      multiLanguagePreviewModal.visible = true;
    };

    const adsetLanguageModal = reactive({
      visible: false,
      adsetData: null,
    });

    const adsetAudienceModal = reactive({
      visible: false,
      adsetData: null,
    });

    const showAdsetLanguages = record => {
      adsetLanguageModal.adsetData = record;
      adsetLanguageModal.visible = true;
    };

    const showAdsetAudience = record => {
      adsetAudienceModal.adsetData = record;
      adsetAudienceModal.visible = true;
    };

    // 收藏夹相关功能
    const bookmarks = ref([]);
    const saveBookmarkModal = reactive({
      visible: false,
      name: '',
      description: '',
    });

    // 随机添加语言Modal
    const addLanguagesModal = reactive({
      visible: false,
      languageCount: 1,
      selectedAds: [],
      loading: false,
    });

    // 自定义同步交易Modal
    const customSyncTransactionsModal = reactive({
      visible: false,
      loading: false,
      timeRange: null as [Dayjs, Dayjs] | null,
    });

    // 自定义同步Keitaro Modal
    const customSyncKeitaroModal = reactive({
      visible: false,
      loading: false,
      timeRange: null as [Dayjs, Dayjs] | null,
    });

    // 广告账户详情Modal
    const adAccountInfoModal = reactive({
      visible: false,
      loading: false,
      adAccountData: null,
    });

    // 产品集详情Modal
    const productSetModal = reactive({
      visible: false,
      productSetData: null,
    });

    // CBO 2 ABO 模态框
    const cbo2AboModal = reactive({
      visible: false,
      campaignIds: [] as string[],
      budget: 5.0,
      loading: false,
    });

    // ABO 2 CBO 模态框
    const abo2CboModal = reactive({
      visible: false,
      selectedCampaigns: [] as any[],
      budget: 5.0,
      loading: false,
    });

    // 处理CBO 2 ABO转换
    const handleCbo2Abo = async () => {
      if (!cbo2AboModal.budget || cbo2AboModal.budget < 1) {
        message.error(t('Budget must be greater than or equal to 1'));
        return;
      }

      cbo2AboModal.loading = true;
      try {
        const params = {
          campaign_source_ids: cbo2AboModal.campaignIds,
          budget: cbo2AboModal.budget,
        };

        const response = await cbo2Abo(params);
        message.success((response as any)?.message || t('CBO to ABO conversion submitted successfully'));
        cbo2AboModal.visible = false;

        // 重新加载Campaign表格数据
        reloadCampaignTable();
      } catch (error) {
        console.error('CBO 2 ABO error:', error);
        message.error(t('CBO to ABO conversion failed'));
      } finally {
        cbo2AboModal.loading = false;
      }
    };

    // 处理ABO 2 CBO转换
    const handleAbo2Cbo = async () => {
      if (!abo2CboModal.budget || abo2CboModal.budget < 1) {
        message.error(t('Budget must be greater than or equal to 1'));
        return;
      }

      abo2CboModal.loading = true;
      try {
        // 构建批量更新预算的参数
        const items = abo2CboModal.selectedCampaigns.map(campaign => ({
          id: campaign.campaign_id, // 使用campaign的source_id
          object_type: 'campaign' as const,
          budget_type: 'daily_budget' as const,
          budget: abo2CboModal.budget.toString(),
        }));

        const params = { items };
        const response = await batchUpdateObjectBudget(params);

        message.success((response as any)?.message || t('ABO to CBO conversion submitted successfully'));
        abo2CboModal.visible = false;

        // 重新加载Campaign表格数据
        reloadCampaignTable();
      } catch (error) {
        console.error('ABO 2 CBO error:', error);
        message.error(t('ABO to CBO conversion failed'));
      } finally {
        abo2CboModal.loading = false;
      }
    };

    // 显示产品集详情
    const showProductSetModal = (productSetData: any) => {
      console.log('显示产品集详情:', productSetData);
      // 使用深拷贝避免引用共享，防止子组件修改影响父组件数据
      productSetModal.productSetData = JSON.parse(JSON.stringify(productSetData));
      productSetModal.visible = true;
    };

    // 获取搜索书签列表
    const loadBookmarks = async () => {
      try {
        const response = await getSearchBookmarks();
        console.log('书签API响应:', response);

        // 由于响应拦截器直接返回了response.data，所以response就是API返回的数据
        if (response.success) {
          bookmarks.value = response.data || [];
          console.log('书签列表已更新:', bookmarks.value);
        } else {
          console.log('API响应失败:', response.message);
        }
      } catch (error: any) {
        console.error('获取书签失败:', error);
        message.error(t('Failed to load bookmarks'));
      }
    };

    // 显示保存书签Modal
    const showSaveBookmarkModal = () => {
      saveBookmarkModal.visible = true;
      saveBookmarkModal.name = '';
      saveBookmarkModal.description = '';
    };

    // 保存书签
    const handleSaveBookmark = async () => {
      if (!saveBookmarkModal.name.trim()) {
        message.error(t('Please enter bookmark name'));
        return;
      }

      try {
        const searchConditions: any = {
          ...appliedFilters.value,
          'with-campaign': false, // 按需求添加
        };

        // 移除 pageSize 和 pageNo
        if ('pageSize' in searchConditions) {
          delete searchConditions.pageSize;
        }
        if ('pageNo' in searchConditions) {
          delete searchConditions.pageNo;
        }

        const response = await createSearchBookmark({
          name: saveBookmarkModal.name.trim(),
          search_conditions: searchConditions,
          description: saveBookmarkModal.description.trim() || undefined,
        });

        if (response.success) {
          message.success(t('Bookmark saved successfully'));
          saveBookmarkModal.visible = false;
          saveBookmarkModal.name = '';
          saveBookmarkModal.description = '';
          loadBookmarks(); // 重新加载书签列表
        }
      } catch (error: any) {
        console.error('保存书签失败:', error);
        if (error.response?.data?.message) {
          message.error(error.response.data.message);
        } else {
          message.error(t('Failed to save bookmark'));
        }
      }
    };

    // 应用书签的搜索条件
    const applyBookmark = bookmark => {
      console.log('应用书签:', bookmark);
      console.log('书签搜索条件:', bookmark.search_conditions);

      // 完全清空 queryAdAccountParam 对象
      Object.keys(queryAdAccountParam).forEach(key => {
        delete queryAdAccountParam[key];
      });

      // 等待DOM更新后应用书签的搜索条件
      nextTick(() => {
        const conditions = bookmark.search_conditions;

        // 只设置书签中保存的搜索条件
        Object.entries(conditions).forEach(([key, value]) => {
          if (key !== 'pageSize' && key !== 'pageNo') {
            queryAdAccountParam[key] = value;
          }
        });

        // 移除pageNo设置，让useFetchData通过resetPagination参数控制分页重置

        // 完全重置 appliedFilters
        appliedFilters.value = { ...conditions };

        // 同步更新 formItems 中的字段值，让过滤器界面显示应用的条件
        formItems.value.forEach((item: any) => {
          if (Object.prototype.hasOwnProperty.call(conditions, item.field)) {
            item.value = conditions[item.field];
          } else {
            // 如果书签中没有这个字段，清空该字段的值
            item.value = item.multiple ? [] : null;
          }
        });

        console.log('应用书签后的 queryAdAccountParam:', { ...queryAdAccountParam });
        console.log('应用书签后的 appliedFilters:', appliedFilters.value);
        console.log(
          '应用书签后的 formItems 值:',
          formItems.value.map((item: any) => ({ field: item.field, value: item.value })),
        );

        // 触发搜索
        handleSearch(true); // 应用书签，重置分页

        message.success(t('Bookmark applied: {name}', { name: bookmark.name }));
      });
    };

    // 删除书签
    const deleteBookmark = async (bookmarkId: string) => {
      try {
        const response = await deleteSearchBookmark(bookmarkId);
        if (response.success) {
          message.success(t('Bookmark deleted successfully'));
          loadBookmarks(); // 重新加载书签列表
        }
      } catch (error: any) {
        console.error('删除书签失败:', error);
        message.error(t('Failed to delete bookmark'));
      }
    };

    // 删除单个过滤条件
    const removeFilter = (fieldKey: string) => {
      console.log('删除过滤条件:', fieldKey);

      // 从 queryAdAccountParam 中删除该字段
      delete queryAdAccountParam[fieldKey];

      // 从 appliedFilters 中删除该字段
      delete appliedFilters.value[fieldKey];

      // 更新 formItems 中对应字段的值
      const formItem = formItems.value.find((item: any) => item.field === fieldKey);
      if (formItem) {
        (formItem as any).value = formItem.multiple ? [] : null;
      }

      // 触发搜索
      handleSearch(false); // 删除单个过滤条件，保持当前分页

      message.success(t('Filter removed'));
    };

    // 清除所有过滤条件
    const clearAllFilters = () => {
      console.log('清除所有过滤条件');

      // 清空所有搜索参数
      Object.keys(queryAdAccountParam).forEach(key => {
        delete queryAdAccountParam[key];
      });

      // 清空 appliedFilters
      appliedFilters.value = {};

      // 重置所有 formItems 的值
      formItems.value.forEach((item: any) => {
        item.value = item.multiple ? [] : null;
      });

      // 触发搜索
      handleSearch(true); // 清除所有过滤条件，重置分页

      message.success(t('All filters cleared'));
    };

    // 处理添加多种语言
    const handleAddLanguages = async () => {
      if (
        !addLanguagesModal.languageCount ||
        addLanguagesModal.languageCount < 1 ||
        addLanguagesModal.languageCount > 30
      ) {
        message.error(t('Please enter a valid number of languages (1-30)'));
        return;
      }

      addLanguagesModal.loading = true;
      try {
        const adSourceIds = addLanguagesModal.selectedAds
          .map(ad => ad.ad_id)
          .filter(id => id != null);

        // 添加调试日志
        console.log('选中的广告:', addLanguagesModal.selectedAds);
        console.log('广告ID列表:', adSourceIds);

        // 检查广告ID列表是否为空
        if (adSourceIds.length === 0) {
          message.error(t('No valid ads selected'));
          return;
        }

        const response = (await addLanguagesToAds({
          ad_source_ids: adSourceIds,
          language_count: addLanguagesModal.languageCount,
        })) as any;

        if (response.success) {
          const { success_count, failure_count } = response.data;
          message.success(
            t('Add languages completed: Success {success}, Failed {failed}', {
              success: success_count,
              failed: failure_count,
            }),
          );
          addLanguagesModal.visible = false;
          addLanguagesModal.languageCount = 1;
          reload(); // 刷新数据
        }
      } catch (error: any) {
        console.error('添加语言失败:', error);
        message.error(t('Failed to add languages'));
      } finally {
        addLanguagesModal.loading = false;
      }
    };

    // 页面加载时获取书签列表
    onMounted(() => {
      loadBookmarks();
    });

    return {
      t,
      activeTab,
      scroll,

      // Ad account tab 相关
      // state,
      adAccountTableDataState,
      adAccountTabLoading, // 合并的loading状态，优化用户体验
      queryParam,
      handleTableChange,
      handleSearch,
      handleReset,
      columnState,
      dynamicColumns,
      dynamicColumnItems,
      handleColumnAllClick,
      handleColumnChange,
      reset,
      move,
      accountTotals,
      customAdAccountRow,
      adAccCellClick,
      selectedAdAccountState,

      // 选中表格相关
      // selectedAdAccountRowKeys,
      hasAdAccountSelected,
      onSelectedAdAccountChange,

      // Tab 的标题
      adAccountTabTitle,
      onEditTab,
      // 控制是否可关闭
      adAccountClosable,
      campaignClosable,
      adsetClosable,
      adClosable,

      // campaign tab 相关
      campaignTableDataState, //给 stable 用于获取数据,分页使用
      campaignColumnState,
      campaignDynaColumns,
      campaignDynamicColumnItems,
      handleCampaignColumnAllClick,
      handleCampaignColumnChange,
      resetCampaign,
      moveCampaign,
      selectedCampaignState,
      hasCampaignSelected,
      onSelectedCampaignChange,
      campaignTabTitle,
      campaignDisable,
      handleCampaignChange,
      campaignCellClick,
      campaignTotals,
      customCampaignRow,

      // ad totals
      adTotals,

      // adset totals
      adsetTotals,

      // adset tab 相关
      adsetTableDataState,
      adsetColumnState,
      adsetDynaColumns,
      adsetDynamicColumnItems,
      handleAdsetColumnAllClick,
      handleAdsetColumnChange,
      resetAdset,
      moveAdset,
      selectedAdsetState,
      hasAdsetSelected,
      onSelectedAdsetChange,
      adsetTabTitle,
      adsetDisable,
      handleAdsetChange,
      adsetCellClick,
      customAdsetRow,

      // ad tab 相关
      adTableDataState,
      adColumnState,
      adDynaColumns,
      adDynamicColumnItems,
      handleAdColumnAllClick,
      handleAdColumnChange,
      resetAd,
      moveAd,
      selectedAdState,
      hasAdSelected,
      onSelectedAdChange: onSelectedAdChange,
      adTabTitle,
      adDisable,
      handleAdChange,
      adCellClick,
      customAdRow,

      // 获取广告账户数据
      AdAccountDataState,

      handleSyncOne,
      handleActionManuClick,
      handleMenuClick,

      copyCell,
      dayjs,
      // tab click
      onChangeTab,

      // 搜索相关
      rangePresets,
      date_range,
      inputParam,
      queryAdAccountParam,

      // 日期选择器
      onDatePickerPanelChange,
      onDatePickerChange,
      openDatePicker,
      onDatePickerOpenChange,
      onDatePickerOk,

      //权限
      canCreateAds,
      canPreviewAds,

      // 刷新
      reload,

      // 创建广告
      gotoCreateAd,

      // tags
      fbAccountTagOptions,
      fbAdAccountTagOptions,

      // role
      role,

      // user list
      userList,

      // tag Modal
      tagModal,
      showManageTagsModal,

      //budget Modal
      budgetModal,

      // copy Modal
      copyModal,

      // copy ads Modal
      copyAdsModal,
      groupedAds,
      applyGlobalCount,
      handleGlobalCountChange,
      handleCopyAds,

      // copy ad to adsets Modal
      copyAdToAdsetsModal,
      handleCopyAdToAdsets,

      // rename Modal
      renameModal,

      formItems,
      appliedFilters,
      onSearch,

      adAccountColumns,
      campaignColumns,
      adsetColumns,
      adColumns,

      dynamicColumnsTab1,
      dynamicColumnsTab2,
      dynamicColumnsTab3,
      dynamicColumnsTab4,
      newAdAccountSummaryItems,
      newCampaignSummaryItems,
      newAdsetSummaryItems,
      newAdSummaryItems,

      ReloadOutlined,
      TagOutlined,
      PlayCircleOutlined,
      PauseCircleOutlined,
      RocketOutlined,
      GlobalOutlined,
      EditOutlined,
      h,

      showCopywriting,

      infoModalRef,

      updateFbItemStatus,

      // edit bid modal
      editBidStrategyModalRef,
      showEditBidStrategyModal,

      // delete modal
      deleteModal,

      // 多语言管理
      showMultiLanguageManagement,
      handleMultiLanguageMenuClick,
      checkAdMultiLanguageSupport,
      multiLanguageModal,

      // 多语言预览
      isMultiLanguageAd,
      showMultiLanguagePreview,
      multiLanguagePreviewModal,

      adsetLanguageModal,
      adsetAudienceModal,
      showAdsetLanguages,
      showAdsetAudience,

      // Payment modal
      paymentModal,

      // Batch budget modal
      batchBudgetModal,

      // 收藏夹相关
      bookmarks,
      saveBookmarkModal,
      showSaveBookmarkModal,
      handleSaveBookmark,
      applyBookmark,
      deleteBookmark,

      // 随机添加语言相关
      addLanguagesModal,
      handleAddLanguages,

      // 自定义同步交易相关
      customSyncTransactionsModal,
      customSyncKeitaroModal,
      handleCustomSyncKeitaro,
      handleCustomSyncTransactions,
      handleSyncCardTransactions,

      // 广告账户详情相关
      adAccountInfoModal,
      showAdAccountInfoModal,
      getAdAccountBalance,
      viewAdAccount,

      // 产品集详情相关
      productSetModal,
      showProductSetModal,

      // CBO 2 ABO相关
      cbo2AboModal,
      handleCbo2Abo,

      // ABO 2 CBO相关
      abo2CboModal,
      handleAbo2Cbo,

      // 过滤条件管理
      removeFilter,
      clearAllFilters,

      // 排序状态（用于汇总计算的响应式更新）
      campaignSorter,
      adsetSorter,
      adSorter,

      // 前端分页状态（用于汇总计算）
      campaignCurrentPage,
      campaignPageSize,
      adsetCurrentPage,
      adsetPageSize,
      adCurrentPage,
      adPageSize,

      // 响应式分页配置
      campaignPagination,
      adsetPagination,
      adPagination,

      // 添加需要的图标组件
      StarOutlined,
      DeleteOutlined,
    };
  },
  components: {
    DownOutlined,
    ProfileOutlined,
    UpSquareOutlined,
    ShopOutlined,
    TabletOutlined,
    CopyOutlined,
    ArrowsAltOutlined,
    TagModal,
    BudgetModal,
    CopyModal,
    RenameModal,
    DynamicForm,
    ColumnOrgnizer,
    EyeOutlined,
    EditOutlined,
    FileTextTwoTone,
    InfoModal,
    ProductSetModal,
    EditBidStrategyModal,
    DeleteModal,
    MultiLanguageModal,
    MultiLanguagePreviewModal,
    AdsetLanguageModal,
    AdsetAudienceModal,
    PaymentModal,
    BatchBudgetModal,
    TeamOutlined,
    StarOutlined,
    DeleteOutlined,
    RobotOutlined,
    InfoCircleOutlined,
    AppliedFilters,
  },
});
</script>

<style lang="less" scoped>
.my-wrapper {
  ::v-deep(.ant-tabs-nav-list) {
    flex: 1;
  }
}
.my-wrapper {
  ::v-deep(.ant-tabs-tab) {
    flex: 1;
    flex-basis: 25%;
    min-width: 0;
    text-align: left;
    justify-content: space-between;
  }
}

.my-wrapper {
  ::v-deep(.ant-tabs-nav-operations) {
    display: none !important;
  }
}

.my-wrapper {
  ::v-deep(.ant-card-body) {
    padding: 0px;
  }
}

.my-wrapper {
  ::v-deep(.ant-tabs-nav) {
    margin: 0 0 8px 0;
  }
}

// 批量复制广告Modal样式
.copy-ads-modal {
  ::v-deep(.ant-modal-content) {
    border-radius: 12px;
    overflow: hidden;
    box-shadow: 0 10px 40px rgba(0, 0, 0, 0.15);
  }

  ::v-deep(.ant-modal-header) {
    background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
    border-bottom: none;
    padding: 20px 24px;

    .ant-modal-title {
      color: white;
      font-size: 18px;
      font-weight: 600;
    }
  }

  ::v-deep(.ant-modal-close) {
    .ant-modal-close-x {
      color: white;
      font-size: 18px;

      &:hover {
        color: rgba(255, 255, 255, 0.8);
      }
    }
  }

  ::v-deep(.ant-modal-footer) {
    background: #fafafa;
    border-top: 1px solid #f0f0f0;
    padding: 16px 24px;

    .ant-btn {
      height: 40px;
      border-radius: 8px;
      font-weight: 500;

      &.ant-btn-primary {
        background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
        border: none;
        box-shadow: 0 4px 12px rgba(102, 126, 234, 0.4);

        &:hover {
          transform: translateY(-1px);
          box-shadow: 0 6px 16px rgba(102, 126, 234, 0.5);
        }
      }
    }
  }

  .copy-ads-content {
    .copy-ads-header {
      padding: 20px 24px 16px;
      background: #fafbfc;
      border-bottom: 1px solid #e8eaed;

      .copy-settings-section {
        display: flex;
        flex-direction: column;
        gap: 16px;

        .copy-mode-selection {
          display: flex;
          align-items: center;
          gap: 16px;

          .label {
            font-weight: 600;
            color: #262626;
            font-size: 14px;
            white-space: nowrap;
          }

          .mode-radio-group {
            display: flex;
            flex-wrap: wrap;
            gap: 12px;

            ::v-deep(.ant-tooltip) {
              .ant-tooltip-inner {
                background: #001529;
                color: white;
                font-size: 12px;
                border-radius: 6px;
                padding: 8px 12px;
                max-width: 280px;
                line-height: 1.4;
                font-weight: 500;
              }

              .ant-tooltip-arrow-content {
                background-color: #001529;
              }
            }

            .mode-radio {
              display: flex;
              align-items: center;
              gap: 6px;
              padding: 8px 12px;
              border-radius: 6px;
              border: 1px solid #d9d9d9;
              transition: all 0.3s ease;
              background: white;

              &:hover:not(.ant-radio-wrapper-disabled) {
                border-color: #40a9ff;
                background: #f6ffed;
              }

              ::v-deep(.ant-radio-checked .ant-radio-inner) {
                border-color: #1890ff;
                background-color: #1890ff;
              }

              ::v-deep(.ant-radio-wrapper-disabled) {
                color: rgba(0, 0, 0, 0.25);
                cursor: not-allowed;
                background: #fafafa;
              }

              .coming-soon-tag {
                margin-left: 6px;
                font-size: 10px;
                font-weight: 500;
              }
            }
          }
        }

        .global-count-section {
          display: flex;
          align-items: center;
          gap: 12px;
          padding: 12px 16px;
          background: white;
          border-radius: 6px;
          border: 1px solid #e8eaed;

          .label {
            font-weight: 600;
            color: #262626;
            font-size: 14px;
          }

          .count-input {
            width: 120px;
            border-radius: 6px;

            ::v-deep(.ant-input-number-input) {
              font-weight: 500;
            }
          }

          .apply-btn {
            border-radius: 6px;
            font-weight: 500;
            height: 32px;
          }
        }
      }
    }

    .ads-list-container {
      max-height: 450px;
      overflow-y: auto;
      padding: 0 24px 24px;

      &::-webkit-scrollbar {
        width: 6px;
      }

      &::-webkit-scrollbar-track {
        background: #f5f5f5;
        border-radius: 3px;
      }

      &::-webkit-scrollbar-thumb {
        background: #d9d9d9;
        border-radius: 3px;

        &:hover {
          background: #bfbfbf;
        }
      }

      .account-group {
        margin-bottom: 24px;

        &:last-child {
          margin-bottom: 0;
        }

        .account-header {
          display: flex;
          align-items: center;
          justify-content: space-between;
          padding: 16px 20px;
          background: linear-gradient(135deg, #f8f9fa 0%, #e9ecef 100%);
          border-radius: 8px 8px 0 0;
          border-bottom: 2px solid #667eea;
          margin-bottom: 0;

          .account-title {
            margin: 0;
            font-size: 16px;
            font-weight: 600;
            color: #262626;
          }

          .ads-count {
            background: white;
            color: #667eea;
            padding: 4px 12px;
            border-radius: 16px;
            font-size: 12px;
            font-weight: 600;
            border: 1px solid #667eea;
          }
        }

        .ads-list {
          background: white;
          border-radius: 0 0 8px 8px;
          border: 1px solid #e8eaed;
          border-top: none;

          .ad-item {
            display: flex;
            align-items: center;
            justify-content: space-between;
            padding: 16px 20px;
            border-bottom: 1px solid #f5f5f5;
            transition: all 0.2s ease;

            &:hover {
              background: #fafbfc;
            }

            &.last {
              border-bottom: none;
            }

            .ad-info {
              flex: 1;

              .ad-id {
                font-size: 15px;
                font-weight: 600;
                color: #262626;
                margin-bottom: 8px;
                font-family: 'Monaco', 'Menlo', 'Ubuntu Mono', monospace;
              }

              .ad-status {
                display: flex;
                align-items: center;
                gap: 8px;

                .status-label {
                  font-size: 13px;
                  color: #8c8c8c;
                  font-weight: 500;
                }

                .status-tag {
                  border-radius: 12px;
                  font-size: 11px;
                  font-weight: 600;
                  border: none;
                }
              }
            }

            .copy-count-section {
              display: flex;
              align-items: center;
              gap: 12px;

              .count-label {
                font-size: 14px;
                font-weight: 600;
                color: #595959;
              }

              .ad-count-input {
                width: 90px;
                border-radius: 6px;

                ::v-deep(.ant-input-number-input) {
                  font-weight: 600;
                  text-align: center;
                }

                ::v-deep(.ant-input-number-handler-wrap) {
                  border-radius: 0 6px 6px 0;
                }
              }
            }
          }
        }
      }
    }
  }
}

// 随机添加语言Modal样式
.add-languages-modal {
  .language-count-section {
    background: #fafafa;
    border: 1px solid #f0f0f0;
    border-radius: 6px;
    padding: 16px;

    .ant-form-item {
      margin-bottom: 0;
    }

    .ant-input-number {
      border-radius: 6px;
    }
  }

  .selected-ads-section {
    .ads-info {
      .ads-count {
        .ant-tag {
          padding: 4px 12px;
          border-radius: 16px;
          font-weight: 500;
        }
      }
    }
  }

  .ant-alert {
    border-radius: 6px;

    .ant-alert-message {
      font-weight: 600;
    }
  }
}

.my-wrapper {
  ::v-deep(.ant-row) {
    padding: 6px;
  }
}

.date-picker {
  padding: 8px;
  display: flex;
  justify-content: flex-end;
  margin-right: 8px;
}

.date-picker .cancel {
  margin-right: 8px;
}

.vertical {
  overflow-y: scroll;
  max-height: 300px;
}

/* 工具栏布局样式 - 左右分布，自然换行 */
.action-toolbar {
  display: flex;
  align-items: flex-start;
  justify-content: space-between;
  width: 100%;
  padding: 12px 16px;
  gap: 16px;
  flex-wrap: wrap;
}

/* 左侧容器 */
.toolbar-left {
  flex: 0 1 auto;
  min-width: 0;
}

/* 右侧容器 */
.toolbar-right {
  display: flex;
  align-items: center;
  gap: 8px;
  flex-wrap: wrap;
  flex: 0 1 auto;
  justify-content: flex-end;
  min-width: 0;
}

/* 响应式换行：当空间不足时，右侧内容换到下一行 */
@media (max-width: 1000px) {
  .action-toolbar {
    flex-direction: column;
    align-items: stretch;
  }

  .toolbar-right {
    justify-content: flex-start;
    margin-top: 8px;
  }
}

/* 工具栏项目容器 */
.toolbar-item {
  display: flex;
  align-items: center;
  justify-content: center;
  min-width: 32px;
  min-height: 32px;
  flex-shrink: 0;
}

.my-wrapper ::v-deep(.ant-btn) {
  border-radius: 8px;
  font-weight: 400;
  transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1);
  box-shadow: 0 1px 3px rgba(0, 0, 0, 0.08);
  border-width: 1px;
  display: flex;
  align-items: center;
  justify-content: center;
  margin: 0 !important;
}

.my-wrapper ::v-deep(.ant-btn-circle) {
  width: 32px !important;
  height: 32px !important;
  min-width: 32px !important;
  padding: 0 !important;
}

.action-toolbar ::v-deep(.ant-dropdown) {
  margin: 0 !important;
}

.action-toolbar ::v-deep(.ant-dropdown > .ant-btn) {
  margin: 0 !important;
}

/* 主要操作按钮 - 操作 */
.my-wrapper ::v-deep(.ant-btn-primary) {
  background: linear-gradient(135deg, #1890ff 0%, #096dd9 100%);
  border: none;
  box-shadow: 0 2px 8px rgba(24, 144, 255, 0.3);
}

.my-wrapper ::v-deep(.ant-btn-primary:hover) {
  background: linear-gradient(135deg, #40a9ff 0%, #1890ff 100%);
  transform: translateY(-1px);
  box-shadow: 0 4px 12px rgba(24, 144, 255, 0.4);
}

/* 收藏夹按钮 - 特殊功能按钮 */
.my-wrapper ::v-deep(.bookmark-btn) {
  background: linear-gradient(135deg, #fff7e6 0%, #fff2e6 100%);
  border-color: #ffd591;
  color: #d46b08;
}

.my-wrapper ::v-deep(.bookmark-btn:hover) {
  background: linear-gradient(135deg, #ffe7ba 0%, #ffd591 100%);
  border-color: #d46b08;
  color: #ad4e00;
  transform: translateY(-1px);
  box-shadow: 0 4px 12px rgba(255, 178, 56, 0.25);
}

.my-wrapper ::v-deep(.bookmark-btn .anticon-star) {
  color: #fa8c16;
}

/* 工具类按钮 - 设置和过滤 */
.my-wrapper ::v-deep(.ant-btn-default) {
  background: #fafafa;
  border-color: #e8e8e8;
  color: #595959;
}

.my-wrapper ::v-deep(.ant-btn-default):hover {
  background: #f0f0f0;
  border-color: #d9d9d9;
  color: #262626;
  transform: translateY(-1px);
  box-shadow: 0 4px 8px rgba(0, 0, 0, 0.12);
}

/* 7. 特殊组件的间距控制 */
.action-toolbar ::v-deep(.column-orgnizer),
.action-toolbar ::v-deep(.dynamic-form),
.action-toolbar ::v-deep(.applied-filters) {
  margin: 0 !important;
}

/* 8. 最终确保：强制所有直接子元素遵循统一间距 */
.toolbar-right > .toolbar-item,
.toolbar-right > .action-buttons-group,
.toolbar-right > .function-buttons-group {
  margin-right: 8px !important;
  margin-left: 0 !important;
  margin-top: 0 !important;
  margin-bottom: 0 !important;
}

.toolbar-right > *:last-child {
  margin-right: 0 !important;
}

/* 6. 确保内部组件对齐和行为 */
.toolbar-item > * {
  margin: 0 !important;
  box-sizing: border-box;
}

.toolbar-item .ant-tooltip,
.toolbar-item .ant-dropdown {
  display: flex;
  align-items: center;
  justify-content: center;
  margin: 0 !important;
}

.toolbar-item .ant-btn {
  display: flex !important;
  align-items: center !important;
  justify-content: center !important;
}

/* 保持工具栏始终为单行紧凑布局 */

/* 书签Modal样式优化 */
::v-deep(.ant-modal-header) {
  padding: 20px 24px 16px 24px;
  border-bottom: 1px solid #f0f0f0;
}

::v-deep(.ant-modal-title) {
  font-size: 16px;
  font-weight: 600;
  color: #262626;
}

::v-deep(.ant-modal-footer) {
  padding: 16px 24px 20px 24px;
  border-top: 1px solid #f0f0f0;
}

::v-deep(.ant-form-item-label) {
  font-weight: 500;
  color: #262626;
}

::v-deep(.ant-form-item-required::before) {
  color: #ff4d4f;
}

::v-deep(.ant-input-affix-wrapper),
::v-deep(.ant-input) {
  transition: all 0.3s ease;
}

::v-deep(.ant-input-affix-wrapper:hover),
::v-deep(.ant-input:hover) {
  border-color: #40a9ff;
}

::v-deep(.ant-input-affix-wrapper:focus),
::v-deep(.ant-input:focus) {
  border-color: #1890ff;
  box-shadow: 0 0 0 2px rgba(24, 144, 255, 0.2);
}

::v-deep(.ant-btn-primary) {
  background: #1890ff;
  border-color: #1890ff;
  border-radius: 6px;
  transition: all 0.3s ease;
}

::v-deep(.ant-btn-primary:hover) {
  background: #40a9ff;
  border-color: #40a9ff;
}

::v-deep(.ant-btn) {
  border-radius: 6px;
  transition: all 0.3s ease;
}

/* CBO 2 ABO 模态框样式 */
.cbo-2-abo-modal-content {
  .form-help-text {
    color: #8c8c8c;
    font-size: 12px;
    margin-top: 4px;
  }
}

/* ABO 2 CBO 模态框样式 */
.abo-2-cbo-modal-content {
  .conversion-warning {
    margin-bottom: 16px;
  }

  .form-help-text {
    color: #8c8c8c;
    font-size: 12px;
    margin-top: 4px;
  }
}

/* 收藏夹下拉菜单样式 - 参考操作菜单的简洁风格 */
.bookmark-dropdown-menu {
  max-height: 600px;
  overflow-y: auto;
  min-width: 200px;

  .bookmark-menu-item {
    display: flex;
    align-items: center;
    justify-content: space-between;
    width: 100%;
    padding: 0;

    .bookmark-name {
      flex: 1;
      overflow: hidden;
      text-overflow: ellipsis;
      white-space: nowrap;
      color: #262626;
      font-size: 14px;
    }

    .bookmark-delete-icon {
      color: #ff4d4f;
      margin-left: 8px;
      padding: 2px;
      border-radius: 4px;
      transition: all 0.2s ease;
      opacity: 0.6;

      &:hover {
        opacity: 1;
        background-color: #fff2f0;
        transform: scale(1.1);
      }
    }
  }

  .bookmark-empty-text {
    color: #8c8c8c;
    font-style: italic;
  }

  /* 滚动条样式 */
  &::-webkit-scrollbar {
    width: 6px;
  }

  &::-webkit-scrollbar-track {
    background: #f1f1f1;
    border-radius: 3px;
  }

  &::-webkit-scrollbar-thumb {
    background: #c1c1c1;
    border-radius: 3px;

    &:hover {
      background: #a8a8a8;
    }
  }
}
</style>
