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


use App\Admin;
use App\Role;
use DB;

Route::get('/debug-postgresql-roles', function() {
    try {
        // 1. First, check if roles table exists
        $tableExists = Schema::hasTable('roles');
        
        if (!$tableExists) {
            return response()->json([
                'success' => false,
                'message' => 'Roles table does not exist in database',
                'suggestion' => 'Run: php artisan make:migration create_roles_table --create=roles'
            ]);
        }
        
        // 2. Get all columns in roles table
        $columns = Schema::getColumnListing('roles');
        
        // 3. Check all data in roles table
        $allRoles = DB::table('roles')->get();
        
        // 4. Check admin user and their role
        $admin = Admin::where('email', 'admin@example.com')->first();
        
        // 5. Get PostgreSQL specific information
        $postgresInfo = DB::select("
            SELECT 
                table_name,
                column_name,
                data_type,
                is_nullable,
                column_default
            FROM information_schema.columns 
            WHERE table_name = 'roles' 
            ORDER BY ordinal_position
        ");
        
        // 6. Check constraints
        $constraints = DB::select("
            SELECT 
                tc.constraint_name,
                tc.constraint_type,
                kcu.column_name
            FROM information_schema.table_constraints tc
            JOIN information_schema.key_column_usage kcu 
                ON tc.constraint_name = kcu.constraint_name
            WHERE tc.table_name = 'roles'
        ");
        
        return response()->json([
            'success' => true,
            'database_info' => [
                'connection' => config('database.default'),
                'driver' => DB::connection()->getDriverName(),
                'database' => DB::connection()->getDatabaseName(),
            ],
            'roles_table' => [
                'exists' => $tableExists,
                'columns' => $columns,
                'postgres_columns' => $postgresInfo,
                'constraints' => $constraints,
                'total_records' => $allRoles->count(),
                'all_data' => $allRoles
            ],
            'admin_user' => $admin ? [
                'id' => $admin->id,
                'email' => $admin->email,
                'role_id' => $admin->role_id,
                'role_from_relation' => $admin->role ?? null
            ] : 'Admin user not found',
            'role_model' => [
                'class_exists' => class_exists('App\\Models\\Role') ? 'App\\Models\\Role' : 
                                 (class_exists('App\\Role') ? 'App\\Role' : 'Not found'),
                'fillable_properties' => class_exists('App\\Models\\Role') ? 
                    (new App\Models\Role())->getFillable() : 
                    (class_exists('App\\Role') ? (new App\Role())->getFillable() : [])
            ]
        ]);
        
    } catch (\Exception $e) {
        return response()->json([
            'success' => false,
            'message' => 'Error: ' . $e->getMessage(),
            'trace' => $e->getTraceAsString()
        ], 500);
    }
});

Route::get('/assign-admin-role', function() {
    try {
        // First, let's see what columns exist in roles table
        $columns = Schema::getColumnListing('roles');
        
        // Find or create admin role
        // Try different column names that might exist
        if (in_array('slug', $columns)) {
            $adminRole = Role::firstOrCreate(
                ['slug' => 'super-admin'], // Try slug
                [
                    'name' => 'Super Administrator',
                    'description' => 'Full system access'
                ]
            );
        } elseif (in_array('key', $columns)) {
            $adminRole = Role::firstOrCreate(
                ['key' => 'super_admin'], // Try key
                [
                    'name' => 'Super Administrator',
                    'description' => 'Full system access'
                ]
            );
        } elseif (in_array('name', $columns)) {
            // Just use name if that's the only identifier
            $adminRole = Role::firstOrCreate(
                ['name' => 'Super Administrator'],
                [
                    'description' => 'Full system access'
                ]
            );
        } else {
            // If no identifier columns, create with just name
            $adminRole = Role::create([
                'name' => 'Super Administrator',
                'description' => 'Full system access'
            ]);
        }
        
        // Find admin user
        $admin = Admin::where('email', 'admin@example.com')->first();
        
        if (!$admin) {
            return response()->json([
                'success' => false,
                'message' => 'Admin user not found!',
                'existing_admins' => Admin::all()->toArray()
            ]);
        }
        
        // Assign role
        $admin->role_id = $adminRole->id;
        $admin->save();
        
        return response()->json([
            'success' => true,
            'message' => 'Role assigned successfully',
            'debug' => [
                'roles_table_columns' => $columns,
                'role_created' => $adminRole->toArray()
            ],
            'data' => [
                'admin' => [
                    'id' => $admin->id,
                    'email' => $admin->email,
                    'role_id' => $admin->role_id,
                ],
                'role' => $adminRole->toArray()
            ]
        ]);
        
    } catch (\Exception $e) {
        return response()->json([
            'success' => false,
            'message' => 'Error: ' . $e->getMessage(),
            'trace' => config('app.debug') ? $e->getTraceAsString() : null
        ], 500);
    }
});

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
Route::get('/clear-cache', function () {
  $exitCode = Artisan::call('config:clear');
  $exitCode = Artisan::call('cache:clear');
  $exitCode = Artisan::call('config:cache');
  return 'DONE'; //Return anything
});
