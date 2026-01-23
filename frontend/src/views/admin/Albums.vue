<template>
  <div class="albums-container">
    <h2>相册管理</h2>
    
    <!-- 搜索和筛选 -->
    <div class="albums-header">
      <div class="search-bar">
        <input 
          type="text" 
          placeholder="搜索相册名称或用户..." 
          v-model="searchQuery"
          @input="handleSearch"
        />
      </div>
      <div class="albums-count">
        共 {{ filteredAlbums.length }} 个相册
      </div>
    </div>
    
    <!-- 相册列表 -->
    <div class="albums-list">
      <div class="album-card" 
           v-for="album in filteredAlbums" 
           :key="album.id"
           @click="showAlbumDetails(album)">
        <div class="album-info">
          <h3 class="album-name">{{ album.name }}</h3>
          <p class="album-owner">
            <span class="label">所属用户：</span>
            <span class="value">{{ album.username }}</span>
          </p>
          <p class="album-description">
            <span class="label">描述：</span>
            <span class="value">{{ album.description || '无描述' }}</span>
          </p>
          <div class="album-meta">
            <span class="meta-item">
              <i class="meta-icon">📅</i>
              {{ formatDate(album.created_at) }}
            </span>
            <button class="delete-btn" @click.stop="deleteAlbum(album.id, album.name)">
              删除
            </button>
          </div>
        </div>
      </div>
      
      <!-- 空状态 -->
      <div v-if="filteredAlbums.length === 0" class="empty-state">
        <div class="empty-icon">🗂️</div>
        <p>暂无相册记录</p>
      </div>
    </div>
    
    <!-- 相册详情弹窗 -->
    <div v-if="selectedAlbum" class="modal-overlay" @click="closeAlbumDetails">
      <div class="modal-content" @click.stop>
        <div class="modal-header">
          <h3>{{ selectedAlbum.name }}</h3>
          <button class="close-btn" @click="closeAlbumDetails">×</button>
        </div>
        <div class="modal-body">
          <!-- 固定信息区域 -->
          <div class="album-details-info">
            <div class="detail-item">
              <span class="detail-label">所属用户：</span>
              <span class="detail-value">{{ selectedAlbum.username }}</span>
            </div>
            <div class="detail-item">
              <span class="detail-label">创建时间：</span>
              <span class="detail-value">{{ formatDate(selectedAlbum.created_at) }}</span>
            </div>
            <div class="detail-item">
              <span class="detail-label">更新时间：</span>
              <span class="detail-value">{{ formatDate(selectedAlbum.updated_at) }}</span>
            </div>
            <div class="detail-item">
              <span class="detail-label">描述：</span>
              <span class="detail-value">{{ selectedAlbum.description || '无描述' }}</span>
            </div>
          </div>
          
          <!-- 可滚动照片区域 -->
          <div class="album-photos-scrollable">
            <!-- 相册中的照片 -->
            <div class="album-photos">
              <h4>相册中的照片 ({{ albumPhotos.length }})</h4>
              <div class="photos-grid" v-if="albumPhotos.length > 0">
                <div class="photo-item" v-for="photo in albumPhotos" :key="photo.id">
                  <img :src="photo.thumbnail_url || photo.url" :alt="photo.original_name" class="photo-thumbnail" />
                  <div class="photo-name">{{ photo.original_name }}</div>
                </div>
              </div>
              <div v-else class="no-photos">
                该相册中暂无照片
              </div>
            </div>
          </div>
        </div>
        <div class="modal-footer">
          <button class="confirm-delete-btn" @click="deleteAlbum(selectedAlbum.id, selectedAlbum.name)">
            删除相册
          </button>
        </div>
      </div>
    </div>
  </div>
</template>

<script setup>
import { ref, computed, onMounted } from 'vue'
import { adminAPI } from '@/services/api'

// 相册数据
const albums = ref([])
const filteredAlbums = ref([])
const searchQuery = ref('')
const selectedAlbum = ref(null)
const albumPhotos = ref([])

// 加载所有相册
const loadAlbums = async () => {
  try {
    const response = await adminAPI.getAlbums()
    if (response.status === 'success') {
      albums.value = response.albums
      filteredAlbums.value = response.albums
    }
  } catch (error) {
    console.error('获取相册列表失败:', error)
  }
}

