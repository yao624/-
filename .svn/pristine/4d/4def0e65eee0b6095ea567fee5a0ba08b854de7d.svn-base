<template>
  <div class="step-creative-group">
    <div class="section-title-row">
      <h3 class="section-title">{{ t('创意组') }}</h3>
      <div class="section-title-actions">
        <span class="cg-count">{{ local.groups.length }}/{{ MAX_GROUPS }}</span>
        <a-button type="link" html-type="button" :disabled="local.groups.length >= MAX_GROUPS" @click="addCreativeGroup">
          + {{ t('新增') }}
        </a-button>
        <a-button type="default" html-type="button" @click="openBulkModal">{{ t('批量设置') }}</a-button>
      </div>
    </div>
    <p class="section-hint">
      {{
        t(
          '对应 Meta Ad Creative：object_story_spec / link_data（正文 message、标题 name、描述 description、链接 link、行动号召 call_to_action）；动态创意与灵活版位对应 asset_feed_spec / degrees_of_freedom 等能力。',
        )
      }}
      <a
        class="doc-link"
        href="https://developers.facebook.com/docs/marketing-api/reference/ad-creative"
        target="_blank"
        rel="noopener noreferrer"
        >{{ t('Ad Creative 参考') }}</a
      >
    </p>

    <a-tabs
      v-model:activeKey="local.activeGroupId"
      type="editable-card"
      class="creative-group-tabs"
      :hide-add="local.groups.length >= MAX_GROUPS"
      @edit="onTabEdit"
    >
      <a-tab-pane v-for="g in local.groups" :key="g.id" :closable="local.groups.length > 1">
        <template #tab>
          <span v-if="editingTabId !== g.id" class="tab-title" @dblclick.stop="startRename(g.id)">{{ g.name }}</span>
          <a-input
            v-else
            v-model:value="g.name"
            size="small"
            class="tab-title-input"
            :maxlength="40"
            @blur="finishRename"
            @press-enter="finishRename"
          />
        </template>
      </a-tab-pane>
    </a-tabs>

    <div v-if="local.groups.length" class="tab-panel-body">
      <a-alert type="info" show-icon class="tip">
        <template #message>
          {{
            t(
              '视频、图片、轮播、单帖、灵活格式等素材总额度以 Meta 账户与产品限制为准；下方为当前创意组配置。已选素材约 {n}/{max}（全部创意组合计估算）。',
              { n: String(totalAssetEstimate), max: String(MAX_ASSETS_HINT) },
            )
          }}
        </template>
      </a-alert>

      <a-form layout="vertical">
        <a-form-item :label="t('绑定对象') + ' *'">
          <div class="bind-object-row">
            <span>{{ t('地区') }}（{{ t('全部') }}）</span>
            <a-button type="link" size="small" disabled>{{ t('编辑') }}</a-button>
          </div>
        </a-form-item>

        <a-form-item>
          <a-checkbox v-model:checked="local.groups[activeIdx].useExisting" @change="onUseExistingChange">{{
            t('选择已有创意组')
          }}</a-checkbox>
        </a-form-item>

        <a-form-item :label="t('绑定规则') + ' *'">
          <a-radio-group v-model:value="local.groups[activeIdx].bindingRule" button-style="solid">
            <a-radio-button value="by_account">{{ t('按账户绑定') }}</a-radio-button>
            <a-radio-button value="by_region_group">{{ t('按地区组绑定') }}</a-radio-button>
          </a-radio-group>
          <div class="field-hint">
            {{
              t(
                '按账户：本创意组素材与广告账户范围关联；按地区组：与定向包（地区组）对应，便于多地区拆分。投放任务可据此解析 creative_binding。',
              )
            }}
          </div>
          <template v-if="local.groups[activeIdx].bindingRule === 'by_account'">
            <div class="binding-sub-block">
              <a-radio-group v-model:value="local.groups[activeIdx].bindingAdAccountMode" class="binding-account-mode">
                <a-radio value="all">{{ t('全部账户') }}</a-radio>
                <a-radio value="selected">{{ t('指定账户') }}</a-radio>
              </a-radio-group>
              <a-select
                v-if="local.groups[activeIdx].bindingAdAccountMode === 'selected'"
                v-model:value="local.groups[activeIdx].bindingAdAccountIds"
                mode="multiple"
                :placeholder="t('请选择广告账户')"
                style="width: 100%; max-width: 560px; margin-top: 8px"
                show-search
                :filter-option="filterAdAccountOption"
                :options="adAccountOptions"
                :loading="adAccountsLoading"
                @dropdown-visible-change="onAdAccountDropdownVisible"
              />
            </div>
          </template>
          <template v-else>
            <a-select
              v-model:value="local.groups[activeIdx].bindingRegionGroupIds"
              mode="multiple"
              :placeholder="t('请选择定向包（地区组）')"
              style="width: 100%; max-width: 560px; margin-top: 8px"
              :options="regionGroupOptions"
            />
            <div v-if="!regionGroupOptions.length" class="field-hint">{{ t('请先在「定向包」步骤配置定向包。') }}</div>
          </template>
        </a-form-item>

        <a-form-item :label="t('动态素材')">
          <a-switch v-model:checked="local.groups[activeIdx].dynamicCreative" />
          <span class="field-hint inline">{{ t('对应 Meta 动态创意 / 多素材优选（asset_feed_spec 等）。') }}</span>
        </a-form-item>

        <a-form-item :label="t('创意类型') + ' *'">
          <a-radio-group v-model:value="local.groups[activeIdx].creativeType" button-style="solid">
            <a-radio-button value="create">{{ t('创建广告') }}</a-radio-button>
            <a-radio-button value="post">{{ t('使用已有帖子') }}</a-radio-button>
          </a-radio-group>
        </a-form-item>

        <a-form-item :label="t('格式')">
          <a-radio-group v-model:value="local.groups[activeIdx].format" button-style="solid">
            <a-radio-button value="flexible">{{ t('灵活') }}</a-radio-button>
            <a-radio-button value="single">{{ t('单图片或视频') }}</a-radio-button>
            <a-radio-button value="carousel">{{ t('轮播') }}</a-radio-button>
          </a-radio-group>
        </a-form-item>

        <a-form-item>
          <template #label>
            <span class="setting-mode-label">
              {{ t('设置方式') }}
              <a-tooltip :title="settingModeTooltip">
                <span class="setting-mode-help" role="button" tabindex="0">?</span>
              </a-tooltip>
            </span>
          </template>
          <a-radio-group v-model:value="local.groups[activeIdx].settingMode" button-style="solid">
            <a-radio-button value="by_group">{{ t('按创意组') }}</a-radio-button>
            <a-radio-button value="by_material">{{ t('按素材') }}</a-radio-button>
          </a-radio-group>
          <div v-if="local.groups[activeIdx].settingMode === 'by_material'" class="field-hint">
            {{
              t(
                '按素材：每条对应 Meta asset_feed_spec 中的一项（images/videos）及可选 bodies、titles、link_urls；当前投放按「每行一条广告」拆分。详见 Ad Creative 文档。',
              )
            }}
          </div>
        </a-form-item>

        <a-form-item :label="t('深度链接')">
          <a-input v-model:value="local.groups[activeIdx].deepLink" :placeholder="t('请输入（app_links / applink 等）')" />
        </a-form-item>

        <a-form-item v-if="local.groups[activeIdx].creativeType === 'create'" :label="t('网址') + ' *'">
          <a-input
            ref="linkUrlInputRef"
            v-model:value="local.groups[activeIdx].linkUrl"
            :placeholder="t('请输入网址')"
            style="max-width: 720px"
          />
          <div class="link-url-toolbar">
            <a type="link" class="preview-link" @click.prevent="previewActiveLinkUrl">{{ t('预览网址') }}</a>
          </div>
          <div class="url-param-tags">
            <span class="url-param-label">{{ t('XMP 通配符') }}</span>
            <a-button
              v-for="item in xmpUrlTokens"
              :key="item"
              type="dashed"
              size="small"
              html-type="button"
              @click="appendLinkUrlToken(item)"
              >{{ item }}</a-button
            >
          </div>
          <div class="url-param-tags">
            <span class="url-param-label">{{ t('Meta 动态网址参数') }}</span>
            <a-button
              v-for="item in metaDynamicUrlTokensShort"
              :key="item"
              type="dashed"
              size="small"
              html-type="button"
              @click="appendLinkUrlToken(item)"
              >{{ item }}</a-button
            >
            <a-button type="link" size="small" html-type="button" @click="linkUrlMetaMoreOpen = !linkUrlMetaMoreOpen">
              {{ linkUrlMetaMoreOpen ? t('收起') : t('更多') }}
            </a-button>
          </div>
          <div v-show="linkUrlMetaMoreOpen" class="url-param-tags url-param-tags-more">
            <a-button
              v-for="item in metaDynamicUrlTokensMore"
              :key="item"
              type="dashed"
              size="small"
              html-type="button"
              @click="appendLinkUrlToken(item)"
              >{{ item }}</a-button
            >
          </div>
          <div class="field-hint">{{ t('与投放内容中的推广链接可不同；留空则创建时由后端按投放内容兜底。') }}</div>
        </a-form-item>

        <a-form-item v-if="local.groups[activeIdx].creativeType === 'create'" :label="t('显示链接')">
          <a-input
            v-model:value="local.groups[activeIdx].displayLink"
            :placeholder="t('link_destination_display_url，可选')"
          />
        </a-form-item>

        <a-form-item
          v-if="local.groups[activeIdx].creativeType === 'create' && local.groups[activeIdx].settingMode === 'by_group'"
          :label="t('视频') + ' *'"
        >
          <a-space direction="vertical" style="width: 100%">
            <a-button html-type="button" @click="onPickMaterials('video')">{{ t('添加素材') }}</a-button>
            <a-select
              v-model:value="local.groups[activeIdx].videoOptimization"
              style="width: 100%; max-width: 320px"
              :options="optimizationOptions"
            />
            <div v-if="local.groups[activeIdx].videoMaterialIds?.length" class="id-tags">
              <a-tag
                v-for="vid in local.groups[activeIdx].videoMaterialIds"
                :key="vid"
                closable
                @close="removeMaterialId('video', vid)"
              >
                {{ vid }}
              </a-tag>
            </div>
          </a-space>
        </a-form-item>

        <a-form-item
          v-if="local.groups[activeIdx].creativeType === 'create' && local.groups[activeIdx].settingMode === 'by_group'"
          :label="t('图片') + ' *'"
        >
          <a-space direction="vertical" style="width: 100%">
            <a-button html-type="button" @click="onPickMaterials('image')">{{ t('添加素材') }}</a-button>
            <a-select
              v-model:value="local.groups[activeIdx].imageOptimization"
              style="width: 100%; max-width: 320px"
              :options="optimizationOptions"
            />
            <div v-if="local.groups[activeIdx].imageMaterialIds?.length" class="id-tags">
              <a-tag
                v-for="iid in local.groups[activeIdx].imageMaterialIds"
                :key="iid"
                closable
                @close="removeMaterialId('image', iid)"
              >
                {{ iid }}
              </a-tag>
            </div>
          </a-space>
        </a-form-item>

        <template v-if="local.groups[activeIdx].creativeType === 'create' && local.groups[activeIdx].settingMode === 'by_material'">
          <a-form-item :label="t('按素材配置')">
            <p class="field-hint material-by-asset-intro">
              {{
                t(
                  '从素材库选择或粘贴素材 ID 后自动拉取缩略图与文件名；未单独填写的文案/链接仍使用下方默认值。',
                )
              }}
            </p>

            <div class="asset-type-block">
              <div class="asset-type-head">
                <span class="asset-type-title asset-type-title--video">{{ t('视频') }}</span>
                <a-button type="primary" size="small" html-type="button" @click="addMaterialSlot('video', true)">{{
                  t('添加素材')
                }}</a-button>
              </div>
              <div v-if="!slotsOfKind(local.groups[activeIdx], 'video').length" class="asset-empty">{{ t('暂无视频素材') }}</div>
              <div v-else class="asset-cards">
                <div
                  v-for="slot in slotsOfKind(local.groups[activeIdx], 'video')"
                  :key="slot.slotId"
                  class="material-asset-card"
                >
                  <div class="material-asset-thumb">
                    <a-spin v-if="previewEntry(slot.materialId)?.status === 'loading'" />
                    <template v-else-if="slotPreviewOk(slot)?.type === 'video' && slotPreviewOk(slot)?.url">
                      <video
                        class="thumb-media"
                        :src="slotPreviewOk(slot)!.url"
                        muted
                        playsinline
                        preload="metadata"
                      />
                    </template>
                    <template v-else-if="slotPreviewOk(slot)?.type === 'image' && slotPreviewOk(slot)?.url">
                      <img class="thumb-media" :src="slotPreviewOk(slot)!.url" alt="" />
                    </template>
                    <div v-else-if="previewEntry(slot.materialId)?.status === 'err'" class="thumb-placeholder">{{ t('加载失败') }}</div>
                    <div v-else class="thumb-placeholder">{{ t('暂无预览') }}</div>
                  </div>
                  <div class="material-asset-body">
                    <div class="material-file-name" :title="displayFileLabel(slot)">{{ displayFileLabel(slot) }}</div>
                    <a-input
                      v-model:value="slot.materialId"
                      :placeholder="t('素材 ID（ULID）')"
                      allow-clear
                      @blur="onMaterialIdCommit(slot)"
                    />
                    <div class="material-asset-actions">
                      <a-button size="small" html-type="button" @click="openMaterialPicker(slot.slotId, 'video')">{{
                        t('从素材库选择')
                      }}</a-button>
                      <a-button size="small" html-type="button" @click="refreshMaterialPreview(slot)">{{ t('刷新预览') }}</a-button>
                      <a-button danger type="link" size="small" html-type="button" @click="removeMaterialSlotById(slot.slotId)">{{
                        t('删除')
                      }}</a-button>
                    </div>
                    <div class="material-asset-fields">
                      <div class="field-label">{{ t('延迟深度链接') }}</div>
                      <a-input v-model:value="slot.deferredDeepLink" :placeholder="t('请输入延迟深度链接')" />
                      <div class="field-label">{{ t('自定义商品页面') }}</div>
                      <a-input v-model:value="slot.customProductPageId" :placeholder="t('请输入自定义商品页面 ID')" />
                    </div>
                    <a-collapse ghost class="material-copy-collapse">
                      <a-collapse-panel key="copy" :header="t('正文与链接')">
                        <a-textarea v-model:value="slot.body" :rows="2" :placeholder="t('正文') + '（message）'" />
                        <a-input v-model:value="slot.headline" class="mt8" :placeholder="t('标题') + '（name）'" />
                        <a-input v-model:value="slot.description" class="mt8" :placeholder="t('描述') + '（description）'" />
                        <a-input v-model:value="slot.link" class="mt8" :placeholder="t('落地页链接') + '（可选）'" />
                        <a-select v-model:value="slot.cta" class="mt8" style="width: 100%; max-width: 280px" :options="ctaOptions" />
                      </a-collapse-panel>
                    </a-collapse>
                  </div>
                </div>
              </div>
            </div>

            <div class="asset-type-block">
              <div class="asset-type-head">
                <span class="asset-type-title asset-type-title--image">{{ t('图片') }}</span>
                <a-button type="primary" size="small" html-type="button" @click="addMaterialSlot('image', true)">{{
                  t('添加素材')
                }}</a-button>
              </div>
              <div v-if="!slotsOfKind(local.groups[activeIdx], 'image').length" class="asset-empty">{{ t('暂无图片素材') }}</div>
              <div v-else class="asset-cards">
                <div
                  v-for="slot in slotsOfKind(local.groups[activeIdx], 'image')"
                  :key="slot.slotId"
                  class="material-asset-card"
                >
                  <div class="material-asset-thumb">
                    <a-spin v-if="previewEntry(slot.materialId)?.status === 'loading'" />
                    <template v-else-if="slotPreviewOk(slot)?.type === 'video' && slotPreviewOk(slot)?.url">
                      <video
                        class="thumb-media"
                        :src="slotPreviewOk(slot)!.url"
                        muted
                        playsinline
                        preload="metadata"
                      />
                    </template>
                    <template v-else-if="slotPreviewOk(slot)?.url">
                      <img class="thumb-media" :src="slotPreviewOk(slot)!.url" alt="" />
                    </template>
                    <div v-else-if="previewEntry(slot.materialId)?.status === 'err'" class="thumb-placeholder">{{ t('加载失败') }}</div>
                    <div v-else class="thumb-placeholder">{{ t('暂无预览') }}</div>
                  </div>
                  <div class="material-asset-body">
                    <div class="material-file-name" :title="displayFileLabel(slot)">{{ displayFileLabel(slot) }}</div>
                    <a-input
                      v-model:value="slot.materialId"
                      :placeholder="t('素材 ID（ULID）')"
                      allow-clear
                      @blur="onMaterialIdCommit(slot)"
                    />
                    <div class="material-asset-actions">
                      <a-button size="small" html-type="button" @click="openMaterialPicker(slot.slotId, 'image')">{{
                        t('从素材库选择')
                      }}</a-button>
                      <a-button size="small" html-type="button" @click="refreshMaterialPreview(slot)">{{ t('刷新预览') }}</a-button>
                      <a-button danger type="link" size="small" html-type="button" @click="removeMaterialSlotById(slot.slotId)">{{
                        t('删除')
                      }}</a-button>
                    </div>
                    <div class="material-asset-fields">
                      <div class="field-label">{{ t('延迟深度链接') }}</div>
                      <a-input v-model:value="slot.deferredDeepLink" :placeholder="t('请输入延迟深度链接')" />
                      <div class="field-label">{{ t('自定义商品页面') }}</div>
                      <a-input v-model:value="slot.customProductPageId" :placeholder="t('请输入自定义商品页面 ID')" />
                    </div>
                    <a-collapse ghost class="material-copy-collapse">
                      <a-collapse-panel key="copy" :header="t('正文与链接')">
                        <a-textarea v-model:value="slot.body" :rows="2" :placeholder="t('正文') + '（message）'" />
                        <a-input v-model:value="slot.headline" class="mt8" :placeholder="t('标题') + '（name）'" />
                        <a-input v-model:value="slot.description" class="mt8" :placeholder="t('描述') + '（description）'" />
                        <a-input v-model:value="slot.link" class="mt8" :placeholder="t('落地页链接') + '（可选）'" />
                        <a-select v-model:value="slot.cta" class="mt8" style="width: 100%; max-width: 280px" :options="ctaOptions" />
                      </a-collapse-panel>
                    </a-collapse>
                  </div>
                </div>
              </div>
            </div>

            <div class="asset-type-block">
              <div class="asset-type-head">
                <span class="asset-type-title asset-type-title--carousel">{{ t('轮播') }}</span>
                <a-button type="primary" size="small" html-type="button" @click="addMaterialSlot('carousel', true)">{{
                  t('添加素材')
                }}</a-button>
              </div>
              <div v-if="!slotsOfKind(local.groups[activeIdx], 'carousel').length" class="asset-empty">{{ t('暂无轮播素材') }}</div>
              <div v-else class="asset-cards">
                <div
                  v-for="slot in slotsOfKind(local.groups[activeIdx], 'carousel')"
                  :key="slot.slotId"
                  class="material-asset-card"
                >
                  <div class="material-asset-thumb">
                    <a-spin v-if="previewEntry(slot.materialId)?.status === 'loading'" />
                    <template v-else-if="slotPreviewOk(slot)?.type === 'video' && slotPreviewOk(slot)?.url">
                      <video
                        class="thumb-media"
                        :src="slotPreviewOk(slot)!.url"
                        muted
                        playsinline
                        preload="metadata"
                      />
                    </template>
                    <template v-else-if="slotPreviewOk(slot)?.url">
                      <img class="thumb-media" :src="slotPreviewOk(slot)!.url" alt="" />
                    </template>
                    <div v-else-if="previewEntry(slot.materialId)?.status === 'err'" class="thumb-placeholder">{{ t('加载失败') }}</div>
                    <div v-else class="thumb-placeholder">{{ t('暂无预览') }}</div>
                  </div>
                  <div class="material-asset-body">
                    <div class="material-file-name" :title="displayFileLabel(slot)">{{ displayFileLabel(slot) }}</div>
                    <a-input
                      v-model:value="slot.materialId"
                      :placeholder="t('素材 ID（ULID）')"
                      allow-clear
                      @blur="onMaterialIdCommit(slot)"
                    />
                    <div class="material-asset-actions">
                      <a-button size="small" html-type="button" @click="openMaterialPicker(slot.slotId, 'carousel')">{{
                        t('从素材库选择')
                      }}</a-button>
                      <a-button size="small" html-type="button" @click="refreshMaterialPreview(slot)">{{ t('刷新预览') }}</a-button>
                      <a-button danger type="link" size="small" html-type="button" @click="removeMaterialSlotById(slot.slotId)">{{
                        t('删除')
                      }}</a-button>
                    </div>
                    <div class="material-asset-fields">
                      <div class="field-label">{{ t('延迟深度链接') }}</div>
                      <a-input v-model:value="slot.deferredDeepLink" :placeholder="t('请输入延迟深度链接')" />
                      <div class="field-label">{{ t('自定义商品页面') }}</div>
                      <a-input v-model:value="slot.customProductPageId" :placeholder="t('请输入自定义商品页面 ID')" />
                    </div>
                    <a-collapse ghost class="material-copy-collapse">
                      <a-collapse-panel key="copy" :header="t('正文与链接')">
                        <a-textarea v-model:value="slot.body" :rows="2" :placeholder="t('正文') + '（message）'" />
                        <a-input v-model:value="slot.headline" class="mt8" :placeholder="t('标题') + '（name）'" />
                        <a-input v-model:value="slot.description" class="mt8" :placeholder="t('描述') + '（description）'" />
                        <a-input v-model:value="slot.link" class="mt8" :placeholder="t('落地页链接') + '（可选）'" />
                        <a-select v-model:value="slot.cta" class="mt8" style="width: 100%; max-width: 280px" :options="ctaOptions" />
                      </a-collapse-panel>
                    </a-collapse>
                  </div>
                </div>
              </div>
            </div>
          </a-form-item>

          <a-modal
            v-model:open="materialPickerOpen"
            :title="t('选择素材')"
            width="840px"
            :footer="null"
            destroy-on-close
          >
            <a-spin :spinning="pickerLoading">
              <div class="material-picker-toolbar">
                <span class="field-hint">{{ t('点击卡片选用该素材') }}</span>
              </div>
              <div class="material-picker-grid">
                <button
                  v-for="m in pickerMaterials"
                  :key="m.id"
                  type="button"
                  class="material-picker-item"
                  @click="applyPickedMaterial(m)"
                >
                  <div class="material-picker-thumb-wrap">
                    <img v-if="m.type === 'image' && m.url" class="material-picker-thumb" :src="m.url" alt="" />
                    <video
                      v-else-if="m.type === 'video' && m.url"
                      class="material-picker-thumb"
                      :src="m.url"
                      muted
                      playsinline
                      preload="metadata"
                    />
                    <div v-else class="material-picker-thumb fallback">{{ t('无预览') }}</div>
                  </div>
                  <div class="material-picker-name" :title="m.filename || m.name">{{ m.filename || m.name || m.id }}</div>
                </button>
              </div>
              <a-empty v-if="!pickerLoading && !pickerMaterials.length" :description="t('暂无素材')" />
            </a-spin>
          </a-modal>
        </template>

        <a-form-item v-if="local.groups[activeIdx].creativeType === 'post'" :label="t('帖子 ID')">
          <a-select
            v-model:value="local.groups[activeIdx].postIds"
            mode="tags"
            :placeholder="t('输入 Meta 帖子 ID，回车添加')"
            style="width: 100%"
          />
        </a-form-item>

        <a-form-item>
          <template #label>
            <span>{{ t('多语言') }}</span>
            <a-tooltip :title="t('为不同语言受众提供多套文案时开启')">
              <span class="setting-mode-help" role="button" tabindex="0">?</span>
            </a-tooltip>
          </template>
          <a-switch v-model:checked="local.groups[activeIdx].multilang" />
          <div v-if="local.groups[activeIdx].multilang && multilangInvalid" class="multilang-error">
            {{ t('请选择默认语言和备选语言') }}
          </div>
        </a-form-item>

        <template v-if="local.groups[activeIdx].multilang">
          <a-form-item :label="t('默认语言')">
            <a-select
              v-model:value="local.groups[activeIdx].defaultLang"
              show-search
              :placeholder="t('搜索…')"
              style="width: 100%; max-width: 360px"
              :options="languageOptions"
              option-filter-prop="label"
            />
          </a-form-item>
          <a-form-item :label="t('备选语言')">
            <a-select
              v-model:value="local.groups[activeIdx].altLangs"
              mode="multiple"
              :placeholder="t('请选择')"
              style="width: 100%; max-width: 400px"
              :options="languageOptions"
              option-filter-prop="label"
            />
          </a-form-item>
        </template>

        <a-form-item :label="t('正文') + '（message）'">
          <div class="copy-actions">
            <a-button size="small" html-type="button" @click="message.info(t('请从文案库选择（待对接）'))">{{ t('选文案') }}</a-button>
            <a-button size="small" html-type="button" @click="message.info(t('批量添加（待对接）'))">{{ t('批量添加文案') }}</a-button>
            <span class="copy-count">({{ (local.groups[activeIdx].body || '').length }}/63206)</span>
          </div>
          <a-textarea
            v-model:value="local.groups[activeIdx].body"
            :placeholder="t('link_data.message / 动态正文')"
            :rows="4"
            :maxlength="63206"
            show-count
          />
        </a-form-item>

        <a-form-item :label="t('标题') + '（name）?'">
          <a-input v-model:value="local.groups[activeIdx].title" :placeholder="t('link_data.name / 标题')" :maxlength="255" show-count />
        </a-form-item>

        <a-form-item :label="t('描述') + '（description）?'">
          <a-textarea
            v-model:value="local.groups[activeIdx].description"
            :placeholder="t('link_data.description')"
            :rows="2"
            :maxlength="10000"
            show-count
          />
        </a-form-item>

        <a-form-item :label="t('行动号召') + '（call_to_action.type） *'">
          <a-select
            v-model:value="local.groups[activeIdx].cta"
            :placeholder="t('请选择')"
            style="width: 100%; max-width: 400px"
            :options="ctaOptions"
          />
        </a-form-item>

        <a-form-item>
          <template #label>
            <span>{{ t('标签') }}</span>
            <a-tooltip :title="t('用于内部筛选与报表维度，可选')">
              <span class="setting-mode-help" role="button" tabindex="0">?</span>
            </a-tooltip>
          </template>
          <a-select
            v-model:value="local.groups[activeIdx].tags"
            mode="tags"
            :placeholder="t('请选择')"
            style="width: 100%; max-width: 560px"
          />
        </a-form-item>

        <a-form-item :label="t('创意组名称')">
          <a-input
            v-model:value="local.groups[activeIdx].creativeGroupName"
            :placeholder="t('请输入')"
            :maxlength="100"
            style="max-width: 560px"
            show-count
          />
          <div class="field-hint">{{ t('用于投放标识与宏 creative_group_name；可与 Tab 标题不同。') }}</div>
        </a-form-item>

        <div class="cg-save-row">
          <a-button type="primary" html-type="button" @click="saveCreativeGroupToForm">{{ t('保存创意组') }}</a-button>
        </div>
      </a-form>
    </div>

    <a-modal
      v-model:open="existingGroupModalVisible"
      :title="t('选择已有创意组')"
      width="min(960px, 96vw)"
      :ok-text="t('确定')"
      :cancel-text="t('取消')"
      destroy-on-close
      @ok="onExistingGroupModalOk"
    >
      <div class="existing-cg-toolbar">
        <a-input-search
          v-model:value="existingSearchKeyword"
          :placeholder="t('搜索…')"
          style="width: 220px"
          disabled
        />
        <a-select
          v-model:value="existingTagFilter"
          allow-clear
          :placeholder="t('请选择')"
          style="width: 160px"
          :options="existingTagFilterOptions"
          disabled
        />
        <a-button type="link" disabled>{{ t('同步') }}</a-button>
      </div>
      <p class="field-hint existing-cg-hint">{{ t('历史创意组列表待对接后端；筛选与同步暂不可用。') }}</p>
      <a-table
        size="small"
        :columns="existingTableColumns"
        :data-source="existingTableRows"
        :pagination="existingPagination"
        :locale="{ emptyText: t('暂无数据') }"
        row-key="id"
        :row-selection="{ selectedRowKeys: existingSelectedKeys, onChange: onExistingSelectionChange }"
      />
    </a-modal>

    <a-modal
      v-model:open="bulkModalVisible"
      :title="t('批量设置')"
      width="min(1200px, 96vw)"
      :ok-text="t('确定')"
      :cancel-text="t('取消')"
      destroy-on-close
      @ok="onBulkModalOk"
    >
      <div class="bulk-modal-toolbar">
        <a-dropdown :trigger="['click']">
          <a-button>
            {{ t('批量操作') }}
            <span class="bulk-dd-caret">▼</span>
          </a-button>
          <template #overlay>
            <a-menu @click="onBulkMenuClick">
              <a-menu-item key="binding">{{ t('绑定规则') }}</a-menu-item>
              <a-menu-item key="cta">{{ t('修改行动号召') }}</a-menu-item>
              <a-menu-item key="title">{{ t('修改标题') }}</a-menu-item>
              <a-menu-item key="body">{{ t('修改正文') }}</a-menu-item>
              <a-menu-item key="deferredDeepLink">{{ t('修改延迟深度链接') }}</a-menu-item>
              <a-menu-item key="url">{{ t('修改网址') }}</a-menu-item>
              <a-menu-item key="displayLink">{{ t('修改显示链接') }}</a-menu-item>
            </a-menu>
          </template>
        </a-dropdown>

        <div v-if="bulkPasteField" class="bulk-paste-fields">
          <template v-if="bulkPasteField === 'binding'">
            <a-select
              v-model:value="bulkPasteBindingRule"
              style="width: 160px"
              :options="bindingRuleSelectOptions"
            />
            <template v-if="bulkPasteBindingRule === 'by_account'">
              <a-select v-model:value="bulkPasteBindingAccountMode" style="width: 120px" :options="bindingAccountModeOptions" />
              <a-select
                v-if="bulkPasteBindingAccountMode === 'selected'"
                v-model:value="bulkPasteBindingAccountIds"
                mode="multiple"
                style="min-width: 220px; max-width: 360px"
                :placeholder="t('请选择广告账户')"
                :options="adAccountOptions"
                show-search
                :filter-option="filterAdAccountOption"
                :loading="adAccountsLoading"
                @dropdown-visible-change="onAdAccountDropdownVisible"
              />
            </template>
            <a-select
              v-else
              v-model:value="bulkPasteBindingRegionIds"
              mode="multiple"
              style="min-width: 220px; max-width: 360px"
              :placeholder="t('请选择定向包（地区组）')"
              :options="regionGroupOptions"
            />
          </template>
          <a-select
            v-else-if="bulkPasteField === 'cta'"
            v-model:value="bulkPasteCta"
            style="width: 280px"
            :options="ctaOptions"
          />
          <a-input v-else-if="bulkPasteField === 'title'" v-model:value="bulkPasteTitle" :placeholder="t('点击输入标题')" />
          <a-textarea
            v-else-if="bulkPasteField === 'body'"
            v-model:value="bulkPasteBody"
            :rows="2"
            :placeholder="t('点击输入正文')"
          />
          <a-input
            v-else-if="bulkPasteField === 'deferredDeepLink'"
            v-model:value="bulkPasteDeferredDeepLink"
            :placeholder="t('http://…')"
          />
          <a-input v-else-if="bulkPasteField === 'url'" v-model:value="bulkPasteUrl" :placeholder="t('https://…')" />
          <a-input v-else-if="bulkPasteField === 'displayLink'" v-model:value="bulkPasteDisplayLink" :placeholder="t('显示链接')" />
        </div>

        <a-button type="primary" html-type="button" :disabled="!bulkPasteField" @click="applyBulkPaste">{{
          t('应用到选中')
        }}</a-button>
      </div>

      <div class="bulk-table-scroll">
        <table class="bulk-table">
          <thead>
            <tr>
              <th class="bulk-col-check">
                <a-checkbox
                  :checked="bulkAllSelected"
                  :indeterminate="bulkIndeterminate"
                  @change="onBulkSelectAll"
                />
              </th>
              <th>{{ t('创意组') }}</th>
              <th>{{ t('绑定规则') }}</th>
              <th>{{ t('行动号召') }}</th>
              <th>{{ t('标题') }}</th>
              <th>{{ t('正文') }}</th>
              <th>{{ t('延迟深度链接') }}</th>
              <th>{{ t('网址') }}</th>
              <th>{{ t('显示链接') }}</th>
            </tr>
          </thead>
          <tbody>
            <tr v-for="g in local.groups" :key="g.id">
              <td class="bulk-col-check">
                <a-checkbox :checked="bulkSelectedRowKeys.includes(g.id)" @change="(e) => onBulkRowChange(g.id, e)" />
              </td>
              <td>{{ g.name }}</td>
              <td class="bulk-col-binding">
                <a-select v-model:value="g.bindingRule" size="small" style="width: 100%" :options="bindingRuleSelectOptions" />
                <a-select
                  v-if="g.bindingRule === 'by_account'"
                  v-model:value="g.bindingAdAccountMode"
                  size="small"
                  style="width: 100%; margin-top: 4px"
                  :options="bindingAccountModeOptions"
                />
                <a-select
                  v-if="g.bindingRule === 'by_account' && g.bindingAdAccountMode === 'selected'"
                  v-model:value="g.bindingAdAccountIds"
                  mode="multiple"
                  size="small"
                  style="width: 100%; margin-top: 4px"
                  :placeholder="t('广告账户')"
                  :options="adAccountOptions"
                  @dropdown-visible-change="onAdAccountDropdownVisible"
                />
                <a-select
                  v-if="g.bindingRule === 'by_region_group'"
                  v-model:value="g.bindingRegionGroupIds"
                  mode="multiple"
                  size="small"
                  style="width: 100%; margin-top: 4px"
                  :placeholder="t('定向包')"
                  :options="regionGroupOptions"
                />
              </td>
              <td>
                <a-select v-model:value="g.cta" size="small" style="width: 100%" :options="ctaOptions" />
              </td>
              <td><a-input v-model:value="g.title" size="small" :placeholder="t('点击输入标题')" /></td>
              <td><a-input v-model:value="g.body" size="small" :placeholder="t('点击输入正文')" /></td>
              <td><a-input v-model:value="g.deepLink" size="small" :placeholder="t('延迟深度链接')" /></td>
              <td><a-input v-model:value="g.linkUrl" size="small" :placeholder="t('网址')" /></td>
              <td><a-input v-model:value="g.displayLink" size="small" :placeholder="t('显示链接')" /></td>
            </tr>
          </tbody>
        </table>
      </div>
    </a-modal>
  </div>
