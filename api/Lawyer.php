<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Lawyer extends Model
{
    protected $table = 'lawyers';

    protected $fillable = [
        'name', 'email', 'phone', 'password_hash',
        'bar_council_id', 'id_card_path', 'specialization',
        'bio', 'profile_photo', 'status', 'id_verified',
        'verified_at', 'verified_by', 'total_cases', 'rating'
    ];

    protected $hidden = ['password_hash'];

    public function legalCases()
    {
        return $this->hasMany(LegalCase::class, 'lawyer_id');
    }

    public function offers()
    {
        return $this->hasMany(LegalCaseOffer::class, 'lawyer_id');
    }

    public function payments()
    {
        return $this->hasMany(LegalPayment::class, 'lawyer_id');
    }

    public function notifications()
    {
        return $this->hasMany(LegalNotification::class, 'recipient_id')
                    ->where('recipient_type', 'lawyer');
    }

    public function unreadNotificationsCount()
    {
        return $this->notifications()->where('is_read', false)->count();
    }
}
