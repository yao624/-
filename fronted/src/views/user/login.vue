<template>
  <div class="login-page">
    <div class="login-left">
      <div class="left-overlay">
        <div class="brand">Firefly Ads</div>
        <h1 class="title">{{ t('pages.login.hero.title') }}</h1>
        <p class="subtitle">{{ t('pages.login.hero.subtitle') }}</p>
        <div class="feature-and-trusted">
          <ul class="feature-list">
            <li class="trusted-card-item item1">
              <div class="avatars">
                <span class="avatar a1"></span>
                <span class="avatar a2"></span>
                <span class="avatar a3"></span>
              </div>
              <div>
                <div class="trusted-title">{{ t('pages.login.hero.feature1') }}</div>
                <div class="trusted-sub">{{ t('pages.login.hero.feature1Desc') }}</div>
              </div>
            </li>
            <li class="trusted-card-item item2">
              <div class="avatars">
                <span class="avatar a1"></span>
                <span class="avatar a2"></span>
                <span class="avatar a3"></span>
              </div>
              <div>
                <div class="trusted-title">{{ t('pages.login.hero.feature2') }}</div>
                <div class="trusted-sub">{{ t('pages.login.hero.feature2Desc') }}</div>
              </div>
            </li>
            <li class="trusted-card-item item3">
              <div class="avatars">
                <span class="avatar a1"></span>
                <span class="avatar a2"></span>
                <span class="avatar a3"></span>
              </div>
              <div>
                <div class="trusted-title">{{ t('pages.login.hero.feature3') }}</div>
                <div class="trusted-sub">{{ t('pages.login.hero.feature3Desc') }}</div>
              </div>
            </li>
            <li class="trusted-card-item item4">
              <div class="avatars">
                <span class="avatar a1"></span>
                <span class="avatar a2"></span>
                <span class="avatar a3"></span>
              </div>
              <div>
                <div class="trusted-title">{{ t('pages.login.hero.trustedTitle') }}</div>
                <div class="trusted-sub">{{ t('pages.login.hero.trustedSub') }}</div>
              </div>
            </li>
          </ul>
        </div>
      </div>
    </div>

    <div class="login-right">
      <div class="lang-switch">
        <a-space size="small">
          <global-outlined />
          <a-typography-link :class="{ active: currentLang === 'zh-CN' }" @click="switchLang('zh-CN')">
            中文
          </a-typography-link>
          <span>/</span>
          <a-typography-link :class="{ active: currentLang === 'en-US' }" @click="switchLang('en-US')">
            EN
          </a-typography-link>
        </a-space>
      </div>

      <div class="form-card">
        <div class="auth-label">{{ t('pages.login.authPortal') }}</div>
        <h2 class="welcome">{{ t('pages.login.welcomeBack') }}</h2>
        <a-alert
          v-if="isLoginError"
          type="error"
          show-icon
          style="margin-bottom: 20px"
          :message="t('pages.login.accountLogin.errorMessage')"
        />

        <a-form id="formLogin" layout="vertical" class="user-layout-login">
          <a-form-item :label="t('pages.login.emailLabel')" v-bind="validateInfos.username">
            <a-input
              size="large"
              v-model:value="modelRef.username"
              :placeholder="t('pages.login.username.placeholder')"
            >
              <template #prefix>
                <user-outlined class="prefixIcon" />
              </template>
            </a-input>
          </a-form-item>

          <a-form-item :label="t('pages.login.passwordLabel')" v-bind="validateInfos.password">
            <a-input-password
              v-model:value="modelRef.password"
              size="large"
              :placeholder="t('pages.login.password.placeholder')"
            >
              <template #prefix>
                <lock-outlined class="prefixIcon" />
              </template>
            </a-input-password>
          </a-form-item>

          <div class="forgot-wrap">
            <a-typography-link>{{ t('pages.login.forgotPassword') }}</a-typography-link>
          </div>

          <a-form-item style="margin-top: 16px; margin-bottom: 18px">
            <a-button
              size="large"
              type="primary"
              html-type="submit"
              class="login-button"
              :loading="loginBtn"
              @click="handleSubmit"
            >
              {{ t('pages.login.submitButton') }}
            </a-button>
          </a-form-item>

          <div class="register-row">
            <span>{{ t('pages.login.newTo') }}</span>
            <a-typography-link>{{ t('pages.login.createAccount') }}</a-typography-link>
          </div>
        </a-form>

        <div class="copyright">{{ t('pages.login.copyright') }}</div>
      </div>
    </div>
  </div>
</template>

