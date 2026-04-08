<template>
  <a-result status="success" :title="t('pages.op.successfully')"></a-result>
  <div class="center">
    <a-button>{{ t('pages.ad.accounts.launch.check.status') }}</a-button>
    <a-button type="primary" @click="resetLaunchAD">
      {{ t('pages.ad.accounts.launch.create.new.campaign') }}
    </a-button>
  </div>
</template>
<script lang="ts">
import { defineComponent } from 'vue';
import { useI18n } from 'vue-i18n';

export default defineComponent({
  emits: ['createNewCampaign'],
  setup(_, { emit }) {
    const { t } = useI18n();
    const resetLaunchAD = () => {
      emit('createNewCampaign');
    };
    return {
      t,
      resetLaunchAD,
    };
  },
});
</script>
