{{-- resources/views/admin/analytics/realtime.blade.php --}}
@extends('admin.layouts.admin_layout')

@section('content')
<div class="page-content-wrapper">
    <div class="page-content">
        <div class="page-bar">
            <ul class="page-breadcrumb">
                <li><a href="{{ route('admin.home') }}">Home</a><i class="fa fa-circle"></i></li>
                <li><a href="javascript:;">Analytics</a><i class="fa fa-circle"></i></li>
                <li><span>Real-time Users</span></li>
            </ul>
        </div>
        
        <h3 class="page-title">Real-time Active Users</h3>
        
        <div class="row">
            <div class="col-md-12">
                <div class="portlet light bordered">
                    <div class="portlet-title">
                        <div class="caption">
                            <i class="fa fa-clock-o"></i>
                            <span class="caption-subject font-dark sbold uppercase">Live User Activity</span>
                        </div>
                        <div class="actions">
                            <button id="startRealtime" class="btn btn-sm btn-success">
                                <i class="fa fa-play"></i> Start Live Updates
                            </button>
                            <button id="stopRealtime" class="btn btn-sm btn-danger" disabled>
                                <i class="fa fa-stop"></i> Stop
                            </button>
                            <button id="refreshRealtime" class="btn btn-sm btn-primary">
                                <i class="fa fa-refresh"></i> Refresh Now
                            </button>
                        </div>
                    </div>
                    <div class="portlet-body text-center">
                        <div class="row">
                            <div class="col-md-6">
                                <div class="well well-lg">
                                    <div class="realtime-number" id="activeUsers5min" style="font-size: 70px; color: #36c6d3;">--</div>
                                    <h4>Active Users (Last 5 Minutes)</h4>
                                </div>
                            </div>
                            <div class="col-md-6">
                                <div class="well well-lg">
                                    <div class="realtime-number" id="activeUsers1min" style="font-size: 70px; color: #e7505a;">--</div>
                                    <h4>Active Users (Last 1 Minute)</h4>
                                </div>
                            </div>
                        </div>
                        <div class="progress" style="height: 30px; margin-top: 20px;">
                            <div id="activityProgress" class="progress-bar progress-bar-success progress-bar-striped" role="progressbar" style="width: 0%;">
                                <span id="progressPercent">0%</span>
                            </div>
                        </div>
                        <p class="text-muted" id="lastUpdated">Last updated: --</p>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection

@push('scripts')
<script>
// Optimized real-time monitoring
var intervalId = null;
var maxUsers = 1000;
var isUpdating = false;
var animationFrameId = null;

$(document).ready(function() {
    // Initial load with requestAnimationFrame
    requestAnimationFrame(function() {
        loadRealtimeData();
    });
    
    // Start real-time updates
    $('#startRealtime').click(function() {
        if (intervalId) {
            clearInterval(intervalId);
            intervalId = null;
        }
        
        // Use longer interval for better performance (15 seconds instead of 10)
        intervalId = setInterval(function() {
            // Throttle updates
            if (!isUpdating) {
                requestAnimationFrame(function() {
                    loadRealtimeData();
                });
            }
        }, 15000);
        
        toastr.success('Real-time updates started (every 15 seconds)');
        $(this).prop('disabled', true);
        $('#stopRealtime').prop('disabled', false);
    });
    
    // Stop real-time updates
    $('#stopRealtime').click(function() {
        if (intervalId) {
            clearInterval(intervalId);
            intervalId = null;
            toastr.info('Real-time updates stopped');
            $(this).prop('disabled', true);
            $('#startRealtime').prop('disabled', false);
        }
    });
    
    // Manual refresh
    $('#refreshRealtime').click(function() {
        if (!isUpdating) {
            requestAnimationFrame(function() {
                loadRealtimeData();
            });
            toastr.info('Data refreshed manually');
        }
    });
    
    // Cleanup on page unload
    $(window).on('beforeunload', function() {
        if (intervalId) {
            clearInterval(intervalId);
        }
        if (animationFrameId) {
            cancelAnimationFrame(animationFrameId);
        }
    });
});

function loadRealtimeData() {
    if (isUpdating) return;
    
    isUpdating = true;
    
    $.ajax({
        url: '{{ route("admin.analytics.api.realtime") }}',
        type: 'GET',
        timeout: 10000,
        success: function(response) {
            if (response.success) {
                updateUI(response.data);
            }
        },
        error: function(xhr) {
            console.warn('Realtime data fetch failed:', xhr.status);
            // Don't show error toast for network issues to avoid spamming
        },
        complete: function() {
            isUpdating = false;
        }
    });
}

function updateUI(data) {
    // Use requestAnimationFrame for smooth DOM updates
    animationFrameId = requestAnimationFrame(function() {
        var count5min = data.active_users_5min || 0;
        var count1min = data.active_users_1min || 0;
        
        // Update numbers with fade effect
        updateNumberWithFade('activeUsers5min', count5min);
        updateNumberWithFade('activeUsers1min', count1min);
        
        // Update last updated time
        $('#lastUpdated').text('Last updated: ' + data.last_updated);
        
        // Update progress bar
        var percentage = Math.min((count5min / maxUsers * 100), 100);
        var progressBar = $('#activityProgress');
        var progressPercent = $('#progressPercent');
        
        // Use CSS transition for smooth progress bar update
        progressBar.css('width', percentage + '%');
        progressPercent.text(Math.round(percentage) + '%');
        
        // Add active class for animation
        progressBar.addClass('active');
        
        // Simple visual feedback without heavy CSS animations
        $('.realtime-number').css('transform', 'scale(1.05)');
        setTimeout(function() {
            $('.realtime-number').css('transform', 'scale(1)');
        }, 200);
    });
}

function updateNumberWithFade(elementId, newValue) {
    var element = $('#' + elementId);
    var formattedValue = formatNumber(newValue);
    
    if (element.text() !== formattedValue) {
        // Simple fade effect without heavy CSS animations
        element.css('opacity', '0.5');
        setTimeout(function() {
            element.text(formattedValue);
            element.css('opacity', '1');
        }, 100);
    } else {
        element.text(formattedValue);
    }
}

function formatNumber(num) {
    if (num >= 1000000) return (num / 1000000).toFixed(1) + 'M';
    if (num >= 1000) return (num / 1000).toFixed(1) + 'K';
    return num.toString();
}
</script>

<style>
/* Optimized animations - use transform instead of animation for better performance */
.realtime-number {
    transition: opacity 0.15s ease-out, transform 0.2s ease-out;
    will-change: transform;
}

.progress-bar {
    transition: width 0.3s ease-out;
    will-change: width;
}

.well-lg {
    min-height: 200px;
}

/* Remove heavy pulse animation, use simple transition instead */
</style>
@endpush