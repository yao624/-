<template>
  <div class="ad_form">
    <div class="ad_form_step">
      <a-steps
        :current="current"
        :items="[
          {
            title: t('pages.ad.accounts.launch.step1'),
          },
          {
            title: t('pages.ad.accounts.launch.step2'),
          },
          {
            title: t('pages.ad.accounts.launch.step3'),
          },
          {
            title: t('pages.ad.accounts.launch.finish'),
          },
        ]"
      ></a-steps>
    </div>
    <div class="steps-content">
      <div v-if="current == 0">
        <a-form
          :model="form"
          :form="form"
          ref="formRef"
          :label-col="labelCol"
          :wrapper-col="wrapperCol"
        >
          <a-divider>{{ t('pages.ad.accounts.launch.divider') }}</a-divider>
          <a-form-item
            :label="$t('pages.ad.accounts.launch.name')"
            name="campaign_name_tpl"
            :rules="[{ required: true }]"
          >
            <a-input v-model:value="form.campaign_name_tpl" />
          </a-form-item>
          <a-form-item
            name="campaign_objective"
            :label="t('pages.ad.accounts.launch.objective')"
            :rules="[{ required: true }]"
          >
            <a-select v-model:value="form.campaign_objective">
              <a-select-option value="Sales">
                {{ t('pages.ad.accounts.launch.sales') }}
              </a-select-option>
              <a-select-option disabled value="App promotion">
                {{ t('pages.ad.accounts.launch.app.promotion') }}
              </a-select-option>
              <a-select-option disabled value="Leads">
                {{ t('pages.ad.accounts.launch.leads') }}
              </a-select-option>
              <a-select-option disabled value="Engagement">
                {{ t('pages.ad.accounts.launch.engagement') }}
              </a-select-option>
              <a-select-option disabled value="Traffic">
                {{ t('pages.ad.accounts.launch.traffic') }}
              </a-select-option>
              <a-select-option disabled value="Awareness">
                {{ t('pages.ad.accounts.launch.awareness') }}
              </a-select-option>
            </a-select>
          </a-form-item>
          <a-divider>{{ t('pages.ad.accounts.launch.campaign.group') }}</a-divider>
          <a-form-item
            :label="$t('pages.ad.accounts.launch.group.name')"
            name="adset_name_tpl"
            :rules="[{ required: true }]"
          >
            <a-input v-model:value="form.adset_name_tpl" />
          </a-form-item>
          <a-form-item name="geo" :label="t('pages.country')" :rules="[{ required: true }]">
            <a-select v-model:value="form.geo" mode="multiple">
              <a-select-option value="US">US</a-select-option>
              <a-select-option value="CA">CA</a-select-option>
              <a-select-option value="AU">AU</a-select-option>
              <a-select-option value="UK">UK</a-select-option>
              <a-select-option value="ZA">ZA</a-select-option>
              <a-select-option value="NZ">NZ</a-select-option>
            </a-select>
          </a-form-item>
          <a-form-item name="genders" :label="t('pages.genders')" :rules="[{ required: true }]">
            <a-radio-group v-model:value="form.genders">
              <a-radio :value="0">{{ t('pages.no.limit') }}</a-radio>
              <a-radio :value="1">{{ t('pages.male') }}</a-radio>
              <a-radio :value="2">{{ t('pages.female') }}</a-radio>
            </a-radio-group>
          </a-form-item>
          <a-form-item name="age_range" :label="t('pages.age.age')" :rules="[{ required: true }]">
            <a-slider v-model:value="form.age_range" range :min="21" :max="65" />
          </a-form-item>
          <a-form-item
            name="conversion_event"
            :label="t('pages.ad.accounts.launch.conversion.event')"
            :rules="[{ required: true }]"
          >
            <a-select v-model:value="form.conversion_event">
              <a-select-option value="Purchase">Purchase</a-select-option>
              <a-select-option disabled value=" Add To Cart">Add To Cart</a-select-option>
            </a-select>
          </a-form-item>
          <a-divider>{{ t('pages.ad.accounts.launch.ad') }}</a-divider>
          <a-form-item
            :label="$t('pages.ad.accounts.launch.ad.name')"
            name="adset_name_tpl"
            :rules="[{ required: true }]"
          >
            <a-input v-model:value="form.adset_name_tpl" />
          </a-form-item>
          <a-divider>{{ t('pages.ad.accounts.launch.budget') }}</a-divider>
          <a-form-item
            name="budget_type"
            :label="t('pages.ad.accounts.launch.budget.type')"
            :rules="[{ required: true }]"
          >
            <a-select v-model:value="form.budget_type">
              <a-select-option value="CBO">
                {{ t('pages.ad.accounts.launch.budget.period.cbo') }}
              </a-select-option>
              <a-select-option value="ABO">
                {{ t('pages.ad.accounts.launch.budget.period.abo') }}
              </a-select-option>
            </a-select>
          </a-form-item>
          <a-form-item
            name="budget_period"
            :label="t('pages.ad.accounts.launch.budget.period')"
            :rules="[{ required: true }]"
          >
            <a-select v-model:value="form.budget_period">
              <a-select-option value="daily">
                {{ t('pages.ad.accounts.launch.daily.budget') }}
              </a-select-option>
              <a-select-option value="lifetime">
                {{ t('pages.ad.accounts.launch.lifetime.budget') }}
              </a-select-option>
            </a-select>
          </a-form-item>
        </a-form>
      </div>
      <div v-if="current == 1">
        <div class="center">
          <a-button type="primary" @click="openSelectAdAccountModal">
            {{ t('pages.ad.accounts.launch.select.ad.account') }}
          </a-button>
        </div>

        <div class="container">
          <div v-for="(item, index) in selectedAdAccountsForm" :key="index" class="ad-container">
            <sync-outlined
              :style="{ fontSize: '29px', color: '#1677ff' }"
              :spin="item.loading"
              @click="() => reloadAdAccount(index)"
            />
            <a-card class="ad-info">
              <a-row :gutter="[15, 0]">
                <a-col :span="8" class="ad-left-column">
                  <span>{{ t('pages.ad.accounts.ad.account') }}:</span>
                </a-col>
                <a-col :span="16">
                  <span class="ad-right-column">{{ item.name }}</span>
                </a-col>
              </a-row>
              <a-row :gutter="[15, 0]">
                <a-col :span="8" class="ad-left-column">{{ t('pages.id') }}:</a-col>
                <a-col :span="16">
                  <span class="ad-right-column">{{ item.id }}</span>
                </a-col>
              </a-row>
              <a-row :gutter="[15, 0]">
                <a-col :span="8" class="ad-left-column">{{ t('pages.status') }}:</a-col>
                <a-col :span="16">
                  <span class="ad-right-column">{{ item.account_status }}</span>
                </a-col>
              </a-row>
              <a-row :gutter="[15, 0]">
                <a-col :span="8" class="ad-left-column">
                  {{ t('pages.ad.accounts.adtrust.dsl') }}:
                </a-col>
                <a-col :span="16">
                  <span class="ad-right-column">{{ item.adtrust_dsl }} USD</span>
                </a-col>
              </a-row>
            </a-card>

            <div class="six-selection-boxes">
              <a-row :gutter="[15, 0]">
                <a-col :span="9">
                  <a-form-item
                    :label="t('pages.ad.accounts.launch.personal.account')"
                    :rules="[{ required: true }]"
                  >
                    <a-select
                      @change="queryFbAccountOne"
                      v-model:value="item.fb_account_id"
                      size="middle"
                      :placeholder="t('pages.plsSelect')"
                    >
                      <a-select-option
                        v-for="option in item.fb_accounts"
                        :key="option.id"
                        :value="option.id"
                        :label="option.name"
                        :index="index"
                      >
                        {{ option.name }}
                      </a-select-option>
                    </a-select>
                  </a-form-item>
                </a-col>
                <a-col :span="1">
                  <align-right-outlined
                    :style="{ fontSize: '29px' }"
                    @click="() => handleFbAccuntsAlignClick(index)"
                  />
                </a-col>
                <a-col :span="9">
                  <a-form-item
                    :label="t('pages.ad.accounts.launch.budget')"
                    :rules="[{ required: true }]"
                  >
                    <a-input-number
                      v-model:value="item.budget"
                      :step="0.01"
                      style="width: 150px"
                      :parser="parseNumber"
                      string-mode
                      min="0"
                    />
                  </a-form-item>
                </a-col>
                <a-col :span="1">
                  <align-right-outlined
                    :style="{ fontSize: '29px' }"
                    @click="() => handleBudgetAlignClick(index)"
                  />
                </a-col>
              </a-row>
              <a-row :gutter="[15, 0]">
                <a-col :span="9">
                  <a-form-item
                    :label="t('pages.ad.accounts.launch.home.page')"
                    :rules="[{ required: true }]"
                  >
                    <a-select
                      v-model:value="item.page_id"
                      size="middle"
                      :placeholder="t('pages.plsSelect')"
                    >
                      <template v-if="!item.loading">
                        <a-select-option
                          v-for="option in item.pages"
                          :key="option.id"
                          :value="option.id"
                          :label="option.name"
                          :disabled="!option.promotion_eligible"
                        >
                          {{ option.name }}
                        </a-select-option>
                      </template>
                      <template v-else>
                        <a-select-option disabled>
                          <a-spin size="small" />
                        </a-select-option>
                      </template>
                    </a-select>
                  </a-form-item>
                </a-col>
                <a-col :span="1">
                  <align-right-outlined
                    :style="{ fontSize: '29px' }"
                    @click="() => handlePageAlignClick(index)"
                  />
                </a-col>
                <a-col :span="9">
                  <a-form-item :label="t('pages.pixels.pixel')" :rules="[{ required: true }]">
                    <a-select
                      v-model:value="item.pixel_id"
                      size="middle"
                      :placeholder="t('pages.plsSelect')"
                    >
                      <a-select-option
                        v-for="option in item.pixels"
                        :key="option.id"
                        :value="option.id"
                        :label="option.name"
                      >
                        {{ option.name }}
                      </a-select-option>
                    </a-select>
                  </a-form-item>
                </a-col>
                <a-col :span="1">
                  <align-right-outlined
                    :style="{ fontSize: '29px' }"
                    @click="() => handlePixelAlignClick(index)"
                  />
                </a-col>
              </a-row>
            </div>
            <close-outlined
              :style="{ fontSize: '24px', color: 'red' }"
              @click="() => removeSelected(index)"
            />
          </div>
        </div>
      </div>
      <div v-if="current == 2">
        <div v-for="(item, index) in selectedAdAccountsForm" :key="index" class="ad-container">
          <a-card class="ad-info">
            <a-row :gutter="[15, 0]">
              <a-col :span="8" class="ad-left-column">
                <span>{{ t('pages.ad.accounts.ad.account') }}:</span>
              </a-col>
              <a-col :span="16">
                <span class="ad-right-column">{{ item.name }}</span>
              </a-col>
            </a-row>
            <a-row :gutter="[15, 0]">
              <a-col :span="8" class="ad-left-column">{{ t('pages.id') }}:</a-col>
              <a-col :span="16">
                <span class="ad-right-column">{{ item.id }}</span>
              </a-col>
            </a-row>
            <a-row :gutter="[15, 0]">
              <a-col :span="8" class="ad-left-column">{{ t('pages.status') }}:</a-col>
              <a-col :span="16">
                <span class="ad-right-column">{{ item.account_status }}</span>
              </a-col>
            </a-row>
            <a-row :gutter="[15, 0]">
              <a-col :span="8" class="ad-left-column">
                {{ t('pages.ad.accounts.adtrust.dsl') }}:
              </a-col>
              <a-col :span="16">
                <span class="ad-right-column">{{ item.adtrust_dsl }} USD</span>
              </a-col>
            </a-row>
            <a-row :gutter="[15, 0]">
              <a-col :span="8" class="ad-left-column">
                <a-checkbox v-model:checked="item.multiple_adset"></a-checkbox>
              </a-col>
              <a-col :span="16">
                <span class="ad-right-column">{{ t('pages.ad.accounts.launch.multi.adset') }}</span>
              </a-col>
            </a-row>
          </a-card>

          <div class="six-selection-boxes">
            <a-row :gutter="[15, 0]">
              <a-col v-for="(creative, idx) in item.creatives" :key="idx" :span="8">
                <a-card style="padding: 20px; margin-bottom: 20px">
                  <div style="position: absolute; top: 0; right: 0">
                    <close-outlined
                      :style="{ fontSize: '24px', color: 'red' }"
                      @click="() => removeSelectedCreative(index, idx)"
                    />
                  </div>
                  <a-form-item
                    :label="t('pages.ad.accounts.launch.copywriting')"
                    :rules="[{ required: true }]"
                  >
                    <a-select
                      v-model:value="creative.copywriting_id"
                      size="middle"
                      :placeholder="t('pages.plsSelect')"
                    >
                      <a-select-option
                        v-for="option in copywritingData"
                        :key="option.id"
                        :value="option.id"
                        :label="option.headline"
                        :index="idx"
                      >
                        {{ option.headline }}
                      </a-select-option>
                    </a-select>
                  </a-form-item>
                  <a-form-item
                    :label="t('pages.ad.accounts.launch.material')"
                    :rules="[{ required: true }]"
                  >
                    <a-select
                      v-model:value="creative.material_id"
                      size="middle"
                      :placeholder="t('pages.plsSelect')"
                    >
                      <a-select-option
                        v-for="option in materialsData"
                        :key="option.id"
                        :value="option.id"
                        :label="option.name"
                        :index="idx"
                      >
                        {{ option.name }}
                      </a-select-option>
                    </a-select>
                  </a-form-item>
                  <a-form-item :label="t('pages.links.link')" :rules="[{ required: true }]">
                    <a-select
                      v-model:value="creative.link_id"
                      size="middle"
                      :placeholder="t('pages.plsSelect')"
                    >
                      <a-select-option
                        v-for="option in linksData"
                        :key="option.id"
                        :value="option.id"
                        :label="option.link"
                        :index="idx"
                      >
                        {{ option.link }}
                      </a-select-option>
                    </a-select>
                  </a-form-item>
                </a-card>
              </a-col>

              <div class="add-button-container">
                <plus-outlined
                  :style="{ fontSize: '29px', color: '#1677ff' }"
                  @click="() => addOneCreative(index)"
                />
              </div>
            </a-row>
          </div>
          <close-outlined
            :style="{ fontSize: '24px', color: 'red' }"
            @click="() => removeSelected(index)"
          />
        </div>
      </div>

      <div v-if="current == 3">
        <simple-submitted-success @createNewCampaign="resetCreateCampaign" />
      </div>
      <div class="steps-action">
        <a-button
          v-if="current < 3"
          :disabled="(current === 1 && selectedAdAccountsForm.length === 0) || submitting"
          type="primary"
          @click="next"
        >
          {{ t('pages.next') }}
        </a-button>
        <a-button v-if="current === 1 || current === 2" style="margin-left: 8px" @click="prev">
          {{ t('pages.previous') }}
        </a-button>
      </div>
    </div>

    <a-modal
      v-if="selectAdAccountOpen"
      v-model:open="selectAdAccountOpen"
      :title="t('pages.ad.accounts.launch.select.ad.account')"
      @ok="handleSelect"
      :width="800"
    >
      <a-alert
        :message="`${t('pages.selected')} ${selectedRowKeys.length}`"
        v-if="selectedRowKeys.length > 0"
        type="info"
        show-icon
      ></a-alert>
      <a-table
        :columns="columns"
        :pagination="false"
        :data-source="dataSource"
        :scroll="{ y: 500, x: 600 }"
        :loading="loading"
        :row-key="record => record.id"
        :row-selection="{ selectedRowKeys: selectedRowKeys, onChange: onSelectChange }"
      >
        >
      </a-table>
    </a-modal>
  </div>
