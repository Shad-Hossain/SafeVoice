<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class LegalPayment extends Model
{
    protected $table = 'legal_payments';

    public $timestamps = false;

    protected $fillable = [
        'legal_case_id', 'user_id', 'lawyer_id',
        'payment_type', 'amount', 'transaction_id',
        'status', 'paid_at'
    ];

    protected $casts = [
        'amount'   => 'decimal:2',
        'paid_at'  => 'datetime',
    ];

    public function legalCase()
    {
        return $this->belongsTo(LegalCase::class, 'legal_case_id');
    }

    public function user()
    {
        return $this->belongsTo(User::class, 'user_id');
    }

    public function lawyer()
    {
        return $this->belongsTo(Lawyer::class, 'lawyer_id');
    }
}
