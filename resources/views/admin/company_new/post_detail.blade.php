{{-- resources/views/admin/company_new/post_detail.blade.php --}}
@extends('admin.layouts.admin_layout')

@section('content')
<div class="page-content-wrapper">
    <div class="page-content">
        <!-- BEGIN PAGE BAR -->
        <div class="page-bar">
            <ul class="page-breadcrumb">
                <li>
                    <a href="{{ route('admin.home') }}">Home</a>
                    <i class="fa fa-circle"></i>
                </li>
                <li>
                    <a href="{{ route('companies.index') }}">Companies</a>
                    <i class="fa fa-circle"></i>
                </li>
                <li>
                    <a href="{{ route('companies.show', $post->user_id) }}">{{ $post->user->name ?? 'Company' }}</a>
                    <i class="fa fa-circle"></i>
                </li>
                <li>
                    <span>Post Details</span>
                </li>
            </ul>
            <div class="page-toolbar">
                <a href="{{ route('companies.show', $post->user_id) }}#posts" class="btn btn-sm btn-default">
                    <i class="fa fa-arrow-left"></i> Back to Company
                </a>
            </div>
        </div>
        <!-- END PAGE BAR -->

        <div class="row">
            <div class="col-md-12">
                <!-- Post Header -->
                <div class="portlet light bordered">
                    <div class="portlet-title">
                        <div class="caption">
                            <i class="fa fa-file-text font-dark"></i>
                            <span class="caption-subject font-dark sbold uppercase">Post Details</span>
                        </div>
                        <div class="actions">
                            <button class="btn btn-circle btn-{{ $post->is_published ? 'success' : 'warning' }} btn-sm" id="togglePostStatus">
                                <i class="fa {{ $post->is_published ? 'fa-check' : 'fa-eye-slash' }}"></i> 
                                {{ $post->is_published ? 'Published' : 'Draft' }}
                            </button>
                            <button class="btn btn-circle btn-danger btn-sm delete-post" data-id="{{ $post->id }}">
                                <i class="fa fa-trash"></i> Delete Post
                            </button>
                        </div>
                    </div>
                    <div class="portlet-body">
                        <div class="row">
                            <div class="col-md-8">
                                <h2>{{ $post->title }}</h2>
                                <p class="text-muted">
                                    <i class="fa fa-calendar"></i> Created: {{ $post->created_at->format('d M Y h:i A') }}
                                    @if($post->created_at != $post->updated_at)
                                        <br><i class="fa fa-edit"></i> Updated: {{ $post->updated_at->format('d M Y h:i A') }}
                                    @endif
                                </p>
                                
                                <div class="well well-sm">
                                    <strong>Category:</strong> {{ $post->category->name ?? 'N/A' }} 
                                    @if($post->subcategory)
                                        > {{ $post->subcategory->name ?? '' }}
                                    @endif
                                    <br>
                                    <strong>Post Type:</strong> {{ $post->postType->name ?? 'N/A' }}
                                    @if($post->is_job_post)
                                        <span class="label label-primary">Job Post</span>
                                    @endif
                                    @if($post->is_repost)
                                        <span class="label label-info">Repost</span>
                                    @endif
                                </div>

                                <h4>Content</h4>
                                <div class="well">
                                    {!! nl2br(e($post->content)) !!}
                                </div>

                                @if($post->short_description)
                                    <h4>Short Description</h4>
                                    <div class="well well-sm">
                                        {{ $post->short_description }}
                                    </div>
                                @endif

                                {{-- ============================================ --}}
                                {{-- CATEGORY 1: PROJECTS --}}
                                {{-- ============================================ --}}
                                @if($post->category_id == 1)
                                    <h4>Project Details</h4>
                                    <table class="table table-bordered">
                                        @if($post->tech_stack)
                                            <tr>
                                                <th style="width: 200px;">Tech Stack</th>
                                                <td>
                                                    @php $techStack = is_array($post->tech_stack) ? $post->tech_stack : json_decode($post->tech_stack ?? '[]', true); @endphp
                                                    @foreach($techStack as $tech)
                                                        <span class="label label-info" style="margin: 2px; display: inline-block;">{{ $tech }}</span>
                                                    @endforeach
                                                </td>
                                            </tr>
                                        @endif
                                        
                                        @if($post->subcategory_id == 1) {{-- Mini Innovation/Fun Innovation --}}
                                            @if($post->idea_or_goal)
                                                <tr><th>Idea/Goal</th><td>{{ $post->idea_or_goal }}</td></tr>
                                            @endif
                                            @if($post->outcome_or_fun_element)
                                                <tr><th>Outcome/Fun Element</th><td>{{ $post->outcome_or_fun_element }}</td></tr>
                                            @endif
                                        @elseif($post->subcategory_id == 2) {{-- Real Project --}}
                                            @if($post->project_domain)
                                                <tr><th>Project Domain</th><td>{{ $post->project_domain }}</td></tr>
                                            @endif
                                            @if($post->role_in_project)
                                                <tr><th>Role in Project</th><td>{{ $post->role_in_project }}</td></tr>
                                            @endif
                                            @if($post->duration_start || $post->duration_end)
                                                <tr>
                                                    <th>Duration</th>
                                                    <td>
                                                        @if($post->duration_start)
                                                            {{ \Carbon\Carbon::parse($post->duration_start)->format('d M Y') }}
                                                        @endif
                                                        @if($post->duration_end)
                                                            - {{ \Carbon\Carbon::parse($post->duration_end)->format('d M Y') }}
                                                        @endif
                                                    </td>
                                                </tr>
                                            @endif
                                        @endif
                                    </table>

                                {{-- ============================================ --}}
                                {{-- CATEGORY 2: ACHIEVEMENTS --}}
                                {{-- ============================================ --}}
                                @elseif($post->category_id == 2)
                                    <h4>Achievement Details</h4>
                                    <table class="table table-bordered">
                                        @if($post->subcategory_id == 3) {{-- Certification --}}
                                            @if($post->certification_title)
                                                <tr><th style="width: 200px;">Certification Title</th><td>{{ $post->certification_title }}</td></tr>
                                            @endif
                                        @elseif($post->subcategory_id == 4) {{-- Rewards/Recognitions --}}
                                            @if($post->award_name)
                                                <tr><th>Award Name</th><td>{{ $post->award_name }}</td></tr>
                                            @endif
                                        @elseif($post->subcategory_id == 5) {{-- Congratulate Someone --}}
                                            @if($post->occasion_title)
                                                <tr><th>Occasion Title</th><td>{{ $post->occasion_title }}</td></tr>
                                            @endif
                                            @if($post->message)
                                                <tr><th>Message</th><td>{{ $post->message }}</td></tr>
                                            @endif
                                        @endif
                                        
                                        @if($post->technology_topic)
                                            <tr><th>Technology Topic</th><td>{{ $post->technology_topic }}</td></tr>
                                        @endif
                                    </table>

                                {{-- ============================================ --}}
                                {{-- CATEGORY 3: EVENTS --}}
                                {{-- ============================================ --}}
                                @elseif($post->category_id == 3)
                                    <h4>Event Details</h4>
                                    <table class="table table-bordered">
                                        @if($post->event_date)
                                            <tr>
                                                <th style="width: 200px;">Event Date</th>
                                                <td>
                                                    {{ \Carbon\Carbon::parse($post->event_date)->format('d M Y') }}
                                                    @if($post->event_end_date)
                                                        - {{ \Carbon\Carbon::parse($post->event_end_date)->format('d M Y') }}
                                                    @endif
                                                </td>
                                            </tr>
                                        @endif
                                        
                                        @if($post->technology_topic)
                                            <tr><th>Technology Topic</th><td>{{ $post->technology_topic }}</td></tr>
                                        @endif
                                        
                                        @if($post->subcategory_id == 8) {{-- Hackathon --}}
                                            @if($post->result_rank)
                                                <tr><th>Result/Rank</th><td>{{ $post->result_rank }}</td></tr>
                                            @endif
                                        @elseif($post->subcategory_id == 7) {{-- Attended an Event --}}
                                            @if($post->organizer_id)
                                                <tr><th>Organizer ID</th><td>{{ $post->organizer_id }}</td></tr>
                                            @endif
                                        @elseif($post->subcategory_id == 9) {{-- Webinar --}}
                                            @if($post->host_id)
                                                <tr><th>Host ID</th><td>{{ $post->host_id }}</td></tr>
                                            @endif
                                        @endif
                                    </table>

                                {{-- ============================================ --}}
                                {{-- CATEGORY 4: KNOWLEDGE SHARING --}}
                                {{-- ============================================ --}}
                                @elseif($post->category_id == 4)
                                    <h4>Knowledge Sharing Details</h4>
                                    <table class="table table-bordered">
                                        @if($post->subcategory_id == 10) {{-- Ideas/Suggestions --}}
                                            @if($post->idea_title)
                                                <tr><th style="width: 200px;">Idea Title</th><td>{{ $post->idea_title }}</td></tr>
                                            @endif
                                        @elseif($post->subcategory_id == 11) {{-- Playbook/Guide --}}
                                            @if($post->guide_title)
                                                <tr><th>Guide Title</th><td>{{ $post->guide_title }}</td></tr>
                                            @endif
                                        @endif
                                        
                                        @if($post->technology_topic)
                                            <tr><th>Technology Topic</th><td>{{ $post->technology_topic }}</td></tr>
                                        @endif
                                    </table>

                                {{-- ============================================ --}}
                                {{-- CATEGORY 5: JOBS (3 TYPES) --}}
                                {{-- ============================================ --}}
                                @elseif($post->category_id == 5)
                                    <h4>Job Details</h4>
                                    <table class="table table-bordered">
                                        <tr><th style="width: 200px;">Company Name</th><td>{{ $post->company_name ?? 'N/A' }}</td></tr>
                                        <tr><th>Job Location</th><td>{{ $post->job_location ?? 'N/A' }}</td></tr>
                                        
                                        @if($post->role_type)
                                            <tr><th>Role Type</th><td>{{ ucfirst(str_replace('_', ' ', $post->role_type)) }}</td></tr>
                                        @endif
                                        
                                        @if($post->work_mode)
                                            <tr><th>Work Mode</th><td>{{ ucfirst($post->work_mode) }}</td></tr>
                                        @endif
                                        
                                        @if($post->key_deliverables)
                                            <tr><th>Key Deliverables</th><td>{{ $post->key_deliverables }}</td></tr>
                                        @endif
                                        
                                        @if($post->skills_required)
                                            <tr>
                                                <th>Skills Required</th>
                                                <td>
                                                    @php $skills = is_array($post->skills_required) ? $post->skills_required : json_decode($post->skills_required ?? '[]', true); @endphp
                                                    @foreach($skills as $skill)
                                                        <span class="label label-primary" style="margin: 2px; display: inline-block;">{{ $skill }}</span>
                                                    @endforeach
                                                </td>
                                            </tr>
                                        @endif
                                        
                                        @if($post->experience_required)
                                            <tr><th>Experience Required</th><td>{{ $post->experience_required }}</td></tr>
                                        @endif
                                        
                                        @if($post->salary_range)
                                            <tr><th>Salary Range</th><td>{{ $post->salary_range }}</td></tr>
                                        @endif
                                        
                                        @if($post->benefits)
                                            <tr><th>Benefits</th><td>{{ $post->benefits }}</td></tr>
                                        @endif
                                        
                                        @if($post->application_url)
                                            <tr>
                                                <th>Application URL</th>
                                                <td><a href="{{ $post->application_url }}" target="_blank">{{ $post->application_url }}</a></td>
                                            </tr>
                                        @endif
                                        
                                        {{-- Mini Mission Specific (subcategory_id = 13) --}}
                                        @if($post->subcategory_id == 13)
                                            <h5 class="text-primary">Mini Mission Details</h5>
                                            @if($post->deliverables)
                                                <tr><th>Deliverables</th><td>{{ $post->deliverables }}</td></tr>
                                            @endif
                                            @if($post->timeline_start || $post->timeline_end)
                                                <tr>
                                                    <th>Timeline</th>
                                                    <td>
                                                        @if($post->timeline_start)
                                                            {{ \Carbon\Carbon::parse($post->timeline_start)->format('d M Y') }}
                                                        @endif
                                                        @if($post->timeline_end)
                                                            - {{ \Carbon\Carbon::parse($post->timeline_end)->format('d M Y') }}
                                                        @endif
                                                    </td>
                                                </tr>
                                            @endif
                                        
                                        {{-- Internship Specific (subcategory_id = 14) --}}
                                        @elseif($post->subcategory_id == 14)
                                            <h5 class="text-primary">Internship Details</h5>
                                            @if($post->internship_duration)
                                                <tr><th>Internship Duration</th><td>{{ $post->internship_duration }}</td></tr>
                                            @endif
                                            @if($post->stipend_amount)
                                                <tr>
                                                    <th>Stipend</th>
                                                    <td>
                                                        {{ $post->stipend_currency ?? '₹' }} {{ number_format($post->stipend_amount) }}
                                                        @if($post->convertible_to_full_time)
                                                            <span class="label label-success">Convertible to Full Time</span>
                                                        @endif
                                                    </td>
                                                </tr>
                                            @endif
                                        
                                        {{-- Full-Time Specific (subcategory_id = 15) --}}
                                        @elseif($post->subcategory_id == 15)
                                            <h5 class="text-primary">Full-Time Details</h5>
                                            @if($post->ctc_amount)
                                                <tr>
                                                    <th>CTC</th>
                                                    <td>{{ $post->ctc_currency ?? '₹' }} {{ number_format($post->ctc_amount) }}</td>
                                                </tr>
                                            @endif
                                        @endif
                                        
                                        @if($post->application_deadline)
                                            <tr>
                                                <th>Application Deadline</th>
                                                <td class="{{ \Carbon\Carbon::parse($post->application_deadline)->isPast() ? 'text-danger' : 'text-success' }}">
                                                    {{ \Carbon\Carbon::parse($post->application_deadline)->format('d M Y') }}
                                                    @if(\Carbon\Carbon::parse($post->application_deadline)->isPast())
                                                        (Expired)
                                                    @else
                                                        ({{ \Carbon\Carbon::parse($post->application_deadline)->diffForHumans() }})
                                                    @endif
                                                </td>
                                            </tr>
                                        @endif
                                    </table>
                                @endif

                                {{-- ============================================ --}}
                                {{-- MEDIA ATTACHMENTS --}}
                                {{-- ============================================ --}}
                                @if($post->images || $post->files)
                                    <h4>Media Attachments</h4>
                                    <div class="row">
                                        @if($post->images)
                                            @php $images = is_array($post->images) ? $post->images : json_decode($post->images ?? '[]', true); @endphp
                                            @foreach($images as $image)
                                                <div class="col-md-3">
                                                    <div class="thumbnail">
                                                        <img src="{{ asset('post_images/' . $image) }}" 
                                                             alt="Post Image" style="max-height: 150px; width: 100%; object-fit: cover;">
                                                        <div class="caption text-center">
                                                            <a href="{{ asset('post_images/' . $image) }}" 
                                                               target="_blank" class="btn btn-xs btn-primary">View Full</a>
                                                        </div>
                                                    </div>
                                                </div>
                                            @endforeach
                                        @endif
                                    </div>
                                    
                                    @if($post->files)
                                        @php $files = is_array($post->files) ? $post->files : json_decode($post->files ?? '[]', true); @endphp
                                        <div class="list-group">
                                            @foreach($files as $file)
                                                <a href="{{ asset('post_files/' . $file) }}" 
                                                   class="list-group-item" target="_blank">
                                                    <i class="fa fa-file"></i> {{ $file }}
                                                    <span class="badge">
                                                        @if(file_exists(public_path('post_files/' . $file)))
                                                            {{ round(filesize(public_path('post_files/' . $file)) / 1024, 2) }} KB
                                                        @endif
                                                    </span>
                                                </a>
                                            @endforeach
                                        </div>
                                    @endif
                                @endif

                                {{-- ============================================ --}}
                                {{-- TAGGED USERS & COMPANIES --}}
                                {{-- ============================================ --}}
                                @if($post->taggedUsers->count() > 0 || $post->taggedCompanies->count() > 0)
                                    <h4>Tagged</h4>
                                    <div class="well">
                                        @foreach($post->taggedUsers as $taggedUser)
                                            <span class="label label-info" style="margin: 2px; display: inline-block; font-size: 12px;">
                                                <i class="fa fa-user"></i> {{ $taggedUser->name }}
                                            </span>
                                        @endforeach
                                        @foreach($post->taggedCompanies as $taggedCompany)
                                            <span class="label label-success" style="margin: 2px; display: inline-block; font-size: 12px;">
                                                <i class="fa fa-building"></i> {{ $taggedCompany->name }}
                                            </span>
                                        @endforeach
                                    </div>
                                @endif

                                {{-- ============================================ --}}
                                {{-- REPOST INFORMATION --}}
                                {{-- ============================================ --}}
                                @if($post->is_repost && $post->original_post_id)
                                    <div class="alert alert-info">
                                        <i class="fa fa-retweet"></i> 
                                        <strong>This is a repost</strong> 
                                        from post ID: {{ $post->original_post_id }}
                                    </div>
                                @endif
                            </div>

                            {{-- ============================================ --}}
                            {{-- STATS SIDEBAR --}}
                            {{-- ============================================ --}}
                            <div class="col-md-4">
                                <div class="portlet light bordered">
                                    <div class="portlet-title">
                                        <div class="caption">
                                            <i class="fa fa-bar-chart"></i>
                                            <span class="caption-subject">Engagement Stats</span>
                                        </div>
                                    </div>
                                    <div class="portlet-body">
                                        <div class="row text-center">
                                            <div class="col-xs-6">
                                                <h3>{{ number_format($stats['total_likes']) }}</h3>
                                                <small>Likes</small>
                                            </div>
                                            <div class="col-xs-6">
                                                <h3>{{ number_format($stats['total_comments']) }}</h3>
                                                <small>Comments</small>
                                            </div>
                                        </div>
                                        <div class="row text-center margin-top-20">
                                            <div class="col-xs-6">
                                                <h3>{{ number_format($stats['total_shares']) }}</h3>
                                                <small>Shares</small>
                                            </div>
                                            <div class="col-xs-6">
                                                <h3>{{ number_format($stats['total_views']) }}</h3>
                                                <small>Views</small>
                                            </div>
                                        </div>
                                        <hr>
                                        <div class="text-center">
                                            <p><strong>Unique Viewers:</strong> {{ number_format($stats['unique_viewers']) }}</p>
                                            <p><strong>Repost Count:</strong> {{ $post->repost_count ?? 0 }}</p>
                                        </div>
                                    </div>
                                </div>

                                @if(!empty($stats['likes_trend']) && $stats['likes_trend']->count() > 0)
                                    <div class="portlet light bordered">
                                        <div class="portlet-title">
                                            <div class="caption">
                                                <i class="fa fa-line-chart"></i>
                                                <span class="caption-subject">Likes Trend (30 days)</span>
                                            </div>
                                        </div>
                                        <div class="portlet-body">
                                            <canvas id="likesChart" height="200"></canvas>
                                        </div>
                                    </div>
                                @endif
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        {{-- ============================================ --}}
        {{-- COMMENTS SECTION --}}
        {{-- ============================================ --}}
        <div class="row">
            <div class="col-md-12">
                <div class="portlet light bordered">
                    <div class="portlet-title">
                        <div class="caption">
                            <i class="fa fa-comments"></i>
                            <span class="caption-subject">Comments ({{ $post->comments->count() }})</span>
                        </div>
                    </div>
                    <div class="portlet-body">
                        @forelse($post->comments as $comment)
                            <div class="media" id="commentRow{{ $comment->id }}" style="margin-bottom: 15px;">
                                <div class="media-left">
                                    @if($comment->user && $comment->user->image)
                                        <img src="{{ asset('user_images/' . $comment->user->image) }}" 
                                             class="media-object img-circle" style="width: 50px; height: 50px;">
                                    @else
                                        <div class="media-object img-circle text-center" 
                                             style="width: 50px; height: 50px; background: #ccc; line-height: 50px;">
                                            <i class="fa fa-user"></i>
                                        </div>
                                    @endif
                                </div>
                                <div class="media-body">
                                    <h4 class="media-heading">
                                        <strong>{{ $comment->user->name ?? 'Unknown User' }}</strong>
                                        <small class="text-muted">{{ $comment->created_at->diffForHumans() }}</small>
                                        @if(!$comment->is_active)
                                            <span class="label label-warning">Inactive</span>
                                        @endif
                                        <span class="pull-right">
                                            <button class="btn btn-xs btn-{{ $comment->is_active ? 'success' : 'warning' }} toggle-comment-status" 
                                                    data-id="{{ $comment->id }}">
                                                {{ $comment->is_active ? 'Active' : 'Inactive' }}
                                            </button>
                                            <button class="btn btn-xs btn-danger delete-comment" data-id="{{ $comment->id }}">
                                                <i class="fa fa-trash"></i>
                                            </button>
                                        </span>
                                    </h4>
                                    <p>{{ $comment->content }}</p>
                                    
                                    {{-- Replies --}}
                                    @if(isset($comment->replies) && $comment->replies->count() > 0)
                                        <div class="media" style="margin-top: 15px; padding-left: 30px;">
                                            @foreach($comment->replies as $reply)
                                                <div class="media" id="commentRow{{ $reply->id }}" style="margin-bottom: 10px;">
                                                    <div class="media-left">
                                                        @if($reply->user && $reply->user->image)
                                                            <img src="{{ asset('user_images/' . $reply->user->image) }}" 
                                                                 class="media-object img-circle" style="width: 40px; height: 40px;">
                                                        @else
                                                            <div class="media-object img-circle text-center" 
                                                                 style="width: 40px; height: 40px; background: #ccc; line-height: 40px;">
                                                                <i class="fa fa-user"></i>
                                                            </div>
                                                        @endif
                                                    </div>
                                                    <div class="media-body">
                                                        <h5 class="media-heading">
                                                            <strong>{{ $reply->user->name ?? 'Unknown User' }}</strong>
                                                            <small class="text-muted">{{ $reply->created_at->diffForHumans() }}</small>
                                                            @if(!$reply->is_active)
                                                                <span class="label label-warning">Inactive</span>
                                                            @endif
                                                            <span class="pull-right">
                                                                <button class="btn btn-xs btn-{{ $reply->is_active ? 'success' : 'warning' }} toggle-comment-status" 
                                                                        data-id="{{ $reply->id }}">
                                                                    {{ $reply->is_active ? 'Active' : 'Inactive' }}
                                                                </button>
                                                                <button class="btn btn-xs btn-danger delete-comment" data-id="{{ $reply->id }}">
                                                                    <i class="fa fa-trash"></i>
                                                                </button>
                                                            </span>
                                                        </h5>
                                                        <p>{{ $reply->content }}</p>
                                                    </div>
                                                </div>
                                            @endforeach
                                        </div>
                                    @endif
                                </div>
                            </div>
                            <hr>
                        @empty
                            <div class="alert alert-info">No comments yet</div>
                        @endforelse
                    </div>
                </div>
            </div>
        </div>

        {{-- ============================================ --}}
        {{-- LIKES SECTION --}}
        {{-- ============================================ --}}
        @if($post->likes->count() > 0)
        <div class="row">
            <div class="col-md-12">
                <div class="portlet light bordered">
                    <div class="portlet-title">
                        <div class="caption">
                            <i class="fa fa-heart"></i>
                            <span class="caption-subject">Likes ({{ $post->likes->count() }})</span>
                        </div>
                    </div>
                    <div class="portlet-body">
                        <div class="row">
                            @foreach($post->likes->take(20) as $like)
                                <div class="col-md-2 col-sm-3 col-xs-6 text-center" style="margin-bottom: 15px;">
                                    @if($like->user && $like->user->image)
                                        <img src="{{ asset('user_images/' . $like->user->image) }}" 
                                             class="img-circle" style="width: 50px; height: 50px;">
                                    @else
                                        <div class="img-circle text-center" 
                                             style="width: 50px; height: 50px; background: #ccc; line-height: 50px; margin: 0 auto;">
                                            <i class="fa fa-user"></i>
                                        </div>
                                    @endif
                                    <p style="margin-top: 5px;">
                                        <strong>{{ $like->user->name ?? 'Unknown' }}</strong><br>
                                        <small>{{ $like->created_at->format('d M Y') }}</small>
                                    </p>
                                </div>
                            @endforeach
                        </div>
                        @if($post->likes->count() > 20)
                            <div class="text-center">
                                <a href="#" class="btn btn-sm btn-default">View all {{ $post->likes->count() }} likes</a>
                            </div>
                        @endif
                    </div>
                </div>
            </div>
        </div>
        @endif

        {{-- ============================================ --}}
        {{-- VIEWS SECTION --}}
        {{-- ============================================ --}}
        @if($post->views->count() > 0)
        <div class="row">
            <div class="col-md-12">
                <div class="portlet light bordered">
                    <div class="portlet-title">
                        <div class="caption">
                            <i class="fa fa-eye"></i>
                            <span class="caption-subject">Recent Views ({{ $post->views->count() }})</span>
                        </div>
                    </div>
                    <div class="portlet-body">
                        <table class="table table-striped table-hover">
                            <thead>
                                <tr>
                                    <th>User</th>
                                    <th>IP Address</th>
                                    <th>Viewed At</th>
                                </tr>
                            </thead>
                            <tbody>
                                @foreach($post->views->take(20) as $view)
                                    <tr>
                                        <td>
                                            @if($view->user)
                                                {{ $view->user->name }}
                                            @else
                                                <span class="label label-default">Guest</span>
                                            @endif
                                        </td>
                                        <td>{{ $view->ip_address ?? 'N/A' }}</td>
                                        <td>{{ $view->created_at->format('d M Y h:i A') }}</td>
                                    </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>
        @endif
    </div>