</template>

<script lang="ts">
import { defineComponent, ref, computed, reactive, toRefs, watch, onMounted } from 'vue';
import { message } from 'ant-design-vue';
import { useI18n } from 'vue-i18n';
import { queryFB_AD_AccountsApi, queryFB_AD_AccountOneApi } from '@/api/fb_ad_accounts';
import { materialsList } from '@/api/materials';
import { queryCopywritingsApi } from '@/api/copywritings';
import { queryLinksApi } from '@/api/links';
import { getFbAccountOne } from '@/api/fb_accounts';
import { quickLaunchAds } from '@/api/fb-ads';
import {
  AlignRightOutlined,
  SyncOutlined,
  PlusOutlined,
  CloseOutlined,
} from '@ant-design/icons-vue';
import SimpleSubmittedSuccess from '@/views/result/simple-submitted-success.vue';
import dayjs from 'dayjs';
// import { useRouter } from 'vue-router';
export default defineComponent({
  components: {
    SimpleSubmittedSuccess,
    AlignRightOutlined,
    SyncOutlined,
    PlusOutlined,
    CloseOutlined,
  },
  setup() {
    const { t } = useI18n();
    const submitting = ref(false);
    const current = ref<number>(0);
    const next = () => {
      if (current.value === 0) {
        formRef.value.validateFields().then(() => {
          current.value++;
        });
      } else if (current.value === 1) {
        let canGoNext = true;
        for (const item of selectedAdAccountsForm.value) {
          if (
            !item.pixel_id ||
            !item.budget ||
            !item.page_id ||
            !item.fb_account_id ||
            !item.fb_ad_account_id
          ) {
            canGoNext = false;
            break;
          }
        }
        if (canGoNext) current.value++;
        else message.error(t('pages.pls.select.all.required'));
      } else if (current.value === 2) {
        let canGoNext = true;
        for (const item of selectedAdAccountsForm.value) {
          for (const creative of item.creatives) {
            if (!creative.material_id || !creative.copywriting_id || !creative.link_id) {
              canGoNext = false;
              break;
            }
          }
        }
        if (canGoNext && !submitting.value) {
          submitting.value = true;
          const formToSubmit = selectedAdAccountsForm.value.map(item => {
            let filteredObject = (({
              fb_account_id,
              fb_ad_account_id,
              pixel_id,
              page_id,
              multiple_adset,
              budget,
              creatives,
            }) => ({
              fb_account_id,
              fb_ad_account_id,
              pixel_id,
              page_id,
              multiple_adset,
              budget,
              creatives,
            }))(item);
            filteredObject = { ...filteredObject, ...form.value };
            return filteredObject;
          });

          if (formToSubmit.multiple_adset) formToSubmit.multiple_adset = 1;
          else formToSubmit.multiple_adset = 0;
          formToSubmit.map(item => {
            item.creatives.map(creative => {
              creative.page_id = item.page_id;
              creative.ad_name_tpl = form.value.campaign_name_tpl;
            });
          });
          quickLaunchAds(formToSubmit)
            .then(() => {
              current.value++;
            })
            .finally(() => {
              submitting.value = false;
            });
        } else message.error(t('pages.pls.select.all.required'));
      }
    };
    const prev = () => {
      current.value--;
    };
    const selectAdAccountOpen = ref<boolean>(false);
    const openSelectAdAccountModal = () => {
      fetchAdAccounts();
      selectAdAccountOpen.value = true;
    };

    const columns = computed<any[]>(() => {
      return [
        {
          title: t('pages.id'),
          dataIndex: 'source_id',
          resizable: true,
          fixed: 'left',
          width: 160,
        },
        {
          title: t('pages.name'),
          dataIndex: 'name',
          resizable: true,
          fixed: 'left',
          width: 220,
        },
        {
          title: t('pages.status'),
          dataIndex: 'account_status',
          resizable: true,
          width: 100,
        },
        {
          title: t('pages.ad.accounts.adtrust.dsl'),
          dataIndex: 'adtrust_dsl',
          resizable: true,
          width: 200,
        },
      ];
    });
    const selectedAdAccountsForm = ref<any>([]);
    const state = reactive<{
      selectedRowKeys: string[];
      selectedRows: any[];
    }>({
      selectedRowKeys: [], // Check here to configure the default column
      selectedRows: [],
    });
    const handleSelect = () => {
      selectedAdAccountsForm.value = state.selectedRows;
      const secondForm = {
        pixel_id: '',
        budget: '',
        page_id: '',
        fb_account_id: '',
        fb_ad_account_id: '',
        pages: [],
        pagesLoading: true,
        loading: false,
        multiple_adset: false,
      };
      for (let i = 0; i < selectedAdAccountsForm.value.length; i++) {
        for (const key in secondForm) {
          if (Object.prototype.hasOwnProperty.call(secondForm, key)) {
            selectedAdAccountsForm.value[i][key] = secondForm[key];
          }
        }
        selectedAdAccountsForm.value[i].fb_ad_account_id = selectedAdAccountsForm.value[i].id;
        const creatives = [
          {
            page_id: '',
            ad_name_tpl: '',
            material_id: '',
            copywriting_id: '',
            link_id: '',
          },
        ];
        selectedAdAccountsForm.value[i].creatives = creatives;
      }
      for (let i = 0; i < selectedAdAccountsForm.value.length; i++) {
        // By default select first one of Pages
        if (selectedAdAccountsForm.value[i].fb_accounts.length >= 1) {
          selectedAdAccountsForm.value[i].fb_account_id =
            selectedAdAccountsForm.value[i].fb_accounts[0].id;
          const option = { index: i };
          queryFbAccountOne(selectedAdAccountsForm.value[i].fb_accounts[0].id, option);
        }
      }
      selectAdAccountOpen.value = false;
      state.selectedRows = [];
    };
    const removeSelected = index => {
      selectedAdAccountsForm.value.splice(index, 1);
    };
    const removeSelectedCreative = (index, idx) => {
      selectedAdAccountsForm.value[index].creatives.splice(idx, 1);
    };
    const onSelectChange = (_selectedRowKeys: string[], _selectedRows: any[]) => {
      state.selectedRowKeys = _selectedRowKeys;
      state.selectedRows = _selectedRows;
    };
    interface Query {
      name?: string;
      pageNo: number;
      pageSize: number;
    }
    const loading = ref(false);
    const dataSource = ref<any>([]);
    const fetchAdAccounts = () => {
      loading.value = true;
      const param = {
        pageNo: 1,
        pageSize: 9999,
      } as Query;

      queryFB_AD_AccountsApi(param)
        .then((res: any) => {
          const onlyActiveData = res.data.filter(item => item.account_status === 'ACTIVE');
          dataSource.value = onlyActiveData;
          // state.selectedRowKeys = [];
        })
        .finally(() => {
          loading.value = false;
        });
    };

    const form = ref({
      campaign_name_tpl: '',
      campaign_objective: 'Sales',
      groupName: '1',
      genders: 0,
      geo: ['US'],
      age_range: [21, 65] as [number, number],
      adset_name_tpl: '',
      budget_period: 'daily',
      budget_type: 'CBO',
      conversion_event: 'Purchase',
    });
    const updateCampaignNameTemplate = () => {
      const date = dayjs().format('YYYY-MM-DD');
      const geo = form.value.geo.join('-');
      const budget_type = form.value.budget_type;
      const random = Math.floor(Math.random() * 1000);
      form.value.campaign_name_tpl = `${date}-${geo}-${budget_type}-SingleAdset-${random}`;
      form.value.adset_name_tpl = `${date}-${geo}`;
    };

    watch([() => form.value.geo, () => form.value.budget_type], () => {
      updateCampaignNameTemplate();
    });

    updateCampaignNameTemplate();

    const formRef = ref<any>();
    const labelCol = { style: { width: '250px' } };
    const wrapperCol = { span: 14 };
    const copywritingData = ref<any>([]);
    const materialsData = ref<any>([]);
    const linksData = ref<any>([]);

    const parseNumber = value => {
      const floatValue = parseFloat(value);
      if (!isNaN(floatValue) && floatValue >= 0) {
        return floatValue.toFixed(2);
      }
      return null;
    };
    const queryFbAccountOne = function (value, option) {
      getFbAccountOne({ id: value }).then(res => {
        selectedAdAccountsForm.value[option.index].pages = res.data.pages;

        for (let i = 0; i < selectedAdAccountsForm.value[option.index].pages.length; i++) {
          if (!selectedAdAccountsForm.value[option.index].pages[i].promotion_eligible) {
            continue;
          } else {
            selectedAdAccountsForm.value[option.index].page_id =
              selectedAdAccountsForm.value[option.index].pages[i].id;
            selectedAdAccountsForm.value[option.index].pagesLoading = false;
            break;
          }
        }
      });
    };
    const handleFbAccuntsAlignClick = index => {
      const id = selectedAdAccountsForm.value[index].fb_account_id;
      for (let i = 0; i < selectedAdAccountsForm.value.length; i++) {
        if (i !== index) {
          const hasValueOne = selectedAdAccountsForm.value[i].fb_accounts.some(
            obj => obj.id === id,
          );
          if (hasValueOne) {
            selectedAdAccountsForm.value[i].fb_account_id = id;
          }
        }
      }
    };
    const handleBudgetAlignClick = index => {
      const id = selectedAdAccountsForm.value[index].fb_account_id;
      const budget = selectedAdAccountsForm.value[index].budget;
      for (let i = 0; i < selectedAdAccountsForm.value.length; i++) {
        if (i !== index) {
          const hasValueOne = selectedAdAccountsForm.value[i].fb_accounts.some(
            obj => obj.id === id,
          );
          if (hasValueOne) {
            selectedAdAccountsForm.value[i].budget = budget;
          }
        }
      }
    };
    const handlePageAlignClick = index => {
      const id = selectedAdAccountsForm.value[index].fb_account_id;
      const pageId = selectedAdAccountsForm.value[index].pageId;
      for (let i = 0; i < selectedAdAccountsForm.value.length; i++) {
        if (i !== index) {
          const hasValueOne = selectedAdAccountsForm.value[i].fb_accounts.some(
            obj => obj.id === id,
          );
          if (hasValueOne) {
            selectedAdAccountsForm.value[i].pageId = pageId;
          }
        }
      }
    };
    const handlePixelAlignClick = index => {
      const id = selectedAdAccountsForm.value[index].fb_account_id;
      const pixelId = selectedAdAccountsForm.value[index].pixel_id;
      for (let i = 0; i < selectedAdAccountsForm.value.length; i++) {
        if (i !== index) {
          const hasValueOne = selectedAdAccountsForm.value[i].fb_accounts.some(
            obj => obj.id === id,
          );
          if (hasValueOne) {
            const hasPixelValueOne = selectedAdAccountsForm.value[i].pixels.some(
              obj => obj.id === pixelId,
            );
            if (hasPixelValueOne) selectedAdAccountsForm.value[i].pixel_id = pixelId;
          }
        }
      }
    };
    const reloadAdAccount = index => {
      console.log(index);
      const adAccountId = selectedAdAccountsForm.value[index].id;
      selectedAdAccountsForm.value[index].loading = true;
      queryFB_AD_AccountOneApi({ id: adAccountId }).then(res => {
        selectedAdAccountsForm.value[index] = res.data;
        selectedAdAccountsForm.value[index].loading = false;
      });
    };
    const addOneCreative = index => {
      selectedAdAccountsForm.value[index].creatives.push({
        page_id: '',
        ad_name_tpl: '',
        material_id: '',
        copywriting_id: '',
        link_id: '',
      });
    };
    const resetCreateCampaign = () => {
      form.value = {
        campaign_name_tpl: '',
        campaign_objective: 'Sales',
        groupName: '1',
        genders: 0,
        geo: ['US'],
        age_range: [21, 65] as [number, number],
        adset_name_tpl: '',
        budget_period: 'daily',
        budget_type: 'CBO',
        conversion_event: 'Purchase',
      };
      selectedAdAccountsForm.value = [];
      current.value = 0;
    };
    onMounted(() => {
      const params = {
        pageNo: 1,
        pageSize: 9999,
      };
      materialsList(params).then(res => {
        materialsData.value = res.data;
      });
      queryCopywritingsApi(params).then(res => {
        copywritingData.value = res.data;
      });
      queryLinksApi(params).then(res => {
        linksData.value = res.data;
      });
    });
    return {
      t,
      next,
      prev,
      current,
      openSelectAdAccountModal,
      selectAdAccountOpen,
      message,
      form,
      formRef,
      labelCol,
      wrapperCol,
      handleSelect,
      columns,
      loading,
      dataSource,
      onSelectChange,
      ...toRefs(state),
      removeSelected,
      selectedAdAccountsForm,
      materialsData,
      linksData,
      copywritingData,
      parseNumber,
      queryFbAccountOne,
      handleFbAccuntsAlignClick,
      handleBudgetAlignClick,
      handlePageAlignClick,
      handlePixelAlignClick,
      reloadAdAccount,
      addOneCreative,
      submitting,
      resetCreateCampaign,
      removeSelectedCreative,
    };
  },
});
</script>
<style scoped>
.ad_form {
  display: flex;
  justify-content: center;
  align-items: center;
  flex-direction: column;
  max-width: 90%;
  margin: 0 auto;
}
.ad_form_step {
  width: 80%;
}
.steps-content {
  margin: 16px auto;
  width: 100%;
  max-height: 700px;
  padding-top: 20px;
  overflow-y: auto;
}
.add-button-container {
  display: flex;
  align-items: center;
}
.steps-action {
  display: flex;
  justify-content: center;
  margin-top: 24px;
  margin-bottom: 24px !important;
}

.container {
  display: flex;
  flex-direction: column;
  align-items: center;
  width: 100%;
  gap: 8px;
}

.ad-container {
  display: flex;
  align-items: center;
  justify-content: space-between;
  margin-bottom: 10px;
  flex-wrap: nowrap;
  width: 100%;
  margin-top: 25px;
  padding: 20px;
  border: 1px solid #ccc;
  background-color: #f2f2f2;
}

.six-selection-boxes {
  width: 80%;
  padding: 20px;
}

.ad-info {
  width: 30%;
  background-color: #f2f2f2;
}

.ad-left-column {
  text-align: right;
}
.ad-right-column {
  text-align: left;
}

.remove-btn {
  margin-left: 10px;
  color: red;
}
</style>