<script lang="ts">
import { computed, defineComponent, reactive, toRefs } from 'vue';
import { notification, Form } from 'ant-design-vue';
import { UserOutlined, LockOutlined, GlobalOutlined } from '@ant-design/icons-vue';

import { useRouter } from 'vue-router';
import type { RequestError } from '@/utils/request';
import { useUserStore } from '@/store/user';
// import { postAccountLoginApi } from '@/api/user/login';
import { useI18n } from 'vue-i18n';
import { useAppStore } from '@/store/app';
import type { Locale } from '@/locales';

export default defineComponent({
  name: 'Login',
  setup() {
    const { t } = useI18n();
    const router = useRouter();
    const userStore = useUserStore();
    const appStore = useAppStore();
    const state = reactive({
      loginBtn: false,
      // login type: 0 email, 1 username, 2 telephone
      loginType: 0,
      isLoginError: false,
    });
    const currentLang = computed(() => appStore.lang || 'en-US');

    const handleUsernameOrEmail = (_rule: any, value: any) => {
      return new Promise(resolve => {
        const regex = /^([a-zA-Z0-9_-])+@([a-zA-Z0-9_-])+((\.[a-zA-Z0-9_-]{2,3}){1,2})$/;
        if (regex.test(value)) {
          state.loginType = 0;
        } else {
          state.loginType = 1;
        }
        resolve(state.loginType);
      });
    };
    const modelRef = reactive({
      rememberMe: true,
      username: '',
      password: '',
    });
    const rulesRef = reactive({
      rememberMe: undefined,
      username: [
        { required: true, message: t('pages.login.username.required'), type: 'string' },
        { validator: handleUsernameOrEmail, trigger: 'change' },
      ],
      password: [
        { required: true, message: t('pages.login.password.required'), type: 'string', trigger: ['blur', 'change'] },
      ],
    });
    const { validateInfos, validate } = Form.useForm(modelRef, rulesRef);

    const requestFailed = (err: RequestError) => {
      state.isLoginError = true;
      notification.error({
        message: t('pages.login.errorTitle'),
        description: ((err.response || {}).data || {}).errorMessage || t('pages.login.errorDescription'),
        duration: 4,
      });
    };

    const loginSuccess = () => {
      router.push('/');
      // 延迟 1 秒显示欢迎信息
      setTimeout(() => {
        notification.success({
          message: t('pages.login.successTitle'),
          description: t('pages.login.successDescription'),
        });
      }, 1000);
      state.isLoginError = false;
    };

    // const loginSubmit = () => {
    //   // 发起 AJAX 请求到后端
    //   postAccountLoginApi({
    //     username: modelRef.username,
    //     password: modelRef.password,
    //   })
    //     .then((res: any) => {
    //     })
    //     .finally(() => {
    //     });
    // };

    const switchLang = (lang: Locale) => {
      appStore.SET_LANG(lang);
    };

    const handleSubmit = (e: Event) => {
      e.preventDefault();
      state.loginBtn = true;
      validate(['username', 'password'])
        .then(values => {
          userStore
            .LOGIN({
              ...values,
              type: true,
            })
            .then(() => {
              loginSuccess();
            })
            .catch(err => {
              requestFailed(err);
            })
            .finally(() => {
              state.loginBtn = false;
            });
        })
        .catch((/*err*/) => {
          // 屏蔽错误处理
          // requestFailed(err);
          state.loginBtn = false;
        });
    };
    // this.loginBtn = false;
    // this.stepCaptchaVisible = false;

    return {
      t,
      currentLang,
      ...toRefs(state),
      modelRef,
      validateInfos,

      switchLang,
      handleSubmit,
    };
  },
  components: {
    UserOutlined,
    LockOutlined,
    GlobalOutlined,
  },
});
</script>

<style lang="less" scoped>
.login-page {
  display: flex;
  min-height: 100vh;
  background: #fff;
}

.login-left {
  flex: 0 0 60%;
  background: url('../../assets/user/login_bg.png') center center / cover no-repeat;
  position: relative;
  border-right: 1px solid #e8ebf5;
}

.left-overlay {
  display: flex;
  flex-direction: column;
  justify-content: space-between;
  height: 100%;
  padding: 150px 80px 44px;
  color: #fff;
  background: 
    linear-gradient(to right, 
      rgb(0 86 193 / 0.4) 0%, 
      rgb(247 245 255 / 0.2) 100%
    ),
    linear-gradient(135deg, rgba(15, 23, 42, 0.8) 0%, rgba(30, 41, 59, 0.6) 100%);
}

