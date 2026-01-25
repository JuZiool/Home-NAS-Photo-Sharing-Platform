<template>
  <div class="photo-types">
    <h1>照片内容分类</h1>
    <div class="types-header">
      <p>按照片内容自动分类展示</p>
    </div>
    
    <div class="types-grid">
      <div 
        v-for="category in categories" 
        :key="category.id"
        class="category-card"
        @click="viewCategoryPhotos(category.id)"
      >
        <div class="category-cover" :style="{ backgroundImage: `url(${getCategoryCover(category)})` }">
          <div class="photo-count">{{ category.count }} 张照片</div>
        </div>
        <div class="category-info">
          <h3>{{ category.name }}</h3>
          <p class="category-date">
            {{ category.latestPhoto ? formatDate(category.latestPhoto.created_at) : '无描述' }}
          </p>
        </div>
      </div>
    </div>
    
    <!-- 照片查看模态框 -->
    <div v-if="showModal" class="modal-overlay" @click="closeModal">
      <div class="modal-content" @click.stop>
        <button class="close-button" @click="closeModal">×</button>
        <h2>{{ selectedCategory?.name }} - 照片列表</h2>
        <div class="modal-photos-grid">
          <div 
            v-for="photo in selectedCategoryPhotos" 
            :key="photo.id"
            class="modal-photo-item"
            @click="openImageViewer(photo)"
          >
            <img :src="photo.thumbnail_url" :alt="photo.original_name" />
          </div>
        </div>
        <div v-if="selectedCategoryPhotos.length === 0" class="no-photos">
          该分类下暂无照片
        </div>
      </div>
    </div>
    
    <!-- 图片查看器 - 与Dashboard一致 -->
    <div v-if="imageViewerVisible" class="image-viewer-overlay" @click="closeImageViewer">
      <div class="image-viewer" @click.stop>
        <!-- 删除和关闭按钮 -->
        <div class="viewer-controls">
          <button 
            class="favorite-btn-large"
            :class="{ 'is-favorited': currentImage.is_favorite }"
            @click.stop="toggleFavorite(currentImage)"
            v-if="currentImage"
          >
            <span v-if="currentImage.is_favorite">❤️</span>
            <span v-else>🤍</span>
          </button>
          <button class="share-btn" @click="handleSharePhoto">🔗</button>
          <button class="delete-btn" @click="handleDeletePhoto">🗑️</button>
          <button class="close-btn" @click="closeImageViewer">×</button>
        </div>
        
        <!-- 左右切换按钮 -->
        <button class="nav-btn prev-btn" @click="prevImage" :disabled="currentImageIndex <= 0">
          <span>←</span>
        </button>
        <button class="nav-btn next-btn" @click="nextImage" :disabled="currentImageIndex >= selectedCategoryPhotos.length - 1">
          <span>→</span>
        </button>
        
        <!-- 图片内容 -->
        <div class="image-content" @wheel="handleWheel">
          <img 
            v-if="currentImage" 
            :src="`/Photos/${currentImage.user_id}/${currentImage.filename}`" 
            :alt="currentImage.original_name" 
            class="viewer-image"
            @mousedown="startDrag"
            @dragstart.prevent
            :style="{ 
              transform: `translate(${imageX}px, ${imageY}px) scale(${imageScale})`, 
              transition: isDragging ? 'none' : 'transform 0.1s ease' 
            }"
            :class="{ 'dragging': isDragging }"
            draggable="false"
          >
        </div>
      </div>
    </div>
    
    <!-- 自定义确认对话框 -->
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
    
    <!-- 分享对话框 -->
    <div class="confirm-dialog-overlay" v-if="shareDialogVisible" @click="closeShareDialog">
      <div class="confirm-dialog" @click.stop>
        <h3 class="confirm-dialog-title">分享照片</h3>
        <div class="share-dialog-content">
          <!-- 分享对话框内容 -->
        </div>
        <div class="confirm-dialog-buttons">
          <button class="confirm-dialog-cancel" @click="closeShareDialog">取消</button>
          <button class="confirm-dialog-confirm" @click="createShare">创建分享</button>
        </div>
      </div>
    </div>
  </div>
</template>

<script setup>
import { ref, onMounted, computed } from 'vue'
import { tagsAPI, photosAPI, sharesAPI } from '../services/api'

// 分类数据
const categories = ref([])
// 选中的分类
const selectedCategory = ref(null)
// 选中分类的照片
const selectedCategoryPhotos = ref([])
// 模态框显示状态
const showModal = ref(false)

