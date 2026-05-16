<?php
namespace App\Models;
use Illuminate\Database\Eloquent\Model;

class PrivateInvestigator extends Model
{
    protected $fillable = [
        'pi_code','full_name','email','phone','address',
        'nid_number','photo_url','nid_photo_url','login_email',
        'password_hash','is_active','active_cases','total_cases','notes',
    ];
    protected $hidden = ['password_hash'];
    protected $casts = ['is_active'=>'boolean'];
    public $timestamps = false;
}