.brand {
  font-size: 40px;
  font-weight: 700;
  color: #f0f4ff;
}

.title {
  margin: 72px 0 24px;
  color: #f0f4ff;
  font-size: 68px;
  line-height: 1.18;
  white-space: pre-line;
}

.subtitle {
  margin: 0 0 26px;
  max-width: 560px;
  color: rgba(240, 244, 255, 0.9);
  font-size: 30px;
  line-height: 1.45;
}

.feature-and-trusted {
  display: flex;
  gap: 24px;
  align-items: flex-start;
  margin-top: auto;
  margin-bottom: 0;
  
  .feature-list {
    flex: 1;
    margin: 0;
    padding: 0;
    list-style: none;
    display: flex;
    flex-wrap: wrap;
    gap: 16px;
    
    li {
      flex: 0 0 calc(50% - 8px);
      margin: 0;
      padding: 20px 24px;
      border-radius: 16px;
      background: rgb(255 255 255 / 0.1);
      backdrop-filter: blur(12px);
      -webkit-backdrop-filter: blur(12px);
      border: 1px solid rgb(255 255 255 / 0.1);
      box-shadow: 
        0 4px 24px rgb(0 0 0 / 0.15),
        inset 0 1px 0 rgb(255 255 255 / 0.05);
      display: flex;
      align-items: flex-start;
      gap: 16px;
    }
    
    .check-icon {
      width: 24px;
      height: 24px;
      flex-shrink: 0;
      margin-top: 2px;
      background: url('data:image/svg+xml;base64,PHN2ZyB3aWR0aD0iMjQiIGhlaWdodD0iMjQiIHZpZXdCb3g9IjAgMCAyNCAyNCIgeG1sbnM9Imh0dHA6Ly93d3cudzMub3JnLzIwMDAvc3ZnIj48cGF0aCBkPSJNMTIgMkMxNi40MTgxOCAyIDIwIDUuNTgyMDkgMjAgMTBDMjAgMTQuNDE4IDE2LjQxODE4IDE4IDEyIDE4QzcuNTgxODE4IDE4IDQgMTQuNDE4IDQgMTBDNCA1LjU4MjA5IDcuNTgxODE4IDIgMTIgMlptMC4xMTgxODE4IDE1LjY3NzY4bC02LjM2MzY0LTYuMzYzNjRjLS4zOTAuMzktLjk5LjM5LTEuMzggMEwtMi45OTY1NCAxMC40MzQ2Yy0uMzguMzgtLjM4IDEuMDI0IDAgMS40MDhDNi4wOTI4MzQgMTEuOTQyIDEyIDYuMDY1NDEgMTIgNi4wNjU0MWM2LjAzMzg1IDAgMTEuOTQyIDYuMDY1NDEgMTEuOTQyIDExLjk0MnMtNi4wMDggMTEuOTQyLTExLjk0MiAxMS45NDJjMC4wMDkgMC4wMDkgMC4wMTggMCAwLjAyN2wtMS4yNDM1IDkuMjg2MzZjLS4wMDkuMDc1LS4wODIuMTI2LS4xNTcuMTI2cy0uMTQ4LS4wNTItLjE1Ny0uMTI2bC0xLjI1MzUtOS4yODgzYy0uMDcuMDcyLS4xNDkuMTE0LS4yMzMuMTE0cy0uMTY0LS4wNDItLjIzMy0uMTE0Yy0uMzgtLjM4LS4zOC0xLjAyNCAwLTEuNDA4bDEuNTQxLTEuNTQxYzMuODAzOS0zLjgwMzkgMTAuMjA0LTQuMDQ4MSAxNC4wMDgtLjI0NDJjLjM4LjM4LjM4IDEuMDI0IDAgMS40MDhMMTIuMTg4IDE3LjY3Nzh6IiBmaWxsPSIjRjBGNDRGRCIgZmlsbC1vcGFjaXR5PSIwLjgiLz48L3N2Zz4=') no-repeat center center;
    }
    
    .feature-title {
      font-size: 24px;
      font-weight: 600;
      color: rgba(240, 244, 255, 0.95);
      margin-bottom: 8px;
    }
    
    .feature-desc {
      font-size: 16px;
      line-height: 1.5;
      color: rgba(240, 244, 255, 0.7);
    }
  }
  
  .trusted-card {
    flex: 0 0 320px;
    margin: 0;
  }
}

@media (max-width: 1200px) {
  .login-left {
    flex: 0 0 55%;
  }
  .title {
    font-size: 56px;
  }
  .subtitle {
    font-size: 26px;
  }
  .feature-list {
    font-size: 30px;
  }
}

