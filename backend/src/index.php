<?php

header('Content-Type: application/json');

// 处理OPTIONS请求
if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    exit;
}

// 数据库连接配置
$dbConfig = [
    'host' => 'mysql',
    'port' => 3306,
    'dbname' => 'photodb',
    'user' => 'photouser',
    'password' => 'photopassword'
];

// 初始化数据库连接
$pdo = null;
try {
    $pdo = new PDO(
        "mysql:host={$dbConfig['host']};port={$dbConfig['port']};dbname={$dbConfig['dbname']};charset=utf8mb4",
        $dbConfig['user'],
        $dbConfig['password'],
        [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION]
    );
} catch (PDOException $e) {
    echo json_encode([
        'status' => 'error',
        'message' => 'Database connection failed: ' . $e->getMessage()
    ], JSON_UNESCAPED_UNICODE);
    exit;
}

// 简单的JWT生成函数
function generateJWT($user_id, $username) {
    $secretKey = 'your-secret-key-change-this-in-production';
    $payload = [
        'sub' => $user_id,
        'name' => $username,
        'iat' => time(),
        'exp' => time() + 3600 * 24 // 24小时过期
    ];
    
    // 简单的JWT生成（实际项目中建议使用专门的JWT库）
    $header = json_encode(['alg' => 'HS256', 'typ' => 'JWT']);
    $payload = json_encode($payload);
    
    $base64UrlHeader = str_replace(['+', '/', '='], ['-', '_', ''], base64_encode($header));
    $base64UrlPayload = str_replace(['+', '/', '='], ['-', '_', ''], base64_encode($payload));
    
    $signature = hash_hmac('sha256', $base64UrlHeader . '.' . $base64UrlPayload, $secretKey, true);
    $base64UrlSignature = str_replace(['+', '/', '='], ['-', '_', ''], base64_encode($signature));
    
    return $base64UrlHeader . '.' . $base64UrlPayload . '.' . $base64UrlSignature;
}

// 提取照片拍摄日期的函数
function extractPhotoTakenDate($filePath) {
    // 检查文件是否为图像
    $imageInfo = getimagesize($filePath);
    if (!$imageInfo) {
        return null;
    }
    
    // 检查是否支持EXIF读取
    if (!function_exists('exif_read_data')) {
        return null;
    }
    
    try {
        // 读取EXIF信息
        $exif = exif_read_data($filePath, 'IFD0', true);
        
        // 尝试从不同的EXIF字段获取拍摄日期
        $takenDate = null;
        
        // 优先尝试DateTimeOriginal字段（通常包含实际拍摄时间）
        if (isset($exif['EXIF']['DateTimeOriginal'])) {
            $takenDate = $exif['EXIF']['DateTimeOriginal'];
        } 
        // 其次尝试DateTime字段
        elseif (isset($exif['IFD0']['DateTime'])) {
            $takenDate = $exif['IFD0']['DateTime'];
        }
        // 最后尝试CreateDate字段
        elseif (isset($exif['EXIF']['CreateDate'])) {
            $takenDate = $exif['EXIF']['CreateDate'];
        }
        
        if ($takenDate) {
            // 将EXIF日期格式（YYYY:MM:DD HH:MM:SS）转换为MySQL日期格式（YYYY-MM-DD HH:MM:SS）
            $takenDate = str_replace(':', '-', substr($takenDate, 0, 10)) . ' ' . substr($takenDate, 11);
            return $takenDate;
        }
    } catch (Exception $e) {
        // 忽略任何EXIF读取错误
    }
    
    return null;
}

// 生成缩略图的函数
function generateThumbnail($filePath, $outputPath, $maxWidth = 200, $maxHeight = 200) {
    // 确保输出目录存在
    $outputDir = dirname($outputPath);
    if (!file_exists($outputDir)) {
        mkdir($outputDir, 0755, true);
    }
    
    // 检查文件是否为图像
    $imageInfo = getimagesize($filePath);
    if (!$imageInfo) {
        error_log("generateThumbnail: Not an image - $filePath");
        return false;
    }
    
    $originalWidth = $imageInfo[0];
    $originalHeight = $imageInfo[1];
    $imageType = $imageInfo[2];
    
    // 计算缩放比例
    $widthRatio = $maxWidth / $originalWidth;
    $heightRatio = $maxHeight / $originalHeight;
    $ratio = min($widthRatio, $heightRatio);
    
    $newWidth = intval($originalWidth * $ratio);
    $newHeight = intval($originalHeight * $ratio);
    
    // 创建新图像
    $newImage = imagecreatetruecolor($newWidth, $newHeight);
    if (!$newImage) {
        error_log("generateThumbnail: Failed to create new image - $filePath");
        return false;
    }
    
    // 根据图像类型读取原始图像
    $sourceImage = null;
    switch ($imageType) {
        case IMAGETYPE_JPEG:
            $sourceImage = imagecreatefromjpeg($filePath);
            break;
        case IMAGETYPE_PNG:
            $sourceImage = imagecreatefrompng($filePath);
            // 保留PNG透明通道
            imagesavealpha($newImage, true);
            $transparent = imagecolorallocatealpha($newImage, 0, 0, 0, 127);
            imagefill($newImage, 0, 0, $transparent);
            break;
        case IMAGETYPE_GIF:
            $sourceImage = imagecreatefromgif($filePath);
            break;
        default:
            error_log("generateThumbnail: Unsupported image type - $imageType for $filePath");
            imagedestroy($newImage);
            return false;
    }
    
    if (!$sourceImage) {
        error_log("generateThumbnail: Failed to load source image - $filePath");
        imagedestroy($newImage);
        return false;
    }
    
    // 缩放图像
    $result = imagecopyresampled(
        $newImage,
        $sourceImage,
        0,
        0,
        0,
        0,
        $newWidth,
        $newHeight,
        $originalWidth,
        $originalHeight
    );
    
    if (!$result) {
        error_log("generateThumbnail: Failed to resize image - $filePath");
        imagedestroy($sourceImage);
        imagedestroy($newImage);
        return false;
    }
    
    // 保存缩略图
    $success = false;
    switch ($imageType) {
        case IMAGETYPE_JPEG:
            $success = imagejpeg($newImage, $outputPath, 80);
            break;
        case IMAGETYPE_PNG:
            $success = imagepng($newImage, $outputPath, 6);
            break;
        case IMAGETYPE_GIF:
            $success = imagegif($newImage, $outputPath);
            break;
    }
    
    if (!$success) {
        error_log("generateThumbnail: Failed to save thumbnail - $outputPath");
    }
    
    // 释放内存
    imagedestroy($sourceImage);
    imagedestroy($newImage);
    
    return $success;
}

// 解析请求体，支持JSON和表单格式
$requestBody = [];
if ($_SERVER['REQUEST_METHOD'] === 'POST' || $_SERVER['REQUEST_METHOD'] === 'PUT') {
    $contentType = $_SERVER['CONTENT_TYPE'] ?? '';
    if (strpos($contentType, 'application/json') !== false) {
        // JSON格式请求
        $requestBody = json_decode(file_get_contents('php://input'), true) ?? [];
    } else {
        // 表单格式请求
        $requestBody = $_POST ?? [];
    }
}

// API路由处理
$path = $_SERVER['REQUEST_URI'];
$method = $_SERVER['REQUEST_METHOD'];

// 健康检查
if ($path === '/health') {
    echo json_encode(['status' => 'OK'], JSON_UNESCAPED_UNICODE);
    exit;
}

// 根路径响应
if ($path === '/' || $path === '/index.php') {
    echo json_encode([
        'status' => 'success',
        'message' => 'Photo Sharing Platform API is running',
        'timestamp' => date('Y-m-d H:i:s'),
        'version' => '1.0.0'
    ], JSON_UNESCAPED_UNICODE);
    exit;
}

