import { createRouter, createWebHistory } from 'vue-router'
import Login from '../views/Login.vue'
import Register from '../views/Register.vue'
import MainLayout from '../views/MainLayout.vue'
import Dashboard from '../views/Dashboard.vue'
import Sharing from '../views/Sharing.vue'
import Favorites from '../views/Favorites.vue'
import Albums from '../views/Albums.vue'
import PhotoTypes from '../views/PhotoTypes.vue'
import Trash from '../views/Trash.vue'
import Profile from '../views/Profile.vue'
import SharedView from '../views/SharedView.vue'
import AdminLayout from '../views/AdminLayout.vue'
import AdminDashboard from '../views/admin/Dashboard.vue'
import AdminUsers from '../views/admin/Users.vue'
import AdminAlbums from '../views/admin/Albums.vue'

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
    path: '/shared/:code',
    name: 'SharedView',
    component: SharedView,
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
    path: '/photo-types',
    name: 'PhotoTypes',
    component: MainLayout,
    meta: { requiresAuth: true },
    children: [
      {
        path: '',
        component: PhotoTypes
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
  },
  // 管理员路由
  {
    path: '/admin',
    name: 'AdminDashboard',
    component: AdminLayout,
    meta: { requiresAuth: true, requiresAdmin: true },
    children: [
      {
        path: '',
        component: AdminDashboard
      },
      {
        path: 'users',
        name: 'AdminUsers',
        component: AdminUsers
      },
      {
        path: 'albums',
        name: 'AdminAlbums',
        component: AdminAlbums
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
  
  // 检查用户是否已登录 - 同时检查localStorage和sessionStorage
  const isLoggedIn = localStorage.getItem('token') !== null || sessionStorage.getItem('token') !== null
  
  // 检查路由是否需要管理员权限
  const requiresAdmin = to.matched.some(record => record.meta.requiresAdmin)
  
  // 获取用户信息 - 同时检查localStorage和sessionStorage
  const userStr = localStorage.getItem('user') || sessionStorage.getItem('user')
  const user = userStr ? JSON.parse(userStr) : null
  const isAdmin = user && user.is_admin
  
  if (requiresAuth && !isLoggedIn) {
    // 未登录且需要认证，重定向到登录页
    next('/login')
  } else if (requiresAdmin && !isAdmin) {
    // 需要管理员权限但不是管理员，重定向到普通用户仪表盘
    next('/dashboard')
  } else if ((to.path === '/login' || to.path === '/register') && isLoggedIn) {
    // 已登录且访问登录/注册页，根据用户角色重定向
    if (isAdmin) {
      next('/admin')
    } else {
      next('/dashboard')
    }
  } else {
    // 其他情况，正常访问
    next()
  }
})

export default router