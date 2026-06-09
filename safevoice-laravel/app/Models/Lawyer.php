<?php
namespace App\Models;

use Illuminate\Foundation\Auth\User as Authenticatable;
use Laravel\Sanctum\HasApiTokens;

class Lawyer extends Authenticatable
{
    use HasApiTokens;

    protected $table = 'lawyers';

    protected $fillable = [
        'lawyer_code', 'full_name', 'email', 'email_hash', 'phone',
        'password_hash', 'bar_council_id', 'bar_council_photo',
        'profile_photo', 'address', 'city', 'division', 'serving_areas',
        'specializations', 'experience_years', 'min_fee', 'bio', 'status',
        'is_available', 'total_cases', 'completed_cases',
        'total_earned', 'total_commission_paid',
        'rating', 'rating_count',
    ];

    protected $hidden = ['password_hash'];

    protected $casts = [
        'specializations' => 'array',
        'serving_areas'   => 'array',
        'is_available'    => 'boolean',
        'min_fee'         => 'decimal:2',
        'rating'          => 'decimal:2',
    ];

    public $timestamps = false;

    // ── Relationships ──────────────────────────────────────────────
    public function bids()
    {
        return $this->hasMany(LawyerBid::class);
    }

    public function notifications()
    {
        return $this->hasMany(LawyerNotification::class);
    }

    public function assignedRequests()
    {
        return $this->hasMany(LegalRequest::class, 'assigned_lawyer_id');
    }

    // ── Helpers ────────────────────────────────────────────────────
    public function getUnreadNotificationCount(): int
    {
        return $this->notifications()->where('is_read', false)->count();
    }

    // auto-generate next lawyer code
    public static function generateCode(): string
    {
        $last   = static::orderByDesc('id')->first();
        $nextNo = $last ? (intval(substr($last->lawyer_code, 3)) + 1) : 1;
        return 'LAW' . str_pad($nextNo, 3, '0', STR_PAD_LEFT);
    }
}