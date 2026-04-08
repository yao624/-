<template>
  <page-container :title="t('pages.pages.title')">
    <a-card mb-4>
      <a-row :gutter="[12, 0]" align="middle">
        <a-col :flex="1"></a-col>
        <a-col>
          <page-dialog
            ref="pageModal"
            :tags-data="tagsData"
            @change:tags-changed="fetchPages"
          />
        </a-col>
        <a-col>
          <a-tooltip :title="t('pages.common.refresh')">
            <a-button shape="circle" :icon="h(ReloadOutlined)" @click="fetchPages"></a-button>
          </a-tooltip>
        </a-col>
        <a-col>
          <a-button @click="filterVisible = !filterVisible">
            {{ t('pages.common.filters') }}
          </a-button>
        </a-col>
      </a-row>
      <a-row v-if="filterVisible" :gutter="[12, 0]" class="filter-row">
        <a-col :span="24">
          <applied-filters :filters="appliedFilters"></applied-filters>
        </a-col>
        <a-col :span="24">
          <dynamic-form :form-items="formItems" @change:form-data="onSearch"></dynamic-form>
        </a-col>
      </a-row>
    </a-card>
    <div class="pages-table-wrapper">
      <a-table
        :columns="columns"
        :data-source="dataSource"
        @change="handleTableChange"
        :scroll="{ x: 1200, y: tableHeight }"
        :pagination="pagination"
        :loading="loading"
        :row-key="record => record.id"
      >
      <template #bodyCell="{ column, text, record }">
        <template v-if="column['dataIndex'] === 'active'">
          <span>{{ text === false ? t('pages.deActive') : t('pages.active') }}</span>
        </template>
        <template v-if="column['dataIndex'] === 'picture'">
          <img :src="text" alt="Avatar" width="40" height="40" class="page-avatar" @error="($event.target as HTMLImageElement).style.display = 'none'" />
        </template>
        <template v-if="column['dataIndex'] === 'bm'">
          <a class="href-btn" href="javascript:;">{{ record.verification_status || '-' }}</a>
        </template>
        <template v-if="['tags'].includes(`${column['dataIndex']}`)">
          <a-tag v-for="m in text" :key="m.id">{{ m.name }}</a-tag>
        </template>
        <template v-if="column['dataIndex'] === 'created_at'">
          <span>{{ dayjs(text).format('YYYY-MM-DD HH:mm:ss') }}</span>
        </template>
        <template v-if="column['dataIndex'] === 'operation'">
          <a class="href-btn action-link" @click="showModal(record)">{{ t('pages.edit') }}</a>
          <a class="href-btn action-link" @click="syncPageForms(record)">{{ t('Sync forms') }}</a>
          <a class="href-btn action-link" @click="refreshToken(record)">{{ t('Refresh token') }}</a>
        </template>
        <!-- <template v-if="column['dataIndex'] === 'promotion_eligible'">
          <a-badge
            :color="text === true ? 'green' : 'red'"
            :text="
              text === true ? t('pages.acc.page.not_restricted') : t('pages.acc.page.is_restricted')
            "
          />
        </template> -->
      </template>
      <template #emptyText>
        <a-empty :description="t('pages.pages.emptyHint')">
          <a-button type="primary" @click="pageModal?.showModal()">{{ t('pages.add') }}</a-button>
        </a-empty>
      </template>
      </a-table>
    </div>
    <users-table-modal
      :openModal="modalVisible"
      :userData="modalUserData"
      :closeUserModal="closeUserModal"
    />
  </page-container>
