<?php
class Logger
{
    private static $logDir = __DIR__ . '/logs/';
    private static $maxSize = 10485760; // 10MB
    
    public static function log($message, $level = 'INFO')
    {
        try {
            $date = date('Y-m-d');
            $logFile = self::$logDir . "app_{$date}.log";
            
            // 修复1：目录创建增加权限检查
            if (!is_dir(self::$logDir)) {
                if (!mkdir(self::$logDir, 0775, true)) {
                    throw new Exception("无法创建日志目录");
                }
            }
            
            $time = date('H:i:s');
            $ip = $_SERVER['REMOTE_ADDR'] ?? '127.0.0.1';
            
            // 修复2：添加trace信息(可选)
            $trace = debug_backtrace(DEBUG_BACKTRACE_IGNORE_ARGS, 1)[0];
            $caller = basename($trace['file']) . ':' . $trace['line'];
            
            // 核心修复：转换数组/对象为可读字符串
            if (is_array($message) || is_object($message)) {
                $message = json_encode($message, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE);
            } elseif (is_bool($message)) {
                $message = $message ? 'true' : 'false';
            } else {
                $message = (string)$message;
            }
            
            // 修复3：格式化消息避免换行问题
            $cleanMessage = str_replace(["\r", "\n"], ' ', $message);
            
            $entry = "[{$time}] [{$level}] [{$caller}] {$ip} {$cleanMessage}" . PHP_EOL;
            
            // 修复4：轮转前检查文件是否存在且不为空
            if (file_exists($logFile) && filesize($logFile) > 0 && 
               filesize($logFile) > self::$maxSize) {
                self::rotateLog($logFile);
            }
            
            // 修复5：防止写入冲突的重试机制
            $retry = 0;
            $maxRetries = 3;
            while ($retry < $maxRetries) {
                try {
                    file_put_contents($logFile, $entry, FILE_APPEND | LOCK_EX);
                    break;
                } catch (Exception $e) {
                    $retry++;
                    usleep(100000); // 0.1秒延迟
                }
            }
        } catch (\Exception $e) {
            error_log('Logger Error: ' . $e->getMessage());
        }
    }

    private static function rotateLog($filePath)
    {
        // 修复6：确保轮转文件不重复
        $timestamp = time();
        $counter = 0;
        $rotatedFile = str_replace('.log', "_full_{$timestamp}.log", $filePath);
        
        while (file_exists($rotatedFile)) {
            $counter++;
            $rotatedFile = str_replace('.log', "_full_{$timestamp}_{$counter}.log", $filePath);
        }
        
        rename($filePath, $rotatedFile);
    }
}