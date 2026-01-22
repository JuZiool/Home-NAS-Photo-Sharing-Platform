<template>
  <div class="sharing">
    <h1>分享管理</h1>
    
    <!-- 分享列表 -->
    <div v-if="shares.length > 0" class="shares-list">
      <div v-for="share in shares" :key="share.id" class="share-item">
        <div class="share-info">
          <h3 class="share-title">
            {{ share.photo_name || share.album_name || '未命名' }}
          </h3>
          <div class="share-meta">
            <span v-if="share.photo_id" class="share-type">照片</span>
            <span v-else-if="share.album_id" class="share-type">相册</span>
            <span class="share-time">{{ formatDate(share.created_at) }}</span>
            <span v-if="share.expires_at" class="share-expiry">
              过期时间: {{ formatDate(share.expires_at) }}
            </span>
          </div>
          <div class="share-code">
            <span class="code-label">分享链接:</span>
            <div class="code-container">
              <code>{{ shareUrl(share.share_code) }}</code>
              <button class="copy-btn" @click="copyShareUrl(share.share_code)">
                {{ copiedCode === share.share_code ? '已复制' : '复制' }}
              </button>
            </div>
          </div>
        </div>
        <div class="share-actions">
          <button class="delete-btn" @click="deleteShare(share.id)">
            删除
          </button>
        </div>
      </div>
    </div>
    
    <!-- 空状态 -->
    <div v-else class="empty-state">
      <div class="empty-icon">🔗</div>
      <h2>还没有分享</h2>
      <p>分享一些照片或相册来开始使用吧</p>
    </div>
  </div>
</template>

<script setup>
import { ref, onMounted } from 'vue'
import { sharesAPI } from '../services/api'

// 响应式数据
const shares = ref([])
const loading = ref(true)
const error = ref('')
const copiedCode = ref('')

// 格式化日期
const formatDate = (dateString) => {
  const date = new Date(dateString)
  return date.toLocaleString('zh-CN', {
    year: 'numeric',
    month: 'short',
    day: 'numeric',
    hour: '2-digit',
    minute: '2-digit'
  })
}

// 生成分享链接
const shareUrl = (code) => {
  // 使用前端域名，确保分享链接可以访问
  return `${window.location.origin}/shared/${code}`
}

// 复制分享链接到剪贴板
const copyShareUrl = async (code) => {
  const url = shareUrl(code)
  try {
    await navigator.clipboard.writeText(url)
    copiedCode.value = code
    // 3秒后重置复制状态
    setTimeout(() => {
      copiedCode.value = ''
    }, 3000)
  } catch (err) {
    console.error('复制失败:', err)
    // 降级方案：使用输入框复制
    const textArea = document.createElement('textarea')
    textArea.value = url
    document.body.appendChild(textArea)
    textArea.select()
    document.execCommand('copy')
    document.body.removeChild(textArea)
    copiedCode.value = code
    setTimeout(() => {
      copiedCode.value = ''
    }, 3000)
  }
}

// 删除分享
const deleteShare = async (shareId) => {
  try {
    await sharesAPI.deleteShare(shareId)
    // 从本地列表中移除
    shares.value = shares.value.filter(share => share.id !== shareId)
  } catch (err) {
    console.error('删除分享失败:', err)
    alert('删除分享失败，请稍后重试')
  }
}

// 获取分享列表
const fetchShares = async () => {
  try {
    loading.value = true
    const response = await sharesAPI.getShares()
    shares.value = response.shares || []
  } catch (err) {
    error.value = err.response?.data?.message || '获取分享列表失败，请稍后重试'
    console.error('Error fetching shares:', err)
  } finally {
    loading.value = false
  }
}

// 页面加载时获取分享列表
onMounted(() => {
  fetchShares()
})
</script>

<style scoped>
.sharing {
  padding: 20px 0;
}

.sharing h1 {
  font-size: 24px;
  font-weight: 600;
  margin-bottom: 30px;
  color: #ffffff;
}

