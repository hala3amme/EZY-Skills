<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class NotificationController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        $user = $request->user();

        $notifications = $user->notifications()
            ->latest()
            ->limit(50)
            ->get();

        return response()->json([
            'notifications' => $notifications->map(fn ($n) => [
                'id' => $n->id,
                'type' => $n->type,
                'data' => $n->data,
                'read_at' => $n->read_at,
                'created_at' => $n->created_at,
            ])->values(),
        ]);
    }

    public function markAsRead(string $notification, Request $request): JsonResponse
    {
        $user = $request->user();

        $n = $user->notifications()->whereKey($notification)->first();
        if (!$n) {
            return response()->json(['message' => 'Not found.'], 404);
        }

        $n->markAsRead();

        return response()->json(['message' => 'Notification marked as read.']);
    }
}
