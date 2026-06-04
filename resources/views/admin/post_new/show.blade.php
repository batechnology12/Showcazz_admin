{{-- resources/views/admin/post_new/show.blade.php --}}
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
                    <a href="{{ route('admin.posts.index') }}">Posts</a>
                    <i class="fa fa-circle"></i>
                </li>
                <li>
                    <span>Post Details</span>
                </li>
            </ul>
            <div class="page-toolbar">
                <a href="{{ route('admin.posts.index') }}" class="btn btn-sm btn-default">
                    <i class="fa fa-arrow-left"></i> Back to List
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
                            @if(!$post->is_active && !$post->is_published)
                                <button class="btn btn-circle btn-success btn-sm" id="approvePostBtn" data-id="{{ $post->id }}">
                                    <i class="fa fa-check"></i> Approve
                                </button>
                                <button class="btn btn-circle btn-danger btn-sm" id="rejectPostBtn" data-id="{{ $post->id }}">
                                    <i class="fa fa-times"></i> Reject
                                </button>
                            @endif

                            <button class="btn btn-circle {{ $post->is_active ? 'btn-warning' : 'btn-info' }} btn-sm" id="toggleStatusBtn" data-id="{{ $post->id }}">
                                <i class="fa {{ $post->is_active ? 'fa-pause' : 'fa-play' }}"></i> {{ $post->is_active ? 'Deactivate' : 'Activate' }}
                            </button>

                            <button class="btn btn-circle btn-{{ $post->is_featured ? 'warning' : 'default' }} btn-sm" id="toggleFeatureBtn" data-id="{{ $post->id }}">
                                <i class="fa fa-star"></i> {{ $post->is_featured ? 'Featured' : 'Not Featured' }}
                            </button>
                            
                            <button class="btn btn-circle btn-danger btn-sm delete-post" data-id="{{ $post->id }}">
                                <i class="fa fa-trash"></i> Delete
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
                                    <br>
                                    <strong>Status:</strong> 
                                    @if($post->is_published)
                                        <span class="label label-success">Published</span>
                                    @else
                                        <span class="label label-warning">Draft</span>
                                    @endif
                                    @if($post->is_featured)
                                        <span class="label label-warning">Featured</span>
                                    @endif
                                    @if($post->is_active)
                                        <span class="label label-info">Active</span>
                                    @else
                                        <span class="label label-danger">Inactive</span>
                                    @endif
                                </div>

                                <h4>Content</h4>
                                <div class="well">
                                    {!! $post->content !!}
                                </div>

                                @if($post->short_description)
                                    <h4>Short Description</h4>
                                    <div class="well well-sm">
                                        {{ $post->short_description }}
                                    </div>
                                @endif

                                {{-- ============================================ --}}
                                {{-- CATEGORY SPECIFIC FIELDS --}}
                                {{-- ============================================ --}}
                                
                                {{-- Category 1: Projects --}}
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
                                        
                                        @if($post->subcategory_id == 1) {{-- Mini Innovation --}}
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

                                {{-- Category 2: Achievements --}}
                                @elseif($post->category_id == 2)
                                    <h4>Achievement Details</h4>
                                    <table class="table table-bordered">
                                        @if($post->subcategory_id == 3) {{-- Certification --}}
                                            @if($post->certification_title)
                                                <tr><th style="width: 200px;">Certification Title</th><td>{{ $post->certification_title }}</td></tr>
                                            @endif
                                        @elseif($post->subcategory_id == 4) {{-- Rewards --}}
                                            @if($post->award_name)
                                                <tr><th>Award Name</th><td>{{ $post->award_name }}</td></tr>
                                            @endif
                                        @elseif($post->subcategory_id == 5) {{-- Congratulate --}}
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

                                {{-- Category 3: Events --}}
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
                                        @elseif($post->subcategory_id == 7) {{-- Attended --}}
                                            @if($post->organizer_id)
                                                <tr><th>Organizer ID</th><td>{{ $post->organizer_id }}</td></tr>
                                            @endif
                                        @elseif($post->subcategory_id == 9) {{-- Webinar --}}
                                            @if($post->host_id)
                                                <tr><th>Host ID</th><td>{{ $post->host_id }}</td></tr>
                                            @endif
                                        @endif
                                    </table>

                                {{-- Category 4: Knowledge Sharing --}}
                                @elseif($post->category_id == 4)
                                    <h4>Knowledge Sharing Details</h4>
                                    <table class="table table-bordered">
                                        @if($post->subcategory_id == 10) {{-- Ideas --}}
                                            @if($post->idea_title)
                                                <tr><th style="width: 200px;">Idea Title</th><td>{{ $post->idea_title }}</td></tr>
                                            @endif
                                        @elseif($post->subcategory_id == 11) {{-- Playbook --}}
                                            @if($post->guide_title)
                                                <tr><th>Guide Title</th><td>{{ $post->guide_title }}</td></tr>
                                            @endif
                                        @endif
                                        
                                        @if($post->technology_topic)
                                            <tr><th>Technology Topic</th><td>{{ $post->technology_topic }}</td></tr>
                                        @endif
                                    </table>
                                @endif

                                {{-- ============================================ --}}
                                {{-- MEDIA ATTACHMENTS --}}
                                {{-- ============================================ --}}
                                @if(!empty($images) || !empty($files))
                                    <h4>Media Attachments</h4>
                                    
                                    @if(!empty($images))
                                        <div class="row">
                                            @foreach($images as $image)
                                                <div class="col-md-3">
                                                    <div class="thumbnail">
                                                        <img src="{{ env('DO_ACCESS_KEY_ID') ? \Illuminate\Support\Facades\Storage::disk('do')->url('post_images/' . $image) : asset('post_images/' . $image) }}" 
                                                             alt="Post Image" style="max-height: 150px; width: 100%; object-fit: cover;">
                                                        <div class="caption text-center">
                                                            <a href="{{ env('DO_ACCESS_KEY_ID') ? \Illuminate\Support\Facades\Storage::disk('do')->url('post_images/' . $image) : asset('post_images/' . $image) }}" 
                                                               target="_blank" class="btn btn-xs btn-primary">View Full</a>
                                                        </div>
                                                    </div>
                                                </div>
                                            @endforeach
                                        </div>
                                    @endif
                                    
                                    @if(!empty($files))
                                        <div class="list-group">
                                            @foreach($files as $file)
                                                <a href="{{ env('DO_ACCESS_KEY_ID') ? \Illuminate\Support\Facades\Storage::disk('do')->url('post_files/' . $file) : asset('post_files/' . $file) }}" 
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
                            </div>

                            {{-- ============================================ --}}
                            {{-- AUTHOR & STATS SIDEBAR --}}
                            {{-- ============================================ --}}
                            <div class="col-md-4">
                                {{-- Author Info --}}
                                <div class="portlet light bordered">
                                    <div class="portlet-title">
                                        <div class="caption">
                                            <i class="fa fa-user"></i>
                                            <span class="caption-subject">Author Information</span>
                                        </div>
                                    </div>
                                    <div class="portlet-body text-center">
                                        @if($author && $author['image'])
                                            <img src="{{ $author['image'] }}" class="img-circle" style="width: 100px; height: 100px; object-fit: cover;">
                                        @else
                                            <div class="img-circle text-center" style="width: 100px; height: 100px; background: #ccc; line-height: 100px; margin: 0 auto;">
                                                <i class="fa fa-user fa-3x"></i>
                                            </div>
                                        @endif
                                        <h4>{{ $author['name'] ?? 'Unknown' }}</h4>
                                        <p><span class="label label-info">{{ ucfirst($author['type'] ?? 'user') }}</span></p>
                                        <p><i class="fa fa-id-card"></i> <strong>{{ $author['unique_id'] ?? 'No ID' }}</strong></p>
                                    </div>
                                </div>

                                {{-- Stats --}}
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
                                                <h3>{{ number_format($post->likes_count ?? 0) }}</h3>
                                                <small>Likes</small>
                                            </div>
                                            <div class="col-xs-6">
                                                <h3>{{ number_format($post->comments_count ?? 0) }}</h3>
                                                <small>Comments</small>
                                            </div>
                                        </div>
                                        <div class="row text-center margin-top-20">
                                            <div class="col-xs-6">
                                                <h3>{{ number_format($post->shares_count ?? 0) }}</h3>
                                                <small>Shares</small>
                                            </div>
                                            <div class="col-xs-6">
                                                <h3>{{ number_format($post->views_count ?? 0) }}</h3>
                                                <small>Views</small>
                                            </div>
                                        </div>
                                    </div>
                                </div>

                                {{-- Recent Likes --}}
                                @if($post->likes->count() > 0)
                                    <div class="portlet light bordered">
                                        <div class="portlet-title">
                                            <div class="caption">
                                                <i class="fa fa-heart"></i>
                                                <span class="caption-subject">Recent Likes ({{ $post->likes->count() }})</span>
                                            </div>
                                        </div>
                                        <div class="portlet-body">
                                            @foreach($post->likes->take(5) as $like)
                                                <div class="media">
                                                    <div class="media-left">
                                                        @if($like->user && $like->user->image)
                                                            <img src="{{ (env('DO_ACCESS_KEY_ID') ? \Illuminate\Support\Facades\Storage::disk('do')->url('user_images/' . $like->user->image) : asset('user_images/' . $like->user->image)) }}" class="img-circle" style="width: 30px; height: 30px;">
                                                        @else
                                                            <div class="img-circle text-center" style="width: 30px; height: 30px; background: #ccc; line-height: 30px;">
                                                                <i class="fa fa-user"></i>
                                                            </div>
                                                        @endif
                                                    </div>
                                                    <div class="media-body">
                                                        <strong>{{ $like->user->name ?? 'Unknown' }}</strong>
                                                        <br><small>{{ $like->created_at->diffForHumans() }}</small>
                                                    </div>
                                                </div>
                                            @endforeach
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
        @if($post->comments->count() > 0)
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
                        @foreach($post->comments as $comment)
                            <div class="media" style="margin-bottom: 15px; padding: 10px; background: #f9f9f9;">
                                <div class="media-left">
                                    @if($comment->user && $comment->user->image)
                                        <img src="{{ (env('DO_ACCESS_KEY_ID') ? \Illuminate\Support\Facades\Storage::disk('do')->url('user_images/' . $comment->user->image) : asset('user_images/' . $comment->user->image)) }}" class="img-circle" style="width: 40px; height: 40px;">
                                    @else
                                        <div class="img-circle text-center" style="width: 40px; height: 40px; background: #ccc; line-height: 40px;">
                                            <i class="fa fa-user"></i>
                                        </div>
                                    @endif
                                </div>
                                <div class="media-body">
                                    <h4 class="media-heading">
                                        <strong>{{ $comment->user->name ?? 'Unknown' }}</strong>
                                        <small class="text-muted">{{ $comment->created_at->diffForHumans() }}</small>
                                        <button class="btn btn-xs btn-link text-danger delete-comment pull-right" data-id="{{ $comment->id }}" title="Delete Comment">
                                            <i class="fa fa-trash"></i>
                                        </button>
                                    </h4>
                                    <p>{{ $comment->content }}</p>
                                    
                                    {{-- Replies --}}
                                    @if(isset($comment->replies) && $comment->replies->count() > 0)
                                        <div style="margin-left: 50px; margin-top: 10px;">
                                            @foreach($comment->replies as $reply)
                                                <div class="media" style="padding: 5px; background: #fff;">
                                                    <div class="media-left">
                                                        @if($reply->user && $reply->user->image)
                                                            <img src="{{ (env('DO_ACCESS_KEY_ID') ? \Illuminate\Support\Facades\Storage::disk('do')->url('user_images/' . $reply->user->image) : asset('user_images/' . $reply->user->image)) }}" class="img-circle" style="width: 30px; height: 30px;">
                                                        @else
                                                            <div class="img-circle text-center" style="width: 30px; height: 30px; background: #ccc; line-height: 30px;">
                                                                <i class="fa fa-user"></i>
                                                            </div>
                                                        @endif
                                                    </div>
                                                    <div class="media-body">
                                                        <strong>{{ $reply->user->name ?? 'Unknown' }}</strong>
                                                        <small class="text-muted">{{ $reply->created_at->diffForHumans() }}</small>
                                                        <button class="btn btn-xs btn-link text-danger delete-comment pull-right" data-id="{{ $reply->id }}" title="Delete Reply">
                                                            <i class="fa fa-trash"></i>
                                                        </button>
                                                        <p>{{ $reply->content }}</p>
                                                    </div>
                                                </div>
                                            @endforeach
                                        </div>
                                    @endif
                                </div>
                            </div>
                        @endforeach
                    </div>
                </div>
            </div>
        </div>
        @endif
    </div>
