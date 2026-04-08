<template>
  <a-modal
    :title="t('pages.adc.action.sys-user')"
    :open="visible"
    :width="600"
    :confirmLoading="loading"
    @ok="handleSubmit"
    @cancel="
      () => {
        resetFields();
        $emit('cancel');
      }
    "
  >
    <a-spin :spinning="loading">
      <a-form :label-col="labelCol" :wrapper-col="wrapperCol">
        <a-form-item label="操作">
          <a-radio-group v-model:value="modelRef.action" button-style="solid">
            <a-radio-button value="assign">
              {{ t('pages.adc.action.sys-user.assign') }}
            </a-radio-button>
            <a-radio-button value="remove">
              {{ t('pages.adc.action.sys-user.remove') }}
            </a-radio-button>
          </a-radio-group>
        </a-form-item>
        <a-form-item label="Users">
          <a-select
            v-model:value="selectedUsersIds"
            mode="multiple"
            style="width: 100%"
            placeholder="Please"
          >
            <a-select-option v-for="user in modelRef.userList" :key="user.id" :value="user.id">
              {{ user.name }}
            </a-select-option>
          </a-select>
        </a-form-item>
      </a-form>
    </a-spin>
  </a-modal>
</template>

<script lang="ts">
import { Form, message } from 'ant-design-vue';
import type { PropType } from 'vue';
import { defineComponent, ref, reactive, toRaw, watchEffect } from 'vue';
import { assignUsers, removeUsers } from '@/api/fb_ad_accounts';
import { useI18n } from 'vue-i18n';

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

interface User {
  id: string | number;
  name: string;
}

interface TagAction {
  adAccountIds?: string[];
  action?: string;
  userList: User[];
}

export default defineComponent({
  name: 'UserModal',
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
      adAccountIds: [],
      action: 'assign',
      userList: [] as any[],
    });
    const modelRef = reactive<TagAction>(initValues());

    const rulesRef = reactive({
      // 注意如果数据类型不相同，一定要指定数据类型 `type` 否则会校验失败
    });

    watchEffect(() => {
      if (props.model) {
        const raw = toRaw(props.model);
        modelRef.action = raw.action;
        modelRef.userList = raw.userList;
        modelRef.adAccountIds = raw.adAccountIds;
        console.log('model ref');
        console.log(modelRef);
      } else if (props.model === null) {
        Object.assign(modelRef, initValues());
      }
    });

    const { resetFields, validate, validateInfos } = Form.useForm(modelRef, rulesRef);
    const handleSubmit = (e: Event) => {
      e.preventDefault();
      loading.value = true;
      validate()
        .then(() => {
          console.log(modelRef.action);
          const data = toRaw(modelRef);
          console.log(data);
          const reqData = {
            ad_account_ids: data['adAccountIds'],
            user_ids: selectedUsersIds.value,
          };

          if (modelRef.action === 'assign') {
            assignUsers(reqData)
              .then(res => {
                message.success(t('pages.op.successfully'));
                loading.value = false;
                resetFields();
                emit('ok', res);
              })
              .catch(err => {
                message.success(t('pages.op.failed'));
                message.error(err.response.data.message);
                loading.value = false;
              });
          } else if (modelRef.action === 'remove') {
            removeUsers(reqData)
              .then(res => {
                message.success(t('pages.op.successfully'));
                loading.value = false;
                resetFields();
                emit('ok', res);
              })
              .catch(err => {
                message.success(t('pages.op.failed'));
                message.error(err.response.data.message);
                loading.value = false;
              });
          }

          // manageTags(modelRef.modelType, {
          //   action: data.action,
          //   names: data.tags,
          //   ids: data.ids,
          // })
          //   .then(res => {
          //     message.success('修改成功');
          //     loading.value = false;
          //     resetFields();
          //     emit('ok', res);
          //   })
          //   .catch(err => {
          //     message.error(err.response.data.message);
          //     loading.value = false;
          //   });
        })
        .catch(err => {
          console.log('error', err);
          loading.value = false;
        });
    };

    const selectedUsersIds = ref([]);

    return {
      ...formLayout,
      initValues,
      modelRef,
      loading,
      handleSubmit,
      resetFields,
      validateInfos,
      emit,
      t,
      selectedUsersIds,
    };
  },
});
</script>
