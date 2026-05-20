<?php
namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class PiCaseAssignment extends Model
{
    protected $fillable = [
        'complaint_id',
        'pi_id',
        'token',
        'token_expires_at',
        'status',       // pending | accepted | rejected | expired
        'mailed_at',
        'acted_at',
        'action_ip',
    ];

    protected $casts = [
        'token_expires_at' => 'datetime',
        'mailed_at'        => 'datetime',
        'acted_at'         => 'datetime',
    ];

    public function pi()
    {
        return $this->belongsTo(PrivateInvestigator::class, 'pi_id');
    }

    public function complaint()
    {
        return $this->belongsTo(Complaint::class, 'complaint_id', 'complaint_id');
    }

    public function isExpired(): bool
    {
        return $this->token_expires_at->isPast();
    }
}