</template>
<script lang="ts">
import dayjs from 'dayjs';
import { computed, defineComponent, ref, onMounted, watch, h } from 'vue';
import { message } from 'ant-design-vue';
import { queryTagsApi } from '@/api/networks';
import { queryPagesApi, refreshPageToken, syncPageFormsApi } from '@/api/pages';
import UsersTableModal from '@/components/users/UsersTableModal.vue';
import PageDialog from '@/components/page-dialog/page-dialog.vue';
import DynamicForm from '@/components/dynamic-form/dynamic-form.vue';
import AppliedFilters from '@/components/applied-filters/applied-filters.vue';
import { useI18n } from 'vue-i18n';
import useClipboard from 'vue-clipboard3';
import { useTableHeight } from '@/utils/hooks/useTableHeight';
import type { FbPage } from '@/utils/fb-interfaces';
import { ReloadOutlined } from '@ant-design/icons-vue';
export default defineComponent({
  components: {
    UsersTableModal,
    DynamicForm,
    PageDialog,
    AppliedFilters,
  },
  setup() {
    const { t } = useI18n();
    const modalVisible = ref(false);
    const modalUserData = ref([]);
    const expand = ref(true);
    // const gap = 392;
    // const screenHeight = ref<number>(window.innerHeight);
    // const tableHeight = ref(screenHeight.value - gap);
    const { tableHeight } = useTableHeight();

    const formItems = ref([
      { label: 'pages.source.id', field: 'pageId' },
      { label: 'pages.name', field: 'name' },
      { label: 'pages.notes', field: 'notes' },
    ]);

    // watch(screenHeight, newValue => {
    //   tableHeight.value = newValue - gap;
    // });
    const showUserModal = usersData => {
      modalUserData.value = usersData;
      modalVisible.value = true;
    };
    const closeUserModal = () => {
      modalVisible.value = false;
      modalUserData.value = [];
    };

    const filterVisible = ref(false);
    const sortedInfo = ref();
    const columns = computed<any[]>(() => {
      const sorted = sortedInfo.value || {};
      return [
        { title: t('pages.source.id'), dataIndex: 'source_id', width: 140, ellipsis: true },
        { title: t('pages.name'), dataIndex: 'name', width: 120, ellipsis: true },
        { title: t('pages.avatar'), dataIndex: 'picture', width: 70 },
        {
          title: t('pages.pages.fan.count'),
          dataIndex: 'fan_count',
          width: 100,
          sorter: true,
          sortOrder: sorted.field === 'fan_count' && sorted.order,
        },
        { title: t('pages.notes'), dataIndex: 'notes', width: 120, ellipsis: true },
        { title: t('pages.bm'), dataIndex: 'bm', width: 80 },
        {
          title: t('pages.createdAt'),
          dataIndex: 'created_at',
          width: 160,
          sorter: true,
          sortOrder: sorted.field === 'created_at' && sorted.order,
        },
        { title: t('pages.tag'), dataIndex: 'tags', width: 120 },
        {
          title: t('pages.operation'),
          width: 200,
          dataIndex: 'operation',
        },
      ];
    });
    const handleTableChange: any['onChange'] = (pag, _filters, sorter) => {
      if (pag) {
        if (pag.current != null) pagination.value.current = pag.current;
        if (pag.pageSize != null) pagination.value.pageSize = pag.pageSize;
      }
      sortedInfo.value = sorter;
      fetchPages();
    };

    const appliedFilters = ref({});
    const onSearch = data => {
      Object.entries(data).forEach(([key, value]) => (pagination.value[key] = value));
      appliedFilters.value = data;
      fetchPages();
    };

    const loading = ref(true);
    const dataSource = ref<any>([]);
    const pagination = ref<any>({
      name: '',
      showQuickJumper: true,
      showSizeChanger: true,
      current: 1,
      total: 0,
      showTotal: (total: number) => `Total ${total} items`,
      pageSize: 20,
      pageSizeOptions: ['10', '20', '50', '100'],
      value: {},
    });
    const resetPagination = () => {
      pagination.value.pageId = '';
      pagination.value.notes = '';
      pagination.value.name = '';
      pagination.value.tags = [];
      pagination.value.created_at = [];
      sortedInfo.value = null;
      fetchPages();
    };
    interface Query {
      name?: string;
      source_id?: string;
      notes?: string;
      date_start?: string;
      date_end?: string;
      pageNo: number;
      pageSize: number;
      sortOrder?: string;
      sortField?: string;
      tags?: string;
    }
    const fetchPages = () => {
      loading.value = true;
      let sortParam = '';
      if (sortedInfo?.value?.order === 'ascend') sortParam = 'asc';
      else if (sortedInfo?.value?.order === 'descend') sortParam = 'desc';
      const param = {
        name: pagination.value.name,
        notes: pagination.value.notes,
        source_id: pagination.value.pageId,
        pageNo: pagination.value.current,
        pageSize: pagination.value.pageSize,
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

      queryPagesApi(param)
        .then((res: any) => {
          dataSource.value = res.data;
          pagination.value.total = res.totalCount;
        })
        .finally(() => {
          loading.value = false;
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

    const tagOptions = ref<TagOption[]>([]);
    const tagsData = ref<any[]>([]);
    // watch(tagsData, newValue => {
    //   tagOptions.value = newValue.map(tag => ({
    //     label: tag.name,
    //     value: tag.name,
    //   }));
    // });

    const pageModal = ref(null);
    const showModal = rec => pageModal.value?.showModal(rec);

    watch(
      () => ({ ...pagination.value }),
      (cur, pre) => {
        if (cur.current !== pre.current || cur.pageSize !== pre.pageSize) {
          fetchPages();
        }
      },
    );
    const countUserAndReturn = users => {
      let m = 0; //fb_account_id not null
      let n = 0;
      for (const user of users) {
        // console.log(user, user.fb_account_id, m, n);
        if (user.fb_account_id) {
          m++;
        } else {
          n++;
        }
      }
      return `${m}+${n}`;
    };
    onMounted(() => {
      fetchPages();
      queryTagsApi();
      queryTagsApi().then(res => {
        tagsData.value = res.data;
      });
    });

    const refreshToken = (record: FbPage) => {
      refreshPageToken({
        page_ids: [record.id],
      })
        .then(() => {
          message.success(t('Task sumbmitted'));
        })
        .catch(e => {
          console.log('error:', e);
          message.error(t('Request error'));
        });
    };

    const syncPageForms = (record: FbPage) => {
      syncPageFormsApi({
        page_ids: [record.id],
      })
        .then(() => {
          message.success(t('Task sumbmitted'));
        })
        .catch(e => {
          console.log('error:', e);
          message.error(t('Request error'));
        });
    };

    return {
      dataSource,
      formItems,
      loading,
      fetchPages,
      resetPagination,
      dayjs,
      pagination,
      columns,
      copyCell,
      t,
      handleTableChange,
      tagOptions,
      tagsData,
      countUserAndReturn,
      modalVisible,
      modalUserData,
      showUserModal,
      closeUserModal,
      expand,
      tableHeight,
      onSearch,
      showModal,
      pageModal,
      appliedFilters,
      refreshToken,
      syncPageForms,
      filterVisible,
      ReloadOutlined,
      h,
    };
  },
});
</script>

<style scoped>
.pages-table-wrapper {
  overflow-x: auto;
  min-width: 0;
}
.filter-row {
  margin-top: 12px;
  padding-top: 12px;
  border-top: 1px solid #f0f0f0;
}
.action-link {
  margin-right: 12px;
}
.action-link:last-child {
  margin-right: 0;
}
.page-avatar {
  object-fit: cover;
  border-radius: 4px;
}
</style>
