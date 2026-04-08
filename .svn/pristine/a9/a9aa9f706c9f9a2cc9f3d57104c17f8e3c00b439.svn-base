<template>
  <page-container :show-page-header="false" title="素材库">
    <div class="material-library-container">
      <!-- 左侧文件夹树 -->
      <div class="folder-sidebar">
        <a-input v-model:value="folderSearchText" :placeholder="t('搜索文件夹')" prefix="Q" class="folder-search" />
        <div class="folder-tree">
          <tree class="folder-ant-tree" :tree-data="combinedTreeData" draggable
            :field-names="{ title: 'name', key: 'id', children: 'children' }" :selected-keys="selectedTreeKeys"
            :expanded-keys="combinedExpandedKeys" block-node :show-line="{ showLeafIcon: false }"
            :switcher-icon="renderTreeSwitcherIcon" @select="handleTreeSelect" @expand="handleCombinedTreeExpand"
            @drop="onTreeDrop" @dragenter="handleTreeDragEnter" @dragend="handleTreeDragEnd" :allow-drop="allowDrop">
            <template #title="{ dataRef }">
              <div v-if="dataRef.isVirtualRoot" class="folder-header folder-header-virtual">
                <component :is="dataRef.icon" class="folder-header-virtual-icon" />
                <span class="folder-header-virtual-title">{{ dataRef.name }}</span>
                <span v-if="dataRef.count !== undefined" class="folder-count">{{ dataRef.count }}</span>
                <a-button v-if="dataRef.createType" type="link" size="small"
                  @click.stop="showCreateLibraryModal(dataRef.createType)">
                  {{ t('创建') }}
                </a-button>
              </div>
              <a-dropdown :trigger="['contextmenu']">
                <div class="folder-tree-title-row" :class="{
                  'drop-target-empty-folder': isDropTargetEmptyFolder(dataRef),
                  'drop-target-inside-folder': isDropTargetInside(dataRef),
                  'drop-target-gap-folder': isDropTargetGap(dataRef),
                }">
                  <span class="folder-tree-folder-icon-wrap">
                    <folder-filled class="folder-tree-folder-icon" />
                  </span>
                  <span class="folder-tree-title-text" :title="String(dataRef?.name || '')"
                    @click.stop="handleFolderTitleClick(dataRef)">
                    {{ dataRef?.name }}
                  </span>
                  <span v-if="isDropTargetInside(dataRef)" class="folder-drop-hint folder-drop-hint-inside">
                    {{ t('放入') }}
                  </span>
                  <span v-else-if="isDropTargetGap(dataRef)" class="folder-drop-hint folder-drop-hint-gap">
                    {{ t('与同级') }}
                  </span>
                  <div class="folder-tree-right">
                    <a-popconfirm
                      v-if="dataRef?.canDelete !== false && !dataRef?.isEmptyTip"
                      :title="t('确定删除该文件夹？删除后该目录下素材将不可见。')"
                      :ok-text="t('确定')"
                      :cancel-text="t('取消')"
                      @confirm.stop="deleteFolder(String(dataRef?.id))"
                    >
                      <a class="folder-tree-delete">
                        {{ t('删除') }}
                      </a>
                    </a-popconfirm>
                    <div v-if="(dataRef.subfolder_count > 0) || (dataRef.material_count > 0)" class="folder-tree-metrics">
                      <span v-if="dataRef.subfolder_count > 0" class="folder-tree-count metric-item">
                        <folder-filled class="metric-icon" />
                        <span class="metric-value">{{ dataRef.subfolder_count }}</span>
                      </span>
                      <span v-if="dataRef.material_count > 0" class="folder-tree-material metric-item">
                        <file-image-outlined class="metric-icon" />
                        <span class="metric-value">{{ dataRef.material_count }}</span>
                      </span>
                    </div>
                  </div>
                </div>
                <template #overlay>
                  <a-menu v-if="!dataRef?.isEmptyTip && !dataRef?.isVirtualRoot" @click="(info: any) => handleFolderContextMenuClick(info?.key, dataRef)">
                    <a-menu-item key="create-child">
                      {{ t('新建子文件夹') }}
                    </a-menu-item>
                    <a-menu-item key="upload-material">
                      {{ t('上传素材') }}
                    </a-menu-item>
                    <a-menu-item v-if="dataRef?.canDelete !== false" key="delete" danger>
                      {{ t('删除文件夹') }}
                    </a-menu-item>
                  </a-menu>
                </template>
              </a-dropdown>
            </template>
          </tree>
        </div>
        <div class="storage-info">
          <span>{{ storageUsed }}TB / {{ storageTotal }}TB</span>
        </div>
      </div>

      <!-- 主内容区域 -->
      <div class="main-content">
        <!-- 面包屑 -->
        <div class="breadcrumb-bar">
          <div class="breadcrumb">
            <span class="breadcrumb-label">{{ t('位置') }}:</span>
            <a-space size="small" class="breadcrumb-items">
              <template v-for="(item, idx) in currentBreadcrumbItems" :key="`${item.folderId}-${idx}`">
                <span v-if="idx === currentBreadcrumbItems.length - 1" class="breadcrumb-current">{{ item.label }}</span>
                <a v-else class="breadcrumb-link" @click.prevent="handleBreadcrumbNavigate(item.folderId)">{{ item.label }}</a>
                <span v-if="idx < currentBreadcrumbItems.length - 1" class="breadcrumb-sep">/</span>
              </template>
            </a-space>
          </div>
          <a-input v-model:value="globalSearchText" :placeholder="t('请输入名称/Local ID/文件夹/备注')" class="global-search"
            allow-clear @pressEnter="handleGlobalSearch">
            <template #prefix>
              <search-outlined />
            </template>
          </a-input>
        </div>

        <!-- 筛选区域 -->
        <div class="page-section section-filter">
          <div class="section-title">{{ t('筛选') }}</div>
          <div class="filter-left">
            <div class="filter-grid">
              <a-select v-if="showFilterField('materialGroupId')" v-model:value="filters.materialGroupId"
                :placeholder="t('素材组')" class="filter-field" allow-clear @change="handleFilterChange">
                <a-select-option v-for="g in materialGroups" :key="g.id" :value="g.id">
                  {{ g.name }}
                </a-select-option>
              </a-select>
              <AdvancedSearchSelect
                v-if="showFilterField('tags')"
                v-model="tagSelectedValues"
                :categories="tagCategories"
                :items="tagItems"
                :placeholder="t('标签')"
                class="filter-field"
                @change="handleFilterChange"
              />
              <AdvancedSearchSelect
                v-if="showFilterField('designer')"
                v-model="designerSelectedValues"
                :categories="designerCategories"
                :items="designerItems"
                :placeholder="t('设计师')"
                class="filter-field"
                @change="handleFilterChange"
              />
              <AdvancedSearchSelect
                v-if="showFilterField('creator')"
                v-model="creatorSelectedValues"
                :categories="creatorCategories"
                :items="creatorItems"
                :placeholder="t('创意人')"
                class="filter-field"
                @change="handleFilterChange"
              />
              <AdvancedSearchSelect
                v-if="showFilterField('materialType')"
                v-model="materialTypeSelectedValues"
                :categories="materialTypeCategories"
                :items="materialTypeItems"
                :placeholder="t('类型')"
                :showLogicSelector="false"
                class="filter-field"
                @change="handleFilterChange"
              />
              <AdvancedSearchSelect
                v-if="showFilterField('sizeLevel')"
                v-model="sizeLevelSelectedValues"
                :categories="sizeLevelCategories"
                :items="sizeLevelItems"
                :placeholder="t('尺寸')"
                :showLogicSelector="false"
                class="filter-field"
                @change="handleFilterChange"
              />
              <RatingMultiSelect
                v-if="showFilterField('rating')"
                v-model="ratingSelectedValues"
                :placeholder="t('评分')"
                class="filter-field"
                @change="handleFilterChange"
              />
              <SystemTagSelect
                v-if="showFilterField('systemTags')"
                v-model="systemTagSelectedValues"
                v-model:mode="systemTagMode"
                :options="systemTagOptions"
                :placeholder="t('系统标签')"
                class="filter-field"
                @change="handleFilterChange"
              />
              <a-input v-if="showFilterField('source')" v-model:value="filters.source" :placeholder="t('来源')"
                class="filter-field" allow-clear @pressEnter="handleFilterChange" @blur="handleFilterChange" />
              <a-select v-if="showFilterField('auditStatus')" v-model:value="filters.auditStatus"
                :placeholder="t('审核状态')" class="filter-field" allow-clear @change="handleFilterChange">
                <a-select-option value="0">{{ t('待审核') }}</a-select-option>
                <a-select-option value="1">{{ t('审核通过') }}</a-select-option>
                <a-select-option value="2">{{ t('审核拒绝') }}</a-select-option>
              </a-select>
            </div>
            <div class="filter-grid filter-grid-bottom">
              <a-range-picker
                v-if="showFilterField('createTimePreset')"
                v-model:value="filters.createTimeRange"
                class="filter-field"
                :placeholder="[t('上传开始时间'), t('上传结束时间')]"
                :presets="uploadTimeRangePresets"
                @change="handleUploadTimeRangeChange"
              />
              <AdvancedSearchSelect
                v-if="showFilterField('rejectReason')"
                v-model="rejectReasonSelectedValues"
                :categories="rejectReasonCategories"
                :items="rejectReasonItems"
                :placeholder="t('拒审信息')"
                :showLogicSelector="false"
                class="filter-field"
                @change="handleFilterChange"
              />
              <a-select v-if="showFilterField('sortField')" v-model:value="filters.sortField" :placeholder="t('排序信息')"
                class="filter-field" @change="handleFilterChange">
                <a-select-option value="create_time">{{ t('创建时间') }}</a-select-option>
                <a-select-option value="material_name">{{ t('素材名称') }}</a-select-option>
                <a-select-option value="local_id">{{ t('Local ID') }}</a-select-option>
                <a-select-option value="file_format">{{ t('文件类型') }}</a-select-option>
                <a-select-option value="material_type">{{ t('素材类型') }}</a-select-option>
              </a-select>
              <a-select v-if="showFilterField('sortOrder')" v-model:value="filters.sortOrder" :placeholder="t('排序方式')"
                class="filter-field" @change="handleFilterChange">
                <a-select-option value="desc">{{ t('最新优先') }}</a-select-option>
                <a-select-option value="asc">{{ t('最早优先') }}</a-select-option>
              </a-select>
            </div>

            <div class="filter-template-bar filter-actions">
              <a-button :icon="h(FilterOutlined)" @click="handleFilterChange">{{ t('查询') }}</a-button>
              <a-popover v-model:open="filterTemplateModalVisible" trigger="click" placement="bottomLeft"
                overlayClassName="filter-template-popover-overlay" arrowPointAtCenter>
                <template #content>
                  <div class="filter-template-popover-content" @click.stop>
                    <div class="template-checkbox-wrapper">
                      <a-checkbox-group v-model:value="editTemplateFields" class="template-checkbox-grid">
                        <a-checkbox v-for="opt in filterFieldOptions" :key="opt.key" :value="opt.key">
                          {{ opt.label }}
                        </a-checkbox>
                      </a-checkbox-group>
                    </div>
                    <div class="template-popover-actions">
                      <a-button size="small" @click="closeFilterTemplateModal">{{ t('取消') }}</a-button>
                      <a-button size="small" type="primary" @click="handleSaveCustomFilterTemplate">{{ t('保存')
                        }}</a-button>
                    </div>
                  </div>
                </template>

                <a-button>{{ t('自定义筛选模板') }}</a-button>
              </a-popover>
              <a-button @click="resetCustomFilterTemplateToDefault">{{ t('恢复默认') }}</a-button>
            </div>
          </div>

        </div>

        <!-- 设置区域 -->
        <div class="page-section section-setting">
          <div class="section-title">{{ t('设置') }}</div>
          <div class="filter-right">
            <a-checkbox v-model:checked="showSubfolders" @change="handleFilterChange">{{ t('显示子文件夹素材') }}</a-checkbox>
            <a-checkbox v-model:checked="showDeliveryData" @change="handleFilterChange">{{ t('显示投放数据') }}</a-checkbox>
            <a-range-picker v-if="showDeliveryData" v-model:value="statisticsDateRange" style="width: 260px"
              :allowClear="false" @change="handleFilterChange" />
            <a-select v-model:value="viewMode" style="width: 120px">
              <a-select-option value="list">{{ t('视图: 列表') }}</a-select-option>
              <a-select-option value="grid">{{ t('视图: 网格') }}</a-select-option>
            </a-select>
          </div>
        </div>

        <!-- 操作区域 -->
        <div class="page-section section-action">
          <div class="section-title">{{ t('操作') }}</div>
          <div class="action-bar">
            <a-dropdown>
              <template #overlay>
                <a-menu>
                  <a-menu-item @click="showUploadModal('file')">{{ t('上传素材') }}</a-menu-item>
                  <a-menu-item @click="showUploadModal('folder')">{{ t('上传文件夹') }}</a-menu-item>
                  <a-menu-item>{{ t('批量上传') }}</a-menu-item>
                </a-menu>
              </template>
              <a-button type="primary" :icon="h(UploadOutlined)">
                {{ t('上传') }}
              </a-button>
            </a-dropdown>
            <a-button :icon="h(FolderAddOutlined)" @click="() => showCreateFolderModal()">
              {{ t('新建文件夹') }}
            </a-button>
            <a-dropdown>
              <template #overlay>
                <a-menu>
                  <a-menu-item key="batch-delete">
                    <a-popconfirm
                      :title="t('确定批量删除选中素材？')"
                      :ok-text="t('确定')"
                      :cancel-text="t('取消')"
                      @confirm="openBatchDeleteConfirm"
                    >
                      <span>{{ t('批量删除') }}</span>
                    </a-popconfirm>
                  </a-menu-item>
                  <a-menu-item @click="openBatchMoveModal">{{ t('批量移动') }}</a-menu-item>
                  <a-menu-item @click="openBatchTagModal">{{ t('批量设置标签') }}</a-menu-item>
                </a-menu>
              </template>
              <a-button>
                {{ t('批量操作') }}
              </a-button>
            </a-dropdown>
            <div class="action-right">
              <a-button :icon="h(ReloadOutlined)" @click="reloadTable">{{ t('刷新') }}</a-button>
              <a-dropdown>
                <template #overlay>
                  <a-menu>
                    <a-menu-item @click="handleExport('xlsx')">{{ t('导出Excel') }}</a-menu-item>
                    <a-menu-item @click="handleExport('csv')">{{ t('导出CSV') }}</a-menu-item>
                  </a-menu>
                </template>
                <a-button>{{ t('导出') }}</a-button>
              </a-dropdown>
              <a-button v-if="showDeliveryData" type="link" :loading="autoUpdateLoading"
                @click="handleAutoUpdateXmpTags">
                {{ t('自动更新 XMP 标签') }}
              </a-button>
              <a-button type="link" @click="openMediaSyncModal">
                {{ t('媒体同步') }}
              </a-button>
              <a-dropdown :trigger="['hover']" placement="bottomRight">
                <a-button :icon="h(SettingOutlined)">
                  {{ t('自定义列') }}
                </a-button>
                <template #overlay>
                  <a-menu>
                    <a-menu-item @click="openColumnSettingsModal()">
                      {{ t('选择自定义列') }}
                    </a-menu-item>
                    <a-menu-divider />
                    <a-menu-item-group :title="t('常用自定义列')">
                      <a-menu-item v-if="columnTemplates.length === 0" disabled>
                        {{ t('暂无模板') }}
                      </a-menu-item>
                      <a-menu-item v-for="tpl in columnTemplates" :key="tpl.id" @click="applyColumnTemplate(tpl.id)">
                        {{ tpl.name }}
                      </a-menu-item>
                    </a-menu-item-group>
                  </a-menu>
                </template>
              </a-dropdown>
            </div>
          </div>
        </div>

        <!-- 列表内容区域 -->
        <div class="page-section section-list">
          <div class="section-title">{{ t('列表') }}</div>
          <a-table
            v-if="viewMode === 'list'"
            :loading="loading"
            :columns="visibleColumns"
            :data-source="tableData"
            :pagination="pagination"
            :row-selection="rowSelection"
            :row-key="record => record.id"
            :scroll="{ x: 2800 }"
            @change="handleTableChange"
          >
            <template #bodyCell="{ column, record }">
              <template v-if="column.dataIndex === 'material'">
                <div class="material-cell">
                  <folder-outlined v-if="record.type === 'folder'" class="material-icon" />
                  <img v-else-if="record.type === 'image'" :src="record.thumbnail" class="material-thumbnail" />
                  <play-circle-outlined v-else-if="record.type === 'video'" class="material-icon" />
                  <file-outlined v-else class="material-icon" />
                  <div class="material-main">
                    <span class="material-name" :class="{ 'material-name--clamp2': record.type !== 'folder' }"
                      @click.stop="record.type === 'folder' && selectFolder(record.folderSelfId ?? record.id)">
                      {{ record.name }}
                    </span>
                    <span v-if="record.type === 'folder'" class="material-count">
                      <span v-if="(record.subfolderCount ?? 0) > 0" class="count-item">
                        {{ t('子文件夹') }}: {{ record.subfolderCount }}
                      </span>
                      <span class="count-item">
                        {{ t('素材数') }}: {{ record.folderMaterialCount ?? 0 }}
                      </span>
                    </span>
                  </div>
                </div>
              </template>
              <template v-else-if="column.dataIndex === 'localId'">
                <span>{{ record.localId ?? '-' }}</span>
              </template>
              <template v-else-if="column.dataIndex === 'tags'">
                <template v-if="record.type === 'folder'">
                  <div>
                    <template v-if="Array.isArray(record.tags) && record.tags.length">
                      <a-tag v-for="tag in record.tags" :key="tag" size="small">{{ tag }}</a-tag>
                    </template>
                    <span v-else>-</span>
                  </div>
                </template>
                <template v-else>
                  <div
                    class="editable-cell"
                    :class="{ 'editable-cell--disabled': !isCellEditableRecord(record) }"
                    @click.stop="openCellEditor('tags', record)"
                  >
                    <template v-if="Array.isArray(record.tags) && record.tags.length">
                      <a-tag v-for="tag in record.tags" :key="tag" size="small">{{ tag }}</a-tag>
                    </template>
                    <span v-else class="editable-cell-placeholder">{{ t('点击编辑') }}</span>
                  </div>
                </template>
              </template>
              <template v-else-if="column.dataIndex === 'folderId'">
                <span>{{ getFolderName(record.folderId) }}</span>
              </template>
              <template v-else-if="column.dataIndex === 'designerId'">
                <template v-if="record.type === 'folder'">
                  <span>{{ getDesignerName(record.designerId) }}</span>
                </template>
                <template v-else>
                  <span
                    class="editable-cell"
                    :class="{ 'editable-cell--disabled': !isCellEditableRecord(record) }"
                    @click.stop="openCellEditor('designerId', record)"
                  >
                    {{ getDesignerName(record.designerId) === '-' ? t('点击编辑') : getDesignerName(record.designerId) }}
                  </span>
                </template>
              </template>
              <template v-else-if="column.dataIndex === 'creatorId'">
                <template v-if="record.type === 'folder'">
                  <span>{{ getCreatorName(record.creatorId) }}</span>
                </template>
                <template v-else>
                  <span
                    class="editable-cell"
                    :class="{ 'editable-cell--disabled': !isCellEditableRecord(record) }"
                    @click.stop="openCellEditor('creatorId', record)"
                  >
                    {{ getCreatorName(record.creatorId) === '-' ? t('点击编辑') : getCreatorName(record.creatorId) }}
                  </span>
                </template>
              </template>
              <template v-else-if="column.dataIndex === 'productionCost'">
                <span
                  class="editable-cell"
                  :class="{ 'editable-cell--disabled': !isCellEditableRecord(record) }"
                  @click.stop="openCellEditor('productionCost', record)"
                >
                  {{ formatEditableProductionCost(record.productionCost) }}
                </span>
              </template>
              <template v-else-if="column.dataIndex === 'type'">
                <span>{{ formatMaterialType(record.type) }}</span>
              </template>
              <template v-else-if="column.dataIndex === 'sizeLevel'">
                <span>{{ formatSizeLevel(record.width, record.height) }}</span>
              </template>
              <template v-else-if="column.dataIndex === 'duration'">
                <span>{{ formatDurationSeconds(record.duration) }}</span>
              </template>
              <template v-else-if="column.dataIndex === 'createTime'">
                <span>{{ formatCreateTime(record.createTime) }}</span>
              </template>
              <template v-else-if="column.dataIndex === 'size'">
                <span>{{ formatSize(record.width, record.height) }}</span>
              </template>
              <template v-else-if="column.dataIndex === 'materialCount'">
                <span>{{ record.materialCount ?? '-' }}</span>
              </template>
              <template v-else-if="column.dataIndex === 'auditStatus'">
                <span>{{ formatAuditStatus(record.auditStatus, record.rejectReason) }}</span>
              </template>
              <template v-else-if="column.dataIndex === 'source'">
                <span>{{ formatSource(record.source) }}</span>
              </template>
              <template v-else-if="column.dataIndex === 'materialGroupId'">
                <span>{{ formatMaterialGroup(record.materialGroupId) }}</span>
              </template>
              <template v-else-if="column.dataIndex === 'shape'">
                <span>{{ formatShape(record.width, record.height) }}</span>
              </template>
              <template v-else-if="column.dataIndex === 'rejectReason'">
                <span>{{ record.rejectReason ?? '-' }}</span>
              </template>
              <template v-else-if="column.dataIndex === 'rating'">
                <span>{{ record.rating ?? '-' }}</span>
              </template>
              <template v-else-if="column.dataIndex === 'remarks'">
                <template v-if="record.type === 'folder'">
                  <span>{{ record.remarks || '-' }}</span>
                </template>
                <template v-else>
                  <span
                    class="editable-cell"
                    :class="{ 'editable-cell--disabled': !isCellEditableRecord(record) }"
                    @click.stop="openCellEditor('remarks', record)"
                  >
                    {{ record.remarks ? record.remarks : t('点击编辑') }}
                  </span>
                </template>
              </template>
              <template v-else-if="column.dataIndex === 'playCount'">
                <span>{{ formatNumberOrDash(record.playCount) }}</span>
              </template>
              <template v-else-if="column.dataIndex === 'play100Count'">
                <span>{{ formatNumberOrDash(record.play100Count) }}</span>
              </template>
              <template v-else-if="column.dataIndex === 'completionRate'">
                <span>{{ formatPercentOrDash(record.completionRate) }}</span>
              </template>
              <template v-else-if="column.dataIndex === 'play75Count'">
                <span>{{ formatNumberOrDash(record.play75Count) }}</span>
              </template>
              <template v-else-if="column.dataIndex === 'play75Rate'">
                <span>{{ formatPercentOrDash(record.play75Rate) }}</span>
              </template>
              <template v-else-if="column.dataIndex === 'play50Count'">
                <span>{{ formatNumberOrDash(record.play50Count) }}</span>
              </template>
              <template v-else-if="column.dataIndex === 'play50Rate'">
                <span>{{ formatPercentOrDash(record.play50Rate) }}</span>
              </template>
              <template v-else-if="column.dataIndex === 'play25Count'">
                <span>{{ formatNumberOrDash(record.play25Count) }}</span>
              </template>
              <template v-else-if="column.dataIndex === 'play25Rate'">
                <span>{{ formatPercentOrDash(record.play25Rate) }}</span>
              </template>
              <template v-else-if="column.dataIndex === 'operation'">
                <a-space v-if="record.type !== 'folder'">
                  <a-button type="link" size="small" @click="editMaterial(record)">
                    {{ t('编辑') }}
                  </a-button>
                  <a-popconfirm
                    :title="t('确定删除该素材？')"
                    :ok-text="t('确定')"
                    :cancel-text="t('取消')"
                    @confirm="deleteMaterial(record)"
                  >
                    <a-button type="link" size="small">
                      {{ t('删除') }}
                    </a-button>
                  </a-popconfirm>
                  <a-button v-if="record.auditStatus === 0" type="link" size="small"
                    @click="handleAuditApprove(record)">
                    {{ t('通过') }}
                  </a-button>
                  <a-button v-if="record.auditStatus === 0" type="link" size="small" @click="openAuditReject(record)">
                    {{ t('拒绝') }}
                  </a-button>
                  <a-button type="link" size="small" @click="openUsagesModal(record)">
                    {{ t('使用记录') }}
                  </a-button>
                </a-space>
                <span v-else>-</span>
              </template>
              <template v-else>
                <span>{{ record?.[String(column.dataIndex)] ?? '-' }}</span>
              </template>
            </template>
          </a-table>
          <div v-else class="materials-grid-wrapper">
            <a-spin :spinning="loading">
              <div v-if="tableData.length" class="materials-grid">
                <div v-for="record in tableData" :key="record.id" class="grid-card"
                  :class="{ 'grid-card--folder': record.type === 'folder', 'grid-card--selected': isGridSelected(record) }">
                  <div class="grid-card-check">
                    <a-checkbox v-if="record.type !== 'folder'" :checked="isGridSelected(record)"
                      @change="(e: any) => updateGridSelection(record, e?.target?.checked)" />
                  </div>
                  <div class="grid-card-body"
                    @click="record.type === 'folder' && selectFolder(record.folderSelfId ?? record.id)">
                    <template v-if="record.type === 'folder'">
                      <div class="grid-folder-cover">
                        <folder-filled />
                      </div>
                      <div class="grid-title" :title="String(record.name || '')">{{ record.name || '-' }}</div>
                      <div class="grid-subtitle">
                        {{ t('素材数') }}: {{ record.folderMaterialCount ?? 0 }}
                        <template v-if="(record.subfolderCount ?? 0) > 0">
                          · {{ t('子文件夹') }}: {{ record.subfolderCount }}
                        </template>
                      </div>
                    </template>
                    <template v-else>
                      <div class="grid-material-cover">
                        <img v-if="record.thumbnail" :src="record.thumbnail" class="grid-material-image" />
                        <div v-else class="grid-material-empty">
                          <picture-outlined class="grid-empty-picture-icon" />
                          <exclamation-circle-filled class="grid-empty-warning-icon" />
                        </div>
                      </div>
                      <div class="grid-title grid-title--clamp1" :title="String(record.name || '')">
                        {{ record.name || '-' }}
                      </div>
                      <div class="grid-subtitle">localID: {{ record.localId ?? '-' }}</div>
                    </template>
                  </div>
                  <div v-if="record.type !== 'folder'" class="grid-card-footer">
                    <a-button type="link" size="small" class="grid-add-tag-btn">{{ t('添加标签') }}</a-button>
                  </div>
                </div>
              </div>
              <a-empty v-else />
            </a-spin>
            <div class="materials-grid-pagination">
              <pagination :current="pagination.current" :page-size="pagination.pageSize" :total="pagination.total"
                :show-size-changer="pagination.showSizeChanger" :page-size-options="pagination.pageSizeOptions"
                :show-total="pagination.showTotal" @change="handleGridPageChange" />
            </div>
          </div>
        </div>
      </div>
    </div>

    <!-- 上传素材/文件夹弹窗 -->
    <upload-material-modal v-model:open="uploadModalVisible" :folder-id="selectedFolder"
      :folder-name="selectedFolderName" :breadcrumb="currentPath" :breadcrumb-items="currentBreadcrumbItems"
      :upload-mode="currentUploadMode" @navigate-breadcrumb="handleBreadcrumbNavigate" @success="handleUploadSuccess" />

    <!-- 新建文件夹弹窗 -->
    <create-folder-modal v-model:open="createFolderModalVisible" :parent-folder-id="createFolderParentId"
      :sibling-names="createFolderSiblingNames"
      @success="handleCreateFolderSuccess" />

    <!-- 新建素材库弹窗 -->
    <create-library-modal v-model:open="createLibraryModalVisible" :library-type="createLibraryType"
      @success="handleCreateLibrarySuccess" />

    <!-- 批量设置标签弹窗 -->
    <a-modal v-model:open="batchTagModalVisible" :title="t('批量编辑标签')" width="900px" :confirm-loading="batchSaving"
      @ok="handleBatchTagSave" @cancel="closeBatchTagModal">
      <!-- 顶部信息行：已选数量、选择编辑的标签 -->
      <div class="batch-tag-header">
        <div class="batch-tag-row">
          <span class="batch-tag-label">{{ t('已选择') }}:</span>
          <span class="batch-tag-value">{{ selectedRows.length }} {{ t('条素材') }}</span>
        </div>

        <div class="batch-tag-row">
          <span class="batch-tag-label">{{ t('选择编辑的标签') }}:</span>
          <a-radio-group v-model:value="editScope">
            <a-radio value="all">{{ t('全部标签') }}</a-radio>
            <a-radio value="partial">{{ t('指定标签') }}</a-radio>
          </a-radio-group>
        </div>

        <div class="batch-tag-row">
          <span class="batch-tag-label">{{ t('设置方式') }}:</span>
          <a-radio-group v-model:value="batchMode" button-style="solid">
            <a-radio-button value="manual">{{ t('统一设置') }}</a-radio-button>
            <a-radio-button value="single">{{ t('单独设置') }}</a-radio-button>
            <a-radio-button value="ai">{{ t('智能识别') }}</a-radio-button>
          </a-radio-group>
        </div>

        <div class="batch-tag-row">
          <span class="batch-tag-label">{{ t('将标签设置为') }}:</span>
          <a-radio-group v-model:value="applyMode">
            <a-radio value="assign">{{ t('指定') }}</a-radio>
            <a-radio value="clear">{{ t('清空') }}</a-radio>
          </a-radio-group>
        </div>
      </div>

      <!-- 统一设置样式：左右结构，左侧选择标签，右侧显示已选标签 -->
      <template v-if="batchMode === 'manual'">
        <div class="batch-tag-body">
          <!-- 左侧：下拉选择标签 -->
          <div class="batch-tag-left">
            <a-select v-model:value="manualBatchTags" :disabled="applyMode === 'clear'" mode="multiple"
              style="width: 100%" :placeholder="t('输入搜索关键词')" :options="tagOptions" :filter-option="filterTagOption"
              show-search />
          </div>

          <!-- 右侧：已选标签列表 -->
          <div class="batch-tag-right">
            <div class="batch-tag-right-header">
              <span>{{ t('已选') }} {{ manualBatchTags.length }} {{ t('个') }}</span>
              <a-button type="link" size="small" :disabled="!manualBatchTags.length" @click="clearManualTags">
                {{ t('清除') }}
              </a-button>
            </div>
            <div class="batch-tag-selected">
              <a-tag v-for="tag in manualBatchTags" :key="tag" closable @close="removeManualTag(tag)">
                {{ tag }}
              </a-tag>
              <span v-if="!manualBatchTags.length && applyMode !== 'clear'" class="batch-tag-empty">
                {{ t('请选择左侧的标签') }}
              </span>
              <span v-if="applyMode === 'clear'" class="batch-tag-empty">
                {{ t('将清空所选素材的标签') }}
              </span>
            </div>
          </div>
        </div>
      </template>

      <!-- 单独设置：表格逐条手动设置标签（无识别按钮） -->
      <template v-else-if="batchMode === 'single'">
        <div class="batch-single-tip">
          {{ t('请为每条素材单独选择或编辑标签') }}
        </div>
        <a-table :data-source="editableBatchRows" :columns="batchColumns" :pagination="false" size="small" row-key="id">
          <template #bodyCell="{ column, record }">
            <template v-if="column.dataIndex === 'tags'">
              <a-select v-model:value="record.tags" mode="multiple" style="width: 100%" allow-clear>
                <a-select-option v-for="tag in tags" :key="tag.id" :value="tag.name">
                  {{ tag.name }}
                </a-select-option>
              </a-select>
            </template>
          </template>
        </a-table>
      </template>

      <!-- 智能识别：先识别再表格逐条调整 -->
      <template v-else>
        <div class="batch-ai-tip">
          <a-button type="primary" size="small" :loading="aiLoading" @click="runAiRecognize">
            {{ aiLoaded ? t('重新识别') : t('开始识别') }}
          </a-button>
          <span class="batch-ai-desc">
            {{ t('将根据素材名称等信息自动推荐标签,可手动调整') }}
          </span>
        </div>
        <a-table :data-source="editableBatchRows" :columns="batchColumns" :pagination="false" size="small" row-key="id">
          <template #bodyCell="{ column, record }">
            <template v-if="column.dataIndex === 'tags'">
              <a-select v-model:value="record.tags" mode="multiple" style="width: 100%" allow-clear>
                <a-select-option v-for="tag in tags" :key="tag.id" :value="tag.name">
                  {{ tag.name }}
                </a-select-option>
              </a-select>
            </template>
          </template>
        </a-table>
      </template>
    </a-modal>

    <!-- 单元格编辑弹窗 -->
    <a-modal
      v-model:open="cellEditModalVisible"
      :title="cellEditModalTitle"
      width="760px"
      :confirm-loading="cellEditSaving"
      @ok="handleCellEditConfirm"
      @cancel="closeCellEditModal"
    >
      <template v-if="cellEditType === 'productionCost'">
        <div class="cell-edit-cost">
          <a-input v-model:value="cellEditCostValue" :placeholder="t('请输入制作费用')" />
          <span class="cell-edit-cost-suffix">USD</span>
        </div>
      </template>

      <template v-else-if="cellEditType === 'designerId' || cellEditType === 'creatorId'">
        <div class="cell-edit-selector">
          <div class="cell-edit-selector-toolbar">
            <a-tag color="blue">{{ t('手动选择') }}</a-tag>
          </div>
          <a-input v-model:value="cellEditPersonKeyword" :placeholder="t('搜索姓名')" allow-clear />
          <div class="cell-edit-selector-body">
            <div class="cell-edit-selector-left">
              <tree :tree-data="cellEditPersonTreeData" :expanded-keys="['person-root']" :selectable="true"
                :selected-keys="cellEditPersonSelectedId ? [cellEditPersonSelectedId] : []"
                @select="handleCellEditPersonSelect" />
            </div>
            <div class="cell-edit-selector-right">
              <div class="cell-edit-selected-header">
                <span>{{ t('已选') }} {{ cellEditPersonSelectedId ? 1 : 0 }} {{ t('个') }}</span>
                <a @click="clearCellEditPersonSelection">{{ t('清除') }}</a>
              </div>
              <div class="cell-edit-selected-list">
                <a-tag v-if="cellEditPersonSelectedId">{{ cellEditPersonSelectedName }}</a-tag>
                <span v-else class="cell-edit-empty">{{ t('暂无选中') }}</span>
              </div>
            </div>
          </div>
        </div>
      </template>

      <template v-else-if="cellEditType === 'tags'">
        <div class="cell-edit-selector">
          <a-input v-model:value="cellEditTagKeyword" :placeholder="t('搜索标签')" allow-clear />
          <div class="cell-edit-selector-body">
            <div class="cell-edit-selector-left">
              <tree checkable :tree-data="cellEditTagTreeData" :expanded-keys="['tag-root']"
                :checked-keys="cellEditTagCheckedIds" @check="handleCellEditTagCheck" />
            </div>
            <div class="cell-edit-selector-right">
              <div class="cell-edit-selected-header">
                <span>{{ t('已选') }} {{ cellEditTagCheckedIds.length }} {{ t('个') }}</span>
                <a @click="clearCellEditTagSelection">{{ t('清除') }}</a>
              </div>
              <div class="cell-edit-selected-list">
                <a-tag v-for="id in cellEditTagCheckedIds" :key="id">{{ getTagNameById(id) }}</a-tag>
                <span v-if="!cellEditTagCheckedIds.length" class="cell-edit-empty">{{ t('暂无选中') }}</span>
              </div>
              <div class="cell-edit-hint">{{ t('仅保存当前素材标签，不影响其他素材') }}</div>
            </div>
          </div>
        </div>
      </template>

      <template v-else-if="cellEditType === 'remarks'">
        <a-textarea v-model:value="cellEditRemarkValue" :rows="5" :maxlength="200" :placeholder="t('请输入素材备注')" />
        <div class="cell-edit-remark-count">{{ cellEditRemarkValue.length }}/200</div>
      </template>
    </a-modal>

    <!-- 编辑素材弹窗 -->
    <a-modal v-model:open="editMaterialModalVisible" :title="t('编辑素材')" width="700px" :confirm-loading="editSaving"
      @ok="handleEditMaterialSave" @cancel="closeEditMaterialModal">
      <a-form :model="editFormData" layout="vertical">
        <a-form-item :label="t('素材名称') + ' *'" required>
          <a-input v-model:value="editFormData.material_name" :maxlength="255" />
        </a-form-item>

        <a-form-item :label="t('所属文件夹')">
          <a-select v-model:value="editFormData.folder_id" style="width: 100%" allow-clear>
            <a-select-option v-for="f in folderSelectOptions" :key="f.id" :value="f.id">
              {{ f.name }}
            </a-select-option>
          </a-select>
        </a-form-item>

        <a-form-item :label="t('设计师')">
          <a-select v-model:value="editFormData.designer_id" style="width: 100%" allow-clear>
            <a-select-option v-for="d in designers" :key="d.id" :value="d.id">
              {{ d.name }}
            </a-select-option>
          </a-select>
        </a-form-item>

        <a-form-item :label="t('创意人')">
          <a-select v-model:value="editFormData.creator_id" style="width: 100%" allow-clear>
            <a-select-option v-for="c in creators" :key="c.id" :value="c.id">
              {{ c.name }}
            </a-select-option>
          </a-select>
        </a-form-item>

        <a-form-item :label="t('素材组')">
          <a-select v-model:value="editFormData.material_group_id" style="width: 100%" allow-clear>
            <a-select-option v-for="g in materialGroups" :key="g.id" :value="g.id">
              {{ g.name }}
            </a-select-option>
          </a-select>
        </a-form-item>

        <a-form-item :label="t('备注')">
          <a-textarea v-model:value="editFormData.remarks" :rows="3" :maxlength="500" />
        </a-form-item>

        <a-form-item :label="t('XMP 生命周期阶段')">
          <a-select v-model:value="editFormData.xmp_tag" style="width: 100%" allow-clear>
            <a-select-option v-for="opt in xmpTagOptions" :key="opt.value" :value="opt.value">
              {{ opt.label }}
            </a-select-option>
          </a-select>
        </a-form-item>

        <a-form-item :label="t('Mindworks 禁止下载与导出')">
          <a-checkbox v-model:checked="editFormData.mindworks_locked">
            {{ t('禁止下载/导出') }}
          </a-checkbox>
        </a-form-item>
      </a-form>
    </a-modal>

    <!-- 批量移动弹窗 -->
    <a-modal v-model:open="batchMoveModalVisible" :title="t('批量移动')" width="520px" :confirm-loading="batchMoveSaving"
      @ok="handleBatchMoveConfirm" @cancel="closeBatchMoveModal">
      <a-form :model="batchMoveFormData" layout="vertical">
        <a-form-item :label="t('目标文件夹') + ' *'" required>
          <a-select v-model:value="batchMoveTargetFolderId" style="width: 100%" allow-clear>
            <a-select-option v-for="f in folderSelectOptions" :key="f.id" :value="f.id">
              {{ f.name }}
            </a-select-option>
          </a-select>
        </a-form-item>
        <div class="hint-text">{{ t('将选中素材移动到目标文件夹') }}</div>
      </a-form>
    </a-modal>

    <!-- 审核拒绝弹窗 -->
    <a-modal v-model:open="auditRejectModalVisible" :title="t('审核拒绝')" width="560px"
      :confirm-loading="auditRejectSaving" @ok="handleAuditReject" @cancel="closeAuditRejectModal">
      <a-form :model="auditRejectForm" layout="vertical">
        <a-form-item :label="t('拒绝原因') + ' *'" required>
          <a-textarea v-model:value="auditRejectForm.reject_reason" :rows="4" :maxlength="500" />
        </a-form-item>
      </a-form>
    </a-modal>

    <!-- 使用记录弹窗 -->
    <a-modal v-model:open="usagesModalVisible" :title="t('使用记录')" width="900px" :footer="null"
      @cancel="closeUsagesModal">
      <a-table :data-source="usagesData" :columns="usagesColumns" :pagination="false" row-key="usedAt" size="small" />
    </a-modal>

    <!-- 媒体同步弹窗 -->
    <a-modal v-model:open="mediaSyncModalVisible" :title="t('媒体素材同步')" width="620px"
      :confirm-loading="mediaSyncLoading" @ok="handleStartMediaSync" @cancel="closeMediaSyncModal">
      <a-form :model="mediaSyncForm" layout="vertical">
        <a-form-item :label="t('渠道') + ' *'" required>
          <a-select v-model:value="mediaSyncForm.channel" style="width: 100%">
            <a-select-option value="Meta">Meta</a-select-option>
            <a-select-option value="TikTok">TikTok</a-select-option>
            <a-select-option value="YouTube">YouTube</a-select-option>
          </a-select>
        </a-form-item>
        <a-form-item :label="t('账户ID') + ' *'" required>
          <a-input v-model:value="mediaSyncForm.account_id" type="number" />
        </a-form-item>
        <a-form-item :label="t('同步范围')">
          <div class="hint-text">
            {{ selectedFolder === 'favorites' ? t('将同步全量素材') : t('将同步当前目录下素材') }}
          </div>
        </a-form-item>
      </a-form>

      <div v-if="mediaSyncResult" class="media-sync-result">
        <div>{{ t('sync_id') }}: {{ mediaSyncResult.sync_id }}</div>
        <div>{{ t('状态') }}: {{ mediaSyncResult.sync_status }}</div>
        <div>{{ t('成功数') }}: {{ mediaSyncResult.success_count }}</div>
        <div>{{ t('失败数') }}: {{ mediaSyncResult.fail_count }}</div>
        <div v-if="mediaSyncResult.error_message" style="color: #d4380d; margin-top: 8px;">
          {{ mediaSyncResult.error_message }}
        </div>
      </div>
    </a-modal>

    <!-- 自定义列弹窗（高级版：搜索/全选/反选/拖拽排序/固定列/模板保存） -->
    <a-modal v-model:open="columnSettingsModalVisible" :title="t('自定义列')" width="980px" :footer="null"
      @cancel="closeColumnSettingsModal">
      <div class="column-settings-modal">
        <div class="column-settings-left">
          <a-input v-model:value="columnSearchText" :placeholder="t('请输入列名称')" allow-clear
            class="column-settings-search" />

          <div class="column-settings-groups">
            <div class="col-group">
              <div class="col-group-header">
                <div class="col-group-batch">
                  <a-checkbox :checked="defaultGroupAllChecked" size="small"
                    @change="(e: any) => handleGroupCheckboxChange('default', e?.target?.checked)"></a-checkbox>
                </div>
                <div class="col-group-title">{{ t('属性') }}</div>
              </div>
              <div class="col-group-grid">
                <a-checkbox v-for="opt in propertyColumnOptionsFiltered" :key="opt.key"
                  :checked="columnSettingsDraftKeys.includes(opt.key)"
                  @change="toggleDraftColumn(opt.key, ($event as any).target.checked)">
                  {{ opt.label }}
                </a-checkbox>
              </div>
            </div>
            <div class="col-group">
              <div class="col-group-header">
                <div class="col-group-batch">
                  <a-checkbox :checked="deliveryGroupAllChecked" size="small"
                    @change="(e: any) => handleGroupCheckboxChange('delivery', e?.target?.checked)"></a-checkbox>
                </div>
                <div class="col-group-title">{{ t('数据') }}</div>

              </div>
              <div class="col-group-grid">
                <a-checkbox v-for="opt in dataColumnOptionsFiltered" :key="opt.key"
                  :checked="columnSettingsDraftKeys.includes(opt.key)"
                  @change="toggleDraftColumn(opt.key, ($event as any).target.checked)">
                  {{ opt.label }}
                </a-checkbox>
              </div>
            </div>
          </div>

        </div>

        <div class="column-settings-right">
          <div class="selected-header">
            <div class="selected-title">{{ t('已选') }} {{ orderedDraftKeys.length }} {{ t('列') }}</div>
            <a-button type="link" size="small" @click="clearDraftSelected">{{ t('清除') }}</a-button>
          </div>

          <div class="column-settings-right-scroll">
            <div class="fixed-zone">
              <div class="fixed-title">{{ t('固定列') }}</div>
              <div class="fixed-hint">{{ t('拖动到上方的列将固定显示') }}</div>
              <div class="drop-zone" @dragover.prevent @drop.prevent="handleDropToFixedZone">
                <div v-for="k in fixedLeftDraftKeys" :key="k" class="selected-item"
                  :draggable="!fixedBaseColumnKeys.includes(String(k))"
                  @dragstart="fixedBaseColumnKeys.includes(String(k)) ? null : handleSelectedDragStart(String(k))"
                  @dragend="fixedBaseColumnKeys.includes(String(k)) ? null : handleSelectedDragEnd()" @dragover.prevent
                  @drop.prevent="fixedBaseColumnKeys.includes(String(k)) ? handleDropToFixedZone() : handleSelectedDrop(String(k), 'fixed')">
                  <span v-if="!fixedBaseColumnKeys.includes(String(k))" class="drag-handle">≡</span>
                  <span class="selected-label">{{ getColumnLabelByKey(k) }}</span>
                  <a v-if="!fixedBaseColumnKeys.includes(String(k))" class="selected-remove"
                    @click.prevent.stop="removeFromFixed(k)">×</a>
                </div>
              </div>
            </div>

            <div class="selected-list" @dragover.prevent @drop.prevent="handleDropToSelectedListEnd">
              <div v-for="k in orderedDraftKeys" :key="k" class="selected-item"
                :class="{ 'is-fixed': fixedLeftDraftKeys.includes(k) }" draggable="true"
                @dragstart="handleSelectedDragStart(k)" @dragend="handleSelectedDragEnd" @dragover.prevent
                @drop.prevent="handleSelectedDrop(k, 'list')">
                <span v-if="!fixedBaseColumnKeys.includes(String(k))" class="drag-handle">≡</span>
                <span class="selected-label">{{ getColumnLabelByKey(k) }}</span>
                <a class="selected-remove" @click.prevent.stop="removeDraftColumn(k)">×</a>
              </div>
            </div>
          </div>

        </div>

        <div class="column-settings-footer">
          <div class="save-template-row">
            <a-checkbox v-model:checked="saveAsTemplate">{{ t('保存为常用自定义列') }}</a-checkbox>
            <a-input v-model:value="templateName" :disabled="!saveAsTemplate" :placeholder="t('模板名称')"
              class="template-name-input" />
          </div>
          <div class="modal-actions">
            <a-button @click="closeColumnSettingsModal">{{ t('取消') }}</a-button>
            <a-button type="primary" @click="handleColumnSettingsOkAdvanced">{{ t('确认') }}</a-button>
          </div>
        </div>
      </div>
    </a-modal>
  </page-container>