// 搜索处理
const handleSearch = () => {
  const query = searchQuery.value.toLowerCase()
  filteredAlbums.value = albums.value.filter(album => {
    return album.name.toLowerCase().includes(query) || 
           album.username.toLowerCase().includes(query) ||
           (album.description && album.description.toLowerCase().includes(query))
  })
}

// 显示相册详情
const showAlbumDetails = async (album) => {
  selectedAlbum.value = album
  try {
    const response = await adminAPI.getAlbumPhotos(album.id)
    if (response.status === 'success') {
      albumPhotos.value = response.photos
    }
  } catch (error) {
    console.error('获取相册照片失败:', error)
    albumPhotos.value = []
  }
}

// 关闭相册详情
const closeAlbumDetails = () => {
  selectedAlbum.value = null
  albumPhotos.value = []
}

// 删除相册
const deleteAlbum = async (albumId, albumName) => {
  if (confirm(`确定要删除相册 "${albumName}" 吗？此操作将删除相册中的所有照片，且无法恢复。`)) {
    try {
      const response = await adminAPI.deleteAlbum(albumId)
      if (response.status === 'success') {
        // 更新列表
        albums.value = albums.value.filter(album => album.id !== albumId)
        filteredAlbums.value = filteredAlbums.value.filter(album => album.id !== albumId)
        closeAlbumDetails()
      }
    } catch (error) {
      console.error('删除相册失败:', error)
    }
  }
}

// 日期格式化
const formatDate = (dateString) => {
  const date = new Date(dateString)
  return date.toLocaleString('zh-CN')
}

// 初始加载
onMounted(() => {
  loadAlbums()
})
</script>

<style scoped>
.albums-container {
  padding: 20px;
  max-width: 1200px;
  margin: 0 auto;
}

.albums-header {
  display: flex;
  justify-content: space-between;
  align-items: center;
  margin-bottom: 20px;
  flex-wrap: wrap;
  gap: 15px;
}

.search-bar {
  flex: 1;
  max-width: 400px;
}

.search-bar input {
  width: 100%;
  padding: 10px 15px;
  border: 1px solid #ddd;
  border-radius: 8px;
  font-size: 14px;
  transition: all 0.3s ease;
}

.search-bar input:focus {
  outline: none;
  border-color: #4facfe;
  box-shadow: 0 0 0 2px rgba(79, 172, 254, 0.1);
}

.albums-count {
  color: #666;
  font-size: 14px;
}

.albums-list {
  display: grid;
  grid-template-columns: repeat(auto-fill, minmax(350px, 1fr));
  gap: 20px;
}

.album-card {
  background: white;
  border: 1px solid #e1e8ed;
  border-radius: 12px;
  padding: 20px;
  cursor: pointer;
  transition: all 0.3s ease;
  box-shadow: 0 2px 8px rgba(0, 0, 0, 0.05);
}

.album-card:hover {
  transform: translateY(-2px);
  box-shadow: 0 4px 16px rgba(0, 0, 0, 0.1);
  border-color: #4facfe;
}

.album-info {
  width: 100%;
}

.album-name {
  margin: 0 0 12px 0;
  font-size: 18px;
  font-weight: 600;
  color: #1f2937;
}

.album-owner, .album-description {
  margin: 8px 0;
  font-size: 14px;
  line-height: 1.5;
}

.label {
  color: #6b7280;
  margin-right: 5px;
  font-weight: 500;
}

.value {
  color: #374151;
}

.album-meta {
  display: flex;
  justify-content: space-between;
  align-items: center;
  margin-top: 15px;
  padding-top: 15px;
  border-top: 1px solid #f3f4f6;
}

.meta-item {
  display: flex;
  align-items: center;
  gap: 5px;
  color: #6b7280;
  font-size: 13px;
}

.meta-icon {
  font-size: 16px;
}

.delete-btn {
  background-color: #ef4444;
  color: white;
  border: none;
  padding: 8px 15px;
  border-radius: 6px;
  font-size: 13px;
  cursor: pointer;
  transition: all 0.2s ease;
}

.delete-btn:hover {
  background-color: #dc2626;
  transform: translateY(-1px);
}

