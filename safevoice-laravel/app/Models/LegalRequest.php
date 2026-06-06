<?php
namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class LegalRequest extends Model
{
    protected $table = 'legal_requests';
    public $timestamps = false;

    protected $fillable = [
        'request_id', 'user_id', 'user_name', 'user_phone',
        'issue_type', 'description', 'location', 'is_urgent',
        'budget_max', 'status', 'assigned_lawyer_id',
        'accepted_at', 'completed_at',
        'created_at', 'updated_at',
    ];

    protected $casts = [
        'is_urgent'    => 'boolean',
        'budget_max'   => 'decimal:2',
        'accepted_at'  => 'datetime',
        'completed_at' => 'datetime',
        'created_at'   => 'datetime',
        'updated_at'   => 'datetime',
    ];

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