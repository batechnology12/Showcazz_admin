{{-- resources/views/admin/company/partials/activity_tab.blade.php --}}
<div class="row">
    <div class="col-md-12">
        <div class="timeline">
            @forelse($activities as $activity)
            <div class="timeline-item">
                <div class="timeline-badge">
                    <i class="fa {{ $activity['icon'] }} bg-{{ $activity['color'] }}"></i>
                </div>
                <div class="timeline-body">
                    <div class="timeline-body-content">
                        <span class="text-muted">{{ $activity['created_at']->diffForHumans() }}</span>
                        <p>
                            {{ $activity['description'] }}
                            @if(isset($activity['url']))
                                <a href="{{ $activity['url'] }}" class="btn btn-xs btn-primary">View</a>
                            @endif
                        </p>
                    </div>
                </div>
            </div>
            @empty
            <div class="alert alert-info">No recent activity found</div>
            @endforelse
        </div>
    </div>
</div>

<style>
.timeline {
    position: relative;
    padding: 20px 0;
    list-style: none;
}

.timeline-item {
    position: relative;
    margin-bottom: 20px;
}

.timeline-badge {
    position: absolute;
    top: 0;
    left: 0;
    width: 40px;
    height: 40px;
    border-radius: 50%;
    text-align: center;
    line-height: 40px;
    color: #fff;
    z-index: 100;
}

.timeline-badge i {
    font-size: 16px;
}

.timeline-body {
    margin-left: 60px;
    padding: 15px;
    background: #f4f4f4;
    border-radius: 4px;
}

.timeline-body-content {
    color: #333;
}

.bg-blue { background-color: #36c6d3; }
.bg-green { background-color: #26c281; }
.bg-purple { background-color: #8775a7; }
.bg-yellow { background-color: #F4D03F; }
</style>