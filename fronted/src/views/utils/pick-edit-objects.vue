<template>
  <a-button type="link" :icon="getIcon()" @click="open = true"></a-button>
  <a-modal
    v-model:visible="open"
    :title="t(`Select`)"
    :width="800"
    :height="'80vh'"
    @ok="onOk"
    :okText="t('Submit')"
  >
    <a-row :style="{ marginBottom: '8px' }">
      <a-col>
        <a-input-search allow-clear v-model:value="value" style="width: 200px" @search="loadData" />
      </a-col>
      <a-col>
        <span v-if="state.selectedRowKeys.length > 0">
          {{ `Selected ${state.selectedRowKeys.length} items` }}
        </span>
      </a-col>
    </a-row>
    <a-table
      :loading="loading"
      :scroll="{ y: 360 }"
      :columns="tableColumns"
      :data-source="dataSource"
      :pagination="pagination"
      :row-selection="rowSelection"
      :custom-row="customRow"
      :row-key="record => record.id"
      @change="handleTableChange"
      bordered
      sticky
    >
      <template #bodyCell="{ column, text }">
        <template v-if="column['dataIndex'] == 'account_status'">
          <a-badge :color="text === 'ACTIVE' ? 'green' : 'gray'" />
          {{ text }}
        </template>
      </template>
    </a-table>
  </a-modal>
</template>
<script lang="ts">
import { SelectOutlined, PlusCircleOutlined, EditOutlined } from '@ant-design/icons-vue';
import { defineComponent, ref, h, toRaw, watch, reactive, computed } from 'vue';
import { useI18n } from 'vue-i18n';
import type { TableRowSelection } from 'ant-design-vue/es/table/interface';
import type { DefaultRecordType, Key } from 'ant-design-vue/es/vc-table/interface';

