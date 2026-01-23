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
          <button class="action-button" @click="goToSettings">
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

// 跳转到设置/管理页面
const goToSettings = () => {
  // 从localStorage或sessionStorage获取用户信息
  const userStr = localStorage.getItem('user') || sessionStorage.getItem('user')
  const user = userStr ? JSON.parse(userStr) : null
  
  // 如果是管理员，跳转到管理页面
  if (user && user.is_admin) {
    router.push('/admin')
  } else {
    // 否则可以跳转到普通用户设置页面（如果有的话）
    // 目前没有普通用户设置页面，所以暂时不做任何操作
    console.log('普通用户设置页面待实现')
  }
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
  width: 120px;
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
  padding: 0 12px;
  border-bottom: 1px solid #333333;
}

.logo {
  display: flex;
  align-items: center;
  gap: 6px;
  font-size: 15px;
  font-weight: bold;
}

.logo-text {
  color: #ffffff;
  font-size: 13px;
  display: inline; /* 在较宽导航栏中显示文字 */
}

/* 亮色主题适配 */
:root.light-mode .sidebar {
  background-color: #ffffff;
  border-right: 1px solid #e0e0e0;
}

:root.light-mode .logo-text {
  color: #000000;
}

:root.light-mode .logo {
  color: #000000;
}

:root.light-mode .nav-item {
  color: #666666;
}

:root.light-mode .nav-item:hover {
  background-color: rgba(0, 0, 0, 0.05);
  color: #000000;
}

:root.light-mode .nav-item.active {
  background-color: rgba(102, 126, 234, 0.1);
  color: #667eea;
}

:root.light-mode .section-title {
  color: #999999;
}

:root.light-mode .top-bar {
  background-color: #ffffff;
  border-bottom: 1px solid #e0e0e0;
}

:root.light-mode .search-bar {
  background-color: #f5f5f5;
}

:root.light-mode .search-icon {
  color: #999999;
}

:root.light-mode .search-bar input {
  color: #333333;
}

:root.light-mode .search-bar input::placeholder {
  color: #999999;
}

:root.light-mode .action-button {
  color: #666666;
}

:root.light-mode .action-button:hover {
  background-color: rgba(0, 0, 0, 0.05);
}

:root.light-mode .user-avatar {
  background-color: #f5f5f5;
  color: #666666;
}

:root.light-mode .user-avatar:hover {
  background-color: #e0e0e0;
}

:root.light-mode .content-area {
  background-color: #fafafa;
  color: #333333;
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
  padding: 0 12px 10px;
  font-size: 11px;
  font-weight: bold;
  color: #888888;
  text-transform: uppercase;
  letter-spacing: 0.5px;
  text-align: left;
}

.nav-item {
  display: flex;
  align-items: center;
  gap: 8px;
  padding: 12px 12px;
  color: #cccccc;
  text-decoration: none;
  transition: all 0.2s ease;
  border-left: 3px solid transparent;
  justify-content: flex-start;
}

.nav-text {
  font-size: 12px;
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
  /* 隐藏滚动条但保持滚动功能 */
  scrollbar-width: none; /* Firefox */
  -ms-overflow-style: none; /* IE/Edge */
  scrollbar-gutter: auto;
}

/* 隐藏滚动条样式 */
.content-area::-webkit-scrollbar {
  width: 0 !important;
  height: 0 !important;
}

/* 禁用滚动条轨道和滑块显示 */
.content-area::-webkit-scrollbar-track,
.content-area::-webkit-scrollbar-thumb {
  display: none !important;
}

/* 全局滚动条样式（用于其他可能需要滚动条的元素） */
::-webkit-scrollbar {
  width: 0 !important;
  height: 0 !important;
}

::-webkit-scrollbar-track,
::-webkit-scrollbar-thumb {
  display: none !important;
}

/* Firefox全局滚动条隐藏 */
* {
  scrollbar-width: none !important;
  -ms-overflow-style: none !important;
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