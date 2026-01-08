<?php

namespace App;

use Illuminate\Database\Eloquent\Model;

class UnlockedUser extends Model
{
    protected $table = 'unlocked_users';
    public $timestamps = true;
    protected $guarded = ['id'];
    protected $dates = ['created_at', 'updated_at'];

    public function company()
    {
        return $this->belongsTo('App\Company', 'company_id', 'id');
    }

    public function getCompany($field = '')
    {
        if (null !== $company = $this->company()->first()) {
            return !empty($field) ? $company->$field : $company;
        }
    }
}
