<?php

class BaiduAIService {
    private $config;
    private $accessToken;
    
    public function __construct($config) {
        $this->config = $config;
        $this->accessToken = $this->getAccessToken();
    }
    
    /**
     * 获取百度AI访问令牌
     * @return string 访问令牌
     */
    private function getAccessToken() {
        $url = 'https://aip.baidubce.com/oauth/2.0/token';
        $params = [
            'grant_type' => 'client_credentials',
            'client_id' => $this->config['api_key'],
            'client_secret' => $this->config['secret_key']
        ];
        
        $response = $this->httpRequest($url, $params, 'GET');
        $result = json_decode($response, true);
        
        if (isset($result['access_token'])) {
            error_log('Baidu AI: Access token obtained successfully');
            return $result['access_token'];
        } else {
            error_log('Baidu AI: Failed to get access token: ' . json_encode($result));
            return '';
        }
    }
    
    /**
     * 调用图像分类API进行内容分类
     * @param string $imagePath 图片路径
     * @return array 识别结果数组
     */
    public function imageClassify($imagePath) {
        error_log('Baidu AI: Starting image classification for path: ' . $imagePath);
        // 使用通用图像分类API，而非主体检测API
        $url = 'https://aip.baidubce.com/rest/2.0/image-classify/v2/advanced_general?access_token=' . $this->accessToken;
        
        if (!file_exists($imagePath)) {
            error_log('Baidu AI: Image file not found - ' . $imagePath);
            return [];
        }
        
        error_log('Baidu AI: Image file exists, size: ' . filesize($imagePath) . ' bytes');
        $image = file_get_contents($imagePath);
        $base64Image = base64_encode($image);
        error_log('Baidu AI: Image read and base64 encoded, length: ' . strlen($base64Image));
        
        $params = [
            'image' => $base64Image
        ];
        
        error_log('Baidu AI: Sending API request to: ' . $url);
        $response = $this->httpRequest($url, $params, 'POST');
        error_log('Baidu AI: API response received, length: ' . strlen($response));
        
        $result = json_decode($response, true);
        error_log('Baidu AI: API response parsed: ' . json_encode($result));
        
        // 处理图像分类结果
        if (isset($result['result']) && is_array($result['result'])) {
            error_log('Baidu AI: Image classification successful, found ' . count($result['result']) . ' categories');
            $tags = [];
            $addedCategories = [];
            
            // 置信度阈值，只保留置信度大于 的结果
            $confidenceThreshold = 0.10;
            
            // 按置信度降序排序结果
            usort($result['result'], function($a, $b) {
                return $b['score'] - $a['score'];
            });
            
            // 遍历分类结果，生成标签
            foreach ($result['result'] as $item) {
                if (isset($item['keyword']) && isset($item['score'])) {
                    $keyword = $item['keyword'];
                    $score = $item['score'];
                    
                    // 只保留置信度高于阈值的结果
                    if ($score < $confidenceThreshold) {
                        continue;
                    }
                    
                    // 根据关键词进行分类
                    $category = $this->mapToCategory($keyword);
                    
                    // 跳过已添加的类别，确保每张照片只被分到一个最相关的类别
                    if (!in_array($category, $addedCategories)) {
                        $tags[] = ['keyword' => $category, 'score' => $score];
                        $addedCategories[] = $category;
                        
                        // 只保留最相关的一个类别
                        break;
                    }
                }
            }
            
            return $tags;
        } else {
            error_log('Baidu AI: Image classification failed: ' . json_encode($result));
            return [];
        }
    }
    
