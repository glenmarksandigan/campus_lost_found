<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Services\GeminiService;
use Illuminate\Support\Facades\DB;

class ChatbotController extends Controller
{
    public function ask(Request $request, GeminiService $gemini)
    {
        $message = $request->input('message');
        $history = $request->input('history', []);

        // Fetch live data for context
        $stats = $this->getLiveData();
        $systemInstruction = "You are the FoundIt! AI Assistant for BISU Candijay Campus. " . $stats;

        $response = $gemini->chat($message, $history, $systemInstruction);

        return response()->json($response);
    }

    private function getLiveData()
    {
        $foundCount = DB::table('items')->count();
        $lostCount = DB::table('lost_reports')->count();
        
        $recentFound = DB::table('items')->latest()->take(5)->get();
        $itemsList = $recentFound->map(fn($i) => "'{$i->item_name}' at {$i->found_location}")->implode(', ');

        return "Current stats: Total Found: $foundCount, Total Lost: $lostCount. Recent items: $itemsList.";
    }
}
