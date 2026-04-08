<template>
  <a-form
    layout="vertical"
    :model="formData"
    :validate-trigger="['change', 'blur']"
    :scroll-to-first-error="true"
    ref="formRef"
    :rules="rules"
  >
    <template v-for="item in formItems">
      <slot :name="`${item.field}Template`" :item="item" :form="formData">
        <a-form-item
          :label="t(item.label)"
          :name="item.field"
          :rules="item.rules?.map(r => ({ ...r, message: r.message ? t(r.message) : '' })) || []"
        >
          <template v-if="item.isBoolean">
            <a-checkbox v-model:checked="formData[item.field]">
              {{ t((item.checkBoxLabel as string) || item.label) }}
            </a-checkbox>
          </template>
          <template v-else>
            <template v-if="!item.options">
              <a-select
                v-if="item.multiple"
                v-model:value="formData[item.field]"
                mode="tags"
                placeholder=""
                max-tag-count="responsive"
              ></a-select>
              <template v-if="!item.multiple">
                <a-input v-if="!item.text" v-model:value="formData[item.field]" />
                <a-textarea
                  v-else
                  v-model:value="formData[item.field]"
                  :auto-size="{ minRows: 3, maxRows: 5 }"
                />
              </template>
            </template>
            <template v-else>
              <template v-if="item.mode === 'radio' && Array.isArray(item.options)">
                <a-radio-group v-model:value="formData[item.field]">
                  <a-radio v-for="op in item.options" :key="getOptionValue(op)" :value="getOptionValue(op)">
                    {{ t(getOptionLabel(op)) }}
                  </a-radio>
                </a-radio-group>
              </template>
              <template v-else>
                <template v-if="isFunction(item.options)">
                  <!-- 可查询的 -->
                  <a-auto-complete
                    v-model:value="formData[item.field]"
                    :options="asyncOptions[item.field]"
                    @search="value => onSearch(item, value)"
                  >
                    <template #option="item">
                      <span>{{ getOptionLabel(item) }}</span>
                    </template>
                    <a-input-search size="large"></a-input-search>
                  </a-auto-complete>
                </template>
                <template v-else>
                  <a-select
                    ref="select"
                    :mode="item.multiple ? 'multiple' : null"
                    v-model:value="formData[item.field]"
                  >
                    <template v-if="Array.isArray(item.options)">
                      <a-select-option
                        v-for="op in item.options"
                        :key="getOptionValue(op)"
                        :value="getOptionValue(op)"
                        :disabled="isOptionDisabled(item, op, formData)"
                      >
                        {{ t(getOptionLabel(op)) }}
                      </a-select-option>
                    </template>
                    <template v-else-if="isPromise(item.options)">
                      <a-select-option
                        v-for="op in asyncOptions[item.field]"
                        :key="getOptionValue(op)"
                        :value="getOptionValue(op)"
                        :disabled="isOptionDisabled(item, op, formData)"
                      >
                        {{ t(getOptionLabel(op)) }}
                      </a-select-option>
                    </template>
                  </a-select>
                </template>
              </template>
            </template>
          </template>
        </a-form-item>
      </slot>
    </template>
  </a-form>
</template>

<script lang="ts">
import { defineComponent, onMounted, reactive, ref, toRaw, watch } from 'vue';
import { useI18n } from 'vue-i18n';
import { isObject, isFunction, isNil } from 'lodash';
import { AutoComplete } from 'ant-design-vue';
import type { FormItem } from './types';

interface Option {
  label: string;
  value: string | number;
}
type AsyncOption = (keyword: string) => Promise<Array<Option>>;

export default defineComponent({
  name: 'Formly',
  components: {
    'a-auto-complete': AutoComplete,
  },
  props: {
    formItems: {
      type: Array<FormItem>,
      required: true,
    },
    rules: {
      type: Object,
      required: false,
    },
  },
  emits: ['change:form-data', 'validation:failed'],
  setup(props, { emit }) {
    const { t } = useI18n();
    const isPromise = ops => isObject(ops) && isFunction((ops as any).then);

    const formRef = ref(null);
    const open = ref(false);

    const fields = props.formItems
      .map(({ field, value }) => ({ field, value }))
      .reduce((p, c) => ({ ...p, [c.field]: c.value }), {});
    const formData = reactive(fields);

    const asyncOptions = ref<Record<string, any[]>>({});
    props.formItems
      .filter(item => isPromise(item.options))
      .forEach(item => (asyncOptions.value[item.field] = []));

    const isOptionDisabled = (item: FormItem, option: Option, form: any) => {
      if (isFunction(item.isOptionDisabled)) {
        return item.isOptionDisabled(item, option, form);
      }
      return false;
    };

    onMounted(() => {
      props.formItems.forEach(item => (formData[item.field] = item.value));
      props.formItems
        .filter(item => isPromise(item.options))
        .map(({ field, options }) => ({ field, options: options as Promise<Array<Option>> }))
        .forEach(item => item.options.then(value => (asyncOptions.value[item.field] = value)));
    });

    watch(
      () => props.formItems, // 监听的对象
      newValue => {
        newValue.forEach(
          item => (formData[item.field] = isNil(item.value) ? formData[item.field] : item.value),
        );
      },
      { deep: true },
    );

    watch(
      () => formData,
      newValue => {
        emit('change:form-data', { ...toRaw(newValue) });
      },
      { deep: true },
    );

    const getOptionLabel = (op: Option | string): string => {
      if (typeof op === 'string') return op;
      return Object.prototype.hasOwnProperty.call(op, 'label') ? op.label : String(op);
    };
    const getOptionValue = (op: Option | string | number): string | number => {
      if (typeof op === 'string' || typeof op === 'number') return op;
      return Object.prototype.hasOwnProperty.call(op, 'value') ? op.value : String(op);
    };

    const validate = () => formRef.value.validate();

    const onSearch = (item: FormItem, keyword: string) => {
      const asyncFunc = item.options as AsyncOption;
      asyncFunc(keyword)
        .then(data => (asyncOptions.value[item.field] = data))
        .catch(() => console.error(`Failed to search ${item.field}`));
    };

    const onReset = (values = {}) =>
      Object.keys(formData).forEach(key => {
        const item = props.formItems.find(({ field }) => field === key);
        if (Object.prototype.hasOwnProperty.call(values, key)) {
          formData[key] = values[key];
        } else {
          formData[key] = item?.multiple ? [] : null;
        }
      });

    return {
      t,
      open,
      formData,
      onReset,
      isObject,
      isFunction,
      asyncOptions,
      formRef,
      validate,
      getOptionLabel,
      getOptionValue,
      onSearch,
      isPromise,
      isOptionDisabled,
      ...props,
    };
  },
});
</script>

<style scoped>
.ant-form-item {
  margin-bottom: 12px; /* 更小的间距 */
}
</style>
