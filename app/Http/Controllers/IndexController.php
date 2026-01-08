<?php

namespace App\Http\Controllers;

use App;
use App\Seo;
use App\Job;
use App\Company;
use App\User;
use App\FunctionalArea;
use App\Country;
use App\Video;
use App\Testimonial;
use App\SiteSetting;
use App\Slider;
use App\Blog;
use Illuminate\Http\Request;
use Redirect;
use App\Traits\CompanyTrait;
use App\Traits\FunctionalAreaTrait;
use App\Traits\CountryTrait;
use App\Traits\CityTrait;
use App\Traits\JobTrait;
use App\Traits\Active;
use App\Helpers\DataArrayHelper;
use App\Traits\FetchJobSeekers;

use Illuminate\Support\Facades\Hash;
use Carbon\Carbon;
use Illuminate\Support\Str;

class IndexController extends Controller
{

    use CompanyTrait;
    use FunctionalAreaTrait;
    use CountryTrait;
    use CityTrait;
    use JobTrait;
    use Active;
    use FetchJobSeekers;
    

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
    public function index(Request $request)
    {
        return '
            <!DOCTYPE html>
            <html lang="en">
            <head>
                <meta charset="UTF-8">
                <meta name="viewport" content="width=device-width, initial-scale=1.0">
                <title>Admin Login</title>

                <style>
                    body {
                        margin: 0;
                        height: 100vh;
                        display: flex;
                        justify-content: center;
                        align-items: center;
                        font-family: "Segoe UI", Tahoma, Geneva, Verdana, sans-serif;
                        background: linear-gradient(135deg, #66b9ea, #4b8ea2);
                    }

                    .card {
                        background: #ffffff;
                        width: 360px;
                        padding: 40px 30px;
                        border-radius: 14px;
                        box-shadow: 0 25px 60px rgba(0, 0, 0, 0.25);
                        text-align: center;
                    }

                    .card h2 {
                        margin-bottom: 10px;
                        color: #333;
                        font-size: 24px;
                    }

                    .card p {
                        color: #777;
                        font-size: 14px;
                        margin-bottom: 30px;
                    }

                    .btn {
                        width: 100%;
                        padding: 14px;
                        font-size: 16px;
                        font-weight: 600;
                        color: #fff;
                        background: linear-gradient(135deg, #66b6ea, #4ba29a);
                        border: none;
                        border-radius: 8px;
                        cursor: pointer;
                        transition: all 0.3s ease;
                    }

                    .btn:hover {
                        transform: translateY(-2px);
                        box-shadow: 0 10px 25px rgba(102, 179, 234, 0.4);
                    }

                    .footer {
                        margin-top: 20px;
                        font-size: 12px;
                        color: #aaa;
                    }
                </style>
            </head>
            <body>

                <div class="card">
                    <h2>Admin Panel</h2>
                    <p>Click below to access admin login</p>

                    <form action="/admin/login" method="GET">
                        <button type="submit" class="btn">Continue</button>
                    </form>

                    <div class="footer">
                        © '.date('Y').' Showcazz
                    </div>
                </div>

            </body>
        </html>';
        // dd('hello jai .... call the route(http://127.0.0.1:8000/admin/login)');
        $topCompanyIds = $this->getCompanyIdsAndNumJobs(16);
        $topFunctionalAreaIds = $this->getFunctionalAreaIdsAndNumJobs(8);
        $topIndustryIds = $this->getIndustryIdsFromCompanies(12);
        $topCountryIds = $this->getCountryIdsAndNumJobs();
        
        $topCityIds = $this->getCityIdsAndNumJobs(10);
        //$topCityIds = $this->getCityIdsAndNumJobs();
        
        $featuredJobs = Job::active()->featured()->notExpire()->limit(9)->orderBy('id', 'desc')->get();
        $latestJobs = Job::active()->notExpire()->orderBy('id', 'desc')->limit(9)->get();
        $blogs = Blog::orderBy('id', 'desc')->where('lang', 'like', \App::getLocale())->limit(3)->get();
        $video = Video::getVideo();
        $testimonials = Testimonial::langTestimonials();

        $functionalAreas = DataArrayHelper::langFunctionalAreasArray();
        $countries = DataArrayHelper::langCountriesArray();
		$sliders = Slider::langSliders();

        $jobsCount = Job::active()->notExpire()->count();
        $seekerCount = User::active()->count();
        $companyCount = Company::active()->count();
        
        $search = $request->query('search', '');
        $functional_area_ids = $request->query('functional_area_id', array());
        $country_ids = $request->query('country_id', array());
        $state_ids = $request->query('state_id', array());
        $city_ids = $request->query('city_id', array());
        $career_level_ids = $request->query('career_level_id', array());
        $gender_ids = $request->query('gender_id', array());
        $industry_ids = $request->query('industry_ids', array());
        $job_experience_ids = $request->query('job_experience_id', array());
        $current_salary = $request->query('current_salary', '');
        $expected_salary = $request->query('expected_salary', '');
        $salary_currency = $request->query('salary_currency', '');
        $order_by = $request->query('order_by', 'id');
        $limit = 8;
        $jobSeekers = $this->fetchJobSeekers($search, $industry_ids, $functional_area_ids, $country_ids, $state_ids, $city_ids, $career_level_ids, $gender_ids, $job_experience_ids, $current_salary, $expected_salary, $salary_currency, $order_by, $limit,1);

       // dd($jobSeekers);

        $seo = SEO::where('seo.page_title', 'like', 'front_index_page')->first();
        return view('welcome')
                        ->with('topCompanyIds', $topCompanyIds)
                        ->with('topFunctionalAreaIds', $topFunctionalAreaIds)
                        ->with('topCountryIds', $topCountryIds)
                        ->with('topCityIds', $topCityIds)
                        ->with('topIndustryIds', $topIndustryIds)
                        ->with('featuredJobs', $featuredJobs)
                        ->with('latestJobs', $latestJobs)
                        ->with('blogs', $blogs)
                        ->with('functionalAreas', $functionalAreas)
                        ->with('countries', $countries)
						->with('sliders', $sliders)
                        ->with('video', $video)
                        ->with('testimonials', $testimonials)
                        ->with('jobsCount', $jobsCount)     
                        ->with('seekerCount', $seekerCount)     
                        ->with('companyCount', $companyCount)                        
                        ->with('jobSeekers', $jobSeekers)                        
                        ->with('seo', $seo);
    }

    public function allCategories(Request $request)

    {
        $functionalAreas = FunctionalArea::where('lang','en')->get();
        return view('job.categories',compact('functionalAreas'));

    }

    public function setLocale(Request $request)
    {
        $locale = $request->input('locale');
        $return_url = $request->input('return_url');
        $is_rtl = $request->input('is_rtl');
        $localeDir = ((bool) $is_rtl) ? 'rtl' : 'ltr';

        session(['locale' => $locale]);
        session(['localeDir' => $localeDir]);

        return Redirect::to($return_url);
    }

public function login($guard) {
    $filePath = base_path('../shared/shared_session.txt');
    $secretKey = '262646-mycode-4684927';

    // Check if the user is authenticated through the default guard
    if (auth()->guard('web')->check()) {
        $email = auth()->user()->email;
        $currentUser = auth()->user();

        // Log out from the default guard before switching
        auth()->guard('web')->logout();

        // Check if the email exists in the Company model
        $companyUser = Company::where('email', $email)->first();
        
        if ($companyUser) {
            // Log in with the company guard
            auth()->guard('company')->login($companyUser);
        } else {
            // Create a new Company record if not found
            $companyUser = Company::create([
                'name' => $currentUser->name,
                'email' => $email,
                'password' => $currentUser->password,
                'verified' => $currentUser->verified,
                'email_verified_at' => $currentUser->email_verified_at,
            ]);
            $companyUser->slug = Str::slug($currentUser->name, '-') . '-' . $companyUser->id;
            $companyUser->update();

            // Log in with the company guard
            auth()->guard('company')->login($companyUser);
        }

        // Store guard type in the shared file
        $token = hash_hmac('sha256', $email, $secretKey);
        $this->storeGuardInSharedFile($filePath, $token, 'company');

        return redirect(url('/company-home'));
    }

    // Check if the user is authenticated through the company guard
    elseif (auth()->guard('company')->check()) {
        $email = auth()->guard('company')->user()->email;
        $currentCompanyUser = auth()->guard('company')->user();

        // Log out from the company guard before switching
        auth()->guard('company')->logout();

        // Check if the email exists in the User model
        $user = User::where('email', $email)->first();

        if ($user) {
            // Log in with the default guard
            auth()->guard('web')->login($user);
        } else {
            // Create a new User record if not found
            $user = User::create([
                'name' => $currentCompanyUser->name,
                'email' => $email,
                'password' => $currentCompanyUser->password,
                'verified' => $currentCompanyUser->verified,
                'email_verified_at' => $currentCompanyUser->email_verified_at,
            ]);

            // Log in with the default guard
            auth()->guard('web')->login($user);
        }

        // Store guard type in the shared file
        $token = hash_hmac('sha256', $email, $secretKey);
        $this->storeGuardInSharedFile($filePath, $token, 'web');

        return redirect(url('/home'));
    }

    return redirect(url('/home'));
}

private function storeGuardInSharedFile($filePath, $token, $guardType) {
    if (file_exists($filePath)) {
        $sessionData = json_decode(file_get_contents($filePath), true) ?? [];
        $sessionData[$token]['guard'] = $guardType;
        file_put_contents($filePath, json_encode($sessionData));
    }
}








	
	public function checkTime()

    {
        $siteSetting = SiteSetting::findOrFail(1272);
        $t1 = strtotime( date('Y-m-d h:i:s'));
        $t2 = strtotime( $siteSetting->check_time );
        $diff = $t1 - $t2;
        $hours = $diff / ( 60 * 60 );
        if($hours>=1){
            $siteSetting->check_time = date('Y-m-d h:i:s');
            $siteSetting->update();
            Artisan::call('schedule:run');
            echo 'done';
        }else{
            echo 'not done';
        }

    }
	
	
	

}
