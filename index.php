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
                        
                        if(gettype($result) == 'string'){
                            replyText($linebot,$event['replyToken'],$result);
                        } else{
                            $events = getCalendarEvents($result);
                            if(empty($events)){
                                replyText($linebot,$event['replyToken'],"快來建立活動～～");
                            } else {
                                replyEvents($linebot,$event['replyToken'],$events);
                            }  
                        }
                    }
                    
                    else if($message['text']=='換帳號'){
                        $result = deleteClient($event['source']['userId']);
                        replyText($linebot,$event['replyToken'],$result);
                    }
                    
                    else if (strpos($message['text'], '建立') !== false){
                        $event_name = explode(" ", $message['text']);
                        
                        if(empty($event_name[1]) && strlen($event_name[0]) < 7){
                            replyText($linebot,$event['replyToken'],'您沒有輸入活動名稱QQ');
                        } else if(strlen($event_name[0]) > 6){
                            replyText($linebot,$event['replyToken'],'建立跟活動名稱間要有空白格啦 ><   例如：建立 活動');
                        } else {
                            replyDatetimePicker($linebot,$event['replyToken'],$event_name[1]);
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
                        
                    if( gettype($result) == 'string'){
                        replyText($linebot,$event['replyToken'],$authUrl);
                    } else{
                        $status = cancelEvent($result,$eventId);
                        replyText($linebot,$event['replyToken'],$status);
                    }
                break;

                case 'edit':
                    $uid = $event['source']['userId'];
                    $result = setGoogleClient($uid);
                    
                    if( gettype($result) == 'string'){
                        replyText($linebot,$event['replyToken'],$authUrl);
                    } else{
                        $time_type = $dataArray[4];

                        if($time_type == 'start'){
                            $start_time = $event['postback']['params']['datetime'];
                            $_SESSION[$uid.'start'] = $start_time;
                            replyText($linebot,$event['replyToken'],"已設定開始時間 : $start_time \n記得設定結束時間唷！若不需要請輸入「日曆」看看最新變化");
                        } else {
                            $end_time = $event['postback']['params']['datetime'];
                            $_SESSION[$uid.'end'] = $end_time;
                            $_SESSION[$event['source']['userId']] = $event['replyToken'];
                            replyText($linebot,$event['replyToken'],"已設定結束時間 : $end_time \n記得設定結束時間唷！若不需要請輸入「日曆」看看最新變化");
                        }
                        
                        $status = editEvent($result,$eventId,$_SESSION[$uid.'start'],$_SESSION[$uid.'end']);
                    }
                break;

                case 'create':
                    $hour = $dataArray[4];
                    $uid = $event['source']['userId'];
                    
                    $result = setGoogleClient($uid);
                    $status = createEvent($result,$eventId,$event['postback']['params']['datetime'],$hour);
                    replyText($linebot,$event['replyToken'],$status);
                break;
            }
            
            break;
            
        default:
            error_log('Unsupported event type: ' . $event['type']);
            break;
    }
};