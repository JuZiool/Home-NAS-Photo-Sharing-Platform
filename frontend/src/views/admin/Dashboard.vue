<template>
  <div class="dashboard-container">
    <h3>系统概览</h3>
    <div class="stats-grid">
      <div class="stat-card">
        <div class="stat-icon users-icon">👥</div>
        <div class="stat-content">
          <div class="stat-value">{{ userCount }}</div>
          <div class="stat-label">注册用户</div>
        </div>
      </div>
      <div class="stat-card">
        <div class="stat-icon photos-icon">📸</div>
        <div class="stat-content">
          <div class="stat-value">{{ photoCount }}</div>
          <div class="stat-label">照片数量</div>
        </div>
      </div>
      <div class="stat-card">
        <div class="stat-icon albums-icon">🗂️</div>
        <div class="stat-content">
          <div class="stat-value">{{ albumCount }}</div>
          <div class="stat-label">相册数量</div>
        </div>
      </div>
      <div class="stat-card">
        <div class="stat-icon admins-icon">🛡️</div>
        <div class="stat-content">
          <div class="stat-value">{{ adminCount }}</div>
          <div class="stat-label">管理员数量</div>
        </div>
      </div>
    </div>
    
    <div class="recent-activity">
      <h3>最近活动</h3>
      <div class="activity-list">
        <div class="activity-item" v-for="activity in recentActivities" :key="activity.id">
          <div class="activity-icon">{{ getActivityIcon(activity.action_type) }}</div>
          <div class="activity-content">
            <div class="activity-text">{{ activity.action_description }}</div>
            <div class="activity-time">{{ formatTime(activity.created_at) }}</div>
          </div>
        </div>
        <div v-if="recentActivities.length === 0" class="no-activity">
          暂无活动记录
        </div>
      </div>
    </div>
  </div>
</template>

<script setup>
import { ref, onMounted } from 'vue'
import { adminAPI } from '@/services/api'

const userCount = ref(0)
const photoCount = ref(0)
const albumCount = ref(0)
const adminCount = ref(0)
const recentActivities = ref([])

// 获取统计数据
const fetchStats = async () => {
  try {
    const response = await adminAPI.getStats()
    if (response.status === 'success') {
      const stats = response.stats
      userCount.value = stats.userCount
      photoCount.value = stats.photoCount
      albumCount.value = stats.albumCount
      adminCount.value = stats.adminCount
    }
  } catch (error) {
    console.error('获取统计数据失败:', error)
  }
}

// 获取最近活动记录
const fetchRecentActivities = async () => {
  try {
    const response = await adminAPI.getActivityLogs()
    if (response.status === 'success') {
      recentActivities.value = response.activities || []
    }
  } catch (error) {
    console.error('获取最近活动失败:', error)
  }
}

// 根据活动类型获取图标
const getActivityIcon = (actionType) => {
  const iconMap = {
    'login': '👑',
    'register': '📝',
    'upload_photo': '📸',
    'create_album': '🗂️',
    'update_album': '✏️',
    'delete_album': '🗑️',
    'update_user': '👥',
    'delete_user': '❌'
  }
  return iconMap[actionType] || '🔔'
}

// 格式化时间显示
const formatTime = (timeString) => {
  const now = new Date()
  const activityTime = new Date(timeString)
  const diffInSeconds = Math.floor((now - activityTime) / 1000)
  
  if (diffInSeconds < 60) {
    return `${diffInSeconds}秒前`
  } else if (diffInSeconds < 3600) {
    return `${Math.floor(diffInSeconds / 60)}分钟前`
  } else if (diffInSeconds < 86400) {
    return `${Math.floor(diffInSeconds / 3600)}小时前`
  } else {
    return `${Math.floor(diffInSeconds / 86400)}天前`
  }
}

onMounted(() => {
  fetchStats()
  fetchRecentActivities()
})
</script>

<style scoped>
.dashboard-container {
  display: flex;
  flex-direction: column;
  gap: 20px;
}

h3 {
  margin: 0;
  font-size: 18px;
  color: #1f2937;
}

/* 统计卡片样式 */
.stats-grid {
  display: grid;
  grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
  gap: 20px;
}

.stat-card {
  background: white;
  padding: 25px;
  border-radius: 12px;
  box-shadow: 0 2px 8px rgba(0, 0, 0, 0.08);
  display: flex;
  align-items: center;
  gap: 15px;
  transition: transform 0.3s ease, box-shadow 0.3s ease;
}

.stat-card:hover {
  transform: translateY(-2px);
  box-shadow: 0 4px 16px rgba(0, 0, 0, 0.12);
}

.stat-icon {
  width: 50px;
  height: 50px;
  border-radius: 12px;
  display: flex;
  align-items: center;
  justify-content: center;
  font-size: 24px;
}

.users-icon {
  background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
  color: white;
}

.photos-icon {
  background: linear-gradient(135deg, #f093fb 0%, #f5576c 100%);
  color: white;
}

.albums-icon {
  background: linear-gradient(135deg, #4facfe 0%, #00f2fe 100%);
  color: white;
}

.admins-icon {
  background: linear-gradient(135deg, #fa709a 0%, #fee140 100%);
  color: white;
}

.stat-content {
  flex: 1;
}

.stat-value {
  font-size: 32px;
  font-weight: bold;
  color: #1f2937;
  line-height: 1;
}

.stat-label {
  font-size: 14px;
  color: #6b7280;
  margin-top: 5px;
}

/* 最近活动样式 */
.recent-activity {
  background: white;
  padding: 25px;
  border-radius: 12px;
  box-shadow: 0 2px 8px rgba(0, 0, 0, 0.08);
}

.activity-list {
  margin-top: 15px;
  display: flex;
  flex-direction: column;
  gap: 15px;
}

.activity-item {
  display: flex;
  align-items: flex-start;
  gap: 12px;
  padding: 15px;
  border-radius: 8px;
  background: #f9fafb;
  transition: background-color 0.2s ease;
}

.activity-item:hover {
  background: #f3f4f6;
}

.activity-icon {
  font-size: 20px;
  margin-top: 2px;
}

.activity-content {
  flex: 1;
}

.activity-text {
  font-size: 14px;
  color: #1f2937;
  margin-bottom: 4px;
}

.activity-time {
  font-size: 12px;
  color: #9ca3af;
}

.no-activity {
  text-align: center;
  padding: 30px;
  color: #9ca3af;
  font-size: 14px;
  background: #f9fafb;
  border-radius: 8px;
}
</style>