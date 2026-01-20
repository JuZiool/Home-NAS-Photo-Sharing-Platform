<template>
  <div class="sharing">
    <h1>分享</h1>
    <div class="content-placeholder">
      <div class="placeholder-icon">👥</div>
      <div class="placeholder-text">与朋友和家人分享您的照片</div>
    </div>
  </div>
</template>

<script setup>
// Sharing 页面逻辑
</script>

<style scoped>
.sharing {
  padding: 20px 0;
}

.sharing h1 {
  font-size: 24px;
  font-weight: 600;
  margin-bottom: 40px;
  color: #ffffff;
}

.content-placeholder {
  display: flex;
  flex-direction: column;
  align-items: center;
  justify-content: center;
  height: 400px;
  background-color: #1a1a1a;
  border-radius: 12px;
  border: 1px dashed #333333;
  color: #888888;
}

.placeholder-icon {
  font-size: 48px;
  margin-bottom: 20px;
}

.placeholder-text {
  font-size: 16px;
}

/* 亮色主题适配 */
.light-mode .sharing h1 {
  color: #212529;
}

.light-mode .content-placeholder {
  background-color: #f8f9fa;
  border-color: #dee2e6;
  color: #6c757d;
}
</style>