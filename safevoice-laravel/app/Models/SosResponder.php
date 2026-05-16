<?php
namespace App\Models;
use Illuminate\Database\Eloquent\Model;

class SosResponder extends Model
{
    protected $fillable = ['sos_id','responder_id'];
    public $timestamps = false;
    const CREATED_AT = 'responded_at';
}
