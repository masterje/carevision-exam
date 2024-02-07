<?php

namespace App\Services;

use App\Models\Events;
use App\Models\UserEvents;
use Carbon\Carbon;

class ScheduleService
{
    public static function checkForConflicts($inputStartDate, $endDateTime, $userIds, $duration)
    {
        $conflict = 0 ;

        //pass 1 check
        $event = Events::where('start_datetime','>=', $inputStartDate)
            ->whereIn('user_events.user_id', $userIds )
            ->join('user_events','user_events.event_id','=', 'events.id')
            ->orderBy('start_datetime')
            ->first();

        if(!$event) {
            $conflict = 0 ;
        }
        else {
            if($event->start_datetime <= $inputStartDate) {
                $conflict = 1;
            }

            $estimatedEnd = Carbon::create($inputStartDate)->addMinutes($duration)->toDateTimeString();

            if($estimatedEnd > $event->start_datetime) {
                $conflict = 1;
            }
        }

        //pass 2, check duration times
        $durationCheck = Events::where('start_datetime','<=', $inputStartDate)
        ->whereIn('user_events.user_id', $userIds )
        ->join('user_events','user_events.event_id','=', 'events.id')
        ->orderBy('start_datetime','DESC')
        ->first();

        if ($durationCheck) {
            if($durationCheck->duration_datetime > $inputStartDate) {
                $conflict = 1;
            }
        }

        return $conflict;
    }
}
