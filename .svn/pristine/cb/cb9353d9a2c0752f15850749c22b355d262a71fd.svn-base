<template>
  <page-container :title="t('pages.business.manager.title')">
    <a-card mb-4>
      <a-row :gutter="[12, 0]">
        <!-- <a-col>
          <page-dialog :tags-data="tagsData" ref="pageModal" @change:tags-changed="fetchPages"></page-dialog>
        </a-col> -->

        <a-col :flex="1">
          <applied-filters :filters="appliedFilters"></applied-filters>
        </a-col>
        <a-col>
          <a-tooltip :title="t('pages.common.refresh')">
            <a-button :icon="h(ReloadOutlined)" @click="fetchPages"></a-button>
          </a-tooltip>
        </a-col>
        <a-col>
          <dynamic-form :form-items="formItems" @change:form-data="onSearch"></dynamic-form>
        </a-col>
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
        <template v-if="column['dataIndex'] === 'picture'">
          <img :src="text" alt="Avatar" width="50" height="50" />
        </template>
        <template v-if="column['dataIndex'] === 'users'">
          <a class="href-btn" @click="showUserModal(record.users)">
            {{ countUserAndReturn(record?.users) }}
          </a>
        </template>

        <template v-if="['tags'].includes(`${column['dataIndex']}`)">
          <a-tag v-for="m in text" :key="m.id">{{ m.name }}</a-tag>
        </template>
        <template v-if="['created_at', 'updated_at'].includes(`${column['dataIndex']}`)">
          <span>{{ dayjs(text).format('YYYY-MM-DD HH:mm:ss') }}</span>
        </template>
        <!-- <template v-if="column['dataIndex'] === 'operation'">
          <a class="href-btn" @click="showModal(record)">{{ t('pages.edit') }}</a>
        </template> -->
        <template v-if="column['dataIndex'] == 'is_disabled_for_integrity_reasons'">
          <a-badge
            :color="text === false ? 'green' : text === true ? 'red' : 'gray'"
            :text="text"
          />
        </template>
      </template>
    </a-table>
    <users-table-modal
      :openModal="modalVisible"
      :userData="modalUserData"
      :closeUserModal="closeUserModal"
    />
    <!-- <a-modal v-if="open" v-model:open="open" :title="dialogTitle" @ok="handleOk" :width="800">
      <a-form
        ref="formRef"
        :label-col="{ span: 4 }"
        name="dynamic_form_nest_item"
        :model="dynamicValidateForm"
      >
        <a-form-item name="notes" :label="t('pages.links.note')">
          <a-input v-model:value="dynamicValidateForm.notes" />
        </a-form-item>
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
    </a-modal> -->
  </page-container>
</template>
<script lang="ts">
import dayjs from 'dayjs';
import { computed, defineComponent, ref, onMounted, watch, h } from 'vue';
import { message } from 'ant-design-vue';
import { queryBMsApi } from '@/api/fb_bms';
import UsersTableModal from '@/components/users/UsersTableModalForBMs.vue';
import { useI18n } from 'vue-i18n';
import useClipboard from 'vue-clipboard3';
import DynamicForm from '@/components/dynamic-form/dynamic-form.vue';
import AppliedFilters from '@/components/applied-filters/applied-filters.vue';
import { ReloadOutlined } from '@ant-design/icons-vue';

