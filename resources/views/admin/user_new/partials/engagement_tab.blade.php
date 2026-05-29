{{-- resources/views/admin/user_new/partials/engagement_tab.blade.php --}}
<div class="row">
    <div class="col-md-12">
        <ul class="nav nav-pills">
            <li class="active"><a href="#likes" data-toggle="pill">Likes ({{ $likes->total() }})</a></li>
            <li><a href="#comments" data-toggle="pill">Comments ({{ $comments->total() }})</a></li>
            <li><a href="#shares" data-toggle="pill">Shares ({{ $shares->total() }})</a></li>
            <li><a href="#views" data-toggle="pill">Views ({{ $views->total() }})</a></li>
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
                                    {{ $like->user->getName() }}
                                @else
                                    <span class="label label-danger">User Deleted</span>
                                @endif
                            </td>
                            <td>{{ $like->user->usertype ?? 'N/A' }}</td>
                            <td>
                                @if($like->post)
                                    <a href="">
                                        {{ \Illuminate\Support\Str::limit($like->post->title, 50) }}
                                    </a>
                                @else
                                    <span class="label label-danger">Post Deleted</span>
                                @endif
                            </td>
                            <td>{{ $like->created_at->format('d M Y h:i A') }}</td>
                            <td>
                                @if($like->post)
                                    <a href="" 
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
                                    {{ $comment->user->getName() }}
                                @else
                                    <span class="label label-danger">User Deleted</span>
                                @endif
                            </td>
                            <td>{{ \Illuminate\Support\Str::limit($comment->content, 100) }}</td>
                            <td>
                                @if($comment->post)
                                    <a href="">
                                        {{ \Illuminate\Support\Str::limit($comment->post->title, 50) }}
                                    </a>
                                @else
                                    <span class="label label-danger">Post Deleted</span>
                                @endif
                            </td>
                            <td>
                                <span class="label {{ $comment->is_active ? 'label-success' : 'label-warning' }}">
                                    {{ $comment->is_active ? 'Active' : 'Inactive' }}
                                </span>
                            </td>
                            <td>{{ $comment->created_at->format('d M Y h:i A') }}</td>
                            <td>
                                <button class="btn btn-xs btn-danger delete-comment" data-id="{{ $comment->id }}">
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
                                    {{ $share->user->getName() }}
                                @else
                                    <span class="label label-danger">User Deleted</span>
                                @endif
                            </td>
                            <td>
                                @if($share->sharedToUser)
                                    {{ $share->sharedToUser->getName() }}
                                @else
                                    <span class="label label-default">Public</span>
                                @endif
                            </td>
                            <td>
                                @if($share->post)
                                    <a href="">
                                        {{ \Illuminate\Support\Str::limit($share->post->title, 50) }}
                                    </a>
                                @else
                                    <span class="label label-danger">Post Deleted</span>
                                @endif
                            </td>
                            <td>{{ $share->created_at->format('d M Y h:i A') }}</td>
                            <td>
                                @if($share->post)
                                    <a href="" 
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
                                    {{ $view->user->getName() }}
                                @else
                                    <span class="label label-default">Guest</span>
                                @endif
                            </td>
                            <td>
                                @if($view->post)
                                    <a href="">
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
                                    <a href="" 
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
        </div>
    </div>
</div>