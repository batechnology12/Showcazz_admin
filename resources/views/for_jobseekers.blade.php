@extends('layouts.app')

@section('content')

<!-- Header start -->

@include('includes.header')

<!-- Header end --> 



<div class="frsktopbanner">

    <div class="container">

        <div class="row align-items-center justify-content-center frskinfo">

            <div class="col-lg-6">

                <h3>Discover Your Ideal Job Board</h3>

<p>Unlock endless opportunities with trusted remote companies globally. Our platform streamlines your job search, making it efficient and stress-free, helping you find roles that match your career goals and lifestyle.</p>



                <div class="viewallbtn">

                    @if(Auth::check() && !Auth::guard('company')->check())

                    <a href="{{ route('my.profile') }}">Create a CV</a>

                    @else                    

                    <a href="{{route('login')}}">Create a CV</a>

                    @endif







                </div>

            </div>    

            <div class="col-lg-5">

            <div class="seekerimgtop"><img src="{{asset('/')}}images/for-seeker-top.png" alt="" /></div>	

            



            </div>

        </div>





    </div>

</div>




















<div class="section txtsec1 whitebg">

        <div class="container">

            <div class="dbtitle">

                <h3>Why Choose Us</h3>                

             </div>



        

             <div class="txtdata">

                <div class="row justify-content-center">

                    <div class="col-lg-4">

                     <div class="secimg mb-3"><img src="{{asset('/')}}images/its-free.png" alt="" /></div>

                     <div class="subheading">Job Search Simplified</div>

                        <p>Find the right fit with our user-friendly tools and resources, empowering you to search smarter, not harder.</p>       

                                        

                    </div>

                    <div class="col-lg-4">

                        <div class="secimg mb-3"><img src="{{asset('/')}}images/reelancer-cuate.png" alt="" /></div>

                        <div class="subheading">Only Verified Opportunities</div>

                        <p>We ensure every company and role is vetted, so you can trust the jobs you apply for are genuine and align with your career goals. Enjoy quality, curated opportunities without endless searching.</p> 

                       

                    </div>

                   

                </div>

             </div>

 



        </div>

    </div>















<div class="section ctabg">

         <div class="container wow bounceInUp animated" data-wow-duration="2s" style="visibility: visible; animation-duration: 2s; animation-name: bounceInUp;">

            <h4>Your dream job is just a click away</h4>

            <p>Begin your remote job search today! Create your profile and discover verified opportunities</p>

            <div class="viewallbtn">

            @if(Auth::check() && !Auth::guard('company')->check())

                    <a href="{{ route('my.profile') }}">Create a CV</a>

                    @else                    

                    <a href="{{route('login')}}">Create a CV</a>

                    @endif

            </div>

         </div>

      </div>











@include('includes.footer')

@endsection

