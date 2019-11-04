<?php

require_once('setConfig.php');

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

    $uid = $_GET['state'];
    addUidTokenJson($uid,$client->getAccessToken(),$channelAccessToken);

    $linebot = new LINEBotTiny($channelAccessToken, $channelSecret);
    $events = getCalendarEvents($client);
    replyEvents($linebot,$_SESSION[$uid],$events);
}