// 图片查看器相关数据
const imageViewerVisible = ref(false)
const currentImage = ref(null)
const currentImageIndex = ref(0)
const imageScale = ref(1.0)
const minScale = 0.5
const maxScale = 3.0
// 拖动相关数据
const isDragging = ref(false)
const startX = ref(0)
const startY = ref(0)
const imageX = ref(0)
const imageY = ref(0)

// 确认对话框相关数据
const confirmDialogVisible = ref(false)
const confirmDialogTitle = ref('')
const confirmDialogMessage = ref('')
const confirmDialogAction = ref(null)
const confirmDialogParams = ref(null)

// 分享对话框相关数据
const shareDialogVisible = ref(false)
const shareItemId = ref(null) // 照片ID

// 获取分类数据
const fetchCategories = async () => {
  try {
    const response = await tagsAPI.getTags()
    if (response.status === 'success') {
      // 为每个分类获取最后上传的照片
      for (const tag of response.tags) {
        const tagPhotos = await tagsAPI.getPhotosByTag(tag.id)
        if (tagPhotos.status === 'success' && tagPhotos.photos.length > 0) {
          // 按created_at排序，获取最后上传的照片
          const sortedPhotos = tagPhotos.photos.sort((a, b) => {
            return new Date(b.created_at) - new Date(a.created_at)
          })
          tag.latestPhoto = sortedPhotos[0]
          tag.photos = sortedPhotos
        }
      }
      categories.value = response.tags
    }
  } catch (error) {
    console.error('Failed to fetch categories:', error)
  }
}

// 获取分类封面
const getCategoryCover = (category) => {
  if (category.latestPhoto && category.latestPhoto.thumbnail_url) {
    return category.latestPhoto.thumbnail_url
  }
  // 默认封面
  return 'data:image/svg+xml;base64,PHN2ZyB3aWR0aD0iMjAwIiBoZWlnaHQ9IjIwMCIgeG1sbnM9Imh0dHA6Ly93d3cudzMub3JnLzIwMDAvc3ZnIj4KICA8cmVjdCB3aWR0aD0iMjAwIiBoZWlnaHQ9IjIwMCIgZmlsbD0iIzM0MzQzNCIvPgogIDxjaXJjbGUgY3g9IjEwMCIgY3k9IjEwMCIgcj0iNTAiIGZpbGw9IiM1NTU1NTUiLz4KICA8Y2lyY2xlIGN4PSIxMDAiIGN5PSIxMDAiIHI9IjMwIiBmaWxsPSIjNzc3Nzc3Ii8+CiAgPHJlY3QgeD0iODAiIHk9IjgwIiB3aWR0aD0iNDAiIGhlaWdodD0iNDAiIGZpbGw9IiM5OTk5OTkifQogIDxjaXJjbGUgY3g9IjEyMCIgY3k9IjEyMCIgcj0iMTAiIGZpbGw9IiM5OTk5OTkifQo8L3N2Zz4='
}

// 查看分类照片
const viewCategoryPhotos = async (categoryId) => {
  try {
    const response = await tagsAPI.getPhotosByTag(categoryId)
    if (response.status === 'success') {
      const category = categories.value.find(c => c.id === categoryId)
      selectedCategory.value = category
      selectedCategoryPhotos.value = response.photos
      showModal.value = true
    }
  } catch (error) {
    console.error('Failed to fetch category photos:', error)
  }
}

// 关闭模态框
const closeModal = () => {
  showModal.value = false
  selectedCategory.value = null
  selectedCategoryPhotos.value = []
}

// 格式化日期
const formatDate = (dateString) => {
  const date = new Date(dateString)
  return date.toLocaleDateString('zh-CN', {
    year: 'numeric',
    month: '2-digit',
    day: '2-digit'
  })
}

// 打开图片查看器
const openImageViewer = (photo) => {
  currentImage.value = photo
  // 找到当前图片在选中分类照片中的索引
  const index = selectedCategoryPhotos.value.findIndex(p => p.id === photo.id)
  if (index !== -1) {
    currentImageIndex.value = index
  }
  // 重置缩放和拖动状态
  imageScale.value = 1.0
  imageX.value = 0
  imageY.value = 0
  isDragging.value = false
  imageViewerVisible.value = true
}

// 关闭图片查看器
const closeImageViewer = () => {
  imageViewerVisible.value = false
  currentImage.value = null
  currentImageIndex.value = 0
  // 重置缩放和拖动状态
  imageScale.value = 1.0
  imageX.value = 0
  imageY.value = 0
  isDragging.value = false
}

