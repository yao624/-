<!--
 * @Author: Claude
 * @Date: 2024-04-07
 * @Description: 模版与批量生成管理页面
-->
<template>
  <div class="manage-page">
    <Layout>
      <Header :style="{ position: 'fixed', width: '100%', zIndex: 99 }">
        <div class="left">
          <logo></logo>
          <Divider type="vertical" />
          模版管理
        </div>
        <div class="right">
          <Button type="primary" @click="router.push('/')">
            <Icon type="md-add" />
            新建设计
          </Button>
          <Divider type="vertical" />
          <lang></lang>
        </div>
      </Header>

      <Content :style="{ marginTop: '64px', padding: '20px', minHeight: 'calc(100vh - 140px)' }">
        <div class="manage-container">
          <Tabs v-model="activeTab" type="card">
            <TabPane name="templates" label="我的模版">
              <div class="tab-content">
                <Card>
                  <localTemplateManager />
                </Card>
              </div>
            </TabPane>
            <TabPane name="batch" label="批量生成">
              <div class="tab-content">
                <batchGeneratePanel />
              </div>
            </TabPane>
          </Tabs>
        </div>
      </Content>

      <Footer class="layout-footer-center">{{ year }} &copy; 图片模版编辑器</Footer>
    </Layout>
  </div>
</template>

<script name="ManagePage" setup lang="ts">
import { ref } from 'vue';
import { useRouter } from 'vue-router';
import logo from '@/components/logo.vue';
import lang from '@/components/lang.vue';
import localTemplateManager from '@/components/localTemplateManager.vue';
import batchGeneratePanel from '@/components/batchGeneratePanel.vue';

const router = useRouter();
const activeTab = ref('templates');
const year = ref(new Date().getFullYear());
</script>

<style lang="less" scoped>
.manage-page {
  :deep(.ivu-layout-header) {
    --height: 45px;
    padding: 0 20px;
    border-bottom: 1px solid #eef2f8;
    background: #fff;
    height: var(--height);
    line-height: var(--height);
    display: flex;
    justify-content: space-between;

    .left,
    .right {
      display: flex;
      align-items: center;
      gap: 10px;
    }
  }

  .layout-footer-center {
    text-align: center;
    padding: 20px;
    color: #999;
  }

  .manage-container {
    max-width: 1400px;
    margin: 0 auto;
  }

  .tab-content {
    padding: 16px 0;
  }

  :deep(.ivu-card) {
    height: 100%;
  }

  :deep(.ivu-card-body) {
    max-height: calc(100vh - 250px);
    overflow-y: auto;
  }
}
</style>
