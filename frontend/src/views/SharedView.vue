<template>
  <div class="shared-view-container">
    <!-- 加载状态 -->
    <div v-if="loading" class="loading-state">
      <div class="loading-spinner">⏳</div>
      <p>加载分享内容...</p>
    </div>
    
    <!-- 错误状态 -->
    <div v-else-if="error" class="error-state">
      <div class="error-icon">❌</div>
      <h2>{{ error }}</h2>
      <p>分享链接可能已过期或不存在</p>
      <router-link to="/" class="back-link">返回首页</router-link>
    </div>
    
    <!-- 分享内容 -->
    <div v-else-if="shareData" class="shared-content">
      <!-- 分享标题 -->
      <h1 class="share-title">
        {{ shareData.photo_name || shareData.album_name || '分享内容' }}
      </h1>
      
      <!-- 操作按钮 -->
      <div class="action-buttons">
        <button class="action-btn select-btn" @click="toggleSelectMode">
          {{ isSelectMode ? '取消选择' : '选择' }}
        </button>
        <button 
          class="action-btn download-btn" 
          @click="handleDownload"
          :disabled="selectedPhotos.length === 0"
        >
          下载 ({{ selectedPhotos.length }})
        </button>
        <button 
          class="action-btn save-btn" 
          @click="toggleSaveDialog"
          :disabled="selectedPhotos.length === 0"
        >
          一键转存 ({{ selectedPhotos.length }})
        </button>
      </div>
      
      <!-- 分享元信息 -->
      <div class="share-meta">
        <span v-if="shareData.photo_id" class="share-type">照片</span>
        <span v-else-if="shareData.album_id" class="share-type">相册</span>
        <span class="share-time">分享于 {{ formatDate(shareData.created_at) }}</span>
      </div>
      
      <!-- 单张照片或相册分享 -->
      <div v-if="(shareData.photo_id && shareContent.photo) || (shareData.album_id && shareContent.album)" class="album-photos">
        <!-- 直接显示所有照片 -->
        <div class="photos-grid">
          <!-- 单张照片 -->
          <div 
            v-if="shareData.photo_id && shareContent.photo" 
            class="photo-card" 
            @click="isSelectMode ? togglePhotoSelection(shareContent.photo) : openImageViewer(shareContent.photo)"
          >
            <!-- 选择框 -->
            <div v-if="isSelectMode" class="photo-selector">
              <input 
                type="checkbox" 
                :checked="selectedPhotos.includes(shareContent.photo)" 
                @change.stop="togglePhotoSelection(shareContent.photo)"
              >
            </div>
            <img 
              :src="shareContent.photo.thumbnail_url || shareContent.photo.url" 
              :alt="shareContent.photo.original_name" 
              class="photo-image"
            >
          </div>
          
          <!-- 相册照片 -->
          <div 
            v-else-if="shareData.album_id && shareContent.album" 
            class="photo-card" 
            v-for="photo in sortedPhotos" 
            :key="photo.id" 
            @click="isSelectMode ? togglePhotoSelection(photo) : openImageViewer(photo)"
          >
            <!-- 选择框 -->
            <div v-if="isSelectMode" class="photo-selector">
              <input 
                type="checkbox" 
                :checked="selectedPhotos.includes(photo)" 
                @change.stop="togglePhotoSelection(photo)"
              >
            </div>
            <img 
              :src="photo.thumbnail_url || photo.url" 
              :alt="photo.original_name" 
              class="photo-image"
            >
          </div>
        </div>
        
        <!-- 空状态 -->
        <div v-if="shareData.album_id && (!shareContent.album.photos || shareContent.album.photos.length === 0)" class="empty-state">
          <div class="empty-icon">📷</div>
          <h2>还没有照片</h2>
          <p>这个相册是空的</p>
        </div>
      </div>
    </div>
    
    <!-- 一键转存弹窗 -->
    <div class="dialog-overlay" v-if="saveDialogVisible" @click="toggleSaveDialog">
      <div class="dialog-content" @click.stop>
        <div class="dialog-header">
          <h3>一键转存照片</h3>
          <button class="dialog-close-btn" @click="toggleSaveDialog">×</button>
        </div>
        
        <div class="dialog-body">
          <!-- 登录状态检查中 -->
          <div v-if="loginStatusLoading" class="loading-state">
            <div class="loading-spinner">⏳</div>
            <p>检查登录状态...</p>
          </div>
          
          <!-- 未登录状态 -->
          <div v-else-if="!isLoggedIn">
            <!-- 登录提示 -->
            <div v-if="!showLoginForm" class="login-prompt">
              <p>请先登录以使用一键转存功能</p>
              <button class="login-btn" @click="handleLogin">登录</button>
            </div>
            
            <!-- 登录表单 -->
            <div v-else class="login-form">
              <h4>登录</h4>
              
              <!-- 登录错误信息 -->
              <div v-if="loginError" class="login-error">{{ loginError }}</div>
              
              <div class="form-group">
                <label for="username">用户名</label>
                <input 
                  type="text" 
                  id="username" 
                  v-model="loginForm.username" 
                  placeholder="请输入用户名"
                  class="form-input"
                  @keyup.enter="submitLogin"
                >
              </div>
              
              <div class="form-group">
                <label for="password">密码</label>
                <input 
                  type="password" 
                  id="password" 
                  v-model="loginForm.password" 
                  placeholder="请输入密码"
                  class="form-input"
                  @keyup.enter="submitLogin"
                >
              </div>
              
              <div class="form-actions">
                <button class="cancel-btn" @click="showLoginForm = false">取消</button>
                <button 
                  class="login-btn" 
                  @click="submitLogin"
                  :disabled="loginLoading"
                >
                  {{ loginLoading ? '登录中...' : '登录' }}
                </button>
              </div>
            </div>
          </div>
          
          <!-- 已登录状态 -->
          <div v-else>
            <h4>选择相册</h4>
            <div class="album-list">
              <!-- 相册列表 -->
              <div 
                v-for="album in userAlbums" 
                :key="album.id" 
                class="album-item"
                :class="{ selected: selectedAlbum === album.id }"
                @click="selectedAlbum = album.id"
              >
                <span class="album-name">{{ album.name }}</span>
                <span class="album-count">({{ album.photo_count }} 张)</span>
              </div>
              
              <!-- 新建相册选项 -->
              <div 
                class="album-item create-album-item"
                @click="toggleNewAlbumForm"
              >
                <span class="create-album-icon">+</span>
                <span>新建相册</span>
              </div>
              
              <!-- 新建相册表单 -->
              <div v-if="showNewAlbumForm" class="new-album-form">
                <input 
                  type="text" 
                  v-model="newAlbumName" 
                  placeholder="请输入新相册名称"
                  class="new-album-input"
                >
                <div class="new-album-actions">
                  <button class="cancel-btn" @click="toggleNewAlbumForm">取消</button>
                  <button class="create-btn" @click="createNewAlbum">创建</button>
                </div>
              </div>
            </div>
          </div>
        </div>
        
        <div class="dialog-footer">
          <button class="cancel-btn" @click="toggleSaveDialog">取消</button>
          <button 
            class="confirm-btn" 
            @click="handleSaveToAlbum"
            :disabled="!isLoggedIn || !selectedAlbum"
          >
            确定转存
          </button>
        </div>
      </div>
    </div>
    
    <!-- 图片查看器模态框 -->
    <div class="image-viewer-overlay" v-if="imageViewerVisible" @click="closeImageViewer">
      <div class="image-viewer" @click.stop>
        <button class="close-btn" @click="closeImageViewer">×</button>
        
        <button class="nav-btn prev-btn" @click="prevImage" :disabled="currentImageIndex <= 0">
          <span>←</span>
        </button>
        <button class="nav-btn next-btn" @click="nextImage" :disabled="currentImageIndex >= allPhotos.length - 1">
          <span>→</span>
        </button>
        
        <div class="image-content" @wheel="handleWheel">
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
import { ref, onMounted, computed, watch } from 'vue'
import { useRoute, useRouter } from 'vue-router'
import { sharesAPI, authAPI, albumsAPI } from '../services/api'
import api from '../services/api'

