<?php

namespace App\Services;

use App\Exceptions\CustomException;
use Exception;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use JsonException;
use phpDocumentor\Reflection\Types\This;
use RuntimeException;

class BasService
{
    private $mKey;
    private $clientId;
    private $clientSecret;
    private $appId;
    private $baseUrl;
    private $token;
    private $iv;
    public function __construct()
    {
        // Load your keys and fingerprint from config
        $this->mKey = config('services.bas_api.m_key');
        $this->iv = config('services.bas_api.m_iv');
        $this->clientId = config('services.bas_api.client_id');
        $this->clientSecret = config('services.bas_api.client_secret');
        $this->appId = config('services.bas_api.app_id');
        $this->baseUrl = config('services.bas_api.base_url');
        $this->token = $this->getTokenFromCache();  // Get token from cache
    }


    public function checkTransactionStatus($orderId)
    {
        $endpoint = '/api/v1/merchant/sdk-payment/get-transaction-status';
        $requestTimestamp = time() * 1000;

        $body = [
            'appId' => $this->appId,
            'orderId' => $orderId,
            'requestTimestamp' => $requestTimestamp
        ];
        $encodeBody = json_encode($body, JSON_THROW_ON_ERROR);

        $signature = $this->generateSignature($encodeBody);

        $data = [
            'head' => [
                'signature' => $signature,
                'requestTimestamp' => $requestTimestamp,
                'bodystring' => ''
            ],
            'body' => $body
        ];

        $response = $this->callApi($endpoint, $data);
        return $this->handleTransactionResponse($response);
    }
    // Function to initiate transaction

    /**
     * @throws JsonException
     * @throws Exception
     */
    public function initiateTransaction($amount, $currency, $orderId)
    {
        $endpoint = '/api/v1/merchant/sdk-payment/initiate-transaction';
        $requestTimestamp = time() * 1000;

        $body = [
            'amount' => ['value' => $amount, 'currency' => $currency],
            'ordertype' => 'PayBill',
            'orderId' => $orderId,
            'requestTimestamp' => $requestTimestamp,
            'appId' => $this->appId
        ];

        $signature = $this->generateSignature(json_encode($body, JSON_THROW_ON_ERROR));
        $data = ['head' => ['signature' => $signature, 'requestTimestamp' => $requestTimestamp], 'body' => $body];
        $response = $this->callApi($endpoint, $data);

        return $this->handleTransactionResponse($response);
    }

    /**
     * @throws Exception
     */
    private function handleTransactionResponse($response)
    {
        if ($response['status'] === 1 && $response['code'] === '1111') {
            $params = $response['body']['trxToken'] . $response['body']['trxStatus'] . $response['body']['order']['orderId'];
            if ($this->verifySignature($params, $response['head']['signature'])) {
                return $response;
            }
            return 'verifySignature failed';
        }
        if ($response['status'] === 0) {
            return $response;
        }

        throw new RuntimeException('Transaction failed: ' . $response);
    }
    // General function to call API
    public function callApi($endpoint, $data, $method = 'POST', $timeout = 30)
    {
        $this->ensureTokenIsValid(); // Ensure the token is valid

        try {
            $response = Http::withToken($this->token)
                ->timeout($timeout) // Set a timeout
                ->retry(2, 100) // Retry 3 times with a 100ms delay between attempts
                ->withHeaders([
                    'Content-Type' => 'application/json'
                ])
                ->$method($this->baseUrl . $endpoint, $data);

            if ($response->successful()) {
                return $response->json();
            } else {
                throw new Exception('API call failed: ' . $response->body());
            }
        } catch (Exception $e) {
            Log::error('Error making API call to ' . $endpoint . ': ' . $e->getMessage());
            throw new Exception('An error occurred while communicating with the API: ' . $e->getMessage());
        }
    }





