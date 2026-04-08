<template>
  <page-container title="Operator">
    <div class="content-title">
      <div class="search-box">
        <span>名称：</span>
        <a-input v-model:value="pagination.name" />
        <a-button type="primary" :loading="loading" @click="fetchAccount()">查询</a-button>
        <a-button @click="resetPagination">重置</a-button>
      </div>
      <div class="add-box">
        <a-button type="primary" @click="showCostModal('')">获取最新消费</a-button>
        <a-button type="primary" class="href-btn" @click="showModal('')">新增</a-button>
      </div>
    </div>
    <a-table
      :columns="columns"
      :data-source="dataSource"
      :scroll="{ x: 2000 }"
      :pagination="pagination"
      :loading="loading"
      :row-key="record => record.id"
      @change="handleTableChange"
    >
      <template #bodyCell="{ column, text, record }">
        <template v-if="column['dataIndex'] === 'disable_reason'">
          <span>{{ text === 0 ? '停用' : '启用' }}</span>
        </template>
        <template v-if="column['dataIndex'] === 'ad_account'">
          <div v-if="text">{{ text.name }}</div>
        </template>
        <template
          v-if="['balance', 'spend_balance', 'spend_cap'].includes(`${column['dataIndex']}`)"
        >
          <span>${{ text / 100 }}</span>
        </template>
        <template v-if="['created_at', 'updated_at'].includes(`${column['dataIndex']}`)">
          <span>{{ dayjs(text).format('YYYY-MM-DD HH:mm:ss') }}</span>
        </template>
        <template v-if="column['dataIndex'] === 'operation'">
          <a @click="showModal(record)">修改</a>
          <a-popconfirm
            title="确认删除此条数据?"
            ok-text="确定"
            cancel-text="取消"
            @confirm="deleteOne(record)"
          >
            <a class="href-btn" href="#">删除</a>
          </a-popconfirm>
          <a class="href-btn" @click="showCostModal(record)">拉取数据</a>
        </template>
      </template>
    </a-table>
    <a-modal
      v-if="openFetchCost"
      v-model:open="openFetchCost"
      :title="openFetchCostDialogTitle"
      @ok="handleCost"
      :width="600"
      wrapClassName="time-dialog"
    >
      <a-form ref="datePickRef" :model="dateRangerForm">
        <a-form-item
          name="dateRanger"
          label="请选择日期区间"
          :rules="[{ required: true, message: '请选择日期区间' }]"
        >
          <a-range-picker v-model:value="dateRangerForm.dateRanger" format="YYYY-MM-DD" />
        </a-form-item>
      </a-form>
    </a-modal>
    <a-modal v-if="open" v-model:open="open" :title="dialogTitle" @ok="handleOk" :width="800">
      <a-form
        ref="formRef"
        :label-col="{ span: 4 }"
        name="dynamic_form_nest_item"
        :model="dynamicValidateForm"
      >
        <a-form-item
          name="name"
          label="Name"
          :rules="[{ required: true, message: 'Missing Name' }]"
        >
          <a-input v-model:value="dynamicValidateForm.name" />
        </a-form-item>
        <a-form-item
          name="finger"
          label="finger"
          :rules="[{ required: true, message: 'Missing Finger' }]"
        >
          <a-input v-model:value="dynamicValidateForm.finger" />
        </a-form-item>
        <a-form-item name="ua" label="ua" :rules="[{ required: true, message: 'Missing ua' }]">
          <a-input v-model:value="dynamicValidateForm.ua" />
        </a-form-item>
        <a-form-item
          name="proxy"
          label="proxy"
          :rules="[{ required: true, message: 'Missing Proxy' }]"
        >
          <a-input v-model:value="dynamicValidateForm.proxy" />
        </a-form-item>
        <a-form-item name="selected_ad_account" label="Ad Account">
          <a-select
            v-model:value="dynamicValidateForm.selected_ad_account"
            :options="adAccounts"
            :fieldNames="{ label: 'name', value: 'id' }"
            optionFilterProp="name"
            showSearch
          />
        </a-form-item>
        <a-form-item
          name="ad_token"
          label="ad_token"
          :rules="[{ required: true, message: 'Missing AdToken' }]"
        >
          <a-input v-model:value="dynamicValidateForm.ad_token" />
        </a-form-item>
        <a-form-item label="ad_token">
          <a-switch v-model:checked="dynamicValidateForm.ad_token_valid" />
        </a-form-item>
        <a-form-item
          name="cookies"
          label="cookies"
          :rules="[{ required: true, message: 'Missing Cookies' }]"
        >
          <a-textarea v-model:value="dynamicValidateForm.cookies" :rows="4" />
        </a-form-item>
        <a-form-item name="comments" label="Comments">
          <a-textarea v-model:value="dynamicValidateForm.comments" :rows="4" />
        </a-form-item>
      </a-form>
    </a-modal>
  </page-container>
</template>
<script lang="ts">
import dayjs from 'dayjs';
import { defineComponent, ref, onMounted, watch } from 'vue';
import { message } from 'ant-design-vue';
import { queryListApi, deleteOneApi, addOneApi } from '@/api/operator/table-list';
import { queryListApi as queryAdAccountListApi } from '@/api/adaccount/table-list';
import { cloneDeep } from 'lodash';

