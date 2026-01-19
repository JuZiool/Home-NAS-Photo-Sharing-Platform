import { createRouter, createWebHistory } from 'vue-router'
import Login from '../views/Login.vue'
import Register from '../views/Register.vue'
import MainLayout from '../views/MainLayout.vue'
import Dashboard from '../views/Dashboard.vue'
import Sharing from '../views/Sharing.vue'
import Favorites from '../views/Favorites.vue'
import Albums from '../views/Albums.vue'
import Trash from '../views/Trash.vue'
import Profile from '../views/Profile.vue'

const routes = [
  {
    path: '/',
    redirect: '/login'
  },
  {
    path: '/login',
    name: 'Login',
    component: Login,
    meta: { requiresAuth: false }
  },
  {
    path: '/register',
    name: 'Register',
    component: Register,
    meta: { requiresAuth: false }
  },
  {
    path: '/dashboard',
    name: 'Dashboard',
    component: MainLayout,
    meta: { requiresAuth: true },
    children: [
      {
        path: '',
        component: Dashboard
      }
    ]
  },
  {
    path: '/sharing',
    name: 'Sharing',
    component: MainLayout,
    meta: { requiresAuth: true },
    children: [
      {
        path: '',
        component: Sharing
      }
    ]
  },
  {
    path: '/favorites',
    name: 'Favorites',
    component: MainLayout,
    meta: { requiresAuth: true },
    children: [
      {
        path: '',
        component: Favorites
      }
    ]
  },
  {
    path: '/albums',
    name: 'Albums',
    component: MainLayout,
    meta: { requiresAuth: true },
    children: [
      {
        path: '',
        component: Albums
      }
    ]
  },
  {
    path: '/trash',
    name: 'Trash',
    component: MainLayout,
    meta: { requiresAuth: true },
    children: [
      {
        path: '',
        component: Trash
      }
    ]
  },
  {
    path: '/profile',
    name: 'Profile',
    component: MainLayout,
    meta: { requiresAuth: true },
    children: [
      {
        path: '',
        component: Profile
      }
    ]
  }
]

const router = createRouter({
  history: createWebHistory(),
  routes
})

// 路由守卫
router.beforeEach((to, from, next) => {
  // 检查路由是否需要认证
  const requiresAuth = to.matched.some(record => record.meta.requiresAuth)
  
  // 检查用户是否已登录
  const isLoggedIn = localStorage.getItem('token') !== null
  
  if (requiresAuth && !isLoggedIn) {
    // 未登录且需要认证，重定向到登录页
    next('/login')
  } else if ((to.path === '/login' || to.path === '/register') && isLoggedIn) {
    // 已登录且访问登录/注册页，重定向到仪表盘
    next('/dashboard')
  } else {
    // 其他情况，正常访问
    next()
  }
})

export default router