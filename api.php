<?php
ob_start();
require_once("log.php");
require_once("lib/epay.config.php");
require_once("lib/EpayCore.class.php");
header('Content-Type: application/json; charset=utf-8');

$response = ['code' => -1, 'data' => '']; // 统一响应格式

// 安全过滤输入参数
$requestMethod = strtoupper($_SERVER['REQUEST_METHOD'] ?? 'GET');
$orderNo = $requestMethod === 'GET' 
    ? ($_GET["order_no"] ?? '') 
    : (json_decode(file_get_contents('php://input'), true)["order_no"] ?? '');

try {
    // 创建数据库连接
    $conn = new mysqli(
        $epay_config['host'],
        $epay_config['user'],
        $epay_config['pass'],
        $epay_config['name']
    );
    
    if ($conn->connect_error) {
        throw new Exception("Database connection failed: " . $conn->connect_error);
    }
    
    // 设置字符集
    if (!$conn->set_charset("utf8mb4")) {
        throw new Exception("Charset setting failed: " . $conn->error);
    }

    // GET请求处理
    if ($requestMethod === 'GET') {
        if (empty($orderNo)) {
            throw new Exception("Missing order_no parameter");
        }
        
        $stmt = $conn->prepare("SELECT trade_no FROM pay_order WHERE out_trade_no = ?");
        $stmt->bind_param("s", $orderNo);
        $stmt->execute();
        $stmt->store_result();
        
        if ($stmt->num_rows > 0) {
            $response = ['code' => 0, 'data' => 'PAID'];
        } else {
            $response = ['code' => 2, 'data' => 'ORDER_NOT_FOUND'];
        }
        $stmt->close();
    } 
    // POST请求处理
    elseif ($requestMethod === 'POST') {
        $jsonData = json_decode(file_get_contents('php://input'), true);
        
        // 验证必要参数
        if (empty($jsonData) || !is_array($jsonData)) {
            throw new Exception("Invalid JSON input");
        }
        //Logger::log($jsonData, "data");
        $requiredParams = ['order_no', 'name', 'amount'];
        foreach ($requiredParams as $param) {
            if (empty($jsonData[$param])) {
                throw new Exception("Missing required parameter: $param");
            }
        }

        // 构建支付参数
        $payParams = [
            "pid" => $epay_config['pid'],
            "type" => $jsonData["type"] ?? "wxpay", // 默认微信支付
            "notify_url" => $epay_config['notify_url'],
            "return_url" => $epay_config['return_url'],
            "out_trade_no" => $jsonData["order_no"],
            "name" => $jsonData["name"],
            "money" => $jsonData["amount"]
        ];

        // 生成支付链接
        $epay = new EpayCore($epay_config);
        $payLink = $epay->getPayLink($payParams);
        $response = ['code' => 0, 'data' => $payLink];
    }
    else {
        throw new Exception("Method not allowed", 405);
    }

} catch (Exception $e) {
    error_log("[" . date('Y-m-d H:i:s') . "] ERROR: " . $e->getMessage());
    $response['data'] = $e->getMessage();
    $response['code'] = $e->getCode() ?: -1;
} finally {
    ob_end_clean();
    echo json_encode($response);
    if (isset($conn) && $conn instanceof mysqli) {
        $conn->close();
    }
}
?>