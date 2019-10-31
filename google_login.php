<?php

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

if(!empty($_GET['code'])){
    require __DIR__ . '/vendor/autoload.php';

    $client = new Google_Client();
    $client->setScopes(Google_Service_Calendar::CALENDAR_READONLY);
    $client->setAuthConfig(__DIR__.'/credentials.json');
    $client->setAccessType('offline');
    $client->setPrompt('select_account consent');
    $client->setRedirectUri('https://979ac3cb.ngrok.io/bot/google_login.php');

    $tokenPath = __DIR__.'/token.json';
    if (!file_exists(dirname($tokenPath))) {
        mkdir(dirname($tokenPath), 0700, true);
    }
    $authCode = $_GET['code'];
    $accessToken = $client->fetchAccessTokenWithAuthCode($authCode);
    $client->setAccessToken($accessToken);

    addUidTokenJson($_GET['state'],$client->getAccessToken(),$channelAccessToken);

    // echo "<script type='text/javascript'>window.close();</script>";

    getCalendarEvents($client,$channelAccessToken);
}



function addUidTokenJson($uid,$token,$channelAccessToken){
    $tokenPath = __DIR__.'/token.json';
    $getContent = file_get_contents($tokenPath);
    $decodeContent = json_decode($getContent, true);
    $decodeContent[$uid] = $token;
    file_put_contents($tokenPath,json_encode($decodeContent));
}


function getCalendarEvents($client,$channelAccessToken){
    $reply ='';
    $service = new Google_Service_Calendar($client);
    $calendarId = 'primary';
    
    $optParams = array(
        'maxResults' => 10,
        'orderBy' => 'startTime',
        'singleEvents' => true,
        'timeMin' => date('c'),
    );
    $results = $service->events->listEvents($calendarId, $optParams);
    $events = $results->getItems();

    $events_array = [];

    if (empty($events)) {
        $reply = "No upcoming events found.\n";
    } else {
        foreach ($events as $event) {
            $start = $event->start->dateTime;
            if (empty($start)) {
                $start = $event->start->date;
            }
            $reply .= $event->getSummary(). $start .'\n';
            // var_dump($event->getSummary(), $start);
        }
        pushMessage($_GET['state'],$reply,$channelAccessToken);
    }
}


function pushMessage($uid,$message,$channelAccessToken)
{
    $token = 'Bearer '.$channelAccessToken;
    $url = 'https://api.line.me/v2/bot/message/push';
    $headers = array(
        'Content-Type: application/json',
        'Authorization: '.$token
    );

    $post_data = array(
        'to' => $uid,
        'messages'=> [["type" => "text", "text" => $message]],
    );

    $post_data = json_encode($post_data);
    // $post_data = json_decode($post_data);
    $ch = curl_init();
    curl_setopt($ch, CURLOPT_URL, $url);
    curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
    curl_setopt($ch, CURLOPT_POST, true);
    curl_setopt($ch, CURLOPT_CUSTOMREQUEST, 'POST');
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, false);
    curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
    curl_setopt($ch, CURLOPT_POSTFIELDS, $post_data);
    $result = curl_exec($ch);
    curl_close($ch);
    // $result = json_decode($result);
    // close window
    return $post_data;
}