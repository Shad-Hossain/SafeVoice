<?php
namespace App\Models;
use Illuminate\Database\Eloquent\Model;

class ComplaintEvidence extends Model
{
    protected $fillable = ['complaint_id','file_path','file_name','uploaded_at'];
    public $timestamps = false;
}
