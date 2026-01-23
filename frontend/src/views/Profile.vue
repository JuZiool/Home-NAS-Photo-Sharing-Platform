<template>
  <div class="profile">
    <h1>个人中心</h1>
    
    <div class="profile-card">
      <div class="profile-header">
        <div class="avatar-large">
          <span>👤</span>
        </div>
        <div class="profile-info">
          <h2>{{ user.username }}</h2>
          <p>{{ user.email }}</p>
        </div>
      </div>
      
      <div class="profile-content">
        <div class="profile-section">
          <h3>账户信息</h3>
          <div class="profile-item">
            <span class="label">用户名:</span>
            <span class="value">{{ user.username }}</span>
          </div>
          <div class="profile-item">
            <span class="label">邮箱:</span>
            <span class="value">{{ user.email }}</span>
          </div>
          <div class="profile-item">
            <span class="label">注册时间:</span>
            <span class="value">{{ user.created_at }}</span>
          </div>
        </div>
        
        <div class="profile-section">
          <h3>账户设置</h3>
          <div class="password-change-form" v-if="showPasswordForm">
            <div class="form-group">
              <label>原密码</label>
              <input 
                type="password" 
                v-model="passwordData.oldPassword"
                placeholder="请输入原密码"
                class="input-field"
              >
            </div>
            <div class="form-group">
              <label>新密码</label>
              <input 
                type="password" 
                v-model="passwordData.newPassword"
                placeholder="请输入新密码"
                class="input-field"
              >
            </div>
            <div class="form-group">
              <label>确认新密码</label>
              <input 
                type="password" 
                v-model="passwordData.confirmPassword"
                placeholder="请确认新密码"
                class="input-field"
              >
            </div>
            
            <div v-if="passwordError" class="error-message">{{ passwordError }}</div>
            
            <div class="form-actions">
              <button class="cancel-button" @click="showPasswordForm = false">取消</button>
              <button 
                class="save-button" 
                @click="changePassword"
                :disabled="passwordLoading"
              >
                {{ passwordLoading ? '保存中...' : '保存' }}
              </button>
            </div>
          </div>
          
          <div class="setting-item" v-else>
            <span class="setting-label">修改密码</span>
            <button class="setting-button" @click="showPasswordForm = true">修改</button>
          </div>
        </div>
        
        <div class="profile-section danger">
          <h3>账户安全</h3>
          <div class="setting-item">
            <span class="setting-label">退出登录</span>
            <button class="danger-button" @click="logout">退出</button>
          </div>
        </div>
      </div>
    </div>
  </div>
</template>

<script setup>
import { ref, onMounted } from 'vue'
import { useRouter } from 'vue-router'
import { authAPI } from '../services/api'

const router = useRouter()
const user = ref({
  username: '',
  email: '',
  created_at: ''
})
const loading = ref(true)

// 修改密码相关
const showPasswordForm = ref(false)
const passwordData = ref({
  oldPassword: '',
  newPassword: '',
  confirmPassword: ''
})
const passwordError = ref('')
const passwordLoading = ref(false)

// 获取当前用户信息
const fetchUserInfo = async () => {
  try {
    loading.value = true
    const response = await authAPI.getCurrentUser()
    // 从响应中提取用户数据
    const userData = response.user
    // 格式化日期
    userData.created_at = new Date(userData.created_at).toLocaleDateString()
    user.value = userData
  } catch (err) {
    console.error('获取用户信息失败:', err)
  } finally {
    loading.value = false
  }
}

// 修改密码
const changePassword = async () => {
  // 表单验证
  if (!passwordData.value.oldPassword || !passwordData.value.newPassword || !passwordData.value.confirmPassword) {
    passwordError.value = '请填写所有密码字段'
    return
  }
  
  if (passwordData.value.newPassword !== passwordData.value.confirmPassword) {
    passwordError.value = '两次输入的新密码不一致'
    return
  }
  
  if (passwordData.value.oldPassword === passwordData.value.newPassword) {
    passwordError.value = '新密码不能与原密码相同'
    return
  }
  
  passwordLoading.value = true
  passwordError.value = ''
  
  try {
    await authAPI.changePassword({
      old_password: passwordData.value.oldPassword,
      new_password: passwordData.value.newPassword
    })
    
    // 修改成功
    showPasswordForm.value = false
    passwordData.value = {
      oldPassword: '',
      newPassword: '',
      confirmPassword: ''
    }
    // 可以添加成功提示
  } catch (err) {
    passwordError.value = err.response?.data?.message || '修改密码失败，请检查原密码是否正确'
  } finally {
    passwordLoading.value = false
  }
}

// 退出登录
const logout = async () => {
  try {
    await authAPI.logout()
  } catch (err) {
    console.error('退出登录失败:', err)
  } finally {
    // 清除本地存储的token和用户信息，实现真正的退出登录
    // 保留loginCredentials，以便记住登录功能继续工作
    localStorage.removeItem('token')
    localStorage.removeItem('user')
    localStorage.removeItem('rememberMe')
    sessionStorage.removeItem('token')
    sessionStorage.removeItem('user')
    sessionStorage.removeItem('rememberMe')
    
    // 跳转到登录页
    router.push('/login')
  }
}

// 页面加载时获取用户信息
onMounted(() => {
  fetchUserInfo()
})
</script>

<style scoped>
.profile {
  padding: 20px 0;
}

.profile h1 {
  font-size: 24px;
  font-weight: 600;
  margin-bottom: 20px;
}

