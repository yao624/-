<template>
  <a-modal
    v-model:visible="visible"
    :title="t('新建广告')"
    width="600px"
    :footer="null"
  >
    <a-tabs v-model:activeKey="activeTab">
      <a-tab-pane key="create" :tab="t('新建广告')">
        <a-form :model="formData" layout="vertical">
          <a-form-item :label="t('使用模版')">
            <a-button type="primary" :icon="h(FileTextOutlined)" @click="selectTemplate">
              {{ t('选择模版') }}
            </a-button>
            <a-button style="margin-left: 8px" @click="copyHistoryTask">
              {{ t('复制历史任务') }}
            </a-button>
          </a-form-item>
          <a-form-item :label="t('创建模式')">
            <a-radio-group v-model:value="formData.creationMode">
              <a-radio-button value="standard">{{ t('标准') }}</a-radio-button>
              <a-radio-button value="quick">{{ t('快速') }}</a-radio-button>
            </a-radio-group>
          </a-form-item>
          <a-form-item :label="t('广告目标')">
            <a-radio-group v-model:value="formData.adGoal">
              <a-radio-button value="sales">{{ t('销量') }}</a-radio-button>
              <a-radio-button value="leads">{{ t('潜在客户') }}</a-radio-button>
              <a-radio-button value="engagement">{{ t('互动') }}</a-radio-button>
              <a-radio-button value="traffic">{{ t('流量') }}</a-radio-button>
              <a-radio-button value="awareness">{{ t('知名度') }}</a-radio-button>
            </a-radio-group>
          </a-form-item>
          <a-alert
            :message="t('在创建页面打开赋能型广告系列预算优化、进阶赋能型版位、进阶赋能型受众, 即可创建进阶赋能型广告')"
            type="info"
            show-icon
            style="margin-bottom: 16px"
          />
          <a-form-item :label="t('进阶赋能型目录广告')">
            <a-switch v-model:checked="formData.advancedCatalogAds" />
          </a-form-item>
          <a-form-item :label="t('转化发生位置')">
            <a-radio-group v-model:value="formData.conversionLocation">
              <a-radio-button value="app">{{ t('应用') }}</a-radio-button>
              <a-radio-button value="website">{{ t('网站') }}</a-radio-button>
              <a-radio-button value="messaging">{{ t('消息应用') }}</a-radio-button>
            </a-radio-group>
          </a-form-item>
          <div class="modal-footer-info">
            {{ t('本月已创建广告') }}{{ createdCount }}{{ t('个, 上限') }}{{ maxCount }}{{ t('个。如需提升上限, 请升级套餐, 或通过右下角"联系我们"') }}
          </div>
        </a-form>
      </a-tab-pane>
      <a-tab-pane key="select" :tab="t('选择已有')">
        <div class="select-existing-content">
          {{ t('选择已有广告功能待实现') }}
        </div>
      </a-tab-pane>
    </a-tabs>
    <div class="modal-footer">
      <a-button @click="handleCancel">{{ t('取消') }}</a-button>
      <a-button @click="enterLastEdit">{{ t('进入上次编辑') }}</a-button>
      <a-button type="primary" @click="handleContinue">{{ t('继续') }}</a-button>
    </div>
  </a-modal>
</template>

<script lang="ts" setup>
import { ref, h } from 'vue';
import { useI18n } from 'vue-i18n';
import { FileTextOutlined } from '@ant-design/icons-vue';
import { message } from 'ant-design-vue';

interface Props {
  visible: boolean;
}

interface Emits {
  (e: 'update:visible', value: boolean): void;
  (e: 'continue', value: any): void;
}

const props = defineProps<Props>();
const emit = defineEmits<Emits>();
const { t } = useI18n();

import { computed } from 'vue';

const visible = computed({
  get: () => props.visible,
  set: (value) => emit('update:visible', value),
});

const activeTab = ref('create');
const formData = ref({
  creationMode: 'standard',
  adGoal: 'sales',
  advancedCatalogAds: false,
  conversionLocation: 'app',
});

const createdCount = ref(0);
const maxCount = ref(100000);

const selectTemplate = () => {
  message.info(t('选择模版功能待实现'));
};

const copyHistoryTask = () => {
  message.info(t('复制历史任务功能待实现'));
};

const enterLastEdit = () => {
  message.info(t('进入上次编辑功能待实现'));
};

const handleContinue = () => {
  emit('continue', { ...formData.value });
  visible.value = false;
};

const handleCancel = () => {
  visible.value = false;
};
</script>


<style lang="less" scoped>
.modal-footer-info {
  margin-top: 16px;
  padding: 12px;
  background: #f5f5f5;
  border-radius: 4px;
  color: #666;
  font-size: 12px;
}

.modal-footer {
  display: flex;
  justify-content: flex-end;
  gap: 8px;
  margin-top: 24px;
  padding-top: 16px;
  border-top: 1px solid #f0f0f0;
}

.select-existing-content {
  padding: 40px;
  text-align: center;
  color: #999;
}
</style>

