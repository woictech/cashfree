<?php

namespace App\Controllers;

use CodeIgniter\Controller;

class Home extends BaseController
{
    public function index(): string
    {
        return view('payment_gateway');
    }

    public function createOrder()
    {
        helper('cashfree_helper'); // for generating unique IDs if needed

        // Read from POST payload
        $request = service('request');
        $orderId = $request->getPost('order_id');
        $amount = $request->getPost('amount');
        $email = $request->getPost('email');
        $phone = $request->getPost('phone');

        $token = $this->getAccessToken();
        if (!$token) {
            return $this->response->setStatusCode(500)->setJSON(['error' => 'Token generation failed']);
        }

        $payload = [
            'order_id' => $orderId,
            'order_amount' => $amount,
            'order_currency' => 'INR',
            'customer_details' => [
                'customer_id' => uniqid('CUST_'),
                'customer_email' => $email,
                'customer_phone' => $phone,
            ],
            'order_meta' => [
                'return_url' => base_url('payment-response?order_id={order_id}'),
            ],
        ];

        $headers = [
            'Authorization: Bearer ' . $token,
            'Content-Type: application/json',
        ];

        $ch = curl_init(getenv('CASHFREE_BASE_URL') . '/v3/orders');
        curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($payload));
        curl_setopt($ch, CURLOPT_POST, true);
        $response = curl_exec($ch);
        curl_close($ch);

        return $this->response->setJSON(json_decode($response, true));
    }

    private function getAccessToken()
    {
        $url = getenv('CASHFREE_BASE_URL') . '/oauth/token';
        $headers = [
            'Content-Type: application/x-www-form-urlencoded',
        ];
        $data = http_build_query([
            'grant_type' => 'client_credentials',
            'client_id' => getenv('CASHFREE_CLIENT_ID'),
            'client_secret' => getenv('CASHFREE_CLIENT_SECRET'),
        ]);

        $ch = curl_init($url);
        curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_POSTFIELDS, $data);
        curl_setopt($ch, CURLOPT_POST, true);
        $response = curl_exec($ch);
        curl_close($ch);

        $res = json_decode($response, true);
        return $res['access_token'] ?? null;
    }
}
