<template>
  <div class="photo-tags">
    <div class="tags-header">
      <h3>📌 照片标签</h3>
      <p class="tags-description">点击标签筛选对应照片</p>
    </div>
    
    <!-- 标签云 -->
    <div class="tags-container">
      <span 
        v-for="tag in tags" 
        :key="tag.id"
        :class="['tag-item', { 'active': selectedTag === tag.id }]"
        @click="selectTag(tag.id)"
      >
        {{ tag.name }} <span class="tag-count">({{ tag.count }})</span>
      </span>
      
      <!-- 空状态 -->
      <div v-if="tags.length === 0" class="empty-tags">
        <span class="empty-icon">🔍</span>
        <span class="empty-text">暂无标签</span>
      </div>
    </div>
  </div>
</template>

<script setup>
import { ref, onMounted } from 'vue'
import { tagsAPI } from '../services/api'

const props = defineProps(['selectedTag'])
const emit = defineEmits(['tagChange'])

const tags = ref([])
const loading = ref(false)

/**
 * 加载标签列表
 */
const loadTags = async () => {
  loading.value = true
  try {
    const response = await tagsAPI.getTags()
    if (response.status === 'success') {
      tags.value = response.tags
    }
  } catch (error) {
    console.error('加载标签失败:', error)
  } finally {
    loading.value = false
  }
}

/**
 * 选择标签
 * @param {number} tagId 标签ID
 */
const selectTag = (tagId) => {
  emit('tagChange', tagId === props.selectedTag ? null : tagId)
}

// 组件挂载时加载标签
onMounted(() => {
  loadTags()
})
</script>

<style scoped>
.photo-tags {
  margin-bottom: 20px;
  padding: 16px;
  background: var(--card-bg);
  border-radius: 12px;
  box-shadow: 0 2px 8px rgba(0, 0, 0, 0.05);
}

.tags-header {
  margin-bottom: 16px;
}

.tags-header h3 {
  margin: 0 0 6px 0;
  color: var(--text-color);
  font-size: 16px;
  font-weight: 600;
}

.tags-description {
  margin: 0;
  color: var(--text-secondary);
  font-size: 14px;
}

.tags-container {
  display: flex;
  flex-wrap: wrap;
  gap: 10px;
  align-items: center;
  min-height: 40px;
}

.tag-item {
  padding: 8px 16px;
  background: var(--bg-color);
  border: 1px solid var(--border-color);
  border-radius: 20px;
  font-size: 14px;
  cursor: pointer;
  transition: all 0.2s ease;
  color: var(--text-color);
  user-select: none;
  display: flex;
  align-items: center;
  gap: 6px;
}

.tag-item:hover {
  background: var(--primary-light);
  border-color: var(--primary-color);
  transform: translateY(-1px);
}

.tag-item.active {
  background: var(--primary-color);
  color: white;
  border-color: var(--primary-color);
}

.tag-count {
  font-size: 12px;
  opacity: 0.8;
}

.empty-tags {
  display: flex;
  align-items: center;
  gap: 8px;
  color: var(--text-secondary);
  font-size: 14px;
  padding: 10px;
}

.empty-icon {
  font-size: 16px;
}

.empty-text {
  opacity: 0.8;
}
</style>