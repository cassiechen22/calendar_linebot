<?php

require_once('setConfig.php');


function cancelEvent($client,$eventId){
    try {
        $service = new Google_Service_Calendar($client);
        $service->events->delete('primary', $eventId);
        return 'cancel successed. Please request calendar again to see new event list.';
    } catch(Exception $e) {
        return 'cancel failed. Please request calendar again then click cancel.';
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
        return 'updated successed. Please request calendar again to see new event list.';
    } catch(Exception $e) {
        return 'updated failed. Please request calendar again then click cancel.';
    }
    
}

function newEvent($summary,$start,$end){
    // $start = 2019-11-24 20:00
 
    $event = new Google_Service_Calendar_Event([
        'summary' => $summary,
        'start' => [
            'dateTime' => formatDateTime($start),
            'timeZone' => $default_timezone,
        ],
        'end' => [
            'dateTime' => formatDateTime($end),
            'timeZone' => $default_timezone,
        ],
    ]);
    
    $event = $service->events->insert('primary', $event);
}

function formatDateTime($dateTime){
    $date = new DateTime($dateTime);
    return date_format($date,'Y-m-d\TH:i:s');
}