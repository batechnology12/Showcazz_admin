<?php

namespace App\Http\Controllers;

use App\Traits\Cron;
use App\Http\Controllers\Controller;

class CronController extends Controller
{

    use Cron;

    public function checkPackageValidity()
    {
        $this->runCheckPackageValidity();
    }

}
