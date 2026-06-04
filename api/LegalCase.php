

<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class LegalCase extends Model
{
    protected $table = 'legal_cases';

    protected $fillable = [
        'case_id', 'user_id', 'lawyer_id', 'issue_type',
        'description', 'user_budget', 'contact_phone',
        'preferred_time', 'evidence_files', 'user_consent',
        'status', 'agreed_price', 'lawyer_assigned_at',
        'completed_at', 'payment_deadline', 'offer_expires_at'
    ];

    protected $casts = [
        'evidence_files' => 'array',
        'user_consent' => 'boolean',
        'lawyer_assigned_at' => 'datetime',
        'completed_at' => 'datetime',
        'payment_deadline' => 'datetime',
        'offer_expires_at' => 'datetime',
    ];

    public function user()
    {
        return $this->belongsTo(User::class, 'user_id');
    }

    public function lawyer()
    {
        return $this->belongsTo(Lawyer::class, 'lawyer_id');
    }

    public function offers()
    {
        return $this->hasMany(LegalCaseOffer::class, 'legal_case_id');
    }

    public function payments()
    {
        return $this->hasMany(LegalPayment::class, 'legal_case_id');
    }

    public function messages()
    {
        return $this->hasMany(LegalCaseMessage::class, 'legal_case_id')->orderBy('created_at', 'asc');
    }

    public function notifications()
    {
        return $this->hasMany(LegalNotification::class, 'legal_case_id');
    }

    public function is30PercentPaid()
    {
        return $this->payments()
                    ->where('payment_type', '30_percent')
                    ->where('status', 'completed')
                    ->exists();
    }

    public function is70PercentPaid()
    {
        return $this->payments()
                    ->where('payment_type', '70_percent')
                    ->where('status', 'completed')
                    ->exists();
    }

    public function getStatusLabelAttribute()
    {
        return match($this->status) {
            'pending'          => 'Lawyer খোঁজা হচ্ছে',
            'offer_received'   => 'Offer পেয়েছো',
            'lawyer_booked'    => 'Lawyer Booked',
            'in_progress'      => 'Case চলছে',
            'waiting_payment'  => 'Payment বাকি (৭০%)',
            'resolved'         => 'Case Resolved',
            'expired'          => 'Expired — আবার চেষ্টা করো',
            'disputed'         => 'Admin এর কাছে Complaint দেওয়া হয়েছে',
            default            => $this->status
        };
    }
}
