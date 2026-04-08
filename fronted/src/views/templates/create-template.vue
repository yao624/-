<template>
  <a-button type="primary" @click="onCreate">{{ t('pages.template.create-template') }}</a-button>
  <a-modal
    v-model:visible="open"
    :title="t('pages.template.create-template')"
    :width="800"
    :height="'80vh'"
    :mask-closable="false"
  >
    <a-steps :current="currentStep" class="steps-header">
      <a-step :title="t('Compaigns')" @click="() => validateAndJumpTo(0)" />
      <a-step :title="t('Adsets')" @click="() => validateAndJumpTo(1)" />
      <a-step :title="t('Ads')" @click="() => validateAndJumpTo(2)" />
    </a-steps>

    <div ref="scrollerRef" :style="{ maxHeight: '60vh', overflowY: 'auto', overflowX: 'auto' }">
      <div :style="{ width: '99%' }">
        <div :class="{ visible: currentStep === 0, invisible: currentStep !== 0 }">
          <formly
            :form-items="stepOne"
            @change:form-data="onFormChange"
            ref="formRef1"
            :rules="{
              budget: { required: true, message: 'Required', trigger: ['change', 'blur'] },
            }"
          >
            <template #campaign_nameTemplate="{ item, form }">
              <macro-form-item :form="form" :item="item"></macro-form-item>
            </template>
            <template #bid_amountTemplate="{ item, form }">
              <template v-if="form.bid_strategy !== 'LOWEST_COST_WITHOUT_CAP'">
                <a-form-item :label="t(item.label)">
                  <a-input
                    v-model:value="form[item.field]"
                    placeholder=".00"
                    @input="e => onNumberChange(e.target.value, form, item.field)"
                  />
                </a-form-item>
              </template>
              <template v-else>
                <div></div>
              </template>
            </template>
            <template #budgetTemplate="{ item, form }">
              <a-form-item :label="t(item.label)" name="budget">
                <a-input
                  v-model:value="form[item.field]"
                  placeholder=".00"
                  @input="e => onNumberChange(e.target.value, form, item.field)"
                />
              </a-form-item>
            </template>
          </formly>
        </div>
        <div :class="{ visible: currentStep === 1, invisible: currentStep !== 1 }">
          <formly ref="formRef2" :form-items="stepTwo" @change:form-data="onFormChange">
            <template #adset_nameTemplate="{ item, form }">
              <macro-form-item :form="form" :item="item"></macro-form-item>
            </template>
            <template #conversion_locationTemplate="{ item, form }">
              <a-form-item :label="t(item.label)">
                <a-radio-group v-model:value="form[item.field]">
                  <a-radio v-for="op in conversionLocationOptions" :key="op.value" :value="op.value">
                    {{ t(op.label) }}
                  </a-radio>
                </a-radio-group>
              </a-form-item>
            </template>
            <template #optimization_goalTemplate="{ item, form }">
              <a-form-item :label="t(item.label)">
                <a-radio-group v-model:value="form[item.field]">
                  <a-radio v-for="op in optimizationGoalOptions" :key="op.value" :value="op.value">
                    {{ t(op.label) }}
                  </a-radio>
                </a-radio-group>
              </a-form-item>
            </template>
            <template #pixel_eventTemplate="{ item, form }">
              <div v-if="pixelEventOptions.length === 0"></div>
              <a-form-item v-else :label="t(item.label)">
                <a-select v-model:value="form[item.field]">
                  <a-select-option v-for="op in pixelEventOptions" :key="op.value" :value="op.value">
                    {{ t(op.label) }}
                  </a-select-option>
                </a-select>
              </a-form-item>
            </template>
            <template #countriesTemplate><div></div></template>
            <template #countries_excludedTemplate><div></div></template>
            <template #regionsTemplate><div></div></template>
            <template #regions_excludedTemplate><div></div></template>
            <template #citiesTemplate><div></div></template>
            <template #cities_excludedTemplate><div></div></template>
            <template #locationTemplate="{ form: formlyForm }">
              <a-card :title="t('Geo')">
                <a-form-item>
                  <a-radio-group v-model:value="form.geo">
                    <a-radio value="countries">{{ t('Countries') }}</a-radio>
                    <a-radio value="regions">{{ t('Regions') }}</a-radio>
                    <a-radio value="cities">{{ t('Cities') }}</a-radio>
                  </a-radio-group>
                </a-form-item>
                <a-form-item :label="t('Location')">
                  <searchable-select
                    :form="formlyForm"
                    :field="form.geo"
                    :searchFunc="searchFuncMap[form.geo]"
                    label-field="name"
                    value-field="key"
                  >
                    <template #option="{ item: optionItem }">
                      <div>
                        <span v-if="form.geo === 'cities'">
                          {{ optionItem.name }}, {{ optionItem.region }},
                          {{ optionItem.country_name }}
                        </span>
                        <span v-else-if="form.geo === 'regions'">
                          {{ optionItem.name }}, {{ optionItem.country_name }}
                        </span>
                        <span v-else>{{ optionItem.label }}</span>
                      </div>
                    </template>
                  </searchable-select>
                </a-form-item>
                <a-form-item :label="t('Location excluded')">
                  <searchable-select
                    :form="formlyForm"
                    :field="`${form.geo}_excluded`"
                    :searchFunc="searchFuncMap[form.geo]"
                    label-field="name"
                    value-field="key"
                  />
                </a-form-item>
              </a-card>
            </template>
            <template #localesTemplate="{ form }">
              <a-form-item :label="t('Languages')">
                <searchable-select
                  :form="form"
                  field="locales"
                  :searchFunc="searchLanguages"
                  label-field="name"
                  value-field="key"
                />
              </a-form-item>
            </template>
            <template #interestsTemplate="{ form }">
              <a-card :title="t('Interests')" :style="{ marginBottom: '8px' }">
                <a-form-item :label="t('Interests')">
                  <searchable-select
                    :form="form"
                    field="interests"
                    :searchFunc="searchInterests"
                    label-field="name"
                    value-field="id"
                  >
                    <template #option="{ item: optionItem }">
                      <div>
                        <a-row>
                          <span>{{ optionItem.label }}</span>
                        </a-row>
                        <a-row>
                          <span class="grey-text">
                            {{ t('Size') }}: {{ optionItem.audience_size_lower_bound }} -
                            {{ optionItem.audience_size_upper_bound }}
                          </span>
                        </a-row>
                      </div>
                    </template>
                  </searchable-select>
                </a-form-item>
                <a-form-item :label="t('Interests excluded')">
                  <searchable-select
                    :form="form"
                    field="interests_excluded"
                    :searchFunc="searchInterests"
                    label-field="name"
                    value-field="id"
                  >
                    <template #option="{ item: optionItem }">
                      <div>
                        <a-row>
                          <span>{{ optionItem.label }}</span>
                        </a-row>
                        <a-row>
                          <span class="grey-text">
                            {{ t('Size') }}: {{ optionItem.audience_size_lower_bound }} -
                            {{ optionItem.audience_size_upper_bound }}
                          </span>
                        </a-row>
                      </div>
                    </template>
                  </searchable-select>
                </a-form-item>
              </a-card>
            </template>
            <template #interests_excludedTemplate>
              <div></div>
            </template>
          </formly>
        </div>
        <div :class="{ visible: currentStep === 2, invisible: currentStep !== 2 }">
          <formly ref="formRef3" :form-items="stepThree" @change:form-data="onFormChange">
            <template #ad_nameTemplate="{ item, form }">
              <macro-form-item :form="form" :item="item"></macro-form-item>
            </template>
          </formly>
        </div>
      </div>
    </div>
    <template #footer>
      <a-button key="back" @click="onCancel">{{ getCancelText(currentStep) }}</a-button>
      <a-button key="submit" type="primary" :loading="loading" @click="onOk">
        {{ getOkText(currentStep) }}
      </a-button>
    </template>
  </a-modal>
