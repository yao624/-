<template>
  <page-container :title="pageTitle" :showPageHeader="true">
    <template #headerContent>
      <a-breadcrumb>
        <a-breadcrumb-item>位置：定时报表管理</a-breadcrumb-item>
        <a-breadcrumb-item>{{ isEdit ? '编辑' : '新建' }}</a-breadcrumb-item>
      </a-breadcrumb>
    </template>

    <div class="scheduled-report-edit-container">
      <div class="edit-content-card">
        <!-- 基础信息 -->
        <div class="edit-section">
          <div class="section-title">基础信息</div>
          <div class="section-body">
            <a-form :label-col="{ style: { width: '120px' } }" :wrapper-col="{ flex: '1' }">
              <a-form-item label="报表名称">
                <a-input v-model:value="formState.name" placeholder="请输入" style="width: 100%; max-width: 650px" />
                <div class="quick-tags">
                  <a-tag v-for="tag in ['昨天', '上周(周一至周日)', '上周(周日至周六)']" :key="tag" @click="formState.name = tag">{{ tag }}</a-tag>
                </div>
              </a-form-item>
              
              <a-form-item label="报表生成时间">
                <div class="frequency-row">
                  <a-radio-group v-model:value="formState.frequencyType" button-style="outline">
                    <a-radio-button value="daily">每天</a-radio-button>
                    <a-radio-button value="weekly">每周</a-radio-button>
                    <a-radio-button value="custom">自定义</a-radio-button>
                  </a-radio-group>
                </div>
                <div class="time-select-row">
                  <a-time-picker v-model:value="formState.time" format="HH:mm" value-format="HH:mm" placeholder="请选择" style="width: 120px" />
                </div>
                <div class="add-link-wrapper">
                  <a class="add-link">添加</a>
                </div>
              </a-form-item>

              <a-form-item label="通知对象">
                <div class="notice-object-box">
                  <div class="object-item">
                    <span class="label">邮件通知</span>
                    <a-input v-model:value="formState.email" placeholder="请输入" style="width: 260px" />
                  </div>
                  <a class="add-link">添加</a>
                </div>
              </a-form-item>
            </a-form>
          </div>
        </div>

        <!-- 数据内容 -->
        <div class="edit-section data-section">
          <div class="section-title">
            数据内容
            <a-tooltip title="通过设置一个或多个卡片，在定时邮件中为您展示指定维度与指标的数据报表。">
              <info-circle-outlined style="margin-left: 8px; color: #1890ff" />
            </a-tooltip>
            <span class="guide-text">详情请查看 <a class="use-guide">使用指南</a></span>
          </div>
          
          <div v-for="(card, cardIndex) in dataCards" :key="cardIndex" class="card-wrapper">
            <div class="section-body card-body">
              <div v-if="dataCards.length > 1" class="card-delete-icon" @click="removeDataCard(cardIndex)">
                <close-outlined />
              </div>
              <a-form :label-col="{ style: { width: '100px' } }" :wrapper-col="{ flex: '1' }">
                <a-form-item label="标题">
                  <a-input v-model:value="card.title" placeholder="请输入" :maxlength="100" show-count style="width: 260px" />
                </a-form-item>
                
                <a-form-item label="报表类型">
                  <a-radio-group v-model:value="card.reportType" button-style="outline">
                    <a-radio-button value="comprehensive">综合</a-radio-button>
                    <a-radio-button value="material">素材</a-radio-button>
                  </a-radio-group>
                </a-form-item>

                <a-form-item label="渠道">
                  <a-select v-model:value="card.level" style="width: 260px">
                    <a-select-option value="total">汇总</a-select-option>
                  </a-select>
                </a-form-item>

                <a-form-item label="数据时间">
                  <div class="data-time-row">
                    <a-select v-model:value="card.dateRange" style="width: 180px" :options="dateRangeOptions" placeholder="请选择时间" />
                    <a-checkbox v-model:checked="card.compare">对比</a-checkbox>
                  </div>
                </a-form-item>

                <a-form-item>
                  <template #label>
                    <span class="label-with-icon">维度 <setting-outlined class="setting-icon" @click="openDimensionModal(cardIndex)" /></span>
                  </template>
                  <div class="dimension-row">
                    <a-tag v-for="d in card.selectedDimensions" :key="d" class="date-tag">{{ d }}</a-tag>
                    <span class="dimension-tip">
                      <info-circle-outlined style="color: #1890ff; margin-right: 4px" />
                      您可以通过左侧齿轮设置选择维度
                    </span>
                  </div>
                </a-form-item>

                <a-form-item>
                  <template #label>
                    <span class="label-with-icon">筛选 <setting-outlined class="setting-icon" @click="openFilterModal(cardIndex)" /></span>
                  </template>
                  <div class="selected-filters-row">
                    <div v-for="item in getActiveFilters(cardIndex)" :key="item.key" class="filter-tag-item">
                      <span class="filter-tag-label">{{ item.label }}:</span>
                      <a-select
                        v-model:value="item.value"
                        placeholder="请选择"
                        style="width: 160px"
                        :options="item.options"
                        size="small"
                        :bordered="false"
                        class="filter-tag-select"
                      />
                      <close-outlined class="filter-tag-close" @click="removeFilter(cardIndex, item)" />
                    </div>
                  </div>
                </a-form-item>

                <a-form-item>
                  <template #label>
                    <span class="label-with-icon">指标 <setting-outlined class="setting-icon" @click="openMetricsModal(cardIndex)" /></span>
                  </template>
                  <div class="metrics-container" @dragover.prevent @drop="onMainDrop(cardIndex)">
                    <div 
                      v-for="(m, index) in card.selectedMetrics" 
                      :key="m" 
                      class="metric-tag"
                      draggable="true"
                      @dragstart="onMainDragStart(cardIndex, index)"
                      @dragenter="onMainDragEnter(cardIndex, index)"
                    >
                      <span class="drag-handle">:::</span>
                      <span class="tag-text">{{ m }}</span>
                      <span class="close-icon" @click="handleRemoveMetric(cardIndex, m)">×</span>
                    </div>
                  </div>
                </a-form-item>

                <a-form-item label="排序">
                  <div class="sort-row">
                    <a-select v-model:value="card.sortBy" style="width: 150px" :options="sortOptions" placeholder="请选择" />
                    <a-select v-model:value="card.sortOrder" style="width: 120px">
                      <a-select-option value="desc">降序</a-select-option>
                      <a-select-option value="asc">升序</a-select-option>
                    </a-select>
                  </div>
                </a-form-item>

                <a-form-item label="数据筛选">
                  <a-radio-group v-model:value="card.limit" button-style="outline">
                    <a-radio-button :value="10">前10条</a-radio-button>
                    <a-radio-button :value="20">前20条</a-radio-button>
                    <a-radio-button :value="50">前50条</a-radio-button>
                  </a-radio-group>
                </a-form-item>
              </a-form>
            </div>
          </div>
          
          <div class="add-card-wrapper">
            <a class="add-card-link" @click="addDataCard">新增数据卡片</a>
          </div>
        </div>
      </div>

      <!-- 底部操作栏 -->
      <div class="footer-actions">
        <a-button @click="handleCancel">取消</a-button>
        <a-button type="primary" @click="handleSave">确定</a-button>
      </div>
    </div>

    <!-- 维度选择弹窗 -->
    <a-modal
      v-model:visible="dimensionModalVisible"
      title="维度"
      width="600px"
      @ok="handleDimensionOk"
      ok-text="确定"
      cancel-text="取消"
      class="dimension-modal"
    >
      <div class="dimension-modal-content">
        <a-checkbox-group v-model:value="tempSelectedDimensions">
          <div class="dimension-grid">
            <a-checkbox v-for="opt in dimensionOptions" :key="opt.value" :value="opt.label">
              {{ opt.label }}
            </a-checkbox>
          </div>
        </a-checkbox-group>
      </div>
    </a-modal>

    <!-- 指标选择弹窗 (重构为自定义列样式) -->
    <a-modal
      v-model:visible="metricsModalVisible"
      title="自定义列"
      width="1000px"
      @ok="handleMetricsOk"
      ok-text="确定"
      cancel-text="取消"
      class="metrics-custom-modal"
    >
      <div class="metrics-custom-content">
        <!-- 左侧：指标选择区 -->
        <div class="metrics-left-panel">
          <div class="search-wrapper">
            <a-input v-model:value="metricSearchQuery" placeholder="请输入列名称">
              <template #prefix><search-outlined /></template>
            </a-input>
          </div>
          <a-tabs v-model:activeKey="metricTabKey" class="metric-tabs">
            <a-tab-pane key="summary" tab="汇总">
              <a-alert message="点击查看汇总渠道指标与单渠道指标的映射关系" type="info" show-icon class="mb-16" />
              <div class="metric-group">
                <div class="group-header">
                  <a-checkbox v-model:checked="isAllBaseMetricsSelected">基础数据</a-checkbox>
                </div>
                <div class="group-grid">
                  <a-checkbox 
                    v-for="opt in metricOptions" 
                    :key="opt.value" 
                    :checked="isMetricSelected(opt.label)"
                    @change="(e: any) => handleMetricToggle(opt.label, e.target.checked)"
                  >
                    {{ opt.label }} <question-circle-outlined class="help-icon" />
                  </a-checkbox>
                </div>
              </div>
            </a-tab-pane>
            <a-tab-pane key="ecommerce" tab="电商平台">
              <a-alert message="当天数据每小时更新一次。下列指标汇总了Shopify、Shoplazza和SHOPLINE数据，请为广告添加追踪参数后查看。详细了解" type="info" show-icon class="mb-16" />
              <div class="metric-group">
                <div class="group-header">
                  <a-checkbox v-model:checked="isAllEcommerceMetricsSelected">基础数据</a-checkbox>
                </div>
                <div class="group-grid">
                  <a-checkbox 
                    v-for="opt in ecommerceMetricOptions" 
                    :key="opt.value" 
                    :checked="isMetricSelected(opt.label)"
                    @change="(e: any) => handleMetricToggle(opt.label, e.target.checked)"
                  >
                    {{ opt.label }} <question-circle-outlined v-if="opt.hasHelp" class="help-icon" />
                  </a-checkbox>
                </div>
              </div>
            </a-tab-pane>
            <a-tab-pane key="s2s" tab="S2S(电商)">
              <div class="empty-placeholder">没有数据</div>
            </a-tab-pane>
            <a-tab-pane key="custom" tab="自定义公式">
              <div class="custom-formula-header">
                <a class="add-link" @click="openFormulaModal">添加</a>
              </div>
              <div v-if="customFormulas.length === 0" class="empty-placeholder">没有数据</div>
              <div v-else class="formula-list">
                <div v-for="(formula, idx) in customFormulas" :key="idx" class="formula-item">
                  <a-checkbox v-model:checked="formula.selected">{{ idx + 1 }}</a-checkbox>
                  <span class="formula-name">{{ formula.name }}</span>
                  <div class="formula-actions">
                    <a class="edit-btn" @click="editFormula(idx)">编辑</a>
                    <a class="delete-btn" @click="deleteFormula(idx)">删除</a>
                  </div>
                </div>
              </div>
            </a-tab-pane>
          </a-tabs>
        </div>
        <!-- 右侧：已选预览区 -->
        <div class="metrics-right-panel">
          <div class="panel-header">
            <span>已选择 {{ tempSelectedMetrics.length }} 列</span>
            <a class="clear-btn" @click="tempSelectedMetrics = []">清除</a>
          </div>
          <div class="fixed-tip">—— 拖动到上方的列将固定显示 ——</div>
          <div class="selected-list" @dragover.prevent @drop="onDrop">
            <div 
              v-for="(m, index) in tempSelectedMetrics" 
              :key="m" 
              class="selected-item"
              draggable="true"
              @dragstart="onDragStart(index)"
              @dragenter="onDragEnter(index)"
            >
              <span class="drag-handle"><holder-outlined /></span>
              <span class="item-text">{{ m }}</span>
              <close-outlined class="remove-icon" @click="removeTempMetric(m)" />
            </div>
          </div>
        </div>
      </div>
    </a-modal>

    <!-- 自定义公式弹窗 -->
    <a-modal
      v-model:visible="formulaModalVisible"
      title="自定义公式"
      width="700px"
      @ok="handleFormulaSave"
      ok-text="确定"
      cancel-text="取消"
      class="formula-edit-modal"
    >
      <a-form :label-col="{ span: 4 }" :wrapper-col="{ span: 20 }">
        <a-form-item label="指标名称" required>
          <a-input v-model:value="formulaForm.name" placeholder="请输入指标名称" :maxlength="20" show-count />
        </a-form-item>
        <a-form-item label="计算公式" required>
          <div class="formula-editor-container">
            <a-input v-model:value="formulaForm.expression" placeholder="请搜索指标" class="formula-input" />
            <div class="formula-helper-grid">
              <div class="metric-list">
                <div class="helper-title">指标</div>
                <div class="helper-scroll">
                  <div v-for="m in metricOptions" :key="m.value" class="helper-item" @click="formulaForm.expression += m.label">
                    {{ m.label }}
                  </div>
                </div>
              </div>
              <div class="operator-list">
                <div class="helper-title">运算符号</div>
                <div class="operator-grid">
                  <div v-for="op in ['+', '-', 'x', '/', '(', ')']" :key="op" class="op-btn" @click="formulaForm.expression += op">{{ op }}</div>
                </div>
              </div>
            </div>
          </div>
        </a-form-item>
        <a-form-item label="显示方式" required>
          <a-radio-group v-model:value="formulaForm.displayType" button-style="solid">
            <a-radio-button value="value">数值</a-radio-button>
            <a-radio-button value="currency">货币</a-radio-button>
            <a-radio-button value="percent">百分比</a-radio-button>
          </a-radio-group>
        </a-form-item>
        <a-form-item label="小数位数" required>
          <a-select v-model:value="formulaForm.decimalPlaces" style="width: 100%">
            <a-select-option :value="0">0</a-select-option>
            <a-select-option :value="1">1</a-select-option>
            <a-select-option :value="2">2</a-select-option>
          </a-select>
        </a-form-item>
      </a-form>
    </a-modal>

    <!-- 筛选设置弹窗 -->
    <a-modal
      v-model:visible="filterModalVisible"
      title="筛选设置"
      width="900px"
      @ok="handleFilterOk"
      ok-text="确定"
      cancel-text="取消"
      class="filter-settings-modal"
    >
      <div class="filter-modal-content">
        <a-alert
          message="勾选筛选条件确定后将会在界面显示"
          type="info"
          show-icon
          class="mb-24"
        />
        
        <div class="filter-grid-container">
          <div v-for="item in filterOptions" :key="item.key" class="filter-row">
            <a-checkbox v-model:checked="item.enabled" />
            <span class="filter-label">{{ item.label }}:</span>
            <a-select
              v-model:value="item.value"
              placeholder="请选择"
              style="flex: 1"
              :options="item.options"
              allow-clear
            />
          </div>
        </div>

        <div class="switch-row">
          <a-switch v-model:checked="filterForm.metricFilterEnabled" size="small" />
          <span class="switch-text">按指标数值筛选</span>
        </div>

        <div v-if="filterForm.metricFilterEnabled" class="metric-filter-content">
          <div v-for="(row, index) in filterForm.metricRows" :key="index" class="metric-filter-row">
            <a-select
              v-model:value="row.metric"
              placeholder="请选择指标"
              style="width: 200px"
              :options="metricOptions"
            />
            <a-select
              v-model:value="row.operator"
              placeholder="请选择"
              style="width: 150px"
              :options="operatorOptions"
            />
            <a-input-number
              v-model:value="row.value"
              placeholder="请输入数值"
              style="width: 150px"
            />
            <close-outlined v-if="filterForm.metricRows.length > 1" class="remove-row-icon" @click="removeMetricRow(index)" />
          </div>
          <div class="add-row-action">
            <a class="add-link" @click="addMetricRow">添加行</a>
          </div>
        </div>
      </div>
      <template #footer>
        <div class="modal-footer-between">
          <a class="clear-link" @click="handleClearFilters">清除已选</a>
          <div class="footer-buttons">
            <a-button @click="filterModalVisible = false">取消</a-button>
            <a-button type="primary" @click="handleFilterOk">确定</a-button>
          </div>
        </div>
      </template>
    </a-modal>
  </page-container>
