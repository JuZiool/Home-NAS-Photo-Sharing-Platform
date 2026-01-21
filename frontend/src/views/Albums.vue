<template>
  <div class="albums">
    <div class="page-header">
      <h1>相册</h1>
      <button class="create-album-btn" @click="showCreateModal = true">
        <span>📁</span>
        创建相册
      </button>
    </div>

    <!-- 创建相册模态框 -->
    <div class="modal-overlay" v-if="showCreateModal" @click="showCreateModal = false">
      <div class="modal-content" @click.stop>
        <div class="modal-header">
          <h2>创建新相册</h2>
          <button class="close-btn" @click="showCreateModal = false">×</button>
        </div>
        <div class="modal-body">
          <div class="form-group">
            <label for="album-name">相册名称</label>
            <input 
              type="text" 
              id="album-name" 
              v-model="newAlbum.name" 
              placeholder="请输入相册名称"
              class="input-field"
            >
          </div>
          <div class="form-group">
            <label for="album-desc">描述（可选）</label>
            <textarea 
              id="album-desc" 
              v-model="newAlbum.description" 
              placeholder="请输入相册描述"
              class="textarea-field"
              rows="3"
            ></textarea>
          </div>
        </div>
        <div class="modal-footer">
          <button class="cancel-btn" @click="showCreateModal = false">取消</button>
          <button 
            class="confirm-btn" 
            @click="createAlbum"
            :disabled="!newAlbum.name.trim()"
          >
            创建
          </button>
        </div>
      </div>
    </div>

    <!-- 相册列表 -->
    <div class="albums-grid" v-if="albums.length > 0">
      <div class="album-card" v-for="album in albums" :key="album.id">
        <div class="album-cover" @click="openAlbum(album)">
          <span class="cover-icon">📷</span>
          <span class="photo-count">{{ album.photo_count || 0 }} 张照片</span>
        </div>
        <div class="album-info">
          <h3 class="album-name" @click="openAlbum(album)">{{ album.name }}</h3>
          <p class="album-desc" @click="openAlbum(album)">{{ album.description || '无描述' }}</p>
          <div class="album-footer">
            <p class="album-date" @click="openAlbum(album)">{{ formatDate(album.created_at) }}</p>
            <button class="delete-btn" @click.stop="deleteAlbum(album)">
              <span>🗑️</span>
            </button>
          </div>
        </div>
      </div>
    </div>

    <!-- 空状态 -->
    <div class="empty-state" v-else>
      <div class="empty-icon">📁</div>
      <h2>还没有相册</h2>
      <p>点击右上角的按钮创建您的第一个相册</p>
    </div>

    <!-- 相册详情模态框 -->
    <div class="modal-overlay" v-if="selectedAlbum" @click="closeAlbum">
      <div class="album-modal-content" @click.stop>
        <div class="modal-header">
          <h2>{{ selectedAlbum.name }}</h2>
          <button class="close-btn" @click="closeAlbum">×</button>
        </div>
        <div class="modal-body">
          <div class="album-stats">
            <span>{{ selectedAlbum.photo_count || 0 }} 张照片</span>
            <span>{{ formatDate(selectedAlbum.created_at) }}</span>
          </div>
          
          <!-- 上传照片按钮 -->
          <div class="upload-section">
            <label class="upload-btn" :disabled="isUploading">
              <input 
                type="file" 
                multiple 
                accept="image/*" 
                @change="handleFileUpload"
                style="display: none;"
                :disabled="isUploading"
              >
              <span>📤</span>
              {{ isUploading ? '上传中...' : '上传照片' }}
            </label>
            <span class="upload-tip">支持多选，点击上传</span>
          </div>
          
          <!-- 上传进度条 -->
          <div v-if="isUploading" class="upload-progress-container">
            <div class="progress-bar-wrapper">
              <div 
                class="progress-bar" 
                :style="{ width: `${uploadProgress}%` }"
                :class="{ 'progress-success': uploadStatus === 'success', 'progress-error': uploadStatus === 'error' }"
              ></div>
            </div>
            <div class="progress-info">
              <span class="progress-text">{{ uploadProgress }}%</span>
            </div>
          </div>
          
          <!-- 上传状态消息 -->
          <div v-if="uploadStatus" class="upload-message" :class="uploadStatus">
            {{ uploadMessage }}
          </div>

          <!-- 相册照片列表 -->
          <div class="album-photos" v-if="selectedAlbumPhotos.length > 0">
            <div class="photos-grid">
              <div class="photo-thumb" v-for="photo in selectedAlbumPhotos" :key="photo.id">
                <img 
                :src="`/Photos/${photo.user_id}/${photo.filename}`" 
                :alt="photo.original_name"
                class="photo-image"
              >
              <div class="photo-info">
                <span class="photo-taken-date">{{ formatDate(photo.taken_at || photo.created_at) }}</span>
              </div>
              </div>
            </div>
          </div>

          <!-- 相册空状态 -->
          <div class="empty-state" v-else>
            <div class="empty-icon">📷</div>
            <h3>还没有照片</h3>
            <p>上传一些照片到这个相册吧</p>
          </div>
        </div>
      </div>
    </div>

    <!-- 确认对话框 -->
    <div class="confirm-dialog-overlay" v-if="confirmDialogVisible" @click="handleConfirmDialogCancel">
      <div class="confirm-dialog" @click.stop>
        <h3 class="confirm-dialog-title">{{ confirmDialogTitle }}</h3>
        <p class="confirm-dialog-message">{{ confirmDialogMessage }}</p>
        <div class="confirm-dialog-buttons">
          <button class="confirm-dialog-cancel" @click="handleConfirmDialogCancel">取消</button>
          <button class="confirm-dialog-confirm" @click="handleConfirmDialogConfirm">确认</button>
        </div>
      </div>
    </div>
  </div>
