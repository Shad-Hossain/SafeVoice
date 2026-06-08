<?php
namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class LegalRequest extends Model
{
    protected $table = 'legal_requests';
    public $timestamps = false;

    protected $fillable = [
        'request_id', 'user_id', 'user_name', 'user_phone',
        'issue_type', 'description', 'location', 'preferred_city',
        'is_urgent', 'is_instant', 'budget_max', 'deadline', 'deadline_notified',
        'status', 'assigned_lawyer_id',
        'accepted_at', 'completed_at',
        'created_at', 'updated_at',
    ];

    protected $casts = [
        'is_urgent'         => 'boolean',
        'is_instant'        => 'boolean',
        'deadline_notified' => 'boolean',
        'budget_max'        => 'decimal:2',
        'deadline'          => 'datetime',
        'accepted_at'       => 'datetime',
        'completed_at'      => 'datetime',
        'created_at'        => 'datetime',
        'updated_at'        => 'datetime',
    ];

    // deadline পার হয়েছে কিনা
    public function isExpired(): bool
    {
        return $this->deadline && now()->isAfter($this->deadline) && in_array($this->status, ['open','bidding']);
    }

    // deadline পর্যন্ত কত সময় বাকি (seconds)
    public function secondsLeft(): int
    {
        if (!$this->deadline) return 0;
        return max(0, now()->diffInSeconds($this->deadline, false));
    }

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function assignedLawyer()
    {
        return $this->belongsTo(Lawyer::class, 'assigned_lawyer_id');
    }

    public function bids()
    {
        return $this->hasMany(LawyerBid::class);
    }

    public function acceptedBid()
    {
        return $this->hasOne(LawyerBid::class)->where('status', 'accepted');
    }

    public static function generateRequestId(): string
    {
        $date  = now()->format('Ymd');
        $count = static::whereDate('created_at', now()->toDateString())->count() + 1;
        return 'LR-' . $date . '-' . str_pad($count, 4, '0', STR_PAD_LEFT);
    }
}