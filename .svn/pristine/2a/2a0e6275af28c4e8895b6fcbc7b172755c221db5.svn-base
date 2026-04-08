<template>
  <a-modal
    :title="t('Select ad account')"
    :open="visible"
    :width="800"
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
      <a-form :label-col="labelCol" :wrapper-col="wrapperCol">
        <a-form-item :label="t('ACTION')">
          <a-radio-group v-model:value="modelRef.action" button-style="solid">
            <a-radio-button value="share">
              {{ t('SHARE') }}
            </a-radio-button>
            <a-radio-button value="unshare">
              {{ t('UNSHARE') }}
            </a-radio-button>
          </a-radio-group>
        </a-form-item>
        <a-form-item :label="t('Share To')">
          <a-radio-group v-model:value="modelRef.shareType" button-style="solid">
            <a-radio-button value="ad_account">
              {{ t('Ad Account') }}
            </a-radio-button>
            <a-radio-button value="catalog">
              {{ t('Catalog') }}
            </a-radio-button>
          </a-radio-group>
        </a-form-item>
        <a-form-item
          label="Accounts"
          v-bind="validateInfos.adAccountIds"
          v-if="modelRef.shareType === 'ad_account'"
        >
          <a-select
            v-model:value="modelRef.adAccountIds"
            mode="multiple"
            style="width: 100%"
            placeholder="Please select ad accounts"
            show-search
            :filter-option="filterAdAccounts"
            :dropdown-match-select-width="false"
          >
            <a-select-option
              v-for="account in modelRef.data_source"
              :key="account.id"
              :value="account.id"
            >
              <div class="ad-account-option">
                <div class="account-info">
                  <a-badge
                    :color="
                      account.account_status === 'ACTIVE'
                        ? 'green'
                        : account.account_status === 'DISABLED'
                        ? 'red'
                        : 'gray'
                    "
                  />
                  <span class="account-name">{{ account.name }}</span>
                </div>
                <div class="account-id">{{ account.source_id }}</div>
              </div>
            </a-select-option>
          </a-select>
        </a-form-item>
        <a-form-item
          label="Catalogs"
          v-bind="validateInfos.catalogIds"
          v-if="modelRef.shareType === 'catalog'"
        >
          <a-select
            v-model:value="modelRef.catalogIds"
            mode="multiple"
            style="width: 100%"
            placeholder="Please select catalogs"
            show-search
            :filter-option="filterCatalogs"
            :dropdown-match-select-width="false"
          >
            <a-select-option
              v-for="catalog in modelRef.catalogs"
              :key="catalog.id"
              :value="catalog.id"
            >
              {{ catalog.name }}
            </a-select-option>
          </a-select>
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
import { sharePixelApi } from '@/api/fb_bms';

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
  adAccountIds?: string[];
  catalogIds?: string[];
  action?: string;
  shareType?: string;
  data_source: any[];
  catalogs: any[];
  pixel_id: string;
  bm_id: string;
}

export default defineComponent({
  name: 'SharePixelModal',
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
      adAccountIds: [],
      catalogIds: [],
      action: 'share',
      shareType: 'ad_account',
      data_source: [],
      catalogs: [],
      pixel_id: '',
      bm_id: '',
    });
    const modelRef = reactive<TagAction>(initValues());

    const rulesRef = reactive({
      adAccountIds: [
        {
          required: true,
          message: 'Please select',
          type: 'array',
          trigger: ['blur', 'change'],
        },
      ],
      catalogIds: [
        {
          required: true,
          message: 'Please select',
          type: 'array',
          trigger: ['blur', 'change'],
        },
      ],
    });

    watchEffect(() => {
      if (props.model) {
        const raw = toRaw(props.model);
        modelRef.data_source = raw.data_source;
        modelRef.catalogs = raw.catalogs || [];
        modelRef.pixel_id = raw.pixel_id;
        modelRef.bm_id = raw.bm_id;
      } else if (props.model === null) {
        Object.assign(modelRef, initValues());
      }
    });

    const { resetFields, validate, validateInfos } = Form.useForm(modelRef, rulesRef);

    // 搜索过滤函数
    const filterAdAccounts = (input: string, option: any) => {
      if (!input) return true;

      // 从data_source中找到对应的account数据
      const account = modelRef.data_source.find(acc => acc.id === option.value);
      if (!account) return false;

      const searchText = input.toLowerCase();
      const accountName = (account.name || '').toLowerCase();
      const accountId = (account.source_id || '').toLowerCase();

      // 支持按名称或ID搜索
      return accountName.includes(searchText) || accountId.includes(searchText);
    };

    const filterCatalogs = (input: string, option: any) => {
      if (!input) return true;

      // 从catalogs中找到对应的catalog数据
      const catalog = modelRef.catalogs.find(cat => cat.id === option.value);
      if (!catalog) return false;

      const searchText = input.toLowerCase();
      const catalogName = (catalog.name || '').toLowerCase();

      // 支持按名称搜索
      return catalogName.includes(searchText);
    };

    const handleSubmit = (e: Event) => {
      e.preventDefault();
      loading.value = true;
      const validateField = modelRef.shareType === 'ad_account' ? 'adAccountIds' : 'catalogIds';
      validate(validateField)
        .then(() => {
          const reqData = {
            pixel_id: modelRef.pixel_id,
            bm_id: modelRef.bm_id,
            action: modelRef.action,
            share_type: modelRef.shareType,
          };

          if (modelRef.shareType === 'ad_account') {
            reqData['ad_account_ids'] = modelRef.adAccountIds;
          } else {
            reqData['catalog_ids'] = modelRef.catalogIds;
          }

          sharePixelApi(reqData)
            .then(res => {
              message.success(t('pages.op.successfully'));
              resetFields();
              loading.value = false;
              emit('ok', res);
            })
            .catch(err => {
              message.error(t('pages.op.failed'));
              message.error(err.response.data.message);
              loading.value = false;
            });
        })
        .catch(err => {
          console.log('validate error', err);
          loading.value = false;
        });
    };

    return {
      ...formLayout,
      initValues,
      modelRef,
      loading,
      handleSubmit,
      resetFields,
      validateInfos,
      filterAdAccounts,
      filterCatalogs,
      emit,
      t,
    };
  },
});
</script>

<style scoped>
.ad-account-option {
  padding: 8px 0;
  line-height: 1.4;
  min-height: 40px;
}

.account-info {
  display: flex;
  align-items: flex-start;
  font-weight: 500;
  font-size: 14px;
  color: #262626;
}

.account-info .ant-badge {
  margin-right: 8px;
  margin-top: 2px;
}

.account-name {
  flex: 1;
  word-wrap: break-word;
  word-break: break-word;
  line-height: 1.3;
}

.account-id {
  font-size: 12px;
  color: #8c8c8c;
  margin-left: 20px;
  margin-top: 2px;
}
</style>
