import globals from './global/en-US';
import routes from './routes/en-US';
import pages from './pages/en-US';
import antd from 'ant-design-vue/es/locale/en_US';
import dayjs from 'dayjs/locale/en';

import settingDrawerLocales from '@/components/setting-drawer/locales/en-US';
import filterSectionLocales from '@/components/filter-section/locales/en-US';


const locales = {
  localeName: 'enUS',
  dayjsLocaleName: 'en',
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

export default locales;
