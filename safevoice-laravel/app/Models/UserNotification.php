<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class UserNotification extends Model
{
    public $timestamps = false;

    protected $fillable = [
        'user_id',
        'type',
        'title',
        'message',
        'complaint_id',
        'action_url',
        'icon',
        'is_read',
    ];

    protected $casts = [
        'is_read'    => 'boolean',
        'created_at' => 'datetime',
    ];

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    // ─── Static helper: notification তৈরি করতে ───────────────────
    public static function notify(int $userId, string $type, string $title, string $message, array $extra = []): self
    {
        return self::create([
            'user_id'      => $userId,
            'type'         => $type,
            'title'        => $title,
            'message'      => $message,
            'complaint_id' => $extra['complaint_id'] ?? null,
            'action_url'   => $extra['action_url']   ?? null,
            'icon'         => $extra['icon']          ?? '🔔',
            'is_read'      => false,
        ]);
    }

    // ─── Type অনুযায়ী icon ───────────────────────────────────────
    public static function iconFor(string $type): string
    {
        return match($type) {
            'complaint_submitted'  => '📋',
            'status_update'        => '🔄',
            'pi_payment_reminder'  => '⏰',
            'pi_payment_expired'   => '❌',
            'pi_assigned'          => '🕵️',
            'refund_initiated'     => '💰',
            default                => '🔔',
        };
    }
}