// 认证相关路由
if (strpos($path, '/api/auth') === 0) {
    // 登录路由
    if ($path === '/api/auth/login' && $method === 'POST') {
        $username = $requestBody['username'] ?? '';
        $password = $requestBody['password'] ?? '';
        
        if (empty($username) || empty($password)) {
            http_response_code(400);
            echo json_encode([
                'status' => 'error',
                'message' => '用户名和密码不能为空'
            ], JSON_UNESCAPED_UNICODE);
            exit;
        }
        
        // 查询用户
        $stmt = $pdo->prepare("SELECT id, username, email, password FROM users WHERE username = ?");
        $stmt->execute([$username]);
        $user = $stmt->fetch(PDO::FETCH_ASSOC);
        
        if (!$user) {
            http_response_code(401);
            echo json_encode([
                'status' => 'error',
                'message' => '用户名不存在'
            ], JSON_UNESCAPED_UNICODE);
            exit;
        }
        
        // 验证密码（使用bcrypt）
        if (!password_verify($password, $user['password'])) {
            http_response_code(401);
            echo json_encode([
                'status' => 'error',
                'message' => '密码错误'
            ], JSON_UNESCAPED_UNICODE);
            exit;
        }
        
        // 生成JWT
        $token = generateJWT($user['id'], $user['username']);
        
        // 返回响应
        echo json_encode([
            'status' => 'success',
            'token' => $token,
            'user' => [
                'id' => $user['id'],
                'username' => $user['username'],
                'email' => $user['email']
            ]
        ], JSON_UNESCAPED_UNICODE);
        exit;
    }
    
    // 注册路由
    if ($path === '/api/auth/register' && $method === 'POST') {
        $username = $requestBody['username'] ?? '';
        $email = $requestBody['email'] ?? '';
        $password = $requestBody['password'] ?? '';
        
        if (empty($username) || empty($email) || empty($password)) {
            echo json_encode([
                'status' => 'error',
                'message' => '用户名、邮箱和密码不能为空'
            ], JSON_UNESCAPED_UNICODE);
            exit;
        }
        
        // 检查用户名是否已存在
        $stmt = $pdo->prepare("SELECT id FROM users WHERE username = ?");
        $stmt->execute([$username]);
        if ($stmt->fetch(PDO::FETCH_ASSOC)) {
            echo json_encode([
                'status' => 'error',
                'message' => '用户名已存在'
            ], JSON_UNESCAPED_UNICODE);
            exit;
        }
        
        // 检查邮箱是否已存在
        $stmt = $pdo->prepare("SELECT id FROM users WHERE email = ?");
        $stmt->execute([$email]);
        if ($stmt->fetch(PDO::FETCH_ASSOC)) {
            echo json_encode([
                'status' => 'error',
                'message' => '邮箱已存在'
            ], JSON_UNESCAPED_UNICODE);
            exit;
        }
        
        // 密码加密
        $hashedPassword = password_hash($password, PASSWORD_DEFAULT);
        
        // 插入用户
        $stmt = $pdo->prepare("INSERT INTO users (username, email, password) VALUES (?, ?, ?)");
        if ($stmt->execute([$username, $email, $hashedPassword])) {
            $userId = $pdo->lastInsertId();
            $token = generateJWT($userId, $username);
            
            echo json_encode([
                'status' => 'success',
                'token' => $token,
                'user' => [
                    'id' => $userId,
                    'username' => $username,
                    'email' => $email
                ]
            ], JSON_UNESCAPED_UNICODE);
        } else {
            echo json_encode([
                'status' => 'error',
                'message' => '注册失败'
            ], JSON_UNESCAPED_UNICODE);
        }
        exit;
    }
    
    // 获取当前用户信息
    if ($path === '/api/auth/me' && $method === 'GET') {
        // 简单的JWT验证（实际项目中建议使用专门的JWT库）
    $authHeader = $_SERVER['HTTP_AUTHORIZATION'] ?? '';
    if (empty($authHeader) || substr($authHeader, 0, 7) !== 'Bearer ') {
        http_response_code(401);
        echo json_encode([
            'status' => 'error',
            'message' => '未授权'
        ], JSON_UNESCAPED_UNICODE);
        exit;
    }
        
        // 这里应该解析JWT获取用户ID，为了简化我们从请求中提取用户ID
        // 在实际项目中，应该使用JWT库解析token获取用户ID
        // 为了演示，我们直接返回所有用户信息（实际项目中应根据JWT中的user_id查询）
        
        // 解析token（简化版，实际项目中需要使用JWT库）
        $token = substr($authHeader, 7); // 移除 'Bearer ' 前缀
        
        // 简化处理：从token中提取用户名（实际项目中应验证token并获取用户ID）
        // 这里我们将token按点分割，获取payload部分
        $tokenParts = explode('.', $token);
        if (count($tokenParts) === 3) {
            $payload = json_decode(base64_decode($tokenParts[1]), true);
            $username = $payload['name'] ?? '';
            
            if ($username) {
                // 根据用户名查询用户信息
                $stmt = $pdo->prepare("SELECT id, username, email, created_at FROM users WHERE username = ?");
                $stmt->execute([$username]);
                $user = $stmt->fetch(PDO::FETCH_ASSOC);
                
                if ($user) {
                    echo json_encode([
                        'status' => 'success',
                        'user' => $user
                    ], JSON_UNESCAPED_UNICODE);
                    exit;
                }
            }
        }
        
        // 如果token解析失败或用户不存在，返回错误
        http_response_code(401);
        echo json_encode([
            'status' => 'error',
            'message' => '无效的token',
            'detail' => 'JWT token验证失败或用户不存在'
        ], JSON_UNESCAPED_UNICODE);
        exit;
    }
    
    // 退出登录
    if ($path === '/api/auth/logout' && $method === 'POST') {
        // JWT是无状态的，退出登录只需要前端清除token即可
        echo json_encode([
            'status' => 'success',
            'message' => '退出登录成功'
        ], JSON_UNESCAPED_UNICODE);
        exit;
    }
    
    // 修改密码
    if ($path === '/api/auth/password' && $method === 'PUT') {
        // 简单的JWT验证（实际项目中建议使用专门的JWT库）
        $authHeader = $_SERVER['HTTP_AUTHORIZATION'] ?? '';
        if (empty($authHeader) || !str_starts_with($authHeader, 'Bearer ')) {
            echo json_encode([
                'status' => 'error',
                'message' => '未授权'
            ], JSON_UNESCAPED_UNICODE);
            exit;
        }
        
        $oldPassword = $requestBody['old_password'] ?? '';
        $newPassword = $requestBody['new_password'] ?? '';
        
        if (empty($oldPassword) || empty($newPassword)) {
            echo json_encode([
                'status' => 'error',
                'message' => '原密码和新密码不能为空'
            ], JSON_UNESCAPED_UNICODE);
            exit;
        }
        
        // 解析token获取用户名
        $token = substr($authHeader, 7); // 移除 'Bearer ' 前缀
        $tokenParts = explode('.', $token);
        
        if (count($tokenParts) === 3) {
            $payload = json_decode(base64_decode($tokenParts[1]), true);
            $username = $payload['name'] ?? '';
            
            if ($username) {
                // 根据用户名查询用户
                $stmt = $pdo->prepare("SELECT id, password FROM users WHERE username = ?");
                $stmt->execute([$username]);
                $user = $stmt->fetch(PDO::FETCH_ASSOC);
                
                if (!$user) {
                    echo json_encode([
                        'status' => 'error',
                        'message' => '用户不存在'
                    ], JSON_UNESCAPED_UNICODE);
                    exit;
                }
                
                // 验证原密码
                if (!password_verify($oldPassword, $user['password'])) {
                    echo json_encode([
                        'status' => 'error',
                        'message' => '原密码错误'
                    ], JSON_UNESCAPED_UNICODE);
                    exit;
                }
                
                // 加密新密码
                $hashedPassword = password_hash($newPassword, PASSWORD_DEFAULT);
                
                // 更新密码
                $stmt = $pdo->prepare("UPDATE users SET password = ? WHERE id = ?");
                if ($stmt->execute([$hashedPassword, $user['id']])) {
                    echo json_encode([
                        'status' => 'success',
                        'message' => '密码修改成功'
                    ], JSON_UNESCAPED_UNICODE);
                } else {
                    echo json_encode([
                        'status' => 'error',
                        'message' => '密码修改失败'
                    ], JSON_UNESCAPED_UNICODE);
                }
                exit;
            }
        }
        
        // 如果token解析失败
        echo json_encode([
            'status' => 'error',
            'message' => '无效的token'
        ], JSON_UNESCAPED_UNICODE);
        exit;
    }
}

