<template>
  <div class="register-container">
    <div class="register-form">
      <form @submit.prevent="register">
        <div class="logo">
          <div class="logo-icon">
            <span>📸</span>
          </div>
          <div class="logo-text">PhotoShare</div>
        </div>
        <div class="form-group">
          <input 
            type="text" 
            placeholder="用户名" 
            class="input-field"
            v-model="username"
          >
        </div>
        <div class="form-group">
          <input 
            type="email" 
            placeholder="邮箱" 
            class="input-field"
            v-model="email"
          >
        </div>
        <div class="form-group">
          <input 
            type="password" 
            placeholder="密码" 
            class="input-field"
            v-model="password"
          >
        </div>
        <div class="form-group">
          <input 
            type="password" 
            placeholder="确认密码" 
            class="input-field"
            v-model="confirmPassword"
          >
        </div>
        
        <div v-if="error" class="error-message">
          {{ error }}
        </div>
        
        <button class="register-button" type="submit" :disabled="loading">
          <span v-if="loading" class="loading-spinner">⏳</span>
          <span v-else>注册</span>
        </button>
        
        <div class="login-link">
          <span>已有账号? </span>
          <router-link to="/login">立即登录</router-link>
        </div>
      </form>
    </div>
  </div>
</template>

<script setup>
import { ref } from 'vue'
import { useRouter } from 'vue-router'
import { authAPI } from '../services/api'

const router = useRouter()
const username = ref('')
const email = ref('')
const password = ref('')
const confirmPassword = ref('')
const error = ref('')
const loading = ref(false)

const register = async () => {
  // 表单验证
  if (!username.value || !email.value || !password.value) {
    error.value = '请填写所有必填字段'
    return
  }
  
  if (password.value !== confirmPassword.value) {
    error.value = '两次输入的密码不一致'
    return
  }
  
  loading.value = true
  error.value = ''
  
  try {
    await authAPI.register({
      username: username.value,
      email: email.value,
      password: password.value
    })
    
    // 注册成功，跳转到登录页
    router.push('/login')
  } catch (err) {
    error.value = err.response?.data?.message || '注册失败，请稍后重试'
  } finally {
    loading.value = false
  }
}
</script>

<style scoped>
.register-container {
  display: flex;
  justify-content: center;
  align-items: center;
  min-height: 100vh;
  background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
  background-size: 400% 400%;
  animation: gradientAnimation 15s ease infinite;
}

@keyframes gradientAnimation {
  0% { background-position: 0% 50%; }
  50% { background-position: 100% 50%; }
  100% { background-position: 0% 50%; }
}

.register-form {
  background: rgba(255, 255, 255, 0.1);
  backdrop-filter: blur(10px);
  border-radius: 20px;
  padding: 40px;
  width: 100%;
  max-width: 400px;
  box-shadow: 0 8px 32px rgba(0, 0, 0, 0.1);
  border: 1px solid rgba(255, 255, 255, 0.2);
}

.logo {
  display: flex;
  flex-direction: column;
  align-items: center;
  margin-bottom: 30px;
}

.logo-icon {
  width: 80px;
  height: 80px;
  background: rgba(255, 255, 255, 0.2);
  border-radius: 50%;
  display: flex;
  justify-content: center;
  align-items: center;
  font-size: 32px;
  margin-bottom: 10px;
}

.logo-text {
  font-size: 24px;
  font-weight: bold;
  color: white;
}

.form-group {
  margin-bottom: 20px;
}

.input-field {
  width: 100%;
  padding: 15px;
  border: 1px solid rgba(255, 255, 255, 0.3);
  border-radius: 10px;
  background: rgba(255, 255, 255, 0.1);
  color: white;
  font-size: 16px;
  outline: none;
  transition: all 0.3s ease;
}

.input-field::placeholder {
  color: rgba(255, 255, 255, 0.7);
}

.input-field:focus {
  border-color: rgba(255, 255, 255, 0.8);
  background: rgba(255, 255, 255, 0.2);
}

.register-button {
  width: 100%;
  padding: 15px;
  background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
  border: none;
  border-radius: 10px;
  color: white;
  font-size: 18px;
  font-weight: bold;
  cursor: pointer;
  transition: all 0.3s ease;
  box-shadow: 0 4px 15px rgba(102, 126, 234, 0.4);
}

.error-message {
  color: #ff4757;
  background: rgba(255, 71, 87, 0.1);
  padding: 12px;
  border-radius: 10px;
  margin-bottom: 20px;
  text-align: center;
  font-size: 14px;
  border: 1px solid rgba(255, 71, 87, 0.3);
}

.loading-spinner {
  margin-right: 8px;
}

.register-button {
  width: 100%;
  padding: 15px;
  background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
  border: none;
  border-radius: 10px;
  color: white;
  font-size: 18px;
  font-weight: bold;
  cursor: pointer;
  transition: all 0.3s ease;
  box-shadow: 0 4px 15px rgba(102, 126, 234, 0.4);
}

.register-button:hover {
  transform: translateY(-2px);
  box-shadow: 0 6px 20px rgba(102, 126, 234, 0.6);
}

.register-button:disabled {
  opacity: 0.6;
  cursor: not-allowed;
  transform: none;
  box-shadow: none;
}

.register-button:disabled:hover {
  transform: none;
  box-shadow: none;
}

.login-link {
  text-align: center;
  margin-top: 20px;
  color: white;
  font-size: 14px;
}

.login-link a {
  color: white;
  text-decoration: none;
  font-weight: bold;
  transition: opacity 0.3s ease;
}

.login-link a:hover {
  opacity: 0.8;
}
</style>