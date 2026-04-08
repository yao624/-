import NProgress from 'nprogress'; // progress bar
import type { Router } from 'vue-router';

const init = (router: Router) => {
  NProgress.configure({ showSpinner: true }); // NProgress Configuration

  router.beforeEach((_to, _from, next) => {
    NProgress.start(); // start progress bar
    next();
  });

  router.afterEach(() => {
    NProgress.done(); // finish progress bar
  });
};

export default init;
