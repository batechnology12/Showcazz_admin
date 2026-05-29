{{-- resources/views/admin/analytics/dashboard.blade.php --}}
@extends('admin.layouts.admin_layout')

@section('content')
<div class="page-content-wrapper">
    <div class="page-content">
        <!-- BEGIN PAGE BAR -->
        <div class="page-bar">
            <ul class="page-breadcrumb">
                <li><a href="{{ route('admin.home') }}">Home</a><i class="fa fa-circle"></i></li>
                <li><a href="javascript:;">Analytics</a><i class="fa fa-circle"></i></li>
                <li><span>Dashboard</span></li>
            </ul>
        </div>

        <h3 class="page-title">Firebase Analytics Dashboard</h3>

        <!-- Period Selector -->
        <div class="row">
            <div class="col-md-12">
                <div class="portlet light bordered">
                    <div class="portlet-title">
                        <div class="caption">
                            <i class="fa fa-calendar"></i>
                            <span class="caption-subject font-dark sbold uppercase">Time Period</span>
                        </div>
                        <div class="actions">
                            <div class="btn-group">
                                <button class="btn btn-default btn-sm period-btn" data-days="7">7 Days</button>
                                <button class="btn btn-default btn-sm period-btn active" data-days="30">30 Days</button>
                                <button class="btn btn-default btn-sm period-btn" data-days="90">90 Days</button>
                            </div>
                            <button id="refreshData" class="btn btn-sm btn-primary">
                                <i class="fa fa-refresh"></i> Refresh
                            </button>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Stats Cards Row 1 -->
        <div class="row">
            <div class="col-lg-3 col-md-3 col-sm-6 col-xs-12">
                <div class="dashboard-stat blue">
                    <div class="visual"><i class="fa fa-users"></i></div>
                    <div class="details">
                        <div class="number" id="dau">--</div>
                        <div class="desc">Daily Active Users</div>
                    </div>
                </div>
            </div>
            <div class="col-lg-3 col-md-3 col-sm-6 col-xs-12">
                <div class="dashboard-stat red">
                    <div class="visual"><i class="fa fa-calendar"></i></div>
                    <div class="details">
                        <div class="number" id="wau">--</div>
                        <div class="desc">Weekly Active Users</div>
                    </div>
                </div>
            </div>
            <div class="col-lg-3 col-md-3 col-sm-6 col-xs-12">
                <div class="dashboard-stat green">
                    <div class="visual"><i class="fa fa-calendar-plus-o"></i></div>
                    <div class="details">
                        <div class="number" id="mau">--</div>
                        <div class="desc">Monthly Active Users</div>
                    </div>
                </div>
            </div>
            <div class="col-lg-3 col-md-3 col-sm-6 col-xs-12">
                <div class="dashboard-stat purple">
                    <div class="visual"><i class="fa fa-users"></i></div>
                    <div class="details">
                        <div class="number" id="totalUsers">--</div>
                        <div class="desc">Total Registered Users</div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Stats Cards Row 2 -->
        <div class="row">
            <div class="col-lg-3 col-md-3 col-sm-6 col-xs-12">
                <div class="dashboard-stat yellow">
                    <div class="visual"><i class="fa fa-file-text"></i></div>
                    <div class="details">
                        <div class="number" id="totalPosts">--</div>
                        <div class="desc">Total Posts</div>
                    </div>
                </div>
            </div>
            <div class="col-lg-3 col-md-3 col-sm-6 col-xs-12">
                <div class="dashboard-stat info">
                    <div class="visual"><i class="fa fa-envelope"></i></div>
                    <div class="details">
                        <div class="number" id="totalMessages">--</div>
                        <div class="desc">Total Messages</div>
                    </div>
                </div>
            </div>
            <div class="col-lg-3 col-md-3 col-sm-6 col-xs-12">
                <div class="dashboard-stat blue">
                    <div class="visual"><i class="fa fa-comment"></i></div>
                    <div class="details">
                        <div class="number" id="totalComments">--</div>
                        <div class="desc">Total Comments</div>
                    </div>
                </div>
            </div>
            <div class="col-lg-3 col-md-3 col-sm-6 col-xs-12">
                <div class="dashboard-stat red">
                    <div class="visual"><i class="fa fa-heart"></i></div>
                    <div class="details">
                        <div class="number" id="totalLikes">--</div>
                        <div class="desc">Total Likes</div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Daily Activity Chart -->
        <div class="row">
            <div class="col-md-12">
                <div class="portlet light bordered">
                    <div class="portlet-title">
                        <div class="caption">
                            <i class="fa fa-line-chart"></i>
                            <span class="caption-subject font-dark sbold uppercase">Daily Activity Trends</span>
                        </div>
                    </div>
                    <div class="portlet-body">
                        <canvas id="dailyActivityChart" height="300" style="max-height: 300px;"></canvas>
                    </div>
                </div>
            </div>
        </div>

        <!-- User Growth & Retention -->
        <div class="row">
            <div class="col-md-6">
                <div class="portlet light bordered">
                    <div class="portlet-title">
                        <div class="caption">
                            <i class="fa fa-line-chart"></i>
                            <span class="caption-subject font-dark sbold uppercase">User Growth</span>
                        </div>
                    </div>
                    <div class="portlet-body">
                        <canvas id="userGrowthChart" height="250" style="max-height: 250px;"></canvas>
                    </div>
                </div>
            </div>
            <div class="col-md-6">
                <div class="portlet light bordered">
                    <div class="portlet-title">
                        <div class="caption">
                            <i class="fa fa-pie-chart"></i>
                            <span class="caption-subject font-dark sbold uppercase">User Type Distribution</span>
                        </div>
                    </div>
                    <div class="portlet-body">
                        <canvas id="userTypeChart" height="250" style="max-height: 250px;"></canvas>
                        <div class="text-center mt-20" id="userTypeStats"></div>
                    </div>
                </div>
            </div>
        </div>

        <div id="loadingOverlay" style="display: none; position: fixed; top: 0; left: 0; width: 100%; height: 100%; background: rgba(0,0,0,0.5); z-index: 9999;">
            <div style="position: absolute; top: 50%; left: 50%; transform: translate(-50%, -50%); background: white; padding: 30px; border-radius: 8px; text-align: center;">
                <i class="fa fa-spinner fa-spin fa-3x text-primary"></i>
                <p class="mt-10">Loading analytics data...</p>
            </div>
        </div>
    </div>
