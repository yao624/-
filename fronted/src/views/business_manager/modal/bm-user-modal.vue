<template>
  <a-modal
    :title="t('People')"
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
      :data-source="model.data_source['users']"
      :row-key="record => record.id"
      bordered
      sticky
size="default"
    >
      <template #bodyCell="{ column, record }">
        <template v-if="column['dataIndex'] == 'assigned_ad_accounts'">
          <a-button type="link" @click="showAdAccountModal(record)">
            {{ record.assigned_ad_accounts?.length }}
          </a-button>
          <a-button type="link">
            <pick-edit-objects
              :columns="[
                { title: 'ID', dataIndex: 'source_id', key: 'source_id' },
                { title: 'Name', dataIndex: 'name', key: 'name' },
                { title: 'Status', dataIndex: 'account_status', key: 'account_status' },
                { title: 'Role', dataIndex: 'role', key: 'role' },
              ]"
              :allow-empty="true"
              :icon-type="3"
              :datasets="modelRef.data_source['ad_accounts']"
              :selected-keys="record.assigned_ad_accounts.map(item => item.id)"
              @confirm:items-selected="ids => saveUserAdAccount(ids, record)"
            />
          </a-button>
        </template>
        <template v-if="column['dataIndex'] == 'assigned_pages'">
          <a-button type="link" @click="showFbPageModal(record)">
            {{ record.assigned_pages?.length }}
          </a-button>
          <a-button type="link">
            <pick-edit-objects
              :columns="[
                { title: 'ID', dataIndex: 'source_id', key: 'source_id' },
                { title: 'Name', dataIndex: 'name', key: 'name' },
                { title: 'Role', dataIndex: 'role', key: 'role' },
              ]"
              :allow-empty="true"
              :icon-type="3"
              :datasets="modelRef.data_source['pages']"
              :selected-keys="record.assigned_pages.map(item => item.id)"
              @confirm:items-selected="ids => saveUserPage(ids, record)"
            />
          </a-button>
        </template>

        <!-- catalogs -->
        <template v-if="column['dataIndex'] == 'assigned_catalogs'">
          <a-button type="link" @click="showFbCatalogModal(record)">
            {{ record.assigned_catalogs?.length }}
          </a-button>
          <a-button type="link">
            <pick-edit-objects
              :columns="[
                { title: 'ID', dataIndex: 'source_id', key: 'source_id' },
                { title: 'Name', dataIndex: 'name', key: 'name' },
                { title: 'Role', dataIndex: 'role', key: 'role' },
              ]"
              :allow-empty="true"
              :icon-type="3"
              :datasets="modelRef.data_source['catalogs']"
              :selected-keys="record.assigned_catalogs.map(item => item.id)"
              @confirm:items-selected="ids => saveUserCatalog(ids, record)"
            />
          </a-button>
        </template>
      </template>
    </a-table>
  </a-modal>

  <ad-account-modal
    :model="adAccountModal.model"
    :visible="adAccountModal.visible"
    @cancel="
      () => {
        adAccountModal.visible = false;
      }
    "
    @ok="
      () => {
        adAccountModal.visible = false;
      }
    "
  />

  <fb-page-modal
    :model="fbPageModal.model"
    :visible="fbPageModal.visible"
    @cancel="
      () => {
        fbPageModal.visible = false;
      }
    "
    @ok="
      () => {
        fbPageModal.visible = false;
      }
    "
  />

  <fb-catalog-modal
    :model="fbCatalogModal.model"
    :visible="fbCatalogModal.visible"
    @cancel="
      () => {
        fbCatalogModal.visible = false;
      }
    "
    @ok="
      () => {
        fbCatalogModal.visible = false;
      }
    "
  />
</template>

<script lang="ts">
import type { PropType } from 'vue';
import { defineComponent, ref, reactive, toRaw, watchEffect, h } from 'vue';
import { useI18n } from 'vue-i18n';
import AdAccountModal from './ad-account-modal.vue';
import FbPageModal from './fb-page-modal.vue';
import FbCatalogModal from './fb-catalog-modal.vue';
import PickEditObjects from '@/views/utils/pick-edit-objects.vue';
import {
  manageBmUserAdAccApi,
  manageBmUserCatalogApi,
  manageBmUserPageApi,
  setOperatorApi,
} from '@/api/fb_bms';
import { message, Switch as ASwitch } from 'ant-design-vue';
import _ from 'lodash';

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
  data_source: any;
}

