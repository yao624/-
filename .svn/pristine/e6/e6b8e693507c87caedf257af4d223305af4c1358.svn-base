<template>
  <div>
    <a-modal
      :open="open"
      :title="t('Edit config')"
      @cancel="handleCancel"
      @ok="handleOk"
      :confirm-loading="loading"
      :width="800"
    >
      <a-form
        ref="formRef"
        :label-col="{ span: 4 }"
        name="dynamic_form_nest_item"
        :model="modelRef"
      >
        <a-form-item name="active" :label="t('Status')" :rules="[{ required: true }]">
          <a-radio-group v-model:value="modelRef.active">
            <a-radio value="1">Active</a-radio>
            <a-radio value="2">InActive</a-radio>
          </a-radio-group>
        </a-form-item>
        <a-form-item name="type" :label="t('Type')" :rules="[{ required: true }]">
          <a-radio-group v-model:value="modelRef.type">
            <a-radio value="domain_whitelist">Domain whitelist</a-radio>
            <a-radio value="url_whitelist">Url whitelist</a-radio>
          </a-radio-group>
        </a-form-item>
        <a-form-item name="valueText" :label="t('Values')" :rules="[{ required: true }]">
          <a-textarea
            v-model:value="modelRef.valueText"
            :rows="6"
            :placeholder="t('Please input one URL per line')"
            style="width: 100%"
          />
        </a-form-item>
        <a-form-item name="actions" :label="t('Actions')" :rules="[{ required: true }]">
          <a-checkbox-group v-model:value="modelRef.actions" mode="tags" style="width: 100%">
            <a-checkbox value="tg_alert">TG Alert</a-checkbox>
            <a-checkbox value="pause_ad">Pause Ad</a-checkbox>
            <a-checkbox value="lock_card">Lock Card</a-checkbox>
          </a-checkbox-group>
        </a-form-item>
        <a-form-item name="excludedAdsText" :label="'Excluded Ads'" :rules="[{ required: false }]">
          <a-textarea
            v-model:value="modelRef.excludedAdsText"
            :rows="4"
            :placeholder="'Please input one Ad ID per line (optional)'"
            style="width: 100%"
          />
        </a-form-item>
      </a-form>
    </a-modal>
  </div>
</template>

<script lang="ts">
import type { PropType } from 'vue';
import { defineComponent, reactive, ref, toRaw, watchEffect } from 'vue';
import { message } from 'ant-design-vue';
import { useI18n } from 'vue-i18n';
import { Form } from 'ant-design-vue';
import type { FraudConfigModel } from '@/utils/fb-interfaces';
import { updateFraudConfig } from '@/api/fraud_config';

export default defineComponent({
  name: 'EditItem',
  components: {},
  props: {
    open: {
      type: Boolean,
      required: true,
    },
    model: {
      type: Object as PropType<FraudConfigModel | null>,
      default: () => null,
    },
  },
  emits: ['ok', 'cancel'],
  setup(props, { emit }) {
    const { t } = useI18n();
    const loading = ref(false);
    const inputValue = ref('');
    const useForm = Form.useForm;

    const modelRef = reactive<FraudConfigModel>({
      id: '',
      active: '1',
      valueText: '',
      excludedAdsText: '',
    });

    const rulesRef = reactive({});

    watchEffect(() => {
      if (props.model) {
        const raw = toRaw(props.model);
        modelRef.id = raw.id;
        modelRef.active = raw.active ? '1' : '0';
        modelRef.type = raw.type;
        modelRef.value = raw.value;
        modelRef.actions = raw.actions;
        modelRef.excluded_ads = raw.excluded_ads;
        // 将数组转换为textarea格式
        modelRef.valueText = Array.isArray(raw.value) ? raw.value.join('\n') : '';
        modelRef.excludedAdsText = Array.isArray(raw.excluded_ads) ? raw.excluded_ads.join('\n') : '';
      }
    });

    const { resetFields, validate } = useForm(modelRef, rulesRef);

    const onCreate = () => {};

    const handleCancel = () => {
      emit('cancel');
    };

    const handleOk = () => {
      // 在这里处理确认逻辑
      loading.value = true;

      // 将textarea的内容转换为数组
      const valueArray = modelRef.valueText
        ? modelRef.valueText.split('\n').filter(line => line.trim() !== '')
        : [];

      const excludedAdsArray = modelRef.excludedAdsText
        ? modelRef.excludedAdsText.split('\n').filter(line => line.trim() !== '')
        : [];

      const params = {
        id: modelRef.id,
        value: valueArray,
        actions: modelRef.actions,
        type: modelRef.type,
        active: modelRef.active === '1' ? true : false,
        excluded_ads: excludedAdsArray.length > 0 ? excludedAdsArray : null,
      };
      validate().then(() => {
        updateFraudConfig(params)
          .then(() => {
            emit('ok');
            message.success(t('pages.opSuccessfully'));
          })
          .finally(() => {
            resetFields();
            loading.value = false;
          });
      });
    };

    return {
      inputValue,
      loading,
      onCreate,
      handleCancel,
      handleOk,
      t,
      modelRef,
    };
  },
});
</script>
