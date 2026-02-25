<?php

$all_users = ['allowed_roles' => ['SUP_ADM', 'SUB_ADM', 'admin']];
$sup_only = ['allowed_roles' => ['SUP_ADM', 'admin']];


 Route::get('/home', 'Admin\AdminReportController@dashboard')->name('admin.home');
//Route::get('/home', array_merge(['uses' => 'Admin\HomeController@index'], $all_users))->name('admin.home');
Route::post('tinymce-image_upload', array_merge(['uses' => 'Admin\TinyMceController@uploadImage'], $all_users))->name('tinymce.image_upload');
/* * ********************************* */
$real_path = realpath(__DIR__) . DIRECTORY_SEPARATOR . 'admin_routes' . DIRECTORY_SEPARATOR;
include_once($real_path . 'admin_user.php');
include_once($real_path . 'site_user.php');
include_once($real_path . 'faq.php');
include_once($real_path . 'seo.php');
include_once($real_path . 'cms.php');
include_once($real_path . 'site_setting.php');
include_once($real_path . 'career_level.php');
include_once($real_path . 'country.php');
include_once($real_path . 'country_detail.php');
include_once($real_path . 'functional_area.php');
include_once($real_path . 'gender.php');
include_once($real_path . 'industry.php');
include_once($real_path . 'job_experience.php');
include_once($real_path . 'job_skill.php');
include_once($real_path . 'job_title.php');
include_once($real_path . 'job_type.php');
include_once($real_path . 'job_shift.php');
include_once($real_path . 'degree_level.php');
include_once($real_path . 'degree_type.php');
include_once($real_path . 'major_subject.php');
include_once($real_path . 'language.php');
include_once($real_path . 'state.php');
include_once($real_path . 'city.php');
include_once($real_path . 'result_type.php');
include_once($real_path . 'language_level.php');
include_once($real_path . 'marital_status.php');
include_once($real_path . 'company.php');
include_once($real_path . 'ownership_type.php');
include_once($real_path . 'job.php');
include_once($real_path . 'salary_period.php');
include_once($real_path . 'package.php');
include_once($real_path . 'video.php');
include_once($real_path . 'testimonial.php');
include_once($real_path . 'slider.php');



Route::group(['prefix' => 'admin_report', 'middleware' => ['auth:admin']], function() {
    
    // ============================================
    // DASHBOARD OVERVIEW
    // ============================================
    Route::get('dashboard', 'Admin\AdminReportController@dashboard')->name('admin.reports.dashboard');
    
    // ============================================
    // USER REPORTS
    // ============================================
    Route::get('users', 'Admin\AdminReportController@userReports')->name('admin.reports.users');
    Route::get('users/export', 'Admin\AdminReportController@exportUserReport')->name('admin.reports.users.export');
    Route::get('users/active', 'Admin\AdminReportController@activeUsers')->name('admin.reports.users.active');
    Route::get('users/inactive', 'Admin\AdminReportController@inactiveUsers')->name('admin.reports.users.inactive');
    Route::get('users/growth', 'Admin\AdminReportController@userGrowth')->name('admin.reports.users.growth');
    
    // ============================================
    // POST REPORTS
    // ============================================
    Route::get('posts', 'Admin\AdminReportController@postReports')->name('admin.reports.posts');
    Route::get('posts/export', 'Admin\AdminReportController@exportPostReport')->name('admin.reports.posts.export');
    Route::get('posts/popular', 'Admin\AdminReportController@popularPosts')->name('admin.reports.posts.popular');
    Route::get('posts/engagement', 'Admin\AdminReportController@postEngagement')->name('admin.reports.posts.engagement');
    Route::get('posts/categories', 'Admin\AdminReportController@postCategories')->name('admin.reports.posts.categories');
    
    // ============================================
    // JOB REPORTS
    // ============================================
    Route::get('jobs', 'Admin\AdminReportController@jobReports')->name('admin.reports.jobs');
    Route::get('jobs/export', 'Admin\AdminReportController@exportJobReport')->name('admin.reports.jobs.export');
    Route::get('jobs/posted', 'Admin\AdminReportController@jobsPosted')->name('admin.reports.jobs.posted');
    Route::get('jobs/applied', 'Admin\AdminReportController@jobsApplied')->name('admin.reports.jobs.applied');
    Route::get('jobs/by-type', 'Admin\AdminReportController@jobsByType')->name('admin.reports.jobs.by-type');
    Route::get('jobs/by-company', 'Admin\AdminReportController@jobsByCompany')->name('admin.reports.jobs.by-company');
    
    // ============================================
    // ENGAGEMENT REPORTS
    // ============================================
    Route::get('engagement', 'Admin\AdminReportController@engagementReports')->name('admin.reports.engagement');
    Route::get('engagement/export', 'Admin\AdminReportController@exportEngagementReport')->name('admin.reports.engagement.export');
    Route::get('engagement/time-spent', 'Admin\AdminReportController@timeSpent')->name('admin.reports.engagement.time-spent');
    Route::get('engagement/page-views', 'Admin\AdminReportController@pageViews')->name('admin.reports.engagement.page-views');
    Route::get('engagement/interactions', 'Admin\AdminReportController@interactions')->name('admin.reports.engagement.interactions');
    
    // ============================================
    // PLATFORM PERFORMANCE
    // ============================================
    Route::get('performance', 'Admin\AdminReportController@performance')->name('admin.reports.performance');
    Route::get('performance/growth', 'Admin\AdminReportController@growthMetrics')->name('admin.reports.performance.growth');
    
    // ============================================
    // EXPORT ALL REPORTS
    // ============================================
    Route::get('export/all', 'Admin\AdminReportController@exportAllReports')->name('admin.reports.export.all');
});


