{{-- resources/views/admin/analytics/retention.blade.php --}}
@extends('admin.layouts.admin_layout')

@section('content')
<div class="page-content-wrapper">
    <div class="page-content">
        <div class="page-bar">
            <ul class="page-breadcrumb">
                <li><a href="{{ route('admin.home') }}">Home</a><i class="fa fa-circle"></i></li>
                <li><a href="javascript:;">Analytics</a><i class="fa fa-circle"></i></li>
                <li><span>User Retention</span></li>
            </ul>
        </div>
        
        <h3 class="page-title">User Retention Analysis</h3>
        
        <div class="row">
            <div class="col-md-12">
                <div class="portlet light bordered">
                    <div class="portlet-title">
                        <div class="caption">
                            <i class="fa fa-users"></i>
                            <span class="caption-subject font-dark sbold uppercase">Cohort Retention</span>
                        </div>
                        <div class="actions">
                            <button id="refreshRetention" class="btn btn-sm btn-primary">
                                <i class="fa fa-refresh"></i> Refresh
                            </button>
                        </div>
                    </div>
                    <div class="portlet-body">
                        <canvas id="retentionChart" height="400" style="max-height: 400px;"></canvas>
                        <div class="table-responsive mt-20">
                            <div id="retentionTable"></div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection

@push('scripts')
<script src="https://cdn.jsdelivr.net/npm/chart.js@3.9.1/dist/chart.min.js"></script>
<script>
// Optimized retention chart
var retentionChart = null;
var isLoading = false;
var chartUpdateQueued = false;

$(document).ready(function() {
    // Initial load with requestAnimationFrame
    requestAnimationFrame(function() {
        loadRetentionData();
    });
    
    // Refresh button with debounce
    var refreshDebounce = false;
    $('#refreshRetention').click(function() {
        if (refreshDebounce) return;
        refreshDebounce = true;
        
        if (!isLoading) {
            loadRetentionData();
        }
        
        setTimeout(function() {
            refreshDebounce = false;
        }, 2000);
    });
});

function loadRetentionData() {
    if (isLoading) return;
    
    isLoading = true;
    $('#loadingOverlay').show();
    
    $.ajax({
        url: '{{ route("admin.analytics.api.retention") }}',
        type: 'GET',
        timeout: 15000,
        success: function(response) {
            if (response && typeof response === 'object') {
                // Use requestAnimationFrame for chart updates
                requestAnimationFrame(function() {
                    updateRetentionChart(response);
                    updateRetentionTable(response);
                });
            } else {
                toastr.error('Failed to load retention data');
            }
        },
        error: function(xhr) {
            console.error('Error loading retention data:', xhr.status);
            toastr.error('Error loading retention data');
        },
        complete: function() {
            isLoading = false;
            $('#loadingOverlay').hide();
        }
    });
}

function updateRetentionChart(retentionData) {
    var ctx = document.getElementById('retentionChart').getContext('2d');
    
    var labels = Object.keys(retentionData).map(key => {
        return key.replace('_', ' ').toUpperCase();
    });
    var values = Object.values(retentionData);
    
    // Destroy existing chart if it exists
    if (retentionChart) {
        retentionChart.destroy();
        retentionChart = null;
    }
    
    // Create new chart with optimized options
    retentionChart = new Chart(ctx, {
        type: 'bar',
        data: {
            labels: labels,
            datasets: [{
                label: 'Retention Rate (%)',
                data: values,
                backgroundColor: '#36c6d3',
                borderColor: '#2b9ca8',
                borderWidth: 1,
                borderRadius: 4,
                barPercentage: 0.7,
                categoryPercentage: 0.8
            }]
        },
        options: {
            responsive: true,
            maintainAspectRatio: false,
            animation: {
                duration: 500,
                easing: 'easeOutQuart'
            },
            plugins: {
                tooltip: {
                    callbacks: {
                        label: function(context) {
                            return context.raw + '%';
                        }
                    }
                },
                legend: {
                    display: true,
                    position: 'top',
                    labels: {
                        boxWidth: 12,
                        usePointStyle: true
                    }
                }
            },
            scales: {
                y: {
                    beginAtZero: true,
                    max: 100,
                    grid: {
                        color: 'rgba(0,0,0,0.05)'
                    },
                    ticks: {
                        callback: function(value) {
                            return value + '%';
                        },
                        stepSize: 20
                    }
                },
                x: {
                    grid: {
                        display: false
                    },
                    ticks: {
                        autoSkip: true,
                        maxRotation: 0,
                        font: {
                            size: 11
                        }
                    }
                }
            },
            elements: {
                bar: {
                    backgroundColor: '#36c6d3',
                    hoverBackgroundColor: '#2b9ca8'
                }
            },
            layout: {
                padding: {
                    left: 10,
                    right: 10,
                    top: 10,
                    bottom: 10
                }
            }
        }
    });
}

function updateRetentionTable(retentionData) {
    // Use DocumentFragment for better performance
    var fragment = document.createDocumentFragment();
    var table = document.createElement('table');
    table.className = 'table table-striped table-bordered';
    
    // Create header
    var thead = document.createElement('thead');
    var headerRow = document.createElement('tr');
    ['Cohort', 'Retention Rate', 'Status'].forEach(function(text) {
        var th = document.createElement('th');
        th.textContent = text;
        headerRow.appendChild(th);
    });
    thead.appendChild(headerRow);
    table.appendChild(thead);
    
    // Create body
    var tbody = document.createElement('tbody');
    
    for (var key in retentionData) {
        var rate = retentionData[key];
        var status = '';
        var statusClass = '';
        
        if (rate >= 50) {
            status = 'Excellent';
            statusClass = 'label-success';
        } else if (rate >= 30) {
            status = 'Good';
            statusClass = 'label-warning';
        } else if (rate >= 15) {
            status = 'Average';
            statusClass = 'label-info';
        } else {
            status = 'Poor';
            statusClass = 'label-danger';
        }
        
        var row = document.createElement('tr');
        
        // Cohort cell
        var cohortCell = document.createElement('td');
        cohortCell.innerHTML = '<strong>' + key.replace('_', ' ').toUpperCase() + '</strong>';
        row.appendChild(cohortCell);
        
        // Rate cell
        var rateCell = document.createElement('td');
        rateCell.textContent = rate + '%';
        row.appendChild(rateCell);
        
        // Status cell
        var statusCell = document.createElement('td');
        statusCell.innerHTML = '<span class="label ' + statusClass + '">' + status + '</span>';
        row.appendChild(statusCell);
        
        tbody.appendChild(row);
    }
    
    table.appendChild(tbody);
    fragment.appendChild(table);
    
    // Clear and update the DOM
    var retentionTable = document.getElementById('retentionTable');
    retentionTable.innerHTML = '';
    retentionTable.appendChild(fragment);
}
</script>

<style>
/* Optimized styles */
#retentionChart {
    max-width: 100%;
    will-change: transform;
}

.table-responsive {
    overflow-x: auto;
    -webkit-overflow-scrolling: touch;
}

.label {
    display: inline-block;
    padding: 3px 8px;
    border-radius: 3px;
    font-size: 11px;
    font-weight: 600;
}

.label-success { background-color: #36c6d3; color: #fff; }
.label-warning { background-color: #ffb848; color: #fff; }
.label-info { background-color: #659be0; color: #fff; }
.label-danger { background-color: #ed6b75; color: #fff; }

.mt-20 { margin-top: 20px; }

/* Performance optimization */
.will-change-transform {
    will-change: transform;
}
</style>
@endpush