    // Request a new token
    private function requestNewToken(): void
    {
        try {
            $response = Http::asForm()
                ->withHeaders([
                //    'Content-Type' => 'application/x-www-form-urlencoded'
                ])
                ->timeout(15)
                ->retry(1, 100) // Retry 3 times with a 100ms delay between attempts
                ->post( $this->baseUrl.'/api/v1/auth/token', [
                    'grant_type' => 'client_credentials',
                    'redirect_uri' =>  $this->baseUrl.'/api/v1/auth/callback',
                    'client_secret' => $this->clientSecret,
                    'client_id' => $this->clientId,
                ]);

            if ($response->successful()) {
                $data = $response->json();
                $this->token = $data['access_token'];
                $this->storeTokenInCache($this->token, $data['expires_in']);  // Store token with expiration
            } else {
                throw new \RuntimeException('Failed to obtain token: ' . $response->body());
            }
        } catch (Exception $e) {
            Log::error('Error obtaining token: ' . $e->getMessage());
            throw new CustomException('An error occurred while communicating with the BAS_API: ' . $e->getMessage());

        }
    }

    // Ensure the token is valid
    private function ensureTokenIsValid(): void
    {
        if (!$this->token || $this->isTokenExpired()) {
            $this->requestNewToken();
        }
    }

    // Check if the token is expired
    private function isTokenExpired(): bool
    {
        $expirationTime = cache('bas_api_token_expiration');

        // If the expiration time is not in the cache
        if (!$expirationTime) {
            return true;
        }

        // If the expiration time has already passed
        return now()->greaterThan($expirationTime);
    }

    // Store the token in cache
    private function storeTokenInCache($token, $expiresIn): void
    {
        cache(['bas_api_token' => $token], now()->addSeconds($expiresIn));
        cache(['bas_api_token_expiration' => now()->addSeconds($expiresIn)], now()->addSeconds($expiresIn));
    }

    // Get the token from cache
    private function getTokenFromCache()
    {
        return cache('bas_api_token');
    }











    public function generateSignature($params) {
        if(!is_array($params) && !is_string($params)){
            throw new Exception("string or array expected, ".gettype($params)." given");
        }
        if(is_array($params)){

            $params = $this->getStringByParams($params);
        }
        return $this->generateSignatureByString($params);
    }
    private function getStringByParams($params) {
        ksort($params);
        $params = array_map(function ($value){
            return ($value !== null && strtolower($value) !== "null") ? $value : "";
        }, $params);
        return implode("|", $params);
    }
    private function generateSignatureByString($params){
        $salt = $this->generateRandomString(4);
        return $this->calculateChecksum($params,$salt);
    }
    private function generateRandomString($length) {
        $data = "9876543210ZYXWVUTSRQPONMLKJIHGFEDCBAabcdefghijklmnopqrstuvwxyz!@#$&_";
        return substr(str_shuffle(str_repeat($data, $length)), 0, $length);

    }
     private function calculateChecksum($params, $salt){
        $hashString = $this->calculateHash($params, $salt);
        return $this->encrypt($hashString);
    }
    private function calculateHash($params, $salt) {
        return hash("sha256", $params . "|" . $salt) . $salt;
    }
    private function encrypt($input) {
        $key = html_entity_decode($this->mKey);
        $password = substr(hash('sha256', $key, true), 0, 32);
        $data = openssl_encrypt($input, 'aes-256-cbc', $password, OPENSSL_RAW_DATA, $this->iv);
        return base64_encode($data);
    }
    public function verifySignature($params, $checksum){
        if(!is_array($params) && !is_string($params)){
            throw new Exception("string or array expected, ".gettype($params)." given");
        }
        if(isset($params['CHECKSUMHASH'])){
            unset($params['CHECKSUMHASH']);
        }
        if(is_array($params)){
            $params = $this->getStringByParams($params);
        }
        return $this->verifySignatureByString($params,$checksum);
    }

    private function verifySignatureByString($params, $checksum)
    {
        $bas_hash = $this->decrypt($checksum);

        $salt = substr($bas_hash, -4);
        return $bas_hash === $this->calculateHash($params, $salt);
    }
    private function decrypt($encrypted) {
        $key = html_entity_decode($this->mKey);
        $password = substr(hash('sha256', $key, true), 0, 32);
        return openssl_decrypt($encrypted , "aes-256-cbc" ,$password,0, $this->iv);

    }

}