</template>

<script lang="ts" setup>
import { ref, computed, onMounted, h, watch } from 'vue';
import { useI18n } from 'vue-i18n';
import { useUserStore } from '@/store/user';
import dayjs, { type Dayjs } from 'dayjs';
import { message, Tree } from 'ant-design-vue';
import {
  StarOutlined,
  UserOutlined,
  BankOutlined,
  TeamOutlined,
  RightOutlined,
  DownOutlined,
  FolderFilled,
  FileImageOutlined,
  FolderOutlined,
  SearchOutlined,
  FilterOutlined,
  UploadOutlined,
  FolderAddOutlined,
  ReloadOutlined,
  SettingOutlined,
  PlayCircleOutlined,
  FileOutlined,
  PictureOutlined,
  ExclamationCircleFilled,
} from '@ant-design/icons-vue';
import UploadMaterialModal from './components/upload-material-modal.vue';
import CreateFolderModal from './components/create-folder-modal.vue';
import CreateLibraryModal from './components/create-library-modal.vue';
import AdvancedSearchSelect, {
  type AdvancedSelectCategory,
  type AdvancedSelectItem,
  type ValueLike,
} from './components/advanced-search-select.vue';
import RatingMultiSelect from './components/rating-multi-select.vue';
import SystemTagSelect, { type SystemTagMode, type SystemTagOption } from './components/system-tag-select.vue';
import {
  getMaterialLibraryFolders,
  getMaterialLibraryFolderChildren,
  createMaterialLibraryFolder,
  deleteMaterialLibraryFolder,
  moveMaterialLibraryFolder,
} from '@/api/material-library/folders';

