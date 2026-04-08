<template>
  <a-form layout="vertical">
    <a-row :gutter="[12, 0]" :wrap="false" :style="{ width: '100%' }">
      <a-col :span="6">
        <a-form-item :label="t('Ad account')">
          <a-row :gutter="[12, 0]" :wrap="false">
            <a-col :flex="1">
              <a-tooltip :title="t('Refresh ad account')">
                <a-input v-model:value="form.ad_account" disabled />
              </a-tooltip>
            </a-col>
            <a-col>
              <a-button
                type="primary"
                :loading="loadings['account']"
                :icon="h(ReloadOutlined)"
                @click="onReload"
              ></a-button>
            </a-col>
          </a-row>
        </a-form-item>
      </a-col>
      <a-col :span="18">
        <a-row :gutter="[12, 0]" :wrap="true">
          <a-col :span="6">
            <a-form-item :label="t('Operator')">
              <a-select
                v-model:value="form.operator"
                :loading="loadings['account']"
                :label-in-value="true"
                @change="({ option }: any) => getFbPagesLocal(option.type, option.value)"
              >
                <a-select-opt-group :label="t('BMs')">
                  <a-select-option
                    v-for="bm in adAccount.bm_system_users"
                    :value="bm.id"
                    :type="'bm'"
                    :key="bm.id"
                  >
                    {{ bm.name }}
                  </a-select-option>
                </a-select-opt-group>
                <a-select-opt-group :label="t('Fb Accounts')">
                  <a-select-option
                    v-for="fa in adAccount.fb_accounts"
                    :value="fa.id"
                    :key="fa.id"
                    :type="'fb'"
                  >
                    {{ fa.name }}
                  </a-select-option>
                </a-select-opt-group>
              </a-select>
            </a-form-item>
          </a-col>
          <a-col :span="6">
            <a-form-item :label="t('Page')">
              <a-select
                v-model:value="form.page"
                :loading="loadings['page']"
                :label-in-value="true"
                @change="({ option }: any) => form.page_source_id = option.source_id"
              >
                <a-select-option
                  v-for="p in pages"
                  :value="p.id"
                  :key="p.id"
                  :source_id="p.source_id"
                >
                  {{ p.name }} - {{ p.source_id }}
                </a-select-option>
              </a-select>
            </a-form-item>
          </a-col>
          <a-col
            :span="6"
            v-if="
              template.objective === 'OUTCOME_LEADS' &&
              template.conversion_location === 'INSTANT_FORMS'
            "
          >
            <a-form-item :label="t('Form')">
              <a-select
                v-model:value="form.form"
                :loading="loadings['form']"
                :label-in-value="true"
              >
                <a-select-option v-for="p in forms" :key="p.id" :value="p.id">
                  {{ p.name }} - {{ p.source_id }}
                </a-select-option>
              </a-select>
            </a-form-item>
          </a-col>
          <a-col :span="6">
            <a-form-item :label="t('Pixel')">
              <a-select
                v-model:value="form.pixel"
                :loading="loadings['account']"
                :label-in-value="true"
              >
                <a-select-option
                  v-for="p in adAccount.pixels"
                  :key="p.id"
                  :value="p.id"
                  :disabled="p.is_unavailable"
                >
                  {{ p.name }}
                </a-select-option>
              </a-select>
            </a-form-item>
          </a-col>
          <a-col :span="6">
            <a-form-item :label="t('Ad setup')">
              <a-select v-model:value="form.ad_setup" @change="() => console.log(form.ad_setup)">
                <a-select-option value="material">{{ t('Material') }}</a-select-option>
                <a-select-option value="post">{{ t('Post') }}</a-select-option>
              </a-select>
            </a-form-item>
          </a-col>
          <template v-if="form.ad_setup === 'material'">
            <a-col :span="6">
              <a-form-item :label="t('Materials')">
                <a-row :gutter="[6, 0]" :wrap="false">
                  <a-col :flex="1">
                    <a-select :value="getMaterialsValue()">
                      <a-select-option
                        v-for="m in form.materials"
                        :value="m.id"
                        :key="m.id"
                        disabled
                      >
                        {{ m.name }}
                      </a-select-option>
                    </a-select>
                  </a-col>
                  <a-col>
                    <pick-objects
                      :api="materialsList"
                      :columns="[
                        { title: 'Name', dataIndex: 'name', key: 'name' },
                        { title: 'Filename', dataIndex: 'filename', key: 'filename' },
                        { title: 'Notes', dataIndex: 'notes', key: 'notes' },
                        { title: 'Tags', dataIndex: 'tags', key: 'tags' },
                      ]"
                      @confirm:items-selected="(_, rows) => (form.materials = rows)"
                    ></pick-objects>
                  </a-col>
                </a-row>
              </a-form-item>
            </a-col>
            <a-col :span="6">
              <a-form-item :label="t('Links')">
                <a-row :gutter="[6, 0]" :wrap="false">
                  <a-col :flex="1">
                    <a-select :value="(form.links || [])[0]?.link || ''">
                      <a-select-option v-for="m in form.links" :value="m.id" :key="m.id" disabled>
                        {{ m.link }}
                      </a-select-option>
                    </a-select>
                  </a-col>
                  <a-col>
                    <pick-objects
                      :multiple="false"
                      :api="queryLinksApi"
                      :allow-empty="false"
                      :columns="[
                        { title: 'Link', dataIndex: 'link', key: 'link' },
                        { title: 'Notes', dataIndex: 'notes', key: 'notes' },
                        { title: 'Tags', dataIndex: 'tags', key: 'tags' },
                      ]"
                      @confirm:items-selected="(_, rows) => (form.links = rows)"
                    ></pick-objects>
                  </a-col>
                </a-row>
              </a-form-item>
            </a-col>
            <a-col :span="6">
              <a-form-item :label="t('Copywriting')">
                <a-row :gutter="[6, 0]" :wrap="false">
                  <a-col :flex="1">
                    <a-select :value="(form.copywriting || [])[0]?.id || ''">
                      <a-select-option
                        v-for="c in form.copywriting"
                        :value="c.id"
                        :key="c.id"
                        disabled
                      >
                        {{ c.headline }}
                      </a-select-option>
                    </a-select>
                  </a-col>
                  <a-col>
                    <pick-objects
                      :multiple="false"
                      :api="queryCopywritingsApi"
                      :allow-empty="true"
                      :columns="[
                        { title: 'Headline', dataIndex: 'headline', key: 'headline' },
                        { title: 'Primary Text', dataIndex: 'primary_text', key: 'primary_text' },
                        { title: 'Description', dataIndex: 'description', key: 'description' },
                        { title: 'Notes', dataIndex: 'notes', key: 'notes' },
                      ]"
                      @confirm:items-selected="(_, rows) => (form.copywriting = rows)"
                    ></pick-objects>
                  </a-col>
                </a-row>
              </a-form-item>
            </a-col>
          </template>
          <template v-else>
            <a-col :span="6">
              <a-form-item :label="t('Post')">
                <a-input v-model:value="form.post" />
              </a-form-item>
            </a-col>
          </template>
        </a-row>
      </a-col>
    </a-row>
  </a-form>
