<template>
  <div class="trash">
    <h1>回收站</h1>
    
    <!-- 照片内容 -->
    <div>
      <!-- 按日期分组显示照片 -->
      <div v-if="groupedPhotos.length > 0">
        <div v-for="group in groupedPhotos" :key="group.key" :class="['date-section', { 'is-month': group.isMonth }]">
          <h3>{{ group.displayDate }}</h3>
          <div v-if="!group.isMonth" class="photos-grid">
            <div class="photo-card" v-for="photo in group.photos" :key="photo.id" @click="openImageViewer(photo)">
              <img 
                :src="`/TheDeletePhotos/${photo.user_id}/${photo.filename}`" 
                :alt="photo.original_name" 
                class="photo-image"
              >
            </div>
          </div>
        </div>
      </div>
      
      <!-- 空状态 -->
      <div v-else class="empty-state">
        <div class="empty-icon">🗑️</div>
        <h2>回收站是空的</h2>
        <p>删除的照片将显示在这里</p>
      </div>
    </div>
    
    <!-- 图片查看器模态框 -->
    <div class="image-viewer-overlay" v-if="imageViewerVisible" @click="closeImageViewer">
      <div class="image-viewer" @click.stop>
        <!-- 恢复、删除和关闭按钮 -->
        <div class="viewer-controls">
          <button class="restore-btn" @click.stop.prevent="handleRestorePhoto">🔄</button>
          <button class="delete-btn" @click.stop.prevent="handleDeletePhoto">🗑️</button>
          <button class="close-btn" @click.stop.prevent="closeImageViewer">×</button>
        </div>
        
        <!-- 左右切换按钮 -->
        <button class="nav-btn prev-btn" @click.stop.prevent="prevImage" :disabled="currentImageIndex <= 0">
          <span>←</span>
        </button>
        <button class="nav-btn next-btn" @click.stop.prevent="nextImage" :disabled="currentImageIndex >= allPhotos.length - 1">
          <span>→</span>
        </button>
        
        <!-- 图片内容 -->
        <div class="image-content" @wheel="handleWheel">
          <img 
            v-if="currentImage" 
            :src="`/TheDeletePhotos/${currentImage.user_id}/${currentImage.filename}`" 
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
  </div>
</template>

<script setup>
import { ref, onMounted, computed } from 'vue'
import { photosAPI } from '../services/api'

// 响应式数据
const photos = ref([])
const loading = ref(true)
const error = ref('')

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

// 格式化日期
const formatDate = (dateString) => {
  const date = new Date(dateString)
  return date.toLocaleDateString('zh-CN', {
    year: 'numeric',
    month: 'short',
    day: 'numeric',
    hour: '2-digit',
    minute: '2-digit'
  })
}

// 获取友好的日期显示
const getFriendlyDate = (dateString) => {
  const date = new Date(dateString)
  const now = new Date()
  const today = new Date(now.getFullYear(), now.getMonth(), now.getDate())
  const yesterday = new Date(today)
  yesterday.setDate(yesterday.getDate() - 1)
  const photoDate = new Date(date.getFullYear(), date.getMonth(), date.getDate())
  
  // 计算月份显示
  const monthStr = date.toLocaleDateString('zh-CN', { month: 'long' })
  
  if (photoDate.getTime() === today.getTime()) {
    return { key: 'today', displayDate: '今天' }
  } else if (photoDate.getTime() === yesterday.getTime()) {
    return { key: 'yesterday', displayDate: '昨天' }
  } else if (date.getFullYear() === now.getFullYear()) {
    // 今年的照片，显示月+日+星期
    return {
      key: photoDate.toISOString().split('T')[0],
      displayDate: `${monthStr} ${date.getDate()}日 ${date.toLocaleDateString('zh-CN', { weekday: 'long' })}`
    }
  } else {
    // 往年的照片，显示年份+月+日+星期
    return {
      key: photoDate.toISOString().split('T')[0],
      displayDate: `${date.getFullYear()}年 ${monthStr} ${date.getDate()}日 ${date.toLocaleDateString('zh-CN', { weekday: 'long' })}`
    }
  }
}

// 获取所有照片的列表（用于左右切换）
const allPhotos = computed(() => {
  const result = []
  groupedPhotos.value.forEach(group => {
    if (!group.isMonth) {
      result.push(...group.photos)
    }
  })
  return result
})

