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

try {
    // 创建百度AI服务实例
    $baiduAIService = new BaiduAIService($config['baidu_ai']);
    
    // 测试图像文件路径
    $testImagePath = __DIR__ . '/Photos/1/6975b333ad75e.jpeg';
    
    echo "测试图像路径: $testImagePath\n";
    echo "图像文件是否存在: " . (file_exists($testImagePath) ? "是" : "否") . "\n";
    
    if (file_exists($testImagePath)) {
        // 调用图像分类API
        $result = $baiduAIService->imageClassify($testImagePath);
        
        echo "分类结果: \n";
        print_r($result);
    }
} catch (Exception $e) {
    echo "错误: " . $e->getMessage() . "\n";
}
