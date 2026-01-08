<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Applicants extends Model
{
    use HasFactory;
    protected $table = 'applicant';

    public function seeker()
    {
        return $this->belongsTo('App\User','user_id');
    }  
}
