const axios = require('axios');

// 配置基础URL
const BASE_URL = 'http://localhost:3333/api';
const BASE_URL_NO_API = 'http://localhost:3333';

// 创建axios实例
const api = axios.create({
  baseURL: BASE_URL,
  timeout: 10000,
  headers: {
    'Content-Type': 'application/json'
  }
});

// 创建不包含/api前缀的axios实例，用于健康检查
const apiNoApi = axios.create({
  baseURL: BASE_URL_NO_API,
  timeout: 10000,
  headers: {
    'Content-Type': 'application/json'
  }
});

// 测试结果统计
let totalTests = 0;
let passedTests = 0;
let failedTests = 0;

// 测试函数
async function testApi(method, endpoint, name, headers = {}, data = {}, expectedStatus) {
  totalTests++;
  
  process.stdout.write(`测试 ${name}... `);
  
  // 选择合适的axios实例
  const currentApi = endpoint === '/health' ? apiNoApi : api;
  const currentEndpoint = endpoint === '/health' ? endpoint : endpoint;
  
  try {
    const response = await currentApi({
      method,
      url: currentEndpoint,
      headers,
      data
    });
    
    if (response.status === expectedStatus) {
      console.log(`✅ 成功 (HTTP ${response.status})`);
      passedTests++;
      return true;
    } else {
      console.log(`❌ 失败 (预期: ${expectedStatus}, 实际: ${response.status})`);
      failedTests++;
      return false;
    }
  } catch (error) {
    const actualStatus = error.response?.status || '500';
    if (actualStatus === expectedStatus) {
      console.log(`✅ 成功 (HTTP ${actualStatus})`);
      passedTests++;
      return true;
    } else {
      console.log(`❌ 失败 (预期: ${expectedStatus}, 实际: ${actualStatus})`);
      failedTests++;
      return false;
    }
  }
}

// 主测试函数
async function runTests() {
  console.log('=== 开始检测所有API ===');
  console.log(`检测时间: ${new Date().toLocaleString()}`);
  console.log('=========================');
  
  // 1. 健康检查
  console.log('\n=== 健康检查 ===');
  // 健康检查API路径是/health，没有/api前缀
  await testApi('GET', '/health', '健康检查', {}, {}, 200);
  
  // 2. 认证相关API
  console.log('\n=== 认证相关API ===');
  await testApi('POST', '/auth/login', '登录接口 - 无效凭证', {}, { username: 'test', password: 'test' }, 401);
  await testApi('POST', '/auth/register', '注册接口', {}, { username: 'testuser', email: 'test@example.com', password: 'password123' }, 200);
  await testApi('GET', '/auth/me', '获取当前用户 - 无效token', { Authorization: 'Bearer invalid-token' }, {}, 401);
  
  // 3. 照片相关API
  console.log('\n=== 照片相关API ===');
  await testApi('GET', '/photos', '获取照片列表 - 无效token', { Authorization: 'Bearer invalid-token' }, {}, 401);
  await testApi('POST', '/photos', '上传照片 - 无效token', { Authorization: 'Bearer invalid-token' }, {}, 401);
  await testApi('GET', '/photos/1', '获取单张照片 - 无效token', { Authorization: 'Bearer invalid-token' }, {}, 401);
  await testApi('DELETE', '/photos/1', '删除照片 - 无效token', { Authorization: 'Bearer invalid-token' }, {}, 401);
  await testApi('GET', '/photos/trash', '获取回收站照片 - 无效token', { Authorization: 'Bearer invalid-token' }, {}, 401);
  
  // 4. 相册相关API
  console.log('\n=== 相册相关API ===');
  await testApi('GET', '/albums', '获取相册列表 - 无效token', { Authorization: 'Bearer invalid-token' }, {}, 401);
  await testApi('POST', '/albums', '创建相册 - 无效token', { Authorization: 'Bearer invalid-token' }, { name: '测试相册' }, 401);
  await testApi('GET', '/albums/1', '获取相册详情 - 无效token', { Authorization: 'Bearer invalid-token' }, {}, 401);
  await testApi('GET', '/albums/1/photos', '获取相册照片 - 无效token', { Authorization: 'Bearer invalid-token' }, {}, 401);
  
  // 5. 分享相关API
  console.log('\n=== 分享相关API ===');
  await testApi('GET', '/shares', '获取分享列表 - 无效token', { Authorization: 'Bearer invalid-token' }, {}, 401);
  await testApi('POST', '/shares', '创建分享 - 无效token', { Authorization: 'Bearer invalid-token' }, { photo_id: 1 }, 401);
  await testApi('GET', '/shares/invalid-code', '通过分享码获取分享内容 - 无效码', {}, {}, 404);
  
  // 6. 照片下载API
  console.log('\n=== 照片下载API ===');
  await testApi('POST', '/photos/download', '批量下载照片 - 无效token', { Authorization: 'Bearer invalid-token' }, { photo_ids: [1] }, 401);
  
  // 7. 照片转存API
  console.log('\n=== 照片转存API ===');
  await testApi('POST', '/photos/transfer', '转存照片 - 无效token', { Authorization: 'Bearer invalid-token' }, { 
    album_id: 1, 
    photos: [{ url: '/Photos/1/photo.jpg', original_name: 'test.jpg' }] 
  }, 401);
  
  // 8. 分享下载API
  console.log('\n=== 分享下载API ===');
  await testApi('POST', '/shares/invalid-code/download', '分享下载 - 无效码', {}, { photo_ids: [1] }, 404);
  
  // 总结
  console.log('\n=========================');
  console.log('测试总结:');
  console.log(`总测试数: ${totalTests}`);
  console.log(`通过: ${passedTests}`);
  console.log(`失败: ${failedTests}`);
  console.log(`通过率: ${Math.round((passedTests / totalTests) * 100)}%`);
  console.log('=========================');
  
  if (failedTests === 0) {
    console.log('✅ 所有API测试通过！');
    process.exit(0);
  } else {
    console.log('❌ 部分API测试失败！');
    process.exit(1);
  }
}

// 运行测试
runTests().catch(error => {
  console.error('测试过程中发生错误:', error);
  process.exit(1);
});
