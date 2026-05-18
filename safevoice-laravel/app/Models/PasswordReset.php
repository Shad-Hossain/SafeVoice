<?php
namespace App\Models;
use Illuminate\Database\Eloquent\Model;

class PasswordReset extends Model
{
    protected $fillable = ['phone','email','otp_code','expires_at','used'];
    protected $casts = ['used'=>'boolean'];
    public $timestamps = false;
}