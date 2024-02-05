<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Resources\EventsResource;
use App\Models\Events;
use App\Models\UserEvents;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use Exception;

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
            'end_datetime' => 'required|date|after:start_datetime',
            'duration' => 'required|integer', //to be autocomputed
            'invitees' => 'required|json'
        ]);

        $invitees = json_decode($request->invitees);

        if ($validator->fails()) return sendError('Validation Error.', $validator->errors(), 422);

        if ( !is_array($invitees) && !empty($invitees))
            return sendError('Validation Error.', ['invitees must be an array'], 422);

        try {
            $post = Events::create([
                'name'       => $request->name,
                'frequency' => $request->frequency,
                'start_datetime' => $request->start_datetime,
                'end_datetime' => $request->end_datetime,
                'duration' => $request->duration, //to be autocomputed
            ]);

            $success = new EventsResource($post);
            $message = 'Event successfully created.';
        } catch (Exception $e) {
            $success = [];
            $message = 'Oops! Unable to create a the event.';
        }

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
            $message = 'Oops, Failed to update the post.';
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
            return sendError('Oops! Unable to delete event.');
        }
    }
}
