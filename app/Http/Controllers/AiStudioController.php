<?php

namespace App\Http\Controllers;

use App\Exceptions\GeminiServiceException;
use App\Services\GeminiService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class AiStudioController extends Controller
{
    public function __construct(private GeminiService $gemini)
    {
    }

    public function show(Request $request): View
    {
        $user = $request->user();
        $user->loadMissing(['learningPath', 'skills']);

        return view('ai-studio', [
            'user' => $user,
            'pathName' => $user->learningPath?->path_name,
            'skillNames' => $user->skills->pluck('name')->values(),
        ]);
    }

    public function chat(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'message' => ['required', 'string', 'min:1', 'max:2000'],
        ]);

        $message = trim(preg_replace('/\s+/', ' ', $validated['message']) ?? '');

        if ($message === '') {
            return response()->json([
                'message' => 'Please enter a question for the career assistant.',
            ], 422);
        }

        $history = $request->session()->get('ai_studio_history', []);

        if (! is_array($history)) {
            $history = [];
        }

        try {
            $reply = $this->gemini->generateReply($request->user(), $message, $history);
        } catch (GeminiServiceException $e) {
            return response()->json([
                'message' => $e->getMessage(),
            ], 503);
        }

        $history[] = ['role' => 'user', 'text' => $message];
        $history[] = ['role' => 'model', 'text' => $reply];
        $request->session()->put(
            'ai_studio_history',
            array_slice($history, -GeminiService::MAX_HISTORY)
        );

        return response()->json([
            'reply' => $reply,
        ]);
    }
}
