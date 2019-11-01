<?php

function buildCarouselItem($event,$time){
    $item = [];
    $item['title'] = $event;
    $item['text'] = $time;
    $item['actions'] = [
                            [
                                'type' => 'message', 
                                'label' => '取消預約', // 顯示在 btn 的字
                                'text' => '取消預約' // 用戶發送文字
                            ],
                            [
                                'type' => 'message', 
                                'label' => '改時間', 
                                'text' => '改時間'
                            ]
                        ];
    return $item;
}

function replyEvents($linebot, $replyToken, $events) {
    $linebot->replyMessage([
        'replyToken' => $replyToken,
        'messages' => [
            [
                'type' => 'template', 
                'altText' => 'Click to see more details', 
                'template' => [
                    'type' => 'carousel', 
                    'columns' => $events
                ]
            ]
        ]
    ]);
    
}

function replyAuth($linebot, $replyToken, $message) {
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

function pushMessage($uid,$message,$channelAccessToken) {
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

function logMessage($input){
    $file = 'recievedMessage.txt';
    $current = file_get_contents($file);
    $string = var_export($input, 1);
    $current .= $string;
    file_put_contents($file, $current);
}