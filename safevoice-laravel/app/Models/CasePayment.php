<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\DB;

class CasePayment extends Model
{
    protected $table = 'case_payments';

    protected $fillable = [
        'payment_code',
        'legal_request_id',
        'lawyer_id',
        'user_id',
        'gross_amount',
        'commission',
        'net_amount',
        'status',
        'payment_deadline',
        'paid_claimed_at',
        'claim_deadline',
        'confirmed_at',
        'disputed_at',
        'admin_contacted_at',
    ];

    protected $casts = [
        'gross_amount'        => 'decimal:2',
        'commission'          => 'decimal:2',
        'net_amount'          => 'decimal:2',
        'payment_deadline'    => 'datetime',
        'paid_claimed_at'     => 'datetime',
        'claim_deadline'      => 'datetime',
        'confirmed_at'        => 'datetime',
        'disputed_at'         => 'datetime',
        'admin_contacted_at'  => 'datetime',
    ];

    // ── Relationships ──────────────────────────────────────────────
    public function legalRequest()
    {
        return $this->belongsTo(LegalRequest::class);
    }

    public function lawyer()
    {
        return $this->belongsTo(Lawyer::class);
    }

    // ── Static helpers ─────────────────────────────────────────────
    /**
     * PAY-20260609-0042 format এ unique code generate করো
     */
    public static function generateCode(): string
    {
        $date  = now()->format('Ymd');
        $today = now()->startOfDay();

        $count = static::where('created_at', '>=', $today)->count() + 1;

        return 'PAY-' . $date . '-' . str_pad($count, 4, '0', STR_PAD_LEFT);
    }

    // ── Computed helpers ───────────────────────────────────────────
    public function isOverdue(): bool
    {
        return $this->status === 'pending' && now()->greaterThan($this->payment_deadline);
    }

    public function daysLeft(): int
    {
        if ($this->status !== 'pending') return 0;
        return max(0, (int) now()->diffInDays($this->payment_deadline, false));
    }

    public function hoursLeftForLawyerConfirm(): int
    {
        if (!$this->claim_deadline) return 0;
        return max(0, (int) now()->diffInHours($this->claim_deadline, false));
    }
}