// 路由和导航
const route = useRoute()

// 响应式数据
const shareData = ref(null)
const shareContent = ref({})
const loading = ref(true)
const error = ref('')

// 图片查看器相关数据
const imageViewerVisible = ref(false)
const currentImage = ref(null)
const currentImageIndex = ref(0)

// 选择模式相关数据
const isSelectMode = ref(false)
const selectedPhotos = ref([])

// 一键转存相关数据
const saveDialogVisible = ref(false)
const isLoggedIn = ref(false) // 初始化为未登录状态
const loginStatusLoading = ref(false) // 登录状态检查加载状态
const userAlbums = ref([]) // 相册列表，实际项目中应从API获取
const selectedAlbum = ref(null)
const showNewAlbumForm = ref(false)
const newAlbumName = ref('')
const showLoginForm = ref(false) // 登录表单显示状态
const loginForm = ref({ // 登录表单数据
  username: '',
  password: ''
})
const loginLoading = ref(false) // 登录加载状态
const loginError = ref('') // 登录错误信息

// 获取所有照片的列表（用于左右切换）
const allPhotos = computed(() => {
  if (shareData.value?.photo_id && shareContent.value?.photo) {
    // 单张照片时，返回包含该照片的数组
    return [shareContent.value.photo]
  } else if (shareContent.value?.album && shareContent.value?.album.photos) {
    // 相册时，返回相册照片数组
    return shareContent.value.album.photos
  }
  return []
})

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

