<template>
  <a-modal
    :title="t('Bid strategy')"
    :open="visible"
    width="700px"
    :confirmLoading="loading"
    @ok="handleSubmit"
    @cancel="
      () => {
        resetFields();
        $emit('cancel');
      }
    "
  >
    <a-spin :spinning="loading">
      <a-form :label-col="labelCol" :wrapper-col="wrapperCol" ref="formRef" :model="formState">
        <a-form-item :label="t('Bid strategy')" name="strategy">
          <a-radio-group v-model:value="formState.strategy" button-style="solid">
            <a-radio-button value="LOWEST_COST_WITHOUT_CAP" :disabled="can_only_change_value()">
              {{ t('High volume') }}
            </a-radio-button>
            <a-radio-button value="COST_CAP" :disabled="can_only_change_value()">
              {{ t('Cost per result') }}
            </a-radio-button>
            <a-radio-button value="LOWEST_COST_WITH_BID_CAP" :disabled="can_only_change_value()">
              {{ t('Bid cap') }}
            </a-radio-button>
          </a-radio-group>
        </a-form-item>
        <a-form-item
          :label="t('Bid amount')"
          name="bidAmount"
          v-bind="validateInfos.bidAmount"
          data-validate-id="bidAmount"
          v-if="formState.strategy !== 'LOWEST_COST_WITHOUT_CAP'"
        >
          <a-input-number
            v-model:value="formState.bidAmount"
            :placeholder="t('amount')"
            addon-after="$"
          ></a-input-number>
        </a-form-item>
      </a-form>
    </a-spin>
  </a-modal>
</template>

<script lang="ts">
import { Form, message } from 'ant-design-vue';
import type { PropType } from 'vue';
import { defineComponent, ref, reactive, toRaw, watchEffect } from 'vue';
import { useI18n } from 'vue-i18n';
import type { FormInstance } from 'ant-design-vue';
import { UpdateBidAmountApi, UpdateBidStrategyApi } from '@/api/ads';

const formLayout = {
  labelCol: {
    xs: { span: 24 },
    sm: { span: 7 },
  },
  wrapperCol: {
    xs: { span: 24 },
    sm: { span: 13 },
  },
};

interface TagAction {
  id?: string;
  strategy: string;
  campaignStrategy?: string;
  adsetStrategy?: string;
  bidAmount?: number;
}

interface FormState {
  id?: string;
  strategy: string;
  campaignStrategy?: string;
  adsetStrategy?: string;
  bidAmount?: number;
  level?: string;
}

export default defineComponent({
  name: 'EditBidStrategyModal',
  props: {
    visible: {
      type: Boolean,
      required: true,
    },
    model: {
      type: Object as PropType<TagAction | null>,
      default: () => null,
    },
  },
  emits: ['ok', 'cancel'],
  setup(props, { emit }) {
    const { t } = useI18n();
    const loading = ref(false);
    const initValues = () => ({
      id: '',
      strategy: '',
      campaignStrategy: '',
      adsetStrategy: '',
      bidAmount: 1,
      level: '',
    });
    const rawData = ref({});
    const formRef = ref<FormInstance>();
    const formState = reactive<FormState>({
      id: '',
      strategy: '',
      campaignStrategy: '',
      adsetStrategy: '',
      bidAmount: 1,
      level: '',
    });

    const rulesRef = reactive({
      bidAmount: [
        {
          required: formState.strategy !== 'LOWEST_COST_WITHOUT_CAP',
        },
      ],
    });

    const { resetFields, validate, validateInfos } = Form.useForm(formRef, rulesRef);

    watchEffect(() => {
      if (props.model) {
        const raw = toRaw(props.model);
        rawData.value = raw;
        formState.id = raw.id;
        formState.level = raw['adset_id'] ? 'adset' : 'campaign';
        formState.campaignStrategy = raw['campaign_bid_strategy'] || '';
        formState.adsetStrategy = raw['adset_bid_strategy'] || '';
        formState.strategy = formState.campaignStrategy || formState.adsetStrategy;
        formState.bidAmount = raw['bid_amount'] || 0;
        console.log('model ref');
        console.log(formState);
      } else if (props.model === null) {
        Object.assign(formState, initValues());
      }
    });

    const can_only_change_value = () => {
      return formState.level === 'adset' && !!formState.campaignStrategy;
    };

    const handleSubmit = (e: Event) => {
      e.preventDefault();
      // console.log(toRaw(formRef));
      // console.log(formState);
      loading.value = true;
      validate()
        .then(() => {
          console.log('passed the validate');
          const data = toRaw(formState);
          console.log(data);
          if (can_only_change_value()) {
            // high volume 不处理, 并且bid amount 修改了才处理
            if (
              formState.strategy !== 'LOWEST_COST_WITHOUT_CAP' &&
              formState.bidAmount !== rawData.value['bid_amount']
            ) {
              const req_body = {
                id: formState.id,
                value: formState.bidAmount,
              };
              UpdateBidAmountApi(req_body)
                .then(res => {
                  message.success('Success');
                  loading.value = false;
                  resetFields();
                  emit('ok', res);
                })
                .catch(err => {
                  message.error(err.response.data.message);
                  loading.value = false;
                });
            }
            emit('cancel');
          } else {
            const req_body = {
              id: formState.id,
              bid_strategy: formState.strategy,
            };
            if (formState.adsetStrategy) {
              req_body['object_type'] = 'adset';
            } else {
              req_body['object_type'] = 'campaign';
            }
            if (formState.strategy !== 'LOWEST_COST_WITHOUT_CAP') {
              req_body['object_value'] = formState.bidAmount;
            }
            UpdateBidStrategyApi(req_body)
              .then(res => {
                message.success('Success');
                loading.value = false;
                resetFields();
                emit('ok', res);
              })
              .catch(err => {
                message.error(err.response.data.message);
                loading.value = false;
              });
          }
          loading.value = false;
        })
        .catch(err => {
          console.log('error', err);
          loading.value = false;
        });
    };

    return {
      t,
      ...formLayout,
      initValues,
      loading,
      handleSubmit,
      resetFields,
      validateInfos,
      formRef,
      formState,
      can_only_change_value,
    };
  },
});
</script>
