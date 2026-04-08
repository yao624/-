<template>
  <a-modal
    :title="t('Product sets')"
    :open="visible"
    width="1000px"
    :confirmLoading="loading"
    @ok="handleSubmit"
    @cancel="
      () => {
        $emit('cancel');
      }
    "
  >
    <a-table
      :columns="columns"
      :data-source="model.data_source['product_sets']"
      :row-key="record => record.id"
      bordered
      sticky
size="small"
    >
      <template #bodyCell="{ column, text, record }">
        <template
          v-if="['name', 'description', 'url', 'image_url'].includes(`${column['dataIndex']}`)"
        >
          <a-popover trigger="click" placement="topLeft">
            <template #content>
              {{ text }}
            </template>
            <copy-outlined style="color: #1677ff" v-if="text" @click="copyCell(text)" />
            {{ text }}
          </a-popover>
        </template>
        <template v-if="column['dataIndex'] === 'filter'">
          {{ text }}
        </template>
        <template v-if="column['dataIndex'] === 'tags'">
          <div>
            <a-tag v-for="item in record.tags" :key="item.id">{{ item.name }}</a-tag>
          </div>
        </template>
        <template v-if="column['dataIndex'] === 'operation'">
          <a>
            <a @click="onEdit(record)"><edit-outlined /></a>
            <a-divider type="vertical" />
            <a @click="showTagModal(record)"><tag-outlined /></a>
          </a>
        </template>
      </template>
    </a-table>
  </a-modal>

  <a-modal
    v-model:visible="isModalVisible"
    title="Edit Item"
    @ok="handleOk"
    @cancel="handleCancel"
    :confirm-loading="isSaving"
    :destroyOnClose="true"
  >
    <edit-product-set-modal
      ref="editModalRef"
      :name="productSetName"
      :filter="currentFilter"
      :product-list="productList"
    ></edit-product-set-modal>
  </a-modal>

  <tag-modal
    :model="tagModal.model"
    :visible="tagModal.visible"
    @cancel="
      () => {
        tagModal.visible = false;
      }
    "
    @ok="
      () => {
        tagModal.visible = false;
      }
    "
  />
</template>

<script lang="ts">
import type { PropType } from 'vue';
import { defineComponent, ref, reactive, toRaw, watchEffect } from 'vue';
import { useI18n } from 'vue-i18n';
import { CopyOutlined, EditOutlined, TagOutlined } from '@ant-design/icons-vue';
import useClipboard from 'vue-clipboard3';
import { message } from 'ant-design-vue';
import EditProductSetModal from './edit-product-set-modal.vue';
import TagModal from '../../utils/tag-modal.vue';
import { updateProductSetApi } from '@/api/fb_bms';

const formLayout = {
  labelCol: {
    xs: { span: 24 },
    sm: { span: 7 },
  },
  wrapperCol: {
    xs: { span: 24 },
    sm: { span: 13 },
  },
};

interface TagAction {
  data_source: any[];
}

