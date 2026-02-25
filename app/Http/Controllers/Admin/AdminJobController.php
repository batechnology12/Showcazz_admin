<?php
// app/Http/Controllers/Admin/AdminJobController.php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Post;
use App\User;
use App\Company;
use App\UserMessage;
use App\Models\ChatType;
use App\JobSkill;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;
use Carbon\Carbon;
use Illuminate\Support\Str; // Add this for CSV export

class AdminJobController extends Controller
{
    /**
     * Constructor with admin auth middleware
     */
    public function __construct()
    {
        $this->middleware('auth:admin');
    }

    /**
     * Helper function to safely decode JSON fields
     */
    private function decodeField($field)
    {
        if (empty($field)) {
            return [];
        }
        
        if (is_array($field)) {
            return $field;
        }
        
        $decoded = json_decode($field, true);
        return is_array($decoded) ? $decoded : [];
    }

    /**
     * Get entity name by ID (user or company)
     */
    private function getEntityName($id)
    {
        $user = User::find($id);
        if ($user) {
            if ($user->usertype === 'company') {
                $company = Company::where('user_id', $user->id)->first();
                return $company ? $company->name : $user->name;
            }
            return trim(($user->first_name ?? '') . ' ' . ($user->last_name ?? '')) ?: $user->name;
        }
        
        $company = Company::find($id);
        return $company ? $company->name : 'Unknown';
    }

    /**
     * Get skill names from IDs
     */
    private function getSkillNames($skillIds)
    {
        if (empty($skillIds)) {
            return [];
        }

        if (is_array($skillIds)) {
            return JobSkill::whereIn('id', $skillIds)
                ->where('is_active', 1)
                ->pluck('job_skill', 'id')
                ->toArray();
        }

        if (is_string($skillIds)) {
            $ids = array_map('intval', explode(',', $skillIds));
            return JobSkill::whereIn('id', $ids)
                ->where('is_active', 1)
                ->pluck('job_skill', 'id')
                ->toArray();
        }

        return [];
    }

    /**
     * Display a listing of jobs and internships
     */
    public function index(Request $request)
    {
        $query = Post::with(['user'])
            ->whereIn('category_id', [5, 6]); // 5 = Mini Mission, 6 = Internship

        // Apply filters
        if ($request->filled('title')) {
            $query->where('title', 'LIKE', '%' . $request->title . '%');
        }

        if ($request->filled('type')) {
            if ($request->type == 'mini_mission') {
                $query->where('category_id', 5);
            } elseif ($request->type == 'internship') {
                $query->where('category_id', 6);
            }
        }

        if ($request->filled('work_mode')) {
            $query->where('work_mode', $request->work_mode);
        }

        if ($request->filled('location')) {
            $query->where('job_location', 'LIKE', '%' . $request->location . '%');
        }

        if ($request->filled('company')) {
            $query->where('company_name', 'LIKE', '%' . $request->company . '%');
        }

        if ($request->filled('status')) {
            if ($request->status == 'active') {
                $query->where('is_active', true)->where('is_published', true);
            } elseif ($request->status == 'inactive') {
                $query->where('is_active', false);
            } elseif ($request->status == 'expired') {
                $query->where('application_deadline', '<', Carbon::now());
            } elseif ($request->status == 'pending') {
                $query->where('is_active', true)->where('is_published', false);
            }
        }

        if ($request->filled('date_from')) {
            $query->whereDate('created_at', '>=', $request->date_from);
        }

        if ($request->filled('date_to')) {
            $query->whereDate('created_at', '<=', $request->date_to);
        }

        // Get stats
        $stats = [
            'total' => Post::whereIn('category_id', [5, 6])->count(),
            'mini_missions' => Post::where('category_id', 5)->count(),
            'internships' => Post::where('category_id', 6)->count(),
            'active' => Post::whereIn('category_id', [5, 6])
                ->where('is_active', true)
                ->where('is_published', true)
                ->count(),
            'expired' => Post::whereIn('category_id', [5, 6])
                ->where('application_deadline', '<', Carbon::now())
                ->count(),
            'total_applications' => UserMessage::where('chat_type_id', function($q) {
                $q->select('id')->from('chat_types')->where('slug', 'job_application');
            })->count(),
        ];

        $jobs = $query->orderBy('id', 'DESC')->paginate(20);

        // Add author names and application counts
        foreach ($jobs as $job) {
            $job->author_name = $this->getEntityName($job->user_id);
            $job->applications_count = UserMessage::where('listing_id', $job->id)
                ->where('chat_type_id', function($q) {
                    $q->select('id')->from('chat_types')->where('slug', 'job_application');
                })
                ->count();
        }

        return view('admin.job_new.index', compact('jobs', 'stats'));
    }

