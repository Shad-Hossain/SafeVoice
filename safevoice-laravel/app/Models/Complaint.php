<?php
namespace App\Models;
use Illuminate\Database\Eloquent\Model;

class Complaint extends Model
{
    protected $fillable = [
        'complaint_id','type','incident_date','location','description',
        'is_anonymous','status','assigned_officer_code','submitted_at',
        'user_name','user_phone','user_email','user_address',
        'assigned_pi_id','pi_assigned_at','pi_email_sent','user_id','evidence_files',
    ];
    protected $casts = ['evidence_files'=>'array','is_anonymous'=>'boolean','pi_email_sent'=>'boolean'];
    public $timestamps = false;
    const CREATED_AT = 'submitted_at';
    const UPDATED_AT = 'updated_at';

    public function user()               { return $this->belongsTo(User::class); }
    public function officer()            { return $this->belongsTo(Officer::class,'assigned_officer_code','officer_code'); }
    public function evidence()           { return $this->hasMany(ComplaintEvidence::class,'complaint_id','complaint_id'); }
    public function privateInvestigator(){ return $this->belongsTo(PrivateInvestigator::class,'assigned_pi_id'); }
}
