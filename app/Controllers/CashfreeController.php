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
    
        $appId     = getenv('CASHFREE_APP_ID');
        $secretKey = getenv('CASHFREE_SECRET_KEY');
        $mode = (int) env('CASHFREE_MODE');
        $environment = ($mode === 1) ? 'PRODUCTION' : 'SANDBOX';

    
        if (!in_array($environment, ['SANDBOX', 'PRODUCTION'], true)) {
            return $this->response->setJSON([
                'error' => true,
                'message' => 'Invalid CASHFREE_MODE in .env'
            ]);
        }
    
        $cashfree = new Cashfree(
            $environment,
            $appId,
            $secretKey,
            '',
            '',
            '',
            true,
            '2022-09-01'
        );
    
        $order_id = 'ORDER_' . time();
        $amount = number_format((float)$data['amount'], 2, '.', '');
    
        $order = new CreateOrderRequest();
        $order->setOrderId($order_id);
        $order->setOrderAmount($amount);
        $order->setOrderCurrency('INR');
    
        $customer = new CustomerDetails();
        $customer->setCustomerId('CUST_' . time());
        $customer->setCustomerName($data['name']);
        $customer->setCustomerPhone($data['mobile']);
        $customer->setCustomerEmail($data['email']);
    
        $order->setCustomerDetails($customer);
    
        $meta = new OrderMeta();
        $meta->setReturnUrl(base_url("payment-success?order_id={$order_id}"));
        $order->setOrderMeta($meta);
    
        try {
            $result = $cashfree->pGCreateOrder($order);
            $res = json_decode($result[0], true);
    
            return $this->response->setJSON([
                'status' => 'success',
                'session_id' => $res['payment_session_id'],
                'order_amount' => $res['order_amount'],
                'redirect_url' => site_url(
                    "cashfree/checkoutPage?session_id={$res['payment_session_id']}&order_amount={$res['order_amount']}"
                )
            ]);
        } catch (\Throwable $e) {
            return $this->response->setJSON([
                'error' => true,
                'message' => $e->getMessage()
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
        $appId     = getenv('CASHFREE_APP_ID');
        $secretKey = getenv('CASHFREE_SECRET_KEY');
        $mode = (int) env('CASHFREE_MODE');
        $environment = ($mode === 1) ? 'PRODUCTION' : 'SANDBOX';
        $orderId = $this->request->getGet('order_id');

        $cashfree = new Cashfree(
            $environment,
            $appId,
            $secretKey,
            '',
            '',
            '',
            true,
            '2022-09-01'
        );

        $baseUrl = ($environment === 'PRODUCTION')
            ? 'https://api.cashfree.com/pg/orders/'
            : 'https://sandbox.cashfree.com/pg/orders/';

        try {
            $result = $cashfree->PGFetchOrder($orderId);
            $response = json_decode($result[0], true);

            if (($response['order_status'] ?? '') !== 'PAID') {
                return view('payment_failure');
            }

            $curl = curl_init($baseUrl . $orderId . '/payments');
            curl_setopt_array($curl, [
                CURLOPT_RETURNTRANSFER => true,
                CURLOPT_HTTPHEADER => [
                    "x-client-id: $appId",
                    "x-client-secret: $secretKey",
                    "x-api-version: 2022-09-01",
                    "Content-Type: application/json"
                ],
            ]);

            $paymentResponse = curl_exec($curl);
            curl_close($curl);

            $paymentData = json_decode($paymentResponse, true);

            return view('payment_success', [
                'donationId' => $response['order_id'] ?? '',
                'customerDetails' => $response['customer_details'] ?? [],
                'paymentData' => $paymentData[0] ?? []
            ]);
        } catch (\Throwable $e) {
            return $this->response->setJSON([
                'error' => $e->getMessage()
            ]);
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