Route::group(['prefix' => 'admin_post', 'middleware' => ['auth:admin']], function() {
    
    // ============================================
    // POST MANAGEMENT ROUTES
    // ============================================
    
    // Post listing and details
    Route::get('posts', 'Admin\AdminPostController@index')->name('admin.posts.index');
    Route::get('posts/{id}', 'Admin\AdminPostController@show')->name('admin.posts.show');
    
    // Post status management
    Route::post('posts/{id}/approve', 'Admin\AdminPostController@approvePost')->name('admin.posts.approve');
    Route::post('posts/{id}/reject', 'Admin\AdminPostController@rejectPost')->name('admin.posts.reject');
    Route::post('posts/{id}/feature', 'Admin\AdminPostController@toggleFeature')->name('admin.posts.feature');
    Route::post('posts/bulk-action', 'Admin\AdminPostController@bulkAction')->name('admin.posts.bulk-action');
    
    // Post deletion
    Route::delete('posts/{id}', 'Admin\AdminPostController@destroy')->name('admin.posts.destroy');
    
    // View uploaded content
    Route::get('posts/{id}/media', 'Admin\AdminPostController@viewMedia')->name('admin.posts.media');
    
    // ============================================
    // JOB/OPPORTUNITY MANAGEMENT ROUTES
    // ============================================
    
    // Jobs listing (category_id = 5 - Mini Mission, 6 - Internship)
    Route::get('jobs', 'Admin\AdminJobController@index')->name('admin.jobs.index');
    Route::get('jobs/{id}', 'Admin\AdminJobController@show')->name('admin.jobs.show');
    
    // Job status management
    Route::post('jobs/{id}/approve', 'Admin\AdminJobController@approveJob')->name('admin.jobs.approve');
    Route::post('jobs/{id}/reject', 'Admin\AdminJobController@rejectJob')->name('admin.jobs.reject');
    Route::post('jobs/{id}/feature', 'Admin\AdminJobController@toggleFeature')->name('admin.jobs.feature');
    
    // Track applications received per job
    Route::get('jobs/{id}/applications', 'Admin\AdminJobController@viewApplications')->name('admin.jobs.applications');
    Route::get('jobs/{id}/applications/export', 'Admin\AdminJobController@exportApplications')->name('admin.jobs.applications.export');
    
    // Filter by type
    Route::get('jobs/type/mini-mission', 'Admin\AdminJobController@miniMissions')->name('admin.jobs.mini-mission');
    Route::get('jobs/type/internship', 'Admin\AdminJobController@internships')->name('admin.jobs.internship');
});


//companymangement
Route::group(['prefix' => 'admin_comapny', 'middleware' => ['auth:admin']], function() {
    
    // ============================================
    // COMPANY MANAGEMENT ROUTES
    // ============================================
    
    // Company listing and details
    Route::get('companies', 'Admin\AdminCompanyController@index')->name('companies.index');
    Route::get('companies/{id}', 'Admin\AdminCompanyController@show')->name('companies.show');
    
    // Company AJAX operations
    Route::post('companies/{id}/update-status', 'Admin\AdminCompanyController@updateStatus')->name('companies.update-status');
    Route::post('companies/{id}/update-featured', 'Admin\AdminCompanyController@updateFeatured')->name('companies.update-featured');
    Route::post('companies/{id}/verify-document', 'Admin\AdminCompanyController@verifyDocument')->name('companies.verify-document');
    Route::post('companies/{id}/send-notification', 'Admin\AdminCompanyController@sendNotification')->name('companies.send-notification');
    Route::get('companies/{id}/export', 'Admin\AdminCompanyController@exportData')->name('companies.export');
    Route::delete('companies/{id}', 'Admin\AdminCompanyController@destroy')->name('companies.destroy');

    // ============================================
    // POST MANAGEMENT ROUTES (For Company Posts)
    // ============================================
    
    // Get all posts of a specific company (AJAX)
    Route::get('companies/{companyId}/posts', 'Admin\AdminCompanyController@getCompanyPosts')->name('companies.posts');
    
    // Post CRUD operations
    Route::get('posts/{id}', 'Admin\AdminCompanyController@showPost')->name('companies.post.show');
    Route::delete('posts/{id}', 'Admin\AdminCompanyController@deletePost')->name('companies.post.delete');
    Route::post('posts/bulk-delete', 'Admin\AdminCompanyController@bulkDeletePosts')->name('companies.posts.bulk-delete');
    Route::post('posts/{id}/toggle-status', 'Admin\AdminCompanyController@togglePostStatus')->name('companies.post.toggle-status');

    // ============================================
    // COMMENT MANAGEMENT ROUTES
    // ============================================
    
    Route::delete('comments/{id}', 'Admin\AdminCompanyController@deleteComment')->name('companies.comment.delete');
    Route::post('comments/{id}/toggle-status', 'Admin\AdminCompanyController@toggleCommentStatus')->name('companies.comment.toggle');
});


