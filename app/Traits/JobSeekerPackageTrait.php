<?php

namespace App\Traits;

use DB;
use Carbon\Carbon;
use App\User;

trait JobSeekerPackageTrait
{

    public function addJobSeekerPackage($user, $package)    
{
    if ($package->id == 9) {
        // For featured package
        $now = Carbon::now();
        $user->featured_package_start_at = $now->toDateTimeString();  // Converts to proper DATETIME format
        $user->featured_package_end_at = $now->addDays($package->package_num_days)->toDateTimeString();  // Converts to proper DATETIME format
        $user->is_featured = 1;
        $user->package_id = $package->id;  // Set package_id when package is active
        $user->update();
    } else {
        // For other packages
        $now = Carbon::now();
        $user->package_id = $package->id;  // Set the package_id for other packages
        $user->package_start_date = $now;
        $user->package_end_date = $now->addDays($package->package_num_days);
        $user->jobs_quota = $package->package_num_listings;
        $user->availed_jobs_quota = 0;
        $user->update();
    }
}

    



public function updateJobSeekerPackage($user, $package)
{
    $now = Carbon::now();

    if ($package->is_featured) { // Dynamically check if the package is featured
        $user->package_start_date = $now;
        $user->featured_package_end_at = $now->addDays($package->package_num_days);
        $user->is_featured = 1;
    } else {
        $package_end_date = $user->package_end_date;
        $current_end_date = Carbon::parse($package_end_date);

        $user->package_id = $package->id;
        $user->package_end_date = $current_end_date->addDays($package->package_num_days);
        $user->jobs_quota = ($user->jobs_quota - $user->availed_jobs_quota) + $package->package_num_listings;
        $user->availed_jobs_quota = 0;
    }

    $user->update();
}


}
