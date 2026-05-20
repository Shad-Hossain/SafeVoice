<?php
namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use App\Models\FcmToken;

class FcmController extends Controller
{
    // FCM V1 endpoint — project_id দিয়ে তৈরি হয়
    private static function fcmEndpoint(): string
    {
        $projectId = env('FCM_PROJECT_ID', 'safevoice-3c9c5');
        return "https://fcm.googleapis.com/v1/projects/{$projectId}/messages:send";
    }

    // ─────────────────────────────────────────────────────────
    // POST /api/fcm/register-token
    // ─────────────────────────────────────────────────────────
    public function registerToken(Request $request)
    {
        $request->validate([
            'token'       => 'required|string|max:512',
            'device_type' => 'nullable|string|in:web,android,ios',
        ]);

        $userId = $request->session()->get('user_id')
                ?? $request->input('user_id');

        FcmToken::updateOrCreate(
            ['token' => $request->token],
            [
                'user_id'     => $userId,
                'device_type' => $request->input('device_type', 'web'),
            ]
        );

        return response()->json(['success' => true, 'message' => 'FCM token registered.']);
    }

    // ─────────────────────────────────────────────────────────
    // POST /api/fcm/unregister-token
    // ─────────────────────────────────────────────────────────
    public function unregisterToken(Request $request)
    {
        $request->validate(['token' => 'required|string']);
        FcmToken::where('token', $request->token)->delete();
        return response()->json(['success' => true]);
    }

    // ─────────────────────────────────────────────────────────
    // Public static helpers — অন্য Controller থেকে call করো
    // ─────────────────────────────────────────────────────────

    public static function sendToUser(int $userId, string $title, string $body, array $data = []): bool
    {
        $tokens = FcmToken::where('user_id', $userId)->pluck('token')->toArray();
        if (empty($tokens)) return false;
        return self::sendToTokens($tokens, $title, $body, $data);
    }

    public static function sendToAll(string $title, string $body, array $data = []): bool
    {
        $tokens = FcmToken::pluck('token')->toArray();
        if (empty($tokens)) return false;
        return self::sendToTokens($tokens, $title, $body, $data);
    }

    public static function sendToUsers(array $userIds, string $title, string $body, array $data = []): bool
    {
        if (empty($userIds)) return false;
        $tokens = FcmToken::whereIn('user_id', $userIds)->pluck('token')->toArray();
        if (empty($tokens)) return false;
        return self::sendToTokens($tokens, $title, $body, $data);
    }

    // ─────────────────────────────────────────────────────────
    // Core sender — FCM V1 API
    // V1 তে একবারে একটাই token পাঠাতে হয় (multicast নেই)
    // তাই loop করে প্রতিটা token এ আলাদা request পাঠাই
    // ─────────────────────────────────────────────────────────
    private static function sendToTokens(array $tokens, string $title, string $body, array $data = []): bool
    {
        $accessToken = self::getAccessToken();
        if (!$accessToken) return false;

        $endpoint = self::fcmEndpoint();
        $success  = false;

        // String convert — FCM data field শুধু string value নেয়
        $stringData = array_map('strval', array_merge($data, [
            'title' => $title,
            'body'  => $body,
        ]));

        foreach ($tokens as $token) {
            $payload = [
                'message' => [
                    'token' => $token,
                    'notification' => [
                        'title' => $title,
                        'body'  => $body,
                    ],
                    'webpush' => [
                        'notification' => [
                            'title' => $title,
                            'body'  => $body,
                            'icon'  => '/images/logo.png',
                            'click_action' => env('APP_URL', 'http://localhost'),
                        ],
                        'fcm_options' => [
                            'link' => env('APP_URL', 'http://localhost'),
                        ],
                    ],
                    'data' => $stringData,
                ],
            ];

            $ch = curl_init($endpoint);
            curl_setopt_array($ch, [
                CURLOPT_POST           => true,
                CURLOPT_RETURNTRANSFER => true,
                CURLOPT_HTTPHEADER     => [
                    'Content-Type: application/json',
                    'Authorization: Bearer ' . $accessToken,
                ],
                CURLOPT_POSTFIELDS => json_encode($payload),
                CURLOPT_TIMEOUT    => 10,
            ]);

            $response = curl_exec($ch);
            $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);

            if ($httpCode === 200) {
                $success = true;
            } else {
                // Invalid / expired token — DB থেকে মুছে দাও
                $result  = json_decode($response, true);
                $errCode = $result['error']['details'][0]['errorCode'] ?? '';
                if (in_array($errCode, ['INVALID_ARGUMENT', 'UNREGISTERED'])) {
                    FcmToken::where('token', $token)->delete();
                }
            }
        }

        return $success;
    }

    // ─────────────────────────────────────────────────────────
    // Service Account দিয়ে OAuth2 Access Token নাও
    // JWT বানিয়ে Google Token endpoint এ পাঠাই
    // ─────────────────────────────────────────────────────────
    private static function getAccessToken(): ?string
    {
        // Service account JSON file path — storage/app/ এ রাখো
        $keyPath = storage_path('app/firebase-service-account.json');

        if (!file_exists($keyPath)) {
            Log::error('FCM: firebase-service-account.json not found at ' . $keyPath);
            return null;
        }

        $serviceAccount = json_decode(file_get_contents($keyPath), true);

        if (!isset($serviceAccount['private_key'], $serviceAccount['client_email'])) {
            Log::error('FCM: Invalid service account JSON');
            return null;
        }

        // JWT Header
        $header = self::base64UrlEncode(json_encode([
            'alg' => 'RS256',
            'typ' => 'JWT',
        ]));

        // JWT Claim
        $now   = time();
        $claim = self::base64UrlEncode(json_encode([
            'iss'   => $serviceAccount['client_email'],
            'scope' => 'https://www.googleapis.com/auth/firebase.messaging',
            'aud'   => 'https://oauth2.googleapis.com/token',
            'iat'   => $now,
            'exp'   => $now + 3600,
        ]));

        // JWT Signature — RS256
        $signingInput = "{$header}.{$claim}";
        $privateKey   = openssl_pkey_get_private($serviceAccount['private_key']);

        if (!$privateKey) {
            Log::error('FCM: Could not load private key');
            return null;
        }

        openssl_sign($signingInput, $signature, $privateKey, 'SHA256');
        $jwt = $signingInput . '.' . self::base64UrlEncode($signature);

        // Google Token endpoint এ JWT পাঠাও, access token নাও
        $ch = curl_init('https://oauth2.googleapis.com/token');
        curl_setopt_array($ch, [
            CURLOPT_POST           => true,
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_HTTPHEADER     => ['Content-Type: application/x-www-form-urlencoded'],
            CURLOPT_POSTFIELDS     => http_build_query([
                'grant_type' => 'urn:ietf:params:oauth:grant-type:jwt-bearer',
                'assertion'  => $jwt,
            ]),
            CURLOPT_TIMEOUT => 10,
        ]);

        $response = curl_exec($ch);
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);

        if ($httpCode !== 200) {
            Log::error('FCM: Failed to get access token. Response: ' . $response);
            return null;
        }

        $result = json_decode($response, true);
        return $result['access_token'] ?? null;
    }

    // Base64 URL encode (JWT এর জন্য)
    private static function base64UrlEncode(string $data): string
    {
        return rtrim(strtr(base64_encode($data), '+/', '-_'), '=');
    }
}