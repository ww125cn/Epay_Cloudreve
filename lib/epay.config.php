<?php
/* *
 * 配置文件
 */

//支付接口地址
$epay_config['apiurl'] = 'https://pay.ww125.cn/';

//商户ID
$epay_config['pid'] = '140269060';

//商户密钥
$epay_config['key'] = 'q1F1N68FdF6ayM8zwbad1cDF69Zaf7f8';

//数据库账号
$epay_config['user'] = 'root';

//数据库密码
$epay_config['pass'] = 'o0AiCC1G8Xq';

//数据库地址
$epay_config['host'] = '127.0.0.1';

//数据库表
$epay_config['name'] = 'pan_paylog';

//数据库
$epay_config['user'] = 'pan_paylog';

//异步通知
$epay_config['notify_url'] = "http://ai.ww125.cn/notify_url.php";

//跳转同步通知
$epay_config['return_url'] = "http://ai.ww125.cn/return_url.php";