<template>
  <div class="archive">
    <h1>归档</h1>
    <div class="content-placeholder">
      <div class="placeholder-icon">📦</div>
      <div class="placeholder-text">查看您的归档照片</div>
    </div>
  </div>
</template>

<script setup>
// Archive 页面逻辑
</script>

<style scoped>
.archive {
  padding: 20px 0;
}

.archive h1 {
  font-size: 24px;
  font-weight: 600;
  margin-bottom: 40px;
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
</style>