<?php



namespace App;



use Auth;

use App;

use Carbon\Carbon;
use Laravel\Sanctum\HasApiTokens;
use App\Traits\Active;
use App\Traits\Featured;
use App\Traits\JobTrait;
use App\Traits\CountryStateCity;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Notifications\Notifiable;
use App\Notifications\CompanyResetPassword;
use Illuminate\Foundation\Auth\User as Authenticatable;



class Company extends Authenticatable

{

    use HasApiTokens, Notifiable;
    use Active;
    use Featured;
    use Notifiable;
    use JobTrait;
    use CountryStateCity;



    protected $table = 'companies';

    public $timestamps = true;

    protected $guarded = ['id'];

    //protected $dateFormat = 'U';

    protected $dates = ['created_at', 'updated_at', 'package_start_date', 'package_end_date'];

    // protected $fillable = [
    //     'name', 'email', 'password', 'slug', 'email_verified_at', 'verified',
    //     'cvs_package_id', 'cvs_package_start_date', 'cvs_package_end_date', 
    //     'cvs_quota', 'availed_cvs_quota'
    // ];


    // In app/Company.php
    protected $fillable = [
        'name', 
        'email', 
        'password', 
        'slug', 
        'email_verified_at', 
        'verified',
        'unique_id',
        'visibility_control',
        'website',
        'linkedin_url',
        'gst_number',
        'upi_id',
        'ceo',
        'industry_id',
        'ownership_type_id',
        'description',
        'location',
        'no_of_offices',
        'no_of_employees',
        'established_in',
        'fax',
        'phone',
        'logo',
        'country_id',
        'state_id',
        'city_id',
        'is_active',
        'is_featured',
        'verification_token',
        'map',
        'facebook',
        'twitter',
        'linkedin',
        'google_plus',
        'pinterest',
        'package_id',
        'package_start_date',
        'package_end_date',
        'jobs_quota',
        'availed_jobs_quota',
        'is_subscribed',
        'cvs_package_id',
        'cvs_package_start_date',
        'cvs_package_end_date',
        'cvs_quota',
        'availed_cvs_quota',
        'availed_cvs_ids',
        'payment_method',
        'type',
        'count',
        'json_object',
        'incorporation_or_formation_certificate',
        'incorporation_or_formation_certificate_status',
        'valid_tax_clearance',
        'valid_tax_clearance_status',
        'proof_of_address',
        'proof_of_address_status',
        'other_supporting_documents',
        'other_supporting_documents_status',
        'incorporation_or_formation_certificate_comment',
        'valid_tax_clearance_comment',
        'proof_of_address_comment',
        'other_supporting_documents_comment',
        'registration_number',
        'name_of_legal_representative',
        'identity_number',
        'contact_name',
        'contact_email'
    ];
    
    

    protected $hidden = [

        'password', 'remember_token',

    ];



    public function sendPasswordResetNotification($token)

    {

        $this->notify(new CompanyResetPassword($token));

    }



    public function printCompanyImage($width = 0, $height = 0)

    {

        $logo = (string)$this->logo;

        $logo = (null!==($logo) && !empty($logo)) ? $logo : 'no-image.png';
        return \ImgUploader::print_image("company_logos/$logo", $width, $height, '/admin_assets/no-image.png', $this->name);

    }
    
      public function printUserImage($width = 0, $height = 0)

    {



        $logo = (string)$this->logo;

        $logo = (null!==($logo) && !empty($logo)) ? $logo : 'no-image.png';
        return \ImgUploader::print_image("company_logos/$logo", $width, $height, '/admin_assets/no-image.png', $this->name);

    }



    public function jobs()

    {

        return $this->hasMany('App\Job', 'company_id', 'id');

    }



    public function openJobs()

    {

        return Job::where('company_id', '=', $this->id)->notExpire();

    }



    public function getOpenJobs()

    {

        return $this->openJobs()->get();

    }



    public function countOpenJobs()

    {

        return $this->openJobs()->count();

    }



    public function industry()

    {

        return $this->belongsTo('App\Industry', 'industry_id', 'id');

    }



    public function getIndustry($field = '')

    {

        $industry = $this->industry()->lang()->first();

        if (null === $industry) {

            $industry = $this->industry()->first();

        }

        if (null !== $industry) {

            if (!empty($field)) {

                return $industry->$field;

            } else {

                return $industry;

            }

        }

    }



    public function ownershipType()

    {

        return $this->belongsTo('App\OwnershipType', 'ownership_type_id', 'id');

    }



    public function getOwnershipType($field = '')

    {

        $ownershipType = $this->ownershipType()->lang()->first();

        if (null === $ownershipType) {

            $ownershipType = $this->ownershipType()->first();

        }

        if (null !== $ownershipType) {

            if (!empty($field)) {

                return $ownershipType->$field;

            } else {

                return $ownershipType;

            }

        }

    }



    public function countFollowers()

    {

        return FavouriteCompany::where('company_slug', 'like', $this->slug)->count();

    }