import {
  listMaterialLibraryColumnTemplates,
  saveMaterialLibraryColumnTemplate,
} from '@/api/material-library/column-templates';
import {
  getMaterialLibraryMaterials,
  getMaterialLibraryFavorites,
  updateMaterial,
  updateMaterialProductionCost,
  deleteMaterial as deleteMaterialById,
  batchMaterialActions,
  auditMaterial,
  exportMaterials,
  autoUpdateXmpTags,
  syncMediaMaterials,
  getMediaMaterialsSyncStatus,
  getMaterialUsages,
} from '@/api/material-library/materials';
import {
  getMaterialLibraryDesigners,
  getMaterialLibraryTags,
  getMaterialLibraryCreators,
  getMaterialLibraryRejectReasonOptions,
  getMaterialLibraryMaterialGroups,
  getMaterialLibrarySystemTags,
} from '@/api/material-library/options';

const { t } = useI18n();
const userStore = useUserStore();

type ColumnTemplate = {
  id: string;
  name: string;
  selectedKeys: string[];
  fixedLeftKeys: string[];
};

const COLUMN_TEMPLATES_STORAGE_KEY = 'materialLibraryColumnTemplates_v1';
const columnTemplates = ref<ColumnTemplate[]>([]);

const loadColumnTemplates = async () => {
  // 先 localStorage 兜底，后续尝试拉取后端数据覆盖
  try {
    const raw = localStorage.getItem(COLUMN_TEMPLATES_STORAGE_KEY);
    if (!raw) columnTemplates.value = [];
    else {
      const parsed = JSON.parse(raw);
      const arr = Array.isArray(parsed?.templates) ? parsed.templates : Array.isArray(parsed) ? parsed : [];
      columnTemplates.value = (arr || [])
        .map((x: any) => {
          if (!x || typeof x.name !== 'string') return null;
          const id = x.id === undefined || x.id === null ? String(Date.now()) : String(x.id);
          const selectedKeys = Array.isArray(x.selectedKeys) ? x.selectedKeys.map(String) : [];
          const fixedLeftKeys = Array.isArray(x.fixedLeftKeys) ? x.fixedLeftKeys.map(String) : [];
          return { id, name: x.name, selectedKeys, fixedLeftKeys } as ColumnTemplate;
        })
        .filter(Boolean) as ColumnTemplate[];
    }
  } catch (e) {
    console.warn('loadColumnTemplates localStorage failed:', e);
    columnTemplates.value = [];
  }

  // 后端接口（可选）：失败不影响本地模板功能
  try {
    const res = await listMaterialLibraryColumnTemplates();
    const payload = res?.data ?? res;
    const arr = payload?.templates ?? payload ?? [];
    if (!Array.isArray(arr)) return;

    columnTemplates.value = (arr || [])
      .map((x: any) => {
        if (!x || typeof x.name !== 'string') return null;
        const id = x.id === undefined || x.id === null ? String(Date.now()) : String(x.id);
        const selectedKeys = Array.isArray(x.selectedKeys) ? x.selectedKeys.map(String) : [];
        const fixedLeftKeys = Array.isArray(x.fixedLeftKeys) ? x.fixedLeftKeys.map(String) : [];
        return { id, name: x.name, selectedKeys, fixedLeftKeys } as ColumnTemplate;
      })
      .filter(Boolean) as ColumnTemplate[];

    // 同步落盘
    try {
      localStorage.setItem(COLUMN_TEMPLATES_STORAGE_KEY, JSON.stringify({ templates: columnTemplates.value }));
    } catch {
      // ignore
    }
  } catch (e) {
    // ignore: 后端尚未实现时仍可用 localStorage
  }
};

const persistColumnTemplates = async () => {
  // 先本地落盘，保证前端可用
  try {
    localStorage.setItem(COLUMN_TEMPLATES_STORAGE_KEY, JSON.stringify({ templates: columnTemplates.value }));
  } catch (e) {
    console.warn('persistColumnTemplates localStorage failed:', e);
  }

  // 后端可选：尽最大努力保存
  try {
    const templates = columnTemplates.value || [];
    // 并发保存过多可能影响后端，MVP 这里限制保存前 10 个
    const limited = templates.slice(0, 10);
    await Promise.all(
      limited.map((tpl) =>
        saveMaterialLibraryColumnTemplate({
          id: tpl.id,
          name: tpl.name,
          selectedKeys: tpl.selectedKeys,
          fixedLeftKeys: tpl.fixedLeftKeys,
        }),
      ),
    );
  } catch {
    // ignore
  }
};

// 搜索
const folderSearchText = ref('');
const globalSearchText = ref('');
const DEFAULT_LIBRARY_NAME = '默认素材库';

// 选中的文件夹
const selectedFolder = ref<any>(null);

const findFolderById = (folders: any[], id: any): any => {
  for (const f of folders || []) {
    if (String(f.id) === String(id)) return f;
    const children = f.children || [];
    const found = findFolderById(children, id);
    if (found) return found;
  }
  return null;
};

const findFolderChainById = (folders: any[], id: any, path: any[] = []): any[] | null => {
  for (const f of folders || []) {
    const nextPath = [...path, f];
    if (String(f?.id) === String(id)) return nextPath;
    const found = findFolderChainById(f?.children || [], id, nextPath);
    if (found) return found;
  }
  return null;
};

const getFolderNodeName = (node: any) => {
  return String(node?.name || node?.folder_name || '').trim();
};

const getFolderRootLabel = (folderId: any) => {
  const id = String(folderId ?? '');
  if (!id) return t('我的素材库');
  if (id === 'favorites') return t('我的收藏');

  const inMy = !!findFolderById(myFolders.value || [], id);
  if (inMy) return t('我的素材库');
  const inEnterprise = !!findFolderById(enterpriseFolders.value || [], id);
  if (inEnterprise) return t('企业素材库');
  const inDept = !!findFolderById(departmentFolders.value || [], id);
  if (inDept) return t('我的部门');

  return t('我的素材库');
};

const splitFolderPathParts = (folder: any): string[] => {
  const raw = String(folder?.folder_path || folder?.name || '').trim();
  if (!raw) return [];
  return raw
    .split('/')
    .map((x) => String(x).trim())
    .filter(Boolean);
};

type BreadcrumbItem = { label: string; folderId: any };

const currentBreadcrumbItems = computed<BreadcrumbItem[]>(() => {
  if (selectedFolder.value === 'favorites') {
    const myRootId = (myFolders.value || [])[0]?.id ?? null;
    return [
      { label: t('我的素材库'), folderId: myRootId },
      { label: t('我的收藏'), folderId: 'favorites' },
    ];
  }

  if (!selectedFolder.value) {
    const myRootId = (myFolders.value || [])[0]?.id ?? null;
    return [{ label: t('我的素材库'), folderId: myRootId }];
  }

  const selectedId = String(selectedFolder.value);
  const chainInMy = findFolderChainById(myFolders.value || [], selectedId);
  if (chainInMy && chainInMy.length) {
    const rootId = chainInMy[0]?.id;
    const nodes = chainInMy
      .map((n: any) => ({ label: getFolderNodeName(n), folderId: n?.id }))
      .filter((x: BreadcrumbItem) => !!x.label);
    return [{ label: t('我的素材库'), folderId: rootId }, ...nodes];
  }

  const chainInEnterprise = findFolderChainById(enterpriseFolders.value || [], selectedId);
  if (chainInEnterprise && chainInEnterprise.length) {
    const rootId = chainInEnterprise[0]?.id;
    const nodes = chainInEnterprise
      .map((n: any) => ({ label: getFolderNodeName(n), folderId: n?.id }))
      .filter((x: BreadcrumbItem) => !!x.label);
    return [{ label: t('企业素材库'), folderId: rootId }, ...nodes];
  }

  const chainInDept = findFolderChainById(departmentFolders.value || [], selectedId);
  if (chainInDept && chainInDept.length) {
    const rootId = chainInDept[0]?.id;
    const nodes = chainInDept
      .map((n: any) => ({ label: getFolderNodeName(n), folderId: n?.id }))
      .filter((x: BreadcrumbItem) => !!x.label);
    return [{ label: t('我的部门'), folderId: rootId }, ...nodes];
  }

  const rootLabel = getFolderRootLabel(selectedFolder.value);
  const allFolders = [
    ...(myFolders.value || []),
    ...(enterpriseFolders.value || []),
    ...(departmentFolders.value || []),
  ];
  const folder = findFolderById(allFolders, selectedFolder.value);
  const parts = folder ? splitFolderPathParts(folder) : [];
  if (!parts.length) return [{ label: rootLabel, folderId: selectedFolder.value }];
  return parts.map((label, idx) => ({
    label,
    folderId: idx === parts.length - 1 ? selectedFolder.value : undefined,
  }));
});

const currentPath = computed(() => currentBreadcrumbItems.value.map((x) => x.label).join(' / '));

const handleBreadcrumbNavigate = (folderId: any) => {
  if (folderId === null || folderId === undefined || folderId === '') return;
  if (String(folderId) === String(selectedFolder.value)) return;
  selectFolder(folderId);
};

const selectedFolderName = computed(() => {
  if (!selectedFolder.value || selectedFolder.value === 'favorites') return '';
  const allFolders = [
    ...(myFolders.value || []),
    ...(enterpriseFolders.value || []),
    ...(departmentFolders.value || []),
  ];
  const folder = findFolderById(allFolders, selectedFolder.value);
  return String(folder?.name || '');
});

// 文件夹数据（侧边栏树：favorites + 各素材库根节点）
const myFolders = ref<any[]>([]);
const enterpriseFolders = ref<any[]>([]);
const departmentFolders = ref<any[]>([]);
// 获取所有匹配节点的父节点ID（用于自动展开）
const getMatchingParentKeys = (folders: any[], searchText: string): (string | number)[] => {
  if (!searchText || !searchText.trim()) {
    return [];
  }

  const searchLower = searchText.toLowerCase().trim();
  const parentKeys: Set<string | number> = new Set();

  const traverse = (items: any[], parentPath: (string | number)[] = []): boolean => {
    let hasMatch = false;
    items.forEach(folder => {
      const currentPath = [...parentPath, folder.id];
      const childrenMatch = traverse(folder.children || [], currentPath);
      const nameMatch = folder.name && String(folder.name).toLowerCase().includes(searchLower);

      if (nameMatch || childrenMatch) {
        hasMatch = true;
        // 将所有父节点添加到展开列表
        parentPath.forEach(key => parentKeys.add(key));
      }
    });
    return hasMatch;
  };

  traverse(folders);
  return Array.from(parentKeys);
};

const baseExpandedKeys = computed(() => {
  const baseKeys = ['root-my', 'root-enterprise', 'root-department'];

  if (!folderSearchText.value || !folderSearchText.value.trim()) {
    return baseKeys;
  }

  const myParentKeys = getMatchingParentKeys(myFolders.value, folderSearchText.value);
  const enterpriseParentKeys = getMatchingParentKeys(enterpriseFolders.value, folderSearchText.value);
  const departmentParentKeys = getMatchingParentKeys(departmentFolders.value, folderSearchText.value);

  return [
    ...baseKeys,
    ...myParentKeys,
    ...enterpriseParentKeys,
    ...departmentParentKeys,
  ];
});

const combinedExpandedKeys = ref<string[]>(['root-my', 'root-enterprise', 'root-department']);

watch(baseExpandedKeys, (newKeys) => {
  combinedExpandedKeys.value = newKeys.map(String);
}, { deep: true });

// 过滤文件夹树（支持搜索）
const filterFolderTree = (folders: any[], searchText: string): any[] => {
  if (!searchText || !searchText.trim()) {
    return folders;
  }

  const searchLower = searchText.toLowerCase().trim();

  const filterRecursive = (items: any[]): any[] => {
    return items
      .map(folder => {
        const children = filterRecursive(folder.children || []);
        const nameMatch = folder.name && String(folder.name).toLowerCase().includes(searchLower);
        const hasMatchingChildren = children.length > 0;

        if (nameMatch || hasMatchingChildren) {
          return {
            ...folder,
            children,
          };
        }
        return null;
      })
      .filter(Boolean);
  };

  return filterRecursive(folders);
};

const combinedTreeData = computed(() => {
  const filteredMyFolders = filterFolderTree(myFolders.value, folderSearchText.value);
  const filteredEnterpriseFolders = filterFolderTree(enterpriseFolders.value, folderSearchText.value);
  const filteredDepartmentFolders = filterFolderTree(departmentFolders.value, folderSearchText.value);

  const data: any[] = [];
  data.push({
    id: 'root-favorites',
    name: t('我的收藏'),
    isVirtualRoot: true,
    isFavoritesRoot: true,
    icon: StarOutlined,
    selectable: true,
    canDelete: false,
    children: [],
  });
  data.push({
    id: 'root-my',
    name: t('我的素材库'),
    isVirtualRoot: true,
    icon: UserOutlined,
    createType: 'my',
    children: filteredMyFolders,
    selectable: false,
    canDelete: false,
  });
  data.push({
    id: 'root-enterprise',
    name: t('企业素材库'),
    isVirtualRoot: true,
    icon: BankOutlined,
    createType: 'enterprise',
    count: filteredEnterpriseFolders.length,
    children: filteredEnterpriseFolders,
    selectable: false,
    canDelete: false,
  });
  data.push({
    id: 'root-department',
    name: t('我的部门'),
    isVirtualRoot: true,
    icon: TeamOutlined,
    count: filteredDepartmentFolders.length,
    children: filteredDepartmentFolders,
    selectable: false,
    canDelete: false,
  });
  return data;
});

const selectedTreeKeys = computed(() => {
  if (selectedFolder.value === 'favorites') return ['root-favorites'];
  if (!selectedFolder.value) return [];
  return [selectedFolder.value];
});

// 存储信息
const storageUsed = ref(0);
const storageTotal = ref(20);

// 筛选
const filters = ref({
  designer: undefined,
  tags: undefined,
  creator: undefined,
  materialGroupId: undefined,
  createTimePreset: 'all',
  materialType: undefined,
  sizeLevel: undefined,
  rating: [] as number[],
  systemTagMode: 'exclude' as SystemTagMode,
  systemTagIds: [] as number[],
  source: undefined,
  auditStatus: undefined,
  rejectReason: undefined as any,

  createTimeRange: null as [Dayjs, Dayjs] | null,
  sortField: 'create_time',
  sortOrder: 'desc',
});

type FilterTemplate = {
  id: string;
  name: string;
  fields: string[];
};

const FILTER_TEMPLATE_STORAGE_KEY = 'materialLibraryFilterTemplates_v1';

// 弹窗里可配置、且默认需要开放的筛选控件集合（与当前“真实需要”的筛选框对齐）
const allowedCustomFilterFields = [
  'materialGroupId', // 素材组
  'tags', // 标签
  'designer', // 设计师
  'creator', // 创意人
  'materialType', // 类型
  'createTimePreset', // 创建时间（包含 custom 的 range）
  'sizeLevel', // 尺寸
  'rating', // 评分
  'systemTags', // system_tag 标签
  'auditStatus', // 审核状态
  'rejectReason', // 拒审信息
];

const defaultFilterFields = [
  'materialGroupId',
  'designer',
  'creator',
  'tags',
  'createTimePreset',
];

const defaultFilterTemplate: FilterTemplate = {
  id: 'default',
  name: '默认模板',
  fields: defaultFilterFields,
};

// 内置“自定义模板”用于承载用户编辑后的筛选项
const customFilterTemplate: FilterTemplate = {
  id: 'custom',
  name: '自定义模板',
  fields: [...defaultFilterFields],
};

const filterTemplates = ref<FilterTemplate[]>([
  defaultFilterTemplate,
  customFilterTemplate,
]);
const selectedFilterTemplateId = ref<string>('default');

const filterTemplateModalVisible = ref(false);
const editTemplateFields = ref<string[]>([]);

const filterFieldOptions = computed(() => {
  return [
    { key: 'materialGroupId', label: t('素材组') },
    { key: 'tags', label: t('标签') },
    { key: 'designer', label: t('设计师') },
    { key: 'creator', label: t('创意人') },
    { key: 'createTimePreset', label: t('上传时间') },
    { key: 'materialType', label: t('类型') },
    { key: 'sizeLevel', label: t('尺寸') },
    { key: 'rating', label: t('评分') },
    { key: 'systemTags', label: t('系统标签') },
    { key: 'auditStatus', label: t('审核状态') },
    { key: 'rejectReason', label: t('拒审信息') },
  ];
});

const activeFilterFieldsSet = computed(() => {
  const tpl = filterTemplates.value.find((x) => x.id === selectedFilterTemplateId.value);
  const fields = tpl?.fields?.length ? tpl.fields : defaultFilterFields;
  return new Set(fields.map((x) => String(x)));
});

const showFilterField = (key: string) => activeFilterFieldsSet.value.has(key);

