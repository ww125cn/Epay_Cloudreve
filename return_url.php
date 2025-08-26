<?php
/* * 
 * 功能：跳转同步通知页面
 */

require_once("lib/epay.config.php");
require_once("lib/EpayCore.class.php");
?>
<!DOCTYPE HTML>
<html>
<head>
    <meta http-equiv="Content-Type" content="text/html; charset=utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>支付处理结果</title>
    <style>
        * {
            box-sizing: border-box;
            margin: 0;
            padding: 0;
            font-family: "Microsoft YaHei", sans-serif;
        }
        
        body {
            background-color: #f5f7fa;
            display: flex;
            justify-content: center;
            align-items: center;
            min-height: 100vh;
            padding: 20px;
        }
        
        .container {
            background: #fff;
            border-radius: 16px;
            box-shadow: 0 10px 30px rgba(0, 0, 0, 0.08);
            padding: 40px;
            max-width: 500px;
            width: 100%;
            text-align: center;
        }
        
        .icon {
            font-size: 60px;
            margin-bottom: 20px;
        }
        
        .success { color: #52c41a; }
        .failure { color: #f5222d; }
        
        h1 {
            font-size: 28px;
            margin-bottom: 20px;
            color: #1d1d1d;
        }
        
        .result-info {
            background: #f8f9fa;
            border-radius: 12px;
            padding: 20px;
            margin: 25px 0;
            text-align: left;
            font-size: 16px;
            line-height: 1.6;
        }
        
        .result-info p {
            margin: 10px 0;
            color: #555;
            overflow: hidden;
            text-overflow: ellipsis;
        }
        
        .result-info span {
            font-weight: 600;
            color: #1d1d1d;
        }
        
        .countdown {
            margin-top: 25px;
            font-size: 16px;
            color: #666;
        }
        
        .countdown-num {
            font-weight: 700;
            color: #1890ff;
        }
        
        .close-btn {
            display: inline-block;
            background: #1890ff;
            color: white;
            border: none;
            border-radius: 6px;
            padding: 12px 30px;
            font-size: 16px;
            margin-top: 25px;
            cursor: pointer;
            transition: background 0.3s;
        }
        
        .close-btn:hover {
            background: #40a9ff;
        }
    </style>
</head>
<body>
<?php
// 计算得出通知验证结果
$epay = new EpayCore($epay_config);
$verify_result = $epay->verifyReturn();

if($verify_result) {
    // 获取参数
    $out_trade_no = $_GET['out_trade_no'];
    $trade_no = $_GET['trade_no'];
    $trade_status = $_GET['trade_status'];
    $type = $_GET['type'];
    
    $status_text = $trade_status == 'TRADE_SUCCESS' ? '支付成功' : '支付处理中';
    $status_class = $trade_status == 'TRADE_SUCCESS' ? 'success' : 'failure';
    
    echo '<div class="container">';
    echo '<div class="icon '.$status_class.'">'.$status_text.'</div>';
    echo '<h1>支付结果通知</h1>';
    
    echo '<div class="result-info">';
    echo '<p><span>商户订单号：</span>'.$out_trade_no.'</p>';
    echo '<p><span>支付交易号：</span>'.$trade_no.'</p>';
    echo '<p><span>支付方式：</span>'.$type.'</p>';
    echo '<p><span>交易状态：</span>'.$trade_status.'</p>';
    echo '</div>';
    
    echo '<div class="countdown">页面将在 <span class="countdown-num" id="countdown">10</span> 秒后自动关闭</div>';
    echo '<button class="close-btn" onclick="closeWindow()">立即关闭</button>';
    echo '</div>';
}
else {
    echo '<div class="container">';
    echo '<div class="icon failure">!</div>';
    echo '<h1>验证失败</h1>';
    echo '<div class="result-info">';
    echo '<p>支付验证未通过，这可能表示：</p>';
    echo '<p>- 支付信息被篡改</p>';
    echo '<p>- 安全验证失败</p>';
    echo '<p>- 非法访问请求</p>';
    echo '</div>';
    echo '<div class="countdown">页面将在 <span class="countdown-num" id="countdown">10</span> 秒后自动关闭</div>';
    echo '<button class="close-btn" onclick="closeWindow()">立即关闭</button>';
    echo '</div>';
}
?>

<script>
    // 倒计时和关闭窗口功能
    var seconds = 10;
    var countdown = document.getElementById('countdown');
    
    function updateCountdown() {
        seconds--;
        countdown.textContent = seconds;
        
        if(seconds <= 0) {
            closeWindow();
        } else {
            setTimeout(updateCountdown, 1000);
        }
    }
    
    function closeWindow() {
        window.opener = null;
        window.open('', '_self');
        window.close();
    }
    
    // 启动倒计时
    setTimeout(updateCountdown, 1000);
</script>
</body>
</html>