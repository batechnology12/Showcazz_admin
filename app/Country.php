<?php

namespace App;

use App\Traits\Lang;
use App\Traits\IsDefault;
use App\Traits\Active;
use App\Traits\Sorted;
use Illuminate\Database\Eloquent\Model;

class Country extends Model
{
    use Lang, IsDefault, Active, Sorted;

    protected $table = 'countries';
    public $timestamps = true;
    protected $guarded = ['id'];
    protected $dates = ['created_at', 'updated_at'];

    // Add method to get the Country by ID
    public static function getCountryById($id)
    {
        // Fetch country by ID with language and active status filters
        $country = self::where('countries.id', '=', $id)->lang()->active()->first();

        // Fallback if no language-specific or active country is found
        if (null === $country) {
            $country = self::where('countries.id', '=', $id)->active()->first();
        }

        return $country;
    }
}