.profile-card {
  background-color: #1a1a1a;
  border-radius: 12px;
  padding: 30px;
  box-shadow: 0 4px 12px rgba(0, 0, 0, 0.15);
  border: 1px solid #333333;
}

.profile-header {
  display: flex;
  align-items: center;
  gap: 20px;
  margin-bottom: 30px;
  padding-bottom: 20px;
  border-bottom: 1px solid #333333;
}

.avatar-large {
  width: 100px;
  height: 100px;
  background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
  border-radius: 50%;
  display: flex;
  align-items: center;
  justify-content: center;
  font-size: 48px;
}

.profile-info h2 {
  font-size: 24px;
  font-weight: 600;
  margin: 0 0 5px 0;
}

.profile-info p {
  font-size: 14px;
  color: #cccccc;
  margin: 0;
}

.profile-content {
  display: grid;
  gap: 30px;
}

.profile-section {
  background-color: #2a2a2a;
  border-radius: 8px;
  padding: 20px;
  border: 1px solid #333333;
}

.profile-section h3 {
    font-size: 16px;
    font-weight: 600;
    margin: 0 0 20px 0;
    color: #ffffff;
  }

  /* 密码修改表单样式 */
  .password-change-form {
    background-color: #1a1a1a;
    border-radius: 8px;
    padding: 20px;
    margin-bottom: 20px;
    border: 1px solid #333333;
  }

  .form-group {
    margin-bottom: 15px;
  }

  .form-group label {
    display: block;
    margin-bottom: 8px;
    font-size: 14px;
    color: #cccccc;
    font-weight: 500;
  }

  .input-field {
    width: 100%;
    padding: 12px;
    border: 1px solid #333333;
    border-radius: 6px;
    background-color: #2a2a2a;
    color: #ffffff;
    font-size: 14px;
    outline: none;
    transition: all 0.3s ease;
  }

  .input-field:focus {
    border-color: #667eea;
    background-color: #333333;
  }

  .input-field::placeholder {
    color: #666666;
  }

  .error-message {
    color: #ff4757;
    background: rgba(255, 71, 87, 0.1);
    padding: 10px;
    border-radius: 6px;
    margin-bottom: 15px;
    font-size: 13px;
    border: 1px solid rgba(255, 71, 87, 0.3);
  }

  .form-actions {
    display: flex;
    justify-content: flex-end;
    gap: 12px;
    margin-top: 20px;
  }

  .cancel-button, .save-button {
    padding: 10px 20px;
    border: none;
    border-radius: 6px;
    font-size: 14px;
    font-weight: 500;
    cursor: pointer;
    transition: all 0.3s ease;
  }

  .cancel-button {
    background-color: #333333;
    color: #ffffff;
  }

  .cancel-button:hover {
    background-color: #444444;
  }

  .save-button {
    background-color: #667eea;
    color: #ffffff;
  }

  .save-button:hover {
    background-color: #764ba2;
    transform: translateY(-1px);
  }

  .save-button:disabled {
    opacity: 0.6;
    cursor: not-allowed;
    transform: none;
  }

  .save-button:disabled:hover {
    transform: none;
  }

.profile-item {
  display: flex;
  justify-content: space-between;
  align-items: center;
  padding: 12px 0;
  border-bottom: 1px solid #333333;
}

.profile-item:last-child {
  border-bottom: none;
}

.label {
  font-size: 14px;
  color: #888888;
}

.value {
  font-size: 14px;
  color: #ffffff;
}

.setting-item {
  display: flex;
  justify-content: space-between;
  align-items: center;
  padding: 12px 0;
  border-bottom: 1px solid #333333;
}

.setting-item:last-child {
  border-bottom: none;
}

.setting-label {
  font-size: 14px;
  color: #ffffff;
}

.setting-button {
  background-color: #667eea;
  color: white;
  border: none;
  border-radius: 6px;
  padding: 8px 16px;
  font-size: 12px;
  cursor: pointer;
  transition: all 0.3s ease;
}

.setting-button:hover {
  background-color: #764ba2;
  transform: translateY(-1px);
}

.danger {
  background-color: rgba(245, 87, 108, 0.1);
  border-color: rgba(245, 87, 108, 0.3);
}

.danger-button {
  background-color: #f5576c;
  color: white;
  border: none;
  border-radius: 6px;
  padding: 8px 16px;
  font-size: 12px;
  cursor: pointer;
  transition: all 0.3s ease;
}

.danger-button:hover {
  background-color: #e53e56;
  transform: translateY(-1px);
}

/* 亮色主题适配 */
:root.light-mode .profile-card {
  background-color: #ffffff;
  border-color: #e9ecef;
  box-shadow: 0 2px 8px rgba(0, 0, 0, 0.1);
}

:root.light-mode .profile-header {
  border-bottom-color: #e9ecef;
}

:root.light-mode .profile-info p {
  color: #6c757d;
}

:root.light-mode .profile-section {
  background-color: #f8f9fa;
  border-color: #e9ecef;
}

:root.light-mode .profile-section h3 {
  color: #212529;
}

:root.light-mode .profile-item {
  border-bottom-color: #e9ecef;
}

:root.light-mode .label {
  color: #6c757d;
}

:root.light-mode .value {
  color: #212529;
}

:root.light-mode .setting-item {
  border-bottom-color: #e9ecef;
}

:root.light-mode .setting-label {
  color: #212529;
}

:root.light-mode .danger {
  background-color: rgba(245, 87, 108, 0.05);
  border-color: rgba(245, 87, 108, 0.2);
}
</style>