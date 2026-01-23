<template>
  <div class="admin-container">
    <aside class="admin-sidebar">
      <div class="sidebar-header">
        <div class="logo">
          <span>⚙️</span>
          <h1>Admin Panel</h1>
        </div>
      </div>
      
      <nav class="sidebar-nav">
        <div 
          class="nav-item" 
          :class="{ active: isActive('/admin') }"
          @click="navigateTo('/admin')"
        >
          <span class="nav-icon">📊</span>
          <span class="nav-text">仪表盘</span>
        </div>
        <div 
          class="nav-item" 
          :class="{ active: isActive('/admin/users') }"
          @click="navigateTo('/admin/users')"
        >
          <span class="nav-icon">👥</span>
          <span class="nav-text">用户管理</span>
        </div>
        <!-- 未来可以添加更多管理员功能 -->
        <!-- <div 
          class="nav-item" 
          :class="{ active: isActive('/admin/photos') }"
          @click="navigateTo('/admin/photos')"
        >
          <span class="nav-icon">📸</span>
          <span class="nav-text">照片管理</span>
        </div> -->
      </nav>
      <div class="sidebar-footer">
        <button class="logout-btn" @click="logout">
          <span class="nav-icon">🚪</span>
          <span class="nav-text">退出登录</span>
        </button>
      </div>
    </aside>
    <main class="admin-main">
      <header class="main-header">
        <div class="header-content">
          <h2>{{ currentPage }}</h2>
          <div class="user-info">
            <span class="welcome-text">欢迎, {{ user?.username || '管理员' }}</span>
          </div>
        </div>
      </header>
      <div class="main-content">
        <router-view />
      </div>
    </main>
  </div>
</template>

<script setup>
import { ref, computed, onMounted } from 'vue'
import { useRouter } from 'vue-router'

const router = useRouter()
const user = ref(null)

// 计算当前页面标题
const currentPage = computed(() => {
  const route = router.currentRoute.value
  if (route.path === '/admin') return '仪表盘'
  if (route.path === '/admin/users') return '用户管理'
  return '管理员面板'
})

// 导航到指定路径
const navigateTo = (path) => {
  router.push(path)
}

// 检查当前路径是否激活
const isActive = (path) => {
  const currentPath = router.currentRoute.value.path
  return currentPath === path
}

// 获取用户信息
const getUserInfo = () => {
  const userStr = localStorage.getItem('user') || sessionStorage.getItem('user')
  if (userStr) {
    user.value = JSON.parse(userStr)
  }
}

// 退出登录
const logout = () => {
  // 不清除登录状态，直接重定向到dashboard页面
  router.push('/dashboard')
}

onMounted(() => {
  getUserInfo()
})
</script>

<style scoped>
.admin-container {
  display: flex;
  height: 100vh;
  font-family: Arial, sans-serif;
}

/* 侧边栏样式 */
.admin-sidebar {
  width: 250px;
  background: linear-gradient(180deg, #1e3a8a 0%, #1e40af 100%);
  color: white;
  display: flex;
  flex-direction: column;
  box-shadow: 2px 0 10px rgba(0, 0, 0, 0.1);
}

.sidebar-header {
  padding: 20px;
  border-bottom: 1px solid rgba(255, 255, 255, 0.1);
}

.logo {
  display: flex;
  align-items: center;
  gap: 10px;
}

.logo span {
  font-size: 24px;
}

.logo h1 {
  font-size: 18px;
  font-weight: bold;
  margin: 0;
}

.sidebar-nav {
  flex: 1;
  padding: 20px 0;
}

.nav-item {
  display: flex;
  align-items: center;
  gap: 12px;
  padding: 12px 20px;
  color: white;
  text-decoration: none;
  transition: all 0.3s ease;
  border-left: 3px solid transparent;
}

.nav-item:hover {
  background: rgba(255, 255, 255, 0.1);
  border-left-color: #3b82f6;
}

.nav-item.active {
  background: rgba(255, 255, 255, 0.15);
  border-left-color: #3b82f6;
  font-weight: bold;
}

.nav-icon {
  font-size: 18px;
}

.sidebar-footer {
  padding: 20px;
  border-top: 1px solid rgba(255, 255, 255, 0.1);
}

.logout-btn {
  display: flex;
  align-items: center;
  gap: 12px;
  width: 100%;
  padding: 12px;
  background: rgba(239, 68, 68, 0.2);
  color: white;
  border: none;
  border-radius: 6px;
  cursor: pointer;
  transition: all 0.3s ease;
  font-size: 14px;
}

.logout-btn:hover {
  background: rgba(239, 68, 68, 0.3);
}

/* 主内容区域样式 */
.admin-main {
  flex: 1;
  display: flex;
  flex-direction: column;
  background: #f3f4f6;
  overflow: hidden;
}

.main-header {
  background: white;
  padding: 20px 30px;
  box-shadow: 0 2px 4px rgba(0, 0, 0, 0.1);
}

.header-content {
  display: flex;
  justify-content: space-between;
  align-items: center;
}

.header-content h2 {
  margin: 0;
  font-size: 24px;
  color: #1f2937;
}

.user-info {
  display: flex;
  align-items: center;
  gap: 10px;
}

.welcome-text {
  font-size: 14px;
  color: #6b7280;
}

.main-content {
  flex: 1;
  padding: 30px;
  overflow-y: auto;
}
</style>