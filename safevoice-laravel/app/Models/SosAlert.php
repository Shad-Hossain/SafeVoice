<?php
namespace App\Models;
use Illuminate\Database\Eloquent\Model;

class SosAlert extends Model
{
    protected $fillable = [
        'user_id','latitude','longitude','location_text',
        'crime_type','description','status','notification_sent','notified_count',
    ];
    protected $casts = ['notification_sent'=>'boolean'];
    public $timestamps = false;
    const CREATED_AT = 'created_at';

    public function user()          { return $this->belongsTo(User::class); }
    public function evidence()      { return $this->hasMany(SosEvidence::class,'sos_id'); }
    public function notifications() { return $this->hasMany(SosNotification::class,'sos_id'); }
}