@media (max-width: 992px) {
  .login-left {
    flex: 0 0 50%;
  }
  .left-overlay {
    padding: 48px 60px 32px;
    justify-content: center;
  }
  .brand {
    font-size: 32px;
  }
  .title {
    font-size: 48px;
    margin: 48px 0 20px;
  }
  .subtitle {
    font-size: 22px;
    margin: 0 0 20px;
  }
  .feature-list {
    font-size: 24px;
  }
}

@media (max-width: 768px) {
  .login-page {
    flex-direction: column;
  }
  .login-left {
    flex: 0 0 auto;
    min-height: 45vh;
    border-right: none;
    border-bottom: 1px solid #e8ebf5;
  }
  .left-overlay {
    padding: 32px 32px 24px;
    justify-content: flex-start;
  }
  .brand {
    font-size: 28px;
  }
  .title {
    font-size: 36px;
    margin: 32px 0 16px;
  }
  .subtitle {
    font-size: 18px;
    max-width: 100%;
  }
  .feature-list {
    font-size: 18px;
  }
  .trusted-card {
    width: 100%;
    padding: 12px 16px;
  }
  .trusted-title {
    font-size: 20px;
  }
  .trusted-sub {
    font-size: 16px;
  }
}

@media (max-width: 576px) {
  .login-left {
    min-height: 40vh;
  }
  .brand {
    font-size: 24px;
  }
  .title {
    font-size: 28px;
  }
  .subtitle {
    font-size: 16px;
  }
  .feature-list {
    font-size: 16px;
    padding-left: 16px;
  }
  .login-right {
    flex: 1;
  }
  .lang-switch {
    top: 16px;
    right: 20px;
  }
  .form-card {
    width: calc(100% - 40px);
    padding: 0 20px;
  }
  .auth-label {
    font-size: 11px;
  }
  .welcome {
    font-size: 32px;
    margin-bottom: 20px;
  }
  .user-layout-login {
    :deep(label) {
      font-size: 14px;
    }
    :deep(.ant-input-affix-wrapper),
    :deep(.ant-input) {
      height: 44px;
      font-size: 14px;
    }
    :deep(.ant-input-affix-wrapper input.ant-input) {
      line-height: 44px !important;
      padding-top: 0 !important;
      padding-bottom: 0 !important;
    }
  }
  button.login-button {
    height: 44px;
    font-size: 16px;
  }
  .register-row {
    font-size: 12px;
  }
  .copyright {
    font-size: 9px;
    margin-top: 24px;
  }
}

.trusted-card {
  margin-top: auto;
  width: 470px;
  max-width: 100%;
  display: flex;
  align-items: center;
  gap: 18px;
  padding: 20px 24px;
  border-radius: 20px;
  background: rgb(255 255 255 / 0.15);
  backdrop-filter: blur(12px);
  -webkit-backdrop-filter: blur(12px);
  border: 1px solid rgb(255 255 255 / 0.2);
  box-shadow: 
    0 4px 24px rgb(0 0 0 / 0.2),
    inset 0 1px 0 rgb(255 255 255 / 0.1);
}

.trusted-card-item {
  flex: 0 0 calc(50% - 8px);
  margin: 0;
  padding: 16px 20px;
  border-radius: 16px;
  background: rgb(255 255 255 / 0.15);
  backdrop-filter: blur(12px);
  -webkit-backdrop-filter: blur(12px);
  border: 1px solid rgb(255 255 255 / 0.2);
  box-shadow: 
    0 4px 24px rgb(0 0 0 / 0.2),
    inset 0 1px 0 rgb(255 255 255 / 0.1);
  display: flex;
  align-items: center;
  gap: 14px;
}

.avatars {
  position: relative;
  width: 100px;
  height: 40px;
  flex-shrink: 0;
}

.avatar {
  position: absolute;
  top: 50%;
  transform: translateY(-50%);
  width: 40px;
  height: 40px;
  border-radius: 50%;
  border: 2px solid #1e293b;
  box-shadow: 
    0 4px 12px rgba(0, 0, 0, 0.4),
    0 2px 4px rgba(0, 0, 0, 0.3),
    inset 0 2px 4px rgba(255, 255, 255, 0.2);
}