// 切换照片收藏状态
const toggleFavorite = async (photo) => {
  if (!photo) return
  
  try {
    const response = await photosAPI.toggleFavorite(photo.id)
    if (response.status === 'success') {
      // 更新本地照片的收藏状态
      photo.is_favorite = response.is_favorite
    }
  } catch (err) {
    console.error('切换收藏状态失败:', err)
  }
}

// 处理分享照片 - 显示分享对话框
const handleSharePhoto = () => {
  if (!currentImage.value) return
  
  shareItemId.value = currentImage.value.id
  shareDialogVisible.value = true
}

// 处理删除照片 - 显示确认对话框
const handleDeletePhoto = () => {
  if (!currentImage.value) return
  
  showConfirmDialog(
    '确认删除',
    '确定要删除这张照片吗？删除后将移入回收站。',
    doDeletePhoto,
    { photoId: currentImage.value.id }
  )
}

// 实际执行删除照片操作
const doDeletePhoto = async (params) => {
  try {
    const { photoId } = params
    
    // 调用API删除照片（移入回收站）
    await photosAPI.deletePhoto(photoId)
    
    // 从本地数据中移除该照片
    selectedCategoryPhotos.value = selectedCategoryPhotos.value.filter(p => p.id !== photoId)
    
    // 关闭图片查看器或显示下一张照片
    if (selectedCategoryPhotos.value.length > 0) {
      // 如果删除的是最后一张照片，显示前一张
      if (currentImageIndex.value >= selectedCategoryPhotos.value.length) {
        currentImageIndex.value = Math.max(0, selectedCategoryPhotos.value.length - 1)
      }
      
      if (selectedCategoryPhotos.value[currentImageIndex.value]) {
        currentImage.value = selectedCategoryPhotos.value[currentImageIndex.value]
      } else {
        closeImageViewer()
      }
    } else {
      closeImageViewer()
    }
  } catch (err) {
    console.error('删除照片失败:', err)
    alert('删除照片失败，请稍后重试')
  }
}

// 查看上一张图片
const prevImage = () => {
  if (currentImageIndex.value > 0) {
    currentImageIndex.value--
    currentImage.value = selectedCategoryPhotos.value[currentImageIndex.value]
    // 重置缩放和拖动状态
    imageScale.value = 1.0
    imageX.value = 0
    imageY.value = 0
    isDragging.value = false
  }
}

// 查看下一张图片
const nextImage = () => {
  if (currentImageIndex.value < selectedCategoryPhotos.value.length - 1) {
    currentImageIndex.value++
    currentImage.value = selectedCategoryPhotos.value[currentImageIndex.value]
    // 重置缩放和拖动状态
    imageScale.value = 1.0
    imageX.value = 0
    imageY.value = 0
    isDragging.value = false
  }
}

// 处理滚轮缩放
const handleWheel = (event) => {
  event.preventDefault()
  const delta = event.deltaY > 0 ? -0.1 : 0.1
  const newScale = Math.max(minScale, Math.min(maxScale, imageScale.value + delta))
  imageScale.value = newScale
}

// 开始拖动 - 仅在左键按住时
const startDrag = (event) => {
  // 仅处理左键拖动 (button === 0)
  if (event.button === 0) {
    isDragging.value = true
    startX.value = event.clientX - imageX.value
    startY.value = event.clientY - imageY.value
    
    // 添加文档级别的事件监听，确保拖动在整个页面范围内都能工作
    document.addEventListener('mousemove', drag)
    document.addEventListener('mouseup', endDrag)
    document.addEventListener('mouseleave', endDrag)
  }
}

// 正在拖动
const drag = (event) => {
  if (!isDragging.value) return
  event.preventDefault()
  imageX.value = event.clientX - startX.value
  imageY.value = event.clientY - startY.value
}

// 结束拖动
const endDrag = () => {
  isDragging.value = false
  
  // 移除文档级别的事件监听
  document.removeEventListener('mousemove', drag)
  document.removeEventListener('mouseup', endDrag)
  document.removeEventListener('mouseleave', endDrag)
}

// 显示确认对话框
const showConfirmDialog = (title, message, action, params = {}) => {
  confirmDialogTitle.value = title
  confirmDialogMessage.value = message
  confirmDialogAction.value = action
  confirmDialogParams.value = params
  confirmDialogVisible.value = true
}

