<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Post;
use App\User;
use App\UserMessage;
use App\Models\ChatType;
use App\JobSkill;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;
use Carbon\Carbon;
use Illuminate\Support\Str;

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
     * Get entity name by ID from users table
     */
    private function getEntityName($id)
    {
        $user = User::find($id);
        if ($user) {
            if ($user->usertype === 'company') {
                return $user->company_name ?? $user->name ?? 'Unknown Company';
            }
            return trim(($user->first_name ?? '') . ' ' . ($user->last_name ?? '')) ?: ($user->name ?? 'Unknown User');
        }
        
        return 'Unknown';
    }

    /**
     * Get skill names from IDs
     */
    private function getSkillNames($skillIds)
    {
        if (empty($skillIds)) {
            return [];
        }

        $ids = [];
        
        if (is_array($skillIds)) {
            $ids = $skillIds;
        } elseif (is_string($skillIds)) {
            // Try to decode JSON first
            $decoded = json_decode($skillIds, true);
            if (is_array($decoded)) {
                $ids = $decoded;
            } else {
                // Fallback to comma-separated
                $ids = array_map('intval', explode(',', $skillIds));
            }
        }

        if (empty($ids)) {
            return [];
        }

        return JobSkill::whereIn('id', $ids)
            ->where('is_active', 1)
            ->pluck('job_skill', 'id')
            ->toArray();
    }

    /**
     * Get author info from user
     */
    private function getAuthorInfo($userId)
    {
        $user = User::find($userId);
        
        if (!$user) {
            return null;
        }

        if ($user->usertype === 'company') {
            return [
                'id' => $user->id,
                'name' => $user->company_name ?? $user->name,
                'type' => 'company',
                'image' => $user->company_logo ? asset('company_logos/' . $user->company_logo) : 
                           ($user->image ? asset('user_images/' . $user->image) : null),
                'email' => $user->email,
                'phone' => $user->phone,
            ];
        }

        return [
            'id' => $user->id,
            'name' => trim(($user->first_name ?? '') . ' ' . ($user->last_name ?? '')) ?: ($user->name ?? 'Unknown User'),
            'type' => 'user',
            'image' => $user->image ? asset('user_images/' . $user->image) : null,
            'email' => $user->email,
            'phone' => $user->phone,
        ];
    }

    /**
     * Get applicant details from users table
     */
    private function getApplicantDetails($id)
    {
        $user = User::find($id);
        if ($user) {
            if ($user->usertype === 'company') {
                return [
                    'id' => $user->id,
                    'name' => $user->company_name ?? $user->name,
                    'email' => $user->email,
                    'phone' => $user->phone,
                    'image' => $user->company_logo ? asset('company_logos/' . $user->company_logo) : 
                               ($user->image ? asset('user_images/' . $user->image) : null),
                    'headline' => $user->company_description ?? $user->headline,
                    'type' => 'company'
                ];
            }
            
            return [
                'id' => $user->id,
                'name' => trim(($user->first_name ?? '') . ' ' . ($user->last_name ?? '')) ?: ($user->name ?? 'Unknown User'),
                'email' => $user->email,
                'phone' => $user->phone,
                'image' => $user->image ? asset('user_images/' . $user->image) : null,
                'headline' => $user->headline,
                'type' => 'user'
            ];
        }

        return null;
    }

    /**
     * Display a listing of jobs and internships
     */
    public function index(Request $request)
    {
        $query = Post::with(['user'])
            ->whereIn('category_id', [5, 6, 7]); // 5 = Mini Mission, 6 = Internship

        // Apply filters
        if ($request->filled('title')) {
            $query->where('title', 'LIKE', '%' . $request->title . '%');
        }

        if ($request->filled('type')) {
            if ($request->type == 'mini_mission') {
                $query->where('category_id', 5);
            } elseif ($request->type == 'internship') {
                $query->where('category_id', 6);
            }elseif ($request->type == 'fresherrole') {
                $query->where('category_id', 7);
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
            'total' => Post::whereIn('category_id', [5, 6, 7])->count(),
            'mini_missions' => Post::where('category_id', 5)->count(),
            'internships' => Post::where('category_id', 6)->count(),
            'fresher_roles' => Post::where('category_id', 7)->count(),
            'active' => Post::whereIn('category_id', [5, 6, 7])
                ->where('is_active', true)
                ->where('is_published', true)
                ->count(),
            'expired' => Post::whereIn('category_id', [5, 6, 7])
                ->where('application_deadline', '<', Carbon::now())
                ->count(),
            'total_applications' => UserMessage::where('chat_type_id', function($q) {
                $q->select('id')->from('chat_types')->where('slug', 'job_application');
            })->count(),
        ];

        $jobs = $query->orderBy('id', 'DESC')->paginate(20);

        // Add author names and application counts
        foreach ($jobs as $job) {
            $author = $job->user;
            $job->author_name = $this->getEntityName($job->user_id);
            $job->author_type = $author ? $author->usertype : 'unknown';
            
            // If post company_name is empty, use user's company name
            if (empty($job->company_name) && $author && $author->usertype === 'company') {
                $job->company_name = $author->company_name ?? $author->name;
            }
            
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
     * Show job details
     */
    public function show($id)
    {
        $job = Post::with(['user'])
            ->whereIn('category_id', [5, 6, 7])
            ->findOrFail($id);

        // Get author info using helper method
        $author = $this->getAuthorInfo($job->user_id);

        // Parse skills
        $skills = [];
        if ($job->skills_required) {
            $skillIds = $this->decodeField($job->skills_required);
            $skills = $this->getSkillNames($skillIds);
        }

        // Parse images
        $images = $this->decodeField($job->images);
        
        // Get application count
        $applicationsCount = UserMessage::where('listing_id', $job->id)
            ->where('chat_type_id', function($q) {
                $q->select('id')->from('chat_types')->where('slug', 'job_application');
            })
            ->count();

        return view('admin.job_new.show', compact('job', 'author', 'skills', 'images', 'applicationsCount'));
    }

    /**
     * View applications for a job
     */
    public function viewApplications($id)
    {
        $job = Post::whereIn('category_id', [5, 6, 7])->findOrFail($id);

        $applications = UserMessage::where('listing_id', $job->id)
            ->where('chat_type_id', function($q) {
                $q->select('id')->from('chat_types')->where('slug', 'job_application');
            })
            ->with(['chatSession'])
            ->orderBy('created_at', 'desc')
            ->paginate(20);

        // Get applicant details
        foreach ($applications as $app) {
            $app->applicant = $this->getApplicantDetails($app->from_id);
            
            // Parse JSON data
            $app->application_data = $this->decodeField($app->json_data);
            
            // Get conversation count
            $app->conversation_count = UserMessage::where('chat_session_id', $app->chat_session_id)->count();
            
            // Get unread count for admin
            $app->unread_count = UserMessage::where('chat_session_id', $app->chat_session_id)
                ->where('to_id', auth()->guard('admin')->user()->id)
                ->where('is_read', false)
                ->count();
        }

        return view('admin.job_new.applications', compact('job', 'applications'));
    }

    /**
     * Export applications as CSV
     */
    public function exportApplications($id)
    {
        $job = Post::whereIn('category_id', [5, 6, 7])->findOrFail($id);

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

        $columns = ['ID', 'Applicant', 'Email', 'Phone', 'Message', 'Portfolio Links', 'Resume Link', 'Additional Notes', 'Applied Date'];

        $callback = function() use ($applications, $columns) {
            $file = fopen('php://output', 'w');
            fputcsv($file, $columns);

            foreach ($applications as $app) {
                $applicant = $this->getApplicantDetails($app->from_id);
                $data = $this->decodeField($app->json_data);

                // Format answers if any
                $answers = '';
                if (!empty($data['answers'])) {
                    $answerTexts = [];
                    foreach ($data['answers'] as $qa) {
                        $answerTexts[] = $qa['question'] . ': ' . $qa['answer'];
                    }
                    $answers = implode(' | ', $answerTexts);
                }

                fputcsv($file, [
                    $app->id,
                    $applicant['name'] ?? 'Unknown',
                    $applicant['email'] ?? '',
                    $applicant['phone'] ?? '',
                    $app->message_txt,
                    implode(' | ', $data['portfolio_links'] ?? []),
                    $data['resume_link'] ?? '',
                    $data['additional_notes'] ?? $answers,
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
        $job = Post::whereIn('category_id', [5, 6, 7])->findOrFail($id);
        $job->is_active = true;
        $job->is_published = true;
        $job->save();

        // Optional: Send notification to job poster
        $this->sendJobStatusNotification($job->user_id, $job->title, 'approved');

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

        $job = Post::whereIn('category_id', [5, 6, 7])->findOrFail($id);
        $job->is_active = false;
        $job->is_published = false;
        $job->save();

        // Optional: Send notification to job poster
        $this->sendJobStatusNotification($job->user_id, $job->title, 'rejected', $request->reason);

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
        $job = Post::whereIn('category_id', [5, 6, 7])->findOrFail($id);
        $job->is_featured = !$job->is_featured;
        $job->save();

        return response()->json([
            'success' => true,
            'message' => 'Job featured status updated',
            'new_status' => $job->is_featured
        ]);
    }

    /**
     * Delete a job
     */
    public function destroy($id)
    {
        DB::beginTransaction();
        
        try {
            $job = Post::whereIn('category_id', [5, 6, 7])->findOrFail($id);
            
            // Delete associated images
            if ($job->images) {
                $images = $this->decodeField($job->images);
                foreach ($images as $image) {
                    $path = public_path('post_images/' . $image);
                    if (file_exists($path)) {
                        @unlink($path);
                    }
                }
            }
            
            // Delete associated files
            if ($job->files) {
                $files = $this->decodeField($job->files);
                foreach ($files as $file) {
                    $path = public_path('post_files/' . $file);
                    if (file_exists($path)) {
                        @unlink($path);
                    }
                }
            }

            // Delete related applications
            UserMessage::where('listing_id', $job->id)
                ->where('chat_type_id', function($q) {
                    $q->select('id')->from('chat_types')->where('slug', 'job_application');
                })
                ->delete();
            
            $job->delete();
            
            DB::commit();
            
            return response()->json([
                'success' => true,
                'message' => 'Job deleted successfully'
            ]);
            
        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json([
                'success' => false,
                'message' => 'Failed to delete job: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Send notification about job status change
     */
    private function sendJobStatusNotification($userId, $jobTitle, $status, $reason = null)
    {
        $user = User::find($userId);
        
        if (!$user || !$user->firebase_token || !$user->push_notification) {
            return;
        }

        try {
            $fcmService = app(\App\Services\FCMService::class);
            
            $title = "Job " . ucfirst($status);
            $body = "Your job posting '{$jobTitle}' has been {$status}.";
            
            if ($reason) {
                $body .= " Reason: {$reason}";
            }

            $dataPayload = [
                'screen' => 'my_jobs',
                'type' => 'job_status',
                'job_title' => $jobTitle,
                'status' => $status
            ];

            $fcmService->fcmSendNotification(
                $user->firebase_token,
                $title,
                $body,
                $dataPayload
            );
        } catch (\Exception $e) {
            \Log::error('Failed to send job status notification', [
                'error' => $e->getMessage(),
                'user_id' => $userId
            ]);
        }
    }

    /**
     * Get job statistics
     */
    public function getJobStats($id)
    {
        $job = Post::whereIn('category_id', [5, 6, 7])->findOrFail($id);

        $applications = UserMessage::where('listing_id', $job->id)
            ->where('chat_type_id', function($q) {
                $q->select('id')->from('chat_types')->where('slug', 'job_application');
            })
            ->get();

        $stats = [
            'total_applications' => $applications->count(),
            'unique_applicants' => $applications->pluck('from_id')->unique()->count(),
            'applications_by_date' => $applications->groupBy(function($app) {
                return $app->created_at->format('Y-m-d');
            })->map(function($group) {
                return $group->count();
            }),
            'views' => $job->views_count ?? 0,
            'likes' => $job->likes_count ?? 0,
            'shares' => $job->shares_count ?? 0,
        ];

        return response()->json([
            'success' => true,
            'data' => $stats
        ]);
    }
}