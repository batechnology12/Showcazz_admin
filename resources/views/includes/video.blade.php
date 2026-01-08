@if(isset($video))
<div class="videowraper section">
			
	<div class="container">

		<div class="vidover">
			<div class="titleTop">
				<h3>{{__('Our Office Tour')}}</h3>
			</div>        
			<p>{{$video->video_text}}</p>
		</div>

		<div class="ratio ratio-16x9 mt-5">
			<iframe src="{{$video->video_link}}" frameborder="0" allowfullscreen></iframe>
		</div>

	
	
	</div>       
     
</div>







@endif