</div>

{{-- ============================================ --}}
{{-- MODALS --}}
{{-- ============================================ --}}
<div class="modal fade" id="deleteConfirmModal" tabindex="-1" role="dialog">
    <div class="modal-dialog" role="document">
        <div class="modal-content">
            <div class="modal-header">
                <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                    <span aria-hidden="true">&times;</span>
                </button>
                <h4 class="modal-title">Confirm Delete</h4>
            </div>
            <div class="modal-body">
                <p>Are you sure you want to delete this post? This action cannot be undone.</p>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-default" data-dismiss="modal">Cancel</button>
                <button type="button" class="btn btn-danger" id="confirmDeleteBtn">Delete</button>
            </div>
        </div>
    </div>
</div>
@endsection

@push('styles')
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/Chart.js/2.9.4/Chart.min.css">
<style>
    .thumbnail {
        margin-bottom: 10px;
    }
    .thumbnail img {
        width: 100%;
        height: 150px;
        object-fit: cover;
    }
    .media {
        margin-top: 0;
    }
    .media-object.img-circle {
        object-fit: cover;
    }
    .label {
        font-size: 11px;
        padding: 3px 6px;
    }
    .well {
        background-color: #f9f9f9;
        border: 1px solid #e3e3e3;
        border-radius: 4px;
    }
    .table-bordered th {
        background-color: #f5f5f5;
    }
    h5.text-primary {
        color: #337ab7;
        margin-top: 20px;
        margin-bottom: 10px;
        border-bottom: 1px solid #eee;
        padding-bottom: 5px;
    }
