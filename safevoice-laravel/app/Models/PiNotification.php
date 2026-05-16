<?php
namespace App\Models;
use Illuminate\Database\Eloquent\Model;

class PiNotification extends Model
{
    protected $fillable = ['complaint_id','user_id','status','sent_at','responded_at'];
    public $timestamps = false;
}
