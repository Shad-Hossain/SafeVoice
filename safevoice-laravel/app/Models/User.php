<?php
namespace App\Models;

use Illuminate\Foundation\Auth\User as Authenticatable;
use Laravel\Sanctum\HasApiTokens; // ← Sanctum token এর জন্য

class User extends Authenticatable
{
    use HasApiTokens; // ← এটা add করা হয়েছে

    protected $fillable = [
        'name', 'email', 'email_hash', 'phone', 'password_hash',
        'id_type', 'id_number', 'id_document_path',
        'location', 'profile_photo', 'status', 'complaints_count',
        'fcm_token', 'sos_helped_count', 'sos_helped_verified_count',
        'suspension_count', 'suspended_until',
    ];

    protected $casts = [
        'suspended_until' => 'datetime',
    ];

    // password_hash কখনো API response এ যাবে না
    protected $hidden = ['password_hash'];

    public $timestamps = false;

    public function complaints() { return $this->hasMany(Complaint::class); }
    public function sosAlerts()  { return $this->hasMany(SosAlert::class); }
}
