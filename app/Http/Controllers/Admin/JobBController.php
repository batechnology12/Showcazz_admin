<?php
namespace App\Http\Controllers\Admin;
use Auth;
use DB;
use Input;
use Redirect;
use App\JobB as Job;
use App\JobApply;
use App\Company;
use App\Http\Requests;
use Illuminate\Http\Request;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use DataTables;
use App\Http\Controllers\Controller;
use App\Traits\JobTrait;
use App\Helpers\MiscHelper;
use App\Helpers\DataArrayHelper;
use Illuminate\Support\Str;



class JobBController extends Controller
{
    use JobTrait;
    /**
     * Create a new controller instance.
     *
     * @return void
     */
    public function __construct()
    {
        //
    }
    public function indexJobs()
    {
        $companies = DataArrayHelper::companiesArray();
        $countries = DataArrayHelper::defaultCountriesArray();
        return view('admin.jobB.index')
                        ->with('companies', $companies)
                        ->with('countries', $countries);
    }
    public function fetchJobsData(Request $request)
    {
        $jobs = Job::select([
                    'jobsb.id', 'jobsb.company_id', 'jobsb.title', 'jobsb.description', 'jobsb.country_id', 'jobsb.state_id', 'jobsb.city_id', 'jobsb.is_freelance', 'jobsb.career_level_id', 'jobsb.salary_from', 'jobsb.salary_to', 'jobsb.hide_salary', 'jobsb.functional_area_id', 'jobsb.job_type_id', 'jobsb.job_shift_id', 'jobsb.num_of_positions', 'jobsb.gender_id', 'jobsb.expiry_date', 'jobsb.degree_level_id', 'jobsb.job_experience_id', 'jobsb.is_active', 'jobsb.is_featured',
        ]);
        return Datatables::of($jobs)
                        ->filter(function ($query) use ($request) {
                            if ($request->has('company_id') && !empty($request->company_id)) {
                                $query->where('jobsb.company_id', '=', "{$request->get('company_id')}");
                            }
                            if ($request->has('title') && !empty($request->title)) {
                                $query->where('jobsb.title', 'like', "%{$request->get('title')}%");
                            }
                            if ($request->has('description') && !empty($request->description)) {
                                $query->where('jobsb.description', 'like', "%{$request->get('description')}%");
                            }
                            if ($request->has('country_id') && !empty($request->country_id)) {
                                $query->where('jobsb.country_id', '=', "{$request->get('country_id')}");
                            }
                            if ($request->has('state_id') && !empty($request->state_id)) {
                                $query->where('jobsb.state_id', '=', "{$request->get('state_id')}");
                            }
                            if ($request->has('city_id') && !empty($request->city_id)) {
                                $query->where('jobsb.city_id', '=', "{$request->get('city_id')}");
                            }
                            if ($request->has('is_active') && $request->is_active != -1) {
                                $query->where('jobsb.is_active', '=', "{$request->get('is_active')}");
                            }
                            if ($request->has('is_featured') && $request->is_featured != -1) {
                                $query->where('jobsb.is_featured', '=', "{$request->get('is_featured')}");
                            }
                        })
                        ->addColumn('checkbox', function ($jobs) {
                            return '<input class="checkboxes" type="checkbox" id="check_'.$jobs->id.'" name="job_ids[]" autocomplete="off" value="'.$jobs->id.'">';
                        })
                        ->addColumn('company_id', function ($jobs) {
                            return $jobs->getCompany('name');
                        })
                        ->addColumn('city_id', function ($jobs) {
                            return $jobs->getCity('city') . '(' . $jobs->getState('state') . '-' . $jobs->getCountry('country') . ')';
                        })
                        ->addColumn('description', function ($jobs) {
                            return strip_tags(Str::limit($jobs->description, 50, '...'));
                        })                        
                        ->addColumn('action', function ($jobs) {
                            /*                             * ************************* */
                            $activeTxt = 'Make Active';
                            $activeHref = 'makeActive(' . $jobs->id . ');';
                            $activeIcon = 'square-o';
                            if ((int) $jobs->is_active == 1) {
                                $activeTxt = 'Make InActive';
                                $activeHref = 'makeNotActive(' . $jobs->id . ');';
                                $activeIcon = 'check-square-o';
                            }
                            $featuredTxt = 'Make Featured';
                            $featuredHref = 'makeFeatured(' . $jobs->id . ');';
                            $featuredIcon = 'square-o';
                            if ((int) $jobs->is_featured == 1) {
                                $featuredTxt = 'Make Not Featured';
                                $featuredHref = 'makeNotFeatured(' . $jobs->id . ');';
                                $featuredIcon = 'check-square-o';
                            }
                            $list_candidates = JobApply::where('job_id', '=', $jobs->id)->count();
                            $title = '"'.$jobs->title.'"';
                            return '
				<div class="btn-group">
					<button class="btn blue dropdown-toggle" data-toggle="dropdown" aria-expanded="false">Action
						<i class="fa fa-angle-down"></i>
					</button>
					<ul class="dropdown-menu">
						<li>
							<a href="' . route('edit.jobB', ['id' => $jobs->id]) . '"><i class="fa fa-pencil" aria-hidden="true"></i>Edit</a>
						</li>	                        					
						<li>
							<a href="javascript:void(0);" onclick="deleteJob(' . $jobs->id . ', ' . $jobs->is_default . ');" class=""><i class="fa fa-trash-o" aria-hidden="true"></i>Delete</a>
						</li>
					</ul>
				</div>';
                        })
                        ->rawColumns(['action', 'company_id', 'city_id', 'description','checkbox'])
                        ->setRowId(function($jobs) {
                            return 'jobDtRow' . $jobs->id;
                        })
                        ->make(true);
        //$query = $dataTable->getQuery()->get();
        //return $query;
    }
    public function deleteJobs(Request $request)
    {
        $ids = $request->input('ids');
        $arr = explode(',', $ids);
        $user = Job::whereIn('id',$arr)->delete();
        echo 'done';
    }
    public function deleteJob(Request $request)
    {
        $id = $request->input('id');
        $user = Job::where('id',$id)->delete();
        echo 'done';
    }
    public function makeActiveJob(Request $request)
    {
        $id = $request->input('id');
        try {
            $job = Job::findOrFail($id);
            $job->is_active = 1;
            $job->update();
            echo 'ok';
        } catch (ModelNotFoundException $e) {
            echo 'notok';
        }
    }
    public function makeNotActiveJob(Request $request)
    {
        $id = $request->input('id');
        try {
            $job = Job::findOrFail($id);
            $job->is_active = 0;
            $job->update();
            echo 'ok';
        } catch (ModelNotFoundException $e) {
            echo 'notok';
        }
    }
    public function makeFeaturedJob(Request $request)
    {
        $id = $request->input('id');
        try {
            $job = Job::findOrFail($id);
            $job->is_featured = 1;
            $job->update();
            echo 'ok';
        } catch (ModelNotFoundException $e) {
            echo 'notok';
        }
    }
    public function makeNotFeaturedJob(Request $request)
    {
        $id = $request->input('id');
        try {
            $job = Job::findOrFail($id);
            $job->is_featured = 0;
            $job->update();
            echo 'ok';
        } catch (ModelNotFoundException $e) {
            echo 'notok';
        }
    }
    public function moveJobs()
{
    // Fetch all jobs from jobsb table
    $jobsToMove = DB::table('jobsb')->get();

    if ($jobsToMove->isEmpty()) {
        return response()->json([
            'message' => 'No jobs found to move.',
            'success' => false
        ]);
    }

    // Insert jobs one by one to get the new job ID and generate the correct slug
    foreach ($jobsToMove as $job) {
        $jobArray = (array) $job; // Convert stdClass object to array
        
        // Remove the old ID to avoid conflicts (if jobs table uses auto-increment)
        unset($jobArray['id']);

        // Insert job into jobs table without slug first
        $newJobId = DB::table('jobs')->insertGetId($jobArray);

        // Generate the correct slug with the new job ID
        $slug = Str::slug($job->title) . '-' . $newJobId;

        // Update the slug in the jobs table
        DB::table('jobs')->where('id', $newJobId)->update(['slug' => $slug]);
    }

    // Delete the moved jobs from jobsb table
    DB::table('jobsb')->delete();

    return response()->json([
        'message' => count($jobsToMove) . ' jobs moved successfully with slugs!',
        'success' => true
    ]);
}


}
