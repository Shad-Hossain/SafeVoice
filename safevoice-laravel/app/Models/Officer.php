<?php
namespace App\Models;
use Illuminate\Database\Eloquent\Model;

class Officer extends Model
{
    protected $fillable = ['officer_code','name','badge','department','is_active','assigned_cases'];
    protected $casts = ['is_active'=>'boolean'];
    public $timestamps = false;

    public function complaints() { return $this->hasMany(Complaint::class,'assigned_officer_code','officer_code'); }
}
