# 家庭 NAS 照片分享平台

一个基于 Vue 3 + PHP + MySQL 构建的家庭 NAS 照片分享平台，支持照片上传、管理、分享和主题切换等功能。

## 功能特性

### 照片管理
- 照片上传、预览和删除
- 按日期分组显示
- 回收站和恢复功能
- 照片收藏功能
- 照片上传后数量自动更新

### 相册功能
- 相册创建和管理
- 照片添加到相册
- 相册分享功能

### 主题和体验
- 暗色/亮色主题切换
- 响应式设计
- 图片查看器支持缩放、拖动和切换
- 优化的爱心按钮视觉效果

### 管理员功能
- 用户管理（查看、修改角色、删除）
- 管理员修改用户密码
- 系统最近活动记录和显示
- 相册和照片管理

### 技术特性
- 前后端分离架构
- Docker 容器化部署
- RESTful API 设计
- JWT 认证
- 增强的复制功能兼容性

## 技术栈

| 分类 | 技术 |
|------|------|
| 前端 | Vue 3 + Vite + Vue Router + Axios |
| 后端 | PHP 8 + Nginx |
| 数据库 | MySQL 8.0 |
| 缓存 | Redis 7.0 |
| 部署 | Docker + Docker Compose |

## 快速开始

### Docker 部署（推荐）

1. 克隆仓库
   ```bash
   git clone https://github.com/JuZiool/Home-NAS-Photo-Sharing-Platform.git
   cd Home-NAS-Photo-Sharing-Platform
   ```

2. 启动服务
   ```bash
   docker-compose up --build -d
   ```

3. 访问应用
   - 前端：http://localhost:3334
   - 后端 API：http://localhost:3333

### 本地开发

#### 前端
```bash
cd frontend
npm install
npm run dev
```
前端应用将运行在 http://localhost:5173

#### 后端
```bash
cd backend
php -S localhost:8000 -t src/
```
后端 API 将运行在 http://localhost:8000

## 核心 API 接口

### 照片相关
- `GET /api/photos` - 获取照片列表
- `POST /api/photos` - 上传照片
- `DELETE /api/photos/:id` - 删除照片
- `GET /api/photos/trash` - 获取回收站照片

### 相册相关
- `GET /api/albums` - 获取相册列表
- `POST /api/albums` - 创建相册
- `GET /api/albums/:id/photos` - 获取相册照片

### 认证相关
- `POST /api/auth/login` - 用户登录
- `POST /api/auth/register` - 用户注册
- `GET /api/auth/me` - 获取当前用户信息

## 环境变量

主要环境变量配置在 `.env` 文件中，包括：
- MySQL 数据库连接信息
- Redis 连接信息
- JWT 密钥

## 安全性

- 所有 API 请求使用 JWT 认证
- 照片上传限制为 10MB
- Docker 容器化部署，隔离运行环境
- 数据库密码通过环境变量配置

## 日志查看

```bash
# 查看所有容器日志
docker-compose logs

# 实时查看日志
docker-compose logs -f
```

## 常见问题

### 照片上传失败
检查文件大小是否超过 10MB，或检查 Nginx 配置中的 `client_max_body_size` 设置。

### 照片无法显示
检查 Nginx 配置中是否正确配置了静态文件路径，或检查照片文件权限。

## 许可证

MIT License

## 贡献

欢迎提交 Pull Request 来改进这个项目！
