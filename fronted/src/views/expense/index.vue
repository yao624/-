<template>
  <page-container title="Expense">
    <div class="content-title">
      <div class="search-box">
        <span>名称：</span>
        <a-input v-model:value="pagination.name" />
        <a-button type="primary" :loading="loading" @click="fetchAccount()">查询</a-button>
        <a-button @click="resetPagination">重置</a-button>
      </div>
      <div class="add-box">
        <a-button type="primary" @click="fetchAccount()">新增</a-button>
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
      <template #bodyCell="{ column, text }">
        <template v-if="column['dataIndex'] === 'category'">
          <span>{{ text.join(',') }}</span>
        </template>
        <template v-if="['created_at', 'datetime'].includes(`${column['dataIndex']}`)">
          <span>{{ dayjs(text).format('YYYY-MM-DD HH:mm:ss') }}</span>
        </template>
        <template v-if="column['dataIndex'] === 'operation'">
          <span>编辑</span>
        </template>
      </template>
    </a-table>
  </page-container>
</template>
<script lang="ts">
import dayjs from 'dayjs';
import { defineComponent, ref, onMounted, watch } from 'vue';
import { queryListApi } from '@/api/expense/table-list';

const columns: any[] = [
  {
    title: 'Amount',
    dataIndex: 'amount',
  },
  {
    title: 'Currency',
    dataIndex: 'currency',
  },
  {
    title: 'Category',
    dataIndex: 'category',
  },
  {
    title: 'Vendor',
    dataIndex: 'vendor',
  },
  {
    title: 'Method',
    dataIndex: 'payment_method',
  },
  {
    title: 'Transaction Datetime',
    dataIndex: 'datetime',
  },
  {
    title: '添加时间',
    dataIndex: 'created_at',
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
      // sortedInfo.value = null;
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
      columns: ref(columns),
    };
  },
});
</script>
