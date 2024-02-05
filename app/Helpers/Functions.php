<?php

/**
 * This class formats api return respones
 * this needs to be autoloaded in composer.json
 */

/**
 * Success response method
 *
 * @param $result
 * @param $message
 * @return \Illuminate\Http\JsonResponse
 */
function sendResponse($result, $message)
{
    $response = [
        'success' => true,
        'items'    => $result,
        'message' => $message,
    ];

    return response()->json($response, 200);
}

/**
 * Return error response
 *
 * @param       $error
 * @param array $errorMessages
 * @param int   $code
 * @return \Illuminate\Http\JsonResponse
 */
function sendError($error, $errorMessages = [], $code = 404)
{
    $response = [
        'success' => false,
        'message' => $error,
    ];

    !empty($errorMessages) ? $response['items'] = $errorMessages : null;

    return response()->json($response, $code);
}