// 按日期分组照片
const groupedPhotos = computed(() => {
  // 按拍摄时间或创建时间降序排序
  const sortedPhotos = [...photos.value].sort((a, b) => {
    const dateA = new Date(a.taken_at || a.created_at)
    const dateB = new Date(b.taken_at || b.created_at)
    return dateB - dateA
  })
  
  if (sortedPhotos.length === 0) {
    return []
  }
  
  // 按日期分组
  const dateGroups = {}
  
  // 先按具体日期分组
  sortedPhotos.forEach(photo => {
    const dateInfo = getFriendlyDate(photo.taken_at || photo.created_at)
    if (!dateGroups[dateInfo.key]) {
      dateGroups[dateInfo.key] = {
        ...dateInfo,
        photos: []
      }
    }
    dateGroups[dateInfo.key].photos.push(photo)
  })
  
  // 转换为数组并按日期降序排序
  const sortedDateGroups = Object.values(dateGroups).sort((a, b) => {
    const dateA = new Date(a.photos[0].taken_at || a.photos[0].created_at)
    const dateB = new Date(b.photos[0].taken_at || b.photos[0].created_at)
    return dateB - dateA
  })
  
  // 按月份合并分组
  const result = []
  let currentMonth = null
  
  sortedDateGroups.forEach(dateGroup => {
    const firstPhoto = dateGroup.photos[0]
    const photoDate = new Date(firstPhoto.taken_at || firstPhoto.created_at)
    const monthStr = photoDate.toLocaleDateString('zh-CN', { month: 'long' })
    
    // 如果是新的月份，添加月份分组
    if (monthStr !== currentMonth) {
      currentMonth = monthStr
      result.push({
        key: `${photoDate.getFullYear()}-${monthStr}`,
        displayDate: monthStr,
        isMonth: true
      })
    }
    
    // 添加日期分组
    result.push(dateGroup)
  })
  
  return result
})

