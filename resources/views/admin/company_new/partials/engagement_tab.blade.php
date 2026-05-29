{{-- resources/views/admin/company_new/partials/engagement_tab.blade.php --}}
<div class="row">
    <div class="col-md-12">
        <ul class="nav nav-pills">
            <li class="active"><a href="#likes" data-toggle="pill">Likes ({{ $likes->total() }})</a></li>
            <li><a href="#comments" data-toggle="pill">Comments ({{ $comments->total() }})</a></li>
            <li><a href="#shares" data-toggle="pill">Shares ({{ $shares->total() }})</a></li>
            <li><a href="#views" data-toggle="pill">Views ({{ $views->total() }})</a></li>
            <li><a href="#analytics" data-toggle="pill">Analytics</a></li>
        </ul>

        <div class="tab-content margin-top-20">
            {{-- Likes Tab --}}
            <div class="tab-pane active" id="likes">
                <table class="table table-striped table-bordered table-hover" id="likesTable">
                    <thead>
                        <tr>
                            <th>ID</th>
                            <th>User</th>
                            <th>User Type</th>
                            <th>Post</th>
                            <th>Liked At</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($likes as $like)
                        <tr>
                            <td>{{ $like->id }}</td>
                            <td>
                                @if($like->user)
                                    @if($like->user->image)
                                        <img src="{{ asset('user_images/' . $like->user->image) }}" 
                                             style="max-width: 30px; max-height: 30px; border-radius: 50%;">
                                    @endif
                                    {{ $like->user->name }}
                                @else
                                    <span class="label label-danger">User Deleted</span>
                                @endif
                            </td>
                            <td>{{ $like->user->usertype ?? 'N/A' }}</td>
                            <td>
                                @if($like->post)
                                    <a href="{{ route('companies.post.show', $like->post_id) }}">
                                        {{ \Illuminate\Support\Str::limit($like->post->title, 50) }}
                                    </a>
                                @else
                                    <span class="label label-danger">Post Deleted</span>
                                @endif
                            </td>
                            <td>{{ $like->created_at->format('d M Y h:i A') }}</td>
                            <td>
                                @if($like->post)
                                    <a href="{{ route('companies.post.show', $like->post_id) }}" 
                                       class="btn btn-xs btn-primary">
                                        <i class="fa fa-eye"></i>
                                    </a>
                                @endif
                            </td>
                        </tr>
                        @empty
                        <tr>
                            <td colspan="6" class="text-center">No likes found</td>
                        </tr>
                        @endforelse
                    </tbody>
                </table>
                <div class="text-center">
                    {{ $likes->links() }}
                </div>
            </div>

            {{-- Comments Tab --}}
            <div class="tab-pane" id="comments">
                <table class="table table-striped table-bordered table-hover" id="commentsTable">
                    <thead>
                        <tr>
                            <th>ID</th>
                            <th>User</th>
                            <th>Comment</th>
                            <th>Post</th>
                            <th>Status</th>
                            <th>Created At</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($comments as $comment)
                        <tr id="commentRow{{ $comment->id }}">
                            <td>{{ $comment->id }}</td>
                            <td>
                                @if($comment->user)
                                    @if($comment->user->image)
                                        <img src="{{ asset('user_images/' . $comment->user->image) }}" 
                                             style="max-width: 30px; max-height: 30px; border-radius: 50%;">
                                    @endif
                                    {{ $comment->user->name }}
                                @else
                                    <span class="label label-danger">User Deleted</span>
                                @endif
                            </td>
                            <td>{{ \Illuminate\Support\Str::limit($comment->content, 100) }}</td>
                            <td>
                                @if($comment->post)
                                    <a href="{{ route('companies.post.show', $comment->post_id) }}">
                                        {{ \Illuminate\Support\Str::limit($comment->post->title, 50) }}
                                    </a>
                                @else
                                    <span class="label label-danger">Post Deleted</span>
                                @endif
                            </td>
                            <td>
                                <button class="btn btn-xs toggle-comment-status 
                                    {{ $comment->is_active ? 'btn-success' : 'btn-warning' }}" 
                                    data-id="{{ $comment->id }}">
                                    {{ $comment->is_active ? 'Active' : 'Inactive' }}
                                </button>
                            </td>
                            <td>{{ $comment->created_at->format('d M Y h:i A') }}</td>
                            <td>
                                <button class="btn btn-xs btn-danger delete-comment" 
                                        data-id="{{ $comment->id }}">
                                    <i class="fa fa-trash"></i>
                                </button>
                            </td>
                        </tr>
                        @empty
                        <tr>
                            <td colspan="7" class="text-center">No comments found</td>
                        </tr>
                        @endforelse
                    </tbody>
                </table>
                <div class="text-center">
                    {{ $comments->links() }}
                </div>
            </div>

            {{-- Shares Tab --}}
            <div class="tab-pane" id="shares">
                <table class="table table-striped table-bordered table-hover" id="sharesTable">
                    <thead>
                        <tr>
                            <th>ID</th>
                            <th>User</th>
                            <th>Shared To</th>
                            <th>Post</th>
                            <th>Shared At</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($shares as $share)
                        <tr>
                            <td>{{ $share->id }}</td>
                            <td>
                                @if($share->user)
                                    @if($share->user->image)
                                        <img src="{{ asset('user_images/' . $share->user->image) }}" 
                                             style="max-width: 30px; max-height: 30px; border-radius: 50%;">
                                    @endif
                                    {{ $share->user->name }}
                                @else
                                    <span class="label label-danger">User Deleted</span>
                                @endif
                            </td>
                            <td>
                                @if($share->sharedToUser)
                                    {{ $share->sharedToUser->name }}
                                @else
                                    <span class="label label-default">Public</span>
                                @endif
                            </td>
                            <td>
                                @if($share->post)
                                    <a href="{{ route('companies.post.show', $share->post_id) }}">
                                        {{ \Illuminate\Support\Str::limit($share->post->title, 50) }}
                                    </a>
                                @else
                                    <span class="label label-danger">Post Deleted</span>
                                @endif
                            </td>
                            <td>{{ $share->created_at->format('d M Y h:i A') }}</td>
                            <td>
                                @if($share->post)
                                    <a href="{{ route('companies.post.show', $share->post_id) }}" 
                                       class="btn btn-xs btn-primary">
                                        <i class="fa fa-eye"></i>
                                    </a>
                                @endif
                            </td>
                        </tr>
                        @empty
                        <tr>
                            <td colspan="6" class="text-center">No shares found</td>
                        </tr>
                        @endforelse
                    </tbody>
                </table>
                <div class="text-center">
                    {{ $shares->links() }}
                </div>
            </div>

            {{-- Views Tab --}}
            <div class="tab-pane" id="views">
                <table class="table table-striped table-bordered table-hover" id="viewsTable">
                    <thead>
                        <tr>
                            <th>ID</th>
                            <th>User</th>
                            <th>Post</th>
                            <th>IP Address</th>
                            <th>Viewed At</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($views as $view)
                        <tr>
                            <td>{{ $view->id }}</td>
                            <td>
                                @if($view->user)
                                    @if($view->user->image)
                                        <img src="{{ asset('user_images/' . $view->user->image) }}" 
                                             style="max-width: 30px; max-height: 30px; border-radius: 50%;">
                                    @endif
                                    {{ $view->user->name }}
                                @else
                                    <span class="label label-default">Guest</span>
                                @endif
                            </td>
                            <td>
                                @if($view->post)
                                    <a href="{{ route('companies.post.show', $view->post_id) }}">
                                        {{ \Illuminate\Support\Str::limit($view->post->title, 50) }}
                                    </a>
                                @else
                                    <span class="label label-danger">Post Deleted</span>
                                @endif
                            </td>
                            <td>{{ $view->ip_address ?? 'N/A' }}</td>
                            <td>{{ $view->created_at->format('d M Y h:i A') }}</td>
                            <td>
                                @if($view->post)
                                    <a href="{{ route('companies.post.show', $view->post_id) }}" 
                                       class="btn btn-xs btn-primary">
                                        <i class="fa fa-eye"></i>
                                    </a>
                                @endif
                            </td>
                        </tr>
                        @empty
                        <tr>
                            <td colspan="6" class="text-center">No views found</td>
                        </tr>
                        @endforelse
                    </tbody>
                </table>
                <div class="text-center">
                    {{ $views->links() }}
                </div>
            </div>

            {{-- Analytics Tab --}}
            <div class="tab-pane" id="analytics">
                <div class="row">
                    <div class="col-md-6">
                        <div class="portlet light bordered">
                            <div class="portlet-title">
                                <div class="caption">
                                    <i class="fa fa-calendar"></i>
                                    <span class="caption-subject">Engagement by Day of Week</span>
                                </div>
                            </div>
                            <div class="portlet-body">
                                <canvas id="dayOfWeekChart" height="300"></canvas>
                            </div>
                        </div>
                    </div>
                    <div class="col-md-6">
                        <div class="portlet light bordered">
                            <div class="portlet-title">
                                <div class="caption">
                                    <i class="fa fa-bar-chart"></i>
                                    <span class="caption-subject">Top Liked Posts</span>
                                </div>
                            </div>
                            <div class="portlet-body">
                                <table class="table table-striped">
                                    <thead>
                                        <tr>
                                            <th>Post Title</th>
                                            <th>Likes</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        @forelse($analytics['top_posts']['liked'] as $post)
                                        <tr>
                                            <td>
                                                <a href="{{ route('companies.post.show', $post->id) }}">
                                                    {{ \Illuminate\Support\Str::limit($post->title, 50) }}
                                                </a>
                                            </td>
                                            <td><span class="badge">{{ $post->likes_count }}</span></td>
                                        </tr>
                                        @empty
                                        <tr>
                                            <td colspan="2" class="text-center">No data</td>
                                        </tr>
                                        @endforelse
                                    </tbody>
                                </table>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="row">
                    <div class="col-md-6">
                        <div class="portlet light bordered">
                            <div class="portlet-title">
                                <div class="caption">
                                    <i class="fa fa-comments"></i>
                                    <span class="caption-subject">Top Commented Posts</span>
                                </div>
                            </div>
                            <div class="portlet-body">
                                <table class="table table-striped">
                                    <thead>
                                        <tr>
                                            <th>Post Title</th>
                                            <th>Comments</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        @forelse($analytics['top_posts']['commented'] as $post)
                                        <tr>
                                            <td>
                                                <a href="{{ route('companies.post.show', $post->id) }}">
                                                    {{ \Illuminate\Support\Str::limit($post->title, 50) }}
                                                </a>
                                            </td>
                                            <td><span class="badge">{{ $post->comments_count }}</span></td>
                                        </tr>
                                        @empty
                                        <tr>
                                            <td colspan="2" class="text-center">No data</td>
                                        </tr>
                                        @endforelse
                                    </tbody>
                                </table>
                            </div>
                        </div>
                    </div>
                    <div class="col-md-6">
                        <div class="portlet light bordered">
                            <div class="portlet-title">
                                <div class="caption">
                                    <i class="fa fa-eye"></i>
                                    <span class="caption-subject">Most Viewed Posts</span>
                                </div>
                            </div>
                            <div class="portlet-body">
                                <table class="table table-striped">
                                    <thead>
                                        <tr>
                                            <th>Post Title</th>
                                            <th>Views</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        @forelse($analytics['top_posts']['viewed'] as $post)
                                        <tr>
                                            <td>
                                                <a href="{{ route('companies.post.show', $post->id) }}">
                                                    {{ \Illuminate\Support\Str::limit($post->title, 50) }}
                                                </a>
                                            </td>
                                            <td><span class="badge">{{ $post->views_count }}</span></td>
                                        </tr>
                                        @empty
                                        <tr>
                                            <td colspan="2" class="text-center">No data</td>
                                        </tr>
                                        @endforelse
                                    </tbody>
                                </table>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="row">
                    <div class="col-md-12">
                        <div class="portlet light bordered">
                            <div class="portlet-title">
                                <div class="caption">
                                    <i class="fa fa-pie-chart"></i>
                                    <span class="caption-subject">Engagement Summary</span>
                                </div>
                            </div>
                            <div class="portlet-body">
                                <div class="row text-center">
                                    <div class="col-md-3">
                                        <div class="well">
                                            <h2>{{ number_format($analytics['engagement']['likes']) }}</h2>
                                            <p>Total Likes</p>
                                        </div>
                                    </div>
                                    <div class="col-md-3">
                                        <div class="well">
                                            <h2>{{ number_format($analytics['engagement']['comments']) }}</h2>
                                            <p>Total Comments</p>
                                        </div>
                                    </div>
                                    <div class="col-md-3">
                                        <div class="well">
                                            <h2>{{ number_format($analytics['engagement']['shares']) }}</h2>
                                            <p>Total Shares</p>
                                        </div>
                                    </div>
                                    <div class="col-md-3">
                                        <div class="well">
                                            <h2>{{ number_format($analytics['engagement']['views']) }}</h2>
                                            <p>Total Views</p>
                                        </div>
                                    </div>
                                </div>
                                <div class="row text-center">
                                    <div class="col-md-3">
                                        <div class="well well-sm">
                                            <strong>{{ number_format($analytics['engagement']['unique_likers']) }}</strong>
                                            <br>Unique Likers
                                        </div>
                                    </div>
                                    <div class="col-md-3">
                                        <div class="well well-sm">
                                            <strong>{{ number_format($analytics['engagement']['unique_commenters']) }}</strong>
                                            <br>Unique Commenters
                                        </div>
                                    </div>
                                    <div class="col-md-3">
                                        <div class="well well-sm">
                                            <strong>{{ number_format($analytics['engagement']['unique_sharers']) }}</strong>
                                            <br>Unique Sharers
                                        </div>
                                    </div>
                                    <div class="col-md-3">
                                        <div class="well well-sm">
                                            <strong>{{ number_format($analytics['engagement']['unique_viewers']) }}</strong>
                                            <br>Unique Viewers
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

@push('scripts')
<script src="https://cdnjs.cloudflare.com/ajax/libs/Chart.js/2.9.4/Chart.min.js"></script>
<script>
$(document).ready(function() {
    // Day of Week Chart
    var dayOfWeekCtx = document.getElementById('dayOfWeekChart').getContext('2d');
    var dayOfWeekData = @json($analytics['engagement']['by_day_of_week']);
    
    new Chart(dayOfWeekCtx, {
        type: 'bar',
        data: {
            labels: dayOfWeekData.map(item => item.day_name),
            datasets: [{
                label: 'Engagement',
                data: dayOfWeekData.map(item => item.count),
                backgroundColor: '#36c6d3',
                borderColor: '#2b9ca8',
                borderWidth: 1
            }]
        },
        options: {
            responsive: true,
            maintainAspectRatio: false,
            scales: {
                y: {
                    beginAtZero: true,
                    ticks: {
                        stepSize: 1
                    }
                }
            }
        }
    });
});
</script>
@endpush