const loadFilterTemplates = () => {
  try {
    const raw = localStorage.getItem(FILTER_TEMPLATE_STORAGE_KEY);
    if (!raw) return;
    const parsed = JSON.parse(raw);
    const templatesFromStorage = Array.isArray(parsed?.templates) ? parsed.templates : null;
    if (templatesFromStorage) {
      const normalize = (tpl: any): FilterTemplate | null => {
        if (!tpl || typeof tpl !== 'object') return null;
        if (typeof tpl.id !== 'string' || typeof tpl.name !== 'string') return null;
        const fields = Array.isArray(tpl.fields)
          ? tpl.fields.map((x: any) => String(x)).filter((k) => allowedCustomFilterFields.includes(k))
          : [];
        return { id: tpl.id, name: tpl.name, fields };
      };

      const normalized = templatesFromStorage.map(normalize).filter(Boolean) as FilterTemplate[];
      // 始终保证内置 default/custom 存在
      const ids = new Set(normalized.map((x) => x.id));
      if (!ids.has('default')) normalized.push(defaultFilterTemplate);
      if (!ids.has('custom')) normalized.push(customFilterTemplate);

      // 去重（以最后出现为准）
      const dedup = new Map<string, FilterTemplate>();
      normalized.forEach((tpl) => dedup.set(tpl.id, tpl));
      filterTemplates.value = Array.from(dedup.values());
    }

    const customTpl = filterTemplates.value.find((x) => x.id === 'custom');
    const customFields = (customTpl?.fields && customTpl.fields.length ? customTpl.fields : defaultFilterFields) as string[];

    // 如果自定义字段和默认完全一致，就视为“未启用自定义模板”
    const defaultSet = new Set(defaultFilterFields.map(String));
    const customSet = new Set(customFields.map(String));
    const isSameAsDefault =
      defaultSet.size === customSet.size && Array.from(defaultSet).every((k) => customSet.has(k));

    selectedFilterTemplateId.value = isSameAsDefault ? 'default' : 'custom';
  } catch (e) {
    // 本地存储不可用时不影响正常使用
    console.warn('loadFilterTemplates failed:', e);
  }
};

const persistFilterTemplates = () => {
  try {
    const payload = JSON.stringify({
      templates: filterTemplates.value,
      selectedId: selectedFilterTemplateId.value,
    });
    localStorage.setItem(FILTER_TEMPLATE_STORAGE_KEY, payload);
  } catch (e) {
    console.warn('persistFilterTemplates failed:', e);
  }
};

const handleFilterTemplateChange = () => {
  pagination.value.current = 1;
  loadTableData();
};

const openCustomFilterTemplateModal = () => {
  const tpl = filterTemplates.value.find((x) => x.id === 'custom');
  const effectiveTpl = tpl ?? customFilterTemplate;
  // 弹窗里只维护“唯一自定义模板”
  selectedFilterTemplateId.value = 'custom';
  editTemplateFields.value = [...(effectiveTpl.fields ?? defaultFilterFields)];
};

watch(
  filterTemplateModalVisible,
  (open) => {
    if (open) openCustomFilterTemplateModal();
  },
  { immediate: false },
);

const closeFilterTemplateModal = () => {
  filterTemplateModalVisible.value = false;
};

const handleSaveCustomFilterTemplate = async () => {
  const fields = [...new Set(editTemplateFields.value.map((x) => String(x)))].filter((k) =>
    allowedCustomFilterFields.includes(k),
  );

  if (fields.length === 0) {
    message.warning(t('至少选择一个筛选项'));
    return;
  }

  const idx = filterTemplates.value.findIndex((x) => x.id === 'custom');
  if (idx >= 0) {
    filterTemplates.value[idx] = { ...filterTemplates.value[idx], name: customFilterTemplate.name, fields };
  } else {
    filterTemplates.value.push({
      id: 'custom',
      name: customFilterTemplate.name,
      fields,
    });
  }

  persistFilterTemplates();
  filterTemplateModalVisible.value = false;
  selectedFilterTemplateId.value = 'custom';
  // 立即应用模板
  handleFilterTemplateChange();
};

const resetCustomFilterTemplateToDefault = () => {
  selectedFilterTemplateId.value = 'default';
  // 将自定义字段回填成默认（避免下一次打开时还保留“历史模板”）
  const idx = filterTemplates.value.findIndex((x) => x.id === 'custom');
  if (idx >= 0) {
    filterTemplates.value[idx] = { ...filterTemplates.value[idx], name: customFilterTemplate.name, fields: [...defaultFilterFields] };
  }
  persistFilterTemplates();
  handleFilterTemplateChange();
};

const showSubfolders = ref(false);
const showDeliveryData = ref(false);
const viewMode = ref('list');

// 统计时间范围（MVP：默认最近 7 天）
const statisticsDateRange = ref<[Dayjs, Dayjs]>([dayjs().subtract(6, 'days'), dayjs()]);

const autoUpdateLoading = ref(false);

// 筛选选项数据
const designers = ref<any[]>([]);
const tags = ref<any[]>([]);
const creators = ref<any[]>([]);
const materialGroups = ref<any[]>([]);

const makeMultiFilterModel = (key: string) => computed<ValueLike[]>({
  get() {
    const v = (filters.value as any)?.[key];
    if (Array.isArray(v)) return v.filter((x) => x !== null && x !== undefined && x !== '') as ValueLike[];
    if (v === null || v === undefined || v === '') return [];
    return [v as ValueLike];
  },
  set(vals) {
    (filters.value as any)[key] = Array.isArray(vals)
      ? vals.filter((x) => x !== null && x !== undefined && x !== '')
      : [];
  },
});


const tagCategories = computed<AdvancedSelectCategory[]>(() => [{ key: 'tags', label: t('未分类') }]);
const tagItems = computed<AdvancedSelectItem[]>(() =>
  (tags.value || []).map((x: any) => ({
    categoryKey: 'tags',
    value: (x?.id ?? '') as ValueLike,
    label: String(x?.name ?? ''),
  })),
);
const tagSelectedValues = makeMultiFilterModel('tags');

const designerCategories = computed<AdvancedSelectCategory[]>(() => [{ key: 'designer', label: t('未分类') }]);
const designerItems = computed<AdvancedSelectItem[]>(() =>
  (designers.value || []).map((x: any) => ({
    categoryKey: 'designer',
    value: (x?.id ?? '') as ValueLike,
    label: String(x?.name ?? ''),
  })),
);
const designerSelectedValues = makeMultiFilterModel('designer');

const creatorCategories = computed<AdvancedSelectCategory[]>(() => [{ key: 'creator', label: t('未分类') }]);
const creatorItems = computed<AdvancedSelectItem[]>(() =>
  (creators.value || []).map((x: any) => ({
    categoryKey: 'creator',
    value: (x?.id ?? '') as ValueLike,
    label: String(x?.name ?? ''),
  })),
);
const creatorSelectedValues = makeMultiFilterModel('creator');

const materialTypeCategories = computed<AdvancedSelectCategory[]>(() => [{ key: 'materialType', label: t('未分类') }]);
const materialTypeItems = computed<AdvancedSelectItem[]>(() => [
  { categoryKey: 'materialType', value: 'regular', label: t('常规') },
  { categoryKey: 'materialType', value: 'playable', label: t('试玩') },
]);
const materialTypeSelectedValues = makeMultiFilterModel('materialType');

const sizeLevelCategories = computed<AdvancedSelectCategory[]>(() => [{ key: 'sizeLevel', label: t('未分类') }]);
const sizeLevelItems = computed<AdvancedSelectItem[]>(() => [
  { categoryKey: 'sizeLevel', value: 'small', label: t('小图') },
  { categoryKey: 'sizeLevel', value: 'medium', label: t('中图') },
  { categoryKey: 'sizeLevel', value: 'large', label: t('大图') },
]);
const sizeLevelSelectedValues = makeMultiFilterModel('sizeLevel');

const ratingSelectedValues = computed<number[]>({
  get() {
    const v = (filters.value as any)?.rating;
    return Array.isArray(v) ? v : [];
  },
  set(vals) {
    (filters.value as any).rating = Array.isArray(vals) ? vals : [];
  },
});

const systemTagMode = computed<SystemTagMode>({
  get() {
    return (filters.value as any).systemTagMode || 'exclude';
  },
  set(v) {
    (filters.value as any).systemTagMode = v;
  },
});

const systemTagSelectedValues = computed<number[]>({
  get() {
    const v = (filters.value as any).systemTagIds;
    return Array.isArray(v) ? v : [];
  },
  set(vals) {
    (filters.value as any).systemTagIds = Array.isArray(vals) ? vals : [];
  },
});

const rejectReasonOptionList = ref<Array<{ value: number; label: string }>>([]);
const rejectReasonCategories = computed<AdvancedSelectCategory[]>(() => [{ key: 'rejectReason', label: t('未分类') }]);
const rejectReasonItems = computed<AdvancedSelectItem[]>(() =>
  rejectReasonOptionList.value.map((x) => ({
    categoryKey: 'rejectReason',
    value: x.value,
    label: x.label,
  })),
);
const rejectReasonSelectedValues = makeMultiFilterModel('rejectReason');

// 展平后的文件夹列表：用于“编辑/移动”目标选择
const folderSelectOptions = computed(() => {
  const roots = [
    ...(myFolders.value || []),
    ...(enterpriseFolders.value || []),
    ...(departmentFolders.value || []),
  ];

  const out: Array<{ id: any; name: string }> = [];
  const seen = new Set<string>();

  const visit = (node: any) => {
    if (!node) return;
    if (node.id === undefined || node.id === null) return;
    const key = String(node.id);
    if (!seen.has(key)) {
      seen.add(key);
      out.push({ id: node.id, name: node.name });
    }
    (node.children || []).forEach((c: any) => visit(c));
  };

  roots.forEach((r: any) => visit(r));
  return out;
});

const xmpTagOptions = computed(() => {
  return [
    { value: 0, label: t('学习期') },
    { value: 1, label: t('成长期') },
    { value: 2, label: t('衰退期') },
    { value: 3, label: t('未使用') },
    { value: 4, label: t('无消耗') },
    { value: 5, label: t('有消耗') },
    { value: 6, label: 'YouTube' },
    { value: 7, label: t('近7天新素材') },
  ];
});

const systemTagOptions = ref<SystemTagOption[]>([]);

const loadSystemTags = async () => {
  try {
    const res = await getMaterialLibrarySystemTags({ group: 'system_tag' });
    const rows = Array.isArray(res?.data?.data) ? res.data.data : [];
    systemTagOptions.value = rows
      .map((r: any) => ({ value: Number(r?.id), label: String(r?.name ?? '') }))
      .filter((x: any) => Number.isFinite(x.value) && x.label);
  } catch (e) {
    console.error('加载system_tag标签失败:', e);
    systemTagOptions.value = [];
  }
};

// 表格
const loading = ref(false);
const tableData = ref<any[]>([]);
const latestTableLoadSeq = ref(0);
const pagination = ref({
  current: 1,
  pageSize: 20,
  total: 0,
  showTotal: (total: number) => t('共') + total + t('条'),
  showSizeChanger: true,
  pageSizeOptions: ['10', '20', '50', '100'],
});

// =============================
// 表格列：固定列 + 可选列（默认列 + 投放素材列）
// 自定义列支持：勾选、排序、固定到左侧
// =============================
const fixedBaseColumnDefs = ref<any[]>([
  { key: 'fixedMaterial', title: t('素材'), dataIndex: 'material', width: 360, fixed: 'left' },
  { key: 'fixedLocalId', title: 'Local ID', dataIndex: 'localId', width: 140, fixed: 'left' },
]);

const defaultColumnDefs = ref<any[]>([
  { key: 'designerId', title: t('设计师'), dataIndex: 'designerId', width: 140 },
  { key: 'creatorId', title: t('创意人'), dataIndex: 'creatorId', width: 140 },
  { key: 'tags', title: t('标签'), dataIndex: 'tags', width: 240 },
  { key: 'sizeLevel', title: t('尺寸'), dataIndex: 'sizeLevel', width: 110 },
  { key: 'duration', title: t('时长（秒）'), dataIndex: 'duration', width: 130 },
  { key: 'shape', title: t('形状'), dataIndex: 'shape', width: 90 },
  { key: 'type', title: t('类型'), dataIndex: 'type', width: 110 },
  { key: 'createTime', title: t('上传时间'), dataIndex: 'createTime', width: 170 },
  { key: 'rejectReason', title: t('拒审信息'), dataIndex: 'rejectReason', width: 180 },
  { key: 'materialGroupId', title: t('素材组'), dataIndex: 'materialGroupId', width: 200 },
  { key: 'auditStatus', title: t('审核状态'), dataIndex: 'auditStatus', width: 110 },
  { key: 'remarks', title: t('素材备注'), dataIndex: 'remarks', width: 260 },
  { key: 'productionCost', title: t('制作费用'), dataIndex: 'productionCost', width: 110, sorter: true },
  { key: 'materialCount', title: t('关联创意数'), dataIndex: 'materialCount', width: 150 },
  { key: 'size', title: t('素材大小'), dataIndex: 'size', width: 140 },
  { key: 'rating', title: t('评分'), dataIndex: 'rating', width: 90 },
  { key: 'source', title: t('投放渠道'), dataIndex: 'source', width: 200 },
]);

const deliveryColumnDefs = ref<any[]>([
  { key: 'spend', title: t('花费'), dataIndex: 'spend', width: 100, sorter: true },
  { key: 'impressions', title: t('展示数'), dataIndex: 'impressions', width: 120, sorter: true },
  { key: 'cpm', title: t('千次展示成本'), dataIndex: 'cpm', width: 120, sorter: true },
  { key: 'clicks', title: t('点击数'), dataIndex: 'clicks', width: 100, sorter: true },
  { key: 'clickCost', title: t('点击成本'), dataIndex: 'clickCost', width: 120, sorter: true },
  { key: 'ctr', title: t('点击率'), dataIndex: 'ctr', width: 110, sorter: true },
  { key: 'conversions', title: t('转化数'), dataIndex: 'conversions', width: 110, sorter: true },
  { key: 'playCount', title: t('播放次数'), dataIndex: 'playCount', width: 120, sorter: true },
  { key: 'play100Count', title: t('100%播放次数'), dataIndex: 'play100Count', width: 140, sorter: true },
  { key: 'completionRate', title: t('完播率'), dataIndex: 'completionRate', width: 110, sorter: true },
  { key: 'play75Count', title: t('75%播放次数'), dataIndex: 'play75Count', width: 140, sorter: true },
  { key: 'play75Rate', title: t('75%播放率'), dataIndex: 'play75Rate', width: 120, sorter: true },
  { key: 'play50Count', title: t('50%播放次数'), dataIndex: 'play50Count', width: 140, sorter: true },
  { key: 'play50Rate', title: t('50%播放率'), dataIndex: 'play50Rate', width: 120, sorter: true },
  { key: 'play25Count', title: t('25%播放次数'), dataIndex: 'play25Count', width: 140, sorter: true },
  { key: 'play25Rate', title: t('25%播放率'), dataIndex: 'play25Rate', width: 120, sorter: true },
]);

const operationColumnDef = computed(() => ({
  title: t('操作'),
  dataIndex: 'operation',
  key: 'operation',
  width: 150,
  fixed: 'right',
}));

const fixedBaseColumnKeys = computed(() => fixedBaseColumnDefs.value.map((c) => String(c.key)));
const deliveryColumnKeys = computed(() => deliveryColumnDefs.value.map((c) => String(c.key)));

// 当前选中的列（用于自定义列）
const selectedColumnKeys = ref<string[]>([
  ...fixedBaseColumnDefs.value.map((c) => String(c.key)),
  ...defaultColumnDefs.value.map((c) => String(c.key)),
]);

// 左侧固定列：用“key集合”表示，顺序以 selectedColumnKeys 的先后为准
const fixedLeftColumnKeys = ref<string[]>([...fixedBaseColumnDefs.value.map((c) => String(c.key))]);

// 自定义列弹窗临时选择
const columnSettingsModalVisible = ref(false);
const columnSettingsDraftKeys = ref<string[]>([]);
const fixedLeftDraftKeys = ref<string[]>([]);
const columnSearchText = ref('');
const saveAsTemplate = ref(false);
const templateName = ref('');
const draggingSelectedKey = ref<string>('');
const defaultGroupAllChecked = computed(() => {
  const opts = propertyColumnOptionsFiltered.value;
  if (!opts.length) return false;
  return opts.every((o) => columnSettingsDraftKeys.value.includes(o.key));
});
const deliveryGroupAllChecked = computed(() => {
  const opts = dataColumnOptionsFiltered.value;
  if (!opts.length) return false;
  return opts.every((o) => columnSettingsDraftKeys.value.includes(o.key));
});

const allSelectableColumnDefs = computed(() => {
  return [...defaultColumnDefs.value, ...deliveryColumnDefs.value].map((c: any) => ({
    key: String(c.key),
    label: String(c.title ?? c.key),
  }));
});

const propertyColumnOptions = computed(() => {
  return (defaultColumnDefs.value || []).map((c: any) => ({ key: String(c.key), label: String(c.title ?? c.key) }));
});
const dataColumnOptions = computed(() => {
  return (deliveryColumnDefs.value || []).map((c: any) => ({ key: String(c.key), label: String(c.title ?? c.key) }));
});

const filterColumnOptions = (opts: Array<{ key: string; label: string }>) => {
  const q = String(columnSearchText.value || '').trim().toLowerCase();
  if (!q) return opts;
  return opts.filter((o) => String(o.label).toLowerCase().includes(q));
};

const propertyColumnOptionsFiltered = computed(() => filterColumnOptions(propertyColumnOptions.value));
const dataColumnOptionsFiltered = computed(() => filterColumnOptions(dataColumnOptions.value));

const orderedDraftKeys = computed(() => {
  // 草稿显示顺序完全取决于 columnSettingsDraftKeys 的当前顺序，
  // 这样拖拽 reorder 时 UI 才会实时变化。
  return columnSettingsDraftKeys.value.map(String);
});

const getColumnLabelByKey = (key: string) => {
  const k = String(key);
  const fixed = fixedBaseColumnDefs.value.find((c: any) => String(c.key) === k);
  if (fixed) return String(fixed.title ?? fixed.key);
  const all = [...defaultColumnDefs.value, ...deliveryColumnDefs.value];
  const found = all.find((c: any) => String(c.key) === k);
  return String(found?.title ?? k);
};

const openColumnSettingsModal = () => {
  columnSettingsModalVisible.value = true;
};

watch(
  columnSettingsModalVisible,
  (open) => {
    if (open) {
      // 草稿：排除固定基座列（固定基座永远存在，不参与勾选/移除）
      const fixedSet = new Set<string>(fixedBaseColumnKeys.value.map(String));
      columnSettingsDraftKeys.value = selectedColumnKeys.value.map(String).filter((k) => !fixedSet.has(k));
      fixedLeftDraftKeys.value = fixedLeftColumnKeys.value.map(String);
      columnSearchText.value = '';
      saveAsTemplate.value = false;
      templateName.value = '';
      draggingSelectedKey.value = '';
    }
  },
  { immediate: false },
);

const closeColumnSettingsModal = () => {
  columnSettingsModalVisible.value = false;
};

const toggleDraftColumn = (key: string, checked: boolean) => {
  const k = String(key);
  const fixedSet = new Set<string>(fixedBaseColumnKeys.value.map(String));
  if (fixedSet.has(k)) return;
  if (checked) {
    if (!columnSettingsDraftKeys.value.includes(k)) columnSettingsDraftKeys.value.push(k);
  } else {
    columnSettingsDraftKeys.value = columnSettingsDraftKeys.value.filter((x) => String(x) !== k);
    fixedLeftDraftKeys.value = fixedLeftDraftKeys.value.filter((x) => String(x) !== k);
  }
};

const handleGroupCheckboxChange = (group: 'default' | 'delivery', checked: boolean) => {
  const opts = group === 'default' ? propertyColumnOptionsFiltered.value : dataColumnOptionsFiltered.value;
  const keys = opts.map((o) => String(o.key));
  const keySet = new Set<string>(keys);

  if (checked) {
    const set = new Set<string>(columnSettingsDraftKeys.value.map(String));
    keys.forEach((k) => set.add(k));
    columnSettingsDraftKeys.value = [...set];
  } else {
    columnSettingsDraftKeys.value = columnSettingsDraftKeys.value.filter((k) => !keySet.has(String(k)));
  }

  // 固定列顺序：保留基座列，其它固定列若取消勾选则移除
  const base = fixedBaseColumnKeys.value.map(String);
  const kept = fixedLeftDraftKeys.value.map(String).filter((k) => !keySet.has(String(k)) && !base.includes(k));
  fixedLeftDraftKeys.value = [...base, ...kept];
};

const clearDraftSelected = () => {
  columnSettingsDraftKeys.value = [];
  fixedLeftDraftKeys.value = fixedBaseColumnKeys.value.map(String);
};

const removeDraftColumn = (key: string) => {
  toggleDraftColumn(String(key), false);
};

const removeFromFixed = (key: string) => {
  const k = String(key);
  if (fixedBaseColumnKeys.value.map(String).includes(k)) return;
  fixedLeftDraftKeys.value = fixedLeftDraftKeys.value.filter((x) => String(x) !== k);
};

const handleSelectedDragStart = (key: string) => {
  const k = String(key);
  if (fixedBaseColumnKeys.value.map(String).includes(k)) return;
  draggingSelectedKey.value = k;
};
const handleSelectedDragEnd = () => {
  draggingSelectedKey.value = '';
};

const reorderArray = (arr: string[], fromKey: string, toKey: string) => {
  const from = String(fromKey);
  const to = String(toKey);
  const list = arr.map(String);
  const fromIdx = list.indexOf(from);
  const toIdx = list.indexOf(to);
  if (fromIdx < 0 || toIdx < 0 || fromIdx === toIdx) return list;
  const next = [...list];
  next.splice(fromIdx, 1);
  next.splice(toIdx, 0, from);
  return next;
};

const handleSelectedDrop = (targetKey: string, zone: 'fixed' | 'list') => {
  const from = String(draggingSelectedKey.value || '');
  const to = String(targetKey || '');
  if (!from || !to) return;
  const list = orderedDraftKeys.value.map(String);
  if (!list.includes(from) || !list.includes(to)) return;

  columnSettingsDraftKeys.value = reorderArray(list, from, to);

  if (zone === 'fixed') {
    const fixedSet = new Set<string>(fixedLeftDraftKeys.value.map(String));
    fixedSet.add(from);
    fixedSet.add(to);
    fixedBaseColumnKeys.value.map(String).forEach((k) => fixedSet.add(k));
    const base = fixedBaseColumnKeys.value.map(String);
    const orderedFixed = orderedDraftKeys.value.map(String).filter((k) => fixedSet.has(k));
    fixedLeftDraftKeys.value = [...base, ...orderedFixed];
  }
};

