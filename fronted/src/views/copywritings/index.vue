<template>
  <page-container :title="t('pages.copywritings.title')">
    <a-card mb-4>
      <a-row :gutter="[15, 0]" v-if="expand">
        <a-col :span="6">
          <a-form-item :label="t('pages.copywritings.primary.text')">
            <a-input v-model:value="pagination.primary_text" />
          </a-form-item>
        </a-col>
        <a-col :span="6">
          <a-form-item :label="t('pages.copywritings.headline')">
            <a-input v-model:value="pagination.headline" />
          </a-form-item>
        </a-col>
        <a-col :span="6">
          <a-form-item :label="t('pages.copywritings.description')">
            <a-input v-model:value="pagination.description" />
          </a-form-item>
        </a-col>
      </a-row>

      <a-row :gutter="[15, 0]" v-if="expand">
        <a-col :span="6">
          <a-form-item :label="t('pages.tag')">
            <a-select
              v-model:value="pagination.tags"
              :options="tagOptions"
              mode="multiple"
              size="middle"
              :placeholder="t('pages.plsSelect')"
            ></a-select>
          </a-form-item>
        </a-col>
        <a-col :span="10">
          <a-form-item :label="t('pages.createdAt')">
            <a-range-picker v-model:value="pagination.created_at" show-time />
          </a-form-item>
        </a-col>
      </a-row>
      <a-row :span="24" style="text-align: left; display: flex; justify-content: space-between">
        <a-col :span="12">
          <a-button type="primary" @click="showModal('')">
            <template #icon>
              <plus-outlined />
            </template>
            {{ t('pages.add') }}
          </a-button>
        </a-col>
        <a-space>
          <a-button :loading="loading" type="primary" @click="fetchLinks">
            {{ t('pages.query') }}
          </a-button>
          <a-button @click="resetPagination">
            {{ t('pages.reset') }}
          </a-button>
          <a-button type="link" @click="expand = !expand">
            {{ expand ? t('pages.collapse') : t('pages.expand') }}
            <up-outlined v-if="expand" />
            <down-outlined v-else />
          </a-button>
        </a-space>
      </a-row>
    </a-card>
    <a-table
      :columns="columns"
      :data-source="dataSource"
      @change="handleTableChange"
      :scroll="{ y: tableHeight, x: 2000 }"
      :pagination="pagination"
      :loading="loading"
      :row-key="record => record.id"
    >
      <template #bodyCell="{ column, text, record }">
        <template v-if="column['dataIndex'] === 'active'">
          <span>{{ text === false ? t('pages.deActive') : t('pages.active') }}</span>
        </template>
        <template v-if="['primary_text'].includes(`${column['dataIndex']}`)">
          <span>{{ truncateText(text) }}</span>
        </template>
        <template v-if="['tags'].includes(`${column['dataIndex']}`)">
          <a-tag v-for="m in text" :key="m.id">{{ m.name }}</a-tag>
        </template>
        <template v-if="['created_at', 'updated_at'].includes(`${column['dataIndex']}`)">
          <span>{{ dayjs(text).format('YYYY-MM-DD HH:mm:ss') }}</span>
        </template>
        <template v-if="column['dataIndex'] === 'operation'">
          <a class="href-btn" @click="showModal(record)">{{ t('pages.edit') }}</a>
          <a-popconfirm
            :title="t('pages.doubleConfirmDel')"
            :ok-text="t('pages.confirm')"
            :cancel-text="t('pages.cancel')"
            @confirm="deleteOne(record)"
          >
            <a class="href-btn" href="#">{{ t('pages.del') }}</a>
          </a-popconfirm>
        </template>
      </template>
    </a-table>

    <a-modal v-if="open" v-model:open="open" :title="dialogTitle" @ok="handleOk" :width="800">
      <a-form
        ref="formRef"
        :label-col="{ span: 4 }"
        name="dynamic_form_nest_item"
        :model="dynamicValidateForm"
      >
        <a-form-item name="headline" :label="t('pages.copywritings.headline')">
          <a-input v-model:value="dynamicValidateForm.headline" />
        </a-form-item>
        <a-form-item name="primary_text" :label="t('pages.copywritings.primary.text')">
          <a-textarea :rows="4" v-model:value="dynamicValidateForm.primary_text" />
        </a-form-item>
        <a-form-item name="description" :label="t('pages.copywritings.description')">
          <a-input v-model:value="dynamicValidateForm.description" />
        </a-form-item>
        <a-form-item name="notes" :label="t('pages.notes')">
          <a-input v-model:value="dynamicValidateForm.notes" />
        </a-form-item>
        <a-divider orientation="left">多语言翻译</a-divider>
        <a-space wrap style="margin-bottom: 8px">
          <a-select
            v-model:value="newLocaleToAdd"
            allow-clear
            show-search
            :options="localeOptionsForAdd"
            placeholder="选择 Meta locale 后添加"
            style="width: 220px"
            :filter-option="filterLocaleOption"
          />
          <a-button type="dashed" @click="addTranslationLocale">添加语言</a-button>
        </a-space>
        <div v-for="loc in translationLocaleKeys" :key="loc" style="margin-bottom: 12px; padding: 10px; border: 1px solid #f0f0f0; border-radius: 6px">
          <div style="display:flex;justify-content:space-between;align-items:center;margin-bottom:8px">
            <span style="font-weight:600">{{ loc }}</span>
            <a-button type="link" danger size="small" @click="removeTranslationLocale(loc)">移除</a-button>
          </div>
          <a-form-item label="正文">
            <a-textarea v-model:value="itemTranslationsForm[loc].primary_text" :rows="2" />
          </a-form-item>
          <a-form-item label="标题">
            <a-input v-model:value="itemTranslationsForm[loc].headline" />
          </a-form-item>
          <a-form-item label="描述">
            <a-textarea v-model:value="itemTranslationsForm[loc].description" :rows="2" />
          </a-form-item>
        </div>
        <a-form-item name="tags" :label="'Tags'">
          <template v-for="tag in tagsData" :key="tag">
            <a-tag
              :color="dynamicValidateForm.tags.some(i => i.name === tag.name) ? 'green' : ''"
              @close="handleClose(tag)"
              @click="changeTag(tag)"
              closable
            >
              {{ tag.name }}
            </a-tag>
          </template>
          <a-input
            v-if="inputVisible"
            ref="inputRef"
            v-model:value="inputValue"
            type="text"
            size="small"
            :style="{ width: '78px' }"
            @blur="handleInputConfirm"
            @keyup.enter="handleInputConfirm"
          />
          <a-tag v-else style="background: #fff; border-style: dashed" @click="showInput">
            <plus-outlined />
            New Tag
          </a-tag>
        </a-form-item>
      </a-form>
    </a-modal>
  </page-container>