.a1 {
  left: 0;
  z-index: 3;
  background: linear-gradient(135deg, #f4b183, #d66c33);
}

.a2 {
  left: 28px;
  z-index: 2;
  background: linear-gradient(135deg, #86a7ff, #4e67d8);
}

.a3 {
  left: 56px;
  z-index: 1;
  background: linear-gradient(135deg, #8ad8a1, #3ea75f);
}

.item1 .a1 { left: 0; background: linear-gradient(135deg, #ff9a56, #ff6b35); }
.item1 .a2 { left: 20px; background: linear-gradient(135deg, #ffb347, #ffcc33); }
.item1 .a3 { left: 40px; background: linear-gradient(135deg, #f6d365, #fda085); }

.item2 .a1 { left: 0; background: linear-gradient(135deg, #a18cd1, #fbc2eb); }
.item2 .a2 { left: 20px; background: linear-gradient(135deg, #84fab0, #8fd3f4); }
.item2 .a3 { left: 40px; background: linear-gradient(135deg, #e0c3fc, #8ec5fc); }

.item3 .a1 { left: 0; background: linear-gradient(135deg, #ff9a9e, #fecfef); }
.item3 .a2 { left: 20px; background: linear-gradient(135deg, #fbc2eb, #a6c1ee); }
.item3 .a3 { left: 40px; background: linear-gradient(135deg, #ffecd2, #fcb69f); }

.item4 .a1 { left: 0; background: linear-gradient(135deg, #f4b183, #d66c33); }
.item4 .a2 { left: 28px; background: linear-gradient(135deg, #86a7ff, #4e67d8); }
.item4 .a3 { left: 56px; background: linear-gradient(135deg, #8ad8a1, #3ea75f); }

.trusted-title {
  font-size: 20px;
  font-weight: 600;
  color: #ffffff;
  line-height: 1.3;
}

.trusted-sub {
  color: rgba(255, 255, 255, 0.65);
  font-size: 14px;
  line-height: 1.4;
  margin-top: 2px;
}

.login-right {
  flex: 1;
  position: relative;
  display: flex;
  align-items: center;
  justify-content: center;
  background: #fff;
}

.lang-switch {
  position: absolute;
  top: 26px;
  right: 40px;
  color: #1b2456;
  font-size: 14px;
  cursor: pointer;
  user-select: none;
  
  :deep(.ant-typography) {
    cursor: pointer;
    transition: all 0.3s ease;
    padding: 4px 8px;
    border-radius: 4px;
    
    &:hover {
      opacity: 0.8;
    }
  }
  
  :deep(.active) {
    color: #1677ff;
    font-weight: 600;
  }
  
  :deep(.lang-divider) {
    margin: 0 4px;
    color: #c0c3ce;
  }
}

.form-card {
  width: min(460px, calc(100% - 80px));
}

.auth-label {
  margin-bottom: 8px;
  color: #6f7486;
  font-size: 13px;
  letter-spacing: 1.2px;
  text-transform: uppercase;
}

.welcome {
  margin-bottom: 26px;
  color: #1b2456;
  font-size: 52px;
  font-weight: 700;
}

.user-layout-login {
  :deep(label) {
    color: #1b2456;
    font-size: 19px;
    letter-spacing: 0.8px;
    text-transform: uppercase;
  }
  :deep(.ant-form-item) {
    margin-bottom: 14px;
  }
  :deep(.ant-input-prefix) {
    /* Ensure prefix icon is vertically centered with input text */
 
    display: flex;
    align-items: center;
  }
  :deep(.ant-input-affix-wrapper),
  :deep(.ant-input) {
  
    border-radius: 10px;
    background: #f1f3ff;
    border: none;
    box-shadow: none;
    font-size: 18px;
  }
  :deep(.ant-input-affix-wrapper input.ant-input) {
    background: transparent;
    /* Make the text baseline align to the same vertical center as prefix icon */
    // line-height: 56px !important;
    padding-top: 0 !important;
    padding-bottom: 0 !important;
  }
}

.prefixIcon {
  color: #8087a7;
  /* Ant Design Vue icon uses font-size; using it improves visual centering */
  font-size: 18px;
  line-height: 1;
}

.forgot-wrap {
  margin-top: 4px;
  text-align: right;
  font-size: 14px;
}

button.login-button {
  width: 100%;
  height: 58px;
  border-radius: 10px;
  font-size: 29px;
  font-weight: 600;
}

.register-row {
  margin-top: 6px;
  display: flex;
  justify-content: center;
  gap: 8px;
  color: #7a7f94;
  font-size: 14px;
}

.copyright {
  margin-top: 44px;
  color: #c0c3ce;
  font-size: 11px;
  letter-spacing: 0.6px;
  text-transform: uppercase;
}
</style>
