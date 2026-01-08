@extends('admin.layouts.admin_layout')
@section('content')
<div class="page-content-wrapper"> 
    <!-- BEGIN CONTENT BODY -->
    <div class="page-content"> 
      <!-- Main-body start -->
      <div class="main-body">
         <div class="page-wrapper">
            <!-- Page header start -->
            <div class="page-header">
               @if(session()->has('message.added'))
              <div class="alert alert-success alert-dismissible fade show" role="alert">
                <strong>{{__('Task Done!')}}</strong> {!! session('message.content') !!}
                <button type="button" class="close" data-dismiss="alert" aria-label="Close">
                  <span aria-hidden="true">&times;</span>
                </button>
              </div>
              @endif
               <div class="page-header-title">
                  <h4>{{$page->title}}</h4>
               </div>
               <div class="page-header-breadcrumb">
                  <ul class="breadcrumb-title">
                     <li class="breadcrumb-item">
                        <a href="{{url('/admin')}}">
                        Home
                        </a>
                     </li>
                    
                     <li class="breadcrumb-item">{{$page->title}}  {{__('Components')}}
                     </li>
                  </ul>
               </div>
            </div>
            <!-- Page header end -->
            <!-- Page body start -->
            @if(null!==($widgets))
            @foreach($widgets as $wid)
            <?php $widget_data = null; ?>
            <div class="page-body" id="widget_{{$wid->id}}" style="border:1px solid #eee; padding:35px; margin-bottom: 30px; background:#f5f5f5;">
               <div class="row">
                  <div class="col-sm-12">
                     <!-- Basic Form Inputs card start -->
                     <div class="card">
                      <div class="card-header">
                           <h4>{{$wid->title}} Widget</h4>
                        </div>
                        <hr>
                        <div class="card-block">
                          
                           <?php 

                              $widget_data = App\Models\WidgetsData::where('widget_id',$wid->id)->first()
                            ?>
                          @if(null!==($widget_data))
                          {!! Form::model($widget_data, array('method' => 'post', 'route' => array('admin.widget_data.store',$wid->id), 'class' => 'form', 'files'=>true)) !!}
                           
                          @else
                          {!! Form::open(array('method' => 'post', 'route' => array('admin.widget_data.store',$wid->id), 'class' => 'form', 'files'=>true)) !!}
                           
                          @endif
                           {!! Form::hidden('id', $wid->id) !!}
                           @include('admin.widgets_data.inc.form')
                           <div class="row">
                              <div class="col-md-5"></div>
                              <div class="col-md-4"><button type="submit" class="btn btn-primary">{{__('Update')}}</button></div>
                           </div>

                           {!! Form::close() !!}
                           
                        </div>

                     </div>
                  </div>
               </div>
            </div>            
            @endforeach
            @endif


            <!-- Page body end -->
         </div>
      </div>
   </div>
</div>
@endsection
@push('scripts')

@include('admin.widgets_data.widgetfiler')

@endpush