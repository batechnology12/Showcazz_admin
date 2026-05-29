{{-- resources/views/admin/company_new/partials/post_types_tab.blade.php --}}
<div class="row">
    <div class="col-md-6">
        <div class="portlet light bordered">
            <div class="portlet-title">
                <div class="caption">
                    <i class="fa fa-tags font-dark"></i>
                    <span class="caption-subject font-dark sbold uppercase">Posts by Type</span>
                </div>
            </div>
            <div class="portlet-body">
                <table class="table table-striped table-bordered table-hover">
                    <thead>
                        <tr>
                            <th>Post Type</th>
                            <th>Count</th>
                            <th>Percentage</th>
                        </tr>
                    </thead>
                    <tbody>
                        @php $totalPosts = $postTypeSummary->sum('count'); @endphp
                        @forelse($postTypeSummary as $type)
                        <tr>
                            <td>{{ $type->name }}</td>
                            <td>{{ $type->count }}</td>
                            <td>
                                @if($totalPosts > 0)
                                    {{ round(($type->count / $totalPosts) * 100, 1) }}%
                                @else
                                    0%
                                @endif
                            </td>
                        </tr>
                        @empty
                        <tr>
                            <td colspan="3" class="text-center">No post types found</td>
                        </tr>
                        @endforelse
                    </tbody>
                    <tfoot>
                        <tr>
                            <th><strong>Total</strong></th>
                            <th><strong>{{ $totalPosts }}</strong></th>
                            <th><strong>100%</strong></th>
                        </tr>
                    </tfoot>
                </table>
            </div>
        </div>
    </div>

    <div class="col-md-6">
        <div class="portlet light bordered">
            <div class="portlet-title">
                <div class="caption">
                    <i class="fa fa-folder-open font-dark"></i>
                    <span class="caption-subject font-dark sbold uppercase">Posts by Category</span>
                </div>
            </div>
            <div class="portlet-body">
                <table class="table table-striped table-bordered table-hover">
                    <thead>
                        <tr>
                            <th>Category</th>
                            <th>Count</th>
                            <th>Percentage</th>
                        </tr>
                    </thead>
                    <tbody>
                        @php $totalCategories = $categorySummary->sum('count'); @endphp
                        @forelse($categorySummary as $category)
                        <tr>
                            <td>{{ $category->name }}</td>
                            <td>{{ $category->count }}</td>
                            <td>
                                @if($totalCategories > 0)
                                    {{ round(($category->count / $totalCategories) * 100, 1) }}%
                                @else
                                    0%
                                @endif
                            </td>
                        </tr>
                        @empty
                        <tr>
                            <td colspan="3" class="text-center">No categories found</td>
                        </tr>
                        @endforelse
                    </tbody>
                    <tfoot>
                        <tr>
                            <th><strong>Total</strong></th>
                            <th><strong>{{ $totalCategories }}</strong></th>
                            <th><strong>100%</strong></th>
                        </tr>
                    </tfoot>
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
                    <i class="fa fa-pie-chart font-dark"></i>
                    <span class="caption-subject font-dark sbold uppercase">Visual Distribution</span>
                </div>
            </div>
            <div class="portlet-body">
                <div class="row">
                    <div class="col-md-6">
                        <canvas id="postTypesChart" height="300"></canvas>
                    </div>
                    <div class="col-md-6">
                        <canvas id="categoriesChart" height="300"></canvas>
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
    // Post Types Chart
    var postTypesCtx = document.getElementById('postTypesChart').getContext('2d');
    var postTypesData = @json($postTypeSummary);
    
    new Chart(postTypesCtx, {
        type: 'pie',
        data: {
            labels: postTypesData.map(item => item.name),
            datasets: [{
                data: postTypesData.map(item => item.count),
                backgroundColor: [
                    '#36c6d3', '#5bc0de', '#f0ad4e', '#5cb85c', '#d9534f',
                    '#8775a7', '#F4D03F', '#e7505a', '#26c281', '#bfcad7'
                ],
                borderWidth: 1
            }]
        },
        options: {
            responsive: true,
            maintainAspectRatio: false,
            legend: {
                position: 'bottom'
            },
            title: {
                display: true,
                text: 'Posts by Type'
            }
        }
    });

    // Categories Chart
    var categoriesCtx = document.getElementById('categoriesChart').getContext('2d');
    var categoriesData = @json($categorySummary);
    
    new Chart(categoriesCtx, {
        type: 'doughnut',
        data: {
            labels: categoriesData.map(item => item.name),
            datasets: [{
                data: categoriesData.map(item => item.count),
                backgroundColor: [
                    '#36c6d3', '#5bc0de', '#f0ad4e', '#5cb85c', '#d9534f',
                    '#8775a7', '#F4D03F', '#e7505a', '#26c281', '#bfcad7'
                ],
                borderWidth: 1
            }]
        },
        options: {
            responsive: true,
            maintainAspectRatio: false,
            legend: {
                position: 'bottom'
            },
            title: {
                display: true,
                text: 'Posts by Category'
            }
        }
    });
});
</script>
@endpush