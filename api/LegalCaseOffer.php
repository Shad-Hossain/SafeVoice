<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class LegalCaseOffer extends Model
{
    protected $table = 'legal_case_offers';

    protected $fillable = [
        'legal_case_id', 'lawyer_id', 'type',
        'counter_price', 'message', 'status'
    ];

    protected $casts = [
        'counter_price' => 'decimal:2',
    ];

    public function legalCase()
    {
        return $this->belongsTo(LegalCase::class, 'legal_case_id');
    }

    public function lawyer()
    {
        return $this->belongsTo(Lawyer::class, 'lawyer_id');
    }
}
