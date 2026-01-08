<div class="section greybg">
<div class="container">

<div class="titleTop text-center">
                <h3>{{__('Popular Industries')}}</h3>
            </div>


<div class="popularind">
            
            <ul class="hmindlist">					
                @if(isset($topIndustryIds) && count($topIndustryIds)) @foreach($topIndustryIds as $industry_id => $num_jobs)
                <?php
                $industry = App\Industry::where('industry_id', '=', $industry_id)->lang()->active()->first();
                ?> @if(null !== $industry)
                <li><a href="{{route('job.list', ['industry_id[]'=>$industry->industry_id])}}" title="{{$industry->industry}}">
                    {{$industry->industry}}
                    ({{$num_jobs}})
                </a></li>
                @endif @endforeach @endif
            </ul>
        </div>



</div>
</div>