</template>

<script lang="ts" setup>
import { reactive, ref, computed, watch, nextTick, toRaw, onMounted } from 'vue';
import { useI18n } from 'vue-i18n';
import { message } from 'ant-design-vue';
import type { CheckboxChangeEvent } from 'ant-design-vue/es/checkbox/interface';
import { getMaterialApi, materialsList } from '@/api/materials';
import { queryFB_AD_AccountsApi } from '@/api/fb_ad_accounts';

const { t } = useI18n();

const MAX_GROUPS = 30;
const MAX_ASSETS_HINT = 50;

const settingModeTooltip = computed(() =>
  t(
    '决定创意以「整组」还是「单条素材」分配到各展示位置：按创意组为统一配置；按素材为逐条素材与文案组合，便于对齐 Meta asset_feed_spec / 动态创意等能力。',
  ),
);

const props = defineProps<{
  formData: any;
  /** 定向包列表，用于「按地区组绑定」多选 */
  stepTargeting?: Record<string, unknown>;
}>();
const emit = defineEmits<{ (e: 'update:form-data', v: Record<string, unknown>): void }>();

const syncingFromParent = ref(false);
const editingTabId = ref<string | null>(null);

type MaterialSlotRow = {
  slotId: string;
  assetType: 'image' | 'video' | 'carousel';
  materialId: string;
  deferredDeepLink: string;
  customProductPageId: string;
  body: string;
  headline: string;
  description: string;
  link: string;
  cta: string;
};