const handleDropToFixedZone = () => {
  const from = String(draggingSelectedKey.value || '');
  if (!from) return;
  const fixedSet = new Set<string>(fixedLeftDraftKeys.value.map(String));
  fixedSet.add(from);
  // 固定基座永远在固定区
  fixedBaseColumnKeys.value.map(String).forEach((k) => fixedSet.add(k));
  const base = fixedBaseColumnKeys.value.map(String);
  const orderedFixed = orderedDraftKeys.value.map(String).filter((k) => fixedSet.has(k));
  fixedLeftDraftKeys.value = [...base, ...orderedFixed];
};

const handleDropToSelectedListEnd = () => {
  const from = String(draggingSelectedKey.value || '');
  if (!from) return;
  const list = columnSettingsDraftKeys.value.map(String);
  const fromIdx = list.indexOf(from);
  if (fromIdx < 0) return;
  const next = [...list];
  next.splice(fromIdx, 1);
  next.push(from);
  columnSettingsDraftKeys.value = next;
};

const handleColumnSettingsOkAdvanced = () => {
  const allowed = new Set<string>([
    ...fixedBaseColumnKeys.value.map(String),
    ...allSelectableColumnDefs.value.map((x) => String(x.key)),
  ]);

  const base = fixedBaseColumnKeys.value.map(String);
  const ordered = orderedDraftKeys.value.map(String).filter((k) => allowed.has(k));
  selectedColumnKeys.value = [...base, ...ordered];

  const selectedSet = new Set<string>(selectedColumnKeys.value.map(String));
  const fixed = [...new Set(fixedLeftDraftKeys.value.map(String))].filter((k) => selectedSet.has(k));
  base.forEach((k) => {
    if (!fixed.includes(k)) fixed.unshift(k);
  });
  fixedLeftColumnKeys.value = fixed;

  if (saveAsTemplate.value) {
    const name = String(templateName.value || '').trim();
    if (!name) {
      message.warning(t('请填写模板名称'));
      return;
    }
    const tpl: ColumnTemplate = {
      id: `${Date.now()}`,
      name,
      selectedKeys: selectedColumnKeys.value.map(String),
      fixedLeftKeys: fixedLeftColumnKeys.value.map(String),
    };
    columnTemplates.value = [tpl, ...columnTemplates.value].slice(0, 30);
    void persistColumnTemplates();
  }

  columnSettingsModalVisible.value = false;
};

const applyColumnTemplate = (tplId: string) => {
  const tpl = columnTemplates.value.find((x) => String(x.id) === String(tplId));
  if (!tpl) return;
  const allowed = new Set<string>([
    ...fixedBaseColumnKeys.value.map(String),
    ...allSelectableColumnDefs.value.map((x) => String(x.key)),
  ]);
  const base = fixedBaseColumnKeys.value.map(String);

  const selected = (tpl.selectedKeys || []).map(String).filter((k) => allowed.has(k));
  const ensuredSelected = [...base, ...selected.filter((k) => !base.includes(k))];
  selectedColumnKeys.value = ensuredSelected;

  const fixed = (tpl.fixedLeftKeys || []).map(String).filter((k) => allowed.has(k));
  const ensuredFixed = [...base, ...fixed.filter((k) => !base.includes(k))]
    .filter((k, idx, arr) => arr.indexOf(k) === idx)
    .filter((k) => ensuredSelected.includes(k));
  fixedLeftColumnKeys.value = ensuredFixed;
};

const visibleColumns = computed(() => {
  const selectedSet = new Set<string>(selectedColumnKeys.value.map(String));
  const fixedLeftSet = new Set<string>(fixedLeftColumnKeys.value.map(String));

  const allDefs = [
    ...fixedBaseColumnDefs.value,
    ...defaultColumnDefs.value,
    ...deliveryColumnDefs.value,
  ];
  const defByKey = new Map<string, any>(allDefs.map((c) => [String(c.key), c]));

  // 以 selectedColumnKeys 的顺序输出（支持拖拽排序）
  const ordered = selectedColumnKeys.value
    .map(String)
    .filter((k) => selectedSet.has(k))
    .map((k) => defByKey.get(k))
    .filter(Boolean)
    .map((c: any) => {
      const key = String(c.key);
      // 左侧固定：只要 key 在 fixedLeftColumnKeys 里，就固定 left
      if (fixedLeftSet.has(key)) return { ...c, fixed: 'left' };
      // 防止遗留 fixed
      const { fixed, ...rest } = c;
      return rest;
    });

  const all = [...ordered, operationColumnDef.value];
  // 避免某些字段同时存在于 default / delivery 两组导致重复渲染
  const seen = new Set<string>();
  return all.filter((c: any) => {
    const k = String(c?.key ?? c?.dataIndex ?? '');
    if (!k) return true;
    if (seen.has(k)) return false;
    seen.add(k);
    return true;
  });
});

// 列渲染：id -> name / 格式化
const designerNameById = computed<Map<string, string>>(
  () => new Map<string, string>((designers.value || []).map((d: any) => [String(d.id), String(d.name || '')])),
);
const creatorNameById = computed<Map<string, string>>(
  () => new Map<string, string>((creators.value || []).map((c: any) => [String(c.id), String(c.name || '')])),
);
const materialGroupNameById = computed<Map<string, string>>(
  () => new Map<string, string>((materialGroups.value || []).map((g: any) => [String(g.id), String(g.name || '')])),
);

const allFolderRoots = computed(() => [
  ...(myFolders.value || []),
  ...(enterpriseFolders.value || []),
  ...(departmentFolders.value || []),
]);

const getFolderName = (folderId: any) => {
  if (folderId === null || folderId === undefined || folderId === '') return '-';
  const folder = findFolderById(allFolderRoots.value, folderId);
  return folder?.name || String(folderId);
};

const getDesignerName = (designerId: any) => {
  if (designerId === null || designerId === undefined || designerId === '') return '-';
  return designerNameById.value.get(String(designerId)) || '-';
};

const getCreatorName = (creatorId: any) => {
  if (creatorId === null || creatorId === undefined || creatorId === '') return '-';
  return creatorNameById.value.get(String(creatorId)) || '-';
};

const getTagId = (tag: any) => String(tag?.id ?? tag?.value ?? '');
const getTagName = (tag: any) => String(tag?.name ?? tag?.label ?? '');
const getTagParentId = (tag: any) => {
  const raw = tag?.parent_id ?? tag?.parentId ?? tag?.pid ?? null;
  if (raw === null || raw === undefined || raw === '') return null;
  return String(raw);
};

const tagNameById = computed<Map<string, string>>(
  () => new Map<string, string>((tags.value || []).map((tag: any) => [getTagId(tag), getTagName(tag)])),
);

const getTagNameById = (id: string) => tagNameById.value.get(String(id)) || String(id);

const formatEditableProductionCost = (value: any) => {
  if (value === null || value === undefined || value === '') return t('点击编辑');
  const n = Number(value);
  if (!Number.isFinite(n)) return String(value);
  return `${n.toFixed(2)} USD`;
};

const formatSize = (w: any, h: any) => {
  const width = w !== null && w !== undefined && w !== '' ? Number(w) : null;
  const height = h !== null && h !== undefined && h !== '' ? Number(h) : null;
  if (width === null || height === null || !Number.isFinite(width) || !Number.isFinite(height)) return '-';
  return `${width}x${height}`;
};

const formatSizeLevel = (w: any, h: any) => {
  const width = w !== null && w !== undefined && w !== '' ? Number(w) : null;
  const height = h !== null && h !== undefined && h !== '' ? Number(h) : null;
  if (width === null || height === null || !Number.isFinite(width) || !Number.isFinite(height)) return '-';
  const maxSide = Math.max(width, height);
  if (maxSide <= 720) return t('小图');
  if (maxSide <= 1920) return t('中图');
  return t('大图');
};

const formatMaterialType = (type: any) => {
  const v = type === null || type === undefined || type === '' ? null : String(type);
  if (!v) return '-';
  if (v === 'folder') return t('文件夹');
  if (v === 'video') return '视频';
  if (v === 'image') return '图片';
  return v;
};

const formatDurationSeconds = (duration: any) => {
  if (duration === null || duration === undefined || duration === '') return '-';
  const n = Number(duration);
  if (!Number.isFinite(n)) return '-';
  return String(n);
};

const formatNumberOrDash = (v: any) => {
  if (v === null || v === undefined || v === '') return '-';
  const n = Number(v);
  if (!Number.isFinite(n)) return String(v);
  return String(n);
};

const formatPercentOrDash = (v: any) => {
  if (v === null || v === undefined || v === '') return '-';
  const n = Number(v);
  if (!Number.isFinite(n)) return String(v);
  // 兼容后端返回 0-1 或 0-100
  const percent = n <= 1 ? n * 100 : n;
  return `${percent.toFixed(2)}%`;
};

const formatCreateTime = (v: any) => {
  if (v === null || v === undefined || v === '') return '-';
  const d = dayjs(v);
  if (!d.isValid()) return '-';
  return d.format('YYYY-MM-DD HH:mm');
};

const formatShape = (w: any, h: any) => {
  const width = w !== null && w !== undefined && w !== '' ? Number(w) : null;
  const height = h !== null && h !== undefined && h !== '' ? Number(h) : null;
  if (width === null || height === null || !Number.isFinite(width) || !Number.isFinite(height)) return '-';
  return width >= height ? '横' : '竖';
};

const formatAuditStatus = (auditStatus: any, rejectReason: any) => {
  const v = auditStatus === null || auditStatus === undefined || auditStatus === '' ? null : Number(auditStatus);
  if (v === 0) return t('待审核');
  if (v === 1) return t('审核通过');
  if (v === 2) return rejectReason ? `${t('审核拒绝')}(${rejectReason})` : t('审核拒绝');
  return '-';
};

const formatSource = (source: any) => {
  return source === null || source === undefined || source === '' ? '-' : String(source);
};

const formatMaterialGroup = (materialGroupId: any) => {
  const groupIdStr =
    materialGroupId === null || materialGroupId === undefined || materialGroupId === '' ? '' : String(materialGroupId);
  if (!groupIdStr) return '-';
  return materialGroupNameById.value.get(groupIdStr) || groupIdStr;
};

// showDeliveryData 与投放列选择同步
const deliveryKeySet = computed(() => new Set<string>(deliveryColumnKeys.value));
// 需要后端 with_statistics 的列集合（含默认列里的“制作费用”）
const statisticsKeySet = computed(() => new Set<string>([...deliveryColumnKeys.value, 'productionCost']));

watch(
  showDeliveryData,
  (enabled) => {
    const deliveryKeys = deliveryColumnKeys.value;
    if (enabled) {
      selectedColumnKeys.value = [...new Set([...selectedColumnKeys.value, ...deliveryKeys])];
    } else {
      selectedColumnKeys.value = selectedColumnKeys.value.filter((k) => !deliveryKeySet.value.has(k));
    }

    // 切换投放数据开关后，需要重新拉取数据
    pagination.value.current = 1;
    loadTableData();
  },
  { immediate: false },
);

watch(
  selectedColumnKeys,
  (keys) => {
    const anyDeliverySelected = keys.some((k) => deliveryKeySet.value.has(k));
    if (anyDeliverySelected !== showDeliveryData.value) {
      showDeliveryData.value = anyDeliverySelected;
    }
  },
  { deep: true, immediate: false },
);

// 行选择
const rowSelection = ref({
  selectedRowKeys: [],
  onChange: (_selectedKeys: any[], selected: any[]) => {
    // 文件夹行不是素材，禁止进入批量导出/批量标签/批量操作
    const materialSelected = (selected || []).filter((r) => r?.type !== 'folder');
    rowSelection.value.selectedRowKeys = materialSelected.map((r) => r.id);
    selectedRows.value = materialSelected;
  },
  getCheckboxProps: (record: any) => ({
    disabled: record?.type === 'folder',
  }),
});

// 选中行数据
const selectedRows = ref<any[]>([]);

const gridSelectedKeySet = computed(() => new Set<string>((rowSelection.value.selectedRowKeys || []).map(String)));
const isGridSelected = (record: any) => gridSelectedKeySet.value.has(String(record?.id));

const updateGridSelection = (record: any, checked: boolean) => {
  if (!record || record.type === 'folder') return;
  const id = String(record.id);
  const keySet = new Set<string>((rowSelection.value.selectedRowKeys || []).map(String));
  if (checked) keySet.add(id);
  else keySet.delete(id);

  const selectableRows = (tableData.value || []).filter((r: any) => r?.type !== 'folder');
  const selectableMap = new Map<string, any>(selectableRows.map((r: any) => [String(r.id), r]));

  rowSelection.value.selectedRowKeys = [...keySet];
  selectedRows.value = [...keySet]
    .map((k) => selectableMap.get(k))
    .filter(Boolean);
};

const handleGridPageChange = (page: number, pageSize: number) => {
  pagination.value.current = page;
  pagination.value.pageSize = pageSize;
  loadTableData();
};

// 弹窗
const uploadModalVisible = ref(false);
const createFolderModalVisible = ref(false);
const createFolderParentId = ref<any>(null);
const normalizeFolderNameForCompare = (name: any) => String(name || '').trim().toLowerCase();
const isDefaultLibraryByName = (name: any) =>
  normalizeFolderNameForCompare(name) === normalizeFolderNameForCompare(t(DEFAULT_LIBRARY_NAME));
const isLibraryNodeByMeta = (node: any) => {
  if (!node || node?.isVirtualRoot || node?.isEmptyTip) return false;
  return Number(node?.parent_id ?? -1) === 0;
};
const createFolderSiblingNames = computed<string[]>(() => {
  const parentId = createFolderParentId.value;
  if (parentId === null || parentId === undefined || parentId === '' || parentId === 'favorites') return [];
  const roots = [
    ...(myFolders.value || []),
    ...(enterpriseFolders.value || []),
    ...(departmentFolders.value || []),
  ];
  const parent = findFolderById(roots, parentId);
  const children = Array.isArray(parent?.children) ? parent.children : [];
  return children
    .map((x: any) => String(x?.name || x?.folder_name || '').trim())
    .filter(Boolean)
    .filter((v, i, arr) => arr.findIndex((n) => normalizeFolderNameForCompare(n) === normalizeFolderNameForCompare(v)) === i);
});
const createLibraryModalVisible = ref(false);
const createLibraryType = ref<'my' | 'enterprise'>('my');

// 批量标签编辑
const batchTagModalVisible = ref(false);
const batchMode = ref<'manual' | 'single' | 'ai'>('manual');
const editScope = ref<'all' | 'partial'>('all');
const applyMode = ref<'assign' | 'clear'>('assign');
const manualBatchTags = ref<string[]>([]);
const batchSaving = ref(false);
const aiLoading = ref(false);
const aiLoaded = ref(false);
const editableBatchRows = ref<any[]>([]);

type CellEditType = 'productionCost' | 'designerId' | 'creatorId' | 'tags' | 'remarks';
const cellEditModalVisible = ref(false);
const cellEditSaving = ref(false);
const cellEditType = ref<CellEditType>('productionCost');
const cellEditMaterialId = ref<any>(null);
const cellEditCostValue = ref('');
const cellEditRemarkValue = ref('');
const cellEditPersonKeyword = ref('');
const cellEditPersonSelectedId = ref<string>('');
const cellEditTagKeyword = ref('');
const cellEditTagCheckedIds = ref<string[]>([]);

const cellEditModalTitle = computed(() => {
  if (cellEditType.value === 'productionCost') return t('编辑制作费用');
  if (cellEditType.value === 'designerId') return t('编辑设计师');
  if (cellEditType.value === 'creatorId') return t('编辑创意人');
  if (cellEditType.value === 'tags') return t('编辑标签');
  return t('编辑素材备注');
});

// 编辑素材弹窗
const editMaterialModalVisible = ref(false);
const editSaving = ref(false);
const editingMaterialId = ref<any>(null);
const editFormData = ref({
  material_name: '',
  folder_id: undefined as any,
  designer_id: undefined as any,
  creator_id: undefined as any,
  material_group_id: undefined as any,
  remarks: '',
  xmp_tag: undefined as any,
  mindworks_locked: false,
});

// 批量移动弹窗
const batchMoveModalVisible = ref(false);
const batchMoveSaving = ref(false);
const batchMoveTargetFolderId = ref<any>(null);
const batchMoveFormData = ref({});

// 7.9 审核拒绝弹窗
const auditRejectModalVisible = ref(false);
const auditRejectSaving = ref(false);
const auditRejectMaterialId = ref<any>(null);
const auditRejectForm = ref({
  reject_reason: '',
});

// 7.14 使用记录弹窗
const usagesModalVisible = ref(false);
const usagesData = ref<any[]>([]);
const usagesColumns = ref<any[]>([
  { title: t('使用类型'), dataIndex: 'usageType', key: 'usageType', width: 180 },
  { title: t('引用对象类型'), dataIndex: 'refType', key: 'refType', width: 180 },
  { title: t('引用对象ID'), dataIndex: 'refId', key: 'refId', width: 180 },
  { title: t('使用时间'), dataIndex: 'usedAt', key: 'usedAt', width: 200 },
  { title: t('操作人ID'), dataIndex: 'operatorId', key: 'operatorId', width: 140 },
]);

// 7.13 媒体同步弹窗
const mediaSyncModalVisible = ref(false);
const mediaSyncLoading = ref(false);
const mediaSyncForm = ref<any>({
  channel: 'Meta',
  account_id: undefined,
});
const mediaSyncResult = ref<any>(null);
let mediaSyncPollTimer: any = null;

const batchColumns: any[] = [
  {
    title: t('素材名称'),
    dataIndex: 'name',
    key: 'name',
    width: 280,
  },
  {
    title: t('标签'),
    dataIndex: 'tags',
    key: 'tags',
  },
];

const handleFilterChange = () => {
  pagination.value.current = 1;
  loadTableData();
};

type UploadTimePresetKey =
  | 'all'
  | 'today'
  | 'yesterday'
  | 'last3'
  | 'last7'
  | 'last30'
  | 'thisWeek'
  | 'lastWeek'
  | 'thisMonth'
  | 'lastMonth'
  | 'custom';

const getUploadTimeRangeByPreset = (key: Exclude<UploadTimePresetKey, 'custom'>): [Dayjs, Dayjs] | null => {
  const now = dayjs();
  switch (key) {
    case 'all':
      return null;
    case 'today':
      return [now.startOf('day'), now.endOf('day')];
    case 'yesterday': {
      const d = now.subtract(1, 'day');
      return [d.startOf('day'), d.endOf('day')];
    }
    case 'last3':
      return [now.subtract(2, 'day').startOf('day'), now.endOf('day')];
    case 'last7':
      return [now.subtract(6, 'day').startOf('day'), now.endOf('day')];
    case 'last30':
      return [now.subtract(29, 'day').startOf('day'), now.endOf('day')];
    case 'thisWeek':
      return [now.startOf('week'), now.endOf('week')];
    case 'lastWeek': {
      const d = now.subtract(1, 'week');
      return [d.startOf('week'), d.endOf('week')];
    }
    case 'thisMonth':
      return [now.startOf('month'), now.endOf('month')];
    case 'lastMonth': {
      const d = now.subtract(1, 'month');
      return [d.startOf('month'), d.endOf('month')];
    }
    default:
      return null;
  }
};

const uploadTimePresetOrder: Exclude<UploadTimePresetKey, 'custom'>[] = [
  'all',
  'today',
  'yesterday',
  'last3',
  'last7',
  'last30',
  'thisWeek',
  'lastWeek',
  'thisMonth',
  'lastMonth',
];

const uploadTimePresetLabelMap: Record<Exclude<UploadTimePresetKey, 'custom'>, string> = {
  all: '所有时间',
  today: '今天',
  yesterday: '昨天',
  last3: '最近3天',
  last7: '最近7天',
  last30: '最近30天',
  thisWeek: '本周',
  lastWeek: '上周',
  thisMonth: '本月',
  lastMonth: '上月',
};

const uploadTimeRangePresets = computed(() =>
  uploadTimePresetOrder.map((key) => {
    const range = getUploadTimeRangeByPreset(key);
    return {
      label: t(uploadTimePresetLabelMap[key]),
      value: range ?? ([] as any),
    };
  }),
);

const isSameRange = (a: [Dayjs, Dayjs] | null, b: [Dayjs, Dayjs] | null) => {
  if (!a && !b) return true;
  if (!a || !b) return false;
  return a[0].valueOf() === b[0].valueOf() && a[1].valueOf() === b[1].valueOf();
};

const resolveUploadTimePreset = (range: [Dayjs, Dayjs] | null): UploadTimePresetKey => {
  if (!range) return 'all';
  for (const key of uploadTimePresetOrder) {
    if (key === 'all') continue;
    if (isSameRange(range, getUploadTimeRangeByPreset(key))) return key;
  }
  return 'custom';
};

const handleUploadTimeRangeChange = (value: [Dayjs, Dayjs] | null) => {
  filters.value.createTimeRange = value && value.length === 2 ? value : null;
  filters.value.createTimePreset = resolveUploadTimePreset(filters.value.createTimeRange);
  handleFilterChange();
};

const handleGlobalSearch = () => {
  pagination.value.current = 1;
  loadTableData();
};

const normalizeFolderNode = (folder: any) => {
  const isDefaultLibrary = isDefaultLibraryByName(folder?.name || folder?.folder_name);
  const node: any = {
    ...folder,
    isDefaultLibrary,
    isLibraryNode: Number(folder?.parent_id ?? -1) === 0,
    canDelete: isDefaultLibrary ? false : (folder?.canDelete ?? true),
    isExpanded: !!folder?.isExpanded,
    childrenLoaded: !!folder?.childrenLoaded,
    children: Array.isArray(folder?.children) ? folder.children : [],
    isLeaf: false,
  };

  if (
    (node.child_count === undefined || node.child_count === null || node.child_count === '')
    && Array.isArray(node.children)
    && node.children.length > 0
  ) {
    node.child_count = node.children.length;
  }
  if (
    (node.material_count === undefined || node.material_count === null || node.material_count === '')
    && node.count !== undefined
    && node.count !== null
    && node.count !== ''
  ) {
    node.material_count = Number(node.count);
  }
  return node;
};