    /**
     * 将AI识别的关键词映射到分类标签
     * @param string $keyword AI识别的关键词
     * @return string 分类标签
     */
    private function mapToCategory($keyword) {
        $keyword = mb_strtolower($keyword);
        
        // 1. 优先识别人物相关
        $personKeywords = ['人', '人物', '人像', '人类', '人脸', '女孩', '男孩', '女士', '先生', '青年', '小孩', '儿童', '婴儿', '老人', '成人', '男性', '女性', '五官', '面部', '表情', '肖像', '合影', '人群', '帅哥', '美女', '情侣', '家庭', '团队', '集体', '明星', '名人'];
        foreach ($personKeywords as $key) {
            if (strpos($keyword, $key) !== false) {
                return '人物';
            }
        }
        
        // 2. 动物相关
        $animalKeywords = ['狗', '猫', '动物', '宠物', '猫科', '犬科', '鸟类', '鱼', '昆虫', '动物群', '哺乳动物', '爬行动物', '两栖动物', '海洋生物', '蝴蝶', '蜜蜂', '蚂蚁', '蜘蛛', '蛇', '蜥蜴', '青蛙', '乌龟', '兔子', '狐狸', '狼', '老虎', '狮子', '大象', '长颈鹿', '猴子', '熊猫', '熊', '斑马', '马', '牛', '羊', '猪', '鸡', '鸭', '鹅', '鸟', '鹦鹉', '鹰', '猫头鹰', '鲨鱼', '海豚', '鲸鱼', '螃蟹', '虾', '贝壳', '珊瑚', '水母', '章鱼', '海星', '恐龙', '鳄鱼', '河马', '犀牛', '骆驼', '鹿', '袋鼠', '考拉', '企鹅', '天鹅', '孔雀', '蜻蜓', '萤火虫'];
        foreach ($animalKeywords as $key) {
            if (strpos($keyword, $key) !== false) {
                return '动物';
            }
        }
        
        // 3. 截图相关
        $screenshotKeywords = ['截图', '屏幕截图', '截屏', '屏幕'];
        foreach ($screenshotKeywords as $key) {
            if (strpos($keyword, $key) !== false) {
                return '截图';
            }
        }
        
        // 4. 自然环境相关（植物、自然风景等）
        $natureKeywords = ['风景', '自然', '天空', '海洋', '海', '湖泊', '湖', '山', '山脉', '树', '森林', '植物', '花', '草', '草原', '沙漠', '雪山', '冰川', '草地', '花园', '公园', '自然景观', '自然风景', '自然风光', '自然环境', '自然生态', '自然保护区', '自然公园', '自然遗产', '自然奇观', '自然现象'];
        foreach ($natureKeywords as $key) {
            if (strpos($keyword, $key) !== false) {
                if (strpos($keyword, '花') !== false || strpos($keyword, '草') !== false || strpos($keyword, '植物') !== false || strpos($keyword, '花园') !== false || strpos($keyword, '草地') !== false || strpos($keyword, '树') !== false || strpos($keyword, '森林') !== false) {
                    return '植物';
                }
                return '自然风景';
            }
        }
        
        // 5. 其他
        return '其他';
    }
    
    /**
     * HTTP请求封装
     * @param string $url 请求URL
     * @param array $params 请求参数
     * @param string $method 请求方法
     * @return string 请求响应
     */
    private function httpRequest($url, $params, $method = 'POST') {
        $ch = curl_init();
        
        if ($method == 'POST') {
            curl_setopt($ch, CURLOPT_POST, 1);
            curl_setopt($ch, CURLOPT_POSTFIELDS, http_build_query($params));
        } else {
            $url .= '?' . http_build_query($params);
        }
        
        curl_setopt($ch, CURLOPT_URL, $url);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
        curl_setopt($ch, CURLOPT_HTTPHEADER, [
            'Content-Type: application/x-www-form-urlencoded',
            'Accept: application/json'
        ]);
        
        // 设置超时时间
        curl_setopt($ch, CURLOPT_TIMEOUT, 10);
        curl_setopt($ch, CURLOPT_CONNECTTIMEOUT, 5);
        
        $output = curl_exec($ch);
        
        if (curl_errno($ch)) {
            error_log('Baidu AI: HTTP request error - ' . curl_error($ch));
        }
        
        curl_close($ch);
        
        return $output;
    }
}