</template>

<script setup>
import { ref, onMounted } from 'vue'
import { albumsAPI } from '../services/api'

// 响应式数据
const albums = ref([])
const showCreateModal = ref(false)
const selectedAlbum = ref(null)
const selectedAlbumPhotos = ref([])
const newAlbum = ref({
  name: '',
  description: ''
})

// 上传进度相关数据
const uploadProgress = ref(0)
const isUploading = ref(false)
const uploadStatus = ref(null) // null, 'success', 'error'
const uploadMessage = ref('')

// 确认对话框相关数据
const confirmDialogVisible = ref(false)
const confirmDialogTitle = ref('')
const confirmDialogMessage = ref('')
const confirmDialogAction = ref(null)
const confirmDialogParams = ref(null)

// 格式化日期
const formatDate = (dateString) => {
  const date = new Date(dateString)
  return date.toLocaleDateString('zh-CN', {
    year: 'numeric',
    month: 'long',
    day: 'numeric'
  })
}

// 显示确认对话框
const showConfirmDialog = (title, message, action, params = {}) => {
  confirmDialogTitle.value = title
  confirmDialogMessage.value = message
  confirmDialogAction.value = action
  confirmDialogParams.value = params
  confirmDialogVisible.value = true
}

// 处理确认对话框取消
const handleConfirmDialogCancel = () => {
  confirmDialogVisible.value = false
  confirmDialogAction.value = null
  confirmDialogParams.value = null
}

// 处理确认对话框确认
const handleConfirmDialogConfirm = () => {
  if (confirmDialogAction.value) {
    confirmDialogAction.value(confirmDialogParams.value)
  }
  confirmDialogVisible.value = false
  confirmDialogAction.value = null
  confirmDialogParams.value = null
}

// 获取相册列表
const fetchAlbums = async () => {
  try {
    const response = await albumsAPI.getAlbums()
    albums.value = response.albums || []
  } catch (err) {
    console.error('获取相册失败:', err)
  }
}

// 创建相册
const createAlbum = async () => {
  if (!newAlbum.value.name.trim()) return
  
  try {
    await albumsAPI.createAlbum(newAlbum.value)
    // 重置表单
    newAlbum.value = {
      name: '',
      description: ''
    }
    // 关闭模态框
    showCreateModal.value = false
    // 重新获取相册列表
    fetchAlbums()
  } catch (err) {
    console.error('创建相册失败:', err)
  }
}

