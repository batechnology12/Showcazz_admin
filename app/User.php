<?php
namespace App;

use Auth;
use App\JobSkill;
use App\CompanyMessage;
use Illuminate\Notifications\Notifiable;
use Illuminate\Foundation\Auth\User as Authenticatable;
use App\Traits\CountryStateCity;
use App\Traits\CommonUserFunctions;
use Illuminate\Support\Carbon;
use Illuminate\Contracts\Auth\MustVerifyEmail;
use Laravel\Sanctum\HasApiTokens; 

class User extends Authenticatable implements MustVerifyEmail
{
    use Notifiable;
    use CountryStateCity;
    use CommonUserFunctions;
    use HasApiTokens;
    
    // Online/Offine constants
    const ONLINE_THRESHOLD_SECONDS = 30; // User considered online if active in last 10 seconds
    
    /**
     * The attributes that are mass assignable.
     *
     * @var array
     */
    protected $fillable = [
        'first_name', 
        'middle_name', 
        'last_name', 
        'name', 
        'email', 
        'password', 
        'email_verified_at',
        'usertype', 
        'verified',
        'unique_id',
        'visibility_control',
        'post_visibility_control',
        'message_visibility_control',
        'college_name',
        'school_name',
        'degree',
        'course_duration',
        'specialization',
        'portfolio_website',
        'area_of_interest_id',
        'headline',
        'phone',
        'is_active',
        'father_name',
        'date_of_birth',
        'gender_id',
        'marital_status_id',
        'nationality_id',
        'national_id_card_number',
        'country_id',
        'state_id',
        'city_id',
        'mobile_num',
        'job_experience_id',
        'career_level_id',
        'industry_id',
        'functional_area_id',
        'current_salary',
        'expected_salary',
        'salary_currency',
        'street_address',
        'verification_token',
        'provider',
        'provider_id',
        'image',
        'cover_image',
        'lang',
        'is_immediate_available',
        'num_profile_views',
        'package_id',
        'package_start_date',
        'package_end_date',
        'jobs_quota',
        'availed_jobs_quota',
        'search',
        'is_subscribed',
        'video_link',
        'is_featured',
        'featured_package_end_at',
        'featured_package_start_at',
        'start_year',
        'end_year',
        'last_activity',
        'currently_pursuing',
        'firebase_token',
        'push_notification'
    ];

    protected $casts = [
        'area_of_interest_id' => 'array',
        'date_of_birth' => 'date',
        'package_start_date' => 'date',
        'package_end_date' => 'date',
        'featured_package_start_at' => 'date',
        'featured_package_end_at' => 'date',
        'last_activity' => 'datetime', // Cast last_activity to datetime
    ];

    protected $dates = ['created_at', 'updated_at', 'date_of_birth', 'package_start_date', 'package_end_date'];
    
    /**
     * The attributes that should be hidden for arrays.
     *
     * @var array
     */
    protected $hidden = [
        'password', 'remember_token',
    ];
    
    /**
     * The "booting" method of the model.
     * Automatically update last_activity on every model retrieval/update
     */
    protected static function boot()
    {
        parent::boot();
        
        // Update last_activity whenever the model is retrieved (for API calls)
        static::retrieved(function ($user) {
            // Don't auto-save on retrieval to avoid too many writes
            // Instead, let the controller handle it explicitly
        });
        
        // You could also update on every save
        static::saving(function ($user) {
            // This will be called before save
        });
    }

    /**
     * Update the user's last activity timestamp
     * Call this method explicitly in controllers after user actions
     */
    public function updateLastActivity()
    {
        $this->last_activity = now();
        $this->saveQuietly(); // Save without triggering events
        return $this;
    }

    /**
     * Check if user is online based on last activity
     */
    public function isOnline()
    {
        if (!$this->last_activity) {
            return false;
        }
        
        return $this->last_activity->diffInSeconds(now()) < self::ONLINE_THRESHOLD_SECONDS;
    }