</div>

{{-- Reject Modal --}}
<div class="modal fade" id="rejectModal" tabindex="-1" role="dialog">
    <div class="modal-dialog" role="document">
        <div class="modal-content">
            <div class="modal-header">
                <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                    <span aria-hidden="true">&times;</span>
                </button>
                <h4 class="modal-title">Reject Post</h4>
            </div>
            <div class="modal-body">
                <div class="form-group">
                    <label>Reason for rejection</label>
                    <textarea id="rejectReason" class="form-control" rows="3" placeholder="Enter rejection reason..."></textarea>
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-default" data-dismiss="modal">Cancel</button>
                <button type="button" class="btn btn-danger" id="confirmRejectBtn">Reject</button>
            </div>
        </div>
    </div>
</div>
@endsection

@push('styles')
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
    .well {
        background-color: #f9f9f9;
        border: 1px solid #e3e3e3;
        border-radius: 4px;
    }
    .table-bordered th {
        background-color: #f5f5f5;
    }
</style>
@endpush

@push('scripts')
<script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
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

    // Toggle status
    $('#toggleStatusBtn').click(function() {
        var id = $(this).data('id');
        var btn = $(this);
        
        Swal.fire({
            title: 'Change Post Status?',
            text: "This will toggle the active/inactive status and notify the user.",
            icon: 'info',
            showCancelButton: true,
            confirmButtonText: 'Yes, toggle it!'
        }).then((result) => {
            if (result.isConfirmed) {
                $.ajax({
                    url: '{{ route("admin.posts.toggle-status", ":id") }}'.replace(':id', id),
                    type: 'POST',
                    data: { _token: '{{ csrf_token() }}' },
                    success: function(response) {
                        if (response.success) {
                            toastr.success(response.message);
                            setTimeout(function() {
                                location.reload();
                            }, 1000);
                        }
                    },
                    error: function() {
                        toastr.error('Failed to update status');
                    }
                });
            }
        });
    });

    // Approve post
    $('#approvePostBtn').click(function() {
        var id = $(this).data('id');
        
        Swal.fire({
            title: 'Approve Post?',
            text: "This post will be published and user will be notified.",
            icon: 'question',
            showCancelButton: true,
            confirmButtonColor: '#28a745',
            confirmButtonText: 'Yes, approve it!'
        }).then((result) => {
            if (result.isConfirmed) {
                $.ajax({
                    url: '{{ route("admin.posts.approve", ":id") }}'.replace(':id', id),
                    type: 'POST',
                    data: {
                        _token: '{{ csrf_token() }}'
                    },
                    success: function(response) {
                        if (response.success) {
                            toastr.success(response.message);
                            setTimeout(function() {
                                location.reload();
                            }, 1500);
                        }
                    },
                    error: function() {
                        toastr.error('Failed to approve post');
                    }
                });
            }
        });
    });

    // Reject post - show modal
    $('#rejectPostBtn').click(function() {
        var id = $(this).data('id');
        $('#confirmRejectBtn').data('id', id);
        $('#rejectModal').modal('show');
    });

    // Confirm reject
    $('#confirmRejectBtn').click(function() {
        var id = $(this).data('id');
        var reason = $('#rejectReason').val();
        
        if (!reason) {
            toastr.warning('Please enter a rejection reason');
            return;
        }
        
        $.ajax({
            url: '{{ route("admin.posts.reject", ":id") }}'.replace(':id', id),
            type: 'POST',
            data: {
                reason: reason,
                _token: '{{ csrf_token() }}'
            },
            success: function(response) {
                if (response.success) {
                    toastr.success(response.message);
                    $('#rejectModal').modal('hide');
                    setTimeout(function() {
                        location.reload();
                    }, 1500);
                }
            },
            error: function() {
                toastr.error('Failed to reject post');
            }
        });
    });

    // Toggle featured
    $('#toggleFeatureBtn').click(function() {
        var id = $(this).data('id');
        var button = $(this);
        
        $.ajax({
            url: '{{ route("admin.posts.feature", ":id") }}'.replace(':id', id),
            type: 'POST',
            data: {
                _token: '{{ csrf_token() }}'
            },
            success: function(response) {
                if (response.success) {
                    if (response.new_status) {
                        button.removeClass('btn-default').addClass('btn-warning').html('<i class="fa fa-star"></i> Featured');
                    } else {
                        button.removeClass('btn-warning').addClass('btn-default').html('<i class="fa fa-star"></i> Not Featured');
                    }
                    toastr.success(response.message);
                }
            },
            error: function() {
                toastr.error('Failed to update featured status');
            }
        });
    });

    // Delete post with reason
    $('.delete-post').click(function() {
        var id = $(this).data('id');
        
        Swal.fire({
            title: 'Delete Post?',
            text: "Please provide a reason for deletion. This will notify the user.",
            icon: 'warning',
            input: 'textarea',
            inputPlaceholder: 'Enter reason here...',
            showCancelButton: true,
            confirmButtonColor: '#d33',
            confirmButtonText: 'Yes, delete it!',
            preConfirm: (reason) => {
                if (!reason) {
                    Swal.showValidationMessage('Deletion reason is required')
                }
                return reason;
            }
        }).then((result) => {
            if (result.isConfirmed) {
                $.ajax({
                    url: '{{ route("admin.posts.destroy", ":id") }}'.replace(':id', id),
                    type: 'DELETE',
                    data: {
                        _token: '{{ csrf_token() }}',
                        reason: result.value
                    },
                    success: function(response) {
                        if (response.success) {
                            toastr.success('Post deleted and user notified');
                            setTimeout(function() {
                                window.location.href = '{{ route("admin.posts.index") }}';
                            }, 1500);
                        }
                    },
                    error: function(xhr) {
                        toastr.error(xhr.responseJSON?.message || 'Failed to delete post');
                    }
                });
            }
        });
    });

    // Delete comment
    $('.delete-comment').click(function() {
        var id = $(this).data('id');
        var mediaElement = $(this).closest('.media');
        
        Swal.fire({
            title: 'Delete Comment?',
            text: "This action will remove the comment permanently.",
            icon: 'warning',
            showCancelButton: true,
            confirmButtonColor: '#d33',
            confirmButtonText: 'Yes, delete it!'
        }).then((result) => {
            if (result.isConfirmed) {
                $.ajax({
                    url: '{{ route("companies.comment.delete", ":id") }}'.replace(':id', id),
                    type: 'DELETE',
                    data: { _token: '{{ csrf_token() }}' },
                    success: function(response) {
                        if (response.success) {
                            toastr.success('Comment deleted successfully');
                            setTimeout(function() {
                                location.reload();
                            }, 1000);
                        }
                    },
                    error: function() {
                        toastr.error('Failed to delete comment');
                    }
                });
            }
        });
    });
});
</script>
@endpush