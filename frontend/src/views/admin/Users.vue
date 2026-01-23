<template>
  <div class="users-container">
    <h3>用户管理</h3>
    <div class="users-content">
      <div class="users-table-wrapper">
        <table class="users-table">
          <thead>
            <tr>
              <th>ID</th>
              <th>用户名</th>
              <th>邮箱</th>
              <th>角色</th>
              <th>注册时间</th>
              <th>操作</th>
            </tr>
          </thead>
          <tbody>
            <tr v-for="user in users" :key="user.id" class="user-row">
              <td class="user-id">{{ user.id }}</td>
              <td class="user-username">{{ user.username }}</td>
              <td class="user-email">{{ user.email }}</td>
              <td class="user-role">
                <div class="role-badge" :class="user.is_admin ? 'admin-badge' : 'user-badge'">
                  {{ user.is_admin ? '管理员' : '普通用户' }}
                </div>
              </td>
              <td class="user-created">{{ formatDate(user.created_at) }}</td>
              <td class="user-actions">
                <button 
                  class="action-btn role-btn" 
                  :class="user.is_admin ? 'remove-admin' : 'add-admin'"
                  @click="toggleAdmin(user)"
                  :disabled="loadingUsers.includes(user.id)"
                >
                  <span v-if="loadingUsers.includes(user.id)">⏳</span>
                  <span v-else>
                    {{ user.is_admin ? '取消管理员' : '设为管理员' }}
                  </span>
                </button>
                <button 
                  class="action-btn password-btn"
                  @click="openChangePasswordModal(user)"
                  :disabled="loadingUsers.includes(user.id)"
                >
                  <span v-if="loadingUsers.includes(user.id)">⏳</span>
                  <span v-else>修改密码</span>
                </button>
                <button 
                  class="action-btn delete-btn" 
                  @click="confirmDelete(user)"
                  :disabled="loadingUsers.includes(user.id)"
                >
                  <span v-if="loadingUsers.includes(user.id)">⏳</span>
                  <span v-else>删除</span>
                </button>
              </td>
            </tr>
          </tbody>
        </table>
        <div v-if="users.length === 0 && !loading" class="no-users">
          暂无用户数据
        </div>
        <div v-if="loading" class="loading-state">
          <span class="loading-spinner">⏳</span>
          <span>加载用户数据中...</span>
        </div>
      </div>
    </div>
    
    <!-- 删除确认对话框 -->
    <div v-if="showDeleteConfirm" class="modal-overlay" @click="closeDeleteConfirm">
      <div class="modal-content" @click.stop>
        <div class="modal-header">
          <h4>确认删除</h4>
          <button class="close-btn" @click="closeDeleteConfirm">×</button>
        </div>
        <div class="modal-body">
          <p>确定要删除用户 <strong>{{ deleteUser?.username }}</strong> 吗？</p>
          <p class="warning-text">此操作将永久删除用户及其所有数据，包括照片和相册，不可恢复！</p>
        </div>
        <div class="modal-footer">
          <button class="btn cancel-btn" @click="closeDeleteConfirm">取消</button>
          <button 
            class="btn delete-btn" 
            @click="deleteUserConfirm"
            :disabled="deletingUser"
          >
            <span v-if="deletingUser">⏳</span>
            <span v-else>确认删除</span>
          </button>
        </div>
      </div>
    </div>

    <!-- 修改密码对话框 -->
    <div v-if="showChangePasswordModal" class="modal-overlay" @click="closeChangePasswordModal">
      <div class="modal-content" @click.stop>
        <div class="modal-header">
          <h4>修改密码</h4>
          <button class="close-btn" @click="closeChangePasswordModal">×</button>
        </div>
        <div class="modal-body">
          <p>为用户 <strong>{{ changePasswordUser?.username }}</strong> 设置新密码：</p>
          <div class="password-input-group">
            <label for="new-password">新密码</label>
            <input 
              type="password" 
              id="new-password" 
              v-model="newPassword" 
              placeholder="请输入新密码" 
              class="password-input"
            >
          </div>
        </div>
        <div class="modal-footer">
          <button class="btn cancel-btn" @click="closeChangePasswordModal">取消</button>
          <button 
            class="btn save-btn" 
            @click="changePasswordConfirm"
            :disabled="!newPassword || changingPassword"
          >
            <span v-if="changingPassword">⏳</span>
            <span v-else>保存</span>
          </button>
        </div>
      </div>
    </div>
  </div>
</template>

