<template>
  <div class="delivery-content">
    <h2 class="section-title">{{ t('投放内容') }}</h2>
    <a-form :model="localFormData" layout="vertical">
      <!-- 广告组名称 -->
      <a-form-item :label="t('广告组名称') + ' *'" required>
        <a-input
          v-model:value="localFormData.adGroupName"
          :placeholder="t('请输入')"
          :maxlength="200"
          show-count
        />
        <div class="dynamic-name-tags">
          <a-tag
            v-for="tag in dynamicNameTags"
            :key="tag"
            class="name-tag"
            @click="insertDynamicName(tag)"
          >
            {{ tag }}
          </a-tag>
          <a-button type="link" size="small" @click="toggleExpand">
            {{ isExpanded ? t('折叠') : t('展开') }}
          </a-button>
        </div>
      </a-form-item>

      <!-- 广告组状态 -->
      <a-form-item :label="t('广告组状态')">
        <a-switch v-model:checked="localFormData.status" />
      </a-form-item>

      <!-- 移动应用商店 -->
      <a-form-item :label="t('移动应用商店')">
        <a-radio-group v-model:value="localFormData.appStore">
          <a-radio-button value="google_play">Google Play</a-radio-button>
          <a-radio-button value="app_store">App Store</a-radio-button>
          <a-radio-button value="samsung">Samsung Galaxy Store</a-radio-button>
          <a-radio-button value="amazon">Amazon App Store</a-radio-button>
        </a-radio-group>
      </a-form-item>

      <!-- 应用 -->
      <a-form-item :label="t('应用') + ' *'" required>
        <a-select
          v-model:value="localFormData.app"
          :placeholder="t('请选择')"
          show-search
          :filter-option="filterOption"
        >
          <a-select-option v-for="app in apps" :key="app.id" :value="app.id">
            {{ app.name }}
          </a-select-option>
        </a-select>
      </a-form-item>
    </a-form>
  </div>
</template>

<script lang="ts" setup>
import { ref, computed, watch } from 'vue';
import { useI18n } from 'vue-i18n';
import { getApps } from '../mock-data';

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
  adGroupName: '',
  status: true,
  appStore: 'google_play',
  app: undefined,
  ...props.formData,
});

const apps = ref<any[]>([]);

// 动态名称标签
const isExpanded = ref(false);
const dynamicNameTags = computed(() => {
  const basic = [
    t('系统用户名'),
    t('账户名'),
    t('账户备注名'),
    t('地区组名称'),
    t('定向包名称'),
    t('创意组名称'),
    t('首个素材名称'),
    t('首个素材名称(含格式)'),
    t('首个素材备注'),
    t('地区'),
    t('地区2(示例:CN)'),
    t('地区3(示例:CHN)'),
    t('语言'),
    t('创建日期(yyyy/mm/dd)'),
    t('时分秒'),
    t('开始日期(yyyy/mm/dd)'),
    t('开始时间'),
    t('App OS'),
    t('性别'),
    t('年龄'),
    t('出价'),
    t('成效目标'),
    t('版位'),
    t('原名称'),
    t('账户ID'),
    t('序号'),
    t('推广应用'),
    t('广告系列名称'),
    t('首个素材文件夹名称'),
  ];
  return isExpanded.value ? basic : basic.slice(0, 6);
});

// 过滤选项
const filterOption = (input: string, option: any) => {
  return option.children.toLowerCase().indexOf(input.toLowerCase()) >= 0;
};

// 插入动态名称
const insertDynamicName = (tag: string) => {
  localFormData.value.adGroupName += `{${tag}}`;
};

// 切换展开
const toggleExpand = () => {
  isExpanded.value = !isExpanded.value;
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
    apps.value = await getApps();
  } catch (error) {
    console.error('加载数据失败:', error);
  }
};

loadData();
</script>

<style lang="less" scoped>
.delivery-content {
  .section-title {
    font-size: 18px;
    font-weight: 500;
    margin-bottom: 24px;
    color: #333;
  }

  .dynamic-name-tags {
    margin-top: 8px;
    display: flex;
    flex-wrap: wrap;
    gap: 8px;

    .name-tag {
      cursor: pointer;
      user-select: none;

      &:hover {
        background: #e6f7ff;
        border-color: #1890ff;
      }
    }
  }
}
</style>

