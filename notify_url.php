<?php
/* *
 * 功能：异步通知页面
 */
require_once("log.php");
require_once("lib/epay.config.php");
require_once("lib/EpayCore.class.php");

// 创建数据库连接
$conn = new mysqli($epay_config['host'], $epay_config['user'], $epay_config['pass'], $epay_config['name']);

if ($conn->connect_error) {
    Logger::log("[" . date('Y-m-d H:i:s') . "] 数据库连接失败: " . $conn->connect_error,"Error");
    echo "fail";
    exit;
}

// 设置字符集（如果需要）
if (!$conn->set_charset("utf8mb4")) {
    Logger::log("字符集设置失败: " . $conn->error,"Error");
}

// 计算得出通知验证结果
$epay = new EpayCore($epay_config);
$verify_result = $epay->verifyNotify();

if ($verify_result) {
    //Logger::log($_GET, 'INFO');
    // 获取通知参数
    $out_trade_no = $conn->real_escape_string($_GET['out_trade_no']);
    $trade_no = $conn->real_escape_string($_GET['trade_no']);
    $trade_status = $conn->real_escape_string($_GET['trade_status']);
    $type = $conn->real_escape_string($_GET['type']);
    $money = floatval($_GET['money']); // 转换为浮点数
    $type_if = false;
    if ($trade_status == 'TRADE_SUCCESS') {
        // 检查订单是否已存在
        $stmt = $conn->prepare("SELECT trade_no FROM pay_order WHERE out_trade_no = ?");
        $stmt->bind_param("s", $out_trade_no);
        $stmt->execute();
        $result = $stmt->get_result();
        $stmt->store_result(); // 确保结果集已存储
        $orderExists = ($result->num_rows > 0);
        $stmt->close(); // 立即关闭查询语句
        if ($orderExists) {
            // 订单已存在，不执行插入操作
            Logger::log("订单已存在: $out_trade_no", "Error");
        } else {
            // 准备插入新订单
            $insert_stmt = $conn->prepare("INSERT INTO pay_order 
                (trade_no, out_trade_no, notify_url, return_url, type, name, price, endtime) 
                VALUES (?, ?, ?, ?, ?, ?, ?, NOW())");

            // 设置默认值（根据实际需要调整）
            $notify_url = "";
            $return_url = "";
            $name = "中转支付插件";

            // 绑定参数（注意参数数量和顺序）
            $insert_stmt->bind_param("ssssssd",
                $trade_no,
                $out_trade_no,
                $notify_url,
                $return_url,
                $type,
                $name,
                $money
            );
            if ($insert_stmt->execute()) {
                $type_if = true;
                $requestUrl = $epay_config['apiurl'] . "api/v4/callback/custom/" . $out_trade_no;
                // 初始化 cURL
                $ch = curl_init();
                curl_setopt_array($ch, [
                    CURLOPT_URL            => $requestUrl,      // 设置请求地址
                    CURLOPT_RETURNTRANSFER => true,  // 返回响应内容不直接输出
                    CURLOPT_SSL_VERIFYPEER => false, // 禁用 SSL 验证（仅测试用，生产环境应启用）
                    CURLOPT_TIMEOUT        => 10,           // 超时时间（秒）
                ]);
                // 执行请求
                $response = curl_exec($ch);
                // 处理错误
                if (curl_errno($ch)) {
                    Logger::log("cURL Error: " . curl_error($ch), "Error");
                }
                // 关闭连接
                curl_close($ch);
            } else {
                Logger::log("订单插入失败: " . $insert_stmt->error, "Error");
            }
            $insert_stmt->close();
        }
    }
    if ($type_if) {
        echo "success";
    } else {
        echo "fail";
    }
} else {
    echo "fail";
}
$conn->close();

