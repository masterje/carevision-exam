<?php

namespace App\Http\Resources;

use Illuminate\Http\Resources\Json\JsonResource;
use App\Models\UserEvents;

class EventsResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @param \Illuminate\Http\Request $request
     * @return array
     */
    public function toArray($request)
    {
        return [
            'id' => $this->id,
            'eventName' => $this->name,
            'frequency' => $this->frequency,
            'startDateTime' => $this->start_datetime,
            'endDateTime' => $this->end_datetime,
            'duration' => $this->duration,
            'invites' => $this->users()
        ];
    }

    private function users() : array {
        $users = UserEvents::where('event_id','=', $this->id)->select('user_id')->get();

        $data = [];
        foreach($users as $user) {
            array_push($data, $user->user_id);
        }
        
        return $data;
    }
}