const loadFolderChildren = async (folder: any) => {
  const res = await getMaterialLibraryFolderChildren(folder.id, {
    owner_id: folder.owner_id,
    library_type: folder.library_type,
  });
  const children = (res?.data || []).map((c: any) => normalizeFolderNode(c));
  const currentFolderMaterialCount = Number(folder?.material_count ?? folder?.count ?? 0) || 0;
  // 只有在“既没有子文件夹，也没有素材”时，才显示空提示节点。
  const shouldShowEmptyTip = children.length === 0 && currentFolderMaterialCount <= 0;
  const displayChildren = !shouldShowEmptyTip
    ? children
    : [{
      id: `__empty__${String(folder.id)}`,
      name: t('暂无内容'),
      isEmptyTip: true,
      canDelete: false,
      selectable: false,
      disabled: true,
      isLeaf: true,
      children: [],
    }];
  folder.children = displayChildren;
  folder.childrenLoaded = true;
  folder.child_count = children.length;
  return children;
};

const preloadFolderTreeCounts = async (roots: any[]) => {
  const walk = async (node: any) => {
    if (!node) return;
    const children = await loadFolderChildren(node);
    for (const child of children) {
      await walk(child);
    }
  };
  for (const root of roots || []) {
    await walk(root);
  }
};

const selectFolder = (folderId: any) => {
  selectedFolder.value = folderId;
  loadTableData();
};

const handleTreeSelect = (selectedKeys: Array<string | number>) => {
  if (!selectedKeys?.length) return;
  if (String(selectedKeys[0]) === 'root-favorites') {
    selectFolder('favorites');
    return;
  }
  if (String(selectedKeys[0]).startsWith('root-')) return;
  selectFolder(selectedKeys[0]);
};

const renderTreeSwitcherIcon = (props: any) => {
  if (props.dataRef?.isVirtualRoot || props.dataRef?.subfolder_count > 0) {
    return props?.expanded ? h(DownOutlined) : h(RightOutlined);
  }
  return h('span', { style: 'width: 14px; height: 14px; display: inline-block;' });
};

const handleFolderTitleClick = async (node: any) => {
  if (node?.isEmptyTip) return;
  const folderId = node?.id;
  if (folderId === null || folderId === undefined || folderId === '') return;

  if (!String(folderId).startsWith('root-')) {
    selectFolder(folderId);
  }

  const currentKeys = combinedExpandedKeys.value.map((k) => String(k));
  const key = String(folderId);
  const isExpanded = currentKeys.includes(key);

  if (isExpanded) {
    combinedExpandedKeys.value = combinedExpandedKeys.value.filter((k) => String(k) !== key);
    return;
  }

  combinedExpandedKeys.value = [...combinedExpandedKeys.value, folderId];
  if (String(folderId).startsWith('root-')) return;
  const folder = findFolderById(allFolderRoots.value, folderId);
  if (!folder || folder.childrenLoaded) return;
  try {
    await loadFolderChildren(folder);
  } catch (e) {
    console.error('加载子文件夹失败:', e);
  }
};

// 展开/折叠文件夹树节点（懒加载 children）
const handleCombinedTreeExpand = async (keys: Array<string | number>, info: any) => {
  const nodeData = info?.node?.dataRef;
  if (nodeData?.isEmptyTip) return;

  combinedExpandedKeys.value = keys.map(String);

  if (!info?.expanded) return;
  const folderId = info?.node?.id ?? info?.node?.key;
  if (folderId === undefined || folderId === null) return;
  if (String(folderId).startsWith('root-')) return;

  const folder = findFolderById(allFolderRoots.value, folderId);
  if (!folder || folder.childrenLoaded) return;

  try {
    await loadFolderChildren(folder);
  } catch (e) {
    console.error('加载子文件夹失败:', e);
  }
};

type DragOverDropMode = 'inside' | 'gap';
const dragOverDropMode = ref<DragOverDropMode>('inside');

const allowDrop = ({ dragNode, dropNode, dropPosition }: { dragNode: any; dropNode: any; dropPosition: number }) => {
  const dragId = dragNode?.eventKey ?? dragNode?.dataRef?.id ?? dragNode?.key ?? dragNode?.id;
  const dragFolder = findFolderById(allFolderRoots.value, dragId);
  if (isLibraryNodeByMeta(dragFolder)) return false;
  const dropId = dropNode?.eventKey ?? dropNode?.dataRef?.id ?? dropNode?.key ?? dropNode?.id;
  if (String(dropId).startsWith('root-')) return false;
  // 空文件夹在 rc-tree 中可能会被判定为 leaf，此时若按 leaf 阻断会导致无法“拖入该空文件夹”
  const dataRef = dropNode?.dataRef ?? dropNode;
  const isEmptyFolder = Number(dataRef?.subfolder_count ?? 0) === 0 && !dataRef?.isVirtualRoot;
  // rc-tree 的 dropPosition: 0 通常表示“放入节点内部”，其它表示“gap/前后插入”
  const mode: DragOverDropMode = isEmptyFolder ? 'inside' : dropPosition === 0 ? 'inside' : 'gap';
  dragOverFolderId.value = String(dropId);
  dragOverDropMode.value = mode;
  return true;
};

const dragOverFolderId = ref<string>('');
const treeMoveSaving = ref(false);

const isDropTargetEmptyFolder = (node: any) => {
  if (!node || node?.isVirtualRoot) return false;
  return String(node.id) === dragOverFolderId.value && Number(node?.subfolder_count ?? 0) === 0;
};

const isDropTargetInside = (node: any) => {
  if (!node || node?.isVirtualRoot) return false;
  return String(node.id) === dragOverFolderId.value && dragOverDropMode.value === 'inside';
};

const isDropTargetGap = (node: any) => {
  if (!node || node?.isVirtualRoot) return false;
  return String(node.id) === dragOverFolderId.value && dragOverDropMode.value === 'gap';
};

const handleTreeDragEnter = (info: any) => {
  const nodeData = info?.node?.dataRef ?? info?.node;
  if (nodeData?.isEmptyTip) {
    dragOverFolderId.value = '';
    return;
  }
  const nodeId = nodeData?.id ?? nodeData?.key ?? nodeData?.eventKey;
  if (nodeId === undefined || nodeId === null) {
    dragOverFolderId.value = '';
    return;
  }
  if (String(nodeId).startsWith('root-')) {
    dragOverFolderId.value = '';
    return;
  }
  dragOverFolderId.value = String(nodeId);
};

const handleTreeDragEnd = () => {
  dragOverFolderId.value = '';
  dragOverDropMode.value = 'inside';
};

const onTreeDrop = async (info: any) => {
  const dragNode = info.dragNode;
  const dropNode = info.node;
  const dropToGap = info.dropToGap;

  const extractId = (n: any) => n?.eventKey ?? n?.dataRef?.id ?? n?.key ?? n?.id;
  const dragId = extractId(dragNode);

  if (dragId === undefined || dragId === null) {
    return;
  }

  // 防止拖拽虚拟根目录
  if (String(dragId).startsWith('root-')) {
    message.warning(t('无法移动根目录'));
    return;
  }
  const dragFolder = findFolderById(allFolderRoots.value, dragId);
  if (isLibraryNodeByMeta(dragFolder)) {
    message.warning(t('素材库节点不允许拖拽'));
    return;
  }
  // rc-tree 可能在某些情况下触发多次 drop；加锁避免重复请求
  if (treeMoveSaving.value) return;
  treeMoveSaving.value = true;

  const dropId = extractId(dropNode);
  let targetParentId: number | string = 0;

  if (dropToGap) {
    // 插入到节点前后（将拖拽节点居为目标的兄弟）
    if (String(dropId).startsWith('root-')) {
      return;
    }
    const targetFolder = findFolderById(allFolderRoots.value, dropId);
    if (!targetFolder) return;

    // 兼容空文件夹：某些情况下拖到空文件夹标题区也会被 rc-tree 判为 dropToGap=true
    // 这里优先按“放入该文件夹内部”处理，满足“拖入新建空文件夹”的场景
    if (Number(targetFolder?.subfolder_count ?? 0) === 0) {
      targetParentId = dropId;
    } else {
      targetParentId = targetFolder.parent_id;
    }

    if (targetParentId === undefined) {
      message.error(t('文件夹数据状态不一致，请刷新页面后再试'));
      return;
    }
  } else {
    // dropToGap=false：直接放入目标节点（有子目录的文件夹，或 isLeaf:true 的空文件夹）
    targetParentId = dropId;
  }

  if (dropToGap && targetParentId === 0) {
    const targetFolder = findFolderById(allFolderRoots.value, dropId);
    if (targetFolder.library_type === 1) targetParentId = 'root-enterprise';
    else if (targetFolder.owner_id && targetFolder.owner_id !== userStore.info?.id) targetParentId = 'root-department';
    else targetParentId = 'root-my';
  }

  let targetOwnerId: string | number | undefined = undefined;
  if (String(targetParentId).startsWith('root-')) {
    if (targetParentId === 'root-enterprise') {
      const firstEnt = enterpriseFolders.value[0];
      targetOwnerId = firstEnt ? firstEnt.owner_id : userStore.info?.tenant_id;
    } else if (targetParentId === 'root-department') {
      const firstDept = departmentFolders.value[0];
      targetOwnerId = firstDept ? firstDept.owner_id : userStore.info?.id;
    }
  }

  try {
    const hide = message.loading(t('正在移动文件夹并级联路径分布...'), 0);
    await moveMaterialLibraryFolder(dragId, {
      target_parent_id: targetParentId,
      target_owner_id: targetOwnerId,
    });
    hide();
    message.success(t('移动成功'));
    await loadFolders();
  } catch (e: any) {
    message.error(e?.response?.data?.message || t('移动失败'));
  } finally {
    dragOverFolderId.value = '';
    dragOverDropMode.value = 'inside';
    treeMoveSaving.value = false;
  }
};

// 删除文件夹（软删）
const deleteFolder = async (folderId: any) => {
  if (folderId === null || folderId === undefined || folderId === '') return;

  try {
    await deleteMaterialLibraryFolder(folderId);

    await loadFolders();
    const stillExists = folderSelectOptions.value.some((f) => String(f.id) === String(selectedFolder.value));
    if (selectedFolder.value !== 'favorites' && !stillExists) selectedFolder.value = 'favorites';
    await loadTableData();
  } catch (e) {
    console.error('删除文件夹失败:', e);
  }
};

// 批量删除
const openBatchDeleteConfirm = async () => {
  if (!selectedRows.value.length) {
    message.warning(t('请先选择至少一条素材'));
    return;
  }

  try {
    await batchMaterialActions({
      action_type: 'DELETE',
      resource_ids: selectedRows.value.map((r) => r.id),
      payload: {},
    });
    closeBatchTagModal();
    await loadTableData();
  } catch (e) {
    console.error('批量删除失败:', e);
  }
};

// 批量移动
const openBatchMoveModal = () => {
  if (!selectedRows.value.length) {
    message.warning(t('请先选择至少一条素材'));
    return;
  }

  // 默认目标：当前非 favorites 的文件夹（若当前是 favorites，则取第一个可选项）
  const defaultTarget =
    selectedFolder.value !== 'favorites' && selectedFolder.value !== null
      ? selectedFolder.value
      : folderSelectOptions.value[0]?.id ?? null;

  batchMoveTargetFolderId.value = defaultTarget;
  batchMoveModalVisible.value = true;
};

const closeBatchMoveModal = () => {
  batchMoveModalVisible.value = false;
  batchMoveTargetFolderId.value = null;
};

const handleBatchMoveConfirm = async () => {
  if (!batchMoveTargetFolderId.value && batchMoveTargetFolderId.value !== 0) {
    message.warning(t('请选择目标文件夹'));
    return;
  }

  batchMoveSaving.value = true;
  try {
    await batchMaterialActions({
      action_type: 'MOVE',
      resource_ids: selectedRows.value.map((r) => r.id),
      payload: {
        target_folder_id: batchMoveTargetFolderId.value,
      },
    });
    closeBatchMoveModal();
    await loadTableData();
  } catch (e) {
    console.error('批量移动失败:', e);
  } finally {
    batchMoveSaving.value = false;
  }
};

// 打开批量标签弹窗
const openBatchTagModal = () => {
  if (!selectedRows.value.length) {
    // 这里简单用浏览器提示, 如果有全局 message 组件可以替换
    message.warning(t('请先选择至少一条素材'));
    return;
  }
  batchMode.value = 'manual';
  editScope.value = 'all';
  applyMode.value = 'assign';
  manualBatchTags.value = [];
  aiLoaded.value = false;
  editableBatchRows.value = selectedRows.value.map((item) => ({
    id: item.id,
    name: item.name,
    // 克隆当前标签数组, 避免直接修改表格数据
    tags: Array.isArray(item.tags) ? [...item.tags] : [],
  }));
  batchTagModalVisible.value = true;
};

const closeBatchTagModal = () => {
  batchTagModalVisible.value = false;
};

const removeManualTag = (tag: string) => {
  manualBatchTags.value = manualBatchTags.value.filter((t) => t !== tag);
};

const clearManualTags = () => {
  manualBatchTags.value = [];
};

// 将后端下拉接口加载的 tags 转为下拉选项
const tagOptions = computed(() => {
  return (tags.value || [])
    .map((tag: any) => ({
      label: tag.name || '',
      value: tag.name || '',
    }));
});

const filterTagOption = (input: string, option: any) =>
  (option?.label ?? '').toLowerCase().includes(input.toLowerCase());

// 简单模拟智能识别标签, 真实项目可替换为后端接口调用
const runAiRecognize = async () => {
  if (!editableBatchRows.value.length) return;
  aiLoading.value = true;
  try {
    editableBatchRows.value = editableBatchRows.value.map((row) => {
      const resultTags = new Set(row.tags || []);
      const lowerName = String(row.name || '').toLowerCase();
      if (lowerName.includes('video') || lowerName.includes('mp4')) {
        resultTags.add('视频');
      }
      if (lowerName.includes('banner') || lowerName.includes('jpg') || lowerName.includes('png')) {
        resultTags.add('图片');
      }
      return {
        ...row,
        tags: Array.from(resultTags),
      };
    });
    aiLoaded.value = true;
  } finally {
    aiLoading.value = false;
  }
};

// 保存批量标签（调用后端批量接口）
const handleBatchTagSave = async () => {
  if (batchMode.value === 'manual' && applyMode.value === 'assign' && !manualBatchTags.value.length) {
    message.warning(t('请先选择统一标签'));
    return;
  }

  batchSaving.value = true;
  try {
    const resourceIds = selectedRows.value.map((r) => r.id);

    if (applyMode.value === 'clear') {
      await batchMaterialActions({
        action_type: 'CLEAR_TAGS',
        resource_ids: resourceIds,
        payload: {},
      });
    } else {
      if (batchMode.value === 'manual') {
        await batchMaterialActions({
          action_type: 'SET_TAGS',
          resource_ids: resourceIds,
          payload: {
            tags: manualBatchTags.value,
          },
        });
      } else {
        const materialsTags = editableBatchRows.value.map((r) => ({
          material_id: r.id,
          tags: Array.isArray(r.tags) ? r.tags : [],
        }));

        await batchMaterialActions({
          action_type: 'SET_TAGS',
          resource_ids: resourceIds,
          payload: {
            materials_tags: materialsTags,
          },
        });
      }
    }

    closeBatchTagModal();
    await loadTableData();
  } finally {
    batchSaving.value = false;
  }
};

const currentUploadMode = ref<'file' | 'folder'>('file');

// 显示上传弹窗
const showUploadModal = (mode: 'file' | 'folder' = 'file') => {
  currentUploadMode.value = mode;
  uploadModalVisible.value = true;
};

// 右键菜单（文件夹树）
const handleFolderContextMenuClick = (action: string, node: any) => {
  if (!node || node?.isVirtualRoot) return;
  if (action === 'create-child') {
    showCreateFolderModal(node?.id);
    return;
  }
  if (action === 'upload-material') {
    if (node?.id !== null && node?.id !== undefined && node?.id !== '') {
      selectedFolder.value = node.id;
    }
    showUploadModal('file');
    return;
  }
  if (action === 'delete') {
    deleteFolder(String(node?.id));
  }
};

// 显示新建文件夹弹窗
const showCreateFolderModal = (parentFolderId?: string | number | null) => {
  const target =
    parentFolderId !== undefined
      ? parentFolderId
      : (selectedFolder.value !== 'favorites' ? selectedFolder.value : null);
  createFolderParentId.value = target;
  createFolderModalVisible.value = true;
};

// 显示新建素材库弹窗
const showCreateLibraryModal = (type: 'my' | 'enterprise') => {
  createLibraryType.value = type;
  createLibraryModalVisible.value = true;
};

// 加载表格数据
const loadTableData = async () => {
  const currentLoadSeq = ++latestTableLoadSeq.value;
  loading.value = true;
  try {
    // 只要当前勾选了需要投放统计的字段，就必须打开后端 with_statistics，
    // 否则默认列里即使有“制作费用”也会拿不到数据。
    const needStatistics =
      showDeliveryData.value || selectedColumnKeys.value.some((k) => statisticsKeySet.value.has(String(k)));

    const pageNo = pagination.value.current;
    const pageSize = pagination.value.pageSize;
    const shouldSendSort = showFilterField('sortField') || showFilterField('sortOrder');
    const params: Record<string, any> = {
      pageNo,
      pageSize,
      designer_id: showFilterField('designer') ? filters.value.designer : undefined,
      creator_id: showFilterField('creator') ? filters.value.creator : undefined,
      tag_ids: showFilterField('tags') ? filters.value.tags : undefined,
      global_search: globalSearchText.value,
      include_subfolders: showSubfolders.value ? 1 : 0,
      with_statistics: needStatistics ? 1 : 0,
      statistics_start_date:
        needStatistics && statisticsDateRange.value?.[0]
          ? statisticsDateRange.value[0].format('YYYY-MM-DD')
          : undefined,
      statistics_end_date:
        needStatistics && statisticsDateRange.value?.[1]
          ? statisticsDateRange.value[1].format('YYYY-MM-DD')
          : undefined,
      material_type: showFilterField('materialType') ? filters.value.materialType : undefined,
      material_group_id: showFilterField('materialGroupId')
        ? filters.value.materialGroupId
        : undefined,
      size_level: showFilterField('sizeLevel') ? filters.value.sizeLevel : undefined,
      rating_scores: showFilterField('rating') ? filters.value.rating : undefined,
      system_tag_ids: showFilterField('systemTags') ? filters.value.systemTagIds : undefined,
      system_tag_mode: showFilterField('systemTags') ? filters.value.systemTagMode : undefined,
      source: showFilterField('source') ? filters.value.source : undefined,
      audit_status: showFilterField('auditStatus') ? filters.value.auditStatus : undefined,
      sortField: shouldSendSort ? filters.value.sortField : undefined,
      sortOrder: shouldSendSort ? filters.value.sortOrder : undefined,
      create_time_preset: showFilterField('createTimePreset') ? filters.value.createTimePreset : undefined,
      reject_reason_option_ids: showFilterField('rejectReason') ? filters.value.rejectReason : undefined,
      create_start_date:
        showFilterField('createTimePreset') && filters.value.createTimeRange?.[0]
          ? filters.value.createTimeRange[0].format('YYYY-MM-DD')
          : undefined,
      create_end_date:
        showFilterField('createTimePreset') && filters.value.createTimeRange?.[1]
          ? filters.value.createTimeRange[1].format('YYYY-MM-DD')
          : undefined,
    };

    const allFolders = [
      ...(myFolders.value || []),
      ...(enterpriseFolders.value || []),
      ...(departmentFolders.value || []),
    ];
    const selectedFolderMeta =
      selectedFolder.value && selectedFolder.value !== 'favorites'
        ? findFolderById(allFolders, selectedFolder.value)
        : null;

    const result =
      selectedFolder.value === 'favorites'
        ? await getMaterialLibraryFavorites(params)
        : await getMaterialLibraryMaterials({
          ...params,
          folder_id: selectedFolder.value,
          ...(selectedFolderMeta?.owner_id ? { owner_id: selectedFolderMeta.owner_id } : {}),
        });

    // 忽略过期响应：避免勾选状态改变后，被旧请求结果覆盖。
    if (currentLoadSeq !== latestTableLoadSeq.value) return;

    tableData.value = result.data || [];
    pagination.value.total = result.totalCount || 0;
  } catch (error) {
    if (currentLoadSeq !== latestTableLoadSeq.value) return;
    console.error('加载数据失败:', error);
  } finally {
    if (currentLoadSeq !== latestTableLoadSeq.value) return;
    loading.value = false;
  }
};

// 表格变化
const handleTableChange = (pag: any, _filters: any, _sorter: any) => {
  pagination.value.current = pag.current;
  pagination.value.pageSize = pag.pageSize;
  loadTableData();
};

// 刷新表格
const reloadTable = () => {
  loadTableData();
};

const isCellEditableRecord = (record: any) => !!record && record.type !== 'folder';

const cellEditPersonSourceList = computed(() =>
  cellEditType.value === 'creatorId' ? (creators.value || []) : (designers.value || []),
);

const cellEditPersonSelectedName = computed(() => {
  if (!cellEditPersonSelectedId.value) return '';
  const found = (cellEditPersonSourceList.value || []).find(
    (item: any) => String(item?.id) === String(cellEditPersonSelectedId.value),
  );
  return found?.name || cellEditPersonSelectedId.value;
});

const cellEditPersonTreeData = computed(() => {
  const keyword = cellEditPersonKeyword.value.trim().toLowerCase();
  const children = (cellEditPersonSourceList.value || [])
    .filter((item: any) => {
      if (!keyword) return true;
      return String(item?.name || '').toLowerCase().includes(keyword);
    })
    .map((item: any) => ({
      key: String(item.id),
      title: item.name || String(item.id),
      isLeaf: true,
    }));
  return [{ key: 'person-root', title: t('Admin'), children }];
});

