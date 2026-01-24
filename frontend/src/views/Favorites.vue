<template>
  <div class="favorites">
    <h1>收藏</h1>
    
    <!-- 加载状态 -->
    <div v-if="loading" class="loading-state">
      <div class="loading-spinner">⏳</div>
      <p>加载收藏照片...</p>
    </div>
    
    <!-- 错误状态 -->
    <div v-else-if="error" class="error-state">
      <div class="error-icon">❌</div>
      <p>{{ error }}</p>
      <button class="retry-btn" @click="fetchFavoritePhotos">重试</button>
    </div>
    
    <!-- 空状态 -->
    <div v-else-if="photos.length === 0" class="content-placeholder">
      <div class="placeholder-icon">❤️</div>
      <div class="placeholder-text">您收藏的照片将显示在这里</div>
    </div>
    
    <!-- 照片列表 -->
    <div v-else class="photos-grid">
      <!-- 照片卡片 -->
      <div 
        v-for="photo in photos" 
        :key="photo.id" 
        class="photo-card"
        @click="openImageViewer(photo)"
      >
        <!-- 收藏按钮 -->
        <button 
          class="favorite-btn"
          :class="{ 'is-favorited': photo.is_favorite }"
          @click.stop="toggleFavorite(photo)"
        >
          <span v-if="photo.is_favorite">❤️</span>
          <span v-else>🤍</span>
        </button>
        
        <!-- 照片图片 -->
        <img 
          :src="photo.thumbnail_url || photo.url" 
          :alt="photo.original_name" 
          class="photo-image"
        >
      </div>
    </div>
    
    <!-- 图片查看器模态框 -->
    <div class="image-viewer-overlay" v-if="imageViewerVisible" @click="closeImageViewer">
      <div class="image-viewer" @click.stop>
        <button class="close-btn" @click="closeImageViewer">×</button>
        
        <button class="nav-btn prev-btn" @click="prevImage" :disabled="currentImageIndex <= 0">
          <span>←</span>
        </button>
        <button class="nav-btn next-btn" @click="nextImage" :disabled="currentImageIndex >= photos.length - 1">
          <span>→</span>
        </button>
        
        <div class="image-content">
          <img 
            v-if="currentImage" 
            :src="currentImage.url" 
            :alt="currentImage.original_name" 
            class="viewer-image"
          >
        </div>
      </div>
    </div>
  </div>
</template>

<script setup>
import { ref, onMounted, computed } from 'vue'
import { favoritesAPI, photosAPI } from '../services/api'

// 响应式数据
const loading = ref(true)
const error = ref('')
const photos = ref([])

// 图片查看器相关数据
const imageViewerVisible = ref(false)
const currentImage = ref(null)
const currentImageIndex = ref(0)

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

// 获取收藏照片列表
const fetchFavoritePhotos = async () => {
  loading.value = true
  error.value = ''
  
  try {
    const response = await favoritesAPI.getFavorites()
    if (response.status === 'success') {
      photos.value = response.photos || []
    } else {
      error.value = '获取收藏照片失败'
    }
  } catch (err) {
    error.value = err.response?.data?.message || '网络错误，请稍后重试'
  } finally {
    loading.value = false
  }
}

// 切换收藏状态
const toggleFavorite = async (photo) => {
  try {
    const response = await photosAPI.toggleFavorite(photo.id)
    if (response.status === 'success') {
      // 更新本地照片的收藏状态
      photo.is_favorite = response.is_favorite
      
      // 如果取消收藏，从列表中移除
      if (!response.is_favorite) {
        photos.value = photos.value.filter(p => p.id !== photo.id)
      }
    }
  } catch (err) {
    console.error('切换收藏状态失败:', err)
  }
}

// 打开图片查看器
const openImageViewer = (photo) => {
  currentImage.value = photo
  // 找到当前图片在列表中的索引
  const index = photos.value.findIndex(p => p.id === photo.id)
  if (index !== -1) {
    currentImageIndex.value = index
  }
  imageViewerVisible.value = true
}

// 关闭图片查看器
const closeImageViewer = () => {
  imageViewerVisible.value = false
  currentImage.value = null
  currentImageIndex.value = 0
}

// 查看上一张图片
const prevImage = () => {
  if (currentImageIndex.value > 0) {
    currentImageIndex.value--
    currentImage.value = photos.value[currentImageIndex.value]
  }
}

// 查看下一张图片
const nextImage = () => {
  if (currentImageIndex.value < photos.value.length - 1) {
    currentImageIndex.value++
    currentImage.value = photos.value[currentImageIndex.value]
  }
}

// 组件挂载时获取数据
onMounted(() => {
  fetchFavoritePhotos()
})
</script>

<style scoped>
.favorites {
  padding: 20px 0;
}

.favorites h1 {
  font-size: 24px;
  font-weight: 600;
  margin-bottom: 40px;
  color: #ffffff;
}

/* 加载状态 */
.loading-state {
  display: flex;
  flex-direction: column;
  align-items: center;
  justify-content: center;
  height: 400px;
  color: #888888;
}

.loading-spinner {
  font-size: 48px;
  margin-bottom: 16px;
  animation: spin 1s linear infinite;
}

