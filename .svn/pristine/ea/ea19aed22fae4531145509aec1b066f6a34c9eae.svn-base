<template>
  <a-modal
    title="分配用户"
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
        <a-form-item label="用户列表">
          <a-select v-model:value="modelRef.user_id" placeholder="请选择">
            <a-select-option v-for="user in userList" :key="user.id">
              {{ user.name }}
            </a-select-option>
          </a-select>
        </a-form-item>
      </a-form>
    </a-spin>
  </a-modal>
</template>

<script lang="ts">
import { assignUser } from '@/api/fb_accounts';
import { getUsers } from '@/api/user/role_v2';
import { Form, message } from 'ant-design-vue';
import type { PropType } from 'vue';
import { defineComponent, ref, reactive, toRaw, watchEffect, onMounted } from 'vue';

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

interface AssignUserAction {
  ids?: string[];
  user_id?: string;
}

export default defineComponent({
  name: 'AssignUserModal',
  props: {
    visible: {
      type: Boolean,
      required: true,
    },
    model: {
      type: Object as PropType<AssignUserAction | null>,
      default: () => null,
    },
  },
  emits: ['ok', 'cancel'],
  setup(props, { emit }) {
    const loading = ref(false);
    const initValues = () => ({
      ids: [],
      user_id: '',
    });

    const userList = ref([]);

    const modelRef = reactive<AssignUserAction>(initValues());

    const rulesRef = reactive({
      // 注意如果数据类型不相同，一定要指定数据类型 `type` 否则会校验失败
      user_id: [{ required: true, message: '编码必须填写' }],
    });

    watchEffect(() => {
      if (props.model) {
        const raw = toRaw(props.model);
        modelRef.ids = raw.ids;
        console.log('model ref');
        console.log(modelRef);
        getUsers()
          .then(res => {
            userList.value = res.data;
            console.log('user list:');
            console.log(userList.value);
          })
          .catch(() => {
            message.error('获取用户列表失败');
          });
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
          const data = toRaw(modelRef);

          assignUser({
            user_id: data.user_id,
            fb_account_ids: data.ids,
          })
            .then(res => {
              message.success('分配用户成功');
              loading.value = false;
              resetFields();
              emit('ok', res);
            })
            .catch(err => {
              message.error(err.response.data.message);
              loading.value = false;
            });
        })
        .catch(err => {
          console.log('error', err);
          loading.value = false;
        });
    };

    onMounted(() => {});

    return {
      ...formLayout,
      initValues,
      modelRef,
      loading,
      handleSubmit,
      resetFields,
      validateInfos,
      userList,
    };
  },
});
</script>
