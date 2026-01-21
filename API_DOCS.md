# 家庭 NAS 照片分享平台 API 文档

## 1. 概述

这是家庭 NAS 照片分享平台的 API 文档，提供了照片管理、相册管理和用户认证等功能的接口说明。

## 2. 基本信息

- **API 基础 URL**: `http://localhost:3333` 或 `http://your-server-ip:3333`
- **认证方式**: JWT Token
- **请求格式**: JSON (POST/PUT 请求)
- **响应格式**: JSON
- **字符编码**: UTF-8

## 3. 认证

### 3.1 获取 JWT Token

所有需要认证的 API 都需要在请求头中携带 `Authorization: Bearer <token>`

## 4. API 端点

### 4.1 健康检查和根路径

#### 4.1.1 健康检查

```
GET /health
```

**功能**: 检查服务是否正常运行

**响应示例**:
```json
{
  "status": "OK"
}
```

#### 4.1.2 根路径

```
GET /
GET /index.php
```

**功能**: 获取 API 基本信息

**响应示例**:
```json
{
  "status": "success",
  "message": "Photo Sharing Platform API is running",
  "timestamp": "2026-01-21 09:38:56",
  "version": "1.0.0"
}
```

### 4.2 认证相关 API

#### 4.2.1 用户登录

```
POST /api/auth/login
```

**功能**: 用户登录并获取 JWT Token

**请求参数**:
```json
{
  "username": "testuser",
  "password": "password"
}
```

**响应示例**:
```json
{
  "status": "success",
  "token": "eyJhbGciOiJIUzI1NiIsInR5cCI6IkpXVCJ9...",
  "user": {
    "id": 1,
    "username": "testuser",
    "email": "test@example.com"
  }
}
```

#### 4.2.2 用户注册

```
POST /api/auth/register
```

**功能**: 注册新用户

**请求参数**:
```json
{
  "username": "newuser",
  "email": "new@example.com",
  "password": "newpassword"
}
```

**响应示例**:
```json
{
  "status": "success",
  "token": "eyJhbGciOiJIUzI1NiIsInR5cCI6IkpXVCJ9...",
  "user": {
    "id": 2,
    "username": "newuser",
    "email": "new@example.com"
  }
}
```

#### 4.2.3 获取当前用户信息

```
GET /api/auth/me
```

**功能**: 获取当前登录用户的信息

**请求头**:
```
Authorization: Bearer <token>
```

**响应示例**:
```json
{
  "status": "success",
  "user": {
    "id": 1,
    "username": "testuser",
    "email": "test@example.com",
    "created_at": "2026-01-19 00:48:09"
  }
}
```

#### 4.2.4 用户登出

```
POST /api/auth/logout
```

**功能**: 用户登出（前端清除 token）

**请求头**:
```
Authorization: Bearer <token>
```

**响应示例**:
```json
{
  "status": "success",
  "message": "退出登录成功"
}
```

#### 4.2.5 修改密码

```
PUT /api/auth/password
```

**功能**: 修改当前用户密码

**请求头**:
```
Authorization: Bearer <token>
```

**请求参数**:
```json
{
  "old_password": "oldpassword",
  "new_password": "newpassword"
}
```

**响应示例**:
```json
{
  "status": "success",
  "message": "密码修改成功"
}
```

### 4.3 照片相关 API

#### 4.3.1 上传照片

```
POST /api/photos
```

**功能**: 上传照片到用户相册

**请求头**:
```
Authorization: Bearer <token>
Content-Type: multipart/form-data
```

**请求参数**:
- `file`: 照片文件（multipart/form-data）

**响应示例**:
```json
{
  "status": "success",
  "message": "照片上传成功",
  "photo": {
    "id": 1,
    "user_id": 1,
    "album_id": null,
    "filename": "69708371c0f7b.png",
    "original_name": "test.png",
    "size": 244789,
    "type": "image/png",
    "url": "/Photos/1/69708371c0f7b.png",
    "thumbnail_url": null,
    "is_favorite": 0,
    "is_deleted": 0,
    "taken_at": null
  }
}
```

#### 4.3.2 获取用户照片列表

```
GET /api/photos
```

**功能**: 获取当前用户的所有照片（不包括已删除的）

**请求头**:
```
Authorization: Bearer <token>
```

