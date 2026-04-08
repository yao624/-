<template>
  <page-container title="Rule">
    <div class="content-title">
      <div class="search-box">
        <span>名称：</span>
        <a-input v-model:value="pagination.name" />
        <a-button type="primary" :loading="loading" @click="fetchAccount()">查询</a-button>
        <a-button @click="resetPagination">重置</a-button>
      </div>
      <div class="add-box">
        <a-button type="primary" @click="syncDataCheckRule()">同步数据-检查规则</a-button>
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
        <template v-if="column['dataIndex'] === 'ad_accounts'">
          <div v-for="m in text" :key="m.id">{{ m.name }}</div>
        </template>
        <template v-if="column['dataIndex'] === 'conditions'">
          <div v-for="m in text" :key="m.id">{{ `${m.metric} ${m.operator} ${m.value}` }}</div>
        </template>
        <template v-if="column['dataIndex'] === 'actions'">
          <div v-for="m in text" :key="m.id">{{ m }}</div>
        </template>
        <template v-if="['created_at', 'updated_at'].includes(`${column['dataIndex']}`)">
          <span>{{ dayjs(text).format('YYYY-MM-DD HH:mm:ss') }}</span>
        </template>
        <template v-if="column['dataIndex'] === 'operation'">
          <a class="href-btn" @click="showModal(record)">编辑</a>
          <a-popconfirm
            title="确认删除此条数据?"
            ok-text="确定"
            cancel-text="取消"
            @confirm="deleteOne(record)"
          >
            <a class="href-btn" href="#">删除</a>
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
        <a-form-item
          name="name"
          label="Name"
          :rules="[{ required: true, message: 'Missing Name' }]"
        >
          <a-input v-model:value="dynamicValidateForm.name" />
        </a-form-item>
        <a-form-item
          name="date_preset"
          label="Date Preset"
          :rules="[{ required: true, message: 'Missing Date Preset' }]"
        >
          <a-select v-model:value="dynamicValidateForm.date_preset">
            <a-select-option value="All">All</a-select-option>
            <a-select-option value="Today">Today</a-select-option>
          </a-select>
        </a-form-item>
        <a-form-item
          name="scope"
          label="Scope"
          :rules="[{ required: true, message: 'Missing Scope' }]"
        >
          <a-select v-model:value="dynamicValidateForm.scope">
            <a-select-option value="Campaign">Campaign</a-select-option>
            <a-select-option value="Adset">Adset</a-select-option>
            <a-select-option value="Ads">Ads</a-select-option>
          </a-select>
        </a-form-item>
        <a-form-item name="fb_adaccount_ids" label="Ad Account">
          <a-select
            v-model:value="dynamicValidateForm.fb_adaccount_ids"
            :options="adAccounts"
            mode="multiple"
            optionFilterProp="name"
            :fieldNames="{ label: 'name', value: 'id' }"
          />
        </a-form-item>
        <a-form-item
          name="logic"
          label="Relation"
          :rules="[{ required: true, message: 'Missing Relation' }]"
        >
          <a-select v-model:value="dynamicValidateForm.logic">
            <a-select-option :value="1">Must Match All of the Conditions</a-select-option>
            <a-select-option :value="0">Match Any of these Conditions</a-select-option>
          </a-select>
        </a-form-item>
        <a-space
          v-for="(condition, index) in dynamicValidateForm.conditions"
          :key="index"
          style="display: flex; margin-bottom: 8px"
          align="baseline"
        >
          <a-form-item
            :name="['conditions', index, 'metric']"
            :label="`Condition${index + 1}`"
            :label-col="{ span: 10 }"
            :rules="{
              required: true,
              message: 'Missing metric',
            }"
          >
            <a-select
              v-model:value="condition.metric"
              style="width: 160px; margin-right: 32px"
              placeholder="metric"
            >
              <a-select-option value="Conversion">Conversion</a-select-option>
              <a-select-option value="Offer Clicks">Offer Clicks</a-select-option>
              <a-select-option value="Spend">Spend</a-select-option>
              <a-select-option value="Offer CPC">Offer CPC</a-select-option>
              <a-select-option value="CPM">CPM</a-select-option>
              <a-select-option value="Add To Cart">Add To Cart</a-select-option>
              <a-select-option value="Lead">Lead</a-select-option>
              <a-select-option value="Impressions">Impressions</a-select-option>
              <a-select-option value="Revenue">Revenue</a-select-option>
              <a-select-option value="Profit">Profit</a-select-option>
              <a-select-option value="ROI">ROI</a-select-option>
            </a-select>
          </a-form-item>
          <a-form-item
            :name="['conditions', index, 'operator']"
            :rules="{
              required: true,
              message: 'Missing operator',
            }"
          >
            <a-select
              v-model:value="condition.operator"
              style="width: 170px"
              placeholder="operator"
            >
              <a-select-option value=">">大于</a-select-option>
              <a-select-option value="<">小于</a-select-option>
              <a-select-option value="=">等于</a-select-option>
            </a-select>
          </a-form-item>
          <a-form-item
            :name="['conditions', index, 'value']"
            :rules="{
              required: true,
              message: 'Missing value',
            }"
          >
            <a-input v-model:value="condition.value" style="width: 230px" placeholder="value" />
          </a-form-item>
          <minus-circle-outlined
            @click="removeCondition(condition)"
            v-if="dynamicValidateForm.conditions.length > 1"
          />
        </a-space>
        <a-form-item>
          <a-button
            type="dashed"
            style="margin-left: 120px; width: 320px"
            block
            @click="addCondition"
          >
            <plus-outlined />
            Add Condition
          </a-button>
        </a-form-item>
        <a-form-item
          name="actions"
          label="Actions"
          :rules="[{ required: true, message: 'Missing Actions' }]"
        >
          <a-select v-model:value="dynamicValidateForm.actions" mode="multiple">
            <a-select-option value="TG Alert">TG Alert</a-select-option>
            <a-select-option value="Stop Campaign">Stop Campaign</a-select-option>
            <a-select-option value="Stop Adset">Stop Adset</a-select-option>
            <a-select-option value="Stop Ad">Stop Ad</a-select-option>
            <a-select-option value="Start Campaign">Start Campaign</a-select-option>
            <a-select-option value="Start Adset">Start Adset</a-select-option>
            <a-select-option value="Start Ad">Start Ad</a-select-option>
          </a-select>
        </a-form-item>
        <a-form-item name="description" label="Description">
          <a-textarea v-model:value="dynamicValidateForm.description" :rows="4" />
        </a-form-item>
      </a-form>
    </a-modal>
  </page-container>
