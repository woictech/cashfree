<?php

namespace App\Controllers;

use App\Controllers\BaseController;
use Cashfree\Cashfree;
use Cashfree\Model\CreateOrderRequest;
use Cashfree\Model\CustomerDetails;
use Cashfree\Model\OrderMeta;


class CashfreeController extends BaseController
{
        public function createOrder()
        {   
            $data = $this->request->getPost();
            $type = $data['modalType'];
            $mobile = $data['mobile'];
            $email = $data['email'];
            $name = $data['name'];
            $appId = getenv('CASHFREE_APP_ID');
            $secretKey = getenv('CASHFREE_SECRET_KEY');
        
            $env = (getenv('CASHFREE_MODE') === 'PRODUCTION')
                    ? Cashfree::SANDBOX
                    : Cashfree::PRODUCTION; // 0 = SANDBOX, 1 = PRODUCTION
            $partnerApiKey = ''; // optional if not used
            $partnerMerchantId = ''; // optional if not used
            $clientSignature = ''; // optional if not used
            $enableErrorAnalytics = true;
            $x_api_version = '2022-09-01';
        
            $cashfree = new Cashfree(
                $env,
                $appId,
                $secretKey,
                $partnerApiKey,
                $partnerMerchantId,
                $clientSignature,
                $enableErrorAnalytics,
                $x_api_version
            );
            
            // ✅ Step 2: Create order request
            $order_id = "ORDER_" . time();
            $amount = number_format((float)$data['amount'], 2, '.', '');
            $create_orders_request = new CreateOrderRequest();
            $create_orders_request->setOrderId($order_id);
            $create_orders_request->setOrderAmount($amount);
            $create_orders_request->setOrderCurrency("INR");

            $customer_details = new CustomerDetails();
            $prefix = ($type === 'UD') ? 'UD_' : 'DD_';
            $customer_details->setCustomerId($prefix . time());
            $customer_details->setCustomerName($name);
            $customer_details->setCustomerPhone($mobile);
            $customer_details->setCustomerEmail($email);

            $create_orders_request->setCustomerDetails($customer_details);
            // ✅ Add OrderMeta here
            $returnUrl = base_url("payment-success?order_id={$order_id}");
            $notifyUrl = base_url('payment-webhook');
            $orderMeta = new OrderMeta();
            $orderMeta->setReturnUrl($returnUrl);
            $create_orders_request->setOrderMeta($orderMeta); 

            try {
                $result = $cashfree->pGCreateOrder($create_orders_request); 
                $res = json_decode($result[0]);
                $sessionId = $res->payment_session_id;
                $orderAmount = $res->order_amount;
                helper('cashfree_helper');
                if ($type === 'UD') {
                    $userId = $data['user_id'];
                    $response = curlPost(getenv('NGO_API_BASE_URL') . '/user-donation/add', [
                        'user_id' => $userId,
                        'mode'    => $data['mode'] ?? null,
                        'amount'  => $amount,
                    ]);
                    $refData = json_decode($response, true);
                    if (is_null($refData) || !isset($refData['data']['id'])) {
                        return $this->response->setJSON([
                            'status' => 'failure',
                            'msg' => 'Failed to create userDonation data',
                        ]);
                    }
                    $referenceId = $refData['data']['id'];
                    $modelType = "UD-{$referenceId}";
            
                } elseif ($type === 'DD') {
                    $paymentImage = $this->request->getFile('payment_image');
                    $profileImage = $this->request->getFile('profile_image');
                    $response = curlPost(getenv('NGO_API_BASE_URL') . '/donation/createdonation', [
                        'mode'   => $data['mode'] ?? null,
                        'amount' => $amount,
                        'name' => $data['name'],
                        'mobile_no' => $mobile,
                        'email' => $email,
                        'pan_card_no' =>$data['pan_no']?? null,
                        'address' => $data['address'],
                        'amount' =>$amount,
                        'payment_image' => $paymentImage,
                        'image' => $profileImage
                    ]);
                    $refData = json_decode($response, true);
                    $referenceId = $data['donationId'];
                    $modelType = "DD-{$referenceId}";
                }
            
                if ($referenceId && $modelType) {
                    $transResponse = curlPost(getenv('NGO_API_BASE_URL') . '/transaction/add', [
                        'amount'    => $amount,
                        'orderId'   => $order_id,
                        'type'      => $modelType
                    ]);
                    $transRefData = json_decode($transResponse, true);
                }
                // return redirect()->to(site_url("cashfree/checkoutPage?session_id={$sessionId}&order_amount={$orderAmount}"));
                return $this->response->setJSON([
                    'status' => 'success',
                    'session_id' => $sessionId,
                    'order_amount' => $orderAmount,
                    'referenceId' => $referenceId,
                    'redirect_url' => site_url("cashfree/checkoutPage?session_id={$sessionId}&order_amount={$orderAmount}")
                ]);
                // return $this->response->setJSON($res);
            } catch (\Exception $e) {
                return $this->response->setJSON([
                    'error' => true,
                    'message' => 'Exception when calling PGCreateOrder',
                    'exception_message' => $e->getMessage(),
                    'file' => $e->getFile(),
                    'line' => $e->getLine(),
                    'trace' => $e->getTraceAsString(),
                ]);
            }
        }

