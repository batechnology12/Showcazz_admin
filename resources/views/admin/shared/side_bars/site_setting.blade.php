<li class="nav-item  "> <a href="javascript:;" class="nav-link nav-toggle"> <i class="icon-wrench"></i> <span class="title">Site Settings</span> <span class="arrow"></span> </a>
    <ul class="sub-menu">
        <li class="nav-item  "> <a href="{{ route('edit.site.setting') }}" class="nav-link "> <span class="title">Manage Site Settings</span> </a> </li>


    </ul>
</li>


<li class="nav-item  "> <a href="javascript:;" class="nav-link nav-toggle"> <i class="icon-wrench"></i> <span class="title">Static Content Widgets</span> <span class="arrow"></span> </a>
    <ul class="sub-menu">

<?php 

$w_pages = App\Models\WidgetPages::where('status','active')->get();

?>

@if(null!==($w_pages))


          @foreach($w_pages as $w_p)
          <li class="nav-item  "> <a href="{{route('admin.widgets_data',$w_p->slug)}}" class="nav-link "> <span class="title">{{$w_p->title}}</span> </a> </li>
          
        
          @endforeach 
  @endif

    </ul>
</li>
