<?php

namespace Javaabu\Notifications\Tests\TestSupport\Http;

use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Javaabu\Notifications\Http\Controllers\NotificationsController;

class CustomNotificationsController extends NotificationsController
{
    public function latest(Request $request): JsonResponse
    {
        return response()->json(['custom' => true]);
    }
}