    /**
     * Get last activity formatted for human reading
     */
    public function getLastActivityFormattedAttribute()
    {
        if (!$this->last_activity) {
            return null;
        }
        
        $now = now();
        $diffInSeconds = $now->diffInSeconds($this->last_activity);
        $diffInMinutes = $now->diffInMinutes($this->last_activity);
        $diffInHours = $now->diffInHours($this->last_activity);
        $diffInDays = $now->diffInDays($this->last_activity);

        if ($diffInSeconds < 60) {
            return $diffInSeconds <= 5 ? 'Just now' : $diffInSeconds . ' seconds ago';
        } elseif ($diffInMinutes < 60) {
            return $diffInMinutes . ' ' . ($diffInMinutes == 1 ? 'minute ago' : 'minutes ago');
        } elseif ($diffInHours < 24) {
            return $diffInHours . ' ' . ($diffInHours == 1 ? 'hour ago' : 'hours ago');
        } elseif ($diffInDays < 7) {
            return $diffInDays . ' ' . ($diffInDays == 1 ? 'day ago' : 'days ago');
        } elseif ($diffInDays < 30) {
            $weeks = floor($diffInDays / 7);
            return $weeks . ' ' . ($weeks == 1 ? 'week ago' : 'weeks ago');
        } elseif ($diffInDays < 365) {
            $months = floor($diffInDays / 30);
            return $months . ' ' . ($months == 1 ? 'month ago' : 'months ago');
        } else {
            $years = floor($diffInDays / 365);
            return $years . ' ' . ($years == 1 ? 'year ago' : 'years ago');
        }
    }

    /**
     * Get online status as boolean attribute
     */
    public function getIsOnlineAttribute()
    {
        return $this->isOnline();
    }

    /**
     * Scope a query to only include online users
     */
    public function scopeOnline($query)
    {
        return $query->where('last_activity', '>=', now()->subSeconds(self::ONLINE_THRESHOLD_SECONDS));
    }

    /**
     * Scope a query to only include offline users
     */
    public function scopeOffline($query)
    {
        return $query->where(function($q) {
            $q->where('last_activity', '<', now()->subSeconds(self::ONLINE_THRESHOLD_SECONDS))
              ->orWhereNull('last_activity');
        });
    }

    public function profileSummary()
    {
        return $this->hasMany('App\ProfileSummary', 'user_id', 'id');
    }
    
    public function getProfileSummary($field = '')
    {
        if (null !== $this->profileSummary->first()) {
            $profileSummary = $this->profileSummary->first();
            if ($field != '') {
                return $profileSummary->$field;
            } else {
                return $profileSummary;
            }
        } else {
            return '';
        }
    }
    
    public function profileProjects()
    {
        return $this->hasMany('App\ProfileProject', 'user_id', 'id');
    }
    
    public function getProfileProjectsArray()
    {
        return $this->profileProjects->pluck('id')->toArray();
    }
    
    public function getDefaultCv()
    {
        $cv = ProfileCv::where('user_id', '=', $this->id)->where('is_default', '=', 1)->first();
        if (null === $cv)
            $cv = ProfileCv::where('user_id', '=', $this->id)->first();
        return $cv;
    }
    
    public function profileCvs()
    {
        return $this->hasMany('App\ProfileCv', 'user_id', 'id');
    }
    
    public function getProfileCvsArray()
    {
        return $this->profileCvs->pluck('id')->toArray();
    }
    
    public function countProfileCvs()
    {
        return $this->profileCvs->count();
    }
    
    public function profileExperience()
    {
        return $this->hasMany('App\ProfileExperience', 'user_id', 'id');
    }
    
    public function profileEducation()
    {
        return $this->hasMany('App\ProfileEducation', 'user_id', 'id');
    }
    
    public function profileSkills()
    {
        return $this->hasMany('App\ProfileSkill', 'user_id', 'id');
    }
    
    public function getProfileSkills()
    {
        return $this->profileSkills->get();
    }
    
