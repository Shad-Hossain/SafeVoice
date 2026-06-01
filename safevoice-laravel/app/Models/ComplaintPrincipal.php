<?php
namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Crypt;

/**
 * ComplaintPrincipal — anonymous complaint এর notification পাঠানোর জন্য
 *
 * এই table টা কোনো admin API-তে EXPOSE করবে না।
 * শুধু system নিজে ব্যবহার করবে।
 *
 * complaint_id → encrypted_user_id (Laravel Crypt, APP_KEY দিয়ে encrypt)
 * DB চুরি হলেও APP_KEY ছাড়া decrypt করা যাবে না।
 */
class ComplaintPrincipal extends Model
{
    public $timestamps = false;

    protected $fillable = [
        'complaint_id',
        'encrypted_user_id',
    ];

    /**
     * complaint submit করার সময় call করো — user_id encrypt করে store করো
     */
    public static function store(string $complaintId, int $userId): void
    {
        self::updateOrCreate(
            ['complaint_id' => $complaintId],
            ['encrypted_user_id' => Crypt::encryptString((string) $userId)]
        );
    }

    /**
     * complaint_id দিয়ে actual user_id বের করো (notification এর জন্য)
     * ব্যর্থ হলে null return করবে
     */
    public static function getUserId(string $complaintId): ?int
    {
        $record = self::where('complaint_id', $complaintId)->first();
        if (!$record) return null;

        try {
            return (int) Crypt::decryptString($record->encrypted_user_id);
        } catch (\Exception $e) {
            return null;
        }
    }
}
