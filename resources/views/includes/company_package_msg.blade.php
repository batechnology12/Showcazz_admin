<div class="instoretxt">

<h3>{{__('Purchased Job Package Details')}}</h3>
<div class="table-responsive">
<table class="table table-bordered mb-0">
<thead class="table-dark">
    <tr>
      <th scope="col">{{__('Package Name')}}</th>
      <th scope="col">{{__('Price')}}</th>
      <th scope="col">{{__('Available quota')}}</th>
      <th scope="col">{{__('Purchased On')}}</th>
      <th scope="col">{{__('Package Expired')}}</th>
    </tr>
  </thead>
  <tbody>
    <tr>
      <th>{{$package->package_title}}</th>
      <td>{{ $siteSetting->default_currency_code }}{{$package->package_price}}</td>
      <td><strong>{{Auth::guard('company')->user()->availed_jobs_quota}}</strong> / <strong>{{Auth::guard('company')->user()->jobs_quota}}</strong></td>
      <td><strong>{{ Auth::guard('company')->user()->package_start_date }}</strong></td>
      <td><strong>{{ Auth::guard('company')->user()->package_end_date }}</strong></td>
    </tr>
  </tbody>
</table>
</div>
</div>