    public function checkoutPage()
    {
        $sessionId = $this->request->getGet('session_id');
        $orderAmount = $this->request->getGet('order_amount');
        return view('cashfree_checkout', [
            'session_id' => $sessionId,
            'order_amount' => $orderAmount,
        ]);
    }


    public function paymentSuccess()
    {
        $appId = getenv('CASHFREE_APP_ID');
        $secretKey = getenv('CASHFREE_SECRET_KEY');
        $env = (getenv('CASHFREE_MODE') === 'PRODUCTION')
                            ? Cashfree::SANDBOX
                            : Cashfree::PRODUCTION;
        $orderId = $this->request->getGet('order_id');

        // $env = 0; // 0 = SANDBOX, 1 = PRODUCTION
        $partnerApiKey = ''; // optional if not used
        $partnerMerchantId = ''; // optional if not used
        $clientSignature = ''; // optional if not used
        $enableErrorAnalytics = true;
        $x_api_version = '2022-09-01';
    
        $cashfree = new Cashfree(
            $env,
            $appId,
            $secretKey,
            $partnerApiKey,
            $partnerMerchantId,
            $clientSignature,
            $enableErrorAnalytics,
            $x_api_version
        );
        $baseUrl = ($env === 'PRODUCTION')
                    ?'https://api.cashfree.com/pg/orders/' 
                    : 'https://sandbox.cashfree.com/pg/orders/';
        try {
            $result = $cashfree->PGFetchOrder($orderId); // likely returns array with JSON in [0]
            $response = json_decode($result[0], true);

            if ($response['order_status']=='PAID')
            {   
                $curl = curl_init();
                curl_setopt_array($curl, [
                    CURLOPT_URL => $baseUrl . $orderId . '/payments',
                    CURLOPT_RETURNTRANSFER => true,
                    CURLOPT_HTTPHEADER => [
                        "x-client-id: $appId",
                        "x-client-secret: $secretKey",
                        "x-api-version: $x_api_version",
                        "Content-Type: application/json"
                    ],
                ]);
    
                $paymentResponse = curl_exec($curl);
                $httpCode = curl_getinfo($curl, CURLINFO_HTTP_CODE);
                curl_close($curl);
    
                $paymentData = json_decode($paymentResponse, true);    
                // Merge payment method into main response
                $response['payment_method'] = $paymentData;
                $cfOrderId = $response['cf_order_id'] ?? null;
                if ($cfOrderId) {
                    $externalUrl = getenv('NGO_API_BASE_URL') . '/transactionStatus/update';

                    $externalPayload = http_build_query([
                        'order_id'    => $orderId,
                        'cf_order_id' => $cfOrderId,
                    ]);

                    $externalCurl = curl_init($externalUrl);
                    curl_setopt_array($externalCurl, [
                        CURLOPT_RETURNTRANSFER => true,
                        CURLOPT_POST           => true,
                        CURLOPT_POSTFIELDS     => $externalPayload,
                        CURLOPT_HTTPHEADER     => [
                            'Content-Type: application/x-www-form-urlencoded'
                        ]
                    ]);

                    $externalResponse = curl_exec($externalCurl);
                    curl_close($externalCurl);
                }
                $paymentDetails = $response['payment_method'][0] ?? []; 
                return view('payment_success', [
                    'donationId'     => $response['order_id'] ?? '',
                    'customerDetails'=> $response['customer_details'] ?? [],
                    'paymentData'    => $paymentDetails
                ]);    
            }
            else{
                return view('payment_failure');
            }
            // return $this->response->setJSON($response);
        } catch (\Exception $e) {
            return $this->response->setJSON(['error' => $e->getMessage()]);
        }

    }