</template>
<script lang="ts">
import dayjs from 'dayjs';
import { defineComponent, ref, onMounted, watch } from 'vue';
import { message } from 'ant-design-vue';
import { MinusCircleOutlined, PlusOutlined } from '@ant-design/icons-vue';
import { queryListApi, deleteOneApi, syncDataCheckRuleApi, addOneApi } from '@/api/rule/';
import { queryListApi as queryAdAccountListApi } from '@/api/adaccount/table-list';
import { cloneDeep } from 'lodash';

const columns: any[] = [
  {
    title: 'ID',
    dataIndex: 'id',
    width: 60,
  },
  {
    title: 'Name',
    dataIndex: 'name',
  },
  {
    title: 'Date Preset',
    dataIndex: 'date_preset',
  },
  {
    title: 'Ad Account',
    dataIndex: 'ad_accounts',
  },
  {
    title: 'Conditions',
    dataIndex: 'conditions',
  },
  {
    title: 'Actions',
    dataIndex: 'actions',
  },
  {
    title: '添加时间',
    dataIndex: 'created_at',
  },
  {
    title: 'Scope',
    dataIndex: 'scope',
    width: 90,
  },
  {
    title: 'Logic',
    dataIndex: 'logic',
    width: 90,
  },
  {
    title: '操作',
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
    const handleTableChange: any['onChange'] = (pag, _filters, _sorter) => {
      if (pag) {
        if (pag.current != null) pagination.value.current = pag.current;
        if (pag.pageSize != null) pagination.value.pageSize = pag.pageSize;
      }
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
    const syncDataCheckRule = () => {
      message.success('已添加到队列！');
      syncDataCheckRuleApi();
    };
    const open = ref<boolean>(false);
    const dialogTitle = ref<string>('');
    const adAccounts = ref<any>([]);
    const formRef = ref<any>();
    const resetForm = {
      id: 0,
      name: '',
      date_preset: 'All',
      fb_adaccount_ids: [],
      actions: [],
      scope: 'Campaign',
      description: '',
      logic: 1,
      conditions: [
        {
          metric: undefined,
          operator: undefined,
          value: undefined,
        },
      ],
    };
    const dynamicValidateForm = ref<any>(cloneDeep(resetForm));
    const showModal = (data: any) => {
      if (data) {
        dynamicValidateForm.value = cloneDeep(data);
        dynamicValidateForm.value.fb_adaccount_ids = dynamicValidateForm.value.ad_accounts.map(
          i => i.id,
        );
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
      syncDataCheckRule,
      open,
      dialogTitle,
      showModal,
      handleOk,
      dynamicValidateForm,
      adAccounts,
      formRef,
      removeCondition,
      addCondition,
      columns: ref(columns),
    };
  },
  components: {
    MinusCircleOutlined,
    PlusOutlined,
  },
});
</script>
