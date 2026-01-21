# 家庭 NAS 照片分享平台

一个基于 Vue 3 + PHP + MySQL 构建的家庭 NAS 照片分享平台，支持照片上传、管理、分享和主题切换等功能。

## 功能特性

### 照片管理
- ✅ 照片上传和预览
- ✅ 按日期分组显示
- ✅ 照片删除和回收站功能
- ✅ 照片恢复功能
- ✅ 永久删除功能
- ✅ 照片收藏功能

### 相册功能
- ✅ 相册创建和管理
- ✅ 照片添加到相册
- ✅ 相册分享功能

### 主题和用户体验
- ✅ 暗色/亮色主题切换
- ✅ 主题状态持久化
- ✅ 响应式设计，适配不同设备
- ✅ 自定义确认对话框
- ✅ 图片查看器支持缩放、拖动和切换

### 技术特性
- ✅ 前后端分离架构
- ✅ Docker 容器化部署
- ✅ RESTful API 设计
- ✅ JWT 认证
- ✅ MySQL 数据库存储
- ✅ Redis 缓存支持

## 技术栈

### 前端
- **框架**: Vue 3 (Composition API)
- **构建工具**: Vite
- **路由**: Vue Router
- **HTTP 客户端**: Axios
- **样式**: CSS3 (Grid + Flexbox)
- **容器**: Nginx

### 后端
- **语言**: PHP 8
- **Web 服务器**: Nginx
- **数据库**: MySQL 8.0
- **缓存**: Redis 7.0
- **API**: RESTful

### 部署
- **容器化**: Docker
- **编排工具**: Docker Compose

## 目录结构

```
.
├── backend/              # 后端代码
│   ├── src/              # PHP 源码
│   ├── nginx/            # Nginx 配置
│   ├── mysql/            # MySQL 初始化脚本
│   ├── Dockerfile        # 后端 Dockerfile
│   └── start.sh          # 启动脚本
├── frontend/             # 前端代码
│   ├── src/              # Vue 源码
│   ├── nginx.conf        # 前端 Nginx 配置
│   ├── Dockerfile        # 前端 Dockerfile
│   └── package.json      # 前端依赖
├── Photos/               # 照片存储目录
├── TheDeletePhotos/      # 回收站照片目录
├── docker-compose.yml    # Docker Compose 配置
└── README.md             # 项目说明
```

## 快速开始

### 本地开发

#### 前端开发
```bash
cd frontend
npm install
npm run dev
```

前端应用将运行在 http://localhost:5173

#### 后端开发
```bash
cd backend
# 启动 PHP 内置服务器
php -S localhost:8000 -t src/
```

后端 API 将运行在 http://localhost:8000

### Docker 部署

#### 1. 克隆仓库
```bash
git clone https://github.com/JuZiool/Home-NAS-Photo-Sharing-Platform.git
cd Home-NAS-Photo-Sharing-Platform
```

#### 2. 启动服务
```bash
# 停止可能存在的旧容器
docker-compose down

# 构建并启动容器（后台运行）
docker-compose up --build -d
```

#### 3. 访问应用
- 前端：http://localhost:3334
- 后端 API：http://localhost:3333

#### 4. 配置 frp 穿透（可选）

如果需要通过公网访问，可以配置 frp 穿透：

1. 在 frp 客户端配置文件中添加：
   ```ini
   [photo-sharing]
   type = tcp
   local_ip = 127.0.0.1
   local_port = 3334
   remote_port = 你的公网端口
   ```

2. 启动 frp 客户端：
   ```bash
   ./frpc -c frpc.ini
   ```

3. 通过 `公网IP:公网端口` 访问应用

## 核心 API 接口

### 照片相关
- `GET /api/photos` - 获取照片列表
- `POST /api/photos` - 上传照片
- `DELETE /api/photos/:id` - 删除照片（移至回收站）
- `POST /api/photos/:id/restore` - 恢复照片
- `DELETE /api/photos/:id/permanent` - 永久删除照片

### 回收站相关
- `GET /api/photos/trash` - 获取回收站照片列表

### 相册相关
- `GET /api/albums` - 获取相册列表
- `POST /api/albums` - 创建相册
- `GET /api/albums/:id/photos` - 获取相册照片

### 认证相关
- `POST /api/auth/login` - 用户登录
- `POST /api/auth/register` - 用户注册
- `GET /api/auth/me` - 获取当前用户信息

## 环境变量

### MySQL
- `MYSQL_ROOT_PASSWORD` - MySQL 根密码
- `MYSQL_DATABASE` - 数据库名称
- `MYSQL_USER` - 数据库用户名
- `MYSQL_PASSWORD` - 数据库密码

## 主题切换

应用支持暗色/亮色主题切换，主题状态会持久化存储在浏览器的 localStorage 中，刷新页面后不会恢复默认背景。

## 安全性

- 所有 API 请求使用 JWT 认证
- 照片上传限制为 10MB
- Docker 容器化部署，隔离运行环境
- 数据库密码通过环境变量配置

## 备份策略

### 数据库备份
```bash
docker exec photo_sharing_mysql mysqldump -u root -p密码 photodb > backup.sql
```

### 照片文件备份
```bash
# 备份照片目录
cp -r Photos /path/to/backup/
# 备份回收站目录
cp -r TheDeletePhotos /path/to/backup/
```

## 日志查看

### 查看容器日志
```bash
# 查看所有容器日志
docker-compose logs

# 查看特定容器日志
docker-compose logs frontend
docker-compose logs backend
docker-compose logs mysql
```

### 实时查看日志
```bash
docker-compose logs -f
```

## 常见问题

### Q: 照片上传失败
A: 检查文件大小是否超过 10MB，或者检查 Nginx 配置中的 `client_max_body_size` 设置。

### Q: 主题切换后刷新页面恢复默认背景
A: 主题状态已实现持久化，刷新页面不会恢复默认背景。如果出现问题，检查浏览器 localStorage 是否被禁用。

### Q: API 请求失败
A: 检查前端和后端容器是否在同一个 Docker 网络中，或者检查 Nginx 反向代理配置。

### Q: 照片无法显示
A: 检查 Nginx 配置中是否正确配置了静态文件路径，或者检查照片文件权限。

## 技术支持

如果您在使用过程中遇到问题，可以：
- 查看项目的 GitHub Issues
- 提交新的 Issue
- 联系项目维护者

## 许可证

MIT License

## 贡献

欢迎提交 Pull Request 来改进这个项目！

## 致谢

- Vue 3 官方文档
- PHP 官方文档
- MySQL 官方文档
- Docker 官方文档
- 所有为这个项目做出贡献的开发者

---

**版本**: 1.0.0
**最后更新**: 2026-01-19
## 更新日志
lsphotos表缺失taken_at列的问题
