<template>
  <a-card :style="{ marginBottom: '8px' }">
    <a-steps :current="currentStep" class="steps-header">
      <a-step :title="t('Pick Accounts')" @click="currentStep = 0" />
      <a-step :title="t('Process Accounts')" @click="currentStep = 1" />
      <!-- <a-step :title="t('Creative')" @click="currentStep = 2" /> -->
    </a-steps>
  </a-card>
  <div class="content">
    <a-card>
      <div :class="{ invisible: currentStep > 0 }">
        <pick-accounts ref="ref0" :loading="loading" :ad-accounts="adAccounts" />
      </div>
      <div :class="{ invisible: currentStep !== 1 }">
        <edit-account
          v-for="(acc, index) in adAccounts"
          :template="template"
          :ad-account="acc"
          :get-fb-pages="getFbPages"
          @change:account-data="data => onDataChange(data, index)"
          :key="acc.id"
        />
        <a-divider />
      </div>
    </a-card>
  </div>
  <a-card>
    <a-row :gutter="[12, 0]" justify="end">
      <a-col>
        <a-button v-if="currentStep === 1" key="back" @click="onCancel">{{ t('Back') }}</a-button>
      </a-col>
      <a-col>
        <a-button :loading="loading" key="submit" type="primary" @click="onOk">
          {{ getOkText(currentStep) }}
        </a-button>
      </a-col>
    </a-row>
  </a-card>
</template>
<script lang="ts">
import { defineComponent, onMounted, ref, toRaw } from 'vue';
import { useI18n } from 'vue-i18n';
import PickAccounts from './pick-accounts.vue';
import { Divider, message, Modal } from 'ant-design-vue';
import EditAccount from './edit-account.vue';
import { debounce, isString } from 'lodash';
import { queryPagesApi } from '@/api/pages';
import { getFbAdTemplate } from '@/api/fb_ad_template';
import { launchAds } from '@/api/fb-ads';
import { useRoute } from 'vue-router';
import { queryFB_AD_AccountsApi } from '@/api/fb_ad_accounts';

export default defineComponent({
  name: 'CreateAds',
  components: {
    PickAccounts,
    EditAccount,
    'a-divider': Divider,
  },
  setup() {
    const { t } = useI18n();
    const route = useRoute();

    const open = ref(false);
    const currentStep = ref(0);
    const adAccounts = ref([]);
    const ref0 = ref(null);
    const ref1 = ref(null);
    const form = ref<any>({});
    const template = ref<any>({});
    const accounts = ref([]);
    const loading = ref(false);

    const getOkText = (step: number) => (step < 2 ? t('Next') : t('Save'));
    const getCancelText = (step: number) => (step > 0 ? t('Back') : t('Cancel'));

    onMounted(() => {
      if (route.query.aid?.length) {
        loading.value = true;
        const ids = route.query.aid;
        const params = {
          ad_account_ids: Array.isArray(ids) ? ids : [ids],
          pageNo: 1,
          pageSize: Array.isArray(ids) ? ids.length : 1,
        };
        queryFB_AD_AccountsApi(params)
          .then(({ data }) => (adAccounts.value = data))
          .catch(err => console.error(err))
          .finally(() => (loading.value = false));
      }
    });

    const onDataChange = (data, index) => {
      accounts[index] = data;
    };

    const onOpen = () => {
      if (!adAccounts.value?.length) {
        Modal.warn({
          title: t('Select ad accounts'),
          content: t('Please select ad accounts to process'),
        });
      } else {
        open.value = true;
      }
    };

    const onCancel = () => {
      if (currentStep.value === 0) {
        onClose();
      }
      currentStep.value--;
    };

    const onOk = () => {
      if (currentStep.value === 0) {
        form.value = {
          ...form.value,
          ...ref0.value.getData(),
        };
        if (!form.value.template) {
          message.warn(t('Select template to continue'));
        } else {
          loading.value = true;
          getFbAdTemplate(form.value.template)
            .then(({ data }) => {
              template.value = data;
              currentStep.value++;
            })
            .finally(() => (loading.value = false));
        }
      } else {
        loading.value = true;
        const raw = [];
        for (let i = 0; i < adAccounts.value.length; i++) {
          raw.push(toRaw(accounts[i]));
          console.log(accounts[i]);
        }
        const payload = raw.map(r => ({
          fb_ad_account_id: r.ad_account_id,
          fb_ad_template_id: template.value.id,
          operator_type: r.operator_type === 'fb' ? 'facebook-user' : 'bm-user',
          operator_id: isString(r.operator) ? r.operator : r.operator.value,
          options:
            r.ad_setup === 'material'
              ? {
                  launch_mode: 1,
                  pixel_id: isString(r.pixel) ? r.pixel : r.pixel.value,
                  material_id_list: r.materials.map(m => (isString(m) ? m : m?.id)),
                  page_id: isString(r.page) ? r.page : r.page.value,
                  link_id: isString(r.links[0]) ? r.links[0] : r.links[0]?.id,
                  copywriting_id: isString(r.copywriting[0])
                    ? r.copywriting[0]
                    : r.copywriting[0]?.id,
                  form_id: isString(r.form) ? r.form : r.form?.value,
                }
              : {
                  launch_mode: 1,
                  pixel_id: isString(r.pixel) ? r.pixel : r.pixel.value,
                  page_id: isString(r.page) ? r.page : r.page.value,
                  copywriting_id: '',
                  form_id: isString(r.form) ? r.form : r.form?.value,
                  post_id: r.post,
                },
        }));
        console.log(payload);
        launchAds(payload)
          .then(() => {
            currentStep.value = 0;
            message.success(t('Ad Launched'));
          })
          .catch(e => {
            console.log(e);
            message.error(t('Operation Failed'));
          })
          .finally(() => (loading.value = false));
      }
    };

    const onClose = () => {
      open.value = false;
    };

    const cache = { fb: [], bm: [], callbacks: [] };
    const debounced = debounce(() => {
      cache.fb.map(id =>
        queryPagesApi({ fb_account_id: id }).then(({ data }) => {
          cache.callbacks.filter(c => c.id === id).forEach(({ callback }) => callback(data));
          cache.callbacks = cache.callbacks.filter(c => c.id !== id);
        }),
      );
      cache.bm.map(id =>
        queryPagesApi({ bm_system_user_id: id }).then(({ data }) => {
          cache.callbacks.filter(c => c.id === id).forEach(({ callback }) => callback(data));
          cache.callbacks = cache.callbacks.filter(c => c.id !== id);
        }),
      );
      cache.fb = [];
      cache.bm = [];
    }, 300);
    const getFbPages = (accountType: 'fb' | 'bm', id: string, callback: (data) => void) => {
      if (!cache[accountType].includes(id)) {
        cache[accountType].push(id);
        cache.callbacks.push({ id, callback });
      }
      debounced();
    };

    return {
      open,
      loading,
      adAccounts,
      template,
      currentStep,
      form,
      ref0,
      ref1,
      getOkText,
      getCancelText,
      onOpen,
      onCancel,
      onOk,
      getFbPages,
      onDataChange,
      t,
    };
  },
});
</script>
<style lang="less">
.invisible {
  display: none;
}
</style>