// 打开相册详情
const openAlbum = async (album) => {
  selectedAlbum.value = album
  // 获取相册中的照片
  try {
    const response = await albumsAPI.getAlbumPhotos(album.id)
    selectedAlbumPhotos.value = response.photos || []
  } catch (err) {
    console.error('获取相册照片失败:', err)
    selectedAlbumPhotos.value = []
  }
}

// 关闭相册详情
const closeAlbum = () => {
  selectedAlbum.value = null
  selectedAlbumPhotos.value = []
}

// 处理文件上传
const handleFileUpload = async (event) => {
  const files = event.target.files
  if (!files.length || !selectedAlbum.value) return
  
  // 重置上传状态
  isUploading.value = true
  uploadProgress.value = 0
  uploadStatus.value = null
  uploadMessage.value = ''
  
  try {
    const formData = new FormData()
    for (let i = 0; i < files.length; i++) {
      formData.append('files[]', files[i])
      formData.append('album_id', selectedAlbum.value.id)
    }
    
    // 上传进度回调
    const onUploadProgress = (progressEvent) => {
      const percentCompleted = Math.round((progressEvent.loaded * 100) / progressEvent.total)
      uploadProgress.value = percentCompleted
    }
    
    await albumsAPI.uploadPhotosToAlbum(selectedAlbum.value.id, formData, onUploadProgress)
    
    // 上传成功
    uploadStatus.value = 'success'
    uploadMessage.value = `成功上传 ${files.length} 张照片`
    
    // 刷新相册照片列表
    const response = await albumsAPI.getAlbumPhotos(selectedAlbum.value.id)
    selectedAlbumPhotos.value = response.photos || []
    
    // 3秒后重置上传状态
    setTimeout(() => {
      isUploading.value = false
      uploadStatus.value = null
      uploadMessage.value = ''
    }, 3000)
  } catch (err) {
    console.error('上传照片失败:', err)
    uploadStatus.value = 'error'
    uploadMessage.value = '上传失败，请重试'
    
    // 3秒后重置上传状态
    setTimeout(() => {
      isUploading.value = false
      uploadStatus.value = null
      uploadMessage.value = ''
    }, 3000)
  }
  
  // 清空文件输入
  event.target.value = ''
}

// 删除相册
const deleteAlbum = async (album) => {
  // 使用自定义确认对话框
  showConfirmDialog(
    '确认删除',
    `确定要删除相册 "${album.name}" 及其所有照片吗？`,
    async () => {
      try {
        await albumsAPI.deleteAlbum(album.id)
        // 删除成功后重新获取相册列表
        fetchAlbums()
      } catch (err) {
        console.error('删除相册失败:', err)
      }
    }
  )
}

// 页面加载时获取相册列表
onMounted(() => {
  fetchAlbums()
})
</script>

<style scoped>
.albums {
  padding: 20px 0;
}

/* 页面头部 */
.page-header {
  display: flex;
  justify-content: space-between;
  align-items: center;
  margin-bottom: 30px;
}

.page-header h1 {
  font-size: 24px;
  font-weight: 600;
  margin: 0;
}

/* 创建相册按钮 */
.create-album-btn {
  display: flex;
  align-items: center;
  gap: 8px;
  padding: 10px 20px;
  background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
  border: none;
  border-radius: 8px;
  color: white;
  font-size: 14px;
  font-weight: 500;
  cursor: pointer;
  transition: all 0.3s ease;
}

.create-album-btn:hover {
  transform: translateY(-1px);
  box-shadow: 0 4px 12px rgba(102, 126, 234, 0.4);
}

/* 相册网格 */
.albums-grid {
  display: grid;
  grid-template-columns: repeat(auto-fill, minmax(280px, 1fr));
  gap: 20px;
}

/* 相册卡片 */
.album-card {
  background-color: #1a1a1a;
  border-radius: 12px;
  overflow: hidden;
  cursor: pointer;
  transition: all 0.3s ease;
  border: 1px solid #333333;
}

.album-card:hover {
  transform: translateY(-4px);
  box-shadow: 0 8px 20px rgba(0, 0, 0, 0.3);
}

.album-cover {
  width: 100%;
  height: 200px;
  background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
  display: flex;
  flex-direction: column;
  align-items: center;
  justify-content: center;
  position: relative;
}

