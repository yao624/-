// 这是一个多页面示例，没有 store、没有 router
import 'ant-design-vue/dist/reset.css';
import { Button } from 'ant-design-vue';
import { createApp } from 'vue';
import App from '../src/SubApp.vue';

const app = createApp(App);

app.use(Button);

app.mount('#app');
