<?php
namespace App\Models;
use Illuminate\Database\Eloquent\Model;

class SosNotification extends Model
{
    protected $fillable = ['sos_id','notified_user_id','status'];
    public $timestamps = false;
    const CREATED_AT = 'created_at';

    public function sosAlert() { return $this->belongsTo(SosAlert::class, 'sos_id'); }
}