</template>

<script lang="ts">
import { defineComponent, reactive, ref, computed, onMounted } from 'vue';
import { useRouter, useRoute } from 'vue-router';
import { InfoCircleOutlined, SettingOutlined, RightOutlined, LeftOutlined, CloseOutlined, SearchOutlined, QuestionCircleOutlined, HolderOutlined } from '@ant-design/icons-vue';
import { message } from 'ant-design-vue';
import {
  createMetaScheduledReport,
  updateMetaScheduledReport,
  fetchMetaScheduledReportDetail,
  MetaScheduledReportDetailCard,
} from '@/api/meta-scheduled-report';

export default defineComponent({
  name: 'ScheduledReportEdit',
  components: {
    InfoCircleOutlined,
    SettingOutlined,
    RightOutlined,
    LeftOutlined,
    CloseOutlined,
    SearchOutlined,
    QuestionCircleOutlined,
    HolderOutlined,
  },
  setup() {
    const router = useRouter();
    const route = useRoute();
    const isEdit = computed(() => !!route.query.id);
    const pageTitle = computed(() => (isEdit.value ? '编辑报表' : '新建报表'));

    const formState = reactive({
      name: '',
      frequencyType: 'daily',
      time: '00:15',
      email: '111@qq.com',
    });

    const createDefaultCard = (): MetaScheduledReportDetailCard => ({
      title: '11',
      reportType: 'comprehensive',
      level: 'total',
      dateRange: 'last27_17',
      compare: false,
      selectedDimensions: ['日期'],
      selectedMetrics: ['花销', '花销 (AdsPolar设置币种)', '展示数', '千次展示成本', '点击数', '点击成本', '点击率', '转化数', '转化成本', '转化率', '注册数', '付费次数', '付费金额'],
      sortBy: 'spend',
      sortOrder: 'desc',
      limit: 10,
      filterOptions: [
        { key: 'dept', label: '部门', enabled: false, value: undefined, options: [] },
        { key: 'optimizer', label: '优化师', enabled: false, value: undefined, options: [] },
        { key: 'channel', label: '渠道', enabled: false, value: undefined, options: [] },
        { key: 'account', label: '广告账户', enabled: false, value: undefined, options: [] },
        { key: 'campaign', label: '广告系列', enabled: false, value: undefined, options: [] },
        { key: 'adgroup', label: '广告组', enabled: false, value: undefined, options: [] },
        { key: 'creative', label: '创意', enabled: false, value: undefined, options: [] },
        { key: 'region', label: '地区', enabled: false, value: undefined, options: [] },
      ],
      metricFilterEnabled: false,
      metricRows: [{ metric: undefined, operator: undefined, value: undefined }]
    });

    const dataCards = ref([createDefaultCard()]);
    const currentCardIndex = ref(0);

    const addDataCard = () => {
      dataCards.value.push(createDefaultCard());
    };

    const removeDataCard = (index: number) => {
      dataCards.value.splice(index, 1);
    };

    const dateRangeOptions = [
      { label: '今天', value: 'today' },
      { label: '昨天', value: 'yesterday' },
      { label: '过去2天 (含今天)', value: 'last2_with_today' },
      { label: '过去3天 (含今天)', value: 'last3_with_today' },
      { label: '过去7天 (含今天)', value: 'last7_with_today' },
      { label: '过去3天', value: 'last3' },
      { label: '过去7天', value: 'last7' },
      { label: '上周 (周一至周日)', value: 'last_week_mon_sun' },
      { label: '过去27天至前17天', value: 'last27_17' },
    ];

    const metricOptions = [
      { label: '花销', value: 'spend' },
      { label: '花销 (AdsPolar设置币种)', value: 'spend_ads_polar' },
      { label: '展示数', value: 'impressions' },
      { label: '千次展示成本', value: 'cpm' },
      { label: '点击数', value: 'clicks' },
      { label: '点击成本', value: 'cpc' },
      { label: '点击率', value: 'ctr' },
      { label: '转化数', value: 'conversions' },
      { label: '转化成本', value: 'cost_per_conversion' },
      { label: '转化率', value: 'conversion_rate' },
      { label: '注册数', value: 'registrations' },
      { label: '付费次数', value: 'purchase_count' },
      { label: '付费金额', value: 'purchase_amount' },
    ];

    const sortOptions = metricOptions.map(m => ({ label: m.label, value: m.value }));

    // 维度选择弹窗相关
    const dimensionModalVisible = ref(false);
    const tempSelectedDimensions = ref<string[]>([]);
    const dimensionOptions = [
      { label: '日期', value: 'date' },
      { label: '部门', value: 'department' },
      { label: '优化师', value: 'optimizer' },
      { label: '渠道', value: 'channel' },
      { label: '广告账户', value: 'ad_account' },
      { label: '系列', value: 'campaign' },
      { label: '广告组', value: 'ad_group' },
      { label: '创意', value: 'ad_creative' },
      { label: '地区', value: 'region' },
    ];

    const openDimensionModal = (index: number) => {
      currentCardIndex.value = index;
      tempSelectedDimensions.value = [...dataCards.value[index].selectedDimensions];
      dimensionModalVisible.value = true;
    };

    const handleDimensionOk = () => {
      dataCards.value[currentCardIndex.value].selectedDimensions = [...tempSelectedDimensions.value];
      dimensionModalVisible.value = false;
    };

    const openFilterModal = (index: number) => {
      currentCardIndex.value = index;
      const card = dataCards.value[index];
      // 同步弹窗内的临时状态
      filterForm.metricFilterEnabled = card.metricFilterEnabled;
      filterForm.metricRows = JSON.parse(JSON.stringify(card.metricRows));
      filterOptions.value = JSON.parse(JSON.stringify(card.filterOptions));
      filterModalVisible.value = true;
    };

    // 筛选设置弹窗相关
    const filterModalVisible = ref(false);
    const filterForm = reactive({
      metricFilterEnabled: false,
      metricRows: [{ metric: undefined, operator: undefined, value: undefined }],
    });
    
    const operatorOptions = [
      { label: '大于', value: '>' },
      { label: '小于', value: '<' },
      { label: '等于', value: '=' },
      { label: '大于等于', value: '>=' },
      { label: '小于等于', value: '<=' },
    ];

    const addMetricRow = () => {
      filterForm.metricRows.push({ metric: undefined, operator: undefined, value: undefined });
    };

    const removeMetricRow = (index: number) => {
      filterForm.metricRows.splice(index, 1);
    };

    const filterOptions = ref([]);

    const handleFilterOk = () => {
      const card = dataCards.value[currentCardIndex.value];
      card.metricFilterEnabled = filterForm.metricFilterEnabled;
      card.metricRows = JSON.parse(JSON.stringify(filterForm.metricRows));
      card.filterOptions = JSON.parse(JSON.stringify(filterOptions.value));
      filterModalVisible.value = false;
    };

    const getActiveFilters = (cardIndex: number) => {
      return dataCards.value[cardIndex].filterOptions.filter(item => item.enabled);
    };

    const removeFilter = (cardIndex: number, item: any) => {
      const filter = dataCards.value[cardIndex].filterOptions.find(f => f.key === item.key);
      if (filter) {
        filter.enabled = false;
        filter.value = undefined;
      }
    };

    const handleClearFilters = () => {
      filterOptions.value.forEach(item => {
        item.enabled = false;
        item.value = undefined;
      });
      filterForm.metricFilterEnabled = false;
      filterForm.metricRows = [{ metric: undefined, operator: undefined, value: undefined }];
    };

    const openMetricsModal = (index: number) => {
      currentCardIndex.value = index;
      tempSelectedMetrics.value = [...dataCards.value[index].selectedMetrics];
      metricsModalVisible.value = true;
    };

    // 指标选择弹窗相关
    const metricsModalVisible = ref(false);
    const tempSelectedMetrics = ref<string[]>([]);
    const metricSearchQuery = ref('');
    const metricTabKey = ref('summary');

    const ecommerceMetricOptions = [
      { label: '总销售额', value: 'total_sales', hasHelp: false },
      { label: '订单量', value: 'order_count', hasHelp: false },
      { label: 'ROAS', value: 'roas', hasHelp: true },
      { label: '引流销售额', value: 'referral_sales', hasHelp: true },
    ];

    const isAllEcommerceMetricsSelected = computed({
      get: () => ecommerceMetricOptions.every(opt => tempSelectedMetrics.value.includes(opt.label)),
      set: (val) => {
        if (val) {
          ecommerceMetricOptions.forEach(opt => {
            if (!tempSelectedMetrics.value.includes(opt.label)) {
              tempSelectedMetrics.value.push(opt.label);
            }
          });
        } else {
          ecommerceMetricOptions.forEach(opt => {
            const idx = tempSelectedMetrics.value.indexOf(opt.label);
            if (idx > -1) tempSelectedMetrics.value.splice(idx, 1);
          });
        }
      }
    });

    const isAllBaseMetricsSelected = computed({
      get: () => metricOptions.every(opt => tempSelectedMetrics.value.includes(opt.label)),
      set: (val) => {
        if (val) {
          metricOptions.forEach(opt => {
            if (!tempSelectedMetrics.value.includes(opt.label)) {
              tempSelectedMetrics.value.push(opt.label);
            }
          });
        } else {
          metricOptions.forEach(opt => {
            const idx = tempSelectedMetrics.value.indexOf(opt.label);
            if (idx > -1) tempSelectedMetrics.value.splice(idx, 1);
          });
        }
      },
    });

    const handleMetricToggle = (label: string, checked: boolean) => {
      if (checked) {
        if (!tempSelectedMetrics.value.includes(label)) {
          tempSelectedMetrics.value.push(label);
        }
      } else {
        const idx = tempSelectedMetrics.value.indexOf(label);
        if (idx > -1) tempSelectedMetrics.value.splice(idx, 1);
      }
    };

    // 自定义公式相关
    const formulaModalVisible = ref(false);
    const customFormulas = ref<any[]>([]);
    const formulaForm = reactive({
      name: '',
      expression: '',
      displayType: 'value',
      decimalPlaces: 2
    });
    const editingFormulaIdx = ref(-1);

    const openFormulaModal = () => {
      formulaForm.name = '';
      formulaForm.expression = '';
      formulaForm.displayType = 'value';
      formulaForm.decimalPlaces = 2;
      editingFormulaIdx.value = -1;
      formulaModalVisible.value = true;
    };

    const handleFormulaSave = () => {
      if (!formulaForm.name || !formulaForm.expression) {
        message.warning('请填写完整信息');
        return;
      }
      if (editingFormulaIdx.value > -1) {
        customFormulas.value[editingFormulaIdx.value] = { ...formulaForm, selected: true };
      } else {
        customFormulas.value.push({ ...formulaForm, selected: true });
        const label = `${formulaForm.name} (自定义公式)`;
        if (!tempSelectedMetrics.value.includes(label)) {
          tempSelectedMetrics.value.push(label);
        }
      }
      formulaModalVisible.value = false;
    };

    const editFormula = (idx: number) => {
      const f = customFormulas.value[idx];
      formulaForm.name = f.name;
      formulaForm.expression = f.expression;
      formulaForm.displayType = f.displayType;
      formulaForm.decimalPlaces = f.decimalPlaces;
      editingFormulaIdx.value = idx;
      formulaModalVisible.value = true;
    };

    const deleteFormula = (idx: number) => {
      const f = customFormulas.value[idx];
      const label = `${f.name} (自定义公式)`;
      const sIdx = tempSelectedMetrics.value.indexOf(label);
      if (sIdx > -1) tempSelectedMetrics.value.splice(sIdx, 1);
      customFormulas.value.splice(idx, 1);
    };

    // 弹窗内拖拽排序逻辑
    const dragIndex = ref(-1);
    const onDragStart = (index: number) => {
      dragIndex.value = index;
    };
    const onDragEnter = (index: number) => {
      if (dragIndex.value !== index) {
        const item = tempSelectedMetrics.value.splice(dragIndex.value, 1)[0];
        tempSelectedMetrics.value.splice(index, 0, item);
        dragIndex.value = index;
      }
    };
    const onDrop = () => {
      dragIndex.value = -1;
    };

    // 主页面标签拖拽排序逻辑
    const mainDragIndex = ref(-1);
    const onMainDragStart = (cardIndex: number, index: number) => {
      currentCardIndex.value = cardIndex;
      mainDragIndex.value = index;
    };
    const onMainDragEnter = (cardIndex: number, index: number) => {
      if (mainDragIndex.value !== index && currentCardIndex.value === cardIndex) {
        const list = dataCards.value[cardIndex].selectedMetrics;
        const item = list.splice(mainDragIndex.value, 1)[0];
        list.splice(index, 0, item);
        mainDragIndex.value = index;
      }
    };
    const onMainDrop = (cardIndex: number) => {
      console.log('Drop on card:', cardIndex);
      mainDragIndex.value = -1;
    };

    const removeTempMetric = (label: string) => {
      tempSelectedMetrics.value = tempSelectedMetrics.value.filter(m => m !== label);
    };

    const handleMetricsOk = () => {
      dataCards.value[currentCardIndex.value].selectedMetrics = [...tempSelectedMetrics.value];
      metricsModalVisible.value = false;
    };

    const handleRemoveMetric = (cardIndex: number, metricLabel: string) => {
      const list = dataCards.value[cardIndex].selectedMetrics;
      const idx = list.indexOf(metricLabel);
      if (idx > -1) list.splice(idx, 1);
    };

    const handleCancel = () => {
      router.back();
    };

    const handleSave = async () => {
      if (!formState.name) {
        message.warning('请填写报表名称');
        return;
      }

      const payload = {
        name: formState.name,
        frequencyType: formState.frequencyType,
        time: formState.time,
        email: formState.email,
        dataCards: dataCards.value as MetaScheduledReportDetailCard[],
      };

      if (isEdit.value && route.query.id) {
        await updateMetaScheduledReport(route.query.id as string, payload);
        message.success('更新成功');
      } else {
        await createMetaScheduledReport(payload);
        message.success('创建成功');
      }
      router.push('/instrument/scheduled-report');
    };

    const loadDetail = async () => {
      if (!isEdit.value || !route.query.id) return;
      const res = await fetchMetaScheduledReportDetail(route.query.id as string);
      const detail = res.data;
      formState.name = detail.name;
      formState.frequencyType = detail.frequencyType as any;
      formState.time = (detail.time as any) || '00:15';
      formState.email = (detail.email as any) || '';
      
      if (detail.dataCards && detail.dataCards.length > 0) {
        dataCards.value = (detail.dataCards as MetaScheduledReportDetailCard[]).map(card => {
          const defaultCard = createDefaultCard();
          // 合并保存的筛选条件到默认结构中，确保 options 等 UI 属性存在
          const mergedFilterOptions = defaultCard.filterOptions.map(defaultOpt => {
            const savedOpt = card.filterOptions?.find(f => f.key === defaultOpt.key);
            if (savedOpt) {
              return { ...defaultOpt, ...savedOpt };
            }
            return defaultOpt;
          });
          return { ...defaultCard, ...card, filterOptions: mergedFilterOptions };
        });
      } else {
        dataCards.value = [createDefaultCard()];
      }
    };

    onMounted(() => {
      loadDetail();
    });

    return {
      isEdit,
      pageTitle,
      formState,
      dataCards,
      addDataCard,
      removeDataCard,
      dateRangeOptions,
      sortOptions,
      handleRemoveMetric,
      handleCancel,
      handleSave,
      dimensionModalVisible,
      tempSelectedDimensions,
      dimensionOptions,
      openDimensionModal,
      handleDimensionOk,
      openFilterModal,
      handleFilterOk,
      getActiveFilters,
      removeFilter,
      filterModalVisible,
      filterForm,
      filterOptions,
      handleClearFilters,
      openMetricsModal,
      operatorOptions,
      addMetricRow,
      removeMetricRow,
      metricsModalVisible,
      tempSelectedMetrics,
      isMetricSelected: (label: string) => tempSelectedMetrics.value.includes(label),
      removeTempMetric,
      handleMetricsOk,
      metricOptions,
      onDragStart,
      onDragEnter,
      onDrop,
      onMainDragStart,
      onMainDragEnter,
      onMainDrop,
      metricSearchQuery,
      metricTabKey,
      isAllBaseMetricsSelected,
      isAllEcommerceMetricsSelected,
      ecommerceMetricOptions,
      handleMetricToggle,
      formulaModalVisible,
      customFormulas,
      formulaForm,
      openFormulaModal,
      handleFormulaSave,
      editFormula,
      deleteFormula,
      loadDetail,
    };
  },
});
</script>

