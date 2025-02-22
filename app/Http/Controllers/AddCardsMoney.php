<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Auth;
use Validator;
use App\Models\User;
use App\Models\TransactionHistory;
use Illuminate\Support\Facades\Http;
use Illuminate\Http\Client\RequestException;

class AddCardsMoney extends Controller
{
    private $paystackToken;
    private $paystackApiUrl;

    private $maxRetries;
    private $retryDelay;  // in milliseconds

    // Get Paystack API credentials

    public function __construct(){

        $this->paystackToken = env('PAYSTACK_SECRET_KEY');
        $this->paystackApiUrl = env('PAYSTACK_API_URL');
        $this->maxRetries = 3;
        $this->retryDelay= 100;

        $this->middleware('auth:api', ['except'=>['login','register']]);
    }

    // new payment process starts here and below

    public function processPayment(Request $request)
    {
        // Validate request data
        $validator = validator($request->all(), [
            'name' => 'required',
            'amount' => 'required|numeric',
            'cvv' => 'required',
            'card_number' => 'required',
            'expiry_month' => 'required',
            'expiry_year' => 'required',
            'street' => 'required',
            'city' => 'required',
            'state' => 'required',
            'zipcode' => 'required',
            'card_pin' => 'required',
        ]);

        if ($validator->fails()) {
            return response()->json(['msg' => $validator->errors(), 'status' => 'error']);
        }

        $token = $this->paystackToken;
        $apiUrl = $this->paystackApiUrl;

        // Get authenticated user
        $user = Auth::user();

        // Prepare card details
        $cardDetails = [
            'email' => "ganiutoyeeb31@gmail.com",
            'amount' => $request->amount * 100, // Amount in kobo
            'card' => [
                'cvv' => $request->cvv,
                'number' => $request->card_number,
                'expiry_month' => $request->expiry_month,
                'expiry_year' => $request->expiry_year,
            ],
        ];

        // Make payment request to Paystack

        try{
            $paymentResponse =  retry($this->maxRetries, function () use ($token, $apiUrl, $cardDetails){
                return Http::withHeaders([
                    'Accept' => 'application/json',
                    'Content-Type' => 'application/json',
                ])->withToken($token)->post($apiUrl, $cardDetails);
            }, $this->retryDelay);
            
            if ($paymentResponse->successful()) {
                // Handle the Paystack response
                $responseBody = json_decode($paymentResponse->body(), true);
                if ($responseBody["data"]["status"] == "success" && $responseBody["status"] == "true") {
                    // means, card processed without otp 
                    // updateBalance
                    return $this->updateUserBalance($user, $request->amount/100, $paymentResponse);
                }elseif($responseBody["status"] == "1" && $responseBody["data"]["status"] =="send_address"){
                    // card processing with address
                    $addressInfo = [
                        'street' => $request->street,
                        'city' => $request->city,
                        'state' => $request->state,
                        'zipcode' => $request->zipcode,
                    ];
                    return $this->handleAddressResponse($user,$request->amount, $paymentResponse,$addressInfo);
                }

                elseif($responseBody["status"] == "1" && $responseBody["data"]["status"] =="send_pin"){

                    return $this->handlPinResponse($user, $request->amount, $paymentResponse, $request->card_pin);
                }

                else{
                    return $responseBody["status"]. $responseBody["data"]["status"];
                    // card want to process with otp, pin or other stuff
                    return $this->handlePaymentResponse($paymentResponse, $user, $request->amount);
                }
            } else {
                // Payment request failed
                $errMsg = json_decode($paymentResponse->body(),true);
                return response()->json(['msg' => $errMsg["data"]["message"]],400);
            }
        }catch (RequestException $e) {
            // Log or handle the exception
            return response()->json(['msg' => 'Failed after multiple retries.'],400);
        }
    }
    // This method handles send card pin response
    private function handlPinResponse($user, $amount, $paymentResponse, $pin)
    {
        $token = $this->paystackToken;
        $apiUrl = $this->paystackApiUrl."/submit_pin";
        $paymentReference= $paymentResponse["data"]["reference"];

        $fields = [
            'pin' => $pin,
            'reference' => $paymentReference
        ];

        try {
            $paymentResponse = Http::withHeaders([
                'Authorization' => "Bearer $token",
                'Cache-Control' => 'no-cache',
            ])->retry(3, 1000)->post($apiUrl, $fields);

            // Access the response body
            if($paymentResponse->successful()){

                $responseBody = json_decode($paymentResponse->body(), true);
                // If the payment process with pin only without sending otp

                if ($responseBody["data"]["status"] == "success" && $responseBody["status"] == "true") {
                    // means, card processed without otp 
                    // updateBalance
                    return $this->updateUserBalance($user, $amount/100, $paymentResponse);
                }
                // if the payment process and request for otp
                elseif($responseBody["status"] == "1" && $responseBody["data"]["status"] =="send_otp")
                {
                    // proceed to handle otp, must be a public methos
                    return response()->json(["msg" =>$responseBody["data"]["display_text"], "data" => $responseBody["data"]["reference"], "type" =>"submit_otp"],200);
                }else{
                    return response()->json(['msg' => "Transaction Failed"],400);
                }

            }else{
                // Payment request failed because of too many attempt
                $errMsg = json_decode($paymentResponse->body(),true);
                return response()->json(['msg' => $errMsg["data"]["message"]],400);
            }
        } catch (\Illuminate\Http\Client\RequestException $e) {
            // Handle request exception (e.g., log the error)
            return response()->json(['msg' => 'Request failed'], 400);
        } catch (\Exception $e) {
            // Handle other exceptions
            return response()->json(['msg' => 'Request failed'], 400);
        }

    }

