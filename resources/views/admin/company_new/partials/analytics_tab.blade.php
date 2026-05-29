{{-- resources/views/admin/company/partials/analytics_tab.blade.php --}}
<div class="row">
    <div class="col-md-6">
        <div class="portlet light bordered">
            <div class="portlet-title">
                <div class="caption">
                    <i class="fa fa-bar-chart font-dark"></i>
                    <span class="caption-subject font-dark sbold uppercase">Posts Analytics</span>
                </div>
            </div>
            <div class="portlet-body">
                <table class="table table-striped">
                    <tr>
                        <th>Total Posts:</th>
                        <td>{{ $analytics['posts']['total'] }}</td>
                    </tr>
                    <tr>
                        <th>Job Posts:</th>
                        <td>{{ $analytics['posts']['job_posts'] }}</td>
                    </tr>
                    <tr>
                        <th>Regular Posts:</th>
                        <td>{{ $analytics['posts']['regular_posts'] }}</td>
                    </tr>
                  
                </table>
            </div>
        </div>
    </div>
    
    <div class="col-md-6">
        <div class="portlet light bordered">
            <div class="portlet-title">
                <div class="caption">
                    <i class="fa fa-heart font-dark"></i>
                    <span class="caption-subject font-dark sbold uppercase">Engagement Analytics</span>
                </div>
            </div>
            <div class="portlet-body">
                <table class="table table-striped">
                    <tr>
                        <th>Total Likes:</th>
                        <td>{{ $analytics['engagement']['likes'] }}</td>
                    </tr>
                    <tr>
                        <th>Total Comments:</th>
                        <td>{{ $analytics['engagement']['comments'] }}</td>
                    </tr>
                    <tr>
                        <th>Total Shares:</th>
                        <td>{{ $analytics['engagement']['shares'] }}</td>
                    </tr>
                    <tr>
                        <th>Total Views:</th>
                        <td>{{ $analytics['engagement']['views'] }}</td>
                    </tr>
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
                    <i class="fa fa-users font-dark"></i>
                    <span class="caption-subject font-dark sbold uppercase">Follower Analytics</span>
                </div>
            </div>
            <div class="portlet-body">
                <table class="table table-striped">
                    <tr>
                        <th>Total Followers:</th>
                        <td>{{ $analytics['followers']['total'] }}</td>
                    </tr>
                   
                </table>
            </div>
        </div>
    </div>
    
    <div class="col-md-6">
        <div class="portlet light bordered">
            <div class="portlet-title">
                <div class="caption">
                    <i class="fa fa-briefcase font-dark"></i>
                    <span class="caption-subject font-dark sbold uppercase">Job Analytics</span>
                </div>
            </div>
            <div class="portlet-body">
                <table class="table table-striped">
                    <tr>
                        <th>Total Jobs:</th>
                        <td>{{ $analytics['jobs']['total'] }}</td>
                    </tr>
                    <tr>
                        <th>Active Jobs:</th>
                        <td>{{ $analytics['jobs']['active'] }}</td>
                    </tr>
                    <tr>
                        <th>Expired Jobs:</th>
                        <td>{{ $analytics['jobs']['expired'] }}</td>
                    </tr>
                    <tr>
                        <th>Total Applications:</th>
                        <td>{{ $analytics['applications'] }}</td>
                    </tr>
                </table>
            </div>
        </div>
    </div>
</div>