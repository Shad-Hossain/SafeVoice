<?php
namespace App\Models;
use Illuminate\Database\Eloquent\Model;

class PiPayment extends Model
{
    protected $fillable = [
        'complaint_id','user_id','amount','payment_method',
        'sender_number','txn_id','status','initiated_at','confirmed_at',
    ];
    public $timestamps = false;
}