export default defineComponent({
  name: 'PickObjects',
  props: {
    multiple: {
      type: Boolean,
      default: true,
    },
    // api: {
    //   type: Function,
    //   required: true,
    // },
    datasets: {
      type: Array<any>,
      required: true,
    },
    selectedKeys: {
      type: Array<any>,
      required: false,
    },
    columns: {
      type: Array<any>,
      required: true,
    },
    allowEmpty: {
      type: Boolean,
      default: false,
    },
    iconType: {
      type: Number,
      required: false,
      default: 1,
    },
  },
  emits: ['confirm:items-selected'],
  setup(props, { emit }) {
    const { t } = useI18n();

    const pagination = ref({
      // pageSize: 3,
      // current: 1,
      // totalCount: 0,
      current: 1,
      pageSize: 10,
      total: 0,
      showTotal: total => `Total ${total} items`,
      pageSizeOptions: ['10', '20', '50', '100'],
      showSizeChanger: true,
      showQuickJumper: true,
    });

    const open = ref(false);
    const loading = ref(false);
    const dataSource = ref([]);
    // const type = ref(props.type);
    const value = ref('');
    const tableColumns = ref(props.columns);
    const tableData = props.datasets;

    const state = reactive({
      selectedRowKeys: [],
      selectedRows: [],
    });

    state.selectedRowKeys = props.selectedKeys;
    const defaultRows = props.datasets.filter(obj => props.selectedKeys.includes(obj.id));
    state.selectedRows = defaultRows;

    const rowSelection = computed<TableRowSelection<DefaultRecordType>>(() => {
      return {
        type: props.multiple ? 'checkbox' : 'radio',
        selectedRowKeys: state.selectedRowKeys,

        // onChange 事件不能少, 因为这个会在用户直接点击 checkbox 或者 radio 的时候触发, 这里不会触发 onClick 事件
        onChange: (selectedRowKeys: Key[], rows: DefaultRecordType[]) => {
          console.log('on change');
          state.selectedRowKeys = selectedRowKeys;
          state.selectedRows = rows;
        },
      };
    });

    const selectRow = record => {
      console.log('select row, record: ', record);
      const selectedRowKeys = [...state.selectedRowKeys];
      const selectedRows = [...state.selectedRows];

      const index = selectedRowKeys.indexOf(record.id);
      console.log(`index: ${index}`);
      if (index >= 0) {
        // 如果点击的是已经选中的行
        console.log('index: ', index, ', remove it');
        if (selectedRowKeys.length > 1) {
          selectedRowKeys.splice(index, 1);
          selectedRows.splice(index, 1);
        } else if (selectedRowKeys.length === 1 && props.allowEmpty) {
          // 如果当前只有一个元素了, 并且允许为空, 则删除当前选中的行
          selectedRowKeys.splice(index, 1);
          selectedRows.splice(index, 1);
        }
      } else {
        console.log('index: ', index, ', add it ');
        console.log('current length: ', selectedRowKeys.length);
        if (selectedRowKeys.length > 0) {
          // 如果当前已经有选中的行了
          console.log('clean current');
          if (props.multiple) {
            // 如果允许多选
            selectedRowKeys.push(record.id);
            selectedRows.push(record);
          } else {
            // 如果不允许多选, 先清空当前的数据, 再添加
            selectedRowKeys.splice(0, selectedRowKeys.length);
            selectedRowKeys.push(record.id);

            selectedRows.splice(0, selectedRows.length);
            selectedRows.push(record);
          }
        } else {
          // 当前没有选中的, 直接加入
          console.log('current is empty, just add it');
          selectedRowKeys.push(record.id);
          selectedRows.push(record);
        }
      }
      console.log('after click, selectedRowKeys: ', selectedRowKeys);
      state.selectedRowKeys = selectedRowKeys;
      state.selectedRows = selectedRows;
    };

    const customRow = record => {
      return {
        onClick: () => {
          selectRow(record);
        },
      };
    };

    watch(
      () => open.value,
      value => {
        console.log('event: ', props.datasets);
        if (value && !dataSource.value.length) {
          loadData();
        }
      },
    );

    const loadData = (key?: string) => {
      loading.value = true;
      // dataSource.value = tableData;
      // console.log(dataSource.value);

      if (!key) {
        dataSource.value = tableData;
      } else {
        dataSource.value = tableData.filter(item => {
          const idMatch = item.source_id && String(item.source_id).includes(key);
          const nameMatch = item.name && item.name.includes(key);
          return idMatch || nameMatch;
        });
      }

      loading.value = false;

      // const func = props.api;
      // func({ pageSize: pagination.value.pageSize, pageNo: pagination.value.current, key })
      //   .then(({ data, pageSize, pageNo, totalCount }: any) => {
      //     dataSource.value = data;
      //     // pagination.value = { pageSize, current: pageNo, totalCount };
      //     pagination.value.current = pageNo;
      //     pagination.value.pageSize = pageSize;
      //     pagination.value.total = totalCount;
      //     console.log(pageSize, pageNo, totalCount);
      //   })
      //   .finally(() => (loading.value = false));
    };

    const onOk = () => {
      open.value = false;
      const selectedRowKeys = toRaw(state.selectedRowKeys);
      const selectedRow = toRaw(state.selectedRows);
      state.selectedRowKeys = props.selectedKeys;
      state.selectedRows = defaultRows;
      emit('confirm:items-selected', selectedRowKeys, selectedRow);
    };

    const getIcon = () => {
      if (props.iconType === 1) {
        return h(SelectOutlined);
      } else if (props.iconType === 2) {
        return h(PlusCircleOutlined);
      } else if (props.iconType === 3) {
        return h(EditOutlined);
      }
      return h(SelectOutlined);
    };

    const handleTableChange = ({ current, pageSize }, _filters: any, _sorter: any) => {
      // sorterInfoMap.value = sorter;
      pagination.value.current = current;
      pagination.value.pageSize = pageSize;
      loadData();
    };

    return {
      tableColumns,
      value,
      loading,
      open,
      pagination,
      dataSource,
      SelectOutlined,
      PlusCircleOutlined,
      EditOutlined,
      loadData,
      rowSelection,
      // type,
      onOk,
      t,
      h,
      customRow,
      getIcon,

      handleTableChange,
      state,
    };
  },
});
</script>
