@extends('admin.layouts.email_template')
@section('content')
<table border="0" cellpadding="0" cellspacing="0" class="force-row" style="width: 100%;    border-bottom: solid 1px #ccc;">
    <tr>
        <td class="content-wrapper" style="padding-left:24px;padding-right:24px"><br>
            <div class="title" style="font-family: Helvetica, Arial, sans-serif; font-size: 18px;font-weight:400;color: #000;text-align: left;
                 padding-top: 20px;">Dear {{ isset($is_admin) && $is_admin ? ($full_name ?? 'Super Adm') : 'Super Adm' }} ,
                 
                 <?php if($company->is_active == 1 && $is_admin){ ?>
                 <p>Congratulations! Your account on {{ $siteSetting->site_name }} has been approved by our Super Administrator. You are now ready to begin posting job listings and connecting with potential candidates.  </p>
                 <?php  }elseif($is_admin  && $status !=1){ ?>
                 <p>Thank you for your interest in posting jobs on {{ $siteSetting->site_name }}. Unfortunately, your recent application was declined due to insufficient documentation to verify your business status.  </p>
                 <?php }else{ ?>
                 <p>{{$is_admin?'Thank you for submitting your application to register as an employer on {{ $siteSetting->site_name }}. We have received your request and attached documents. ':'We hope this message finds you well. We would like to bring to your attention that a new company or employer has recently registered on the {{ $siteSetting->site_name }}. Before they can begin posting job listings, we kindly request your approval.'}} </p>
                 <?php } ?>   
            <h3>
                Company/Employer Details:
            </h3> 
            <p><strong>Company Public link: {{route('company.detail', $company->slug)}}</strong></p>
            
            <?php if($company->is_active == 1 && $is_admin ){ ?>
            
            <h3>
                Getting Started:
                
            </h3>
            <ol>
                <li>Login to your {{ $siteSetting->site_name }} account.</li>
                <li>Navigate to the Jobs section.</li>
                <li>Click on "Post a New Job" to create your job listing. </li>
            </ol>
            
            <p>If you have any questions or need assistance, feel free to reach out to our support team with your {{ $siteSetting->site_name }} public profile url.<br>
Thank you for choosing {{ $siteSetting->site_name }} for your recruitment needs! </p>

            <?php  }elseif($is_admin  && $status !=1){ ?>
            
            <h3>
                Next Steps:
                
            </h3>
            <ol>
                <li>Review Your Documents: Please ensure that you provide clear and valid documents
that establish your company's legitimacy. </li>
                <li>Resubmit Your Application: Once you have the necessary documents, login to your
                {{ $siteSetting->site_name }} account and resubmit your application. </li>
                <li>Prompt Review: Our team will expedite the review process upon receiving your
updated application.  </li>
            </ol>
            
            <p>We appreciate your commitment to {{ $siteSetting->site_name }} Jobs and apologize for any inconvenience. If you
have any questions or need assistance, feel free to reach out to our support team. </p>
            
            <?php }else{ ?>
            @if(!$is_admin)
           
            <p><strong>Administrator's backend link: {{route('public.company', ['id' => $company->id])}}</strong></p>
            <p>Please login to your Super Administrator account on {{ $siteSetting->site_name }} and review the registration
details. If everything appears satisfactory, kindly approve their account to enable them to start
posting jobs. </p>
            @else
            <p>Our review team is currently assessing your submission. Please allow some time for the
verification process. You will receive a notification email once your account has been
verified.</p>
<p>If you have any questions or need further assistance, feel free to reach out to us </p>
            @endif
            
             <?php } ?>   
            
            
            <p style="margin-top:10px; font-family: Helvetica, Arial, sans-serif;font-size: 14px; color: #333;">                               
            Warm regards, <br>
Warm regards, <br>
{{ $siteSetting->site_name }} Team 
            </p>
           
                 
        </div>
                 
        </td>
    </tr>
</table>
@endsection