**响应示例**:
```json
{
  "status": "success",
  "photos": [
    {
      "id": 1,
      "user_id": 1,
      "album_id": null,
      "filename": "69708371c0f7b.png",
      "original_name": "test.png",
      "size": 244789,
      "type": "image/png",
      "url": "/Photos/1/69708371c0f7b.png",
      "thumbnail_url": null,
      "is_favorite": 0,
      "is_deleted": 0,
      "taken_at": null,
      "created_at": "2026-01-21 07:42:41",
      "updated_at": "2026-01-21 07:42:41"
    }
  ]
}
```

#### 4.3.3 删除照片（移至回收站）

```
DELETE /api/photos/{photo_id}
```

**功能**: 将照片移至回收站

**请求头**:
```
Authorization: Bearer <token>
```

**路径参数**:
- `photo_id`: 照片 ID

**响应示例**:
```json
{
  "status": "success",
  "message": "照片已删除并移至回收站"
}
```

#### 4.3.4 获取回收站照片列表

```
GET /api/photos/trash
```

**功能**: 获取当前用户回收站中的所有照片

**请求头**:
```
Authorization: Bearer <token>
```

**响应示例**:
```json
{
  "status": "success",
  "photos": [
    {
      "id": 2,
      "user_id": 1,
      "album_id": null,
      "filename": "6970837ac39ae.png",
      "original_name": "deleted.png",
      "size": 1154,
      "type": "image/png",
      "url": "/TheDeletePhotos/1/6970837ac39ae.png",
      "thumbnail_url": null,
      "is_favorite": 0,
      "is_deleted": 1,
      "taken_at": null,
      "created_at": "2026-01-21 07:42:50",
      "updated_at": "2026-01-21 07:54:19"
    }
  ]
}
```

#### 4.3.5 恢复照片

```
POST /api/photos/{photo_id}/restore
```

**功能**: 从回收站恢复照片

**请求头**:
```
Authorization: Bearer <token>
```

**路径参数**:
- `photo_id`: 照片 ID

**响应示例**:
```json
{
  "status": "success",
  "message": "照片已成功恢复"
}
```

#### 4.3.6 永久删除照片

```
DELETE /api/photos/{photo_id}/permanent
```

**功能**: 永久删除回收站中的照片

**请求头**:
```
Authorization: Bearer <token>
```

**路径参数**:
- `photo_id`: 照片 ID

**响应示例**:
```json
{
  "status": "success",
  "message": "照片已永久删除"
}
```

### 4.4 相册相关 API

#### 4.4.1 获取相册列表

```
GET /api/albums
```

**功能**: 获取当前用户的所有相册

**请求头**:
```
Authorization: Bearer <token>
```

**响应示例**:
```json
{
  "status": "success",
  "albums": [
    {
      "id": 1,
      "user_id": 1,
      "name": "默认相册",
      "description": "系统默认相册",
      "created_at": "2026-01-21 07:37:13",
      "updated_at": "2026-01-21 07:37:13",
      "photo_count": 10
    }
  ]
}
```

#### 4.4.2 创建相册

```
POST /api/albums
```

**功能**: 创建新相册

**请求头**:
```
Authorization: Bearer <token>
Content-Type: application/json
```

**请求参数**:
```json
{
  "name": "我的相册",
  "description": "这是我的新相册"
}
```

**响应示例**:
```json
{
  "status": "success",
  "message": "相册创建成功",
  "album": {
    "id": 2,
    "user_id": 1,
    "name": "我的相册",
    "description": "这是我的新相册",
    "created_at": "2026-01-21 09:45:30",
    "updated_at": "2026-01-21 09:45:30",
    "photo_count": 0
  }
}
```

#### 4.4.3 获取相册详情

```
GET /api/albums/{album_id}
```

**功能**: 获取指定相册的详情

**请求头**:
```
Authorization: Bearer <token>
```

**路径参数**:
- `album_id`: 相册 ID

**响应示例**:
```json
{
  "status": "success",
  "album": {
    "id": 1,
    "user_id": 1,
    "name": "默认相册",
    "description": "系统默认相册",
    "created_at": "2026-01-21 07:37:13",
    "updated_at": "2026-01-21 07:37:13",
    "photo_count": 10
  }
}
```

#### 4.4.4 获取相册中的照片

```
GET /api/albums/{album_id}/photos
```