    /**
     * Show mini missions only
     */
    public function miniMissions(Request $request)
    {
        $request->merge(['type' => 'mini_mission']);
        return $this->index($request);
    }

    /**
     * Show internships only
     */
    public function internships(Request $request)
    {
        $request->merge(['type' => 'internship']);
        return $this->index($request);
    }

    /**
     * Show job details - FIXED the nested ternary operator
     */
    public function show($id)
    {
        $job = Post::with(['user'])
            ->whereIn('category_id', [5, 6])
            ->findOrFail($id);

        // Get author info - FIXED: Using helper method for image
        $author = null;
        $user = User::find($job->user_id);
        if ($user) {
            if ($user->usertype === 'company') {
                $company = Company::where('user_id', $user->id)->first();
                $author = [
                    'id' => $company->id ?? $user->id,
                    'name' => $company->name ?? $user->name,
                    'type' => 'company',
                    'image' => $company && $company->logo 
                        ? asset('company_logos/' . $company->logo) 
                        : ($user->image ? asset('user_images/' . $user->image) : null),
                ];
            } else {
                $author = [
                    'id' => $user->id,
                    'name' => trim(($user->first_name ?? '') . ' ' . ($user->last_name ?? '')) ?: $user->name,
                    'type' => 'user',
                    'image' => $user->image ? asset('user_images/' . $user->image) : null,
                ];
            }
        } else {
            $company = Company::find($job->user_id);
            if ($company) {
                $author = [
                    'id' => $company->id,
                    'name' => $company->name,
                    'type' => 'company',
                    'image' => $company->logo ? asset('company_logos/' . $company->logo) : null,
                ];
            }
        }

        // Parse skills - FIXED: Simplified logic
        $skills = [];
        if ($job->skills_required) {
            if (is_array($job->skills_required)) {
                $skills = $this->getSkillNames($job->skills_required);
            } else {
                $decoded = json_decode($job->skills_required, true);
                if (is_array($decoded)) {
                    $skills = $this->getSkillNames($decoded);
                } else {
                    $skills = $this->getSkillNames($job->skills_required);
                }
            }
        }

        // Parse images - FIXED: Using helper method
        $images = $this->decodeField($job->images);

        return view('admin.job_new.show', compact('job', 'author', 'skills', 'images'));
    }

    /**
     * View applications for a job
     */
    public function viewApplications($id)
    {
        $job = Post::whereIn('category_id', [5, 6])->findOrFail($id);

        $applications = UserMessage::where('listing_id', $job->id)
            ->where('chat_type_id', function($q) {
                $q->select('id')->from('chat_types')->where('slug', 'job_application');
            })
            ->with(['fromUser', 'chatSession'])
            ->orderBy('created_at', 'desc')
            ->paginate(20);

        // Get applicant details
        foreach ($applications as $app) {
            $app->applicant = $this->getApplicantDetails($app->from_id);
            
            // Parse JSON data - FIXED: Using helper method
            $app->application_data = $this->decodeField($app->json_data);
            
            // Get conversation count
            $app->conversation_count = UserMessage::where('chat_session_id', $app->chat_session_id)->count();
        }

        return view('admin.job_new.applications', compact('job', 'applications'));
    }

