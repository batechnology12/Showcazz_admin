{{-- resources/views/admin/user_new/partials/posts_tab.blade.php --}}
<div class="row">
    <div class="col-md-12">
        <ul class="nav nav-pills">
            <li class="active"><a href="#allPosts" data-toggle="pill">All Posts ({{ $posts->total() }})</a></li>
            <li><a href="#jobPosts" data-toggle="pill">Job Posts ({{ $jobPosts->count() }})</a></li>
            <li><a href="#regularPosts" data-toggle="pill">Regular Posts ({{ $regularPosts->count() }})</a></li>
        </ul>
        
        <div class="tab-content margin-top-20">
            {{-- All Posts --}}
            <div class="tab-pane active" id="allPosts">
                <table class="table table-striped table-bordered table-hover" id="allPostsTable">
                    <thead>
                        <tr>
                            <th>ID</th>
                            <th>Title</th>
                            <th>Category</th>
                            <th>Type</th>
                            <th>Stats</th>
                            <th>Status</th>
                            <th>Created</th>
                            <!--<th>Actions</th>-->
                        </tr>
                    </thead>
                    <tbody>
                        @foreach($posts as $post)
                        <tr id="postRow{{ $post->id }}">
                            <td>{{ $post->id }}</td>
                            <td>
                                <strong>{{ $post->title }}</strong><br>
                                <small>{{ \Illuminate\Support\Str::limit($post->content, 50) }}</small>
                            </td>
                            <td>
                                {{ $post->category->name ?? 'N/A' }}<br>
                                <small>{{ $post->subcategory->name ?? '' }}</small>
                            </td>
                            <td>
                                @if($post->is_job_post)
                                    <span class="label label-primary">Job Post</span>
                                @else
                                    <span class="label label-info">Regular Post</span>
                                @endif
                            </td>
                            <td>
                                <i class="fa fa-eye" title="Views"></i> {{ $post->views_count ?? 0 }}<br>
                                <i class="fa fa-heart" title="Likes"></i> {{ $post->likes_count ?? 0 }}<br>
                                <i class="fa fa-comment" title="Comments"></i> {{ $post->comments_count ?? 0 }}
                            </td>
                            <td>
                                @if($post->is_published)
                                    <span class="label label-success">Published</span>
                                @else
                                    <span class="label label-warning">Draft</span>
                                @endif
                            </td>
                            <td>{{ $post->created_at->format('d M Y') }}</td>
                            <!--<td>-->
                            <!--    <button class="btn btn-xs btn-primary view-post" data-id="{{ $post->id }}">-->
                            <!--        <i class="fa fa-eye"></i>-->
                            <!--    </button>-->
                            <!--    <button class="btn btn-xs btn-danger delete-post" data-id="{{ $post->id }}">-->
                            <!--        <i class="fa fa-trash"></i>-->
                            <!--    </button>-->
                            <!--</td>-->
                        </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
            
            {{-- Job Posts --}}
            <div class="tab-pane" id="jobPosts">
                <table class="table table-striped table-bordered table-hover" id="jobPostsTable">
                    <thead>
                        <tr>
                            <th>ID</th>
                            <th>Title</th>
                            <th>Job Type</th>
                            <th>Work Mode</th>
                            <th>Location</th>
                            <th>Salary</th>
                          
                            <th>Deadline</th>
                            <!--<th>Actions</th>-->
                        </tr>
                    </thead>
                    <tbody>
                        @foreach($jobPosts as $post)
                        <tr>
                            <td>{{ $post->id }}</td>
                            <td>
                                <strong>{{ $post->title }}</strong><br>
                                <small>{{ $post->company_name ?? '' }}</small>
                            </td>
                            <td>{{ $post->role_type ?? 'N/A' }}</td>
                            <td>{{ $post->work_mode ?? 'N/A' }}</td>
                            <td>{{ $post->job_location ?? 'N/A' }}</td>
                            <td>
                                @if($post->stipend_amount)
                                    {{ $post->stipend_currency ?? '₹' }} {{ number_format($post->stipend_amount) }}
                                @elseif($post->ctc_amount)
                                    {{ $post->ctc_currency ?? '₹' }} {{ number_format($post->ctc_amount) }}
                                @else
                                    N/A
                                @endif
                            </td>
                            
                            <td>{{ $post->application_deadline ? date('d M Y', strtotime($post->application_deadline)) : 'N/A' }}</td>
                            <!--<td>-->
                            <!--    <button class="btn btn-xs btn-primary view-post" data-id="{{ $post->id }}">-->
                            <!--        <i class="fa fa-eye"></i>-->
                            <!--    </button>-->
                            <!--</td>-->
                        </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
            
            {{-- Regular Posts --}}
            <div class="tab-pane" id="regularPosts">
                <table class="table table-striped table-bordered table-hover" id="regularPostsTable">
                    <thead>
                        <tr>
                            <th>ID</th>
                            <th>Title</th>
                            <th>Category</th>
                            <th>Content</th>
                            <th>Media</th>
                            <th>Engagement</th>
                            <!--<th>Actions</th>-->
                        </tr>
                    </thead>
                    <tbody>
                        @foreach($regularPosts as $post)
                        <tr>
                            <td>{{ $post->id }}</td>
                            <td>
                                <strong>{{ $post->title }}</strong><br>
                                <small>{{ $post->short_description ?? '' }}</small>
                            </td>
                            <td>
                                {{ $post->category->name ?? 'N/A' }}<br>
                                <small>{{ $post->subcategory->name ?? '' }}</small>
                            </td>
                            <td>{{ \Illuminate\Support\Str::limit($post->content, 100) }}</td>
                            <td>
                                @if($post->images)
                                    @php $images = is_array($post->images) ? $post->images : json_decode($post->images, true); @endphp
                                    <span class="label label-info">{{ count($images ?: []) }} Images</span>
                                @endif
                                @if($post->files)
                                    @php $files = is_array($post->files) ? $post->files : json_decode($post->files, true); @endphp
                                    <span class="label label-warning">{{ count($files ?: []) }} Files</span>
                                @endif
                            </td>
                            <td>
                                <i class="fa fa-heart"></i> {{ $post->likes_count ?? 0 }}<br>
                                <i class="fa fa-comment"></i> {{ $post->comments_count ?? 0 }}
                            </td>
                            <!--<td>-->
                            <!--    <button class="btn btn-xs btn-primary view-post" data-id="{{ $post->id }}">-->
                            <!--        <i class="fa fa-eye"></i>-->
                            <!--    </button>-->
                            <!--</td>-->
                        </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        </div>
        
        <div class="row">
            <div class="col-md-12 text-center">
                {{ $posts->appends(['posts_page' => $posts->currentPage()])->fragment('posts')->links() }}
            </div>
        </div>
    </div>
</div>