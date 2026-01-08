<div class="section homeblogposts">
    <div class="container"> 
        <!-- title start -->
        <div class="titleTop">
            <h3>{{__('Latest blog articles')}}</h3>
        </div>
        <!-- title end -->

        <ul class="blogList row">
           @if(null!==($blogs))
                        <?php
                        $count = 1;
                        ?>
                        @foreach($blogs as $blog)
                        <?php
                        $cate_ids = explode(",", $blog->cate_id);
                        $data = DB::table('blog_categories')->whereIn('id', $cate_ids)->get();
                        $cate_array = array();
                        foreach ($data as $cat) {
                            $cate_array[] = "<a href='" . url('/blog/category/') . "/" . $cat->slug . "'>$cat->heading</a>";
                        }
                        ?>
                        <li class="col-lg-4">
                            <div class="bloginner">
                                <div class="postimg">
									@if(null!==($blog->image) && $blog->image!="")

									<img src="{{asset('uploads/blogs/'.$blog->image)}}"
                                        alt="{{$blog->heading}}">
									@else
									<img src="{{asset('images/blog/1.jpg')}}"
                                        alt="{{$blog->heading}}">
									@endif
								</div>

                                <div class="post-header">
                                <div class="postmeta"><i class="fas fa-calendar-alt"></i> {{$blog->updated_at->format('d-M-Y')}}</div>
                                    <h4><a href="{{route('blog-detail',$blog->slug)}}">{{$blog->heading}}</a></h4>
                                    
                                </div>
                               

                            </div>
                        </li>

                        
                        <?php $count++; ?>
                        @endforeach
                        @endif
        </ul>
        <!--view button-->
        <div class="viewallbtn mt-0"><a href="{{route('blogs')}}">{{__('View All Blog Posts')}}</a></div>
        <!--view button end--> 
    </div>
</div>