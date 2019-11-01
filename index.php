<?php

require_once('setConfig.php');

// when user request
$linebot = new LINEBotTiny($channelAccessToken, $channelSecret);
foreach ($linebot->parseEvents() as $event) {
    logMessage($event);
    switch ($event['type']) {
        case 'message':
            $message = $event['message'];
            switch ($message['type']) {
                case 'text':
                    if($message['text']=='日曆' || $message['text']=='預約'){
                        
                        // 開一個 client
                        $client = setGoogleClient($event['source']['userId']);

                        // 利用此 uid 去 token file 找這個人的 token
                        $token = findTokenByUid($event['source']['userId']);

                        if($token=='false'){
                            // 產生新的 token 並存起來
                            $authUrl = $client->createAuthUrl();
                            replyAuth($linebot,$event['replyToken'],$authUrl);
                        } else {
                            // 有token
                            // $a = var_export($client,1);
                            $client->setAccessToken($token);
                            if ($client->isAccessTokenExpired()) {
                                if ($client->getRefreshToken()) {
                                    $client->fetchAccessTokenWithRefreshToken($client->getRefreshToken());
                                    $events = getCalendarEvents($client);
                                    replyEvents($linebot,$event['replyToken'],$events);
                                } else {
                                    $authUrl = $client->createAuthUrl();
                                    replyAuth($linebot,$event['replyToken'],$authUrl);
                                }
                            } else{
                                $events = getCalendarEvents($client);   
                                replyEvents($linebot,$event['replyToken'],$events);
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