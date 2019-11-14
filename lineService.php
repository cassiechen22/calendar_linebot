<?php
function buildCarouselItem($event,$start,$end,$id){
    $item = [];
    $item['title'] = $event;
    $item['text'] = $start.' ~ ' . $end;
    $item['actions'] = [
                            [
                                'type' => 'postback', 
                                'label' => '取消活動', // 顯示在 btn 的字
                                'data' => 'action/cancel/eventId/'.$id
                            ],
                            [
                                "type" => "datetimepicker",
                                "label" => "更改活動開始時間",
                                "data" => 'action/edit/eventId/'.$id.'/start',
                                "mode" => "datetime"
                            ],
                            [
                                "type" => "datetimepicker",
                                "label" => "更改活動結束時間",
                                "data" => 'action/edit/eventId/'.$id.'/end',
                                "mode" => "datetime"
                            ],
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

function replyText($linebot, $replyToken, $message) {
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

function replyDatetimePicker($linebot, $replyToken, $message){
    $linebot->replyMessage([
        'replyToken' => $replyToken,
        'messages' => [
            [
                'type' => 'template', 
                'altText' => 'Click to see more details', 
                'template' => [
                    'type' => 'buttons', 
                    'text' => $message,
                    "actions" => [
                        [
                            "type" => "datetimepicker",
                            "data" => "action/create/event_name/". $message .'/1', 
                            "label" => "課程一小時",
                            "mode" => "datetime", 
                        ],
                        [
                            "type" => "datetimepicker",
                            "data" => "action/create/event_name/". $message .'/1.5', 
                            "label" => "課程一個半小時",
                            "mode" => "datetime", 
                        ],
                    ],
                ]
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
    if(gettype($message) == string){
        $post_data = array(
            'to' => $uid,
            'messages' => [
                [
                    'type' => 'text',
                    'text' => $message
                ]
            ]
        );
    } else {
        $post_data = array(
            'to' => $uid,
            'messages' => [
                [
                    'type' => 'template', 
                    'altText' => 'Click to see more details', 
                    'template' => [
                        'type' => 'carousel', 
                        'columns' => $message
                    ]
                ]
            ]
        );
    }
    

    $post_data = json_encode($post_data);
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