import axios from 'axios'

// 创建axios实例
const api = axios.create({
  baseURL: '/api',
  timeout: 10000,
  headers: {
    'Content-Type': 'application/json'
  }
})

// 请求拦截器
api.interceptors.request.use(
  config => {
    // 从localStorage或sessionStorage获取token
    const token = localStorage.getItem('token') || sessionStorage.getItem('token')
    if (token) {
      config.headers.Authorization = `Bearer ${token}`
    }
    return config
  },
  error => {
    return Promise.reject(error)
  }
)

// 响应拦截器
api.interceptors.response.use(
  response => {
    return response.data
  },
  error => {
    // 处理401未授权 - 仅在非登录请求时跳转
    // 登录请求的URL包含'/auth/login'，我们不希望在登录失败时跳转
    const isLoginRequest = error.config && error.config.url && 
                         (error.config.url === '/auth/login' || 
                          error.config.url.includes('/auth/login'))
    if (error.response && error.response.status === 401 && !isLoginRequest) {
      // 清除token并跳转到登录页
      localStorage.removeItem('token')
      localStorage.removeItem('user')
      localStorage.removeItem('rememberMe')
      sessionStorage.removeItem('token')
      sessionStorage.removeItem('user')
      sessionStorage.removeItem('rememberMe')
      window.location.href = '/login'
    }
    return Promise.reject(error)
  }
)

// 认证相关API
export const authAPI = {
  // 用户登录
  login: (credentials) => api.post('/auth/login', credentials),
  // 用户注册
  register: (userData) => api.post('/auth/register', userData),
  // 获取当前用户信息
  getCurrentUser: () => api.get('/auth/me'),
  // 用户退出
  logout: () => {
    // 不清除登录状态，只调用后端退出接口
    return api.post('/auth/logout')
  },
  // 修改密码
  changePassword: (passwordData) => api.put('/auth/password', passwordData)
}

// 照片相关API
export const photosAPI = {
  // 获取照片列表
  getPhotos: (params) => api.get('/photos', { params }),
  // 上传照片
  uploadPhoto: (formData) => api.post('/photos', formData, {
    headers: {
      'Content-Type': 'multipart/form-data'
    }
  }),
  // 获取单张照片
  getPhoto: (id) => api.get(`/photos/${id}`),
  // 删除照片（移至回收站）
  deletePhoto: (id) => api.delete(`/photos/${id}`),
  // 获取回收站照片列表
  getTrashPhotos: () => api.get('/photos/trash'),
  // 恢复照片
  restorePhoto: (id) => api.post(`/photos/${id}/restore`),
  // 永久删除照片
  permanentlyDeletePhoto: (id) => api.delete(`/photos/${id}/permanent`),
  // 标记/取消标记收藏
  toggleFavorite: (id) => api.patch(`/photos/${id}/favorite`)
}

// 相册相关API
export const albumsAPI = {
  // 获取相册列表
  getAlbums: () => api.get('/albums'),
  // 创建相册
  createAlbum: (albumData) => api.post('/albums', albumData),
  // 获取相册详情
  getAlbum: (id) => api.get(`/albums/${id}`),
  // 更新相册
  updateAlbum: (id, albumData) => api.put(`/albums/${id}`, albumData),
  // 删除相册
  deleteAlbum: (id) => api.delete(`/albums/${id}`),
  // 获取相册中的照片
  getAlbumPhotos: (id) => api.get(`/albums/${id}/photos`),
  // 上传照片到相册
  uploadPhotosToAlbum: (id, formData, onUploadProgress) => api.post(`/albums/${id}/photos`, formData, {
    headers: {
      'Content-Type': 'multipart/form-data'
    },
    onUploadProgress
  })
}

// 分享相关API
export const sharesAPI = {
  // 获取分享列表
  getShares: () => api.get('/shares'),
  // 创建分享
  createShare: (shareData) => api.post('/shares', shareData),
  // 通过分享码获取分享内容
  getShareByCode: (code) => api.get(`/shares/${code}`),
  // 删除分享
  deleteShare: (id) => api.delete(`/shares/${id}`)
}

// 收藏相关API
export const favoritesAPI = {
  // 获取收藏列表
  getFavorites: () => api.get('/favorites')
}

// 回收站相关API
export const trashAPI = {
  // 获取回收站列表
  getTrash: () => api.get('/trash'),
  // 恢复照片
  restorePhoto: (id) => api.post(`/trash/${id}/restore`),
  // 永久删除照片
  permanentlyDeletePhoto: (id) => api.delete(`/trash/${id}`)
}

// 管理员相关API
export const adminAPI = {
  // 获取统计数据
  getStats: () => api.get('/admin/stats'),
  // 获取所有用户列表
  getUsers: () => api.get('/admin/users'),
  // 更新用户角色
  updateUserRole: (userId, isAdmin) => api.put(`/admin/users/${userId}/role`, { is_admin: isAdmin }),
  // 修改用户密码
  updateUserPassword: (userId, newPassword) => api.put(`/admin/users/${userId}/password`, { new_password: newPassword }),
  // 删除用户
  deleteUser: (userId) => api.delete(`/admin/users/${userId}`),
  // 获取所有相册
  getAlbums: () => api.get('/admin/albums'),
  // 获取相册详情
  getAlbum: (albumId) => api.get(`/admin/albums/${albumId}`),
  // 删除相册
  deleteAlbum: (albumId) => api.delete(`/admin/albums/${albumId}`),
  // 获取相册中的照片
  getAlbumPhotos: (albumId) => api.get(`/admin/albums/${albumId}/photos`)
}

export default api