<?php

/* * ******  Job Start ********** */
Route::get('list-jobs', array_merge(['uses' => 'Admin\JobController@indexJobs'], $all_users))->name('list.jobs');
Route::get('create-job', array_merge(['uses' => 'Admin\JobController@createJob'], $all_users))->name('create.job');
Route::post('store-job', array_merge(['uses' => 'Admin\JobController@storeJob'], $all_users))->name('store.job');
Route::get('edit-job/{id}', array_merge(['uses' => 'Admin\JobController@editJob'], $all_users))->name('edit.job');
Route::put('update-job/{id}', array_merge(['uses' => 'Admin\JobController@updateJob'], $all_users))->name('update.job');
Route::delete('delete-job', array_merge(['uses' => 'Admin\JobController@deleteJob'], $all_users))->name('delete.job');
Route::get('fetch-jobs', array_merge(['uses' => 'Admin\JobController@fetchJobsData'], $all_users))->name('fetch.data.jobs');
Route::put('make-active-job', array_merge(['uses' => 'Admin\JobController@makeActiveJob'], $all_users))->name('make.active.job');
Route::put('make-not-active-job', array_merge(['uses' => 'Admin\JobController@makeNotActiveJob'], $all_users))->name('make.not.active.job');
Route::put('make-featured-job', array_merge(['uses' => 'Admin\JobController@makeFeaturedJob'], $all_users))->name('make.featured.job');
Route::put('make-not-featured-job', array_merge(['uses' => 'Admin\JobController@makeNotFeaturedJob'], $all_users))->name('make.not.featured.job');
Route::get('delete-jobs', array_merge(['uses' => 'Admin\JobController@deleteJobs'], $all_users))->name('delete.jobs');

Route::get('list-beta-jobs', array_merge(['uses' => 'Admin\JobBController@indexJobs'], $all_users))->name('list.jobsB');
Route::get('create-jobB', array_merge(['uses' => 'Admin\JobBController@createJob'], $all_users))->name('create.jobB');
Route::post('store-jobB', array_merge(['uses' => 'Admin\JobBController@storeJob'], $all_users))->name('store.jobB');
Route::get('edit-jobB/{id}', array_merge(['uses' => 'Admin\JobBController@editJobB'], $all_users))->name('edit.jobB');
Route::put('update-jobB/{id}', array_merge(['uses' => 'Admin\JobBController@updateJobB'], $all_users))->name('update.jobB');
Route::delete('delete-jobB', array_merge(['uses' => 'Admin\JobBController@deleteJob'], $all_users))->name('delete.jobB');
Route::get('fetch-jobsB', array_merge(['uses' => 'Admin\JobBController@fetchJobsData'], $all_users))->name('fetch.data.jobsB');
Route::put('make-active-jobB', array_merge(['uses' => 'Admin\JobBController@makeActiveJob'], $all_users))->name('make.active.jobB');
Route::put('make-not-active-jobB', array_merge(['uses' => 'Admin\JobBController@makeNotActiveJob'], $all_users))->name('make.not.active.jobB');
Route::put('make-featured-jobB', array_merge(['uses' => 'Admin\JobBController@makeFeaturedJob'], $all_users))->name('make.featured.jobB');
Route::put('make-not-featured-jobB', array_merge(['uses' => 'Admin\JobBController@makeNotFeaturedJob'], $all_users))->name('make.not.featured.jobB');
Route::get('delete-jobsB', array_merge(['uses' => 'Admin\JobBController@deleteJobs'], $all_users))->name('delete.jobsB');
Route::get('move-to-active', array_merge(['uses' => 'Admin\JobBController@moveJobs'], $all_users))->name('move-to-active');

/* * ****** End Job ********** */