export default defineComponent({
  name: 'productSetListModal',
  components: {
    // SharePixelModal,
    CopyOutlined,
    EditOutlined,
    EditProductSetModal,
    TagOutlined,
    TagModal,
  },
  props: {
    visible: {
      type: Boolean,
      required: true,
    },
    model: {
      type: Object as PropType<TagAction | null>,
      default: () => null,
    },
    canShare: {
      type: Boolean,
      default: true,
    },
  },
  emits: ['ok', 'cancel'],
  setup(props, { emit }) {
    const { t } = useI18n();
    const loading = ref(false);
    const initValues = () => ({
      data_source: [],
    });
    const modelRef = reactive<TagAction>(initValues());
    const { toClipboard } = useClipboard();

    const isModalVisible = ref(false);
    const currentFilter = ref(null);
    const currentProductSetId = ref(null);
    const productList = ref(null);
    const productSetName = ref(null);
    const isSaving = ref(false);
    // 使用 InstanceType 获取子组件实例的准确类型
    const editModalRef = ref<InstanceType<typeof EditProductSetModal> | null>(null);

    watchEffect(() => {
      if (props.model) {
        const raw = toRaw(props.model);
        modelRef.data_source = raw.data_source;
        console.log('model ref');
        console.log(modelRef);
        console.log(raw);
      } else if (props.model === null) {
        Object.assign(modelRef, initValues());
      }
    });

    const handleSubmit = () => {
      // e.preventDefault();
      // loading.value = true;
      emit('ok');
    };

    const columns = [
      {
        title: t('pages.adc.index'),
        dataIndex: 'index',
        customRender: ({ index }) => {
          return `${index + 1}`;
        },
        width: 60,
        align: 'center',
      },
      {
        title: t('ID'),
        dataIndex: 'source_id',
        width: 180,
        align: 'center',
        resizable: true,
      },
      {
        title: t('Name'),
        dataIndex: 'name',
        width: 150,
        align: 'center',
        resizable: true,
        ellipsis: true,
      },
      {
        title: t('Filter'),
        dataIndex: 'filter',
        minWidth: 50,
        align: 'center',
        resizable: true,
        ellipsis: true,
      },
      {
        title: t('pages.tag'),
        dataIndex: 'tags',
        align: 'center',
        width: 100,
        resizable: true,
        ellipsis: true,
      },
      {
        title: t('pages.operation'),
        width: 100,
        dataIndex: 'operation',
        align: 'center',
        fixed: 'right',
      },
    ];

    const copyCell = async (text: any) => {
      try {
        await toClipboard(text);
        message.success('copied');
      } catch (e) {
        console.error(e);
      }
    };

    const onEdit = record => {
      console.log('edit product set');
      currentFilter.value = JSON.parse(JSON.stringify(record.filter)); // 深拷贝
      currentProductSetId.value = record.id;
      isModalVisible.value = true;
      productList.value = modelRef.data_source['products'];
      productSetName.value = record.name;
      console.log(productSetName.value, productList.value, currentFilter.value);
    };

    const updateFilter = newFilter => {
      console.log('new filter');
      currentFilter.value = newFilter;
    };

    const handleOk = async () => {
      // 确认更新后的逻辑 (保存到数据源)
      // 检查子组件 ref 和当前编辑项 ID 是否存在
      if (!editModalRef.value) {
        console.error('无法获取子组件实例或编辑项 ID。');
        message.error('操作失败，请重试。');
        console.log(editModalRef);
        return;
      }
      isSaving.value = true; // 开始 loading

      try {
        // 1. 调用子组件的验证和数据获取方法
        const formData = await editModalRef.value.getFormDataAndValidate();
        console.log(formData);
        console.log(modelRef.data_source);
        if (formData) {
          const params = {
            bm_id: modelRef.data_source['fb_bm_id'],
            product_set_id: currentProductSetId.value,
            ...formData,
          };
          console.log(params);
          updateProductSetApi(params)
            .then(() => {
              message.success(t('pages.op.successfully'));
              editModalRef.value.resetFields();
              isModalVisible.value = false;
            })
            .catch(err => {
              editModalRef.value.resetFields();
              message.error(t('pages.op.failed'));
              message.error(err.response.data.message);
            });
        }
      } catch (error) {
        console.error('保存过程中发生错误:', error);
        message.error('发生未知错误，请稍后重试。');
        // 保留 Modal 打开
      } finally {
        isSaving.value = false; // 无论成功或失败，结束 loading
      }
    };

    const handleCancel = () => {
      isModalVisible.value = false;
      console.log('cancel');
    };

    // tagModal
    const tagModal = reactive({
      visible: false,
      model: null,
    });
    const showTagModal = record => {
      tagModal.model = {
        ids: [record['id']],
        action: 'add',
        tagList: record['tags']?.map(item => item.name),
        modelType: 'fbcatalogproductset',
      };
      tagModal.visible = true;
    };

    return {
      ...formLayout,
      initValues,
      modelRef,
      loading,
      handleSubmit,
      emit,
      t,
      columns,
      copyCell,

      // edit modal
      isModalVisible,
      currentFilter,
      onEdit,
      updateFilter,
      handleOk,
      handleCancel,
      productList,
      productSetName,
      isSaving,
      editModalRef,

      // tag modal
      tagModal,
      showTagModal,
    };
  },
});
</script>
