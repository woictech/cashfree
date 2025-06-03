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
        $create_orders_request = new CreateOrderRequest();
        $create_orders_request->setOrderId($order_id);
        $create_orders_request->setOrderAmount(100.00);
        $create_orders_request->setOrderCurrency("INR");

        $customer_details = new CustomerDetails();
        $customer_details->setCustomerId("CUSTOMER_" . time());
        $customer_details->setCustomerPhone("9999999999");
        $customer_details->setCustomerEmail("user@example.com");

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

            return $this->response->setJSON($response);
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

        try {
            $response = $cashfree->PGFetchOrder($orderId);

            return $this->response->setJSON($response);
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
}