<script setup>
import { ref, onMounted } from 'vue'
import { useRouter } from 'vue-router'
import { adminAPI } from '../../services/api'

const router = useRouter()
const users = ref([])
const loading = ref(false)
const loadingUsers = ref([])
const showDeleteConfirm = ref(false)
const deleteUser = ref(null)
const deletingUser = ref(false)
const showChangePasswordModal = ref(false)
const changePasswordUser = ref(null)
const newPassword = ref('')
const changingPassword = ref(false)

// 格式化日期
const formatDate = (dateString) => {
  const date = new Date(dateString)
  return date.toLocaleString('zh-CN', {
    year: 'numeric',
    month: '2-digit',
    day: '2-digit',
    hour: '2-digit',
    minute: '2-digit'
  })
}

// 获取用户列表
const fetchUsers = async () => {
  loading.value = true
  try {
    const response = await adminAPI.getUsers()
    users.value = response.users || []
  } catch (error) {
    console.error('Failed to fetch users:', error)
    alert('获取用户列表失败')
  } finally {
    loading.value = false
  }
}

// 切换用户管理员角色
const toggleAdmin = async (user) => {
  if (loadingUsers.value.includes(user.id)) return
  
  loadingUsers.value.push(user.id)
  try {
    const newRole = !user.is_admin
    await adminAPI.updateUserRole(user.id, newRole)
    user.is_admin = newRole
  } catch (error) {
    console.error('Failed to update user role:', error)
    alert('更新用户角色失败')
  } finally {
    loadingUsers.value = loadingUsers.value.filter(id => id !== user.id)
  }
}

// 确认删除用户
const confirmDelete = (user) => {
  deleteUser.value = user
  showDeleteConfirm.value = true
}

// 关闭删除确认对话框
const closeDeleteConfirm = () => {
  showDeleteConfirm.value = false
  deleteUser.value = null
}

// 删除用户确认
const deleteUserConfirm = async () => {
  if (!deleteUser.value) return
  
  deletingUser.value = true
  try {
    await adminAPI.deleteUser(deleteUser.value.id)
    users.value = users.value.filter(user => user.id !== deleteUser.value.id)
    showDeleteConfirm.value = false
    deleteUser.value = null
    alert('用户删除成功')
  } catch (error) {
    console.error('Failed to delete user:', error)
    alert('删除用户失败')
  } finally {
    deletingUser.value = false
  }
}

// 打开修改密码模态框
const openChangePasswordModal = (user) => {
  changePasswordUser.value = user
  showChangePasswordModal.value = true
  newPassword.value = ''
}

// 关闭修改密码模态框
const closeChangePasswordModal = () => {
  showChangePasswordModal.value = false
  changePasswordUser.value = null
  newPassword.value = ''
}

// 修改密码确认
const changePasswordConfirm = async () => {
  if (!changePasswordUser.value || !newPassword.value) return
  
  changingPassword.value = true
  try {
    await adminAPI.updateUserPassword(changePasswordUser.value.id, newPassword.value)
    showChangePasswordModal.value = false
    changePasswordUser.value = null
    newPassword.value = ''
    alert('密码修改成功')
  } catch (error) {
    console.error('Failed to change password:', error)
    alert('密码修改失败')
  } finally {
    changingPassword.value = false
  }
}

onMounted(() => {
  fetchUsers()
})
</script>

<style scoped>
.users-container {
  display: flex;
  flex-direction: column;
  gap: 20px;
}

.users-content {
  background: white;
  border-radius: 12px;
  box-shadow: 0 2px 8px rgba(0, 0, 0, 0.08);
  overflow: hidden;
}

.users-table-wrapper {
  overflow-x: auto;
}

.users-table {
  width: 100%;
  border-collapse: collapse;
  font-size: 14px;
}

.users-table th {
  background: #f9fafb;
  padding: 15px 20px;
  text-align: left;
  font-weight: 600;
  color: #374151;
  border-bottom: 1px solid #e5e7eb;
}

.users-table td {
  padding: 15px 20px;
  border-bottom: 1px solid #f3f4f6;
}

.user-row:hover {
  background: #f9fafb;
}

/* 用户角色标签样式 */
.role-badge {
  display: inline-block;
  padding: 6px 12px;
  border-radius: 12px;
  font-size: 12px;
  font-weight: 600;
  text-align: center;
}

.admin-badge {
  background: #dbeafe;
  color: #1e40af;
}

.user-badge {
  background: #d1fae5;
  color: #065f46;
}

/* 操作按钮样式 */
.user-actions {
  display: flex;
  gap: 8px;
}