type CreativeGroupItem = {
  id: string;
  name: string;
  creativeGroupName: string;
  useExisting: boolean;
  dynamicCreative: boolean;
  creativeType: 'create' | 'post';
  format: 'flexible' | 'single' | 'carousel';
  settingMode: 'by_group' | 'by_material';
  deepLink: string;
  linkUrl: string;
  /** display link（Meta link_destination_display_url） */
  displayLink: string;
  videoOptimization: string;
  imageOptimization: string;
  videoMaterialIds: string[];
  imageMaterialIds: string[];
  materialIds: string[];
  multilang: boolean;
  /** Meta 多语言：默认语言 locale key */
  defaultLang: string;
  /** 备选语言 */
  altLangs: string[];
  body: string;
  title: string;
  description: string;
  cta: string;
  tags: string[];
  postIds: string[];
  materialSlots: MaterialSlotRow[];
  bindingRule: 'by_account' | 'by_region_group';
  bindingAdAccountMode: 'all' | 'selected';
  bindingAdAccountIds: string[];
  bindingRegionGroupIds: string[];
};

type PreviewEntry =
  | { status: 'loading' }
  | { status: 'ok'; name?: string; filename?: string; type?: string; url?: string }
  | { status: 'err' };

const previewCache = ref<Record<string, PreviewEntry>>({});

