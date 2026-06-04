<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class LegalCaseMessage extends Model
{
    protected $table = 'legal_case_messages';

    public $timestamps = false;

    protected $fillable = [
        'legal_case_id', 'sender_type', 'sender_id',
        'message', 'attachment_path', 'is_read'
    ];

    protected $casts = [
        'is_read'    => 'boolean',
        'created_at' => 'datetime',
    ];

    public function legalCase()
    {
        return $this->belongsTo(LegalCase::class, 'legal_case_id');
    }

    public function getSenderAttribute()
    {
        if ($this->sender_type === 'user') {
            return User::find($this->sender_id);
        }
        return Lawyer::find($this->sender_id);
    }
}