//user management 



Route::group(['prefix' => 'admin_user', 'middleware' => ['auth:admin']], function() {
    
    // ============================================
    // USER MANAGEMENT ROUTES
    // ============================================
    
    // User listing and details
    Route::get('users', 'Admin\AdminUserController@index')->name('users.index');
    Route::get('users/{id}', 'Admin\AdminUserController@show')->name('users.show');
    
    // User AJAX operations
    Route::post('users/{id}/update-status', 'Admin\AdminUserController@updateStatus')->name('users.update-status');
    Route::post('users/{id}/update-featured', 'Admin\AdminUserController@updateFeatured')->name('users.update-featured');
    Route::post('users/{id}/send-notification', 'Admin\AdminUserController@sendNotification')->name('users.send-notification');
    Route::get('users/{id}/export', 'Admin\AdminUserController@exportData')->name('users.export');
    Route::delete('users/{id}', 'Admin\AdminUserController@destroy')->name('users.destroy');

    // ============================================
    // USER POST MANAGEMENT ROUTES
    // ============================================
    
    // Get all posts of a specific user
    Route::get('users/{userId}/posts', 'Admin\AdminUserController@getUserPosts')->name('users.posts');
    
    // ============================================
    // USER CONNECTION MANAGEMENT ROUTES
    // ============================================
    
    Route::get('users/{userId}/followers', 'Admin\AdminUserController@getFollowers')->name('users.followers');
    Route::get('users/{userId}/following', 'Admin\AdminUserController@getFollowing')->name('users.following');
});

Route::group(['namespace' => 'Admin'], function () {
    Route::get('/blog_category', 'Blog_categoriesController@index');
    Route::post('/blog_category/create', 'Blog_categoriesController@create');
    Route::post('/blog_category', 'Blog_categoriesController@update');
    Route::delete('/blog_category/{blog_category}', 'Blog_categoriesController@destroy');
    Route::get('/blog_category/get_blog_category_by_id/{blog_category}', 'Blog_categoriesController@get_blog_category_by_id');

    Route::get('/blog', 'BlogsController@index')->name('blog');
    Route::get('/blog/add-new-blog', 'BlogsController@show_form')->name('add-new-blog');
    Route::post('/blog/create', 'BlogsController@create');
    Route::post('/blog/update', 'BlogsController@update');
    Route::delete('/blog/{blog}', 'BlogsController@destroy');
    Route::get('/blog/remove_blog_feature_image/{blog}', 'BlogsController@remove_blog_feature_image');
    Route::get('/blog/get_blog_by_id/{blog}', 'BlogsController@get_blog_by_id');
    Route::get('/blog/edit-blog/{blog}', 'BlogsController@get_blog')->name('edit-blog');



    /*Widget Page routes Start*/

    Route::get('/widget-pages', 'WidgetPagesController@index')->name('admin.widget_pages');

    Route::get('/add-widget-page', 'WidgetPagesController@add')->name('admin.widget_pages.add');

    Route::post('/store-widget-page', 'WidgetPagesController@store')->name('admin.widget_pages.store');

    Route::post('/update-widget-page', 'WidgetPagesController@update')->name('admin.widget_pages.update');

    Route::get('/edit-widget-page/{widget_page}', 'WidgetPagesController@edit')->name('admin.widget_pages.edit');

    Route::get('/delete-widget-page/{widget_page}', 'WidgetPagesController@destroy')->name('admin.widget_pages.delete');

    /*Widget Page routes End*/

    /*Widget routes Start*/

    Route::get('/widgets', 'WidgetsController@index')->name('admin.widgets');

    Route::get('/add-widget', 'WidgetsController@add')->name('admin.widgets.add');

    Route::post('/store-widget', 'WidgetsController@store')->name('admin.widgets.store');

    Route::post('/update-widget', 'WidgetsController@update')->name('admin.widgets.update');

    Route::get('/edit-widget/{widget}', 'WidgetsController@edit')->name('admin.widgets.edit');

    Route::get('/delete-widget/{widget}', 'WidgetsController@destroy')->name('admin.widgets.delete');

    /*Widget routes End*/

    /*Widget data routes Start*/

    Route::get('/page/{page}', 'WidgetDataController@index')->name('admin.widgets_data');


    Route::post('/store-widget-data/{id}', 'WidgetDataController@store')->name('admin.widget_data.store');

    Route::post('/update-widget-page', 'WidgetDataController@update')->name('admin.widget_pages.update');


    Route::get('/delete-widget-page/{widget_page}', 'WidgetPagesController@destroy')->name('admin.widget_pages.delete');

    /*Widget data routes End*/



});
