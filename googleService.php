<?php

function setGoogleClient($uid){
    $client = new Google_Client();
    $client->setScopes(Google_Service_Calendar::CALENDAR);
    $client->setScopes(Google_Service_Calendar::CALENDAR_EVENTS);
    $client->setAuthConfig(__DIR__.'/credentials.json');
    $client->setAccessType('offline');
    $client->setPrompt('select_account consent');
    $client->setRedirectUri('https://9846eab4.ngrok.io/bot/google_login.php');
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

    $events_array = [];
    $eventIds_array = [];

    if (!empty($events)) {
        foreach ($events as $event) {
            $start = $event->start->dateTime;
            $end = $event->end->dateTime;
            if (empty($end)) {
                $end = $event->end->date;
            }
            $start = date("Y-m-d H:i", strtotime($start));
            $end = date("Y-m-d H:i", strtotime($end));
            
            $item = buildCarouselItem($event->getSummary(),$start,$end,$event->id);
            array_push($events_array, $item);
        }
    }
    return $events_array; 
}