// 打开图片查看器
const openImageViewer = (photo) => {
  currentImage.value = photo
  // 找到当前图片在所有照片中的索引
  const index = allPhotos.value.findIndex(p => p.id === photo.id)
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

// 查看上一张图片
const prevImage = () => {
  if (currentImageIndex.value > 0) {
    currentImageIndex.value--
    currentImage.value = allPhotos.value[currentImageIndex.value]
    // 重置缩放和拖动状态
    imageScale.value = 1.0
    imageX.value = 0
    imageY.value = 0
    isDragging.value = false
  }
}

// 查看下一张图片
const nextImage = () => {
  if (currentImageIndex.value < allPhotos.value.length - 1) {
    currentImageIndex.value++
    currentImage.value = allPhotos.value[currentImageIndex.value]
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
const showConfirmDialog = (title, message, action, params = null) => {
  confirmDialogTitle.value = title;
  confirmDialogMessage.value = message;
  confirmDialogAction.value = action;
  confirmDialogParams.value = params;
  confirmDialogVisible.value = true;
};

// 处理确认对话框取消
const handleConfirmDialogCancel = () => {
  confirmDialogVisible.value = false;
};

// 处理确认对话框确认
const handleConfirmDialogConfirm = async () => {
  confirmDialogVisible.value = false;
  
  if (confirmDialogAction.value) {
    try {
      await confirmDialogAction.value(confirmDialogParams.value);
    } catch (err) {
      console.error('执行确认操作失败:', err);
      alert('操作失败，请稍后重试');
    }
  }
};

// 真正的恢复照片操作
const doRestorePhoto = async () => {
  if (!currentImage.value) return;
  
  const photoId = currentImage.value.id;
  
  // 调用API恢复照片
  await photosAPI.restorePhoto(photoId);
  
  // 从本地数据中移除该照片
  photos.value = photos.value.filter(p => p.id !== photoId);
  
  // 关闭图片查看器或显示下一张照片
  if (allPhotos.value.length > 0) {
    if (currentImageIndex.value >= allPhotos.value.length) {
      currentImageIndex.value = Math.max(0, allPhotos.value.length - 1);
    }
    
    if (allPhotos.value[currentImageIndex.value]) {
      currentImage.value = allPhotos.value[currentImageIndex.value];
    } else {
      closeImageViewer();
    }
  } else {
    closeImageViewer();
  }
};

// 处理恢复照片
const handleRestorePhoto = () => {
  if (!currentImage.value) return;
  
  showConfirmDialog(
    '确认恢复',
    '确定要恢复这张照片吗？',
    doRestorePhoto
  );
};

// 真正的删除照片操作
const doDeletePhoto = async () => {
  if (!currentImage.value) return;
  
  const photoId = currentImage.value.id;
  
  // 调用API永久删除照片
  await photosAPI.permanentlyDeletePhoto(photoId);
  
  // 从本地数据中移除该照片
  photos.value = photos.value.filter(p => p.id !== photoId);
  
  // 关闭图片查看器或显示下一张照片
  if (allPhotos.value.length > 0) {
    if (currentImageIndex.value >= allPhotos.value.length) {
      currentImageIndex.value = Math.max(0, allPhotos.value.length - 1);
    }
    
    if (allPhotos.value[currentImageIndex.value]) {
      currentImage.value = allPhotos.value[currentImageIndex.value];
    } else {
      closeImageViewer();
    }
  } else {
    closeImageViewer();
  }
};

// 处理删除照片（真正删除）
const handleDeletePhoto = () => {
  if (!currentImage.value) return;
  
  showConfirmDialog(
    '确认删除',
    '确定要永久删除这张照片吗？此操作不可恢复。',
    doDeletePhoto
  );
}

// 获取回收站照片列表
const fetchTrashPhotos = async () => {
  try {
    loading.value = true
    const response = await photosAPI.getTrashPhotos()
    photos.value = response.photos || []
  } catch (err) {
    error.value = err.response?.data?.message || '获取回收站照片失败，请稍后重试'
    console.error('Error fetching trash photos:', err)
  } finally {
    loading.value = false
  }
}

// 页面加载时获取回收站照片
onMounted(() => {
  fetchTrashPhotos()
})
</script>

<style scoped>
.trash {
  padding: 20px 0;
}

.trash h1 {
  font-size: 24px;
  font-weight: 600;
  margin-bottom: 30px;
  color: #ffffff;
}

/* 日期分组 */
.date-section {
  margin-bottom: 32px;
}

.date-section h3 {
  font-size: 16px;
  font-weight: 600;
  color: #ffffff;
  margin-bottom: 16px;
  padding: 0 8px;
}

.date-section.is-month h3 {
  font-size: 20px;
  font-weight: 700;
  margin-top: 0;
  margin-bottom: 24px;
  color: #cccccc;
}

/* 照片网格 - 更密集的布局，匹配示例效果 */
.photos-grid {
  display: grid;
  grid-template-columns: repeat(auto-fill, minmax(120px, 1fr));
  gap: 8px;
  margin-bottom: 20px;
}

/* 照片卡片 */
.photo-card {
  aspect-ratio: 1;
  background-color: #1a1a1a;
  border-radius: 6px;
  overflow: hidden;
  cursor: pointer;
  transition: all 0.2s ease;
  position: relative;
  border: 1px solid transparent;
}

.photo-card:hover {
  transform: scale(1.02);
  box-shadow: 0 4px 12px rgba(0, 0, 0, 0.3);
  border-color: rgba(255, 255, 255, 0.1);
}

/* 照片图片样式 */
.photo-image {
  width: 100%;
  height: 100%;
  object-fit: cover;
  display: block;
  border-radius: 6px;
  transition: all 0.3s ease;
}

.photo-card:hover .photo-image {
  filter: brightness(1.1);
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
  margin: 0;
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
.viewer-controls {
  position: fixed;
  top: 20px;
  right: 20px;
  display: flex;
  gap: 10px;
  z-index: 1001;
}

/* 恢复按钮 */
.restore-btn {
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

.restore-btn:hover {
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

/* 自定义确认对话框样式 */
.confirm-dialog-overlay {
  position: fixed;
  top: 0;
  left: 0;
  right: 0;
  bottom: 0;
  background-color: rgba(0, 0, 0, 0.8);
  display: flex;
  align-items: center;
  justify-content: center;
  z-index: 2000;
  backdrop-filter: blur(2px);
}

.confirm-dialog {
  background-color: #2a2a2a;
  border-radius: 8px;
  padding: 24px;
  width: 90%;
  max-width: 400px;
  box-shadow: 0 8px 32px rgba(0, 0, 0, 0.5);
}

.confirm-dialog-title {
  font-size: 18px;
  font-weight: 600;
  margin-bottom: 16px;
  color: #ffffff;
}

.confirm-dialog-message {
  font-size: 14px;
  color: #cccccc;
  margin-bottom: 24px;
  line-height: 1.5;
}

.confirm-dialog-buttons {
  display: flex;
  gap: 12px;
  justify-content: flex-end;
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
  background-color: #ff4757;
  color: #ffffff;
}

.confirm-dialog-confirm:hover {
  background-color: #ff3742;
}

/* 亮色主题适配 */
:root.light-mode .trash h1 {
  color: #212529;
}

:root.light-mode .date-section h3 {
  color: #212529;
}

:root.light-mode .date-section.is-month h3 {
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

:root.light-mode .empty-state h2 {
  color: #212529;
}

:root.light-mode .empty-state p {
  color: #6c757d;
}

:root.light-mode .confirm-dialog {
  background-color: #ffffff;
  box-shadow: 0 8px 32px rgba(0, 0, 0, 0.15);
}

:root.light-mode .confirm-dialog-title {
  color: #212529;
}

:root.light-mode .confirm-dialog-message {
  color: #6c757d;
}

:root.light-mode .confirm-dialog-cancel {
  background-color: #e9ecef;
  color: #495057;
}

:root.light-mode .confirm-dialog-cancel:hover {
  background-color: #dee2e6;
}

/* 响应式设计 */
@media (max-width: 1200px) {
  .photos-grid {
    grid-template-columns: repeat(auto-fill, minmax(100px, 1fr));
    gap: 6px;
  }
}

@media (max-width: 768px) {
  .photos-grid {
    grid-template-columns: repeat(auto-fill, minmax(80px, 1fr));
    gap: 4px;
  }
  
  .nav-btn {
    font-size: 24px;
    padding: 12px 8px;
    min-width: 40px;
  }
  
  .prev-btn {
    left: 10px;
  }
  
  .next-btn {
    right: 10px;
  }
  
  .viewer-controls {
    top: 10px;
    right: 10px;
  }
  
  .restore-btn,
  .delete-btn {
    font-size: 20px;
  }
  
  .close-btn {
    font-size: 24px;
  }
  
  .confirm-dialog {
    padding: 20px;
    margin: 20px;
  }
}

@media (max-width: 480px) {
  .photos-grid {
    grid-template-columns: repeat(auto-fill, minmax(70px, 1fr));
    gap: 3px;
  }
}
</style>