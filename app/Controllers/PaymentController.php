<?php

namespace App\Controllers;

use CodeIgniter\Controller;
use CodeIgniter\HTTP\CURLRequest;

class PaymentController extends BaseController
{
    public function checkout()
    {
        return view('checkout');
    }

    public function createOrder()
    {
        $appId = getenv('CASHFREE_APP_ID');
        $secretKey = getenv('CASHFREE_SECRET_KEY');
        $orderId = 'ORDER' . time();
        $orderAmount = 100.00;
        $customerEmail = 'user@example.com';
        $customerPhone = '9999999999';

        $data = [
            "order_id" => $orderId,
            "order_amount" => $orderAmount,
            "order_currency" => "INR",
            "customer_details" => [
                "customer_id" => "12345",
                "customer_email" => $customerEmail,
                "customer_phone" => $customerPhone
            ],
            "order_meta" => [
                "return_url" => base_url("payment-success") . "?order_id={order_id}",
                "notify_url" => base_url("webhook-handler")
            ]
        ];

        // $headers = [
        //     "Content-Type: application/json",
        //     "x-api-version: 2022-09-01",
        //     "x-client-id: $appId",
        //     "x-client-secret: $secretKey"
        // ];

        $ch = curl_init('https://sandbox.cashfree.com/pg/orders');
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_POST, true);
        curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($data));
        curl_setopt($ch, CURLOPT_HTTPHEADER, [
            'Content-Type: application/json',
            'x-api-version: 2022-09-01',
            'x-client-id:' .$appId,
            'x-client-secret:' .$secretKey
        ]);
    
        $response = curl_exec($ch);
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        curl_close($ch);

        $responseData = json_decode($response, true);
//         echo '<pre>';
//   print_r($responseData);
//   echo '</pre>';
//  exit;
        if (isset($responseData['payments']['url'])) {
            return redirect()->to($responseData['payments']['url']);
        }
        return view('payment_failure', ['message' => 'Unable to create payment']);
    }

   
}