.action-btn {
  padding: 8px 12px;
  border: none;
  border-radius: 6px;
  font-size: 12px;
  font-weight: 500;
  cursor: pointer;
  transition: all 0.2s ease;
}

.action-btn:disabled {
  opacity: 0.6;
  cursor: not-allowed;
}

.role-btn {
  background: #e0e7ff;
  color: #3730a3;
}

.role-btn.add-admin:hover:not(:disabled) {
  background: #c7d2fe;
}

.role-btn.remove-admin {
  background: #fef3c7;
  color: #92400e;
}

.role-btn.remove-admin:hover:not(:disabled) {
  background: #fde68a;
}

.password-btn {
  background: #dcfce7;
  color: #15803d;
}

.password-btn:hover:not(:disabled) {
  background: #bbf7d0;
}

.delete-btn {
  background: #fee2e2;
  color: #b91c1c;
}

.delete-btn:hover:not(:disabled) {
  background: #fecaca;
}

/* 加载和空状态样式 */
.loading-state {
  display: flex;
  flex-direction: column;
  align-items: center;
  justify-content: center;
  padding: 60px 20px;
  gap: 10px;
  color: #6b7280;
}

.loading-spinner {
  font-size: 24px;
  animation: spin 1s linear infinite;
}

@keyframes spin {
  from { transform: rotate(0deg); }
  to { transform: rotate(360deg); }
}

.no-users {
  text-align: center;
  padding: 60px 20px;
  color: #6b7280;
}

/* 模态对话框样式 */
.modal-overlay {
  position: fixed;
  top: 0;
  left: 0;
  right: 0;
  bottom: 0;
  background: rgba(0, 0, 0, 0.5);
  display: flex;
  align-items: center;
  justify-content: center;
  z-index: 1000;
}

.modal-content {
  background: white;
  border-radius: 12px;
  width: 90%;
  max-width: 400px;
  box-shadow: 0 4px 20px rgba(0, 0, 0, 0.15);
}

.modal-header {
  display: flex;
  justify-content: space-between;
  align-items: center;
  padding: 20px;
  border-bottom: 1px solid #e5e7eb;
}

.modal-header h4 {
  margin: 0;
  font-size: 18px;
  color: #1f2937;
}

.close-btn {
  background: none;
  border: none;
  font-size: 24px;
  color: #6b7280;
  cursor: pointer;
  padding: 0;
  width: 24px;
  height: 24px;
  display: flex;
  align-items: center;
  justify-content: center;
  border-radius: 4px;
  transition: all 0.2s ease;
}

.close-btn:hover {
  background: #f3f4f6;
  color: #1f2937;
}

.modal-body {
  padding: 20px;
}

.modal-body p {
  margin: 0 0 15px 0;
  color: #374151;
  line-height: 1.5;
}

.warning-text {
  color: #dc2626 !important;
  font-weight: 500;
}

/* 密码输入组样式 */
.password-input-group {
  margin-top: 20px;
}

.password-input-group label {
  display: block;
  margin-bottom: 8px;
  font-weight: 500;
  color: #374151;
  font-size: 14px;
}

.password-input {
  width: 100%;
  padding: 10px 12px;
  border: 1px solid #d1d5db;
  border-radius: 6px;
  font-size: 14px;
  transition: all 0.2s ease;
  box-sizing: border-box;
}

.password-input:focus {
  outline: none;
  border-color: #6366f1;
  box-shadow: 0 0 0 3px rgba(99, 102, 241, 0.1);
}

.modal-footer {
  display: flex;
  justify-content: flex-end;
  gap: 10px;
  padding: 15px 20px;
  border-top: 1px solid #e5e7eb;
  background: #f9fafb;
  border-bottom-left-radius: 12px;
  border-bottom-right-radius: 12px;
}

.modal-footer .btn {
  padding: 8px 16px;
  border: none;
  border-radius: 6px;
  font-size: 14px;
  font-weight: 500;
  cursor: pointer;
  transition: all 0.2s ease;
}

.cancel-btn {
  background: #f3f4f6;
  color: #374151;
}

.cancel-btn:hover {
  background: #e5e7eb;
}

.save-btn {
  background: #3b82f6;
  color: white;
}

.save-btn:hover:not(:disabled) {
  background: #2563eb;
}

.modal-footer .delete-btn {
  background: #dc2626;
  color: white;
}

.modal-footer .delete-btn:hover {
  background: #b91c1c;
}

.modal-footer .btn:disabled {
  opacity: 0.6;
  cursor: not-allowed;
}
</style>