    /**
     * Get applicant details
     */
    private function getApplicantDetails($id)
    {
        $user = User::find($id);
        if ($user) {
            return [
                'id' => $user->id,
                'name' => trim(($user->first_name ?? '') . ' ' . ($user->last_name ?? '')) ?: $user->name,
                'email' => $user->email,
                'phone' => $user->phone,
                'image' => $user->image ? asset('user_images/' . $user->image) : null,
                'headline' => $user->headline,
                'type' => 'user'
            ];
        }

        $company = Company::find($id);
        if ($company) {
            return [
                'id' => $company->id,
                'name' => $company->name,
                'email' => $company->email,
                'phone' => $company->phone,
                'image' => $company->logo ? asset('company_logos/' . $company->logo) : null,
                'headline' => $company->description,
                'type' => 'company'
            ];
        }

        return null;
    }

    /**
     * Export applications as CSV
     */
    public function exportApplications($id)
    {
        $job = Post::whereIn('category_id', [5, 6])->findOrFail($id);

        $applications = UserMessage::where('listing_id', $job->id)
            ->where('chat_type_id', function($q) {
                $q->select('id')->from('chat_types')->where('slug', 'job_application');
            })
            ->orderBy('created_at', 'desc')
            ->get();

        $filename = 'applications_' . Str::slug($job->title) . '_' . date('Y-m-d') . '.csv';

        $headers = [
            'Content-Type' => 'text/csv',
            'Content-Disposition' => 'attachment; filename="' . $filename . '"',
        ];

        $columns = ['ID', 'Applicant', 'Email', 'Phone', 'Message', 'Portfolio Links', 'Resume Link', 'Applied Date'];

        $callback = function() use ($applications, $columns) {
            $file = fopen('php://output', 'w');
            fputcsv($file, $columns);

            foreach ($applications as $app) {
                $applicant = $this->getApplicantDetails($app->from_id);
                $data = $this->decodeField($app->json_data);

                fputcsv($file, [
                    $app->id,
                    $applicant['name'] ?? 'Unknown',
                    $applicant['email'] ?? '',
                    $applicant['phone'] ?? '',
                    $app->message_txt,
                    implode(' | ', $data['portfolio_links'] ?? []),
                    $data['resume_link'] ?? '',
                    $app->created_at->format('d M Y H:i'),
                ]);
            }

            fclose($file);
        };

        return response()->stream($callback, 200, $headers);
    }

    /**
     * Approve job
     */
    public function approveJob($id)
    {
        $job = Post::whereIn('category_id', [5, 6])->findOrFail($id);
        $job->is_active = true;
        $job->is_published = true;
        $job->save();

        return response()->json([
            'success' => true,
            'message' => 'Job approved successfully'
        ]);
    }

    /**
     * Reject job with reason
     */
    public function rejectJob(Request $request, $id)
    {
        $validator = Validator::make($request->all(), [
            'reason' => 'required|string|max:500',
        ]);

        if ($validator->fails()) {
            return response()->json(['success' => false, 'errors' => $validator->errors()], 422);
        }

        $job = Post::whereIn('category_id', [5, 6])->findOrFail($id);
        $job->is_active = false;
        $job->is_published = false;
        $job->save();

        return response()->json([
            'success' => true,
            'message' => 'Job rejected successfully',
            'reason' => $request->reason
        ]);
    }

    /**
     * Toggle featured status
     */
    public function toggleFeature($id)
    {
        $job = Post::whereIn('category_id', [5, 6])->findOrFail($id);
        $job->is_featured = !$job->is_featured;
        $job->save();

        return response()->json([
            'success' => true,
            'message' => 'Job featured status updated',
            'new_status' => $job->is_featured
        ]);
    }
}