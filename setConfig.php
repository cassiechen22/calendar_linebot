<?php

date_default_timezone_set("Asia/Taipei"); 
require_once('./LINEBotTiny.php');
require_once('./vendor/autoload.php');
require_once('lineService.php');
require_once('googleService.php');

// Get Linebot config
if (file_exists(__DIR__ . '/config.ini')) {
    $config = parse_ini_file("config.ini", true); // 解析配置檔
    if ($config['Channel']['Token'] == Null || $config['Channel']['Secret'] == Null) {
        error_log("config.ini 配置檔未設定完全！", 0); // 輸出錯誤
    } else {
        $channelAccessToken = $config['Channel']['Token'];
        $channelSecret = $config['Channel']['Secret'];
    }
} else {
    $configFile = fopen("config.ini", "w") or die("Unable to open file!");
    $configFileContent = '';
}