const columns: any[] = [
  {
    title: 'ID',
    dataIndex: 'id',
  },
  {
    title: 'Name',
    dataIndex: 'name',
    ellipsis: true,
  },
  {
    title: 'Ad Account',
    dataIndex: 'ad_account',
  },
  {
    title: 'finger',
    dataIndex: 'finger',
  },
  {
    title: 'Token',
    dataIndex: 'ad_token',
    ellipsis: true,
  },
  {
    title: 'Valid',
    dataIndex: 'ad_token_valid',
  },
  {
    title: 'Created',
    dataIndex: 'created_at',
  },
  {
    title: 'Update',
    dataIndex: 'updated_at',
  },
  {
    title: 'Comments',
    dataIndex: 'comments',
  },
  {
    title: '操作',
    width: 180,
    dataIndex: 'operation',
  },
];
export default defineComponent({
  setup() {
    const loading = ref(true);
    const dataSource = ref<any>([]);
    const pagination = ref<any>({
      name: '',
      showQuickJumper: true,
      showSizeChanger: true,
      current: 1,
      total: 0,
      showTotal: total => `Total ${total} items`,
      pageSize: 10,
    });
    const resetPagination = () => {
      pagination.value.name = '';
      fetchAccount();
    };
    const fetchAccount = () => {
      // 使 table 打开加载状态指示
      loading.value = true;
      // 发起 AJAX 请求到后端
      queryListApi({
        name: pagination.value.name,
        pageNo: pagination.value.current,
        pageSize: pagination.value.pageSize,
      })
        .then((res: any) => {
          // 更新数据
          dataSource.value = res.result.data;
          pagination.value.total = res.result.totalCount;
        })
        .finally(() => {
          // 使 table 关闭加载状态指示
          loading.value = false;
        });
    };
    const deleteOne = (data: any) => {
      loading.value = true;
      deleteOneApi(data.id).then(() => {
        message.success('操作成功！');
        fetchAccount();
      });
    };
    const open = ref<boolean>(false);
    const openFetchCost = ref<boolean>(false);
    const dialogTitle = ref<string>('');
    const openFetchCostDialogTitle = ref<string>('拉取消费数据');
    const adAccounts = ref<any>([]);
    const datePickRef = ref<any>();
    const formRef = ref<any>();
    const resetForm = {
      id: 0,
      name: '',
      finger: '',
      ua: '',
      proxy: '',
      selected_ad_account: '',
      ad_token: '',
      cookies: '',
      comments: '',
      ad_token_valid: false,
    };
    const dateRangerForm = ref<any>({
      dateRanger: [],
      id: '',
    });
    const dynamicValidateForm = ref<any>(cloneDeep(resetForm));
    const showCostModal = (data: any) => {
      dateRangerForm.value.dateRanger = [];
      if (data) {
        dateRangerForm.value.id = data.id;
      }
      openFetchCost.value = true;
    };
    const showModal = (data: any) => {
      if (data) {
        dynamicValidateForm.value = cloneDeep(data);
        dynamicValidateForm.value.selected_ad_account = dynamicValidateForm.value.ad_account
          ? dynamicValidateForm.value.ad_account.id
          : '';
      } else {
        dynamicValidateForm.value = cloneDeep(resetForm);
      }
      dialogTitle.value = data ? '编辑' : '新增';
      open.value = true;
    };
    const handleOk = () => {
      formRef.value.validateFields().then(() => {
        console.error(dynamicValidateForm);
        const params = {
          ...dynamicValidateForm.value,
        };
        addOneApi(params).then(() => {
          message.success('操作成功！');
          fetchAccount();
          open.value = false;
        });
      });
    };
    const handleCost = () => {
      datePickRef.value.validateFields().then(() => {
        const params = {
          date_start: dateRangerForm.value.dateRanger[0],
          date_stop: dateRangerForm.value.dateRanger[1],
          id: dateRangerForm.value.id,
        };
        if (!dateRangerForm.value.id) {
          delete params.id;
        }
        addOneApi(params).then(() => {
          message.success('操作成功！');
          openFetchCost.value = false;
        });
      });
    };
    const removeCondition = (item: any) => {
      const index = dynamicValidateForm.value.conditions.indexOf(item);
      if (index !== -1) {
        dynamicValidateForm.value.conditions.splice(index, 1);
      }
    };
    const addCondition = () => {
      dynamicValidateForm.value.conditions.push({
        metric: undefined,
        operator: undefined,
        value: undefined,
      });
    };
    const handleTableChange: any['onChange'] = (pag, _filters, _sorter) => {
      if (pag) {
        if (pag.current != null) pagination.value.current = pag.current;
        if (pag.pageSize != null) pagination.value.pageSize = pag.pageSize;
      }
    };
    watch(
      () => ({ ...pagination.value }),
      (cur, pre) => {
        if (cur.current !== pre.current || cur.pageSize !== pre.pageSize) {
          fetchAccount();
        }
      },
    );
    onMounted(() => {
      queryAdAccountListApi({ pageNo: 1, pageSize: 999 }).then((res: any) => {
        adAccounts.value = res.result.data;
      });
      fetchAccount();
    });
    return {
      dataSource,
      loading,
      fetchAccount,
      resetPagination,
      dayjs,
      pagination,
      handleTableChange,
      deleteOne,
      open,
      openFetchCost,
      openFetchCostDialogTitle,
      dialogTitle,
      showModal,
      showCostModal,
      handleOk,
      handleCost,
      dateRangerForm,
      dynamicValidateForm,
      adAccounts,
      formRef,
      datePickRef,
      removeCondition,
      addCondition,
      columns: ref(columns),
    };
  },
});
</script>