// 按照相册中照片的原始顺序显示
const sortedPhotos = computed(() => {
  if (!shareContent.value.album || !shareContent.value.album.photos) {
    return []
  }
  
  // 直接使用相册中照片的原始顺序
  return [...shareContent.value.album.photos]
})

// 格式化文件大小
const formatFileSize = (bytes) => {
  if (bytes < 1024) {
    return bytes + ' B'
  } else if (bytes < 1024 * 1024) {
    return (bytes / 1024).toFixed(2) + ' KB'
  } else if (bytes < 1024 * 1024 * 1024) {
    return (bytes / (1024 * 1024)).toFixed(2) + ' MB'
  } else {
    return (bytes / (1024 * 1024 * 1024)).toFixed(2) + ' GB'
  }
}

// 打开图片查看器
const openImageViewer = (photo) => {
  currentImage.value = photo
  // 找到当前图片在所有照片中的索引
  const index = allPhotos.value.findIndex(p => p.id === photo.id)
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
    currentImage.value = allPhotos.value[currentImageIndex.value]
  }
}

// 查看下一张图片
const nextImage = () => {
  if (currentImageIndex.value < allPhotos.value.length - 1) {
    currentImageIndex.value++
    currentImage.value = allPhotos.value[currentImageIndex.value]
  }
}

// 处理滚轮缩放
const handleWheel = (event) => {
  event.preventDefault()
  // 这里可以添加缩放功能
}

// 获取分享内容
const fetchShareContent = async () => {
  const shareCode = route.params.code
  if (!shareCode) {
    error.value = '无效的分享链接'
    loading.value = false
    return
  }
  
  try {
    loading.value = true
    const response = await sharesAPI.getShareByCode(shareCode)
    shareData.value = response.share
    shareContent.value = response.content
  } catch (err) {
    error.value = err.response?.data?.message || '获取分享内容失败'
    console.error('Error fetching share content:', err)
  } finally {
    loading.value = false
  }
}

// 切换选择模式
const toggleSelectMode = () => {
  isSelectMode.value = !isSelectMode.value
  // 退出选择模式时清空选择
  if (!isSelectMode.value) {
    selectedPhotos.value = []
  }
}

// 切换照片选择状态
const togglePhotoSelection = (photo) => {
  const index = selectedPhotos.value.indexOf(photo)
  if (index === -1) {
    selectedPhotos.value.push(photo)
  } else {
    selectedPhotos.value.splice(index, 1)
  }
}

// 处理下载功能
const handleDownload = async () => {
  // 单张照片下载（非选择模式下）
  if (shareData.value.photo_id && shareContent.value.photo && !isSelectMode.value) {
    const link = document.createElement('a')
    link.href = shareContent.value.photo.url
    link.download = shareContent.value.photo.original_name
    document.body.appendChild(link)
    link.click()
    document.body.removeChild(link)
    return
  }
  
  // 获取要下载的照片列表
  let photosToDownload = []
  if (selectedPhotos.value.length > 0) {
    photosToDownload = selectedPhotos.value
  } else if (shareData.value.album_id && shareContent.value.album && shareContent.value.album.photos && !isSelectMode.value) {
    photosToDownload = shareContent.value.album.photos
  }
  
  if (photosToDownload.length === 0) {
    return
  }
  
  try {
    // 显示加载状态
    loading.value = true
    
    // 准备照片ID列表
    const photoIds = photosToDownload.map(photo => photo.id)
    
    // 发送POST请求获取ZIP下载链接
    const response = await fetch(`/api/shares/${route.params.code}/download`, {
      method: 'POST',
      headers: {
        'Content-Type': 'application/json'
      },
      body: JSON.stringify({ photo_ids: photoIds })
    })
    
    if (!response.ok) {
      throw new Error(`HTTP error! status: ${response.status}`)
    }
    
    const data = await response.json()
    
    if (data.status === 'success') {
      if (data.download_url) {
        // 使用a标签下载ZIP文件
        const link = document.createElement('a')
        link.href = data.download_url
        link.download = 'photos.zip'
        document.body.appendChild(link)
        link.click()
        document.body.removeChild(link)
      } else if (data.photos) {
        // 如果后端返回了照片数组，逐个下载（兼容模式）
        data.photos.forEach(photo => {
          const link = document.createElement('a')
          link.href = photo.url
          link.download = photo.original_name
          document.body.appendChild(link)
          link.click()
          document.body.removeChild(link)
        })
      } else {
        // 如果没有返回ZIP链接或照片数组，使用原始照片列表逐个下载
        photosToDownload.forEach(photo => {
          const link = document.createElement('a')
          link.href = photo.url
          link.download = photo.original_name
          document.body.appendChild(link)
          link.click()
          document.body.removeChild(link)
        })
      }
    } else {
      throw new Error(data.message || '下载失败')
    }
  } catch (err) {
    console.error('Error downloading photos:', err)
    // 错误处理：如果批量下载失败，尝试逐个下载
    try {
      console.log('尝试逐个下载照片...')
      photosToDownload.forEach(photo => {
        const link = document.createElement('a')
        link.href = photo.url
        link.download = photo.original_name
        document.body.appendChild(link)
        link.click()
        document.body.removeChild(link)
      })
    } catch (fallbackErr) {
      alert(err.message || fallbackErr.message || '下载失败，请重试')
    }
  } finally {
    loading.value = false
  }
}

