@extends('layouts.app')

@section('content')

<!-- Header start -->

@include('includes.header')

<!-- Header end --> 

<!-- Inner Page Title start -->

<div class="frsktopbanner">

    <div class="container">

        <div class="row align-items-center frskinfo justify-content-center">

            <div class="col-lg-6">

                <h3>Hire globally, faster and smarter</h3>

                <p>Discover a new hiring approach. Connect with top talent worldwide and easily find qualified candidates who fit your needs.</p>

                <div class="viewallbtn">                    

                    @if(Auth::guard('company')->check())    

                    <a href="{{ route('post.job') }}">{{__('Post a Job')}}</a>

                    @else

                    <a href="{{url('company-login')}}">{{__('Post a Job')}}</a>

                    @endif

                    

                </div>

            </div>    

            <div class="col-lg-6">

            <div class="seekerimgtop"><img src="{{asset('/')}}images/for-employers.png" alt="" /></div>	

            



            </div>

        </div>





    </div>

</div>









<div class="section whywork">

    <div class="container">

            



            <div class="row align-items-center justify-content-center">

                <div class="col-lg-4">

                    <div class="titleTop text-start d-block">

                    <h3>Join the Global Movement - Trusted HR for Startups</h3>

                    <p>Join global companies in simplifying hiring. We handle international recruitment, payroll, and compliance, allowing you to grow your team with confidence and ease</p>

                    </div>



                    <div class="viewallbtn text-start">

                    @if(Auth::guard('company')->check())    

                    <a href="{{ route('post.job') }}">{{__('Post a Job')}}</a>

                    @else

                    <a href="{{url('company-login')}}">{{__('Post a Job')}}</a>

                    @endif

                    </div>



                </div>

                <div class="col-lg-6">

                    <div class="frempimgbx">

                    <img src="{{asset('/')}}images/hr-rec.jpg" alt="" />

                    </div>

                </div>

            </div>





            

     </div>

</div>











<div class="section nochargebx">

    <div class="container">

            



            <div class="row align-items-center">

            <div class="col-lg-6">

                    <div class="">

                    <img src="{{asset('/')}}images/nohidden-charge.jpg" alt="" />

                    </div>

                </div>

                <div class="col-lg-6">

                    <div class="ps-md-5">

                    <div class="titleTop text-center d-block">

                    <h3>Transparent Pricing, No Surprises</h3>

                 


<ul class="row mt-5">
    <li class="col-md-4">
    <div class="mb-2"><span class="material-symbols-outlined">workspace_premium</span></div>    
    <p>Enjoy premium tools at no extra cost</p>
    </li>

    <li class="col-md-4">
    <div class="mb-2"><span class="material-symbols-outlined">hub</span></div>    
    <p>Find and connect with top talent at no extra cost</p>
    </li>

    <li class="col-md-4">
    <div class="mb-2"><span class="material-symbols-outlined">article</span></div>
    <p>Your job posts reach the right audience at no additional cost</p>
    </li>
</ul>







                    </div>



                    <div class="viewallbtn">

                    @if(Auth::guard('company')->check())    

                    <a href="{{ route('post.job') }}">{{__('Post a Job')}}</a>

                    @else

                    <a href="{{url('company-login')}}">{{__('Post a Job')}}</a>

                    @endif

                    </div>

                    </div>



                </div>

               

            </div>





            

     </div>

</div>