    public function getFollowerIdsArray()

    {

        return FavouriteCompany::where('company_slug', 'like', $this->slug)->pluck('user_id')->toArray();

    }



    public function countCompanyMessages()

    {

        return CompanyMessage::where('company_id', '=', $this->id)->where('status', '=', 'unviewed')->where('type', '=', 'reply')->count();

    }

    public function countMessages($id)

    {

        return CompanyMessage::where('company_id', '=', $this->id)->where('seeker_id', '=', $id)->where('type', 'reply')->where('status', '=', 'unviewed')->count();

    }



    public function getSocialNetworkHtml()

    {

        $html = '';

        if (!empty($this->facebook))

            $html .= '<a href="' . $this->facebook . '" target="_blank"><i class="fab fa-facebook" aria-hidden="true"></i></a>';



        if (!empty($this->twitter))

            $html .= '<a href="' . $this->twitter . '" target="_blank"><i class="fab fa-twitter" aria-hidden="true"></i></a>';



        if (!empty($this->linkedin))

            $html .= '<a href="' . $this->linkedin . '" target="_blank"><i class="fab fa-linkedin" aria-hidden="true"></i></a>';



        // if (!empty($this->google_plus))

        //     $html .= '<a href="' . $this->google_plus . '" target="_blank"><i class="fab fa-google-plus" aria-hidden="true"></i></a>';



        if (!empty($this->pinterest))

            $html .= '<a href="' . $this->pinterest . '" target="_blank"><i class="fab fa-pinterest" aria-hidden="true"></i></a>';



        return $html;

    }



    public function isFavouriteApplicant($user_id, $job_id, $company_id)

    {

        $return = false;

        if (Auth::guard('company')->check()) {

            $count = FavouriteApplicant::where('user_id', $user_id)

                ->where('job_id', $job_id)

                ->where('company_id', $company_id)

                ->count();

            if ($count > 0)

                $return = true;

        }

        return $return;

    }


    public function isHiredApplicant($user_id, $job_id, $company_id)

    {

        $return = false;

        if (Auth::guard('company')->check()) {

            $count = FavouriteApplicant::where('user_id', $user_id)

                ->where('job_id', $job_id)

                ->where('company_id', $company_id)

                ->where('status', 'hired')

                ->count();

            if ($count > 0)

                $return = true;

        }

        return $return;

    }



    public function package()

    {

        return $this->hasOne('App\Package', 'id', 'package_id');

    }



    public function getPackage($field = '')

    {

        $package = $this->package()->first();

        if (null !== $package) {

            if (!empty($field)) {

                return $package->$field;

            } else {

                return $package;

            }

        }

    }


    public function cvsPackageRelation()
    {
        return $this->hasOne('App\Package', 'id', 'cvs_package_id');
    }

    public function cvs_getPackage($field = '')
    {
        $package = $this->cvsPackageRelation()->first();

        if ($package !== null) {
            return !empty($field) ? $package->$field : $package;
        }

        return null; // Return null if no package found
    }


    // Add to Company.php
    public function followers()
    {
        return $this->hasMany(FavouriteCompany::class, 'company_id')
                    ->with('user');
    }


    public function getFollowersCountAttribute()
    {
        return $this->followers()->count();
    }

    /**
     * Check if user follows this company
     */
    public function isFollowedByUser($userId = null)
    {
        if (!$userId && auth()->check()) {
            $userId = auth()->user()->id;
        }
        
        if (!$userId) {
            return false;
        }
        
        return $this->followers()
                    ->where('user_id', $userId)
                    ->exists();
    }

    /**
     * Get suggested followers (users who might be interested)
     */
    public function suggestedFollowers()
    {
        // Users in same industry or with matching interests
        return User::where(function($query) {
                // Add your suggestion logic here
                // Example: same industry, similar interests, etc.
            })
            ->whereNotIn('id', $this->followers()->pluck('user_id')->toArray())
            ->where('is_active', 1)
            ->limit(10)
            ->get();
    }

    public function favouriteByUsers()
    {
        return $this->hasMany(FavouriteCompany::class, 'company_id');
    }

    public function followStats()
    {
        return $this->hasOne(CompanyFollowStat::class, 'company_id');
    }

    /**
     * Get followers count (with caching)
     */
    // public function getFollowersCountAttribute()
    // {
    //     if ($this->relationLoaded('followStats')) {
    //         return $this->followStats ? $this->followStats->followers_count : 0;
    //     }
        
    //     return FavouriteCompany::where('company_id', $this->id)->count();
    // }

    /**
     * Check if user follows this company
     */
    // public function isFollowedByUser($userId = null)
    // {
    //     if (!$userId && auth()->check()) {
    //         $userId = auth()->user()->id;
    //     }
        
    //     if (!$userId) {
    //         return false;
    //     }
        
    //     return FavouriteCompany::where('company_id', $this->id)
    //                         ->where('user_id', $userId)
    //                         ->exists();
    // }




}

