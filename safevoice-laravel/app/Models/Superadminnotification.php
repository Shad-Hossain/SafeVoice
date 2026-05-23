<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class SuperAdminNotification extends Model
{
    public $timestamps = false;

    protected $fillable = [
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

    // ─── Static helper ───────────────────────────────────────────
    public static function notify(string $type, string $title, string $message, array $extra = []): self
    {
        return self::create([
            'type'         => $type,
            'title'        => $title,
            'message'      => $message,
            'complaint_id' => $extra['complaint_id'] ?? null,
            'action_url'   => $extra['action_url']   ?? '/super-admin/dashboard',
            'icon'         => $extra['icon']          ?? '⚠️',
            'is_read'      => false,
        ]);
    }

    public static function unreadCount(): int
    {
        return self::where('is_read', false)->count();
    }
}