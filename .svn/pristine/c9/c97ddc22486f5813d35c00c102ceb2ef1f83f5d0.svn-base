import { message } from 'ant-design-vue';
import type { ComposerTranslation } from 'vue-i18n';
import {
  uploadTempSessionInit,
  uploadTempSessionChunk,
  uploadTempSessionFilesStatus,
  uploadTempSessionCommit,
} from '@/api/material-library/materials';

const CHUNK_SIZE = 2 * 1024 * 1024; // 2MB 分片大小
const MAX_CHUNK_RETRIES = 3;
const RETRY_BASE_DELAY_MS = 1000;
const RETRY_MAX_DELAY_MS = 8000;

export interface UploadMaterialItem {
  id: string;
  name: string;
  blob: Blob;
  fileKey?: string;
  folderId?: string;
}

export interface UploadOptions {
  folderId?: string;
  materialType?: string;
  uploadMode?: string;
  designerMode?: string;
  designerId?: string;
  creatorMode?: string;
  tagMode?: string;
  tagIds?: string[];
  batchPrefix?: string;
  filterDuplicate?: boolean;
  creativeId?: string;
}

export interface UploadResult {
  uploadBatchId: string;
  createdMaterialIds: string[];
  successCount: number;
  failCount: number;
}

interface UploadSession {
  sessionId: string;
}

// ---------- 工具函数 ----------

function createChunks(blob: Blob, chunkSize: number = CHUNK_SIZE): Blob[] {
  const chunks: Blob[] = [];
  let offset = 0;
  while (offset < blob.size) {
    chunks.push(blob.slice(offset, Math.min(offset + chunkSize, blob.size)));
    offset += chunkSize;
  }
  return chunks;
}

function buildFileKey(material: UploadMaterialItem, index: number): string {
  return material.fileKey || `edited_${material.id}_${Date.now()}_${index}`;
}

// ---------- 分片上传（带重试） ----------

async function uploadChunkWithRetry(
  sessionId: string,
  fileKey: string,
  chunkIndex: number,
  chunkTotal: number,
  chunk: Blob,
  fileName: string,
): Promise<void> {
  const formData = new FormData();
  formData.append('file_key', fileKey);
  formData.append('chunk_index', String(chunkIndex));
  formData.append('chunk_total', String(chunkTotal));
  formData.append('chunk', chunk, fileName);

  let lastError: any = null;
  for (let attempt = 1; attempt <= MAX_CHUNK_RETRIES; attempt++) {
    try {
      await uploadTempSessionChunk(sessionId, formData);
      return;
    } catch (error: any) {
      lastError = error;
      if (attempt >= MAX_CHUNK_RETRIES) break;
      const delay = Math.min(RETRY_MAX_DELAY_MS, RETRY_BASE_DELAY_MS * Math.pow(2, attempt - 1));
      await new Promise(resolve => setTimeout(resolve, delay));
    }
  }
  throw lastError;
}

// ---------- 核心：批量上传 ----------

export async function uploadEditedMaterials(
  materials: UploadMaterialItem[],
  uploadOptions: UploadOptions = {},
  t: ComposerTranslation,
): Promise<UploadResult> {
  if (!materials || materials.length === 0) {
    throw new Error(t('pages.materialEditorManage.upload.noMaterials'));
  }

  const total = materials.length;
  const folderId = uploadOptions.folderId || materials[0]?.folderId || '';
  if (!folderId) {
    throw new Error('folder_id is required');
  }

  // 用一个全局 loading 提示，后续更新内容即可
  const hide = message.loading(t('pages.materialEditorManage.upload.startingUpload', { count: total }), 0);

  try {
    // 1. 预生成 fileKey，构建 files_manifest
    materials.forEach((m, idx) => {
      m.fileKey = buildFileKey(m, idx);
    });

    const filesManifest = materials.map((item, idx) => ({
      file_key: item.fileKey,
      file_name: item.name,
      file_size: item.blob.size,
      file_index: idx,
      chunk_total: Math.ceil(item.blob.size / CHUNK_SIZE),
      relative_path: null,
      smart_tag_ids: [],
    }));

    // 2. 初始化会话
    const initPayload: Record<string, any> = {
      file_count: total,
      total_size: materials.reduce((sum, item) => sum + item.blob.size, 0),
      folder_id: folderId,
      material_type: uploadOptions.materialType || 'regular',
      upload_mode: uploadOptions.uploadMode || 'file',
      files_manifest: filesManifest,
      designer_mode: uploadOptions.designerMode || 'unified',
      creator_mode: uploadOptions.creatorMode || 'unified',
      tag_mode: uploadOptions.tagMode || 'unified',
      tag_ids: uploadOptions.tagIds || [],
      batch_prefix: uploadOptions.batchPrefix || '',
      filter_duplicate: uploadOptions.filterDuplicate ?? true,
    };
    if (uploadOptions.designerId) initPayload.designer_id = uploadOptions.designerId;
    if (uploadOptions.creativeId) initPayload.creative_id = uploadOptions.creativeId;

    const initRes = await uploadTempSessionInit(initPayload);
    const sessionId = String(initRes?.data?.session_id || '');
    if (!sessionId) throw new Error('Failed to initialize upload session');

    // 3. 查询断点续传状态（失败则忽略，重新上传全部分片）
    let receivedMap = new Map<string, Set<number>>();
    try {
      const statusRes = await uploadTempSessionFilesStatus(sessionId);
      const serverFiles: any[] = statusRes?.data?.files || [];
      serverFiles.forEach((file: any) => {
        const indices = Array.isArray(file?.received_chunk_indices) ? file.received_chunk_indices : [];
        receivedMap.set(String(file.file_key), new Set(indices.map((x: number) => Number(x))));
      });
    } catch {
      // 忽略，从零开始上传
    }

    // 4. 逐个上传分片
    for (let i = 0; i < materials.length; i++) {
      const material = materials[i];
      const chunks = createChunks(material.blob);
      const fileKey = material.fileKey!;
      const receivedSet = receivedMap.get(fileKey) || new Set<number>();

      for (let c = 0; c < chunks.length; c++) {
        if (receivedSet.has(c)) continue;
        await uploadChunkWithRetry(sessionId, fileKey, c, chunks.length, chunks[c], material.name);
      }

      // 更新 loading 进度
      hide();
      if (i + 1 < total) {
        // 还有下一个文件，继续显示进度
        hide = message.loading(
          t('pages.materialEditorManage.upload.uploadingProgress', { current: i + 1, total }),
          0,
        );
      }
    }

    // 5. 提交会话
    const commitPayload: Record<string, any> = {};
    if (folderId) commitPayload.material_group_id = folderId;
    const commitRes = await uploadTempSessionCommit(sessionId, commitPayload);
    const data = commitRes?.data || {};

    hide();
    return {
      uploadBatchId: String(data.upload_batch_id || ''),
      createdMaterialIds: Array.isArray(data.created_material_ids) ? data.created_material_ids.map(String) : [],
      successCount: data.success_count ?? total,
      failCount: data.fail_count ?? 0,
    };
  } catch (error: any) {
    hide();
    message.error(t('pages.materialEditorManage.upload.uploadFailed', { error: error.message }));
    throw error;
  }
}
