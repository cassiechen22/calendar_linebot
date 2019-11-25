<?php

require_once('setConfig.php');

function getTodayEvents($client){
    $events = getCalendarEvents($client);
    $today_events = [];
    $tomorrow=strtotime("tomorrow");

    foreach($events as $event) {
        $end = $event->end->dateTime;
        if (empty($end)) {
            $end = $event->end->date;
        }
        if(strtotime($end) < $tomorrow){
            $end = date("Y-m-d H:i", strtotime($end));
            $start = $event->start->dateTime;
            $start = date("Y-m-d H:i", strtotime($start));
            $item = buildCarouselItem($event->getSummary(),$start,$end,$event->id);
            array_push($today_events, $item);
        }
    } 
    return $today_events;
}

function cancelEvent($client,$eventId){
    try {
        $service = new Google_Service_Calendar($client);
        $service->events->delete('primary', $eventId);
        return '取消成功囉！請再次輸入「日曆」看看最新的活動吧';
    } catch(Exception $e) {
        return '取消失敗惹QQ 請您在操作一次 ><';
    }
}

function editEvent($client,$eventId,$start,$end){
    try {
        $service = new Google_Service_Calendar($client);
        $event = $service->events->get('primary', $eventId);
        $event->start->timeZone = 'Asia/Taipei';
        $event->start->dateTime = formatDateTime($start);
        $event->end->timeZone = 'Asia/Taipei';
        $event->end->dateTime = formatDateTime($end);
        $service->events->update('primary', $event->getId(), $event);
        return '更新成功囉！請再次輸入「日曆」看看最新的活動吧';
    } catch(Exception $e) {
        return '更新失敗惹QQ 請您在操作一次 >< ';
    }
    
}

function createEvent($client,$eventId,$start,$hour){
    $start = $start . ':00';
    
    if($hour == 1){
        $onehour = strtotime($start) + 3600; 
        $end = date('Y-m-d\TH:i:s',$onehour);
    } else {
        $halfhour = strtotime($start) + 5400; 
        $end = date('Y-m-d\TH:i:s',$halfhour);
    }
    
    $service = new Google_Service_Calendar($client);
    $event = new Google_Service_Calendar_Event([
        'summary' => $eventId,
        'start' => [
            'dateTime' => $start,
            'timeZone' => 'Asia/Taipei',
        ],
        'end' => [
            'dateTime' => $end,
            'timeZone' => 'Asia/Taipei',
        ],
    ]);
    
    $event = $service->events->insert('primary', $event);
    if(empty($event->getId())){
        return '新增失敗惹QQ 請您在操作一次 ><';
    } else {
        return '建立成功囉！請再次輸入「日曆」看看最新的活動吧';
    }
}

function formatDateTime($dateTime){
    $date = new DateTime($dateTime);
    return date_format($date,'Y-m-d\TH:i:s');
}