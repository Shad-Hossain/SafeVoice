<?php
namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class LawyerBid extends Model
{
    protected $table = 'lawyer_bids';
    public $timestamps = false;

    protected $fillable = [
        'legal_request_id', 'lawyer_id', 'proposed_fee',
        'cover_note', 'estimated_days', 'status',
        'consultation_date', 'office_address',
        'bid_at', 'responded_at',
    ];

    protected $casts = [
        'proposed_fee'      => 'decimal:2',
        'bid_at'            => 'datetime',
        'responded_at'      => 'datetime',
        'consultation_date' => 'datetime',
    ];

    public function lawyer()
    {
        return $this->belongsTo(Lawyer::class);
    }

    public function legalRequest()
    {
        return $this->belongsTo(LegalRequest::class);
    }
}