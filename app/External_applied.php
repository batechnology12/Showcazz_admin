<?php

namespace App;

use Illuminate\Database\Eloquent\Model;

class External_applied extends Model
{

    protected $table = 'external_applied';
    public $timestamps = true;
    protected $guarded = ['id'];
    //protected $dateFormat = 'U';
    protected $dates = ['created_at', 'updated_at'];

    

}
