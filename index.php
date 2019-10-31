<?php
/**
 * Copyright 2016 LINE Corporation
 *
 * LINE Corporation licenses this file to you under the Apache License,
 * version 2.0 (the "License"); you may not use this file except in compliance
 * with the License. You may obtain a copy of the License at:
 *
 *   https://www.apache.org/licenses/LICENSE-2.0
 *
 * Unless required by applicable law or agreed to in writing, software
 * distributed under the License is distributed on an "AS IS" BASIS, WITHOUT
 * WARRANTIES OR CONDITIONS OF ANY KIND, either express or implied. See the
 * License for the specific language governing permissions and limitations
 * under the License.
 */
date_default_timezone_set("Asia/Taipei"); 
require_once('./LINEBotTiny.php');

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

require __DIR__ . '/vendor/autoload.php';



session_start();


// when user request
$linebot = new LINEBotTiny($channelAccessToken, $channelSecret);
foreach ($linebot->parseEvents() as $event) {
    logMessage($event);
    switch ($event['type']) {
        case 'message':
            $message = $event['message'];
            switch ($message['type']) {
                case 'text':
                    if($message['text']=='課程' || $message['text']=='預約'){

                        // 儲存 uid session -> push message 會用到
                        $_SESSION[$event['source']['userId']] = $event['source']['userId'];   
                        
                        // 開一個 client
                        $client = setGoogleClient($event['source']['userId']);

                        // 利用此 uid 去 token file 找這個人的 token
                        $token = findTokenByUid($_SESSION[$event['source']['userId']]);

                        if($token=='false'){
                            // 產生新的 token 並存起來
                            $authUrl = $client->createAuthUrl();
                            replyMessage($linebot,$event['replyToken'],$authUrl);
                        } else {
                            // 有token
                            // $a = var_export($client,1);
                            $client->setAccessToken($token);
                            if ($client->isAccessTokenExpired()) {
                                if ($client->getRefreshToken()) {
                                    $client->fetchAccessTokenWithRefreshToken($client->getRefreshToken());
                                    $events = getCalendarEvents($client);
                                    
                                    replyMessage($linebot,$event['replyToken'],$events);
                                } else {
                                    $authUrl = $client->createAuthUrl();
                                    replyMessage($linebot,$event['replyToken'],$authUrl);
                                }
                            } else{
                                $events = getCalendarEvents($client);   
                                replyMessage($linebot,$event['replyToken'],$events);
                            }
                        }

                    }
                    break;
                default:
                    error_log('Unsupported message type: ' . $message['type']);
                    break;
            }
            break;
        default:
            error_log('Unsupported event type: ' . $event['type']);
            break;
    }
};


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
            $reply .= $event->getSummary() ."\n". $start ."\n";
            // var_dump($event->getSummary(), $start);
        }
    }
    return $reply; 
}

function replyMessage($linebot, $replyToken, $message) {
    $linebot->replyMessage([
        'replyToken' => $replyToken,
        'messages' => [
            [
                'type' => 'text',
                'text' => $message
            ]
        ]
    ]);
}

function logMessage($input){
    $file = 'recievedMessage.txt';
    $current = file_get_contents($file);
    $string = var_export($input, 1);
    $current .= $string;
    file_put_contents($file, $current);
}