<?php
header('Content-Type: application/json; charset=utf-8');
header('Access-Control-Allow-Origin: *');

$name = isset($_POST['name']) ? trim($_POST['name']) : '';
$company = isset($_POST['company']) ? trim($_POST['company']) : '';
$phone = isset($_POST['phone']) ? trim($_POST['phone']) : '';
$service = isset($_POST['service']) ? trim($_POST['service']) : '';

// 验证
if (!$name || !$company || !$phone) {
    echo json_encode(['code'=>400, 'msg'=>'缺少必填字段']);
    exit;
}
if (!preg_match('/^1[3-9]\d{9}$/', $phone)) {
    echo json_encode(['code'=>400, 'msg'=>'手机号格式错误']);
    exit;
}

// 保存到本地文件
$data_file = '/var/www/h5-complete/data.json';
$existing = [];
if (file_exists($data_file)) {
    $existing = json_decode(file_get_contents($data_file), true) ?: [];
}
$existing[] = [
    'name' => $name,
    'company' => $company,
    'phone' => $phone,
    'service' => $service,
    'time' => date('Y-m-d H:i:s'),
    'ip' => $_SERVER['REMOTE_ADDR'] ?? ''
];
file_put_contents($data_file, json_encode($existing, JSON_UNESCAPED_UNICODE|JSON_PRETTY_PRINT), LOCK_EX);

// 飞书机器人推送
$webhook = 'https://open.feishu.cn/open-apis/bot/v2/hook/8a3faff5-65f1-473e-93dc-1a8b0655e9bf';
$msg = "=?青商H5体检预约=\n=?姓名：={$name}\n=?公司：={$company}\n=?手机：={$phone}\n=?预约服务：={$service}\n=?提交时间：=".date('Y-m-d H:i:s');
$ch = curl_init($webhook);
curl_setopt($ch, CURLOPT_POST, true);
curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode(['msg_type'=>'text','content'=>['text'=>$msg]]));
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
curl_setopt($ch, CURLOPT_HTTPHEADER, ['Content-Type: application/json']);
curl_setopt($ch, CURLOPT_TIMEOUT, 5);
curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
$resp = curl_exec($ch);
$http_code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
curl_close($ch);

if ($http_code === 200) {
    echo json_encode(['code'=>200, 'msg'=>'提交成功', 'redirect'=>'/success.html']);
} else {
    echo json_encode(['code'=>500, 'msg'=>'提交失败，请稍后重试', 'detail'=>$resp]);
}