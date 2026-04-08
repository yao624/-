<template>
  <a-button type="primary" @click="showModal">{{ t('Macro') }}</a-button>
  <div>
    <!-- Macro Selection Modal -->
    <a-modal
      v-model:open="isModalVisible"
      title="Select macros for the campaign name"
      :width="600"
      @ok="handleSave"
      @cancel="handleCancel"
    >
      <p>
        A macro is a macro command that can be used to automate the naming of created campaigns. In
        addition to macros, you can also enter any characters.
      </p>
      <a-form layout="vertical">
        <a-form-item :label="t('Campaign Name')">
          <a-input
            v-bind:value="localValue"
            @update:value="localValue = $event"
            placeholder="Campaign name in Fb"
          />
        </a-form-item>
      </a-form>
      <a-list>
        <a-list-item v-for="mac in macros" :key="mac.label">
          <a-checkable-tag @change="checked => onCheck(mac, checked)" :checked="isChecked(mac)">
            {{ mac.label }}
          </a-checkable-tag>
          - {{ mac.description }}
        </a-list-item>
      </a-list>
    </a-modal>
  </div>
</template>

<script lang="ts">
import { defineComponent, ref, watchEffect } from 'vue';
import { useI18n } from 'vue-i18n';

export default defineComponent({
  name: 'MarcosDialog',
  emits: ['change:macros'],
  props: {
    value: String,
  },
  setup(props, { emit }) {
    const { t } = useI18n();
    const localValue = ref('');

    const isModalVisible = ref(false);
    const macros = ref([
      { label: '{{date}}', value: 'date', description: 'UTC+8 date string, shown as (08/02-13:35:11)' },
      { label: '{{acc.id}}', value: 'ad account id', description: 'last 4 digits of ad account id' },
      { label: '{{random}}', value: 'random string', description: 'random 6 letters' },
      // { label: '{{CAMPAIGN.ID}}', value: 'campaign_id', description: 'Campaign ID' },
      // { label: '{{BM.NAME}}', value: 'bm_name', description: 'BM name' },
      // {
      //   label: '{{CREATIVE.NAME}}',
      //   value: 'creative_name',
      //   description: 'creative filename (every creative filename will be joined with + sign)',
      // },
      // { label: '{{DATE}}', value: 'date', description: 'current date in 2020-01-01 format' },
      // {
      //   label: '{{DATETIME}}',
      //   value: 'datetime',
      //   description: 'current datetime in 2020-01-01 00:00:00 format',
      // },
      // {
      //   label: '{{RANDOM.DIGITS.6}}',
      //   value: 'random_digits_6',
      //   description: "random digits set. you can specify set's length",
      // },
      // {
      //   label: '{{RANDOM.LETTERS.EN.6}}',
      //   value: 'random_letters_en_6',
      //   description: "random latin letters set. you can specify set's length",
      // },
      // {
      //   label: '{{RANDOM.LETTERS.RU.6}}',
      //   value: 'random_letters_ru_6',
      //   description: "random cyrillic letters set. you can specify set's length",
      // },
      // {
      //   label: '{{CAMPAIGN.NUMBER}}',
      //   value: 'campaign_number',
      //   description: 'number of campaign when creating',
      // },
      // { label: '{{AGE}}', value: 'age', description: 'age' },
      // { label: '{{GENDER}}', value: 'gender', description: 'gender' },
      // { label: '{{PAGE.NAME}}', value: 'page_name', description: 'Fan page name' },
      // { label: '{{PAGE.ID}}', value: 'page_id', description: 'Fan page ID' },
      // {
      //   label: '{{CAB.TIME}}',
      //   value: 'cab_time',
      //   description:
      //     'time zone of the advertising account on which the advertisement will be uploaded',
      // },
    ]);

    watchEffect(() => (localValue.value = props.value || ''));

    const isChecked = mac => localValue.value?.includes(mac.label);

    const onCheck = (mac, checked) => {
      localValue.value = localValue.value || '';
      if (checked) {
        localValue.value += mac.label;
      } else {
        localValue.value = localValue.value.replace(mac.label, '');
      }
    };

    const showModal = () => {
      isModalVisible.value = true;
    };
    const handleSave = () => {
      isModalVisible.value = false;
      emit('change:macros', localValue.value);
    };
    const handleCancel = () => {
      isModalVisible.value = false;
      // 把value还原成入参的value
      localValue.value = props.value || '';
    };

    return {
      isModalVisible,
      localValue,
      macros,
      isChecked,
      onCheck,
      showModal,
      handleSave,
      handleCancel,
      t,
    };
  },
  methods: {},
});
</script>

<style scoped>
.a-card {
  margin-bottom: 20px;
}
</style>
