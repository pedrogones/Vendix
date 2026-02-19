<?php

namespace App\Http\Controllers;

use App\Services\Chatbot\ChatbotService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class ChatbotController extends Controller
{
    public function __construct(private readonly ChatbotService $chatbotService)
    {
    }

    public function message(Request $request): JsonResponse
    {
        $data = $request->validate([
            'message' => ['required', 'string', 'max:500'],
        ]);

        $sessionId = $request->session()->getId();
        $response = $this->chatbotService->handle($sessionId, $data['message']);

        return response()->json([
            'ok' => true,
            'reply' => $response['message'],
            'intent' => $response['intent'],
            'meta' => $response['meta'],
            'timestamp' => now()->toIso8601String(),
        ]);
    }
}