    public function getProfileSkillsStr()
    {
        $profileSkills = $this->profileSkills()->get();
        $str = '';
        if ($profileSkills !== null) {
            foreach ($profileSkills as $profileSkill) {
                $jobSkill = JobSkill::where('job_skill_id', '=', $profileSkill->job_skill_id)->lang()->first();
                $str .= ' ' . $jobSkill->job_skill;
            }
        }
        return $str;
    }
    
    public function profileLanguages()
    {
        return $this->hasMany('App\ProfileLanguage', 'user_id', 'id');
    }
    
    public function favouriteJobs()
    {
        return $this->hasMany('App\FavouriteJob', 'user_id', 'id');
    }
    
    public function getFavouriteJobSlugsArray()
    {
        return $this->favouriteJobs->pluck('job_slug')->toArray();
    }
    
    public function isFavouriteJob($job_slug)
    {
        $return = false;
        if (Auth::check()) {
            $count = FavouriteJob::where('user_id', Auth::user()->id)->where('job_slug', 'like', $job_slug)->count();
            if ($count > 0)
                $return = true;
        }
        return $return;
    }
    
    public function favouriteCompanies()
    {
        return $this->hasMany('App\FavouriteCompany', 'user_id', 'id');
    }

    public function jobs()
    {
        return $this->hasMany('App\Job', 'company_id', 'id');
    }
    
    public function mutualConnectionsWith($userId)
    {
        // Get users this user follows
        $userFollowingIds = $this->acceptedFollowing()->pluck('following_id')->toArray();
        
        // Get users followed by target user
        $targetUser = User::find($userId);
        if (!$targetUser) return collect([]);
        
        $targetFollowingIds = $targetUser->acceptedFollowing()->pluck('following_id')->toArray();
        
        // Find mutual connections
        $mutualIds = array_intersect($userFollowingIds, $targetFollowingIds);
        
        return User::whereIn('id', $mutualIds)
                ->where('is_active', 1)
                ->get(['id', 'name', 'email', 'usertype', 'headline', 'image']);
    }

    public function isFollowingCompany($companyId)
    {
        return $this->followedCompanies()
                    ->where('company_id', $companyId)
                    ->exists();
    }

    public function isFollowedByUser($userId)
    {
        return $this->connectionsAsFollowing()
                    ->where('follower_id', $userId)
                    ->where('status', UserConnection::STATUS_ACCEPTED)
                    ->exists();
    }

    public function isFollowingUser($userId)
    {
        return $this->connectionsAsFollower()
                    ->where('following_id', $userId)
                    ->where('status', UserConnection::STATUS_ACCEPTED)
                    ->exists();
    }

    public function followedCompanies()
    {
        return $this->hasMany(FavouriteCompany::class, 'user_id')
                    ->with('company');
    }

    public function activities()
    {
        return $this->hasMany(UserActivity::class, 'user_id');
    }

    public function blockedByUsers()
    {
        return $this->hasMany(BlockedUser::class, 'blocked_id');
    }

    public function blockedUsers()
    {
        return $this->hasMany(BlockedUser::class, 'blocker_id');
    }

    public function pendingReceivedRequests()
    {
        return $this->connectionsAsFollowing()
                    ->where('status', UserConnection::STATUS_PENDING)
                    ->with('follower');
    }

    public function pendingSentRequests()
    {
        return $this->connectionsAsFollower()
                    ->where('status', UserConnection::STATUS_PENDING)
                    ->with('following');
    }

    public function acceptedFollowing()
    {
        return $this->connectionsAsFollower()
                    ->where('status', UserConnection::STATUS_ACCEPTED)
                    ->with('following');
    }

    public function acceptedFollowers()
    {
        return $this->connectionsAsFollowing()
                    ->where('status', UserConnection::STATUS_ACCEPTED)
                    ->with('follower');
    }

    public function connectionsAsFollowing()
    {
        return $this->hasMany(UserConnection::class, 'following_id');
    }

