<?php

// 引入百度AI服务类
require_once __DIR__ . '/src/services/BaiduAIService.php';

// 配置API密钥
$config = [
    'baidu_ai' => [
        'api_key' => 'n46ZcByHtR6Gpm8qpTHvzI1j',
        'secret_key' => 'SAuqIouLxZfP2EAEcZnMxf65ICUKiyCS'
    ]
];

// 数据库连接配置
$dbConfig = [
    'host' => 'mysql',
    'port' => 3306,
    'dbname' => 'photodb',
    'user' => 'photouser',
    'password' => 'photopassword'
];

try {
    // 创建数据库连接
    $pdo = new PDO(
        "mysql:host={$dbConfig['host']};port={$dbConfig['port']};dbname={$dbConfig['dbname']};charset=utf8mb4",
        $dbConfig['user'],
        $dbConfig['password'],
        [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION]
    );
    
    // 创建百度AI服务实例
    $baiduAIService = new BaiduAIService($config['baidu_ai']);
    
    // 获取所有没有内容标签的照片
    $stmt = $pdo->query("SELECT id, filename, user_id FROM photos WHERE has_content_tags = 0");
    $photos = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    echo "找到 " . count($photos) . " 张照片需要添加标签\n";
    
    foreach ($photos as $photo) {
        $photoId = $photo['id'];
        $filename = $photo['filename'];
        $userId = $photo['user_id'];
        
        // 照片路径
        $photoPath = __DIR__ . "/Photos/{$userId}/{$filename}";
        
        echo "处理照片: {$filename} (ID: {$photoId})\n";
        echo "照片路径: {$photoPath}\n";
        
        if (file_exists($photoPath)) {
            // 调用图像分类API
            $tagsResult = $baiduAIService->imageClassify($photoPath);
            
            echo "分类结果: \n";
            print_r($tagsResult);
            
            if (!empty($tagsResult)) {
                // 更新has_content_tags字段
                $stmt = $pdo->prepare("UPDATE photos SET has_content_tags = 1 WHERE id = ?");
                $stmt->execute([$photoId]);
                echo "更新照片标签状态成功\n";
                
                // 保存标签到数据库 - 先去重，避免重复关联
                $uniqueTags = [];
                foreach ($tagsResult as $tagData) {
                    $tagName = $tagData['keyword'];
                    $uniqueTags[$tagName] = true; // 使用关联数组去重
                }
                
                // 遍历去重后的标签
                foreach (array_keys($uniqueTags) as $tagName) {
                    // 插入标签
                    $stmt = $pdo->prepare("INSERT IGNORE INTO photo_tags (name) VALUES (?)");
                    $stmt->execute([$tagName]);
                    echo "插入标签: {$tagName}\n";
                    
                    // 获取标签ID
                    $stmt = $pdo->prepare("SELECT id FROM photo_tags WHERE name = ?");
                    $stmt->execute([$tagName]);
                    $tagId = $stmt->fetchColumn();
                    
                    // 关联照片和标签
                    if ($tagId) {
                        $stmt = $pdo->prepare("INSERT IGNORE INTO photo_tag_relations (photo_id, tag_id) VALUES (?, ?)");
                        $stmt->execute([$photoId, $tagId]);
                        echo "关联照片ID: {$photoId} 和标签ID: {$tagId}\n";
                    }
                }
            }
        } else {
            echo "照片文件不存在\n";
        }
        
        echo "\n";
    }
    
    echo "标签更新完成\n";
} catch (Exception $e) {
    echo "错误: " . $e->getMessage() . "\n";
    echo $e->getTraceAsString() . "\n";
}