// 切换转存弹窗
const toggleSaveDialog = async () => {
  if (!saveDialogVisible.value) {
    // 显示弹窗之前，先检查登录状态
    await checkLoginStatus()
  }
  
  saveDialogVisible.value = !saveDialogVisible.value
  
  // 重置弹窗状态
  if (!saveDialogVisible.value) {
    selectedAlbum.value = null
    showNewAlbumForm.value = false
    newAlbumName.value = ''
    showLoginForm.value = false
    loginError.value = ''
  }
}

// 处理登录
const handleLogin = () => {
  // 实际项目中这里应该显示登录表单或跳转到登录页面
  // 这里我们显示一个简单的登录表单
  showLoginForm.value = true
}

// 登录提交
const submitLogin = async () => {
  if (!loginForm.value.username || !loginForm.value.password) {
    loginError.value = '请输入用户名和密码'
    return
  }
  
  loginLoading.value = true
  loginError.value = ''
  
  try {
    // 调用真实的登录API
    const response = await authAPI.login(loginForm.value)
    
    // 检查登录响应状态
    if (response.status === 'success' && response.token) {
      // 保存token和用户信息到localStorage
      localStorage.setItem('token', response.token)
      localStorage.setItem('user', JSON.stringify(response.user))
      
      // 更新登录状态
      isLoggedIn.value = true
      
      // 获取用户相册列表
      await fetchUserAlbums()
      
      // 隐藏登录表单
      showLoginForm.value = false
    } else {
      // 登录失败，显示错误信息
      loginError.value = response.message || '登录失败，请检查用户名和密码'
    }
  } catch (error) {
    // 处理网络错误或其他异常
    loginError.value = error.response?.data?.message || '登录失败，请检查用户名和密码'
    console.error('Login error:', error)
  } finally {
    loginLoading.value = false
  }
}

// 获取用户相册列表
const fetchUserAlbums = async () => {
  try {
    const response = await albumsAPI.getAlbums()
    userAlbums.value = response.albums || []
  } catch (error) {
    console.error('Failed to fetch user albums:', error)
    userAlbums.value = []
  }
}

// 切换新建相册表单
const toggleNewAlbumForm = () => {
  showNewAlbumForm.value = !showNewAlbumForm.value
  if (showNewAlbumForm.value) {
    selectedAlbum.value = null
  }
}

// 创建新相册
const createNewAlbum = async () => {
  if (!newAlbumName.value.trim()) {
    alert('请输入相册名称')
    return
  }
  
  try {
    // 调用真实的API创建新相册
    const response = await albumsAPI.createAlbum({ name: newAlbumName.value.trim() })
    const newAlbum = response.album || response
    
    // 添加到相册列表
    userAlbums.value.push(newAlbum)
    selectedAlbum.value = newAlbum.id
    showNewAlbumForm.value = false
    newAlbumName.value = ''
  } catch (error) {
    console.error('Failed to create album:', error)
    alert('创建相册失败')
  }
}

// 处理转存照片到相册
const handleSaveToAlbum = async () => {
  if (!selectedAlbum.value) {
    alert('请选择相册')
    return
  }
  
  if (selectedPhotos.value.length === 0) {
    alert('请选择要转存的照片')
    return
  }
  
  try {
    console.log('=== Starting photo transfer ===')
    console.log('Selected album:', selectedAlbum.value)
    console.log('Selected photos count:', selectedPhotos.value.length)
    console.log('Selected photos:', selectedPhotos.value)
    
    // 调用API将照片转存到用户相册
    const response = await api.post('/photos/transfer', {
      album_id: selectedAlbum.value,
      photos: selectedPhotos.value
    })
    
    console.log('Transfer response:', response)
    
    if (response.status === 'success') {
      alert(`成功转存 ${response.transferred_count} 张照片到相册，共 ${response.total_count} 张`)
      toggleSaveDialog()
    } else {
      console.error('Transfer failed with status:', response.status)
      alert('转存失败：' + (response.message || '未知错误'))
    }
  } catch (error) {
    console.error('Failed to save photos to album:', error)
    console.error('Error details:', error.response ? error.response.data : error.message)
    alert('转存失败，请稍后重试')
  }
}