**功能**: 获取指定相册中的所有照片

**请求头**:
```
Authorization: Bearer <token>
```

**路径参数**:
- `album_id`: 相册 ID

**响应示例**:
```json
{
  "status": "success",
  "photos": [
    {
      "id": 1,
      "user_id": 1,
      "album_id": 1,
      "filename": "69708371c0f7b.png",
      "original_name": "test.png",
      "size": 244789,
      "type": "image/png",
      "url": "/Photos/1/69708371c0f7b.png",
      "thumbnail_url": null,
      "is_favorite": 0,
      "is_deleted": 0,
      "taken_at": null,
      "created_at": "2026-01-21 07:42:41",
      "updated_at": "2026-01-21 07:42:41"
    }
  ]
}
```

#### 4.4.5 上传照片到相册

```
POST /api/albums/{album_id}/photos
```

**功能**: 上传照片到指定相册

**请求头**:
```
Authorization: Bearer <token>
Content-Type: multipart/form-data
```

**路径参数**:
- `album_id`: 相册 ID

**请求参数**:
- `file` 或 `files[]`: 照片文件（单文件或多文件上传）

**响应示例**:
```json
{
  "status": "success",
  "message": "照片上传成功",
  "uploaded_count": 2,
  "photos": [
    {
      "id": 11,
      "user_id": 1,
      "album_id": 1,
      "filename": "69708a478ce6f.png",
      "original_name": "photo1.png",
      "size": 79838,
      "type": "image/png",
      "url": "/Photos/1/69708a478ce6f.png",
      "thumbnail_url": null,
      "is_favorite": 0,
      "is_deleted": 0,
      "taken_at": null,
      "created_at": "2026-01-21 08:11:51",
      "updated_at": "2026-01-21 08:11:51"
    },
    {
      "id": 12,
      "user_id": 1,
      "album_id": 1,
      "filename": "69708a4791cf5.jpg",
      "original_name": "photo2.jpg",
      "size": 1144902,
      "type": "image/jpeg",
      "url": "/Photos/1/69708a4791cf5.jpg",
      "thumbnail_url": null,
      "is_favorite": 0,
      "is_deleted": 0,
      "taken_at": "2025-12-14 15:41:07",
      "created_at": "2026-01-21 08:11:51",
      "updated_at": "2026-01-21 08:11:51"
    }
  ]
}
```

#### 4.4.6 删除相册

```
DELETE /api/albums/{album_id}
```

**功能**: 删除指定相册及其所有照片

**请求头**:
```
Authorization: Bearer <token>
```

**路径参数**:
- `album_id`: 相册 ID

**响应示例**:
```json
{
  "status": "success",
  "message": "相册删除成功"
}
```

## 5. 响应状态码

| 状态码 | 描述 |
| ---- | ---- |
| 200 | 请求成功 |
| 400 | 请求参数错误 |
| 401 | 未授权，无效的 Token |
| 403 | 禁止访问 |
| 404 | API 端点不存在 |
| 500 | 服务器内部错误 |

## 6. 错误响应格式

```json
{
  "status": "error",
  "message": "错误描述",
  "detail": "可选的详细错误信息"
}
```

## 7. 示例请求

### 7.1 使用 curl 发送请求

```bash
# 获取 Token
curl -X POST http://localhost:3333/api/auth/login \
  -H "Content-Type: application/json" \
  -d '{"username":"testuser","password":"password"}'

# 使用 Token 访问受保护的 API
curl -X GET http://localhost:3333/api/photos \
  -H "Authorization: Bearer <token>"

# 上传照片
curl -X POST http://localhost:3333/api/photos \
  -H "Authorization: Bearer <token>" \
  -F "file=@/path/to/photo.jpg"
```

## 8. 注意事项

1. 所有上传的照片都会被存储在服务器的 `Photos` 目录下，按用户 ID 分组
2. 删除的照片会被移至 `TheDeletePhotos` 目录，不会立即删除
3. 支持单文件和多文件上传
4. 照片上传大小限制为 50MB
5. JWT Token 有效期为 24 小时

## 9. 更新日志

### v1.0.0 (2026-01-21)
- 初始版本
- 实现了用户认证功能
- 实现了照片管理功能
- 实现了相册管理功能
- 支持照片上传、删除、恢复和永久删除
- 支持相册创建、删除和照片管理