    public function connectionsAsFollower()
    {
        return $this->hasMany(UserConnection::class, 'follower_id');
    }

    public function getFavouriteCompanies()
    {
        return $this->favouriteCompanies->pluck('company_slug')->toArray();
    }
    
    public function isAppliedOnJob($job_id)
    {
        $return = false;
        if (Auth::check()) {
            $count = JobApply::where('user_id', Auth::user()->id)->where('job_id', '=', $job_id)->count();
            if ($count > 0)
                $return = true;
        }
        return $return;
    }
    
    public function appliedJobs()
    {
        return $this->hasMany('App\JobApply', 'user_id', 'id');
    }
    
    public function getAppliedJobIdsArray()
    {
        return $this->appliedJobs->pluck('job_id')->toArray();
    }

    public function getAppliedJob()
    {
        return $this->appliedJobs()->orderBy('id')->get();
    }
    
    public function isFavouriteCompany($company_slug)
    {
        $return = false;
        if (Auth::check()) {
            $count = FavouriteCompany::where('user_id', Auth::user()->id)->where('company_slug', 'like', $company_slug)->count();
            if ($count > 0)
                $return = true;
        }
        return $return;
    }
    
    public function printUserImage($width = 0, $height = 0)
    {
        $image = (string)$this->image;
        $image = (!empty($image)) ? $image : 'no-no-image.gif';
        return \ImgUploader::print_image("user_images/$image", $width, $height, '/admin_assets/no-image.png', $this->getName());
    }
    
    public function printUserCoverImage($width = 0, $height = 0)
    {
        $cover_image = (string) $this->cover_image;
        $cover_image = (!empty($cover_image)) ? $cover_image : 'no-no-image.gif';
        return \ImgUploader::print_image("user_images/$cover_image", $width, $height, '/admin_assets/no-cover.jpg', $this->name);
    }
    
    public function getName()
    {
        $html = '';
        if (!empty($this->first_name))
            $html .= $this->first_name;
        if (!empty($this->last_name))
            $html .= ' ' . $this->last_name;
        return $html;
    }
    
    public function getAge()
    {
        if (!empty((string)$this->date_of_birth) && null !== $this->date_of_birth) {
            // If date_of_birth is a string, convert it to Carbon
            $dob = is_string($this->date_of_birth) ? Carbon::parse($this->date_of_birth) : $this->date_of_birth;
            // Calculate and return the age
            return $dob->age;
        }
        return null; // or any default value if date_of_birth is not set
    }
    
    public function careerLevel()
    {
        return $this->belongsTo('App\CareerLevel', 'career_level_id', 'career_level_id');
    }
    
    public function getCareerLevel($field = '')
    {
        $careerLevel = $this->careerLevel()->lang()->first();
        if (null === $careerLevel) {
            $careerLevel = $this->careerLevel()->first();
        }
        if (null !== $careerLevel) {
            if (!empty($field))
                return $careerLevel->$field;
            else
                return $careerLevel;
        }
    }
    
    public function jobExperience()
    {
        return $this->belongsTo('App\JobExperience', 'job_experience_id', 'job_experience_id');
    }
    
    public function getJobExperience($field = '')
    {
        $jobExperience = $this->jobExperience()->lang()->first();
        if (null === $jobExperience) {
            $jobExperience = $this->jobExperience()->first();
        }
        if (null !== $jobExperience) {
            if (!empty($field))
                return $jobExperience->$field;
            else
                return $jobExperience;
        }
    }
    
    public function gender()
    {
        return $this->belongsTo('App\Gender', 'gender_id', 'gender_id');
    }
    
    public function getGender($field = '')
    {
        $gender = $this->gender()->lang()->first();
        if (null === $gender) {
            $gender = $this->gender()->first();
        }
        if (null !== $gender) {
            if (!empty($field))
                return $gender->$field;
            else
                return $gender;
        }
    }
    
    public function maritalStatus()
    {
        return $this->belongsTo('App\MaritalStatus', 'marital_status_id', 'marital_status_id');
    }
    