</style>
@endpush

@push('scripts')
<script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
<script src="https://cdnjs.cloudflare.com/ajax/libs/Chart.js/2.9.4/Chart.min.js"></script>
<script src="https://cdnjs.cloudflare.com/ajax/libs/toastr.js/latest/toastr.min.js"></script>
<script>
$(document).ready(function() {
    // Initialize toastr
    toastr.options = {
        "closeButton": true,
        "debug": false,
        "newestOnTop": false,
        "progressBar": true,
        "positionClass": "toast-top-right",
        "preventDuplicates": false,
        "onclick": null,
        "showDuration": "300",
        "hideDuration": "1000",
        "timeOut": "5000",
        "extendedTimeOut": "1000",
        "showEasing": "swing",
        "hideEasing": "linear",
        "showMethod": "fadeIn",
        "hideMethod": "fadeOut"
    };

    // Toggle post status
    $('#togglePostStatus').click(function() {
        var button = $(this);
        var postId = {{ $post->id }};
        
        $.ajax({
            url: '{{ route("companies.post.toggle-status", ":id") }}'.replace(':id', postId),
            type: 'POST',
            data: {
                _token: '{{ csrf_token() }}'
            },
            success: function(response) {
                if (response.success) {
                    if (response.new_status) {
                        button.removeClass('btn-warning').addClass('btn-success')
                              .html('<i class="fa fa-check"></i> Published');
                    } else {
                        button.removeClass('btn-success').addClass('btn-warning')
                              .html('<i class="fa fa-eye-slash"></i> Draft');
                    }
                    toastr.success('Post status updated');
                }
            },
            error: function() {
                toastr.error('Failed to update post status');
            }
        });
    });

    // Delete post
    $('.delete-post').click(function() {
        var postId = $(this).data('id');
        
        Swal.fire({
            title: 'Delete Post?',
            text: "This action cannot be undone!",
            icon: 'warning',
            showCancelButton: true,
            confirmButtonColor: '#d33',
            cancelButtonColor: '#3085d6',
            confirmButtonText: 'Yes, delete it!'
        }).then((result) => {
            if (result.isConfirmed) {
                $.ajax({
                    url: '{{ route("companies.post.delete", ":id") }}'.replace(':id', postId),
                    type: 'DELETE',
                    data: {
                        _token: '{{ csrf_token() }}'
                    },
                    success: function(response) {
                        toastr.success('Post deleted successfully');
                        setTimeout(function() {
                            window.location.href = '{{ route("companies.show", $post->user_id) }}#posts';
                        }, 1500);
                    },
                    error: function() {
                        toastr.error('Failed to delete post');
                    }
                });
            }
        });
    });

    // Delete comment
    $('.delete-comment').click(function() {
        var commentId = $(this).data('id');
        var row = $(this).closest('.media');
        
        Swal.fire({
            title: 'Delete Comment?',
            text: "This action cannot be undone!",
            icon: 'warning',
            showCancelButton: true,
            confirmButtonColor: '#d33',
            cancelButtonColor: '#3085d6',
            confirmButtonText: 'Yes, delete it!'
        }).then((result) => {
            if (result.isConfirmed) {
                $.ajax({
                    url: '{{ route("companies.comment.delete", ":id") }}'.replace(':id', commentId),
                    type: 'DELETE',
                    data: {
                        _token: '{{ csrf_token() }}'
                    },
                    success: function(response) {
                        row.fadeOut();
                        toastr.success('Comment deleted successfully');
                    },
                    error: function() {
                        toastr.error('Failed to delete comment');
                    }
                });
            }
        });
    });

    // Toggle comment status
    $('.toggle-comment-status').click(function() {
        var button = $(this);
        var commentId = button.data('id');
        
        $.ajax({
            url: '{{ route("companies.comment.toggle", ":id") }}'.replace(':id', commentId),
            type: 'POST',
            data: {
                _token: '{{ csrf_token() }}'
            },
            success: function(response) {
                if (response.success) {
                    if (response.new_status) {
                        button.removeClass('btn-warning').addClass('btn-success').text('Active');
                    } else {
                        button.removeClass('btn-success').addClass('btn-warning').text('Inactive');
                    }
                    toastr.success('Comment status updated');
                }
            },
            error: function() {
                toastr.error('Failed to update comment status');
            }
        });
    });

    // Likes Chart
    @if(!empty($stats['likes_trend']) && $stats['likes_trend']->count() > 0)
        var ctx = document.getElementById('likesChart').getContext('2d');
        var likesData = @json($stats['likes_trend']);
        
        new Chart(ctx, {
            type: 'line',
            data: {
                labels: likesData.map(item => item.date),
                datasets: [{
                    label: 'Likes',
                    data: likesData.map(item => item.count),
                    borderColor: 'rgb(54, 162, 235)',
                    backgroundColor: 'rgba(54, 162, 235, 0.2)',
                    tension: 0.1,
                    fill: true
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                scales: {
                    y: {
                        beginAtZero: true,
                        ticks: {
                            stepSize: 1,
                            precision: 0
                        }
                    }
                }
            }
        });
    @endif
});
</script>
@endpush