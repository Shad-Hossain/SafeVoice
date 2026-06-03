

<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class LegalNotification extends Model
{
    protected $table = 'legal_notifications';

    public $timestamps = false;

    protected $fillable = [
        'recipient_type', 'recipient_id', 'legal_case_id',
        'title', 'message', 'type', 'is_read'
    ];

    protected $casts = [
        'is_read'    => 'boolean',
        'created_at' => 'datetime',
    ];

    public function legalCase()
    {
        return $this->belongsTo(LegalCase::class, 'legal_case_id');
    }

    // Helper: notification পাঠানো সহজ করার জন্য
    public static function notify(string $recipientType, int $recipientId, int $caseId = null, string $title = '', string $message = '', string $type = '')
    {
        return self::create([
            'recipient_type' => $recipientType,
            'recipient_id'   => $recipientId,
            'legal_case_id'  => $caseId,
            'title'          => $title,
            'message'        => $message,
            'type'           => $type,
            'is_read'        => false,
        ]);
    }
}
