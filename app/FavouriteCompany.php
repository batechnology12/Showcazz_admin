<?php

namespace App;

use Illuminate\Database\Eloquent\Model;

class FavouriteCompany extends Model
{
    protected $table = 'favourites_company';
    public $timestamps = true;
    protected $guarded = ['id'];
    protected $dates = ['created_at', 'updated_at'];
    
    protected $fillable = [
        'user_id',
        'company_slug',
        'company_id',
        'created_at',
        'updated_at'
    ];
    
    // Get user who follows
    public function user()
    {
        return $this->belongsTo(User::class, 'user_id');
    }
    
    // Get company being followed
    public function company()
    {
        return $this->belongsTo(Company::class, 'company_id');
    }
}