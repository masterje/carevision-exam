<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Resources\EventsResource;
use App\Models\Events;
use App\Models\UserEvents;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use Exception;
use Carbon\Carbon;
use DB;
use App\Services\ScheduleService;

class EventsController extends Controller
{
    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Http\JsonResponse
     */
    public function index()
    {
        $posts = Events::all();

        return sendResponse(EventsResource::collection($posts), 'Events retrieved successfully.');
    }

    /**
     * Store a newly created resource in storage.
     *
     * @param Request $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function store(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'name'       => 'required|min:10',
            'frequency' => 'required',
            'start_datetime' => 'required|date',
            'end_datetime' => 'date|after:start_datetime',
            'duration' => 'required|integer|min:5',
            'invitees' => 'required|json'
        ]);

        if ($validator->fails()) return sendError('Validation Error.', $validator->errors(), 422);

        $invitees = json_decode($request->invitees);
        if ( !is_array($invitees) || empty($invitees))
            return sendError('Validation Error.', ['invitees must be an array'], 422);

        $validFrequencies = ['once-off','weekly','monthly'];
        if (!in_array( strtolower($request->frequency), $validFrequencies)) {
            return sendError('Validation Error.', ['Invalid frequency'], 422);
        }

        if (strtolower($request->frequency) == 'once-off' && $request->end_datetime != '') {
            return sendError('Validation Error.', ['endDateTime should be null if frequency is Once-off'], 422);
        }

        $scheduleCheck = ScheduleService::checkForConflicts($request->start_datetime, $request->end_datetime,
            $invitees, $request->duration);
        if($scheduleCheck > 0) {
            return sendError('Validation Error.', ['Schedule conflict detected for 1 or more users'], 422);
        }

        try {
            $duration = $request->duration;

            if (!$request->end_datetime || $request->end_datetime == '') {
                $endDateTime = '';
            }
            else {
                $tmpEndDateTime = Carbon::create( $request->end_datetime );

                //overrides duration
                $duration = $tmpEndDateTime->diffInMinutes(Carbon::create( $request->start_datetime ));
                $endDateTime = $tmpEndDateTime->toDateTimeString();
            }

            $post = Events::create([
                'name' => $request->name,
                'frequency' => strtolower($request->frequency),
                'start_datetime' => $request->start_datetime,
                'end_datetime' => $endDateTime,
                'duration' => $duration,
                'duration_datetime' => Carbon::create( $request->start_datetime )->addMinutes($duration)->toDateTimeString(),
            ]);

            try {
                foreach ($invitees as $row) {
                    $userEvents = UserEvents::create([
                        'event_id' => $post->id,
                        'user_id' => $row
                    ]);
                }

            } catch (Exception $e) {

                UserEvents::where('event_id','=',$post->id)->delete();
                Events::find($post->id)->delete();

                $success = [];
                $message = $e;
            }

            $success = new EventsResource($post);
            $message = 'Event successfully created.';

        } catch (Exception $e) {
            $success = [];
            $message = $e. 'Sorry! Unable to create a the event.';
        }

        return sendResponse($success, $message);
    }

    /**
     * Display the specified resource.
     *
     * @param $id
     * @return \Illuminate\Http\JsonResponse
     */
    public function show($id)
    {
        $event = Events::find($id);
        if (is_null($event)) return sendError('Event not found.');
        return sendResponse(new EventsResource($event), 'Event retrieved successfully.');
    }

    /**
     * Update the specified resource in storage.
     *
     * @param Request $request
     * @param Post    $post
     * @return \Illuminate\Http\JsonResponse
     */
    public function update(Request $request, Post $post)
    {
        $validator = Validator::make($request->all(), [
            'title'       => 'required|min:10',
            'description' => 'required|min:40'
        ]);

        if ($validator->fails()) return sendError('Validation Error.', $validator->errors(), 422);

        try {
            $post->title       = $request->title;
            $post->description = $request->description;
            $post->save();

            $success = new EventsResource($post);
            $message = 'Yay! Post has been successfully updated.';
        } catch (Exception $e) {
            $success = [];
            $message = 'Sorry, Failed to update the post.';
        }

        return sendResponse($success, $message);
    }

    /**
     * Remove the specified resource from storage.
     *
     * @param Events $event
     * @return \Illuminate\Http\JsonResponse
     */
    public function destroy(Events $event)
    {
        try {
            $event->delete();
            return sendResponse([], 'Event successfully deleted.');
        } catch (Exception $e) {
            return sendError('Sorry! Unable to delete event.');
        }
    }

    public function search(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'from' => 'required|date',
            'to' => 'date|after:start_datetime'
        ]);

        if ($validator->fails()) return sendError('Validation Error.', $validator->errors(), 422);

        $events = Events::select('events.*', DB::raw('group_concat("user_events.user_id")') )
            ->join('user_events','user_events.event_id','=', 'events.id');

        if($request->to != '') {
            $to = Carbon::create($request->to);
            $events->whereBetween('start_datetime', [$request->from, $to->addDays(1)->toDateString()]);
        } else {
            $events->where('start_datetime','>=', $request->from);
        }

        if($request->invitees != '') {
            $users = explode(",", $request->invitees);
            $events->whereIn('user_events.user_id', $users );
        }

        $events->groupBy('events.id');

        return sendResponse(EventsResource::collection($events->get()), 'Events retrieved successfully...');
    }
}
