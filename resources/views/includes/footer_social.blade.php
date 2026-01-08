@if ((string)$siteSetting->facebook_address !== '')
<a href="{{ $siteSetting->facebook_address }}" target="_blank"><i class="fab fa-facebook" aria-hidden="true"></i></a>
@endif
@if ((string)$siteSetting->pinterest_address !== '')
<a href="{{ $siteSetting->pinterest_address }}" target="_blank"><i class="fab fa-pinterest" aria-hidden="true"></i></a>
@endif
@if ((string)$siteSetting->twitter_address !== '')
<a href="{{ $siteSetting->twitter_address }}" target="_blank"><svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 512 512"><path d="M389.2 48h70.6L305.6 224.2 487 464H345L233.7 318.6 106.5 464H35.8L200.7 275.5 26.8 48H172.4L272.9 180.9 389.2 48zM364.4 421.8h39.1L151.1 88h-42L364.4 421.8z"></path></svg></a>
@endif
@if ((string)$siteSetting->instagram_address !== '')
<a href="{{ $siteSetting->instagram_address }}" target="_blank"><i class="fab fa-instagram" aria-hidden="true"></i></a>
@endif
@if ((string)$siteSetting->linkedin_address !== '')
<a href="{{ $siteSetting->linkedin_address }}" target="_blank"><i class="fab fa-linkedin" aria-hidden="true"></i></a>
@endif
@if ((string)$siteSetting->youtube_address !== '')
<a href="{{ $siteSetting->youtube_address }}" target="_blank"><i class="fab fa-youtube" aria-hidden="true"></i></a>
@endif
@if ((string)$siteSetting->tumblr_address !== '')
<a href="{{ $siteSetting->tumblr_address }}" target="_blank"><i class="fab fa-tumblr" aria-hidden="true"></i></a>
@endif
@if ((string)$siteSetting->flickr_address !== '')
<a href="{{ $siteSetting->flickr_address }}" target="_blank"><i class="fab fa-flickr" aria-hidden="true"></i></a>
@endif