    // process otp 
    public function handleOtp(Request $request)
    {
         // Validate request data
        $validator = validator($request->all(), [
            'otp' => 'required|numeric',
            'reference' => 'required',
        ]);

        if ($validator->fails()) {
            return response()->json(['msg' => $validator->errors(), 'status' => 'error']);
        }

        $token = $this->paystackToken;
        $apiUrl = $this->paystackApiUrl."/submit_otp";;

        // Get authenticated user
        $user = Auth::user();

        $headers = [
            "Authorization" => "Bearer $token",
            "Cache-Control" => "no-cache",
        ];
        $fields = [
            'otp' => $request->otp,
            'reference' => $request->reference
        ];
        try{

            $paymentResponse = Http::withHeaders([
                "Authorization" => "Bearer $token",
                "Cache-Control" => "no-cache",
            ])->post($apiUrl, $fields);
            if ($paymentResponse->successful()) {
                $amount = $paymentResponse["data"]["amount"]/100;
                return $this->updateUserBalanceAndCreateTransactionHistory($user, $amount, $paymentResponse);
            }else{
                return response()->json(['msg' => $paymentResponse->body()]);
            }
        } catch (\Exception $e) {
            // Handle the exception
            $errMsg = json_decode($e, true);

            // Log or handle other exceptions
             return response()->json(['msg' => "Something went wrong"]);
             // return response()->json(['msg' => $errMsg["data"]["message"]]);
        }
    }

    // This method handles address process
    private function handleAddressResponse($user, $aount, $paymentResponse, $addressInfo)
    {
        $token = $this->paystackToken;
        $apiUrl = $this->paystackApiUrl."/submit_address";
        $paymentReference= $paymentResponse["data"]["reference"];
        $fields = [
            "reference" => $paymentReference,
            "address" => $addressInfo["street"],
            "city" => $addressInfo["city"],
            "state" => $addressInfo["state"],
            "zip_code" => $addressInfo["zipcode"],
        ];


        try {
            return retry($this->maxRetries, function () use ($apiUrl, $fields, $token) {
                // process the send address
                $response = Http::withHeaders([
                    "Authorization" => "Bearer $token",
                    "Cache-Control" => "no-cache",
                ])->post($apiUrl, $fields);

                $result = json_decode($response->body(), true);

                if ($response->successful()) {
                    return response()->json(["msg" => $result["data"]["url"], "type" =>"submit_address"],200);
                }

                throw new RequestException($response);
            }, $this->retryDelay);
        } catch (RequestException $e) {
                // Handle 400 Bad Request (e.g., display or log the error message)
            $errMsg = json_decode($e->response->body(), true);

            // Log or handle other exceptions
             return response()->json(['msg' => $errMsg["data"]["message"]]);
        }
    }