// 关闭分享对话框
const closeShareDialog = () => {
  shareDialogVisible.value = false
  shareItemId.value = null
}

// 创建分享
const createShare = async () => {
  try {
    const shareData = {
      photo_id: shareItemId.value,
      album_id: null
    }
    
    const response = await sharesAPI.createShare(shareData)
    
    // 显示成功提示
    alert(`分享创建成功！\n分享链接: ${window.location.origin}/shared/${response.share.share_code}`)
    
    // 关闭对话框
    closeShareDialog()
  } catch (err) {
    console.error('创建分享失败:', err)
    alert('创建分享失败，请稍后重试')
  }
}

// 处理确认对话框取消
const handleConfirmDialogCancel = () => {
  confirmDialogVisible.value = false
  // 清空相关数据
  confirmDialogAction.value = null
  confirmDialogParams.value = null
}

// 处理确认对话框确认
const handleConfirmDialogConfirm = () => {
  if (confirmDialogAction.value) {
    confirmDialogAction.value(confirmDialogParams.value)
  }
  confirmDialogVisible.value = false
  // 清空相关数据
  confirmDialogAction.value = null
  confirmDialogParams.value = null
}

// 初始化
onMounted(() => {
  fetchCategories()
})
</script>

<style scoped>
.photo-types {
  padding: 20px 0;
}

.photo-types h1 {
  font-size: 24px;
  font-weight: 600;
  margin-bottom: 20px;
}

.types-header {
  margin-bottom: 30px;
  color: #888;
}

.types-grid {
  display: grid;
  grid-template-columns: repeat(auto-fill, minmax(280px, 1fr));
  gap: 20px;
}

.category-card {
  background-color: #1a1a1a;
  border-radius: 12px;
  overflow: hidden;
  cursor: pointer;
  transition: transform 0.2s ease, box-shadow 0.2s ease;
  border: 1px solid #333;
}

.category-card:hover {
  transform: translateY(-5px);
  box-shadow: 0 10px 20px rgba(0, 0, 0, 0.3);
}

.category-cover {
  height: 200px;
  background-size: cover;
  background-position: center;
  position: relative;
  display: flex;
  align-items: center;
  justify-content: center;
  background-color: #333;
}

.category-cover::before {
  content: '';
  position: absolute;
  top: 0;
  left: 0;
  right: 0;
  bottom: 0;
  background: linear-gradient(180deg, rgba(0, 0, 0, 0) 0%, rgba(0, 0, 0, 0.7) 100%);
  z-index: 1;
}

.photo-count {
  position: relative;
  z-index: 2;
  background-color: rgba(0, 0, 0, 0.6);
  color: white;
  padding: 5px 15px;
  border-radius: 20px;
  font-size: 14px;
  font-weight: 600;
}

.category-info {
  padding: 15px;
}

.category-info h3 {
  margin: 0 0 5px 0;
  font-size: 18px;
  font-weight: 600;
}

.category-date {
  margin: 0;
  color: #888;
  font-size: 14px;
}

/* 模态框样式 */
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
  padding: 20px;
  width: 90%;
  max-width: 900px;
  max-height: 80vh;
  overflow-y: auto;
  position: relative;
}

.close-button {
  position: absolute;
  top: 10px;
  right: 10px;
  background: none;
  border: none;
  color: white;
  font-size: 24px;
  cursor: pointer;
  padding: 5px;
}

.modal-content h2 {
  margin-top: 0;
  margin-bottom: 20px;
}

.modal-photos-grid {
  display: grid;
  grid-template-columns: repeat(auto-fill, minmax(150px, 1fr));
  gap: 10px;
}

.modal-photo-item {
  cursor: pointer;
  border-radius: 8px;
  overflow: hidden;
  aspect-ratio: 1;
}

.modal-photo-item img {
  width: 100%;
  height: 100%;
  object-fit: cover;
  transition: transform 0.2s ease;
}

.modal-photo-item img:hover {
  transform: scale(1.05);
}

.no-photos {
  text-align: center;
  color: #888;
  padding: 40px;
}

/* 图片查看器样式 - 与Dashboard一致 */
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
.viewer-controls {
  position: fixed;
  top: 20px;
  right: 20px;
  display: flex;
  gap: 10px;
  z-index: 1001;
}

/* 收藏按钮 */
.favorite-btn-large {
  background: none;
  border: none;
  color: white;
  font-size: 28px;
  cursor: pointer;
  padding: 0;
  width: 36px;
  height: 36px;
  display: flex;
  align-items: center;
  justify-content: center;
  transition: all 0.2s ease;
  opacity: 0.8;
}

