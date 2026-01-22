#!/bin/bash

echo "=== 开始检测所有API ==="
echo "检测时间: $(date)"
echo "========================="

# 基础URL
BASE_URL="http://localhost:8080/api"

# 测试结果统计
TOTAL_TESTS=0
PASSED_TESTS=0
FAILED_TESTS=0

# 颜色定义
GREEN="\033[0;32m"
RED="\033[0;31m"
YELLOW="\033[1;33m"
NC="\033[0m" # No Color

# 测试函数
test_api() {
    local method=$1
    local endpoint=$2
    local name=$3
    local headers=$4
    local data=$5
    local expected_status=$6
    
    ((TOTAL_TESTS++))
    
    echo -n "测试 $name... "
    
    # 构建curl命令
    local curl_cmd="curl -s -o /dev/null -w '%{http_code}' -X $method $BASE_URL$endpoint"
    
    # 添加 headers
    if [ -n "$headers" ]; then
        curl_cmd+=" $headers"
    fi
    
    # 添加 data
    if [ -n "$data" ]; then
        curl_cmd+=" -d '$data'"
    fi
    
    # 执行测试
    local status=$(eval $curl_cmd)
    
    # 检查结果
    if [ "$status" == "$expected_status" ]; then
        echo -e "${GREEN}✓ 成功${NC} (HTTP $status)"
        ((PASSED_TESTS++))
        return 0
    else
        echo -e "${RED}✗ 失败${NC} (预期: $expected_status, 实际: $status)"
        ((FAILED_TESTS++))
        return 1
    fi
}

# 1. 健康检查
echo "\n=== 健康检查 ==="
test_api "GET" "/health" "健康检查" "" "" "200"

# 2. 认证相关API
echo "\n=== 认证相关API ==="
test_api "POST" "/auth/login" "登录接口" "" '{"username":"test","password":"test"}' "401"
test_api "POST" "/auth/register" "注册接口" "" '{"username":"testuser","email":"test@example.com","password":"password123"}' "200"
test_api "GET" "/auth/me" "获取当前用户" "-H 'Authorization: Bearer invalid-token'" "" "401"

# 3. 照片相关API
echo "\n=== 照片相关API ==="
test_api "GET" "/photos" "获取照片列表" "-H 'Authorization: Bearer invalid-token'" "" "401"
test_api "POST" "/photos" "上传照片" "-H 'Authorization: Bearer invalid-token' -H 'Content-Type: multipart/form-data'" "" "401"
test_api "GET" "/photos/1" "获取单张照片" "-H 'Authorization: Bearer invalid-token'" "" "401"
test_api "DELETE" "/photos/1" "删除照片" "-H 'Authorization: Bearer invalid-token'" "" "401"
test_api "GET" "/photos/trash" "获取回收站照片" "-H 'Authorization: Bearer invalid-token'" "" "401"

# 4. 相册相关API
echo "\n=== 相册相关API ==="
test_api "GET" "/albums" "获取相册列表" "-H 'Authorization: Bearer invalid-token'" "" "401"
test_api "POST" "/albums" "创建相册" "-H 'Authorization: Bearer invalid-token'" '{"name":"测试相册"}' "401"
test_api "GET" "/albums/1" "获取相册详情" "-H 'Authorization: Bearer invalid-token'" "" "401"
test_api "GET" "/albums/1/photos" "获取相册照片" "-H 'Authorization: Bearer invalid-token'" "" "401"

# 5. 分享相关API
echo "\n=== 分享相关API ==="
test_api "GET" "/shares" "获取分享列表" "-H 'Authorization: Bearer invalid-token'" "" "401"
test_api "POST" "/shares" "创建分享" "-H 'Authorization: Bearer invalid-token'" '{"photo_id":1}' "401"
test_api "GET" "/shares/invalid-code" "通过分享码获取分享内容" "" "" "401"

# 6. 照片下载API
echo "\n=== 照片下载API ==="
test_api "POST" "/photos/download" "批量下载照片" "-H 'Authorization: Bearer invalid-token'" '{"photo_ids":[1]}' "401"

# 7. 照片转存API
echo "\n=== 照片转存API ==="
test_api "POST" "/photos/transfer" "转存照片" "-H 'Authorization: Bearer invalid-token'" '{"album_id":1,"photos":[{"url":"/Photos/1/photo.jpg","original_name":"test.jpg"}]}' "401"

# 8. 分享下载API
echo "\n=== 分享下载API ==="
test_api "POST" "/shares/invalid-code/download" "分享下载" "" '{"photo_ids":[1]}' "404"

# 总结
echo "\n========================="
echo "测试总结:"
echo "总测试数: $TOTAL_TESTS"
echo "通过: ${GREEN}$PASSED_TESTS${NC}"
echo "失败: ${RED}$FAILED_TESTS${NC}"
echo "通过率: $((PASSED_TESTS * 100 / TOTAL_TESTS))%"
echo "========================="

if [ $FAILED_TESTS -eq 0 ]; then
    echo -e "${GREEN}✓ 所有API测试通过！${NC}"
    exit 0
else
    echo -e "${RED}✗ 部分API测试失败！${NC}"
    exit 1
fi
