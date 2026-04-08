<template>
  <a-button :type="appliedCount > 0 ? 'primary' : undefined" @click="open = !open">
    {{ t('pages.common.filters') }} {{ appliedCount ? `(${appliedCount})` : '' }}
  </a-button>
  <a-drawer
    v-model:open="open"
    :title="t('pages.common.filters')"
    class="responsive-drawer"
    placement="right"
  >
    <formly ref="formly" :form-items="formItems" @change:form-data="onChange"></formly>
    <template #extra>
      <a-space>
        <a-button @click="onReset">{{ t('pages.reset') }}</a-button>
        <a-button :loading="loading" type="primary" @click="onSearch">
          {{ t('pages.acc.search') }}
        </a-button>
      </a-space>
    </template>
  </a-drawer>
</template>
<script lang="ts">
import { defineComponent, ref, toRaw, watch } from 'vue';
import { useI18n } from 'vue-i18n';
import { isObject, isFunction, isNil } from 'lodash';
import Formly from './formly.vue';

interface Option {
  label: string;
  value: string | number | boolean;
}
interface FormItem {
  label: string;
  field: string;
  multiple?: boolean | unknown;
  options?: Array<Option> | Promise<Array<Option>> | unknown;
  mode?: 'radio' | 'select' | unknown;
  isBoolean?: boolean | unknown;
  isDate?: boolean | unknown;
  value?: any | unknown;
}

export default defineComponent({
  name: 'DynamicForm',
  components: {
    Formly,
  },
  props: {
    formItems: {
      type: Array<FormItem>,
      required: true,
    },
    loading: {
      type: Boolean,
      required: false,
    },
  },
  emits: ['change:form-data'],
  setup(props, { emit }) {
    const { t } = useI18n();

    const open = ref(false);
    const reseted = ref(false);
    const appliedCount = ref(0);

    let formData: any = {};

    const formly = ref(null);

    // 计算应用的条件个数
    const calculateAppliedCount = (data: any) => {
      return Object.values(data).filter(value =>
        Array.isArray(value) ? !!value.length : !isNil(value),
      ).length;
    };

    // 监听 formItems 的变化，重新计算 appliedCount
    watch(
      () => props.formItems,
      (newFormItems) => {
        const currentValues = newFormItems.reduce((acc: any, item: any) => {
          if (item.value !== undefined && item.value !== null) {
            acc[item.field] = item.value;
          }
          return acc;
        }, {});

        appliedCount.value = calculateAppliedCount(currentValues);
      },
      { deep: true, immediate: true },
    );

    const onChange = data => {
      formData = data;
      // 实时更新 appliedCount
      appliedCount.value = calculateAppliedCount(data);

      if (reseted.value) {
        reseted.value = false;
        onSearch();
      }
    };

    const onSearch = () => {
      open.value = !open.value;
      appliedCount.value = calculateAppliedCount(formData);
      emit('change:form-data', { ...toRaw(formData) });
    };

    const onReset = () => {
      formly.value.onReset();
      reseted.value = true;
    };

    return {
      t,
      open,
      onSearch,
      onReset,
      onChange,
      isObject,
      isFunction,
      appliedCount,
      formly,
      ...props,
    };
  },
});
</script>
<style scoped>
.responsive-drawer .ant-drawer-content {
  width: 700px;
}

/* 手机端适配 */
@media (max-width: 767px) {
  .responsive-drawer .ant-drawer-content {
    width: 100% !important;
  }
}
</style>