.favorite-btn-large:hover {
  opacity: 1;
  transform: scale(1.1);
}

.favorite-btn-large.is-favorited {
  opacity: 1;
}

/* 分享按钮 */
.share-btn {
  background: none;
  border: none;
  color: white;
  font-size: 24px;
  cursor: pointer;
  padding: 0;
  width: 30px;
  height: 30px;
  display: flex;
  align-items: center;
  justify-content: center;
  transition: all 0.2s ease;
  opacity: 0.8;
}

.share-btn:hover {
  opacity: 1;
  transform: scale(1.1);
}

/* 删除按钮 */
.delete-btn {
  background: none;
  border: none;
  color: white;
  font-size: 24px;
  cursor: pointer;
  padding: 0;
  width: 30px;
  height: 30px;
  display: flex;
  align-items: center;
  justify-content: center;
  transition: all 0.2s ease;
  opacity: 0.8;
}

.delete-btn:hover {
  opacity: 1;
  transform: scale(1.1);
}

/* 关闭按钮 */
.close-btn {
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
  cursor: grab;
}

.viewer-image.dragging {
  cursor: grabbing;
  user-select: none;
}

/* 确认对话框样式 */
.confirm-dialog-overlay {
  position: fixed;
  top: 0;
  left: 0;
  right: 0;
  bottom: 0;
  background-color: rgba(0, 0, 0, 0.7);
  display: flex;
  align-items: center;
  justify-content: center;
  z-index: 1100;
  backdrop-filter: blur(4px);
}

.confirm-dialog {
  background-color: #2a2a2a;
  border-radius: 8px;
  padding: 24px;
  max-width: 400px;
  width: 90%;
  box-shadow: 0 4px 20px rgba(0, 0, 0, 0.3);
}

.confirm-dialog-title {
  font-size: 18px;
  font-weight: 600;
  color: #ffffff;
  margin-bottom: 12px;
}

.confirm-dialog-message {
  font-size: 14px;
  color: #cccccc;
  margin-bottom: 20px;
  line-height: 1.5;
}

.confirm-dialog-buttons {
  display: flex;
  justify-content: flex-end;
  gap: 12px;
}

.confirm-dialog-cancel,
.confirm-dialog-confirm {
  padding: 8px 16px;
  border-radius: 4px;
  font-size: 14px;
  font-weight: 500;
  cursor: pointer;
  transition: all 0.2s ease;
  border: none;
}

.confirm-dialog-cancel {
  background-color: #4a4a4a;
  color: #ffffff;
}

.confirm-dialog-cancel:hover {
  background-color: #5a5a5a;
}

.confirm-dialog-confirm {
  background-color: #e53935;
  color: #ffffff;
}

.confirm-dialog-confirm:hover {
  background-color: #c62828;
}

/* 分享对话框样式 */
.share-dialog-content {
  margin-bottom: 20px;
}

/* 亮色主题适配 */
:root.light-mode .category-card {
  background-color: #ffffff;
  border-color: #e0e0e0;
}

:root.light-mode .category-card:hover {
  box-shadow: 0 10px 20px rgba(0, 0, 0, 0.1);
}

:root.light-mode .category-info h3 {
  color: #333;
}

:root.light-mode .modal-content {
  background-color: #ffffff;
  color: #333;
}

:root.light-mode .close-button {
  color: #333;
}

/* 亮色主题下的查看器控制按钮 */
:root.light-mode .favorite-btn-large,
:root.light-mode .share-btn,
:root.light-mode .delete-btn,
:root.light-mode .close-btn {
  color: white;
}

/* 亮色主题下的确认对话框 */
:root.light-mode .confirm-dialog {
  background-color: #ffffff;
  box-shadow: 0 4px 20px rgba(0, 0, 0, 0.1);
}

:root.light-mode .confirm-dialog-title {
  color: #212529;
}

:root.light-mode .confirm-dialog-message {
  color: #6c757d;
}

:root.light-mode .confirm-dialog-cancel {
  background-color: #e9ecef;
  color: #212529;
}

:root.light-mode .confirm-dialog-cancel:hover {
  background-color: #dee2e6;
}

:root.light-mode .confirm-dialog-confirm {
  background-color: #e53935;
  color: #ffffff;
}

:root.light-mode .confirm-dialog-confirm:hover {
  background-color: #c62828;
}
</style>