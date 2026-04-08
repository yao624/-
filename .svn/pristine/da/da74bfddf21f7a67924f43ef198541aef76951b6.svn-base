import { ref } from 'vue';
import { createI18n } from 'vue-i18n';
import dayjs from 'dayjs';
import zhLangValue from './lang/zh-CN';
import enLangValue from './lang/en-US';
export const locales = ['zh-CN', 'zh-TW', 'en-US', 'pt-BR'];
export type Locale = 'zh-CN' | 'zh-TW' | 'en-US' | 'pt-BR';

const appLocalStorage = localStorage.getItem('app');
const parsedData = JSON.parse(appLocalStorage);
let langFromLSorBrowser = '';
if (parsedData) {
  langFromLSorBrowser = parsedData.lang;
} else {
  langFromLSorBrowser = navigator.language;
}

export const defaultLang = langFromLSorBrowser.startsWith('zh') ? 'zh-CN' : 'en-US';
const loadedLanguages = ref([defaultLang]);
const defaultLangValue = defaultLang.startsWith('zh') ? zhLangValue : enLangValue;

const i18n = createI18n({
  legacy: false,
  missingWarn: false,
  fallbackWarn: false,
  // silentTranslationWarn: true,
  // silentFallbackWarn: true,
  locale: defaultLang,
  messages: {
    [defaultLang]: defaultLangValue as any,
  },
});
dayjs.locale(defaultLangValue.dayjs);

function setI18nLanguage(lang: Locale) {
  i18n.global.locale.value = lang as any;
  // request.headers['Accept-Language'] = lang;
  const HTML = document.querySelector('html');
  HTML && HTML.setAttribute('lang', lang);
  return lang;
}

export function loadLanguageAsync(lang: Locale = defaultLang): Promise<string> {
  return new Promise<string>(resolve => {
    const currentLocale = i18n.global;
    if (currentLocale.locale.value !== lang) {
      if (!loadedLanguages.value.includes(lang)) {
        return import(
          /* webpackChunkName: "lang-[request]" */
          /* 根据所用文件后缀(ts、js、vue)，自行添加后缀 */
          // eslint-disable-next-line comma-dangle
          /* @vite-ignore */ `./lang/${lang}`
        ).then(result => {
          const loadedLang = result.default;
          // set vue-i18n lang
          currentLocale.setLocaleMessage(lang, loadedLang);
          // set dayjs lang
          dayjs.locale(loadedLang.dayjsLocaleName);
          // save loaded
          loadedLanguages.value.push(lang);
          return resolve(setI18nLanguage(lang));
        });
      }
      return resolve(setI18nLanguage(lang));
    }
    resolve(lang);
  });
}

export default i18n;
