<?php

namespace App\Http\Controllers;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Auth;
use App\JobB;
use App\Company;
use App\Country;
use App\State;
use App\City;
use App\JobExperience;
use App\DegreeLevel;
use App\Gender;
use App\JobShift;
use App\JobType;
use App\FunctionalArea;
use App\SalaryPeriod;
use App\CareerLevel;

class ImportController extends Controller
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

    public function csvToArray($filename = '', $delimiter = ',')
{
    if (!file_exists($filename) || !is_readable($filename)) {
        return false;
    }

    $header = null;
    $data = array();

    if (($handle = fopen($filename, 'r')) !== false) {
        while (($row = fgetcsv($handle, 1000, $delimiter)) !== false) {
            // Skip empty rows
            if (empty(array_filter($row))) {
                continue;
            }

            if (!$header) {
                $header = $row;
            } else {
                // Pad the row with empty values if it is shorter than the header
                $row = array_pad($row, count($header), null);
                $data[] = array_combine($header, $row);
            }
        }
        fclose($handle);
    }

    return $data;
}



    public function store(Request $request)
    {
        $this->validate($request, [
            'csv_file' => 'required',
        ]);

        $file = $request->csv_file;

        $customerArr = $this->csvToArray($file);

        //dd($customerArr);
        $data = array();    
        if(null!==($customerArr)){

            

            foreach ($customerArr as $key => $jobData) {
                $email = @$jobData['company_id'];
                $name = @$jobData['company_name'];
                $company = Company::where('email', $email)->first();

                // Step 2: If the company doesn't exist, create a new one
                if (!$company && $email) {
                    // Extract name and email before '@'
                    if(!$name){
                       $name = explode('@', $email)[0]; 
                    }
                    

                    // Create a new company
                    $company = Company::create([
                        'name' => $name,
                        'email' => $email,
                        'password' => bcrypt(time()), // Hashed password using time()
                    ]);

                    $company->slug = Str::slug($company->name, '-') . '-' . $company->id;
                    $company->save();
                }else if ($name){
                    $company = Company::create([
                        'name' => $name,
                        'email' => $name.'@'.request()->getHost(),
                        'password' => bcrypt(time()), // Hashed password using time()
                    ]);

                    $company->slug = Str::slug($company->name, '-') . '-' . $company->id; 
                    $company->save();
                }

                $countryName = $jobData['country_id'];
                $country = Country::where('country', $countryName)->first();

                if (!$country) {
                    // Create a new country
                    $country = Country::create([
                        'country' => $countryName,
                        'is_default' => 1, // You can adjust this based on your requirements
                        'is_active' => 1, // You can adjust this based on your requirements
                        'lang' => 'en', // Set lang to 'en'
                    ]);
                }

                $country->country_id = $country->id;
                $country->update();

                // Step 2: Create a new State object or retrieve an existing one
                $stateName = $jobData['state_id'];
                $state = State::where('state', $stateName)->first();

                if (!$state) {
                    // Create a new state
                    $state = State::create([
                        'state' => $stateName,
                        'country_id' => $country->id,
                        'is_default' => 1, // You can adjust this based on your requirements
                        'is_active' => 1, // You can adjust this based on your requirements
                        'lang' => 'en', // Set lang to 'en'
                    ]);
                }
                $state->state_id = $state->id;
                $state->update();

                // Step 3: Create a new City object or retrieve an existing one
                $cityName = $jobData['city_id'];
                $city = City::where('city', $cityName)->first();

                if (!$city) {
                    // Create a new city
                    $city = City::create([
                        'city' => $cityName,
                        'state_id' => $state->id,
                        'is_default' => 1, // You can adjust this based on your requirements
                        'is_active' => 1, // You can adjust this based on your requirements
                        'lang' => 'en', // Set lang to 'en'
                    ]);
                }
                $city->city_id = $city->id;
                $city->update();

                $careerLevelName = $jobData['career_level_id'];
                $careerLevel = CareerLevel::where('career_level', $careerLevelName)->first();

                if (!$careerLevel) {
                    // Create a new career level
                    $careerLevel = CareerLevel::create([
                        'career_level' => $careerLevelName,
                        'is_default' => 1, // You can adjust this based on your requirements
                        'is_active' => 1, // You can adjust this based on your requirements
                        'lang' => 'en', // Set lang to 'en'
                    ]);
                }
                $careerLevel->career_level_id = $careerLevel->id;
                $careerLevel->update();

                $salaryPeriodName = $jobData['salary_period_id'];
                $salaryPeriod = SalaryPeriod::where('salary_period', $salaryPeriodName)->first();

                if (!$salaryPeriod) {
                    // Create a new salary period
                    $salaryPeriod = SalaryPeriod::create([
                        'salary_period' => $salaryPeriodName,
                        'is_default' => 1, // You can adjust this based on your requirements
                        'is_active' => 1, // You can adjust this based on your requirements
                        'lang' => 'en', // Set lang to 'en'
                    ]);
                }
                $salaryPeriod->salary_period_id = $salaryPeriod->id;
                $salaryPeriod->update();

                $functionalAreaName = $jobData['functional_area_id'];
                $functionalArea = FunctionalArea::where('functional_area', $functionalAreaName)->first();

                if (!$functionalArea) {
                    // Create a new functional area
                    $functionalArea = FunctionalArea::create([
                        'functional_area' => $functionalAreaName,
                        'is_default' => 1, // You can adjust this based on your requirements
                        'is_active' => 1, // You can adjust this based on your requirements
                        'lang' => 'en', // Set lang to 'en'
                    ]);
                }
                $functionalArea->functional_area_id = $functionalArea->id;
                $functionalArea->update();

                $jobTypeName = $jobData['job_type_id'];
                $jobType = JobType::where('job_type', $jobTypeName)->first();

                if (!$jobType) {
                    // Create a new job type
                    $jobType = JobType::create([
                        'job_type' => $jobTypeName,
                        'is_default' => 1, // You can adjust this based on your requirements
                        'is_active' => 1, // You can adjust this based on your requirements
                        'lang' => 'en', // Set lang to 'en'
                    ]);
                }
                $jobType->job_type_id = $jobType->id;
                $jobType->update();

                $jobShiftName = $jobData['job_shift_id'];
                $jobShift = JobShift::where('job_shift', $jobShiftName)->first();

                if (!$jobShift) {
                    // Create a new job shift
                    $jobShift = JobShift::create([
                        'job_shift' => $jobShiftName,
                        'is_default' => 1, // You can adjust this based on your requirements
                        'is_active' => 1, // You can adjust this based on your requirements
                        'lang' => 'en', // Set lang to 'en'
                    ]);
                }

                $jobShift->job_shift_id = $jobShift->id;
                $jobShift->update();

                $genderName = $jobData['gender_id'];
                $gender = Gender::where('gender', $genderName)->first();

                if (!$gender) {
                    // Create a new gender
                    $gender = Gender::create([
                        'gender' => $genderName,
                        'is_default' => 1, // You can adjust this based on your requirements
                        'is_active' => 1, // You can adjust this based on your requirements
                        'lang' => 'en', // Set lang to 'en'
                    ]);
                }

                $gender->gender_id = $gender->id;
                $gender->update();

                $degreeLevelName = $jobData['degree_level_id'];
                $degreeLevel = DegreeLevel::where('degree_level', $degreeLevelName)->first();

                if (!$degreeLevel) {
                    // Create a new degree level
                    $degreeLevel = DegreeLevel::create([
                        'degree_level' => $degreeLevelName,
                        'is_default' => 1, // You can adjust this based on your requirements
                        'is_active' => 1, // You can adjust this based on your requirements
                        'lang' => 'en', // Set lang to 'en'
                    ]);
                }
                $degreeLevel->degree_level_id = $degreeLevel->id;
                $degreeLevel->update();

                $jobExperienceName = $jobData['job_experience_id'];
                $jobExperience = JobExperience::where('job_experience', $jobExperienceName)->first();

                if (!$jobExperience) {
                    // Create a new job experience
                    $jobExperience = JobExperience::create([
                        'job_experience' => $jobExperienceName,
                        'is_default' => 1, // You can adjust this based on your requirements
                        'is_active' => 1, // You can adjust this based on your requirements
                        'lang' => 'en', // Set lang to 'en'
                    ]);
                }
                $jobExperience->job_experience_id = $jobExperience->id;
                $jobExperience->update();

                $is_valid_date = strtotime(@$jobData['salary_from']) !== false;

                // Step 3: Create a new job object with the updated company_id
                $job = new JobB();
                $job->company_id = $company->id;
                $job->title = @$jobData['title'];
                $job->description = @$jobData['description']?utf8_encode(@$jobData['description']):null;
                $job->benefits = @$jobData['benefits'];
                $job->country_id = $country->id;
                $job->state_id = $state->id;
                $job->city_id = $city->id;
                $job->is_freelance = @$jobData['is_freelance']?@$jobData['is_freelance']:0;
                $job->career_level_id = $careerLevel->id;
                $job->salary_from = @$jobData['salary_from']?@$jobData['salary_from']:1;
                $job->salary_to = @$jobData['salary_to']?@$jobData['salary_to']:1;
                $job->hide_salary = @$jobData['hide_salary']?@$jobData['hide_salary']:0;
                $job->salary_currency = @$jobData['salary_currency'];
                $job->salary_period_id = $salaryPeriod->id;
                $job->functional_area_id = $functionalArea->id;
                $job->job_type_id = $jobType->id;
                $job->job_shift_id = $jobShift->id;
                $job->num_of_positions = @$jobData['num_of_positions']?@$jobData['num_of_positions']:1;
                $job->gender_id = $gender->id;
                $job->expiry_date = @$jobData['expiry_date']?date('Y-m-d H:i:s',strtotime(@$jobData['expiry_date'])):date('Y-m-d H:i:s', strtotime('+6 months'));
                $job->degree_level_id = $degreeLevel->id;
                $job->job_experience_id = $jobExperience->id;
                $job->is_active = @$jobData['is_active'];
                $job->is_featured = @$jobData['is_featured'];
                $job->search = @$jobData['search'];
                $job->slug = @$jobData['slug'];
                $job->reference = @$jobData['reference'];
                $job->location = @$jobData['location'];
                $job->logo = @$jobData['logo'];
                $job->type = @$jobData['type'];
                $job->postal_code = @$jobData['postal_code'];
                $job->job_advertiser = @$jobData['job_advertiser'];
                $job->application_url = @$jobData['application_url'];
                $job->json_object = @$jobData['json_object'];
                $job->external_job = @$jobData['external_job']?@$jobData['external_job']:1;
                $job->job_link = @$jobData['job_link'];


                //dd($job);

                // Step 4: Save the job object to the database
                $job->save();
            }

        }

        /*if(null!==($data)){
            //dd($data);
            ModulesData::insert($data);
        }*/
        //dd($data);
        flash('Jobs has been imported!')->success();
        return redirect()->back();
        
        
    }

    public function remove_utf8_bom_head($text) {
        if(substr(bin2hex($text), 0, 6) === 'efbbbf') {
            $text = substr($text, 3);
        }
        return $text;
    }


}
