<template>
  <div class="map">
    <h1>Map</h1>
    <div class="content-placeholder">
      <div class="placeholder-icon">🗺️</div>
      <div class="placeholder-text">View your photos on a map</div>
    </div>
  </div>
</template>

<script setup>
// Map 页面逻辑
</script>

<style scoped>
.map {
  padding: 20px 0;
}

.map h1 {
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