// 照片相关路由
if (strpos($path, '/api/photos') === 0) {
    // 验证JWT token
    $authHeader = $_SERVER['HTTP_AUTHORIZATION'] ?? '';
    if (empty($authHeader) || substr($authHeader, 0, 7) !== 'Bearer ') {
        http_response_code(401);
        echo json_encode([
            'status' => 'error',
            'message' => '未授权'
        ], JSON_UNESCAPED_UNICODE);
        exit;
    }
    
    $token = substr($authHeader, 7);
    $tokenParts = explode('.', $token);
    $userId = null;
    
    if (count($tokenParts) === 3) {
        $header = json_decode(base64_decode($tokenParts[0]), true);
        $payload = json_decode(base64_decode($tokenParts[1]), true);
        $signature = $tokenParts[2];
        
        // 验证token签名
        $secretKey = 'your-secret-key-change-this-in-production';
        $expectedSignature = str_replace(['+', '/', '='], ['-', '_', ''], base64_encode(hash_hmac('sha256', $tokenParts[0] . '.' . $tokenParts[1], $secretKey, true)));
        
        if ($signature === $expectedSignature) {
            $userId = $payload['sub'] ?? null;
        }
    }
    
    if (!$userId) {
        http_response_code(401);
        echo json_encode([
            'status' => 'error',
            'message' => '无效的token',
            'detail' => 'JWT token验证失败'
        ], JSON_UNESCAPED_UNICODE);
        exit;
    }
    
    // 确保照片目录存在
    $photosDir = __DIR__ . '/../Photos';
    $userPhotosDir = $photosDir . '/' . $userId;
    $thumbnailDir = $photosDir . '/thumbnail/' . $userId;
    
    if (!file_exists($photosDir)) {
        mkdir($photosDir, 0755, true);
        chown($photosDir, 'nginx');
        chgrp($photosDir, 'nginx');
    }
    
    if (!file_exists($userPhotosDir)) {
        mkdir($userPhotosDir, 0755, true);
        chown($userPhotosDir, 'nginx');
        chgrp($userPhotosDir, 'nginx');
    }
    
    // 确保缩略图目录存在
    if (!file_exists($thumbnailDir)) {
        mkdir($thumbnailDir, 0755, true);
        chown($thumbnailDir, 'nginx');
        chgrp($thumbnailDir, 'nginx');
    }
    
    // 照片上传
    if ($path === '/api/photos' && $method === 'POST') {
        // 检查是否有文件上传
        if (empty($_FILES['file'])) {
            echo json_encode([
                'status' => 'error',
                'message' => '请选择要上传的文件或文件大小超过限制',
                'detail' => 'PHP上传限制：' . ini_get('post_max_size')
            ], JSON_UNESCAPED_UNICODE);
            exit;
        }
        
        $file = $_FILES['file'];
        
        // 确保file是数组
        if (!is_array($file)) {
            echo json_encode([
                'status' => 'error',
                'message' => '文件格式错误'
            ], JSON_UNESCAPED_UNICODE);
            exit;
        }
        
        // 检查文件上传错误
        if (!isset($file['error']) || $file['error'] !== UPLOAD_ERR_OK) {
            echo json_encode([
                'status' => 'error',
                'message' => '文件上传失败: ' . ($file['error'] ?? '未知错误')
            ], JSON_UNESCAPED_UNICODE);
            exit;
        }
        
        // 生成唯一文件名
        $extension = pathinfo($file['name'], PATHINFO_EXTENSION);
        $filename = uniqid() . '.' . $extension;
        
        // 移动文件到用户目录
        $destination = $userPhotosDir . '/' . $filename;
        if (!move_uploaded_file($file['tmp_name'], $destination)) {
            echo json_encode([
                'status' => 'error',
                'message' => '文件移动失败'
            ], JSON_UNESCAPED_UNICODE);
            exit;
        }
        
        // 生成缩略图
        $thumbnailFilename = $filename;
        $thumbnailPath = $thumbnailDir . '/' . $thumbnailFilename;
        $thumbnailUrl = '/Photos/thumbnail/' . $userId . '/' . $thumbnailFilename;
        
        // 调用缩略图生成函数
        generateThumbnail($destination, $thumbnailPath);
        
        // 提取照片拍摄时间
        $taken_at = extractPhotoTakenDate($destination);
        
        // 准备照片信息
        $photoInfo = [
            'user_id' => $userId,
            'album_id' => null,
            'filename' => $filename,
            'original_name' => $file['name'],
            'size' => $file['size'],
            'type' => $file['type'],
            'url' => '/Photos/' . $userId . '/' . $filename,
            'thumbnail_url' => $thumbnailUrl,
            'is_favorite' => 0, // 使用整数0表示false
            'is_deleted' => 0, // 使用整数0表示false
            'taken_at' => $taken_at
        ];
        
        // 保存到数据库
        $stmt = $pdo->prepare("INSERT INTO photos (user_id, album_id, filename, original_name, size, type, url, thumbnail_url, is_favorite, is_deleted, taken_at) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)");
        if ($stmt->execute([
            $photoInfo['user_id'],
            $photoInfo['album_id'],
            $photoInfo['filename'],
            $photoInfo['original_name'],
            $photoInfo['size'],
            $photoInfo['type'],
            $photoInfo['url'],
            $photoInfo['thumbnail_url'],
            $photoInfo['is_favorite'],
            $photoInfo['is_deleted'],
            $photoInfo['taken_at']
        ])) {
            $photoId = $pdo->lastInsertId();
            $photoInfo['id'] = $photoId;
            
            echo json_encode([
                'status' => 'success',
                'message' => '照片上传成功',
                'photo' => $photoInfo
            ], JSON_UNESCAPED_UNICODE);
        } else {
            // 删除已上传的文件和缩略图
            unlink($destination);
            if (file_exists($thumbnailPath)) {
                unlink($thumbnailPath);
            }
            
            echo json_encode([
                'status' => 'error',
                'message' => '照片信息保存失败'
            ], JSON_UNESCAPED_UNICODE);
        }
        
        exit;
    }
    
    // 获取用户照片列表
    if ($path === '/api/photos' && $method === 'GET') {
        $stmt = $pdo->prepare("SELECT * FROM photos WHERE user_id = ? AND is_deleted = false ORDER BY taken_at DESC, created_at DESC");
        $stmt->execute([$userId]);
        $photos = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
        echo json_encode([
            'status' => 'success',
            'photos' => $photos
        ], JSON_UNESCAPED_UNICODE);
        exit;
    }
    
    // 删除照片（移至回收站）
    if (preg_match('/^\/api\/photos\/([0-9]+)$/', $path, $matches) && $method === 'DELETE') {
        $photoId = $matches[1];
        
        // 获取照片信息
        $stmt = $pdo->prepare("SELECT * FROM photos WHERE id = ? AND user_id = ? AND is_deleted = false");
        $stmt->execute([$photoId, $userId]);
        $photo = $stmt->fetch(PDO::FETCH_ASSOC);
        
        if (!$photo) {
            echo json_encode([
                'status' => 'error',
                'message' => '照片不存在或无权限访问'
            ], JSON_UNESCAPED_UNICODE);
            exit;
        }
        
        // 确保回收站目录存在
        $deleteDir = __DIR__ . '/../TheDeletePhotos';
        $userDeleteDir = $deleteDir . '/' . $userId;
        
        if (!file_exists($deleteDir)) {
            mkdir($deleteDir, 0777, true);
        }
        
        if (!file_exists($userDeleteDir)) {
            mkdir($userDeleteDir, 0777, true);
        }
        
        // 源文件路径和目标文件路径
        $sourcePath = __DIR__ . '/../' . substr($photo['url'], 1);
        $destinationPath = $userDeleteDir . '/' . $photo['filename'];
        
        // 移动文件到回收站目录
        if (file_exists($sourcePath)) {
            // 确保目标目录存在且有正确的权限
            if (!is_dir(dirname($destinationPath))) {
                mkdir(dirname($destinationPath), 0755, true);
                chown(dirname($destinationPath), 'nginx');
                chgrp(dirname($destinationPath), 'nginx');
            }
            // 尝试移动文件
            if (!rename($sourcePath, $destinationPath)) {
                // 如果移动失败，尝试复制后删除
                if (copy($sourcePath, $destinationPath)) {
                    unlink($sourcePath);
                }
            }
        }
        
        // 更新数据库中的照片状态
        $stmt = $pdo->prepare("UPDATE photos SET is_deleted = 1 WHERE id = ? AND user_id = ?");
        if ($stmt->execute([$photoId, $userId])) {
            echo json_encode([
                'status' => 'success',
                'message' => '照片已删除并移至回收站'
            ], JSON_UNESCAPED_UNICODE);
        } else {
            echo json_encode([
                'status' => 'error',
                'message' => '照片删除失败'
            ], JSON_UNESCAPED_UNICODE);
        }
        exit;
    }
    
    // 获取回收站照片列表
    if ($path === '/api/photos/trash' && $method === 'GET') {
        // 获取当前用户的回收站照片
        $stmt = $pdo->prepare("SELECT * FROM photos WHERE user_id = ? AND is_deleted = true ORDER BY taken_at DESC, created_at DESC");
        $stmt->execute([$userId]);
        $photos = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
        // 更新回收站照片的URL为回收站目录
        foreach ($photos as &$photo) {
            // 将/Photos/替换为/TheDeletePhotos/
            $photo['url'] = str_replace('/Photos/', '/TheDeletePhotos/', $photo['url']);
        }
        
        echo json_encode([
            'status' => 'success',
            'photos' => $photos
        ], JSON_UNESCAPED_UNICODE);
        exit;
    }
    
    // 恢复照片
    if (preg_match('/^\/api\/photos\/([0-9]+)\/restore$/', $path, $matches) && $method === 'POST') {
        $photoId = $matches[1];
        
        // 获取照片信息
        $stmt = $pdo->prepare("SELECT * FROM photos WHERE id = ? AND user_id = ? AND is_deleted = true");
        $stmt->execute([$photoId, $userId]);
        $photo = $stmt->fetch(PDO::FETCH_ASSOC);
        
        if (!$photo) {
            echo json_encode([
                'status' => 'error',
                'message' => '照片不存在或无权限访问'
            ], JSON_UNESCAPED_UNICODE);
            exit;
        }
        
        // 确保用户照片目录存在
        $userPhotosDir = __DIR__ . '/../Photos/' . $userId;
        if (!file_exists($userPhotosDir)) {
            mkdir($userPhotosDir, 0777, true);
        }
        
        // 源文件路径（回收站）和目标文件路径（原始位置）
        $sourcePath = __DIR__ . '/../TheDeletePhotos/' . $userId . '/' . $photo['filename'];
        $destinationPath = $userPhotosDir . '/' . $photo['filename'];
        
        // 移动文件回原始目录
        if (file_exists($sourcePath)) {
            rename($sourcePath, $destinationPath);
        }
        
        // 更新数据库中的照片状态
        $stmt = $pdo->prepare("UPDATE photos SET is_deleted = 0 WHERE id = ? AND user_id = ?");
        if ($stmt->execute([$photoId, $userId])) {
            echo json_encode([
                'status' => 'success',
                'message' => '照片已成功恢复'
            ], JSON_UNESCAPED_UNICODE);
        } else {
            echo json_encode([
                'status' => 'error',
                'message' => '照片恢复失败'
            ], JSON_UNESCAPED_UNICODE);
        }
        exit;
    }
    
    // 永久删除照片
    if (preg_match('/^\/api\/photos\/([0-9]+)\/permanent$/', $path, $matches) && $method === 'DELETE') {
        $photoId = $matches[1];
        
        // 获取照片信息
        $stmt = $pdo->prepare("SELECT * FROM photos WHERE id = ? AND user_id = ? AND is_deleted = true");
        $stmt->execute([$photoId, $userId]);
        $photo = $stmt->fetch(PDO::FETCH_ASSOC);
        
        if (!$photo) {
            echo json_encode([
                'status' => 'error',
                'message' => '照片不存在或无权限访问'
            ], JSON_UNESCAPED_UNICODE);
            exit;
        }
        
        // 从回收站目录中删除文件
        $userDeleteDir = __DIR__ . '/../TheDeletePhotos/' . $userId;
        $filePath = $userDeleteDir . '/' . $photo['filename'];
        
        if (file_exists($filePath)) {
            unlink($filePath);
        }
        
        // 从数据库中删除照片记录
        $stmt = $pdo->prepare("DELETE FROM photos WHERE id = ? AND user_id = ? AND is_deleted = true");
        if ($stmt->execute([$photoId, $userId])) {
            echo json_encode([
                'status' => 'success',
                'message' => '照片已永久删除'
            ], JSON_UNESCAPED_UNICODE);
        } else {
            echo json_encode([
                'status' => 'error',
                'message' => '照片删除失败'
            ], JSON_UNESCAPED_UNICODE);
        }
        exit;
    }
}

