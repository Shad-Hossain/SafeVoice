<?php
namespace App\Models;
use Illuminate\Foundation\Auth\User as Authenticatable;

class SuperAdmin extends Authenticatable
{
    protected $fillable = ['username','password_hash'];
    protected $hidden = ['password_hash'];
    public $timestamps = false;
}