const materialPickerOpen = ref(false);
const materialPickerTargetSlotId = ref('');
const materialPickerKind = ref<'video' | 'image' | 'carousel'>('image');
const pickerMaterials = ref<any[]>([]);
const pickerLoading = ref(false);

const adAccounts = ref<{ id: string; name?: string }[]>([]);
const adAccountsLoading = ref(false);
const adAccountsLoaded = ref(false);

const adAccountOptions = computed(() =>
  adAccounts.value.map((a) => ({ label: a.name ?? a.id, value: a.id })),
);

const regionGroupOptions = computed(() => {
  const pkgs = props.stepTargeting?.packages as unknown[] | undefined;
  if (!Array.isArray(pkgs)) return [];
  return pkgs
    .map((p) => {
      const row = p as Record<string, unknown>;
      return {
        label: String(row.name ?? row.id ?? ''),
        value: String(row.id ?? ''),
      };
    })
    .filter((o) => o.value !== '');
});

function filterAdAccountOption(input: string, option: { label?: string }) {
  return String(option?.label ?? '').toLowerCase().includes(input.toLowerCase());
}

async function loadAdAccounts() {
  if (adAccountsLoaded.value) return;
  adAccountsLoading.value = true;
  try {
    const res = (await queryFB_AD_AccountsApi({ 'with-campaign': false, is_archived: false })) as { data?: unknown };
    const list = res?.data ?? [];
    adAccounts.value = Array.isArray(list) ? (list as { id: string; name?: string }[]) : [];
    adAccountsLoaded.value = true;
  } catch {
    adAccounts.value = [];
  } finally {
    adAccountsLoading.value = false;
  }
}

