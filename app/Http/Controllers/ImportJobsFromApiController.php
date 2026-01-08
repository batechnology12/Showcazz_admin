<?php

namespace App\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Company;
use App\Job;
use App\FunctionalArea;
use App\Country;
use App\JobType;
use App\JobShift;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\DB;
use Exception;
use DateTime;
use DateInterval;

class ImportJobsFromApiController extends Controller
{
    public function fetchAndInsertJobs(Request $request)
    {
        // API credentials and headers
        $headers = [
            'Content-Type: application/json',
            'Authorization: Bearer eyJhbGciOiJFZERTQSIsImtpZCI6IjJlNDIxZTQ4LWQ4NDQtOTJjZC1iODJkLWNkMGUwN2JhYTZjNSJ9.eyJhdWQiOiJwaXJhdGVzdGVjaG5vbG9naWVzLmNvbSIsImV4cCI6MTc1NDg5ODQ2OCwiaWF0IjoxNzIzMzQxNTE2LCJpc3MiOiJodHRwczovL29wcy5jb3Jlc2lnbmFsLmNvbTo4MzAwL3YxL2lkZW50aXR5L29pZGMiLCJuYW1lc3BhY2UiOiJyb290IiwicHJlZmVycmVkX3VzZXJuYW1lIjoicGlyYXRlc3RlY2hub2xvZ2llcy5jb20iLCJzdWIiOiJmYTBjNGM5Yy1jMjFjLWZmZGYtYzBiOS00OGFlZDVhZjljMTYiLCJ1c2VyaW5mbyI6eyJzY29wZXMiOiJjZGFwaSJ9fQ.jwovmQAq2MORtyMDiPWhdvzgbe4V-Bk0ZnAZjXGRz5JopA1WGEGK7yMPJ8sBDicTSsx-WOp3_rnXpfqH_popDQ',
        ];

        // Define the API endpoint and data payload for the first request
        $apiUrl = 'https://api.coresignal.com/cdapi/v1/linkedin/job/search/filter';
        $data = [
            'created_at_gte' => '2024-08-09 00:00:00',
            'application_active' => false,
        ];

        try {
            // Make the first API request
            $searchResults = $this->makeApiRequest($apiUrl, 'POST', $headers, $data);

            // Process the first 1 job results
            if (is_array($searchResults) && count($searchResults) > 0) {
                $searchResults = array_slice($searchResults, 0, 100);

                foreach ($searchResults as $jobId) {
                    
                        // Define the second API endpoint for collecting job details
                        $jobDetailUrl = 'https://api.coresignal.com/cdapi/v1/linkedin/job/collect/' . $jobId;

                        // Make the second API request to get job details
                        $jobDetails = $this->makeApiRequest($jobDetailUrl, 'GET', $headers);

                        if (is_array($jobDetails) && isset($jobDetails['id'])) {
                            // Check if the job already exists in the database
                            $existingJob = Job::where('id', $jobDetails['id'])->first();
                            if ($existingJob) {
                                continue; // Skip if the job already exists
                            }

                            // Process company data if available
                            $companyData = null;
                            if (isset($jobDetails['company_id'])) {
                                $companyDetailUrl = 'https://api.coresignal.com/cdapi/v1/linkedin/company/collect/' . $jobDetails['company_id'];

                                // Make the API request to get company details
                                $companyDetails = $this->makeApiRequest($companyDetailUrl, 'GET', $headers);

                                if (is_array($companyDetails)) {
                                    $companyData = $this->prepareCompanyData($companyDetails);
                                }
                            }

                            // Bulk insert company and retrieve its ID
                            $check_company = Company::where('id',$jobDetails['company_id'])->first();
                            if(!$check_company && is_array($companyData)){
                                $inserted_company = Company::insert($companyData);
                            }
                            

                            // Prepare job data for insertion with the retrieved company ID
                            $jobData = $this->prepareJobData($jobDetails);

                            // Insert the job into the database
                            if(is_array($jobData)){
                                DB::table('jobs')->insert($jobData);
                            }
                        } else {
                            throw new Exception("Invalid job details format");
                        }
                   
                }

                return response()->json(['message' => 'Jobs and Companies inserted successfully!']);
            }

            return response()->json(['message' => 'No jobs found for the given criteria.'], 404);
        } catch (Exception $e) {
            // Handle any errors
            return response()->json(['error' => $e->getMessage()], 500);
        }
    }

    private function makeApiRequest($url, $method = 'GET', $headers = [], $data = null)
    {
        // Initialize a cURL session
        $curl = curl_init();

        // Set the cURL options
        $options = [
            CURLOPT_URL => $url,
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_CUSTOMREQUEST => strtoupper($method),
            CURLOPT_HTTPHEADER => $headers,
            CURLOPT_FOLLOWLOCATION => true,
        ];

        // If data is provided, attach it to the request
        if ($data !== null) {
            $options[CURLOPT_POSTFIELDS] = json_encode($data);
        }

        curl_setopt_array($curl, $options);

        // Execute the cURL request
        $response = curl_exec($curl);

        // Check for errors
        if ($response === false) {
            $error = curl_error($curl);
            curl_close($curl);
            throw new Exception("cURL Error: " . $error);
        }

        // Close the cURL session
        curl_close($curl);

        // Return the decoded JSON response
        return json_decode($response, true);
    }

