<?php

require_once('setConfig.php');
require_once('calendarService.php');

session_start();

// when user request
$linebot = new LINEBotTiny($channelAccessToken, $channelSecret);
foreach ($linebot->parseEvents() as $event) {
    logMessage($event);
    $_SESSION[$event['source']['userId']] = $event['replyToken'];
    switch ($event['type']) {
        case 'message':
            $message = $event['message'];
            switch ($message['type']) {
                case 'text':
                    if($message['text']=='日曆'){
                        $result = setGoogleClient($event['source']['userId']);
                        
                        if(gettype($result) == string){
                            replyText($linebot,$event['replyToken'],$result);
                        } else{
                            $events = getCalendarEvents($result);
                            if(empty($events)){
                                replyText($linebot,$event['replyToken'],"您還沒有任何活動唷！");
                            } else {
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
        case 'postback':
            $message = $event['postback']['data'];
            $dataArray = explode('/', $message);
            $action = $dataArray[1];
            $eventId = $dataArray[3];
            switch ($action){
                case 'cancel':
                    $uid = $event['source']['userId'];
                    $result = setGoogleClient($uid);
                        
                    if( gettype($result) == string){
                        replyText($linebot,$event['replyToken'],$authUrl);
                    } else{
                        $status = cancelEvent($result,$eventId);
                        replyText($linebot,$event['replyToken'],$status);
                    }
                break;

                case 'edit':
                    $uid = $event['source']['userId'];
                    $result = setGoogleClient($uid);
                    $time_type = $dataArray[4];
                    if($time_type == 'start'){
                        $start_time = $event['postback']['params']['datetime'];
                        $_SESSION[$uid.'start'] = $start_time;
                        replyText($linebot,$event['replyToken'],"set start time : $start_time \nPlease request calendar again to see new event list.");
                    } else {
                        $end_time = $event['postback']['params']['datetime'];
                        $_SESSION[$uid.'end'] = $end_time;
                        $_SESSION[$event['source']['userId']] = $event['replyToken'];
                        replyText($linebot,$event['replyToken'],"set end time : $end_time \nPlease request calendar again to see new event list.");
                    }
                    
                    if( gettype($result) == string){
                        replyText($linebot,$event['replyToken'],$authUrl);
                    } else{
                        $status = editEvent($result,$eventId,$_SESSION[$uid.'start'],$_SESSION[$uid.'end']);
                        // pushMessage($uid,$status,$channelAccessToken);
                    }
                break;

                case 'new':
                break;
            }
            
            break;
            
        default:
            error_log('Unsupported event type: ' . $event['type']);
            break;
    }
};