@keyframes spin {
  from { transform: rotate(0deg); }
  to { transform: rotate(360deg); }
}

/* 错误状态 */
.error-state {
  display: flex;
  flex-direction: column;
  align-items: center;
  justify-content: center;
  height: 400px;
  color: #e53935;
}

.error-icon {
  font-size: 48px;
  margin-bottom: 16px;
}

.retry-btn {
  margin-top: 16px;
  padding: 8px 16px;
  background-color: #667eea;
  color: #ffffff;
  border: none;
  border-radius: 6px;
  cursor: pointer;
  transition: all 0.2s ease;
}

.retry-btn:hover {
  background-color: #5a6fd8;
  transform: translateY(-1px);
}

/* 空状态 */
.content-placeholder {
  display: flex;
  flex-direction: column;
  align-items: center;
  justify-content: center;
  height: 400px;
  background-color: #1a1a1a;
  border-radius: 12px;
  border: 1px dashed #333333;
  color: #888888;
}

.placeholder-icon {
  font-size: 48px;
  margin-bottom: 20px;
}

.placeholder-text {
  font-size: 16px;
}

/* 照片网格 */
.photos-grid {
  display: grid;
  grid-template-columns: repeat(auto-fill, minmax(120px, 1fr));
  gap: 8px;
}

/* 照片卡片 */
.photo-card {
  position: relative;
  aspect-ratio: 1;
  background-color: #1a1a1a;
  border-radius: 8px;
  overflow: hidden;
  cursor: pointer;
  transition: all 0.2s ease;
}

.photo-card:hover {
  transform: scale(1.02);
  box-shadow: 0 4px 12px rgba(0, 0, 0, 0.3);
}

/* 照片图片 */
.photo-image {
  width: 100%;
  height: 100%;
  object-fit: cover;
  object-position: center;
  transition: all 0.3s ease;
}

/* 收藏按钮 */
.favorite-btn {
  position: absolute;
  top: 8px;
  right: 8px;
  background: none;
  border: none;
  border-radius: 50%;
  width: 28px;
  height: 28px;
  display: flex;
  align-items: center;
  justify-content: center;
  cursor: pointer;
  font-size: 16px;
  transition: all 0.2s ease;
  z-index: 10;
  opacity: 0;
}

/* 鼠标悬停时显示收藏按钮 */
.photo-card:hover .favorite-btn {
  opacity: 1;
}

/* 已收藏的照片始终显示收藏按钮 */
.photo-card .favorite-btn.is-favorited {
  opacity: 1;
}

.favorite-btn:hover {
  transform: scale(1.1);
  background: none;
}

/* 图片查看器样式 */
.image-viewer-overlay {
  position: fixed;
  top: 0;
  left: 0;
  right: 0;
  bottom: 0;
  background-color: rgba(0, 0, 0, 0.9);
  display: flex;
  align-items: center;
  justify-content: center;
  z-index: 1000;
  cursor: pointer;
}

.image-viewer {
  position: relative;
  max-width: 90vw;
  max-height: 90vh;
  cursor: default;
  display: flex;
  flex-direction: column;
  align-items: center;
}

/* 查看器控制按钮 */
.close-btn {
  position: fixed;
  top: 20px;
  right: 20px;
  background: none;
  border: none;
  color: white;
  font-size: 32px;
  cursor: pointer;
  padding: 0;
  width: 30px;
  height: 30px;
  display: flex;
  align-items: center;
  justify-content: center;
  transition: opacity 0.2s ease;
  opacity: 0.8;
  z-index: 1001;
}

.close-btn:hover {
  opacity: 1;
}

/* 左右切换按钮 */
.nav-btn {
  position: fixed;
  top: 50%;
  transform: translateY(-50%);
  background: rgba(0, 0, 0, 0.5);
  border: none;
  color: white;
  font-size: 36px;
  cursor: pointer;
  padding: 16px 12px;
  border-radius: 4px;
  transition: background-color 0.2s ease;
  z-index: 1001;
  min-width: 50px;
  display: flex;
  align-items: center;
  justify-content: center;
}

.nav-btn:hover:not(:disabled) {
  background: rgba(0, 0, 0, 0.7);
}

.nav-btn:disabled {
  opacity: 0.3;
  cursor: not-allowed;
}

.prev-btn {
  left: 20px;
}

.next-btn {
  right: 20px;
}

/* 图片内容 */
.image-content {
  max-width: 100%;
  max-height: 80vh;
  display: flex;
  align-items: center;
  justify-content: center;
}

.viewer-image {
  max-width: 100%;
  max-height: 80vh;
  object-fit: contain;
  border-radius: 4px;
}

/* 亮色主题适配 */
:root.light-mode .favorites h1 {
  color: #212529;
}

:root.light-mode .content-placeholder {
  background-color: #f8f9fa;
  border-color: #dee2e6;
  color: #6c757d;
}

:root.light-mode .photo-card {
  background-color: #f8f9fa;
  border: 1px solid #e9ecef;
}

:root.light-mode .photo-card:hover {
  box-shadow: 0 4px 12px rgba(0, 0, 0, 0.15);
  border-color: #ced4da;
}
</style>