    public function getMaritalStatus($field = '')
    {
        $maritalStatus = $this->maritalStatus()->lang()->first();
        if (null === $maritalStatus) {
            $maritalStatus = $this->maritalStatus()->first();
        }
        if (null !== $maritalStatus) {
            if (!empty($field))
                return $maritalStatus->$field;
            else
                return $maritalStatus;
        }
    }
    
    public function followingCompanies()
    {
        return $this->hasMany('App\FavouriteCompany', 'user_id', 'id');
    }
    
    public function getFollowingCompaniesSlugArray()
    {
        return $this->followingCompanies()->pluck('company_slug')->toArray();
    }
    
    public function countFollowings()
    {
        return FavouriteCompany::where('user_id', '=', $this->id)->count();
    }
    
    public function countApplicantMessages()
    {
        return ApplicantMessage::where('user_id', '=', $this->id)->where('is_read', '=', 0)->count();
    }
    
    public function package()
    {
        return $this->belongsTo('App\Package', 'package_id', 'id');
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
    
    public function industry()
    {
        return $this->belongsTo('App\Industry', 'industry_id', 'industry_id');
    }
    
    public function getIndustry($field = '')
    {
        $industry = $this->industry()->lang()->first();
        if (null === $industry) {
            $industry = $this->industry()->first();
        }
        if (null !== $industry) {
            if (!empty($field))
                return $industry->$field;
            else
                return $industry;
        }
    }
    
    public function functionalArea()
    {
        return $this->belongsTo('App\FunctionalArea', 'functional_area_id', 'functional_area_id');
    }
    
    public function getFunctionalArea($field = '')
    {
        $functionalArea = $this->functionalArea()->lang()->first();
        if (null === $functionalArea) {
            $functionalArea = $this->functionalArea()->first();
        }
        if (null !== $functionalArea) {
            if (!empty($field))
                return $functionalArea->$field;
            else
                return $functionalArea;
        }
    }
    
    public function countUserMessages()
    {
        return CompanyMessage::where('seeker_id', '=', $this->id)->where('status', '=', 'unviewed')->where('type', '=', 'message')->count();
    }
    
    public function countMessages($id)
    {
        return CompanyMessage::where('seeker_id', '=', $this->id)->where('company_id', '=', $id)->where('status', '=', 'unviewed')->where('type', '=', 'message')->count();
    }

    public function areaOfInterest()
    {
        // Since area_of_interest_id is stored as JSON array, we need a custom relationship
        // If it's a single ID, use belongsTo. If it's multiple, create a pivot table.
        
        // For now, assuming it's a single ID relationship with JobTitle model
        return $this->belongsTo(JobTitle::class, 'area_of_interest_id', 'id');
    }

    public function followers()
    {
        return $this->belongsToMany(User::class, 'user_connections', 'following_id', 'follower_id')
                    ->wherePivot('status', UserConnection::STATUS_ACCEPTED)
                    ->withTimestamps();
    }

    public function following()
    {
        return $this->belongsToMany(User::class, 'user_connections', 'follower_id', 'following_id')
                    ->wherePivot('status', UserConnection::STATUS_ACCEPTED)
                    ->withTimestamps();
    }

    public function pendingFollowers()
    {
        return $this->belongsToMany(User::class, 'user_connections', 'following_id', 'follower_id')
                    ->wherePivot('status', UserConnection::STATUS_PENDING)
                    ->withTimestamps();
    }

    public function pendingFollowing()
    {
        return $this->belongsToMany(User::class, 'user_connections', 'follower_id', 'following_id')
                    ->wherePivot('status', UserConnection::STATUS_PENDING)
                    ->withTimestamps();
    }

    public function sentConnectionRequests()
    {
        return $this->hasMany(ConnectionRequest::class, 'requester_id');
    }

    public function receivedConnectionRequests()
    {
        return $this->hasMany(ConnectionRequest::class, 'receiver_id');
    }
}