// 相册相关路由
if (strpos($path, '/api/albums') === 0) {
    // 验证JWT token
    $authHeader = $_SERVER['HTTP_AUTHORIZATION'] ?? '';
    if (empty($authHeader) || substr($authHeader, 0, 7) !== 'Bearer ') {
        echo json_encode([
            'status' => 'error',
            'message' => '未授权'
        ], JSON_UNESCAPED_UNICODE);
        exit;
    }
    
    $token = substr($authHeader, 7);
    $tokenParts = explode('.', $token);
    $userId = null;
    
    if (count($tokenParts) === 3) {
        $payload = json_decode(base64_decode($tokenParts[1]), true);
        $signature = $tokenParts[2];
        
        // 验证token签名
        $secretKey = 'your-secret-key-change-this-in-production';
        $expectedSignature = str_replace(['+', '/', '='], ['-', '_', ''], base64_encode(hash_hmac('sha256', $tokenParts[0] . '.' . $tokenParts[1], $secretKey, true)));
        
        if ($signature === $expectedSignature) {
            $userId = $payload['sub'] ?? null;
        }
    }
    
    if (!$userId) {
        http_response_code(401);
        echo json_encode([
            'status' => 'error',
            'message' => '无效的token',
            'detail' => 'JWT token验证失败'
        ], JSON_UNESCAPED_UNICODE);
        exit;
    }
    
    // 获取相册列表
    if ($path === '/api/albums' && $method === 'GET') {
        $stmt = $pdo->prepare("SELECT albums.*, COUNT(photos.id) as photo_count FROM albums LEFT JOIN photos ON albums.id = photos.album_id AND photos.is_deleted = false WHERE albums.user_id = ? GROUP BY albums.id ORDER BY albums.created_at DESC");
        $stmt->execute([$userId]);
        $albums = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
        echo json_encode([
            'status' => 'success',
            'albums' => $albums
        ], JSON_UNESCAPED_UNICODE);
        exit;
    }
    
    // 创建相册
    if ($path === '/api/albums' && $method === 'POST') {
        $name = $requestBody['name'] ?? '';
        $description = $requestBody['description'] ?? '';
        
        if (empty($name)) {
            echo json_encode([
                'status' => 'error',
                'message' => '相册名称不能为空'
            ], JSON_UNESCAPED_UNICODE);
            exit;
        }
        
        $stmt = $pdo->prepare("INSERT INTO albums (user_id, name, description) VALUES (?, ?, ?)");
        if ($stmt->execute([$userId, $name, $description])) {
            $albumId = $pdo->lastInsertId();
            
            echo json_encode([
                'status' => 'success',
                'message' => '相册创建成功',
                'album' => [
                    'id' => $albumId,
                    'user_id' => $userId,
                    'name' => $name,
                    'description' => $description,
                    'created_at' => date('Y-m-d H:i:s'),
                    'updated_at' => date('Y-m-d H:i:s'),
                    'photo_count' => 0
                ]
            ], JSON_UNESCAPED_UNICODE);
        } else {
            echo json_encode([
                'status' => 'error',
                'message' => '相册创建失败'
            ], JSON_UNESCAPED_UNICODE);
        }
        
        exit;
    }
    
    // 获取相册详情
    if (preg_match('/^\/api\/albums\/([0-9]+)$/', $path, $matches) && $method === 'GET') {
        $albumId = $matches[1];
        
        $stmt = $pdo->prepare("SELECT albums.*, COUNT(photos.id) as photo_count FROM albums LEFT JOIN photos ON albums.id = photos.album_id AND photos.is_deleted = false WHERE albums.id = ? AND albums.user_id = ? GROUP BY albums.id");
        $stmt->execute([$albumId, $userId]);
        $album = $stmt->fetch(PDO::FETCH_ASSOC);
        
        if ($album) {
            echo json_encode([
                'status' => 'success',
                'album' => $album
            ], JSON_UNESCAPED_UNICODE);
        } else {
            echo json_encode([
                'status' => 'error',
                'message' => '相册不存在或无权限访问'
            ], JSON_UNESCAPED_UNICODE);
        }
        
        exit;
    }
    
    // 获取相册中的照片
    if (preg_match('/^\/api\/albums\/([0-9]+)\/photos$/', $path, $matches) && $method === 'GET') {
        $albumId = $matches[1];
        
        // 检查相册是否属于当前用户
        $stmt = $pdo->prepare("SELECT id FROM albums WHERE id = ? AND user_id = ?");
        $stmt->execute([$albumId, $userId]);
        if (!$stmt->fetch(PDO::FETCH_ASSOC)) {
            echo json_encode([
                'status' => 'error',
                'message' => '相册不存在或无权限访问'
            ], JSON_UNESCAPED_UNICODE);
            exit;
        }
        
        // 获取相册中的照片
        $stmt = $pdo->prepare("SELECT * FROM photos WHERE album_id = ? AND user_id = ? AND is_deleted = false ORDER BY taken_at DESC, created_at DESC");
        $stmt->execute([$albumId, $userId]);
        $photos = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
        echo json_encode([
            'status' => 'success',
            'photos' => $photos
        ], JSON_UNESCAPED_UNICODE);
        exit;
    }
    
// 上传照片到相册
    if (preg_match('/^\/api\/albums\/([0-9]+)\/photos$/', $path, $matches) && $method === 'POST') {
        $albumId = $matches[1];
        
        // 检查相册是否属于当前用户
        $stmt = $pdo->prepare("SELECT id FROM albums WHERE id = ? AND user_id = ?");
        $stmt->execute([$albumId, $userId]);
        if (!$stmt->fetch(PDO::FETCH_ASSOC)) {
            echo json_encode([
                'status' => 'error',
                'message' => '相册不存在或无权限访问'
            ], JSON_UNESCAPED_UNICODE);
            exit;
        }
        
        // 确保照片目录存在
        $photosDir = __DIR__ . '/../Photos';
        $userPhotosDir = $photosDir . '/' . $userId;
        $thumbnailDir = $photosDir . '/thumbnail/' . $userId;
        
        if (!file_exists($photosDir)) {
            mkdir($photosDir, 0755, true);
            chown($photosDir, 'nginx');
            chgrp($photosDir, 'nginx');
        }
        
        if (!file_exists($userPhotosDir)) {
            mkdir($userPhotosDir, 0755, true);
            chown($userPhotosDir, 'nginx');
            chgrp($userPhotosDir, 'nginx');
        }
        
        // 确保缩略图目录存在
        if (!file_exists($thumbnailDir)) {
            mkdir($thumbnailDir, 0755, true);
            chown($thumbnailDir, 'nginx');
            chgrp($thumbnailDir, 'nginx');
        }
        
        // 处理文件上传
        $uploadedPhotos = [];
        $errorOccurred = false;
        $errorMessage = '';
        
        // 处理不同的文件字段名：支持单文件和多文件上传
        // 单文件上传使用 'file'，多文件上传使用 'files' 或 'files[]'
        $hasSingleFile = isset($_FILES['file']);
        $hasMultiFiles = isset($_FILES['files']) || isset($_FILES['files[]']);
        
        if (!$hasSingleFile && !$hasMultiFiles) {
            echo json_encode([
                'status' => 'error',
                'message' => '请选择要上传的文件或文件大小超过限制',
                'detail' => 'PHP上传限制：' . ini_get('post_max_size')
            ], JSON_UNESCAPED_UNICODE);
            exit;
        }
        
        // 处理单文件上传
        if ($hasSingleFile) {
            $file = $_FILES['file'];
            
            // 确保file是数组
            if (!is_array($file)) {
                echo json_encode([
                    'status' => 'error',
                    'message' => '文件格式错误'
                ], JSON_UNESCAPED_UNICODE);
                exit;
            }
            
            // 检查文件上传错误
            if (!isset($file['error']) || $file['error'] !== UPLOAD_ERR_OK) {
                echo json_encode([
                    'status' => 'error',
                    'message' => '文件上传失败: ' . ($file['error'] ?? '未知错误')
                ], JSON_UNESCAPED_UNICODE);
                exit;
            }
            
            // 生成唯一文件名
            $extension = pathinfo($file['name'], PATHINFO_EXTENSION);
            $filename = uniqid() . '.' . $extension;
            
            // 移动文件到用户目录
            $destination = $userPhotosDir . '/' . $filename;
            if (move_uploaded_file($file['tmp_name'], $destination)) {
                // 生成缩略图
                $thumbnailFilename = $filename;
                $thumbnailPath = $thumbnailDir . '/' . $thumbnailFilename;
                $thumbnailUrl = '/Photos/thumbnail/' . $userId . '/' . $thumbnailFilename;
                
                // 调用缩略图生成函数
                generateThumbnail($destination, $thumbnailPath);
                
                // 提取照片拍摄时间
                $taken_at = extractPhotoTakenDate($destination);
                
                // 准备照片信息
                $photoInfo = [
                    'user_id' => $userId,
                    'album_id' => $albumId,
                    'filename' => $filename,
                    'original_name' => $file['name'],
                    'size' => $file['size'],
                    'type' => $file['type'],
                    'url' => '/Photos/' . $userId . '/' . $filename,
                    'thumbnail_url' => $thumbnailUrl,
                    'is_favorite' => 0, // 使用整数0表示false
                    'is_deleted' => 0, // 使用整数0表示false
                    'taken_at' => $taken_at
                ];
                
                // 保存到数据库
                $stmt = $pdo->prepare("INSERT INTO photos (user_id, album_id, filename, original_name, size, type, url, thumbnail_url, is_favorite, is_deleted, taken_at) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)");
                if ($stmt->execute([
                    $photoInfo['user_id'],
                    $photoInfo['album_id'],
                    $photoInfo['filename'],
                    $photoInfo['original_name'],
                    $photoInfo['size'],
                    $photoInfo['type'],
                    $photoInfo['url'],
                    $photoInfo['thumbnail_url'],
                    $photoInfo['is_favorite'],
                    $photoInfo['is_deleted'],
                    $photoInfo['taken_at']
                ])) {
                    $photoId = $pdo->lastInsertId();
                    $photoInfo['id'] = $photoId;
                    $photoInfo['created_at'] = date('Y-m-d H:i:s');
                    $photoInfo['updated_at'] = date('Y-m-d H:i:s');
                    $photoInfo['taken_at'] = $taken_at;
                    
                    $uploadedPhotos[] = $photoInfo;
                } else {
                    // 删除已上传的文件
                    unlink($destination);
                    $errorOccurred = true;
                    $errorMessage = '照片信息保存失败';
                }
            } else {
                $errorOccurred = true;
                $errorMessage = '文件移动失败';
            }
        } else {
            // 处理多文件上传
            $files = $_FILES['files'] ?? $_FILES['files[]'] ?? [];
            
            // 确保$files是数组
            if (!is_array($files)) {
                $files = [];
            }
            
            // 处理单文件上传的情况
            if (isset($files['name']) && is_string($files['name'])) {
                $files = [
                    'name' => [$files['name']],
                    'type' => [$files['type']],
                    'tmp_name' => [$files['tmp_name']],
                    'error' => [$files['error']],
                    'size' => [$files['size']]
                ];
            }
            
            // 确保有文件上传
            if (empty($files['name'])) {
                echo json_encode([
                    'status' => 'error',
                    'message' => '请选择要上传的文件或文件大小超过限制',
                    'detail' => 'PHP上传限制：' . ini_get('post_max_size')
                ], JSON_UNESCAPED_UNICODE);
                exit;
            }
            
            // 遍历所有上传的文件
            for ($i = 0; $i < count($files['name']); $i++) {
                if (!isset($files['error'][$i]) || $files['error'][$i] !== UPLOAD_ERR_OK) {
                    $errorOccurred = true;
                    $errorMessage = '部分文件上传失败';
                    continue;
                }
                
                $file = [
                    'name' => $files['name'][$i],
                    'type' => $files['type'][$i],
                    'tmp_name' => $files['tmp_name'][$i],
                    'error' => $files['error'][$i],
                    'size' => $files['size'][$i]
                ];
                
                // 生成唯一文件名
                $extension = pathinfo($file['name'], PATHINFO_EXTENSION);
                $filename = uniqid() . '.' . $extension;
                
                // 移动文件到用户目录
                $destination = $userPhotosDir . '/' . $filename;
                if (move_uploaded_file($file['tmp_name'], $destination)) {
                    // 生成缩略图
                    $thumbnailFilename = $filename;
                    $thumbnailPath = $thumbnailDir . '/' . $thumbnailFilename;
                    $thumbnailUrl = '/Photos/thumbnail/' . $userId . '/' . $thumbnailFilename;
                    
                    // 调用缩略图生成函数
                    generateThumbnail($destination, $thumbnailPath);
                    
                    // 提取照片拍摄时间
                    $taken_at = extractPhotoTakenDate($destination);
                    
                    // 准备照片信息
                    $photoInfo = [
                        'user_id' => $userId,
                        'album_id' => $albumId,
                        'filename' => $filename,
                        'original_name' => $file['name'],
                        'size' => $file['size'],
                        'type' => $file['type'],
                        'url' => '/Photos/' . $userId . '/' . $filename,
                        'thumbnail_url' => $thumbnailUrl,
                        'is_favorite' => 0, // 使用整数0表示false
                        'is_deleted' => 0, // 使用整数0表示false
                        'taken_at' => $taken_at
                    ];
                    
                    // 保存到数据库
                    $stmt = $pdo->prepare("INSERT INTO photos (user_id, album_id, filename, original_name, size, type, url, thumbnail_url, is_favorite, is_deleted, taken_at) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)");
                    if ($stmt->execute([
                        $photoInfo['user_id'],
                        $photoInfo['album_id'],
                        $photoInfo['filename'],
                        $photoInfo['original_name'],
                        $photoInfo['size'],
                        $photoInfo['type'],
                        $photoInfo['url'],
                        $photoInfo['thumbnail_url'],
                        $photoInfo['is_favorite'],
                        $photoInfo['is_deleted'],
                        $photoInfo['taken_at']
                    ])) {
                        $photoId = $pdo->lastInsertId();
                        $photoInfo['id'] = $photoId;
                        $photoInfo['created_at'] = date('Y-m-d H:i:s');
                        $photoInfo['updated_at'] = date('Y-m-d H:i:s');
                        $photoInfo['taken_at'] = $taken_at;
                        
                        $uploadedPhotos[] = $photoInfo;
                    } else {
                        // 删除已上传的文件
                        unlink($destination);
                        $errorOccurred = true;
                        $errorMessage = '部分照片信息保存失败';
                    }
                } else {
                    $errorOccurred = true;
                    $errorMessage = '部分文件移动失败';
                }
            }
        }
        
        // 返回响应
        if ($errorOccurred && empty($uploadedPhotos)) {
            // 所有文件都上传失败
            echo json_encode([
                'status' => 'error',
                'message' => $errorMessage,
                'uploaded_count' => 0,
                'photos' => []
            ], JSON_UNESCAPED_UNICODE);
        } else {
            // 部分或全部文件上传成功
            echo json_encode([
                'status' => $errorOccurred ? 'warning' : 'success',
                'message' => $errorOccurred ? $errorMessage : '照片上传成功',
                'uploaded_count' => count($uploadedPhotos),
                'photos' => $uploadedPhotos
            ], JSON_UNESCAPED_UNICODE);
        }
        exit;
    }
    
    // 删除相册
    if (preg_match('/^\/api\/albums\/([0-9]+)$/', $path, $matches) && $method === 'DELETE') {
        $albumId = $matches[1];
        
        // 检查相册是否属于当前用户
        $stmt = $pdo->prepare("SELECT id FROM albums WHERE id = ? AND user_id = ?");
        $stmt->execute([$albumId, $userId]);
        if (!$stmt->fetch(PDO::FETCH_ASSOC)) {
            echo json_encode([
                'status' => 'error',
                'message' => '相册不存在或无权限访问'
            ], JSON_UNESCAPED_UNICODE);
            exit;
        }
        
        // 获取相册中的所有照片
        $stmt = $pdo->prepare("SELECT * FROM photos WHERE album_id = ? AND user_id = ?");
        $stmt->execute([$albumId, $userId]);
        $photos = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
        // 删除照片文件
        foreach ($photos as $photo) {
            $filePath = __DIR__ . '/../' . substr($photo['url'], 1);
            if (file_exists($filePath)) {
                unlink($filePath);
            }
        }
        
        // 开始事务
        $pdo->beginTransaction();
        
        try {
            // 删除相册中的所有照片
            $stmt = $pdo->prepare("DELETE FROM photos WHERE album_id = ? AND user_id = ?");
            $stmt->execute([$albumId, $userId]);
            
            // 删除相册
            $stmt = $pdo->prepare("DELETE FROM albums WHERE id = ? AND user_id = ?");
            $stmt->execute([$albumId, $userId]);
            
            // 提交事务
            $pdo->commit();
            
            echo json_encode([
                'status' => 'success',
                'message' => '相册删除成功'
            ], JSON_UNESCAPED_UNICODE);
        } catch (Exception $e) {
            // 回滚事务
            $pdo->rollBack();
            
            echo json_encode([
                'status' => 'error',
                'message' => '相册删除失败: ' . $e->getMessage()
            ], JSON_UNESCAPED_UNICODE);
        }
        
        exit;
    }
}

