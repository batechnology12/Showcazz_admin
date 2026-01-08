<?php

namespace App;

use App;
use Illuminate\Database\Eloquent\Model;
use Carbon\Carbon;


class Package extends Model
{

    protected $table = 'packages';
    public $timestamps = true;
    protected $guarded = ['id'];
    protected $dates = ['created_at', 'updated_at'];

}