<div class="section whywork">

    <div class="container">

            <div class="titleTop d-block">

               <h3>How it works</h3>

               <p>Our streamlined platform simplifies hiring, from creating your company profile to finding the perfect candidate, helping you connect with top talent easily</p>

            </div>



            <div class="row justify-content-center">

               <!-- Step 1 -->

               <div class="col-lg-3">

                  <div class="whworkbox">

                  <svg xmlns="http://www.w3.org/2000/svg" height="72px" viewBox="0 -960 960 960" width="72px" fill="#5f6368"><path d="M720-240q25 0 42.5-17.5T780-300q0-25-17.5-42.5T720-360q-25 0-42.5 17.5T660-300q0 25 17.5 42.5T720-240Zm0 120q32 0 57-14t42-39q-20-16-45.5-23.5T720-204q-28 0-53.5 7.5T621-173q17 25 42 39t57 14Zm-520 0q-33 0-56.5-23.5T120-200v-560q0-33 23.5-56.5T200-840h560q33 0 56.5 23.5T840-760v268q-19-9-39-15.5t-41-9.5v-243H200v560h242q3 22 9.5 42t15.5 38H200Zm0-120v40-560 243-3 280Zm80-40h163q3-21 9.5-41t14.5-39H280v80Zm0-160h244q32-30 71.5-50t84.5-27v-3H280v80Zm0-160h400v-80H280v80ZM720-40q-83 0-141.5-58.5T520-240q0-83 58.5-141.5T720-440q83 0 141.5 58.5T920-240q0 83-58.5 141.5T720-40Z"/></svg>



                     <div class="number"><span>01.</span></div>

                     <h3>Create company profile</h3>

                     <p>Showcase your mission, values, and culture. Let job seekers see what makes your workplace unique and why they should be excited to join</p>

                     

                  </div>

               </div>



               <!-- Step 2 -->

               <div class="col-lg-3">

                  <div class="whworkbox">

                  <svg xmlns="http://www.w3.org/2000/svg" height="72px" viewBox="0 -960 960 960" width="72px" fill="#5f6368"><path d="M240-80q-33 0-56.5-23.5T160-160v-640q0-33 23.5-56.5T240-880h320l240 240v240h-80v-200H520v-200H240v640h360v80H240Zm638 15L760-183v89h-80v-226h226v80h-90l118 118-56 57Zm-638-95v-640 640Z"/></svg>

                     <div class="number"><span>02.</span></div>

                     <h3>Post a job</h3>

                     <p>Share your open roles with our network. Create job posts with clear descriptions, requirements, and benefits to attract qualified candidates swiftly.</p>

                    

                  </div>

               </div>



               <!-- Step 3 -->

               <div class="col-lg-3">

                  <div class="whworkbox">

                  <svg xmlns="http://www.w3.org/2000/svg" height="72px" viewBox="0 -960 960 960" width="72px" fill="#5f6368"><path d="M80-160v-112q0-33 17-62t47-44q51-26 115-44t141-18q30 0 58.5 3t55.5 9l-70 70q-11-2-21.5-2H400q-71 0-127.5 17T180-306q-9 5-14.5 14t-5.5 20v32h250l80 80H80Zm542 16L484-282l56-56 82 82 202-202 56 56-258 258ZM400-480q-66 0-113-47t-47-113q0-66 47-113t113-47q66 0 113 47t47 113q0 66-47 113t-113 47Zm10 240Zm-10-320q33 0 56.5-23.5T480-640q0-33-23.5-56.5T400-720q-33 0-56.5 23.5T320-640q0 33 23.5 56.5T400-560Zm0-80Z"/></svg>

                     <div class="number"><span>03.</span></div>

                     <h3>Hire job seekers</h3>

                     <p>Review applications, communicate with candidates, and make informed hiring decisions effortlessly with our support</p>

                     

                  </div>

               </div>



            </div>





            <div class="viewallbtn">

                    @if(Auth::guard('company')->check())    

                    <a href="{{ route('post.job') }}">{{__('Post a Job')}}</a>

                    @else

                    <a href="{{url('company-login')}}">{{__('Post a Job')}}</a>

                    @endif

                </div>

     </div>

</div>











<div class="section txtsec1">

        <div class="container">

            <div class="dbtitle">

                <h3>Find employees and hire them anywhere</h3>                

             </div>



        

             <div class="txtdata">

                <div class="row justify-content-center">

                    <div class="col-lg-4">

                        <div class="frmbox">

                     <div class="secimg mb-3"><img src="{{asset('/')}}images/streamlined-sourcing.png" alt="" /></div>

                     <div class="subheading">Streamlined sourcing</div>

                        <p>Discover and connect with top talent worldwide. Our platform simplifies sourcing, helping you quickly find candidates that meet your unique needs, all in one place.</p>       

                        </div>

                    </div>

                    <div class="col-lg-4">

                    <div class="frmbox">

                        <div class="secimg mb-3"><img src="{{asset('/')}}images/instant-onboarding.png" alt="" /></div>

                        <div class="subheading">Instant onboarding</div>

                        <p>Get new hires up to speed quickly. Our onboarding tools ensure a smooth transition, making every team member feel welcomed, prepared, and ready to contribute from day one.</p> 

                        </div>

                    </div>



                    <div class="col-lg-4">

                        <div class="frmbox">

                        <div class="secimg mb-3"><img src="{{asset('/')}}images/seamless-management.png" alt="" /></div>

                        <div class="subheading">Seamless management</div>

                        <p>Effortlessly manage your team from anywhere with tools for performance tracking, schedule coordination, and real-time communication.</p> 

                        </div>

                    </div>

                   

                </div>

             </div>

 



        </div>

    </div>















<div class="section ctabg2">

         <div class="container">

            <h4>Simplified global hiring begins now</h4>

            <p>Sign up now and start attracting top talent globally. It's free!</p>

            <div class="viewallbtn">

            @if(Auth::guard('company')->check())    

                    <a href="{{ route('post.job') }}">{{__('Post a Job')}}</a>

                    @else

                    <a href="{{url('company-login')}}">{{__('Post a Job')}}</a>

                    @endif

            </div>

         </div>

      </div>



@include('includes.footer')

@endsection