<style scoped lang="less">
.scheduled-report-edit-container {
  padding: 0;
  min-height: calc(100vh - 120px);
  background-color: #f0f2f5;
  display: flex;
  flex-direction: column;

  .edit-content-card {
    background: #fff;
    margin: 0;
    padding: 32px 48px;
    flex: 1;

    .edit-section {
      background: #fff;
      padding: 0;
      margin-bottom: 32px;
      border-radius: 0;
      box-shadow: none;

      &.data-section {
        border-top: 1px solid #f0f0f0;
        padding-top: 32px;
      }

      &:last-child {
        margin-bottom: 0;
      }

      .section-title {
        font-size: 16px;
        font-weight: 500;
        margin-bottom: 32px;
        display: flex;
        align-items: center;
        color: #000;
        line-height: 1;

        .guide-text {
          margin-left: 12px;
          font-size: 13px;
          color: #8c8c8c;
          font-weight: 400;
          .use-guide {
            color: #1890ff;
            &:hover { text-decoration: underline; }
          }
        }
      }

      .section-body {
        :deep(.ant-form-item) {
          margin-bottom: 24px;
          
          .ant-form-item-label {
            padding-right: 24px;
            
            .label-with-icon {
              display: flex;
              align-items: center;
              height: 32px;
              color: #262626;
              font-size: 14px;
              font-weight: 400;
              
              .setting-icon {
                margin-left: 8px;
                color: #595959;
                font-size: 16px;
                cursor: pointer;
                transition: color 0.3s;
                
                &:hover {
                  color: #1890ff;
                }
              }
            }
          }
        }

        .quick-tags {
          margin-top: 12px;
          display: flex;
          gap: 12px;
          .ant-tag {
            cursor: pointer;
            border: none;
            background: #f5f5f5;
            color: #595959;
            padding: 4px 16px;
            font-size: 13px;
            border-radius: 2px;
            transition: all 0.3s;
            &:hover {
              background: #e6f7ff;
              color: #1890ff;
            }
          }
        }

        .frequency-row {
          display: block;
          margin-bottom: 12px;
          
          :deep(.ant-radio-button-wrapper) {
            height: 34px;
            line-height: 32px;
            padding: 0 20px;
            border-radius: 4px !important;
            margin-right: 12px;
            border-left: 1px solid #d9d9d9 !important;
            
            &::before { display: none !important; }
            
            &:first-child { border-radius: 4px !important; }
            &:last-child { border-radius: 4px !important; }
            
            &.ant-radio-button-wrapper-checked {
              background: #fff;
              color: #1890ff;
              border-color: #1890ff !important;
            }
          }
        }

        .time-select-row {
          display: block;
          margin-bottom: 12px;
        }

        .notice-object-box {
          background: #f8f9fb;
          padding: 32px 40px;
          border-radius: 4px;
          display: inline-flex;
          flex-direction: column;
          gap: 16px;
          border: none;
          min-width: 550px;
          
          .object-item {
            display: flex;
            align-items: center;
            gap: 20px;
            .label {
              color: #262626;
              font-size: 14px;
              width: 80px;
            }
            :deep(.ant-input) {
              background: #fff;
            }
          }

          .add-link {
            margin-left: 100px;
          }
        }

        .add-link {
          color: #1890ff;
          font-size: 14px;
          cursor: pointer;
          &:hover { opacity: 0.8; }
        }
      }

      .card-body {
        background: #f8f9fb;
        padding: 48px;
        border-radius: 4px;
        border: none;
        position: relative;
        margin-bottom: 24px;

        .card-delete-icon {
          position: absolute;
          right: 20px;
          top: 20px;
          font-size: 18px;
          color: #bfbfbf;
          cursor: pointer;
          z-index: 10;
          &:hover {
            color: #ff4d4f;
          }
        }

        :deep(.ant-form-item-label > label) {
          font-weight: 400;
        }

        .data-time-row, .sort-row {
          display: flex;
          align-items: center;
          gap: 12px;
        }

        .dimension-row {
          display: flex;
          align-items: center;
          gap: 12px;
          .date-tag {
            background: #fff;
            border: 1px solid #d9d9d9;
            color: #262626;
            padding: 4px 20px;
            font-size: 14px;
            border-radius: 2px;
          }
          .dimension-tip {
            color: #8c8c8c;
            font-size: 13px;
          }
        }

        .selected-filters-row {
          display: flex;
          flex-wrap: wrap;
          gap: 12px;

          .filter-tag-item {
            display: flex;
            align-items: center;
            background: #fff;
            border: 1px solid #d9d9d9;
            padding: 0 8px;
            height: 32px;
            border-radius: 2px;
            
            .filter-tag-label {
              font-size: 13px;
              color: #262626;
              padding-right: 4px;
            }

            .filter-tag-select {
              :deep(.ant-select-selector) {
                border: none !important;
                box-shadow: none !important;
                padding: 0 4px !important;
                background: transparent !important;
                height: 24px !important;
                line-height: 24px !important;
                .ant-select-selection-item, .ant-select-selection-placeholder {
                  line-height: 24px !important;
                  font-size: 13px;
                }
              }
              :deep(.ant-select-arrow) {
                right: 0;
              }
            }

            .filter-tag-close {
              font-size: 12px;
              color: #bfbfbf;
              cursor: pointer;
              margin-left: 8px;
              padding-left: 8px;
              border-left: 1px solid #f0f0f0;
              &:hover {
                color: #ff4d4f;
              }
            }
          }
        }

        :deep(.ant-radio-button-wrapper) {
          height: 34px;
          line-height: 32px;
          padding: 0 20px;
          border-radius: 4px !important;
          margin-right: 12px;
          border-left: 1px solid #d9d9d9 !important;
          
          &::before { display: none !important; }
          
          &.ant-radio-button-wrapper-checked {
            background: #fff;
            color: #1890ff;
            border-color: #1890ff !important;
          }
        }

        .metrics-container {
          display: flex;
          flex-wrap: wrap;
          padding-top: 4px;

          .metric-tag {
            display: inline-flex;
            align-items: center;
            background: #fff;
            border: 1px solid #d9d9d9;
            padding: 0 10px;
            height: 32px;
            border-radius: 2px;
            gap: 12px;
            margin-right: 12px;
            margin-bottom: 12px;
            box-shadow: 0 1px 2px rgba(0,0,0,0.02);
            cursor: default;
            
            .drag-handle {
              color: #bfbfbf;
              cursor: move;
              font-size: 14px;
              letter-spacing: 1px;
              font-weight: 300;
            }
            .tag-text {
              color: #000;
              font-size: 13px;
              font-weight: 400;
            }
            .close-icon {
              color: #bfbfbf;
              cursor: pointer;
              font-size: 14px;
              margin-left: 4px;
              font-weight: 300;
              &:hover { color: #ff4d4f; }
            }
          }
        }
      }

      .add-card-wrapper {
        margin-top: 32px;
      }

      .add-card-link {
        color: #1890ff;
        font-size: 14px;
        cursor: pointer;
        &:hover { text-decoration: underline; }
      }
    }
  }

  .footer-actions {
    background: #fff;
    padding: 16px 48px;
    border-top: 1px solid #f0f0f0;
    display: flex;
    justify-content: center;
    gap: 16px;
    position: sticky;
    bottom: 0;
    z-index: 100;

    :deep(.ant-btn) {
      height: 40px;
      padding: 0 32px;
      border-radius: 4px;
      font-size: 14px;
    }
  }
}

.dimension-modal {
  :deep(.ant-modal-header) {
    border-bottom: none;
    padding: 24px 24px 12px;
    .ant-modal-title {
      font-size: 16px;
      font-weight: 500;
    }
  }
  :deep(.ant-modal-body) {
    padding: 12px 24px 32px;
  }
  :deep(.ant-modal-footer) {
    border-top: none;
    padding: 0 24px 24px;
    text-align: right;
    .ant-btn {
      height: 32px;
      padding: 0 20px;
      border-radius: 4px;
    }
  }

  .dimension-grid {
    display: grid;
    grid-template-columns: repeat(3, 1fr);
    gap: 24px 40px;
    
    :deep(.ant-checkbox-wrapper) {
      font-size: 14px;
      color: #262626;
      margin-left: 0;
      
      .ant-checkbox + span {
        padding-left: 12px;
      }
    }
  }
}

.metrics-custom-modal {
  :deep(.ant-modal-header) {
    border-bottom: 1px solid #f0f0f0;
    padding: 16px 24px;
  }
  :deep(.ant-modal-body) {
    padding: 0;
  }
  :deep(.ant-modal-footer) {
    padding: 12px 24px;
  }

  .metrics-custom-content {
    display: flex;
    height: 600px;

    .metrics-left-panel {
      flex: 1;
      border-right: 1px solid #f0f0f0;
      display: flex;
      flex-direction: column;
      padding: 16px 0 0;

      .search-wrapper {
        padding: 0 24px 16px;
      }

      .metric-tabs {
        flex: 1;
        overflow: hidden;
        display: flex;
        flex-direction: column;
        
        :deep(.ant-tabs-nav) {
          margin-bottom: 0;
          padding: 0 24px;
        }
        :deep(.ant-tabs-content-holder) {
          flex: 1;
          overflow-y: auto;
          padding: 20px 24px;
        }

        .mb-16 { margin-bottom: 16px; }

        .metric-group {
          margin-bottom: 24px;
          .group-header {
            background: #f8f9fb;
            padding: 8px 12px;
            margin-bottom: 16px;
            font-weight: 500;
          }
          .group-grid {
            display: grid;
            grid-template-columns: repeat(3, 1fr);
            gap: 16px 20px;
            
            :deep(.ant-checkbox-wrapper) {
              margin-left: 0;
              font-size: 13px;
              display: flex;
              align-items: center;
              
              .help-icon {
                color: #bfbfbf;
                margin-left: 4px;
                font-size: 12px;
              }
            }
          }
        }

        .custom-formula-header {
          display: flex;
          justify-content: flex-end;
          margin-bottom: 16px;
          .add-link {
            color: #1890ff;
          }
        }

        .empty-placeholder {
          text-align: center;
          padding: 60px 0;
          color: #bfbfbf;
        }

        .formula-list {
          .formula-item {
            display: flex;
            align-items: center;
            padding: 12px;
            border-bottom: 1px solid #f0f0f0;
            
            .formula-name {
              flex: 1;
              margin-left: 16px;
              font-size: 14px;
            }
            .formula-actions {
              display: flex;
              gap: 16px;
              .edit-btn { color: #1890ff; }
              .delete-btn { color: #ff4d4f; }
            }
          }
        }
      }
    }

    .metrics-right-panel {
      width: 280px;
      display: flex;
      flex-direction: column;
      background: #fff;

      .panel-header {
        padding: 16px 20px;
        display: flex;
        justify-content: space-between;
        align-items: center;
        font-weight: 500;
        border-bottom: 1px solid #f0f0f0;
        
        .clear-btn {
          color: #1890ff;
          font-weight: 400;
          font-size: 13px;
        }
      }

      .fixed-tip {
        padding: 8px 0;
        text-align: center;
        color: #bfbfbf;
        font-size: 12px;
        background: #fafafa;
      }

      .selected-list {
        flex: 1;
        overflow-y: auto;
        padding: 8px 16px;

        .selected-item {
          display: flex;
          align-items: center;
          padding: 8px 12px;
          background: #f8f9fb;
          border: 1px solid #f0f0f0;
          border-radius: 2px;
          margin-bottom: 8px;
          
          .drag-handle {
            color: #bfbfbf;
            margin-right: 8px;
            cursor: move;
            font-size: 18px;
          }
          .item-text {
            flex: 1;
            font-size: 13px;
            color: #262626;
          }
          .remove-icon {
            color: #bfbfbf;
            cursor: pointer;
            font-size: 12px;
            &:hover { color: #ff4d4f; }
          }
        }
      }
    }
  }
}

.formula-edit-modal {
  .formula-editor-container {
    border: 1px solid #d9d9d9;
    border-radius: 4px;
    
    .formula-input {
      border: none;
      border-bottom: 1px solid #f0f0f0;
      border-radius: 0;
      padding: 12px;
      &:focus { box-shadow: none; }
    }

    .formula-helper-grid {
      display: flex;
      height: 200px;

      .metric-list {
        flex: 3;
        border-right: 1px solid #f0f0f0;
        display: flex;
        flex-direction: column;
      }
      .operator-list {
        flex: 2;
        display: flex;
        flex-direction: column;
      }

      .helper-title {
        padding: 8px 12px;
        background: #fafafa;
        font-weight: 500;
        border-bottom: 1px solid #f0f0f0;
      }

      .helper-scroll {
        flex: 1;
        overflow-y: auto;
        .helper-item {
          padding: 6px 12px;
          cursor: pointer;
          font-size: 13px;
          &:hover { background: #f5f5f5; }
        }
      }

      .operator-grid {
        padding: 12px;
        display: grid;
        grid-template-columns: repeat(3, 1fr);
        gap: 8px;
        
        .op-btn {
          height: 32px;
          display: flex;
          align-items: center;
          justify-content: center;
          background: #f5f5f5;
          border-radius: 2px;
          cursor: pointer;
          font-size: 16px;
          &:hover { background: #e6f7ff; color: #1890ff; }
        }
      }
    }
  }
}

.filter-settings-modal {
  :deep(.ant-modal-header) {
    border-bottom: 1px solid #f0f0f0;
    padding: 16px 24px;
  }
  :deep(.ant-modal-body) {
    padding: 24px;
  }
  
  .mb-24 {
    margin-bottom: 24px;
  }

  .filter-grid-container {
    display: grid;
    grid-template-columns: repeat(3, 1fr);
    gap: 24px 32px;
    margin-bottom: 32px;

    .filter-row {
      display: flex;
      align-items: center;
      gap: 12px;

      .filter-label {
        font-size: 14px;
        color: #262626;
        white-space: nowrap;
        min-width: 50px;
      }
    }
  }

  .switch-row {
    display: flex;
    align-items: center;
    gap: 12px;
    margin-bottom: 16px;
    
    .switch-text {
      font-size: 14px;
      color: #262626;
      font-weight: 500;
    }
  }

  .metric-filter-content {
    padding-left: 44px;
    
    .metric-filter-row {
      display: flex;
      align-items: center;
      gap: 12px;
      margin-bottom: 12px;

      .remove-row-icon {
        color: #bfbfbf;
        cursor: pointer;
        font-size: 14px;
        &:hover {
          color: #ff4d4f;
        }
      }
    }

    .add-row-action {
      margin-top: 8px;
      .add-link {
        color: #1890ff;
        font-size: 14px;
        &:hover {
          text-decoration: underline;
        }
      }
    }
  }

  .modal-footer-between {
    display: flex;
    align-items: center;
    justify-content: space-between;
    padding: 10px 0;

    .clear-link {
      color: #1890ff;
      font-size: 14px;
      &:hover {
        opacity: 0.8;
      }
    }

    .footer-buttons {
      display: flex;
      gap: 12px;
    }
  }
}

:deep(.ant-page-header) {
  padding: 8px 24px !important;
  background: #fff;
}

:deep(.ant-breadcrumb) {
  font-size: 13px;
  color: #8c8c8c;
}
</style>