const cellEditTagTreeData = computed(() => {
  const keyword = cellEditTagKeyword.value.trim().toLowerCase();
  const allTags = (tags.value || [])
    .map((tag: any) => ({
      id: getTagId(tag),
      name: getTagName(tag),
      parentId: getTagParentId(tag),
    }))
    .filter((tag: any) => !!tag.id && !!tag.name);
  const idSet = new Set(allTags.map((tag: any) => tag.id));
  const nodesByParent = new Map<string | null, any[]>();
  allTags.forEach((tag: any) => {
    const parentKey = tag.parentId && idSet.has(tag.parentId) ? tag.parentId : null;
    if (!nodesByParent.has(parentKey)) nodesByParent.set(parentKey, []);
    nodesByParent.get(parentKey)?.push(tag);
  });
  const build = (parentId: string | null): any[] =>
    (nodesByParent.get(parentId) || [])
      .map((tag: any) => {
        const children = build(tag.id);
        const hit = !keyword || tag.name.toLowerCase().includes(keyword);
        const hasHitChild = children.length > 0;
        if (!hit && !hasHitChild) return null;
        return {
          key: tag.id,
          title: tag.name,
          children,
        };
      })
      .filter(Boolean);
  return [{ key: 'tag-root', title: t('全部标签'), children: build(null) }];
});

const openCellEditor = (type: CellEditType, record: any) => {
  if (!isCellEditableRecord(record)) return;
  cellEditType.value = type;
  cellEditMaterialId.value = record.id;
  cellEditSaving.value = false;

  cellEditCostValue.value =
    record.productionCost === null || record.productionCost === undefined || record.productionCost === ''
      ? ''
      : String(record.productionCost);
  cellEditRemarkValue.value = String(record.remarks || '');
  cellEditPersonKeyword.value = '';
  cellEditTagKeyword.value = '';
  cellEditPersonSelectedId.value = '';
  cellEditTagCheckedIds.value = [];

  if (type === 'designerId') {
    cellEditPersonSelectedId.value = record.designerId ? String(record.designerId) : '';
  } else if (type === 'creatorId') {
    cellEditPersonSelectedId.value = record.creatorId ? String(record.creatorId) : '';
  } else if (type === 'tags') {
    const nameToId = new Map<string, string>();
    (tags.value || []).forEach((tag: any) => {
      nameToId.set(getTagName(tag), getTagId(tag));
    });
    cellEditTagCheckedIds.value = (record.tags || [])
      .map((tagName: string) => nameToId.get(String(tagName)))
      .filter(Boolean);
  }

  cellEditModalVisible.value = true;
};

const closeCellEditModal = () => {
  cellEditModalVisible.value = false;
  cellEditSaving.value = false;
  cellEditMaterialId.value = null;
};

const handleCellEditPersonSelect = (selectedKeys: any[]) => {
  const selected = Array.isArray(selectedKeys) ? String(selectedKeys[0] || '') : '';
  if (!selected || selected === 'person-root') return;
  cellEditPersonSelectedId.value = selected;
};

const clearCellEditPersonSelection = () => {
  cellEditPersonSelectedId.value = '';
};

const handleCellEditTagCheck = (checkedKeys: any) => {
  const keys = Array.isArray(checkedKeys) ? checkedKeys : checkedKeys?.checked;
  cellEditTagCheckedIds.value = (Array.isArray(keys) ? keys : [])
    .map((key: any) => String(key))
    .filter((key: string) => key !== 'tag-root');
};

const clearCellEditTagSelection = () => {
  cellEditTagCheckedIds.value = [];
};

const parseAndValidateProductionCost = () => {
  const raw = cellEditCostValue.value.trim();
  if (!raw) return null;
  if (!/^\d+(\.\d{1,2})?$/.test(raw)) return undefined;
  const n = Number(raw);
  if (!Number.isFinite(n) || n < 0) return undefined;
  return n;
};

const handleCellEditConfirm = async () => {
  if (!cellEditMaterialId.value) return;
  const materialId = cellEditMaterialId.value;
  cellEditSaving.value = true;
  try {
    if (cellEditType.value === 'productionCost') {
      const parsed = parseAndValidateProductionCost();
      if (parsed === undefined) {
        message.warning(t('制作费用仅支持非负数，最多两位小数'));
        return;
      }
      await updateMaterialProductionCost(materialId, { production_cost: parsed });
    } else if (cellEditType.value === 'designerId') {
      await updateMaterial(materialId, { designer_id: cellEditPersonSelectedId.value || null });
    } else if (cellEditType.value === 'creatorId') {
      await updateMaterial(materialId, { creator_id: cellEditPersonSelectedId.value || null });
    } else if (cellEditType.value === 'remarks') {
      await updateMaterial(materialId, { remarks: cellEditRemarkValue.value.trim() || null });
    } else if (cellEditType.value === 'tags') {
      if (!cellEditTagCheckedIds.value.length) {
        await batchMaterialActions({
          action_type: 'CLEAR_TAGS',
          resource_ids: [materialId],
          payload: {},
        });
      } else {
        await batchMaterialActions({
          action_type: 'SET_TAGS',
          resource_ids: [materialId],
          payload: { tags: cellEditTagCheckedIds.value },
        });
      }
    }
    closeCellEditModal();
    await loadTableData();
  } catch (error) {
    console.error('单元格编辑保存失败:', error);
  } finally {
    cellEditSaving.value = false;
  }
};

// 编辑素材
const editMaterial = (record: any) => {
  editingMaterialId.value = record.id;
  editFormData.value = {
    material_name: record.name ?? '',
    folder_id: record.folderId != null ? String(record.folderId) : null,
    designer_id: record.designerId != null ? String(record.designerId) : null,
    creator_id: record.creatorId != null ? String(record.creatorId) : null,
    material_group_id: record.materialGroupId != null ? String(record.materialGroupId) : null,
    remarks: record.remarks ?? '',
    xmp_tag: record.xmpTag ?? null,
    mindworks_locked: !!record.mindworksLocked,
  };
  editMaterialModalVisible.value = true;
};

const closeEditMaterialModal = () => {
  editMaterialModalVisible.value = false;
  editSaving.value = false;
  editingMaterialId.value = null;
};

const handleEditMaterialSave = async () => {
  if (!editingMaterialId.value) return;

  if (!editFormData.value.material_name.trim()) {
    message.warning(t('请输入素材名称'));
    return;
  }

  editSaving.value = true;
  try {
    await updateMaterial(editingMaterialId.value, {
      material_name: editFormData.value.material_name,
      folder_id: editFormData.value.folder_id,
      designer_id: editFormData.value.designer_id,
      creator_id: editFormData.value.creator_id,
      material_group_id: editFormData.value.material_group_id,
      remarks: editFormData.value.remarks,
      xmp_tag: editFormData.value.xmp_tag,
      mindworks_locked: editFormData.value.mindworks_locked,
    });
    closeEditMaterialModal();
    await loadTableData();
  } catch (e) {
    console.error('编辑素材失败:', e);
  } finally {
    editSaving.value = false;
  }
};

// 删除素材
const deleteMaterial = async (record: any) => {
  try {
    await deleteMaterialById(record.id);
    await loadTableData();
  } catch (e) {
    console.error('删除素材失败:', e);
  }
};

// 7.9 审核：通过（approve）
const handleAuditApprove = async (record: any) => {
  try {
    await auditMaterial(record.id, { status: 1 });
    await loadTableData();
  } catch (e) {
    console.error('审核通过失败:', e);
  }
};

// 7.9 审核：拒绝（reject，需填写原因）
const openAuditReject = (record: any) => {
  auditRejectMaterialId.value = record.id;
  auditRejectForm.value = { reject_reason: '' };
  auditRejectModalVisible.value = true;
};

const closeAuditRejectModal = () => {
  auditRejectModalVisible.value = false;
  auditRejectSaving.value = false;
  auditRejectMaterialId.value = null;
};

const handleAuditReject = async () => {
  if (!auditRejectMaterialId.value) return;
  if (!auditRejectForm.value.reject_reason?.trim()) {
    message.warning(t('请填写拒绝原因'));
    return;
  }
  auditRejectSaving.value = true;
  try {
    await auditMaterial(auditRejectMaterialId.value, {
      status: 2,
      reject_reason: auditRejectForm.value.reject_reason,
    });
    closeAuditRejectModal();
    await loadTableData();
  } catch (e) {
    console.error('审核拒绝失败:', e);
  } finally {
    auditRejectSaving.value = false;
  }
};

// 7.14 使用记录
const openUsagesModal = async (record: any) => {
  usagesModalVisible.value = true;
  usagesData.value = [];
  try {
    const res = await getMaterialUsages(record.id);
    usagesData.value = res.data || [];
  } catch (e) {
    console.error('获取使用记录失败:', e);
  }
};

const closeUsagesModal = () => {
  usagesModalVisible.value = false;
  usagesData.value = [];
};

// 7.10 导出
const handleExport = async (format: 'csv' | 'xlsx') => {
  try {
    const payload: Record<string, any> = {
      format,
      folder_id: selectedFolder.value,
      include_subfolders: showSubfolders.value,
      designer_id: filters.value.designer,
      creator_id: filters.value.creator,
      tag_ids: filters.value.tags,
      global_search: globalSearchText.value,
      material_group_id: filters.value.materialGroupId,
      material_type: filters.value.materialType,
      size_level: filters.value.sizeLevel,
      rating_scores: filters.value.rating,
      system_tag_ids: filters.value.systemTagIds,
      system_tag_mode: filters.value.systemTagMode,
      source: filters.value.source,
      audit_status: filters.value.auditStatus,
      create_time_preset: filters.value.createTimePreset,
      reject_reason_option_ids: filters.value.rejectReason,
      create_start_date: filters.value.createTimeRange?.[0]?.format('YYYY-MM-DD'),
      create_end_date: filters.value.createTimeRange?.[1]?.format('YYYY-MM-DD'),
      // MVP：如果用户选了行，优先导出这些行；否则导出当前筛选范围
      resource_ids: selectedRows.value.length ? selectedRows.value.map((r) => r.id) : [],
      limit: 1000,
    };

    const res = await exportMaterials(payload);
    const csv = res.data?.csv || '';
    const filename = res.data?.filename || `xmp-materials.${format === 'csv' ? 'csv' : 'csv'}`;

    const blob = new Blob([csv], { type: 'text/csv;charset=utf-8;' });
    const url = window.URL.createObjectURL(blob);
    const a = document.createElement('a');
    a.href = url;
    a.download = filename;
    document.body.appendChild(a);
    a.click();
    a.remove();
    window.URL.revokeObjectURL(url);
  } catch (e) {
    console.error('导出失败:', e);
  }
};

// 7.12 自动更新 XMP 标签
const handleAutoUpdateXmpTags = async () => {
  autoUpdateLoading.value = true;
  try {
    const start = statisticsDateRange.value?.[0]?.format('YYYY-MM-DD');
    const end = statisticsDateRange.value?.[1]?.format('YYYY-MM-DD');
    const res = await autoUpdateXmpTags({ start_date: start, end_date: end });
    const updated = res.data?.updated_count ?? 0;
    message.success(`已更新 ${updated} 条素材的 XMP 标签`);
    await loadTableData();
  } catch (e) {
    console.error('自动更新 XMP 标签失败:', e);
  } finally {
    autoUpdateLoading.value = false;
  }
};

// 7.13 媒体同步
const openMediaSyncModal = () => {
  mediaSyncResult.value = null;
  mediaSyncForm.value = {
    channel: 'Meta',
    account_id: undefined,
  };
  mediaSyncModalVisible.value = true;
};

const closeMediaSyncModal = () => {
  mediaSyncModalVisible.value = false;
  mediaSyncLoading.value = false;
  mediaSyncResult.value = null;
  if (mediaSyncPollTimer) {
    clearInterval(mediaSyncPollTimer);
    mediaSyncPollTimer = null;
  }
};

const handleStartMediaSync = async () => {
  if (!mediaSyncForm.value.account_id || !mediaSyncForm.value.channel) {
    message.warning(t('请输入账户ID与渠道'));
    return;
  }

  mediaSyncLoading.value = true;
  try {
    const payload: Record<string, any> = {
      account_id: mediaSyncForm.value.account_id,
      channel: mediaSyncForm.value.channel,
    };

    if (selectedFolder.value !== 'favorites' && selectedFolder.value != null) {
      payload.materials_folder_id = selectedFolder.value;
    }

    const res = await syncMediaMaterials(payload);
    const syncId = res.data?.sync_id;
    if (!syncId) throw new Error('sync_id missing');

    mediaSyncResult.value = { sync_id: syncId };

    if (mediaSyncPollTimer) clearInterval(mediaSyncPollTimer);
    let attempts = 0;
    mediaSyncPollTimer = setInterval(async () => {
      attempts++;
      try {
        const st = await getMediaMaterialsSyncStatus(syncId);
        const data = st.data;
        mediaSyncResult.value = data;
        if (data.sync_status !== 1 || attempts >= 60) {
          if (mediaSyncPollTimer) clearInterval(mediaSyncPollTimer);
          mediaSyncPollTimer = null;
        }
      } catch (e) {
        // 忽略单次轮询失败，继续重试
        console.error('轮询同步状态失败:', e);
      }
    }, 1000);
  } catch (e) {
    console.error('启动媒体同步失败:', e);
  } finally {
    mediaSyncLoading.value = false;
  }
};

// 上传成功
const handleUploadSuccess = () => {
  loadFolders().then(() => {
    loadTableData();
  });
};

// 创建文件夹成功
const handleCreateFolderSuccess = () => {
  loadFolders().then(() => {
    loadTableData();
  });
};

// 创建素材库成功
const handleCreateLibrarySuccess = () => {
  // 重新加载文件夹树
  loadFolders().then(() => {
    loadTableData();
  });
};

// 加载文件夹
const loadFolders = async () => {
  try {
    const userInfo: any = userStore.info || {};
    const userId = userInfo?.id;
    const enterpriseId = userInfo?.enterprise_id ?? userInfo?.enterpriseId;
    const departmentId = userInfo?.department_id ?? userInfo?.departmentId;

    const normalizeRoots = (rows: any[]) => (rows || []).map((f: any) => normalizeFolderNode(f));


    const myParams: Record<string, any> = { library_type: 0 };
    if (userId !== undefined && userId !== null && userId !== '') {
      myParams.owner_id = userId;
    }

    const enterpriseParams: Record<string, any> = { library_type: 1 };
    if (enterpriseId !== undefined && enterpriseId !== null && enterpriseId !== '') {
      enterpriseParams.owner_id = enterpriseId;
    }

    const departmentParams: Record<string, any> = { library_type: 0 };
    if (departmentId !== undefined && departmentId !== null && departmentId !== '') {
      departmentParams.owner_id = departmentId;
    }

    const [myRes, enterpriseRes, departmentRes] = await Promise.all([
      getMaterialLibraryFolders(myParams),
      getMaterialLibraryFolders(enterpriseParams),
      departmentId ? getMaterialLibraryFolders(departmentParams) : Promise.resolve({ data: [], totalCount: 0 }),
    ]);

    const hasDefaultRoot = (rows: any[]) =>
      (rows || []).some((row: any) => Number(row?.parent_id ?? -1) === 0 && isDefaultLibraryByName(row?.name || row?.folder_name));

    let needReloadMy = false;
    let needReloadEnterprise = false;
    if (!hasDefaultRoot(myRes.data || []) && myParams.owner_id !== undefined) {
      try {
        await createMaterialLibraryFolder({
          parent_id: 0,
          folder_name: t(DEFAULT_LIBRARY_NAME),
          library_type: 0,
          owner_id: myParams.owner_id,
        });
        needReloadMy = true;
      } catch (e: any) {
        if (!String(e?.response?.data?.message || '').includes('同级目录已存在同名文件夹')) {
          console.warn('ensure my default library failed:', e);
        }
      }
    }
    if (!hasDefaultRoot(enterpriseRes.data || []) && enterpriseParams.owner_id !== undefined) {
      try {
        await createMaterialLibraryFolder({
          parent_id: 0,
          folder_name: t(DEFAULT_LIBRARY_NAME),
          library_type: 1,
          owner_id: enterpriseParams.owner_id,
        });
        needReloadEnterprise = true;
      } catch (e: any) {
        if (!String(e?.response?.data?.message || '').includes('同级目录已存在同名文件夹')) {
          console.warn('ensure enterprise default library failed:', e);
        }
      }
    }

    const [myRows, enterpriseRows] = await Promise.all([
      needReloadMy ? getMaterialLibraryFolders(myParams).then((r: any) => r?.data || []) : Promise.resolve(myRes.data || []),
      needReloadEnterprise ? getMaterialLibraryFolders(enterpriseParams).then((r: any) => r?.data || []) : Promise.resolve(enterpriseRes.data || []),
    ]);

    myFolders.value = normalizeRoots(myRows || []);
    enterpriseFolders.value = normalizeRoots(enterpriseRows || []);
    departmentFolders.value = normalizeRoots(departmentRes.data || []);

    // 页面加载时预取整棵树的子文件夹信息，保证“文件夹/素材”数量初始即正确
    await preloadFolderTreeCounts([
      ...myFolders.value,
      ...enterpriseFolders.value,
      ...departmentFolders.value,
    ]);

    // 首次进入：优先选中第一层文件夹；没有文件夹才默认“我的收藏”
    if (!selectedFolder.value) {
      selectedFolder.value =
        myFolders.value[0]?.id ??
        enterpriseFolders.value[0]?.id ??
        departmentFolders.value[0]?.id ??
        'favorites';
    }
  } catch (error) {
    console.error('加载文件夹失败:', error);
  }
};

// 加载筛选选项
const loadFilterOptions = async () => {
  try {
    const [dRes, tRes, cRes, gRes, rrRes] = await Promise.all([
      getMaterialLibraryDesigners(),
      getMaterialLibraryTags(),
      getMaterialLibraryCreators(),
      getMaterialLibraryMaterialGroups(),
      getMaterialLibraryRejectReasonOptions(),
    ]);
    designers.value = dRes.data || [];
    tags.value = tRes.data || [];
    creators.value = cRes.data || [];
    materialGroups.value = gRes.data || [];
    const rrRows = Array.isArray(rrRes?.data?.data) ? rrRes.data.data : (Array.isArray(rrRes?.data) ? rrRes.data : []);
    rejectReasonOptionList.value = rrRows
      .map((x: any) => ({ value: Number(x?.value), label: String(x?.label ?? '') }))
      .filter((x: any) => Number.isFinite(x.value) && x.label);
  } catch (error) {
    console.error('加载筛选选项失败:', error);
  }
};

onMounted(() => {
  void loadColumnTemplates();
  void loadSystemTags();
  loadFolders().then(() => {
    loadFilterTemplates();
    loadTableData();
    loadFilterOptions();
  });
});

// 监听全局搜索文本变化，触发搜索
watch(globalSearchText, (newVal, oldVal) => {
  if (newVal !== oldVal) {
    pagination.value.current = 1;
    loadTableData();
  }
});
</script>

