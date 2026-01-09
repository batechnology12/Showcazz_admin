<?php

namespace App\Http\Controllers\Admin;

use App\User;
use App\Company;
use App\Job;
use Carbon\Carbon;
use App\Http\Controllers\Controller;
use Illuminate\Http\Request;

class HomeController extends Controller
{

    /**
     * Create a new controller instance.
     *
     * @return void
     */
    public function __construct()
    {
        //$this->middleware('auth');
    }

    /**
     * Show the application dashboard.
     *
     * @return \Illuminate\Http\Response
     */
    public function index()
    {

       
        $today = Carbon::now();
        $totalActiveCompanies = Company::where('is_active', 1)->count();
        $totalInactiveCompanies = Company::where('is_active', 0)->count();
        $totalActiveUsers = User::where('is_active', 1)->count();
        $totalVerifiedUsers = User::where('verified', 1)->count();
        $totalTodaysUsers = User::where('created_at', 'like', $today->toDateString() . '%')->count();
        $totalTodaysCompanies = Company::where('created_at', 'like', $today->toDateString() . '%')->count();
        

dd('call');
        $documents = [
            'incorporation_or_formation_certificate',
            'valid_tax_clearance',
            'proof_of_address',
            'other_supporting_documents'
        ];
        
        // Get companies where is_active is 0 and at least one document is present
        $inActiveCompanies = Company::where('is_active', 0)
        ->where(function ($query) use ($documents) {
            foreach ($documents as $document) {
                $query->orWhereNotNull($document);
            }
        })
        ->get();


        $activeCompanies = Company::where('is_active', 1)->get();
       // $inActiveCompanies = Company::where('is_active', 0)->get();

        $recentUsers = User::orderBy('id', 'DESC')->take(25)->get();
        $totalActiveJobs = Job::where('is_active', 1)->count();
        $totalFeaturedJobs = Job::where('is_featured', 1)->count();
        $totalTodaysJobs = Job::where('created_at', 'like', $today->toDateString() . '%')->count();
        $recentJobs = Job::orderBy('id', 'DESC')->take(25)->get();
        return view('admin.home')
                        ->with('totalActiveUsers', $totalActiveUsers)
                        ->with('totalVerifiedUsers', $totalVerifiedUsers)
                        ->with('totalTodaysUsers', $totalTodaysUsers)
                        ->with('totalTodaysCompanies', $totalTodaysCompanies)
                        ->with('recentUsers', $recentUsers)
                        ->with('totalActiveJobs', $totalActiveJobs)
                        ->with('totalFeaturedJobs', $totalFeaturedJobs)
                        ->with('totalTodaysJobs', $totalTodaysJobs)
                        ->with('totalActiveCompanies', $totalActiveCompanies)
                        ->with('totalInactiveCompanies', $totalInactiveCompanies)
                        ->with('activeCompanies', $activeCompanies)
                        ->with('inActiveCompanies', $inActiveCompanies)
                        ->with('recentJobs', $recentJobs);
    }

}