// 检查用户登录状态
const checkLoginStatus = async () => {
  loginStatusLoading.value = true
  
  try {
    // 检查localStorage中是否有token
    const token = localStorage.getItem('token')
    const user = localStorage.getItem('user')
    
    console.log('Checking login status...')
    console.log('Current token:', token ? 'Exists' : 'None')
    console.log('Current user:', user ? 'Exists' : 'None')
    
    if (token && user) {
      // 有token和用户信息，设置为已登录状态
      console.log('Token and user found, setting as logged in')
      isLoggedIn.value = true
      await fetchUserAlbums()
    } else {
      // 没有token或用户信息，设置为未登录状态
      console.log('No token or user found, setting as not logged in')
      isLoggedIn.value = false
      userAlbums.value = []
    }
  } catch (error) {
    console.error('Error checking login status:', error)
    // 发生错误时，设置为未登录状态
    isLoggedIn.value = false
    userAlbums.value = []
    // 清除可能无效的登录数据
    localStorage.removeItem('token')
    localStorage.removeItem('user')
  } finally {
    loginStatusLoading.value = false
    showLoginForm.value = false
    loginError.value = ''
    console.log('Final login status:', isLoggedIn.value)
  }
}

// 页面加载时获取分享内容和检查登录状态
onMounted(() => {
  fetchShareContent()
  checkLoginStatus()
})

// 监听登录状态变化，获取相册列表
watch(isLoggedIn, (newValue) => {
  if (newValue) {
    fetchUserAlbums()
  }
})
</script>

<style scoped>
.shared-view-container {
  padding: 20px 0;
}

