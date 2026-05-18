<?php
namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Http;

class AiController extends Controller
{
    public function enhanceDescription(Request $request)
    {
        $request->validate(['description' => 'required|string|min:10']);

        $desc     = $request->description;
        $type     = $request->input('type', 'incident');
        $location = $request->input('location', '');
        $apiKey   = config('services.gemini.key');

        if (!$apiKey) {
            return response()->json(['success' => false, 'message' => 'AI service not configured.'], 503);
        }

        $prompt = "You are helping a citizen write a clear, professional complaint report for SafeVoice, a civic reporting platform in Bangladesh.\n\nThe user has written this raw description:\n\"{$desc}\"\n\nIncident type: {$type}\nLocation: {$location}\n\nYour task:\n- Extract all key facts (what happened, who was involved, when, where, any specific details)\n- Rewrite it as a clear, structured, professional complaint description in 3-5 sentences\n- Use formal but simple English\n- Do NOT add any facts that were not mentioned\n- Return ONLY the improved description text, no preamble or explanation";

        try {
            $response = Http::withHeaders([
                'Content-Type' => 'application/json',
            ])->post("https://generativelanguage.googleapis.com/v1beta/models/gemini-1.5-flash-8b:generateContent?key={$apiKey}", [
                'contents' => [
                    ['parts' => [['text' => $prompt]]]
                ],
                'generationConfig' => [
                    'maxOutputTokens' => 300,
                    'temperature'     => 0.4,
                ]
            ]);

            $data = $response->json();
            $text = $data['candidates'][0]['content']['parts'][0]['text'] ?? '';

            if (!trim($text)) throw new \Exception('Empty response');

            return response()->json(['success' => true, 'enhanced' => trim($text)]);

        } catch (\Exception $e) {
            return response()->json(['success' => false, 'message' => 'AI enhancement failed. Please try again.'], 500);
        }
    }

    public function analyzeComplaint(Request $request)
    {
        $request->validate(['description' => 'required|string|min:10']);

        $desc     = $request->description;
        $type     = $request->input('type', 'unspecified');
        $location = $request->input('location', 'unspecified');
        $apiKey   = config('services.gemini.key');

        if (!$apiKey) {
            return response()->json(['success' => false, 'message' => 'AI service not configured.'], 503);
        }

        $prompt = "You are a complaint analyst for SafeVoice, a citizen reporting platform in Bangladesh.\n\nA user submitted:\n- Type: {$type}\n- Location: {$location}\n- Description: \"{$desc}\"\n\nIn 2-3 concise sentences: state the Severity (High/Medium/Low) with a brief reason, then give one practical piece of advice (evidence to gather or immediate next step). Be supportive and professional. Start with the severity.";

        try {
            $response = Http::withHeaders([
                'Content-Type' => 'application/json',
            ])->post("https://generativelanguage.googleapis.com/v1beta/models/gemini-1.5-flash-8b:generateContent?key={$apiKey}", [
                'contents' => [['parts' => [['text' => $prompt]]]],
                'generationConfig' => ['maxOutputTokens' => 200, 'temperature' => 0.4]
            ]);

            $data = $response->json();
            $text = $data['candidates'][0]['content']['parts'][0]['text'] ?? '';
            if (!trim($text)) throw new \Exception('Empty response');

            return response()->json(['success' => true, 'analysis' => trim($text)]);
        } catch (\Exception $e) {
            return response()->json(['success' => false, 'message' => 'Analysis failed.'], 500);
        }
    }
}
