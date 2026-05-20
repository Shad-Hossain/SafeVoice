<?php
namespace App\Models;
use Illuminate\Database\Eloquent\Model;

class SosResponder extends Model
{
    protected $fillable = [
        'sos_id', 'responder_id',
        'evidence_path', 'file_type', 'evidence_status',
        'admin_note', 'evidence_submitted_at', 'verified_at',
    ];
    public $timestamps = false;
    const CREATED_AT = 'responded_at';

    public function sos()
    {
        return $this->belongsTo(SosAlert::class, 'sos_id');
    }

    public function responder()
    {
        return $this->belongsTo(User::class, 'responder_id');
    }
}
