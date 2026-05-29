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
            <div class="page-toolbar">
                <a href="{{ route('admin.reports.posts.export', ['period' => $period, 'start_date' => request('start_date'), 'end_date' => request('end_date')]) }}" class="btn btn-sm btn-success">
                    <i class="fa fa-download"></i> Export CSV
                </a>
            </div>
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
                                <a href="{{ route('admin.reports.posts.popular', ['sort' => 'views', 'period' => $period]) }}" class="btn {{ $sortBy == 'views' ? 'btn-success' : 'btn-default' }} btn-sm">Views</a>
                                <a href="{{ route('admin.reports.posts.popular', ['sort' => 'likes', 'period' => $period]) }}" class="btn {{ $sortBy == 'likes' ? 'btn-success' : 'btn-default' }} btn-sm">Likes</a>
                                <a href="{{ route('admin.reports.posts.popular', ['sort' => 'comments', 'period' => $period]) }}" class="btn {{ $sortBy == 'comments' ? 'btn-success' : 'btn-default' }} btn-sm">Comments</a>
                            </div>
                        </div>
                    </div>
                    <div class="portlet-body">
                        <div class="well">
                            <form action="{{ route('admin.reports.posts.popular') }}" method="GET" class="form-inline">
                                <input type="hidden" name="sort" value="{{ $sortBy }}">
                                <div class="form-group">
                                    <label>Period:</label>
                                    <select name="period" class="form-control input-sm" onchange="this.form.submit()">
                                        <option value="all" {{ $period == 'all' ? 'selected' : '' }}>All Time</option>
                                        <option value="today" {{ $period == 'today' ? 'selected' : '' }}>Today</option>
                                        <option value="7_days" {{ $period == '7_days' ? 'selected' : '' }}>7 Days</option>
                                        <option value="30_days" {{ $period == '30_days' ? 'selected' : '' }}>30 Days</option>
                                        <option value="90_days" {{ $period == '90_days' ? 'selected' : '' }}>90 Days</option>
                                        <option value="this_month" {{ $period == 'this_month' ? 'selected' : '' }}>This Month</option>
                                        <option value="custom" {{ $period == 'custom' ? 'selected' : '' }}>Custom Range</option>
                                    </select>
                                </div>
                                
                                @if($period == 'custom')
                                <div class="form-group margin-left-10">
                                    <label>Start:</label>
                                    <input type="date" name="start_date" class="form-control input-sm" value="{{ request('start_date', $date_range['start_formatted'] ?? '') }}">
                                </div>
                                <div class="form-group margin-left-10">
                                    <label>End:</label>
                                    <input type="date" name="end_date" class="form-control input-sm" value="{{ request('end_date', $date_range['end_formatted'] ?? '') }}">
                                </div>
                                <button type="submit" class="btn btn-primary btn-sm margin-left-10">Apply</button>
                                @endif
                            </form>
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