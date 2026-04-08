<template>
  <div class="creative-group">
    <div class="creative-group-header">
      <h2 class="section-title">{{ t('创意组') }}</h2>
      <div class="header-actions">
        <a-button type="link" size="small">{{ t('+ 新增') }}</a-button>
        <span class="item-count">{{ currentItemCount }}/{{ maxItemCount }}</span>
      </div>
    </div>

    <div class="creative-group-tab">
      <span class="tab-name">{{ t('创意组1') }}</span>
      <a-button type="text" size="small" :icon="h(EllipsisOutlined)" />
    </div>

    <a-alert
      :message="t('视频、图片、轮播、单个主页的帖子、灵活格式创意总共最多上传50个,轮播多个素材也仅占用一个额度,现在已上传(0/50)个')"
      type="info"
      show-icon
      style="margin-bottom: 16px"
    />

    <a-form :model="localFormData" layout="vertical">
      <a-form-item>
        <a-checkbox v-model:checked="localFormData.selectExisting">
          {{ t('选择已有创意组') }}
        </a-checkbox>
      </a-form-item>
      <a-form-item :label="t('动态素材')">
        <a-switch v-model:checked="localFormData.dynamicCreative" />
      </a-form-item>
      <a-form-item :label="t('创意类型') + ' *'" required>
        <a-radio-group v-model:value="localFormData.creativeType">
          <a-radio value="create_ad">{{ t('创建广告') }}</a-radio>
          <a-radio value="use_existing">{{ t('使用已有帖子') }}</a-radio>
        </a-radio-group>
      </a-form-item>
      <a-form-item :label="t('格式')">
        <a-radio-group v-model:value="localFormData.format">
          <a-radio-button value="flexible">{{ t('灵活') }}</a-radio-button>
          <a-radio-button value="single">{{ t('单图片或视频') }}</a-radio-button>
          <a-radio-button value="carousel">{{ t('轮播') }}</a-radio-button>
        </a-radio-group>
      </a-form-item>
      <a-form-item :label="t('设置方式')">
        <a-space>
          <a-radio-group v-model:value="localFormData.settingMethod">
            <a-radio value="by_group">{{ t('按创意组') }}</a-radio>
            <a-radio value="by_creative">{{ t('按素材') }}</a-radio>
          </a-radio-group>
          <a-tooltip>
            <template #title>{{ t('设置方式说明') }}</template>
            <question-circle-outlined class="info-icon" />
          </a-tooltip>
        </a-space>
      </a-form-item>
      <a-form-item :label="t('深度链接')">
        <a-input v-model:value="localFormData.deepLink" :placeholder="t('请输入')" />
      </a-form-item>
      <a-form-item :label="t('广告系列名称')">
        <a-input v-model:value="localFormData.campaignName" :placeholder="t('广告系列名称')" disabled />
      </a-form-item>
      <a-form-item :label="t('广告组名称')">
        <a-input v-model:value="localFormData.adGroupName" :placeholder="t('广告组名称')" disabled />
      </a-form-item>
      <a-form-item :label="t('广告名称')">
        <a-input v-model:value="localFormData.adName" :placeholder="t('广告名称')" disabled />
      </a-form-item>
      <a-form-item :label="t('视频') + ' *'" required>
        <a-button @click="showMaterialModal('video')">{{ t('添加素材') }}</a-button>
        <a-select
          v-model:value="localFormData.videoOptimization"
          :placeholder="t('使用进阶赋能型素材优化视频广告')"
          style="margin-top: 8px"
        >
          <a-select-option value="full">{{ t('全面优化(全选)') }}</a-select-option>
        </a-select>
      </a-form-item>
      <a-form-item :label="t('图片') + ' *'" required>
        <a-button @click="showMaterialModal('image')">{{ t('添加素材') }}</a-button>
        <a-select
          v-model:value="localFormData.imageOptimization"
          :placeholder="t('使用进阶赋能型素材优化图片广告')"
          style="margin-top: 8px"
        >
          <a-select-option value="full">{{ t('全面优化(全选)') }}</a-select-option>
        </a-select>
      </a-form-item>
      <a-form-item :label="t('多语言')">
        <a-switch v-model:checked="localFormData.multilingual" />
      </a-form-item>
      <a-form-item :label="t('正文')">
        <a-button @click="showCopywritingModal">{{ t('选文案') }}</a-button>
        <a-button @click="showBatchCopywritingModal">{{ t('批量添加文案') }}</a-button>
        <span class="count-badge">({{ localFormData.bodyTexts.length }}/5)</span>
        <a-textarea
          v-model:value="currentBodyText"
          :placeholder="t('请输入正文')"
          :rows="4"
          style="margin-top: 8px"
        />
        <a-button type="link" @click="addBodyText">{{ t('添加') }}</a-button>
      </a-form-item>
      <a-form-item :label="t('标题')">
        <a-button @click="showCopywritingModal">{{ t('选文案') }}</a-button>
        <a-button @click="showBatchCopywritingModal">{{ t('批量添加文案') }}</a-button>
        <span class="count-badge">({{ localFormData.titles.length }}/5)</span>
        <a-textarea
          v-model:value="currentTitle"
          :placeholder="t('请输入标题')"
          :rows="2"
          style="margin-top: 8px"
        />
        <a-button type="link" @click="addTitle">{{ t('添加') }}</a-button>
      </a-form-item>
      <a-form-item :label="t('行动号召') + ' *'" required>
        <a-select v-model:value="localFormData.callToAction" :placeholder="t('请选择')">
          <a-select-option
            v-for="cta in callToActions"
            :key="cta.value"
            :value="cta.value"
          >
            {{ cta.label }}
          </a-select-option>
        </a-select>
      </a-form-item>
      <a-form-item :label="t('标签')">
        <a-select v-model:value="localFormData.tags" mode="multiple" :placeholder="t('请选择')">
          <a-select-option v-for="tag in tags" :key="tag.id" :value="tag.id">
            {{ tag.name }}
          </a-select-option>
        </a-select>
      </a-form-item>
      <a-form-item :label="t('创意组名称')">
        <a-input
          v-model:value="localFormData.creativeGroupName"
          :maxlength="100"
          show-count
          :placeholder="t('创意组1')"
        />
      </a-form-item>
      <a-form-item>
        <a-button type="primary" @click="saveCreativeGroup">{{ t('保存创意组') }}</a-button>
      </a-form-item>
    </a-form>
  </div>