export default defineComponent({
  components: {
    UsersTableModal,
    DynamicForm,
    AppliedFilters,
  },
  setup() {
    const { t } = useI18n();
    const modalVisible = ref(false);
    const modalUserData = ref([]);
    const showUserModal = usersData => {
      modalUserData.value = usersData;
      modalVisible.value = true;
    };
    const closeUserModal = () => {
      modalVisible.value = false;
      modalUserData.value = [];
    };
    const screenHeight = ref<number>(window.innerHeight);
    const tableHeight = screenHeight.value < 1010 ? ref('48vh') : ref('48vh');

    // watch(expand, newValue => {
    //   if (screenHeight.value < 1010) {
    //     tableHeight.value = newValue ? '48vh' : '65vh';
    //   } else {
    //     tableHeight.value = newValue ? '48vh' : '65vh';
    //   }
    // });
    const sortedInfo = ref();
    const columns = computed<any[]>(() => {
      const sorted = sortedInfo.value || {};
      return [
        {
          title: t('pages.bms.source.id'),
          dataIndex: 'source_id',
        },
        {
          title: t('pages.name'),
          dataIndex: 'name',
          resizable: true,
          width: 260,
        },
        {
          title: t('pages.acc.page.restricted'),
          dataIndex: 'is_disabled_for_integrity_reasons',
          minWidth: 100,
          align: 'center',
        },
        {
          title: t('pages.twofactor.type'),
          dataIndex: 'two_factor_type',
        },
        {
          title: t('pages.verification.status'),
          dataIndex: 'verification_status',
        },
        {
          title: t('pages.notes'),
          dataIndex: 'notes',
        },
        {
          title: t('pages.users'),
          dataIndex: 'users',
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
        // {
        //   title: t('pages.operation'),
        //   width: 180,
        //   dataIndex: 'operation',
        //   fixed: 'right',
        // },
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
    const loading = ref(true);
    // const submitting = ref(false);
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
      pagination.value.bmId = '';
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
        source_id: pagination.value.bmId,
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

      queryBMsApi(param)
        .then((res: any) => {
          dataSource.value = res.data;
          pagination.value.total = res.totalCount;
        })
        .finally(() => {
          loading.value = false;
        });
    };

    const formItems = ref([
      { label: 'pages.bms.source.id', field: 'bmId' },
      { label: 'pages.name', field: 'name' },
      { label: 'pages.notes', field: 'notes' },
    ]);
    const appliedFilters = ref({});
    const onSearch = data => {
      Object.entries(data).forEach(([key, value]) => (pagination.value[key] = value));
      appliedFilters.value = data;
      fetchPages();
    };

    // const tagOptions = ref<any[]>([]);
    // const tagsData = ref<any[]>([]);

    // const pageModal = ref(null);
    // const showModal = (rec) => {
    //   pageModal.value?.showModal(rec);
    // }

    const { toClipboard } = useClipboard();
    const copyCell = async (text: any) => {
      try {
        await toClipboard(text);
        message.success(t('pages.linkCopiedw'));
      } catch (e) {
        console.error(e);
      }
    };
    // interface TagOption {
    //   label: string;
    //   value: string;
    // }
    // const tagsData = ref<any>([]);
    // const tagOptions = ref<TagOption[]>([]);
    // watch(tagsData, newValue => {
    //   tagOptions.value = newValue.map(tag => ({
    //     label: tag.name,
    //     value: tag.name,
    //   }));
    // });
    // const inputVisible = ref<boolean>(false);
    // const inputValue = ref<string>('');
    // const inputRef = ref();
    // const handleClose = (tag: any) => {
    //   const index = tagsData.value.findIndex(i => i.name === tag.name);
    //   tagsData.value.splice(index, 0, tag);
    //   Modal.confirm({
    //     title: t('pages.hint'),
    //     content: t('pages.doubleConfirmDel'),
    //     okText: t('pages.confirm'),
    //     cancelText: t('pages.cancel'),
    //     wrapClassName: 'confirm-dialog',
    //     onOk() {
    //       deleteTagsOneApi(tag.id)
    //         .then(res => {
    //           message.success(t('pages.opSuccessfully'));
    //           tagsData.value.splice(index, 1);
    //           console.error(res);
    //         })
    //         .catch(err => {
    //           console.error(err);
    //         });
    //     },
    //   });
    // };
    // const changeTag = (tag: any) => {
    //   const index = dynamicValidateForm.value.tags.findIndex(i => i.name === tag.name);
    //   if (index >= 0) {
    //     dynamicValidateForm.value.tags.splice(index, 1);
    //   } else {
    //     dynamicValidateForm.value.tags.push(tag);
    //   }
    // };
    // const showInput = () => {
    //   inputVisible.value = true;
    //   nextTick(() => {
    //     inputRef.value.focus();
    //   });
    // };
    // const handleInputConfirm = () => {
    //   const tags = tagsData.value;
    //   if (inputValue.value && !tags.includes(inputValue.value)) {
    //     addTagsOneApi({ name: inputValue.value }).then(res => {
    //       message.success(t('pages.opSuccessfully'));
    //       tags.push(res);
    //       inputVisible.value = false;
    //       tagsData.value = tags;
    //       inputValue.value = '';
    //     });
    //   }
    // };

    // const open = ref<boolean>(false);
    // const dialogTitle = ref<string>('');
    // const formRef = ref<any>();
    // const resetForm = {
    //   id: 0,
    //   notes: '',
    //   tags: [],
    // };
    // const dynamicValidateForm = ref<any>(cloneDeep(resetForm));
    // const showModal = (data: any) => {
    //   if (data) {
    //     dynamicValidateForm.value = cloneDeep(data);
    //   } else {
    //     dynamicValidateForm.value = cloneDeep(resetForm);
    //   }
    //   dialogTitle.value = data ? t('pages.edit') : t('pages.add');
    //   open.value = true;
    // };
    // const handleOk = () => {
    //   if (!submitting.value) {
    //     formRef.value.validateFields().then(() => {
    //       const params = {
    //         id: dynamicValidateForm.value.id,
    //         notes: '',
    //         tag_ids: [],
    //       };
    //       params.tag_ids = dynamicValidateForm.value.tags.map(obj => obj.id);
    //       if (dynamicValidateForm.value.notes) params.notes = dynamicValidateForm.value.notes;
    //       submitting.value = true;
    //       updateBMsOneApi(params)
    //         .then(() => {
    //           message.success(t('pages.opSuccessfully'));
    //           fetchPages();
    //           open.value = false;
    //         })
    //         .catch(err => {
    //           message.error(err.message);
    //         })
    //         .finally(() => {
    //           submitting.value = false;
    //         });
    //     });
    //   }
    // };
    watch(
      () => ({ ...pagination.value }),
      (cur, pre) => {
        if (cur.current !== pre.current || cur.pageSize !== pre.pageSize) {
          fetchPages();
        }
      },
    );
    const countUserAndReturn = users => {
      if (!users) {
        return '';
      }
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
      // queryTagsApi().then(res => {
      //   tagsData.value = res.data;
      // });
    });
    return {
      dataSource,
      loading,
      fetchPages,
      resetPagination,
      dayjs,
      pagination,
      columns,
      copyCell,
      t,
      open,
      // showModal,
      // handleOk,
      // formRef,
      // dialogTitle,
      // dynamicValidateForm,
      // tagsData,
      // showInput,
      // inputVisible,
      // inputValue,
      // handleClose,
      // handleInputConfirm,
      // inputRef,
      // changeTag,
      handleTableChange,
      // tagOptions,
      countUserAndReturn,
      modalVisible,
      modalUserData,
      showUserModal,
      closeUserModal,
      // expand,
      tableHeight,
      onSearch,
      formItems,
      appliedFilters,
      ReloadOutlined,
      h,
      // tagOptions,
      // tagsData,
      // showModal,
      // pageModal,
    };
  },
});
</script>
