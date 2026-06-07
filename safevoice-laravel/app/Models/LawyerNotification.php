<?php
namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class LawyerNotification extends Model
{
    protected $table = 'lawyer_notifications';
    public $timestamps = false;

    protected $fillable = [
        'lawyer_id', 'type', 'title', 'body', 'data', 'is_read', 'created_at',
    ];

    protected $casts = [
        'data'       => 'array',
        'is_read'    => 'boolean',
        'created_at' => 'datetime',
    ];

    public function lawyer()
    {
        return $this->belongsTo(Lawyer::class);
    }
}