<template>
  <div class="login-container">
    <div class="login-form">
      <form @submit.prevent="login">
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
            autocomplete="username"
          >
        </div>
        <div class="form-group">
        <input 
          type="password" 
          placeholder="密码" 
          class="input-field"
          v-model="password"
          autocomplete="current-password"
        >
      </div>
        <div class="form-options">
          <label class="remember-me">
            <input type="checkbox" v-model="remember">
            <span>记住登录</span>
          </label>
          <a href="#" class="forgot-password">忘记密码?</a>
        </div>
        
        <div v-if="error" class="error-message">
          {{ error }}
        </div>
        
        <button class="login-button" type="submit" :disabled="loading">
          <span v-if="loading" class="loading-spinner">⏳</span>
          <span v-else>登录</span>
        </button>
        
        <div class="register-link">
          <span>还没有账号? </span>
          <router-link to="/register">立即注册</router-link>
        </div>
      </form>
    </div>
  </div>
</template>

<script setup>
import { ref, onMounted } from 'vue'
import { useRouter } from 'vue-router'
import { authAPI } from '../services/api'

const router = useRouter()
const username = ref('')
const password = ref('')
const remember = ref(false)
const error = ref('')
const loading = ref(false)

// 组件挂载时，检查是否保存了用户名和密码
onMounted(() => {
  const savedCredentials = localStorage.getItem('loginCredentials')
  if (savedCredentials) {
    try {
      const credentials = JSON.parse(savedCredentials)
      username.value = credentials.username || ''
      password.value = credentials.password || ''
      remember.value = true
    } catch (e) {
      console.error('Failed to parse saved credentials:', e)
      localStorage.removeItem('loginCredentials')
    }
  }
})

const login = async () => {
  if (!username.value || !password.value) {
    error.value = '请输入用户名和密码'
    return
  }

  loading.value = true
  error.value = ''

  try {
    const response = await authAPI.login({
      username: username.value,
      password: password.value
    })

    // 保存token和用户信息
    if (response.token) {
      const storage = remember.value ? localStorage : sessionStorage
      storage.setItem('token', response.token)
      storage.setItem('user', JSON.stringify(response.user))
      storage.setItem('rememberMe', remember.value)
      
      // 如果勾选了记住登录，保存用户名和密码到localStorage
      if (remember.value) {
        localStorage.setItem('loginCredentials', JSON.stringify({
          username: username.value,
          password: password.value
        }))
      } else {
        // 如果没有勾选，清除保存的用户名和密码
        localStorage.removeItem('loginCredentials')
      }
      
      // 跳转到仪表盘
      router.push('/dashboard')
    }
  } catch (err) {
    error.value = err.response?.data?.message || '登录失败，请检查用户名和密码'
  } finally {
    loading.value = false
  }
}
</script>

<style scoped>
.login-container {
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

.login-form {
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

.form-options {
  display: flex;
  justify-content: space-between;
  align-items: center;
  margin-bottom: 20px;
  font-size: 14px;
}

.remember-me {
  display: flex;
  align-items: center;
  color: white;
  cursor: pointer;
}

.remember-me input {
  margin-right: 8px;
}

.forgot-password {
  color: white;
  text-decoration: none;
  transition: opacity 0.3s ease;
}

.forgot-password:hover {
  opacity: 0.8;
}

.login-button {
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

.login-button:hover {
  transform: translateY(-2px);
  box-shadow: 0 6px 20px rgba(102, 126, 234, 0.6);
}

.register-link {
  text-align: center;
  margin-top: 20px;
  color: white;
  font-size: 14px;
}

.register-link a {
  color: white;
  text-decoration: none;
  font-weight: bold;
  transition: opacity 0.3s ease;
}

.register-link a:hover {
    opacity: 0.8;
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

  .login-button:disabled {
    opacity: 0.6;
    cursor: not-allowed;
    transform: none;
    box-shadow: none;
  }

  .login-button:disabled:hover {
    transform: none;
    box-shadow: none;
  }
</style>