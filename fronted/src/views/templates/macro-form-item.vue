<template>
  <a-form-item
    :label="t(item.label)"
    :name="item.field"
    :rules="item.rules?.map(r => ({ ...r, message: t(r.message) })) || []"
  >
    <a-row :gutter="[12, 0]" :wrap="false">
      <a-col :flex="1">
        <a-input :value="form[item.field]" @update:value="(val) => handleValueChange(val)" :placeholder="t(item.label)"></a-input>
      </a-col>
      <a-col>
        <marcos-dialog
          :value="form[item.field]"
          @change:macros="handleMacrosChange"
        ></marcos-dialog>
      </a-col>
    </a-row>
  </a-form-item>
</template>
<script lang="ts">
import { defineComponent } from 'vue';
import MarcosDialog from './marcos-dialog.vue';
import { useI18n } from 'vue-i18n';

export default defineComponent({
  name: 'MacroFormItem',
  components: {
    MarcosDialog,
  },
  props: {
    form: {
      type: Object,
      required: true,
    },
    item: {
      type: Object,
      required: true,
    },
  },
  setup(props) {
    const { t } = useI18n();
    const handleValueChange = (value: string) => {
      // eslint-disable-next-line vue/no-mutating-props
      props.form[props.item.field] = value;
    };
    const handleMacrosChange = (value: string) => {
      // eslint-disable-next-line vue/no-mutating-props
      props.form[props.item.field] = value;
    };
    return {
      ...props,
      t,
      handleValueChange,
      handleMacrosChange,
    };
  },
});
</script>
