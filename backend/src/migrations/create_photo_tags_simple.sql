-- 创建照片标签表
CREATE TABLE IF NOT EXISTS `photo_tags` (
  `id` int NOT NULL AUTO_INCREMENT,
  `name` varchar(50) NOT NULL,
  `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `name` (`name`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- 创建照片标签关联表
CREATE TABLE IF NOT EXISTS `photo_tag_relations` (
  `photo_id` int NOT NULL,
  `tag_id` int NOT NULL,
  `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`photo_id`,`tag_id`),
  KEY `tag_id` (`tag_id`),
  CONSTRAINT `photo_tag_relations_ibfk_1` FOREIGN KEY (`photo_id`) REFERENCES `photos` (`id`) ON DELETE CASCADE,
  CONSTRAINT `photo_tag_relations_ibfk_2` FOREIGN KEY (`tag_id`) REFERENCES `photo_tags` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- 扩展照片表，添加内容标签字段
ALTER TABLE `photos` ADD COLUMN `has_content_tags` tinyint NOT NULL DEFAULT 0;