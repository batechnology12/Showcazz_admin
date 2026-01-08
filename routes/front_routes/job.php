<?php
use App\Http\Controllers\Job\JobController;

Route::get('job/{slug}', 'Job\JobController@jobDetail')->name('job.detail');
Route::get('job-apply/{slug}', 'Job\JobController@jobApplyExt')->name('job.apply');
Route::post('job-apply/{slug}', 'Job\JobController@postJobApply')->name('post.job.apply');
Route::get('apply/{slug}', 'Job\JobController@applyJob')->name('apply.job');
Route::post('apply/{slug}', 'Job\JobController@postApplyJob')->name('post.apply.job');
Route::get('search-jobs', 'Job\JobController@jobsBySearch')->name('job.list');
Route::get('jobs/{category}', 'Job\JobController@jobsByCategory')->name('job.category');
Route::get('add-to-favourite-job/{job_slug}', 'Job\JobController@addToFavouriteJob')->name('add.to.favourite');
Route::get('remove-from-favourite-job/{job_slug}', 'Job\JobController@removeFromFavouriteJob')->name('remove.from.favourite');
Route::get('my-job-applications', 'Job\JobController@myJobApplications')->name('my.job.applications');
Route::get('list-rejected-users/{id}', 'Company\CompanyController@listRejectedUsers')->name('rejected-users');
Route::get('my-favourite-jobs', 'Job\JobController@myFavouriteJobs')->name('my.favourite.jobs');
Route::get('post-job', 'Job\JobPublishController@createFrontJob')->name('post.job');
Route::post('store-front-job', 'Job\JobPublishController@storeFrontJob')->name('store.front.job');
Route::get('edit-front-job/{id}', 'Job\JobPublishController@editFrontJob')->name('edit.front.job');
Route::put('update-front-job/{id}', 'Job\JobPublishController@updateFrontJob')->name('update.front.job');
Route::delete('delete-front-job', 'Job\JobPublishController@deleteJob')->name('delete.front.job');
Route::get('job-seekers', 'Job\JobSeekerController@jobSeekersBySearch')->name('job.seeker.list');

Route::get('job-applicants-change-status','Job\JobController@setStatus')->name('applicants.setStatus');

Route::post('submit-message', 'Job\SeekerSendController@submit_message')->name('submit-message');

Route::get('subscribe-alert', 'SubscriptionController@submitAlert')->name('subscribe.alert');
Route::post('custom-alert', 'SubscriptionController@submitCustomAlert')->name('custom.alert');

Route::middleware(['auth:company'])->group(function () {
    Route::get('/download-csv/{job}', [JobController::class, 'downloadCsv'])->name('job.downloadCsv');
});