</template>

<script lang="ts" setup>
import { ref, watch, h } from 'vue';
import { useI18n } from 'vue-i18n';
import { message } from 'ant-design-vue';
import { QuestionCircleOutlined, EllipsisOutlined } from '@ant-design/icons-vue';
import { getCallToActions, getTags } from '../mock-data';

interface Props {
  formData: any;
}

interface Emits {
  (e: 'update:formData', value: any): void;
}

const props = defineProps<Props>();
const emit = defineEmits<Emits>();
const { t } = useI18n();

const localFormData = ref({
  selectExisting: false,
  dynamicCreative: false,
  creativeType: 'create_ad',
  format: 'single',
  settingMethod: 'by_group',
  deepLink: '',
  campaignName: '',
  adGroupName: '',
  adName: '',
  videoOptimization: 'full',
  imageOptimization: 'full',
  multilingual: false,
  bodyTexts: [],
  titles: [],
  callToAction: undefined,
  tags: [],
  creativeGroupName: '创意组1',
  ...props.formData,
});

const currentItemCount = ref(1);
const maxItemCount = ref(30);

const currentBodyText = ref('');
const currentTitle = ref('');
const callToActions = ref<any[]>([]);
const tags = ref<any[]>([]);

const addBodyText = () => {
  if (currentBodyText.value.trim() && localFormData.value.bodyTexts.length < 5) {
    localFormData.value.bodyTexts.push(currentBodyText.value);
    currentBodyText.value = '';
  }
};

const addTitle = () => {
  if (currentTitle.value.trim() && localFormData.value.titles.length < 5) {
    localFormData.value.titles.push(currentTitle.value);
    currentTitle.value = '';
  }
};

const showMaterialModal = (type: string) => {
  // TODO: 显示素材选择弹窗
  message.info(t('选择') + type);
};

const showCopywritingModal = () => {
  // TODO: 显示文案选择弹窗
  message.info(t('选择文案'));
};

const showBatchCopywritingModal = () => {
  // TODO: 显示批量添加文案弹窗
  message.info(t('批量添加文案'));
};

const saveCreativeGroup = () => {
  message.success(t('保存成功'));
};

watch(
  localFormData,
  (newVal) => {
    emit('update:formData', { ...newVal });
  },
  { deep: true },
);

const loadData = async () => {
  try {
    callToActions.value = await getCallToActions();
    tags.value = await getTags();
  } catch (error) {
    console.error('加载数据失败:', error);
  }
};

loadData();
</script>

<style lang="less" scoped>
.creative-group {
  .creative-group-header {
    display: flex;
    justify-content: space-between;
    align-items: center;
    margin-bottom: 16px;

    .section-title {
      font-size: 18px;
      font-weight: 500;
      color: #333;
      margin: 0;
    }

    .header-actions {
      display: flex;
      gap: 8px;
      align-items: center;

      .item-count {
        color: #999;
        font-size: 12px;
        margin-left: 8px;
      }
    }
  }

  .creative-group-tab {
    display: flex;
    align-items: center;
    gap: 8px;
    margin-bottom: 16px;
    padding: 8px 12px;
    background: #f5f5f5;
    border-radius: 4px;

    .tab-name {
      font-weight: 500;
    }
  }

  .count-badge {
    margin-left: 8px;
    color: #999;
    font-size: 12px;
  }

  .info-icon {
    margin-left: 8px;
    color: #1890ff;
    cursor: help;
  }
}
</style>

