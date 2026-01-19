<template>
  <div class="main-layout">
    <!-- 左侧导航栏 -->
    <aside class="sidebar">
      <div class="sidebar-header">
        <div class="logo">
          <span>📸</span>
          <span class="logo-text">照片分享</span>
        </div>
      </div>
      
      <nav class="nav-menu">
        <div class="nav-section">
          <router-link to="/dashboard" class="nav-item" active-class="active">
            <span class="nav-icon">🖼️</span>
            <span class="nav-text">照片</span>
          </router-link>
          <router-link to="/sharing" class="nav-item" active-class="active">
            <span class="nav-icon">👥</span>
            <span class="nav-text">分享</span>
          </router-link>
        </div>
        
        <div class="nav-section">
          <div class="section-title">资料库</div>
          <router-link to="/favorites" class="nav-item" active-class="active">
            <span class="nav-icon">❤️</span>
            <span class="nav-text">收藏</span>
          </router-link>
          <router-link to="/albums" class="nav-item" active-class="active">
            <span class="nav-icon">📁</span>
            <span class="nav-text">相册</span>
          </router-link>
          <router-link to="/trash" class="nav-item" active-class="active">
            <span class="nav-icon">🗑️</span>
            <span class="nav-text">回收站</span>
          </router-link>
        </div>
      </nav>
    </aside>
    
    <!-- 右侧主内容区 -->
    <main class="main-content">
      <!-- 顶部导航栏 -->
      <header class="top-bar">
        <div class="search-bar">
          <span class="search-icon">🔍</span>
          <input type="text" placeholder="搜索你的照片">
        </div>
        
        <div class="top-actions">
          <button class="action-button" @click="toggleTheme">
            <span>{{ isDarkMode ? '🌓' : '🌞' }}</span>
          </button>
          <button class="action-button">
            <span>⚙️</span>
          </button>
          <div class="user-avatar" @click="goToProfile">
            <span>👤</span>
          </div>
        </div>
      </header>
      
      <!-- 内容区域 -->
      <div class="content-area">
        <router-view />
      </div>
    </main>
  </div>
</template>

<script setup>
import { ref, onMounted } from 'vue'
import { useRouter } from 'vue-router'

const router = useRouter()

// 主题切换状态
const isDarkMode = ref(true)

// 切换主题
const toggleTheme = () => {
  isDarkMode.value = !isDarkMode.value
  document.documentElement.classList.toggle('light-mode', !isDarkMode.value)
  // 保存主题状态到 localStorage
  localStorage.setItem('theme', isDarkMode.value ? 'dark' : 'light')
}

// 跳转到个人中心
const goToProfile = () => {
  router.push('/profile')
}

// 初始化主题
onMounted(() => {
  // 从 localStorage 读取主题状态，如果不存在则使用默认值
  const savedTheme = localStorage.getItem('theme')
  if (savedTheme) {
    isDarkMode.value = savedTheme === 'dark'
  }
  document.documentElement.classList.toggle('light-mode', !isDarkMode.value)
})
</script>

<style scoped>
.main-layout {
  display: flex;
  height: 100vh;
  background-color: #0f0f0f;
  color: #ffffff;
  font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, sans-serif;
  overflow: hidden;
}

/* 左侧导航栏 */
.sidebar {
  width: 250px;
  background-color: #1a1a1a;
  border-right: 1px solid #333333;
  display: flex;
  flex-direction: column;
  overflow-y: auto;
}

.sidebar-header {
  height: 60px;
  display: flex;
  align-items: center;
  padding: 0 20px;
  border-bottom: 1px solid #333333;
}

.logo {
  display: flex;
  align-items: center;
  gap: 10px;
  font-size: 20px;
  font-weight: bold;
}

.logo-text {
  color: #ffffff;
}

/* 亮色主题Logo样式 */
.light-mode .logo-text {
  color: #000000;
}

.light-mode .logo {
  color: #000000;
}

/* 导航菜单 */
.nav-menu {
  flex: 1;
  padding: 20px 0;
}

.nav-section {
  margin-bottom: 20px;
}

.section-title {
  padding: 0 20px 10px;
  font-size: 12px;
  font-weight: bold;
  color: #888888;
  text-transform: uppercase;
  letter-spacing: 1px;
}