export default defineComponent({
  name: 'BmUserModal',
  components: {
    AdAccountModal,
    FbPageModal,
    PickEditObjects,
    FbCatalogModal,
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
  },
  emits: ['ok', 'cancel'],
  setup(props, { emit }) {
    const { t } = useI18n();
    const loading = ref(false);
    const initValues = () => ({
      data_source: {},
    });
    let all_ad_accounts = [];
    const modelRef = reactive<TagAction>(initValues());

    // 记录当前正在loading的用户ID
    const loadingOperator = ref<string | null>(null);

    watchEffect(() => {
      if (props.model) {
        const raw = toRaw(props.model);
        modelRef.data_source = raw.data_source;
        console.log('model ref');
        console.log(modelRef);
        all_ad_accounts = modelRef.data_source['ad_accounts'];
        console.log('all:', all_ad_accounts);
      } else if (props.model === null) {
        Object.assign(modelRef, initValues());
      }
    });

    const handleSubmit = () => {
      emit('ok');
    };

    const columns = [
      {
        title: t('pages.adc.index'),
        dataIndex: 'index',
        customRender: ({ index }) => {
          return `${index + 1}`;
        },
        width: 80,
        align: 'center',
      },
      {
        title: t('Name'),
        dataIndex: 'name',
        align: 'center',
        resizable: true,
      },
      {
        title: t('Email'),
        dataIndex: 'email',
        align: 'center',
        resizable: true,
      },
      {
        title: t('Role'),
        dataIndex: 'role',
        align: 'center',
        resizable: true,
      },
      {
        title: t('Operator'),
        dataIndex: 'is_operator',
        width: 100,
        align: 'center',
        customRender: ({ record }) => {
          return h(ASwitch, {
            checked: !!record.is_operator,
            loading: loadingOperator.value === record.id,
            disabled: record.user_type !== 'system_user',
            onChange: checked => onOperatorChange(Boolean(checked), record),
          });
        },
      },
      {
        title: t('Ad account'),
        dataIndex: 'assigned_ad_accounts',
        align: 'center',
        resizable: true,
      },
      {
        title: t('Page'),
        dataIndex: 'assigned_pages',
        align: 'center',
        resizable: true,
      },
      {
        title: t('Catalog'),
        dataIndex: 'assigned_catalogs',
        align: 'center',
        resizable: true,
      },
    ];

    // 处理操作员切换
    const onOperatorChange = async (checked: boolean, record: any) => {
      // 如果尝试取消操作员，忽略此操作
      if (!checked) {
        // 立即恢复开关状态
        setTimeout(() => {
          record.is_operator = true;
        }, 0);
        return;
      }

      // 设置加载状态
      loadingOperator.value = record.id;

      try {
        const bm_id = modelRef.data_source.id;
        const user_id = record.id;

        // 调用API设置操作员
        await setOperatorApi({ bm_id, user_id });
        message.success(t('Operator set successfully'));

        // 直接修改数据模型中各用户的operator状态
        const users = modelRef.data_source.users;
        if (users) {
          users.forEach(user => {
            user.is_operator = user.id === user_id;
          });
        }
      } catch (error) {
        message.error(t('Failed to set operator'));
        console.error('Error setting operator:', error);
        // 恢复之前的状态
        record.is_operator = false;
      } finally {
        loadingOperator.value = null;
      }
    };

    // ad account modal
    const adAccountModal = reactive({
      visible: false,
      model: null,
    });
    const showAdAccountModal = record => {
      const ids = record['assigned_ad_accounts'].map(item => item.id);
      const filterd = modelRef.data_source['ad_accounts'].filter(item => ids.includes(item.id));
      adAccountModal.model = {
        data_source: filterd,
      };
      adAccountModal.visible = true;
    };

    const saveUserAdAccount = (new_ids, record) => {
      const old_ids = record.assigned_ad_accounts.map(item => item.id);
      const bm_user_id = record['id'];
      const bm_id = modelRef.data_source.id;

      console.log('new ids:', new_ids);
      console.log('old ids: ', old_ids);

      const added_ids = new_ids.filter(id => !old_ids.includes(id));
      const deleted_ids = old_ids.filter(id => !new_ids.includes(id));

      const added_accs = modelRef.data_source['ad_accounts']
        .filter(item => added_ids.includes(item.id))
        .map(item => ({ id: item.id, role: item.role }));
      const deleted_accs = modelRef.data_source['ad_accounts']
        .filter(item => deleted_ids.includes(item.id))
        .map(item => ({ id: item.id, role: item.role }));

      const action = 'add';
      const req_body = {
        action,
        bm_user_id,
        bm_id,
        accs: added_accs,
      };

      if (added_ids.length > 0) {
        console.log('added: ', added_ids);
        console.log(action, req_body);
        manageBmUserAdAccApi(req_body)
          .then(() => {
            message.success(t('pages.op.successfully'));
          })
          .catch(err => {
            message.error(t('pages.op.failed'));
            console.log(err);
          });
      }

      if (deleted_ids.length > 0) {
        console.log('delete: ', deleted_ids);
        const action = 'delete';
        req_body['action'] = action;
        req_body['accs'] = deleted_accs;
        console.log(action, req_body);
        manageBmUserAdAccApi(req_body)
          .then(() => {
            message.success(t('pages.op.successfully'));
          })
          .catch(err => {
            message.error(t('pages.op.failed'));
            console.log(err);
          });
      }
    };

    // fb page modal
    const fbPageModal = reactive({
      visible: false,
      model: null,
    });
    const showFbPageModal = record => {
      const ids = record['assigned_pages'].map(item => item.id);
      const filterd = modelRef.data_source['pages'].filter(item => ids.includes(item.id));
      fbPageModal.model = {
        data_source: filterd,
      };
      fbPageModal.visible = true;
    };

    const saveUserPage = (new_ids, record) => {
      const old_ids = record.assigned_pages.map(item => item.id);
      const bm_user_id = record['id'];
      const bm_id = modelRef.data_source.id;

      console.log('new ids:', new_ids);
      console.log('old ids: ', old_ids);

      const added_ids = new_ids.filter(id => !old_ids.includes(id));
      const deleted_ids = old_ids.filter(id => !new_ids.includes(id));

      const added_pages = modelRef.data_source['pages']
        .filter(item => added_ids.includes(item.id))
        .map(item => ({ id: item.id, role: item.role }));

      const deleted_pages = modelRef.data_source['pages']
        .filter(item => deleted_ids.includes(item.id))
        .map(item => ({ id: item.id, role: item.role }));
      // console.log('added pages: ', added_pages);
      // console.log('delete pages:', deleted_pages);

      const req_body = {
        action: 'add',
        bm_user_id,
        bm_id,
        pages: added_pages,
      };

      if (added_ids.length > 0) {
        console.log('added: ', added_ids);
        manageBmUserPageApi(req_body)
          .then(() => {
            message.success(t('pages.op.successfully'));
          })
          .catch(err => {
            message.error(t('pages.op.failed'));
            console.log(err);
          });
      }
      if (deleted_ids.length > 0) {
        console.log('delete: ', deleted_ids);
        req_body['action'] = 'delete';
        req_body['pages'] = deleted_pages;
        console.log('delete', req_body);
        manageBmUserPageApi(req_body)
          .then(() => {
            message.success(t('pages.op.successfully'));
          })
          .catch(err => {
            message.error(t('pages.op.failed'));
            console.log(err);
          });
      }
    };

    // fb catalog list
    const fbCatalogModal = reactive({
      visible: false,
      model: null,
    });
    const showFbCatalogModal = record => {
      const ids = record['assigned_catalogs'].map(item => item.id);
      const filterd = modelRef.data_source['catalogs'].filter(item => ids.includes(item.id));
      const raw = toRaw(modelRef.data_source);
      const deepCopiedDataSource = _.cloneDeep(raw);
      deepCopiedDataSource['catalogs'] = filterd;
      fbCatalogModal.model = {
        data_source: deepCopiedDataSource,
      };
      fbCatalogModal.visible = true;
    };
    const saveUserCatalog = (new_ids, record) => {
      const old_ids = record.assigned_catalogs.map(item => item.id);
      const bm_user_id = record['id'];
      const bm_id = modelRef.data_source.id;

      console.log('new ids:', new_ids);
      console.log('old ids: ', old_ids);

      const added_ids = new_ids.filter(id => !old_ids.includes(id));
      const deleted_ids = old_ids.filter(id => !new_ids.includes(id));

      const added_items = modelRef.data_source['catalogs']
        .filter(item => added_ids.includes(item.id))
        .map(item => ({ id: item.id, role: item.role }));

      const deleted_items = modelRef.data_source['catalogs']
        .filter(item => deleted_ids.includes(item.id))
        .map(item => ({ id: item.id, role: item.role }));
      // console.log('added pages: ', added_pages);
      // console.log('delete pages:', deleted_pages);

      const req_body = {
        action: 'add',
        bm_user_id,
        bm_id,
        catalogs: added_items,
      };

      if (added_ids.length > 0) {
        console.log('added: ', added_ids);
        manageBmUserCatalogApi(req_body)
          .then(() => {
            message.success(t('pages.op.successfully'));
          })
          .catch(err => {
            message.error(t('pages.op.failed'));
            console.log(err);
          });
      }
      if (deleted_ids.length > 0) {
        console.log('delete: ', deleted_ids);
        req_body['action'] = 'delete';
        req_body['catalogs'] = deleted_items;
        console.log('delete', req_body);
        manageBmUserCatalogApi(req_body)
          .then(() => {
            message.success(t('pages.op.successfully'));
          })
          .catch(err => {
            message.error(t('pages.op.failed'));
            console.log(err);
          });
      }
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
      loadingOperator,
      onOperatorChange,

      // ad account modal
      adAccountModal,
      showAdAccountModal,

      all_ad_accounts,
      saveUserAdAccount,

      // fb page modal
      fbPageModal,
      showFbPageModal,
      saveUserPage,

      // fb catalog modal
      fbCatalogModal,
      showFbCatalogModal,
      saveUserCatalog,
    };
  },
});
</script>
