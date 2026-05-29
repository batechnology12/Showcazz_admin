<?php

namespace App\Helpers;

use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Route;

class AdminAuthorizationHelper
{

    public static function check($allowed_roles = ['SUP_ADM'])
    {
        if (null === Auth::guard('admin')->user()) {
            return false;
        }
        $user = Auth::guard('admin')->user();
        return $user->hasRole($allowed_roles);
    }

    public static function checkPermission($permission_name)
    {
        if (null === Auth::guard('admin')->user()) {
            return false;
        }
        $user = Auth::guard('admin')->user();
        return $user->hasPermission($permission_name);
    }

}
