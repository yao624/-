<template>
  <a-form :model="form" :form="form" ref="formRef">
    <a-form-item :label="$t('change.pwd.currentpwd')" name="currentPassword" :rules="passwordRules">
      <a-input-password v-model:value="form.currentPassword" />
    </a-form-item>
    <a-form-item :label="$t('change.pwd.newpwd')" name="newPassword" :rules="passwordRules">
      <a-input-password v-model:value="form.newPassword" />
    </a-form-item>
    <a-form-item
      :label="$t('change.pwd.confirmpwd')"
      name="confirmNewPassword"
      :rules="passwordRules"
    >
      <a-input-password v-model:value="form.confirmNewPassword" />
    </a-form-item>
    <a-button type="primary" @click="handleSubmit">{{ $t('submit') }}</a-button>
  </a-form>
</template>

<script lang="ts">
import { defineComponent, ref } from 'vue';
import type { ChangePwdParams } from '@/api/user/password';
import { changePwd } from '@/api/user/password';
import { message } from 'ant-design-vue';
import { useI18n } from 'vue-i18n';
import { useRouter } from 'vue-router';
export default defineComponent({
  setup() {
    const router = useRouter();
    const { t } = useI18n();
    const form = ref({
      currentPassword: '',
      newPassword: '',
      confirmNewPassword: '',
    });

    const passwordRules = [
      { required: true, message: t('change.pwd.plsEnterpwd'), trigger: 'blur' },
      { min: 8, message: t('change.pwd.length.min8'), trigger: 'blur' },
    ];
    const formRef = ref<any>();
    const handleSubmit = () => {
      formRef.value.validateFields().then(() => {
        const params: ChangePwdParams = {
          old_password: form.value.currentPassword,
          new_password: form.value.newPassword,
          new_password2: form.value.confirmNewPassword,
        };
        if (
          params.new_password === params.new_password2 &&
          params.new_password !== params.old_password
        ) {
          changePwd(params)
            .then(() => {
              router.push({ path: '/user/login' });
            })
            .catch(err => {
              console.log(err);
              message.error(err?.response?.data?.message);
            });
        } else {
          message.error(t('change.pwd.alert'));
        }
      });
    };

    return {
      form,
      formRef,
      passwordRules,
      handleSubmit,
    };
  },
});
</script>
