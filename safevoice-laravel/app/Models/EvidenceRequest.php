<?php
namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class EvidenceRequest extends Model
{
    public $timestamps = false;

    protected $fillable = [
        'complaint_id',
        'user_id',
        'admin_note',
        'status',
        'deadline',
        'days',
        'responded_at',
        'skip_until',
    ];

    protected $casts = [
        'requested_at' => 'datetime',
        'deadline'     => 'datetime',
        'responded_at' => 'datetime',
        'skip_until'   => 'datetime',
    ];

    const CREATED_AT = 'requested_at';
    const UPDATED_AT = null;

    public function complaint()
    {
        return $this->belongsTo(Complaint::class, 'complaint_id', 'complaint_id');
    }

    public function user()
    {
        return $this->belongsTo(User::class);
    }
}