/* 分享列表 */
.shares-list {
  display: grid;
  grid-template-columns: repeat(auto-fill, minmax(300px, 1fr));
  gap: 20px;
  align-items: start;
}

/* 分享项 */
.share-item {
  background-color: #2a2a2a;
  border-radius: 8px;
  padding: 12px;
  display: flex;
  flex-direction: column;
  gap: 12px;
  box-shadow: 0 2px 8px rgba(0, 0, 0, 0.2);
  width: 100%;
}

/* 分享信息 */
.share-info {
  flex: 1;
}

.share-title {
  font-size: 16px;
  font-weight: 600;
  color: #ffffff;
  margin-bottom: 6px;
}

.share-meta {
  display: flex;
  gap: 12px;
  margin-bottom: 8px;
  font-size: 12px;
  color: #888888;
}

.share-type {
  background-color: #4a4a4a;
  padding: 2px 6px;
  border-radius: 10px;
  font-size: 11px;
  font-weight: 500;
}

/* 分享码 */
.share-code {
  margin-top: 8px;
}

.code-label {
  font-size: 12px;
  color: #888888;
  display: block;
  margin-bottom: 4px;
}

.code-container {
  display: flex;
  align-items: center;
  gap: 8px;
  background-color: #1a1a1a;
  padding: 6px 10px;
  border-radius: 4px;
  overflow: hidden;
}

.code-container code {
  flex: 1;
  font-family: 'Consolas', 'Monaco', monospace;
  font-size: 12px;
  color: #ffffff;
  overflow: hidden;
  text-overflow: ellipsis;
  white-space: nowrap;
}

.copy-btn {
  padding: 4px 8px;
  background-color: #4a4a4a;
  color: #ffffff;
  border: none;
  border-radius: 3px;
  cursor: pointer;
  font-size: 12px;
  transition: all 0.2s ease;
}

.copy-btn:hover {
  background-color: #5a5a5a;
}

/* 分享操作 */
.share-actions {
  display: flex;
  gap: 8px;
  justify-content: flex-end;
  align-items: center;
  margin-top: auto;
}

.delete-btn {
  padding: 6px 12px;
  background-color: #e53935;
  color: #ffffff;
  border: none;
  border-radius: 4px;
  cursor: pointer;
  font-size: 12px;
  transition: all 0.2s ease;
  align-self: flex-end;
}

.delete-btn:hover {
  background-color: #c62828;
}

/* 空状态 */
.empty-state {
  display: flex;
  flex-direction: column;
  align-items: center;
  justify-content: center;
  padding: 80px 20px;
  text-align: center;
}

.empty-icon {
  font-size: 64px;
  margin-bottom: 16px;
  opacity: 0.5;
}

.empty-state h2 {
  font-size: 20px;
  font-weight: 600;
  margin-bottom: 8px;
  color: #ffffff;
}

.empty-state p {
  font-size: 14px;
  color: #888888;
}

/* 亮色主题适配 */
:root.light-mode .sharing h1 {
  color: #212529;
}

:root.light-mode .share-item {
  background-color: #ffffff;
  box-shadow: 0 2px 8px rgba(0, 0, 0, 0.1);
  border: 1px solid #e9ecef;
}

:root.light-mode .share-title {
  color: #212529;
}

:root.light-mode .share-meta {
  color: #6c757d;
}

:root.light-mode .share-type {
  background-color: #e9ecef;
  color: #495057;
}

:root.light-mode .code-container {
  background-color: #f8f9fa;
  border: 1px solid #e9ecef;
}

:root.light-mode .code-container code {
  color: #495057;
}

:root.light-mode .copy-btn {
  background-color: #e9ecef;
  color: #495057;
}

:root.light-mode .copy-btn:hover {
  background-color: #dee2e6;
}

:root.light-mode .delete-btn {
  background-color: #dc3545;
}

:root.light-mode .delete-btn:hover {
  background-color: #c82333;
}

:root.light-mode .empty-state h2 {
  color: #212529;
}

:root.light-mode .empty-state p {
  color: #6c757d;
}
</style>