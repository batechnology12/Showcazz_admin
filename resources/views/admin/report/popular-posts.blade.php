{{-- resources/views/admin/report/popular-posts.blade.php --}}
@extends('admin.layouts.admin_layout')

@section('content')
<div class="page-content-wrapper">
    <div class="page-content">
        <div class="page-bar">
            <ul class="page-breadcrumb">
                <li><a href="{{ route('admin.home') }}">Home</a> <i class="fa fa-circle"></i></li>
                <li><a href="{{ route('admin.reports.posts') }}">Post Reports</a> <i class="fa fa-circle"></i></li>
                <li><span>Popular Posts</span></li>
            </ul>
        </div>

        <h3 class="page-title">Popular Posts</h3>

        <div class="row">
            <div class="col-md-12">
                <div class="portlet light bordered">
                    <div class="portlet-title">
                        <div class="caption">
                            <i class="fa fa-star"></i>
                            <span class="caption-subject font-dark sbold uppercase">Popular Posts</span>
                        </div>
                        <div class="actions">
                            <div class="btn-group">
                                <a href="{{ route('admin.reports.posts.popular', ['sort' => 'views']) }}" class="btn {{ $sortBy == 'views' ? 'btn-success' : 'btn-default' }} btn-sm">Views</a>
                                <a href="{{ route('admin.reports.posts.popular', ['sort' => 'likes']) }}" class="btn {{ $sortBy == 'likes' ? 'btn-success' : 'btn-default' }} btn-sm">Likes</a>
                                <a href="{{ route('admin.reports.posts.popular', ['sort' => 'comments']) }}" class="btn {{ $sortBy == 'comments' ? 'btn-success' : 'btn-default' }} btn-sm">Comments</a>
                            </div>
                        </div>
                    </div>
                    <div class="portlet-body">
                        <table class="table table-striped table-bordered table-hover">
                            <thead>
                                <tr>
                                    <th>ID</th>
                                    <th>Title</th>
                                    <th>Author</th>
                                    <th>Category</th>
                                    <th>Views</th>
                                    <th>Likes</th>
                                    <th>Comments</th>
                                    <th>Shares</th>
                                    <th>Created</th>
                                    <th>Actions</th>
                                </tr>
                            </thead>
                            <tbody>
                                @forelse($posts as $post)
                                <tr>
                                    <td>{{ $post->id }}</td>
                                    <td>
                                        <a href="{{ route('admin.posts.show', $post->id) }}">
                                            {{ \Illuminate\Support\Str::limit($post->title, 50) }}
                                        </a>
                                    </td>
                                    <td>{{ $post->user->name ?? 'Unknown' }}</td>
                                    <td>{{ $post->category->name ?? 'N/A' }}</td>
                                    <td>{{ number_format($post->views_count ?? 0) }}</td>
                                    <td>{{ number_format($post->likes_count ?? 0) }}</td>
                                    <td>{{ number_format($post->comments_count ?? 0) }}</td>
                                    <td>{{ number_format($post->shares_count ?? 0) }}</td>
                                    <td>{{ $post->created_at->format('d M Y') }}</td>
                                    <td>
                                        <a href="{{ route('admin.posts.show', $post->id) }}" class="btn btn-xs btn-primary">
                                            <i class="fa fa-eye"></i>
                                        </a>
                                    </td>
                                </tr>
                                @empty
                                <tr><td colspan="10" class="text-center">No posts found</td></tr>
                                @endforelse
                            </tbody>
                        </table>
                        
                        <div class="text-center">
                            {{ $posts->appends(request()->query())->links() }}
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection