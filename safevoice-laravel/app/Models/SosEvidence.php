<?php
namespace App\Models;
use Illuminate\Database\Eloquent\Model;

class SosEvidence extends Model
{
    protected $fillable = ['sos_id','file_path','file_type'];
    public $timestamps = false;
    const CREATED_AT = 'uploaded_at';
}