    private function prepareJobData($jobDetails)
    {
        // Generate a unique slug for the job
        $jobSlug = $this->generateUniqueSlug(Job::class, $jobDetails['title'] ?? 'untitled');
        $expiryDate = (new DateTime())->add(new DateInterval('P1Y'))->format('Y-m-d');
        // Prepare job data for insertion
        return [
            'id' => $jobDetails['id'] ?? null,
            'company_id' => $jobDetails['company_id'] ?? null,
            'company_name' => $jobDetails['company_name'] ?? null,
            'title' => $jobDetails['title'] ?? null,
            'slug' => $jobSlug,
            'description' => preg_replace('/[^a-zA-Z0-9\s\.,?!]/', '', $jobDetails['description'] ?? null),
            'benefits' => null,
            'country_id' => $this->getOrCreateCountry($jobDetails['country'] ?? null),
            'state_id' => null,
            'city_id' => null,
            'is_freelance' => 0,
            'career_level_id' => null,
            'salary_from' => $jobDetails['salary'] ?? null,
            'salary_to' => $jobDetails['salary'] ?? null,
            'hide_salary' => 0,
            'salary_currency' => $jobDetails['salary_currency'] ?? null,
            'salary_period_id' => null,
            'functional_area_id' => $this->getOrCreateFunctionalArea($jobDetails['job_functions_collection'][0] ?? null),
            'job_type_id' => $this->getOrCreateJobType($jobDetails['type'] ?? null),
            'job_shift_id' => $this->getOrCreateJobShift($jobDetails['employment_type'] ?? null),
            'num_of_positions' => $jobDetails['applicants_count'] ?? null,
            'gender_id' => null,
            'expiry_date' => $expiryDate,
            'degree_level_id' => null,
            'job_experience_id' => null,
            'is_active' => 1,
            'is_featured' => 0,
            'search' => null,
            'reference' => null,
            'location' => $jobDetails['location'] ?? null,
            'logo' => $jobDetails['company']['logo_url'] ?? null,
            'type' => $jobDetails['type'] ?? null,
            'postal_code' => null,
            'job_advertiser' => $jobDetails['job_advertiser'] ?? null,
            'application_url' => $jobDetails['url'] ?? null,
            'json_object' => json_encode($jobDetails), // Serialize the entire job details array
        ];
    }

    private function prepareCompanyData($companyDetails)
    {
        // Generate a unique slug for the company
        $companySlug = $this->generateUniqueSlug(Company::class, $companyDetails['name'] ?? 'untitled');

        // Extract the domain from the company's website to form the email
        $website = $companyDetails['website'] ?? null;
        $email = $this->generateEmailFromWebsite($companyDetails['name'] ?? 'default', $website);

        // Prepare company data for insertion
        return [
            'id' => $companyDetails['id'] ?? null,
            'name' => $companyDetails['name'] ?? null,
            'slug' => $companySlug,
            'email' => $email,
            'password' => Hash::make('ekonty2626'),
            'website' => $website,
            'description' => preg_replace('/[^a-zA-Z0-9\s\.,?!]/', '', $companyDetails['description'] ?? null),
            'logo' => $companyDetails['logo_url'] ?? null,
            'country_id' => $this->getOrCreateCountry($companyDetails['country'] ?? null),
            'is_active' => 1,
            'is_featured' => 0,
            'created_at' => now(),
            'updated_at' => now(),
            'json_object' => json_encode($companyDetails), // Serialize the entire company details array
        ];
    }

    private function generateUniqueSlug($modelClass, $name)
    {
        $slug = Str::slug($name);
        $count = $modelClass::where('slug', 'like', "$slug%")->count();
        return $count ? "{$slug}-{$count}" : $slug;
    }

    private function generateEmailFromWebsite($companyName, $website)
    {
        // Remove spaces from the company name
        $companyName = str_replace(' ', '', strtolower($companyName));
    
        if (!$website) {
            return $companyName . '@ekonty.com';
        }
    
        // Parse the website to get the domain
        $domain = parse_url($website, PHP_URL_HOST);
    
        // Remove 'www.' if present
        if (strpos($domain, 'www.') === 0) {
            $domain = substr($domain, 4);
        }
    
        return $companyName . '@' . $domain;
    }

    private function getOrCreateCountry($countryName)
    {
        if ($countryName) {
            $country = Country::firstOrCreate(['country' => $countryName]);
            $country->country_id = $country->id;
            $country->update();
            return $country->id;
        }
        return null;
    }

    private function getOrCreateFunctionalArea($areaName)
    {
        if ($areaName) {
            $functionalArea = FunctionalArea::firstOrCreate(['functional_area' => $areaName]);
            $functionalArea->functional_area_id = $functionalArea->id;
            $functionalArea->update();
            return $functionalArea->id;
        }
        return null;
    }

    private function getOrCreateJobType($typeName)
    {
        if ($typeName) {
            $jobType = JobType::firstOrCreate(['job_type' => $typeName]);
            $jobType->job_type_id = $jobType->id;
            $jobType->update();
            return $jobType->id;
        }
        return null;
    }

    private function getOrCreateJobShift($shiftName)
    {
        if ($shiftName) {
            $jobShift = JobShift::firstOrCreate(['job_shift' => $shiftName]);
            $jobShift->job_shift_id = $jobShift->id;
            $jobShift->update();
            return $jobShift->id;
        }
        return null;
    }
}
