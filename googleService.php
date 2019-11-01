<?php

function setGoogleClient($uid){
    $client = new Google_Client();
    $client->setScopes(Google_Service_Calendar::CALENDAR_READONLY);
    $client->setAuthConfig(__DIR__.'/credentials.json');
    $client->setAccessType('offline');
    $client->setPrompt('select_account consent');
    $client->setRedirectUri('https://979ac3cb.ngrok.io/bot/google_login.php');
    $client->setState($uid);
    return $client;
}

function findTokenByUid($uid){
    $getContent = file_get_contents(__DIR__.'/token.json');
    $decodeContent = json_decode($getContent, true); 
    return (array_key_exists($uid, $decodeContent)) ? $decodeContent[$uid] : 'false';
}

function addUidTokenJson($uid,$token,$channelAccessToken){
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

    if (empty($events)) {
        $reply = "No upcoming events found.\n";
    } else {
        foreach ($events as $event) {
            $start = $event->start->dateTime;
            if (empty($start)) {
                $start = $event->start->date;
            }
            $item = buildCarouselItem($event->getSummary(),$start);
            array_push($events_array, $item);
        }
    }
    return $events_array; 
}