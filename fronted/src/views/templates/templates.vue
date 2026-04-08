<template>
  <div class="campaign-goal">
    <!-- Templates Table Page -->
    <a-card>
      <a-row :gutter="[8, 0]">
        <a-col>
          <create-template ref="dialogRef" @saved="onSaved"></create-template>
        </a-col>
        <a-col :flex="1">
          <applied-filters :filters="formData"></applied-filters>
        </a-col>
        <a-col>
          <dynamic-form :form-items="searchFormItems" @change:form-data="onSearch"></dynamic-form>
        </a-col>
      </a-row>
    </a-card>
    <a-table
      :columns="columns"
      :loading="loading"
      :data-source="dataSource"
      :pagination="pagination"
      :scroll="scroll"
      @change="handleTableChange"
      :row-key="record => record.key"
    >
      <template #bodyCell="{ column, record }">
        <template v-if="column.dataIndex === 'user'">
          <span>{{ record?.user?.name }}</span>
        </template>
        <a-row v-if="column.key === 'action'" :gutter="[8, 0]">
          <a-col v-if="canCopy">
            <a @click="cloneTemplate(record)">{{ t('Clone') }}</a>
          </a-col>
          <a-col v-if="canEdit">
            <a @click="updateTemplate(record)" v-if="record.is_owner">{{ t('Edit') }}</a>
          </a-col>
          <a-col v-if="canShare">
            <a @click="handleShare(record)" v-if="record.is_owner">{{ t('Share') }}</a>
          </a-col>
          <a-col v-if="canDelete">
            <a @click="deleteTemplate(record)" v-if="record.is_owner">{{ t('Delete') }}</a>
          </a-col>
        </a-row>
      </template>
    </a-table>

    <share-resource
      :model="shareModalRef.model"
      :open="shareModalRef.open"
      :share-api="shareApi"
      :unshare-api="unshareApi"
      @cancel="
        () => {
          shareModalRef.open = false;
        }
      "
      @ok="
        () => {
          shareModalRef.open = false;
          getTemplates();
        }
      "
    ></share-resource>
  </div>
</template>

<script lang="ts">
import { defineComponent, onMounted, reactive, ref } from 'vue';
import { useI18n } from 'vue-i18n';
import CreateTemplate from './create-template.vue';
import {
  createFbAdTemplate,
  deleteFbAdTemplate,
  getFbAdTemplates,
  shareFbAdTemplateApi,
  unShareFbAdTemplateApi,
} from '@/api/fb_ad_template';
import { message, Modal } from 'ant-design-vue';
import DynamicForm from '@/components/dynamic-form/dynamic-form.vue';
import AppliedFilters from '@/components/applied-filters/applied-filters.vue';
import { isNil } from 'lodash';
import { useTableHeight } from '@/utils/hooks/useTableHeight';

import ShareResource from '../utils/share-resource.vue';
import type { FbAdTemplate, ShareModel } from '@/utils/fb-interfaces';
import { Action } from '@/api/user/login';
import { useAuth } from '@/utils/authority';


export default defineComponent({
  name: 'Templates',
  components: {
    CreateTemplate,
    DynamicForm,
    AppliedFilters,
    ShareResource,
  },
  setup() {
    const { t } = useI18n();

    const { scroll } = useTableHeight(376);

    const shareApi = shareFbAdTemplateApi;
    const unshareApi = unShareFbAdTemplateApi;
    const shareModalRef = reactive({
      open: false,
      model: {} as ShareModel,
    });

    const loading = ref(false);
    const formData = ref({});
    const dialogRef = ref(null);
    const searchFormItems = ref([
      { label: t('Name'), field: 'name' },
      { label: t('Notes'), field: 'notes' },
    ]);
    const columns = [
      { title: 'Name', dataIndex: 'name', sorter: true },
      { title: 'Notes', dataIndex: 'notes', sorter: true },
      { title: 'User', dataIndex: 'user' },
      { title: 'Action', dataIndex: 'action', key: 'action' },
    ];
    const dataSource = ref([]);
    const pagination = ref({
      current: 1,
      pageSize: 100,
      total: 0,
    });
    const canCopy = useAuth([Action.COPY]);
    const canEdit = useAuth([Action.UPDATE]);
    const canDelete = useAuth([Action.DELETE]);
    const canShare = useAuth([Action.SHARE]);

    onMounted(() => getTemplates());

    const onSearch = data => {
      formData.value = data;
      getTemplates();
    };

    const onSaved = () => {
      getTemplates();
    };

    const updateTemplate = rec => {
      dialogRef.value.onEdit(rec);
    };

    const handleTableChange = (page, filters, sorter) => {
      console.log('Table Change:', page, filters, sorter);
      pagination.value = page;
    };

    const cloneTemplate = record => {
      Modal.confirm({
        title: t('Confirm'),
        content: t(`Clone template ${record.name}`),
        onOk: () => {
          loading.value = true;
          const clone = {
            ...record,
            name: `${record.name}-clone`,
            genders: isNil(record.genders) ? 0 : record.genders,
          };
          createFbAdTemplate(clone).then(() => getTemplates());
        },
      });
    };

    const deleteTemplate = record => {
      Modal.confirm({
        title: t('Delete'),
        content: `${t('Delete Template')}: ${record.name}`,
        onOk() {
          loading.value = true;
          deleteFbAdTemplate(record.id)
            .then(() => {
              getTemplates();
              message.success(t('Template deleted'));
            })
            .catch(() => message.error(t('Operation failed')));
        },
      });
    };

    const getTemplates = () => {
      loading.value = true;
      getFbAdTemplates({
        pageNo: pagination.value.current,
        pageSize: pagination.value.pageSize,
        ...formData.value,
      })
        .then(({ data, pageSize, pageNo, totalCount }: any) => {
          dataSource.value = data;
          pagination.value.current = pageNo;
          pagination.value.pageSize = pageSize;
          pagination.value.total = totalCount;
        })
        .catch(() => message.error('Operation Failed'))
        .finally(() => (loading.value = false));
    };

    const handleShare = (record: FbAdTemplate) => {
      const ids = [];
      ids.push(record.id);
      shareModalRef.model = {
        action: 'share',
        resourceList: ids,
      };
      shareModalRef.open = true;
    };

    return {
      scroll,
      loading,
      columns,
      dataSource,
      pagination,
      formData,
      dialogRef,
      searchFormItems,
      getTemplates,
      deleteTemplate,
      cloneTemplate,
      handleTableChange,
      updateTemplate,
      onSearch,
      onSaved,
      t,
      shareModalRef,
      shareApi,
      unshareApi,
      handleShare,

      canCopy,
      canEdit,
      canDelete,
      canShare,
    };
  },
  methods: {},
});
</script>

<style scoped>
.a-card {
  margin-bottom: 20px;
}

.table-actions {
  display: flex;
  align-items: center;
  margin-bottom: 20px;
}
</style>
