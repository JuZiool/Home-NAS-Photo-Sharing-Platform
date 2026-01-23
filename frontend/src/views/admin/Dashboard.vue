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
        <div class="activity-item" v-for="(activity, index) in recentActivities" :key="index">
          <div class="activity-icon">{{ activity.icon }}</div>
          <div class="activity-content">
            <div class="activity-text">{{ activity.text }}</div>
            <div class="activity-time">{{ activity.time }}</div>
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

const userCount = ref(0)
const photoCount = ref(0)
const albumCount = ref(0)
const adminCount = ref(0)
const recentActivities = ref([
  { icon: '📝', text: '新用户 testuser 注册', time: '5分钟前' },
  { icon: '📸', text: '用户 testuser 上传了10张照片', time: '1小时前' },
  { icon: '🗂️', text: '用户 testuser 创建了相册 "旅行回忆"', time: '2小时前' },
  { icon: '👑', text: '管理员 admin 登录系统', time: '3小时前' }
])

// 获取统计数据（当前为模拟数据，未来可替换为真实API调用）
const fetchStats = () => {
  // 模拟API调用延迟
  setTimeout(() => {
    userCount.value = 128
    photoCount.value = 2560
    albumCount.value = 156
    adminCount.value = 3
  }, 500)
}

onMounted(() => {
  fetchStats()
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