    public function offlinePayment()    
    {
        $name   = $this->request->getPost('name');
        $mobile = $this->request->getPost('mobile');
        $type = $this->request->getPost('type');
        $token = $this->request->getPost('token');
        if (empty($name) || empty($mobile)) {
            return redirect()->to('/error-page')->with('error', 'Name or Mobile is missing.');
        }
        // $apiUrl = getenv('NGO_API_BASE_URL') . '/donation/getdonations';
        $client = \Config\Services::curlrequest();
        $headers = [
            'Content-Type' => 'application/json'
        ];
        // Check if type starts with 'UD'
        if (strpos($type, 'UD') === 0) {
            $apiUrl = getenv('NGO_API_BASE_URL') . '/user-donation/getUserData';
            if (!$token) {
                return redirect()->to('/error-page')->with('error', 'Authorization token is missing in cookies.');
            }

            $headers['Authorization'] = 'Bearer ' . $token;
        } else {
            $apiUrl = getenv('NGO_API_BASE_URL') . '/donation/getdonations';
        }

        try {
            $response = $client->post($apiUrl, [
                'form_params' => [
                    'name'   => $name,
                    'mobile' => $mobile,
                    'type'   => $type
                ],
                'headers' => $headers,
            ]);

            $result = json_decode($response->getBody(), true);
            if ($result['status'] === 'success' && isset($result['data'])) {
                $donationData = $result['data'];
                $donationId = 'ORDER_DD' . $donationData['id']; // if needed
                $formattedId = 'DD-'.$donationData['id'];
                // Structure customerDetails and paymentData
                $customerDetails = [
                    'customer_id'    => $formattedId ?? '', 
                    'customer_name'  => $donationData['name']   ?? '',
                    'customer_email' => $donationData['email']  ?? '',
                    'customer_phone' => $donationData['mobile_no'] ?? '',
                ];

                $paymentData = [
                    'payment_time' => $donationData['created_at'] ?? '',
                    'order_amount' => $donationData['amount']     ?? '',
                ];
                // print_r("hi"); die();
                try {
                    return view('payment_success', [
                        'donationId'      => $donationId,
                        'customerDetails' => $customerDetails,
                        'paymentData'     => $paymentData,
                    ]);
                } catch (\Throwable $e) {
                    log_message('error', 'View rendering error: ' . $e->getMessage());
                    return redirect()->to('/error-page')->with('error', 'View rendering failed: ' . $e->getMessage());
                }
            }  else {
                return redirect()->to('/error-page')->with('error', 'Donation not found or invalid.');
            }

        } catch (\Exception $e) {
            log_message('error', 'OfflinePayment API error: ' . $e->getMessage());
            return redirect()->to('/error-page')->with('error', 'Unable to fetch receipt data.');
        }
    }


}