    private function handlePaymentResponse($paymentResponse, $user, $amount)
    {
        // steps
        $paymentDataToJson = json_decode($paymentResponse->body());
        // 1. check the     
        if ($paymentDataToJson->status =="1" && $paymentDataToJson->data->status == "send_pin") {
            // Charge attempt requires additional information
            return "pin";
        } elseif ($paymentDataToJson->status =="true" && $paymentDataToJson->data == "s") {
            // Payment is successful, update user balance and create transaction history
            $this->updateUserBalance($user, $amount/100, $data['data']);
            return response()->json(['msg' => 'Payment successful', 'status' => 'success']);
        } else {
            // Payment failed
            return response()->json(['msg' => 'Payment ' . $paymentDataToJson->status, 'status' => 'error']);
        }
    }
    private function updateUserBalanceAndCreateTransactionHistory($user, $amount, $paymentData)
    {
        try{
            // Decode the paymentData
            $paymentDataToJson = json_decode($paymentData->body());
             // Update user balance
            $this->updateUserBalance($user, $amount, $paymentDataToJson);

             // Create transaction history
            $this->createTransactionHistory($user, $amount, $paymentDataToJson);
            return response()->json(['msg' => 'Payment processed successfully'],200);
        } catch (\Exception $e) {
            // Log the error or handle it as needed
            return response()->json(['error' => 'An error occurred during payment processing']);
        }
        
    }

    private function createTransactionHistory($user, $amount, $paymentDataToJson)
    {
         TransactionHistory::create([
            'user_id' => $user->id,
            'amount' => $amount,
            'fees' => $paymentDataToJson->data->fees/100,
            'status' => $paymentDataToJson->data->status,
            'reference' => $paymentDataToJson->data->reference,
            'gateway_response' => 'successful',
            'paid_at' => now(),
            'sender' => 'Charged from card',
            'receiver' => $user->username,
            'transaction_type' => 'cr',
            'details' => 'Received from card',
            'channel' => $paymentDataToJson->data->channel,
            'currency' => $paymentDataToJson->data->currency,
            'ip_address' => $paymentDataToJson->data->ip_address,
            'transaction_id' => $paymentDataToJson->data->id,
            'domain' => $paymentDataToJson->data->domain,
            'receipt_number' => $paymentDataToJson->data->receipt_number,
            'message' => $paymentDataToJson->data->message,
            'metadata' =>$paymentDataToJson->data->log,
            'log' => $paymentDataToJson->data->log,
            'authorization' =>  $paymentDataToJson->data->authorization,
            'customer'=>  $paymentDataToJson->data->customer,
            'plan' =>  $paymentDataToJson->data->plan,
            'split' => $paymentDataToJson->data->split,
            'order_id' => $paymentDataToJson->data->order_id,
            'transaction_date' => $paymentDataToJson->data->transaction_date,
            'plan_object' => $paymentDataToJson->data->plan_object,
            'subaccount' =>$paymentDataToJson->data->subaccount,
        ]);
    }

    private function updateUserBalance($user, $amount, $paymentDataToJson)
    {
        // Update user balance and create transaction history
        // Decode the paymentData
        $newBalance = $user->wallet_balance + $amount;
        $user->update([
            'wallet_balance' => $newBalance,
            'withdraw_status' => $this->calculateWithdrawStatus($user, $paymentDataToJson->data->currency),
        ]);


       
    }

    private function calculateWithdrawStatus($user, $currency)
    {
        // Logic to determine withdraw status based on user's country code and payment currency
        $default_status = $user->withdraw_status;
        $userCountryCode = $user->country_code;
        // Check if user's country code is a specific value
        if ($default_status == "1") {
            return "1";
        } else {
            // Return 0 if currency matches user's country code, otherwise return 1
            return ($currency == $userCountryCode) ? 0 : 1;
        }
    }

}
