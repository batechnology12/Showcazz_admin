<?php

namespace App\Http\Controllers;

use DB;
use Input;
use Form;
use App\Helpers\MiscHelper;
use App\User;
use Auth;
use App\Company;
use App\Job;
use App\CareerLevel;
use App\FunctionalArea;
use App\JobType;
use App\JobShift;
use App\DegreeLevel;
use App\JobExperience;
use App\JobSkill;
use App\JobSkillManager;
use App\Unlocked_users;
use App\SalaryPeriod;
use Hash;

use App\Helpers\DataArrayHelper;
use App\Http\Requests;
use Illuminate\Http\Request;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use App\Http\Controllers\Controller;
use App\Traits\CountryStateCity;

class AjaxController extends Controller
{

    use CountryStateCity;

    /**
     * Create a new controller instance.
     *
     * @return void
     */
    public function __construct()
    {
        //
    }

    public function filterDefaultStates(Request $request)
    {
        $country_id = $request->input('country_id');
        $state_id = $request->input('state_id');
        $new_state_id = $request->input('new_state_id', 'state_id');
        $states = DataArrayHelper::defaultStatesArray($country_id);
        $dd = Form::select('state_id', ['' => __('Select State')] + $states, $state_id, array('id' => $new_state_id, 'class' => 'form-control'));
        echo $dd;
    }

    public function filterDefaultCities(Request $request)
    {
        $state_id = $request->input('state_id');
        $city_id = $request->input('city_id');
        $cities = DataArrayHelper::defaultCitiesArray($state_id);
        $dd = Form::select('city_id', ['' => 'Select City'] + $cities, $city_id, array('id' => 'city_id', 'class' => 'form-control'));
        echo $dd;
    }

    /*     * ***************************************** */

    public function filterLangStates(Request $request)
    {
        $country_id = $request->input('country_id');
        $state_id = $request->input('state_id');
        $new_state_id = $request->input('new_state_id', 'state_id');
        $states = DataArrayHelper::langStatesArray($country_id);
        $dd = Form::select('state_id', ['' => __('Select State')] + $states, $state_id, array('id' => $new_state_id, 'class' => 'form-control'));
        echo $dd;
    }

    public function filterLangCities(Request $request)
    {
        $state_id = $request->input('state_id');
        $city_id = $request->input('city_id');
        $cities = DataArrayHelper::langCitiesArray($state_id);

        $dd = Form::select('city_id', ['' => 'Select City'] + $cities, $city_id, array('id' => 'city_id', 'class' => 'form-control'));
        echo $dd;
    }

    /*     * ***************************************** */

    public function filterStates(Request $request)
    {
        $country_id = $request->input('country_id');
        $state_id = $request->input('state_id');
        $new_state_id = $request->input('new_state_id', 'state_id');
        $states = DataArrayHelper::langStatesArray($country_id);
        $dd = Form::select('state_id[]', ['' => __('Select State')] + $states, $state_id, array('id' => $new_state_id, 'class' => 'form-control'));
        echo $dd;
    }

    public function filterCities(Request $request)
    {
        $state_id = $request->input('state_id');
        $city_id = $request->input('city_id');
        $cities = DataArrayHelper::langCitiesArray($state_id);

        $dd = Form::select('city_id[]', ['' => 'Select City'] + $cities, $city_id, array('id' => 'city_id', 'class' => 'form-control'));
        echo $dd;
    }

    /*     * ***************************************** */

    public function filterDegreeTypes(Request $request)
    {
        $degree_level_id = $request->input('degree_level_id');
        $degree_type_id = $request->input('degree_type_id');

        $degreeTypes = DataArrayHelper::langDegreeTypesArray($degree_level_id);

        $dd = Form::select('degree_type_id', ['' => 'Select degree type'] + $degreeTypes, $degree_type_id, array('id' => 'degree_type_id', 'class' => 'form-control'));
        echo $dd;
    }

    public function viewPublicProfile($id)
    {
        if(Auth::guard('admin')->user()){
            $user = User::findOrFail($id);

        $profileCv = $user->getDefaultCv();

        $jobSkills = DataArrayHelper::defaultJobSkillsArray();

        $companies = Company::get();



        return view('admin.user.applicant_profile')

                        ->with('user', $user)

                        ->with('profileCv', $profileCv)

                        ->with('page_title', $user->getName())

                        ->with('jobSkills', $jobSkills)

                        ->with('companies', $companies)

                        ->with('form_title', 'Contact ' . $user->getName());
                    }else{
                        return redirect(url('admin/login'));
                    }
        



    }

    public function companyProfile($id)

    {
         if(Auth::guard('admin')->user()){
        $countries = DataArrayHelper::defaultCountriesArray();

        $industries = DataArrayHelper::defaultIndustriesArray();

        $ownershipTypes = DataArrayHelper::defaultOwnershipTypesArray();

        $company = Company::findOrFail($id);

        return view('admin.company.detail')

                        ->with('company', $company)

                        ->with('countries', $countries)

                        ->with('industries', $industries)

                        ->with('ownershipTypes', $ownershipTypes);
                    }else{
                        return redirect(url('admin/login'));
                    }

    }

    public function jobDetail(Request $request, $id)
    {
         if(Auth::guard('admin')->user()){
        $job = Job::findOrFail($id);
        
        return view('admin.job.detail')
                        ->with('job', $job);
                    }else{
                        return redirect(url('admin/login'));
                    }
    }


    public function create(Request $request)
    {
        $validatedData = $request->validate([
            'name' => 'required|string|max:255',
            'email' => 'required|string|email|max:255|unique:users',
            'password' => 'required',
        ]);

        $user = new User();

        $user->first_name = $validatedData['name'];
        $user->last_name = $validatedData['name'];
        $user->name = $validatedData['name'];
        $user->email = $validatedData['email'];
        $user->password = Hash::make($validatedData['password']);
        $user->is_active = 1;
        $user->verified = 1;
        $user->email_verified_at = date('Y-m-d H:i:s');

        $user->save();

        //return response()->json(['message' => 'User created successfully', 'user' => $user], 201);
    }


}
