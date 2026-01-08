<?php

namespace App\Traits;

use DB;
use File;
use ImgUploader;
use App\Country;
use Carbon\Carbon;

trait CountryTrait
{
    private function getCountryIdsAndNumJobs($limit = 8)
    {
        return DB::table('jobs')
                        ->select('country_id', DB::raw('COUNT(jobs.country_id) AS num_jobs'))
						->where('expiry_date', '>' ,Carbon::now())
						->where('is_active',1)
                        ->groupBy('country_id')
                        ->orderBy('num_jobs', 'DESC')
                        ->limit($limit)
                        ->get();
    }
}


