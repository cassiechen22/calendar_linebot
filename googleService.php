<?php

function setGoogleClient($uid){
    $client = new Google_Client();
    $client->setScopes(Google_Service_Calendar::CALENDAR);
    $client->setScopes(Google_Service_Calendar::CALENDAR_EVENTS);
    $client->setAuthConfig(__DIR__.'/credentials.json');
    $client->setAccessType('offline');
    $client->setPrompt('select_account consent');
    $client->setRedirectUri('https://ddf3bcc1.ngrok.io/bot/google_login.php');
    $client->setState($uid);

    // 利用此 uid 去 token file 找這個人的 token
    $token = findTokenByUid($uid);

    if($token=='false'){
        // 產生新的 token 並存起來
        $authUrl = $client->createAuthUrl();
        $result = $authUrl;
    } else {
        // 有token
        $client->setAccessToken($token);
        if ($client->isAccessTokenExpired()) {
            if ($client->getRefreshToken()) {
                $client->fetchAccessTokenWithRefreshToken($client->getRefreshToken());
                $result = $client;
            } else {
                $authUrl = $client->createAuthUrl();
                $result = $authUrl;
                
            }
        } else{
            $result = $client;
        }
    }
    return $result; 
}


function findTokenByUid($uid){
    $getContent = file_get_contents(__DIR__.'/token.json');
    $decodeContent = json_decode($getContent, true); 
    return (array_key_exists($uid, $decodeContent)) ? $decodeContent[$uid] : 'false';
}

function addUidTokenJson($uid,$token){
    $tokenPath = __DIR__.'/token.json';
    $getContent = file_get_contents($tokenPath);
    $decodeContent = json_decode($getContent, true);
    $decodeContent[$uid] = $token;
    file_put_contents($tokenPath,json_encode($decodeContent));
}

function deleteClient($uid){
    $tokenPath = __DIR__.'/token.json';
    $getContent = file_get_contents($tokenPath);
    $decodeContent = json_decode($getContent, true); 
    if(array_key_exists($uid, $decodeContent)){
        unset($decodeContent[$uid]);
        file_put_contents($tokenPath,json_encode($decodeContent));
        $result = findTokenByUid($uid);
        if($result == "false") {
            $status = "已刪除囉，重新輸入「日曆」登入新帳號唷！";
        } else {
            $status = "發生一點小錯誤，哭哭QQ";
        }
    } else {
        $status = "Oops！您尚未使用過此服務，請輸入「日曆」登入帳號";
    }
    return $status;
}

function getCalendarEvents($client){
    $reply = '';
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

    return $events; 
}
