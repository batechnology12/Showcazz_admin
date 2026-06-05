<?php

namespace App\Services;

use GuzzleHttp\Client;
use Firebase\JWT\JWT;
use App\Models\User;
use Illuminate\Support\Facades\Log;
class FCMService
{
    protected $client;
    protected $projectId;
    protected $url;

    public function __construct()
    {
        $this->client = new Client();
        $this->projectId = env('FIREBASE_PROJECT_ID');  // Move the project ID to .env for better flexibility
        $this->url = "https://fcm.googleapis.com/v1/projects/{$this->projectId}/messages:send";
    }


    public function fcmSendNotification($token, $title, $body, $data = [])
    {
        try {
            $serviceAccount = json_decode(file_get_contents(base_path('medical-app.json')), true);
            
            // Fix literal newline characters if they were escaped during save
            if (isset($serviceAccount['private_key'])) {
                $serviceAccount['private_key'] = str_replace('\\n', "\n", $serviceAccount['private_key']);
            }
            
            $accessToken = $this->getAccessToken($serviceAccount);
    
            $notificationPayload = [
                'message' => [
                    'token' => $token,
                    'notification' => [
                        'title' => $title,
                        'body'  => $body,
                    ],
                    'data' => !empty($data) ? $data : new \stdClass(),
                ],
            ];
            // Log::info('🔥🔥🔥🔥🔥🔥FCM Request Payload', [
            //     'token' => $token,
            //     'title' => $title,
            //     'body'  => $body,
            //     'data'  => $data,
            //     'payload' => $notificationPayload,
            // ]);
            $response = $this->client->post($this->url, [
                'headers' => [
                    'Authorization' => "Bearer $accessToken",
                    'Content-Type'  => 'application/json',
                ],
                'json' => $notificationPayload,
            ]);
            $responseBody = json_decode($response->getBody(), true);


            // ---------------------------
            // Persist notification (non-blocking)
            // ---------------------------
            try {
                
                $dataArr = [];
                if (is_array($data)) {
                    $dataArr = $data;
                } elseif (is_object($data)) {
                    $dataArr = (array) $data;
                }
                $recipientType = null;
                $userId = null;
                
            
            } catch (\Exception $e) {
                Log::error('🔥🔥🔥FCM Notification DB Save Error', [
                    'message' => $e->getMessage(),
                    'token' => $token,
                    'title' => $title,
                    'body' => $body,
                    'data' => $data,
                ]);
            }



            // Log::info('🔥🔥🔥FCM Notification Response', $responseBody);
            return $responseBody;
        } catch (\Exception $e) {
            // Log::error('🔥🔥🔥FCM Notification Error', [
            //     'message' => $e->getMessage(),
            //     'token' => $token,
            //     'payload' => $notificationPayload
            // ]);
            return [
                'status' => false,
                'message' => 'FCM Notification Error: ' . $e->getMessage(),
            ];
        }
    }


    // Generate access token from service account credentials
    private function getAccessToken($serviceAccount)
    {
        $jwt = $this->generateJwt($serviceAccount);
        $response = $this->client->post('https://oauth2.googleapis.com/token', [
            'form_params' => [
                'grant_type' => 'urn:ietf:params:oauth:grant-type:jwt-bearer',
                'assertion'  => $jwt,
            ],
        ]);
        return json_decode($response->getBody()->getContents(), true)['access_token'];
    }

    // Generate JWT for authentication
    private function generateJwt($serviceAccount)
    {
        $nowSeconds = time();
        $payload = [
            'iss' => $serviceAccount['client_email'],
            'scope' => 'https://www.googleapis.com/auth/firebase.messaging',
            'aud' => 'https://oauth2.googleapis.com/token',
            'iat' => $nowSeconds,
            'exp' => $nowSeconds + 3600, // Expires in one hour
        ];

        return $this->encodeJwt($payload, $serviceAccount['private_key']);
    }

    // Encode JWT using service account private key
    private function encodeJwt($payload, $privateKey)
    {
        return JWT::encode($payload, $privateKey, 'RS256');
    }
}