.cover-icon {
  font-size: 48px;
  margin-bottom: 10px;
}

.photo-count {
  font-size: 12px;
  color: rgba(255, 255, 255, 0.8);
}

.album-info {
  padding: 16px;
}

.album-name {
  font-size: 18px;
  font-weight: 600;
  margin: 0 0 8px 0;
}

.album-desc {
  font-size: 14px;
  color: #888888;
  margin: 0 0 8px 0;
  overflow: hidden;
  text-overflow: ellipsis;
  display: -webkit-box;
  -webkit-line-clamp: 2;
  -webkit-box-orient: vertical;
}

.album-date {
    font-size: 12px;
    color: #666666;
    margin: 0;
}

/* 相册页脚 */
.album-footer {
    display: flex;
    justify-content: space-between;
    align-items: center;
    margin-top: 8px;
}

/* 删除按钮 */
.delete-btn {
    background: none;
    border: none;
    color: #ff4757;
    font-size: 16px;
    cursor: pointer;
    padding: 4px;
    border-radius: 4px;
    transition: all 0.3s ease;
    opacity: 0.7;
}

.delete-btn:hover {
    opacity: 1;
    background-color: rgba(255, 71, 87, 0.1);
}

/* 亮色主题适配 */
:root.light-mode .delete-btn {
    color: #dc3545;
}

:root.light-mode .delete-btn:hover {
    background-color: rgba(220, 53, 69, 0.1);
}

/* 模态框 */
.modal-overlay {
  position: fixed;
  top: 0;
  left: 0;
  right: 0;
  bottom: 0;
  background-color: rgba(0, 0, 0, 0.8);
  display: flex;
  align-items: center;
  justify-content: center;
  z-index: 1000;
}

.modal-content {
  background-color: #1a1a1a;
  border-radius: 12px;
  width: 90%;
  max-width: 500px;
  border: 1px solid #333333;
}

.album-modal-content {
  background-color: #1a1a1a;
  border-radius: 12px;
  width: 90%;
  max-width: 800px;
  height: 80vh;
  display: flex;
  flex-direction: column;
  border: 1px solid #333333;
}

.modal-header {
  display: flex;
  justify-content: space-between;
  align-items: center;
  padding: 20px;
  border-bottom: 1px solid #333333;
}

.modal-header h2 {
  font-size: 20px;
  font-weight: 600;
  margin: 0;
}

.close-btn {
  background: none;
  border: none;
  color: #ffffff;
  font-size: 24px;
  cursor: pointer;
  padding: 0;
  width: 30px;
  height: 30px;
  display: flex;
  align-items: center;
  justify-content: center;
  border-radius: 50%;
  transition: background-color 0.3s ease;
}

.close-btn:hover {
  background-color: #333333;
}

.modal-body {
  padding: 20px;
  overflow-y: auto;
  flex: 1;
}

.modal-footer {
  display: flex;
  justify-content: flex-end;
  gap: 12px;
  padding: 20px;
  border-top: 1px solid #333333;
}

/* 表单样式 */
.form-group {
  margin-bottom: 20px;
}

.form-group label {
  display: block;
  margin-bottom: 8px;
  font-size: 14px;
  font-weight: 500;
  color: #cccccc;
}

.input-field, .textarea-field {
  width: 100%;
  padding: 12px;
  border: 1px solid #333333;
  border-radius: 8px;
  background-color: #2a2a2a;
  color: #ffffff;
  font-size: 14px;
  outline: none;
  transition: all 0.3s ease;
}

.input-field:focus, .textarea-field:focus {
  border-color: #667eea;
  background-color: #333333;
}

.textarea-field {
  resize: vertical;
  min-height: 80px;
}

/* 按钮样式 */
.cancel-btn, .confirm-btn {
  padding: 10px 24px;
  border: none;
  border-radius: 8px;
  font-size: 14px;
  font-weight: 500;
  cursor: pointer;
  transition: all 0.3s ease;
}

.cancel-btn {
  background-color: #333333;
  color: #ffffff;
}

.cancel-btn:hover {
  background-color: #444444;
}

.confirm-btn {
  background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
  color: #ffffff;
}

