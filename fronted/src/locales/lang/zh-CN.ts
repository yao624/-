import globals from './global/zh-CN';
import routes from './routes/zh-CN';
import pages from './pages/zh-CN';
import antd from 'ant-design-vue/es/locale/zh_CN';
import dayjs from 'dayjs/locale/zh-cn';

import settingDrawerLocales from '@/components/setting-drawer/locales/zh-CN';
import filterSectionLocales from '@/components/filter-section/locales/zh-CN';


const locales = {
  localeName: 'zhCN',
  dayjsLocaleName: 'zh-cn',
  antd,
  dayjs,

  ...globals,
  ...routes,
  ...pages,
  ...settingDrawerLocales,
  components: {
    filter: filterSectionLocales,
  },
};

export default {
  ...locales,
};