/* 加载状态 */
.loading-state {
  display: flex;
  flex-direction: column;
  align-items: center;
  justify-content: center;
  padding: 80px 20px;
  text-align: center;
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

.loading-state p {
  font-size: 16px;
  color: #888888;
}

/* 错误状态 */
.error-state {
  display: flex;
  flex-direction: column;
  align-items: center;
  justify-content: center;
  padding: 80px 20px;
  text-align: center;
}

.error-icon {
  font-size: 64px;
  margin-bottom: 16px;
  color: #e53935;
}

.error-state h2 {
  font-size: 24px;
  font-weight: 600;
  margin-bottom: 8px;
  color: #ffffff;
}

.error-state p {
  font-size: 14px;
  color: #888888;
  margin-bottom: 24px;
}

.back-link {
  display: inline-block;
  padding: 10px 20px;
  background-color: #4a4a4a;
  color: #ffffff;
  text-decoration: none;
  border-radius: 4px;
  font-size: 14px;
  transition: all 0.2s ease;
}

.back-link:hover {
  background-color: #5a5a5a;
}

/* 分享内容 */
.shared-content {
  max-width: 1200px;
  margin: 0 auto;
  padding: 0 20px;
}

.share-title {
  font-size: 28px;
  font-weight: 700;
  margin-bottom: 16px;
  color: #ffffff;
  text-align: center;
}

/* 操作按钮 */
.action-buttons {
  display: flex;
  justify-content: center;
  gap: 16px;
  margin-bottom: 24px;
}

.action-btn {
  padding: 8px 20px;
  background-color: #4a4a4a;
  color: #ffffff;
  border: none;
  border-radius: 6px;
  font-size: 14px;
  font-weight: 500;
  cursor: pointer;
  transition: all 0.2s ease;
}

.action-btn:hover {
  background-color: #5a5a5a;
  transform: translateY(-1px);
  box-shadow: 0 4px 8px rgba(0, 0, 0, 0.2);
}

.select-btn {
  background-color: #667eea;
}

.select-btn:hover {
  background-color: #5a6fd8;
}

.download-btn {
  background-color: #e53935;
}

.download-btn:hover {
  background-color: #c62828;
}

/* 亮色主题适配 */
:root.light-mode .action-btn {
  background-color: #e9ecef;
  color: #495057;
}

:root.light-mode .action-btn:hover {
  background-color: #dee2e6;
}

:root.light-mode .select-btn {
  background-color: #667eea;
  color: #ffffff;
}

:root.light-mode .select-btn:hover {
  background-color: #5a6fd8;
}

:root.light-mode .download-btn {
  background-color: #e53935;
  color: #ffffff;
}

:root.light-mode .download-btn:hover {
  background-color: #c62828;
}

.share-meta {
  display: flex;
  justify-content: center;
  gap: 16px;
  margin-bottom: 32px;
  font-size: 14px;
  color: #888888;
}

.share-type {
  background-color: #4a4a4a;
  padding: 4px 12px;
  border-radius: 12px;
  font-size: 12px;
  font-weight: 500;
}

/* 单张照片分享 */
.single-photo {
  display: flex;
  flex-direction: column;
  align-items: center;
  gap: 16px;
}

.photo-image {
  max-width: 100%;
  max-height: 70vh;
  object-fit: contain;
  border-radius: 8px;
  box-shadow: 0 4px 20px rgba(0, 0, 0, 0.3);
}

.photo-info {
  display: flex;
  gap: 20px;
  font-size: 14px;
  color: #888888;
}

.photo-name {
  font-weight: 500;
}

/* 相册分享 */
.album-photos {
  margin-top: 24px;
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

/* 照片网格 - 固定大小网格布局 */
.photos-grid {
  display: grid;
  grid-template-columns: repeat(auto-fill, minmax(120px, 1fr));
  gap: 8px;
  margin-bottom: 20px;
}

/* 照片卡片 */
.photo-card {
  background-color: #1a1a1a;
  border-radius: 6px;
  overflow: hidden;
  cursor: pointer;
  transition: all 0.2s ease;
  position: relative;
  border: 1px solid transparent;
  aspect-ratio: 1;
}

.photo-card:hover {
  transform: scale(1.02);
  box-shadow: 0 4px 12px rgba(0, 0, 0, 0.3);
  border-color: rgba(255, 255, 255, 0.1);
}

/* 照片选择框 */
.photo-selector {
  position: absolute;
  top: 8px;
  right: 8px;
  z-index: 10;
  background-color: rgba(0, 0, 0, 0.7);
  border-radius: 50%;
  padding: 2px;
}

.photo-selector input[type="checkbox"] {
  width: 18px;
  height: 18px;
  cursor: pointer;
  accent-color: #667eea;
}

/* 照片图片样式 */
.photo-image {
  width: 100%;
  height: 100%;
  display: block;
  border-radius: 6px;
  transition: all 0.3s ease;
  object-fit: cover;
  object-position: center;
}

.photo-card:hover .photo-image {
  filter: brightness(1.1);
}

/* 禁用状态的下载按钮 */
.action-btn:disabled {
  opacity: 0.5;
  cursor: not-allowed;
  transform: none;
  box-shadow: none;
}

.action-btn:disabled:hover {
  transform: none;
  box-shadow: none;
}

/* 亮色主题适配 */
:root.light-mode .photo-selector {
  background-color: rgba(255, 255, 255, 0.7);
}

:root.light-mode .photo-selector input[type="checkbox"] {
  accent-color: #667eea;
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
:root.light-mode .error-state h2 {
  color: #212529;
}

:root.light-mode .error-state p {
  color: #6c757d;
}

:root.light-mode .back-link {
  background-color: #e9ecef;
  color: #495057;
}

:root.light-mode .back-link:hover {
  background-color: #dee2e6;
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

:root.light-mode .photo-info {
  color: #6c757d;
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

:root.light-mode .loading-state p {
  color: #6c757d;
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
  
  .share-title {
    font-size: 24px;
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
  
  .close-btn {
    font-size: 24px;
    top: 10px;
    right: 10px;
  }
}

@media (max-width: 480px) {
  .photos-grid {
    grid-template-columns: repeat(auto-fill, minmax(70px, 1fr));
    gap: 3px;
  }
}

/* 一键转存按钮样式 */
.save-btn {
  background-color: #43a047;
}

.save-btn:hover {
  background-color: #388e3c;
}

/* 亮色主题适配 */
:root.light-mode .save-btn {
  background-color: #43a047;
  color: #ffffff;
}

:root.light-mode .save-btn:hover {
  background-color: #388e3c;
}

/* 一键转存弹窗样式 */
.dialog-overlay {
  position: fixed;
  top: 0;
  left: 0;
  right: 0;
  bottom: 0;
  background-color: rgba(0, 0, 0, 0.5);
  display: flex;
  align-items: center;
  justify-content: center;
  z-index: 1100;
}

.dialog-content {
  background-color: #1a1a1a;
  border-radius: 8px;
  width: 90%;
  max-width: 500px;
  max-height: 80vh;
  overflow: hidden;
  box-shadow: 0 4px 20px rgba(0, 0, 0, 0.3);
}

.dialog-header {
  display: flex;
  justify-content: space-between;
  align-items: center;
  padding: 16px 20px;
  border-bottom: 1px solid #333333;
}

.dialog-header h3 {
  margin: 0;
  font-size: 18px;
  font-weight: 600;
  color: #ffffff;
}

.dialog-close-btn {
  background: none;
  border: none;
  color: #888888;
  font-size: 24px;
  cursor: pointer;
  padding: 0;
  width: 30px;
  height: 30px;
  display: flex;
  align-items: center;
  justify-content: center;
  transition: color 0.2s ease;
}

.dialog-close-btn:hover {
  color: #ffffff;
}

.dialog-body {
  padding: 20px 30px;
  max-height: 50vh;
  overflow-y: auto;
}

.login-prompt {
  text-align: center;
  padding: 20px 0;
}

.login-prompt p {
  margin-bottom: 20px;
  color: #888888;
}

.login-btn {
  padding: 10px 30px;
  background-color: #667eea;
  color: #ffffff;
  border: none;
  border-radius: 6px;
  font-size: 14px;
  font-weight: 500;
  cursor: pointer;
  transition: all 0.2s ease;
}

.login-btn:hover {
  background-color: #5a6fd8;
  transform: translateY(-1px);
  box-shadow: 0 4px 8px rgba(0, 0, 0, 0.2);
}

.dialog-body h4 {
  margin: 0 0 16px 0;
  font-size: 16px;
  font-weight: 600;
  color: #ffffff;
}

.album-list {
  max-height: 300px;
  overflow-y: auto;
  overflow-x: hidden;
  padding: 0;
}

/* 自定义滚动条样式 */
.dialog-body::-webkit-scrollbar,
.album-list::-webkit-scrollbar {
  width: 8px;
}

.dialog-body::-webkit-scrollbar-track,
.album-list::-webkit-scrollbar-track {
  background: #1a1a1a;
  border-radius: 4px;
}

.dialog-body::-webkit-scrollbar-thumb,
.album-list::-webkit-scrollbar-thumb {
  background: #4a4a4a;
  border-radius: 4px;
}

.dialog-body::-webkit-scrollbar-thumb:hover,
.album-list::-webkit-scrollbar-thumb:hover {
  background: #5a5a5a;
}

.album-item {
  display: flex;
  align-items: center;
  justify-content: space-between;
  padding: 12px 16px;
  margin-bottom: 8px;
  background-color: #2a2a2a;
  border-radius: 6px;
  cursor: pointer;
  transition: all 0.2s ease;
  border: 1px solid transparent;
  width: 100%;
  box-sizing: border-box;
}

.album-item:hover {
  background-color: #3a3a3a;
  transform: translateX(4px);
}

.album-item.selected {
  background-color: #667eea;
  border-color: #667eea;
}

.album-name {
  font-size: 14px;
  font-weight: 500;
  color: #ffffff;
  overflow: hidden;
  text-overflow: ellipsis;
  white-space: nowrap;
  max-width: 70%;
}

.album-count {
  font-size: 12px;
  color: #888888;
  white-space: nowrap;
}

.album-item.selected .album-count {
  color: rgba(255, 255, 255, 0.8);
}

.create-album-item {
  background-color: transparent;
  border: 1px dashed #666666;
  justify-content: center;
  gap: 8px;
}

.create-album-item:hover {
  background-color: rgba(255, 255, 255, 0.05);
  border-color: #667eea;
}

.create-album-icon {
  font-size: 18px;
  font-weight: bold;
  color: #667eea;
}

.new-album-form {
  margin-top: 16px;
  padding: 16px;
  background-color: #2a2a2a;
  border-radius: 6px;
  box-sizing: border-box;
}

.new-album-input {
  width: 100%;
  padding: 10px 12px;
  margin-bottom: 12px;
  background-color: #3a3a3a;
  border: 1px solid #444444;
  border-radius: 4px;
  color: #ffffff;
  font-size: 14px;
  outline: none;
  transition: all 0.2s ease;
  box-sizing: border-box;
}

.new-album-input:focus {
  border-color: #667eea;
  box-shadow: 0 0 0 2px rgba(102, 126, 234, 0.2);
}

.new-album-actions {
  display: flex;
  gap: 12px;
  justify-content: flex-end;
}

.dialog-footer {
  display: flex;
  justify-content: flex-end;
  gap: 12px;
  padding: 16px 20px;
  border-top: 1px solid #333333;
  background-color: #222222;
}

.cancel-btn {
  padding: 8px 20px;
  background-color: #4a4a4a;
  color: #ffffff;
  border: none;
  border-radius: 6px;
  font-size: 14px;
  font-weight: 500;
  cursor: pointer;
  transition: all 0.2s ease;
}

.cancel-btn:hover {
  background-color: #5a5a5a;
}

.confirm-btn {
  padding: 8px 20px;
  background-color: #667eea;
  color: #ffffff;
  border: none;
  border-radius: 6px;
  font-size: 14px;
  font-weight: 500;
  cursor: pointer;
  transition: all 0.2s ease;
}

.confirm-btn:hover:not(:disabled) {
  background-color: #5a6fd8;
  transform: translateY(-1px);
  box-shadow: 0 4px 8px rgba(0, 0, 0, 0.2);
}

.confirm-btn:disabled {
  opacity: 0.5;
  cursor: not-allowed;
  transform: none;
  box-shadow: none;
}

/* 亮色主题适配 */
:root.light-mode .dialog-content {
  background-color: #ffffff;
  box-shadow: 0 4px 20px rgba(0, 0, 0, 0.15);
}

:root.light-mode .dialog-header {
  border-bottom: 1px solid #e0e0e0;
}

:root.light-mode .dialog-header h3 {
  color: #212529;
}

:root.light-mode .dialog-close-btn {
  color: #6c757d;
}

:root.light-mode .dialog-close-btn:hover {
  color: #212529;
}

:root.light-mode .dialog-body h4 {
  color: #212529;
}

:root.light-mode .album-item {
  background-color: #f8f9fa;
  border: 1px solid #e9ecef;
}

:root.light-mode .album-item:hover {
  background-color: #e9ecef;
}

:root.light-mode .album-item.selected {
  background-color: #667eea;
  border-color: #667eea;
}

:root.light-mode .album-name {
  color: #212529;
}

:root.light-mode .album-count {
  color: #6c757d;
}

:root.light-mode .album-item.selected .album-name,
:root.light-mode .album-item.selected .album-count {
  color: #ffffff;
}

:root.light-mode .create-album-item {
  background-color: transparent;
  border: 1px dashed #adb5bd;
}

:root.light-mode .create-album-item:hover {
  background-color: rgba(0, 0, 0, 0.05);
  border-color: #667eea;
}

:root.light-mode .new-album-form {
  background-color: #f8f9fa;
  border: 1px solid #e9ecef;
}

:root.light-mode .new-album-input {
  background-color: #ffffff;
  border: 1px solid #ced4da;
  color: #212529;
}

:root.light-mode .new-album-input:focus {
  border-color: #667eea;
  box-shadow: 0 0 0 2px rgba(102, 126, 234, 0.2);
}

:root.light-mode .dialog-footer {
  border-top: 1px solid #e0e0e0;
  background-color: #f8f9fa;
}

:root.light-mode .cancel-btn {
  background-color: #e9ecef;
  color: #212529;
}

:root.light-mode .cancel-btn:hover {
  background-color: #dee2e6;
}

/* 登录表单样式 */
.login-form {
  padding: 20px 0;
}

.form-group {
  margin-bottom: 16px;
}

.form-group label {
  display: block;
  margin-bottom: 8px;
  font-size: 14px;
  font-weight: 500;
  color: #ffffff;
}

.form-input {
  width: 100%;
  padding: 10px 12px;
  background-color: #3a3a3a;
  border: 1px solid #444444;
  border-radius: 4px;
  color: #ffffff;
  font-size: 14px;
  outline: none;
  transition: all 0.2s ease;
}

.form-input:focus {
  border-color: #667eea;
  box-shadow: 0 0 0 2px rgba(102, 126, 234, 0.2);
}

.login-error {
  margin-bottom: 16px;
  padding: 10px 12px;
  background-color: rgba(229, 57, 53, 0.1);
  border: 1px solid #e53935;
  border-radius: 4px;
  color: #e53935;
  font-size: 14px;
}

.form-actions {
  display: flex;
  justify-content: flex-end;
  gap: 12px;
  margin-top: 20px;
}

/* 亮色主题适配 */
:root.light-mode .form-group label {
  color: #212529;
}

:root.light-mode .form-input {
  background-color: #ffffff;
  border: 1px solid #ced4da;
  color: #212529;
}

:root.light-mode .form-input:focus {
  border-color: #667eea;
  box-shadow: 0 0 0 2px rgba(102, 126, 234, 0.2);
}

:root.light-mode .login-error {
  background-color: rgba(229, 57, 53, 0.1);
  border: 1px solid #e53935;
  color: #e53935;
}

:root.light-mode .confirm-btn {
  background-color: #667eea;
  color: #ffffff;
}

:root.light-mode .confirm-btn:hover:not(:disabled) {
  background-color: #5a6fd8;
}
</style>