.confirm-btn:hover:not(:disabled) {
  transform: translateY(-1px);
  box-shadow: 0 4px 12px rgba(102, 126, 234, 0.4);
}

.confirm-btn:disabled {
  opacity: 0.6;
  cursor: not-allowed;
  transform: none;
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

.empty-state h2, .empty-state h3 {
  font-size: 20px;
  font-weight: 600;
  margin-bottom: 8px;
  color: #ffffff;
}

.empty-state p {
  font-size: 14px;
  color: #888888;
  margin: 0;
}

/* 上传照片样式 */
.upload-section {
  display: flex;
  align-items: center;
  gap: 16px;
  margin-bottom: 24px;
  padding: 16px;
  background-color: #2a2a2a;
  border-radius: 8px;
  border: 1px dashed #333333;
}

.upload-btn {
  display: flex;
  align-items: center;
  gap: 8px;
  padding: 10px 20px;
  background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
  border: none;
  border-radius: 8px;
  color: white;
  font-size: 14px;
  font-weight: 500;
  cursor: pointer;
  transition: all 0.3s ease;
}

.upload-btn:hover {
  transform: translateY(-1px);
  box-shadow: 0 4px 12px rgba(102, 126, 234, 0.4);
}

.upload-tip {
  font-size: 14px;
  color: #888888;
}

/* 相册照片列表 */
.album-photos {
  margin-top: 20px;
}

.photos-grid {
  display: grid;
  grid-template-columns: repeat(auto-fill, minmax(100px, 1fr));
  gap: 12px;
}

.photo-thumb {
  aspect-ratio: 1;
  overflow: hidden;
  border-radius: 8px;
  background-color: #333333;
  position: relative;
}

.photo-image {
  width: 100%;
  height: 100%;
  object-fit: cover;
  display: block;
  transition: transform 0.3s ease;
}

.photo-image:hover {
  transform: scale(1.05);
}

/* 照片信息 */
.photo-info {
  position: absolute;
  bottom: 0;
  left: 0;
  right: 0;
  background: linear-gradient(to top, rgba(0, 0, 0, 0.8), transparent);
  padding: 8px;
  color: white;
  font-size: 11px;
  opacity: 0;
  transition: opacity 0.3s ease;
  pointer-events: none;
}

.photo-thumb:hover .photo-info {
  opacity: 1;
}

.photo-taken-date {
  display: block;
  text-align: center;
  font-size: 10px;
  color: rgba(255, 255, 255, 0.9);
  font-weight: 500;
}

/* 相册统计信息 */
.album-stats {
  display: flex;
  gap: 20px;
  margin-bottom: 20px;
  font-size: 14px;
  color: #888888;
}

/* 上传进度条 */
.upload-progress-container {
  margin: 16px 0;
}

.progress-bar-wrapper {
  width: 100%;
  height: 8px;
  background-color: #333333;
  border-radius: 4px;
  overflow: hidden;
  margin-bottom: 8px;
}

.progress-bar {
  height: 100%;
  background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
  border-radius: 4px;
  transition: width 0.3s ease;
}

.progress-bar.progress-success {
  background: linear-gradient(135deg, #51cf66 0%, #00b894 100%);
}

.progress-bar.progress-error {
  background: linear-gradient(135deg, #ff6b6b 0%, #ee5a24 100%);
}

.progress-info {
  display: flex;
  justify-content: space-between;
  align-items: center;
}

.progress-text {
  font-size: 12px;
  color: #888888;
}

/* 上传状态消息 */
.upload-message {
  padding: 12px;
  border-radius: 8px;
  font-size: 14px;
  margin-bottom: 16px;
  text-align: center;
}

.upload-message.success {
  background-color: rgba(81, 207, 102, 0.1);
  color: #51cf66;
  border: 1px solid rgba(81, 207, 102, 0.3);
}

.upload-message.error {
  background-color: rgba(255, 107, 107, 0.1);
  color: #ff6b6b;
  border: 1px solid rgba(255, 107, 107, 0.3);
}

/* 禁用状态 */
.upload-btn:disabled {
  opacity: 0.6;
  cursor: not-allowed;
}

.upload-btn:disabled:hover {
  transform: none;
  box-shadow: none;
}

/* 确认对话框样式 */
.confirm-dialog-overlay {
  position: fixed;
  top: 0;
  left: 0;
  right: 0;
  bottom: 0;
  background-color: rgba(0, 0, 0, 0.8);
  display: flex;
  justify-content: center;
  align-items: center;
  z-index: 1001;
}

.confirm-dialog {
  background-color: #1a1a1a;
  border-radius: 12px;
  padding: 24px;
  max-width: 400px;
  width: 90%;
  text-align: center;
  box-shadow: 0 8px 32px rgba(0, 0, 0, 0.3);
  border: 1px solid #333333;
}

.confirm-dialog-title {
  font-size: 20px;
  font-weight: 600;
  margin: 0 0 16px 0;
  color: #ffffff;
}

.confirm-dialog-message {
  font-size: 14px;
  color: #cccccc;
  margin: 0 0 24px 0;
  line-height: 1.5;
}

.confirm-dialog-buttons {
  display: flex;
  gap: 12px;
  justify-content: center;
}

.confirm-dialog-cancel,
.confirm-dialog-confirm {
  padding: 10px 24px;
  border: none;
  border-radius: 8px;
  font-size: 14px;
  font-weight: 500;
  cursor: pointer;
  transition: all 0.3s ease;
}

.confirm-dialog-cancel {
  background-color: #333333;
  color: #ffffff;
}

.confirm-dialog-cancel:hover {
  background-color: #444444;
}

.confirm-dialog-confirm {
  background: linear-gradient(135deg, #ff4757 0%, #ff3742 100%);
  color: #ffffff;
}

.confirm-dialog-confirm:hover {
  transform: translateY(-1px);
  box-shadow: 0 4px 12px rgba(255, 71, 87, 0.4);
}

/* 亮色主题适配 */
:root.light-mode .confirm-dialog {
  background-color: #ffffff;
  border-color: #e9ecef;
}

:root.light-mode .confirm-dialog-title {
  color: #212529;
}

:root.light-mode .confirm-dialog-message {
  color: #495057;
}

:root.light-mode .confirm-dialog-cancel {
  background-color: #e9ecef;
  color: #495057;
}

:root.light-mode .confirm-dialog-cancel:hover {
  background-color: #dee2e6;
}

:root.light-mode .page-header h1 {
  color: #212529;
}

:root.light-mode .album-card {
  background-color: #ffffff;
  border-color: #e9ecef;
}

:root.light-mode .album-card:hover {
  box-shadow: 0 8px 20px rgba(0, 0, 0, 0.1);
}

:root.light-mode .album-info .album-name {
  color: #212529;
}

:root.light-mode .album-info .album-desc {
  color: #6c757d;
}

:root.light-mode .album-info .album-date {
  color: #adb5bd;
}

:root.light-mode .modal-content,
:root.light-mode .album-modal-content {
  background-color: #ffffff;
  border-color: #e9ecef;
}

:root.light-mode .modal-header {
  border-bottom-color: #e9ecef;
}

:root.light-mode .modal-footer {
  border-top-color: #e9ecef;
}

:root.light-mode .form-group label {
  color: #495057;
}

:root.light-mode .input-field,
:root.light-mode .textarea-field {
  background-color: #ffffff;
  border-color: #ced4da;
  color: #495057;
}

:root.light-mode .input-field:focus,
:root.light-mode .textarea-field:focus {
  border-color: #667eea;
  background-color: #ffffff;
}

:root.light-mode .cancel-btn {
  background-color: #e9ecef;
  color: #495057;
}

:root.light-mode .cancel-btn:hover {
  background-color: #dee2e6;
}

:root.light-mode .empty-state h2,
:root.light-mode .empty-state h3 {
  color: #212529;
}

:root.light-mode .empty-state p {
  color: #6c757d;
}

:root.light-mode .upload-section {
  background-color: #f8f9fa;
  border-color: #dee2e6;
}

:root.light-mode .upload-tip {
  color: #6c757d;
}

:root.light-mode .album-stats {
  color: #6c757d;
}

:root.light-mode .photo-thumb {
  background-color: #e9ecef;
}
</style>