.empty-state {
  grid-column: 1 / -1;
  text-align: center;
  padding: 60px 20px;
  color: #9ca3af;
  background: #f9fafb;
  border-radius: 12px;
  border: 1px dashed #e5e7eb;
}

.empty-icon {
  font-size: 48px;
  margin-bottom: 15px;
  opacity: 0.5;
}

/* 弹窗样式 */
.modal-overlay {
  position: fixed;
  top: 0;
  left: 0;
  right: 0;
  bottom: 0;
  background: rgba(0, 0, 0, 0.5);
  display: flex;
  justify-content: center;
  align-items: center;
  z-index: 1000;
}

.modal-content {
  background: white;
  border-radius: 12px;
  max-width: 90vw;
  width: 90%;
  max-height: 90vh;
  overflow-y: auto;
  box-shadow: 0 20px 60px rgba(0, 0, 0, 0.3);
  /* 隐藏滚动条但保留滚动功能 */
  -ms-overflow-style: none;  /* IE and Edge */
  scrollbar-width: none;  /* Firefox */
}

/* Chrome, Safari and Opera */
.modal-content::-webkit-scrollbar {
  display: none;
}

.modal-header {
  display: flex;
  justify-content: space-between;
  align-items: center;
  padding: 20px;
  border-bottom: 1px solid #e5e7eb;
}

.modal-header h3 {
  margin: 0;
  font-size: 20px;
  color: #1f2937;
}

.close-btn {
  background: none;
  border: none;
  font-size: 24px;
  cursor: pointer;
  color: #6b7280;
  padding: 0;
  width: 30px;
  height: 30px;
  display: flex;
  align-items: center;
  justify-content: center;
  border-radius: 50%;
  transition: all 0.2s ease;
}

.close-btn:hover {
  background: #f3f4f6;
  color: #374151;
}

.modal-body {
  padding: 20px;
  display: flex;
  flex-direction: column;
  gap: 20px;
  max-height: calc(80vh - 140px); /* 减去header和footer的高度 */
}

.album-details-info {
  flex-shrink: 0; /* 固定高度，不随滚动变化 */
}

.detail-item {
  display: flex;
  margin-bottom: 12px;
  font-size: 14px;
  line-height: 1.6;
}

.detail-label {
  width: 100px;
  color: #6b7280;
  font-weight: 500;
  flex-shrink: 0;
}

.detail-value {
  color: #374151;
  flex: 1;
}

.album-photos-scrollable {
  flex: 1;
  overflow-y: auto;
  padding-right: 8px; /* 为滚动预留空间 */
  /* 隐藏滚动条但保留滚动功能 */
  -ms-overflow-style: none;  /* IE and Edge */
  scrollbar-width: none;  /* Firefox */
}

/* Chrome, Safari and Opera */
.album-photos-scrollable::-webkit-scrollbar {
  display: none;
}

.album-photos {
  border-top: 1px solid #e5e7eb;
  padding-top: 20px;
}

.album-photos h4 {
  margin: 0 0 15px 0;
  font-size: 16px;
  color: #1f2937;
}

.photos-grid {
  display: grid;
  grid-template-columns: repeat(auto-fill, minmax(120px, 1fr));
  gap: 10px;
}

.photo-item {
  text-align: center;
}

.photo-thumbnail {
  width: 100%;
  height: 120px;
  object-fit: cover;
  border-radius: 8px;
  border: 1px solid #e5e7eb;
  margin-bottom: 5px;
}

.photo-name {
  font-size: 12px;
  color: #6b7280;
  overflow: hidden;
  text-overflow: ellipsis;
  white-space: nowrap;
}

.no-photos {
  text-align: center;
  color: #9ca3af;
  padding: 30px 0;
  font-size: 14px;
}

.modal-footer {
  padding: 20px;
  border-top: 1px solid #e5e7eb;
  text-align: right;
}

.confirm-delete-btn {
  background-color: #ef4444;
  color: white;
  border: none;
  padding: 10px 20px;
  border-radius: 8px;
  font-size: 14px;
  cursor: pointer;
  transition: all 0.2s ease;
}

.confirm-delete-btn:hover {
  background-color: #dc2626;
  transform: translateY(-1px);
}
</style>