</div>
@endsection

@push('styles')
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/Chart.js/2.9.4/Chart.min.css">
<style>
    .dashboard-stat .details .number { font-size: 24px; font-weight: 600; }
    .period-btn.active { background-color: #36c6d3; color: white; border-color: #36c6d3; }
    .mt-20 { margin-top: 20px; }
</style>
@endpush

@push('scripts')
<script src="https://cdnjs.cloudflare.com/ajax/libs/Chart.js/2.9.4/Chart.min.js"></script>
<script>
// Chart instances
var dailyActivityChart = null;
var userGrowthChart = null;
var userTypeChart = null;

// Debounce function to limit API calls
function debounce(func, wait) {
    var timeout;
    return function executedFunction() {
        var later = function() {
            clearTimeout(timeout);
            func();
        };
        clearTimeout(timeout);
        timeout = setTimeout(later, wait);
    };
}

// Throttle function for chart updates
function throttle(func, limit) {
    let inThrottle;
    return function() {
        const args = arguments;
        const context = this;
        if (!inThrottle) {
            func.apply(context, args);
            inThrottle = true;
            setTimeout(() => inThrottle = false, limit);
        }
    };
}

$(document).ready(function() {
    // Initial load with debounce
    var debouncedLoad = debounce(function() {
        loadAnalyticsData(currentDays);
    }, 300);
    
    loadAnalyticsData(30);
    
    // Period selector
    $('.period-btn').click(function() {
        $('.period-btn').removeClass('active');
        $(this).addClass('active');
        currentDays = parseInt($(this).data('days'));
        debouncedLoad();
    });
    
    $('#refreshData').click(function() {
        loadAnalyticsData(currentDays);
    });
});

var currentDays = 30;

function loadAnalyticsData(days) {
    $('#loadingOverlay').show();
    
    $.ajax({
        url: '{{ route("admin.analytics.api.dashboard") }}',
        type: 'GET',
        data: { days: days },
        timeout: 30000,
        success: function(response) {
            if (response.success) {
                updateDashboard(response.data);
            } else {
                toastr.error('Failed to load analytics data');
            }
        },
        error: function(xhr) {
            console.error('Error:', xhr);
            toastr.error('Error loading analytics data');
        },
        complete: function() {
            $('#loadingOverlay').hide();
        }
    });
}

function updateDashboard(data) {
    // Update stats cards with requestAnimationFrame for smooth updates
    requestAnimationFrame(function() {
        $('#dau').text(formatNumber(data.dau || 0));
        $('#wau').text(formatNumber(data.wau || 0));
        $('#mau').text(formatNumber(data.mau || 0));
        $('#totalUsers').text(formatNumber(data.local.total_users || 0));
        $('#totalPosts').text(formatNumber(data.local.total_posts || 0));
        $('#totalMessages').text(formatNumber(data.local.total_messages || 0));
        $('#totalComments').text(formatNumber(data.local.total_comments || 0));
        $('#totalLikes').text(formatNumber(data.local.total_likes || 0));
    });
    
    // Update charts with throttling
    var throttledDailyChart = throttle(function() {
        updateDailyActivityChart(data.daily_activity || []);
    }, 500);
    
    var throttledGrowthChart = throttle(function() {
        updateUserGrowthChart(data.user_growth || []);
    }, 500);
    
    var throttledTypeChart = throttle(function() {
        updateUserTypeChart(data.users.by_type || { users: 0, companies: 0 });
    }, 500);
    
    throttledDailyChart();
    throttledGrowthChart();
    throttledTypeChart();
    
    // Update user type stats (lightweight)
    $('#userTypeStats').html(`
        <div class="row">
            <div class="col-md-6">
                <div class="well well-sm text-center">
                    <h4>Regular Users</h4>
                    <h3 class="text-primary">${formatNumber(data.users.by_type.users)}</h3>
                </div>
            </div>
            <div class="col-md-6">
                <div class="well well-sm text-center">
                    <h4>Companies</h4>
                    <h3 class="text-success">${formatNumber(data.users.by_type.companies)}</h3>
                </div>
            </div>
        </div>
    `);
}

function updateDailyActivityChart(dailyActivity) {
    var ctx = document.getElementById('dailyActivityChart').getContext('2d');
    
    // Limit data points for better performance
    var maxDataPoints = 30;
    var sampledData = dailyActivity;
    if (dailyActivity.length > maxDataPoints) {
        var step = Math.ceil(dailyActivity.length / maxDataPoints);
        sampledData = dailyActivity.filter((_, index) => index % step === 0);
    }
    
    var labels = sampledData.map(item => item.date);
    var activeUsers = sampledData.map(item => item.active_users);
    var newUsers = sampledData.map(item => item.new_users);
    
    if (dailyActivityChart) {
        dailyActivityChart.destroy();
        dailyActivityChart = null;
    }
    
    dailyActivityChart = new Chart(ctx, {
        type: 'line',
        data: {
            labels: labels,
            datasets: [
                {
                    label: 'Active Users',
                    data: activeUsers,
                    borderColor: '#36c6d3',
                    backgroundColor: 'rgba(54, 198, 211, 0.1)',
                    fill: true,
                    tension: 0.4,
                    pointRadius: 2,
                    pointHoverRadius: 5
                },
                {
                    label: 'New Users',
                    data: newUsers,
                    borderColor: '#e7505a',
                    backgroundColor: 'rgba(231, 80, 90, 0.1)',
                    fill: true,
                    tension: 0.4,
                    pointRadius: 2,
                    pointHoverRadius: 5
                }
            ]
        },
        options: {
            responsive: true,
            maintainAspectRatio: false,
            animation: {
                duration: 500,
                easing: 'easeOutQuart'
            },
            scales: {
                y: {
                    beginAtZero: true,
                    ticks: { stepSize: 1 }
                }
            },
            tooltips: {
                mode: 'index',
                intersect: false,
                enabled: true
            },
            elements: {
                line: {
                    tension: 0.4
                }
            }
        }
    });
}

function updateUserGrowthChart(userGrowth) {
    var ctx = document.getElementById('userGrowthChart').getContext('2d');
    
    var labels = userGrowth.map(item => item.date);
    var counts = userGrowth.map(item => item.count);
    
    if (userGrowthChart) {
        userGrowthChart.destroy();
        userGrowthChart = null;
    }
    
    userGrowthChart = new Chart(ctx, {
        type: 'line',
        data: {
            labels: labels,
            datasets: [{
                label: 'Total Users',
                data: counts,
                borderColor: '#36c6d3',
                backgroundColor: 'rgba(54, 198, 211, 0.1)',
                fill: true,
                tension: 0.4,
                pointRadius: 1,
                pointHoverRadius: 4
            }]
        },
        options: {
            responsive: true,
            maintainAspectRatio: false,
            animation: {
                duration: 500
            },
            scales: {
                y: {
                    beginAtZero: true,
                    ticks: { 
                        callback: function(value) { 
                            return formatNumber(value); 
                        } 
                    }
                }
            }
        }
    });
}

function updateUserTypeChart(userTypes) {
    var ctx = document.getElementById('userTypeChart').getContext('2d');
    
    if (userTypeChart) {
        userTypeChart.destroy();
        userTypeChart = null;
    }
    
    userTypeChart = new Chart(ctx, {
        type: 'doughnut',
        data: {
            labels: ['Regular Users', 'Companies'],
            datasets: [{
                data: [userTypes.users || 0, userTypes.companies || 0],
                backgroundColor: ['#36c6d3', '#e7505a'],
                borderWidth: 0
            }]
        },
        options: {
            responsive: true,
            maintainAspectRatio: false,
            animation: {
                duration: 500
            },
            legend: { 
                position: 'bottom',
                labels: { boxWidth: 12 }
            }
        }
    });
}

function formatNumber(num) {
    if (num >= 1000000) return (num / 1000000).toFixed(1) + 'M';
    if (num >= 1000) return (num / 1000).toFixed(1) + 'K';
    return num.toString();
}
</script>
@endpush