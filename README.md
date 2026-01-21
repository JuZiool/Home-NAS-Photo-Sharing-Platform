# 家庭 NAS 照片分享平台

一个简单易用的家庭 NAS 照片分享平台，支持照片上传、管理和分享。

## 功能

- 照片上传和预览
- 按日期分组显示
- 照片删除和回收站
- 相册创建和管理
- 暗色/亮色主题切换
- 响应式设计，适配不同设备

## 技术栈

- 前端：Vue 3 + Vite
- 后端：PHP 8 + Nginx
- 数据库：MySQL + Redis
- 部署：Docker + Docker Compose

## 快速开始

### 启动服务
```bash
docker compose up -d
```

### 访问地址
- 前端：`http://localhost:3334`
- 后端 API：`http://localhost:3333`

## 备份

### 数据库
```bash
docker exec photo_sharing_mysql mysqldump -u root -p密码 photodb > backup.sql
```

### 照片文件
```bash
cp -r Photos /path/to/backup/
cp -r TheDeletePhotos /path/to/backup/
```

## 许可证

MIT License