<style lang="less" scoped>
.material-library-container {
  display: flex;
  height: calc(100vh - 120px);
  gap: 16px;

  .folder-sidebar {
    width: 280px;
    background: #fff;
    border-radius: 4px;
    padding: 16px;
    display: flex;
    flex-direction: column;
    overflow: hidden;

    .folder-search {
      margin-bottom: 16px;
    }

    .folder-tree {
      flex: 1;
      overflow-y: auto;
      padding-right: 2px;

      .folder-ant-tree {
        :deep(.ant-tree-treenode) {
          width: 100%;
        }

        :deep(.ant-tree-node-content-wrapper) {
          width: calc(100% - 18px);
          border-radius: 3px;
          min-height: 34px;
          padding: 6px 8px;
        }

        :deep(.ant-tree-node-content-wrapper:hover) {
          background: #f5f7fb;
        }

        :deep(.ant-tree-node-content-wrapper.ant-tree-node-selected) {
          background: #dbe8ff;
          color: #1d4ea3;
        }

        :deep(.ant-tree-switcher) {
          width: 14px;
          display: inline-flex;
          align-items: center;
          justify-content: center;
          color: #5d6472;
          font-size: 11px;
        }

        :deep(.ant-tree-indent-unit) {
          width: 12px;
        }

        :deep(.ant-tree-indent-unit:not(:first-child)) {
          border-left: 1px dashed #d8dde8;
        }

        :deep(.ant-tree-node-content-wrapper:hover) .folder-tree-delete,
        :deep(.ant-tree-node-content-wrapper.ant-tree-node-selected) .folder-tree-delete {
          display: inline-flex;
        }

        /* 强化原生拖拽的视觉提示，解决反馈不明显的问题 */
        :deep(.ant-tree-drop-indicator) {
          height: 3px;
          background-color: #1890ff;
        }

        :deep(.ant-tree-node-content-wrapper.drag-over) {
          background-color: #e6f7ff;
          border: 1px dashed #1890ff;
        }
      }

      .folder-tree-title-row {
        display: flex;
        align-items: center;
        width: 100%;
        min-width: 0;
        gap: 6px;
        border-radius: 4px;
        transition: background-color 0.15s ease, box-shadow 0.15s ease;
      }

      .folder-header-virtual {
        display: flex;
        align-items: center;
        gap: 8px;
        width: 100%;
        padding: 7px 10px;
        font-weight: 600;
        color: #3a3f4a;
        background: #f1f3f6;
        border-radius: 3px;
        margin-bottom: 4px;
      }

      .folder-header-virtual :deep(.folder-count) {
        margin-left: auto;
        color: #999;
        font-size: 12px;
      }

      .folder-header-virtual :deep(.ant-btn-link) {
        padding-inline: 0;
        margin-left: 8px;
      }

      .folder-header-virtual-icon {
        margin-right: 8px;
      }

      .folder-header-virtual-title {
        font-weight: 600;
      }

      .folder-tree-title-row.drop-target-empty-folder {
        background: #f0f7ff;
        box-shadow: inset 0 0 0 1px #91caff;
      }

      .folder-tree-title-row.drop-target-inside-folder {
        background: #e6f7ff;
        box-shadow: inset 0 0 0 1px #1890ff;
      }

      .folder-tree-title-row.drop-target-gap-folder {
        background: #fff7e6;
        box-shadow: inset 0 0 0 1px #faad14;
      }

      .folder-tree-folder-icon-wrap {
        display: inline-flex;
        align-items: center;
      }

      .folder-tree-folder-icon {
        color: #d8a227;
        font-size: 14px;
      }

      :deep(.ant-tree-node-content-wrapper.ant-tree-node-selected) .folder-tree-folder-icon {
        color: #1890ff;
      }

      .folder-tree-title-text {
        flex: 1;
        min-width: 0;
        overflow: hidden;
        text-overflow: ellipsis;
        white-space: nowrap;
      }

      .folder-tree-right {
        margin-left: auto;
        display: inline-flex;
        align-items: center;
        gap: 4px;
        flex-shrink: 0;
      }

      .folder-drop-hint {
        display: inline-flex;
        align-items: center;
        padding: 0 6px;
        height: 18px;
        border-radius: 9px;
        background: #1677ff;
        color: #fff;
        font-size: 11px;
        line-height: 18px;
        flex-shrink: 0;
      }

      .folder-drop-hint-inside {
        background: #1677ff;
      }

      .folder-drop-hint-gap {
        background: #faad14;
      }

      .folder-tree-delete {
        color: #1677ff;
        font-size: 12px;
        display: none;
      }

      .folder-tree-metrics {
        display: inline-flex;
        align-items: center;
        gap: 6px;
      }

      .folder-tree-count {
        color: #999;
        font-size: 12px;
      }

      .folder-tree-material {
        color: #b0b7c3;
        font-size: 12px;
      }

      .metric-item {
        display: inline-flex;
        align-items: center;
        gap: 2px;
      }

      .metric-icon {
        font-size: 11px;
      }

      .metric-value {
        min-width: 10px;
        text-align: right;
      }

      @media (max-width: 1366px) {
        .metric-label {
          display: none;
        }

        .folder-tree-metrics {
          gap: 4px;
        }
      }

      .folder-item {
        min-height: 36px;
        padding: 7px 10px;
        cursor: pointer;
        display: flex;
        align-items: center;
        gap: 8px;
        border-left: 3px solid transparent;
        border-radius: 3px;
        margin-bottom: 3px;
        color: #2b2f36;

        &:hover {
          background: #f5f7fb;
        }

        &.active {
          border-left-color: #1677ff;
          background: #dbe8ff;
          color: #1d4ea3;

          svg {
            color: #1677ff;
          }
        }

        .folder-count {
          margin-left: auto;
          color: #999;
          font-size: 12px;
        }

        svg {
          flex-shrink: 0;
          color: #d8a227;
        }
      }

      .folder-section {
        margin-bottom: 10px;
        border-radius: 3px;
        background: transparent;

        .folder-header {
          display: flex;
          align-items: center;
          gap: 8px;
          padding: 7px 10px;
          font-weight: 600;
          color: #3a3f4a;
          border-bottom: none;
          background: #f1f3f6;
          border-radius: 3px;
          margin-bottom: 4px;

          .folder-count {
            margin-left: auto;
            color: #999;
            font-size: 12px;
          }
        }
      }
    }

    .storage-info {
      padding-top: 16px;
      border-top: 1px solid #f0f0f0;
      color: #999;
      font-size: 12px;
    }
  }

  .main-content {
    flex: 1;
    background: #fff;
    border-radius: 4px;
    padding: 16px;
    display: flex;
    flex-direction: column;
    overflow: hidden;

    .breadcrumb-bar {
      display: flex;
      justify-content: space-between;
      align-items: center;
      margin-bottom: 16px;
      padding-bottom: 16px;
      border-bottom: 1px solid #f0f0f0;

      .breadcrumb {
        color: #666;
        display: flex;
        align-items: center;
        min-width: 0;
        gap: 6px;
      }

      .breadcrumb-label {
        color: #888;
      }

      .breadcrumb-items {
        min-width: 0;
      }

      .breadcrumb-link {
        color: #1677ff;
        white-space: nowrap;
      }

      .breadcrumb-current {
        color: #999;
        white-space: nowrap;
        cursor: default;
      }

      .breadcrumb-sep {
        color: #bfbfbf;
      }

      .global-search {
        width: 300px;
      }
    }

    .filter-bar,
    .section-filter {
      display: flex;
      justify-content: space-between;
      flex-direction: column;
      align-items: stretch;
      margin-bottom: 16px;

      .filter-left {
        display: flex;
        gap: 8px;
        align-items: flex-start;
        flex-direction: column;
        width: 100%;

        .filter-template-bar {
          display: flex;
          gap: 8px;
          align-items: center;
          width: 100%;
          flex-wrap: wrap;
          margin-bottom: 0;

          .filter-template-item {
            display: flex;
            align-items: center;
            gap: 8px;
          }

          .filter-template-label {
            color: #666;
            font-size: 12px;
            white-space: nowrap;
          }
        }
      }

      .filter-actions {
        margin-top: 8px;
      }

      .filter-grid {
        display: grid;
        grid-template-columns: repeat(auto-fit, minmax(180px, 1fr));
        gap: 8px;
        width: 100%;
      }

      .filter-grid-bottom {
        grid-template-columns: repeat(auto-fit, minmax(180px, 1fr));
      }

      .filter-field {
        width: 100%;
      }

      .create-time-preset {
        min-width: 220px;
      }

      .filter-right {
        display: flex;
        gap: 16px;
        align-items: center;
      }
    }

    .template-modal-name {
      display: flex;
      align-items: center;
      gap: 12px;
      margin-bottom: 12px;
    }

    .template-modal-name-label {
      color: #666;
      font-size: 12px;
      width: 64px;
    }

    .template-checkbox-grid {
      display: grid;
      grid-template-columns: repeat(3, minmax(0, 1fr));
      gap: 8px 12px;
      align-items: start;
    }

    .filter-template-popover-content {
      padding: 12px 12px 8px;
      width: min(360px, calc(100vw - 32px));
      max-width: calc(100vw - 32px);
      min-width: 0;
      box-sizing: border-box;
      max-height: 520px;
      overflow: auto;
      background: #fff;
    }

    .template-popover-actions {
      display: flex;
      justify-content: flex-end;
      gap: 8px;
      margin-top: 12px;
      padding-top: 12px;
      border-top: 1px solid #f0f0f0;
    }

    @media (max-width: 560px) {
      .template-checkbox-grid {
        grid-template-columns: repeat(2, minmax(0, 1fr));
      }
    }

    @media (max-width: 380px) {
      .template-checkbox-grid {
        grid-template-columns: repeat(1, minmax(0, 1fr));
      }
    }

    .section-setting {
      .filter-right {
        display: flex;
        gap: 16px;
        align-items: center;
        flex-wrap: wrap;
      }
    }

    .action-bar {
      display: flex;
      gap: 8px;
      margin-bottom: 16px;
      align-items: center;

      .action-right {
        margin-left: auto;
        display: flex;
        gap: 8px;
      }
    }
  }
}

.filter-template-popover-content {
  padding: 12px 12px 8px;
  width: min(360px, calc(100vw - 32px)) !important;
  max-width: calc(100vw - 32px) !important;
  min-width: 0;
  box-sizing: border-box;
  max-height: 70vh;
  overflow: auto;
  background: #fff;
}

.filter-template-popover-overlay {
  padding: 0;
}

.template-checkbox-grid {
  display: grid;
  grid-template-columns: repeat(3, minmax(0, 1fr));
  gap: 8px 12px;
  align-items: start;
}

.template-checkbox-grid :deep(.ant-checkbox-wrapper) {
  display: flex;
  align-items: center;
  justify-content: flex-start;
  width: 100%;
}

.template-checkbox-grid :deep(.ant-checkbox) {
  margin-inline-end: 8px;
}

:deep(.filter-template-popover-content) {
  padding: 12px 12px 8px;
  width: min(400px, calc(100vw - 32px)) !important;
  max-width: calc(100vw - 32px) !important;
  min-width: 0;
  box-sizing: border-box;
  max-height: 70vh;
  overflow: auto;
  background: #fff;
}

:deep(.template-checkbox-grid) {
  display: grid !important;
  grid-template-columns: repeat(3, minmax(0, 1fr)) !important;
  gap: 8px 12px !important;
  align-items: start;
}

:deep(.template-checkbox-grid .ant-checkbox-wrapper) {
  display: flex !important;
  align-items: center;
  justify-content: flex-start;
  width: 100%;
  min-width: 0;
  flex-wrap: nowrap;
  white-space: nowrap;
}

:deep(.template-checkbox-grid .ant-checkbox-wrapper > span) {
  white-space: nowrap;
}

:deep(.template-checkbox-grid .ant-checkbox + span) {
  white-space: nowrap;
  overflow: hidden;
  text-overflow: ellipsis;
}

@media (max-width: 560px) {
  :deep(.template-checkbox-grid) {
    grid-template-columns: repeat(2, minmax(0, 1fr)) !important;
  }
}

@media (max-width: 380px) {
  :deep(.template-checkbox-grid) {
    grid-template-columns: repeat(1, minmax(0, 1fr)) !important;
  }
}

.template-popover-actions {
  display: flex;
  justify-content: flex-end;
  gap: 8px;
  margin-top: 12px;
  padding-top: 12px;
  border-top: 1px solid #f0f0f0;
  background: #fff;
}

@media (max-width: 560px) {
  .template-checkbox-grid {
    grid-template-columns: repeat(2, minmax(0, 1fr));
  }
}

@media (max-width: 380px) {
  .template-checkbox-grid {
    grid-template-columns: repeat(1, minmax(0, 1fr));
  }
}

.column-settings-container {
  padding: 8px 0;
  display: flex;
  flex-direction: column;
  gap: 12px;
}

.column-settings-popover-overlay {
  padding: 0;
}

.column-settings-popover-content {
  width: min(420px, calc(100vw - 32px));
  max-width: calc(100vw - 32px);
  box-sizing: border-box;
  max-height: 70vh;
  overflow: auto;
  padding: 12px;
  background: #fff;
}

.column-settings-tip {
  color: #666;
  font-size: 12px;
  margin-top: 8px;
}

.column-settings-actions {
  display: flex;
  justify-content: flex-end;
  gap: 8px;
  padding-top: 12px;
  border-top: 1px solid #f0f0f0;
  background: #fff;
}

.column-settings-grid {
  display: grid;
  grid-template-columns: repeat(3, minmax(0, 1fr));
  gap: 8px 12px;
}

@media (max-width: 560px) {
  .column-settings-grid {
    grid-template-columns: repeat(2, minmax(0, 1fr));
  }
}

@media (max-width: 380px) {
  .column-settings-grid {
    grid-template-columns: repeat(1, minmax(0, 1fr));
  }
}

.column-settings-grid :deep(.ant-checkbox-wrapper) {
  display: flex;
  align-items: center;
  justify-content: flex-start;
  width: 100%;
}

.column-settings-grid :deep(.ant-checkbox) {
  margin-inline-end: 8px;
}

.page-section {
  border: 1px solid #e9e9e9;
  border-radius: 6px;
  padding: 12px 12px;
  margin-bottom: 10px;
  background: #fff;
}

.section-list :deep(.ant-table-content) {
  overflow-x: auto;
}

.section-list :deep(.ant-table-body) {
  overflow-x: auto !important;
}

.section-title {
  font-weight: 600;
  color: #8c8c8c;
  font-size: 12px;
  margin-bottom: 10px;
  padding-left: 10px;
  background: transparent;
  border: 0;
  display: inline-flex;
  align-items: center;
  position: relative;
}

.section-title::before {
  content: '';
  position: absolute;
  left: 0;
  top: 50%;
  transform: translateY(-50%);
  width: 3px;
  height: 14px;
  border-radius: 2px;
  background: #d9d9d9;
}

.section-title-setting {
  margin-top: 12px;
}

.batch-tag-header {
  display: flex;
  flex-direction: column;
  gap: 8px;
  margin-bottom: 16px;

  .batch-tag-row {
    display: flex;
    align-items: center;
    gap: 8px;

    .batch-tag-label {
      width: 110px;
      text-align: left;
      color: #666;
    }

    .batch-tag-value {
      color: #333;
    }
  }
}

.batch-tag-body {
  display: flex;
  margin-top: 8px;
  gap: 16px;

  .batch-tag-left {
    flex: 1.2;
  }

  .batch-tag-right {
    flex: 1;
    border: 1px solid #f0f0f0;
    border-radius: 4px;
    padding: 8px 12px;
    max-height: 260px;
    display: flex;
    flex-direction: column;

    .batch-tag-right-header {
      display: flex;
      justify-content: space-between;
      align-items: center;
      margin-bottom: 8px;
    }

    .batch-tag-selected {
      flex: 1;
      overflow: auto;

      .ant-tag {
        margin-bottom: 4px;
      }

      .batch-tag-empty {
        color: #999;
      }
    }
  }
}

.batch-single-tip {
  color: #666;
  margin-bottom: 12px;
}

.batch-ai-tip {
  display: flex;
  align-items: center;
  gap: 8px;
  margin-bottom: 12px;

  .batch-ai-desc {
    color: #999;
  }
}

.material-cell {
  display: flex;
  align-items: center;
  gap: 8px;

  .material-icon {
    font-size: 40px;
    color: #1890ff;
  }

  .material-thumbnail {
    width: 40px;
    height: 40px;
    object-fit: cover;
    border-radius: 4px;
  }

  .material-main {
    flex: 1;
    min-width: 0;
    display: flex;
    flex-direction: column;
    gap: 2px;
  }

  .material-name {
    max-width: 100%;
    overflow: hidden;
    text-overflow: ellipsis;
    white-space: nowrap;
  }

  .material-name--clamp2 {
    white-space: normal;
    display: -webkit-box;
    line-clamp: 2;
    -webkit-line-clamp: 2;
    -webkit-box-orient: vertical;
    overflow: hidden;
    word-break: break-word;
  }

  .material-count {
    color: #999;
    font-size: 12px;
    display: inline-flex;
    align-items: center;
    gap: 8px;

    .count-item {
      display: inline-flex;
      align-items: center;
      gap: 4px;
    }
  }
}

.material-thumb-cell {
  display: flex;
  align-items: center;
  justify-content: center;
}

.material-thumb-img {
  width: 48px;
  height: 48px;
  object-fit: cover;
  border-radius: 6px;
  border: 1px solid #f0f0f0;
}

.materials-grid-wrapper {
  min-height: 320px;
}

.materials-grid {
  display: grid;
  grid-template-columns: repeat(auto-fill, minmax(170px, 170px));
  justify-content: flex-start;
  gap: 12px;
  margin-bottom: 10px;
}

.grid-card {
  position: relative;
  background: #fff;
  border: 1px solid #e9e9e9;
  border-radius: 2px;
  display: flex;
  flex-direction: column;
  min-height: 206px;
  transition: all 0.2s ease;
}

.grid-card:hover {
  border-color: #d5d5d5;
  box-shadow: none;
}

.grid-card--selected {
  border-color: #1677ff;
  box-shadow: inset 0 0 0 1px rgba(22, 119, 255, 0.18);
}

.grid-card-check {
  position: absolute;
  top: 6px;
  left: 6px;
  z-index: 2;
}

.grid-card-body {
  padding: 8px 8px 4px;
  flex: 1;
  display: flex;
  flex-direction: column;
}

.grid-card--folder .grid-card-body {
  cursor: pointer;
}

.grid-folder-cover,
.grid-material-cover {
  width: 100%;
  height: 102px;
  border-radius: 2px;
  background: #f4f6f8;
  border: 1px solid #eceff1;
  display: flex;
  align-items: center;
  justify-content: center;
  margin-bottom: 8px;
}

.grid-folder-cover {
  background: #fff;
  border-color: #fff;
  font-size: 56px;
  color: #efb235;
}

.grid-material-image {
  width: 100%;
  height: 100%;
  object-fit: cover;
  border-radius: 4px;
}

.grid-material-empty {
  position: relative;
  width: 100%;
  height: 100%;
  border-radius: 2px;
  background: linear-gradient(180deg, #eff2f6 0%, #e8edf2 100%);
  display: flex;
  align-items: center;
  justify-content: center;
}

.grid-empty-picture-icon {
  font-size: 46px;
  color: #c8ced6;
}

.grid-empty-warning-icon {
  position: absolute;
  right: 12px;
  bottom: 10px;
  font-size: 18px;
  color: #aeb6c1;
}

.grid-title {
  font-size: 12px;
  color: #2f2f2f;
  line-height: 1.35;
  word-break: break-word;
}

.grid-title--clamp1 {
  white-space: nowrap;
  overflow: hidden;
  text-overflow: ellipsis;
}

.grid-title--clamp2 {
  display: -webkit-box;
  line-clamp: 2;
  -webkit-line-clamp: 2;
  -webkit-box-orient: vertical;
  overflow: hidden;
}

.grid-subtitle {
  margin-top: 4px;
  font-size: 11px;
  color: #a0a0a0;
}

.grid-card-footer {
  padding: 0 8px 8px;
}

.grid-add-tag-btn {
  padding: 0 !important;
  height: 18px;
  line-height: 18px;
  font-size: 12px;
  color: #8bc34a;
}

.materials-grid-pagination {
  display: flex;
  justify-content: flex-end;
}

.column-settings-modal {
  display: grid;
  grid-template-columns: 1fr 320px;
  gap: 16px;
  min-height: 680px;
}

.column-settings-left {
  border-right: 1px solid #f0f0f0;
  padding-right: 12px;
  min-width: 0;
  display: flex;
  flex-direction: column;
  font-size: 16px;
}

.column-settings-search {
  margin-bottom: 10px;
}

.column-settings-batch {
  display: flex;
  gap: 8px;
  margin-bottom: 12px;
}

.column-settings-groups {
  overflow: visible;
  max-height: none;
  padding-right: 6px;
}

.col-group {
  margin-bottom: 14px;
}

.col-group-title {
  font-weight: 600;
  color: #3a3f4a;
  background: transparent;
  padding: 0;
  border-radius: 0;
  margin-bottom: 0;
}

.col-group-header {
  display: flex;
  align-items: center;
  justify-content: flex-start;
  background: #f5f7fb;
  padding: 6px 0;
  border-radius: 6px;
  margin-bottom: 8px;
}

.col-group-title {
  width: 56px;
  flex: 0 0 56px;
}

.col-group-batch {
  margin-right: 6px;
  display: flex;
  align-items: center;
}

.col-group-grid {
  display: grid;
  grid-template-columns: repeat(3, minmax(0, 1fr));
  gap: 10px 12px;
  padding-left: 0;
  margin-left: 45px; // align with header checkbox
}

.column-settings-right {
  display: flex;
  flex-direction: column;
  min-width: 0;
}

.selected-header {
  display: flex;
  align-items: center;
  justify-content: space-between;
  margin-bottom: 8px;
}

.selected-title {
  font-weight: 600;
  color: #3a3f4a;
}

.fixed-zone {
  border: 1px dashed #d8dde8;
  border-radius: 8px;
  padding: 10px;
  margin-bottom: 10px;
  background: #fafcff;
}

.fixed-title {
  font-weight: 600;
  margin-bottom: 4px;
}

.fixed-hint {
  font-size: 12px;
  color: #8c8c8c;
  margin-bottom: 8px;
}

.drop-zone {
  display: flex;
  flex-direction: column;
  gap: 8px;
}

.column-settings-right-scroll {
  max-height: 650px;
  overflow: auto;
}

.selected-list {
  flex: 1;
  border: 1px solid #f0f0f0;
  border-radius: 8px;
  padding: 10px;
  display: flex;
  flex-direction: column;
  gap: 8px;
}

.selected-item {
  display: flex;
  align-items: center;
  gap: 8px;
  padding: 8px 10px;
  border: 1px solid #f0f0f0;
  border-radius: 8px;
  background: #fff;
}

.selected-item.is-fixed {
  border-color: #91caff;
  background: #f0f7ff;
}

.drag-handle {
  color: #b0b7c3;
  cursor: grab;
  user-select: none;
}

.selected-label {
  flex: 1;
  min-width: 0;
  overflow: hidden;
  text-overflow: ellipsis;
  white-space: nowrap;
}

.selected-remove {
  color: #8c8c8c;
  text-decoration: none;
  cursor: pointer;
}

.selected-remove:hover {
  color: #ff4d4f;
}

.save-template-row {
  display: flex;
  align-items: center;
  gap: 10px;
  margin-top: 0;
  padding-left: 0;
  margin-left: 45px; // align with header checkbox and first selectable checkbox
}

.template-name-input {
  flex: 1;
}

.modal-actions {
  display: flex;
  justify-content: flex-end;
  gap: 10px;
  margin-top: 12px;
}

.column-settings-footer {
  grid-column: 1 / -1;
  display: flex;
  align-items: center;
  justify-content: space-between;
  gap: 12px;
  padding-top: 12px;
  border-top: 1px solid #f0f0f0;
}

.editable-cell {
  display: inline-flex;
  align-items: center;
  min-height: 24px;
  cursor: pointer;
  color: #1677ff;
}

.editable-cell:hover {
  text-decoration: underline;
}

.editable-cell--disabled {
  color: inherit;
  cursor: default;
}

.editable-cell--disabled:hover {
  text-decoration: none;
}

.editable-cell-placeholder {
  color: #8c8c8c;
}

.cell-edit-cost {
  display: flex;
  align-items: center;
  gap: 8px;
}

.cell-edit-cost-suffix {
  color: #8c8c8c;
}

.cell-edit-selector-toolbar {
  margin-bottom: 8px;
}

.cell-edit-selector-body {
  display: grid;
  grid-template-columns: 1fr 240px;
  gap: 12px;
  margin-top: 12px;
}

.cell-edit-selector-left,
.cell-edit-selector-right {
  border: 1px solid #f0f0f0;
  border-radius: 6px;
  padding: 10px;
  min-height: 280px;
}

.cell-edit-selected-header {
  display: flex;
  align-items: center;
  justify-content: space-between;
  margin-bottom: 8px;
}

.cell-edit-selected-list {
  display: flex;
  flex-wrap: wrap;
  gap: 8px;
}

.cell-edit-empty {
  color: #999;
}

.cell-edit-hint {
  margin-top: 8px;
  color: #8c8c8c;
  font-size: 12px;
}

.cell-edit-remark-count {
  text-align: right;
  color: #999;
  margin-top: 8px;
}

@media (max-width: 1100px) {
  .column-settings-modal {
    grid-template-columns: 1fr;
  }

  .column-settings-left {
    border-right: none;
    padding-right: 0;
  }

  .col-group-grid {
    grid-template-columns: repeat(2, minmax(0, 1fr));
  }
}
</style>