</template>
<script lang="ts">
import { getPageForms } from '@/api/pages';
// import SearchableSelect from '@/components/searchable-select/searchable-select.vue';
import { defineComponent, onMounted, reactive, ref, watch, watchEffect, h } from 'vue';
import { useI18n } from 'vue-i18n';
// import PickMaterialsLinks from './pick-materials-links.vue';
import { ReloadOutlined } from '@ant-design/icons-vue';
import { queryFB_AD_AccountOneApi } from '@/api/fb_ad_accounts';
import { queryLinksApi } from '@/api/links';
import { materialsList } from '@/api/materials';
import { queryCopywritingsApi } from '@/api/copywritings';
import PickObjects from './pick-objects.vue';

// const fbUser = 'facebook-user';
// const bmUser = 'bm-user';

export default defineComponent({
  name: 'EditAccount',
  emits: ['change:account-data'],
  components: {
    // SearchableSelect,
    // PickMaterialsLinks,
    // PickCopywriting,
    PickObjects,
  },
  props: {
    adAccount: {
      type: Object,
      required: true,
    },
    template: {
      type: Object,
      required: true,
    },
    getFbPages: {
      type: Function,
      required: true,
    },
  },
  setup(props, { emit }) {
    const { t } = useI18n();

    const pages = ref([]);
    const forms = ref([]);
    const loadings = ref({});
    const template = ref<any>({});
    const adAccount = ref(props.adAccount);

    const getDefaultOperator = () => {
      const { bm_system_users, fb_accounts } = adAccount.value;
      return fb_accounts[0]?.id || bm_system_users[0]?.id || null;
    };

    const getMaterialsValue = () => {
      if (!(form.materials || []).length) {
        return '';
      }
      return form.materials.map(({ name }) => name);
    };

    const form = reactive<any>({
      ad_account_id: adAccount.value.id,
      ad_account: adAccount.value.name,
      operator: getDefaultOperator(),
      pixel: (adAccount.value.pixels || [])[0]?.id,
      page_source_id: '',
      ad_setup: 'material',
    });

    onMounted(() => loadPages());

    watchEffect(() => {
      template.value = props.template;
      if (
        form.page_source_id &&
        props.template.objective === 'OUTCOME_LEADS' &&
        props.template.conversion_location === 'INSTANT_FORMS'
      ) {
        getForms(form.page_source_id);
      }
    });

    watch(
      () => form.page_source_id,
      value => getForms(value),
    );

    const loadPages = () => {
      if (adAccount.value.fb_accounts[0]) {
        getFbPagesLocal('fb', props.adAccount.fb_accounts[0].id);
      } else if (adAccount.value.bm_system_users[0]) {
        getFbPagesLocal('bm', props.adAccount.bm_system_users[0].id);
      }
    };

    const onReload = () => {
      loadings.value['account'] = true;
      queryFB_AD_AccountOneApi({ id: adAccount.value.id }).then(({ data }) => {
        adAccount.value = data;
        form.operator = getDefaultOperator();
        (form.pixel = (data.pixels || [])[0]?.id), loadPages();
        loadings.value['account'] = false;
      });
    };

    const getFbPagesLocal = (accountType: 'fb' | 'bm', id: string) => {
      form.operator_type = accountType;
      loadings.value['page'] = true;
      props.getFbPages(accountType, id, data => {
        pages.value = data;
        form.page = data[0]?.id;
        loadings.value['page'] = false;
      });
    };

    const getForms = (page_source_id: string) => {
      loadings.value['form'] = true;
      getPageForms({ page_source_id }).then(({ data }) => {
        forms.value = data;
        form.form = data[0]?.id;
        loadings.value['form'] = false;
      });
    };

    watch(
      () => form,
      value => emit('change:account-data', value),
      { deep: true },
    );

    return {
      adAccount,
      loadings,
      t,
      h,
      form,
      template,
      getFbPages: getFbPagesLocal,
      getMaterialsValue,
      onReload,
      pages,
      forms,
      ReloadOutlined,
      queryLinksApi,
      materialsList,
      queryCopywritingsApi,
    };
  },
});
</script>