</template>
<script lang="ts">
import dayjs from 'dayjs';
import { cloneDeep } from 'lodash';
import { computed, defineComponent, ref, onMounted, watch, nextTick } from 'vue';
import { message, Modal } from 'ant-design-vue';
import { CopyOutlined, PlusOutlined, UpOutlined, DownOutlined } from '@ant-design/icons-vue';
import { queryTagsApi, addTagsOneApi, deleteTagsOneApi } from '@/api/networks';
import {
  queryCopywritingsApi,
  addCopywritingsOneApi,
  deleteCopywritingsApi,
} from '@/api/copywritings';
import { META_COPY_LOCALE_OPTIONS, type MetaCopyItemTranslations } from '@/api/meta-copy-library';
import { useI18n } from 'vue-i18n';
import useClipboard from 'vue-clipboard3';
import { useRoute } from 'vue-router';

export default defineComponent({
  components: {
    CopyOutlined,
    PlusOutlined,
    UpOutlined,
    DownOutlined,
  },
  setup() {
    const { t } = useI18n();
    const route = useRoute();
    const sortedInfo = ref();
    const expand = ref(true);
    const screenHeight = ref<number>(window.innerHeight);
    const tableHeight = screenHeight.value < 1010 ? ref('48vh') : ref('54vh');

    watch(expand, newValue => {
      if (screenHeight.value < 1010) {
        tableHeight.value = newValue ? '48vh' : '65vh';
      } else {
        tableHeight.value = newValue ? '54vh' : '65vh';
      }
    });
    const truncateText = text => {
      const maxLength = 16;
      if (text && text.length > maxLength) {
        return text.slice(0, maxLength) + '...';
      } else {
        return text;
      }
    };
    const columns = computed<any[]>(() => {
      const sorted = sortedInfo.value || {};
      return [
        {
          title: t('pages.copywritings.primary.text'),
          dataIndex: 'primary_text',
        },
        {
          title: t('pages.copywritings.headline'),
          dataIndex: 'headline',
        },
        {
          title: t('pages.copywritings.description'),
          dataIndex: 'description',
        },
        {
          title: t('pages.notes'),
          dataIndex: 'notes',
        },
        {
          title: t('pages.createdAt'),
          dataIndex: 'created_at',
          sorter: true,
          sortOrder: sorted.field === 'created_at' && sorted.order,
        },
        {
          title: t('pages.updatedAt'),
          dataIndex: 'updated_at',
          sorter: true,
          sortOrder: sorted.field === 'updated_at' && sorted.order,
        },
        {
          title: t('pages.tag'),
          dataIndex: 'tags',
        },
        {
          title: t('pages.operation'),
          width: 180,
          dataIndex: 'operation',
          fixed: 'right',
        },
      ];
    });
    const handleTableChange: any['onChange'] = (pag, _filters, sorter) => {
      if (pag) {
        if (pag.current != null) pagination.value.current = pag.current;
        if (pag.pageSize != null) pagination.value.pageSize = pag.pageSize;
      }
      sortedInfo.value = sorter;
      fetchLinks();
    };
    const loading = ref(true);
    const submitting = ref(false);
    const dataSource = ref<any>([]);
    const pagination = ref<any>({
      name: '',
      showQuickJumper: true,
      showSizeChanger: true,
      current: 1,
      total: 0,
      showTotal: total => `Total ${total} items`,
      pageSize: 20,
    });
    const resetPagination = () => {
      pagination.value.primary_text = '';
      pagination.value.description = '';
      pagination.value.headline = '';
      pagination.value.tags = [];
      pagination.value.created_at = [];
      sortedInfo.value = null;
      fetchLinks();
    };
    interface Query {
      primary_text?: string;
      date_start?: string;
      date_end?: string;
      pageNo: number;
      pageSize: number;
      sortOrder?: string;
      sortField?: string;
      tags?: string;
      headline?: string;
      description?: string;
      with_translations?: number;
      country_codes?: string;
      resolve_locales?: string;
    }

    const routePreferredCountryCodes = computed(() => {
      const v = route.query?.country_codes;
      if (v == null) return '';
      return Array.isArray(v) ? v.join(',') : String(v);
    });
    const routePreferredLocales = computed(() => {
      const v = route.query?.resolve_locales;
      if (v == null) return '';
      return Array.isArray(v) ? v.join(',') : String(v);
    });

    const fetchLinks = () => {
      loading.value = true;
      let sortParam = '';
      if (sortedInfo?.value?.order === 'ascend') sortParam = 'asc';
      else if (sortedInfo?.value?.order === 'descend') sortParam = 'desc';
      const param = {
        link: pagination.value.link,
        pageNo: pagination.value.current,
        pageSize: pagination.value.pageSize,
        with_translations: 1,
      } as Query;
      if (sortedInfo?.value?.order) {
        param.sortOrder = sortParam;
        param.sortField = sortedInfo?.value?.field;
      }
      if (pagination.value.tags && pagination.value.tags.length > 0) {
        param.tags = pagination.value.tags.join(',');
      }
      if (pagination.value.created_at && pagination.value.created_at.length === 2) {
        param.date_start = dayjs(pagination.value.created_at[0]).format('YYYY-MM-DDTHH:mm:ss ZZ');
        param.date_end = dayjs(pagination.value.created_at[1]).format('YYYY-MM-DDTHH:mm:ss ZZ');
      }
      if (pagination.value.primary_text) {
        param.primary_text = pagination.value.primary_text;
      }
      if (pagination.value.headline) {
        param.headline = pagination.value.headline;
      }
      if (pagination.value.description) {
        param.description = pagination.value.description;
      }

      // 多语言：允许从 URL query 传入（不修改投放页面也能联调）
      // 例：#/creative/copywritings?country_codes=US,GB
      // 或：#/creative/copywritings?resolve_locales=es_MX,en_US
      if (routePreferredLocales.value) {
        param.resolve_locales = routePreferredLocales.value;
      } else if (routePreferredCountryCodes.value) {
        param.country_codes = routePreferredCountryCodes.value;
      }

      queryCopywritingsApi(param)
        .then((res: any) => {
          dataSource.value = res.data;
          pagination.value.total = res.totalCount;
        })
        .finally(() => {
          loading.value = false;
        });
    };

    const deleteOne = (data: any) => {
      loading.value = true;
      deleteCopywritingsApi(data.id).then(() => {
        message.success(t('pages.opSuccessfully'));
        fetchLinks();
      });
    };
    const { toClipboard } = useClipboard();
    const copyCell = async (text: any) => {
      try {
        await toClipboard(text);
        message.success(t('pages.linkCopiedw'));
      } catch (e) {
        console.error(e);
      }
    };
    interface TagOption {
      label: string;
      value: string;
    }
    const tagsData = ref<any>([]);
    const tagOptions = ref<TagOption[]>([]);
    watch(tagsData, newValue => {
      tagOptions.value = newValue.map(tag => ({
        label: tag.name,
        value: tag.name,
      }));
    });
    const inputVisible = ref<boolean>(false);
    const inputValue = ref<string>('');
    const inputRef = ref();
    const itemTranslationsForm = ref<MetaCopyItemTranslations>({});
    const newLocaleToAdd = ref<string | undefined>(undefined);
    const translationLocaleKeys = computed(() => Object.keys(itemTranslationsForm.value).sort());
    const localeOptionsForAdd = computed(() =>
      META_COPY_LOCALE_OPTIONS.filter(o => !translationLocaleKeys.value.includes(o.value)).map(o => ({
        value: o.value,
        label: `${o.label}（${o.value}）`,
      })),
    );
    const filterLocaleOption = (input: string, option: { label?: string; value?: string }) => {
      const q = (input || '').toLowerCase().trim();
      if (!q) return true;
      const label = String(option?.label ?? '').toLowerCase();
      const value = String(option?.value ?? '').toLowerCase();
      return label.includes(q) || value.includes(q);
    };
    const clearItemTranslationsForm = () => {
      itemTranslationsForm.value = {};
    };
    const addTranslationLocale = () => {
      const loc = (newLocaleToAdd.value || '').trim();
      if (!loc || itemTranslationsForm.value[loc]) return;
      itemTranslationsForm.value[loc] = { primary_text: '', headline: '', description: '' };
      newLocaleToAdd.value = undefined;
    };
    const removeTranslationLocale = (loc: string) => {
      if (itemTranslationsForm.value[loc]) delete itemTranslationsForm.value[loc];
    };
    const cleanTranslationsForSubmit = (): MetaCopyItemTranslations | null => {
      const out: MetaCopyItemTranslations = {};
      for (const loc of Object.keys(itemTranslationsForm.value)) {
        const b = itemTranslationsForm.value[loc];
        const pt = (b?.primary_text ?? '').trim();
        const hl = (b?.headline ?? '').trim();
        const dc = (b?.description ?? '').trim();
        if (pt || hl || dc) {
          out[loc] = {
            ...(pt ? { primary_text: pt } : {}),
            ...(hl ? { headline: hl } : {}),
            ...(dc ? { description: dc } : {}),
          };
        }
      }
      return Object.keys(out).length ? out : null;
    };
    const handleClose = (tag: any) => {
      const index = tagsData.value.findIndex(i => i.name === tag.name);
      tagsData.value.splice(index, 0, tag);
      Modal.confirm({
        title: t('pages.hint'),
        content: t('pages.doubleConfirmDel'),
        okText: t('pages.confirm'),
        cancelText: t('pages.cancel'),
        wrapClassName: 'confirm-dialog',
        onOk() {
          deleteTagsOneApi(tag.id)
            .then(res => {
              message.success(t('pages.opSuccessfully'));
              tagsData.value.splice(index, 1);
              console.error(res);
            })
            .catch(err => {
              console.error(err);
            });
        },
      });
    };
    const changeTag = (tag: any) => {
      const index = dynamicValidateForm.value.tags.findIndex(i => i.name === tag.name);
      if (index >= 0) {
        dynamicValidateForm.value.tags.splice(index, 1);
      } else {
        dynamicValidateForm.value.tags.push(tag);
      }
    };
    const showInput = () => {
      inputVisible.value = true;
      nextTick(() => {
        inputRef.value.focus();
      });
    };
    const handleInputConfirm = () => {
      const tags = tagsData.value;
      if (inputValue.value && !tags.includes(inputValue.value)) {
        addTagsOneApi({ name: inputValue.value }).then(res => {
          message.success(t('pages.opSuccessfully'));
          tags.push(res);
          inputVisible.value = false;
          tagsData.value = tags;
          inputValue.value = '';
        });
      }
    };

    const open = ref<boolean>(false);
    const dialogTitle = ref<string>('');
    const formRef = ref<any>();
    const resetForm = {
      id: 0,
      primary_text: '',
      headline: '',
      description: '',
      notes: '',
      tags: [],
    };
    const dynamicValidateForm = ref<any>(cloneDeep(resetForm));
    const showModal = (data: any) => {
      if (data) {
        dynamicValidateForm.value = cloneDeep(data);
        const tr = data.translations && typeof data.translations === 'object' ? data.translations : {};
        clearItemTranslationsForm();
        Object.keys(tr).forEach(loc => {
          const block = tr[loc];
          itemTranslationsForm.value[loc] = {
            primary_text: block?.primary_text ?? '',
            headline: block?.headline ?? '',
            description: block?.description ?? '',
          };
        });
      } else {
        dynamicValidateForm.value = cloneDeep(resetForm);
        clearItemTranslationsForm();
      }

      // 若 URL 里带了 preferred locales，则自动把这些 locale 加到“多语言翻译”块里，方便直接填写
      const rawLoc = (routePreferredLocales.value || '').trim();
      if (rawLoc) {
        const list = rawLoc.split(/[,\s]+/).map(s => s.trim()).filter(Boolean);
        list.forEach(loc => {
          if (!itemTranslationsForm.value[loc]) {
            itemTranslationsForm.value[loc] = { primary_text: '', headline: '', description: '' };
          }
        });
      }

      dialogTitle.value = data ? t('pages.edit') : t('pages.add');
      open.value = true;
    };
    const handleOk = () => {
      if (!submitting.value) {
        formRef.value.validateFields().then(() => {
          const params = {
            ...dynamicValidateForm.value,
            translations: cleanTranslationsForSubmit() ?? undefined,
          };
          params.tag_ids = params.tags.map(obj => obj.id);
          submitting.value = true;
          addCopywritingsOneApi(params)
            .then(() => {
              message.success(t('pages.opSuccessfully'));
              fetchLinks();
              open.value = false;
            })
            .catch(err => {
              message.error(err.message);
            })
            .finally(() => {
              submitting.value = false;
            });
        });
      }
    };
    watch(
      () => ({ ...pagination.value }),
      (cur, pre) => {
        if (cur.current !== pre.current || cur.pageSize !== pre.pageSize) {
          fetchLinks();
        }
      },
    );
    onMounted(() => {
      fetchLinks();
      queryTagsApi().then(res => {
        tagsData.value = res.data;
      });
    });
    return {
      dataSource,
      loading,
      fetchLinks,
      resetPagination,
      dayjs,
      pagination,
      deleteOne,
      columns,
      copyCell,
      t,
      open,
      showModal,
      handleOk,
      formRef,
      dialogTitle,
      dynamicValidateForm,
      tagsData,
      showInput,
      inputVisible,
      inputValue,
      handleClose,
      handleInputConfirm,
      inputRef,
      changeTag,
      handleTableChange,
      tagOptions,
      truncateText,
      expand,
      tableHeight,
      itemTranslationsForm,
      newLocaleToAdd,
      translationLocaleKeys,
      localeOptionsForAdd,
      filterLocaleOption,
      addTranslationLocale,
      removeTranslationLocale,
    };
  },
});
</script>
