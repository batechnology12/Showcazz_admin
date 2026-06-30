<?php
/*
  |--------------------------------------------------------------------------
  | Web Routes
  |--------------------------------------------------------------------------
  |
  | Here is where you can register web routes for your application. These
  | routes are loaded by the RouteServiceProvider within a group which
  | contains the "web" middleware group. Now create something great!
  |
 */
use App\Http\Controllers\VerificationController;
use App\Http\Controllers\IndexController;
use App\Http\Controllers\ImportController;


Route::get('make-login/{guard}', 'IndexController@login')->name('make.login');
Route::get('company/email/verify', 'Company\CompanyVerificationController@show')->name('company.verification.notice');
Route::post('company/email/resend', 'Company\CompanyVerificationController@resend')->name('company.verification.resend');
$real_path = realpath(__DIR__) . DIRECTORY_SEPARATOR . 'front_routes' . DIRECTORY_SEPARATOR;
Route::get('jobs-autocomplete', function (\Illuminate\Http\Request $request) {
  $term = $request->get('term', '');
  // Fetch job titles from the 'jobs' table where the title matches the search term
  $results = DB::table('jobs')
      ->where('search', 'LIKE', '%' . $term . '%')
      ->pluck('title');
  // Return the results as a JSON response
  return response()->json($results);
})->name('jobs.autocomplete');
/* * ******** IndexController ************ */
Route::get('/', 'IndexController@index')->name('index');

Route::get('/phpinfo', function () {
    phpinfo();
});

Route::get('/check-time', 'IndexController@checkTime')->name('check-time');
Route::post('set-locale', 'IndexController@setLocale')->name('set.locale');


/* * ******** HomeController ************ */
Route::get('/email/verify', [VerificationController::class, 'notice'])->name('verification.notice');
Route::get('/email/verify/{id}/{hash}', [VerificationController::class, 'verify'])->name('verification.verify');
Route::post('/email/resend', [VerificationController::class, 'resend'])->name('verification.resend');

Route::middleware(['verified'])->group(function(){
    Route::get('home', 'HomeController@index')->name('home');
});


Route::get('all-categories', 'IndexController@allCategories')->name('all-categories');
/* * ******** TypeAheadController ******* */
Route::get('typeahead-currency_codes', 'TypeAheadController@typeAheadCurrencyCodes')->name('typeahead.currency_codes');
/* * ******** FaqController ******* */
Route::get('faq', 'FaqController@index')->name('faq');
/* * ******** CronController ******* */
Route::get('check-package-validity', 'CronController@checkPackageValidity');
/* * ******** Verification ******* */
Route::get('email-verification/error', 'Auth\RegisterController@getVerificationError')->name('email-verification.error');
Route::get('email-verification/check/{token}', 'Auth\RegisterController@getVerification')->name('email-verification.check');
Route::get('not-verified', 'Auth\RegisterController@notVerified')->name('not-verified');
Route::get('company-email-verification/error', 'Company\Auth\RegisterController@getVerificationError')->name('company.email-verification.error');
Route::get('company-email-verification/check/{token}', 'Company\Auth\RegisterController@getVerification')->name('company.email-verification.check');
/* * ***************************** */
// Sociallite Start
// OAuth Routes
Route::get('login/jobseeker/{provider}', 'Auth\LoginController@redirectToProvider');
Route::get('company-login', 'Auth\LoginController@companyLogin');
Route::get('company-register', 'Auth\LoginController@companyRegister');
Route::get('login/jobseeker/{provider}/callback', 'Auth\LoginController@handleProviderCallback');
Route::get('login/employer/{provider}', 'Company\Auth\LoginController@redirectToProvider');
Route::get('login/employer/{provider}/callback', 'Company\Auth\LoginController@handleProviderCallback');

Route::post('/import-records', [ImportController::class,'store'])->name('import');



