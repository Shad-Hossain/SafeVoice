<?php
namespace App\Helpers;

/**
 * AnonymousId — complaint submitter এর পরিচয় লুকানোর জন্য
 *
 * কীভাবে কাজ করে:
 *   - user_id + APP_KEY দিয়ে HMAC-SHA256 hash বানানো হয়
 *   - DB-তে plaintext user_id নেই, শুধু hash আছে
 *   - DB চুরি হলেও কেউ বলতে পারবে না কোন hash কোন user এর
 *   - User নিজে verify করতে পারবে কারণ same input → same hash
 *   - কোনো হ্যাকার reverse করতে পারবে না (one-way)
 */
class AnonymousId
{
    /**
     * User ID থেকে secure, irreversible hash বানাও
     */
    public static function make(int $userId): string
    {
        return hash_hmac('sha256', (string) $userId, config('app.key'));
    }

    /**
     * এই hash টা এই user এর কিনা check করো
     */
    public static function matches(int $userId, string $hash): bool
    {
        return hash_equals(self::make($userId), $hash);
    }
}
