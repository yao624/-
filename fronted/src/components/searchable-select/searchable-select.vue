<template>
  <a-select
    mode="multiple"
    max-tag-count="responsive"
    v-model:value="form[field]"
    :label-in-value="true"
    :options="asyncOptions"
    :filter-option="false"
    :placeholder="placeholder"
    :not-found-content="fetching ? undefined : null"
    @search="value => debouncedSearch(value)"
    @select="onSelect"
  >
    <template v-if="fetching" #notFoundContent>
      <a-spin size="small" />
    </template>
    <template v-else #option="item">
      <slot name="option" :item="item">
        <span>{{ item.label }}</span>
      </slot>
    </template>
  </a-select>
</template>

<script lang="ts">
import debounce from '@/utils/debonce';
import { InputSearch, Select } from 'ant-design-vue';
import { isNil, isString } from 'lodash';
import { defineComponent, ref, toRef, watchEffect } from 'vue';

export default defineComponent({
  name: 'SearchableSelect',
  components: {
    'a-select': Select,
    'a-input-search': InputSearch,
  },
  props: {
    form: {
      type: Object,
      required: true,
    },
    field: {
      type: String,
      required: true,
    },
    searchFunc: {
      type: Function,
      required: true,
    },
    allowEmptyKeyword: {
      type: Boolean,
      default: false,
    },
    multiple: {
      type: Boolean,
      default: true,
    },
    labelField: String,
    valueField: String,
    placeholder: String,
  },
  setup(props) {
    const fetching = ref(false);
    const asyncOptions = ref<any[]>([]);
    const form = toRef(props.form);
    const field = toRef(props.field);
    const multiple = toRef<any>(props.multiple);
    const placeholder = toRef(props.placeholder);

    watchEffect(() => {
      field.value = props.field;
      form.value = props.form;
      multiple.value = props.multiple;
      asyncOptions.value = [];
      form.value[field.value] = isNil(form.value[field.value]) ? [] : form.value[field.value];
      console.log(field.value, '-->', form.value[field.value]);
    });

    const onSelect = item => {
      if (!multiple.value) {
        form.value[field.value] = [item];
      }
    };

    const onSearch = (value: string) => {
      if (!value.trim() && !props.allowEmptyKeyword) {
        return;
      }
      const func = props.searchFunc;
      fetching.value = true;
      asyncOptions.value = [];
      func(value)
        .then(data => {
          // console.log(data)
          asyncOptions.value = data.map(item =>
            isString(item)
              ? { label: item, value: item }
              : {
                  ...item,
                  value: props.valueField ? item[props.valueField] : item.value || item,
                  label: props.labelField ? item[props.labelField] : item.label || item.name,
                },
          );
        })
        .catch(() => console.error('Search failed'))
        .finally(() => (fetching.value = false));
    };

    const debouncedSearch = debounce(onSearch);

    return {
      // value,
      placeholder,
      fetching,
      asyncOptions,
      debouncedSearch,
      form,
      field,
      multiple,
      onSelect,
      // ...props,
    };
  },
});
</script>