.nav-item {
  display: flex;
  align-items: center;
  gap: 12px;
  padding: 12px 20px;
  color: #cccccc;
  text-decoration: none;
  transition: all 0.2s ease;
  border-left: 3px solid transparent;
}

.nav-item:hover {
  background-color: rgba(255, 255, 255, 0.05);
  color: #ffffff;
}

.nav-item.active {
  background-color: rgba(255, 255, 255, 0.08);
  color: #ffffff;
  border-left-color: #667eea;
}

.nav-icon {
  font-size: 18px;
  width: 20px;
  text-align: center;
}

.nav-text {
  font-size: 14px;
}

/* 主内容区 */
.main-content {
  flex: 1;
  display: flex;
  flex-direction: column;
  overflow: hidden;
}

/* 顶部导航栏 */
.top-bar {
  height: 60px;
  background-color: #1a1a1a;
  border-bottom: 1px solid #333333;
  display: flex;
  align-items: center;
  justify-content: space-between;
  padding: 0 20px;
}

.search-bar {
  display: flex;
  align-items: center;
  gap: 10px;
  background-color: #2a2a2a;
  border-radius: 20px;
  padding: 8px 16px;
  width: 400px;
}

.search-icon {
  color: #888888;
  font-size: 16px;
}

.search-bar input {
  background: transparent;
  border: none;
  color: #ffffff;
  font-size: 14px;
  width: 100%;
  outline: none;
}

.search-bar input::placeholder {
  color: #888888;
}

.top-actions {
  display: flex;
  align-items: center;
  gap: 12px;
}

.action-button {
  background: transparent;
  border: none;
  color: #ffffff;
  font-size: 18px;
  cursor: pointer;
  padding: 8px;
  border-radius: 8px;
  transition: background-color 0.2s ease;
}

.action-button:hover {
  background-color: rgba(255, 255, 255, 0.1);
}

.user-avatar {
  width: 36px;
  height: 36px;
  background-color: #333333;
  border-radius: 50%;
  display: flex;
  align-items: center;
  justify-content: center;
  cursor: pointer;
  transition: background-color 0.2s ease;
}

.user-avatar:hover {
  background-color: #444444;
}

/* 内容区域 */
.content-area {
  flex: 1;
  overflow-y: auto;
  padding: 20px;
}

/* 滚动条样式 */
::-webkit-scrollbar {
  width: 8px;
  height: 8px;
}

::-webkit-scrollbar-track {
  background: #1a1a1a;
}

::-webkit-scrollbar-thumb {
  background: #444444;
  border-radius: 4px;
}

::-webkit-scrollbar-thumb:hover {
  background: #555555;
}

/* 亮色主题样式 */
.light-mode .main-layout {
  background-color: #ffffff;
  color: #000000;
}

.light-mode .sidebar {
  background-color: #f8f9fa;
  border-right: 1px solid #e9ecef;
}

.light-mode .sidebar-header {
  border-bottom: 1px solid #e9ecef;
}

.light-mode .nav-item {
  color: #495057;
}

.light-mode .nav-item:hover {
  background-color: rgba(0, 0, 0, 0.05);
  color: #212529;
}

.light-mode .nav-item.active {
  background-color: rgba(102, 126, 234, 0.1);
  color: #212529;
}

.light-mode .section-title {
  color: #6c757d;
}

.light-mode .top-bar {
  background-color: #ffffff;
  border-bottom: 1px solid #e9ecef;
}

.light-mode .search-bar {
  background-color: #f1f3f4;
}

.light-mode .search-bar input {
  color: #000000;
}

.light-mode .search-bar input::placeholder {
  color: #6c757d;
}

.light-mode .action-button {
  color: #495057;
}

.light-mode .action-button:hover {
  background-color: rgba(0, 0, 0, 0.1);
}

.light-mode .user-avatar {
  background-color: #e9ecef;
  color: #495057;
}

.light-mode .user-avatar:hover {
  background-color: #dee2e6;
}

.light-mode .content-area {
  background-color: #ffffff;
}

/* 亮色主题滚动条 */
.light-mode ::-webkit-scrollbar-track {
  background: #f1f1f1;
}

.light-mode ::-webkit-scrollbar-thumb {
  background: #c1c1c1;
}

.light-mode ::-webkit-scrollbar-thumb:hover {
  background: #a8a8a8;
}
</style>