// 分享相关路由
if (strpos($path, '/api/shares') === 0) {
    // 生成随机分享码的函数
    function generateShareCode($length = 8) {
        $characters = '0123456789abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ';
        $code = '';
        for ($i = 0; $i < $length; $i++) {
            $code .= $characters[rand(0, strlen($characters) - 1)];
        }
        return $code;
    }
    
    // 获取分享列表
    if ($path === '/api/shares' && $method === 'GET') {
        // 验证JWT token
        $authHeader = $_SERVER['HTTP_AUTHORIZATION'] ?? '';
        if (empty($authHeader) || substr($authHeader, 0, 7) !== 'Bearer ') {
            http_response_code(401);
            echo json_encode([
                'status' => 'error',
                'message' => '未授权'
            ], JSON_UNESCAPED_UNICODE);
            exit;
        }
        
        $token = substr($authHeader, 7);
        $tokenParts = explode('.', $token);
        $userId = null;
        
        if (count($tokenParts) === 3) {
            $payload = json_decode(base64_decode($tokenParts[1]), true);
            $signature = $tokenParts[2];
            
            // 验证token签名
            $secretKey = 'your-secret-key-change-this-in-production';
            $expectedSignature = str_replace(['+', '/', '='], ['-', '_', ''], base64_encode(hash_hmac('sha256', $tokenParts[0] . '.' . $tokenParts[1], $secretKey, true)));
            
            if ($signature === $expectedSignature) {
                $userId = $payload['sub'] ?? null;
            }
        }
        
        if (!$userId) {
            http_response_code(401);
            echo json_encode([
                'status' => 'error',
                'message' => '无效的token'
            ], JSON_UNESCAPED_UNICODE);
            exit;
        }
        
        // 获取当前用户的分享列表
        $stmt = $pdo->prepare("SELECT shares.*, photos.original_name as photo_name, albums.name as album_name FROM shares 
            LEFT JOIN photos ON shares.photo_id = photos.id 
            LEFT JOIN albums ON shares.album_id = albums.id 
            WHERE shares.user_id = ? 
            ORDER BY shares.created_at DESC");
        $stmt->execute([$userId]);
        $shares = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
        echo json_encode([
            'status' => 'success',
            'shares' => $shares
        ], JSON_UNESCAPED_UNICODE);
        exit;
    }
    
    // 创建分享
    if ($path === '/api/shares' && $method === 'POST') {
        // 验证JWT token
        $authHeader = $_SERVER['HTTP_AUTHORIZATION'] ?? '';
        if (empty($authHeader) || substr($authHeader, 0, 7) !== 'Bearer ') {
            http_response_code(401);
            echo json_encode([
                'status' => 'error',
                'message' => '未授权'
            ], JSON_UNESCAPED_UNICODE);
            exit;
        }
        
        $token = substr($authHeader, 7);
        $tokenParts = explode('.', $token);
        $userId = null;
        
        if (count($tokenParts) === 3) {
            $payload = json_decode(base64_decode($tokenParts[1]), true);
            $signature = $tokenParts[2];
            
            // 验证token签名
            $secretKey = 'your-secret-key-change-this-in-production';
            $expectedSignature = str_replace(['+', '/', '='], ['-', '_', ''], base64_encode(hash_hmac('sha256', $tokenParts[0] . '.' . $tokenParts[1], $secretKey, true)));
            
            if ($signature === $expectedSignature) {
                $userId = $payload['sub'] ?? null;
            }
        }
        
        if (!$userId) {
            http_response_code(401);
            echo json_encode([
                'status' => 'error',
                'message' => '无效的token'
            ], JSON_UNESCAPED_UNICODE);
            exit;
        }
        
        $photoId = $requestBody['photo_id'] ?? null;
        $albumId = $requestBody['album_id'] ?? null;
        $expiresAt = $requestBody['expires_at'] ?? null;
        
        // 验证参数
        if (!$photoId && !$albumId) {
            echo json_encode([
                'status' => 'error',
                'message' => '请选择要分享的照片或相册'
            ], JSON_UNESCAPED_UNICODE);
            exit;
        }
        
        // 生成分享码
        $shareCode = generateShareCode();
        
        // 验证分享的资源是否属于当前用户
        if ($photoId) {
            $stmt = $pdo->prepare("SELECT id FROM photos WHERE id = ? AND user_id = ? AND is_deleted = false");
            $stmt->execute([$photoId, $userId]);
            if (!$stmt->fetch(PDO::FETCH_ASSOC)) {
                echo json_encode([
                    'status' => 'error',
                    'message' => '照片不存在或无权限访问'
                ], JSON_UNESCAPED_UNICODE);
                exit;
            }
        } elseif ($albumId) {
            $stmt = $pdo->prepare("SELECT id FROM albums WHERE id = ? AND user_id = ?");
            $stmt->execute([$albumId, $userId]);
            if (!$stmt->fetch(PDO::FETCH_ASSOC)) {
                echo json_encode([
                    'status' => 'error',
                    'message' => '相册不存在或无权限访问'
                ], JSON_UNESCAPED_UNICODE);
                exit;
            }
        }
        
        // 保存分享记录
        $stmt = $pdo->prepare("INSERT INTO shares (user_id, photo_id, album_id, share_code, expires_at) VALUES (?, ?, ?, ?, ?)");
        if ($stmt->execute([$userId, $photoId, $albumId, $shareCode, $expiresAt])) {
            $shareId = $pdo->lastInsertId();
            
            // 获取分享信息
            $stmt = $pdo->prepare("SELECT shares.*, photos.original_name as photo_name, albums.name as album_name FROM shares 
                LEFT JOIN photos ON shares.photo_id = photos.id 
                LEFT JOIN albums ON shares.album_id = albums.id 
                WHERE shares.id = ?");
            $stmt->execute([$shareId]);
            $share = $stmt->fetch(PDO::FETCH_ASSOC);
            
            echo json_encode([
                'status' => 'success',
                'message' => '分享创建成功',
                'share' => $share
            ], JSON_UNESCAPED_UNICODE);
        } else {
            echo json_encode([
                'status' => 'error',
                'message' => '分享创建失败'
            ], JSON_UNESCAPED_UNICODE);
        }
        exit;
    }
    
    // 通过分享码获取分享内容
    if (preg_match('/^\/api\/shares\/([a-zA-Z0-9]+)$/', $path, $matches) && $method === 'GET') {
        $shareCode = $matches[1];
        
        // 获取分享信息
        $stmt = $pdo->prepare("SELECT shares.*, photos.original_name as photo_name, albums.name as album_name FROM shares 
            LEFT JOIN photos ON shares.photo_id = photos.id 
            LEFT JOIN albums ON shares.album_id = albums.id 
            WHERE shares.share_code = ?");
        $stmt->execute([$shareCode]);
        $share = $stmt->fetch(PDO::FETCH_ASSOC);
        
        if (!$share) {
            echo json_encode([
                'status' => 'error',
                'message' => '分享链接不存在或已失效'
            ], JSON_UNESCAPED_UNICODE);
            exit;
        }
        
        // 检查分享是否过期
        if ($share['expires_at'] && $share['expires_at'] < date('Y-m-d H:i:s')) {
            echo json_encode([
                'status' => 'error',
                'message' => '分享链接已过期'
            ], JSON_UNESCAPED_UNICODE);
            exit;
        }
        
        // 获取分享的内容
        $shareContent = [];
        if ($share['photo_id']) {
            // 分享的是单张照片
            $stmt = $pdo->prepare("SELECT * FROM photos WHERE id = ? AND is_deleted = false");
            $stmt->execute([$share['photo_id']]);
            $photo = $stmt->fetch(PDO::FETCH_ASSOC);
            $shareContent['photo'] = $photo;
        } elseif ($share['album_id']) {
            // 分享的是相册
            $stmt = $pdo->prepare("SELECT * FROM photos WHERE album_id = ? AND is_deleted = false ORDER BY taken_at DESC, created_at DESC");
            $stmt->execute([$share['album_id']]);
            $photos = $stmt->fetchAll(PDO::FETCH_ASSOC);
            $shareContent['album'] = [
                'id' => $share['album_id'],
                'name' => $share['album_name'],
                'photos' => $photos
            ];
        }
        
        echo json_encode([
            'status' => 'success',
            'share' => $share,
            'content' => $shareContent
        ], JSON_UNESCAPED_UNICODE);
        exit;
    }
    
    // 删除分享
    if (preg_match('/^\/api\/shares\/([0-9]+)$/', $path, $matches) && $method === 'DELETE') {
        $shareId = $matches[1];
        
        // 验证JWT token
        $authHeader = $_SERVER['HTTP_AUTHORIZATION'] ?? '';
        if (empty($authHeader) || !str_starts_with($authHeader, 'Bearer ')) {
            echo json_encode([
                'status' => 'error',
                'message' => '未授权'
            ], JSON_UNESCAPED_UNICODE);
            exit;
        }
        
        $token = substr($authHeader, 7);
        $tokenParts = explode('.', $token);
        $userId = null;
        
        if (count($tokenParts) === 3) {
            $payload = json_decode(base64_decode($tokenParts[1]), true);
            $signature = $tokenParts[2];
            
            // 验证token签名
            $secretKey = 'your-secret-key-change-this-in-production';
            $expectedSignature = str_replace(['+', '/', '='], ['-', '_', ''], base64_encode(hash_hmac('sha256', $tokenParts[0] . '.' . $tokenParts[1], $secretKey, true)));
            
            if ($signature === $expectedSignature) {
                $userId = $payload['sub'] ?? null;
            }
        }
        
        if (!$userId) {
            echo json_encode([
                'status' => 'error',
                'message' => '无效的token'
            ], JSON_UNESCAPED_UNICODE);
            exit;
        }
        
        // 验证分享是否属于当前用户
        $stmt = $pdo->prepare("SELECT id FROM shares WHERE id = ? AND user_id = ?");
        $stmt->execute([$shareId, $userId]);
        if (!$stmt->fetch(PDO::FETCH_ASSOC)) {
            echo json_encode([
                'status' => 'error',
                'message' => '分享不存在或无权限访问'
            ], JSON_UNESCAPED_UNICODE);
            exit;
        }
        
        // 删除分享
        $stmt = $pdo->prepare("DELETE FROM shares WHERE id = ? AND user_id = ?");
        if ($stmt->execute([$shareId, $userId])) {
            echo json_encode([
                'status' => 'success',
                'message' => '分享已删除'
            ], JSON_UNESCAPED_UNICODE);
        } else {
            echo json_encode([
                'status' => 'error',
                'message' => '分享删除失败'
            ], JSON_UNESCAPED_UNICODE);
        }
        exit;
    }
}

// 批量下载照片
if ($path === '/api/photos/download' && $method === 'POST') {
    // 验证JWT token
    $authHeader = $_SERVER['HTTP_AUTHORIZATION'] ?? '';
    $token = substr($authHeader, 7);
    $tokenParts = explode('.', $token);
    $userId = null;
    
    if (count($tokenParts) === 3) {
        $payload = json_decode(base64_decode($tokenParts[1]), true);
        $signature = $tokenParts[2];
        
        // 验证token签名
        $secretKey = 'your-secret-key-change-this-in-production';
        $expectedSignature = str_replace(['+', '/', '='], ['-', '_', ''], base64_encode(hash_hmac('sha256', $tokenParts[0] . '.' . $tokenParts[1], $secretKey, true)));
        
        if ($signature === $expectedSignature) {
            $userId = $payload['sub'] ?? null;
        }
    }
    
    if (!$userId) {
        echo json_encode([
            'status' => 'error',
            'message' => '无效的token',
            'detail' => 'JWT token验证失败'
        ], JSON_UNESCAPED_UNICODE);
        exit;
    }
    
    // 获取请求中的照片ID列表
    $photoIds = $requestBody['photo_ids'] ?? [];
    
    if (empty($photoIds)) {
        echo json_encode([
            'status' => 'error',
            'message' => '请选择要下载的照片'
        ], JSON_UNESCAPED_UNICODE);
        exit;
    }
    
    // 获取照片信息
    $placeholders = implode(',', array_fill(0, count($photoIds), '?'));
    $stmt = $pdo->prepare("SELECT * FROM photos WHERE id IN ($placeholders) AND user_id = ? AND is_deleted = false");
    $stmt->execute(array_merge($photoIds, [$userId]));
    $photos = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    if (empty($photos)) {
        echo json_encode([
            'status' => 'error',
            'message' => '未找到要下载的照片'
        ], JSON_UNESCAPED_UNICODE);
        exit;
    }
    
    // 创建临时目录用于存放ZIP文件
    $tempDir = __DIR__ . '/../temp';
    if (!file_exists($tempDir)) {
        mkdir($tempDir, 0755, true);
    }
    
    // 生成唯一的ZIP文件名
    $zipFileName = 'photos_' . date('YmdHis') . '_' . uniqid() . '.zip';
    $zipFilePath = $tempDir . '/' . $zipFileName;
    
    // 创建ZIP文件
    $zip = new ZipArchive();
    if ($zip->open($zipFilePath, ZipArchive::CREATE | ZipArchive::OVERWRITE) !== true) {
        echo json_encode([
            'status' => 'error',
            'message' => 'ZIP文件创建失败'
        ], JSON_UNESCAPED_UNICODE);
        exit;
    }
    
    // 添加照片到ZIP文件
    foreach ($photos as $photo) {
        // 获取照片文件的实际路径
        $photoFilePath = __DIR__ . '/../' . ltrim($photo['url'], '/');
        
        // 检查文件是否存在
        if (file_exists($photoFilePath)) {
            // 添加文件到ZIP，使用原始文件名
            $zip->addFile($photoFilePath, $photo['original_name']);
        }
    }
    
    // 关闭ZIP文件
    $zip->close();
    
    // 检查ZIP文件是否创建成功
    if (!file_exists($zipFilePath)) {
        echo json_encode([
            'status' => 'error',
            'message' => 'ZIP文件创建失败'
        ], JSON_UNESCAPED_UNICODE);
        exit;
    }
    
    // 返回ZIP文件的下载链接
    echo json_encode([
        'status' => 'success',
        'message' => '照片打包成功',
        'download_url' => '/temp/' . $zipFileName
    ], JSON_UNESCAPED_UNICODE);
    exit;
}

// 转存照片到用户相册
if ($path === '/api/photos/transfer' && $method === 'POST') {
    // 验证JWT token
    $authHeader = $_SERVER['HTTP_AUTHORIZATION'] ?? '';
    if (empty($authHeader) || !str_starts_with($authHeader, 'Bearer ')) {
        echo json_encode([
            'status' => 'error',
            'message' => '未授权'
        ], JSON_UNESCAPED_UNICODE);
        exit;
    }
    
    $token = substr($authHeader, 7);
    $tokenParts = explode('.', $token);
    $userId = null;
    
    if (count($tokenParts) === 3) {
        $payload = json_decode(base64_decode($tokenParts[1]), true);
        $signature = $tokenParts[2];
        
        // 验证token签名
        $secretKey = 'your-secret-key-change-this-in-production';
        $expectedSignature = str_replace(['+', '/', '='], ['-', '_', ''], base64_encode(hash_hmac('sha256', $tokenParts[0] . '.' . $tokenParts[1], $secretKey, true)));
        
        if ($signature === $expectedSignature) {
            $userId = $payload['sub'] ?? null;
        }
    }
    
    if (!$userId) {
        echo json_encode([
            'status' => 'error',
            'message' => '无效的token',
            'detail' => 'JWT token验证失败'
        ], JSON_UNESCAPED_UNICODE);
        exit;
    }
    
    // 获取请求参数
    $albumId = $requestBody['album_id'] ?? null;
    $photosToTransfer = $requestBody['photos'] ?? [];
    
    if (!$albumId || empty($photosToTransfer)) {
        echo json_encode([
            'status' => 'error',
            'message' => '缺少必要的参数'
        ], JSON_UNESCAPED_UNICODE);
        exit;
    }
    
    // 验证相册是否属于当前用户
    $stmt = $pdo->prepare("SELECT id FROM albums WHERE id = ? AND user_id = ?");
    $stmt->execute([$albumId, $userId]);
    if (!$stmt->fetch(PDO::FETCH_ASSOC)) {
        echo json_encode([
            'status' => 'error',
            'message' => '相册不存在或无权限访问'
        ], JSON_UNESCAPED_UNICODE);
        exit;
    }
    
    // 确保照片目录存在
    $photosDir = __DIR__ . '/../Photos';
    $userPhotosDir = $photosDir . '/' . $userId;
    $thumbnailDir = $photosDir . '/thumbnail/' . $userId;
    
    if (!file_exists($userPhotosDir)) {
        mkdir($userPhotosDir, 0755, true);
        chown($userPhotosDir, 'nginx');
        chgrp($userPhotosDir, 'nginx');
    }
    
    if (!file_exists($thumbnailDir)) {
        mkdir($thumbnailDir, 0755, true);
        chown($thumbnailDir, 'nginx');
        chgrp($thumbnailDir, 'nginx');
    }
    
    // 转存照片
        $transferredCount = 0;
        $totalCount = count($photosToTransfer);
        
        // 详细日志记录
        error_log("=== Photo Transfer Start ===");
        error_log("User ID: $userId");
        error_log("Album ID: $albumId");
        error_log("Total photos to transfer: $totalCount");
        
        foreach ($photosToTransfer as $index => $sourcePhoto) {
            error_log("\n--- Processing photo $index/$totalCount ---");
            
            try {
                // 获取原始照片文件信息
                $sourceUrl = $sourcePhoto['url'] ?? '';
                error_log("Source URL: $sourceUrl");
                
                if (empty($sourceUrl)) {
                    error_log("Skipping: Empty source URL");
                    continue;
                }
                
                // 将URL转换为服务器本地文件路径
                $serverRoot = __DIR__ . '/../';
                $sourceFilePath = $serverRoot . ltrim($sourceUrl, '/');
                error_log("Source file path: $sourceFilePath");
                
                // 检查源文件是否存在
                if (!file_exists($sourceFilePath)) {
                    error_log('Photo transfer: Source file not found - ' . $sourceFilePath);
                    continue;
                }
                
                // 获取原始文件名和扩展名
                $originalName = $sourcePhoto['original_name'] ?? 'photo.jpg';
                $extension = pathinfo($originalName, PATHINFO_EXTENSION);
                error_log("Original name: $originalName, Extension: $extension");
                
                // 生成唯一文件名
                $filename = uniqid() . '.' . $extension;
                error_log("Generated filename: $filename");
                
                // 直接复制文件到用户目录
                $destinationPath = $userPhotosDir . '/' . $filename;
                error_log("Destination path: $destinationPath");
                
                // 确保目标目录存在且有写入权限
                if (!is_dir($userPhotosDir)) {
                    error_log("Creating user photos directory: $userPhotosDir");
                    mkdir($userPhotosDir, 0755, true);
                    chown($userPhotosDir, 'nginx');
                    chgrp($userPhotosDir, 'nginx');
                }
                
                // 复制文件
                if (!copy($sourceFilePath, $destinationPath)) {
                    error_log('Photo transfer: Failed to copy file - ' . $sourceFilePath . ' to ' . $destinationPath);
                    error_log('Error details: ' . error_get_last()['message'] ?? 'Unknown error');
                    continue;
                }
                error_log("File copied successfully");
                
                // 设置文件权限
                chmod($destinationPath, 0644);
                chown($destinationPath, 'nginx');
                chgrp($destinationPath, 'nginx');
                
                // 生成缩略图
                $thumbnailFilename = $filename;
                $thumbnailPath = $thumbnailDir . '/' . $thumbnailFilename;
                error_log("Generating thumbnail: $thumbnailPath");
                
                // 确保缩略图目录存在
                if (!is_dir($thumbnailDir)) {
                    error_log("Creating thumbnail directory: $thumbnailDir");
                    mkdir($thumbnailDir, 0755, true);
                    chown($thumbnailDir, 'nginx');
                    chgrp($thumbnailDir, 'nginx');
                }
                
                generateThumbnail($destinationPath, $thumbnailPath);
                error_log("Thumbnail generated successfully");
                
                // 获取文件类型，兼容不同环境
                $fileType = '';
                if (function_exists('mime_content_type')) {
                    $fileType = mime_content_type($destinationPath);
                } else {
                    // 降级方案：根据文件扩展名猜测类型
                    $mimeTypes = [
                        'jpg' => 'image/jpeg',
                        'jpeg' => 'image/jpeg',
                        'png' => 'image/png',
                        'gif' => 'image/gif',
                        'webp' => 'image/webp',
                        'bmp' => 'image/bmp'
                    ];
                    $fileType = $mimeTypes[strtolower($extension)] ?? 'image/jpeg';
                }
                error_log("File type: $fileType");
                
                // 准备照片信息
                $photoInfo = [
                    'user_id' => $userId,
                    'album_id' => $albumId,
                    'filename' => $filename,
                    'original_name' => $originalName,
                    'size' => filesize($destinationPath),
                    'type' => $fileType,
                    'url' => '/Photos/' . $userId . '/' . $filename,
                    'thumbnail_url' => '/Photos/thumbnail/' . $userId . '/' . $thumbnailFilename,
                    'is_favorite' => 0,
                    'is_deleted' => 0,
                    'taken_at' => $sourcePhoto['taken_at'] ?? null
                ];
                
                // 保存到数据库
                error_log("Saving to database...");
                $stmt = $pdo->prepare("INSERT INTO photos (user_id, album_id, filename, original_name, size, type, url, thumbnail_url, is_favorite, is_deleted, taken_at) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)");
                
                $result = $stmt->execute([
                    $photoInfo['user_id'],
                    $photoInfo['album_id'],
                    $photoInfo['filename'],
                    $photoInfo['original_name'],
                    $photoInfo['size'],
                    $photoInfo['type'],
                    $photoInfo['url'],
                    $photoInfo['thumbnail_url'],
                    $photoInfo['is_favorite'],
                    $photoInfo['is_deleted'],
                    $photoInfo['taken_at']
                ]);
                
                if ($result) {
                    $transferredCount++;
                    error_log("Photo saved to database successfully");
                } else {
                    error_log("Failed to save photo to database");
                    error_log("Database error: " . implode(", ", $stmt->errorInfo()));
                }
                
            } catch (Exception $e) {
                // 忽略单个文件的转存错误，继续处理其他文件
                error_log('Error transferring photo: ' . $e->getMessage());
                error_log('Stack trace: ' . $e->getTraceAsString());
                continue;
            }
        }
        
        error_log("\n=== Photo Transfer End ===");
        error_log("Transferred: $transferredCount/$totalCount photos");
    
    echo json_encode([
        'status' => 'success',
        'message' => '照片转存成功',
        'transferred_count' => $transferredCount,
        'total_count' => count($photosToTransfer)
    ], JSON_UNESCAPED_UNICODE);
    exit;
}

// 分享页面批量下载照片
if (preg_match('/^\/api\/shares\/([a-zA-Z0-9]+)\/download$/', $path, $matches)) {
    // 处理GET请求，直接返回错误
    if ($method === 'GET') {
        echo json_encode([
            'status' => 'error',
            'message' => '该接口只支持POST请求'
        ], JSON_UNESCAPED_UNICODE);
        exit;
    }
    
    $shareCode = $matches[1];
    
    // 获取分享信息
    $stmt = $pdo->prepare("SELECT shares.* FROM shares WHERE shares.share_code = ?");
    $stmt->execute([$shareCode]);
    $share = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if (!$share) {
        echo json_encode([
            'status' => 'error',
            'message' => '分享链接不存在或已失效'
        ], JSON_UNESCAPED_UNICODE);
        exit;
    }
    
    // 检查分享是否过期
    if ($share['expires_at'] && $share['expires_at'] < date('Y-m-d H:i:s')) {
        echo json_encode([
            'status' => 'error',
            'message' => '分享链接已过期'
        ], JSON_UNESCAPED_UNICODE);
        exit;
    }
    
    // 获取请求中的照片ID列表
    $photoIds = $requestBody['photo_ids'] ?? [];
    
    if (empty($photoIds)) {
        echo json_encode([
            'status' => 'error',
            'message' => '请选择要下载的照片'
        ], JSON_UNESCAPED_UNICODE);
        exit;
    }
    
    // 获取照片信息
    $placeholders = implode(',', array_fill(0, count($photoIds), '?'));
    $stmt = $pdo->prepare("SELECT * FROM photos WHERE id IN ($placeholders) AND is_deleted = false");
    $stmt->execute($photoIds);
    $photos = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    if (empty($photos)) {
        echo json_encode([
            'status' => 'error',
            'message' => '未找到要下载的照片'
        ], JSON_UNESCAPED_UNICODE);
        exit;
    }
    
    // 检查ZipArchive是否可用
    if (!class_exists('ZipArchive')) {
        // 如果ZipArchive不可用，直接返回照片信息，让前端处理
        echo json_encode([
            'status' => 'success',
            'message' => '照片信息获取成功',
            'photos' => $photos
        ], JSON_UNESCAPED_UNICODE);
        exit;
    }
    
    // 创建临时目录用于存放ZIP文件
    $tempDir = __DIR__ . '/../temp';
    if (!file_exists($tempDir)) {
        mkdir($tempDir, 0755, true);
    }
    
    // 生成唯一的ZIP文件名
    $zipFileName = 'shared_photos_' . date('YmdHis') . '_' . uniqid() . '.zip';
    $zipFilePath = $tempDir . '/' . $zipFileName;
    
    // 创建ZIP文件
    $zip = new ZipArchive();
    if ($zip->open($zipFilePath, ZipArchive::CREATE) !== true) {
        echo json_encode([
            'status' => 'error',
            'message' => '无法创建ZIP文件'
        ], JSON_UNESCAPED_UNICODE);
        exit;
    }
    
    // 添加照片到ZIP文件
    foreach ($photos as $photo) {
        $filePath = __DIR__ . '/../' . substr($photo['url'], 1);
        if (file_exists($filePath)) {
            // 使用原始文件名作为ZIP中的文件名
            $zip->addFile($filePath, $photo['original_name']);
        }
    }
    
    // 关闭ZIP文件
    $zip->close();
    
    // 检查ZIP文件是否创建成功
    if (!file_exists($zipFilePath)) {
        echo json_encode([
            'status' => 'error',
            'message' => 'ZIP文件创建失败'
        ], JSON_UNESCAPED_UNICODE);
        exit;
    }
    
    // 返回ZIP文件的下载链接
    echo json_encode([
        'status' => 'success',
        'message' => '照片打包成功',
        'download_url' => '/temp/' . $zipFileName
    ], JSON_UNESCAPED_UNICODE);
    exit;
}

// 404处理
http_response_code(404);
echo json_encode([
    'status' => 'error',
    'message' => 'API endpoint not found',
    'path' => $path
], JSON_UNESCAPED_UNICODE);