// Sociallite End
/* * ***************************** */
Route::get('/for-employers', function () {return view('for_employers');});
Route::get('/for-jobseekers', function () {return view('for_jobseekers');});
Route::post('tinymce-image_upload-front', 'TinyMceController@uploadImage')->name('tinymce.image_upload.front');
Route::get('cronjob/send-alerts', 'AlertCronController@index')->name('send-alerts');
Route::post('subscribe-newsletter', 'SubscriptionController@getSubscription')->name('subscribe.newsletter');
/* * ******** OrderController ************ */
include_once($real_path . 'order.php');
/* * ******** CmsController ************ */
include_once($real_path . 'cms.php');
/* * ******** JobController ************ */
include_once($real_path . 'job.php');
/* * ******** ContactController ************ */
include_once($real_path . 'contact.php');
/* * ******** CompanyController ************ */
include_once($real_path . 'company.php');
/* * ******** AjaxController ************ */
include_once($real_path . 'ajax.php');
/* * ******** UserController ************ */
include_once($real_path . 'site_user.php');
/* * ******** User Auth ************ */
Auth::routes(['verify' => true]);
/* * ******** Company Auth ************ */
include_once($real_path . 'company_auth.php');
/* * ******** Admin Auth ************ */
include_once($real_path . 'admin_auth.php');
Route::get('blog', 'BlogController@index')->name('blogs');
Route::get('blog/search', 'BlogController@search')->name('blog-search');
Route::get('blog/{slug}', 'BlogController@details')->name('blog-detail');
Route::get('/blog/category/{blog}', 'BlogController@categories')->name('blog-category');
Route::get('/company-change-message-status', 'CompanyMessagesController@change_message_status')->name('company-change-message-status');
Route::get('/seeker-change-message-status', 'Job\SeekerSendController@change_message_status')->name('seeker-change-message-status');
Route::post('/api/users', 'AjaxController@create');
Route::get('/sitemap/companies', 'SitemapController@companies');
Route::get('job8', 'Job8Controller@job8')->name('job8');
Route::get('cronjob/delete-jobs', 'Job8Controller@delete_jobs')->name('delete-jobs');
Route::get('cronjob/amend-jobs', 'Job8Controller@amend_jobs')->name('amend-jobs');
Route::get('cronjob/set-count-industry', 'Job8Controller@set_count_industry')->name('set_count_industry');
Route::get('cronjob/set-total-count', 'Job8Controller@set_total_count')->name('set_total_count');
Route::get('cronjob/set-total-country', 'Job8Controller@set_count_country')->name('set_count_country');
Route::get('cronjob/set-total-companies', 'Job8Controller@set_count_company')->name('set_count_company');
Route::get('cronjob/set-total-jobType', 'Job8Controller@set_count_jobType')->name('set_count_jobType');
Route::get('cronjob/remove-duplicates', 'Job8Controller@remove_duplicates')->name('remove_duplicates');
Route::get('cronjob/set-count-company', 'Job8Controller@set_count_company')->name('set_count_company');
Route::get('cronjob/remove-duplicate-companies', 'Job8Controller@remove_duplicates')->name('remove-duplicate-companies');
Route::get('cronjob/recover-companies', 'Job8Controller@recover_companies')->name('recover-companies');
Route::get('cronjob/recover-jobs', 'Job8Controller@recover_jobs')->name('recover-jobs');
Route::get('set-location', 'Job8Controller@set_location')->name('set_location');
Route::post('ajax_upload_file', 'FilerController@upload')->name('filer.image-upload');
Route::post('ajax_remove_file', 'FilerController@fileDestroy')->name('filer.image-remove');
Route::get('/test-fcm-curl', function (\Illuminate\Http\Request $request) {
    $email = $request->get('email');
    if (!$email) return 'Please provide an email. Example: /test-fcm-curl?email=testing@gmail.com';

    $user = \App\User::where('email', $email)->first();
    if (!$user) return "User with email {$email} not found";

    $deviceToken = null;
    if (isset($user->firebase_token) && !empty($user->firebase_token)) {
        $deviceToken = $user->firebase_token;
    } else {
        return "No firebase_token found for {$email}";
    }

    try {
        $fcm = new \App\Services\FCMService();
        $reflection = new \ReflectionClass($fcm);
        $method = $reflection->getMethod('getAccessToken');
        $method->setAccessible(true);

        $serviceAccount = json_decode(file_get_contents(base_path('medical-app.json')), true);
        if (isset($serviceAccount['private_key'])) {
            $serviceAccount['private_key'] = str_replace('\n', "\n", $serviceAccount['private_key']);
        }
        dd($serviceAccount['private_key']);

        $token = $method->invoke($fcm, $serviceAccount);
        $projectId = $serviceAccount['project_id'];

        $curl = "curl --location 'https://fcm.googleapis.com/v1/projects/{$projectId}/messages:send' \\\n" .
                "--header 'Authorization: Bearer {$token}' \\\n" .
                "--header 'Content-Type: application/json' \\\n" .
                "--data '{\n" .
                "   \"message\": {\n" .
                "     \"token\": \"{$deviceToken}\",\n" .
                "     \"notification\": {\n" .
                "       \"title\": \"Live Test Notification for {$email}\",\n" .
                "       \"body\": \"This is a test message to verify push on live\"\n" .
                "     },\n" .
                "     \"data\": {\n" .
                "       \"click_action\": \"FLUTTER_NOTIFICATION_CLICK\"\n" .
                "     }\n" .
                "   }\n" .
                " }'";

        $debugInfo = "<h3>DEBUG INFO</h3>\n" .
                     "<b>Email:</b> {$email}<br>\n" .
                     "<b>FCM Device Token:</b> {$deviceToken}<br>\n" .
                     "<b>Project ID:</b> {$projectId}<br>\n" .
                     "<b>Service Account Email:</b> {$serviceAccount['client_email']}<br>\n" .
                     "<b>Generated Bearer Token:</b> " . substr($token, 0, 15) . "...(truncated)<br><br>\n";

        return $debugInfo . "<h3>CURL COMMAND</h3><pre>$curl</pre>";

    } catch (\Exception $e) {
        $debugInfo = "<h3>DEBUG INFO</h3>\n" .
                     "<b>Email:</b> " . ($email ?? 'N/A') . "<br>\n" .
                     "<b>FCM Device Token:</b> " . ($deviceToken ?? 'N/A') . "<br>\n" .
                     "<b>Project ID:</b> " . ($serviceAccount['project_id'] ?? 'N/A') . "<br>\n" .
                     "<b>Service Account Email:</b> " . ($serviceAccount['client_email'] ?? 'N/A') . "<br>\n";
        return "<h3 style='color:red;'>Error: " . $e->getMessage() . "</h3>" . $debugInfo;
    }
});

Route::get('/clear-cache', function () {
  $exitCode = Artisan::call('config:clear');
  $exitCode = Artisan::call('cache:clear');
  $exitCode = Artisan::call('config:cache');
  return 'DONE'; //Return anything
});
