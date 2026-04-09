<template>
  <a-modal
    :open="open"
    :title="title"
    :confirm-loading="loading"
    width="920px"
    @cancel="$emit('cancel')"
    @ok="handleSubmit"
  >
    <a-form ref="formRef" :model="formState" :label-col="{ span: 4 }">
      <a-form-item name="link" :label="t('pages.links.link')" :rules="urlRules">
        <a-textarea v-model:value="formState.link" :auto-size="{ minRows: 2, maxRows: 4 }" />
      </a-form-item>
      <a-form-item name="notes" :label="t('pages.links.note')">
        <a-textarea v-model:value="formState.notes" :auto-size="{ minRows: 2, maxRows: 4 }" />
      </a-form-item>
      <a-form-item :label="t('pages.tag')">
        <a-select
          v-model:value="formState.tags"
          mode="tags"
          :options="tagOptions"
          :placeholder="t('pages.plsSelect')"
          style="width: 100%"
        />
      </a-form-item>
      <a-form-item label="默认语言">
        <a-input v-model:value="formState.default_locale" placeholder="en_US / zh_CN" />
      </a-form-item>
      <a-form-item label="多语言">
        <div class="variant-wrapper">
          <div v-for="(item, index) in formState.language_variants" :key="index" class="variant-row">
            <a-input v-model:value="item.locale" placeholder="语言代码，如 zh_CN" style="width: 140px" />
            <a-input v-model:value="item.url" placeholder="请输入该语言对应的链接" />
            <a-input v-model:value="item.notes" placeholder="备注" style="width: 220px" />
            <a-button danger @click="removeVariant(index)">删除</a-button>
          </div>
          <a-button type="dashed" block @click="addVariant">新增语言 URL</a-button>
        </div>
      </a-form-item>
    </a-form>
  </a-modal>
</template>

<script lang="ts">
import type { PropType } from 'vue';
import { defineComponent, reactive, ref, watch } from 'vue';
import { message } from 'ant-design-vue';
import { useI18n } from 'vue-i18n';
import { addLinksOneApi, getLinksValidTags } from '@/api/links';
import type { LinkModel } from '@/utils/fb-interfaces';

type TagOption = { label: string; value: string };

const createEmptyVariant = () => ({
  locale: '',
  url: '',
  notes: '',
});

export default defineComponent({
  name: 'LinkFormModal',
  props: {
    open: { type: Boolean, required: true },
    title: { type: String, required: true },
    model: {
      type: Object as PropType<LinkModel | null>,
      default: null,
    },
  },
  emits: ['cancel', 'ok'],
  setup(props, { emit }) {
    const { t } = useI18n();
    const formRef = ref();
    const loading = ref(false);
    const tagOptions = ref<TagOption[]>([]);
    const formState = reactive<any>({
      id: '',
      link: '',
      notes: '',
      tags: [] as string[],
      default_locale: '',
      language_variants: [] as Array<{ locale: string; url: string; notes?: string }>,
    });

    const urlRules = [
      { required: true, message: t('pages.link.not.supported'), trigger: 'blur' },
      {
        pattern: /^(https?|http?|socks5):\/\/[^\s/$.?#].[^\s]*$/i,
        message: t('pages.link.not.supported'),
        trigger: 'blur',
      },
    ];

    const loadTags = async () => {
      const res: any = await getLinksValidTags();
      const list = Array.isArray(res?.data) ? res.data : [];
      tagOptions.value = list.map((tag: any) => ({
        label: tag.user_name ? `${tag.name} - ${tag.user_name}` : tag.name,
        value: tag.name,
      }));
    };

    watch(
      () => props.open,
      (value) => {
        if (!value) return;
        loadTags();
        const model = props.model;
        formState.id = model?.id || '';
        formState.link = model?.link || '';
        formState.notes = model?.notes || '';
        formState.tags = Array.isArray(model?.tags) ? model.tags.map((item: any) => item.name) : [];
        formState.default_locale = model?.default_locale || '';
        formState.language_variants = Array.isArray(model?.language_variants)
          ? model.language_variants.map((item) => ({ ...item }))
          : [];
      },
      { immediate: true }
    );

    const addVariant = () => {
      formState.language_variants.push(createEmptyVariant());
    };

    const removeVariant = (index: number) => {
      formState.language_variants.splice(index, 1);
    };

    const handleSubmit = async () => {
      try {
        await formRef.value?.validateFields();
        loading.value = true;
        const payload = {
          ...formState,
          language_variants: formState.language_variants.filter(
            (item: any) => item.locale?.trim() && item.url?.trim()
          ),
        };
        await addLinksOneApi(payload);
        message.success(t('pages.opSuccessfully'));
        emit('ok');
      } catch (err: any) {
        if (err?.errorFields) return;
        message.error(err?.response?.data?.message || err?.message || t('Operation failed'));
      } finally {
        loading.value = false;
      }
    };

    return {
      t,
      formRef,
      formState,
      loading,
      tagOptions,
      urlRules,
      addVariant,
      removeVariant,
      handleSubmit,
    };
  },
});
</script>

<style scoped>
.variant-wrapper {
  display: flex;
  flex-direction: column;
  gap: 10px;
}

.variant-row {
  display: flex;
  gap: 10px;
  align-items: center;
}
</style>