function onAdAccountDropdownVisible(open: boolean) {
  if (open) void loadAdAccounts();
}

onMounted(() => {
  void loadAdAccounts();
});

function newGroup(index: number): CreativeGroupItem {
  const id = `cg-${Date.now()}-${Math.random().toString(36).slice(2, 9)}`;
  return {
    id,
    name: `${t('创意组')}${index}`,
    creativeGroupName: '',
    useExisting: false,
    dynamicCreative: false,
    creativeType: 'create',
    format: 'single',
    settingMode: 'by_group',
    deepLink: '',
    linkUrl: '',
    displayLink: '',
    videoOptimization: 'full',
    imageOptimization: 'full',
    videoMaterialIds: [],
    imageMaterialIds: [],
    materialIds: [],
    multilang: false,
    defaultLang: 'en',
    altLangs: [] as string[],
    body: '',
    title: '',
    description: '',
    cta: 'LEARN_MORE',
    tags: [],
    postIds: [],
    materialSlots: [],
    bindingRule: 'by_account',
    bindingAdAccountMode: 'all',
    bindingAdAccountIds: [],
    bindingRegionGroupIds: [],
  };
}

function migrateIncoming(fd: Record<string, unknown> | null | undefined): {
  activeGroupId: string;
  groups: CreativeGroupItem[];
} {
  const g0 = newGroup(1);
  if (fd && Array.isArray((fd as any).groups) && (fd as any).groups.length > 0) {
    const groups = (fd as any).groups.map((x: any, i: number) => {
      const base = newGroup(i + 1);
      return {
        ...base,
        ...x,
        id: String(x.id || base.id),
        name: String(x.name ?? base.name),
        creativeGroupName: String(x.creativeGroupName ?? ''),
        videoMaterialIds: Array.isArray(x.videoMaterialIds) ? [...x.videoMaterialIds] : [],
        imageMaterialIds: Array.isArray(x.imageMaterialIds) ? [...x.imageMaterialIds] : [],
        materialIds: Array.isArray(x.materialIds) ? [...x.materialIds] : [],
        postIds: Array.isArray(x.postIds) ? [...x.postIds] : [],
        tags: Array.isArray(x.tags) ? [...x.tags] : [],
        cta: x.cta != null && x.cta !== '' ? String(x.cta) : base.cta,
        materialSlots: Array.isArray(x.materialSlots)
          ? x.materialSlots.map((row: any) => normalizeMaterialSlotRow(row, String(x.cta ?? base.cta)))
          : [],
        bindingRule: x.bindingRule === 'by_region_group' ? 'by_region_group' : 'by_account',
        bindingAdAccountMode: x.bindingAdAccountMode === 'selected' ? 'selected' : 'all',
        bindingAdAccountIds: Array.isArray(x.bindingAdAccountIds) ? [...x.bindingAdAccountIds] : [],
        bindingRegionGroupIds: Array.isArray(x.bindingRegionGroupIds) ? [...x.bindingRegionGroupIds] : [],
        displayLink: String(x.displayLink ?? ''),
        defaultLang:
          x.defaultLang != null && String(x.defaultLang).trim() !== '' ? String(x.defaultLang) : base.defaultLang,
        altLangs: Array.isArray(x.altLangs) ? x.altLangs.map((a: unknown) => String(a)) : [...base.altLangs],
      };
    });
    const activeGroupId = String((fd as any).activeGroupId || groups[0].id);
    return { activeGroupId, groups };
  }
  const flat = fd && typeof fd === 'object' ? fd : {};
  const id = `cg-${Date.now()}`;
  const ng: CreativeGroupItem = {
    ...newGroup(1),
    id,
    name: String((flat as any).groupName ?? (flat as any).creativeGroupName ?? g0.name),
    creativeGroupName: String((flat as any).creativeGroupName ?? ''),
    materialIds: Array.isArray((flat as any).materialIds) ? [...(flat as any).materialIds] : [],
    postIds: Array.isArray((flat as any).postIds) ? [...(flat as any).postIds] : [],
    body: String((flat as any).body ?? ''),
    title: String((flat as any).title ?? ''),
    description: String((flat as any).description ?? ''),
    linkUrl: String((flat as any).linkUrl ?? ''),
    displayLink: String((flat as any).displayLink ?? ''),
    deepLink: String((flat as any).deepLink ?? ''),
    cta: (flat as any).cta != null && (flat as any).cta !== '' ? String((flat as any).cta) : 'LEARN_MORE',
    defaultLang:
      (flat as any).defaultLang != null && String((flat as any).defaultLang).trim() !== ''
        ? String((flat as any).defaultLang)
        : 'en',
    altLangs: Array.isArray((flat as any).altLangs) ? (flat as any).altLangs.map((a: unknown) => String(a)) : [],
  };
  recomputeMaterialIds(ng);
  return { activeGroupId: id, groups: [ng] };
}

function normalizeMaterialSlotRow(row: any, defaultCta: string): MaterialSlotRow {
  const raw = String(row?.assetType ?? row?.asset_type ?? 'image');
  let at: MaterialSlotRow['assetType'] = 'image';
  if (raw === 'video') at = 'video';
  else if (raw === 'carousel') at = 'carousel';
  return {
    slotId: String(row?.slotId ?? row?.slot_id ?? `slot-${Date.now()}-${Math.random().toString(36).slice(2, 9)}`),
    assetType: at,
    materialId: String(row?.materialId ?? row?.material_id ?? ''),
    deferredDeepLink: String(row?.deferredDeepLink ?? row?.deferred_deep_link ?? ''),
    customProductPageId: String(row?.customProductPageId ?? row?.custom_product_page_id ?? ''),
    body: String(row?.body ?? ''),
    headline: String(row?.headline ?? ''),
    description: String(row?.description ?? ''),
    link: String(row?.link ?? ''),
    cta: row?.cta != null && String(row.cta) !== '' ? String(row.cta) : defaultCta,
  };
}