</template>

<script lang="ts">
import Formly from '@/components/dynamic-form/formly.vue';
import { defineComponent, reactive, ref, toRaw, watch } from 'vue';
import { useI18n } from 'vue-i18n';
import { searchCities, searchCountries, searchRegions } from '@/api/geo_location';
import debounce from '@/utils/debonce';
import {
  searchLanguages,
  searchInterests,
  createFbAdTemplate,
  updateFbAdTemplate,
} from '@/api/fb_ad_template';
import {
  adsetsForm,
  adsForm,
  BidStrategy,
  compaignForm,
  ConversionLocation,
  Objective,
  OptimizationGoal,
} from './form-items';
import { cloneDeep, isNil } from 'lodash';
import SearchableSelect from '@/components/searchable-select/searchable-select.vue';
import MacroFormItem from './macro-form-item.vue';

export default defineComponent({
  name: 'CreateTemplate',
  components: {
    Formly,
    SearchableSelect,
    MacroFormItem,
  },
  emits: ['saved', 'cancel'],
  setup(_, { emit }) {
    const { t } = useI18n();

    const open = ref(false);
    const loading = ref(false);

    const currentStep = ref(0);

    // const geos = ref(['countries', 'regions', 'cities']);
    const scrollerRef = ref(null);
    const templateFormData = reactive<any>({});
    const templateId = ref('');
    const conversionLocationOptions = ref([]);
    const optimizationGoalOptions = ref([]);
    const pixelEventOptions = ref([]);
    const asyncOptions = ref<any>({
      countries: [],
      regions: [],
      cities: [],
      countriesExcluded: [],
      regionsExcluded: [],
      citiesExcluded: [],
      languages: [],
    });

    const searchFuncMap = ref({
      countries: searchCountries,
      regions: searchRegions,
      cities: searchCities,
    });

    const stepOne = ref(cloneDeep(compaignForm));
    const stepTwo = ref(cloneDeep(adsetsForm));
    const stepThree = ref(cloneDeep(adsForm));

    const formRef1 = ref(null);
    const formRef2 = ref(null);
    const formRef3 = ref(null);

    const form = reactive({
      geo: 'countries',
    });

    const onCreate = () => {
      currentStep.value = 0;
      open.value = true;
      formRef1.value?.onReset({
        objective: Objective.OUTCOME_SALES,
        bid_strategy: BidStrategy.LOWEST_COST_WITHOUT_CAP,
        budget_type: 'daily',
        budget_level: 'campaign',
      });
      formRef2.value?.onReset({
        conversion_location: ConversionLocation.WEBSITE,
        optimization_goal: OptimizationGoal.OFFSITE_CONVERSIONS,
        pixel_event: 'PURCHASE',
      });
      formRef3.value?.onReset();
    };

    const onEdit = temp => {
      const data = toRaw(temp);
      currentStep.value = 0;
      templateId.value = data.id;
      Object.keys(templateFormData).forEach(key => (templateFormData[key] = null));
      Object.keys(data).forEach(key => (templateFormData[key] = data[key]));
      // templateFormData.cities_included = templateFormData.cities_included || [];
      // templateFormData.cities_excluded = templateFormData.cities_excluded || [];
      // templateFormData.countries_excluded = templateFormData.countries_excluded || [];
      // templateFormData.countries_included = templateFormData.countries_included || [];
      // templateFormData.regions_excluded = templateFormData.regions_excluded || [];
      // templateFormData.regions_included = templateFormData.regions_included || [];
      // templateFormData.interests = templateFormData.interests || [];
      // templateFormData.locales = templateFormData.locales || [];
      const formItems1 = cloneDeep(compaignForm).map(item => ({
        ...item,
        value: data[item.field],
      }));
      const formItems2 = cloneDeep(adsetsForm).map(item => ({ ...item, value: data[item.field] }));
      const formItems3 = cloneDeep(adsForm).map(item => ({ ...item, value: data[item.field] }));

      formItems1.find(item => item.field === 'accelerated').value =
        data.accelerated === '1' ? true : false;

      formItems2.find(item => item.field === 'interests').value = data.interests?.map(
        ({ id, name }) => ({ label: name, name, key: id, id, value: id }),
      );
      formItems2.find(item => item.field === 'interests_excluded').value =
        data.interests_excluded?.map(({ id, name }) => ({
          label: name,
          key: id,
          value: id,
          id,
          name,
        }));
      formItems2.find(item => item.field === 'locales').value = data.locales?.map(
        ({ key, name }) => ({ label: name, key, name, id: key, value: key }),
      );

      formItems2.find(item => item.field === 'countries').value = data.countries_included?.map(
        ({ key, name }) => ({ label: name, key, name, id: key }),
      );
      formItems2.find(item => item.field === 'countries_excluded').value =
        data.countries_excluded?.map(({ key, name }) => ({ label: name, key, name, id: key }));
      formItems2.find(item => item.field === 'regions').value = data.regions_included?.map(
        ({ key, name }) => ({ label: name, key }),
      );
      formItems2.find(item => item.field === 'regions_excluded').value = data.regions_excluded?.map(
        ({ key, name }) => ({ label: name, key }),
      );
      formItems2.find(item => item.field === 'cities').value = data.cities_included?.map(
        ({ key, name }) => ({ label: name, key }),
      );
      formItems2.find(item => item.field === 'cities_excluded').value = data.cities_excluded?.map(
        ({ key, name }) => ({ label: name, key }),
      );

      conversionLocationOptions.value = getConversionLocationOptions(data.objective);
      optimizationGoalOptions.value = getOptimizationGoalOptions(
        data.objective,
        data.conversion_location,
      );
      pixelEventOptions.value = getPixelEventOptions(
        data.objective,
        data.conversion_location,
        data.optimization_goal,
      );

      stepOne.value = formItems1;
      stepTwo.value = formItems2;
      stepThree.value = formItems3;
      open.value = true;
    };

    const getOkText = (step: number) => (step < 2 ? t('Next') : t('Save'));

    const getCancelText = (step: number) => (step > 0 ? t('Back') : t('Cancel'));

    const onNumberChange = (value, form, field) =>
      /^\d*\.?\d{0,2}$/.test(value) ? null : (form[field] = value.slice(0, -1));

    const validateAndJumpTo = (step: number) => {
      const formRef = [formRef1, formRef2, formRef3][currentStep.value];
      formRef.value
        .validate()
        .then(() => (currentStep.value = step))
        .catch(err => console.error(err));
    };

    const onOk = () => {
      const formRef = [formRef1, formRef2, formRef3][currentStep.value];
      formRef.value
        .validate()
        .then(() => {
          scrollerRef.value.scrollTop = 0;
          if (currentStep.value < 2) {
            currentStep.value++;
          } else {
            // save
            const raw = toRaw(templateFormData);
            loading.value = true;
            const payload = {
              ...raw,
              locales: raw.locales?.map(({ label, name, key }) => ({ name: label || name, key })),
              countries_included: raw.countries?.map(({ label, name, key }) => ({
                name: label || name,
                key,
              })),
              countries_excluded: raw.countries_excluded?.map(({ label, name, key }) => ({
                name: label || name,
                key,
              })),
              regions_included: raw.regions?.map(({ label, name, key, option }) => ({
                name: label || name,
                key,
                country_name: option?.country_name,
              })),
              regions_excluded: raw.regions_excluded?.map(({ label, name, key, option }) => ({
                name: label || name,
                key,
                country_name: option?.country_name,
              })),
              cities_included: raw.cities?.map(({ label, name, key, option }) => ({
                name: label || name,
                key,
                country_name: option?.country_name,
              })),
              cities_excluded: raw.cities_excluded?.map(({ label, name, key, option }) => ({
                name: label || name,
                key,
                country_name: option?.country_name,
              })),
              interests: raw.interests?.map(({ label, name, key }) => ({
                name: label || name,
                id: key,
              })),
              interests_excluded: raw.interests_excluded?.map(({ label, name, key }) => ({
                name: label || name,
                id: key,
              })),
            };
            delete payload.cities;
            delete payload.countries;
            delete payload.regions;
            Object.entries(payload)
              .filter(([, value]) => isNil(value))
              .map(([key]) => key)
              .filter(key => delete payload[key]);
            if (!templateId.value) {
              createFbAdTemplate(payload)
                .then(() => {
                  // house keeping
                  onCreate();
                  emit('saved');
                  open.value = false;
                })
                .finally(() => (loading.value = false));
            } else {
              updateFbAdTemplate(templateId.value, payload)
                .then(() => {
                  // house keeping
                  onCreate();
                  emit('saved');
                  open.value = false;
                })
                .finally(() => (loading.value = false));
            }
          }
        })
        .catch(err => console.error(err));
    };

    const onCancel = () => {
      if (currentStep.value > 0) {
        currentStep.value--;
        open.value = true;
      } else {
        open.value = false;
      }
      emit('cancel');
    };

    const onFormChange = data => Object.assign(templateFormData, data);

    const onSearch = (geo: string, value: string, key: string) => {
      const map = {
        countries: searchCountries,
        regions: searchRegions,
        cities: searchCities,
        languages: searchLanguages,
      };
      const func: (value: string) => Promise<any> = map[geo];
      func(value)
        .then(data => {
          // console.log(data)
          asyncOptions.value[key] = data.map(({ country_name, name, key }) => ({
            country_name,
            value: country_name || key,
            name,
          }));
        })
        .catch(() => console.error('Search failed'));
    };

    const debouncedSearch = debounce(onSearch);

    const getConversionLocationOptions = objective => {
      if (objective === Objective.OUTCOME_SALES) {
        return [{ label: 'Website', value: ConversionLocation.WEBSITE }];
      } else if (objective === Objective.OUTCOME_LEADS) {
        return [
          { label: 'Website', value: ConversionLocation.WEBSITE },
          { label: 'Instant forms', value: ConversionLocation.INSTANT_FORMS },
        ];
      }
      return [];
    };

    const getOptimizationGoalOptions = (objective, location) => {
      if (
        objective === Objective.OUTCOME_SALES ||
        (objective === Objective.OUTCOME_LEADS && location === ConversionLocation.WEBSITE)
      ) {
        return [
          { label: 'Maximize number of conversions', value: OptimizationGoal.OFFSITE_CONVERSIONS },
        ];
      } else if (
        objective === Objective.OUTCOME_LEADS &&
        location === ConversionLocation.INSTANT_FORMS
      ) {
        return [
          { label: 'Maximize number of leads', value: OptimizationGoal.LEAD_GENERATION },
          { label: 'Maximize number of conversion leads', value: OptimizationGoal.QUALITY_LEAD },
        ];
      }
      return [];
    };

    const getPixelEventOptions = (objective, location, goal) => {
      if (
        objective === Objective.OUTCOME_LEADS &&
        [OptimizationGoal.LEAD_GENERATION, OptimizationGoal.QUALITY_LEAD].includes(goal)
      ) {
        return [];
      } else if (
        objective === Objective.OUTCOME_SALES &&
        location === ConversionLocation.WEBSITE &&
        goal === OptimizationGoal.OFFSITE_CONVERSIONS
      ) {
        return [
          { label: 'Add to cart', value: 'ADD_TO_CART' },
          { label: 'Add payment info', value: 'ADD_PAYMENT_INFO' },
          { label: 'Add to wishlist', value: 'ADD_TO_WISHLIST' },
          { label: 'Complete registration', value: 'COMPLETE_REGISTRATION' },
          { label: 'Donate', value: 'DONATE' },
          { label: 'Init checkout', value: 'INITIATE_CHECKOUT' },
          { label: 'Purchase', value: 'PURCHASE' },
          { label: 'Search', value: 'SEARCH' },
          { label: 'Start trial', value: 'START_TRIAL' },
          { label: 'Subscribe', value: 'SUBSCRIBE' },
          { label: 'View content', value: 'VIEW_CONTENT' },
        ];
      } else if (
        objective === Objective.OUTCOME_LEADS &&
        location === ConversionLocation.WEBSITE &&
        goal === OptimizationGoal.OFFSITE_CONVERSIONS
      ) {
        return [
          { label: 'Contact', value: 'CONTACT' },
          { label: 'Find Location', value: 'FIND_LOCATION' },
          { label: 'Lead', value: 'LEAD' },
          { label: 'Schedule', value: 'SCHEDULE' },
          { label: 'Search', value: 'SEARCH' },
          { label: 'Start trial', value: 'START_TRIAL' },
          { label: 'Submit Application', value: 'SUBMIT_APPLICATION' },
          { label: 'Subscribe', value: 'SUBSCRIBE' },
          { label: 'View content', value: 'VIEW_CONTENT' },
        ];
      }
      return [];
    };

    watch(
      templateFormData,
      newValue => {
        const { objective: obj, conversion_location: loc, optimization_goal: goal } = newValue;
        const locationItem = stepTwo.value.find(item => item.field === 'conversion_location');
        const goalItem = stepTwo.value.find(item => item.field === 'optimization_goal');
        const eventItem = stepTwo.value.find(item => item.field === 'pixel_event');

        // conversion location options
        conversionLocationOptions.value = getConversionLocationOptions(obj);
        if (obj === Objective.OUTCOME_SALES) {
          locationItem.value = ConversionLocation.WEBSITE;
        } else if (obj === Objective.OUTCOME_LEADS) {
          locationItem.value = loc || ConversionLocation.INSTANT_FORMS;
        }

        // optimization goal options
        optimizationGoalOptions.value = getOptimizationGoalOptions(obj, loc);
        if (
          obj === Objective.OUTCOME_SALES ||
          (obj === Objective.OUTCOME_LEADS && loc === ConversionLocation.WEBSITE)
        ) {
          goalItem.value = OptimizationGoal.OFFSITE_CONVERSIONS;
        } else if (obj === Objective.OUTCOME_LEADS && loc === ConversionLocation.INSTANT_FORMS) {
          goalItem.value = OptimizationGoal.LEAD_GENERATION;
        }

        // pixel event options
        pixelEventOptions.value = getPixelEventOptions(obj, loc, goal);
        if (
          obj === Objective.OUTCOME_SALES &&
          loc === ConversionLocation.WEBSITE &&
          goal === OptimizationGoal.OFFSITE_CONVERSIONS
        ) {
          eventItem.value = 'PURCHASE';
        } else if (
          obj === Objective.OUTCOME_LEADS &&
          loc === ConversionLocation.WEBSITE &&
          goal === OptimizationGoal.OFFSITE_CONVERSIONS
        ) {
          eventItem.value = 'LEAD';
        }
      },
      { deep: true },
    );

    return {
      open,
      loading,
      currentStep,
      scrollerRef,
      form,
      stepOne,
      stepTwo,
      stepThree,
      getOkText,
      getCancelText,
      onOk,
      onCancel,
      asyncOptions,
      onFormChange,

      formRef1,
      formRef2,
      formRef3,
      templateId,

      conversionLocationOptions,
      optimizationGoalOptions,
      pixelEventOptions,
      // geos,
      debouncedSearch,
      onNumberChange,
      searchLanguages,
      searchInterests,
      validateAndJumpTo,
      searchFuncMap,
      onEdit,
      onCreate,
      t,
    };
  },
});
</script>

<style scoped>
.visible {
  visibility: visible;
}
.invisible {
  visibility: hidden;
  height: 0;
  display: none;
}

.steps-header {
  margin-bottom: 20px;
}

.form-content {
  max-width: 600px;
  margin: 0 auto;
}

.form-footer {
  display: flex;
  justify-content: space-between;
  margin-top: 20px;
}

.accelerated-switch {
  margin-top: 10px;
}

.grey-text {
  color: grey;
}
</style>
