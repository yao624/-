<template>
  <a-button type="primary" :icon="h(SelectOutlined)" @click="open = true"></a-button>
  <a-modal v-model:visible="open" :title="t(`Select`)" :width="800" :height="'80vh'" @ok="onOk">
    <a-row :style="{ marginBottom: '8px' }">
      <a-col>
        <a-input-search v-model:value="value" style="width: 200px" @search="loadData" />
      </a-col>
    </a-row>
    <a-table
      :loading="loading"
      :scroll="{ y: 360 }"
      :columns="columns"
      :data-source="dataSource"
      :pagination="pagination"
      :row-selection="rowSelection"
      :row-key="record => record.id"
    ></a-table>
  </a-modal>
</template>
<script lang="ts">
import { SelectOutlined } from '@ant-design/icons-vue';
import { defineComponent, ref, h, toRaw, watch } from 'vue';
import { useI18n } from 'vue-i18n';

export default defineComponent({
  name: 'PickMaterialsLinks',
  props: {
    // type: {
    // 	type: String,
    // 	required: true,
    // 	default: 'material'
    // },
    multiple: {
      type: Boolean,
      default: true,
    },
    api: {
      type: Function,
      required: true,
    },
    columns: {
      type: Array<any>,
      required: true,
    },
  },
  emits: ['confirm:items-selected'],
  setup(props, { emit }) {
    const { t } = useI18n();

    const pagination = ref({
      pageSize: 100,
      current: 1,
      totalCount: 0,
    });

    const rowSelection = ref({
      type: props.multiple ? 'select' : 'radio',
      checkStrictly: false,
      onChange: (selectedRowKeys: (string | number)[], rows) => {
        // console.log(`selectedRowKeys: ${selectedRowKeys}`, 'selectedRows: ', selectedRows);
        selectedIds.value = selectedRowKeys;
        selectedRows.value = rows;
      },
      // onSelect: (record: any, selected: boolean, selectedRows: any[]) => {
      // 	console.log(record, selected, selectedRows);
      // },
      // onSelectAll: (selected: boolean, selectedRows: any[], changeRows: any[]) => {
      // 	console.log(selected, selectedRows, changeRows);
      // },
    });

    const open = ref(false);
    const loading = ref(false);
    const dataSource = ref([]);
    // const type = ref(props.type);
    const value = ref('');
    const columns = ref(props.columns);
    const selectedIds = ref([]);
    const selectedRows = ref([]);

    watch(
      () => open.value,
      value => {
        if (value && !dataSource.value.length) {
          loadData();
        }
      },
    );

    const loadData = (name?: string) => {
      loading.value = true;
      const func = props.api;
      func({ ...pagination.value, pageNo: pagination.value.current, name })
        .then(({ data, pageSize, pageNo, totalCount }: any) => {
          dataSource.value = data;
          pagination.value = { pageSize, current: pageNo, totalCount };
        })
        .finally(() => (loading.value = false));
    };

    const onOk = () => {
      open.value = false;
      emit('confirm:items-selected', toRaw(selectedIds.value), toRaw(selectedRows.value));
    };

    return {
      columns: columns,
      value,
      loading,
      open,
      pagination,
      dataSource,
      SelectOutlined,
      loadData,
      rowSelection,
      // type,
      onOk,
      t,
      h,
    };
  },
});
</script>