function recomputeMaterialIds(g: CreativeGroupItem) {
  if (g.settingMode === 'by_material') {
    const set = new Set<string>();
    (g.materialSlots || []).forEach((row) => {
      const id = String(row.materialId || '').trim();
      if (id) set.add(id);
    });
    g.materialIds = Array.from(set);
    return;
  }
  const set = new Set<string>();
  (g.videoMaterialIds || []).forEach((x) => x && set.add(String(x)));
  (g.imageMaterialIds || []).forEach((x) => x && set.add(String(x)));
  g.materialIds = Array.from(set);
}

const _initial = migrateIncoming(props.formData);
const local = reactive({
  activeGroupId: _initial.activeGroupId,
  groups: _initial.groups,
});

function initFromProps() {
  const m = migrateIncoming(props.formData);
  syncingFromParent.value = true;
  local.activeGroupId = m.activeGroupId;
  local.groups.splice(0, local.groups.length, ...m.groups);
  nextTick(() => {
    syncingFromParent.value = false;
    rehydrateAllMaterialPreviews();
  });
}

/** 必须用下标绑定 reactive 数组元素，勿用 computed 返回组对象 + v-model（computed 只读会导致单选/开关无响应） */
const activeIdx = computed(() => {
  if (!local.groups.length) return 0;
  const i = local.groups.findIndex((x) => x.id === local.activeGroupId);
  return i >= 0 ? i : 0;
});

function getActiveGroup(): CreativeGroupItem | undefined {
  return local.groups.find((x) => x.id === local.activeGroupId) ?? local.groups[0];
}

function slotsOfKind(g: CreativeGroupItem | undefined, kind: MaterialSlotRow['assetType']): MaterialSlotRow[] {
  return (g?.materialSlots || []).filter((s) => s.assetType === kind);
}

type OkPreview = Extract<PreviewEntry, { status: 'ok' }>;

function previewEntry(materialId: string | undefined): PreviewEntry | undefined {
  const id = String(materialId || '').trim();
  if (!id) return undefined;
  return previewCache.value[id];
}

function slotPreviewOk(slot: MaterialSlotRow): OkPreview | null {
  const id = String(slot.materialId || '').trim();
  if (!id) return null;
  const p = previewCache.value[id];
  return p?.status === 'ok' ? p : null;
}

function displayFileLabel(slot: MaterialSlotRow): string {
  const id = String(slot.materialId || '').trim();
  if (!id) return t('未填写素材 ID');
  const p = previewCache.value[id];
  if (p?.status === 'ok') return (p.filename || p.name || id) as string;
  if (p?.status === 'loading') return t('加载中…');
  return id;
}

async function fetchPreviewByMaterialId(id: string) {
  const trimmed = id.trim();
  if (!trimmed) return;
  previewCache.value[trimmed] = { status: 'loading' };
  try {
    const res = (await getMaterialApi(trimmed)) as Record<string, any>;
    const data = res?.data ?? res;
    previewCache.value[trimmed] = {
      status: 'ok',
      name: data?.name,
      filename: data?.filename,
      type: data?.type,
      url: data?.url,
    };
  } catch {
    previewCache.value[trimmed] = { status: 'err' };
  }
}

async function refreshMaterialPreview(slot: MaterialSlotRow) {
  const id = String(slot.materialId || '').trim();
  if (!id) return;
  await fetchPreviewByMaterialId(id);
}

function onMaterialIdCommit(slot: MaterialSlotRow) {
  void refreshMaterialPreview(slot);
}

function rehydrateAllMaterialPreviews() {
  const ids = new Set<string>();
  for (const g of local.groups) {
    (g.materialSlots || []).forEach((s) => {
      const id = String(s.materialId || '').trim();
      if (id) ids.add(id);
    });
  }
  ids.forEach((id) => void fetchPreviewByMaterialId(id));
}

async function loadMaterialsForPicker() {
  pickerLoading.value = true;
  try {
    const kind = materialPickerKind.value;
    const type = kind === 'carousel' ? undefined : kind;
    const res = await materialsList({ pageNo: 1, pageSize: 120, ...(type ? { type } : {}) });
    pickerMaterials.value = (res as any)?.data ?? [];
  } catch {
    pickerMaterials.value = [];
    message.error(t('加载素材列表失败'));
  } finally {
    pickerLoading.value = false;
  }
}

watch(materialPickerOpen, (open) => {
  if (open) void loadMaterialsForPicker();
});

function openMaterialPicker(slotId: string, kind: 'video' | 'image' | 'carousel') {
  materialPickerTargetSlotId.value = slotId;
  materialPickerKind.value = kind;
  materialPickerOpen.value = true;
}

function applyPickedMaterial(m: Record<string, any>) {
  const slotId = materialPickerTargetSlotId.value;
  const g = getActiveGroup();
  const slot = g?.materialSlots?.find((s) => s.slotId === slotId);
  if (!slot || !m?.id) return;
  slot.materialId = String(m.id);
  materialPickerOpen.value = false;
  void refreshMaterialPreview(slot);
}

const optimizationOptions = computed(() => [
  { label: t('全面优化(全选)'), value: 'full' },
  { label: t('平衡'), value: 'balanced' },
  { label: t('无'), value: 'none' },
]);

/** Meta call_to_action.type 常用值（Marketing API） */
const ctaOptions = [
  { label: 'LEARN_MORE', value: 'LEARN_MORE' },
  { label: 'SHOP_NOW', value: 'SHOP_NOW' },
  { label: 'SIGN_UP', value: 'SIGN_UP' },
  { label: 'DOWNLOAD', value: 'DOWNLOAD' },
  { label: 'BOOK_TRAVEL', value: 'BOOK_TRAVEL' },
  { label: 'CONTACT_US', value: 'CONTACT_US' },
  { label: 'APPLY_NOW', value: 'APPLY_NOW' },
  { label: 'BUY_NOW', value: 'BUY_NOW' },
  { label: 'GET_QUOTE', value: 'GET_QUOTE' },
  { label: 'SUBSCRIBE', value: 'SUBSCRIBE' },
  { label: 'WATCH_MORE', value: 'WATCH_MORE' },
  { label: 'LISTEN_NOW', value: 'LISTEN_NOW' },
  { label: 'INSTALL_MOBILE_APP', value: 'INSTALL_MOBILE_APP' },
  { label: 'USE_MOBILE_APP', value: 'USE_MOBILE_APP' },
  { label: 'ORDER_NOW', value: 'ORDER_NOW' },
  { label: 'DONATE_NOW', value: 'DONATE_NOW' },
  { label: 'SEE_MENU', value: 'SEE_MENU' },
];

const bindingRuleSelectOptions = computed(() => [
  { label: t('按账户绑定'), value: 'by_account' as const },
  { label: t('按地区组绑定'), value: 'by_region_group' as const },
]);

const bindingAccountModeOptions = computed(() => [
  { label: t('全部账户'), value: 'all' as const },
  { label: t('指定账户'), value: 'selected' as const },
]);

const linkUrlInputRef = ref<{ focus?: () => void } | null>(null);

const xmpUrlTokens = ['{{AccountID}}', '{{AccountName}}'];
const metaDynamicUrlTokensAll = [
  '{{campaign.id}}',
  '{{campaign.name}}',
  '{{adset.id}}',
  '{{adset.name}}',
  '{{ad.id}}',
  '{{ad.name}}',
  '{{placement}}',
  '{{site_source_name}}',
];
const metaDynamicUrlTokensShort = metaDynamicUrlTokensAll.slice(0, 4);
const metaDynamicUrlTokensMore = metaDynamicUrlTokensAll.slice(4);

const linkUrlMetaMoreOpen = ref(false);

function appendLinkUrlToken(token: string) {
  const g = local.groups[activeIdx.value];
  if (!g) return;
  const cur = String(g.linkUrl || '');
  g.linkUrl = cur + token;
  void nextTick(() => linkUrlInputRef.value?.focus?.());
}

