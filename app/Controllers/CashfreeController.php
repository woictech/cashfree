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
            // ✅ CORS HEADERS
            $allowedOrigin = getenv('NGO_BASE_URL');
            header("Access-Control-Allow-Origin: http://localhost:5173");
            // header("Access-Control-Allow-Origin: $allowedOrigin");
            header("Access-Control-Allow-Methods: POST, OPTIONS");
            header("Access-Control-Allow-Headers: Content-Type, Authorization");

            // ✅ Preflight OPTIONS request handler
            if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
                http_response_code(200);
                exit();
            }
            $data = $this->request->getPost();
            $type = $data['type']; // 'UD' or 'DD'
            $userId = $data['user_id'] ?? null; // Ensure you have this in POST
            $mobile = $data['mobile'];
            $email = $data['email'];
            $appId = getenv('CASHFREE_APP_ID');
            $secretKey = getenv('CASHFREE_SECRET_KEY');
        
            $environment = 0; // 0 = SANDBOX, 1 = PRODUCTION
            $partnerApiKey = ''; // optional if not used
            $partnerMerchantId = ''; // optional if not used
            $clientSignature = ''; // optional if not used
            $enableErrorAnalytics = true;
            $x_api_version = '2022-09-01';
        
            $cashfree = new Cashfree(
                $environment,
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
            $mobile = $data['mobile'];
            $email = $data['email'];
            $create_orders_request = new CreateOrderRequest();
            $create_orders_request->setOrderId($order_id);
            $create_orders_request->setOrderAmount($amount);
            $create_orders_request->setOrderCurrency("INR");

            $customer_details = new CustomerDetails();
            $customer_details->setCustomerId("CUSTOMER_" . time());
            $customer_details->setCustomerPhone($mobile);
            $customer_details->setCustomerEmail($email);

            $create_orders_request->setCustomerDetails($customer_details);
            // ✅ Add OrderMeta here
            $returnUrl = base_url("payment-success?order_id={$order_id}");
            $notifyUrl = base_url('payment-webhook');
            $orderMeta = new OrderMeta();
            $orderMeta->setReturnUrl($returnUrl);
            $orderMeta->setNotifyUrl("https://yourdomain.com/payment-webhook");
            $create_orders_request->setOrderMeta($orderMeta); 

            try {
                $result = $cashfree->pGCreateOrder($create_orders_request); 
                $res = json_decode($result[0]);
                $sessionId = $res->payment_session_id;
                $orderAmount = $res->order_amount;
                $client = \Config\Services::curlrequest();
                if ($type === 'UD') {
                    $response = $client->post(getenv('NGO_API_BASE_URL') . '/user-donations/add', [
                        'form_params' => [
                            'user_id' => $userId,
                            'mode'    => $data['mode'] ?? null,
                            'amount'  => $amount,
                        ]
                    ]);
                    $refData = json_decode($response->getBody(), true);
                    echo '<pre>';
                    print_r($refData);
                    echo '</pre>';
                    exit;
                    $referenceId = $refData['id'] ?? null;
                    $modelType = "UD-{$referenceId}";
                
                } elseif ($type === 'DD') {
                    $response = $client->post('https://external-url.com/api/donations', [
                        'form_params' => [
                            'mode'   => $data['mode'] ?? null,
                            'amount' => $amount,
                        ]
                    ]);
                    $refData = json_decode($response->getBody(), true);
                    $referenceId = $refData['id'] ?? null;
                    $modelType = "DD-{$referenceId}";
                }
                if ($referenceId && $modelType) {
                    $client->post(getenv('NGO_API_BASE_URL') . '/transaction/add', [
                        'form_params' => [
                            'amount'    => $amount,
                            'order_id'  => $referenceId,
                            'type'      => $modelType
                        ]
                    ]);
                }
                
                return redirect()->to(site_url("cashfree/checkoutPage?session_id={$sessionId}&order_amount={$orderAmount}"));

                // return $this->response->setJSON($res);
            } catch (\Exception $e) {
                return $this->response->setJSON([
                    'error' => true,
                    'message' => 'Exception when calling PGCreateOrder: ' . $e->getMessage()
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

    public function verifyOrder($order_id)
    {   

        $appId = getenv('CASHFREE_APP_ID');
        $secretKey = getenv('CASHFREE_SECRET_KEY');
        $orderId = $this->request->getGet('order_id');

        $environment = 0; // 0 = SANDBOX, 1 = PRODUCTION
        $partnerApiKey = ''; // optional if not used
        $partnerMerchantId = ''; // optional if not used
        $clientSignature = ''; // optional if not used
        $enableErrorAnalytics = true;
        $x_api_version = '2022-09-01';
    
        $cashfree = new Cashfree(
            $environment,
            $appId,
            $secretKey,
            $partnerApiKey,
            $partnerMerchantId,
            $clientSignature,
            $enableErrorAnalytics,
            $x_api_version
        );

        try {
            $response = $cashfree->PGFetchOrder($order_id);
            $adminData = $this->fetchAdminData();

            return $this->response->setJSON([
                'order' => $response,
                'admin' => $adminData
            ]);
        } catch (\Exception $e) {
            return $this->response->setJSON(['error' => $e->getMessage()]);
        }
    }

    public function paymentSuccess()
    {
        $appId = getenv('CASHFREE_APP_ID');
        $secretKey = getenv('CASHFREE_SECRET_KEY');
        $orderId = $this->request->getGet('order_id');

        $environment = 0; // 0 = SANDBOX, 1 = PRODUCTION
        $partnerApiKey = ''; // optional if not used
        $partnerMerchantId = ''; // optional if not used
        $clientSignature = ''; // optional if not used
        $enableErrorAnalytics = true;
        $x_api_version = '2022-09-01';
    
        $cashfree = new Cashfree(
            $environment,
            $appId,
            $secretKey,
            $partnerApiKey,
            $partnerMerchantId,
            $clientSignature,
            $enableErrorAnalytics,
            $x_api_version
        );
        $baseUrl = $environment === 1
                    ?'https://api.cashfree.com/pg/orders/' 
                    : 'https://sandbox.cashfree.com/pg/orders/';
        try {
            $result = $cashfree->PGFetchOrder($orderId); // likely returns array with JSON in [0]
            $response = json_decode($result[0], true);
            $adminData = $this->fetchAdminData();

           
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
                curl_close($curl);
    
                $paymentData = json_decode($paymentResponse, true);
    
                // Assume only one payment attempt (index 0)
                $paymentInstrument = $paymentData['payments'][0]['payment_instrument'] ?? [];
    
                // Merge payment method into main response
                $response['payment_method'] = $paymentInstrument;
    
                // Add admin data
                $data = array_merge($response, ['adminData' => $adminData['data']]);
    
                // return $this->response->setJSON($data);
    

                // $data = array_merge($response, ['adminData' => $adminData['data']]);
                // return $this->response->setJSON($data);
                return view('payment_success', $response);    
            }
            else{
                return view('payment_failure', $response );
            }
            // return $this->response->setJSON($response);
        } catch (\Exception $e) {
            return $this->response->setJSON(['error' => $e->getMessage()]);
        }
        // 🔄 Verify with Cashfree API or Webhook
        // return view('payment_success', ['order_id' => $orderId]);
    }

    public function paymentFailure()
    {
        return view('payment_failure', ['message' => 'Payment failed or canceled']);
    }


    private function fetchAdminData()
    {
        $url = getenv('NGO_API_BASE_URL') . '/admin/details';

        $curl = curl_init();
        curl_setopt_array($curl, [
            CURLOPT_URL => $url,
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_HTTPHEADER => [
                'Accept: application/json',
                // 'Authorization: Bearer your_token_if_required'
            ]
        ]);

        $response = curl_exec($curl);

        if (curl_errno($curl)) {
            curl_close($curl);
            return ['error' => 'Failed to fetch admin data'];
        }

        $httpCode = curl_getinfo($curl, CURLINFO_HTTP_CODE);
        curl_close($curl);

        if ($httpCode === 200) {
            return json_decode($response, true);
        } else {
            return ['error' => 'Invalid response from NGO API'];
        }
    }

}