function previewActiveLinkUrl() {
  const g = local.groups[activeIdx.value];
  const raw = String(g?.linkUrl || '').trim();
  if (!raw) {
    message.warning(t('请先填写网址'));
    return;
  }
  let href = raw;
  if (!/^https?:\/\//i.test(href)) href = `https://${href}`;
  try {
    const u = new URL(href);
    window.open(u.href, '_blank', 'noopener,noreferrer');
  } catch {
    message.error(t('网址格式无效，无法预览'));
  }
}

const languageOptions = computed(() => [
  { label: t('英语'), value: 'en' },
  { label: t('法语'), value: 'fr' },
  { label: t('西班牙语'), value: 'es' },
  { label: t('世界语'), value: 'eo' },
  { label: t('中文（简体）'), value: 'zh_CN' },
  { label: t('丹麦语'), value: 'da' },
  { label: t('乌克兰语'), value: 'uk' },
  { label: t('乌尔都语'), value: 'ur' },
  { label: t('亚美尼亚语'), value: 'hy' },
]);

const multilangInvalid = computed(() => {
  const g = local.groups[activeIdx.value];
  if (!g?.multilang) return false;
  if (!String(g.defaultLang || '').trim()) return true;
  if (!Array.isArray(g.altLangs) || g.altLangs.length === 0) return true;
  return false;
});

const existingGroupModalVisible = ref(false);
const existingSearchKeyword = ref('');
const existingTagFilter = ref<string | undefined>(undefined);
const existingTagFilterOptions = computed(() => [] as { label: string; value: string }[]);
const existingTableRows = ref<Record<string, unknown>[]>([]);
const existingSelectedKeys = ref<string[]>([]);

const existingTableColumns = computed(() => [
  { title: t('定向包名称'), dataIndex: 'targetingName', key: 'targetingName' },
  { title: t('操作'), dataIndex: 'op', key: 'op', width: 120 },
  { title: t('标签'), dataIndex: 'tags', key: 'tags' },
  { title: t('创建时间'), dataIndex: 'createdAt', key: 'createdAt', width: 180 },
]);

const existingPagination = reactive({
  current: 1,
  pageSize: 20,
  total: 0,
  showSizeChanger: true,
  showQuickJumper: true,
  pageSizeOptions: ['10', '20', '50', '100'],
  showTotal: (total: number) => t('共 {n} 条', { n: String(total) }),
});

function onExistingSelectionChange(keys: (string | number)[]) {
  existingSelectedKeys.value = keys.map(String);
}

function onExistingGroupModalOk() {
  if (existingSelectedKeys.value.length) {
    message.info(t('已记录所选创意组，待后端对接后将自动回填'));
  }
  existingGroupModalVisible.value = false;
}

function onUseExistingChange(e: CheckboxChangeEvent) {
  if (e.target.checked) {
    existingGroupModalVisible.value = true;
  }
}

function addCreativeGroup() {
  onTabEdit('', 'add');
}

function saveCreativeGroupToForm() {
  const g = getActiveGroup();
  if (!g) return;
  recomputeMaterialIds(g);
  emit('update:form-data', buildEmitPayload());
  message.success(t('创意组已保存到表单'));
}

const bulkModalVisible = ref(false);
const bulkSelectedRowKeys = ref<string[]>([]);
const bulkPasteField = ref<string | null>(null);

const bulkPasteBindingRule = ref<CreativeGroupItem['bindingRule']>('by_account');
const bulkPasteBindingAccountMode = ref<CreativeGroupItem['bindingAdAccountMode']>('all');
const bulkPasteBindingAccountIds = ref<string[]>([]);
const bulkPasteBindingRegionIds = ref<string[]>([]);
const bulkPasteCta = ref('LEARN_MORE');
const bulkPasteTitle = ref('');
const bulkPasteBody = ref('');
const bulkPasteDeferredDeepLink = ref('');
const bulkPasteUrl = ref('');
const bulkPasteDisplayLink = ref('');

const bulkAllSelected = computed(
  () => local.groups.length > 0 && bulkSelectedRowKeys.value.length === local.groups.length,
);
const bulkIndeterminate = computed(
  () => bulkSelectedRowKeys.value.length > 0 && bulkSelectedRowKeys.value.length < local.groups.length,
);

function openBulkModal() {
  bulkModalVisible.value = true;
  bulkSelectedRowKeys.value = local.groups.map((g) => g.id);
  bulkPasteField.value = null;
}

function onBulkModalOk() {
  bulkModalVisible.value = false;
}

function onBulkMenuClick(info: { key: string | number }) {
  bulkPasteField.value = String(info.key);
}

function onBulkSelectAll(e: CheckboxChangeEvent) {
  const checked = e.target.checked;
  if (checked) {
    bulkSelectedRowKeys.value = local.groups.map((g) => g.id);
  } else {
    bulkSelectedRowKeys.value = [];
  }
}

function onBulkRowChange(id: string, e: CheckboxChangeEvent) {
  const checked = e.target.checked;
  if (checked) {
    if (!bulkSelectedRowKeys.value.includes(id)) bulkSelectedRowKeys.value.push(id);
  } else {
    bulkSelectedRowKeys.value = bulkSelectedRowKeys.value.filter((x) => x !== id);
  }
}

function applyBulkPaste() {
  const field = bulkPasteField.value;
  if (!field) {
    message.warning(t('请先在「批量操作」中选择一项'));
    return;
  }
  const ids = bulkSelectedRowKeys.value;
  if (!ids.length) {
    message.warning(t('请先勾选创意组'));
    return;
  }
  for (const id of ids) {
    const g = local.groups.find((x) => x.id === id);
    if (!g) continue;
    switch (field) {
      case 'binding': {
        g.bindingRule = bulkPasteBindingRule.value;
        if (g.bindingRule === 'by_account') {
          g.bindingAdAccountMode = bulkPasteBindingAccountMode.value;
          g.bindingAdAccountIds = [...bulkPasteBindingAccountIds.value];
          g.bindingRegionGroupIds = [];
        } else {
          g.bindingRegionGroupIds = [...bulkPasteBindingRegionIds.value];
          g.bindingAdAccountMode = 'all';
          g.bindingAdAccountIds = [];
        }
        break;
      }
      case 'cta':
        g.cta = bulkPasteCta.value;
        break;
      case 'title':
        g.title = bulkPasteTitle.value;
        break;
      case 'body':
        g.body = bulkPasteBody.value;
        break;
      case 'deferredDeepLink': {
        const v = bulkPasteDeferredDeepLink.value;
        g.deepLink = v;
        (g.materialSlots || []).forEach((s) => {
          s.deferredDeepLink = v;
        });
        break;
      }
      case 'url':
        g.linkUrl = bulkPasteUrl.value;
        break;
      case 'displayLink':
        g.displayLink = bulkPasteDisplayLink.value;
        break;
      default:
        break;
    }
  }
  message.success(t('已应用到选中创意组'));
}

const totalAssetEstimate = computed(() => {
  let n = 0;
  for (const g of local.groups) {
    recomputeMaterialIds(g);
    if (g.settingMode === 'by_material' && (g.materialSlots?.length ?? 0) > 0) {
      n += (g.materialSlots || []).filter((r) => String(r.materialId || '').trim()).length;
    } else {
      n += g.materialIds.length || (g.postIds?.length ?? 0) || 1;
    }
  }
  return Math.min(n, MAX_ASSETS_HINT);
});

function onTabEdit(targetKey: string | MouseEvent, action: 'add' | 'remove') {
  if (action === 'add') {
    if (local.groups.length >= MAX_GROUPS) {
      message.warning(t('最多 30 个创意组'));
      return;
    }
    const ng = newGroup(local.groups.length + 1);
    local.groups.push(ng);
    local.activeGroupId = ng.id;
    return;
  }
  const key = typeof targetKey === 'string' ? targetKey : String((targetKey as any)?.target?.value ?? '');
  if (!key) return;
  if (local.groups.length <= 1) {
    message.warning(t('至少保留一个创意组'));
    return;
  }
  const idx = local.groups.findIndex((g) => g.id === key);
  if (idx >= 0) {
    local.groups.splice(idx, 1);
    if (local.activeGroupId === key) {
      const next = local.groups[Math.max(0, idx - 1)] || local.groups[0];
      local.activeGroupId = next?.id ?? '';
    }
  }
}

function startRename(id: string) {
  editingTabId.value = id;
  void nextTick(() => {
    const inputs = document.querySelectorAll('.tab-title-input input');
    const last = inputs[inputs.length - 1] as HTMLInputElement | undefined;
    last?.focus?.();
  });
}

function finishRename() {
  const id = editingTabId.value;
  if (id) {
    const g = local.groups.find((x) => x.id === id);
    if (g && !String(g.name || '').trim()) {
      g.name = t('创意组');
    }
  }
  editingTabId.value = null;
}

function addMaterialSlot(kind: MaterialSlotRow['assetType'], openPickerAfter = false) {
  const g = getActiveGroup();
  if (!g) return;
  if (!Array.isArray(g.materialSlots)) g.materialSlots = [];
  const slot: MaterialSlotRow = {
    slotId: `slot-${Date.now()}-${Math.random().toString(36).slice(2, 9)}`,
    assetType: kind,
    materialId: '',
    deferredDeepLink: '',
    customProductPageId: '',
    body: '',
    headline: '',
    description: '',
    link: '',
    cta: g.cta || 'LEARN_MORE',
  };
  g.materialSlots.push(slot);
  recomputeMaterialIds(g);
  if (openPickerAfter) {
    materialPickerTargetSlotId.value = slot.slotId;
    materialPickerKind.value = kind;
    materialPickerOpen.value = true;
  }
}

function removeMaterialSlotById(slotId: string) {
  const g = getActiveGroup();
  if (!g || !Array.isArray(g.materialSlots)) return;
  const idx = g.materialSlots.findIndex((s) => s.slotId === slotId);
  if (idx >= 0) g.materialSlots.splice(idx, 1);
  recomputeMaterialIds(g);
}

function onPickMaterials(kind: 'video' | 'image') {
  message.info(t('请从素材库选择素材 ID（可粘贴 ULID）；完整选择器可后续对接素材库。'));
  const id = window.prompt(t('请输入素材 ID'), '');
  if (!id || !id.trim()) return;
  const g = getActiveGroup();
  if (!g) return;
  if (kind === 'video') {
    if (!g.videoMaterialIds.includes(id.trim())) g.videoMaterialIds.push(id.trim());
  } else if (!g.imageMaterialIds.includes(id.trim())) {
    g.imageMaterialIds.push(id.trim());
  }
  recomputeMaterialIds(g);
}

function removeMaterialId(kind: 'video' | 'image', id: string) {
  const g = getActiveGroup();
  if (!g) return;
  if (kind === 'video') {
    g.videoMaterialIds = g.videoMaterialIds.filter((x) => x !== id);
  } else {
    g.imageMaterialIds = g.imageMaterialIds.filter((x) => x !== id);
  }
  recomputeMaterialIds(g);
}

function allMaterialIdsUnion(): string[] {
  const s = new Set<string>();
  for (const g of local.groups) {
    recomputeMaterialIds(g);
    (g.materialIds || []).forEach((x) => x && s.add(String(x)));
  }
  return Array.from(s);
}

function allPostIdsUnion(): string[] {
  const s = new Set<string>();
  for (const g of local.groups) {
    (g.postIds || []).forEach((x) => x && s.add(String(x)));
  }
  return Array.from(s);
}

function buildEmitPayload() {
  for (const g of local.groups) {
    recomputeMaterialIds(g);
  }
  const ag = local.groups.find((x) => x.id === local.activeGroupId) || local.groups[0];
  return {
    ...toRaw(local),
    groupName: ag?.name,
    creativeGroupName: ag?.creativeGroupName || ag?.name || '',
    materialIds: allMaterialIdsUnion(),
    postIds: allPostIdsUnion(),
  };
}

/** 仅父级整体替换 stepCreativeGroup 时同步（如加载模板）；勿 deep，否则与子组件 emit 形成循环重置 Tab/表单 */
watch(
  () => props.formData,
  () => {
    initFromProps();
  },
);

watch(
  local,
  () => {
    if (syncingFromParent.value) return;
    for (const g of local.groups) {
      recomputeMaterialIds(g);
    }
    emit('update:form-data', buildEmitPayload());
  },
  { deep: true },
);

</script>

<style lang="less" scoped>
.section-title-row {
  display: flex;
  align-items: center;
  justify-content: space-between;
  gap: 12px;
  margin-bottom: 8px;
  flex-wrap: wrap;
}
.section-title-row .section-title {
  margin-bottom: 0;
}
.section-title {
  font-size: 16px;
  font-weight: 500;
  margin-bottom: 8px;
  color: #262626;
}
.section-hint {
  margin: 0 0 16px;
  font-size: 13px;
  color: #8c8c8c;
  line-height: 1.5;
}
.doc-link {
  margin-left: 8px;
}
.creative-group-tabs {
  margin-bottom: 16px;
}
.tab-title {
  cursor: text;
  padding: 0 4px;
  max-width: 140px;
  display: inline-block;
  overflow: hidden;
  text-overflow: ellipsis;
  white-space: nowrap;
  vertical-align: middle;
}
.tab-title-input {
  width: 120px;
}
.tab-panel-body {
  padding-top: 8px;
}
.tip {
  margin-bottom: 16px;
}
.field-hint {
  margin-top: 8px;
  font-size: 12px;
  color: #8c8c8c;
}
.field-hint.inline {
  margin-top: 0;
  margin-left: 12px;
}
.binding-sub-block {
  margin-top: 8px;
}
.binding-account-mode {
  display: block;
}
.setting-mode-label {
  display: inline-flex;
  align-items: center;
  gap: 6px;
}
.setting-mode-help {
  display: inline-flex;
  align-items: center;
  justify-content: center;
  width: 16px;
  height: 16px;
  border: 1px solid #bfbfbf;
  border-radius: 50%;
  font-size: 11px;
  line-height: 1;
  color: #8c8c8c;
  cursor: help;
  user-select: none;
}
.setting-mode-help:hover {
  color: #1677ff;
  border-color: #1677ff;
}
.char-count {
  margin-left: 8px;
  color: #999;
  font-size: 12px;
}
.copy-actions {
  display: flex;
  align-items: center;
  gap: 8px;
  margin-bottom: 8px;
  flex-wrap: wrap;
}
.copy-count {
  font-size: 12px;
  color: #666;
}
.id-tags {
  display: flex;
  flex-wrap: wrap;
  gap: 8px;
}
.material-by-asset-intro {
  margin-bottom: 16px;
}
.asset-type-block {
  margin-bottom: 24px;
}
.asset-type-head {
  display: flex;
  align-items: center;
  gap: 8px;
  margin-bottom: 12px;
  flex-wrap: wrap;
}
.asset-type-title {
  font-weight: 500;
  flex: 1;
}
.asset-type-title--video::before {
  content: '▶ ';
  color: #1677ff;
}
.asset-type-title--image::before {
  content: '🖼 ';
}
.asset-type-title--carousel::before {
  content: '▦ ';
  color: #1677ff;
}
.asset-empty {
  padding: 12px;
  color: #8c8c8c;
  font-size: 13px;
  background: #fafafa;
  border-radius: 6px;
}
.asset-cards {
  display: flex;
  flex-direction: column;
  gap: 16px;
}
.material-asset-card {
  display: flex;
  gap: 16px;
  padding: 12px;
  border: 1px solid #f0f0f0;
  border-radius: 8px;
  background: #fff;
}
.material-asset-thumb {
  flex-shrink: 0;
  width: 120px;
  height: 120px;
  border-radius: 6px;
  overflow: hidden;
  background: #f5f5f5;
  display: flex;
  align-items: center;
  justify-content: center;
}
.thumb-media {
  width: 100%;
  height: 100%;
  object-fit: cover;
}
.thumb-placeholder {
  font-size: 12px;
  color: #999;
  padding: 8px;
  text-align: center;
}
.material-asset-body {
  flex: 1;
  min-width: 0;
}
.material-file-name {
  font-size: 13px;
  color: #262626;
  margin-bottom: 8px;
  word-break: break-all;
}
.material-asset-actions {
  display: flex;
  flex-wrap: wrap;
  gap: 8px;
  margin: 8px 0 12px;
}
.material-asset-fields .field-label {
  font-size: 12px;
  color: #666;
  margin: 8px 0 4px;
}
.material-asset-fields .field-label:first-child {
  margin-top: 0;
}
.material-copy-collapse {
  margin-top: 8px;
}
.mt8 {
  margin-top: 8px;
}
.material-picker-toolbar {
  margin-bottom: 12px;
}
.material-picker-grid {
  display: grid;
  grid-template-columns: repeat(auto-fill, minmax(120px, 1fr));
  gap: 12px;
  max-height: 420px;
  overflow-y: auto;
}
.material-picker-item {
  border: 1px solid #f0f0f0;
  border-radius: 8px;
  padding: 8px;
  cursor: pointer;
  text-align: left;
  background: #fff;
  transition: border-color 0.2s;
}
.material-picker-item:hover {
  border-color: #1677ff;
}
.material-picker-thumb-wrap {
  height: 88px;
  border-radius: 4px;
  overflow: hidden;
  background: #f5f5f5;
  display: flex;
  align-items: center;
  justify-content: center;
}
.material-picker-thumb {
  width: 100%;
  height: 100%;
  object-fit: cover;
}
.material-picker-thumb.fallback {
  font-size: 11px;
  color: #999;
  display: flex;
  align-items: center;
  justify-content: center;
}
.material-picker-name {
  margin-top: 6px;
  font-size: 12px;
  color: #595959;
  overflow: hidden;
  text-overflow: ellipsis;
  white-space: nowrap;
}
.bulk-modal-toolbar {
  display: flex;
  align-items: flex-start;
  gap: 12px;
  flex-wrap: wrap;
  margin-bottom: 16px;
}
.bulk-dd-caret {
  margin-left: 4px;
  font-size: 10px;
  opacity: 0.65;
}
.bulk-paste-fields {
  display: flex;
  flex-wrap: wrap;
  align-items: center;
  gap: 8px;
  flex: 1;
  min-width: 200px;
}
.bulk-table-scroll {
  width: 100%;
  overflow-x: auto;
  max-height: min(60vh, 520px);
  overflow-y: auto;
}
.bulk-table {
  width: 100%;
  min-width: 1100px;
  border-collapse: collapse;
  font-size: 13px;
}
.bulk-table th,
.bulk-table td {
  border: 1px solid #f0f0f0;
  padding: 8px;
  vertical-align: top;
}
.bulk-table th {
  background: #fafafa;
  font-weight: 500;
  text-align: left;
  white-space: nowrap;
}
.bulk-col-check {
  width: 40px;
  text-align: center;
}
.bulk-col-binding {
  min-width: 200px;
}
.link-url-toolbar {
  margin-top: 8px;
}
.preview-link {
  font-size: 13px;
}
.url-param-tags {
  display: flex;
  flex-wrap: wrap;
  align-items: center;
  gap: 8px;
  margin-top: 10px;
}
.url-param-tags-more {
  padding-left: 0;
}
.url-param-label {
  font-size: 12px;
  color: #666;
  margin-right: 4px;
}
.multilang-error {
  color: #ff4d4f;
  font-size: 13px;
  margin-top: 8px;
}
.cg-save-row {
  margin-top: 16px;
  padding-top: 8px;
}
.existing-cg-toolbar {
  display: flex;
  align-items: center;
  gap: 12px;
  flex-wrap: wrap;
  margin-bottom: 0;
}
.existing-cg-hint {
  margin-bottom: 12px;
}
</style>
